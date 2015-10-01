import sys, struct
from collections import OrderedDict

FMT_DOS_HDR = OrderedDict([
	('MagicNumber'             , None),
	('LastSize'                , None),
	('PagesInFile'             , None),
	('relocations'             , None),
	('HeaderSizeInParagraph'   , None),
	('MinExtraParagraphNeeded' , None),
	('MaxExtraParagraphNeeded' , None),
	('InitialSS'               , None),
	('InitialSP'               , None),
	('Checksum'                , None),
	('InitialIP'               , None),
	('InitialCS'               , None),
	('FileAddOfRelocTable'     , None),
	('OverlayNumber'           , None),
	('Reserved1'               , None),
	('OEMIdentifier'           , None),
	('OEMInformation'          , None),
	('Reserved2'               , None),
	('SignatureOffset'         , None),
])

FMT_DOS_STUB = OrderedDict([
	('Value', None),
])

FMT_PE_SIGNATURE = OrderedDict([
	('Value', None),
])

FMT_COFF_HDR = OrderedDict([
	('Machine'                 , None),
	('NumberOfSections'        , None),
	('TimeDateStamp'           , None),
	('PointerToSymbolTable'    , None),
	('NumberOfSymbols'         , None),
	('SizeOfOptionalHeader'    , None),
	('Characteristics'         , None),
])

FMT_OPT_HDR = OrderedDict([
	('Magic'                   , None),
	('MajorLinkerVersion'      , None),
	('MinorLinkerVersion'      , None),
	('SizeOfCode'              , None),
	('SizeOfInitializedData'   , None),
	('SizeOfUninitializedData' , None),
	('AddressOfEntryPoint'     , None),
	('BaseOfCode'              , None),
	('BaseOfData'              , None),
	('ImageBase'               , None),
	('SectionAlignment'        , None),
	('FileAlignment'           , None),
	('MajorOSVersion'          , None),
	('MinorOSVersion'          , None),
	('MajorImageVersion'       , None),
	('MinorImageVersion'       , None),
	('MajorSubsystemVersion'   , None),
	('MinorSubsystemVersion'   , None),
	('Win32VersionValue'       , None),
	('SizeOfImage'             , None),
	('SizeOfHeaders'           , None),
	('Checksum'                , None),
	('Subsystem'               , None),
	('DllCharacteristics'      , None),
	('SizeOfStackReserve'      , None),
	('SizeOfStackCommit'       , None),
	('SizeOfHeapReserve'       , None),
	('SizeOfHeapCommit'        , None),
	('LoaderFlags'             , None),
	('NumberOfRvaAndSizes'     , None),
])

FMT_DATADIRS = OrderedDict([
	('Export'        , {'rva': None, 'size': None}),
	('Import'        , {'rva': None, 'size': None}),
	('Resource'      , {'rva': None, 'size': None}),
	('Exception'     , {'rva': None, 'size': None}),
	('Security'      , {'rva': None, 'size': None}),
	('Relocation'    , {'rva': None, 'size': None}),
	('Debug'         , {'rva': None, 'size': None}),
	('Architecture'  , {'rva': None, 'size': None}),
	('Reserved1'     , {'rva': None, 'size': None}),
	('TLS'           , {'rva': None, 'size': None}),
	('Configuration' , {'rva': None, 'size': None}),
	('BoundImport'   , {'rva': None, 'size': None}),
	('IAT'           , {'rva': None, 'size': None}),
	('Delay'         , {'rva': None, 'size': None}),
	('MetaData'      , {'rva': None, 'size': None}),
	('Reserved2'     , {'rva': None, 'size': None}),
])

class PE():
	data = None
	cursor = 0

	def __init__(self):
		self.DosHdr      = FMT_DOS_HDR
		self.DosStub     = FMT_DOS_STUB
		self.PeSignature = FMT_PE_SIGNATURE
		self.CoffHdr     = FMT_COFF_HDR
		self.OptHdr      = FMT_OPT_HDR
		self.DataDirs    = FMT_DATADIRS
		self.SectHdr     = OrderedDict()
		self.Sections    = OrderedDict()

	def get_char(self):
		self.cursor += 1
		return struct.unpack('<c', self.data[self.cursor-1:self.cursor])[0]

	def get_word(self):
		self.cursor += 2
		return struct.unpack('<H', self.data[self.cursor-2:self.cursor])[0]

	def get_dword(self):
		self.cursor += 4
		return struct.unpack('<L', self.data[self.cursor-4:self.cursor])[0]

	def get_str(self, n):
		self.cursor += n
		return self.data[self.cursor-n:self.cursor]

	def parse(self, filename):
		self.data = open(filename, 'rb').read()
		
		# Dos Header
		self.DosHdr['MagicNumber']             = self.get_str(2)
		self.DosHdr['LastSize']                = self.get_word()
		self.DosHdr['PagesInFile']             = self.get_word()
		self.DosHdr['relocations']             = self.get_word()
		self.DosHdr['HeaderSizeInParagraph']   = self.get_word()
		self.DosHdr['MinExtraParagraphNeeded'] = self.get_word()
		self.DosHdr['MaxExtraParagraphNeeded'] = self.get_word()
		self.DosHdr['InitialSS']               = self.get_word()
		self.DosHdr['InitialSP']               = self.get_word()
		self.DosHdr['Checksum']                = self.get_word()
		self.DosHdr['InitialIP']               = self.get_word()
		self.DosHdr['InitialCS']               = self.get_word()
		self.DosHdr['FileAddOfRelocTable']     = self.get_word()
		self.DosHdr['OverlayNumber']           = self.get_word()
		self.DosHdr['Reserved1']               = self.get_str(8)
		self.DosHdr['OEMIdentifier']           = self.get_word()
		self.DosHdr['OEMInformation']          = self.get_word()
		self.DosHdr['Reserved2']               = self.get_str(20)
		self.DosHdr['SignatureOffset']         = self.get_dword()
		
		# Dos Stub
		self.DosStub['Value'] = self.get_str(self.DosHdr['SignatureOffset'] - self.cursor)
		
		# PE Signature
		self.PeSignature['Value'] = self.get_str(4)
		
		# Coff Header
		self.CoffHdr['Machine']                = self.get_word()
		self.CoffHdr['NumberOfSections']       = self.get_word()
		self.CoffHdr['TimeDateStamp']          = self.get_dword()
		self.CoffHdr['PointerToSymbolTable']   = self.get_dword()
		self.CoffHdr['NumberOfSymbols']        = self.get_dword()
		self.CoffHdr['SizeOfOptionalHeader']   = self.get_word()
		self.CoffHdr['Characteristics']        = self.get_word()
		
		# Optional Header: Standard Fields
		self.OptHdr['Magic']                   = self.get_str(2)
		self.OptHdr['MajorLinkerVersion']      = self.get_char()
		self.OptHdr['MinorLinkerVersion']      = self.get_char()
		self.OptHdr['SizeOfCode']              = self.get_dword()
		self.OptHdr['SizeOfInitializedData']   = self.get_dword()
		self.OptHdr['SizeOfUninitializedData'] = self.get_dword()
		self.OptHdr['AddressOfEntryPoint']     = self.get_dword()
		self.OptHdr['BaseOfCode']              = self.get_dword()
		self.OptHdr['BaseOfData']              = self.get_dword()
		
		# Optional Header: Windows-Specific Fields
		self.OptHdr['ImageBase']               = self.get_dword()
		self.OptHdr['SectionAlignment']        = self.get_dword()
		self.OptHdr['FileAlignment']           = self.get_dword()
		self.OptHdr['MajorOSVersion']          = self.get_word()
		self.OptHdr['MinorOSVersion']          = self.get_word()
		self.OptHdr['MajorImageVersion']       = self.get_word()
		self.OptHdr['MinorImageVersion']       = self.get_word()	
		self.OptHdr['MajorSubsystemVersion']   = self.get_word()
		self.OptHdr['MinorSubsystemVersion']   = self.get_word()
		self.OptHdr['Win32VersionValue']       = self.get_dword()
		self.OptHdr['SizeOfImage']             = self.get_dword()
		self.OptHdr['SizeOfHeaders']           = self.get_dword()
		self.OptHdr['Checksum']                = self.get_dword()
		self.OptHdr['Subsystem']               = self.get_word()
		self.OptHdr['DllCharacteristics']      = self.get_word()
		self.OptHdr['SizeOfStackReserve']      = self.get_dword()
		self.OptHdr['SizeOfStackCommit']       = self.get_dword()
		self.OptHdr['SizeOfHeapReserve']       = self.get_dword()
		self.OptHdr['SizeOfHeapCommit']        = self.get_dword()
		self.OptHdr['LoaderFlags']             = self.get_dword()
		self.OptHdr['NumberOfRvaAndSizes']     = self.get_dword()
		
		# Data Directories
		self.DataDirs['Export']                = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Import']                = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Resource']              = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Exception']             = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Security']              = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Relocation']            = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Debug']                 = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Architecture']          = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Reserved1']             = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['TLS']                   = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Configuration']         = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['BoundImport']           = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['IAT']                   = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Delay']                 = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['MetaData']              = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs['Reserved2']             = {'rva': self.get_dword(), 'size': self.get_dword()}
		
		# Sections Headers
		for i in range(self.CoffHdr['NumberOfSections']):

			self.SectHdr[i] = OrderedDict([
				('Name'                 , self.get_str(8)),
				('VirtualSize'          , self.get_dword()),
				('VirtualAddress'       , self.get_dword()),
				('SizeOfRawData'        , self.get_dword()),
				('PointerToRawData'     , self.get_dword()),
				('PointerToRelocations' , self.get_dword()),
				('PointerToLineNumbers' , self.get_dword()),
				('NumberOfRelocations'  , self.get_word()),
				('NumberOfLineNumbers'  , self.get_word()),
				('Characteristics'      , self.get_dword()),
			])

			# Sections Data
			startoff = self.SectHdr[i]['PointerToRawData']
			rawsize = self.SectHdr[i]['SizeOfRawData']
			self.Sections[i] = self.get_raw_data(startoff, rawsize)
	
	def get_raw_data(self, startoff, rawsize):
		return self.data[startoff:startoff+rawsize]
	
	def __repr__(self, val):
		if isinstance(val, int) and val <= 0xff:
			return val

		if isinstance(val, int):
			return hex(val)

		if isinstance(val, str):
			return repr(val)

		if isinstance(val, dict):
			ret = '{'
			for k, v in val.iteritems():
				ret += "'%s': %s, " % (k, repr(v))
			return ret[0:-2]+'}'
   
	def add_section(self, name, data, size=None):
		if size == None or size < len(data):
			size = len(data)

		# Rpad to desired section size
		data += "\x00" * (size - len(data))
		
		n = len(self.SectHdr)
		raw_offset = self.SectHdr[n-1]['PointerToRawData'] + self.SectHdr[n-1]['SizeOfRawData']
		virt_addr = size + self.OptHdr['SectionAlignment'] + self.SectHdr[n-1]['VirtualAddress']
		while virt_addr % self.OptHdr['SectionAlignment']:
			virt_addr += 1

		self.SectHdr[n] = OrderedDict([
			('Name'                 , name + "\x00" * (8 - len(name))), # Section name len must be 8),
			('VirtualSize'          , size),
			('VirtualAddress'       , virt_addr),
			('SizeOfRawData'        , size),
			('PointerToRawData'     , raw_offset),
			('PointerToRelocations' , 0),
			('PointerToLineNumbers' , 0),
			('NumberOfRelocations'  , 0),
			('NumberOfLineNumbers'  , 0),
			('Characteristics'      , int('E0000020', 16)),
		])


		self.Sections[n] = data
		
		self.CoffHdr['NumberOfSections'] += 1
		self.OptHdr['SizeOfImage']       += size
		self.update_cksum()
	
	def del_section(self, name=None, n=None):
		if isinstance(name, str):
			for i in self.SectHdr:
				if name + "\x00" * (8 - len(name)) == self.SectHdr[i]['Name']:
					n = i

		if n == None:
			return False
		
		if isinstance(self.SectHdr[n], OrderedDict):
			size = self.SectHdr[n]['SizeOfRawData']
			
			self.CoffHdr['NumberOfSections'] -= 1
			self.OptHdr['SizeOfImage']       -= size
			
			self.SectHdr.pop(n)
			self.Sections.pop(n)
			self.update_cksum()
	
	def update_cksum(self):
		_data = self.build()
		length = len(_data)
		
		# 24 = len(signature) + len(CoffHdr)
		# 64 = offset from top of OptHdr to OptHdr.Checksum
		checksum_offset = self.DosHdr['SignatureOffset'] + 24 + 64
		checksum = 0
		remainder = length % 4
		_data = _data + ( '\0' * ((4-remainder) * ( remainder != 0 )) )

		for i in range(length / 4):
			if i == checksum_offset / 4:
				continue
			dword = struct.unpack('I', _data[i*4 : i*4+4])[0]
			checksum = (checksum & 0xffffffff) + dword + (checksum>>32)
			if checksum > 2**32:
				checksum = (checksum & 0xffffffff) + (checksum >> 32)

		checksum = (checksum & 0xffff) + (checksum >> 16)
		checksum = (checksum) + (checksum >> 16)
		checksum = checksum & 0xffff

		self.OptHdr['Checksum'] = checksum + length
	
	def write(self, filename):
		open(filename, 'wb').write(self.build())
	
	def build(self):
		out  = str(self.DosHdr['MagicNumber'])
		out += struct.pack('<H', self.DosHdr['LastSize'])
		out += struct.pack('<H', self.DosHdr['PagesInFile'])
		out += struct.pack('<H', self.DosHdr['relocations'])
		out += struct.pack('<H', self.DosHdr['HeaderSizeInParagraph'])
		out += struct.pack('<H', self.DosHdr['MinExtraParagraphNeeded'])
		out += struct.pack('<H', self.DosHdr['MaxExtraParagraphNeeded'])
		out += struct.pack('<H', self.DosHdr['InitialSS'])
		out += struct.pack('<H', self.DosHdr['InitialSP'])
		out += struct.pack('<H', self.DosHdr['Checksum'])
		out += struct.pack('<H', self.DosHdr['InitialIP'])
		out += struct.pack('<H', self.DosHdr['InitialCS'])
		out += struct.pack('<H', self.DosHdr['FileAddOfRelocTable'])
		out += struct.pack('<H', self.DosHdr['OverlayNumber'])
		out += str(self.DosHdr['Reserved1'])
		out += struct.pack('<H', self.DosHdr['OEMIdentifier'])
		out += struct.pack('<H', self.DosHdr['OEMInformation'])
		out += str(self.DosHdr['Reserved2'])
		out += struct.pack('<I', self.DosHdr['SignatureOffset'])
		
		out += str(self.DosStub['Value'])

		out += str(self.PeSignature['Value'])

		out += struct.pack('<H', self.CoffHdr['Machine'])
		out += struct.pack('<H', self.CoffHdr['NumberOfSections'])
		out += struct.pack('<I', self.CoffHdr['TimeDateStamp'])
		out += struct.pack('<I', self.CoffHdr['PointerToSymbolTable'])
		out += struct.pack('<I', self.CoffHdr['NumberOfSymbols'])
		out += struct.pack('<H', self.CoffHdr['SizeOfOptionalHeader'])
		out += struct.pack('<H', self.CoffHdr['Characteristics'])
		
		out += str(self.OptHdr['Magic'])
		out += struct.pack('<c', self.OptHdr['MajorLinkerVersion'])
		out += struct.pack('<c', self.OptHdr['MinorLinkerVersion'])
		out += struct.pack('<I', self.OptHdr['SizeOfCode'])
		out += struct.pack('<I', self.OptHdr['SizeOfInitializedData'])
		out += struct.pack('<I', self.OptHdr['SizeOfUninitializedData'])
		out += struct.pack('<I', self.OptHdr['AddressOfEntryPoint'])
		out += struct.pack('<I', self.OptHdr['BaseOfCode'])
		out += struct.pack('<I', self.OptHdr['BaseOfData'])
		out += struct.pack('<I', self.OptHdr['ImageBase'])
		out += struct.pack('<I', self.OptHdr['SectionAlignment'])
		out += struct.pack('<I', self.OptHdr['FileAlignment'])
		out += struct.pack('<H', self.OptHdr['MajorOSVersion'])
		out += struct.pack('<H', self.OptHdr['MinorOSVersion'])
		out += struct.pack('<H', self.OptHdr['MajorImageVersion'])
		out += struct.pack('<H', self.OptHdr['MinorImageVersion'])
		out += struct.pack('<H', self.OptHdr['MajorSubsystemVersion'])
		out += struct.pack('<H', self.OptHdr['MinorSubsystemVersion'])
		out += struct.pack('<I', self.OptHdr['Win32VersionValue'])
		out += struct.pack('<I', self.OptHdr['SizeOfImage'])
		out += struct.pack('<I', self.OptHdr['SizeOfHeaders'])
		out += struct.pack('<I', self.OptHdr['Checksum'])
		out += struct.pack('<H', self.OptHdr['Subsystem'])
		out += struct.pack('<H', self.OptHdr['DllCharacteristics'])
		out += struct.pack('<I', self.OptHdr['SizeOfStackReserve'])
		out += struct.pack('<I', self.OptHdr['SizeOfStackCommit'])
		out += struct.pack('<I', self.OptHdr['SizeOfHeapReserve'])
		out += struct.pack('<I', self.OptHdr['SizeOfHeapCommit'])
		out += struct.pack('<I', self.OptHdr['LoaderFlags'])
		out += struct.pack('<I', self.OptHdr['NumberOfRvaAndSizes'])

		out += struct.pack('<I', self.DataDirs['Export']['rva'])        + struct.pack('<I', self.DataDirs['Export']['size'])
		out += struct.pack('<I', self.DataDirs['Import']['rva'])        + struct.pack('<I', self.DataDirs['Import']['size'])
		out += struct.pack('<I', self.DataDirs['Resource']['rva'])      + struct.pack('<I', self.DataDirs['Resource']['size'])
		out += struct.pack('<I', self.DataDirs['Exception']['rva'])     + struct.pack('<I', self.DataDirs['Exception']['size'])
		out += struct.pack('<I', self.DataDirs['Security']['rva'])      + struct.pack('<I', self.DataDirs['Security']['size'])
		out += struct.pack('<I', self.DataDirs['Relocation']['rva'])    + struct.pack('<I', self.DataDirs['Relocation']['size'])
		out += struct.pack('<I', self.DataDirs['Debug']['rva'])         + struct.pack('<I', self.DataDirs['Debug']['size'])
		out += struct.pack('<I', self.DataDirs['Architecture']['rva'])  + struct.pack('<I', self.DataDirs['Architecture']['size'])
		out += struct.pack('<I', self.DataDirs['Reserved1']['rva'])     + struct.pack('<I', self.DataDirs['Reserved1']['size'])
		out += struct.pack('<I', self.DataDirs['TLS']['rva'])           + struct.pack('<I', self.DataDirs['TLS']['size'])
		out += struct.pack('<I', self.DataDirs['Configuration']['rva']) + struct.pack('<I', self.DataDirs['Configuration']['size'])
		out += struct.pack('<I', self.DataDirs['BoundImport']['rva'])   + struct.pack('<I', self.DataDirs['BoundImport']['size'])
		out += struct.pack('<I', self.DataDirs['IAT']['rva'])           + struct.pack('<I', self.DataDirs['IAT']['size'])
		out += struct.pack('<I', self.DataDirs['Delay']['rva'])         + struct.pack('<I', self.DataDirs['Delay']['size'])
		out += struct.pack('<I', self.DataDirs['MetaData']['rva'])      + struct.pack('<I', self.DataDirs['MetaData']['size'])
		out += struct.pack('<I', self.DataDirs['Reserved2']['rva'])     + struct.pack('<I', self.DataDirs['Reserved2']['size'])
		
		for i in range(self.CoffHdr['NumberOfSections']):
			out += str(self.SectHdr[i]['Name'])
			out += struct.pack('<I', self.SectHdr[i]['VirtualSize'])
			out += struct.pack('<I', self.SectHdr[i]['VirtualAddress'])
			out += struct.pack('<I', self.SectHdr[i]['SizeOfRawData'])
			out += struct.pack('<I', self.SectHdr[i]['PointerToRawData'])
			out += struct.pack('<I', self.SectHdr[i]['PointerToRelocations'])
			out += struct.pack('<I', self.SectHdr[i]['PointerToLineNumbers'])
			out += struct.pack('<H', self.SectHdr[i]['NumberOfRelocations'])
			out += struct.pack('<H', self.SectHdr[i]['NumberOfLineNumbers'])
			out += struct.pack('<I', self.SectHdr[i]['Characteristics'])

		for i in range(self.CoffHdr['NumberOfSections']):
			# Right-pad until total size reaches this section's pointer to raw data
			out += "\x00" * (self.SectHdr[i]['PointerToRawData'] - len(out))
			out += self.Sections[i]

		return out

	def dump(self):
		dump = OrderedDict([
			('DosHdr',      self.DosHdr),
			('DosStub',     self.DosStub),
			('PeSignature', self.PeSignature),
			('CoffHdr',     self.CoffHdr),
			('OptHdr',      self.OptHdr),
			('DataDirs',    self.DataDirs),
		])

		for name, attrs in dump.iteritems():
			print name
			for k, v in attrs.iteritems():
				print "   [%s] -> %s" % (k, self.__repr__(v))
			print ""
		
		print 'Sections'
		for i in range(len(self.SectHdr)):
			print "\n   Section %d - Headers" % i
			for k, v in self.SectHdr[i].iteritems():
				print "       [%s] -> %s" % (k, self.__repr__(v))
			print "\n       [Contents] -> %s (... snip ...)" % (repr(self.Sections[i][:50]))	


pe = PE()
pe.parse(sys.argv[1])
pe.add_section('jeremy', 'aaaaaaaaaabbb')
pe.del_section('jeremy')
pe.dump()
pe.write('/tmp/test.exe')

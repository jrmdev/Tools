import sys, struct
from pprint import pprint

def rpad(data, size):
	while len(data) != size:
		data += '\x00'
	return data

def bytes2int(v):
	s = ''
	for b in v:
		s += "%02x" % ord(b)
	return int(s, 16)

def bytes2hex(v):
	return hex(bytes2int(v))

def int2bytes(i, n):
	ret = ''
	s = hex(i)[2:]
	while len(s) != n*2:
		s = '0' + s
	for i in range(0, len(s), 2):
		ret += chr(int(s[i:i+2], 16))

	return ret

class DosHdr():
	MagicNumber = None
	LastSize = None
	PagesInFile = None
	relocations = None
	HeaderSizeInParagraph = None
	MinExtraParagraphNeeded = None
	MaxExtraParagraphNeeded = None
	InitialSS = None
	InitialSP = None
	Checksum = None
	InitialIP = None
	InitialCS = None
	FileAddOfRelocTable = None
	OverlayNumber = None
	Reserved1 = None
	OEMIdentifier = None
	OEMInformation = None
	Reserved2 = None
	SignatureOffset = None

class DosStub():
	Value = None

class PeSignature():
	Value = None

class CoffHdr():
	Machine = None
	NumberOfSections = None
	TimeDateStamp = None
	PointerToSymbolTable = None
	NumberOfSymbols = None
	SizeOfOptionalHeader = None
	Characteristics = None

class OptHdr():
	Magic = None
	MajorLinkerVersion = None
	MinorLinkerVersion = None
	SizeOfCode = None
	SizeOfInitializedData = None
	SizeOfUninitializedData = None
	AddressOfEntryPoint = None
	BaseOfCode = None
	BaseOfData = None
	ImageBase = None
	SectionAlignment = None
	FileAlignment = None
	MajorOSVersion = None
	MinorOSVersion = None
	MajorImageVersion = None
	MinorImageVersion = None
	MajorSubsystemVersion = None
	MinorSubsystemVersion = None
	Win32VersionValue = None
	SizeOfImage = None
	SizeOfHeaders = None
	Checksum = None
	Subsystem = None
	DllCharacteristics = None
	SizeOfStackReserve = None
	SizeOfStackCommit = None
	SizeOfHeapReserve = None
	SizeOfHeapCommit = None
	LoaderFlags = None
	NumberOfRvaAndSizes = None

class DataDirs():
	Export = {'rva': None, 'size': None}
	Import = {'rva': None, 'size': None}
	Resource = {'rva': None, 'size': None}
	Exception = {'rva': None, 'size': None}
	Security = {'rva': None, 'size': None}
	Relocation = {'rva': None, 'size': None}
	Debug = {'rva': None, 'size': None}
	Architecture = {'rva': None, 'size': None}
	Reserved1 = {'rva': None, 'size': None}
	TLS = {'rva': None, 'size': None}
	Configuration = {'rva': None, 'size': None}
	BoundImport = {'rva': None, 'size': None}
	IAT = {'rva': None, 'size': None}
	Delay = {'rva': None, 'size': None}
	MetaData = {'rva': None, 'size': None}
	Reserved2 = {'rva': None, 'size': None}

class SectionHdr():
	Name = None
	VirtualSize = None
	VirtualAddress = None
	SizeOfRawData = None
	PointerToRawData = None
	PointerToRelocations = None
	PointerToLineNumbers = None
	NumberOfRelocations = None
	NumberOfLineNumbers = None
	Characteristics = None

class PE():
	data = None
	cursor = 0

	def __init__(self):
		self.DosHdr = DosHdr()
		self.DosStub = DosStub()
		self.PeSignature = PeSignature()
		self.CoffHdr = CoffHdr()
		self.OptHdr = OptHdr()
		self.DataDirs = DataDirs()
		self.SectHdr = {}
		self.Sections = {}
		
	def get_word(self):
		return self.get_bytes(2)

	def get_dword(self):
		return self.get_bytes(4)

	def get_bytes(self, n):
		self.cursor += n
		return self.data[self.cursor-n:self.cursor][::-1]

	def parse(self, filename):
		self.data = open(filename, 'rb').read()
		
		# Dos Header
		self.DosHdr.MagicNumber = self.get_word()
		self.DosHdr.LastSize = self.get_word()
		self.DosHdr.PagesInFile = self.get_word()
		self.DosHdr.relocations = self.get_word()
		self.DosHdr.HeaderSizeInParagraph = self.get_word()
		self.DosHdr.MinExtraParagraphNeeded = self.get_word()
		self.DosHdr.MaxExtraParagraphNeeded = self.get_word()
		self.DosHdr.InitialSS = self.get_word()
		self.DosHdr.InitialSP = self.get_word()
		self.DosHdr.Checksum = self.get_word()
		self.DosHdr.InitialIP = self.get_word()
		self.DosHdr.InitialCS = self.get_word()
		self.DosHdr.FileAddOfRelocTable = self.get_word()
		self.DosHdr.OverlayNumber = self.get_word()
		self.DosHdr.Reserved1 = self.get_bytes(8)
		self.DosHdr.OEMIdentifier = self.get_word()
		self.DosHdr.OEMInformation = self.get_word()
		self.DosHdr.Reserved2 = self.get_bytes(20)
		self.DosHdr.SignatureOffset = self.get_dword()
		
		# Dos Stub
		self.DosStub.Value = self.get_bytes(bytes2int(self.DosHdr.SignatureOffset) - self.cursor)
		
		# PE Signature
		self.PeSignature.Value = self.get_dword()
		
		# Coff Header
		self.CoffHdr.Machine = self.get_word()
		self.CoffHdr.NumberOfSections = self.get_word()
		self.CoffHdr.TimeDateStamp = self.get_dword()
		self.CoffHdr.PointerToSymbolTable = self.get_dword()
		self.CoffHdr.NumberOfSymbols = self.get_dword()
		self.CoffHdr.SizeOfOptionalHeader = self.get_word()
		self.CoffHdr.Characteristics = self.get_word()
		
		# Optional Header: Standard Fields
		self.OptHdr.Magic = self.get_word()
		self.OptHdr.MajorLinkerVersion = self.get_bytes(1)
		self.OptHdr.MinorLinkerVersion = self.get_bytes(1)
		self.OptHdr.SizeOfCode = self.get_dword()
		self.OptHdr.SizeOfInitializedData = self.get_dword()
		self.OptHdr.SizeOfUninitializedData = self.get_dword()
		self.OptHdr.AddressOfEntryPoint = self.get_dword()
		self.OptHdr.BaseOfCode = self.get_dword()
		self.OptHdr.BaseOfData = self.get_dword()
		
		# Optional Header: Windows-Specific Fields
		self.OptHdr.ImageBase = self.get_dword()
		self.OptHdr.SectionAlignment = self.get_dword()
		self.OptHdr.FileAlignment = self.get_dword()
		self.OptHdr.MajorOSVersion = self.get_word()
		self.OptHdr.MinorOSVersion = self.get_word()
		self.OptHdr.MajorImageVersion = self.get_word()
		self.OptHdr.MinorImageVersion = self.get_word()	
		self.OptHdr.MajorSubsystemVersion = self.get_word()
		self.OptHdr.MinorSubsystemVersion = self.get_word()
		self.OptHdr.Win32VersionValue = self.get_dword()
		self.OptHdr.SizeOfImage = self.get_dword()
		self.OptHdr.SizeOfHeaders = self.get_dword()
		self.OptHdr.Checksum = self.get_dword()
		self.OptHdr.Subsystem = self.get_word()
		self.OptHdr.DllCharacteristics = self.get_word()
		self.OptHdr.SizeOfStackReserve = self.get_dword()
		self.OptHdr.SizeOfStackCommit = self.get_dword()
		self.OptHdr.SizeOfHeapReserve = self.get_dword()
		self.OptHdr.SizeOfHeapCommit = self.get_dword()
		self.OptHdr.LoaderFlags = self.get_dword()
		self.OptHdr.NumberOfRvaAndSizes = self.get_dword()
		
		# Data Directories
		self.DataDirs.Export = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Import = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Resource = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Exception = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Security = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Relocation = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Debug = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Architecture = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Reserved1 = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.TLS = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Configuration = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.BoundImport = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.IAT = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Delay = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.MetaData = {'rva': self.get_dword(), 'size': self.get_dword()}
		self.DataDirs.Reserved2 = {'rva': self.get_dword(), 'size': self.get_dword()}
		
		# Sections Headers
		for i in range(bytes2int(self.CoffHdr.NumberOfSections)):
			Section = SectionHdr()
			Section.Name = self.get_bytes(8)
			Section.VirtualSize = self.get_dword()
			Section.VirtualAddress = self.get_dword()
			Section.SizeOfRawData = self.get_dword()
			Section.PointerToRawData = self.get_dword()
			Section.PointerToRelocations = self.get_dword()
			Section.PointerToLineNumbers = self.get_dword()
			Section.NumberOfRelocations = self.get_word()
			Section.NumberOfLineNumbers = self.get_word()
			Section.Characteristics = self.get_dword()
			self.SectHdr[i] = Section
			
			# Sections Data
			startoff = bytes2int(Section.PointerToRawData)
			rawsize = bytes2int(Section.SizeOfRawData)
			self.Sections[i] = self.get_raw_data(startoff, rawsize)
	
	def get_raw_data(self, startoff, rawsize):
		return self.data[startoff:startoff+rawsize]
	
	def __repr__(self, val):
		if len(val) <= 4:
			if isinstance(val, dict):
				ret = '{'
				for k, v in val.iteritems():
					ret += "'%s': %s, " % (k, bytes2hex(v))
				ret = ret[0:-2]+'}'
			else:
				ret = bytes2hex(val)
		else:
			ret = repr(val[::-1])

		return ret
   
	def add_section(self, name, data, size=None):
		if size == None or size < len(data):
			size = len(data)

		data = rpad(data, size)
		name = rpad(name, 8)
		
		n = len(self.SectHdr)
		raw_offset = bytes2int(self.SectHdr[n-1].PointerToRawData) + bytes2int(self.SectHdr[n-1].SizeOfRawData)
		virt_addr = size + bytes2int(self.OptHdr.SectionAlignment) + bytes2int(self.SectHdr[n-1].VirtualAddress)
		while virt_addr % bytes2int(self.OptHdr.SectionAlignment):
			virt_addr += 1
		
		new_sect = SectionHdr()
		new_sect.Name = name[::-1]
		new_sect.VirtualSize = int2bytes(size, 4)
		new_sect.VirtualAddress = int2bytes(virt_addr, 4)
		new_sect.SizeOfRawData = int2bytes(size, 4)
		new_sect.PointerToRawData = int2bytes(raw_offset, 4)
		new_sect.PointerToRelocations = int2bytes(0, 4)
		new_sect.PointerToLineNumbers = int2bytes(0, 4)
		new_sect.NumberOfRelocations = int2bytes(0, 2)
		new_sect.NumberOfLineNumbers = int2bytes(0, 2)
		new_sect.Characteristics = int2bytes(int('E0000020', 16), 4)

		self.SectHdr[n] = new_sect
		self.Sections[n] = data
		
		self.CoffHdr.NumberOfSections = int2bytes(bytes2int(self.CoffHdr.NumberOfSections) + 1, 2)
		self.OptHdr.SizeOfImage = int2bytes(bytes2int(self.OptHdr.SizeOfImage) + size, 4)
		self.update_cksum()
	
	def del_section(self, name=None, n=None):
		if isinstance(name, str):
			for i in self.SectHdr:
				if name == self.SectHdr[i].Name[::-1].strip('\x00'):
					n = i

		if n == None:
			return False
		
		if isinstance(self.SectHdr[n], SectionHdr):
			size = bytes2int(self.SectHdr[n].SizeOfRawData)
			
			self.CoffHdr.NumberOfSections = int2bytes(bytes2int(self.CoffHdr.NumberOfSections) - 1, 2)
			self.OptHdr.SizeOfImage = int2bytes(bytes2int(self.OptHdr.SizeOfImage) - size, 4)
			
			self.SectHdr.pop(n)
			self.Sections.pop(n)
			self.update_cksum()
	
	def update_cksum(self):
		_data = self.build()
		length = len(_data)
		
		# 24 = len(signature) + len(CoffHdr)
		# 64 = offset from top of OptHdr to OptHdr.Checksum
		checksum_offset = bytes2int(self.DosHdr.SignatureOffset) + 24 + 64
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

		self.OptHdr.Checksum = int2bytes(checksum + length, 4)
	
	def write(self, filename):
		open(filename, 'wb').write(self.build())
	
	def build(self):
		out  = self.DosHdr.MagicNumber[::-1]
		out += self.DosHdr.LastSize[::-1]
		out += self.DosHdr.PagesInFile[::-1]
		out += self.DosHdr.relocations[::-1]
		out += self.DosHdr.HeaderSizeInParagraph[::-1]
		out += self.DosHdr.MinExtraParagraphNeeded[::-1]
		out += self.DosHdr.MaxExtraParagraphNeeded[::-1]
		out += self.DosHdr.InitialSS[::-1]
		out += self.DosHdr.InitialSP[::-1]
		out += self.DosHdr.Checksum[::-1]
		out += self.DosHdr.InitialIP[::-1]
		out += self.DosHdr.InitialCS[::-1]
		out += self.DosHdr.FileAddOfRelocTable[::-1]
		out += self.DosHdr.OverlayNumber[::-1]
		out += self.DosHdr.Reserved1[::-1]
		out += self.DosHdr.OEMIdentifier[::-1]
		out += self.DosHdr.OEMInformation[::-1]
		out += self.DosHdr.Reserved2[::-1]
		out += self.DosHdr.SignatureOffset[::-1]
		
		out += self.DosStub.Value[::-1]

		out += self.PeSignature.Value[::-1]

		out += self.CoffHdr.Machine[::-1]
		out += self.CoffHdr.NumberOfSections[::-1]
		out += self.CoffHdr.TimeDateStamp[::-1]
		out += self.CoffHdr.PointerToSymbolTable[::-1]
		out += self.CoffHdr.NumberOfSymbols[::-1]
		out += self.CoffHdr.SizeOfOptionalHeader[::-1]
		out += self.CoffHdr.Characteristics[::-1]
		
		out += self.OptHdr.Magic[::-1]
		out += self.OptHdr.MajorLinkerVersion[::-1]
		out += self.OptHdr.MinorLinkerVersion[::-1]
		out += self.OptHdr.SizeOfCode[::-1]
		out += self.OptHdr.SizeOfInitializedData[::-1]
		out += self.OptHdr.SizeOfUninitializedData[::-1]
		out += self.OptHdr.AddressOfEntryPoint[::-1]
		out += self.OptHdr.BaseOfCode[::-1]
		out += self.OptHdr.BaseOfData[::-1]
		out += self.OptHdr.ImageBase[::-1]
		out += self.OptHdr.SectionAlignment[::-1]
		out += self.OptHdr.FileAlignment[::-1]
		out += self.OptHdr.MajorOSVersion[::-1]
		out += self.OptHdr.MinorOSVersion[::-1]
		out += self.OptHdr.MajorImageVersion[::-1]
		out += self.OptHdr.MinorImageVersion	[::-1]
		out += self.OptHdr.MajorSubsystemVersion[::-1]
		out += self.OptHdr.MinorSubsystemVersion[::-1]
		out += self.OptHdr.Win32VersionValue[::-1]
		out += self.OptHdr.SizeOfImage[::-1]
		out += self.OptHdr.SizeOfHeaders[::-1]
		out += self.OptHdr.Checksum[::-1]
		out += self.OptHdr.Subsystem[::-1]
		out += self.OptHdr.DllCharacteristics[::-1]
		out += self.OptHdr.SizeOfStackReserve[::-1]
		out += self.OptHdr.SizeOfStackCommit[::-1]
		out += self.OptHdr.SizeOfHeapReserve[::-1]
		out += self.OptHdr.SizeOfHeapCommit[::-1]
		out += self.OptHdr.LoaderFlags[::-1]
		out += self.OptHdr.NumberOfRvaAndSizes[::-1]

		out += self.DataDirs.Export['rva'][::-1] + self.DataDirs.Export['size'][::-1]
		out += self.DataDirs.Import['rva'][::-1] + self.DataDirs.Import['size'][::-1]
		out += self.DataDirs.Resource['rva'][::-1] + self.DataDirs.Resource['size'][::-1]
		out += self.DataDirs.Exception['rva'][::-1] + self.DataDirs.Exception['size'][::-1]
		out += self.DataDirs.Security['rva'][::-1] + self.DataDirs.Security['size'][::-1]
		out += self.DataDirs.Relocation['rva'][::-1] + self.DataDirs.Relocation['size'][::-1]
		out += self.DataDirs.Debug['rva'][::-1] + self.DataDirs.Debug['size'][::-1]
		out += self.DataDirs.Architecture['rva'][::-1] + self.DataDirs.Architecture['size'][::-1]
		out += self.DataDirs.Reserved1['rva'][::-1] + self.DataDirs.Reserved1['size'][::-1]
		out += self.DataDirs.TLS['rva'][::-1] + self.DataDirs.TLS['size'][::-1]
		out += self.DataDirs.Configuration['rva'][::-1] + self.DataDirs.Configuration['size'][::-1]
		out += self.DataDirs.BoundImport['rva'][::-1] + self.DataDirs.BoundImport['size'][::-1]
		out += self.DataDirs.IAT['rva'][::-1] + self.DataDirs.IAT['size'][::-1]
		out += self.DataDirs.Delay['rva'][::-1] + self.DataDirs.Delay['size'][::-1]
		out += self.DataDirs.MetaData['rva'][::-1] + self.DataDirs.MetaData['size'][::-1]
		out += self.DataDirs.Reserved2['rva'][::-1] + self.DataDirs.Reserved2['size'][::-1]
		
		for i in range(bytes2int(self.CoffHdr.NumberOfSections)):
			out += self.SectHdr[i].Name[::-1]
			out += self.SectHdr[i].VirtualSize[::-1]
			out += self.SectHdr[i].VirtualAddress[::-1]
			out += self.SectHdr[i].SizeOfRawData[::-1]
			out += self.SectHdr[i].PointerToRawData[::-1]
			out += self.SectHdr[i].PointerToRelocations[::-1]
			out += self.SectHdr[i].PointerToLineNumbers[::-1]
			out += self.SectHdr[i].NumberOfRelocations[::-1]
			out += self.SectHdr[i].NumberOfLineNumbers[::-1]
			out += self.SectHdr[i].Characteristics[::-1]

		for i in range(bytes2int(self.CoffHdr.NumberOfSections)):
			out = rpad(out, bytes2int(self.SectHdr[i].PointerToRawData))
			out += self.Sections[i]
			
		return out

	def dump(self):
		dump = {
		'DosHdr': vars(self.DosHdr),
		'DosStub': vars(self.DosStub),
		'PeSignature': vars(self.PeSignature),
		'CoffHdr': vars(self.CoffHdr),
		'OptHdr': vars(self.OptHdr),
		'DataDirs': vars(self.DataDirs),
		}

		for name, attrs in dump.iteritems():
			print name
			for k, v in attrs.iteritems():
				print "   [%s] -> %s" % (k, self.__repr__(v))
			print ""
		
		print 'Sections'
		for i in range(len(self.SectHdr)):
			print "\n   Section %d - Headers" % i
			for k, v in vars(self.SectHdr[i]).iteritems():
				print "       [%s] -> %s" % (k, self.__repr__(v))
			#print "Section %d - Contents: %s" % (i, repr(self.Sections[i][:50]))
			
			
	
		
pe = PE()
pe.parse(sys.argv[1])
pe.add_section('jeremy', 'aaaaaaaaaabbb')
pe.del_section('jeremy')
pe.dump()
pe.write('/tmp/test.exe')

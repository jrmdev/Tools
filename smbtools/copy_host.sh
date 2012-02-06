#! /bin/sh
if [ -z $4 ]; then
	echo "usage: $0 <ip_addr> <username> <password> <binary to copy>"
	exit
fi

fail() {
	echo " [FAIL]"
	exit
}

echo -n "$1 ... "
smbclient -U $2%$3 //$1/c$ -c 'dir \' 1>/dev/null 2>&1 || fail
smbclient -U $2%$3 //$1/c$ -c 'mkdir \temp' 1>/dev/null 2>&1
smbclient -U $2%$3 //$1/c$ -c 'mkdir \temp\temp' 1>/dev/null 2>&1
smbclient -U $2%$3 //$1/c$ -c "put $4 \temp\temp\a.exe" 1>/dev/null 2>&1
echo " [OK]"

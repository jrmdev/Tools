#!/bin/sh
if [ -z "$4" ]; then
	echo "usage: $0 <ip_addr> <username> <password> <cmd>"
	exit
fi

winexe --system --uninstall -U $2%$3 //$1 "cmd /C $4"

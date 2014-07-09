TTMGR - Transparent Tunnel Manager
==================================

TTMR is a python script aimed at simplifying management of transparent tunnels. It allows to transparently redirect network streams over a proxy. It transparently proxifies outgoing streams from your machine, adding proxy support for any host:port couple for application that don't have proxy support.

It makes use of iptables and socat and runs under Linux.

To use it, your user must have permission to run iptables through sudo. As root, run the following command prior to using ttmgr:

	echo "$USER ALL=NOPASSWD: /sbin/iptables" >> /etc/sudoers

Example usage
-------------

Suppose you have a thin/thick client connecting to *https://accounts.google.com* without proxy support. you may use ttgr as follows to perform *man-in-the-middle* using an http interception proxy.

- Create a proxy configuration
- Enable usage of this proxy
- Create the tunnel


	user@host:~$ python ttmgr.py 
	TTMGR is a Transparent Tunnel Manager
	-------------------------------------

	For module-specific help, type "help <module>"
	Use ".." to move to upper level

	- config          manipulate configuration
	- proxy           manipulate proxy list
	- tunnel          manipulate tunnels
	- sh              run shell commands

	ttmgr> proxy add 127.0.0.1 8080
	ttmgr> proxy list
	[1] 127.0.0.1:8080  
	ttmgr> proxy use 1
	proxy.number = 1
	ttmgr> config set proxy.enabled True
	proxy.enabled = True
	ttmgr> config show
	proxy.enabled	=> True
	proxy.number	=> 1
	ttmgr> tunnel add accounts.google.com 443
	** Tunnel to accounts.google.com:443 started
	ttmgr> tunnel list
	[1] accounts.google.com:443 >>> PROXY localhost:8080 <-> DEST accounts.google.com:443
	ttmgr> exit
	Quitting.

The tunnel stays alive even after ttmgr was quit. After your work is finished, you can terminate your tunnel like this:

	user@host:~$ python ttmgr.py tunnel list
	[1] accounts.google.com:443 >>> PROXY localhost:8080 <-> DEST accounts.google.com:443
	user@host:~$ python ttmgr.py tunnel del 1
	** Tunnel to accounts.google.com:443 terminated
	user@host:~$

To see the actual socat running processes and iptables rules, run ttmgr as follows:

	user@host:~$ python pentest/script/ttmgr.py debug
	 9264 /usr/bin/socat TCP4-LISTEN:50704,bind=127.0.0.1,reuseaddr,fork PROXY:127.0.0.1:accounts.google.com:443,proxyport=8080
	-A OUTPUT -d 173.194.72.84/32 -p tcp -m tcp --dport 443 -m comment --comment "ttmgr:50704" -j DNAT --to-destination 127.0.0.1:50704


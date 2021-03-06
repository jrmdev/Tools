TTMGR - Transparent Tunnel Manager
==================================

TTMGR is a python script aimed at simplifying management of transparent tunnels. It allows to transparently redirect TCP sessions over a proxy. It transparently proxifies outgoing TCP streams from your machine, adding proxy support for any host:port couple for applications that don't have proxy support.

You don't have to enable IP forwarding or modify your hosts file.

It makes use of iptables and socat and runs under Linux.

To use it, your user must have permission to run iptables through sudo. As root, run the following command prior to using ttmgr:

	echo "<USER> ALL=NOPASSWD: /sbin/iptables" >> /etc/sudoers

Example usage
-------------

Suppose you have a thin/thick client connecting to *https://accounts.google.com* without proxy support. you may use ttmgr as follows to perform *man-in-the-middle* using an http interception proxy, or simply to give it network access.

- Create a proxy configuration
- Enable usage of this proxy
- Create the tunnel

```
user@host:~$ python ttmgr.py 
TTMGR is a Transparent Tunnel Manager
-------------------------------------

For module-specific help, type "help <module>"
Use ".." to move to upper level

- proxy           manipulate proxy list
- tunnel          manipulate tunnels
- sh              run shell commands

ttmgr> proxy add 127.0.0.1 8080
ttmgr> proxy list
[0] No proxy (direct connection)
[1] 127.0.0.1:8080
ttmgr> tunnel add 1 accounts.google.com 443
** Tunnel to accounts.google.com:443 started
ttmgr> tunnel list
[1] accounts.google.com:443       > LOCAL <-> PROXY localhost:8080 <-> DEST accounts.google.com:443
ttmgr> exit
Quitting.
```
At this point, any direct connection that your system wants to make to accounts.google.com:443 will be transparently relayed to a localport, then forwarded to the specified proxy and finally routed to its real destination.

The tunnel stays alive even after ttmgr was quit. After your work is finished, you can terminate your tunnel like this:

	user@host:~$ python ttmgr.py tunnel list
	[1] accounts.google.com:443       > LOCAL <-> PROXY localhost:8080 <-> DEST accounts.google.com:443
	user@host:~$ python ttmgr.py tunnel del 1
	** Tunnel to accounts.google.com:443 terminated
	user@host:~$


As another exemple usage, TTMGR can be used to proxify connections for any arbitrary TCP protocol. For example, if you want to connect to an IRC server over SSL through your corporate proxy:

	user@host:~$ python ttmgr.py proxy add proxy.mycompany.com 3128
	user@host:~$ python ttmgr.py proxy list
	[0] No proxy (direct connection)
	[1] proxy.mycompany.com:3128 

Then create the tunnel:

	user@host:~$ python ttmgr.py tunnel add 1 kornbluth.freenode.net 6697
	** Tunnel to kornbluth.freenode.net:6697 started
	user@host:~$ python ttmgr.py tunnel list
	[1] kornbluth.freenode.net:6697    > LOCAL <-> PROXY proxy.mycompany.com:3128 <-> DEST kornbluth.freenode.net:6697

From now on, any connection from your machine to kornbluth.freenode.net:6697 will be tunnelled through the proxy:

	user@host:~$ socat - OPENSSL:kornbluth.freenode.net:6697,verify=0
	:kornbluth.freenode.net NOTICE * :*** Looking up your hostname...
	:kornbluth.freenode.net NOTICE * :*** Checking Ident
	:kornbluth.freenode.net NOTICE * :*** Found your hostname
	^C

To see the actual socat running processes and iptables rules, run ttmgr as follows:

	user@host:~$ python ttmgr.py debug
	 9264 /usr/bin/socat TCP4-LISTEN:50704,bind=127.0.0.1,reuseaddr,fork PROXY:127.0.0.1:accounts.google.com:443,proxyport=8080
	-A OUTPUT -d 173.194.72.84/32 -p tcp -m tcp --dport 443 -m comment --comment "ttmgr:50704" -j DNAT --to-destination 127.0.0.1:50704


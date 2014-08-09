<?php

/*
This is Textpattern

Copyright 2005 by Dean Allen
www.textpattern.com
All rights reserved

Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_auth.php $
$LastChangedRevision: 3250 $

*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

function doAuth()
{
	global $txp_user, $path_to_site;
	
	$txp_user = NULL;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (defined('SETUP') and SETUP) {
		
		$txp_user = safe_field('Name',"txp_users","Type = 'user' ORDER BY ID ASC");
		
		return;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if ($_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR']) {
		
		if (gps('user') and gps('key')) {
			
			$user = gps('user');
			$key  = gps('key');
				
			$safe_user = doSlash($user);
			
			if (getCount("txp_users","name = '$safe_user'")) {
				
				$key = $path_to_site.DS.'textpattern'.DS.'tmp'.DS.$key;
				
				if (is_file($key)) {
					
					$txp_user = $safe_user;
						
					// unlink($key);
						
					ob_start();
					
					return;
			
				}
			}
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	$message = doTxpValidate();
	
	// $txp_user = 'steffi';
	// $txp_user = 'hamiltro';
	// $txp_user = 'copyeditor';
	
	if(!$txp_user)
	{
		if (txpinterface == 'admin') {
			doLoginForm($message);
		}
	}

	ob_start();
}

// -------------------------------------------------------------
	function txp_validate($user,$password)
	{
		$safe_user = doSlash($user);
		$passwords = array();

		$passwords[] = "password(lower('".doSlash($password)."'))";
		$passwords[] = "password('".doSlash($password)."')";

		if (version_compare(mysql_get_server_info(), '4.1.0', '>='))
		{
			$passwords[] = "old_password(lower('".doSlash($password)."'))";
			$passwords[] = "old_password('".doSlash($password)."')";
		}

		$name = safe_field("name", "txp_users",
			"name = '$safe_user' and (pass = ".join(' or pass = ', $passwords).") and privs > 0");

		if ($name !== FALSE)
		{
			// update the last access time
			
			safe_update("txp_users", "last_access = now()", "name = '$safe_user'");
			safe_update_parents("txp_users","last_access = now()", "name = '$safe_user'");
			
			return $name;

		}

		return false;
	}

// -------------------------------------------------------------

	function doLoginForm($message)
	{
		global $txpcfg;

		include txpath.'/lib/txplib_head.php';

		echo pagetop(gTxt('login'));

		$stay  = (cs('txp_login') and !gps('logout') ? 1 : 0);
		$reset = gps('reset');
		$cookie_cleared = (isset($_GET['clear_cookie'])) ? 'OK' : '';

		list($name) = explode(',', cs('txp_login'));

		echo form(
			startTable('edit', '', 'login-pane').
				n.n.tr(
					n.td().
					td(graf($message))
				).

				n.n.tr(
					n.fLabelCell('name', '', 'name').
					n.fInputCell('p_userid', $name, 1, '', '', 'name')
				).

				($reset ? '' :
					n.n.tr(
						n.fLabelCell('password', '', 'password').
						n.td(
						  	fInput('password', 'p_password', '', 'edit', '', '', '', 2, 'password')
						)
					)
				).

				($reset ? '' :
					n.n.tr(
						n.td().
						td(
							graf(checkbox('stay', 1, $stay, 3, 'stay').'<label for="stay">'.gTxt('stay_logged_in').'</label>'.
							sp.popHelp('remember_login'))
						)
					)
				).

				n.n.tr(
					n.td().
					td(
						($reset ? hInput('p_reset', 1) : '').
						fInput('submit', '', gTxt($reset ? 'password_reset_button' : 'log_in_button'), 'publish', '', '', '', 4).
						($reset ? '' : graf('<a href="?reset=1">'.gTxt('password_forgotten').'</a>'))
						// .'<p><a href="index.php?clear_cookie">Clear Cookie</a> '.$cookie_cleared.'</p>'
					)
				).

			endTable().

			(gps('event') ? eInput(gps('event')) : '')
		).
		
		n.'</body>'.n.'</html>';

		exit(0);
	}

// -------------------------------------------------------------
	function doTxpValidate()
	{
		global $logout,$txpcfg, $txp_user, $siteurl;
		
		$p_userid   = ps('p_userid');
		$p_password = ps('p_password');
		$p_reset    = ps('p_reset');
		$stay       = ps('stay');
		$logout     = gps('logout');
		$message    = gTxt('login_to_textpattern');
	 // $pub_path   = preg_replace('|//$|','/', rhu.'/');
		
		$uri = trim($_SERVER["REQUEST_URI"],'/');
		
		$path = (str_begins_with($uri,'~'))
			? '/'.reset(explode('/',$uri)).'/'
			: '/';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (cs('txp_login') and strpos(cs('txp_login'), ','))
		{
			list($c_userid, $c_hash) = explode(',', cs('txp_login'));
		}
		else
		{
			$c_hash   = '';
			$c_userid = '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($logout)
		{
			setcookie('txp_login', '', time()-3600);
			setcookie('txp_login', '', time()-3600,$path);
			setcookie('txp_login_public', '', time()-3600,$path);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// if incoming login vars
		
		elseif ($p_userid and $p_password)
		{
			sleep(3);

			$name = txp_validate($p_userid,$p_password);
			
			if ($name !== FALSE)
			{
				$c_hash = md5(uniqid(mt_rand(), TRUE));
				$nonce  = md5($name.pack('H*',$c_hash));
				
				safe_update(
					'txp_users',
					"nonce = '".doSlash($nonce)."'",
					"name = '".doSlash($name)."'"
				);

				setcookie(
					'txp_login',
					$name.','.$c_hash,
					($stay ? time()+3600*24*365 : 0),
					$path
				);

				setcookie(
					'txp_login_public',
					substr(md5($nonce), -10).$name,
					($stay ? time()+3600*24*30 : 0),
					$path
				);

				// login is good, create $txp_user
				$txp_user = $name;
				return '';
			}
			else
			{
				$message = gTxt('could_not_log_in');
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// if cookie exists
		
		elseif ($c_userid and strlen($c_hash) == 32) 
		{
			$nonce = safe_field('nonce', 'txp_users', "name='".doSlash($c_userid)."' AND last_access > DATE_SUB(NOW(), INTERVAL 30 DAY)");
			
			if ($nonce and $nonce === md5($c_userid.pack('H*', $c_hash)))
			{
				// cookie is good, create $txp_user
				$txp_user = $c_userid;
				return '';
			}
			else
			{
				setcookie('txp_login', $c_userid, time()+3600*24*365,$path);
				setcookie('txp_login_public', '', time()-3600,$path);
				$message = gTxt('bad_cookie');
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// if reset request
		
		elseif ($p_reset) 
		{
			sleep(3);

			include_once txpath.'/lib/txplib_admin.php';

			$message = send_reset_confirmation_request($p_userid);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		elseif (gps('reset'))
		{
			$message = gTxt('password_reset');
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		elseif (gps('confirm'))
		{
			sleep(3);

			$confirm = pack('H*', gps('confirm'));
			$name    = substr($confirm, 5);
			$nonce   = safe_field('nonce', 'txp_users', "name = '".doSlash($name)."'");

			if ($nonce and $confirm === pack('H*', substr(md5($nonce), 0, 10)).$name)
			{
				include_once txpath.'/lib/txplib_admin.php';

				$message = reset_author_pass($name);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		$txp_user = '';
		return $message;
	}
?>
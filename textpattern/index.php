<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL: http://svn.textpattern.com/current/textpattern/index.php $
$LastChangedRevision: 789 $

*/
	foreach ($_REQUEST as $name => $value) {
		if (isset($$name)) unset($$name);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// display all errors that occur during initialization
	
	error_reporting(E_ALL & ~(defined('E_STRICT') ? E_STRICT : 0));
	@ini_set("display_errors","1");
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// load main site config
	
	ob_start(NULL, 2048);
	
	if (!isset($txpath)) {
			
		$txpath = dirname(__FILE__);
	}
		
	if (!isset($txpcfg)) {
		
		@include $txpath.'/config.php';
	}
	
	if (!isset($txpcfg)) {
	
		ob_end_clean();
		
		header('HTTP/1.1 503 Service Unavailable');
		
		exit('config.php is missing or corrupt');
	}
	
	if (!defined('txpath')) {
	
		if (isset($txpcfg['txpath']) and $txpcfg['txpath']) {
			
			define('txpath',$txpcfg['txpath']);
		
		} else {
			
			define('txpath',$txpath);
		}
	}
	
	ob_end_clean();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$thisversion   = '4.2.0';
	$txp_using_svn = false; // set false for releases
	
	$site_domain   = $_SERVER['HTTP_HOST'];
	$site_url      = $site_domain.'/';
	$site_url_path = '/';
	$site_dir	   = '';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	include txpath.'/lib/constants.php';
	include txpath.'/lib/languages.php';
	include txpath.'/lib/txplib_misc.php'; 
	include txpath.'/lib/txplib_atts.php';
	include txpath.'/lib/txplib_db.php';
	include txpath.'/lib/txplib_xml.php';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// load site config if this is not the main site
	
	if ($_SERVER['SERVER_ADDR'] == $_SERVER['SERVER_NAME']) {
		
		// when site url is using the IP address
		
		define('IS_MAIN_SITE',true);
	
	} elseif ($site_name = get_site_name()) {
		
		$txpcfg = get_site_config($site_name);
		
		if (!$txpcfg) {
			
			header('HTTP/1.1 503 Service Unavailable');
		
			exit($site_name.' config is missing');
		
		} elseif (!is_dir($txpcfg['path_to_site'])) {
			
			header('HTTP/1.1 503 Service Unavailable');
			
			exit($txpcfg['path_to_site'].' is missing');
		}
		
		define('IS_MAIN_SITE',false);
	
	} else {
		
		define('IS_MAIN_SITE',true);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	/*
	if (!cs('txp_path_to_site')) {
	
		setcookie('txp_path_to_site',$txpcfg['path_to_site'],time()+3600*24*365,'/');
	}
	*/		
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// database
	
 // $DB->refresh();
	$PFX = $txpcfg['table_prefix'];
	
	$tables = safe_tables();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$app_mode = gps('app_mode');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (isset($_GET['getfile'])) {
		
		$file = gps('getfile');
		
		if ($file != 'dir') { 
		
			get_file($file);
		
			exit;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// txp interface mode
	
	if (!defined("txpinterface")) {
	
		if (gps('xsl')) {						// TODO: remove
		
			define("txpinterface", "xsl"); 
		
		} elseif (gps('css')) {					// TODO: use get_file for css
		
			define("txpinterface", "css"); 
		
		} else {
		
			define("txpinterface", "admin"); 
		}	
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// start the timer
	
	$microstart = getmicrotime();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (txpinterface == 'xsl') {
		
		header("Content-type: text/xml; charset=utf-8");
		
		$nolog = 1;
		
		echo get_xsl(); 
		
		exit;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (txpinterface == 'css') {
		
		header('Content-type: text/css');
		
		$nolog = 1;
		
		$s = gps('s');
		$n = gps('n');

		$n = preg_replace('/\.css$/','',$n);

		output_css($s,$n); 
		
		exit;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	include txpath.'/lib/admin_config.php'; 
	include txpath.'/lib/classPath.php'; 
	include txpath.'/lib/txplib_forms.php';
	include txpath.'/lib/txplib_html.php';
	include txpath.'/include/txp_auth.php';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// start a session for both admin and public interface
	
	session_start();
	
	// why would $event be set and have a value right after session_start?
	if (isset($event)) unset($event);	
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (isset($_GET['getfile'])) {
		
		if ($file == 'dir') {
			
			doAuth();
			
			if ($txp_user) {
			
				get_file($file);
			
			} else {
				
				header('HTTP/1.1 403 Forbidden');
				
				echo "<h1>403 Forbidden</h1>";
				echo "<p>Login required to view directory listing.</p>";
			}
			
			exit;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// load plugins
	
	$plugin_list = safe_column('name','txp_plugin',
		"Type = 'plugin' AND Status = 4 AND Trash = 0");
	
	foreach($plugin_list as $plugin) {
		
		if (is_file($txpcfg['path_to_site']."/textpattern/plugins/$plugin/index.php")) {
		
			include $txpcfg['path_to_site']."/textpattern/plugins/$plugin/index.php";
			
		} elseif (is_file(txpath."/plugins/$plugin/index.php")) {
		
			include txpath."/plugins/$plugin/index.php";
		
		} else {
			
			unset($plugin_list[$plugin]);
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (isset($_GET['captcha'])) {
		
		include txpath.'/publish/captcha/captcha.php'; 
		include txpath.'/publish/captcha/validate.php'; 
		
		if (in_array('captcha', $plugin_list)) {
			
			sleep(1);
			
			$captcha = gps('captcha');
			
			if ($captcha == '' or $captcha == '1') {
				captcha_create();
			} else {
				captcha_validate($captcha);
			}	
			
			exit;
			
		} else {
		
			$captcha = new SimpleCaptcha();
			$captcha->resourcesPath = txpath.'/publish/captcha/resources'; 
			$captcha->imageFormat = 'png';
			$captcha->CreateImage();
			
			exit;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (txpinterface == 'public' and gps('plugin')) {
		
		// include txpath.'/publish.php';
		
		$plugin = $txpcfg['path_to_site'].'/plugins/'.gps('plugin');
	
		if (is_file($plugin.'/index.php')) {
			
			include $plugin.'/index.php'; 
		}
		
		exit;
	}
	
	if (txpinterface == 'public' or gps('form_preview')) {
		
		include txpath.'/publish.php';
		
		textpattern();
		
		exit;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	define("SETUP",gps('setup',0));
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	header("Content-type: text/html; charset=utf-8");
	
	include txpath.'/admin.php';
	
?>
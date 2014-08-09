<?php
/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement.

$HeadURL: http://svn.textpattern.com/current/textpattern/setup/index.php $
$LastChangedRevision: 783 $

*/

if (!defined('txpath')) define("txpath", dirname(dirname(__FILE__)));	
if (!defined('txpinterface')) define("txpinterface", "admin");

error_reporting(E_ALL);
@ini_set("display_errors","1");

include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_misc.php'; 
include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/lib/txplib_misc.php';

header("Content-type: text/html; charset=utf-8");

$rel_siteurl = preg_replace('#^(.*)/(textpattern|admin)[/setuphindx.]*?$#i','\\1',$_SERVER['SCRIPT_NAME']);

print <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Textpattern &#8250; setup</title>
	<link rel="Stylesheet" href="$rel_siteurl/textpattern/theme/classic/textpattern.css" type="text/css" />
	<body class="setup" style="border-top:15px solid #FC3">
	<div align="center">
eod;

	$step = isPost('step');
	switch ($step) {	
		case "": chooseLang(); break;
		case "getDbInfo": getDbInfo(); break;
		case "getTxpLogin": getTxpLogin(); break;
		case "printConfig": printConfig(); break;
		case "createTxp": createTxp();
	}
?>
</div>
</body>
</html>
<?php

// dmp($_POST);

// -------------------------------------------------------------
	function chooseLang() 
	{
	  echo '<form action="index.php" method="post">',
	  	'<table id="setup" cellpadding="0" cellspacing="0" border="0">',
		tr(
			tda(
				hed('Welcome to Textpattern',3).
				graf('Please choose a language:'.br.langs()).
				graf(fInput('submit','Submit','Submit','publish')).
				sInput('getDbInfo')
			,' width="400" height="50" colspan="4" align="left"')
		),
		'</table></form>';
	}

// -------------------------------------------------------------
	function getDbInfo()
	{	
		global $txpcfg;
		
		$lang = isPost('lang');
		
		$GLOBALS['textarray'] = setup_load_lang($lang);
		
		$ddb     = '';
		$duser   = '';
		$dpass   = '';
		$dhost   = 'localhost';
		$dprefix = '';
		
		if (is_file(txpath.'/config.php')) {
			
			@include txpath.'/config.php';
			 
			if (isset($txpcfg)) {
				
				$ddb     = $txpcfg['db'];
				$duser   = $txpcfg['user'];
				$dpass   = $txpcfg['pass'];
				$dhost   = $txpcfg['host'];
				$dprefix = $txpcfg['table_prefix'];
			}
		}
		
		$temp_txpath = txpath;
		if (@$_SERVER['SCRIPT_NAME'] && (@$_SERVER['SERVER_NAME'] || @$_SERVER['HTTP_HOST']))
		{
			$guess_siteurl = (@$_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			$guess_siteurl .= $GLOBALS['rel_siteurl'];
		} else $guess_siteurl = 'mysite.com';
	  echo '<form action="index.php" method="post">',
	  	'<table id="setup" cellpadding="0" cellspacing="0" border="0">',
		tr(
			tda(
			  hed(gTxt('welcome_to_textpattern'),3). 
			  graf(gTxt('need_details'),' style="margin-bottom:3em"').
			  hed('MySQL',3).
			  graf(gTxt('db_must_exist'))
			,' width="400" height="50" colspan="4" align="left"')
		),
		tr(
			fLabelCell(gTxt('mysql_login')).fInputCell('duser',$duser,1).
			fLabelCell(gTxt('mysql_password')).fInputCell('dpass',$dpass,2)
		),
		tr(
			fLabelCell(gTxt('mysql_server')).fInputCell('dhost',$dhost,3).
			fLabelCell(gTxt('mysql_database')).fInputCell('ddb',$ddb,4)
		),
		tr(
			fLabelCell(gTxt('table_prefix')).fInputCell('dprefix',$dprefix,5).
			tdcs(small(gTxt('prefix_warning')),2)
		),
		tr(tdcs('&nbsp;',4)),
		tr(
			tdcs(
				hed(gTxt('site_path'),3).
				graf(gTxt('confirm_site_path')),4)
		),
		tr(
			fLabelCell(gTxt('full_path_to_txp')).
				tdcs(fInput('text','txpath',$temp_txpath,'edit','','',40).
				popHelp('full_path'),3)
		),
		tr(tdcs('&nbsp;',4)),
		tr(
			tdcs(
				hed(gTxt('site_url'),3).
				graf(gTxt('please_enter_url')),4)
		),
		tr(
			fLabelCell('http://').
				tdcs(fInput('text','siteurl',$guess_siteurl,'edit','','',40).
				popHelp('siteurl'),3)
		);
		if (!is_callable('mail'))
		{
			echo 
				tr(
					tdcs(gTxt('warn_mail_unavailable'),3,null,'" style="color:red;text-align:center')
				 );
		}
		echo
			tr(
				td().td(fInput('submit','Submit','Next','publish')).td().td()
			);
		echo endTable(),
		hInput('lang',$lang),
		sInput('printConfig'),
		'</form>'; 
	}

// -------------------------------------------------------------
	function printConfig()
	{
		global $txpcfg;
		
		$carry = enumPostItems(
			'ddb','duser','dpass','dhost','dprefix','txpath','siteurl','lang'
		);
		
		extract($carry);
		
		$txpath = preg_replace("/^(.*)\/$/","$1",$txpath);
		
		$path_to_site = explode('/',trim($txpath,'/'));
		array_pop($path_to_site);
		$path_to_site = '/'.implode('/',$path_to_site);
		
		$txpcfg = array(
			'db' 			=> $ddb,
			'user' 			=> $duser,
			'pass' 			=> $dpass,
			'host' 			=> $dhost,
			'table_prefix' 	=> $dprefix,
			'txpath' 		=> $txpath,
			'path_to_site' 	=> $path_to_site
		);
		
		$GLOBALS['textarray'] = setup_load_lang($lang);

		echo graf(gTxt("checking_database"));
		
		if (!($mylink = @mysql_connect($dhost,$duser,$dpass))){
			exit(graf(gTxt('db_cant_connect')).graf(mysql_error()));
		}
		
		echo graf(gTxt('db_connected'));	

		if (!$mydb = mysql_select_db($ddb)) {
			exit(graf(str_replace("{dbname}",strong($ddb),gTxt("db_doesnt_exist"))));
		}
		
		// TODO: allow installing with a prefix
		// if (mysql_query("describe `".$dprefix."textpattern`")) { die(); }
		
		if (mysql_query("describe `textpattern`")) { 
			die("Textpattern database table already exist. Can't run setup.");
		}
				
		// On 4.1 or greater use utf8-tables
		$version = mysql_get_server_info();
		$txpcfg['dbcharset'] = "latin1";
		$txpcfg['dbcollate'] = "";
		
		if (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)) {
			
			if (mysql_query("SET NAMES utf8")) {
				$txpcfg['dbcharset'] = "utf8";
				$txpcfg['dbcollate'] = "utf8_general_ci";
			}
		}
		
		$config = makeConfig($txpcfg);
		write_to_file(txpath.'/config.php',$config);
		
		if (!is_file("$txpath/setup/archive.sql")) {
		
			getTxpLogin($carry);
		
		} else {
			
			createTxp($carry,'archive.sql');
		} 
	}

// -------------------------------------------------------------
	function getTxpLogin($carry) 
	{
		global $txpcfg, $configfile, $subsiteid;
		
		extract($carry);

		$GLOBALS['textarray'] = setup_load_lang($lang);
		
		echo '<form action="index.php" method="post">',
	  	startTable('edit'),
		tr(
			tda(
				graf(gTxt('about_to_create'))
			,' width="400" colspan="2" align="center"')
		),
		tr(
			fLabelCell(gTxt('site_name')).fInputCell('sitename',gTxt('my_site'))
		),
		tr(
			fLabelCell(gTxt('your_full_name')).fInputCell('RealName')
		),
		tr(
			fLabelCell(gTxt('setup_login')).fInputCell('name')
		),
		tr(
			fLabelCell(gTxt('choose_password')).fInputCell('pass')
		),
		tr(
			fLabelCell(gTxt('your_email')).fInputCell('email')
		),
		tr(
			td().td(fInput('submit','Submit','Next','publish'))
		),
		endTable(),
		sInput('createTxp'),
		hInput('carry',postEncode($carry)),
		'</form>';
	}

// -------------------------------------------------------------
	function createTxp($carry='',$sql='txp.sql') 
	{
		global $txpcfg, $rel_siteurl;
		
		if (!$carry) {
			$carry = postDecode(isPost('carry'));
		}
		
		extract($carry);
		
		include txpath.'/config.php';
		include txpath.'/lib/txplib_update.php'; 
		
		$dbb   = $txpcfg['db'];
		$duser = $txpcfg['user'];
		$dpass = $txpcfg['pass'];
		$dhost = $txpcfg['host'];
		$dclient_flags = isset($txpcfg['client_flags']) ? $txpcfg['client_flags'] : 0;
		$dprefix = $txpcfg['table_prefix'];
		
		$GLOBALS['textarray'] = setup_load_lang($lang);

		$siteurl = str_replace("http://",'',$siteurl);
		$siteurl = rtrim($siteurl,"/");
		
		if (!defined('PFX')) define("PFX",trim($dprefix));
		if (!defined('TXP_INSTALL')) define('TXP_INSTALL', 1);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		mysql_connect($dhost,$duser,$dpass,false);
		mysql_select_db($ddb);

		if (mysql_query("describe `textpattern`")) {
			die("Textpattern database table already exist. Can't run setup.");
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$txpsql = txpath.'/setup/'.$sql;
		
 		if (is_file($txpsql)) {
 			
 			@system("mysql -h $dhost -u $duser --password=\"$dpass\" $dbb < $txpsql");
 			
 			if (!mysql_query("describe `textpattern`")) { 
 				
 				echo '<div class="error">Database archive import failed. 
 				Try importing the archive by other means.</div>';	
 				
 				exit;
 			}
 		}
 		
 		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 		
 		$do_update = false;
 		
 		if (!mysql_query("describe `textpattern`")) { 
 			
 			include txpath.'/setup/txpsql.php';
 			
 			$do_update = true;
 		}
 		
 		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 		
 		if (mysql_query("describe `textpattern`")) { 
 		
			// This has to come after txpsql.php, because otherwise we can't call mysql_real_escape_string
			extract(sDoSlash(gpsa(array('sitename','name','pass','RealName','email'))));
	
			$nonce = md5( uniqid( rand(), true ) );
			$path_to_site = $txpcfg['path_to_site'];
			$path_to_txp  = txpath;
			
			mysql_query("update txp_prefs set val = '$siteurl' where `name`='siteurl'");
			mysql_query("update txp_prefs set val = '$path_to_txp/tmp' where `name`='tempdir'");
			mysql_query("update txp_prefs set val = '$path_to_site' where `name`='path_to_site'");
			mysql_query("update txp_prefs set val = '$path_to_site/files' where `name`='file_base_path'");
			
			if ($sitename) {  
				mysql_query("update txp_prefs set val = '$sitename' where `name`='sitename'");
				mysql_query("update txp_prefs set val = '$lang' where `name`='language'");
				mysql_query("update txp_prefs set val = '".getlocale($lang)."' where `name`='locale'");
			}
			
			if ($do_update) {
				
				mysql_query("INSERT INTO txp_users VALUES
					(1,'$name',password(lower('$pass')),'$RealName','$email',1,now(),'$nonce')");

				echo '<div class="update">';
				echo '<iframe class="update" src="../index.php?update=SELF&setup=1" width="650" height="350"></iframe>';
				echo '</div>';
			
			} else {
				
				$tree_tables = array(
					'textpattern', 
					'txp_image',
					'txp_file',
					'txp_link',
					'txp_discuss',
					'txp_category',
					'txp_custom',
					'txp_page',
					'txp_form',
					'txp_css',
					'txp_users'
				);
					
				if ($sql != 'archive.sql') {
				
					mysql_query("INSERT INTO txp_users VALUES
						(1,'$name',password(lower('$pass')),'$RealName','$email',1,now(),'$nonce','',0)");
	
					mysql_query("DELETE FROM `txp_lang`");
					
					install_language_from_file($lang);
					
					$title = $sitename;
					$name  = make_name($sitename);
					
					mysql_query("UPDATE textpattern SET Name = '$name', Title = '$title' WHERE ParentID = 0");
					$r = mysql_query("SELECT ID FROM textpattern WHERE ParentID = 0");
					$id = (mysql_num_rows($r) > 0) ? mysql_result($r,0) : 0;
					mysql_query("UPDATE textpattern SET ParentName = '$name', ParentTitle = '$title' WHERE ParentID = $id");
					
					foreach ($tree_tables as $table) {
						mysql_query("UPDATE $table SET Posted = now(), AuthorID = 'textpattern'");
					}
				
				} else {
					
					$htaccess = "$path_to_site/.htaccess";
					$index    = "$path_to_site/index.php";
					
					// copy htaccess and index.php file to site root dir
		
					// copy("$path_to_txp/setup/www/_htaccess",$htaccess);
					// copy("$path_to_txp/setup/www/index.php",$index);
					
					// create database and log directories 
					
					if (!is_dir("$path_to_site/database")) {
						@mkdir("$path_to_site/database",0711);
					}
					
					if (!is_dir("$path_to_site/log")) {
						@mkdir("$path_to_site/log",0777);
					}
					
					if (!is_dir("$path_to_site/tmp")) {
						@mkdir("$path_to_site/tmp",0777);
					}
					
					// set RewriteBase in htaccess if url is IP address and using tilda ~
					
					if ($_SERVER['SERVER_ADDR'] == $_SERVER['SERVER_NAME']) {
						
						if (preg_match('/(\/~[a-z0-9]+)/',$siteurl,$matches)) {
							
							$base = $matches[1];
							
							if (is_file($htaccess)) {
								
								@chmod($htaccess,0777);
								
								if (is_writable($htaccess)) {
								 
									$content = file_get_contents($htaccess);
									$content = preg_replace("/(RewriteBase) \/\n/","$1 $base\n",$content);
									write_to_file($htaccess,$content);
								}
								
								@chmod($htaccess,0644);
							}
						}
					}
					
					// delete archive file
					
					// unlink($txpsql);
					
					// clear caches
					
					mysql_query("UPDATE txp_users SET session = '', updated = 1");
					mysql_query("DELETE FROM txp_window WHERE 1 = 1");
					mysql_query("DELETE FROM txp_cache WHERE 1 = 1");
					
					// delete trashed items 
					
					foreach ($tree_tables as $table) {
						mysql_query("DELETE FROM $table WHERE Trash > 0");
					}
				}
				
				echo '<div class="success">'.n;
				echo graf(gTxt('that_went_well')).n;
				echo graf(str_replace('"index.php"','"'.$rel_siteurl.'/admin/index.php"',gTxt('you_can_access'))).n;
				echo graf(gTxt('thanks_for_interest'));
				echo '</div>'.n;
			}
		}
	}

// -------------------------------------------------------------
	function isPost($val)
	{
		if(isset($_POST[$val])) {
			return (get_magic_quotes_gpc()) 
			?	stripslashes($_POST[$val])
			:	$_POST[$val];						
		} 
		return '';
	}

// -------------------------------------------------------------
	function makeConfig($config) 
	{
		define("nl","';\n");
		define("o",'$txpcfg[\'');
		define("m","'] = '");
		$open = chr(60).'?php';
		$close = '?'.chr(62);
		extract($config);
		return
		$open."\n".
		o.'db'			  .m.$db.nl
		.o.'user'		  .m.$user.nl
		.o.'pass'		  .m.$pass.nl
		.o.'host'		  .m.$host.nl
		.o.'table_prefix' .m.$table_prefix.nl
		.o.'txpath'		  .m.$txpath.nl
		.o.'path_to_site' .m.$path_to_site.nl
		.o.'dbcharset'	  .m.$dbcharset.nl
		.o.'dbcollate'	  .m.$dbcollate.nl
		.$close;
	}

// -------------------------------------------------------------
/*	function fbCreate($user) 
	{
		if ($GLOBALS['txp_install_successful']===false)
			
			return
			'<div width="450" valign="top" style="margin-left:auto;margin-right:auto">'.
			graf(str_replace('{num}',$GLOBALS['txp_err_count'],gTxt('errors_during_install')),' style="margin-top:3em"').
			'</div>';
		}
	}
*/
// -------------------------------------------------------------
	function postEncode($thing)
	{
		return base64_encode(serialize($thing));
	}

// -------------------------------------------------------------
	function postDecode($thing)
	{
		return unserialize(base64_decode($thing));
	}

// -------------------------------------------------------------
	function enumPostItems() 
	{
		foreach(func_get_args() as $item) { $out[$item] = isPost($item); }
		return $out; 
	}

//-------------------------------------------------------------
	function langs() 
	{
		$things = array(
			'en-gb' => 'English (GB)',
			'en-us' => 'English (US)',
			'fr-fr' => 'Fran&#231;ais',
			'es-es' => 'Espa&#241;ol',
			'da-dk' => 'Dansk',
			'el-gr' => '&#917;&#955;&#955;&#951;&#957;&#953;&#954;&#940;',
			'sv-se' => 'Svenska',
			'it-it' => 'Italiano',
			'cs-cz' => '&#268;e&#353;tina',
			'ja-jp' => '&#26085;&#26412;&#35486;',
			'de-de' => 'Deutsch',
			'no-no' => 'Norsk',
			'pt-pt' => 'Portugu&#234;s',
			'ru-ru' => '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
			'sk-sk' => 'Sloven&#269;ina',
			'th-th' => '&#3652;&#3607;&#3618;',
			'nl-nl' => 'Nederlands'
		);

		$out = '<select name="lang">';

		foreach ($things as $a=>$b) {
			$out .= '<option value="'.$a.'">'.$b.'</option>'.n;
		}		

		$out .= '</select>';
		return $out;
	}
	
// -------------------------------------------------------------
	function setup_load_lang($lang) 
	{
		include txpath.'/setup/setup-langs.php';
		$lang = (isset($langs[$lang]) && !empty($langs[$lang]))? $lang : 'en-gb';
		if (!defined('LANG')) define('LANG', $lang);
		return $langs[LANG];
	}

// -------------------------------------------------------------
	function sDoSlash($in)
	{ 
		if(phpversion() >= "4.3.0") {
			return doArray($in,'mysql_real_escape_string');
		} else {
			return doArray($in,'mysql_escape_string');
		}
	}


?>

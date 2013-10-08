<?php
/*
$HeadURL: $
$LastChangedRevision: $
*/
	
$old_level = error_reporting(E_ALL ^ (E_NOTICE));

define('VERSION','4.2.0');

define('TXP_DEBUG', 0);
define('DEBUG', 1);

define('SPAM', 2);
define('HIDDEN', 2);
define('MODERATE', 3);
define('VISIBLE', 4);
define('LIVE', 4);
define('STICKY', 5);
define('RELOAD', -99);

define('RPC_SERVER', 'http://rpc.textpattern.com');

define('LEAVE_TEXT_UNTOUCHED', 0);
define('USE_TEXTILE', 1);
define('CONVERT_LINEBREAKS', 2);
define('CONVERT_PARAGRAPHS', 3);

if (defined('DIRECTORY_SEPARATOR'))
	define('DS', DIRECTORY_SEPARATOR);
else
	define ('DS', (is_windows() ? '\\' : '/'));

define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

define('NOCACHE','?'.time()); // to trick the browser into reloading a file

// BUG: causes fatal error: method not found in class
// define('REGEXP_UTF8', @preg_match('@\pL@u', 'q')); 
define('REGEXP_UTF8',0); 

define('NULLDATETIME', '\'0000-00-00 00:00:00\'');

define('PERMLINKURL', 0);
define('PAGELINKURL', 1);

define('EXTRA_MEMORY', '32M');

define('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' );
define('IS_FASTCGI', IS_CGI and empty($_SERVER['FCGI_ROLE']) and empty($_ENV['FCGI_ROLE']) );
define('IS_APACHE', !IS_CGI and substr(PHP_SAPI, 0, 6) == 'apache' );

define('PREF_PRIVATE', true);
define('PREF_GLOBAL', false);
define('PREF_BASIC', 0);
define('PREF_ADVANCED', 1);
define('PREF_HIDDEN', 2);

define('PLUGIN_HAS_PREFS', 0x0001);
define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002);
define('PLUGIN_RESERVED_FLAGS', 0x0fff); // reserved bits for use by Textpattern core

define("t","\t");
define("n","\n");
define("br","<br />");
define("sp","&#160;");
define("a","&#38;");
define("line","\n\n<!-- ".str_pad('',120,'- ')."-->\n\n");

	
error_reporting($old_level);
unset($old_level);

$areas = array(
	'list'  	=> 'content',
	'image' 	=> 'content',
	'file' 		=> 'content',
	'link' 		=> 'content',
	'category' 	=> 'content',
	'discuss'	=> 'content',
	'article'	=> 'content',
	'custom'	=> 'content',
	'page'		=> 'presentation',
	'form'		=> 'presentation',
	'css'		=> 'presentation',
	'utilities'	=> 'admin',
	'prefs'		=> 'admin',
	'admin'		=> 'admin',
	'log'		=> 'admin',
	'plugin'	=> 'admin',
	'import'	=> 'admin',
	'sites'		=> 'admin'
);

?>
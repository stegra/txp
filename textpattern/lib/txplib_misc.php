<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/lib/txplib_misc.php $
$LastChangedRevision: 3271 $
*/

	include txpath.'/lib/classMyArray.php';
	include txpath.'/lib/classSession.php';
	include txpath.'/lib/classTimezone.php';
	include txpath.'/lib/classStack.php';
	include txpath.'/lib/classArticleStack.php';
	include txpath.'/lib/txplib_filesystem.php';
	
// -------------------------------------------------------------
	function doArray($in,$function)
	{
		return is_array($in) ? array_map($function,$in) : $function($in);
	}

// -------------------------------------------------------------
	function doStrip($in)
	{
		return doArray($in,'stripslashes');
	}

// -------------------------------------------------------------
	function doStripTags($in)
	{
		return doArray($in,'strip_tags');
	}

// -------------------------------------------------------------
	function doDeEnt($in)
	{
		return doArray($in,'deEntBrackets');
	}

// -------------------------------------------------------------
	function deEntBrackets($in)
	{
		$array = array(
			'&#60;'  => '<',
			'&lt;'   => '<',
			'&#x3C;' => '<',
			'&#62;'  => '>',
			'&gt;'   => '>',
			'&#x3E;' => '>'
		);

		foreach($array as $k=>$v){
			$in = preg_replace("/".preg_quote($k)."/i",$v, $in);
		}
		return $in;
	}

// -------------------------------------------------------------
	function doSlash($in)
	{
		return doArray($in,'mysql_real_escape_string');
	}

// -------------------------------------------------------------
	function doSpecial($in)
	{
		return doArray($in,'htmlspecialchars');
	}

// -------------------------------------------------------------
	function doAnd($in)
	{
		if (is_array($in)) {
		
			foreach($in as $key => $value) {
			
				if (!strlen($value)) unset($in[$key]);
			}
		
			return implode(' AND ',$in);
		}
		
		return $in;
	}

// -------------------------------------------------------------
	function _null($a)
	{
		return NULL;
	}
// -------------------------------------------------------------
	function array_null($in)
	{
		return array_map('_null', $in);
	}

// -------------------------------------------------------------
	function escape_title($title)
	{
		return strtr($title,
			array(
				'<' => '&#60;',
				'>' => '&#62;',
				"'" => '&#39;',
				'"' => '&#34;',
			)
		);
	}

// -------------------------------------------------------------
// deprecated in 4.2.0
	function escape_output($str)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'htmlspecialchars')), E_USER_NOTICE);
		return htmlspecialchars($str);
	}

// -------------------------------------------------------------
// deprecated in 4.2.0
	function escape_tags($str)
	{
		trigger_error(gTxt('deprecated_function', array('{name}' => __FUNCTION__)), E_USER_NOTICE);
		return strtr($str,
			array(
				'<' => '&#60;',
				'>' => '&#62;',
			)
		);
	}

// -------------------------------------------------------------
	function escape_cdata($str)
	{
		return '<![CDATA['.str_replace(']]>', ']]]><![CDATA[]>', $str).']]>';
	}

//-------------------------------------------------------------
// change: make $atts attribute compatible with 4.0 version

	function gTxt($var, $atts=array())
	{
		global $textarray;
		
		if (!is_array($atts)) $atts = array($atts); // new
		
		if(isset($textarray[strtolower($var)])) {
			$out = $textarray[strtolower($var)];
			return strtr($out, $atts);
		}
		
		if (!isset($textarray)) {
			
			$lang = get_pref('language');
			
			if ($text = safe_field("data","txp_lang","name = '$var' AND lang = '$lang'")) {
				return $text;
			}
		}

		if ($atts)
			return $var.': '.join(', ', $atts);
		return $var;
	}

//-------------------------------------------------------------
	function gTime($timestamp)
	{
		return safe_strftime('%d&#160;%b&#160;%Y %X', $timestamp);
	}

// -------------------------------------------------------------
	function dmp()
	{
		static $f = FALSE;
		
		if(defined('txpdmpfile'))
		{
			global $prefs;

			if(!$f) $f = fopen($prefs['tempdir'].'/'.txpdmpfile, 'a');

			$stack = get_caller();
			fwrite($f, "\n[".$stack[0].t.safe_strftime('iso8601')."]\n");
		}

		$a = func_get_args();

		if(!$f) echo "<pre class=\"dump\">".n;

		foreach ($a as $thing)
		{
			$out = is_scalar($thing) ? strval($thing) : var_export($thing, true);

			if ($f)
			{
				fwrite($f, $out."\n");
			}
			else
			{
				echo htmlspecialchars($out), n;
			}
		}

		if(!$f) echo "</pre>".n;
	}

// -------------------------------------------------------------
	function load_lang($lang)
	{
		global $txpcfg;

		foreach(array($lang, 'en-gb') as $lang_code)
		{
			$rs = (txpinterface == 'admin')
				? safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."'")
				: safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."' AND ( event='public' OR event='common')");

			if (mysql_num_rows($rs)) break;
		}

		$out = array();

		if ($rs && mysql_num_rows($rs) > 0)
		{
			while ($a = nextRow($rs))
			{
				$out[$a['name']] = $a['data'];
			}
		}else{
			#backward compatibility stuff. Remove when necessary.
			$filename = is_file(txpath.'/lang/'.$lang.'.txt')
			?	txpath.'/lang/'.$lang.'.txt'
			:	txpath.'/lang/en-gb.txt';

			$file = @fopen($filename, "r");
			if ($file) {
				while (!feof($file)) {
					$line = fgets($file, 4096);
				if($line[0]=='#') continue;
				@list($name,$val) = explode(' => ',trim($line));
				$out[$name] = $val;
			 }
				@fclose($filename);
			}
		}

		return $out;
	}

// -------------------------------------------------------------
	function load_lang_dates($lang)
	{
		global $txpcfg;
		$filename = is_file(txpath.'/lang/'.$lang.'_dates.txt')?
			txpath.'/lang/'.$lang.'_dates.txt':
			txpath.'/lang/en-gb_dates.txt';
		$file = @file(txpath.'/lang/'.$lang.'_dates.txt','r');
		if(is_array($file)) {
			foreach($file as $line) {
				if($line[0]=='#' || strlen($line) < 2) continue;
				list($name,$val) = explode('=>',$line,2);
				$out[trim($name)] = trim($val);
			}
			return $out;
		}
		return false;
	}
// -------------------------------------------------------------

	function load_lang_event($event)
	{
		global $txpcfg;
		$lang = LANG;

		$installed = safe_field('name', 'txp_lang',"lang='".doSlash($lang)."' limit 1");

		$lang_code = ($installed)? $lang : 'en-gb';

		$rs = safe_rows_start('name, data','txp_lang',"lang='".doSlash($lang_code)."' AND event='".doSlash($event)."'");

		$out = array();

		if ($rs && !empty($rs))
		{
			while ($a = nextRow($rs))
			{
				$out[$a['name']] = $a['data'];
			}
		}
		return ($out) ? $out : '';
	}

// -------------------------------------------------------------
	function check_privs()
	{
		global $txp_user;
		$privs = safe_field("privs", "txp_users", "name='".doSlash($txp_user)."'");
		$args = func_get_args();
		if(!in_array($privs,$args)) {
			exit(pageTop('Restricted').'<p style="margin-top:3em;text-align:center">'.
				gTxt('restricted_area').'</p>');
		}
	}

// -------------------------------------------------------------
	function add_privs($res, $perm = '1') // perm = '1,2,3'
	{
		global $txp_permissions;
		// Don't let them override privs that exist
		if (!isset($txp_permissions[$res]))
			$txp_permissions[$res] = $perm;
	}

// -------------------------------------------------------------------------------------
// change: administrator user
/*	
	function has_privs($res, $user='')
	{
		global $txp_user, $txp_permissions;

		// If no user name is supplied, assume the current login name
		if (empty($user))
			$user = $txp_user;

		$privs = safe_field("privs", "txp_users", "`name`='".doSlash($user)."'");
		
		if (@$txp_permissions[$res])
			$req = expl($txp_permissions[$res]);
		else
			$req = array('1','7'); // The Publisher gets prived for anything
			
		return in_array($privs, $req);
	}
*/
// -------------------------------------------------------------
	function has_privs($res, $user='')
	{
		global $txp_user, $txp_permissions;
		static $privs;
		
		// If no user name is supplied, assume the current login name
		if (empty($user))
			$user = $txp_user;
		
		if (!isset($privs[$user]))
		{
			$privs[$user] = safe_field("privs", "txp_users", "name='".doSlash($user)."'");
		}
		
		if (isset($txp_permissions[$res]))
		{
			return in_array($privs[$user], expl($txp_permissions[$res]));
		}
		else
		{
			return false;
		}
	}

// -------------------------------------------------------------
	function require_privs($res, $user='')
	{	
		if (!has_privs($res, $user))
			exit(pageTop('Restricted').'<p style="margin-top:3em;text-align:center">'.
				gTxt('restricted_area').'</p>');
	}

// -------------------------------------------------------------
	function sizeImage($name)
	{
		$size = @getimagesize($name);
		return(is_array($size)) ? $size[3] : false;
	}
	
// -------------------------------------------------------------
// new: checks GET for a named variable

	function get($thing,$default=NULL,$allow='') 
	{
		if (isset($_GET[$thing])) {
			
			if (MAGIC_QUOTES_GPC) {
				$value = doStrip($_GET[$thing]);
			} else {
				$value = $_GET[$thing];
			}
			
			if ($allow) {
				
				$allow = (!is_array($allow)) ? do_list($allow) : $allow;
				
				if (!in_array($value,$allow)) {
					
					$value = (!is_array($default)) ? $default : false;
				}
			}
			
			return ($value) ? $value : $default;
		}
		
		return $default;
	}

// -------------------------------------------------------------
// checks POST for a named variable, or creates it blank
// change: default value
// change: allowed value

	function ps($thing,$default=NULL,$allow='') 
	{
		if (isset($_POST[$thing])) {
			
			if (MAGIC_QUOTES_GPC) {
				$value = doStrip($_POST[$thing]);
			} else {
				$value = $_POST[$thing];
			}
			
			if ($allow) {
				
				if (!is_array($allow) and preg_match('/^\/.+\/$/',$allow)) {
					
					if (!preg_match($allow,$value)) {
						
						$value = $default;
					}
					
				} else {
				
					$allow = (!is_array($allow)) ? do_list($allow) : $allow;
					
					if (!in_array($value,$allow)) {
						
						$value = (!is_array($default)) ? $default : false;
					}
				}
			}
			
			return ($value) ? $value : $default;
		}
		
		return $default;
	}
	
// -------------------------------------------------------------
// checks GET and POST for a named variable, or creates it blank
// change: create with default value

	function gps($thing,$default='') 
	{
		// check POST
		
		if (isset($_POST[$thing])) {
		
			return ps($thing,$default);
		}
		
		// check GET
		
		if (isset($_GET[$thing])) {
		
			return get($thing,$default);
		}
		
		return $default;
	}

// -------------------------------------------------------------
// performs gps() on an array of variable names

	function gpsa($array) 
	{
		if(is_array($array)) {
			$out = array();
			foreach($array as $a) {
				$out[$a] = gps($a);
			}
			return $out;
		}
		return false;
	}

// -------------------------------------------------------------
// performs ps on an array of variable names

	function psa($array) 
	{
		foreach($array as $a) {
			$out[$a] = ps($a);
		}
		return $out;
	}

// -------------------------------------------------------------
// same as above, but does strip_tags on post values

	function psas($array) 
	{
		foreach($array as $a) {
			$out[$a] = strip_tags(ps($a));
		}
		return $out;
	}

// -------------------------------------------------------------
	function stripPost()
	{
		if (isset($_POST)) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_POST);
			} else {
				return $_POST;
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function serverSet($thing) // Get a var from $_SERVER global array, or create it
	{
		return (isset($_SERVER[$thing])) ? $_SERVER[$thing] : '';
	}

// -------------------------------------------------------------
	function remote_addr()
	{
		$ip = serverSet('REMOTE_ADDR');
		if (($ip == '127.0.0.1' || $ip == serverSet('SERVER_ADDR')) && serverSet('HTTP_X_FORWARDED_FOR')) {
			$ips = explode(', ', serverSet('HTTP_X_FORWARDED_FOR'));
			$ip = $ips[0];
		}
		return $ip;
	}

// -------------------------------------------------------------
 	function pcs($thing) //	Get a var from POST or COOKIE; if not, create it
	{
		if (isset($_COOKIE["txp_".$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_COOKIE["txp_".$thing]);
			} else return $_COOKIE["txp_".$thing];
		} elseif (isset($_POST[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_POST[$thing]);
			} else return $_POST[$thing];
		}
		return '';
	}

// -------------------------------------------------------------
 	function cs($thing) //	Get a var from COOKIE; if not, create it
	{
		if (isset($_COOKIE[$thing])) {
			if (MAGIC_QUOTES_GPC) {
				return doStrip($_COOKIE[$thing]);
			} else return $_COOKIE[$thing];
		}
		return '';
	}

// -------------------------------------------------------------
	function yes_no($status)
	{
		return ($status==0) ? (gTxt('no')) : (gTxt('yes'));
	}

// -------------------------------------------------------------
	function getmicrotime($name='')
	{	
		static $start = array();
		
		if ($name) { 
		
			if (!isset($start[$name])) {
				
				$start[$name] = getmicrotime();
			
			} else {
				
				$now  = getmicrotime();
				
				$elapsed = $now - $start[$name];
				
				// unset($start[$name]);
				
				$start[$name] = $now;
				
				return substr($elapsed,0,6);
			}
		
		} else {
		
			list($usec, $sec) = explode(" ",microtime());
			return ((float)$usec + (float)$sec);
		}
	}

// -------------------------------------------------------------
	function load_plugin($name, $force=false)
	{
		global $plugins, $plugins_ver, $prefs, $txp_current_plugin;

		if (is_array($plugins) and in_array($name,$plugins)) {
			return true;
		}

		if (!empty($prefs['plugin_cache_dir'])) {
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';
			# in case it's a relative path
			if (!is_dir($dir))
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			if (is_file($dir . $name . '.php')) {
				$plugins[] = $name;
				set_error_handler("pluginErrorHandler");
				if (isset($txp_current_plugin)) $txp_parent_plugin = $txp_current_plugin;
				$txp_current_plugin = $name;
				include($dir . $name . '.php');
				$txp_current_plugin = (isset($txp_parent_plugin) ? $txp_parent_plugin : NULL);
				$plugins_ver[$name] = @$plugin['version'];
				restore_error_handler();
				return true;
			}
		}

		$rs = safe_row("name,code,version","txp_plugin", ($force ? '' : 'status = 1 AND '). "name='".doSlash($name)."'");
		if ($rs) {
			$plugins[] = $rs['name'];
			$plugins_ver[$rs['name']] = $rs['version'];

			set_error_handler("pluginErrorHandler");
			if (isset($txp_current_plugin)) $txp_parent_plugin = $txp_current_plugin;
			$txp_current_plugin = $rs['name'];
			eval($rs['code']);
			$txp_current_plugin = (isset($txp_parent_plugin) ? $txp_parent_plugin : NULL);
			restore_error_handler();

			return true;
		}

		return false;
	}

// -------------------------------------------------------------
	function require_plugin($name)
	{
		if (!load_plugin($name)) {
			trigger_error("Unable to include required plugin \"{$name}\"",E_USER_ERROR);
			return false;
		}
		return true;
	}

// -------------------------------------------------------------
	function include_plugin($name)
	{
		if (!load_plugin($name)) {
			trigger_error("Unable to include plugin \"{$name}\"",E_USER_WARNING);
			return false;
		}
		return true;
	}

// -------------------------------------------------------------
	function pluginErrorHandler($errno, $errstr, $errfile, $errline)
	{
		$error = array( E_WARNING => "Warning", E_NOTICE => "Notice", E_USER_ERROR => "User_Error",
						E_USER_WARNING => "User_Warning", E_USER_NOTICE => "User_Notice");

		if (!($errno & error_reporting())) return;

		global $txp_current_plugin, $production_status;
		printf ("<pre>".gTxt('plugin_load_error').' <b>%s</b> -> <b>%s: %s on line %s</b></pre>',
				$txp_current_plugin, $error[$errno], $errstr, $errline);
		if ($production_status == 'debug')
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".htmlspecialchars(join("\n", get_caller(10)))."</code></pre>";
	}

// -------------------------------------------------------------
	function tagErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		$error = array( E_WARNING => "Warning", E_NOTICE => "Notice", E_USER_ERROR => "Textpattern Error",
						E_USER_WARNING => "Textpattern Warning", E_USER_NOTICE => "Textpattern Notice");

		if (!($errno & error_reporting())) return;
		if ($production_status == 'live') return;

		global $txp_current_tag;
		$errline = ($errstr === 'unknown_tag') ? '' : " on line $errline";
		printf ("<pre>".gTxt('tag_error').' <b>%s</b> -> <b> %s: %s %s</b></pre>',
				htmlspecialchars($txp_current_tag), $error[$errno], $errstr, $errline );
		if ($production_status == 'debug')
			{
			print "\n<pre style=\"padding-left: 2em;\" class=\"backtrace\"><code>".htmlspecialchars(join("\n", get_caller(10)))."</code></pre>";

			$trace_msg = gTxt('tag_error').' '.$txp_current_tag.' -> '.$error[$errno].': '.$errstr.' '.$errline;
			trace_add( $trace_msg );
			}
	}

// -------------------------------------------------------------
	function feedErrorHandler($errno, $errstr, $errfile, $errline)
	{
		global $production_status;

		if ($production_status != 'debug') return;

		return tagErrorHandler($errno, $errstr, $errfile, $errline);
	}

// -------------------------------------------------------------
	function load_plugins($type=0)
	{
		global $prefs, $plugins, $plugins_ver;
		
		return;
		
		if (!is_array($plugins)) $plugins = array();

		if (!empty($prefs['plugin_cache_dir'])) {
			$dir = rtrim($prefs['plugin_cache_dir'], '/') . '/';
			// in case it's a relative path
			if (!is_dir($dir))
				$dir = rtrim(realpath(txpath.'/'.$dir), '/') . '/';
			$files = glob($dir.'*.php');
			if ($files) {
				natsort($files);
				foreach ($files as $f) {
					load_plugin(basename($f, '.php'));
				}
			}
		}

		$where = 'status = 1 AND type IN ('.($type ? '1,3' : '0,1').')';

		$rs = safe_rows("name, code, version", "txp_plugin", $where.' order by load_order');
		if ($rs) {
			$old_error_handler = set_error_handler("pluginErrorHandler");
			foreach($rs as $a) {
				if (!in_array($a['name'],$plugins)) {
					$plugins[] = $a['name'];
					$plugins_ver[$a['name']] = $a['version'];
					$GLOBALS['txp_current_plugin'] = $a['name'];
					$eval_ok = eval($a['code']);
					if ($eval_ok === FALSE)
						echo gTxt('plugin_load_error_above').strong($a['name']).n.br;
					unset($GLOBALS['txp_current_plugin']);
				}
			}
			restore_error_handler();
		}
	}

// -------------------------------------------------------------
	function register_callback($func, $event, $step='', $pre=0)
	{
		global $plugin_callback;

		$plugin_callback[] = array('function'=>$func, 'event'=>$event, 'step'=>$step, 'pre'=>$pre);
	}

// -------------------------------------------------------------
	function register_page_extension($func, $event, $step='', $top=0)
	{
		# For now this just does the same as register_callback
		register_callback($func, $event, $step, $top);
	}

// -------------------------------------------------------------
	function plugin_callback($pre=0)
	{
		global $plugin_list, $production_status;
		
		$trace = debug_backtrace();
		$function = $trace[1]['function'];
		
		if ($plugin_list) {
		
			foreach ($plugin_list as $plugin) {
				
				$plugin_function  = $plugin.'_'.$function;
				$plugin_function .= ($pre) ? '_'.$pre : '';
				
				if (is_callable($plugin_function)) {
					
					$args = func_get_args();
					array_shift($args);
					call_user_func_array($plugin_function,$args);
				}
			}
		}
	}
	
// -------------------------------------------------------------
	function callback_event($event, $step='', $pre=0)
	{
		global $plugin_callback, $production_status;
		
		if (!is_array($plugin_callback))
			return '';

		$return_value = '';
		
		// any payload parameters?
		$argv = func_get_args();
		$argv = (count($argv) > 3) ? array_slice($argv, 3) : array();

		foreach ($plugin_callback as $c) {
			if ($c['event'] == $event and (empty($c['step']) or $c['step'] == $step) and $c['pre'] == $pre) {
				if (is_callable($c['function'])) {
					$return_value .= call_user_func_array($c['function'], array('event' => $event, 'step' => $step) + $argv);
				} elseif ($production_status == 'debug') {
					trigger_error(gTxt('unknown_callback_function', array('function' => $c['function'])), E_USER_WARNING);
				}
			}
		}
		return $return_value;
	}

// -------------------------------------------------------------
	function register_tab($area, $event, $title)
	{
		global $plugin_areas;

		if (!isset($GLOBALS['event']) || ($GLOBALS['event'] !== 'plugin'))
		{
			$plugin_areas[$area][$title] = $event;
		}
	}

// -------------------------------------------------------------
	function pluggable_ui($event, $element, $default='')
	{
		$argv = func_get_args();
		$argv = array_slice($argv, 2);
		// custom user interface, anyone?
		// signature for called functions:
		// string my_called_func(string $event, string $step, string $default_markup[, mixed $context_data...])
		$ui = call_user_func_array('callback_event', array('event' => $event, 'step' => $element, 'pre' => 0) + $argv);
		// either plugins provided a user interface, or we render our own
		return ($ui === '')? $default : $ui;
	}

// -------------------------------------------------------------
	function select_buttons()
	{
		return
		gTxt('select').': '.
		fInput('button','selall',gTxt('all'),'smallerboxsp','select all','selectall();').
		fInput('button','selnone',gTxt('none'),'smallerboxsp','select none','deselectall();').
		fInput('button','selrange',gTxt('range'),'smallerboxsp','select range','selectrange();');
	}

// -------------------------------------------------------------
	function stripSpace($text, $force=0)
	{
		global $prefs;
		if ($force or !empty($prefs['attach_titles_to_permalinks']))
		{
			$text = sanitizeForUrl($text);
			if ($prefs['permalink_title_format']) {
				return (function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text));
			} else {
				return str_replace('-','',$text);
			}
		}
	}

// -------------------------------------------------------------
	function sanitizeForUrl($text)
	{
		// any overrides?
		$out = callback_event('sanitize_for_url', '', 0, $text);
		if ($out !== '') return $out;

		// Remove names entities and tags
		$text = preg_replace("/(^|&\S+;)|(<[^>]*>)/U","",dumbDown($text));
		// Dashify high-order chars leftover from dumbDown()
		$text = preg_replace("/[\x80-\xff]/","-",$text);
		// Collapse spaces, minuses, (back-)slashes and non-words
		$text = preg_replace('/[\s\-\/\\\\]+/', '-', trim(preg_replace('/[^\w\s\-\/\\\\]/', '', $text)));
		// Remove all non-whitelisted characters
		$text = preg_replace("/[^A-Za-z0-9\-_]/","",$text);
		return $text;
	}

// -------------------------------------------------------------
	function sanitizeForFile($text)
	{
		// any overrides?
		$out = callback_event('sanitize_for_file', '', 0, $text);
		if ($out !== '') return $out;
		
		// Remove control characters and " * \ : < > ? / |
		$text = preg_replace('/[\x00-\x1f\x22\x2a\x2f\x3a\x3c\x3e\x3f\x5c\x7c\x7f]+/', '', $text);
		// Remove duplicate dots and any leading or trailing dots/spaces
		$text = preg_replace('/[.]{2,}/', '.', trim($text, '. '));
		
		// $text = dumbDown($text);
		// $text = preg_replace('/[^\w\d\-\.]+/','_',$text);
		$text = preg_replace('/[\,\'\"\!]/','',$text);
		$text = preg_replace('/\s+/','_',$text);
		$text = preg_replace('/\_\_+/','_',$text);
		
		return $text;
	}

// -------------------------------------------------------------
	function dumbDown($str, $lang=LANG)
	{
		static $array;
		if (empty($array[$lang])) {
			$array[$lang] = array( // nasty, huh?.
				'&#192;'=>'A','&Agrave;'=>'A','&#193;'=>'A','&Aacute;'=>'A','&#194;'=>'A','&Acirc;'=>'A',
				'&#195;'=>'A','&Atilde;'=>'A','&#196;'=>'Ae','&Auml;'=>'A','&#197;'=>'A','&Aring;'=>'A',
				'&#198;'=>'Ae','&AElig;'=>'AE',
				'&#256;'=>'A','&#260;'=>'A','&#258;'=>'A',
				'&#199;'=>'C','&Ccedil;'=>'C','&#262;'=>'C','&#268;'=>'C','&#264;'=>'C','&#266;'=>'C',
				'&#270;'=>'D','&#272;'=>'D','&#208;'=>'D','&ETH;'=>'D',
				'&#200;'=>'E','&Egrave;'=>'E','&#201;'=>'E','&Eacute;'=>'E','&#202;'=>'E','&Ecirc;'=>'E','&#203;'=>'E','&Euml;'=>'E',
				'&#274;'=>'E','&#280;'=>'E','&#282;'=>'E','&#276;'=>'E','&#278;'=>'E',
				'&#284;'=>'G','&#286;'=>'G','&#288;'=>'G','&#290;'=>'G',
				'&#292;'=>'H','&#294;'=>'H',
				'&#204;'=>'I','&Igrave;'=>'I','&#205;'=>'I','&Iacute;'=>'I','&#206;'=>'I','&Icirc;'=>'I','&#207;'=>'I','&Iuml;'=>'I',
				'&#298;'=>'I','&#296;'=>'I','&#300;'=>'I','&#302;'=>'I','&#304;'=>'I',
				'&#306;'=>'IJ',
				'&#308;'=>'J',
				'&#310;'=>'K',
				'&#321;'=>'K','&#317;'=>'K','&#313;'=>'K','&#315;'=>'K','&#319;'=>'K',
				'&#209;'=>'N','&Ntilde;'=>'N','&#323;'=>'N','&#327;'=>'N','&#325;'=>'N','&#330;'=>'N',
				'&#210;'=>'O','&Ograve;'=>'O','&#211;'=>'O','&Oacute;'=>'O','&#212;'=>'O','&Ocirc;'=>'O','&#213;'=>'O','&Otilde;'=>'O',
				'&#214;'=>'Oe','&Ouml;'=>'Oe',
				'&#216;'=>'O','&Oslash;'=>'O','&#332;'=>'O','&#336;'=>'O','&#334;'=>'O',
				'&#338;'=>'OE',
				'&#340;'=>'R','&#344;'=>'R','&#342;'=>'R',
				'&#346;'=>'S','&#352;'=>'S','&#350;'=>'S','&#348;'=>'S','&#536;'=>'S',
				'&#356;'=>'T','&#354;'=>'T','&#358;'=>'T','&#538;'=>'T',
				'&#217;'=>'U','&Ugrave;'=>'U','&#218;'=>'U','&Uacute;'=>'U','&#219;'=>'U','&Ucirc;'=>'U',
				'&#220;'=>'Ue','&#362;'=>'U','&Uuml;'=>'Ue',
				'&#366;'=>'U','&#368;'=>'U','&#364;'=>'U','&#360;'=>'U','&#370;'=>'U',
				'&#372;'=>'W',
				'&#221;'=>'Y','&Yacute;'=>'Y','&#374;'=>'Y','&#376;'=>'Y',
				'&#377;'=>'Z','&#381;'=>'Z','&#379;'=>'Z',
				'&#222;'=>'T','&THORN;'=>'T',
				'&#224;'=>'a','&#225;'=>'a','&#226;'=>'a','&#227;'=>'a','&#228;'=>'ae',
				'&auml;'=>'ae',
				'&#229;'=>'a','&#257;'=>'a','&#261;'=>'a','&#259;'=>'a','&aring;'=>'a',
				'&#230;'=>'ae',
				'&#231;'=>'c','&#263;'=>'c','&#269;'=>'c','&#265;'=>'c','&#267;'=>'c',
				'&#271;'=>'d','&#273;'=>'d','&#240;'=>'d',
				'&#232;'=>'e','&#233;'=>'e','&#234;'=>'e','&#235;'=>'e','&#275;'=>'e',
				'&#281;'=>'e','&#283;'=>'e','&#277;'=>'e','&#279;'=>'e',
				'&#402;'=>'f',
				'&#285;'=>'g','&#287;'=>'g','&#289;'=>'g','&#291;'=>'g',
				'&#293;'=>'h','&#295;'=>'h',
				'&#236;'=>'i','&#237;'=>'i','&#238;'=>'i','&#239;'=>'i','&#299;'=>'i',
				'&#297;'=>'i','&#301;'=>'i','&#303;'=>'i','&#305;'=>'i',
				'&#307;'=>'ij',
				'&#309;'=>'j',
				'&#311;'=>'k','&#312;'=>'k',
				'&#322;'=>'l','&#318;'=>'l','&#314;'=>'l','&#316;'=>'l','&#320;'=>'l',
				'&#241;'=>'n','&#324;'=>'n','&#328;'=>'n','&#326;'=>'n','&#329;'=>'n',
				'&#331;'=>'n',
				'&#242;'=>'o','&#243;'=>'o','&#244;'=>'o','&#245;'=>'o','&#246;'=>'oe',
				'&ouml;'=>'oe',
				'&#248;'=>'o','&#333;'=>'o','&#337;'=>'o','&#335;'=>'o',
				'&#339;'=>'oe',
				'&#341;'=>'r','&#345;'=>'r','&#343;'=>'r',
				'&#353;'=>'s',
				'&#249;'=>'u','&#250;'=>'u','&#251;'=>'u','&#252;'=>'ue','&#363;'=>'u',
				'&uuml;'=>'ue',
				'&#367;'=>'u','&#369;'=>'u','&#365;'=>'u','&#361;'=>'u','&#371;'=>'u',
				'&#373;'=>'w',
				'&#253;'=>'y','&#255;'=>'y','&#375;'=>'y',
				'&#382;'=>'z','&#380;'=>'z','&#378;'=>'z',
				'&#254;'=>'t',
				'&#223;'=>'ss',
				'&#383;'=>'ss',
				'&agrave;'=>'a','&aacute;'=>'a','&acirc;'=>'a','&atilde;'=>'a','&auml;'=>'ae',
				'&aring;'=>'a','&aelig;'=>'ae','&ccedil;'=>'c','&eth;'=>'d',
				'&egrave;'=>'e','&eacute;'=>'e','&ecirc;'=>'e','&euml;'=>'e',
				'&igrave;'=>'i','&iacute;'=>'i','&icirc;'=>'i','&iuml;'=>'i',
				'&ntilde;'=>'n',
				'&ograve;'=>'o','&oacute;'=>'o','&ocirc;'=>'o','&otilde;'=>'o','&ouml;'=>'oe',
				'&oslash;'=>'o',
				'&ugrave;'=>'u','&uacute;'=>'u','&ucirc;'=>'u','&uuml;'=>'ue',
				'&yacute;'=>'y','&yuml;'=>'y',
				'&thorn;'=>'t',
				'&szlig;'=>'ss'
			);


			if (is_file(txpath.'/lib/i18n-ascii.txt')) {
				$i18n = parse_ini_file(txpath.'/lib/i18n-ascii.txt', true);
				# load the global map
				if (isset($i18n['default']) && is_array($i18n['default'])) {
					$array[$lang] = array_merge($array[$lang], $i18n['default']);
					# base language overrides: 'de-AT' applies the 'de' section
					if (preg_match('/([a-zA-Z]+)-.+/', $lang, $m)) {
						if (isset($i18n[$m[1]]) && is_array($i18n[$m[1]]))
							$array[$lang] = array_merge($array[$lang], $i18n[$m[1]]);
					};
					# regional language overrides: 'de-AT' applies the 'de-AT' section
					if (isset($i18n[$lang]) && is_array($i18n[$lang]))
						$array[$lang] = array_merge($array[$lang], $i18n[$lang]);
				}
				# load an old file (no sections) just in case
				else
					$array[$lang] = array_merge($array[$lang], $i18n);
			}
		}

		return strtr($str, $array[$lang]);
	}

// -------------------------------------------------------------
	function clean_url($url)
	{
		return preg_replace("/\"|'|(?:\s.*$)/",'',$url);
	}

// -------------------------------------------------------------
	function noWidow($str)
	{	
		// replace the last space with a nbsp
		if (REGEXP_UTF8 == 1)
			return preg_replace('@[ ]+([[:punct:]]?\pL+[[:punct:]]?)$@u', '&#160;$1', rtrim($str));
		return preg_replace('@[ ]+([[:punct:]]?\w+[[:punct:]]?)$@', '&#160;$1', rtrim($str));
	}

// -------------------------------------------------------------
	function is_blacklisted($ip, $checks = '')
	{
		global $prefs;

		if (!$checks)
		{
			$checks = do_list($prefs['spam_blacklists']);
		}

		$rip = join('.', array_reverse(explode('.', $ip)));

		foreach ($checks as $a)
		{
			$parts = explode(':', $a, 2);
			$rbl   = $parts[0];

			if (isset($parts[1]))
			{
				foreach (explode(':', $parts[1]) as $code)
				{
					$codes[] = strpos($code, '.') ? $code : '127.0.0.'.$code;
				}
			}

			$hosts = $rbl ? @gethostbynamel($rip.'.'.trim($rbl, '. ').'.') : FALSE;

			if ($hosts and (!isset($codes) or array_intersect($hosts, $codes)))
			{
				$listed[] = $rbl;
			}
		}

		return (!empty($listed)) ? join(', ', $listed) : false;
	}

// -------------------------------------------------------------
	function is_logged_in($user = '')
	{
		$name = substr(cs('txp_login_public'), 10);

		if (!strlen($name) or strlen($user) and $user !== $name)
		{
			return FALSE;
		}
		
		$columns = (column_exists('txp_users','RealName')) 
			? 'nonce, name, RealName, email, privs'
			: 'nonce, name, Title AS RealName, email, privs';

		$rs = safe_row($columns, 'txp_users', "name = '".doSlash($name)."'");

		if ($rs and substr(md5($rs['nonce']), -10) === substr(cs('txp_login_public'), 0, 10))
		{
			unset($rs['nonce']);
			return $rs;
		}
		else
		{
			return FALSE;
		}
	}

// -------------------------------------------------------------
	function updateSitePath($here)
	{
		global $txpcfg;
		
		$here = (isset($txpcfg['path_to_site'])) 
			? $txpcfg['path_to_site']
			: doSlash($here);
		
		$rs = safe_field ("name",'txp_prefs',"name = 'path_to_site'");
		
		if (!$rs) {
			safe_insert("txp_prefs","prefs_id=1,name='path_to_site',val='$here'");
		} else {
			safe_update('txp_prefs',"val='$here'","name='path_to_site'");
		}
		
		return doStrip($here);
	}

// -------------------------------------------------------------
// change: allow dots in attribute names

	function splat($text)
	{
		$atts  = array();

		if (!defined("ATT_NAME")) define("ATT_NAME",'[\w\.]+');

		if (preg_match_all('@('.ATT_NAME.')\s*=\s*(?:"((?:[^"]|"")*)"|\'((?:[^\']|\'\')*)\'|([^\s\'"/>]+))@s', $text, $match, PREG_SET_ORDER))
		{
			foreach ($match as $m)
			{
				switch (count($m))
				{
					case 3:
						$val = str_replace('""', '"', $m[2]);
						break;
					case 4:
						$val = str_replace("''", "'", $m[3]);

						if (strpos($m[3], '<txp:') !== FALSE)
						{
							trace_add("[attribute '".$m[1]."']");
							$val = parse($val);
							trace_add("[/attribute]");
						}

						break;
					case 5:
						$val = $m[4];
						trigger_error(gTxt('attribute_values_must_be_quoted'), E_USER_WARNING);
						break;
				}

				$atts[strtolower($m[1])] = $val;
			}

		}

		return $atts;
	}

// -------------------------------------------------------------
	function maxMemUsage($message = 'none', $returnit = 0)
	{
		static $memory_top = 0;
		static $memory_message;

		if (is_callable('memory_get_usage'))
		{
			$memory_now = memory_get_usage();
			if ($memory_now > $memory_top)
			{
				$memory_top = $memory_now;
				$memory_message = $message;
			}
		}

		if ($returnit != 0)
		{
			if (is_callable('memory_get_usage'))
				return n.comment(sprintf('Memory: %sKb, %s',
					ceil($memory_top/1024),$memory_message));
			else
				return n.comment('Memory: no info available');
		}
	}

// -------------------------------------------------------------
	function strip_rn($str)
	{
		return strtr($str, "\r\n", '  ');
	}

// -------------------------------------------------------------

	function is_valid_email($address)
	{
		return preg_match('/^[a-z0-9](\.?[a-z0-9_+%-])*@([a-z0-9](-*[a-z0-9])*\.)+[a-z]{2,6}$/i', $address);
	}

// -------------------------------------------------------------

	function txpMail($to_address, $subject, $body, $reply_to = null)
	{
		global $txp_user, $prefs;

		// if mailing isn't possible, don't even try
		if (is_disabled('mail'))
		{
			return false;
		}
		
		$columns = (column_exists('txp_users','RealName')) 
			? 'RealName, email'
			: 'Title AS RealName, email';
			
		// Likely sending passwords
		if (isset($txp_user))
		{
			extract(safe_row($columns, 'txp_users', "name = '".doSlash($txp_user)."'"));
		}

		// Likely sending comments -> "to" equals "from"
		else
		{
			extract(safe_row($columns, 'txp_users', "email = '".doSlash($to_address)."'"));
		}

		if ($prefs['override_emailcharset'] and is_callable('utf8_decode'))
		{
			$charset = 'ISO-8859-1';

			$RealName = utf8_decode($RealName);
			$subject = utf8_decode($subject);
			$body = utf8_decode($body);
		}

		else
		{
			$charset = 'UTF-8';
		}

		$RealName = encode_mailheader(strip_rn($RealName), 'phrase');
		$subject = encode_mailheader(strip_rn($subject), 'text');
		$email = strip_rn($email);

		if (!is_null($reply_to))
		{
			$reply_to = strip_rn($reply_to);
		}

		$sep = !is_windows() ? "\n" : "\r\n";

		$body = str_replace("\r\n", "\n", $body);
		$body = str_replace("\r", "\n", $body);
		$body = str_replace("\n", $sep, $body);
		
		$FromRealName = $RealName;
		$FromEmail = $email;
		
		$columns = (column_exists('txp_users','RealName')) 
			? 'RealName AS FromRealName, email AS FromEmail'
			: 'Title AS FromRealName, email AS FromEmail';
		
		if ($row = safe_row($columns,"txp_users","name = 'admin'")) {
			extract($row);
		}

		$headers = "From: $FromRealName <$FromEmail>".
			$sep.'Reply-To: '.( isset($reply_to) ? $reply_to : "$RealName <$email>" ).
			$sep.'X-Mailer: Textpattern'.
			$sep.'Content-Transfer-Encoding: 8bit'.
			$sep.'Content-Type: text/plain; charset="'.$charset.'"'.
			$sep;

		if (is_valid_email($prefs['smtp_from']))
		{
			if (is_windows())
			{
				ini_set('sendmail_from', $prefs['smtp_from']);
			}
			elseif (!ini_get('safe_mode'))
			{
				return mail($to_address, $subject, $body, $headers, '-f'.$prefs['smtp_from']);
			}
		}

		return mail($to_address, $subject, $body, $headers);
	}

// -------------------------------------------------------------
	function encode_mailheader($string, $type)
	{
		global $prefs;
		if (!strstr($string,'=?') and !preg_match('/[\x00-\x1F\x7F-\xFF]/', $string)) {
			if ("phrase" == $type) {
				if (preg_match('/[][()<>@,;:".\x5C]/', $string)) {
					$string = '"'. strtr($string, array("\\" => "\\\\", '"' => '\"')) . '"';
				}
			}
			elseif ( "text" != $type) {
				trigger_error( 'Unknown encode_mailheader type', E_USER_WARNING);
			}
			return $string;
		}
		if ($prefs['override_emailcharset'] and is_callable('utf8_decode')) {
			$start = '=?ISO-8859-1?B?';
			$pcre  = '/.{1,42}/s';
		}
		else {
			$start = '=?UTF-8?B?';
			$pcre  = '/.{1,45}(?=[\x00-\x7F\xC0-\xFF]|$)/s';
		}
		$end = '?=';
		$sep = is_windows() ? "\r\n" : "\n";
		preg_match_all($pcre, $string, $matches);
		return $start . join($end.$sep.' '.$start, array_map('base64_encode',$matches[0])) . $end;
	}

// -------------------------------------------------------------
	function stripPHP($in)
	{
		return preg_replace("/".chr(60)."\?(?:php)?|\?".chr(62)."/i",'',$in);
	}

// -------------------------------------------------------------

/**
 * PEDRO:
 * Helper functions for common textpattern event files actions.
 * Code refactoring from original files. Intended to do easy and less error
 * prone the future build of new textpattern extensions, and to add new
 * events to multiedit forms.
 */

 	function event_category_popup($name, $cat = '', $id = '')
	{
		$arr = array('');
		// $rs = getTree('root', $name);
		
		$rs = safe_rows_tree(
			0,
			"ID,Name AS name,Title AS title,Level AS level,ParentID AS parent",
			"txp_category");
					
		print_r($rs);
		
		if ($rs)
		{
			// return treeSelectInput('category', $rs, $cat, $id);
			return treeSelectInput('category', $rs, $cat);
		}

		return false;
	}

// -------------------------------------------------------------
// change: save new pageby value to session not prefs
/*
 	function event_change_pageby($name)
	{
		global $EVENT, $WIN;
		
		$qty = gps('qty');
		$win = gps('win',0);
		$pageby = $name.'_list_pageby';
		$GLOBALS[$pageby] = $qty;

		set_pref($pageby, $qty, $EVENT['name'], PREF_HIDDEN, 'text_input', 0, PREF_PRIVATE);
		
		$WIN['limit'] = $qty;
		$WIN['page']  = 1;
		
		save_session($WIN);
		
		return;
	}
*/
// -------------------------------------------------------------

	function event_multiedit_form($name, $methods = null, $page, $sort, $dir, $crit, $search_method)
	{
		$method = ps('edit_method');

		if ($methods === NULL)
		{
			$methods = array(
				'delete' => gTxt('delete')
			);
		}

		return '<label for="withselected">'.gTxt('with_selected').'</label>'.sp.
			selectInput('edit_method', $methods, $method, 1, ' id="withselected" onchange="poweredit(this); return false;"').
			n.eInput($name).
			n.sInput($name.'_multi_edit').
			n.hInput('page', $page).
			( $sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
			( $crit ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '' ).
			n.fInput('submit', '', gTxt('go'), 'smallerbox');
	}

// -------------------------------------------------------------
/*
	function event_multi_edit($table, $id_key)
	{
		$method = ps('edit_method');
		$selected = ps('selected');

		if ($selected)
		{
			if ($method == 'delete')
			{
				foreach ($selected as $id)
				{
					$id = assert_int($id);

					if (safe_delete($table, "$id_key = $id"))
					{
						$ids[] = $id;
					}
				}

				return join(', ', $ids);
			}
		}

		return '';
	}
*/
// -------------------------------------------------------------
	function since($stamp)
	{
		$diff = (time() - $stamp);
		if ($diff <= 3600) {
			$mins = round($diff / 60);
			$since = ($mins <= 1)
			?	($mins==1)
				?	'1 '.gTxt('minute')
				:	gTxt('a_few_seconds')
			:	"$mins ".gTxt('minutes');
		} else if (($diff <= 86400) && ($diff > 3600)) {
			$hours = round($diff / 3600);
			$since = ($hours <= 1) ? '1 '.gTxt('hour') : "$hours ".gTxt('hours');
		} else if ($diff >= 86400) {
			$days = round($diff / 86400);
			$since = ($days <= 1) ? "1 ".gTxt('day') : "$days ".gTxt('days');
		}
		return $since.' '.gTxt('ago'); // sorry, this needs to be hacked until a truly multilingual version is done
	}

// -------------------------------------------------------------
// Calculate the offset between the server local time and the
// user's selected time zone at a given point in time
	function tz_offset($timestamp = NULL)
	{
		global $gmtoffset, $timezone_key, $saved_timezone;
		
		if (is_null($timestamp)) $timestamp = time();

		extract(getdate($timestamp)); 
		$serveroffset = gmmktime($hours,$minutes,0,$mon,$mday,$year) - mktime($hours,$minutes,0,$mon,$mday,$year);
		
		if (is_object($saved_timezone)) {
			$timezone = $saved_timezone;
		} else {
			$timezone = $saved_timezone = new timezone();
		}
		
		$real_dst = $timezone->is_dst($timestamp, $timezone_key);
		
		return $gmtoffset - $serveroffset + ($real_dst ? 3600 : 0);
	}

// -------------------------------------------------------------
// Format a time, respecting the locale and local time zone,
// and make sure the output string is safe for UTF-8
	function safe_strftime($format, $time='', $gmt=0, $override_locale='')
	{
		global $locale;
		$old_locale = $locale;

		if (!$time)
			$time = time();

		# we could add some other formats here
		if ($format == 'iso8601' or $format == 'w3cdtf') {
			$format = '%Y-%m-%dT%H:%M:%SZ';
			$gmt = 1;
		}
		elseif ($format == 'rfc822') {
			$format = '%a, %d %b %Y %H:%M:%S GMT';
			$gmt = 1;
			$override_locale = 'en-gb';
		}

		if ($override_locale)
			getlocale($override_locale);
		
		if ($format == 'since')
			$str = since($time);
		elseif ($gmt)
			$str = gmstrftime($format, $time);
		else
			$str = strftime($format, $time + tz_offset($time));

		@list($lang, $charset) = explode('.', $locale);
		if (empty($charset))
			$charset = 'ISO-8859-1';
		elseif (is_windows() and is_numeric($charset))
			// Score -1 for consistent naming conventions
			$charset = 'Windows-'.$charset;

		if ($charset != 'UTF-8' and $format != 'since') {
			$new = '';
			if (is_callable('iconv'))
				$new = @iconv($charset, 'UTF-8', $str);

			if ($new)
				$str = $new;
			elseif (is_callable('utf8_encode'))
				$str = utf8_encode($str);
		}

		# revert to the old locale
		if ($override_locale)
			$locale = setlocale(LC_ALL, $old_locale);

		return $str;
	}

// -------------------------------------------------------------
// Convert a time string from the Textpattern time zone to GMT
	function safe_strtotime($time_str)
	{
		$ts = strtotime($time_str);
		return strtotime($time_str, time() + tz_offset($ts)) - tz_offset($ts);
	}

// -------------------------------------------------------------
	function myErrorHandler($errno, $errstr, $errfile, $errline)
	{
		# error_reporting() returns 0 when the '@' suppression
		# operator is used
		if (!error_reporting())
			return;

		echo '<pre>'.n.n."$errno: $errstr in $errfile at line $errline\n";
		# Requires PHP 4.3
		if (is_callable('debug_backtrace')) {
			echo "Backtrace:\n";
			$trace = debug_backtrace();
			foreach($trace as $ent) {
				if(isset($ent['file'])) echo $ent['file'].':';
				if(isset($ent['function'])) {
					echo $ent['function'].'(';
					if(isset($ent['args'])) {
						$args='';
						foreach($ent['args'] as $arg) { $args.=$arg.','; }
						echo rtrim($args,',');
					}
					echo ') ';
				}
				if(isset($ent['line'])) echo 'at line '.$ent['line'].' ';
				if(isset($ent['file'])) echo 'in '.$ent['file'];
				echo "\n";
			}
		}
		echo "</pre>";
	}

// -------------------------------------------------------------
	function find_temp_dir()
	{
		global $path_to_site, $img_dir;

		if (is_windows()) {
			$guess = array(txpath.DS.'tmp', getenv('TMP'), getenv('TEMP'), getenv('SystemRoot').DS.'Temp', 'C:'.DS.'Temp', $path_to_site.DS.$img_dir);
			foreach ($guess as $k=>$v)
				if (empty($v)) unset($guess[$k]);
		}
		else
			$guess = array(txpath.DS.'tmp', '', DS.'tmp', $path_to_site.DS.$img_dir);

		foreach ($guess as $dir) {
			$tf = @tempnam($dir, 'txp_');
			if ($tf) $tf = realpath($tf);
			if ($tf and file_exists($tf)) {
				unlink($tf);
				return dirname($tf);
			}
		}

		return false;
	}


// --------------------------------------------------------------
	function set_error_level($level)
	{
		if ($level == 'debug') {
			error_reporting(E_ALL /* TODO: Enable E_STRICT in debug mode/PHP5.x? | (defined('E_STRICT') ? E_STRICT : 0) */);
		}
		elseif ($level == 'live') {
			// don't show errors on screen
			$suppress = E_WARNING | E_NOTICE;
			 // E_STRICT is defined since PHP 5.x and is a member of E_ALL in PHP 6.x. Now handle that!
			if (defined('E_STRICT') && (E_ALL & E_STRICT)) $suppress |= E_STRICT;
			if (defined('E_DEPRECATED')) $suppress |= E_DEPRECATED;
			error_reporting(E_ALL ^ $suppress);
			@ini_set("display_errors","1");
		}
		else {
			// default is 'testing': display everything except notices
			error_reporting(E_ALL ^ (E_NOTICE));
		}
	}

// --------------------------------------------------------------
/*	function set_error_level($level)
	{

		if ($level == 'debug') {
			error_reporting(E_ALL);
		}
		elseif ($level == 'live') {
			// don't show errors on screen
			$suppress = E_WARNING | E_NOTICE;
			if (defined('E_STRICT')) $suppress |= E_STRICT;
			if (defined('E_DEPRECATED')) $suppress |= E_DEPRECATED;
			error_reporting(E_ALL ^ $suppress);
			@ini_set("display_errors","1");
		}
		else {
			// default is 'testing': display everything except notices
			error_reporting(E_ALL ^ (E_NOTICE));
		}
	}
*/
// -------------------------------------------------------------
	function is_cgi()
	{
		return IS_CGI;
	}

// -------------------------------------------------------------
	function is_mod_php()
	{
		return IS_APACHE;
	}

// -------------------------------------------------------------

	function is_disabled($function)
	{
		static $disabled;

		if (!isset($disabled))
		{
			$disabled = expl(ini_get('disable_functions'));
		}

		return in_array($function, $disabled);
	}

// --------------------------------------------------------------
	function build_file_path($base,$path,$id=0)
	{
		$base = rtrim($base,'/\\');
		$path = ltrim($path,'/\\');
		
		if ($id) {
			$base .= DIRECTORY_SEPARATOR.get_file_id_path($id); 
			if (!is_dir($base)) mkdir($base,0777,true);
		}
		
		return $base.DIRECTORY_SEPARATOR.$path;
	}

// --------------------------------------------------------------
	function get_author_name($name)
	{
		static $authors = array();

		if (isset($authors[$name]))
			return $authors[$name];

		$realname = fetch('RealName','txp_users','name',doSlash($name));
		$authors[$name] = $realname;
		return ($realname) ? $realname : $name;
	}

// --------------------------------------------------------------
	function has_single_author($table, $col='author')
	{
		return (safe_field('COUNT(name)', 'txp_users', '1=1') <= 1) &&
			(safe_field('COUNT(DISTINCT('.doSlash($col).'))', doSlash($table), '1=1') <= 1);
	}

// --------------------------------------------------------------
	function EvalElse($thing, $condition)
	{
		global $txp_current_tag;
		
		trace_add("[$txp_current_tag: ".($condition ? gTxt('true') : gTxt('false'))."]");

		if (strpos($thing,'<txp:else') === FALSE) {
		
			return $condition ? $thing : '';
		}
		
		$parsed = preg_split('#(</?txp:[a-z_]+)#',$thing,0,PREG_SPLIT_DELIM_CAPTURE);
		
		$else   = false;
		$level  = 0;
		$before = '';
		$after  = '';
		
		while ($parsed and !$else) {
			
			$item = array_shift($parsed);
			
			if ($item == '<txp:else') {
				
				if ($level == 0) {
					
					$else = true;
				
				} else {
					
					$before .= $item; 
				}
				
			} else {
				
				$before .= $item; 
				
				if (preg_match('/<txp:(if_|article$)/',$item)) {
				
					$level += 1;
				
				} elseif (preg_match('/<\/txp:(if_|article$)/',$item)) {
				
					$level -= 1;
				}
			}
		}
		
		$after = ltrim(implode('',$parsed),'/>');
		
		return $condition ? $before : $after;
	}

// --------------------------------------------------------------
// allow for using curly brackets instead of angle brackets for txp tags
/*
	function EvalElse($thing, $condition)
	{
		global $txp_current_tag;
		
		if (!defined("LEFT"))  define("LEFT",'[<\{]');
		if (!defined("RIGHT")) define("RIGHT",'[>\}]');
		
		trace_add("[$txp_current_tag: ".($condition ? gTxt('true') : gTxt('false'))."]");

		$els = strpos($thing, '<txp:else');
		
		if ($els === FALSE)
		{
			return $condition ? $thing : '';
		}
		elseif ($els === strpos($thing, '<txp:else'))
		{
			return $condition
				? substr($thing, 0, $els)
				: substr($thing, strpos($thing, '>', $els) + 1);
		}

		$tag    = FALSE;
		$level  = 0;
		$str    = '';
	 // $regex  = '@(</?txp:\w+(?:\s+\w+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/>]+))*\s*'.'/?'.chr(62).')@s';
		$regex  = '@('.LEFT.'/?txp:\w+(?:\s+\w+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/'.RIGHT.']+))*\s*'.'/?'.RIGHT.')@s';
		$parsed = preg_split($regex, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parsed as $chunk)
		{
			if ($tag)
			{
				if ($level === 0 and strpos($chunk, 'else') === 5 and substr($chunk, -2, 1) === '/')
				{
					return $condition
						? $str
						: substr($thing, strlen($str)+strlen($chunk));
				}
				elseif (substr($chunk, 1, 1) === '/')
				{
					$level--;
				}
				elseif (substr($chunk, -2, 1) !== '/')
				{
					$level++;
				}
			}

			$tag = !$tag;
			$str .= $chunk;
		}
		
		return $condition ? $thing : '';
	}
*/

// --------------------------------------------------------------
// change: check both name and type

	function fetch_form($name,$type='')
	{
		static $forms = array();

		$name = trim($name,'/');
		
		if (isset($forms[$name.$type]))
		
			$html = $forms[$name.$type];
		
		else {
			
			$path = explode('/',$name);
			$parent = fetch('ID','txp_form','ParentID',0);
			$html = '';
			
			while ($path) {
				
				$html = '';
				$item = doSlash(array_shift($path));
				
				$where = array(
					'ParentID' => "ParentID = $parent",
					'Name' 	   => "Name = '$item'",
					'Trash'    => "Trash = 0"
				);
				
				if (!count($path) and $type) {
					$where['Type'] = "Type = '$type'";
				}
				
				$row = safe_row('ID,Body_html','txp_form',doAnd($where));
				
				if (!$row) {
					$where['Name'] = "Name = '".str_replace('_','-',$item)."'";
					$row = safe_row('ID,Body_html','txp_form',doAnd($where));
				}
				
				if ($row) {
					$parent = $row['ID'];
					$html   = $row['Body_html'];
				}
			}
			
			if (!$html) {
			
				$html = safe_field('Body','txp_form',doAnd($where));
				
				if (!$html) {
					trigger_error(gTxt('form_not_found').': '.$name);
					return;
				}
			}
			
			$forms[$name.$type] = $html;
		}

		trace_add('['.gTxt('form').': '.$name.']');
		
		return $html;
	}

// --------------------------------------------------------------
	function parse_form($name,$type='')
	{
		static $stack = array();
		
		$f = fetch_form($name,$type);
		
		if ($f) {
			
			if (in_array($name, $stack)) {
				trigger_error(gTxt('form_circular_reference', array('{name}' => $name)));
				return;
			}
			array_push($stack, $name);
			$out = parse($f);
			array_pop($stack);
			
			return $out;
		}
	}

// --------------------------------------------------------------
	function fetch_category_title($name, $type='article')
	{
		static $cattitles = array();
		global $thiscategory;

		if (isset($cattitles[$type][$name]))
			return $cattitles[$type][$name];

		if(!empty($thiscategory['title']) && $thiscategory['name'] == $name && $thiscategory['type'] == $type)
		{
			$cattitles[$type][$name] = $thiscategory['title'];
			return $thiscategory['title'];
		}

		$f = safe_field('Title','txp_category',"Name='".doSlash($name)."' AND Type != 'folder'");
		$cattitles[$type][$name] = $f;
		return $f;
	}

// -------------------------------------------------------------
	function fetch_section_title($name)
	{
		static $sectitles = array();
		global $thissection;

		// try cache
		if (isset($sectitles[$name]))
			return $sectitles[$name];

		// try global set by section_list()
		if(!empty($thissection['title']) && $thissection['name'] == $name)
		{
			$sectitles[$name] = $thissection['title'];
			return $thissection['title'];
		}

		if($name == 'default' or empty($name))
			return '';

		$f = safe_field('title','txp_section',"name='".doSlash($name)."'");
		$sectitles[$name] = $f;
		return $f;
	}

// -------------------------------------------------------------
	function update_comments_count($article_id,$comment_id=0)
	{
		$article_id = assert_int($article_id);
		$comment_id = assert_int($comment_id);
		
		if (!$article_id) {
			$article_id = fetch("article_id","txp_discuss","ID",$comment_id);
		}
		
		$thecount = safe_field('count(*)','txp_discuss','article_id='.$article_id.' AND Status='.VISIBLE);
		$thecount = assert_int($thecount);
		$updated = safe_update('textpattern','comments_count='.$thecount,'ID='.$article_id);
		
		return ($updated) ? true : false;
	}

// -------------------------------------------------------------
	function clean_comment_counts($parentids)
	{
		$parentids = array_map('assert_int',$parentids);
		$rs = safe_rows_start('ParentID, count(*) AS thecount','txp_discuss','ParentID IN ('.in($parentids).') AND Status='.VISIBLE.' GROUP BY ParentID');
		if (!$rs) return;

		$updated = array();
		while($a = nextRow($rs)) {
			safe_update('textpattern',"comments_count=".$a['thecount'],"ID=".$a['ParentID']);
			$updated[] = $a['ParentID'];
		}
		// We still need to update all those, that have zero comments left.
		$leftover = array_diff($parentids, $updated);
		if ($leftover)
			safe_update('textpattern',"comments_count = 0","ID IN (".in($leftover).")");
	}

// -------------------------------------------------------------
	function update_category_count($debug=0) 
	{
		global $PFX;
		
		// update category count
		// including articles that are in the trash
		
		safe_update("txp_category AS c",
			"Articles = (SELECT COUNT(*) FROM ".$PFX."txp_content_category AS cc 
				JOIN ".$PFX."textpattern AS t ON cc.article_id = t.ID 
				WHERE c.Name = cc.name AND cc.type = 'article' AND t.Trash <= 2)",
			"c.Type != 'folder'",$debug);
		
		safe_update("txp_category AS c",
			"Images = (SELECT COUNT(*) FROM ".$PFX."txp_content_category AS cc 
				JOIN ".$PFX."txp_image AS t ON cc.article_id = t.ID 
				WHERE c.Name = cc.name AND cc.type = 'image' AND t.Trash <= 2)",
			"c.Type != 'folder'",$debug);
			
		safe_update("txp_category AS c",
			"Files = (SELECT COUNT(*) FROM ".$PFX."txp_content_category AS cc 
				JOIN ".$PFX."txp_image AS t ON cc.article_id = t.ID 
				WHERE c.Name = cc.name AND cc.type = 'file' AND t.Trash <= 2)",
			"c.Type != 'folder'",$debug);
			
		update_summary_field('txp_category','Articles');
		update_summary_field('txp_category','Images');
		update_summary_field('txp_category','Files'); 
	}
	
// -------------------------------------------------------------
	function markup_comment($msg)
	{
		global $prefs;

		$disallow_images = !empty($prefs['comments_disallow_images']);
		$lite = empty($prefs['comments_use_fat_textile']);

		$rel = !empty($prefs['comment_nofollow']) ? 'nofollow' : '';

		include_once txpath.'/lib/classTextile.php';

		$textile = new Textile();

		return $textile->TextileRestricted($msg, $lite, $disallow_images, $rel);
	}

// -------------------------------------------------------------------------------------
// change: update lastmod for article and its parent article

	function update_lastmod($ids='0',$table='')
	{
		global $WIN, $txp_user;
		
		$table = (!$table) ? $WIN['table'] : $table;
		
		$counter = fetch('val','txp_prefs','name','mod_counter');
		
		$ids = (is_array($ids)) ? $ids : expl($ids);
		
		foreach ($ids as $id) {
		
			$micro = pad_number($counter++);
			$micro = preg_replace('/\.00$/','',$micro);
			
			if (trim($id)) {
				safe_update($table, "LastMod = NOW(), LastModMicro = CONCAT(NOW(),' $micro'), LastModID = '$txp_user'", "ID = $id");
				
				if ($parent = fetch("ParentID",$table,"ID",$id)) {
					
					$micro = pad_number($counter++);
					$micro = preg_replace('/\.00$/','',$micro);
				
					safe_update($table, "LastMod = NOW(), LastModMicro = CONCAT(NOW(),' $micro'), LastModID = '$txp_user'", "ID = $parent");
				}
			}
		}
		
		safe_update("txp_prefs", "val = NOW()", "name = 'lastmod'");
		
		if ($counter > 9999999999) $counter = 1;
		
		safe_update("txp_prefs", "val = '$counter'", "name = 'mod_counter'");
	}

//-------------------------------------------------------------
	function get_lastmod($unix_ts=NULL) {
		
		global $prefs;

		if ($unix_ts === NULL)
			$unix_ts = @strtotime($prefs['lastmod']);

		# check for future articles that are now visible
		if ($max_article = safe_field('unix_timestamp(Posted)', 'textpattern', "Posted <= now() and Status >= 4 order by Posted desc limit 1")) {
			$unix_ts = max($unix_ts, $max_article);
		}

		return $unix_ts;
	}

//-------------------------------------------------------------
	function handle_lastmod($unix_ts=NULL, $exit=1) {
		
		global $prefs;
		extract($prefs);

		if($send_lastmod and $production_status == 'live') {
			$unix_ts = get_lastmod($unix_ts);

			# make sure lastmod isn't in the future
			$unix_ts = min($unix_ts, time());
			# or too far in the past (7 days)
			$unix_ts = max($unix_ts, time() - 3600*24*7);

			$last = safe_strftime('rfc822', $unix_ts, 1);
			header("Last-Modified: $last");
			header('Cache-Control: no-cache');

			$hims = serverset('HTTP_IF_MODIFIED_SINCE');
			if ($hims and @strtotime($hims) >= $unix_ts) {
				log_hit('304');
				if (!$exit)
					return array('304', $last);
				txp_status_header('304 Not Modified');
				# some mod_deflate versions have a bug that breaks subsequent
				# requests when keepalive is used.  dropping the connection
				# is the only reliable way to fix this.
				if (empty($lastmod_keepalive))
					header('Connection: close');
				header('Content-Length: 0');
				# discard all output
				while (@ob_end_clean());
				exit;
			}

			if (!$exit)
				return array('200', $last);
		}
	}

//-------------------------------------------------------------
	function unset_pref($name) 
	{
		safe_delete('txp_prefs',"name like '$name'");
	}

//-------------------------------------------------------------
	function set_flag(&$flags,$flag)
	{
		$flags = do_list($flags);
		
		if (!in_array($flag,$flags)) {
			
			array_push($flags,$flag);
		}
		
		$flags = implode(',',$flags);
	}

//-------------------------------------------------------------
	function in_flags(&$flags,$flag)
	{
		return in_array($flag,do_list($flags));
	}
	
//-------------------------------------------------------------
	function set_pref($name, $val, $event='publish',  $type=0, $html='text_input', $position=0, $is_private=PREF_GLOBAL)
	{
		global $txp_user;
		extract(doSlash(func_get_args()));

		$user_name = '';
		if ($is_private == PREF_PRIVATE) {
			if (empty($txp_user))
				return false;

			$user_name = 'user_name = \''.doSlash($txp_user).'\'';
		}

		if (!safe_row('name', 'txp_prefs', "name = '$name'" . ($user_name ? " AND $user_name" : ''))) {
			$user_name = ($user_name ? "$user_name," : '');
			return safe_insert('txp_prefs', "
				name  = '$name',
				val   = '$val',
				event = '$event',
				html  = '$html',
				type  = '$type',
				position = '$position',
				$user_name
				prefs_id = 1"
			);
    	} else {
        	return safe_update('txp_prefs', "val = '$val'","name like '$name'" . ($user_name ? " AND $user_name" : ''));
    	}
	}

//-------------------------------------------------------------
	function get_pref($thing, $default='') // checks $prefs for a named variable, or creates a default
	{
		global $prefs;
		return (isset($prefs[$thing])) ? $prefs[$thing] : $default;
	}

// -------------------------------------------------------------
	function txp_die($msg, $status='503')
	{
		global $path_to_site;
		
		// 503 status might discourage search engines from indexing or caching the error message

		//Make it possible to call this function as a tag, e.g. in an article <txp:txp_die status="410" />
		if (is_array($msg))
			extract(lAtts(array('msg' => '', 'status' => '503'),$msg));

		// Intentionally incomplete - just the ones we're likely to use
		$codes = array(
			'200' => 'OK',
			'301' => 'Moved Permanently',
			'302' => 'Found',
			'304' => 'Not Modified',
			'307' => 'Temporary Redirect',
			'401' => 'Unauthorized',
			'403' => 'Forbidden',
			'404' => 'Not Found',
			'410' => 'Gone',
			'414' => 'Request-URI Too Long',
			'500' => 'Internal Server Error',
			'501' => 'Not Implemented',
			'503' => 'Service Unavailable',
		);

		if ($status) {
			if (isset($codes[strval($status)]))
				$status = strval($status) . ' ' . $codes[$status];

			txp_status_header($status);
		}

		$code = '';
		if ($status and $parts = @explode(' ', $status, 2)) {
			$code = @$parts[0];
		}

		callback_event('txp_die', $code);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// backtrace 
		
		$backtrace = (defined("DEBUG_BACKTRACE_IGNORE_ARGS"))
				? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) 
				: debug_backtrace();
				
		foreach ($backtrace as $key => $item) {
			
			extract($item);
			
			$file = str_replace($path_to_site,'',$file);
			
			$backtrace[$key] = "<tr><td>$file</td><td>$line</td><td>$function</td></tr>";
		}
		
		$backtrace = '<table>'.implode(n,$backtrace).'</table>';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -

		if (@$GLOBALS['connected']) {
			
			$out = safe_field('Body_html','txp_page',"name='error_".doSlash($code)."'");
			if (empty($out))
				$out = safe_field('Body_html','txp_page',"name='error_default'");
		}

		if (empty($out))
			$out = <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
   <meta http-equiv="content-type" content="text/html; charset=utf-8" />
   <title>Textpattern Error: <txp:error_status /></title>
   <style rel="stylesheet" type="text/css">
   		table {border-collapse: collapse; width: 700px; margin: 40px auto;}
   		table td {border:1px solid grey; padding: 5px; font-family: Monaco; font-size: 12px}
  	</style>
</head>
<body>
eod;

$out .= '<p align="center" style="margin-top:4em"><txp:error_message /></p>';
$out .= $backtrace;
$out .= '</body></html>';

		header("Content-type: text/html; charset=utf-8");
		
		if (is_callable('processTags')) {
			
			$GLOBALS['txp_error_message'] = $msg;
			$GLOBALS['txp_error_status'] = $status;
			$GLOBALS['txp_error_code'] = $code;

			set_error_handler("tagErrorHandler");
			die(parse($out));
		}
		else {
			
			$out = preg_replace(array('@<txp:error_status[^>]*/>@', '@<txp:error_message[^>]*/>@'),
				array($status, $msg),
				$out);
			die($out);
		}
	}

// -------------------------------------------------------------
	function join_qs($q)
	{
		$qs = array();
		foreach ($q as $k=>$v)
			if ($v)
				$qs[] = urlencode($k) . '=' . urlencode($v);

		$str = join('&amp;', $qs);
		return ($str ? '?'.$str : '');
	}

// -------------------------------------------------------------
// change: append pagelink to request

	function pagelinkurl($parts, $inherit = array())
	{
		global $pretext,$permlink_mode, $prefs;

		// $inherit can be used to add parameters to an existing url, e.g:
		// $url = pagelinkurl(array('pg'=>2), $pretext);
		$keys = array_merge($inherit, $parts);

		if (isset($prefs['custom_url_func'])
		    and is_callable($prefs['custom_url_func'])
		    and ($url = call_user_func($prefs['custom_url_func'], $keys, PAGELINKURL)) !== FALSE)
		{
			return $url;
		}

		// can't use this to link to an article
		if (isset($keys['id']))
		{
			unset($keys['id']);
		}

		if (@$keys['s'] == 'default')
		{
			unset($keys['s']);
		}

		if ($permlink_mode == 'messy')
		{
			return hu.'index.php'.join_qs($keys);
		}

		else
		{
			// all clean URL modes use the same schemes for list pages
			
			$url = '';
			
			if (!empty($keys['rss']))
			{
				$url = hu.'rss/';
				unset($keys['rss']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['atom']))
			{
				$url = hu.'atom/';
				unset($keys['atom']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['s']))
			{
				$url = hu.urlencode($keys['s']).'/';
				unset($keys['s']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['author']))
			{
				$url = hu.strtolower(urlencode(gTxt('author'))).'/'.urlencode($keys['author']).'/';
				unset($keys['author']);
				return $url.join_qs($keys);
			}

			elseif (!empty($keys['c']))
			{
				$url = hu.strtolower(urlencode(gTxt('category'))).'/'.urlencode($keys['c']).'/';
				unset($keys['c']);
				return $url.join_qs($keys);
			}
			
			$req = ($pos = strpos($pretext['req'],'?')) 
				? substr($pretext['req'],0,$pos) 
				: $pretext['req'];
			
		 // $url = hu.substr($req,1);
		 	$url = hu.preg_replace('/^~[a-z0-9]+\//','',$req);
			
			return $url.join_qs($keys);
		}
	}

// -------------------------------------------------------------
	function filedownloadurl($id, $filename='')
	{
		global $permlink_mode;

		$filename = urlencode($filename); 
		#FIXME: work around yet another mod_deflate problem (double compression)
		# http://blogs.msdn.com/wndp/archive/2006/08/21/Content-Encoding-not-equal-Content-Type.aspx
		if (preg_match('/gz$/i', $filename))
			$filename .= a;
		
		return ($permlink_mode == 'messy') ?
			hu.'index.php?s=file_download'.a.'id='.$id :
			hu.gTxt('file_download').'/'.$id.($filename ? '/'.$filename : '');
	}

// -------------------------------------------------------------

	function in_list($val, $list, $delim = ',')
	{
		$args = do_list($list, $delim);

		return in_array($val, $args);
	}

// -------------------------------------------------------------
	function expl($list, $delim = ',')
	{
		if (is_array($list)) return $list;
		
		if (!strlen($list)) return array();
		
		return do_list($list, $delim);
	}

// -------------------------------------------------------------
	function impl($list, $delim = ',')
	{
		if (!is_array($list)) return $list;
		
		return implode($delim, $list);
	}

// -------------------------------------------------------------
	function in($list,$delim=',')
	{
		$list = do_list($list,$delim);
		
		foreach ($list as $key => $item) {
			if (!is_numeric($item)) $list[$key] = doQuote(trim($item,"'"));
		}
		
		return impl($list,',');
	}
	
// -------------------------------------------------------------

	function do_list($list, $delim = ',')
	{
		if (is_array($list)) return $list;
		if ($delim === '')   return array($list);
		if (!strlen($list))	 return array(); 
		
		return array_map('trim', explode($delim, $list));
	}

// -------------------------------------------------------------
	function doQuote($val)
	{
		return (is_array($val)) ? doArray($val,'doQuote') : "'$val'";
	}

// -------------------------------------------------------------
	function trace_add($msg)
	{
		global $production_status;

		if ($production_status === 'debug')
		{
			global $txptrace,$txptracelevel;

			$txptrace[] = str_repeat("\t", $txptracelevel).$msg;
		}
	}

// -------------------------------------------------------------

	function relative_path($path, $pfx=NULL)
	{
		if ($pfx === NULL)
			$pfx = dirname(txpath);
		return preg_replace('@^/'.preg_quote(ltrim($pfx, '/'), '@').'/?@', '', $path);
	}

// -------------------------------------------------------------
	function get_caller($num=1,$start=2)
	{
		$out = array();
		if (!is_callable('debug_backtrace'))
			return $out;

		$bt = debug_backtrace();
		for ($i=$start; $i< $num+$start; $i++) {
			if (!empty($bt[$i])) {
				$t = '';
				if (!empty($bt[$i]['file']))
					$t .= relative_path($bt[$i]['file']);
				if (!empty($bt[$i]['line']))
					$t .= ':'.$bt[$i]['line'];
				if ($t)
					$t .= ' ';
				if (!empty($bt[$i]['class']))
					$t .= $bt[$i]['class'];
				if (!empty($bt[$i]['type']))
					$t .= $bt[$i]['type'];
				if (!empty($bt[$i]['function'])) {
					$t .= $bt[$i]['function'];

				$t .= '()';
				}


				$out[] = $t;
			}
		}
		return $out;
	}

//-------------------------------------------------------------
// function name is misleading but remains for legacy reasons
// this actually sets the locale
	function getlocale($lang) {
		global $locale;

		if (empty($locale))
			$locale = @setlocale(LC_TIME, '0');

		// Locale identifiers vary from system to system.  The
		// following code will attempt to discover which identifiers
		// are available.  We'll need to expand these lists to
		// improve support.
		// ISO identifiers: http://www.w3.org/WAI/ER/IG/ert/iso639.htm
		// Windows: http://msdn.microsoft.com/library/default.asp?url=/library/en-us/vclib/html/_crt_language_strings.asp
		$guesses = array(
			'ar-dz' => array('ar_DZ.UTF-8', 'ar_DZ', 'ara', 'ar', 'arabic', 'ar_DZ.ISO_8859-6'),
			'bg-bg' => array('bg_BG.UTF-8', 'bg_BG', 'bg', 'bul', 'bulgarian', 'bg_BG.ISO8859-5'),
			'ca-es' => array('ca_ES.UTF-8', 'ca_ES', 'cat', 'ca', 'catalan', 'ca_ES.ISO_8859-1'),
			'cs-cz' => array('cs_CZ.UTF-8', 'cs_CZ', 'ces', 'cze', 'cs', 'csy', 'czech', 'cs_CZ.cs_CZ.ISO_8859-2'),
			'da-dk' => array('da_DK.UTF-8', 'da_DK'),
			'de-de' => array('de_DE.UTF-8', 'de_DE', 'de', 'deu', 'german', 'de_DE.ISO_8859-1'),
			'en-gb' => array('en_GB.UTF-8', 'en_GB', 'en_UK', 'eng', 'en', 'english-uk', 'english', 'en_GB.ISO_8859-1','C'),
			'en-us' => array('en_US.UTF-8', 'en_US', 'english-us', 'en_US.ISO_8859-1'),
			'es-es' => array('es_ES.UTF-8', 'es_ES', 'esp', 'spanish', 'es_ES.ISO_8859-1'),
			'et-ee' => array('et_EE.UTF-8', 'et_EE'),
			'el-gr' => array('el_GR.UTF-8', 'el_GR', 'el', 'gre', 'greek', 'el_GR.ISO_8859-7'),
			'fi-fi' => array('fi_FI.UTF-8', 'fi_FI', 'fin', 'fi', 'finnish', 'fi_FI.ISO_8859-1'),
			'fr-fr' => array('fr_FR.UTF-8', 'fr_FR', 'fra', 'fre', 'fr', 'french', 'fr_FR.ISO_8859-1'),
			'gl-gz' => array('gl_GZ.UTF-8', 'gl_GZ', 'glg', 'gl', '', ''),
			'he_il' => array('he_IL.UTF-8', 'he_IL', 'heb', 'he', 'hebrew', 'he_IL.ISO_8859-8'),
			'hr-hr' => array('hr_HR.UTF-8', 'hr_HR', 'hr'),
			'hu-hu' => array('hu_HU.UTF-8', 'hu_HU', 'hun', 'hu', 'hungarian', 'hu_HU.ISO8859-2'),
			'id-id' => array('id_ID.UTF-8', 'id_ID', 'id', 'ind', 'indonesian','id_ID.ISO_8859-1'),
			'is-is' => array('is_IS.UTF-8', 'is_IS'),
			'it-it' => array('it_IT.UTF-8', 'it_IT', 'it', 'ita', 'italian', 'it_IT.ISO_8859-1'),
			'ja-jp' => array('ja_JP.UTF-8', 'ja_JP', 'ja', 'jpn', 'japanese', 'ja_JP.ISO_8859-1'),
			'ko-kr' => array('ko_KR.UTF-8', 'ko_KR', 'ko', 'kor', 'korean'),
			'lv-lv' => array('lv_LV.UTF-8', 'lv_LV', 'lv', 'lav'),
			'nl-nl' => array('nl_NL.UTF-8', 'nl_NL', 'dut', 'nla', 'nl', 'nld', 'dutch', 'nl_NL.ISO_8859-1'),
			'no-no' => array('no_NO.UTF-8', 'no_NO', 'no', 'nor', 'norwegian', 'no_NO.ISO_8859-1'),
			'pl-pl' => array('pl_PL.UTF-8', 'pl_PL', 'pl', 'pol', 'polish', ''),
			'pt-br' => array('pt_BR.UTF-8', 'pt_BR', 'pt', 'ptb', 'portuguese-brazil', ''),
			'pt-pt' => array('pt_PT.UTF-8', 'pt_PT', 'por', 'portuguese', 'pt_PT.ISO_8859-1'),
			'ro-ro' => array('ro_RO.UTF-8', 'ro_RO', 'ron', 'rum', 'ro', 'romanian', 'ro_RO.ISO8859-2'),
			'ru-ru' => array('ru_RU.UTF-8', 'ru_RU', 'ru', 'rus', 'russian', 'ru_RU.ISO8859-5'),
			'sk-sk' => array('sk_SK.UTF-8', 'sk_SK', 'sk', 'slo', 'slk', 'sky', 'slovak', 'sk_SK.ISO_8859-1'),
			'sv-se' => array('sv_SE.UTF-8', 'sv_SE', 'sv', 'swe', 'sve', 'swedish', 'sv_SE.ISO_8859-1'),
			'th-th' => array('th_TH.UTF-8', 'th_TH', 'th', 'tha', 'thai', 'th_TH.ISO_8859-11'),
			'uk-ua' => array('uk_UA.UTF-8', 'uk_UA', 'uk', 'ukr'),
			'vi-vn' => array('vi_VN.UTF-8', 'vi_VN', 'vi', 'vie'),
			'zh-cn' => array('zh_CN.UTF-8', 'zh_CN'),
			'zh-tw' => array('zh_TW.UTF-8', 'zh_TW'),
		);

		if (!empty($guesses[$lang])) {
			$l = @setlocale(LC_TIME, $guesses[$lang]);
			if ($l !== false)
				$locale = $l;
		}
		@setlocale(LC_TIME, $locale);

		return $locale;
	}

//-------------------------------------------------------------
	function assert_article() {
		global $thisarticle;
		if (empty($thisarticle))
			trigger_error(gTxt('error_article_context'));
	}

//-------------------------------------------------------------
	function assert_comment() {
		global $thiscomment;
		if (empty($thiscomment))
			trigger_error(gTxt('error_comment_context'));
	}

//-------------------------------------------------------------
	function assert_file() {
		global $thisfile;
		if (empty($thisfile))
			trigger_error(gTxt('error_file_context'));
	}

//-------------------------------------------------------------
	function assert_link() {
		global $thislink;
		if (empty($thislink))
			trigger_error(gTxt('error_link_context'));
	}

//-------------------------------------------------------------
	function assert_section() {
		global $thissection;
		if (empty($thissection))
			trigger_error(gTxt('error_section_context'));
	}

//-------------------------------------------------------------
	function assert_category() {
		global $thiscategory;
		if (empty($thiscategory))
			trigger_error(gTxt('error_category_context'));
	}

//-------------------------------------------------------------
	function assert_int($myvar) {
		global $production_status;

		if (is_numeric($myvar) and $myvar == intval($myvar)) {
			return (int) $myvar;
		}

		if (($production_status == 'debug') || (txpinterface == 'admin'))
		{
			trigger_error("<pre>Error: '".htmlspecialchars($myvar)."' is not an integer</pre>".
				n.'<pre style="padding-left: 2em;" class="backtrace"><code>'.
				htmlspecialchars(join(n, get_caller(5,1))).'</code></pre>', E_USER_ERROR);
		}
		else
		{
			trigger_error("'".htmlspecialchars($myvar)."' is not an integer.", E_USER_ERROR);
		}

		return false;
	}

//-------------------------------------------------------------
	function replace_relative_urls($html, $permalink='') {

		global $siteurl;

		# urls like "/foo/bar" - relative to the domain
		if (serverSet('HTTP_HOST')) {
			$html = preg_replace('@(<a[^>]+href=")/@','$1'.PROTOCOL.serverSet('HTTP_HOST').'/',$html);
			$html = preg_replace('@(<img[^>]+src=")/@','$1'.PROTOCOL.serverSet('HTTP_HOST').'/',$html);
		}
		# "foo/bar" - relative to the textpattern root
		# leave "http:", "mailto:" et al. as absolute urls
		$html = preg_replace('@(<a[^>]+href=")(?!\w+:)@','$1'.PROTOCOL.$siteurl.'/$2',$html);
		$html = preg_replace('@(<img[^>]+src=")(?!\w+:)@','$1'.PROTOCOL.$siteurl.'/$2',$html);

		if ($permalink)
			$html = preg_replace("/href=\\\"#(.*)\"/","href=\"".$permalink."#\\1\"",$html);
		return ($html);
	}

//-------------------------------------------------------------
	function show_clean_test($pretext) {
		echo md5(@$pretext['req']).n;
		if (serverSet('SERVER_ADDR') == serverSet('REMOTE_ADDR'))
		{
			var_export($pretext);
		}
	}

//-------------------------------------------------------------

	function pager($total, $limit, $page) {
		$total = (int) $total;
		$limit = (int) $limit;
		$page = (int) $page;

		$num_pages = ceil($total / $limit);

		$page = min(max($page, 1), $num_pages);

		$offset = max(($page - 1) * $limit, 0);

		return array($page, $offset, $num_pages);
	}

//-------------------------------------------------------------
// word-wrap a string using a zero width space
	function soft_wrap($text, $width, $break='&#8203;')
	{
		$wbr = chr(226).chr(128).chr(139);
		$words = explode(' ', $text);
		foreach($words as $wordnr => $word) {
			$word = preg_replace('|([,./\\>?!:;@-]+)(?=.)|', '$1 ', $word);
			$parts = explode(' ', $word);
			foreach($parts as $partnr => $part) {
				$len = strlen(utf8_decode($part));
				if (!$len) continue;
				$parts[$partnr] = preg_replace('/(.{'.ceil($len/ceil($len/$width)).'})(?=.)/u', '$1'.$wbr, $part);
			}
			$words[$wordnr] = join($wbr, $parts);
		}
		return join(' ', $words);
	}

//-------------------------------------------------------------

	function maxwords($text,$max=0) {
		
		if (!$max) return $text;
		
		$length = 0;
		$out    = array();
		$text   = explode(' ',$text);
		
		while (($length < $max) and $text) {
			
			$word = array_shift($text);
			
			if ($length + strlen($word) < $max) {
				$out[] = $word;
				$length += strlen($word);
			}
		}
		
		return implode(' ',$out);
	}
		
//-------------------------------------------------------------
	function strip_prefix($str, $pfx) {
		return preg_replace('/^'.preg_quote($pfx, '/').'/', '', $str);
	}

//-------------------------------------------------------------
// wrap an array of name => value tupels into an XML envelope,
// supports one level of nested arrays at most.
	function send_xml_response($response=array())
	{
		ob_clean();
		$default_response = array (
			'http-status' => '200 OK',
		);

		// backfill default response properties
		$response = $response + $default_response;

		header('Content-Type: text/xml');
		txp_status_header($response['http-status']);
		$out[] = '<?xml version="1.0" encoding="utf-8" standalone="yes"?'.'>';
		$out[] = '<textpattern>';
		foreach ($response as $element => $value)
		{
			// element *names* must not contain <>&, *values* may.
			$value = doSpecial($value);
			if (is_array($value))
			{
				$out[] = t."<$element>".n;
				foreach ($value as $e => $v)
				{
					$out[] = t.t."<$e value='$v' />".n;
				}
				$out[] = t."</$element>".n;
			}
			else
			{
				$out[] = t."<$element value='$value' />".n;
			}
		}
		$out[] = '</textpattern>';
		echo(join(n, $out));
		exit();
	}

// -------------------------------------------------------------
// Perform regular housekeeping.
// Might evolve into some kind of pseudo-cron later...
	function janitor()
	{
		global $prefs;

		// update DST setting
		global $auto_dst, $timezone_key, $is_dst;
		if ($auto_dst && $timezone_key)
		{
			$tz = new timezone();
			$is_dst = $tz->is_dst(time(), $timezone_key);
			if ($is_dst != $prefs['is_dst'])
			{
				$prefs['is_dst'] = $is_dst;
				set_pref('is_dst', $is_dst, 'publish', 2);
			}
		}
	}

// -------------------------------------------------------------------------------------

	function get_protocol()
	{
		switch (serverSet('HTTPS')) {
			
			case '':
			
			case 'off': // ISAPI with IIS
				return 'http://'; break;

			default:
				return 'https://'; break;
		}
	}

// -------------------------------------------------------------------------------------
// change: lookup neighbor based on row in result list

	function checkIfNeighbourMod($dir,$row,$table,$area)
	{
		global $WIN;
		
		$total    = (isset($WIN['total']))	? $WIN['total']			: 0;
		$sort     = (isset($WIN['sortby']))	? $WIN['sortby'] 		: '';
		$where    = (isset($WIN['sql']))	? $WIN['sql']['where']	: '';
		$orderby  = (isset($WIN['sql']))	? $WIN['sql']['orderby']	: '';
		$offset   = $row - 1;
		
		if ($dir == 'prev' && --$offset < 0) 	  return ',';
		if ($dir == 'next' && ++$offset > $total) return ',';
		
		if ($dir == 'prev') $row--;
		if ($dir == 'next') $row++;
		
		// for next, check if total matched items are still the same
		if ($dir == 'next' && $area == 'list') {
			$newtotal = getCount($table,$where);
			if ($newtotal > $total) { $offset++; $row++; } // after publish
			if ($newtotal < $total) { $offset--; $row--; } // after save
			
			$WIN['total'] = $newtotal;
		}
		
		$res = safe_row("ID,IF($sort IS NULL OR $sort = '',1,0) AS isnull",$table,"$where ORDER BY $orderby LIMIT $offset,1",0,1);
		$id = (isset($res['ID'])) ? $res['ID'] : '';
		
		return "$id,$row";
	}

// -------------------------------------------------------------------------------------
// new: if file_base_path is relative add it to path_to_site
	
	function make_file_base_path()
	{
		extract(get_prefs()); 
		
		if (substr($file_base_path,0,1) != DIRECTORY_SEPARATOR) 
			$file_base_path = $path_to_site.DIRECTORY_SEPARATOR.$file_base_path;
		
		return $file_base_path;
	}	

// -------------------------------------------------------------------------------------
// new: convert title to name format
	
	function make_name($title,$max=0,$space='-') {
	
		if (!$title) return '';
		
		$title = trim($title);
		$title = trim($title,'-');
		$title = preg_replace('/\s+/',' ',$title);
		$title = dumbDown($title);
		$title = strtolower($title);
		// $title = strtr($title, $GLOBALS['normalizeChars']);
		
		if (!strlen($title) or preg_match('/^[a-z0-9\-]+$/',$title)) {
			
			return $title;
		}
		
		$title = explode(' ',$title);
		
		foreach ($title as $key => $word) {
			
			$length   = str_pad(strlen($word), 2, "0", STR_PAD_LEFT);
			$position = str_pad($key+1, 2, "0", STR_PAD_LEFT);
			
			$word = trim($word);
			
			$word = preg_replace("/&#[0-9]+;/","",$word);
			$word = preg_replace("/\/|\-|\+|\_|\s/",$space,$word);
			$word = preg_replace("/[\:]+/",$space,$word);
			$word = preg_replace("/[^[:alnum:]\-_]/","",$word);
			$word = preg_replace("/[\'\"]/","",$word);
			$word = trim($word,$space);
			
			if ($word) {
				if ($max) 
					$name[$length][$position] = $word;
				else
					$name[] = $word;
			}
		}	
		
		if ($max) {
		
			krsort($name);
			
			$short_name = array();
		
			foreach ($name as $len) {
				foreach ($len as $pos => $word) { 
					if ($max) { $short_name[$pos] = $word; $max--; }
				}
			}
		
			ksort($short_name);
		
			$name = $short_name;
		}
		
		return implode($space,$name);
	}

// -------------------------------------------------------------------------------------
// new: convert name to title format

function make_title($name) {

	// $name = explode('.',$filename);
	// array_pop($name);
	// $name = implode('.',$name);
		
	// - - - - - - - - - - - - - - - - - - - - - - -
	// '_' => ' ' 
	
	$title = preg_replace('/_/',' ',trim($name));
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	// 'abc5'      => 'abc 5'
	// '1xyz'      => '1 xyz'
	// '123-456'   => '123 - 456'
	
	$title = preg_replace('/(\d)(?![\d\:\.\,\/])/',"$1 ",trim($title));
	$title = preg_replace('/(?<![\d\:\.\,\/])(\d)/'," $1",trim($title));
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	// 'abc-xyz'   => 'abc xyz'
	// 'abc - xyz' => 'abc - xyz'
	
	$title = preg_replace('/(?<!\s)\-/',' ',trim($title));
	$title = preg_replace('/\-(?!\s)/',' ',trim($title));	
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	// 'AbcDef' => 'Abc Def'
	
	$title = preg_replace('/([a-z])([A-Z])/',"$1 $2",trim($title));	
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	// fix date format
	
	$title = preg_replace('/\b(\d\d\d\d) \- (\d\d) \- (\d\d)\b/',"$1-$2-$3",trim($title));	
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	// remove extra spaces
	
	$title = preg_replace('/\s+/',' ',trim($title));
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	// capitalize words that are longer than 1 chararcter
	// or appear at the beginning
	
	$title = explode(' ',trim($title));
	
	foreach ($title as $key => $word) {
		
		if (strlen($word) > 1 or $key == 0) {
			$title[$key] = ucfirst($word);
		}
	}
	
	return implode(' ',$title);
}
	
// -------------------------------------------------------------------------------------
// new: pad number with zeros so it can be stored as text
	
	function pad_number($number,$digits=10) {
	
		$whole    = preg_replace('/\.\d+/','',$number);
		$fraction = preg_replace('/\d+/','',$number,1);
		$fraction = ($fraction) ? $fraction : '.00';
		$zeros	  = $digits - strlen($whole);
		$pad      = '';
	
		for ($i=1;$i<=$zeros;$i++) $pad .= '0';
		
		return $pad.$whole.$fraction;
	}

// -------------------------------------------------------------------------------------
// split on "OR", "AND" or "." 
// no mixed operators
// TO REMOVE

	function split_operator($string) {
	
		$ops = array();
		$out = array($string);
		
		for ($i = 1; $i < func_num_args(); $i++) {
			$ops[] = trim(strtolower(func_get_arg($i)));
		}
		
		while ($ops and count($out) == 1) {
    		
    		$op = array_shift($ops);
    		
    		if (preg_match('/^[a-z]+$/',$op)) $op = ' '.$op.' ';
    		
    		$out = array_map('trim',explode($op,strtolower($out[0])));
    		
    		if (count($out) > 1) {
    			
    			if ($op == ',') $op = 'in';
    			if ($op == '&') $op = 'and';
    			
    			return array(' '.$op.' ',$out);
    		}
    	}

		return array('',$out);
	}
     
	// print_r(split_operator('0 or 1 or 2','or'));
   
// -----------------------------------------------------------------------------
// Remove the comparison operator from the string and return the operator.
// If second value is given then return the result of the comparison 
// using the operator prefixed to the first value.
/*
	function comparison(&$testval,$val=NULL) {
		
		global $dump;
		
		$op  = '=';
		$out = $op;
		
		$entities = "&lt;|&gt;|&gte;|&lte;";
		$text_ops = "(lte|gte|lt|gt|eq|neq|not)(?=[\s\d])";	// followed by a space or a digit
		$char_ops = ">\=|<\=|<|>|\=|\!|\!\=";
		$minus    = "\-(\s+)?(?=[a-zTF])"; // followed by zero or more spaces and a letter
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!is_array($testval)) {
		
			$testval  = trim($testval);
			
			$dump[][] = "testval: $testval";
			
			$operator = "$entities|$text_ops|$char_ops|$minus";
			$value    = ".+";
			
			if (preg_match('/^('.$operator.')?('.$value.')/',$testval,$matches)) {
				
				$testval = trim(array_pop($matches));
				
				$string = trim(trim(trim($matches[1]),'&'),';');
				
				switch ($string) {
					case 'lt'	: $op = '<';  break;
					case 'gt'	: $op = '>';  break;
					case 'lte'	: $op = '<='; break;
					case 'gte'	: $op = '>='; break;
					case '!'	: $op = '!='; break;
					case 'not'	: $op = '!='; break;
					case 'neq'	: $op = '!='; break;
					case '-'	: $op = '!='; break;
					case 'eq'	: $op = '=';  break;
					default		: $op = '=';
				}
				
				if ($testval == 'FALSE') {
					
					$out = ($op == '!=') ? true : false;
					
					$dump[][] = "(".trim($string.' '.$testval).") is " . (($out) ? 'TRUE' : 'FALSE');
					
					return $out;
				}	
					
				if ($testval == 'TRUE') {
					
					$out = ($op == '!=') ? false : true;
					
					$dump[][] = "(".trim($string.' '.$testval).") is " . (($out) ? 'TRUE' : 'FALSE');
					
					return $out;
				}
				
				// check for wildcards at the beginning or end
				if (preg_match('/^(\*[^\*]+?\*)|(\*[^\*]+?)|([^\*]+?\*)$/',$testval,$matches)) {
		
					switch (count($matches)) {
						case 3 : $like_type = 'end'; break;
						case 4 : $like_type = 'begin'; break; 
						case 2 : $like_type = 'middle'; break;
					}
					
					$dump[][] = count($matches).' '.$like_type;
					
					if ($op == '=')  $op = 'like';
					if ($op == '!=') $op = 'not like';
					
					$testval = str_replace('*','%',$testval);
				}
				
				$out = $op;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!is_null($val)) {
		
			if (is_array($testval)) { 
				
				$op      = 'in';
				$out     = in_array($val,$testval); 
				$testval = '('.in($testval).')';
			
			} 
			
			if ($op == 'like' or $op == 'not like') {
			
				$test = trim($testval,'%');
				
				$dump[][] = $test;
				
				if ($like_type == 'begin')  $out = (strpos($val,$test) === 0);
				if ($like_type == 'middle') $out = (strpos($val,$test) >= 0);
				if ($like_type == 'end') 	$out = (strpos(strrev($val),strrev($test)) === 0);
				
				$testval = preg_replace('/^%/','*',$testval);
				$testval = preg_replace('/%$/','*',$testval);
			}
			
			if ($op == 'not like') {
				
				$out = !$out;
			}
			
			if (preg_match('/^('.$char_ops.')$/',$op)) {
				
				switch ($op) {
					case '='  : $out = ($val == $testval); $op = '=='; break;
					case '!=' : $out = ($val != $testval); break;
					case '<'  : $out = ($val <  $testval); break;
					case '>'  : $out = ($val >  $testval); break;
					case '<=' : $out = ($val <= $testval); break;
					case '>=' : $out = ($val >= $testval); break;
				}
			}
			
			$dump[][] = "($val $op $testval) is " . (($out) ? 'TRUE' : 'FALSE');
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $out;
	}
*/
// -------------------------------------------------------------------------------------
	function update_alias_articles($id) {
	
		global $WIN,$PFX;
		
		$content_type = $WIN['content'];
		
		if ($aliases = get_aliases($id)) {
			
			$row = safe_row("*",$WIN['table'],"ID=$id");
			$row = doSlash($row);
			
			$colval  = array("ID = ID");
			$exclude = array('ID','Trash','Path','Level','Posted','Section','Position','ParentID','Alias','ParentPosition');
			
			foreach ($row as $key => $value) {
				if (!in_array($key,$exclude)) $colval[] = "$key = '$value'";
			}
			
			safe_update($WIN['table'],impl($colval),"ID IN (".in($aliases).")");
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// update categories
			
			safe_delete("txp_content_category","article_id IN (".in($aliases).") AND type = '$content_type'");
			$rows = safe_rows("*","txp_content_category","article_id = $id AND type = '$content_type'");
			
			if ($rows) {
			
				$values = array();
				
				foreach ($aliases as $alias) {
					
					foreach ($rows as $row) {
					
						$row['article_id'] = $alias;
						$values[] = implode(',',doQuote($row));
					}	
				}
				
				$columns = implode(',',array_keys($row));
				$values  = implode("),\n(",$values);
				$query   = "INSERT INTO ".$PFX."txp_content_category ($columns) VALUES\n($values)";
				
				safe_query($query);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// update path
			
			update_path($aliases,'TREE');
		}
	}

// -------------------------------------------------------------------------------------
// new

	function delete_alias_articles($to_delete) {
		
		global $WIN;
		
		foreach ($to_delete as $key => $id) {
		
			if ($aliases = safe_column("ID",$WIN['table'],"Alias = $id")) {
			
				foreach ($aliases as $alias_id) {
					$to_delete[] = $alias_id;
				}
			}
		}
		
		return array_unique($to_delete);
	}

// -------------------------------------------------------------------------------------
// new

	function get_aliases($id) {
	
		global $WIN;
		
		$out = array();
		
		// from original article to alias
		
		if (getCount($WIN['table'],"Alias = $id")) {						
		
			$out = safe_column("ID",$WIN['table'],"Alias = $id");
		
		// from alias article to other alias articles and original
		
		} else {			
			
			$original = fetch("Alias",$WIN['table'],"ID",$id);
			
			if ($original > 0) {
			
				$out = safe_column("ID",$WIN['table'],"Alias = $original");
				$out[$original] = $original;
				unset($out[$id]);
			}
		}
		
		return $out;
	}

// -------------------------------------------------------------------------------------
// new

	function renumerate($id,$tree=0,$debug=0,$table='')
	{	
		global $WIN;
		
		if (!$table) $table = $WIN['table']; 
		
		$status = ($tree) ? 0 : 2;
		
		$rows = safe_rows(
			"ID,Status,IF(Position < 0,ABS(Position) + 0.5,Position) AS Pos,LastModMicro",
			$table,
			"ParentID = $id 
			 AND (Status != $status OR Position = 999999999)
			 AND Trash = 0 
			 ORDER BY Pos ASC, LastModMicro DESC");
		
		if ($id == 0) {
			
			foreach($rows as $row) {
				
				renumerate($row['ID'],1,$debug,$table);
			}
		
		} else {
				
			$count = 1;
			
			foreach($rows as $row) {
				
				extract($row);
				
				if ($Status != 2 or $Pos == 999999999) {
				
					safe_update($table,"Position = ".($count++),"id = $ID",$debug);
				}
				
				if ($tree) {
					
					renumerate($ID,1,$debug,$table);
				}
			}
		}
	}

// -------------------------------------------------------------------------------------
// new

	function curl_get_file_contents($URL) 
	{	
		$c = curl_init(); 
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $URL); 
		$contents = curl_exec($c); 
		curl_close($c);
	
		if ($contents) return $contents;
			else return FALSE;
	}
	
// -------------------------------------------------------------------------------------
// new

	function multiexplode($delimiters,$string) 
	{
		$out = explode($delimiters[0],$string);
		
		array_shift($delimiters);
		
		if ($delimiters != NULL) {
		
			foreach ($out as $key => $val) {
				 $out[$key] = multiexplode($delimiters, $val);
			}
		}
		
		return  $out;
	}

// -------------------------------------------------------------------------------------
// new

	function dump($stuff) 
	{	
		global $dump;
		
		$dump[][] = print_r($stuff,true);
	}

// -------------------------------------------------------------------------------------
// new

	function sql_dump($sql) {
		
		global $dump;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$q = array();
		
		foreach($sql as $key => $value) {
			
			if ($key == 'SELECT') {
				$q[$key] = $key.' '.implode(', ',$value);
			}
			
			if ($key == 'FROM') {
				
				foreach ($value as $i => $v) {
					$sql['FROM'][$i] = $value[$i] = safe_pfx($v);
				}
				
				$q[$key] = $key.' '.implode(' LEFT JOIN ',$value);
				$q[$key] = str_replace(' JOIN LEFT ',' ',$q[$key]);
			}
			
			if ($key == 'WHERE') {
				$q[$key] = $key.' '.implode(' AND ',$value);
			}
			
			if ($key == 'GROUP BY') {
				// if ($value) $q[$key] = $key.' '.$value;
				if ($value) $q[$key] = $value;
			}
			
			if ($key == 'ORDER BY') {
				if ($value) $q[$key] = $key.' '.$value;
			}
			
			if ($key == 'LIMIT') {
				if ($value) $q[$key] = $key.' '.$value;
			}
		}
		
		$explain = explain(implode(n,$q));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach ($sql['SELECT'] as $key => $item) {
			
			$item = preg_replace('/^\(\s+/','(',$item);
			$item = preg_replace('/\n/',br.n.sp.sp,$item);
			$item = preg_replace('/(ORDER BY taf\.id)/',br.n.sp.sp.sp."$1",$item);
			
			$sql['SELECT'][$key] = $item;
		}
		
		if (isset($sql['WHERE'][0])) {
			if ($sql['WHERE'][0] == trim('1=1') or $sql['WHERE'][0] == trim('1')) 
				array_shift($sql['WHERE']);
		}
		
		foreach ($sql['WHERE'] as $key => $item) {
			
			// remove parenthesies if not an OR statement and it does not 
			// have inner parenthesies
			
			if (preg_match('/^\([^\)\(]+\)$/',$item)) {
				if (!preg_match('/\s+OR\s+/',$item)) {
					 $item = trim($item,'()');
				}
			}
			
			$item = preg_replace('/\n\t/',br.n.sp.sp.sp,$item);
			$item = preg_replace('/\t/',sp.sp.sp,$item);
			$item = preg_replace('/\n\s(AND)/',br.n.sp.sp.sp.sp.sp.sp.sp."$1",$item);
			$item = preg_replace('/(AND \w+_path\.Reverse)/',br.n.sp.sp.sp.sp.sp.sp.sp."$1",$item);
			$item = preg_replace('/(AND \w+_path\.Type)/',br.n.sp.sp.sp.sp.sp.sp.sp."$1",$item);
			$item = preg_replace('/(AND path\.Reverse)/',br.n.sp.sp.sp.sp.sp.sp.sp."$1",$item);
			$item = preg_replace('/(AND path\.Type)/',br.n.sp.sp.sp.sp.sp.sp.sp."$1",$item);
			$item = preg_replace('/(?<=\s)\s/',sp,$item);
			
			$sql['WHERE'][$key] = $item;
		}
		
		$sql['SELECT'] = br.n.sp.sp.implode(','.br.n.sp.sp,$sql['SELECT']);
		$sql['FROM']   = implode(br.sp.sp.sp.sp.sp.'LEFT JOIN ',$sql['FROM']);
		$sql['FROM']   = str_replace(' JOIN LEFT ',' ',$sql['FROM']);
		$sql['WHERE']  = br.n.sp.sp.implode(br.n.sp.sp.'AND ',$sql['WHERE']);
		
		$out = array();
		
		foreach ($sql as $key => $item) {
			
			$item = preg_replace('/\sdesc\b/',' DESC',$item);
			$item = preg_replace('/\sasc\b/',' ASC',$item);
			$item = preg_replace('/\bnow\(\)/','NOW()',$item);
			$item = preg_replace('/\/\*/','<span class="comment">/*',$item);
			$item = preg_replace('/\*\//','*/</span>'.sp,$item);
			
			$class = strtolower(str_replace(' ','-',$key));
			
			if ($key == 'GROUP BY') $item = substr($item,9);
			
			if ($item) $out[] = '<div class="sql '.$class.'">'.$key.sp.$item.'</div>';
		}
		
		return implode(n.n,$out).n.n.$explain;
	}

// -------------------------------------------------------------------------------------

	function backtrace($start=1,$steps=1,$self=0) {
		
		$backtrace = debug_backtrace();
		$out = array();
		
		$exclude = (!$self) ? $backtrace[0]['file'] : '';
		
		$file = $backtrace[$start]['file'];
		$line = $backtrace[$start]['line'];
		
		while ($exclude == $backtrace[$start]['file']) {
			$start = $start + 1;
		}
		
		$last = $start + $steps;
		
		for ($i = $start; $i < $last; $i++ ) {
			
			if (isset($backtrace[$i])) {
				$file = $backtrace[$i]['file'];
				$line = $backtrace[$i]['line'];
				$file = substr($file,strpos($file,'textpattern'));
				$out[] = "Line $line in $file";
			} 
		}
		
		return implode(br.n,$out);
	}
		
// -------------------------------------------------------------------------------------
	
	function inspect($value,$type='',$group='') {
	
		global $inspect_all, $inspect_tag;
		
		$is_tag = false;
		$backtrace = debug_backtrace();
		$last = count($backtrace) - 1;
		
		for ($i = $last; $i >= 0; $i--) {
			if ($backtrace[$i]['function'] == 'doArticles') {
				$is_tag = true;
			}
		}
		
		static $id = 1;
		
		$item = '';
		
		$hr = n.n.'<!--'.str_pad('',120,' - ').'-->'.n.n;
		
		if (is_array($value)) {
			
			$value = '<span style="font-family: Monaco">'.array_to_string($value).'</span>';
		}
		
		if (trim($value)) {
					
			if ($type === 'error') {
				
				$item = t.'<div class="item list error">Error: '.$value.'</div>'.n;
				
			} elseif ($type === 'h2') {
				
				$value = str_replace('$','\$',$value);
				$item = n.'</div>'.$hr.'<div id="block-'.$id++.'" class="block closed">'.n.t.'<div class="item h2"><h2><span></span>'.$value.'</h2></div>'.n;
				
			} elseif ($type === 'line')  {
			
				$item  = t.'<div class="item list line"></div>'.n;
				$item .= t.'<div class="item list">'.$value.'</div>'.n;
				
			} else {
				
				$item = t.'<div class="item list">'.$value.'</div>'.n;
			}
			
		} elseif ($type === 'line')  {
			
			$item = t.'<div class="item list line"></div>'.n;
		}
		
		if ($item) {
			
			if ($group) {
				
				if (!isset($inspect_all[$group])) {
					$inspect_all[$group] = array();
				}
				
				$inspect_all[$group][] = $item;
				
				if ($is_tag) {
					
					if (!isset($inspect_tag[$group])) {
						$inspect_tag[$group] = array();
					}
					
					$inspect_tag[$group][] = $item;
				}
			
			} else {
				
				$inspect_all[][] = $item;
				
				if ($is_tag) {
					$inspect_tag[][] = $item;
				}
			}
		}
	}
	
// -------------------------------------------------------------------------------------

	function inspector() {
		
		global $inspect_all, $inspect_tag, $app_mode;
		
		if (!isset($inspect_all)) return;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach ($inspect_all as $key => $item) {
			
			$inspect_all[$key] = implode(n,$item);
		}
		
		$inspect_all = n.n.'<div id="inspector" class="dump">'.n.n
			.'<div>'.implode('',$inspect_all)
			.n.n.'</div></div>'.n.n;
		
		$output = txpath.'/plugins/inspector/index.html';
		
		if (!is_file($output) or filesize($output) > 10000) {
			write_to_file($output,'');
		}
		
		$error = write_to_file($output,$inspect_all,0,1);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($inspect_tag) {
		
			foreach ($inspect_tag as $key => $item) {
					
				$inspect_tag[$key] = implode(n,$item);
			}
			
			$inspect_tag = n.n.'<div id="inspector" class="dump">'.n.n
				.'<div>'.implode('',$inspect_tag)
				.n.n.'</div></div>'.n.n;
				
		} else {
		
			$inspect_tag = '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($error) $inspect_tag .= n.'<div class="error">'.$error.'</div>';
			
		return ($app_mode != 'async') ? $inspect_tag : '';
	}

// -------------------------------------------------------------------------------------

	function add_inspector(&$html) 
	{
		global $prefs, $inspect_all, $txp_user;
		
		if (!count($inspect_all)) return $html;
		
		doAuth();
		
		if (!$txp_user) return $html;
		
		// add inspector css and script tags to head

		$base = (!$prefs['base']) ? '/admin/' : $prefs['base'];
		
		$html = preg_replace('/(<\/head>)/',t.'<link rel="stylesheet" type="text/css" href="'.$base.'plugins/inspector/inspector.css" />'.n.'</head>',$html);
		$html = preg_replace('/(<\/body>)/',t.'<script type="text/javascript" src="'.$base.'plugins/inspector/inspector.js"></script>'.n.'</body>',$html);
		$html = preg_replace('/(src\=")\/admin\/(js\/)/',"$1".$base."$2",$html);
		
		// add inspector div within the body
		
		$html = preg_replace('/(<body([^>]+)?'.'>)/',"$1".'<div id="inspector-body">',$html);
		$html = preg_replace('/(<\/body>)/',"</div>".inspector()."$1",$html);
		
		return $html;
	}

// -------------------------------------------------------------------------------------

	function add_common_javascript(&$html) 
	{
		global $prefs,$production_status,$siteurl;
		
		$base = (!$prefs['base']) ? '/admin/' : $prefs['base'];
		$nocache = ($production_status != 'live') ? '?'.rand(100000,999999) : '';
		
		$html = preg_replace('/(<\/body>)/',t.'<script type="text/javascript" src="'.$base.'js/publish/global.js'.$nocache.'"></script>'.n.'</body>',$html);
		$html = preg_replace('/(<\/head>)/',t.'<script type="text/javascript">var txp = { plugins:{} };</script>'.n.'</head>',$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - 
		// add google analytics script if any
		
		$tpl = txpath.'/publish/google_analytics.php';
		
		if (is_file($tpl)) {
			
			$root = ROOTNODEID;
			
			$id = safe_field('text_val','txp_content_value',
				"article_id = $root AND field_name='google-analytics' AND Status = 1");
			
			if ($id) {
			
				$domain = preg_replace('/^www\./','',$siteurl);
			
				include($tpl);
			
				$html = preg_replace('/(<\/head>)/',n.$tpl.n.n.'</head>',$html);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - 
		
		return $html;
	}
	
// -------------------------------------------------------------------------------------
// new

	function pre($var) 
	{
		global $prefs;
		
		if (txpinterface == 'public' and $prefs['production_status'] == 'live' and !PREVIEW) {
			return;
		}
		
		if (txpinterface == 'public' and !PREVIEW) {
			return;
		}
		
		echo n.n.'<pre class="dump">'.n;
		
		print_r($var);
		
		if (!is_array($var)) echo n;
		
		echo '</pre>'.n;
	}

//------------------------------------------------------------------------
// new

	function user_agent() 
	{
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$name  = 'other';
		
		if (strpos($agent,'msie') == true)		$name = 'msie';
		if (strpos($agent,'firefox') == true)	$name = 'firefox';
		if (strpos($agent,'safari') == true)	$name = 'safari';
		
		return $name;
	}

//------------------------------------------------------------------------
// new

	function user_agent_os() 
	{
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$name  = 'other';
		
		if (strpos($agent,'Windows') == true)	$name = 'ms';
		
		return $name;
	}
	
//------------------------------------------------------------------------
// new
	
	function binary_to_decimal($values)
	{
		if ($string_in = !is_array($values)) {
			$values = expl($values);
		}
		
		foreach ($values as $key => $value) {
			
			$value = trim($value);
			
			if (strlen($value) > 1) {
				
				$bits = array_reverse(str_split($value));
				
				$num = array_shift($bits);
				$num = ($num >= 1) ? 1 : 0;
				
				foreach($bits as $pos => $bit) {
					$bit = ($bit >= 1) ? 1 : 0;
					switch ($pos) {
						case 0 : $num += ($bit * 2); break;
						case 1 : $num += ($bit * 4); break;
						case 2 : $num += ($bit * 8); break;
						case 3 : $num += ($bit * 16); break;
						case 4 : $num += ($bit * 32); break;
						case 5 : $num += ($bit * 64); break;
						case 6 : $num += ($bit * 128); break;
					}
				}
				
				$values[$key] = $num;
			}
		}
		
		if ($string_in) return impl($values);
		
		return $values;
	}

//--------------------------------------------------------------------------------------

	function category_popup($name, $val, $id='', $truncate=0)
	{
		static $categories = array();
		
		if (!$categories) {
			
			$categories = safe_subtree(
				0,
				"ID, Name AS name, Title AS title, Level AS level, ParentID AS parent",
				"txp_category",
				"(p.Class != 'yes' 
				  OR EXISTS (SELECT ID FROM txp_category AS c 
							  WHERE p.ID = c.ParentID 
								AND c.Class != 'yes'
								AND c.Status IN (4,5) 
								AND c.Trash = 0)
				 ) AND p.Status IN (4,5) 
				   AND p.Trash = 0  
				 ORDER BY Name ASC");
		}
					 		 
		if ($categories) {
			
			return treeSelectInput($name,$categories,$val,$id,$truncate);
		}
		
		return false;
	}
	
//--------------------------------------------------------------------------------------

	function class_popup($name, $val, $id='', $truncate=0)
	{
		$rs = safe_column(
			"Name,Title",
			"txp_category",
			"`Class` = 'yes' AND Trash = 0 ORDER BY Name ASC");
		
		$rs = array_merge(array('NONE'=>''),$rs);
			
		if ($rs) {
			return selectInput($name,$rs, $val,0,'',$id);
		}
		
		return false;
	}

//--------------------------------------------------------------------------------------
	function get_site() {
		
		global $prefs;
		
		$siteurl     = explode('/',$prefs['siteurl']);
		$site_domain = array_shift($siteurl);
		$req_domain  = $_SERVER["HTTP_HOST"];
		$site_name   = '';
		
		$out = array(
			'name'  => ROOTNODE,
			'id'    => ROOTNODEID,
			'level' => 1,
			'path'	=> array()
		);
		
		if ($req_domain != $site_domain) {
			
			$site_name = preg_replace('/\.'.preg_quote($site_domain).'$/','',$req_domain);
			
			if ($site_name == $req_domain) {
				$site_name = preg_replace('/^www\./','',$site_name);
			}
			
			$site_name = make_name($site_name);
		}
		
		if ($site = gps('site')) {
			
			$site_name = make_name($site);
		}
		
		if ($site_name) {
		
			$row = safe_row("ID,Level,Path","textpattern",
				"Name = '$site_name' AND Class = 'site' AND Status = 4 AND Trash = 0 AND ParentID != 0");
				
			if ($row) {
				
				$path = array();
				
				if ($row['Path']) {
					$path = explode('/',$row['Path']);
				}
				
				$path[] = $row['ID'];
				
				$out['name']  = $site_name;
				$out['id']    = $row['ID'];
				$out['level'] = $row['Level'];
				$out['path']  = $path;
			
			} else {
				
				$out['name']  = $site_name;
			}
		}
		
		return $out;
	}
	
//--------------------------------------------------------------------------------------
	function get_site_old() {
	
		global $prefs;
		
		$site = $siteid = '';
		
		$sites = safe_column("Name,ID","textpattern",
			"Trash = 0 AND Status = 4 AND Class = 'site'");
		
		if (count($sites) > 1) {
		
			$siteid = fetch("ID","textpattern","ParentID",0);
	
			$main_site = $prefs['siteurl'];
			echo $req_site  = $_SERVER["HTTP_HOST"];
			
			if ($req_site != $main_site) {
				
				$main_site = array_reverse(explode('.',$main_site));
				$req_site = array_reverse(explode('.',$req_site));
				
				foreach($main_site as $key => $value) {
					
					if ($req_site[$key] == $value) {
						
						unset($req_site[$key]);
					}
				}
				
				$sitename = array_pop($req_site);
				
				if (isset($sites[$sitename])) {
				
					$site = $sitename;
					$siteid = $sites[$sitename];
				}
			}
		} elseif ($sites) {
			
			foreach($sites as $site => $siteid) {
				
				return array($site,$siteid);
			}
		}
		
		return array($site,$siteid);
	}

// -------------------------------------------------------------------------------------
// change: allow curly brackets in addition to angle brackets for txp tags
// change: allow dots in attribute names

	function parse($thing,$namespace='txp',$tagname='\w+',$process='processTags') {
	
		if (!defined("LEFT"))  	  define("LEFT",'[<\{]');
		if (!defined("RIGHT")) 	  define("RIGHT",'[>\}]');	
		if (!defined("ATT_NAME")) define("ATT_NAME",'[\w\.]+');
		
		$f = '@('.LEFT.'/?'.$namespace.':'.$tagname.'(?:\s+'.ATT_NAME.'\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"/'.RIGHT.']+))*\s*/?'.RIGHT.')@s';
		$t = '@:('.$tagname.')(.*?)/?.$@s';

		$parsed = preg_split($f, $thing, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		$level  = 0;
		$out    = '';
		$inside = '';
		$istag  = FALSE;

		foreach ($parsed as $chunk)
		{
			if ($istag)
			{
				if ($level === 0)
				{
					preg_match($t, $chunk, $tag);

					if (substr($chunk, -2, 1) === '/')
					{ # self closing
						$out .= $process($tag[1], $tag[2],'',$namespace);
					}
					else
					{ # opening
						$level++;
					}
				}
				else
				{
					if (substr($chunk, 1, 1) === '/')
					{ # closing
						if (--$level === 0)
						{
							$out  .= $process($tag[1], $tag[2], $inside,$namespace);
							$inside = '';
						}
						else
						{
							$inside .= $chunk;
						}
					}
					elseif (substr($chunk, -2, 1) !== '/')
					{ # opening inside open
						++$level;
						$inside .= $chunk;
					}
					else
					{
						$inside .= $chunk;
					}
				}
			}
			else
			{
				if ($level)
				{
					$inside .= $chunk;
				}
				else
				{
					$out .= $chunk;
				}
			}

			$istag = !$istag;
		}

		return ($level == 0) ? $out : false;
	}

// -------------------------------------------------------------------------------------

	function array_to_string(&$array,$level=0,$keypad=0,&$out=array()) {
		
		$longest = 1;
		$indent  = $keypad * $level;
		
		$keys = array_keys($array);
		
		foreach ($keys as $key) {
			if (strlen($key) > $longest) {
				$longest = strlen($key);
			}
		}
		
		$keypad = $longest + 2;
		
		$length = count($array);
		$count  = 1;
		
		if (count($array)) {
		
			foreach($array as $key => $value) {
				
				$key = str_pad('',$indent).str_pad('['.$key.']',$keypad);
				$key = preg_replace('/ /','&nbsp;',$key);
				
				$line = ($count == $length) ? br.n : '';
				
				if (is_array($value)) {
				
					$out[] = $key.' => Array'.br.n;
					
					array_to_string($value,$level+1,$keypad+1,$out);
					
				} else {
					
					$out[] = $key.' => '.$value.$line;
				}
				
				$count++;
			}
		
		} else {
			
			$out[] = "Empty Array";
		}
		
		return implode(br.n,$out);
	}
	
// -------------------------------------------------------------------------------------
	
	function SimpleHash($str) {    
 
		$hash = 0;
		$length = strlen($str);
	 
		for ($c=0; $c < $length; $c++) {
			
			$num = ord($str[$c]);
			$hash += ($num != 13) ? $num : 0;
		}
	 	
		return $hash;
	}

// -----------------------------------------------------------------------------

	function get_file_id_path($id,$ds='/') {
		
		$digits = str_split($id);
		$path	= array();
		
		if (!$id) return '';
		
		while ($digits) {
			
			$path[] = str_pad(array_shift($digits),count($digits)+1,'0');
		}
		
		return implode($ds,$path);
	}

// -----------------------------------------------------------------------------

	function get_image_id_path($id,$ds='/') {
		
		return get_file_id_path($id,$ds);
	}

// -------------------------------------------------------------
	function get_ip_location($ip)
	{
		// return get_meta_tags('http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress='.$ip);
		
		if (ini_get('allow_url_fopen')) {
		
			$text = file_get_contents("http://freegeoip.net/json/$ip");
			
			if (!strpos($text,'403 Forbidden')) {
			
				return json_decode($text,true);
			}
		} 
		
		return false;	
	}

// -----------------------------------------------------------------------------
	function not(&$var) {
		
		return (is_true($var)) ? FALSE : TRUE;	
	} 

// -----------------------------------------------------------------------------
	function is_true(&$var) {
	
		if ($var) {
			
			if (is_bool($var) || is_int($var) || is_long($var) || is_float($var)) {
			
			  // we agree with PHP for these types
			  return TRUE;
			
			} elseif (is_numeric($var)) {
			
			  // PHP says that "0.0" is TRUE. We disagree.
			  return ((float)$var) ? TRUE : FALSE;
			
			} elseif (is_string($var)) {
			
			  // PHP says that "FALSE" is TRUE. We disagree.
			  return !in_array(strtolower($var),array("false","f","no","n"));
			
			} elseif (is_object($var)) {
			  
			  // PHP says that ((Object) NULL) is TRUE. We disagree.
			  return ((Array)$var) ? TRUE : FALSE;
			}
			
			return TRUE;
	  	}
	  	
	  	return FALSE;
	}
	
// -----------------------------------------------------------------------------
// TEST 
/*
	pre(get_ip_location('96.224.23.96'));
*/
// -----------------------------------------------------------------------------

	function update_update_table($debug=0) 
	{	
		global $PFX, $path_to_site;
		
		if (!table_exists('txp_update')) return;
		
		$exclude = array(
			'test',
			'config.php',
			'xsl/page/',
			'txp_tpl/txp_tpl_c',
			'sessions/',
			'tmp/'
		);
		
		$files = array(
			'modified' => array(),
			'removed'  => array(),
			'added'	   => array()
		);
		
		$now = date("Y-m-d H:i:s",time());
		$existing = safe_rows("File,LastMod","txp_update","Removed = 0",0,$debug);
		
		// ---------------------------------------------------------
		// modified or removed files
			
		if ($existing) {
		
			$lastmod = safe_field("MAX(LastMod)","txp_update","1=1",$debug);
			
			foreach ($existing as $key => $row) {
				
				extract($row);
				
				if (file_exists($path_to_site.DS.$File)) {
				
					$mtime = date("Y-m-d H:i:s",filemtime($path_to_site.DS.$File));
					
					if ($lastmod < $mtime) {
						
						safe_update("txp_update","LastMod = '$now'","File = '$File'",$debug);
						
						$files['modified'][] = $File;
					}
					
					$existing[$key] = $File;
					
				} else {
					
					safe_update("txp_update","LastMod = '$now', Removed = 1","File = '$File'",$debug);
					
					unset($existing[$key]);
					
					$files['removed'][] = $File;
				}
			}
		}
		
		// ---------------------------------------------------------
		// get new files
		
		$list = dirlist(txpath,'',1);
		
		foreach ($list as $key => $file) {
			
			foreach ($exclude as $ex) {
			
				if (strpos($file,$ex) === 0) {
					unset($list[$key]);
					$file = '';
				}
			}
			
			if ($file) {
			
				$list[$key] = 'textpattern'.DS.$file;
			}
		}
		
		array_unshift($list,'index.php');
		array_unshift($list,'.htaccess');
		
		foreach ($list as $key => $file) {
				
			if (!in_array($file,$existing)) {
				
				$files['added'][] = $file;
			}
		}
		
		// ---------------------------------------------------------
		// add new files to database
		
		if ($files['added']) {
			
			$new = $files['added'];
			
			foreach ($new as $key => $file) {
				
				$item = array(
					doQuote($file),
					doQuote(SimpleHash($file)),
					doQuote($now),
					"0"
				);
			
				$new[$key] = implode(',',$item);
			}
			
			$columns = "File,Hash,LastMod,Removed";
			$values  = implode("),\n(",$new);
			$query   = "INSERT INTO ".$PFX."txp_update ($columns) VALUES\n($values)";
		
			safe_query($query,$debug);
		}
		
		// ---------------------------------------------------------
		// display information
		
		$out = '';
		
		foreach ($files as $status => $list) {
			
			$count  = count($list);
			$amount = ($count) ? $count : 'No'; 
			$files  = ($count == 1) ? 'file was' : 'files were';
			
			$out .= graf("<b>$amount $files $status</b>");
			
			if ($count) {
			
				$out .= "<ul>";
				
				foreach ($list as $file) {
					$out .= "<li>$file</li>".n;
				}
				
				$out .= "</ul>";
			}
		}
		
		return $out;
	}
	
// -------------------------------------------------------------------------------------
	function output_css($s='',$n='') {
	
		global $siteurl;
		
		extract(get_prefs('siteurl'));
		
		if ($n) {
			$cssname = $n;
		} elseif ($s) {
			$cssname = safe_field('css','txp_section',"name='$s'");
		}
		
		if ($cssname) {
		
			$css = safe_field('Body','txp_css',"Name = '$cssname' AND Trash = 0");
		
			if ($css) {
				echo preg_replace("/(url\(\'?)\//","$1http://".$siteurl.'/',$css);
			}
		}
	}

// -------------------------------------------------------------
	
	function get_adminpath()
	{
		if (isset($_SERVER['REQUEST_URI'])) {
			
			$requri = explode('?',$_SERVER['REQUEST_URI']);
			return array_shift($requri);
		}
		
		if (isset($_SERVER['REDIRECT_URL'])) {
		 	
		 	return $_SERVER['REDIRECT_URL'];
		}
	
		return $_SERVER['SCRIPT_NAME'];
	}

// -------------------------------------------------------------

	function get_site_name($test='') 
	{
		global $PFX, $site_domain, $site_dir;
		
		if (!is_dir(txpath.'/../sites')) return '';
		
		$requri = trim($_SERVER['REQUEST_URI'],'/');
		
		$site_name = '';
		
		// check for ~tilda/ 
		 
		if (substr($requri,0,1) == '~') {
			
			$requri = explode('/',$requri);
			$site_dir = substr(array_shift($requri),1);
			
			$site_name = $site_dir;	
		
		} else {
		
			if (table_exists("txp_site")) {
			
				if (is_dir(txpath.'/../sites')) {
					
					if (!is_dir(txpath.'/../sites/_domains/'.$site_domain)) {
						
						// must be the main site 
							
						return ''; 
					}
				}
			}
		}
		
		// check for subdomain site
		// if both subdomain and ~tilda/ then $site_name comes from subdomain
		
		if (table_exists("txp_site")) {
		
			$root_domain = safe_field("Domain","txp_site","Domain != '' AND Trash = 0 ORDER BY ID ASC");
			
			if ($site_domain != $root_domain) {
				
				$site_name = safe_field("Name","txp_site","Domain = '$site_domain' AND Trash = 0");
				
				if (!$site_name) {
					
					$site_domain = str_replace('www.','',$site_domain);
					$site_name   = safe_field("Name","txp_site","Domain = '$site_domain' AND Trash = 0");
				}
				
				if (!$site_name) {
					
					$site_domain = 'www.'.$site_domain;
					$site_name   = safe_field("Name","txp_site","Domain = '$site_domain' AND Trash = 0");
				}
			}
		}
		
		return $site_name;
	}

// -------------------------------------------------------------
	
	function get_root_id($table='textpattern') 
	{
		$id = safe_field("ID",$table,"ParentID = 0 AND Trash = 0");
		
		return ($id) ? $id : 0;
	}

// -------------------------------------------------------------
	
	function get_site_config($name) 
	{
		$config = safe_row(
			"DB AS db,
			 DB_User AS user,
			 DB_Pass AS pass,
			 DB_Host AS host,
			 DB_CharSet AS dbcharset,
			 Prefix AS table_prefix,
			 SiteDir AS path_to_site",
			"txp_site",
			"Type = 'site' AND Name = '$name' AND SiteDir != '' AND Trash = 0");
		
		if ($config) {
			
			/* if (substr($config['path_to_site'],0,1) != '/') {
			
				$sites = fetch("Dir","txp_site","ParentID",0);
				
				$config['path_to_site'] = $sites.'/'.$config['path_to_site'];
			} */
			
			if ($config['table_prefix']) {
				
				if (!$config['user']) {
				
					$db = safe_row(
						"DB_User AS user,
			 			 DB_Pass AS pass,
			 			 DB_Host AS host",
			 			 "txp_site","DB_User != '' ORDER BY ID ASC");
			 		
			 		$config['user'] = $db['user'];
			 		$config['pass'] = $db['pass'];
			 		$config['host'] = $db['host'];
				}
				
				$config['table_prefix'] .= '_';
			}
		
		} else {
		
			$title = safe_field("Title","txp_site",
				"Type = 'site' AND Name = '$name' AND SiteDir != '' AND Trash != 0");
			
			if ($title) 
				exit("$title has been removed!");
			else
				exit("$name does not exist!");
		}
		
		return $config;
	}

// -------------------------------------------------------------
	
	function str_begins_with($str,$start) {
		
		return substr(trim($str),0,strlen($start)) == $start;
	}
	
// -------------------------------------------------------------
	
	function str_ends_with($str,$end) {
		
		return substr(trim($str),-strlen($end)) == $end;
	}

// -------------------------------------------------------------
	
	function num_thousand_sep($num) {
		
		$num = intval($num);
		
		if ($num >= 1000 and $num < 1000000) {
			
			return preg_replace('/^(\d+)(\d\d\d)$/',"$1,$2",$num);
		}
		
		if ($num >= 1000000 and $num < 1000000000) {
			
			return preg_replace('/^(\d+)(\d\d\d)(\d\d\d)$/',"$1,$2,$3",$num);
		}
		
		if ($num >= 1000000000 and $num < 1000000000000) { 
			
			return preg_replace('/^(\d+)(\d\d\d)(\d\d\d)(\d\d\d)$/',"$1,$2,$3,$4",$num);
		}
			
		return $num;
	}

// -------------------------------------------------------------
	function png_has_transparency($filename) 
	{
    	if ( strlen( $filename ) == 0 || !file_exists( $filename ) )
    		return false;

    	if ( ord ( file_get_contents( $filename, false, null, 25, 1 ) ) & 4 ) {
    		inspect(1);
        	return true;
		}
		
    	$contents = file_get_contents( $filename );
    	
    	if ( stripos( $contents, 'PLTE' ) !== false && stripos( $contents, 'tRNS' ) !== false ) {
    		inspect(2);
			return true;
		}
		
		return false;
	}

// -------------------------------------------------------------
	function set_cookie($name,$value,$expire=null,$path='/') 
	{
		$uri = trim($_SERVER["REQUEST_URI"],'/');
		
		if (str_begins_with($uri,'~')) {
			$path = '/'.reset(explode('/',$uri)).'/';
		}
		
		if (is_null($expire)) {
			$expire = time() + (60 * 60 * 8);
		}
		
		setcookie($name,$value,$expire,$path);
	}
	
// -------------------------------------------------------------
// get the page number of an article within the current set of articles 
// selected using pagination (pageby attribute in article tag)

	function find_page_number($id,$lo=1,$hi=0) 
	{
		global $thispage;
		
		if (!$hi) $hi = $thispage['numPages'];
		
		$pageby = $thispage['pageby'];
		$query  = $thispage['query'];
		
		// $tables = $thispage['query']['tables'];
		// $where  = $thispage['query']['where'];
		
		if ($lo <= $hi) {
        	
        	$mid = (int)(($hi - $lo) / 2) + $lo;
        		
        	$offset = ($lo - 1) * $pageby;
       		$limit  = (($mid - $lo) + 1) * $pageby;
       		
       		$found = safe_count("($query LIMIT $offset,$limit) AS x","x.ID = $id");
       		
       		/* $found = safe_count(
      			"(SELECT t.ID FROM $tables WHERE $where LIMIT $offset,$limit) AS x",
      			"x.ID = $id"); */
            
            if ($lo == $hi) {
            	
            	return ($found) ? $lo : 0;	
            
            } elseif ($found) {
            	
            	return find_page_number($id,$lo,$mid); 		// in bottom half
            
            } else {
            	
            	return find_page_number($id,$mid+1,$hi);	// in top half	
            }
 		}
	}	

// -------------------------------------------------------------
	function get_base_admin_path() 
	{
		global $PFX;
		
		if (table_exists('txp_site','')) {
			
			if (column_exists('txp_site','ParentID','')) {
			
				$pfx = $PFX; $PFX = '';
				
				$domain = safe_field('Domain','txp_site',"ParentID = 0");
				
				$PFX = $pfx;
				
				if ($domain) {
					
					return 'http://'.$domain.'/admin/';
				}
			}
		}
		
		return '';
	}

// -------------------------------------------------------------
	function clear_cache($debug=0)
	{
		global $prefs;
		
		if ($prefs['page_caching'] and safe_count("txp_cache")) {
			
			safe_delete("txp_cache","1",$debug);
			
			clear_cache_files($debug);
		}
	}

// -------------------------------------------------------------
	function clear_cache_files($debug=0,$dir='')
	{
		global $path_to_site;
		
		$html = $path_to_site.'/html';
		$html_lock = $html.'_LOCK';
		
		if ($dir and is_dir($dir)) {
			
			$objects = scandir($dir); 
			 
			foreach ($objects as $object) { 
			 	
				if ($object != "." && $object != "..") { 
				
					if (filetype($dir."/".$object) == "dir") { 
						
						clear_cache_files($debug,$dir."/".$object); 
					
					} else { 
						
						unlink($dir."/".$object);
						
						if ($debug) pre("DELETE ".str_replace('html_LOCK','html',$dir)."/$object");
					}
				}
			}
			
			reset($objects); 
			
			if ($dir == $html_lock) {
				
				rename($dir,$html);
			
			} else {
				
				rmdir($dir);
				
				if ($debug) pre("DELETE ".str_replace('html_LOCK','html',$dir));
			}
			
		} elseif (is_dir($html) and is_writable($html)) {
			
			rename($html,$html_lock);
			
			@system("rm -rf $html_lock/*");
			
			if (count(scandir($html_lock)) == 2) {
				
				rename($html_lock,$html);
				
				if ($debug) pre("DELETE $html/*");
				
			} else {
				
				clear_cache_files($debug,$html_lock);
			}
		}
	}
	
?>
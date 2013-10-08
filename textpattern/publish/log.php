<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/publish/log.php $
$LastChangedRevision: 3247 $

*/


// -------------------------------------------------------------
	function log_hit($status)
	{
		global $nolog, $logging;
		callback_event('log_hit');
		if(!isset($nolog) && $status != '404') {
			if($logging == 'refer') {
				logit('refer', $status);
			} elseif ($logging == 'all') {
				logit('', $status);
			}
		}
	}

// -------------------------------------------------------------
	function logit($r='', $status='200')
	{
		global $siteurl, $prefs, $pretext;
		$mydomain = str_replace('www.','',preg_quote($siteurl,"/"));
		$uri = @$pretext['request_uri'];
		$out['uri'] = preg_replace('/^\/~[a-z0-9]+\//','/',$uri);
		$out['ref'] = clean_url(str_replace("http://","",serverSet('HTTP_REFERER')));
		$ip = remote_addr();
		$host = $ip;

		if (!empty($prefs['use_dns'])) {
			// A crude rDNS cache
			if ($h = safe_field('host', 'txp_log', "ip='".doSlash($ip)."' limit 1")) {
				$host = $h;
			}
			else {
				// Double-check the rDNS
				$host = @gethostbyaddr($ip);
				if ($host != $ip and @gethostbyname($host) != $ip)
					$host = $ip;
			}
		}

		$out['ip'] = $ip;
		$out['host'] = $host;
		$out['status'] = $status;
		$out['method'] = serverSet('REQUEST_METHOD');
		if (preg_match("/^[^\.]*\.?$mydomain/i", $out['ref'])) $out['ref'] = "";

		if ($r=='refer') {
			if (trim($out['ref']) != "") { insert_logit($out); }
		} else insert_logit($out);
	}

// -------------------------------------------------------------
	function insert_logit($in)
	{
		global $DB;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$in = doSlash($in);
		extract($in);
		
		if (!isset($page)) $page = 'index.html';
		
		$page = explode('/',trim($page));
		$page = array_pop($page);
		$page = trim(preg_replace('/\.html?/','',trim($page)));
		if (!strlen($page)) $page = 'index';
		
		$name   = make_name($page);
		$title  = doSlash(make_title($page));
		$parent = fetch("ID","txp_log","ParentID",0); 
		$user	= safe_field("name","txp_users","1=1 ORDER BY privs ASC LIMIT 1");
		
		$id = safe_insert("txp_log", 
			"Posted = now(),
			 Title  = '$title',
			 Name   = '$name',
			 page   = '$uri',
			 ip     = '$ip',
			 host   = '$host',
			 refer  = '$ref',
			 Status = '$status',
			 method = '$method',
			 ParentID = $parent,
			 Type     = 'page',
			 AuthorID = '$user'");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		unset($in['status'],$in['method'],$in['uri'],$in['ref']);
		$in['page']  = $uri;
		$in['refer'] = $ref;
		
		$count = safe_row(
		 	"TRIM(LEADING '0' FROM page) AS page,
			 TRIM(LEADING '0' FROM ip) AS ip,
			 TRIM(LEADING '0' FROM host) AS host,
			 TRIM(LEADING '0' FROM refer) AS refer",
			"txp_log","ID = $parent");
		
		foreach($count as $key => $value) {
			
			if (!strlen($in[$key])) {
			
				unset($count[$key]);
			
			} else {
			
				$where = array(
					"ID != $id",
					"$key = '".$in[$key]."'",
					"Trash = 0"
				);
				
				if ($key == 'page') {
					$where[] = "Name = '$name'";
				}
				
				if (safe_count("txp_log",implode(' AND ',$where))) {
				
					unset($count[$key]);
				
				} else {
				
					$count[$key] = str_pad($value + 1,9,'0',STR_PAD_LEFT);	
					
					if ($key == 'ip') {
						$location = get_ip_location($value);
					}
				}
			}
		}
		
		if ($count) {
		
			safe_update("txp_log",doQuote($count),"ID = $parent");
		}
	}

// -------------------------------------------------------------
	function cacheit($html,$name='')
	{
		global $path_to_site;
		
		if (is_dir($path_to_site.'/html_LOCK')) { 
		
			return;
		}
		
		if ($name) {
			
			$html = doSlash($html);
			
			safe_insert("txp_cache","name = '$name', html = '$html'");
		
		} else {
			
			$page = getURI();
			
			// only cache html pages that have no query strings 
			
			if (!preg_match('/\.html$/',$page)) return;
			
			// do not cache the contact form page 
			// TODO: there should be a no-cache option for each page 
		
			if (preg_match('/\/contact\//',$page)) return;
			
			if (preg_match('/\.html$/',$page)) {
			
				cache_html_as_file($page,$html);
				
				$hash = SimpleHash($page);
				$page = doSlash($page);
				$html = doSlash($html);
				
				safe_insert("txp_cache","page = '$page', html = '$html', hash = $hash");
			}
		}
	}

// -------------------------------------------------------------
	function cache_html_as_file($page,$content)
	{
		global $path_to_site;
		
		$html = $path_to_site.'/html';
		$html_lock = $html.'_LOCK';
		
		if (!is_dir($html) and !is_dir($html_lock)) {
			
			@mkdir($html,0755);
		}
		
		if (is_dir($html)) {
			
			if (is_writable($html)) {
				
				$page = preg_replace('/^\/~[a-z0-9]+\//','',$page);	
				
				$path = explode('/',$page);
				$file = array_pop($path);
				$path = implode('/',$path);
				
				if ($path) {
					
					if (!is_dir("$html/$path")) {
						@mkdir("$html/$path",0755,true);
					}
					
					if (is_dir("$html/$path")) {
						write_to_file("$html/$path/$file",$content);
					}	
				
				} else {
					
					write_to_file("$html/$file",$content);
				}
			}
		}
	}
	
// -------------------------------------------------------------
	function trycache($name='')
	{
		if ($name) {
		
			return doStrip(fetch("html","txp_cache","name",$name));
		
		} else {
			
			$page = getURI();
			$hash = SimpleHash($page);
			$page = doSlash($page);
			
			return doStrip(safe_field("html","txp_cache","page = '$page' AND hash = $hash"));
		}
	}

// -------------------------------------------------------------
	function getURI()
	{	
		if (isset($_SERVER['REQUEST_URI']))
			$out = $_SERVER['REQUEST_URI'];
		elseif (isset($_SERVER['SCRIPT_NAME'])) {
			$out  = $_SERVER['SCRIPT_NAME'];
			$out .= ($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '';
		}
		
		return $out;
	}

?>

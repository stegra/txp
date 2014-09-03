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
		
		$out['agent'] = logit_agent(serverSet('HTTP_USER_AGENT'));
		
		if ($r=='refer') {
			if (trim($out['ref']) != "") { insert_logit($out); }
		} else insert_logit($out);
	}

// -------------------------------------------------------------
	function logit_agent($agent) {
		
		$agent = doSlash(trim($agent,"'"));
		
		$id = fetch('id','txp_log_agent','agent',$agent);
		
		if ($id) {
			
			safe_update('txp_log_agent',"count = count + 1","id = $id");
		
		} else {
			
			$id = safe_insert('txp_log_agent',"agent = '$agent'");
		}
		
		return $id;
	}

// -------------------------------------------------------------
	function insert_logit($in)
	{
		global $DB,$txp_user,$pretext,$expire_logs_after;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$expire_logs_after = assert_int($expire_logs_after);
		
		safe_delete('txp_log', 
			"Posted < date_sub(now(), interval $expire_logs_after day) AND Type = 'page'");

		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$in = doSlash($in);
		extract($in);
		
		$req   = explode('?',$uri);
		
		$page  = array_shift($req);
		$page  = preg_replace('/\.(html?|xml)/','',$page);
		$page  = ltrim($page,'/');
		$page  = (!strlen($page)) ? 'index' : $page;
		$page  = explode('/',$page);
		$name  = array();
		$title = array();
		
		foreach ($page as $key => $item) {
			
			if ($key > 0 and $item == 'index') continue;
			
			$name[]  = make_name($item);
		    $title[] = doSlash(make_title($item));
		}
		
		$q = array_shift($req);
		
		if ($q) {
		
			$q = explode('&',$q);
			
			foreach ($q as $item) {
				
				$name[] = make_name($item);
				
				if ($item == 'preview') {
				
					$title[] = '(Preview)';
				
				} elseif (str_begins_with($item,'pg')) {
					
					$title[] = 'Page '.substr($item,3);
				} 
			}
		}
		
		$name  = implode('-',$name);
		$title = implode(' ',$title);
		
		$parent = fetch("ID","txp_log","ParentID",0);
		$user   = ($txp_user) ? $txp_user : 'textpattern'; 
		
		$id = safe_insert("txp_log", 
			"Posted = now(),
			 Title  = '$title',
			 Name   = '$name',
			 page   = '$uri',
			 ip     = '$ip',
			 host   = '$host',
			 refer  = '$ref',
			 agent  = '$agent',
			 Status = '4',
			 method = '$method',
			 ParentID = $parent,
			 Type     = 'page',
			 AuthorID = '$user'");
		
		$pretext['logid'] = $id; 
		
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
					
					/* if ($key == 'ip') {
						$location = get_ip_location($value);
					} */
				}
			}
		}
		
		if ($count) {
		
			safe_update("txp_log",doQuote($count),"ID = $parent");
		}
	}

//-------------------------------------------------------------
	function log_screensize()
	{
		$id = assert_int(gps('id',0));
		$current_width = assert_int(gps('screensize',0));
		
		if ($id and $current_width) {
			
			safe_update('txp_log',"agent_width = $current_width","ID = $id");
			
			$agent = fetch('agent','txp_log','ID',$id);
			$width = fetch('width','txp_log_agent','id',$agent);
			
			if ($width == 0 or $width > $current_width) {
				safe_update('txp_log_agent',"width = $current_width","id = $agent");
			}
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
			
			// only cache html pages that have no post or get 
			
			if (count($_POST) or count($_GET)) return;
			
			// do not cache the contact form page 
			// TODO: there should be a no-cache option for each page 
		
			if (preg_match('/\/contact\//',$page)) return;
			if (preg_match('/\/search\//',$page)) return;
			
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
			
			return;
			// @mkdir($html,0755);
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
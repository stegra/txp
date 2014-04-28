<?php
/*
           _______________________________________
   _______|                                       |_________
  \       |                                       |        /
   \      |              Textpattern              |       /
    \     |                                       |      /
    /     |_______________________________________|      \
   /___________)                               (___________\

	Copyright 2005 by Dean Allen 
	All rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement 

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/publish.php $
$LastChangedRevision: 3258 $

*/
	if (!defined("txpinterface")) {
	
		die('If you just updated and expect to see your site here, '. 
		    'please also update the files in your main installation directory. '.
			'(Otherwise note that publish.php cannot be called directly.)');
	}
	
	define("TXP_UPDATE",0);
	
	include txpath.'/publish/lib/publish.php';
	include txpath.'/publish/log.php';
	include txpath.'/publish/comment.php';
		
//	set_error_handler('myErrorHandler');

// TODO: make init() function so we can do code folding
	
	ob_start();

    	// start the clock for runtime
	getmicrotime('runtime');

		// initialize parse trace globals
	$txptrace        = array();
	$txptracelevel   = 0;
	$txp_current_tag = '';
	$error_message   = '';

		// get all prefs as an array
	$prefs = get_prefs();

		// add prefs to globals
	extract($prefs);

	if (!str_begins_with($timezone_key,'GMT')) {
		date_default_timezone_set($timezone_key);
	}
	
		// check the size of the url request
	bombShelter(); 

		// set a higher error level during initialization
	set_error_level(@$production_status == 'live' ? 'testing' : @$production_status);

		// use the current URL path if $siteurl is unknown
	if (empty($siteurl))
		$prefs['siteurl'] = $siteurl = $_SERVER['HTTP_HOST'] . (($site_dir) ? '/~'.$site_dir : '');
	
	if (empty($path_to_site)) {
		$path_to_site = updateSitePath(dirname(dirname(__FILE__)));
	}
	
	if (isset($txpcfg['path_to_site']) and $path_to_site != $txpcfg['path_to_site']) {
		$path_to_site = updateSitePath($txpcfg['path_to_site']);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// get base admin path
	
	$base = $prefs['base'] = get_base_admin_path();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// new global site settings
	
	$site_base_path = $path_to_site;
	$site_http 		= 'http://'.$site_url;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	define('PROTOCOL',get_protocol());
	
		// v1.0: this should be the definitive http address of the site	
	if (!defined('hu')) 
		define("hu",PROTOCOL.$siteurl.'/');

		// v1.0 experimental relative url global
	if (!defined('rhu'))
		define("rhu",preg_replace("|^https?://[^/]+|","",hu));

		// 1.0: a new $here variable in the top-level index.php 
		// should let us know the server path to the live site
		// let's save it to prefs
	// if (isset($here) and $path_to_site != $here) updateSitePath($here);

		// 1.0 removed $doc_root variable from config, but we'll
		// leave it here for a bit until plugins catch up
	$txpcfg['doc_root'] = @$_SERVER['DOCUMENT_ROOT'];
	// work around the IIS lobotomy
	if (empty($txpcfg['doc_root']))
		$txpcfg['doc_root'] = @$_SERVER['PATH_TRANSLATED'];

	if (!defined('LANG'))
		define("LANG",$language);
	if (!empty($locale)) setlocale(LC_ALL, $locale);

		//Initialize the current user
	$txp_user = NULL;
	doAuth();
	
		// set preview mode
		
	if ($txp_user and isset($_GET['preview'])) {
		define('PREVIEW',true);
		set_cookie('txp_sitemode_preview','on');
	} elseif ($txp_user and cs('txp_sitemode_preview') == 'on') {
		define('PREVIEW',true);
	} else {
		define('PREVIEW',false);
	}	
	
	// set edit mode 
		
	if ($txp_user and isset($_GET['edit'])) {
		set_cookie('txp_sitemode_edit','on');		
	}	
	
		//i18n: $textarray = load_lang('en-gb');
	$textarray = load_lang(LANG);

		// tidy up the site
	janitor();

		// here come the plugins
	if ($use_plugins) load_plugins();

		// this step deprecated as of 1.0 : really only useful with old-style
		// section placeholders, which passed $s='section_name'
	$s = (empty($s)) ? '' : $s;
	
	$WIN = array(
		'content' => 'article',
		'table'	  => 'textpattern',
		'sortby'  => 'Posted',
		'sortdir' => 'DESC',
		'linkdir' => 'asc',
		'view'	  => '',
	);
	
	define("ROOTNODE",fetch("Name","textpattern","ParentID",0));
	define("ROOTNODEID",fetch("ID","textpattern","ParentID",0));
	define("TRASH_ID",fetch("ID","textpattern","name","TRASH"));
	define("IMPATH",$site_base_path.DS.$img_dir.DS);
	define("LEVELS",fetch("MAX(Level)","textpattern"));
	
	$pretext = !isset($pretext) ? array() : $pretext; 
	$pretext = array_merge($pretext, pretext($s,$prefs));
	callback_event('pretext_end'); 
	extract($pretext);
	
	if ($ev = gps('event')) {
		
		$area  = (isset($areas[$ev])) ? $areas[$ev] : '';
		
		$inc = txpath.'/include/txp_'.$area.'_'.$ev.'.php';
		
		// echo "($inc)";
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (isset($_POST['screensize'])) {
	
		log_screensize();
		
		exit;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (isset($_GET['testpath'])) {
		
		include txpath.'/publish/lib/test_path.php';
		
		exit;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	// Now that everything is initialized, we can crank down error reporting
	set_error_level($production_status);

	if (isset($feed))
		exit($feed());
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// SignUp Form Submit
	
	if (gps('signup') == '1' and gps('parentid')) {
		
		$txp_error_message = saveSignup();
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Comment Form Submit
	
	if (gps('parentid') && gps('submit')) {
		
		saveComment();
	} elseif (gps('parentid') and $comments_mode==1) { // popup comments?
		header("Content-type: text/html; charset=utf-8");
		exit(popComments(gps('parentid'))); 
	}
		
	// we are dealing with a download
	if (@$s == 'file_download') {
	
		callback_event('file_download');
		
		if (!isset($file_error)) {
				
				assert_int($id);
				
				if (column_exists('txp_file','FileName')) {
					$file = safe_row("FileID,FileName AS filename","txp_file","ID = $id");
				} else {
					$file = safe_row("FileID,CONCAT(Name,ext) AS filename","txp_file","ID = $id");
				}
				
				extract($file);
				
				$fullpath = build_file_path($file_base_path,$filename,$FileID);
				
			 // if ($FileID and is_file($fullpath)) {
				if (is_file($fullpath)) {

					// discard any error php messages
					ob_clean();
					$filesize = filesize($fullpath); $sent = 0;
					header('Content-Description: File Download');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="' . basename($filename) . '"; size = "'.$filesize.'"');
					// Fix for lame IE 6 pdf bug on servers configured to send cache headers
					header('Cache-Control: private');
					@ini_set("zlib.output_compression", "Off");
					@set_time_limit(0);
					@ignore_user_abort(true);
					if ($file = fopen($fullpath, 'rb')) {
						while(!feof($file) and (connection_status()==0)) {
							echo fread($file, 1024*64); $sent+=(1024*64);
							ob_flush();
							flush();
						}
						fclose($file);
						// record download
						if ((connection_status()==0) and !connection_aborted() ) {
							safe_update("txp_file", "downloads=downloads+1", 'id='.intval($id));
							log_hit('200');
						} else {
							$pretext['request_uri'] .= ($sent >= $filesize)
								? '#aborted'
								: "#aborted-at-".floor($sent*100/$filesize)."%";
							log_hit('200');
						}
					}
				} else {
					$file_error = 404;
				}
		}

		// deal with error
		if (isset($file_error)) {
			switch($file_error) {
			case 403:
				txp_die(gTxt('403_forbidden'), '403');
				break;
			case 404:
				txp_die(gTxt('404_not_found'), '404');
				break;
			default:
				txp_die(gTxt('500_internal_server_error'), '500');
				break;
			}
		}

		// download done
		exit(0);
	}

	// send 304 Not Modified if appropriate
	handle_lastmod();

	// log the page view
	log_hit($status);

	$txptagtrace   = array();
	$inspect_tag   = array();
	$tag_counter   = array();
	$content_type_stack = new Stack('article');
		
// -------------------------------------------------------------------------------------
	function preText($s,$prefs) {
		
		global $site_base_path,$is_article_list,$thisarticle, $article_stack;
		
		// inspect('preText()','h2');
		
		$article_stack = new ArticleStack();
		
		$is_404 = 0;
		
		callback_event('pretext');
		
		extract($prefs);
		
		extract(get_prefs("page_caching,permlink_mode"));
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// set messy variables
		
		$out = makeOut(
			'id','s','t','n','c','cl','q','pg','p',
			'month','page','pophelp','custom','site','path','sort'
		); 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (gps('rss')) {
			include txpath.'/publish/rss.php';
			$out['feed'] = 'rss';
		}

		if (gps('atom')) {
			include txpath.'/publish/atom.php';
			$out['feed'] = 'atom';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// some useful vars for taghandlers, plugins
		
		$out['request_uri'] = preg_replace("|^https?://[^/]+|i","",serverSet('REQUEST_URI'));
		$out['qs'] = serverSet('QUERY_STRING');
		
		if (strlen($out['request_uri']) > 1) {
			$out['request_uri'] = ltrim($out['request_uri'],'/');
		}
		
		if (strlen($out['request_uri']) == 0) {
			$out['request_uri'] = '/';
		}
		
		// IIS fix
		if (!$out['request_uri'] and serverSet('SCRIPT_NAME'))
			$out['request_uri'] = serverSet('SCRIPT_NAME').( (serverSet('QUERY_STRING')) ? '?'.serverSet('QUERY_STRING') : '');
		
		// another IIS fix
		if (!$out['request_uri'] and serverSet('argv'))
		{
			$argv = serverSet('argv');
			$out['request_uri'] = @substr($argv[0], strpos($argv[0], ';') + 1);
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// define the useable url, minus any subdirectories.
		// this is pretty fugly, if anyone wants to have a go at it - dean
		
		$out['subpath'] = $subpath = preg_quote(preg_replace("/https?:\/\/.*(\/.*)/Ui","$1",hu),"/");
		$out['req'] = $req = preg_replace("/^$subpath/i","/",$out['request_uri']);
		
		$req_path = explode('?',$req);
		$req_path = explode('.',$req_path[0]);
		$req_path = trim($req_path[0],'/');
		$req_path = preg_replace('/^~[a-z0-9]+(\/|$)/','',$req_path);
		
		$out['req_path'] = ($req_path) ? $req_path : 'index';
		
		$messy_req = (preg_match('/\/?(index\.php)?\?/',$req)) ? true : false;
		if ($messy_req and preg_match('/\.html?\?/',$req)) $messy_req = false;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// allow article preview
		
		if (gps('txpreview') and is_logged_in())
		{
			global $nolog;

			$nolog = true;
			$rs = safe_row("ID as id,Section as s",'textpattern','ID = '.intval(gps('txpreview')).' limit 1');

			if ($rs and $is_404)
			{
				$is_404 = false;
				$out = array_merge($out, $rs);
			}
		}
				
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Stats: found or not
		
		$out['status'] = ($is_404 ? '404' : '200');

		$out['pg'] = is_numeric($out['pg']) ? intval($out['pg']) : '';
		$out['id'] = is_numeric($out['id']) ? intval($out['id']) : '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get id of potential filename
		
		if ($out['s'] == 'file_download') {
		
			if (!is_numeric($out['id'])) {
				$rs = safe_row("*", "txp_file", "Name='".doSlash($out['id'])."' AND Status = 4");
			} else {
				$rs = safe_row("*", "txp_file", 'ID='.intval($out['id']).' AND Status = 4');
			}

			return ($rs) 
				? array_merge($out, $rs) 
				: array('s'=>'file_download','file_error'=> 404);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// check the html cache
			
		$page_caching = $prefs['page_caching'];
		
		if (gps('contact')) $page_caching = false;
		if (gps('nocache')) $page_caching = false;
		if (cs('txp_sitemode_edit') == 'on') 	$page_caching = false;
		if (cs('txp_sitemode_preview') == 'on') $page_caching = false;
	
		if ($page_caching and $html = trycache()) {
			
			$out['html'] = $html;
			
			return $out;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		if (!$is_404) $out['s'] = (empty($out['s'])) ? 'default' : $out['s'];
		
		$out['lg'] = get_language($out['qs']);
		
		if (!empty($out['q'])) $s = 'search'; // new
		
		$section = $out['s'];
		$id      = $out['id'];
		$path    = $out['path'];
		$name    = $out['n'];
		$pophelp = $out['pophelp'];
		$lg 	 = $out['lg'];
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// advanced search 
		
		foreach ($_POST as $n => $v) {
			
			if (substr($n,0,2) == 'q_') {
			
				$n = make_name(substr($n,2));
				
				$out['q.'.$n] = doSlash($v);
 			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// site
		
		// $_POST['site'] = 'more';
		
		$out['SITE'] = $site = get_site();
		$out['site'] = $site['name'];
		
		define('SITE_ID',$site['id']);
		define('SITE_NAME',$site['name']);
		define('SITE_LEVEL',$site['level']);
		
		// inspect($site);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// check the request path for an id or page number
		// example: gallery/lol/5/cat.html
		
		if (!$messy_req) { 
			
			$req = parse_req($out['req'],$out['qs']);
			
			$out['path']  = $path  = $req['path'];
			$out['id']    = $id    = $req['id'];
			$out['pg']    = $pg    = $req['pg'];
			$out['level'] = $level = $req['level'];
			
			$req_level = $level;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// context ID and path
		
		$context = get_context($path,SITE_ID,SITE_LEVEL+1);
		
		if (!$id) {
			
			if ($context['id']) {
			
				$out['id'] = $id = $context['id'];
				
				// inspect("CONTEXT ID: ".$id,'line');
				
				if ($path = $context['path']) { 
					
					$out['level'] = $level = $context['level'];
					
					$path = implode('/',$path);
					
					// inspect("CONTEXT PATH: ".$path,'line');
				}  
				
				if ($id == ROOTNODEID) {
					
					$out['level'] = 1;
				}
			}
		
		} elseif ($path) {
			
			$out['level'] = $level;
			$context['ids'][$level] = $id;
			
			// OLD: $out['level'] = count(explode('/',$path));
		}
		
		$out['ids'] = implode('/',$context['ids']);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get page template
		
		$page = get_page($req['path'],$id,$req_level);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get context article data
		
		$out['unique']   = false;
		$is_article_list = true;
		
		if (!$is_404 and !$pophelp) {
		
			doArticle(array('path'=>$path),$id);
			
			if ($id != ROOTNODEID) {
				$out['unique']   = true;
				$is_article_list = false;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// popup help
		
		if ($pophelp == 'custom-field') {
			
			include_once txpath.'/lib/classTextile.php';
			$textile = new Textile();
			
			$field = safe_row("title,Body AS help","txp_custom","ID = $id");
			
			$thisarticle['thisid'] = $id;
			$thisarticle['title']  = doStrip($field['title']);
			$thisarticle['body']   = $textile->TextileThis(doStrip($field['help']));
			
			$page = 'pophelp';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$out['path_from_root'] = rhu; // these are deprecated as of 1.0
		$out['pfr']            = rhu; // leaving them here for plugin compat

		$out['path'] 		   = $path;
		$out['path_to_site']   = $site_base_path;
		$out['permlink_mode']  = $permlink_mode;
		
		if ($page) {
			
			$out['page'] = $page;
		
		} else {
			
			$out['page'] = fetch("ID","txp_page","Name","error_default");
			$GLOBALS['txp_error_status']  = '404';
			$GLOBALS['txp_error_message'] = "<b>".trim($out['request_uri'],'/')."</b> not found!";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		// pre($out);
		// pre($thisarticle);
		// inspect($out);
		// inspect($thisarticle);
		
		return $out; 
	}

//	textpattern() is the function that assembles a page, based on
//	the variables passed to it by pretext();

// -------------------------------------------------------------------------------------
	function textpattern() {
		
		global $txp_user,$pretext,$prefs,$qcount,$qtime,$production_status,$txptrace,$has_article_tag,$html,$inspector;
		
		extract($pretext); 
		
		// inspect('textpattern()','h2','textpattern');
		// inspect($pretext);
		// callback_event('textpattern'); 
		plugin_callback();
		
		if ($status == '404') txp_die(gTxt('404_not_found'), '404');
		if ($status == '410') txp_die(gTxt('410_gone'), '410');
		
		$has_article_tag = false;
		$content_type = (preg_match('/\.xml$/',$request_uri)) ? 'xml' : 'html';
		
		$page_caching = $prefs['page_caching'];
		if (PREVIEW) 							$page_caching = false;
		if (cs('txp_sitemode_edit') == 'on') 	$page_caching = false;
		if (!empty($pretext['html'])) 			$page_caching = false;
		
		if ($html) {
			// inspect("Cached: $request_uri",'line','textpattern');
		} else {
			// inspect("path: $path id: $id page: $page",'line','textpattern');
		}
		
		// generate html content from the db
		
		if (!$html) {
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// get txp template
			
			if ($page) { 
				
				$html = doStrip(safe_field('Body_html','txp_page',"ID = $page"));
				
				if (!$html) $html = doStrip(safe_field('Body','txp_page',"ID = $page"));
				
				if (!$html) txp_die(gTxt('unknown_section'), '404');
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (!$html) txp_die(gTxt('404_not_found'), '404');
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			// echo('<pre>START PARSE -----------------------------------------------</pre>');
			
			$html = parse($html);  
			
			// echo('<pre>END PARSE -----------------------------------------------</pre>');
			
			$html = ($prefs['allow_page_php_scripting']) ? evalString($html) : $html;
			$html = tidy_html($html); 
			
			if ($page_caching) cacheit($html);
			
		} else {
		
			$html = parse($html); 
			$html = tidy_html($html);
		}
		
		// callback_event('textpattern_html');
		plugin_callback(2);
		
		// useful for clean urls with error-handlers
		txp_status_header('200 OK');
		
		header("Content-Type: text/".$content_type."; charset=utf-8");
		
		if ($content_type == 'html') {
			$html = '<!DOCTYPE html>'.n.$html;
		}
		
		$runtime = getmicrotime('runtime');
		
		if ($content_type == 'html') {
			
			add_common_javascript($html);
			
			if ($txp_user) {
				add_toolbar_iframe($html);
			}
			
			if ($production_status != 'live' or 
			   ($production_status == 'live' and PREVIEW)) {
				
				// inspect('Runtime: '.$runtime,'line','textpattern');
				// inspect('Query time: '.sprintf('%02.6f', $qtime),'','textpattern');
				// inspect('Queries: '.$qcount,'','textpattern');
				// inspect(maxMemUsage('end of textpattern()',1),'','textpattern');
				
				$html = add_inspector($html);
			}
		}
		
		echo $html;
		
		if ($production_status != 'live' or 
		   ($production_status == 'live' and PREVIEW)) {

			echo n.n.n,comment('Runtime: '.$runtime);
			echo n,comment('Query time: '.sprintf('%02.6f', $qtime));
			echo n,comment('Queries: '.$qcount);
			echo maxMemUsage('end of textpattern()',1);
			if (!empty($txptrace) and is_array($txptrace))
				echo n, comment('txp tag trace: '.n.str_replace('--','&shy;&shy;',join(n, $txptrace)).n);
				// '&shy;&shy;' is *no* tribute to Kajagoogoo, but an attempt to avoid prematurely terminating HTML comments
		}
		
		// callback_event('textpattern_end');
		plugin_callback(3);
	}

// =============================================================================
	function article($atts, $thing = NULL) {	
		
		global $is_article_body, $has_article_tag, $txptrace, $pretext;
		
		if ($is_article_body) {
			trigger_error(gTxt('article_tag_illegal_body'));
			return '';
		}
		
		// atts.doArticles
		
		$has_article_tag = true;
		$iscustom = false;
		
		// if (isset($atts['path'])) 		$iscustom = true;
		// if (isset($atts['type'])) 		$iscustom = true;
		// if (isset($atts['category'])) 	$iscustom = true;
		
		if (count($atts)) $iscustom = true;
		
		if ($pretext['page'] == 'default') {
			if (!safe_count("txp_page","name = 'default' AND user_xsl != ''")) {
				$atts['section'] = 'articles';
			}
		}
		
		return parseArticles($atts,$iscustom,$thing);
	}

// -------------------------------------------------------------------------------------
	function if_article($atts, $thing = NULL) {
		
		// atts.doArticles
		
		$atts['limit'] = 1;
		
		return parse(EvalElse($thing, article($atts,"OK")));
	}

// -------------------------------------------------------------------------------------
	function if_not_article($atts, $thing = NULL) {
		
		// atts.doArticles
		
		$atts['limit'] = 1;
		
		if (!strlen(trim(article($atts,"OK")))) { 
			
			return parse($thing);
		}
	}

// -------------------------------------------------------------------------------------
	function article_parent($atts, $thing = NULL) {
		
		global $txp_current_atts;
		
		// atts.doArticles
		
		$txp_current_atts['path'] = $atts['path'] = '..';
		
		if (!in_atts('status')) {
			$txp_current_atts['status'] = $atts['status'] = '*';
		}
		
		return article($atts,$thing);
	}
	
// -------------------------------------------------------------------------------------
// this tag is no longer necessary

	function article_custom($atts, $thing = NULL) {
		
		// atts.doArticles
		
		return parseArticles($atts, 1, $thing);
	}

// -------------------------------------------------------------------------------------
	function article_search($atts, $thing = NULL) {
		
		global $q;
		
		if ($q) {
			
			$atts['search'] = 1;
			
			if (!isset($atts['status'])) $atts['status'] = '*';
			if (!isset($atts['pageby'])) $atts['pageby'] = 10;
			
			return article($atts,$thing);
		}
		
		return '';
	}
	
// -------------------------------------------------------------------------------------

// =====================================================================================
	function filterFrontPage() {
	
        static $filterFrontPage;

        if (isset($filterFrontPage)) return $filterFrontPage;

		$rs = safe_column("name","txp_section", "on_frontpage != '1'");
		if ($rs) {
			foreach($rs as $name) $filters[] = "and Section != '$name'";	
			$filterFrontPage = ' '.join(' ',$filters);
            return $filterFrontPage;
		}
        $filterFrontPage = false;
		return $filterFrontPage;
	}

// -------------------------------------------------------------------------------------
	function doArticle($atts, $id, $thing = NULL)
	{
		global $PFX, $pretext, $prefs, $thisarticle, $article_stack;
		
		extract($prefs);
		extract($pretext);
		
		extract(gpsa(array('parentid', 'preview')));

		extract(lAtts(array(
			'allowoverride' => '1',
			'form'          => 'default',
			'class'  		=> '',
			'status'        => '',
			'comments' 		=> '1',		// show comments by default
			'debug'  		=> 0,
			'limit'			=> 1,
			'section'		=> '',
			'level'			=> '',
			'sort'			=> '',
			'path'			=> '',
			'category'		=> ''
		),$atts));
		
		$table = "textpattern";
		
		unset($atts['path']);		
		
		// if a form is specified, $thing is for doArticles() - hence ignore $thing here.
		// if (!empty($atts['form'])) $thing = '';
		
		if ($status)
		{
			$status = in_array(strtolower($status), array('sticky', '5')) ? 5 : 4;
		}
		
		if (empty($thisarticle) or $thisarticle['thisid'] != $id)
		{
			$thisarticle = NULL; 
			$where = array();
			$out = array();
			
			$columns = array('*',
				'unix_timestamp(t.Posted) AS uPosted',
				'unix_timestamp(t.Expires) AS uExpires',
				'unix_timestamp(t.LastMod) AS uLastMod'
			);
		
			$columns['custom_fields'] = "(
				SELECT GROUP_CONCAT('&','{',tcv.field_name,'}:{',f.type,'}:{',f.input,'}:{',CONVERT(f.label USING utf8),'}:{',tcv.text_val,'}','&')
				FROM ".$PFX."txp_content_value AS tcv JOIN ".$PFX."txp_custom AS f ON f.id = tcv.field_id
				WHERE tcv.article_id IN (t.ID,t.Alias) AND tcv.tbl = '$table' AND tcv.status = 1 ORDER BY tcv.id ASC) AS custom_fields";
			
			$where['id'] = 'ID = '.intval($id);
			
			if (!$pretext) {
				if (gps('txpreview')) $where['status'] = " AND Status IN (4,5)";
			} else {
				$where['status'] = ($status) ? 'Status = '.intval($status) : 'Status in (4,5)';
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - -

			$sql['SELECT'] = $columns;
			$sql['FROM']   = array("textpattern AS t");
			$sql['WHERE']  = $where;
			
			sql_dump($sql);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$rs = safe_row(implode(', ',$columns),
					"textpattern AS t",doAnd($where)." limit 1",$debug);
			
			if ($rs) {
				
				extract($rs);
				$rs['atts'] = $atts;
				populateArticleData($rs);
				$thisarticle['path']  = $path;
				$thisarticle['table'] = 'textpattern';
				
				if (!$pretext) {
				
					$uExpires = $rs['uExpires'];
					if ($uExpires and time() > $uExpires and !$publish_expired_articles) {
						$out['status'] = '410';
					}
					
					if ($np = getNextPrev($id, $Posted, gps('s','default')))
						$out = array_merge($out, $np);
				}
			}
		}
		
		if (!empty($thisarticle))
		{	
			extract($thisarticle); 
			
			$thisarticle['is_first'] = 1;
			$thisarticle['is_last']  = 1;
			$thisarticle['body_tag_encounter'] = false;
			
			$article_stack->push($thisarticle,'');
			
			if ($pretext) {
				
				if ($allowoverride and $override_form)
				{
					$article = parse_form($override_form);
				}
				else
				{	
					$article = ($thing) ? parse($thing) : parse_form($form);
				}
	
				if ($comments and $use_comments and $comments_auto_append)
				{
					$article .= parse_form('comments_display');
				}
	
				$article_stack->pop();
				
				return trim($article);
			}
			
			return $out;
		}
	}	
	
// -------------------------------------------------------------------------------------
// change: getpg

	function parseArticles($atts, $iscustom = 0, $thing = NULL) {
	
		global $pretext, $is_article_list, $content_type_stack;
		
		static $run = 1;
		
		// inspect("parseArticles()",'h2','parseArticles($run)');
		
		$old_ial = $is_article_list;
		
	 // $is_article_list = (($pretext['unique'] && !$iscustom)) ? false : true;
	 // $is_article_list = ($pretext['id'] && !isset($atts['getpg'])) ? false : true;
		$is_article_list = true;
		
		// inspect("is_article_list: ".$is_article_list,'line','parseArticles($run)');
		
		$list = array();
		
		foreach($atts as $name => $value) {
			
			$list[] = "<li>$name: $value</li>";
		} 
		
		if ($list) {
			
			// inspect('<ul>'.implode(n,$list).'</ul>','line','parseArticles($run)');
		}
		
		$run++;
		
		$content_type_stack->push('article');
		
		$r = ($is_article_list) 
			? doArticles($atts,$iscustom,$thing) 
			: doArticle($atts,$pretext['id'],$thing);
		
		$content_type_stack->pop();
		
		$is_article_list = $old_ial;

		return $r;
	}

// -------------------------------------------------------------------------------------
	function getNeighbour($Posted, $s, $class) {
	
		global $PFX;
		
		$q = array(
			"select ID, Title, url_title, unix_timestamp(Posted) as uposted
			from ".$PFX."textpattern where Posted $class '$Posted'",
			($s!='' && $s!='default') ? "and Section = '$s'" : filterFrontPage(),
			'and Status=4 and Posted < now() order by Posted',
			($class=='<') ? 'desc' : 'asc',
			'limit 1'
		);

		$out = getRow(join(' ',$q));
		return (is_array($out)) ? $out : '';
	}

// -------------------------------------------------------------------------------------
	function getNextPrev($id, $Posted, $s) {
	
		static $next, $cache;

		// If next/prev tags are placed before an article tag on a list page, we
		// have to guess what the current article is
		if (!$id) {
			$current = safe_row('ID, Posted', 'textpattern', 
				(($s!='' && $s!='default') ? "Section = '$s'" : filterFrontPage()).
				'and Status=4 and Posted < now() order by Posted desc limit 1');
			if ($current) {
				$id = $current['ID'];
				$Posted = $current['Posted'];
			}
		}

		if (@isset($cache[$next[$id]]))
			$thenext = $cache[$next[$id]];
		else
			$thenext            = getNeighbour($Posted,$s,'>');

		$out['next_id']     = ($thenext) ? $thenext['ID'] : '';
		$out['next_title']  = ($thenext) ? $thenext['Title'] : '';
		$out['next_utitle'] = ($thenext) ? $thenext['url_title'] : '';
		$out['next_posted'] = ($thenext) ? $thenext['uposted'] : '';

		$theprev            = getNeighbour($Posted,$s,'<');
		$out['prev_id']     = ($theprev) ? $theprev['ID'] : '';
		$out['prev_title']  = ($theprev) ? $theprev['Title'] : '';
		$out['prev_utitle'] = ($theprev) ? $theprev['url_title'] : '';
		$out['prev_posted'] = ($theprev) ? $theprev['uposted'] : '';

		if ($theprev) {
			$cache[$theprev['ID']] = $theprev;
			$next[$theprev['ID']] = $id;
		}

		return $out;
	}

// -------------------------------------------------------------------------------------
/*	function since($stamp) {

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
*/
// -------------------------------------------------------------------------------------
	function lastMod() {
	
		$last = safe_field("unix_timestamp(val)", "txp_prefs", "`name`='lastmod' and prefs_id=1");
		return gmdate("D, d M Y H:i:s \G\M\T",$last);	
	}
	
// -------------------------------------------------------------------------------------
// protection from those who'd bomb the site by GET

	function bombShelter() {
		global $prefs;
		$in = serverset('REQUEST_URI');
		if (!empty($prefs['max_url_len']) and strlen($in) > $prefs['max_url_len']) exit('Nice try.');
	}

// -------------------------------------------------------------------------------------
	function evalString($html) {
	
		global $prefs;
		if (strpos($html, chr(60).'?php') !== false) {
			trigger_error(gTxt('raw_php_deprecated'), E_USER_WARNING);
			if (!empty($prefs['allow_raw_php_scripting']))
				$html = eval(' ?'.chr(62).$html.chr(60).'?php ');
			else
				trigger_error(gTxt('raw_php_disabled'), E_USER_WARNING);
		}
		return $html;
	}
	
// -------------------------------------------------------------------------------------
/*	function getCustomFields() {

		global $prefs;
		$max = get_pref('max_custom_fields', 10);
		$out = array();
		for ($i = 1; $i <= $max; $i++) {
			if (!empty($prefs['custom_'.$i.'_set'])) {
				$out[$i] = strtolower($prefs['custom_'.$i.'_set']);
			}
		}
		return $out;
	}
*/	
// -------------------------------------------------------------------------------------
/*	function buildCustomSql($custom,$pairs) {

		if ($pairs) {
			$pairs = doSlash($pairs);
			foreach($pairs as $k => $v) {
				if(in_array($k,$custom)) {
					$no = array_keys($custom,$k);
					# nb - use 'like' here to allow substring matches
					$out[] = "and custom_".$no[0]." like '$v'";
				}
			}
		}
		return (!empty($out)) ? ' '.join(' ',$out).' ' : false; 
	}
*/
// -------------------------------------------------------------------------------------
// new status note

	function getStatusNum($name) {
	
		$labels = array('draft' => 1, 'hidden' => 2, 'pending' => 3, 'live' => 4, 'sticky' => 5, 'note' => 6);
		$status = strtolower($name);
		$num = empty($labels[$status]) ? 4 : $labels[$status];
		return $num;
	}
	
// -------------------------------------------------------------------------------------
	function ckEx($table,$val,$debug='') {
	
		return safe_field("name",'txp_'.$table,"`name` like '".doSlash($val)."' limit 1",$debug);
	}

// -------------------------------------------------------------------------------------
	function ckExID($val,$debug='') {
	
		return safe_row("ID,Section",'textpattern','ID = '.intval($val).' and Status >= 4 limit 1',$debug);
	}

// -------------------------------------------------------------------------------------
	function lookupByTitle($val,$debug='') {
	
		return safe_row("ID,Section",'textpattern',"url_title like '".doSlash($val)."' and Status >= 4 limit 1",$debug);
	}
	
// -------------------------------------------------------------------------------------
	function lookupByTitleSection($val,$section,$debug='') {
	
		return safe_row("ID,Section",'textpattern',"url_title like '".doSlash($val)."' AND Section='".doSlash($section)."' and Status >= 4 limit 1",$debug);
	}

// -------------------------------------------------------------------------------------
	function lookupByIDSection($id, $section, $debug = '') {
	
		return safe_row('ID, Section', 'textpattern',
			'ID = '.intval($id)." and Section = '".doSlash($section)."' and Status >= 4 limit 1", $debug);
	}

// -------------------------------------------------------------------------------------
	function lookupByID($id,$debug='') {
	
		return safe_row("ID,Section",'textpattern','ID = '.intval($id).' and Status >= 4 limit 1',$debug);
	}

// -------------------------------------------------------------------------------------
	function lookupByDateTitle($when,$title,$debug='') {

		return safe_row("ID,Section","textpattern",
		"posted like '".doSlash($when)."%' and url_title like '".doSlash($title)."' and Status >= 4 limit 1");
	}

// -------------------------------------------------------------------------------------
	function makeOut() {
	
		foreach(func_get_args() as $a) {
			$array[$a] = strval(gps($a));
		}
		return $array;
	}

// -------------------------------------------------------------------------------------
	function parse_req($req,$qs) {
		
		global $prefs;
		
		$out  = array(
			'path'  => array(),
			'id'    => 0,
			'pg'    => 0,
			'level' => 0
		);
		
		$req  = urldecode($req);
		
		if ($qs) {
			
			$req = substr($req,0,strpos($req,'?'));
			$qs  = explode('&',$qs);
			
			foreach($qs as $key => $pair) {
				
				unset($qs[$key]);
				
				$pair  = explode('=',$pair);
				$name  = make_name(array_shift($pair));
				$value = make_name(array_shift($pair));
				
				$qs[$name] = $value;
				
				if ($name == 'pg') $out['pg'] = $value;
			}
		}
		
		$req = preg_replace('/\.html?.*$/','',$req);	// remove .html
		$req = preg_replace('/^\//','',$req);		// remove leading slash
		$req = preg_replace('/^\/$/','',$req);		// remove if slash only
		
		$req = explode('/',strtolower($req));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$last = array_pop($req);
		
		if ($last != '' and $last != 'index') {
			array_push($req,$last);
			$last = '';
		} else {
			$last = 'index';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		while ($req) {
			
			$value = array_shift($req);
			
			if (substr($value,0,1) == '~') continue;
			
			if (count($req)) {
				
				if ($value === (string)(int) $value) {
					
					if (in_list($prefs['permlink_mode'],'id_title,section_id_title')) {
						
						$out['id'] = $value; continue;
					}
				
				} elseif (preg_match('/^pg(\d+)$/',$value,$matches)) {
					
					$out['pg'] = $matches[1]; continue;
				}
			}
			
			$out['path'][] = preg_replace('/[^a-z0-9_-]+/','',$value);	
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$out['level'] = count($out['path']);
		
		if ($last) array_push($out['path'],$last);
		
		$out['path']  = implode('/',$out['path']);
		
		return $out;
	}

// -------------------------------------------------------------------------------------

	function get_context($path, $parentid, $level=1) {
		
		static $context = array(
			'id'    => SITE_ID,
			'path'  => array(),
			'level' => 1,
			'index' => false,
			'ids'	=> array()
		);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$path) {
		
			return $context;
		}
		
		if (!is_array($path)) {
			
			$path = explode('/',$path);
		}
		
		$context['path'][] = $name = array_shift($path);
		
		if ($name == 'index' and !$path) {
			
			$context['index'] = true;
			$context['end']   = true;
			
			return $context;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$where = array();
		
		$where['name']     = "Name = '$name' ";
		$where['level']    = "Level = $level";
		$where['status']   = "Status IN (".LIVE.",".STICKY.")";
		$where['trash']    = "Trash = 0";
		$where['parentid'] = "ParentID = $parentid";
		
		if (PREVIEW) {
			$where['status'] = "Status IN (3,4,5,7)";
		}
		
		// check second to last position in path for an existing ID 
		if (count($path) >= 2) {
		
			if (preg_match('/^\d+$/',$path[count($path)-2])) {
				
				$id = $path[count($path)-2];
				
				$id_level = safe_field('Level',"textpattern","ID = $id AND Trash = 0 AND Status IN (4,5)");
				
				if ($id_level) {
					$context['id'] = $id;
					$context['level'] = $id_level;
					array_pop($path);
					array_pop($path);
					$context['path'] = array_merge($context['path'],$path);
					return $context;
				}
			}
		}
				
		$rows = safe_column("ID","textpattern",doAnd($where),0,0);
		
		if ($rows) {
			
			$context['id'] = $ID = array_shift($rows);
			$context['level'] += 1;
			$context['ids'][$level-1] = $ID;
			
			if ($path) {
				
				$context = get_context($path,$ID,$level+1);
			}
		
		} else {
			
			// if no match was found at this level then the rest of the 
			// path may be in a hidden folder
			
			$where['name']     = "Name != 'TRASH'";
			$where['status']   = "Status IN (".HIDDEN.")";
			$where['children'] = "Children != 0";
			
			$rows = safe_rows("ID,Name","textpattern",doAnd($where),0,0);
			
			if ($rows) {
				
				// replace the name that was not found and check to see 
				// if it may be a child of the found hidden article
				
				array_unshift($path,array_pop($context['path']));
				
				while ($rows and $context['id'] == SITE_ID) {
					
					$context['path']  = array();
					$context['level'] = 2;
					
					extract(array_shift($rows)); 
					
					$context = get_context($path,$ID,$level+1);
				}
				
				// BUG: if no matching children context path is truncated 
				
			} else {
				
				if ($path) {
				
					$last = array_pop($path);
					array_push($path,$last);
					$context['index'] = ($last == 'index');
					$context['path']  = array_merge($context['path'],$path);
				}
			}
		} 
		
		return $context;
	}

// -------------------------------------------------------------------------------------
	function get_page($path,$id,$level,$debug=0) {
		
		// inspect("get_page('$path',$id,$level)",'h2');
		
		if ($debug) pre($path);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$site   = (SITE_NAME != ROOTNODE) ? SITE_NAME : ''; 
		$length = count(explode('/',$path));
		$index  = (substr($path,-6) == '/index') ? 1 : 0;
		
		$use_pattern = column_exists('txp_page','Pattern');
		
		$columns = ($use_pattern) ? 'ID,Name,Level,Pattern' : 'ID,Level,Name';
		$status  = (PREVIEW) ? '3,4,5' : '4,5';
		
		$pages  = safe_rows(
			$columns,
			'txp_page',
			"ParentID != 0 
			 AND Trash = 0 
			 AND Name != 'TRASH'
			 AND Type != 'trash'
			 AND Type in ('txp','xsl') 
			 AND Status IN ($status)
			 ORDER BY Level ASC, Position ASC");
		
		$inspect = array();
		
		foreach ($pages as $key => $page) {
			
			$name = new Path($page['ID'],'','txp_page');
			$name = implode('/',$name->getArr('Name'));
			$pattern = ($use_pattern) ? $page['Pattern'] : '';
			
			if (!isset($pages[$name])) {
			
				if ($debug) { 
					$inspect[] = str_pad($page['ID'],2).' '.str_pad($name,30).' '.$pattern;
				}	
					
				$pages[$name]['id']      = $page['ID'];
				$pages[$name]['level']   = $page['Level'];
				$pages[$name]['pattern'] = $pattern;
			}
			
			unset($pages[$key]);
		}
		
		if ($debug) {
			pre(line());
			pre(implode(n,$inspect));
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (gps('page')) {
			
			$page = gps('page');
			
			if ($debug) pre(line().$page);
			
			if ($site) {
				
				if ($debug) pre("TRY $site/$page");
				
				if (isset($pages[$site.'/'.$page])) {
					
					if ($debug) pre('MATCHED');
					
					return $pages[$site.'/'.$page]['id'];
				}
			}
			
			if ($debug) pre("TRY $page");
			
			if (isset($pages[$page])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages[$page]['id'];
			}
			
			pre('NO MATCH');
			
			return 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// if path ends with 'index' at level 3 or greater
		
		if ($length >= 3 and $index) {
			
			// try .../abc/index with pattern
			
			if ($use_pattern) {
				
				if ($debug) pre(line());
				
				if ($page = get_page_by_pattern($pages,$path,$debug)) {
					
					return $page;
				}
			}
			
			if ($debug) pre("TRY AS $path");
				
			if (isset($pages[$path])) {
			
				if ($debug) pre('MATCHED');
				
				return $pages[$path]['id'];
			}
			
			// NEW: try .../abc/index as .../abc 
			
			if ($debug) pre(line());
			
			$p = explode('/',$path);
			array_pop($p);
			$p = implode('/',$p);
			
			if ($debug) pre("TRY AS $p");
			
			if (isset($pages[$p])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages[$p]['id'];
			}
			
			// try .../abc/index as .../default 
			
			if ($debug) pre(line());
			
			$p = explode('/',$path);
			array_pop($p);
			array_pop($p);
			array_push($p,'default');
			$p = implode('/',$p);
			
			if ($debug) pre("TRY AS $p");
			
			if (isset($pages[$p])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages[$p]['id'];
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// try the user specified regexp pattern 
		
		if ($use_pattern) {
			
			if ($debug) pre(line());
			
			if ($page = get_page_by_pattern($pages,$path,$debug)) {
					
				return $page;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($length > LEVELS + 1) {
			
			if ($debug) { pre(line()); pre('NO MATCH'); }
			
			return 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// if path is 'index';
		
		if ($path == 'index') {
			
			// try site/index
			
			if ($debug) pre(line());
			
			if ($site) {
				
				if ($debug) pre("TRY $site/index");
				
				if (isset($pages[$site.'/index'])) {
					
					if ($debug) pre('MATCHED');
									
					return $pages[$site.'/index']['id'];
				}
			}
			
			// try index
			
			if ($debug) pre("TRY index");
			
			if (isset($pages['index'])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages['index']['id'];
			}
			
			if ($debug) {
				pre("TRY AS default");
				pre('MATCHED');
			}
			
			return $pages['default']['id'];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// if path ends with 'index';
		
		if ($index) {
			
			if ($debug) pre(line());
			
			if ($site) {
			
				// try site/.../abc/index
				
				if ($debug) pre("TRY $site/$path");
				
				if (isset($pages[$site.'/'.$path])) {
					
					if ($debug) pre('MATCHED');
					
					return $pages[$site.'/'.$path]['id'];
				}
				
				// try site/.../abc/index as site/.../abc
				
				$p = substr($path,0,-6);
				
				if ($debug) pre("TRY AS $site/$p");
				
				if (isset($pages[$site.'/'.$p])) {
					
					if ($debug) pre('MATCHED');
					
					return $pages[$site.'/'.$p]['id'];
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			// try .../abc/index
			
			if ($debug) pre("TRY $path");
			
			if (isset($pages[$path])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages[$path]['id'];
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			// try .../abc/index as .../abc
			
			$p = substr($path,0,-6);
			
			if ($debug) pre("TRY AS $p");
			
			if (isset($pages[$p])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages[$p]['id'];
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			// try .../abc/index as .../default
			
			if ($level > 1) {
			
				$p = explode('/',$path);
				array_pop($p);
				array_pop($p);
				array_push($p,'default');
				$p = implode('/',$p);
				
				if ($debug) pre("TRY AS $p");
				
				if (isset($pages[$p])) {
					
					if ($debug) pre('MATCHED');
					
					return $pages[$p]['id'];
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// use override page if set
		
		if ($id != SITE_ID and $page = fetch('override_page',"textpattern","ID",$id)) {
			
			if ($site and isset($pages[$site.'/'.$page])) {
			
				return $pages[$site.'/'.$page]['id'];
			}
		
			if (isset($pages[$page])) {
				
				return $pages[$page]['id'];
			} 
			
			return 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// try site/path
		
		if ($site and isset($pages[$site.'/'.$path])) {
			
			return $pages[$site.'/'.$path]['id'];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// try path
		
		if ($debug) pre("TRY AS $path");
		
		if (isset($pages[$path])) {
			
			if ($debug) pre('MATCHED');
			
			return $pages[$path]['id'];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// try .../abc/xyx as .../abc/default
		
		if ($level > 1) {
			
			$p = explode('/',$path);
			array_pop($p);
			array_push($p,'default');
			$p = implode('/',$p);
			
			if ($debug) pre("TRY AS $p");
			
			if (isset($pages[$p])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages[$p]['id'];
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// the result should be page not found at this point
		
		if ($length - $level >= 2) {
			
			// if the length of the requested path exceeds the 
			// level of the context article by more than 1
		
			return '';
		}
		
		if (!$id) {
			
			// if no article exists for path 
			
			return '';
		}
		
		if ($id == SITE_ID) {
		
			return '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// NOTE: what exactly is $site for?
		
		// try site/default
		
		if ($debug) pre("TRY AS $site/default");
		
		if ($site and isset($pages[$site.'/default'])) {
			
			if ($debug) pre('MATCHED');
			
			return $pages[$site.'/default']['id'];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// try ../default
		
		$p = explode('/',$path);
		
		while (count($p) > 1) {
			
			array_pop($p);
			array_push($p,'default');
			
			$p = implode('/',$p);
			
			if ($debug) pre("TRY AS $p");
			
			if (isset($pages[$p])) {
			
				if ($debug) pre('MATCHED');
			
				return $pages[$p]['id'];
			}
			
			$p = explode('/',$p);
			
			array_pop($p);	// pops 'default'
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// try in possible hidden folder
		
		$hidden_path = new Path($id);
		$hidden_path = $hidden_path->getList('Name','/','*');
		
		if ($debug) pre("TRY AS HIDDEN $hidden_path");
		
		if (isset($pages[$hidden_path])) {
			
			if ($debug) pre('MATCHED');
			
			return $pages[$hidden_path]['id'];
		}
		
		$hidden_path = explode('/',$hidden_path);
		array_pop($hidden_path);
		
		if ($index) { 
		
			array_push($hidden_path,'index');
			$hidden_path = implode('/',$hidden_path);
			
			if ($debug) pre("TRY AS HIDDEN $hidden_path");
			
			if (isset($pages[$hidden_path])) {
				
				if ($debug) pre('MATCHED');
				
				return $pages[$hidden_path]['id'];
			}
			
			array_pop($hidden_path);
		}
		
		array_push($hidden_path,'default');
		$hidden_path = implode('/',$hidden_path);
		
		if ($debug) pre("TRY AS HIDDEN $hidden_path");
		
		if (isset($pages[$hidden_path])) {
			
			if ($debug) pre('MATCHED');
			
			return $pages[$hidden_path]['id'];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $pages['default']['id'];		
	}

// -------------------------------------------------------------------------------------
	function get_page_by_pattern($pages,&$path,$debug=0) {
		
		$pages    = array_reverse($pages);
		$patterns = array();
		$pos      = 1;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// get patterns 
		
		foreach($pages as $key => $page) {
				
			if ($page['pattern']) {
			
				$pattern = $page['pattern'];
				$id		 = $page['id'];
				$level	 = $page['level'];
				
				$pattern = explode('/',$pattern);
				$order   = count($pattern);
				
				foreach ($pattern as $place => $item) {
					if (strlen($item) and !in_list($item,'*,#')) {
						$order = $order + 1;
					}
				}
				
				$order  = str_pad($order,2,'0',STR_PAD_LEFT).'.';
				$order .= str_pad($level,2,'0',STR_PAD_LEFT).'.';
				$order .= str_pad((100-$pos),2,'0',STR_PAD_LEFT).'.';
				$order .= str_pad($id,3,'0',STR_PAD_LEFT);
				
				$patterns[$order] = implode('/',$pattern);
				
				$pos += 1;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// use patterns 
		
		if (count($patterns)) {
		
			ksort($patterns);
			
			$patterns = array_reverse($patterns);
			
			// print each pattern for debugging 
			
			if ($debug) {
				
				$out = '';
				
				foreach($patterns as $order => $pattern) {
				
					$out .= $order.' '.$pattern.n;
				}
				
				pre($out.line());
			}
			
			// match each pattern 
			
			foreach($patterns as $key => $pattern) {
			
				$id = intval(end(explode('.',$key)));
				
				if ($debug) pre("TRY ".$pattern);
				
				$pattern = preg_replace('/\*/','[a-z0-9\-]+',$pattern);
				$pattern = preg_replace('/\#/','\d+',$pattern);
				$pattern = preg_replace('/^\//','^',$pattern);
				$pattern = "/".str_replace('/','\/',$pattern)."/";
				
				if (preg_match($pattern,$path)) {
					
					if ($debug) pre("MATCHED $path");
					
					return $id;
				}
				
			}	
		
			if ($debug) pre("NO MATCH");
		}
		
		return '';
	}	
				
// -------------------------------------------------------------------------------------
	function chopUrl($req) {
	
		$req = strtolower($req);
		//strip off query_string, if present
		$qs = strpos($req,'?');
		if ($qs) $req = substr($req, 0, $qs);
		$req = preg_replace('/index\.php$/', '', $req);
		$r = array_map('urldecode', explode('/',$req));
		$o['u0'] = (isset($r[0])) ? $r[0] : '';
		$o['u1'] = (isset($r[1])) ? $r[1] : '';
		$o['u2'] = (isset($r[2])) ? $r[2] : '';
		$o['u3'] = (isset($r[3])) ? $r[3] : '';
		$o['u4'] = (isset($r[4])) ? $r[4] : '';

		return $o;
	}

//--------------------------------------------------------------------------------------
	function get_section_class($section) {
	
		return safe_field("is_gallery","txp_section","name = '$section'") ? 'gallery' : 'regular'; 
	}

// -------------------------------------------------------------------------------------
// new

	function getAllNeighbours($id,$ids) {
	
		$out = array(
			'before' => array(),
			'after'  => array()
		);
		
		if (!$id) return array(0);
		if (!$ids) return array(0);
		
		$count = 0;
		
		foreach($ids as $key) { 
			$ids[$key] = $count++;
		}
		
		if (isset($ids[$id])) {
		
			$key = $ids[$id];
			
			$ids = array_flip($ids);
			
			$out['before'] = array_slice($ids,0,$key);
			$out['after']  = array_slice($ids,$key+1); 	
		}
		
		return $out;
	}

// -------------------------------------------------------------------------------------
// old

	function getNeighbours($after,$before,$id,$ids,$loop,$limit,$offset) {
	
		$out = array();
		
		if (!$id) return array(0);
		if (!$ids) return array(0);
		
		$count = 1;
		foreach ($ids as $key) {
			$ids[$key] = $count++;
		}
		
		$key = (isset($ids[$id])) ? $ids[$id] : 0;
		
		$ids = array_flip($ids);
		
		if ($after) {
		
			$begin = $offset + 1;
			$end = $after;
			
			for ($i = $begin; $i <= $end; $i++) {
				if(isset($ids[$key+$i]))
					$out[] = $ids[$key+$i];
			}
		}
		
		if ($before) {
			
			if (!$key) {
				$key = count($ids) + 1;
			}
			
			$begin = $before;
			$end = $before - ($limit - 1);
			
			for ($i = $begin; $i >= $end; $i--) {
				if(isset($ids[$key-$i]))
					$out[] = $ids[$key-$i];
			}
		}
		
		if (!count($out)) {
			
			if ($loop and $after)  return array(array_shift($ids));
			if ($loop and $before) return array(array_pop($ids));
			
			return array(0);
		}
		
		return $out;
	}

// -------------------------------------------------------------------------------------
	function getAfter($quantity,$id,$neighbours,$offset=0,$loop=0) {
		
		if (!$quatity) return array();
		
		$ids = $neighbours['after'];
		
		if (isset($ids[$offset])) { 
			
			return array_slice($ids,$offset,$quantity);
		}
		
		return array();
	}
	
// -------------------------------------------------------------------------------------
	function getBefore($before,$id,$neighbours,$limit=0,$loop=0) {
		
		if (!$before) return array();
		
		$ids = $neighbours['before'];
		$ids[] = $id;
		$key = count($ids) - 1;
		$ids = array_merge($ids,$neighbours['after']);
		
		$start = (($key - $before) < 0) ? 0 : $key - $before;
		$limit = ($limit) ? $limit : $before;
		pre($neighbours);
		pre($ids);
		pre('key:'.$key);
		pre('start:'.$start);
		pre('limit:'.$limit);
		pre(array_slice($ids,$start,$limit));
		return array_slice($ids,$start,$limit);
	}
	
// -------------------------------------------------------------------------------------
	function ignored($name,$atts) {
	
		if ($atts['ignore']) {
		
			$ignored = do_list($atts['ignore']);
			$ignored = array_flip($ignored);
		
			if (isset($ignored[$name])) return true;
		}
		
		return false;
	}

// -------------------------------------------------------------------------------------
	function removeTagName($domDocument,$tagname) {
	
		$domNodeList = $domDocument->getElementsByTagname($tagname); 
		$domElemsToRemove = array(); 
		foreach ( $domNodeList as $domElement ) { 
			$domElemsToRemove[] = $domElement; 
		} 
		foreach( $domElemsToRemove as $domElement ){ 
			$domElement->parentNode->removeChild($domElement); 
		} 
		
		return $domDocument;
	}

// -------------------------------------------------------------------------------------
	function get_context_path() {
	
		global $pretext, $article_stack;
		
		if ($article = $article_stack->top()) 
		
			return $article['path'];
		
		elseif ($pretext['path'] and $pretext['unique'])
			
			return $pretext['path'];
		
		return '';
	}

// -------------------------------------------------------------------------------------
	function sortdir($sort) {
	
		$sort = sortvals($sort);
		
		return $sort[0]['dir'];
	}
		
// -------------------------------------------------------------------------------------
	function find_in_array(&$arr, &$array, $sep='/', &$stack=array()) {

	static $found = false;
	$i = 0;
	
	while (!$found and $i < count($arr)) {
	
		$stack[] = $arr[$i][count($stack)];
		
		if (count($stack) == count($arr[0])) {
			
			$test = implode($sep,$stack);
			
			if (isset($array[$test])) {
				
				$found = $array[$test];
			}
				
		} else {
			
			find_in_array($arr, $array, $sep, $stack);
		}
		
		array_pop($stack);
		
		$i++;
	}
	
	return $found;
}

//--------------------------------------------------------------------------------------
	function get_language($qs)
{
	global $prefs;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (isset($prefs['languages']) and $prefs['languages']) {
		
		$uri = trim($_SERVER["REQUEST_URI"],'/');
		$path = (str_begins_with($uri,'~'))
			? '/'.reset(explode('/',$uri)).'/'
			: '/';
			
		if ($lg = get('lg','',$prefs['languages'])) { 
			
			setcookie('txp_lang',$lg,0,$path);
		
		} else {
	
			$languages = str_replace(',','|',$prefs['languages']);
		
			if (preg_match('/^('.$languages.')\b/',$qs,$matches)) {
			
				$lg = $matches[1];
			
				setcookie('txp_lang',$lg,0,$path);
			
			} else {
				
				$lg = cs('txp_lang');
			}
		}
		
		if ($lg) return $lg;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 
  	$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	
	$language_pages = array(
		"en" => "en",
		"fr" => "fr",
		"es" => "es",
		"de" => "de"
	);
	
	$language_default = "en";
	$language_nofound = "en";
	
	if($accept_language == "")
	{
		// no preference set
		return $language_default;
	}
	
	// form an array of preferred languages
	$accept_language = str_replace(" ", "", $accept_language);
	$languages = explode(",", $accept_language);
	
	$total = sizeof($languages);
	
	for($i = 0; $i < $total; $i++)
	{
		$lang = explode(";",$languages[$i]);
		
		$code = $lang[0];
		
		if (count($lang) == 2) {
			$rank = explode("=",$lang[1]);
			$rank = array_pop($rank);
			$languages[$code] = $rank; 
		} else {
			$languages[$code] = "1.0"; 
		}
	
		unset($languages[$i]);
	}
	
	foreach($languages as $lang => $rank)
	{
		$lang = explode("-",$lang);
	
		if (count($lang) == 2) {
		
			$lang = array_shift($lang);
			$languages[$lang] = $rank - 0.01;
		}
	}
	
	// check for a recognised language
	
	foreach($languages as $lang => $rank)
	{
		if (isset($language_pages[$lang])) {
			
			// found a preferred language
			
			return $language_pages[$lang];
		}
	}
	
	return $language_nofound;
}

//--------------------------------------------------------------------------------------

function add_toolbar_iframe(&$html) 
{
	global $prefs,$production_status;
	
	$base = (!$prefs['base']) ? '/admin/' : $prefs['base'];
	$nocache = ($production_status != 'live') ? '?'.rand(100000,999999) : '';
	
	$insert = '<iframe id="toolbar" src="/admin/plugins/toolbar/toolbar.html"></iframe>
	<script type="text/javascript" src="/admin/plugins/toolbar/top.js"></script>
	<link rel="stylesheet" type="text/css" href="/admin/plugins/toolbar/iframe.css"/>';

	$html = preg_replace('/(<\/body>)/',n.t.$insert.n.'</body>',$html);
}

?>


<?php
	
	include txpath.'/lib/txplib_theme.php';
	include txpath.'/lib/txplib_smarty.php';
	include_once txpath.'/lib/classSimpleHTMLDOM.php';
	include txpath.'/include/lib/txp_lib_image.php';
	include txpath.'/include/lib/txp_lib_file.php';
	include txpath.'/include/lib/txp_lib_misc.php';
	include txpath.'/include/lib/txp_lib_api.php';
	
	getmicrotime('adminruntime');
	
	$log_buffer = array();
	
	// -------------------------------------------------------------------------
	
	echo check_session_save_path(); // error message
	
	if (gps('clear_cookie')) { 
	
		setcookie('txp_login', '', time()-3600);
	}
	
	// -------------------------------------------------------------------------
	
	if ($connected and !safe_query("describe `".$PFX."textpattern`")) {
		
		txp_die('DB-Connect was succesful, but the textpattern-table was not found.',
				'503 Service Unavailable');
	}
	
	// -------------------------------------------------------------------------
	
	$prefs = get_prefs();
	extract($prefs); // to be removed
	
	extract(get_prefs("
		siteurl,
		path_to_site,
		file_base_path,
		file_dir,
		img_dir,
		language,
		locale,
		default_event,
		admin_side_plugins"));
	
	if (empty($siteurl)) {
	 // $siteurl = $_SERVER['HTTP_HOST'] . (($site_dir) ? '/~'.$site_dir : '');
		$siteurl = $site_url;
	}
	
	if (empty($path_to_site)) {
		$path_to_site = updateSitePath(dirname(dirname(__FILE__)));
	}
	
	if (isset($txpcfg['path_to_site']) and $path_to_site != $txpcfg['path_to_site']) {
		$path_to_site = updateSitePath($txpcfg['path_to_site']);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if ($timezone_key) {
		date_default_timezone_set($timezone_key);
	}
		
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// get base admin path
	
	$base = $prefs['base'] = get_base_admin_path();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// temporary fix
	
	if (!isset($file_base_path)) $file_base_path = '';
	if ($file_base_path != $path_to_site.DS.$file_dir) {
		$file_base_path = $path_to_site.DS.$file_dir;
		safe_update("txp_prefs","val = '$file_base_path'","name = 'file_base_path'");
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// new global site settings
	
	$site_base_path = $path_to_site;
	$site_http 		= 'http://'.$site_url;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	define("LANG",$language);
	//i18n: define("LANG","en-gb");
	define('txp_version', $thisversion);
	define('PROTOCOL', get_protocol());
	define("hu",PROTOCOL.$siteurl.'/');
	// v1.0 experimental relative url global
	define("rhu",preg_replace("/https?:\/\/.+(\/.*)\/?$/U","$1",hu));
	define('PREVIEW',false);
	
	if (!empty($locale)) setlocale(LC_ALL, $locale);
	$textarray = load_lang(LANG);
	
	$WIN = array(
		'winid'	  	=> gps('win',1),
		'docid'	  	=> gps('id',0),
		'event'	  	=> '',
		'name'	  	=> '',
		'content' 	=> 'article',
		'table'	  	=> 'textpattern',
		'class'	  	=> '',
		'id'	  	=> 0,
		'mini'	  	=> 0,
		'checked' 	=> array(),
		'prevnext'	=> array(),
		'notes'   	=> array(),
		'filter'   	=> array(),
		'scroll'  	=> 0,
		'view'    	=> 'tr',
		'linenum' 	=> 'off'
	);
	
	$events = array(
		'list','article','image','file','link','custom',
		'category','discuss','page','form',
		'css','log','sites','plugin');

	// init global theme
 // $theme = theme::init();
	$theme = new theme();
	$theme = $theme->init();
	
	doAuth();
	
	// once more for global plus private prefs
	$prefs = get_prefs();
	extract($prefs);
	
	$event    = gps('event',$default_event);
	$step     = gps('step','list');
	$app_mode = gps('app_mode');
	
	$WIN['event'] = $event;
	
	if (gps('update')) {
		
		define('TXP_UPDATE',1);
		define("IMPORT",0);
		
		include txpath.'/update/_update.php';
		
		save_log_buffer();
		
		exit;
	}
	
	if (SETUP) {
		
		exit;
	}
	
	define('TXP_UPDATE',0);
	
	if ($app_mode != 'async') {
		janitor();
		backup_db();
	}
	
	if (!empty($admin_side_plugins) and gps('event') != 'plugin')
		load_plugins(1);
	
	// plugins may have altered privilege settings
	if (!gps('event') && !empty($default_event) && has_privs($default_event))
	{
		 $WIN['event'] = $event = $default_event;
	}

	// init private theme
 // $theme = theme::init();
	$them = new theme();
	$theme = $theme->init();
	
	// list($site,$siteid) = get_site();
	
	$site=''; $siteid = 0;
	
	switch ($event) {
		case 'article'	: $WIN['table']   = 'textpattern';
						  $WIN['content'] = 'article';		break;	
		case 'image'   	: $WIN['table']   = 'txp_image'; 
						  $WIN['content'] = 'image';		break;
		case 'file'    	: $WIN['table']   = 'txp_file';
						  $WIN['content'] = 'file';			break;
		case 'link'		: $WIN['table']   = 'txp_link';
						  $WIN['content'] = 'link';			break;
		case 'custom'	: $WIN['table']   = 'txp_custom';
						  $WIN['content'] = 'custom';		break;
		case 'category' : $WIN['table']   = 'txp_category';
						  $WIN['content'] = 'category'; 	break;
		case 'discuss'	: $WIN['table']   = 'txp_discuss';
						  $WIN['content'] = 'comment'; 		break;
		case 'page'		: $WIN['table']   = 'txp_page';
						  $WIN['content'] = 'page';			break;
		case 'form'		: $WIN['table']   = 'txp_form';
						  $WIN['content'] = 'form';			break;
		case 'css'		: $WIN['table']   = 'txp_css';
						  $WIN['content'] = 'css';			break;
		case 'log'		: $WIN['table']   = 'txp_log';		
						  $WIN['content'] = 'log';			break;
		case 'sites'	: $WIN['table']   = 'txp_site';			
						  $WIN['content'] = 'sites';		break;
		case 'plugin'	: $WIN['table']   = 'txp_plugin';		
						  $WIN['content'] = 'plugin';
	}
	
	if ($event == 'admin') {
	
		if (column_exists('txp_users','ParentID')) {
			
			$events[] = 'admin';
			$WIN['table']   = 'txp_users';
			$WIN['content'] = 'users';
		}
	}
	
	/* if ($site) {
		define("ROOTNODE",$site);
		define("ROOTNODEID",$siteid);
	} else {
		define("ROOTNODE",fetch("Name",$WIN['table'],"ParentID",0));
		define("ROOTNODEID",fetch("ID",$WIN['table'],"ParentID",0));
	} */
		
	define("IMPORT",gps('edit_method') == 'import');
	
	define("ROOTNODE",fetch("Name",$WIN['table'],"ParentID",0));
	define("ROOTNODEID",fetch("ID",$WIN['table'],"ParentID",0));
	define("TRASH_ID",fetch("ID",$WIN['table'],"name","TRASH"));
	
	define("IMPATH",$site_base_path.DS.$img_dir.DS);
	define("IMPATH_FTP",IMPATH.'_ftp'.DS);
	define("FPATH",$file_base_path.DS);
	define("FPATH_FTP",$file_base_path.DS.'_ftp'.DS);
	
	define("FILE_PATH",$file_base_path.DS);
	define("IMG_PATH",$site_base_path.DS.$img_dir.DS);
	define("FILE_FTP_PATH",FILE_PATH.'_ftp'.DS);
	define("IMG_FTP_PATH",IMG_PATH.'_ftp'.DS);
	define("IMP_PATH",FILE_PATH.'_import'.DS);
	define("EXP_PATH",FILE_PATH.'_export'.DS);
	define("EXP_DB_PATH",EXP_PATH.'db'.DS);

	$statuses = array(
		6 => gTxt('note'),
		1 => gTxt('draft'),
		3 => gTxt('pending'),
		4 => gTxt('live'),
		2 => gTxt('hidden'),
		5 => gTxt('sticky')
	);
	
	if (table_exists('txp_sticky')) {
		unset($statuses[5]);
	}
	
	$pretext = array(
		'id'   => ROOTNODEID,
		'path' => '',
		'q'	   => '',
		'c'	   => '',
		'cl'   => '',
		'req'  => '',
		'pg'   => 0,
		's'	   => ''
	);
	
	$article_stack = new ArticleStack();
	
	session_data();
	
	if (!empty($admin_side_plugins) and gps('event') != 'plugin')
		load_plugins(1);

	// plugins may have altered privilege settings
	if (!gps('event') && !empty($default_event) && has_privs($default_event))
	{
		 $event = $default_event;
	}
	
	$html = '';
	
	include txpath.'/lib/txplib_head.php';

	// ugly hack, for the people that don't update their admin_config.php
	// Get rid of this when we completely remove admin_config and move privs to db
	if ($event == 'list') 		
		require_privs('article'); 
	else 
		require_privs($event);
	
	callback_event($event, $step, 1);
	
	if ($event == 'article' and $step == 'list') {
	
		include_once txpath.'/include/lib/txp_lib_ContentEdit.php';
		
	} elseif (in_list($step,'edit,save,add_image,remove_image')) {
	
		include_once txpath.'/include/lib/txp_lib_ContentEdit.php';
		include_once txpath.'/include/lib/txp_lib_ContentSave.php';
	
	} elseif (in_list($step,'post,add_folder')) {
	
		include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
		
	} else {
		
		include_once txpath.'/include/lib/txp_class_ContentList.php';
		
		if ($step == 'multi_edit') {
			
			$method = gps('edit_method');
			
			include_once txpath.'/include/lib/txp_class_MultiEdit.php';
			
		 // if ($method == 'export') 
		 //		include_once txpath.'/include/lib/txp_lib_export.php';
			
		 // if ($method == 'import') 
		 //		include_once txpath.'/include/lib/txp_lib_import.php';
			
			if (in_list($method,'new,new_site,group,paste,alias,add_image,add_folder,duplicate'))
				include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
				
			if (in_list($method,'save,add_image,group'))
				include_once txpath.'/include/lib/txp_lib_ContentSave.php';
		}
	}
	
	/* if ($step == 'multi_edit' and gps('edit_method') == 'export') {
		include_once txpath.'/include/txp_content_file.php';
	} */

	$area  = (isset($areas[$event])) ? $areas[$event] : '';
	
	$steps = array(
		'list',
		'hoist',
		'save',
		'edit',
		'post',
		'filter',
		'multi_edit',
		'change_pageby',
		'toggle_column',
		'move_column',
		'add_image',
		'add_folder',
		'show_image',
		'remove_image',
		'save_note_status',
		'save_note_text'
	);
	
	$inc = ($area)
		? txpath.'/include/txp_'.$area.'_'.$event.'.php'
		: txpath.'/include/txp_'.$event.'.php';
	
	if ($event == 'admin' and in_array('admin',$events)) {
		// $inc = txpath.'/include/txp_admin_admin_new.php';
	}
	
	if (is_readable($inc)) {
		
		include($inc);
	}
	
	if ($event == 'article') {
		
		$EVENT = get_event_session();
		$WIN   = get_window_session($WIN);
		
		$step();
		
	} elseif (in_list($area,'content,presentation,admin')) {
		
		if (in_array($event,$events)) {
		
			if (!in_array($step,$steps)) {
			
				echo "Error: unknown step $step";
			
			} else {
				
				$EVENT = get_event_session();
				$WIN   = get_window_session($WIN);
				
				if ($step == 'hoist')  $step = 'list';
				
				$stepfunc = $step;
				
				if ($event != 'article') {
					
					$stepfunc = $event.'_'.$step;
					
					if (!function_exists($stepfunc)) {
						
						$stepfunc = 'event_'.$step;
						
						if (!function_exists($stepfunc)) {
						
							$stepfunc = $step;
						}
					}
				}
				
				if (function_exists($stepfunc)) { 
				
					$stepfunc();
				
				} else {
				
					echo "Error: unknown function $stepfunc";
				}
			}
		}
	}
	
	callback_event($event, $step, 0);
	
	// clear_cache(); 
	store_session_data(); 
	
	if ($app_mode != 'async') {
		$microdiff = (getmicrotime() - $microstart); 
		$html .= n.n.comment(gTxt('runtime').': '.substr($microdiff,0,6));
	}
	
	end_page($event);
	
	// $html = tidy_html($html);
	
	if ($app_mode != 'async' or gps('refresh_content')) {
		
		echo $html;
		echo inspector();
	}
	
	$values = array(
		array('Title' => 'Test Me 3'),
		array('Title' => 'Test Me 4')
	);
	
	// pre(getmicrotime('adminruntime'));
	
	// echo add_article('/events',$values); 
	
	save_log_buffer();
	
// =============================================================================
	function session_data() 
	{
		global $WIN, $event, $site_url, $txp_user;
		
		$winid = $WIN['winid'];
		
		// unset($_SESSION['clipboard']);
		
		if (!isset($_SESSION['clipboard'])) {
			
			$_SESSION['clipboard'] = array(
				'table' => $WIN['table'],
				'cut'	=> array(),
				'copy'	=> array()
			);
		}
		
		if (isset($_SESSION['window'])) {
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (fetch("updated","txp_users","name",$txp_user)) {
				
				$_SESSION['window'] = array();
				
				safe_update("txp_users","updated = 0","name = '$txp_user'");
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (table_exists("txp_window")) {
				
				if (!isset($_SESSION['window'][$winid])) {
					
					$settings = safe_field("settings","txp_window",
						"user = '$txp_user' AND window = '$winid'");
						
					if ($settings) {
						
						// pre('session_data() txp_window: '.$winid);
						
						$settings = unserialize(base64_decode($settings));
						
						$_SESSION['window'][$winid] = $settings;
					}
				
				} else {
					
					if (isset($_SESSION['window'][$winid])) {
					
						foreach($_SESSION['window'] as $id => $win) {
						
							if ($id != $winid) { 
								unset($_SESSION['window'][$id]);
							}
						}
					}
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			return;
		}
		
		retrieve_session_data();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (fetch("updated","txp_users","name",$txp_user)) {
			
			foreach ($_SESSION as $key => $value) { 
				
				unset($_SESSION[$key]);
			}
				
			safe_update("txp_users","updated = 0","name = '$txp_user'");
			
			if (table_exists("txp_window")) {
			
				safe_delete("txp_window","user = '$txp_user'");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		// if (!isset($_SESSION['user']))	
		//	retrieve_session_data();
			
		// if (isset($_SESSION['user']) && $_SESSION['user'] != $site_url.$txp_user) 
		// 	retrieve_session_data();
		
		$_SESSION['user'] = $site_url.$txp_user;
	}

// -------------------------------------------------------------
	function check_session_save_path() 
	{	
		$sessions = (!strlen(ini_get('open_basedir')))
			? session_save_path()
			: '';
		
		if (!$sessions or !is_writable($sessions.'/')) {
		
			$sessions = txpath.'/sessions/';
			
			if (!is_dir($sessions) and !mkdir($sessions)) {
				
				return '<p class="error">Error: Could not create '.$sessions.' folder</p>';
			}
			
			if (!is_writable($sessions)) {
				
				return '<p class="error">Error: '.$sessions.' folder has no write permission</p>';
			}
			
			session_save_path($sessions);
		}
	}
	
// -------------------------------------------------------------
	function save_session($session) 
	{	
		if ($session['type'] == 'window') {
			
			set_window_session($session);
		}
		
		if ($session['type'] == 'event') {
			
			set_event_session($session);
		}
	}
	
// -------------------------------------------------------------
	function reset_session($session,$item='') 
	{	
		if ($item) {
			
			$session[$item] = $session['reset'][$item];
			
			save_session($session);
		}
	}

// -------------------------------------------------------------
	function set_event_session($session) 
	{	
		$name = $session['name'];
		
		$_SESSION['event'][$name] = $session;
	}
	
// -------------------------------------------------------------
	function set_window_session($session) 
	{
		$id    = $session['winid'];
		$event = $session['event'];
		
		$_SESSION['window'][$id][$event]  = $session;
		$_SESSION['window'][$id]['notes'] = $session['notes'];		
	}

// -------------------------------------------------------------
	function get_event_session($defaults=array()) 
	{
		global $event;
		
		$defaults = array_merge(
			array(
				'type'   => 'event',
				'name'   => $event,
				'action' => '',
				'clip'	 => array(
					'cut'   => array(),
					'copy'  => array()
				)
			),$defaults); 
			
		if (isset($_SESSION['event'][$event])) {
			
			return $_SESSION['event'][$event];
		
		} else {
		
			return $_SESSION['event'][$event] = $defaults;
		}
	}
	
// -------------------------------------------------------------
	function get_window_session($defaults=array()) 
	{
		global $event, $step;
		
		$winid   = gps('win','new');
		$id  	 = gps('id',gps('ID'));
		$opener  = gps('opener',0);
		$sortby  = gps('sort');
		$sortdir = gps('dir');
		$mini    = gps('mini',0);
		$checked = gps('checked');
		$selcol  = gps('selcol');
		$editcol = gps('editcol');
		$scroll	 = gps('scroll');
		$headers = gps('headers');
		$main    = gps('main');
		$view	 = gps('view');
		$search  = gps('search');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$defaults = array_merge(
			array(
				'type'	  	=> 'window',
				'event'	    => $event,
				'content'	=> $event,
				'winid'   	=> 0, 
				'docid'   	=> 0, 
				'opener' 	=> 0,
				'id'	  	=> ROOTNODEID,
				'view' 	    => 'tr',
				'thumb'     => 'z',
				'sortby'    => 'Posted',
				'sortdir'   => 'desc',
				'sorthist'	=> array(),
				'linkdir'   => 'asc',
				'linenum'   => 'off',
				'mini'	  	=> 0,
				'scroll'  	=> 0,
				'total'	    => 0,
				'limit'	    => 25,
				'page'	    => 1,
				'row'	    => 1,
				'headers'   => 'show',
				'main'		=> 'show',
				'flat'		=> 0,
				'selcol'	=> '',				// selected columns
				'editcol'	=> '',				// edit a single item in a row
				'search'	=> '',
				'checked'   => array(),
				'prevnext'	=> array(),
				'open'	    => array(0),		// open articles
				'edit'	    => array(0),		// articles in edit mode
				'criteria'  => array(),
				'columns'   => array(),
				'custom'    => array(),
				'sql' 		=> array('where'=>'','orderby'=>''),
				'notes'		=> array()
			),$defaults); 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!isset($_SESSION['window'])) {
			
			$_SESSION['window'] = array();
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($winid === 'new') {
			
			$winid = 1;
			
			if (count($_SESSION['window'])) {
				
				$ids = array_keys($_SESSION['window']);
				
				sort($ids);
				
				$winid = array_pop($ids) + 1;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!isset($_SESSION['window'][$winid])) {
		
			$_SESSION['window'][$winid] = array();
		}
		
		$win = $_SESSION['window'][$winid];
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!isset($win['opener'])) {
			
			$win['opener'] = $opener;
		}
		
		if (!isset($win['mini'])) {
			
			$win['mini'] = $mini;
		}
		
		if (!isset($win['notes'])) {
			
			$win['notes'] = array();
		}
		
		if (!isset($win[$event])) {
			
			$win[$event] = $defaults;
		}
		
		if (!isset($win[$event]['id']) or $win[$event]['id'] == 0) {
			
			$win[$event]['id'] 	  = ROOTNODEID;
			$win[$event]['docid'] = ROOTNODEID;
		}
		
		if (!isset($win['list']['notes'])) {
		
			$win[$event]['notes'] = array();
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// main content id
		
		if ($id) { 
			
			if ($step != 'edit' and $step != 'save') {
			
				/* 	
					when list and edit steps are in the same event
					this prevents the edit step from changing the main 
					article id back on the list page
					
					list page: index.php?event=file&step=list&id=1
					edit link: index.php?event=file&step=edit&id=9
					
					keeps the main article id (1) from changing to 9 after going 
					back to the list page
					
					list page: index.php?event=list&step=list&id=1
					edit link: index.php?event=article&step=edit&id=9
					
					here it does not matter because the edit step goes
					to a different event
				*/
				
				$win[$event]['id'] = $id;
			}
			
			$win[$event]['docid'] = $id;
		}	
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($checked) {
	
			$win[$event]['checked'] = expl($checked);
		
		} elseif (isset($_POST['checked'])) {
			
			$win[$event]['checked'] = array();
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($selcol) {
		
			$win[$event]['selcol'] = $selcol;
			
		} elseif (isset($_POST['selcol'])) {
			
			$win[$event]['selcol'] = '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($editcol) {
		
			$win[$event]['editcol'] = $editcol;
			
		} elseif (isset($_POST['editcol'])) {
			
			$win[$event]['editcol'] = '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($search) {
		
			$win[$event]['search'] = $search;
			
		} elseif (isset($_POST['search']) or isset($_GET['search'])) {
			
			$win[$event]['search'] = '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($sortby and $win[$event]['sortby'] != $sortby) {
			
			array_unshift($win[$event]['sorthist'],
				$win[$event]['sortby'].' '.
				strtoupper($win[$event]['sortdir'])
			);
			
			$win[$event]['sorthist'] = array_slice($win[$event]['sorthist'],0,2);
			
			$win[$event]['sortby'] = $sortby;
		}
		
		if ($sortdir) {
			
			$win[$event]['sortdir'] = $sortdir;
			
			$win[$event]['linkdir'] = ($win[$event]['sortdir'] == "desc") 
				? 'asc' : 'desc';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($view) {
		
			$win[$event]['view'] = $view;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($main) {
		
			$win[$event]['main'] = $main;
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (strlen($scroll)) {
			
			$win[$event]['scroll'] = $scroll;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($headers) {
		
			$win[$event]['headers'] = $headers;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$_SESSION['window'][$winid] = $win;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$win[$event]['winid']   = $winid;
		$win[$event]['opener']  = $win['opener'];
		$win[$event]['mini']    = $win['mini'];
		$win[$event]['notes']   = $win['notes'];
		
		$win = $win[$event];
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// sorting for all events
		/*
		if (isset($win['sortby'])) {
		
			if ($sortby)   $win['sortby']  = $sortby;
			if ($sortdir)  $win['sortdir'] = $sortdir;
		
			$win['linkdir'] = ($win['sortdir'] == "desc") 
				? 'asc' : 'desc';
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// note settings for all events
		
		if (isset($win['list']['notes'])) {
			
			$win['notes'] = $win['list']['notes'];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$defaults['note'] = array(
				'status' => 'closed',
				'minmax' => 'max',
				'x'		 => '',
				'y'		 => '',
				'z'		 => '',
				'width'	 => '',
				'height' => ''
		);
			
		$win['reset'] = $defaults;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $win;
	}

// -------------------------------------------------------------

	function print_notes()
	{
		global $WIN, $smarty;
		
		if (!isset($WIN))
			return;
		
		$notes = $WIN['notes'];
		
		foreach ($notes as $key => $note) {
			
			if (table_exists($note['table'])) {
			
				$id 	  = $note['id'];
				$position = array();
				$size     = array();
				
				$row = safe_row(
					"id, title, Body AS text, 
					 Body_html AS html, 
					 CHAR_LENGTH(Body) AS length",
					$note['table'],
					"Status = 6 AND ID = $id",0,0);
				
				if ($row) {
				
					$note = array_merge($row,$note);
					
					if ($note['y'] != '') {
						$position[] = 'top:'.$note['y'].'px';
						$position[] = 'left:'.$note['x'].'px';
						$position[] = 'z-index:'.$note['z'];
					}
					
					if ($note['width']) {
						$size[] = 'width:'.$note['width'].'px';
						$size[] = 'height:'.$note['height'].'px';
					}
					
					$smarty->assign('note_id',$id);
					$smarty->assign('note_status',$note['status']);
					$smarty->assign('note_minmax',$note['minmax']);
					$smarty->assign('note_title',doStrip($note['title']));
					$smarty->assign('note_html',doStrip($note['html']));
					$smarty->assign('note_text',doStrip($note['text']));
					$smarty->assign('note_length',$note['length']);
					$smarty->assign('note_position',join(';',$position));
					$smarty->assign('note_size',join(';',$size));
					$smarty->assign('note_type',$note['type']);
					
					$notes[$key] = $smarty->fetch('note.tpl');
				
				} else {
			
					unset($notes[$key]);
				}
				
			} else {
				
				unset($notes[$key]);
			}
		} 
		
		return join(n.n,$notes);
	}
?>
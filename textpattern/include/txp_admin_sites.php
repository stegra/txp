<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of
	the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_log.php $
$LastChangedRevision: 3203 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	$site_langs = array(
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
		
	if ($event == 'sites')
	{
		require_privs('sites');
		
		$steps = array_merge($steps,array(
			'view'
		));
	}
	
	include_once txpath.'/utilities/include/utilities.php';
	
//--------------------------------------------------------------
	function sites_list($message='')
	{
		global $EVENT, $WIN, $html, $siteurl, $smarty;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['columns']) {
		
			$WIN['columns'] = array(
				
				'Title'  	=> array('title' => 'Title',  	'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	=> array('title' => 'Image',  	'on' => 1, 'editable' => 0, 'pos' => 2),
				'Posted' 	=> array('title' => 'Created', 	'on' => 1, 'editable' => 0, 'pos' => 3),
				'Name' 	 	=> array('title' => 'Name', 	'on' => 1, 'editable' => 1, 'pos' => 4),
				'DB' 	 	=> array('title' => 'DB', 	   	'on' => 0, 'editable' => 1, 'pos' => 5),
				'Prefix'	=> array('title' => 'Prefix', 	'on' => 1, 'editable' => 1, 'pos' => 6),
				'Domain'    => array('title' => 'Domain',   'on' => 1, 'editable' => 1, 'pos' => 7),
				'Version'   => array('title' => 'Version',  'on' => 1, 'editable' => 0, 'pos' => 8),
				'Hosting'   => array('title' => 'Hosting',  'on' => 0, 'editable' => 1, 'pos' => 9),
				'Articles'  => array('title' => 'Articles', 'on' => 1, 'editable' => 0, 'pos' => 10),
				'Images'	=> array('title' => 'Images',   'on' => 0, 'editable' => 0, 'pos' => 11),
				'Files'		=> array('title' => 'Files',   	'on' => 0, 'editable' => 0, 'pos' => 12),
				'Type'		=> array('title' => 'Type',		'on' => 1, 'editable' => 1, 'pos' => 13),
				'AuthorID'	=> array('title' => 'Owner',    'on' => 0, 'editable' => 0, 'pos' => 14),
				'Status'	=> array('title' => 'Status',	'on' => 1, 'editable' => 1, 'pos' => 15),
				'ID'		=> array('title' => 'ID',		'on' => 0, 'editable' => 0, 'pos' => 16),
				'SiteDir'	=> array('title' => 'SiteDir',	'on' => 1, 'editable' => 0, 'pos' => 0)
			);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$html = pagetop('Sites',$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		update_summary_field("txp_site","Articles"); 
		update_summary_field("txp_site","Images"); 
		update_summary_field("txp_site","Files"); 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$content = new ContentList(); 
		$list = $content->getList();
		
		foreach ($list as $key => $item) {
			
			if ($item['Type'] == 'site' and $item['SiteDir']) {
				
				if (!is_dir($item['SiteDir'])) {
					$list[$key]['Status'] = '<span class="error">Missing</span>';
				}
			}
		}
		
		$html.= $content->viewList($list);
		
		if (gps('edit_method') == 'archive_site') {
			
			$html = str_replace('<!-- EVENT_SPECIFIC_ITEMS -->',download_archive_popup(),$html);
		}
			
		save_session($EVENT);
		save_session($WIN);
	}

// -------------------------------------------------------------
	function sites_multi_edit()
	{
		global $WIN, $PFX, $prefs;
		
		$method   = gps('edit_method');
		$selected = gps('selected',array());
		$excluded = array();
		$old	  = array();
		$error	  = '';
		
		// -----------------------------------------------------
		// PRE-PROCESS
		
		if ($method == 'new') {
			
			// $selected = array_slice($selected,0,1,true);
			// $method   = 'new';
		}
		
		if ($method == 'trash') {
			
			foreach ($selected as $id) {
				
				if (safe_count("txp_site","ID = $id AND Type = 'site'")) {
				
					extract(safe_row("SiteDir,Prefix","txp_site","ID = $id"));
				
					if ($SiteDir and is_dir($SiteDir) and $Prefix) {
						
						$Prefix = $Prefix.'_';
						
						if (safe_count("textpattern","AuthorID != 'textpattern' AND Trash IN (0,1)",0,$Prefix)) {
							if (isset($selected[$id])) unset($selected[$id]);
							$excluded[$id] = $id;
						}
						
						if (safe_count("txp_image","AuthorID != 'textpattern' AND Trash IN (0,1)",0,$Prefix)) {
							if (isset($selected[$id])) unset($selected[$id]);
							$excluded[$id] = $id;
						}
						
						if (safe_count("txp_file","AuthorID != 'textpattern' AND Trash IN (0,1)",0,$Prefix)) {
							if (isset($selected[$id])) unset($selected[$id]);
							$excluded[$id] = $id;
						}
					}
				}
			}
		}
		
		// sites_list(); return;
		
		// -----------------------------------------------------
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$selected  = $multiedit->selected;
		$changed   = $multiedit->changed;	
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($changed) {
			
			if ($method == 'new') {
				
				/* $id = array_shift($changed);
				
				$domain = safe_field("Domain","txp_site",
					"ParentID != 0 AND Domain != '' AND Trash = 0 ORDER BY ID ASC");
					
				$values = array(
					'Type'   => doQuote('site'),
					'Title'  => doQuote('My Site'),
					'Name'	 => doQuote('mysite'),
					'Domain' => doQuote($domain),
					'Status' => 1
				);
				
				safe_update("txp_site",$values,"ID = $id");
				
				rebuild_txp_tree(); */
			}
			
			if ($method == 'save') {
				
				safe_update("txp_site",
					"Name = REPLACE(Name,'-','')",
					"ID IN (".in($changed).") AND Type = 'site'");
			}
			
			if ($multiedit->method == 'copy_paste') {
				
				foreach ($changed as $id) {
				
					if (safe_count("txp_site","ID = $id AND Type = 'site'")) {
						
						safe_update("txp_site","Prefix = ''","ID = $id");
					}
				}
			}
			
			if ($method == 'duplicate') {
				
				foreach ($changed as $id) {
					
					if (safe_count("txp_site","ID = $id AND Type = 'site'")) {
					
						extract(safe_row("Name,SiteDir,URL","txp_site","ID = $id"));
						
						$Name = $Name.'copy';
						if ($SiteDir) $SiteDir = $SiteDir.'copy';
						if (preg_match('/\/~/',$URL)) $URL = $URL.'copy';
						
						safe_update("txp_site",
							"Name = '$Name', SiteDir = '$SiteDir', URL = '$URL', Status = 1, Prefix = ''",
							"ID = $id");
					}
				}
			}
			
			if ($method == 'empty_trash') {
				
				foreach ($changed as $id) {
					
					if (safe_count("txp_site","ID = $id AND Type = 'site'")) {
					
						remove_site($id);
					}
				}
			}
			
		} else {
			
			if ($method == 'trash') {
				
				if ($excluded) $error = 'None trashed';
			}
			
			if ($method == 'archive_site') {
				
				foreach ($selected as $id) {
					
					if (safe_count("txp_site","ID = $id AND Type = 'site'")) {
						
						archive_site($id);
					}
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$WIN['checked'] = $selected;
		
		$message = ($error) ? array($error,E_ERROR) : $message;
		
		sites_list($message);
	}
	
// -------------------------------------------------------------
	function sites_edit_type(&$in,&$html)
	{
		if ($in['Type'] == 'site') {
			
			sites_edit_site($in,$html);
		}
	}
	
// -------------------------------------------------------------
	function sites_edit_site(&$in,&$html)
	{
		global $PFX, $txpcfg, $txp_user, $smarty, $site_langs, $path_to_site;
		
		extract($in);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// image
		/*
		if ($ImageID) {
			
			$image = $html[0]['image'];
			
			// BUG: thumbnail sizes are not being updated
			
			extract(safe_row("thumb_w,thumb_h","txp_image","ID = $ImageID"));
			
			$thumb_w = 150;
			$thumb_h = 100;
			
			$image = preg_replace("/_x\.(jpg|png)/","_t.$1",$image);
			$image = str_replace('width="100"','width="'.$thumb_w.'"',$image);
			
			$html[0]['image'] = $image;
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// redo snapshot 
		/*
		$snapshot = make_site_snapshot($Title,date('F j, Y'));
		
		// add snapshot image to library
		
		if (is_file($snapshot)) {
		
			$snapshot = add_image_to_library($snapshot,'sites',150,0,4); 
			
			// add snapshot image id to site
			
			if ($snapshot) {
				
				safe_update('txp_site',"ImageID = $snapshot","ID = $ID");

			}
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// database
		
		if (!$DB and !isset($_POST['DB'])) { 
			$DB = $txpcfg['db'];
		}
		
		if (!$Prefix and !isset($_POST['Prefix'])) { 
			$Prefix = $txpcfg['table_prefix'];
		}
		
		$databases = array_merge(array(''=>''),safe_databases());
		$DB_exists = ($DB and isset($databases[$DB]));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// site directory
		
		if (!$SiteDir and !isset($_POST['SiteDir'])) {
			
			$name = fetch("Name","txp_site","ID",$ID);
			$SiteDir = $path_to_site.'/sites/'.$name;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// site url
		
		if (!$URL and !isset($_POST['URL'])) {
		
			$main_domain = safe_field("Domain","txp_site",
				"ID != $ID AND Domain != '' AND Trash = 0 ORDER BY ID ASC");
			
			if ($Domain == $main_domain) {
				$URL = "http://".$Domain.'/~'.$Name;
			} elseif ($Domain) {
				$URL = "http://".$Domain;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// site admininistrator info 
		
		$admin_users = safe_column("name","txp_users","privs = 1 ORDER BY ID ASC");
		
		if (!$Admin) {
		
			$Admin = $txp_user;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// create site button 
		
		if ($Status == 1) {
			
			$show_create_site = false;
			
			if ($SiteDir and !is_dir($SiteDir)) {
					
				$show_create_site = true;
			}
			
			if ($Prefix and !table_exists('textpattern',$Prefix)) {
				
				$show_create_site = true;
			}
			
			if ($show_create_site) {
			
				$html[3]['save'] .= n.n.hInput('create_site',''); 
				$html[3]['save'] .= n.fInput('submit','publish','Create Site',"publish create-site",'','','',4); 
			}
		} 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// reorganize images 
		
		if (isset($_GET['redoimg'])) {
			
			$PFX = $Prefix.'_';
			
			$result = reorganize_images($SiteDir); 
			
			$Images = safe_count('txp_image',"Type = 'image' AND Trash = 0");
			
			$PFX = '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$smarty->assign('title',$Title);
		$smarty->assign('body',$Body);
		$smarty->assign('name',$Name);
		$smarty->assign('status',$Status);
		
		$smarty->assign('db_name',$DB);
		$smarty->assign('databases',$databases);
		$smarty->assign('db_prefix',$Prefix);
		
		$smarty->assign('location_site_path',$SiteDir);
		$smarty->assign('location_site_url',$URL);
		$smarty->assign('site_lang',$Language);
		$smarty->assign('site_langs',$site_langs);
		
		$smarty->assign('admin_user',$Admin);
		$smarty->assign('admin_users',$admin_users);
		$smarty->assign('admin_ftp',$FTP);
		$smarty->assign('admin_ssh',$SSH);
		
		$smarty->assign('info_articles',$Articles);
		$smarty->assign('info_images',$Images);
		$smarty->assign('info_files',$Files);
		$smarty->assign('info_ftp',reset(explode('/',$FTP)));
		
		if (!$Images and is_dir($SiteDir.'/images/uploads')) {
			$req = $_SERVER['REQUEST_URI'];
			$smarty->assign('info_images','<a href="'.$req.'&redoimg">Reorganize</a>');
			$smarty->assign('info_files','<a href="'.$req.'&redofile">Reorganize</a>');
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$html[0]['sidehelp'] = '';
		$html[0]['advanced'] = hInput('Name',$Name);
		$html[0]['recent']   = '';
		$html[0]['special']  = $smarty->fetch('admin/sites_edit_site_left.tpl');
		
		$html[1]['body']     = $smarty->fetch('admin/sites_edit_site_main.tpl');
		$html[1]['excerpt']  = '';
		$html[1]['author']   = str_replace(gTxt('posted_by'),gTxt('created_by'),$html[1]['author']);
	}

//--------------------------------------------------------------
	function sites_save($ID=0, $multiedit=null)
	{
		global $WIN, $step, $vars, $app_mode;
		
		$vars = array_merge($vars,array(
			'DB', 
			'Prefix',
			'SiteDir',
			'URL',
			'Admin',
			'Language'
		));
    
		$message = '';
		$create_site = gps('create_site'); 
		
		$ID = content_save($ID,$multiedit);
		
		safe_update("txp_site",
			"Name = REPLACE(Name,'-','')",
			"ID = $ID AND Type = 'site'");
		
		if ($create_site) {
			
			$message = create_site($ID);
		}
		
		if (!$app_mode == 'async') {
			
			$GLOBALS['ID'] = $ID;
			
			event_edit($message);
		}
	}
	
// -------------------------------------------------------------
	function sites_view()
	{
		global $WIN,$smarty;
		
		$rootdomain = safe_field("Domain","txp_site",
			"Domain != '' AND Trash = 0 ORDER BY ID ASC");
		
		$docid   = $WIN['id'];
		$checked = $WIN['checked'];
		$sites   = array();
		
		$columns = "ID,Type,Title,Name,Domain";
		$orderby = " ORDER BY Posted DESC";
		
		if ($checked) {
			
			$checked = safe_column("ID","txp_site",
				"ID IN (".impl($checked).")".$orderby);
			
			foreach ($checked as $id) {
				
				$item = safe_row($columns,'txp_site',"ID = $id".$orderby);
				
				extract($item);
				
				if ($Type == 'folder') {
					
					$items = safe_rows($columns,"txp_site",
						"ParentID = $ID 
					 	 AND Type = 'site' 
					 	 AND Trash = 0 
					     $orderby");
					
					foreach($items as $item) {
						$id = $item['ID'];
						$sites[$id] = $item;
					}
					
				} else {
					
					$sites[$id] = $item;
				}
			}
		}
		
		if (!$sites) {
		
			$sites = safe_rows($columns,"txp_site",
				"ParentID = $docid 
				 AND Type = 'site' 
				 AND Trash = 0 
				 $orderby");
		}
		
		if (!$sites) return;
			 
		foreach ($sites as $key => $site) {
			
			extract($site);
			
			$sites[$key]['href'] = "http://".$Domain.'/';
			
			if ($rootdomain == $Domain) {
				$sites[$key]['href'] .= '~'.$Name.'/';
			}
		}
		
		$sites = array_values($sites);
		
		if (count($sites) == 1) {
			
			header("Location: ".$sites[0]['href']."index.html");
		}
		
		// -------------------------------------------------------------
		
		$smarty->assign('id',$sites[0]['ID']);
		$smarty->assign('src',$sites[0]['href'].'index.html');
		$smarty->assign('sites',$sites);
		
		echo $smarty->fetch('admin/sites_view_sites.tpl');
	}	

//--------------------------------------------------------------
	function site_lang($lang,$status) 
	{
		if ($status == 1) {
		
			$out = '<select name="lang">';
	
			foreach ($things as $a=>$b) {
				$out .= '<option value="'.$a.'">'.$b.'</option>'.n;
			}		
	
			$out .= '</select>';
		
		} else {
			
			$out = (isset($things[$lang])) ? $things[$lang] : '';	
		}
		
		return $out;
	}

// -------------------------------------------------------------
	function create_site($id)
	{
		global $DB, $WIN, $PFX, $prefs, $txpcfg, $path_to_site, $app_mode;
		
		$out   = array();
		$error = '';
		$sites = $path_to_site.DS.'sites';
		$setup = txpath.'/setup';
		
		extract(safe_row("title,name,db,db_user,db_pass,db_host,prefix,SiteDir,TxpDir,URL,Language,Admin,Copy",
			"txp_site","ID = $id"));
		
		$where = (column_exists("txp_users","ParentID"))
			? "name,pass,Title AS RealName,email"
			: "name,pass,RealName,email";
			
		$admin = safe_row($where,"txp_users","name = '$Admin'");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$prefix) {
						
			if (safe_count("txp_site","DB = '$db' AND `Type` = 'site' AND Trash = 0 AND ID != $id")) {
				
				$error = "For database <b>$db</b> a table prefix must be specified!";
			}
			
		} elseif (safe_count("txp_site","DB = '$db' AND Prefix = '$prefix' AND `Type` = 'site' AND Trash = 0 AND ID != $id")) {
			
			$error = "Table prefix <b>$prefix</b> already exists!";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($error) {
			
			return array($error,E_ERROR);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$name) {
			
			$error = "Please enter a <b>name</b>!";
			
		} elseif ($name and safe_count("txp_site","Name = '$name' AND `Type` = 'site' AND Status != 1 AND Trash = 0 AND ID != $id")) {
				
			$error = "<b>$name</b> already exists!";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($error) {
			
			return array($error,E_ERROR);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// create site directory
		
		$sitedir = '';
		
		if (!$error) {
			
			$sitedir = $sites.DS.$name;
			
			if (!is_dir($sitedir)) {
				
				@mkdir($sitedir,0777);
				@chmod($sitedir,0777);
				
				if (is_dir($sitedir)) {
				
					$oldsitedir = ($Copy)
						? fetch("SiteDir","txp_site","ID",$Copy)
						: $setup.'/www';
					
					if ($oldsitedir and is_dir($oldsitedir)) {
					
						@exec("cp -rp $oldsitedir/* $sitedir");
					
						if (is_dir($sitedir.'/images')) {
					
							if (is_file($sitedir.'/_htaccess'))  unlink($sitedir.'/_htaccess');
							if (is_file($sitedir.'/index.php'))  unlink($sitedir.'/index.php');
							if (is_file($sitedir.'/setup.php'))  unlink($sitedir.'/setup.php');
							if (is_file($sitedir.'/README.rtf')) unlink($sitedir.'/README.rtf');
						
						} else {
							
							$error = "unable to populate site direcory <b>$name</b>";	
						}
					
					} else {
						
						$error = "unable to make site directory for <b>$name</b>";
					}
					
				} else {
					
					$error = "unable to make site directory for <b>$name</b>";
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($error) {
			
			return array($error,E_ERROR);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// create database tables
		
		$sql_file = $sitedir.'/database/txp.sql';
		
		if (!$Copy) {
			
			// @exec("cp $setup/txp.sql $sql_file");
			
		} else {
		
			$old_prefix = safe_field("prefix","txp_site","ID = $Copy");
			
			if ($old_prefix) {
				
				mysqldump($sql_file,$old_prefix);
			}
		}
		
		if (is_file($sql_file)) {
			
			$PFX = ($prefix) ? $prefix.'_' : '';
			
			$sql = file_get_contents($sql_file);
			
			if ($Copy) {
			
				$sql = str_replace("`".$old_prefix."_","`$PFX",$sql);
			
			} else {
				
				$sql = str_replace("`textpattern`","`".$PFX."textpattern`",$sql);
				$sql = str_replace("`txp_","`".$PFX."txp_",$sql);
			}
			
			write_to_file($sql_file,$sql);
			
			if (!table_exists('textpattern',$PFX)) {
				
				$options = array(
					"-h ".$DB->host,
					"-u ".$DB->user,
					"--password=".$DB->pass);
					
				exec('mysql '.implode(' ',$options).' '.$DB->db.' < '.$sql_file);
				
			} else {
				
				$error = $PFX."textpattern table already exists!";
			}
		
		} else {
			
			$error = "$sql_file file does not exist!";
		}	
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($error) {
			
			return array($error,E_ERROR);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		/* 
		if (!$Copy) {
			
			foreach (safe_tables($PFX) as $table) {
					
				if (column_exists($table,'AuthorID',$PFX)) {
					safe_delete($table,"AuthorID != 'textpattern'");
					safe_alter($table,"AUTO_INCREMENT = 0",0);
				}
			}
			
			safe_delete("txp_users","1=1");
			safe_alter("txp_users","AUTO_INCREMENT = 0",0);
		}
		*/
		
		clear_cache();
		safe_delete("txp_window","1=1");
						
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add prefs
		
		if (table_exists('txp_prefs',$PFX)) {
					
			set_pref('sitename',$title);
			set_pref('language',$Language);
			set_pref('path_to_site',$SiteDir);
			set_pref('tempdir',$TxpDir.'/tmp');
			set_pref('file_base_path',$SiteDir.'/files');
			set_pref('siteurl',str_replace('http://','',$URL));
			
			/* 
			set_pref('locale','');
			set_pref('is_dst','');
			set_pref('timezone_key','');
			set_pref('timeoffset','');
			set_pref('auto_dst','');
			set_pref('gmtoffset','');
			*/
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add site title and name
		
		if (table_exists('textpattern',$PFX)) {
			
			$rootid = fetch('ID','textpattern',"ParentID",0);
			safe_update("textpattern","Name = '$name', Title = '$title'","ID = $rootid");
			safe_update("textpattern","ParentName = '$name'","ParentID = $rootid");
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add user
		
		if ($admin and table_exists('txp_users',$PFX)) {
			
			extract($admin,EXTR_PREFIX_ALL,'admin');
			
			if (!safe_count("txp_users","name = '$admin_name'")) {
			
				$nonce = md5(uniqid(rand(),true));
				
				$realname = (column_exists("txp_users","ParentID"))
					? "RealName" : "Title";
					
				safe_insert('txp_users',
				   "`name` 	  	= '$admin_name',       
					`pass`		= '$admin_pass',          
					`$realname` = '".doSlash($admin_RealName)."',  
					`email`     = '".doSlash($admin_email)."',  
					`privs`     = 1,
					`last_access` = now(),
					`nonce`     = '$nonce'");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$PFX = $txpcfg['table_prefix'];
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// make home page snapshot image
		
		$snapshot = make_site_snapshot($title,date('F j, Y'));
		
		// add snapshot image to library
		
		if (is_file($snapshot)) {
		
			$snapshot = add_image_to_library($snapshot,'sites',150,0,4); 
			
			// add snapshot image id to site
			
			if ($snapshot) {
				
				safe_update('txp_site',"ImageID = $snapshot","ID = $id");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$error) {
			
			safe_update("txp_site","Status = 4, Copy = 0","ID = $id");
			
			/* if ($Copy) {
			
				safe_update("txp_site","Status = 4, Copy = 0","ID = $id");
			
			} else {
			
				safe_update("txp_site","Status = 4, Articles = 1, Images = 1","ID = $id");
			} */
			
			$message = "site <b>$name</b> created";
			
		} else {
			
			// safe_update("txp_site","Status = 1","ID = $id");
			
			$message = array($error,E_ERROR);
		}
		
		return $message;
	}

// -------------------------------------------------------------
	function remove_site($id)
	{
		global $DB, $prefs;
		
		$out = array();
		
		extract(safe_row("Name,SiteDir,Prefix,URL","txp_site","ID = $id"));
		
		// -----------------------------------------------------
		
		if (!$Prefix or !$SiteDir) { 
			
			return;
		}
		
		if (safe_count("txp_site","Prefix = '$Prefix' AND Trash = 0")) {
				
			return;
		}
		
		if (safe_count("txp_site","SiteDir = '$SiteDir' AND Trash = 0")) {
				
			return;
		}
		
		$SiteDir = '/'.ltrim($SiteDir,'/');
		
		if (!is_dir($SiteDir)) {
			
			return;
		}
		
		if (!preg_match('/\/sites\/[a-z0-9]+$/',$SiteDir)) {
		
			return;
		}
		
		// -----------------------------------------------------
		// mysqldump prefix tables
		
		if (mysqldump($SiteDir.'/database/db.sql.gz',$Prefix))
		{
			// drop prefix tables
		
			safe_drop_pfx($Prefix);
		}
								
		// -----------------------------------------------------
		// move site folder to trash
		
		$trash_dir  = $prefs['path_to_site'].'/sites/_trash';
		$trash_site = $id.'_'.$Name;
		
		if (is_dir($trash_dir)) {
		
			if (is_writable($trash_dir)) {
		
				rename($SiteDir,$trash_dir.'/'.$trash_site);
			
				chdir($trash_dir);
				
				if (is_dir($trash_site)) {
				
					exec("tar --remove-files -cpzf $trash_site.tar.gz $trash_site",$out);
					
					if (is_file($trash_site.'.tar.gz')) {
						
						if (is_dir($trash_site)) {
							remove_empty_subdir($trash_dir.'/'.$trash_site);
						}
					}
				}
			}
		}
	}

// -------------------------------------------------------------
	function archive_site($id)
	{
		global $siteurl, $prefs;
		
		extract(safe_row("Name,Prefix,SiteDir","txp_site","ID = $id"));
		
		if (!$SiteDir) return;
		
		$txpdir = txpath;
		
		// ---------------------------------------------
		// copy textpattern dir to sites folder
		
		@exec("cp -rp $txpdir $SiteDir");
		
		// ---------------------------------------------
		// copy textpattern dir to sites folder
		
		if (is_dir("$SiteDir/textpattern")) {
			
			$pages = dirlist("$SiteDir/textpattern/xsl/page");
			
			foreach ($pages as $file) {
				unlink("$SiteDir/textpattern/xsl/page/$file");
			}
			
			$forms = dirlist("$SiteDir/textpattern/xsl/form");
			
			foreach ($forms as $file) {
				unlink("$SiteDir/textpattern/xsl/form/$file");
			}
			
			$pages = dirlist("$SiteDir/xsl/page");
			
			foreach ($pages as $file) {
				copy("$SiteDir/xsl/page/$file","$SiteDir/textpattern/xsl/page/$file");
			}
			
			$forms = dirlist("$SiteDir/xsl/form");
			
			foreach ($forms as $file) {
				copy("$SiteDir/xsl/form/$file","$SiteDir/textpattern/xsl/form/$file");
			}
		
		} else {
			
			echo "Error: $SiteDir/textpattern dir does not exist";
			return;
		}
		
		// ---------------------------------------------
		// copy htaccess file to sites folder
		
		copy($prefs['path_to_site'].'/.htaccess',"$SiteDir/.htaccess");
		
		// ---------------------------------------------
		// copy index.php file to sites folder
		
		copy($prefs['path_to_site'].'/index.php',"$SiteDir/index.php");
		
		// ---------------------------------------------
		// delete config file
		
		if (is_file("$SiteDir/textpattern/config.php")) {
			
			unlink("$SiteDir/textpattern/config.php");
		}
		
		// ---------------------------------------------
		// delete trashed images
		
		delete_trashed_images($id);
		
		// ---------------------------------------------
		// delete logs
		/*
		$log = dirlist($SiteDir.'/log');
		
		foreach ($log as $file) {
			unlink($SiteDir.'/log/'.$file);
		}
		*/
		// ---------------------------------------------
		// mysqldump prefix tables
		
		$sqlfile = $SiteDir.'/textpattern/setup/archive.sql';
		 
		mysqldump($sqlfile,$Prefix);
		
		// ---------------------------------------------
		// remove prefix from table names
		
		if (is_file($sqlfile)) {
			
			exec("sed -i 's/`".$Prefix."_/`/g' $sqlfile");
		}
		
		// ---------------------------------------------
		// create tmp dir if it does not exist 
		
		if (!is_dir($SiteDir.'/tmp')) {
			
			@mkdir($SiteDir.'/tmp',0777);
		}
		
		// ---------------------------------------------
		// tar sites folder 
		
		$tarfile = "$Name.tar.gz";
		
		$include = implode(' ',array(
			'css',
			'files',
			'images',
			'js',
			'textpattern',
			'index.php',
			'.htaccess'
		));
		
		if (is_dir($SiteDir.'/fonts')) {
			$include .= ' fonts';
		}
		
		if (is_file($SiteDir.'/favicon.ico')) {
			$include .= ' favicon.ico';
		}
		
		if (is_dir($SiteDir.'/tmp')) {
			$tarfile = "tmp/$tarfile";
		}
		
		chdir($SiteDir);
		
		@exec("tar -czpf $tarfile $include",$null);
		
		// ---------------------------------------------
		// remove files no longer needed
		
		if (is_file("$SiteDir/.htaccess")) unlink("$SiteDir/.htaccess");
		if (is_file("$SiteDir/index.php")) unlink("$SiteDir/index.php");
	}

// -------------------------------------------------------------
	function download_archive_popup() 
	{
		global $siteurl;
		
		$selected = gps('selected',array());
		$archives = array();
		$html     = '';
		
		foreach ($selected as $id) {
			
			extract(safe_row("Name,Title,SiteDir","txp_site","ID = $id AND Type = 'site'"));
			
			$tarfile = "$Name.tar.gz";
			
			$archives[$Name] = array(
				'title' => "Download $Title Archive",
				'file' 	=> $tarfile,
				'href' 	=> "",
				'size' 	=> 0
			);
			
			$tmp = (is_dir("$SiteDir/tmp")) ? 'tmp/' : '';
			
			if (is_file("$SiteDir/$tmp$tarfile")) {
				
				$archives[$Name]['href'] = "http://$siteurl/sites/$Name/$tmp$tarfile";
				$archives[$Name]['size'] = format_bytes(filesize("$SiteDir/$tmp$tarfile"));
			}
		}
		
		if ($archives) {
		
			foreach ($archives as $key => $item) {
				
				$archives[$key] = ($item['size'])
					? '<li><a title="'.$item['title'].'" href="'.$item['href'].'">'.$item['file'].'</a> <span class="size">'.$item['size'].'</span></li>'
					: '<li>'.$item['file'].'<span class="size error">Error</span></li>';
			}
			
			$html  = '<div id="download-archive">'.n;
			$html .= '<div class="header">Download <a href="#" title="Close" class="close">x</a></div>'.n;
			$html .= '<ul>'.n.implode(n,$archives).n.'</ul>'.n;
			$html .= '</div>';
		}
		
		return $html;
	}
	
// -------------------------------------------------------------
	function delete_trashed_images($id) 
	{
		global $PFX;
		
		extract(safe_row("Prefix,SiteDir","txp_site","ID = $id"));
		
		$PFX = ($Prefix) ? $Prefix.'_' : '';
		
		$rows = safe_rows("FilePath,Name,ext","txp_image",
			"Type = 'image' AND Trash > 0 AND FileDir != 0");
		
		// -------------------------------------------------
		// remove images 
		
		foreach($rows as $image) {
			
			extract($image);
			
			$dir  = $SiteDir.'/images/content/'.$FilePath;
			$file = $Name.$ext;
			
			if (is_dir($dir)) {
				
				foreach (dirlist($dir) as $file) {
					
					$delete = '';
					
					foreach(array('','_r','_t','_xx','_x','_y','_z','_THUMB') as $size) {
						
						if ($file == $Name.$size.$ext) {
							
							$delete = $file;
						
						} elseif ($size == '_THUMB') {
							
							if ($file == $Name.$size) {
								
								$delete = $file;
							}
						
						} elseif ($size == '_r' or $size == '_t') {
							
							if (str_begins_with($file,$Name.$size)) {
							
								if (preg_match('/_[rt]_\d+(_\d+)?\./',$file)) {
								
									$delete = $file;
								}
							}
						}
						
						if ($delete) break;
					}
					
					if ($delete) {
						
						@unlink($dir.'/'.$delete);
						
					}
				}
				
				// -----------------------------------------
				// remove folder if empty
				
				if (count(dirlist($dir)) == 0) {
					
					if (strlen($FilePath) > 1) {
					
						@rmdir($dir);
					}
				}
			}
		}
		
		// -------------------------------------------------
		// remove empty folders 
		
		foreach($rows as $image) {
			
			extract($image);
			
			$dir  = $SiteDir.'/images/content/'.$FilePath;
			$file = $Name.$ext;
			
			if (!is_dir($dir)) {
				
				$dir = explode('/',$dir);
				array_pop($dir);
				$dir = implode('/',$dir);
				
				if (is_dir($dir)) {
				
					if (is_numeric(basename($dir))) {
						
						if (count(dirlist($dir)) == 0) {
							
							@rmdir($dir);
						}
					}
				}
			}
		}
		
		// -------------------------------------------------
		// delete trashed images from database  
		
		safe_delete("txp_image","Type = 'image' AND Trash > 0");
		safe_update("textpattern","ImageID = 0","ImageID < 0");
		safe_update("txp_image","ImageID = 0","ImageID < 0");
		
		// -------------------------------------------------
		
		$PFX = '';
	}
	
// -------------------------------------------------------------
	function encode_numericentity($text) 
	{
	
		$text_encoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1');
		
		if ($text_encoding != 'UTF-8') {
			$text = mb_convert_encoding($text, 'UTF-8', $text_encoding);
		}
		
		$text = mb_encode_numericentity($text,array (0x0, 0xffff, 0, 0xffff), 'UTF-8');
		$text = preg_replace('/\&\#13;/','',$text);
		
		return $text;
	}

// -------------------------------------------------------------
	function make_site_snapshot($title,$date) 
	{
		$mysite_in  = txpath."/txp_img/mysite.jpg";
		$mysite_out = txpath."/tmp/".make_name($title).".jpg";
		$mysite     = ImageCreateFromJPEG($mysite_in);
		
		// title
		
		$text	= encode_numericentity($title);
		$color	= ImageColorAllocate($mysite,30,30,30);
		$font	= txpath."/fonts/Georgia.ttf";
		$size 	= 34;
		$x 		= 50;
		$y  	= 55;
		
		$box = imagettfbbox($size,0,$font,$text);
		$x = (imagesx($mysite)-$box[2])/2;

		ImageTTFText($mysite,$size,0,$x,$y,$color,$font,$text);
		
		// date 
		
		$text   = $date;
		$color	= ImageColorAllocate($mysite,125,125,125);
		$font	= txpath."/fonts/Verdana.ttf";
		$size 	= 10;
		$x 		= 160;
		$y  	= 150;
		
		ImageTTFText($mysite,$size,0,$x,$y,$color,$font,$text);
		
		ImageJPEG($mysite,$mysite_out,100);
		ImageDestroy($mysite);
		
		if (is_file($mysite_out)) return $mysite_out;
	}
?>

<html>
<head>
	<title>Update DB</title>
	<link rel="stylesheet" type="text/css" href="/admin/update/update.css"/>
	<script type="text/javascript" src="/admin/js/lib/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="/admin/update/update.js"></script>
</head>
<body>

<?php
	
	// VERSION: 4.2.0.8
	
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
	
	include_once txpath.'/lib/classTextile.php';
	include_once txpath.'/update/lib/lib_update.php';
	include_once txpath.'/utilities/include/utilities.php';
	include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
	
	global $PFX, $site_id, $dbupdate, $files, $textarray;
	
	if (SETUP) {
		$textarray = array_merge($textarray,setup_load_lang(LANG));
	}
	
	if (ob_get_level()) ob_end_flush();
	
	echo "<!-- OUTPUT BUFFERING PAD ".str_repeat('*** ',256)."-->";
	
	$dbupdate = get_db_update_version();
	
	// add_custom_fields();
	// add_custom_field_values();
	
	// exit;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// get todo count 
	
	$list = scandir(txpath.'/update');
	$files = array();
	
	foreach($list as $file) {
		
		if (preg_match('/^_to_(\d+)\.(\d+)\.(\d+)\.(\d+)\.php$/',$file,$matches)) {
			
			$key = $matches[4];
			$content = str_replace(n,' ',file_get_contents(txpath.'/update/'.$file));
			preg_match_all('/\btodo\(/',$content,$matches);
			$files[$key] = count($matches[0]);
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$update = trim(gps('update','SELF'));
	
	if ($update == 'SELF') {
		
		update_site_tables(array(
			'ID'=>0,
			'Title'=>$sitename,
			'Version'=>$prefs['version'])
		);
		
		// create setup sql file
		
		if ($PFX == 'mys_') {
		
			create_setup_sql($PFX);
		}
	
	} else {
		
		// echo "MULTI ($update)";
		
		$columns = "ID,DB,DB_User,DB_Pass,DB_Host,
			CONCAT(Prefix,IF(Prefix!='','_','')) AS PFX,
			SiteDir,Title,Version,Type,Status";
		
		if ($update == 'MULTI') {
		
			$where[] = "ToDo = 'update_db'";
		
		} elseif (preg_match('/^[0-9,]+$/',$update)) {
			
			$ids = explode(',',$update);
			
			$where[] = "ID IN ($update)";
		}  
		
		$sites = array();
		
		$items = safe_rows($columns,"txp_site",doAnd($where)."ORDER BY Level ASC, ID ASC",0,1);
		
		pre($items);
		
		foreach ($items as $key => $item) {
			
			if ($item['Type'] == 'folder') {
				
				$where = array(
					'db'   => "DB = '".$txpcfg['db']."'",
					'dir'  => "SiteDir != ''",
					'type' => "Type = 'site'"
				);
				
				$rows = safe_rows_tree($item['ID'],$columns,"txp_site",doAnd($where),1);
				
				pre($rows);
			
			} elseif ($item['DB'] == $txpcfg['db'] and (is_dir($item['SiteDir']))) {
					
				$sites[] = $item;
			}
		}
		
		pre($sites);
		
		foreach ($sites as $site) {
			// $PFX = $site['PFX'];
			// update_site_tables($site);
		}
	}
		
// -----------------------------------------------------------------------------

	function update_site_tables($site) 
	{
		global $PFX, $txp_prefs, $files, $dbversion, $dbupdate, $tables, $tree_tables;
		
		$txp_prefs = get_prefs();
		$gmtoffset = $txp_prefs['gmtoffset'];
		$tables = safe_tables('',$PFX);
		
		$site_id   = $site['ID'];
		$title     = $site['Title'];
		$dbversion = $site['Version'];
		
		if (!$dbversion) {
			$dbversion = $txp_prefs['version'];
		}
		
		echo comment_line('==');
		echo '<div class="site" id="site-'.$site_id.'">'.n.n;
		echo hed(href($title));
		
		if ($dbversion == $dbupdate) {
			
			echo '</div>';
			
			/* if ($site_id) {
			
				$PFX = '';
				
				safe_update('txp_site',
					"Version = '$dbupdate'",
					"ID = $site_id");
			} */
			
			return;
		}
		
		// -----------------------------------------------------------------
		// update to 4.2.0
		
		include txpath.'/update/_update_base.php';
		
		// -----------------------------------------------------------------
		
		$txp_prefs = get_prefs();
		
		$base_version = explode('.',$dbversion);
		$dbversion    = array_pop($base_version);
		$base_version = implode('.',$base_version);
		$dbupdate 	  = end(explode('.',$dbupdate));
		
		// -----------------------------------------------------------------
		// todos
		
		$todo = 0;
		
		for ($update = 1; $update <= $dbupdate; $update++) {
			
			if ($update > $dbversion) {
				
				$todo += (isset($files[$update])) ? $files[$update] : 0;
			}
		}
		
		echo '<div class="progress"><span id="prog-site-'.$site_id.'">0/'.$todo.'</span></div>';
		
		// -----------------------------------------------------------------
		
		for ($update = 1; $update <= $dbupdate; $update++) {
			
			if ($update > $dbversion) {
				
				update_version($base_version,$update);
			}
		}
		
		$update--;
		
		// -----------------------------------------------------------------
		/*
		echo '<script type="text/javascript">';
		echo "toDo($site_id,1);";
		echo '</script>'.n;
		
		if (SETUP) {
			
			$rel_siteurl = preg_replace('#^(.*)/(textpattern|admin)[/setuphindx.]*?$#i','\\1',$_SERVER['SCRIPT_NAME']);
			
			echo '<div class="success">'.n;
			echo graf(str_replace("{version}",$base_version.'.'.$update,gTxt('that_went_well'))).n;
			echo graf(str_replace('"index.php"','"'.$rel_siteurl.'/admin/index.php"',gTxt('you_can_access'))).n;
			echo graf(gTxt('thanks_for_interest'));
			echo '</div>'.n;
		}
		
		echo '</div>';
		*/
		// -----------------------------------------------------------------
		// update version in txp_prefs table
		
		// set_pref('version',$base_version.'.'.$update);
		
		// update version in txp_site table
		
		if ($site_id) {
			
			$PFX = '';
			
			safe_update('txp_site',
				"Version = '$base_version.$update'",
				"ID = $site_id");
		}
	}

// -----------------------------------------------------------------------------

	function update_version($base,$update) {
		
		global $PFX, $txp_user, $txp_prefs, $dbupdate, $tables, $tree_tables;
		
		$version = $base.'.'.$update;
		$file = txpath.'/update/_to_'.$version.'.php';
		
		echo comment_line();
		
		if (is_file($file)) {
			
			echo '<div class="version">'.n.n;
			
			echo graf(href("Version $version")).n.n;
			
			if ((include $file) === false) {
				
				$update = 0;
			}
			
			if ($update == $dbupdate) {
				
				update_tree_tables();
			}
			
			echo n.'</div>';
		
		} else {
			
			$update = 0;
		}
		
		return $update;
	}

// -----------------------------------------------------------------------------

	function newest_file() 
	{
		$newest = 0;
		$dp = opendir(txpath.'/update/');
		
		while (false !== ($file = readdir($dp)))
		{
			if (strpos($file,"_") === 0)
				$newest = max($newest, filemtime(txpath."/update/$file"));
		}
		
		closedir($dp);
		
		return $newest;
	}

// -----------------------------------------------------------------------------

	function setup_load_lang($lang) 
	{
		require_once txpath.'/setup/setup-langs.php';
		$lang = (isset($langs[$lang]) && !empty($langs[$lang]))? $lang : 'en-gb';
		// define('LANG', $lang);
		return $langs[$lang];
	}

// -----------------------------------------------------------------------------

	function create_setup_sql($pfx) 
	{
		$file = txpath.'/setup/txp.sql';
		
		if (mysqldump($file,$pfx)) {
			
			$sql = file_get_contents($file);
			write_to_file($file,str_replace("`".$pfx,"`",$sql));
			chmod($file,0777);
		}
	}
?>

</body>
</html>
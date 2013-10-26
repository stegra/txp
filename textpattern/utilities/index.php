<?php

// -----------------------------------------------------------------------------
// Load up barebones TXP

	define("txpinterface", "admin");
	
	if (!defined('txpath')) {
		define("txpath", dirname(dirname(__FILE__)));
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// load main site config
	
	if (!isset($txpcfg)) {
		
		include txpath.'/config.php';
	}
	
	// session_start(); // this does not work here for some reason
	
	$site_domain   = $_SERVER['HTTP_HOST'];
	$site_url      = $site_domain.'/';
	$site_url_path = '/';

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	include_once txpath.'/lib/constants.php'; 
	include_once txpath.'/lib/txplib_misc.php';
	include_once txpath.'/lib/txplib_atts.php';
	include_once txpath.'/lib/txplib_db.php'; 
	include_once txpath.'/lib/txplib_html.php';
	include_once txpath.'/lib/txplib_forms.php';
	include_once txpath.'/lib/txplib_theme.php';
	include_once txpath.'/lib/txplib_xml.php';
	include_once txpath.'/lib/classTextile.php';
	include_once txpath.'/lib/admin_config.php';
	include_once txpath.'/lib/classPath.php'; 
	include_once txpath.'/include/lib/txp_lib_tags.php';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// load site config if this is not the main site
	
	if ($_SERVER['SERVER_ADDR'] == $_SERVER['SERVER_NAME']) {
		
		// when site url is using the IP address
		
		define('IS_MAIN_SITE',true);
		
	} elseif ($site_name = get_site_name(1)) {
		
		$txpcfg = get_site_config($site_name);
		
		define('IS_MAIN_SITE',false);
	
	} else {
		
		define('IS_MAIN_SITE',true);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// database
	
	$DB->refresh();
	$PFX = $txpcfg['table_prefix'];
	
 // $tables = remove_pfx(getThings('SHOW TABLES',0));
	$tables = array_flip(getThings('SHOW TABLES'));
	foreach ($tables as $key => $val) $tables[$key] = $key;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$prefs = $txp_prefs = get_prefs();
	
	extract($txp_prefs);
	
	$base = $prefs['base'] = $txp_prefs['base'] = get_base_admin_path();
	
	$thisversion = '4.2.0';
	
	define("LANG","en-gb");
	define('txp_version', $thisversion);
	define("hu",'http://'.$siteurl.'/'); 
	define("rhu",preg_replace("/https?:\/\/.+(\/.*)\/?$/U","$1",hu));
	
	define("IMPATH",$path_to_site.DS.$img_dir.DS);
	define("FPATH",$file_base_path.DS);
	define("FPATH_FTP",$file_base_path.DS.'_ftp'.DS);
	
	define("IMG_PATH",$path_to_site.DS.$img_dir.DS);
	define("IMG_FTP_PATH",$path_to_site.DS.$img_dir.DS.'_ftp'.DS);
	define("FILE_PATH",$file_base_path.DS);
	define("FILE_FTP_PATH",$file_base_path.DS.'_ftp'.DS);
	define("IMP_PATH",$file_base_path.DS.'_import'.DS);
	define("EXP_PATH",$file_base_path.DS.'_export'.DS);
	define("EXP_DB_PATH",$file_base_path.DS.'_export'.DS.'db'.DS);
	define("IMPORT",false);
	define('PREVIEW',false);
	
	// include txpath.'/include/txp_auth.php';
	// doAuth();
	
	$txp_user = (isset($_COOKIE['txp_login'])) ? $_COOKIE['txp_login'] : '';
	
	if (!$txp_user) $txp_user = 'steffi';
		
	$WIN = array(
		'id'	  => 0,
		'content' => 'article',
		'table'	  => 'textpattern'
	);
	
	// retrieve_session_data();
	
	if (column_exists('textpattern','ParentID')) {
		define("ROOTNODEID",fetch("ID","textpattern","ParentID",0));
	} else {
		define("ROOTNODEID",0);
	}
	
	error_reporting(E_ALL & ~(defined('E_STRICT') ? E_STRICT : 0));
	@ini_set("display_errors","1");
	
	$textarray = load_lang(LANG);
	$theme = theme::init();
	$base  = (!isset($base) or !$base) ? hu.'admin/' : $base;

// -----------------------------------------------------------------------------
	
	include_once txpath.'/include/txp_admin_diag.php';
	include_once txpath.'/include/lib/txp_lib_misc.php';
	include_once txpath.'/utilities/include/utilities.php';

// -----------------------------------------------------------------------------
	
	$event = 'utilities';
	
	$action = gps('go','diagnostics');
	$clear  = gps('clear',0);
	
	$nocache = rand(100000,999999);
	
	if ($action == 'export_all') {
		include_once txpath.'/include/lib/txp_lib_export.php';
		include_once txpath.'/include/txp_content_file.php';
	}
	
	if ($action == 'import_all') {
		include_once txpath.'/include/lib/txp_lib_import.php';
		include_once txpath.'/include/lib/txp_class_MultiEdit.php';
		include_once txpath.'/include/lib/txp_lib_ContentSave.php';
	}
	
	if ($action == 'log_by_day') {
		include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
		include_once txpath.'/include/lib/txp_class_MultiEdit.php';
	}
	
	if ($action == 'rebuild_pages') {
		include_once txpath.'/include/txp_presentation_page.php';
	}
	
	if ($action == 'view_inspector' and $clear) {
		clear_inspector();
	}
	
// -----------------------------------------------------------------------------

	header('Content-type: text/html');

	if ($action == 'update_database') {
		define('TXP_UPDATE',true);
		define('SETUP',false);
		include_once txpath.'/update/_update.php'; 
		exit;
	}

	if ($action == 'tag_reference') {
		include_once txpath.'/utilities/include/tag_reference/index.php';
		exit;
	}
	
	if ($action == 'restore') {
		include_once txpath.'/utilities/include/restore/index.php';
		exit;
	}

	if ($action == 'custom_field_values') {
		include_once txpath.'/utilities/include/custom_fields/values.php';
		exit;
	}
	
	if ($action == 'custom_field_groups') {
		include_once txpath.'/utilities/include/custom_fields/groups.php';
		exit;
	}
	
	if ($PFX == 'hrp_') { 
		// $action = 'clean_article_body_html';
		// $action = 'make_excerpts';
	}
		
// -----------------------------------------------------------------------------

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Admin > Utilities</title>
	<meta name="generator" content="BBEdit 8.0" />
	<link type="text/css" rel="stylesheet" href="<?php echo $base; ?>utilities/css/lib/jquery.jscrollpane.css" media="all" />
	<link type="text/css" rel="stylesheet" rel="stylesheet" href="<?php echo $base; ?>utilities/css/global.css">
	<?php if ($action == 'update_database') { ?>
		<link type="text/css" rel="stylesheet" rel="stylesheet" href="<?php echo $base; ?>update/update.css">
	<?php } ?>	
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/jquery-1.5.2.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/jquery.mousewheel.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/jquery.jscrollpane.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/jquery-cookie/jquery.cookie.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/html.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/my.array.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/my.object.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/my.interval.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/global.js"></script>
	
	<?php if ($action == 'update_database') { ?>
		<script type="text/javascript" src="<?php echo $base; ?>update/update.js"></script>
	<?php } ?>	
	
	<style type="text/css">
		body.diagnostics 				li#diagnostics span.name,
		body.update_database 			li#update_database span.name,
		body.clear_caches 				li#clear_caches span.name,
		body.fix_posted_date 			li#fix_posted_date span.name,
		body.fix_parent_positions		li#fix_parent_positions span.name,
		body.update_cache_levels 		li#update_cache_levels span.name,
		body.rebuild_path_indexes		li#rebuild_path_indexes span.name,
		body.rebuild_pages				li#rebuild_pages span.name,
		body.update_image_count			li#update_image_count span.name,
		body.update_category_count		li#update_category_count span.name,
		body.update_file_summary 		li#update_file_summary,
		body.update_site_content_count 	li#update_site_content_count span.name,
		body.update_categories			li#update_categories span.name,
		body.renumarate_positions		li#renumarate_positions span.name,
		body.reorganize_images			li#reorganize_images span.name,
		body.reorganize_files			li#reorganize_files span.name,
		body.export_all					li#export_all span.name,
		body.import_all					li#import_all span.name,
		body.view_inspector				li#view_inspector span.name,
		body.create_site				li#.create_site span.name,
		body.create_download			li#.create_download span.name,
		body.log_by_day					li#.log_by_day span.name,
		body.fix_title_field			li#.fix_title_field.name
		{ 
			font-weight: bold;
		}
	</style>
</head>
<body class="<?php echo $action; ?>">

<div id="header">

	<img src="<?php echo $base; ?>txp_img/textpattern.gif" height="15" width="368" alt="textpattern" />

</div>

<div id="content">

	<table class="main">
	<tr>
		<td class="left">
			
			<div class="control">
			
				<div class="top"></div>
				
				<div id="control" class="scroll">
				
					<ul>
						
						<li id="diagnostics" class="item-1"><span class="name">Diagnostics</span> <span class="go"><a title="Diagnostics" href="index.php?go=diagnostics&<?php echo $nocache; ?>">Go</a></span></li>
				   <!-- <li id="crawler"><a href="include/crawler/index.php?<?php echo $nocache; ?>" title="Site Crawler">Site Crawler...</a></li> -->
						
						<?php if (table_exists('txp_tag')) { ?>
							<li id="tag_reference"><a href="index.php?go=tag_reference&<?php echo $nocache; ?>" title="Tag Reference">Tag Reference...</a></li>
						<?php } ?>
						
						<li id="restore"><a href="index.php?go=restore&<?php echo $nocache; ?>" title="Backup/Restore DB">Backup/Restore DB...</a></li>
				   <!-- <li id="change_username"><a href="include/change_username/index.php?<?php echo $nocache; ?>" title="Change User Name">Change User Name...</a></li> -->
						<li id="custom_field_values"><a href="index.php?go=custom_field_values&<?php echo $nocache; ?>" title="Custom Field Values">Custom Field Values...</a></li>
						
						<li id="view_inspector"><span class="name">Inspector</span> <span class="go"><a title="Inspector" href="index.php?go=view_inspector&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="update_database"><span class="name">Update DB</span> <span class="go"><a title="Update Database" href="index.php?go=update_database&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="clear_caches"><span class="name">Clear Caches</span> <span class="go"><a title="Clear All Caches" href="index.php?go=clear_caches&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="rebuild_path_indexes"><span class="name">Rebuild Path</span> <span class="go"><a title="Rebuild Path" href="index.php?go=rebuild_path_indexes&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="rebuild_pages"><span class="name">Rebuild Pages</span> <span class="go"><a title="Rebuild Pages" href="index.php?go=rebuild_pages&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="update_image_count"><span class="name">Update Image Count</span> <span class="go"><a title="Update Image Count" href="index.php?go=update_image_count&<?php echo $nocache; ?>">Go</a></span></li>
					<!-- <li id="update_category_count"><span class="name">Update Category Count</span> <span class="go"><a title="Update Category Count" href="index.php?go=update_category_count&<?php echo $nocache; ?>">Go</a></span></li> -->
						<li id="update_file_summary"><span class="name">Update File Summary</span> <span class="go"><a title="Update File Summary" href="index.php?go=update_file_summary&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="update_categories"><span class="name">Update Categories</span> <span class="go"><a title="Update Categories" href="index.php?go=update_categories&<?php echo $nocache; ?>">Go</a></span></li>
						
						<?php if (!$PFX and table_exists('txp_site')) { ?>
							<li id="update_site_content_count"><span class="name">Update Site Content Count</span> <span class="go"><a title="Update Site Content Count" href="index.php?go=update_site_content_count&<?php echo $nocache; ?>">Go</a></span></li>
							<li id="fix_title_field"><span class="name">Fix Title Field</span> <span class="go"><a title="Fix Title Field" href="index.php?go=fix_title_field&<?php echo $nocache; ?>">Go</a></span></li>				
						<?php } ?>
						
						<li id="renumarate_positions"><span class="name">Renumerate Positions</span> <span class="go"><a title="Renumerate Positions" href="index.php?go=renumarate_positions&<?php echo $nocache; ?>">Go</a></span></li>
						
						<?php if (is_dir($path_to_site.'/images/uploads')) { ?>
						<li id="reorganize_images"><span class="name">Reorganize Images</span> <span class="go"><a title="Reorganize Images" href="index.php?go=reorganize_images&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="reorganize_files"><span class="name">Reorganize Files</span> <span class="go"><a title="Reorganize Files" href="index.php?go=reorganize_files&<?php echo $nocache; ?>">Go</a></span></li>
						<?php } ?>
						
						<!--
						<li id="export_all"><span class="name">Export</span> <span class="go"><a title="Export" href="index.php?go=export_all&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="import_all"><span class="name">Import</span> <span class="go"><a title="Import" href="index.php?go=import_all&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="create_download"><span class="name">Create Download Archive</span> <span class="go"><a title="Create Download Archive" href="index.php?go=create_download&<?php echo $nocache; ?>">Go</a></span></li>
						<li id="log_by_day"><span class="name">Log by Day</span> <span class="go"><a title="Log by Day" href="index.php?go=log_by_day&<?php echo $nocache; ?>">Go</a></span></li>
						-->
					</ul>
					
				</div>
				
				<div class="footer"></div>
			
			</div>
			
		</td>
	
		<td class="right">
		
			<div class="console">
			
				<div id="console" class="scroll">
				
					<div class="pad">
						
						<?php if (function_exists($action)) { $action(); } ?>
						
					</div>
					
				</div>
				
			</div>
			
			<div class="footer">
				
				<?php if ($action == 'view_inspector') { ?>
				
					<a class="button" href="index.php?go=view_inspector&clear=1" title="Clear Console">Clear</a>
				
				<?php } ?>
			</div>
			
		</td>
	</tr>
	</table>

</div>

</body>
</html>

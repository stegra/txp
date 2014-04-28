<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen 
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_content_article.php $
$LastChangedRevision: 3246 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$statuses[4] = strong($statuses[4]);
	
	include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
	include_once txpath.'/include/lib/txp_lib_ContentEdit.php';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (!empty($event) and $event == 'article') {
		
		require_privs('article');
		
		$WIN = array(
			'event'   => 'article',
			'content' => 'article',
			'table'   => 'textpattern',
			'last'    => array('status'=>'','categories'=> array()),
			'image'   => array(
				'view'		=> 'min',
				'category'	=> 'latest_in_any_category',
				'unused'	=> 'on')
		);
		
		if (gps('save'))    $step = 'save';
		if (gps('publish')) $step = 'publish';
		
		switch (strtolower($step)) {
			case "list"		    : $step = 'article_edit';    break;
			case "create"		: $step = 'article_edit';    break;
			case "publish"		: $step = 'article_post';    break;
			case "edit"			: $step = 'article_edit';    break;
			case "save"			: $step = 'article_save';    break;
			
			case "add_image"    : $step = 'event_add_image'; 	break;
			case "show_image" 	: $step = 'event_show_image';	break;
			case "remove_image" : $step = 'event_remove_image';	break;
			case "save_image"   : $step = 'article_save_image';	break;
			
			case "add_file"		: $step = 'article_file_add';	 break;
			case "remove_file"  : $step = 'article_file_remove'; break;
			case "remove_field" : $step = 'remove_custom_field'; break;
			
			case "save_pane_state" : $step = 'article_save_pane_state'; break;
			case "note_save"  	   : $step = 'article_note_save'; break;
			
			default : $step = 'article_edit';
		}
	}

//------------------------------------------------------------------------------
	function article_post() 
	{
		global $WIN, $app_mode;
		
		$winid = $WIN['winid'];
		$ParentID = 0;
		
		if (isset($_SESSION['window'][$winid])) {
			
			if (isset($_SESSION['window'][$winid]['list'])) {
				
				$ParentID = $_SESSION['window'][$winid]['list']['id'];
			}
		}
		
		list($message,$ID,$Status) = content_create($ParentID);
		
		if (!$app_mode == 'async') {
			
			$GLOBALS['ID'] = $WIN['id'] = $ID;
			
			$message = array(get_status_message($Status,'posted'),0);
		
			article_edit($message);
		}
	}
	
//------------------------------------------------------------------------------
	function article_save($ID=0, $multiedit=null, $table='', $type='') 
	{
		global $WIN, $app_mode;
		
		$textpattern  = ($table) ? $table : $WIN['table'];
		$content_type = ($type) ? $type : $WIN['content'];
		
		plugin_callback(1,$ID);
		
		$ID = content_save($ID,$multiedit,$type,$table);
		
		plugin_callback(2,$ID);
		
		safe_delete("txp_content_value","text_val IS NULL OR text_val = ''");
		
		if ($app_mode == 'async') {
			
			echo "OK";
		
		} else {
			
			$GLOBALS['ID'] = $ID;
			$_GET['step']  = 'edit';
			
			article_edit(
				array(get_status_message($ID,'saved'),0), 
				false,$textpattern,$content_type);
		}
	}

//------------------------------------------------------------------------------
	function article_edit($message='', $concurrent=FALSE, $table='', $type='')
	{
		content_edit($message,$concurrent); 
	}

// -----------------------------------------------------------------------------
	function get_status_message($id,$action='posted')
	{
		switch (fetch("Status","textpattern","ID",$id)) {
			case 3: return gTxt("article_saved_pending");
			case 2: return gTxt("article_saved_hidden");
			case 1: return gTxt("article_saved_draft");
			default: return gTxt('article_'.$action);
		}
	}

// -----------------------------------------------------------------------------
	function article_save_pane_state()
	{
		global $event;
		$panes = array('textile_help', 'advanced', 'recent', 'more');
		$pane = gps('pane');
		if (in_array($pane, $panes))
		{
			set_pref("pane_{$event}_{$pane}_visible", (gps('visible') == 'true' ? '1' : '0'), $event, PREF_HIDDEN, 'yesnoradio', 0, PREF_PRIVATE);
			send_xml_response();
		} else {
			send_xml_response(array('http-status' => '400 Bad Request'));
		}
	}

// -----------------------------------------------------------------------------
// this is from txp1

	function article_note_save() 
	{	
		global $txp_user, $vars, $txpcfg;
		
		extract(get_prefs());
		$incoming = psa($vars);
		$out = array();
		
		$ID = $incoming['ID'];
		
		include_once txpath.'/lib/classTextile_mod.php';
		$textile = new TextileMod();
		
		$Body = trim($incoming['Body']);
		$Body_html = $textile->TextileThis($Body);
		$Body_html = clean($Body_html,'body');
		
		safe_update("textpattern", 
			"Body             = '".doSlash($Body)."',
			 Body_html        = '".doSlash($Body_html)."',
			 LastMod          =  now(),
			 LastModID        = '$txp_user'",
			"ID='$ID'"
		);
		
		update_lastmod($ID);
		
		$out['html'] = $Body_html;
		$out['text'] = $Body;
		
		if (!function_exists('json_encode')) include txpath.'/lib/txplib_json.php';

		echo json_encode($out);
	}
	
?>

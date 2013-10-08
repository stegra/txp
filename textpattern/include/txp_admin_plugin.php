<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_plugin.php $
$LastChangedRevision: 3203 $

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'plugin') {
	
		require_privs('plugin');

		$steps = array_merge($steps,array());
	}

// =============================================================================
	function plugin_list($message='')
	{
		global $EVENT, $WIN, $html, $app_mode, $smarty;
		
		// ---------------------------------------------------------------------
		
		if (!$WIN['columns']) {
			
			$WIN['columns'] = array(
					
				'Title'  	 => array('title' => 'Title',  	   	'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	 => array('title' => 'Image',  	   	'on' => 1, 'editable' => 0, 'pos' => 2),
				'Posted' 	 => array('title' => 'Posted', 	   	'on' => 1, 'editable' => 0, 'pos' => 3),
				'LastMod'    => array('title' => 'Modified',   	'on' => 0, 'editable' => 0, 'pos' => 4),
				'Name' 		 => array('title' => 'Name', 	   	'on' => 0, 'editable' => 1, 'pos' => 5),
				'Categories' => array('title' => 'Categories', 	'on' => 0, 'editable' => 1, 'pos' => 6),	
				'version'  	 => array('title' => 'Version', 	'on' => 1, 'editable' => 0, 'pos' => 7),
				'AuthorID'	 => array('title' => 'Author',		'on' => 1, 'editable' => 1, 'pos' => 8),
				'Type' 	 	 => array('title' => 'Type', 	   	'on' => 0, 'editable' => 0, 'pos' => 9),
				'Status'	 => array('title' => 'Status',		'on' => 1, 'editable' => 1, 'pos' => 10),
				'Position'   => array('title' => 'Position',	'on' => 1, 'editable' => 1, 'pos' => 11, 'short' => 'Pos.')
			);
		}
		
		// ---------------------------------------------------------------------
		// PAGE TOP
		
		$html = pagetop(gTxt('tab_file'), $message); 
		
		// ---------------------------------------------------------------------
		
		$plugins = new ContentList();
	
		$list = $plugins->getList();
		
		$html.= $plugins->viewList($list);
		
		// ---------------------------------------------------------------------
		
		save_session($EVENT);
		save_session($WIN);
		
	}
		
?>

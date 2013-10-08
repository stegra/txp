<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_list.php $
$LastChangedRevision: 3203 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	if ($event == 'category') {
		
		require_privs('category'); 
	}
		
// =============================================================================
	function category_list($message='')
	{	
		global $EVENT, $WIN, $html;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['columns']) {
		
			$WIN['columns'] = array(
				
				'Title'  	=> array('title' => 'Title',  	'on' => 1, 'editable' => 1, 'pos' => 1),
				'Plural'	=> array('title' => 'Plural',   'on' => 0, 'editable' => 1, 'pos' => 2),
				'Image'  	=> array('title' => 'Image',  	'on' => 1, 'editable' => 0, 'pos' => 3),
				'Posted' 	=> array('title' => 'Posted', 	'on' => 0, 'editable' => 0, 'pos' => 4),
				'LastMod'   => array('title' => 'Modified', 'on' => 0, 'editable' => 0, 'pos' => 5),
				'Name' 		=> array('title' => 'Name', 	'on' => 0, 'editable' => 1, 'pos' => 7),
				'Type' 		=> array('title' => 'Type', 	'on' => 0, 'editable' => 1, 'pos' => 8),
				'Class' 	=> array('title' => 'Class', 	'on' => 1, 'editable' => 1, 'pos' => 9),
				'Articles'  => array('title' => 'Articles', 'on' => 1, 'editable' => 0, 'pos' => 10),
				'Images'  	=> array('title' => 'Images', 	'on' => 1, 'editable' => 0, 'pos' => 11),
				'Files'  	=> array('title' => 'Files', 	'on' => 1, 'editable' => 0, 'pos' => 12),
				'Links'  	=> array('title' => 'Links', 	'on' => 1, 'editable' => 0, 'pos' => 13),
				'Comments'  => array('title' => 'Comments', 'on' => 0, 'editable' => 0, 'pos' => 14),
				'Customs'  	=> array('title' => 'Custom', 	'on' => 0, 'editable' => 0, 'pos' => 15),
				'AuthorID'	=> array('title' => 'Author', 	'on' => 0, 'editable' => 0, 'pos' => 16),
				'Status'	=> array('title' => 'Status',	'on' => 0, 'editable' => 0, 'pos' => 17),
				'Position'  => array('title' => 'Position', 'on' => 0, 'editable' => 0, 'pos' => 18, 'short' => 'Pos.')
			);
			
			if (!column_exists('txp_category','Plural')) {
				
				unset($WIN['columns']['Plural']);
				
				$pos = 1;
				
				foreach ($WIN['columns'] as $key => $item) {
					if ($item['pos']) $WIN['columns'][$key]['pos'] = $pos++;
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$main_title = safe_field("CONCAT(' &#8250; ',Title)",
			$WIN['table'],"ID = ".$WIN['id']." AND ParentID != 0");
		
		$html = pagetop(gTxt('categories').$main_title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$categories = new ContentList(); 
		$list = $categories->getList();
		
		$html.= $categories->viewList();
		
		save_session($EVENT);
		save_session($WIN);
	}
						
// -------------------------------------------------------------
	function category_multi_edit() 
	{	
		global $WIN;
		
		$method   = ps('edit_method');
		$selected = ps('selected',array());
		
		$tables = array(
			'article' 	=> 'textpattern',
			'image' 	=> 'txp_image',
			'file' 		=> 'txp_file',
			'link' 		=> 'txp_link'
		);
		
		$old = array(
			'Name'  => array(),
			'Class' => array()
		);
		
		// -----------------------------------------------------
		// PRE-PROCESS
		
		if ($method == 'save') {
			
			if (isset($_POST['Name'])) {
				$old['Name'] = safe_column(
						"ID,Name",
						"txp_category",
						"ID IN (".in($selected).")");
			}
			
			if (isset($_POST['Class'])) {
				$old['Class'] = safe_column(
						"ID,Class",
						"txp_category",
						"ID IN (".in($selected).")");
			}
		}
		
		// -----------------------------------------------------
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$selected  = $multiedit->selected;
		$changed   = $multiedit->changed;	
		
		// FIXME: $changed has all selected items, changed or not 
		
		// -----------------------------------------------------
		// POST-PROCESS
		
		if ($method == 'save') {
			
			$rows = safe_rows(
					"ID,Name,Class",
					"txp_category",
					"ID IN (".in($changed).")");
			
			foreach ($rows as $row) {
				
				extract($row);
				
				foreach ($tables as $type => $table) {
				 
					$table = "$table AS t JOIN txp_content_category AS c";
					
					$where = array(
						"t.ID = c.article_id",
						"c.name = '$Name'",
						"c.type = '$type'"
					);
					
					// class designation change
					
					if ($old['Class'] and isset($old['Class'][$ID])) {
						
						if ($old['Class'][$ID] != $Class) {
						
							if ($Class == 'no') {
								
								// if class designation has been removed
								// remove this from articles Class field
								
								$where[] = "t.Class = '$Name'";
								
								safe_update($table,"t.Class = ''",doAnd($where));
								
							} elseif ($Class == 'yes') {
								
								// if class designation has been added
								// add this to articles Class field that is empty
								
								$where[] = "t.Class = ''";
								
								safe_update($table,"t.Class = '$Name'",doAnd($where));
							}
						
							/* TODO: modify by_class column in txp_group 
									 table when class designation change */
						}
					}
				
					// name change
					
					if ($old['Name'] and isset($old['Name'][$ID])) {
						
						$oldName = $old['Name'][$ID];
						
						if (strlen($oldName) and $oldName != $Name) {
						
							safe_update("txp_content_category",
									"name = '$Name'",
									"name = '".$oldName."' AND type = '$type'");
							
							safe_update("txp_group",
									"by_category = '$Name'",
									"by_category = '".$oldName."' 
									 AND type = '$type'");
							
							safe_update("txp_group",
									"by_class = '$Name'",
									"by_class = '".$oldName."'
									 AND type = '$type'");
						}
					}
				}
			}
			
			$selected = array();
		}
		
		$WIN['checked'] = $selected;
		
		category_list($message);
	}

// -------------------------------------------------------------
	function category_edit_type(&$in,&$html) 
	{
		$html[1]['body']    = str_replace('>'.gTxt('body').'<','>'.gTxt('description').'<',$html[1]['body']);
		$html[1]['excerpt'] = '';
		$html[1]['author']  = str_replace(gTxt('posted_by'),gTxt('added_by'),$html[1]['author']);
	}

//------------------------------------------------------------------------------
	function category_save($ID=0, $multiedit=null, $table='', $type='') 
	{
		content_save($ID,$multiedit);
		
 		$_GET['step'] = 'edit';
		
		event_edit();
	}
	
//------------------------------------------------------------------------------
	function category_post() 
	{
		$parent = gps('parent');
		$title  = gps('title');
		
		$title = preg_split('/\n/',$title);
		
		foreach($title as $key => $value) {
		
			$value = trim($value);
			
			if (strlen($value)) {
				
				content_create($parent,array('Title'=>$value));
			}
		}
	}

?>

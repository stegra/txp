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
	
	if ($event == 'custom') {
		
		require_privs('custom');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$custom_field_types = array(
			'text'		=> 'Text',
			'number'	=> 'Number',
			'date'		=> 'Date',
			'time'		=> 'Time'
		);
	}
	
// =============================================================================
	function custom_list($message='')
	{	
		global $EVENT, $WIN, $html;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['criteria']) {
		
			$WIN['criteria'] = array(
				
				'keywords'	=>	'',
				'section'	=>	'all',
				'category1'	=>	'all',
				'category2'	=>	'all',
				'author'	=>	'all',
				'status'	=>	'all',
				'position'	=>	'all',
				'open'		=>	'0'
			);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['columns']) {
		
			$WIN['columns'] = array(
				
				'Title'  	 => array('title' => 'Title',  	   'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	 => array('title' => 'Image',	   'on' => 1, 'editable' => 0, 'pos' => 2),
				'Name' 		 => array('title' => 'Name', 	   'on' => 0, 'editable' => 1, 'pos' => 3),
				'Type'  	 => array('title' => 'Type',  	   'on' => 1, 'editable' => 1, 'pos' => 4),
				'input'  	 => array('title' => 'Input',  	   'on' => 1, 'editable' => 1, 'pos' => 5),
				'Body'  	 => array('title' => 'Options',    'on' => 0, 'editable' => 1, 'pos' => 6),
				'default'  	 => array('title' => 'Default',    'on' => 0, 'editable' => 1, 'pos' => 7),
				'Class' 	 => array('title' => 'Class', 	   'on' => 0, 'editable' => 1, 'pos' => 8),
				'Categories' => array('title' => 'Categories', 'on' => 0, 'editable' => 1, 'pos' => 9),
				'Posted' 	 => array('title' => 'Posted', 	   'on' => 1, 'editable' => 0, 'pos' => 10),
				'LastMod'    => array('title' => 'Modified',   'on' => 0, 'editable' => 0, 'pos' => 11),
				'AuthorID'	 => array('title' => 'Author', 	   'on' => 0, 'editable' => 1, 'pos' => 12),
				'Status'	 => array('title' => 'Status',	   'on' => 1, 'editable' => 1, 'pos' => 13),
				'Set'  		 => array('title' => 'Used', 	   'on' => 1, 'editable' => 0, 'pos' => 14, 'sel' => 'NULL'),
				'Position'   => array('title' => 'Position',   'on' => 0, 'editable' => 1, 'pos' => 15, 'short' => 'Pos.')
			);
			
			$WIN['columns']['input']['options'] = array(
				'textfield'	  => gTxt('input_textfield'),
				'textarea'	  => gTxt('input_textarea'),
				'select'	  => gTxt('input_select'),
				'selectgroup' => gTxt('input_selectgroup'),
				'radio'		  => gTxt('input_radio'),
				'checkbox'	  => gTxt('input_checkbox'),
				'date'		  => gTxt('input_date'),
				'time'		  => gTxt('input_time'),
				'color'		  => 'Color',
				'none'		  => gTxt('input_none')
			);
			
			$WIN['columns']['Type']['options'] = array(
				'folder'	=> 'Group',
				'text'		=> 'Text',
				'number'	=> 'Number',
				'date'		=> 'Date',
				'time'		=> 'Time'
			);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$main_title = safe_field("CONCAT(' &#8250; ',Title)",
			$WIN['table'],"ID = ".$WIN['id']." AND ParentID != 0");
		
		$html = pagetop('Custom'.$main_title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$fields = new ContentList();
		$list = $fields->getList();
		
		foreach ($list as $key => $item) {
			
			if ($item['Type'] != 'folder') {
			
				$id = $item['ID'];
				$count = safe_count("txp_content_value","field_id = '$id' AND status = 1");
				
				$list[$key]['Set'] = ($count) ? $count.'x' : 'None';
			} 
			
			if ($item['Type'] == 'folder') {
			
				$list[$key]['no_edit'] = array('input');
			}
		} 
		
		$html.= $fields->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

// -------------------------------------------------------------
	function custom_multi_edit() 
	{	
		global $WIN;
		
		$method   = ps('edit_method');
		$selected = ps('selected',array());
		
		// -----------------------------------------------------
		// PRE-PROCESS
		
		// -----------------------------------------------------
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$selected  = $multiedit->selected;
		$changed   = $multiedit->changed;	
		
		// -----------------------------------------------------
		// POST-PROCESS
		
		if (in_list($method,'save,move')) {
			
			// FIXME: only save new should append field
			// TODO: remove from group when field is moved out of a folder
			
			foreach ($changed as $id) {
				
				$ParentID = fetch("ParentID","txp_custom","ID",$id);
				
				if ($ParentID != ROOTNODEID) {
					
					if (append_custom_field($id,$ParentID,'textpattern','article')) {
						
						apply_custom_fields(0,$id,'',0,'textpattern');
					}
				}
			}
			
			$selected = array();
		}
		
		if ($method == 'trash') {
			
			include_once txpath.'/include/lib/txp_lib_custom_v4.php';
			
			foreach ($changed as $id) {
					
				remove_custom_field($id,'textpattern');
			}
		}
		
		if ($method == 'untrash') {
			
			include_once txpath.'/include/lib/txp_lib_custom_v4.php';
			
			foreach ($changed as $id) {
				
				restore_custom_field($id,'textpattern');
			}
		}
		
		$WIN['checked'] = $selected;
		
		custom_list($message);
	}

//-------------------------------------------------------------
	function custom_save($ID=0,$multiedit=null)
	{
		global $WIN, $vars, $txptagtrace, $app_mode;
		
		$vars[] = 'default';
		$vars[] = 'input';
		
		$ID 	 = ($ID) ? $ID : assert_int(gps('ID',0));
		$name 	 = gps('Name');
		$options = gps('Body');
		$input   = gps('input');
		
		if (content_save($ID,$multiedit) !== false) {
			
			if ($ID and in_list($input,'select,selectgroup,checkbox,radio')) {
				
				$path = '';
				
				if (strlen($options)) {
					
					if (preg_match('/<txp:article/',$options)) {
						
						$path_pattern = "[a-z0-9\*\/\-\$\s\,]+";
						
						if (preg_match('/\bpath="('.$path_pattern.')"/',$options,$matches)) {
							$path = $matches[1];
						}
						
						include_once txpath.'/publish/lib/publish.php';
						
						$WIN['table'] = 'textpattern';
						
						$options = parse($options,'txp','\w+','processOption');
						
						$WIN['table'] = 'txp_custom';
					}
				}
				
				$label = 0;
				$options = explode(n,$options);
				
				foreach($options as $key => $option) {
					$option = preg_split('/\:/',$option,2);
					$options[$key] = array_shift($option);
					if (count($option)) {
						$label = 1;
						$options[$key] .= ':'.array_shift($option);
					}
				}
				
				$options = doSlash(implode(n,$options));
				
				safe_update("txp_custom","Body_html = '$options', label = $label","ID = $ID");
				
				if ($path) {
					
					$row = safe_row("by_path","txp_group","field_id = $ID AND `type` = 'options'");
					
					if ($row) { 
						
						if ($row['by_path'] != $path) {
							safe_update("txp_group",
								"by_path = '$path', last_mod = NOW()",
								"field_id = $ID AND `type` = 'options'");
						}
					
					} else {
						
						safe_insert("txp_group",
							"`type` = 'options', 
							 field_id = $ID, 
							 field_name = '$name',
							 by_path = '$path',
							 status = 'active',
							 last_mod = NOW()"
						);
					}
				}
			}
			
			// - - - - - - - - - - - - - - - - - - -
			
			if (!$app_mode == 'async') {
			
				$GLOBALS['ID'] = $ID;
				
				event_edit();
			}
		}
	}

//------------------------------------------------------------------------------
	function processOption($tag,$atts,$thing = NULL,$namespace='txp') {
		
		global $txp_current_atts;
		
		$out = '';
		
		if ($tag == 'article') {
			
			$txp_current_atts = $atts = splat($atts);
			
			if (!$thing) $thing = "<txp:name/>:<txp:title/><txp:n/>";
			
			$out = doArticles($atts,1,$thing);
		}
		
		return $out;
	}
		
// =============================================================================
	function custom_search_form($crit, $method)
	{
		$methods = array(
			'id'				 => gTxt('ID'),
			'title_body_excerpt' => gTxt('title_body_excerpt'),
			'section'	 => gTxt('section'),
			'categories' => gTxt('categories'),
			'keywords'	 => gTxt('keywords'),
			'status'	 => gTxt('status'),
			'author'	 => gTxt('author'),
			'article_image' => gTxt('article_image'),
			'posted'	 => gTxt('posted'),
			'lastmod'	 => gTxt('article_modified')
		);

		return search_form('list', 'list', $crit, $methods, $method, 'title_body_excerpt');
	}

// -------------------------------------------------------------
	function custom_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changesection'   => gTxt('changesection'),
			'changecategory1' => gTxt('changecategory1'),
			'changecategory2' => gTxt('changecategory2'),
			'changestatus'    => gTxt('changestatus'),
			'changecomments'  => gTxt('changecomments'),
			'changeauthor'    => gTxt('changeauthor'),
			'delete'          => gTxt('delete'),
		);

		if (has_single_author('textpattern', 'AuthorID'))
		{
			unset($methods['changeauthor']);
		}

		if(!has_privs('article.delete.own') && !has_privs('article.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('list', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------
	function get_criteria() 
	{
		global $WIN;
		
		$vars = array(
			'keywords',
			'section',
			'category1',
			'category2',
			'author',
			'status',
			'position'
		);
				
		extract(gpsa($vars));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (empty($section)) {
			
			return $WIN['criteria'];
		}
		
		$criteria = array(
			'keywords'	=>	$keywords,
			'section'	=>	$section,
			'category1'	=>	$category1,
			'category2'	=>	$category2,
			'author'	=>	$author,
			'status'	=>	$status,
			'position'	=>	$position
		);
		
		return $WIN['criteria'] = $criteria;
	}

// -------------------------------------------------------------
	function custom_edit_type(&$in,&$html) 
	{
		global $custom_field_types;
		
		$type = $in['Type'];
		
		if (isset($custom_field_types[$type])) {
			
			custom_edit_field($in,$html);
		}
	}

//-------------------------------------------------------------
	function custom_edit_field(&$in,&$html)
	{
		global $custom_field_types, $smarty;
		
		extract($in);
		
		$types = array_merge(array('folder'=>'Group'),$custom_field_types);
		$types = selectInput('Type',$types,$Type,'','','Type');
		
		$smarty->assign('name',$Name);
		$smarty->assign('is_not_folder',($Type != 'folder'));
		$smarty->assign('type_pop',$types);
		$smarty->assign('input',$input);
		$smarty->assign('input_pop',custom_fields_input_pop('input', $input));
		$smarty->assign('input_help',popHelp('custom_input'));
		$smarty->assign('default',$default);
		$smarty->assign('default_help',popHelp('custom_default'));

		$out = $smarty->fetch('custom/custom_edit_field.tpl');
		
		$html[0]['special']  = '<div class="event-group1">'.n.$out.n.'</div>';
		$html[1]['body']     = str_replace('>'.gTxt('body').'<','>Options<',$html[1]['body']);
		$html[1]['excerpt']  = str_replace('>'.gTxt('excerpt').'<','>Help Text<',$html[1]['excerpt']);
		$html[1]['author']   = str_replace(gTxt('posted_by'),gTxt('added_by'),$html[1]['author']);
	}

//-------------------------------------------------------------

    function custom_fields_input_pop($name, $val)
    {
        $vals = array(
            'textfield'	  => gTxt('input_textfield'),
            'textarea'	  => gTxt('input_textarea'),
            'select'	  => gTxt('input_select'),
            'selectgroup' => gTxt('input_selectgroup'),
            'radio'		  => gTxt('input_radio'),
            'checkbox'	  => gTxt('input_checkbox'),
            'date'		  => gTxt('input_date'),
            'time'		  => gTxt('input_time'),
            'color'		  => 'Color',
            'none'		  => gTxt('input_none')
        );

        return selectInput($name, $vals, $val, '', '', $name);
    }

?>

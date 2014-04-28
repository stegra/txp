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
	
	if ($event == 'list') {
		
		require_privs('article');
	}
	
// =============================================================================
	function list_list($message='')
	{	
		global $EVENT, $WIN, $html, $prefs;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['criteria']) {
		
			$WIN['criteria'] = array(
				
				'keywords'	=>	'',
				'section'	=>	'all',
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
				'Image'  	 => array('title' => 'Image',  	   'on' => 1, 'editable' => 0, 'pos' => 2),
				'Posted' 	 => array('title' => 'Posted', 	   'on' => 1, 'editable' => 0, 'pos' => 3),
				'LastMod'    => array('title' => 'Modified',   'on' => 0, 'editable' => 0, 'pos' => 4),
				'Name' 		 => array('title' => 'Name', 	   'on' => 0, 'editable' => 1, 'pos' => 5),
				'Section' 	 => array('title' => 'Section',    'on' => 0, 'editable' => 0, 'pos' => 6),
				'Class' 	 => array('title' => 'Class', 	   'on' => 0, 'editable' => 1, 'pos' => 7),
				'Categories' => array('title' => 'Categories', 'on' => 1, 'editable' => 1, 'pos' => 8),
				'Language'   => array('title' => 'Language',   'on' => 0, 'editable' => 1, 'pos' => 9),
				'File'  	 => array('title' => 'File',  	   'on' => 0, 'editable' => 0, 'pos' => 10),
				'Play'  	 => array('title' => 'Play',  	   'on' => 0, 'editable' => 0, 'pos' => 11),
				'AuthorID'	 => array('title' => 'Author', 	   'on' => 1, 'editable' => 1, 'pos' => 12),
				'Status'	 => array('title' => 'Status',	   'on' => 1, 'editable' => 1, 'pos' => 13),
				'ID'	 	 => array('title' => 'ID',	   	   'on' => 0, 'editable' => 0, 'pos' => 14),
				'Position'   => array('title' => 'Position',   'on' => 1, 'editable' => 1, 'pos' => 15, 'short' => 'Pos.')
			);
			
			$filename = "(SELECT CONCAT(f.Name,f.ext) FROM txp_file AS f WHERE t.FileID = f.ID)";
			
			$WIN['columns']['File']['sel'] = $filename;
			$WIN['columns']['Play']['sel'] = $filename;
			
			$WIN['columns']['FileExt'] = array(
				'sel'      => "(SELECT f.ext FROM txp_file AS f WHERE t.FileID = f.ID)",
				'on'  	   => 1,
				'editable' => 0,
				'pos'      => 0
			);
			
			if (!column_exists($WIN['table'],'Language')) {
			
				unset($WIN['columns']['Language']);
			
			} elseif (isset($prefs['languages']) and $prefs['languages']) {
				
				$WIN['columns']['Language']['on'] = 1;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$main_title = fetch("Title",$WIN['table'],"ID",$WIN['id']);
		
		$html = pagetop(gTxt('tab_list').' &#8250; '.$main_title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$articles = new ContentList(); 
		$list = $articles->getList();
		
		$html.= $articles->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

// =============================================================================
	function list_filter()
	{	
		global $WIN;
		
		if (isset($_GET['image'])) {
		
			$WIN['filter']['image'] = array(
				'search' => assert_int(gps('image',0)),
				'result' => ''
			);
		}		
		
		list_list();
	}
						
// =============================================================================
	function list_search_form($crit, $method)
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
	function list_multiedit_form($page, $sort, $dir, $crit, $search_method)
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
			'author'	=>	$author,
			'status'	=>	$status,
			'position'	=>	$position
		);
		
		return $WIN['criteria'] = $criteria;
	}

?>

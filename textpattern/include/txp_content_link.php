<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_link.php $
$LastChangedRevision: 3203 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	if ($event == 'link') {
	
		require_privs('link');
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$custom_field_types = array(
			'link' => 'Link'
		);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		/* 
		$vars = array(
			'category', 'url', 'linkname', 'linksort', 'description', 'id'
		);
		*/
	}

// =============================================================================
	function link_list($message='')
	{
		global $EVENT, $WIN, $html, $smarty;

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['columns']) {
			
			$WIN['columns'] = array(
				
				'Title'  	 => array('title' => 'Title',  	   	'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	 => array('title' => 'Image',  	   	'on' => 1, 'editable' => 0, 'pos' => 2),
				'Posted' 	 => array('title' => 'Posted', 	   	'on' => 1, 'editable' => 0, 'pos' => 3),
				'url' 	 	 => array('title' => 'URL', 	   	'on' => 1, 'editable' => 1, 'pos' => 4),
				'LastMod'    => array('title' => 'Modified',   	'on' => 0, 'editable' => 0, 'pos' => 5),
				'Name' 		 => array('title' => 'Name', 	   	'on' => 0, 'editable' => 1, 'pos' => 6),
				'Categories' => array('title' => 'Categories', 	'on' => 1, 'editable' => 1, 'pos' => 7),	
				'AuthorID'	 => array('title' => 'Author',		'on' => 1, 'editable' => 1, 'pos' => 8),
				'Type'	 	 => array('title' => 'Type',		'on' => 0, 'editable' => 1, 'pos' => 10),
				'Status'	 => array('title' => 'Status',		'on' => 0, 'editable' => 1, 'pos' => 11),
				'Position'   => array('title' => 'Position',	'on' => 0, 'editable' => 1, 'pos' => 12, 'short' => 'Pos.')
			);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$html = pagetop(gTxt('link'), $message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$links = new ContentList();
		
		$list = $links->getList();
		
		foreach ($list as $key => $item) {
			
			if (isset($item['url'])) {
			
				$list[$key]['url'] = preg_replace('/^http:\/\//','',$item['url']);
			}
		}
		
		$html.= $links->viewList($list);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		save_session($EVENT);
		save_session($WIN);
	}

// -------------------------------------------------------------
	function link_post()
	{
		global $txpcfg, $vars, $txp_user;

		$varray = gpsa($vars);

		extract(doSlash($varray));

		if ($linkname === '' && $url === '' && $description === '')
		{
			event_edit();
			return;
		}

		if (!has_privs('link.edit.own'))
		{
			event_edit(gTxt('restricted_area'));
			return;
		}

		if (!$linksort) $linksort = $linkname;

		$q = safe_insert("txp_link",
		   "category    = '$category',
			date        = now(),
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description',
			author		= '$txp_user'"
		);

		$GLOBALS['ID'] = mysql_insert_id( );

		if ($q)
		{
			//update lastmod due to link feeds
			update_lastmod();

			$message = gTxt('link_created', array('{name}' => $linkname));

			event_edit($message);
		}
	}

//------------------------------------------------------------------------------
	function link_save($ID=0, $multiedit=null, $table='', $type='') 
	{
		global $WIN,$vars;
		
		$textpattern  = ($table) ? $table : $WIN['table'];
		$content_type = ($type) ? $type : $WIN['content'];
		
		$vars[] = 'url';
		
		content_save($ID,$multiedit,$type,$table);
		
 		$_GET['step'] = 'edit';
		
		event_edit();
	}

// -------------------------------------------------------------
	function link_save_old()
	{
		global $txpcfg, $vars, $txp_user;

		$varray = gpsa($vars);

		extract(doSlash($varray));

		$id = assert_int($id);

		if ($linkname === '' && $url === '' && $description === '')
		{
			event_edit();
			return;
		}

		$author = fetch('author', 'txp_link', 'id', $id);
		if (!has_privs('link.edit') && !($author == $txp_user && has_privs('link.edit.own')))
		{
			event_edit(gTxt('restricted_area'));
			return;
		}

		if (!$linksort) $linksort = $linkname;

		$rs = safe_update("txp_link",
		   "category    = '$category',
			url         = '".trim($url)."',
			linkname    = '$linkname',
			linksort    = '$linksort',
			description = '$description',
			author 		= '$txp_user'",
		   "id = $id"
		);

		if ($rs)
		{
			update_lastmod();

			$message = gTxt('link_updated', array('{name}' => doStrip($linkname)));

			event_edit($message);
		}
	}

// -------------------------------------------------------------
	function link_edit_type(&$in,&$html) 
	{	
		
		$html[1]['title']  .= '<p class="title"><span class="title"><label for="url">'.gTxt('url').'</label></span><br/>'.
			'<input type="text" id="url" name="url" value="'.$in['url'].'" class="edit" size="40" tabindex="2" /></p>';

		$html[1]['body']    = str_replace('>'.gTxt('body').'<','>'.gTxt('description').'<',$html[1]['body']);
		$html[1]['excerpt'] = '';
		$html[1]['author']  = str_replace(gTxt('posted_by'),gTxt('added_by'),$html[1]['author']);
	}
	
// =============================================================================
	function link_search_form($crit, $method)
	{
		$methods =	array(
			'id'			=> gTxt('ID'),
			'name'			=> gTxt('link_name'),
			'description' 	=> gTxt('description'),
			'category'		=> gTxt('link_category'),
			'author'		=> gTxt('author')
		);

		return search_form('link', 'link_edit', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------
	function link_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changecategory' => gTxt('changecategory'),
			'changeauthor' => gTxt('changeauthor'),
			'delete' => gTxt('delete')
		);

		if (has_single_author('txp_link'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('link.delete.own') && !has_privs('link.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('link', $methods, $page, $sort, $dir, $crit, $search_method);
	}

//--------------------------------------------------------------
	function linkcategory_popup($cat = '')
	{
		return event_category_popup('link', $cat, 'link-category');
	}

//--------------------------------------------------------------
	function link_edit_link_main($item,$html)
	{	
		extract($item);
		
		$html['title'] .= n.n.'<p><span class="url">'
			.'<label for="url">'.gTxt('url').'</label>'.br
			.'</span>'
			.'<input type="text" id="url" name="url" value="'.escape_title($url).'" class="edit" size="40" tabindex="2" />';
		
		return $html;
	}
	
?>
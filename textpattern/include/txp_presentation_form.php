<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_form.php $
$LastChangedRevision: 3260 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');

	if ($event == 'form') {
	
		require_privs('form');
		
		$steps = array_merge($steps,array(
			'line_numbers'
		));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$form_types = array(
			'article',
			'category',
			'comment',
	 		'file',
	 		'link',
	 		'misc',
	 		'section',
	 		'image',
	 		'xsl'
	 	);
	 		
		$essential_forms = array(
			'comments',
			'comments_display',
			'comment_form',
			'default',
			'Links',
			'files'
		);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		include_once txpath.'/include/txp_presentation_page.php';
	}

//------------------------------------------------------------------------------

	function form_list($message='')
	{
		global $EVENT, $WIN, $html;
		
		if (!$WIN['columns']) {
		
			$WIN['columns'] = array(
				
				'Title'  	 => array('title' => 'Title',  	   'on' => 1, 'editable' => 1, 'pos' => 1),
				'Posted' 	 => array('title' => 'Posted', 	   'on' => 1, 'editable' => 0, 'pos' => 2),
				'LastMod'    => array('title' => 'Modified',   'on' => 0, 'editable' => 0, 'pos' => 3),
				'Name' 		 => array('title' => 'Name', 	   'on' => 0, 'editable' => 1, 'pos' => 4),
				'Type' 	 	 => array('title' => 'Type', 	   'on' => 1, 'editable' => 1, 'pos' => 5),
				'Class' 	 => array('title' => 'Class', 	   'on' => 0, 'editable' => 1, 'pos' => 6),
				'Categories' => array('title' => 'Categories', 'on' => 0, 'editable' => 1, 'pos' => 7),
				'AuthorID'	 => array('title' => 'Author', 	   'on' => 1, 'editable' => 1, 'pos' => 8),
				'Status'	 => array('title' => 'Status',	   'on' => 1, 'editable' => 1, 'pos' => 9),
				'Position'   => array('title' => 'Position',   'on' => 1, 'editable' => 1, 'pos' => 10, 'short' => 'Pos.')
			);
		}
		
		$main_title = safe_field("CONCAT(' &#8250; ',Title)",
			$WIN['table'],"ID = ".$WIN['id']." AND ParentID != 0");
		
		$html = pagetop(gTxt('forms').$main_title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (safe_count('txp_form',"Name LIKE '%\_%'")) {
			safe_update('txp_form',"Name = REPLACE(Name,'_','-')","1=1");
		};
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$forms = new ContentList(); 
		$list = $forms->getList();
		
		/* foreach ($list as $key => $item) {
			
			if ($item['ParentID'] == 0 and $item['Title'] == 'Forms') {
				$list[$key]['Title'] = "Page Elements";
			} 
		} */
		
		$html.= $forms->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

// -------------------------------------------------------------
/* 	function form_list($curname)
	{
		global $step,$essential_forms;
		$out[] = startTable('list');
		$out[] = tr(tda(sLink('form','form_create',gTxt('create_new_form')),' colspan="3" style="height:30px"'));

		$out[] = assHead('form','type','');

		$methods = array('delete'=>gTxt('delete'));


		$rs = safe_rows_start("*", "txp_form", "1 order by type asc, name asc");

		if ($rs) {
			while ($a = nextRow($rs)){
				extract($a);
					$editlink = ($curname!=$name)
					?	eLink('form','form_edit','name',$name,$name)
					:	htmlspecialchars($name);
					$modbox = (!in_array($name, $essential_forms))
					?	'<input type="checkbox" name="selected_forms[]" value="'.$name.'" />'
					:	sp;
				$out[] = tr(td($editlink).td(small($type)).td($modbox));
			}

			$out[] = endTable();
			$out[] = eInput('form').sInput('form_multi_edit');
			$out[] = graf(selectInput('edit_method',$methods,'',1).sp.gTxt('selected').sp.
				fInput('submit','form_multi_edit',gTxt('go'),'smallerbox')
				, ' align="right"');

			return form( join('',$out),'',"verify('".gTxt('are_you_sure')."')" );
		}
	}
*/
// -------------------------------------------------------------
	function form_create()
	{
		form_edit();
	}

// -------------------------------------------------------------
	function form_multi_edit() 
	{	
		global $WIN, $essential_forms;
		
		$method   = ps('edit_method');
		$selected = ps('selected',array());
		
		// -----------------------------------------------------
		// PRE-PROCESS
		
		if ($method == 'trash') {
			
			$selected = array_map('assert_int', $selected);
			
			foreach ($selected as $key => $id) {
				
				$name = fetch("Name","txp_form","ID",$id);
				
				if (in_array($name, $essential_forms)) { 
				
					unset($selected[$key]);
				}
			}
			
			if (!count($selected)) {
				
				return form_list();
			}
		}
		
		// -----------------------------------------------------
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$selected  = $multiedit->selected;
		$changed   = $multiedit->changed;	
		
		// -----------------------------------------------------
		// POST-PROCESS
		
		// -----------------------------------------------------
		
		$WIN['checked'] = $selected;
		
		form_list($message);
	}
	
//------------------------------------------------------------------------------

	function form_edit($message='')
	{	
		global $WIN, $event, $html, $smarty;
		
		$id = gps('id',0);
		$id = assert_int($id);
		
		$rs = safe_row("*","txp_form", "ID = '$id' AND Trash = 0");
		
		if (!$rs) return;
		
		extract($rs);
		
		if (!has_privs('form.edit') && !($author == $txp_user && has_privs('form.edit.own')))
		{
			form_list(gTxt('restricted_area'));
			return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$html = pagetop(gTxt('forms').' &#8250; '.$Title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$Body  = preg_replace("/\&(\#[0-9]+\;)/","&amp;$1",doStrip($Body));
		// $Title = (!$ParentID and $Title == 'Forms') ? 'Page Elements' : $Title; 
		
		$list = form_edit_list();
		$path = new Path($ID);
		
		$smarty->assign('id',$ID);
		$smarty->assign('winid',$WIN['winid']);
		$smarty->assign('event',$event);
		$smarty->assign('title',$Title);
		$smarty->assign('path',$path->getList('Title','/'));
		$smarty->assign('name',$Name);
		$smarty->assign('type',$Type);
		$smarty->assign('html',$Body);
		$smarty->assign('list',$list);
		$smarty->assign('total',count($list));
		
		$html .= $smarty->fetch('presentation/edit.tpl');
	}

//------------------------------------------------------------------------------

	function form_edit_list()
	{
		rebuild_txp_tree();
		
		$rows = safe_rows_tree("0","ID,Name,Type,Level","txp_form");
		
		return $rows;
	}
		
// -------------------------------------------------------------
/*	function form_edit($message='')
	{
		global $step,$essential_forms;
		pagetop(gTxt('edit_forms'),$message);

		extract(gpsa(array('Form','name','type')));
		$name = trim(preg_replace('/[<>&"\']/', '', $name));

		if ($step=='form_create') {
			$inputs = fInput('submit','savenew',gTxt('save_new'),'publish').
				eInput("form").sInput('form_save');
		} else {
			$name = (!$name or $step=='form_delete') ? 'default' : $name;
			$rs = safe_row("*", "txp_form", "name='".doSlash($name)."'");
//			if ($rs)
 {
				extract($rs);
				$inputs =  fInput('submit','save',ucwords(gTxt('saved')),'publish saved',gTxt('save'),'','','','save').
					eInput("form").
					sInput('form_save').
					hInput('scroll',0,'scroll').
					hInput('oldname',$name);
			}
		}

		if (!in_array($name, $essential_forms))
			$changename = graf(gTxt('form_name').br.fInput('text','name',$name,'edit','','',15));
		else
			$changename = graf(gTxt('form_name').br.tag($name, 'em').hInput('name',$name));
			
		$out =
			tag(
			
				tag(
					hed(gTxt('tagbuilder'), 2).

					hed('<a href="#article-tags">'.gTxt('articles').'</a>'.
						sp.popHelp('form_articles'), 3, ' class="plain lever expanded"').
						'<div id="article-tags" class="toggle on" style="display:block">'.popTagLinks('article').'</div>'.

					hed('<a href="#link-tags">'.gTxt('links').'</a>'.
						sp.popHelp('form_place_link'), 3, ' class="plain lever"').
						'<div id="link-tags" class="toggle" style="display:none">'.popTagLinks('link').'</div>'.

					hed('<a href="#comment-tags">'.gTxt('comments').'</a>'.
						sp.popHelp('form_comments'), 3, ' class="plain lever"').
						'<div id="comment-tags" class="toggle" style="display:none">'.popTagLinks('comment').'</div>'.

					hed('<a href="#comment-detail-tags">'.gTxt('comment_details').'</a>'.
						sp.popHelp('form_comment_details'), 3, ' class="plain lever"').
						'<div id="comment-detail-tags" class="toggle" style="display:none">'.popTagLinks('comment_details').'</div>'.

					hed('<a href="#comment-form-tags">'.gTxt('comment_form').'</a>'.
						sp.popHelp('form_comment_form'), 3, ' class="plain lever"').
						'<div id="comment-form-tags" class="toggle" style="display:none">'.popTagLinks('comment_form').'</div>'.

					hed('<a href="#search-result-tags">'.gTxt('search_results_form').'</a>'.
						sp.popHelp('form_search_results'), 3, ' class="plain lever"').
						'<div id="search-result-tags" class="toggle" style="display:none">'.popTagLinks('search_result').'</div>'.

					hed('<a href="#file-tags">'.gTxt('file_download_tags').'</a>'.
						sp.popHelp('form_file_download_tags'), 3, ' class="plain lever"').
						'<div id="file-tags" class="toggle" style="display:none">'.popTagLinks('file_download').'</div>'.

					hed('<a href="#category-tags">'.gTxt('category_tags').'</a>'.
						sp.popHelp('form_category_tags'), 3, ' class="plain lever"').
						'<div id="category-tags" class="toggle" style="display:none">'.popTagLinks('category').'</div>'.

					hed('<a href="#section-tags">'.gTxt('section_tags').'</a>'.
						sp.popHelp('form_section_tags'), 3, ' class="plain lever"').
						'<div id="section-tags" class="toggle" style="display:none">'.popTagLinks('section').'</div>'
						
				,'div',' class="column left"').
				
				tag(
					form_list($name)
				,'div',' class="column right"').
				
				tag(
					'<form action="index.php" method="post">'.comment_line().comment('TEXTAREA').n.
						'<div id="box"><div id="scrollpane"><textarea id="code" class="code" name="Form" cols="84" rows="36">'.$Form.'</textarea></div></div>'.comment_line().

					$changename.

					graf(gTxt('form_type').br.
						formtypes($type)).
					// graf(gTxt('only_articles_can_be_previewed')).
					// fInput('submit','form_preview',gTxt('preview'),'smallbox').
					graf($inputs).
					'</form>'
				,'div',' class="column center"')
				
			,'div',' id="edit"');

		echo $out;
	}
*/
// -------------------------------------------------------------

	function form_save($in=array())
	{
		global $WIN, $txp_user, $event, $essential_forms, $path_to_site;
		
		$id = assert_int(gps('id'));
		
		extract(safe_row("AuthorID,Name,Type","txp_form","ID = $id"));
		
		if (!has_privs('form.edit') && !($AuthorID == $txp_user && has_privs('form.edit.own')))
		{
			form_list(gTxt('restricted_area'));
			return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$html = gps('Body');
		$xsl  = preg_match('/<xsl:/',$html) ? true : '';
		
		$path = $path_to_site.DS.'textpattern'.DS.'xsl'.DS.'form'.DS;
		if (!is_dir($path)) mkdir($path,0777,TRUE);
		
		$Body = $html;
		$Body = preg_replace("/\&nbsp;/","&#160;",$Body);
		$Body = preg_replace("/\&amp\;(\#[0-9]+\;)/","&$1",$Body);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		/*
		if ($error = invalidTxpTag($html)) {
				
			extract($error);
			
			$Body = doSlash($Body);
			
			safe_update("txp_page","Body = '$Body'","ID = $id");
			
			$massage = array("Unknown attribute <b>$err_att</b> in <b>$err_tag</b> tag on line $err_line",E_ERROR);
			
			return page_edit($massage);
		}
		*/	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($xsl) {
				
			$html = addCommentLines($html); 
			$html = addImageTypeAttr($html);
			
			if ($html === false) {
				
				$Body = doSlash($Body);
				
				safe_update("txp_form","Body = '$Body'","ID = $id");
				
				return page_edit(array("Not saved! Unbalanced TXP tag", E_ERROR));
			}
			
			$xsl = make_xsl(trim($html),'form',$Name,1);
			
			if ($error = invalid($xsl,$Name.'.xsl')) {
				
				$Body = doSlash($Body);
				
				safe_update("txp_form","Body = '$Body'","ID = $id");
				
				return form_edit(array($error, E_ERROR));
			}
			
			$xsl = examineHTMLTags($xsl); 
			
			$filename = $path.preg_replace('/\//','_',$Name).'.xsl';
			write_to_file($filename,$xsl);
			
			$html = clean_html(xslt('',$xsl));
			$Type = 'xsl';
		}
		
		if ($xsl and $Type == 'xsl' and strlen(trim($html))) {
				
			$Type = 'misc';
		}
		
		if ($Type != 'xsl') {
		
			$html = examineHTMLTags($html); 
			$html = preg_replace('/\[txp\:/','<txp:',$html);
			$html = preg_replace('/\/\]/','/>',$html);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$Body      = doSlash($Body);
		$Body_xsl  = doSlash($xsl);
		$Body_html = doSlash($html);
		
		safe_update("txp_form",
			"Body	   = '$Body',
			 Body_xsl  = '$Body_xsl',
			 Body_html = '$Body_html',
			 `Type`    = '$Type'",
			"ID = $id");
		
		$message = gTxt('form_updated', array('{name}' => $Name));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($event != 'utilities') {
		
			if ($Type == 'xsl') {
			
				update_user_html_cache();
			}
		
			$WIN['scroll'] = gps('scroll',0);
		
			update_lastmod($id);
		
			return form_edit($message);
		}
	}
	
// -------------------------------------------------------------
/*
	function form_save($in=array())
	{
		global $WIN, $event, $vars, $step, $essential_forms, $path_to_site;
		
		if (count($in)) {
			extract($in);
		} else {		
			extract(doSlash(gpsa($vars)));
		}
		
		$name = doSlash(trim(preg_replace('/[<>&"\']/','',$name)));
		$oldtype = '';
		
		$dirpath = $path_to_site.DS.'textpattern'.DS.'xsl'.DS.'form'.DS;
		if (!is_dir($dirpath)) mkdir($dirpath,0777,TRUE);
		
		if (!$name)
		{
			$step = 'form_create';
			$message = gTxt('form_name_invalid');

			return (!$in) ? form_edit(array($message, E_ERROR)) : $message;
		}

		if (!in_array($type, array('article','category','comment','file','link','misc','section','xsl')))
		{
			$step = 'form_create';
			$message = gTxt('form_type_missing');

			return (!$in) ? form_edit(array($message, E_ERROR)) : $message;
		}
		
		$html = ($in) ? $in['Form'] : gps('Form');
		$Form = doSlash($html);
		$xsl  = (preg_match('/<xsl:/',$html)) ? make_xsl($html,'form',1) : '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// create new form
		
		if ($savenew)
		{
			$exists = safe_field('name', 'txp_form', "name = '$name'");

			if ($exists)
			{
				$step = 'form_create';
				$message = gTxt('form_already_exists', array('{name}' => $name));

				return (!$in) ? form_edit(array($message, E_ERROR)) : $message;
			}
			
			if ($xsl) {
				include_once txpath.'/include/txp_presentation_page.php';
				
				$xsl = examineHTMLTags($xsl);
				
				$filename = $dirpath.'/'.preg_replace('/\//','_',$name).'.xsl';
				write_to_file($filename ,$xsl);
					
				$xsl  = doSlash($xsl);
				$type = 'xsl';
			}
			
			safe_insert('txp_form', 
				"`Form` 	= '$Form',
				 `Form_xsl` = '$xsl',
				 `type` 	= '$type',
				 `name` 	= '$name'");
				 
			$message = gTxt('form_created', array('{name}' => $name));

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// save form
		
		} else {
		
			if ($xsl) { 
				
				if ($error = invalid($xsl,$name.'.xsl')) {
					
					safe_update('txp_form',
						"Form = '$Form',
						 type = '$type',
						 name = '$name'",
						"name = '$oldname'");
					
					return form_edit(array($error, E_ERROR));
				}
				include_once txpath.'/include/txp_presentation_page.php';
				
				$xsl = examineHTMLTags($xsl);
				
				$filename = $dirpath.'/'.preg_replace('/\//','_',$name).'.xsl';
				write_to_file($filename ,$xsl);
					
				$xsl  = doSlash($xsl);
				$type = 'xsl';
			
			} else {
				
				if ($type == 'xsl') {
					$type    = 'misc';
					$oldtype = 'xsl';
				}
			}
			
			safe_update('txp_form',
				"Form = '$Form',
				 Form_xsl = '$xsl',
				 type = '$type',
				 name = '$name'",
				"name = '$oldname'");
			
			$message = gTxt('form_updated', array('{name}' => $name));
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($event != 'utilities') {
		
			if ($type == 'xsl') {
				
				include_once txpath.'/include/txp_presentation_page.php';
				
				update_user_html_cache();
			}
			
			if ($oldtype == 'xsl') {
				
				// TODO: - find all forms and pages that import this form
				//	     - comment out the import tag
				//		 - make sure that any call-template tags don't 
				//		   refer to missing templates 
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			update_lastmod();
			
			$WIN['scroll'] = gps('scroll',0);
	
			form_edit($message);
		}
	}
*/	
// -------------------------------------------------------------
	function form_delete($name)
	{
		global $essential_forms;
		if (in_array($name, $essential_forms)) return false;
		$name = doSlash($name);
		if (safe_delete("txp_form","name='$name'")) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function formTypes($type)
	{
	 	$types = array(''=>'','article'=>'article','category'=>'category','comment'=>'comment',
	 		'file'=>'file','link'=>'link','misc'=>'misc','section'=>'section','image'=>'image','xsl'=>'xsl');
		return selectInput('type',$types,$type);
	}
	
?>

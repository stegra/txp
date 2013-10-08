<?php
/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_page.php $
$LastChangedRevision: 3260 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	if ($event == 'page') {
		
		require_privs('page');
		
		$steps = array_merge($steps,array(
			'line_numbers'
		));
	}

//------------------------------------------------------------------------------

	function page_list($message='')
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
				'Pattern' 	 => array('title' => 'Pattern',    'on' => 1, 'editable' => 1, 'pos' => 8),
				'AuthorID'	 => array('title' => 'Author', 	   'on' => 1, 'editable' => 1, 'pos' => 9),
				'Status'	 => array('title' => 'Status',	   'on' => 1, 'editable' => 1, 'pos' => 10),
				'Position'   => array('title' => 'Position',   'on' => 1, 'editable' => 1, 'pos' => 11, 'short' => 'Pos.')
			);
		}
		
		$main_title = safe_field("CONCAT(' &#8250; ',Title)",
			$WIN['table'],"ID = ".$WIN['id']." AND ParentID != 0");
		
		$html = pagetop(gTxt('pages').$main_title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$pages = new ContentList(); 
		$list = $pages->getList();
		
		foreach ($list as $key => $item) {
			
			if ($item['Type'] == 'folder') {
				$list[$key]['Type'] = 'dir';
			}
		}	
		
		$html.= $pages->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

//------------------------------------------------------------------------------

	function page_edit($message='')
	{	
		global $WIN, $event, $html, $smarty;
		
		$id = gps('id',0);
		$id = assert_int($id);
		
		$rs = safe_row("*","txp_page", "ID = '$id' AND Trash = 0");
		
		if (!$rs) return;
		
		extract($rs);
		
		if (!has_privs('page.edit') && !($author == $txp_user && has_privs('page.edit.own')))
		{
			page_list(gTxt('restricted_area'));
			return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$html = pagetop(gTxt('pages').' &#8250; '.$Title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$Body = preg_replace("/\&(\#[0-9]+\;)/","&amp;$1",doStrip($Body));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($Name == 'global') {
			$Body = preg_replace('/(xsl\:template) match="\/">/',"$1 name=\"html\">",$Body);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$list = page_edit_list();
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
/*
	function page_edit($message = '') {
		
		global $step;

		pagetop(gTxt('edit_pages'), $message);
		
		extract(gpsa(array('name', 'newname', 'copy')));

		if (!$name or $step == 'page_delete')
		{
			$name = safe_field('page', 'txp_section', "name = 'default'");
		}

		$name = ( $copy && trim(preg_replace('/[<>&"\']/', '', $newname)) ) ? $newname : $name;

		echo 
			tag(
			
				tag(
					n.hed(
						gTxt('tagbuilder')
					, 2).
	
					n.n.hed(
						'<a href="#article-tags">'.gTxt('page_article_hed').'</a>'
					, 3, ' class="plain lever expanded"').
						n.'<div id="article-tags" class="toggle on" style="display:block">'.taglinks('page_article').'</div>'.
	
					n.n.hed('<a href="#article-nav-tags">'.gTxt('page_article_nav_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="article-nav-tags" class="toggle" style="display:none">'.taglinks('page_article_nav').'</div>'.
	
					n.n.hed('<a href="#nav-tags">'.gTxt('page_nav_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="nav-tags" class="toggle" style="display:none">'.taglinks('page_nav').'</div>'.
	
					n.n.hed('<a href="#xml-tags">'.gTxt('page_xml_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="xml-tags" class="toggle" style="display:none">'.taglinks('page_xml').'</div>'.
	
					n.n.hed('<a href="#misc-tags">'.gTxt('page_misc_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="misc-tags" class="toggle" style="display:none">'.taglinks('page_misc').'</div>'.
	
					n.n.hed('<a href="#file-tags">'.gTxt('page_file_hed').'</a>'
					, 3, ' class="plain lever"').
						n.'<div id="file-tags" class="toggle" style="display:none">'.taglinks('page_file').'</div>'
	
				,'div',' class="column left"').
				
				tag(
					hed(gTxt('all_pages'), 2).
					page_list($name)
				,'div',' class="column right"').
				
				tag(
					page_edit_form($name)
				,'div',' class="column center"')
		
			,'div',' id="edit"');
	}
*/
//------------------------------------------------------------------------------

	function page_edit_form($name)
	{
		global $step;
		
		$html = safe_field('user_html','txp_page',"name='".doSlash($name)."'");
		
		$html = preg_replace("/\&(\#[0-9]+\;)/","&amp;$1",$html);
		
		$button = '<a href="#" title="Line Numbers" id="toggle-line-numbers">1&#183;2&#183;3</a>';

		$out[] = '<p>'.gTxt('you_are_editing_page').sp.strong($name).'</p>'.comment_line().comment('TEXTAREA').n.
					'<div id="box"><div id="scrollpane"><textarea spellcheck="false" id="code" class="code" name="html" cols="84" rows="36">'.$html.'</textarea></div>'.$button.'</div>'.comment_line().
					n.fInput('submit','save',ucwords(gTxt('saved')),'publish saved',gTxt('save'),'','','','save').
					n.eInput('page').
					n.sInput('page_save').
					n.hInput('scroll',0,'scroll').
					n.hInput('name',$name);
		
		$out[] =
				n.'<label for="copy-page">'.gTxt('copy_page_as').'</label>'.sp.
				n.fInput('text', 'newname', '', 'edit', '', '', '', '', 'copy-page').
				n.hInput('oldname',$name).
				n.fInput('submit','copy',gTxt('copy'),'smallerbox');
		return form(join('',$out));
	}
	
//------------------------------------------------------------------------------
/*
	function page_list($current)
	{
		$protected = safe_column('DISTINCT page', 'txp_section', '1=1') + array('error_default');

		$rs = safe_rows_start('name', 'txp_page', "1 order by name asc");

		while ($a = nextRow($rs))
		{
			extract($a);

			$link  = eLink('page', '', 'name', $name, $name);
			$dlink = !in_array($name, $protected) ? dLink('page', 'page_delete', 'name', $name) : '';

			$out[] = ($current == $name) ?
				tr(td($name).td($dlink)) :
				tr(td($link).td($dlink,'','delete'));
		}

		return startTable('list').join(n, $out).endTable();
	}
*/
//------------------------------------------------------------------------------
// change: delete xsl files
/*
	function page_delete()
	{
		$name  = ps('name');
		$count = safe_count('txp_section', "page = '".doSlash($name)."'");
		
		if ($name == 'error_default')
		{
			return page_edit();
		}
		
		if ($count)
		{
			$message = array(gTxt('page_used_by_section', array('{name}' => $name, '{count}' => $count)), E_WARNING);
		}
		else
		{
			safe_delete('txp_page', "name = '".doSlash($name)."'");

			$message = gTxt('page_deleted', array('{name}' => $name));
			
			$file = txpath."/custom/pages/".$name.".xsl";
			if(is_file("$file")) unlink("$file");
		}
		
		page_edit($message);
	}
*/
//------------------------------------------------------------------------------

	function page_save() 
	{
		global $WIN, $txp_user, $event, $path_to_site;
		
		$id = assert_int(gps('id'));
		
		extract(safe_row("AuthorID,Name,Type","txp_page","ID = $id"));
		
		if (!has_privs('page.edit') && !($AuthorID == $txp_user && has_privs('page.edit.own')))
		{
			page_list(gTxt('restricted_area'));
			return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$html = gps('Body');
		$xsl  = preg_match('/<xsl:/',$html) ? true : '';
		
		if (is_dir($path_to_site.DS.'xsl')) {
		
			$path_to_xsl = $path_to_site.DS.'xsl'.DS.'page'.DS;
		
		} else {
		
			$path_to_xsl = $path_to_site.DS.'textpattern'.DS.'xsl'.DS.'page'.DS;
			if (!is_dir($path_to_xsl)) mkdir($path_to_xsl,0777,TRUE);
		}
		
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
				
				safe_update("txp_page","Body = '$Body'","ID = $id");
				
				return page_edit(array("Not saved! Unbalanced TXP tag", E_ERROR));
			}
			
			$html = preg_replace("/\&\#(\d+);/","{ENTITY_$1}",$html);
			
			$xsl = make_xsl(trim($html),'page',$Name,1);
			
			if ($error = invalid($xsl,$Name.'.xsl')) {
				
				$Body = doSlash($Body);
				
				safe_update("txp_page","Body = '$Body'","ID = $id");
				
				return page_edit(array($error, E_ERROR));
			} 
			
			$xsl = examineHTMLTags($xsl); 
			
			$path = new Path($id,'SELF','txp_page');
			$filename = implode('_',$path->get_path('Name'));
			$filename = $path_to_xsl.$filename.'.xsl';
			write_to_file($filename,$xsl);
			
			$html = clean_html(xslt('',$xsl));
			$Type = 'xsl';
		
		} elseif ($Type != 'folder') {
				
			$Type = (strlen(trim($html))) ? 'txp' : 'folder';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$Body      = doSlash($Body);
		$Body_xsl  = doSlash($xsl);
		$Body_html = doSlash($html);
		
		safe_update("txp_page",
			"Body	   = '$Body',
			 Body_xsl  = '$Body_xsl',
			 Body_html = '$Body_html',
			 `Type`    = '$Type'",
			"ID = $id");
		
		$message = gTxt('page_updated', array('{name}' => $Name));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($event != 'utilities') {
		
			if ($Type == 'xsl') {
			
				update_user_html_cache($id);
			}
		
			$WIN['scroll'] = gps('scroll',0);
		
			update_lastmod($id);
		
			return page_edit($message);
		}
	}	
		
//------------------------------------------------------------------------------
// change: go to the copied page if one was created
// change: save xsl
/* 
	function page_save_OLD($in=array()) {
		
		global $WIN, $event, $path_to_site;
		
		if (count($in)) {
			extract($in);
		} else {		
			extract(gpsa(array('name','html','copy','oldname','newname')));
		}
		
		$xsl  = preg_match('/<xsl:/',$html);
		
		$path = $path_to_site.DS.'textpattern'.DS.'xsl'.DS.'page'.DS;
		if (!is_dir($path)) mkdir($path,0777,TRUE);
		
		$html_edit    = doSlash($html);
		$html_edit    = preg_replace("/\&nbsp;/","&#160;",$html_edit);
		$html_edit    = preg_replace("/\&amp\;(\#[0-9]+\;)/","&$1",$html_edit);
		$html_publish = $html_edit;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// copy page 
		
		if ($copy) {
			
			$newname = doSlash(trim(preg_replace('/[<>&"\']/','',$newname)));
			
			if ($newname and safe_field('name', 'txp_page', "name = '$newname'"))
			{
				$message = array(gTxt('page_already_exists', array('{name}' => $newname)), E_ERROR);
				
				return (!$in) ? page_edit($message) : $message;
			}
			elseif ($newname)
			{
				if ($xsl) {
					
					$html = addCommentLines($html);
					$html = addImageTypeAttr($html);
					$xsl  = make_xsl(trim($html),'page',1);
					
					if ($error = invalid($xsl,$newname.'.xsl')) {
						
						safe_update("txp_page",
							"user_html = '$html_edit'",
							"name = '$name'");
						
						return (!$in) ? page_edit(array($error, E_ERROR)) : $error;
					} 
					
					$xsl = examineHTMLTags($xsl); 
					
					$filename = $path.preg_replace('/\//','_',$newname).'.xsl';
					write_to_file($filename,$xsl);
					
					if ($event != 'utilities')
						$html_publish = doSlash(clean_html(xslt('',$xsl)));
					else
						$html_publish = '';
						
					$xsl = doSlash($xsl);
				}
				
				safe_insert("txp_page",
					"name 			   = '$newname',
					 user_html 		   = '$html_edit',
					 user_xsl 		   = '$xsl',
					 user_html_publish = '$html_publish'");
				
				$_GET['name'] = $newname; // go to the newly created page
				$message = gTxt('page_created', array('{name}' => $newname));
			}
			else
			{
				$message = array(gTxt('page_name_invalid'), E_ERROR);
				
				return (!$in) ? page_edit($message) : $message;
			}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// save page
		
		} else {
			
			if ($xsl) {
				
				$html = addCommentLines($html);
				$html = addImageTypeAttr($html);
				$xsl  = make_xsl(trim($html),'page',1);
				
				if ($error = invalid($xsl,$name.'.xsl')) {
					
					safe_update("txp_page",
						"user_html = '$html_edit'",
						"name = '$name'");
					
					return page_edit(array($error, E_ERROR));
				} 
				
				$xsl = examineHTMLTags($xsl); 
				
				$filename = $path.preg_replace('/\//','_',$name).'.xsl';
				write_to_file($filename,$xsl);
					 
				$html_publish = doSlash(clean_html(xslt('',$xsl)));
				
				$xsl = doSlash($xsl);
			}
					
			safe_update("txp_page",
				"user_html		   ='$html_edit',
				 user_xsl		   ='$xsl',
				 user_html_publish ='$html_publish'",
				"name = '$name'");
					
			$message = gTxt('page_updated', array('{name}' => $name)); 
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($event != 'utilities') {
		
			if ($xsl) {
			
				$name = ($newname) ? $newname : $name;
		
				update_user_html_cache($name);
			}
		
			$WIN['scroll'] = gps('scroll',0);
		
			update_lastmod();
		
			return page_edit($message);
		}
	}
*/
//------------------------------------------------------------------------------
// do xsl transformation for all xsl pages to make sure changes in imported xsl 
// is are refreshed in the cache

	function update_user_html_cache($exclude='',$root=0)
	{
		global $event, $path_to_site;
		
		static $path_to_xsl = '';
		
		if (!$path_to_xsl) {
		
			if (is_dir($path_to_site.DS.'textpattern'.DS.'xsl'.DS.'page')) {
				
				$path_to_xsl = $path_to_site.DS.'textpattern'.DS.'xsl'.DS.'page'.DS;
			
			} elseif (is_dir($path_to_site.DS.'xsl'.DS.'page')) {
				
				$path_to_xsl = $path_to_site.DS.'xsl'.DS.'page'.DS;
			
			} else {
				
				return array();
			}
		}
		
		/* foreach (dirlist($path_to_xsl,'xsl') as $file) {
			unlink($path_to_xsl.$file);
		} */
		
		$root = (!$root) ? fetch("ID","txp_page","ParentID",0) : $root;
		
		$rows = safe_rows(
			"ID,Name,Body,Body_xsl,Level,ParentID,Type","txp_page",
			"ParentID = $root AND Trash = 0 AND Type != 'trash' ORDER BY Position ASC");
		
		foreach ($rows as $page) {
			
			$id   = $page['ID'];
			$type = $page['Type'];
			
			if (!in_list($id,$exclude)) {
			
				if ($type == 'xsl') {
				
					$xsl  = doStrip($page['Body_xsl']);
					
					if (!strlen($xsl)) {
						
						$name = $page['Name']; 
						$html = doStrip($page['Body']);
						
						$path = new Path($id,'SELF','txp_page');
						$filename = implode('_',$path->get_path('Name'));
						
						if ($event == 'utilities') { 
							echo str_repeat("",$page['Level']-2).implode('/',$path->get_path('Name')).'.xsl'.n;
						}
						
						if ($name == 'global') {
							$html = preg_replace('/(xsl\:template) match="\/">/',"$1 name=\"html\">",$html);
						}
						
						$html = preg_replace("/\&\#(\d+);/","{ENTITY_$1}",$html);
						$html = addCommentLines($html);
						$html = addImageTypeAttr($html);
						
						$xsl  = make_xsl(trim($html),'page',$name,true);
						$xsl  = examineHTMLTags($xsl);
						
						write_to_file($path_to_xsl.$filename.'.xsl',$xsl);
					}
						
					if (strlen($xsl)) {
						
						$html = doSlash(clean_html(xslt('',$xsl)));
						
						safe_update("txp_page","Body_html = '$html'","ID = $id");
					}
				}
			}
						
			if (safe_count("txp_page","ParentID = $id AND Trash = 0")) {
			
				update_user_html_cache($exclude,$id);
			}
		}
	}
	
//------------------------------------------------------------------------------

	function taglinks($type)
	{
		return popTagLinks($type);
	}

//------------------------------------------------------------------------------

	function page_edit_list()
	{
		rebuild_txp_tree();
		
		$rows = safe_rows_tree("0","ID,Name,Type,Level","txp_page","1=1",1);
		
		return $rows;
	}
			
//------------------------------------------------------------------------------
// search for tags with open and closing tags. single closed tags can be ignored.

	function doNestedTagAlias($html) 
	{
		$tags = array('article','article_custom');
		
		foreach ($tags as $name) {
       		
       		$html = preg_replace_callback('/<\/?txp:'.$name.'(\s[^>]+)?(?<!\/)>/',"makeAlias",$html);
		}
		
		return $html;
	}

//------------------------------------------------------------------------------

	function makeAlias($matches) 
	{
		static $open = 0;
		
		$tag = $matches[0];
		
		if (preg_match('/^<txp:/',$tag)) {
			
			$open++;
			
			if ($open > 1) 
				$tag = preg_replace('/<txp:(\w+)/','<txp:${1}_level'.$open,$tag);
			
			return $tag;
		
		} elseif (preg_match('/^<\/txp:/',$tag)) {
			
			if ($open > 1)
				$tag = preg_replace('/<\/txp:(\w+)/','</txp:${1}_level'.$open,$tag);
			
			$open--;
			
			return $tag;
		}
	}

//--------------------------------------------------------------------------------------
	function invalidTxpTag($code) {
		
		$tags = array();
		
		foreach(explode(n,$code) as $linenum => $line) {
		
			preg_match_all('/\<txp:([a-z0-9_]+)\s+([^\>]+?)\/?\>/',$line,$matches);
		
			foreach ($matches[1] as $key => $tag) {
				
				preg_match_all('/\b([a-z0-9\.\_]+)\=\"/',$matches[2][$key],$atts);
				
				foreach ($atts[1] as $att) {
					
					if (!isset($tags[$tag][$att])) {
					
						
						if (safe_count('txp_tag_attr',"tag = '$tag' AND attribute = '$att'",0,'NONE')) {
							
							if (isset($tags[$tag])) {
								$tags[$tag][$att] = 1;
							} else {
								$tags[$tag] = array($att => 1);
							}
							
						} else {
							
							return array(
								'err_tag'  => $tag,
								'err_att'  => $att,
								'err_line' => $linenum + 1
							);
						}
					}
				}
			}
		}
	}
	
//--------------------------------------------------------------------------------------
	function examineHTMLTags($code) {
	
		$tag_name = '([a-z]+[1-6]?)';
		$tag_attr = '([^\>]+)';
		
		// return preg_replace_callback('/\<'.$tag_name.'\s+'.$tag_attr.'\>/','examineAttributes',$code);
		
		$LEFT  = '\{';
		$RIGHT = '\}';
		$SP    = '\s*';
		
		$var   = '\$([0-9]+)';
		$code  = preg_replace_callback('/'.$LEFT.$SP.$var.$SP.$RIGHT.'/','reformatCurlyVar',$code);
		
		$var   = '\$txp\.'."([a-z0-9\_\-\.\(\)\'\']+)";
		return preg_replace_callback('/'.$LEFT.$SP.$var.$SP.$RIGHT.'/','reformatCurlyVar',$code);
	}	

//--------------------------------------------------------------------------------------
	function examineAttributes($matches,$tag_name='',$tag_attr='') {
		
		if ($matches) {
			$tag_name = $matches[1];
			$tag_attr = $matches[2];
		}
		
		$attr_name  = '([a-z0-9\.\_\-]+)';
		$attr_value = '([^\"]+?)';
		
		$tag_attr = preg_replace_callback('/'.$attr_name.'\="'.$attr_value.'"/','replaceAttr',$tag_attr);
		
		return '<'.$tag_name.' '.$tag_attr.'>';
	}

//--------------------------------------------------------------------------------------
	function replaceAttr($matches,$attr_name='',$attr_value='') {
	
		if ($matches) {
			$attr_name  = $matches[1];
			$attr_value = $matches[2];
		}
		
		$LEFT  = '\{';
		$RIGHT = '\}';
		$SP    = '\s*';
		$var   = '\$txp\.'."([a-z0-9\_\-\.\(\)\'\']+)";
		
		// replace ">=" and ">" signs with entities
		$attr_value = preg_replace('/>\=/','&gte;',$attr_value);
		$attr_value = preg_replace('/>/','&gt;',$attr_value);
		// $attr_value = preg_replace('/<\=/','&lte;',$attr_value);
		// $attr_value = preg_replace('/</','&lt;',$attr_value);
		
		// reformat txp variables in curly brackets
		$attr_value = preg_replace_callback('/'.$LEFT.$SP.$var.$SP.$RIGHT.'/','reformatCurlyVar',$attr_value);
		
		return $attr_name.'="'.$attr_value.'"';
	}
	
//--------------------------------------------------------------------------------------
	function reformatCurlyVar($matches,$name='') {
		
		$match = ($matches) ? explode('.',$matches[1]) : explode('.',$name);
		
		$out_tag_name = array_shift($match);
		$out_tag_attr = array();
		
		$atts = $match;
		
		$parent = false;
		
		if ($out_tag_name == 'parent') {
			
			$parent = true;
			
			$out_tag_name = array_shift($atts);
			
			if (in_list($out_tag_name,'id,title')) {
			
				switch ($out_tag_name) {
					case 'id'    : $out_tag_name = 'parent_id'; break;
					case 'title' : $out_tag_name = 'parent_title'; break;
				}
				
				$parent = false;
			}
		}
		
		// url path position index
		// example: {$1},{$2},etc.
		
		if (preg_match('/^[0-9]+$/',$out_tag_name)) {
			
			$position       = $out_tag_name;
			$out_tag_name   = 'path';
			$out_tag_attr[] = "position='".$position."'";
			$out_tag_attr[] = "mode='req'";
		}
		
		if ($out_tag_name == 'custom' and isset($atts[0])) {
			
			$out_tag_name   = 'custom_field';
			$out_tag_attr[] = "name='".$atts[0]."'";
			
			if (isset($atts[1]) and $atts[1] == 'num') {
				$out_tag_attr[] = "format='number'";
			}
			
			$match = array();
		}
		
		// txp:custom_field tag
		
		if (preg_match('/^custom[1-9]0?$/',$out_tag_name)) {
			
			$out_tag_name = "custom_field";
			$out_tag_attr[] = "name='".$out_tag_name."'";
		}
		
		// txp:var tag
		
		if ($out_tag_name == 'var' and $atts) {
			
			$out_tag_attr[] = "name='".array_shift($atts)."'";
		}
		
		// txp:image_src tag
		
		if ($out_tag_name == 'image_src' and $atts) {
			
			$val = array_shift($atts);
			
			if (in_list($val,'o,r,t,xx,y,z,')) {
				$out_tag_attr[] = "size='$val'";
			}
		}
		
		// txp:body & txp:excerpt tag
		
		if (in_list($out_tag_name,'body,excerpt')) {
			
			$out_tag_attr[] = "textile='0'";
		}
		
		// txp tag attributes if any
		// format for tag attributes: {$txp.tagname.param('value')}
		
		while (count($atts)) {
		
			$param = array_shift($atts);
			
			$param_name  = "([a-z0-9\_\-]+)";
			$param_value = "([^\']*)";
			
			preg_match("/^".$param_name."\(\'".$param_value."\'\)$/",$param,$matches);
			
			if (count($matches) == 3) {
				
				$param_name  = $matches[1];
				$param_value = $matches[2];
				
				$out_tag_attr[] = $param_name."='".$param_value."'";
			}
		}
		
		$out = '[txp:'.$out_tag_name.' '.implode(' ',$out_tag_attr).'/]';
		
		if ($parent) {
			$out = "[txp:article path='..' status='*' debug='0']".$out.'[/txp:article]';
		}
		
		return $out;
	}

//--------------------------------------------------------------------------------------
	function addCommentLines($html) {
		
		return parse($html,'xsl','\w+','processXSLTempl');
	}

//--------------------------------------------------------------------------------------
	function processXSLTempl($tag, $atts, $thing = NULL, $namespace='xsl') {
		
		$out = '<'.$namespace.':'.$tag.$atts.($thing ? '>' : '/>');
		
		if ($thing) {
		
			if ($tag == 'template') {
				
				$atts = splat($atts);
				
				if (isset($atts['name']) and $atts['name'] == 'body') {
					
					$thing = preg_replace('/(<div\b)/',"<txp:line/>\n\n$1",$thing);
				}
			}
			
			$out .= $thing.'</'.$namespace.':'.$tag.'>';
		} 
		
		return $out;
	}
	
//--------------------------------------------------------------------------------------
	function addImageTypeAttr($html) {
		
		return parse($html,'txp','\w+','processTXPTempl');
	}

//--------------------------------------------------------------------------------------
	function processTXPTempl($tag, $atts, $thing = NULL, $namespace='xsl') {
		
		static $level = 1;
		static $trace = array();
		
		if ($tag == 'image') 
			$trace[] = array($level,'image');
		
		if ($tag == 'body' or $tag == 'excerpt')
			$trace[] = array($level,'body');
		
		// - - - - - - - -  - - - - - - - - - - - - - - - - - - - - -
		
		$out = '<'.$namespace.':'.$tag.$atts.($thing ? '>' : '/>');
		
		// - - - - - - - -  - - - - - - - - - - - - - - - - - - - - -
		
		if ($thing) {
			
			$level++;
			
			$out .= parse($thing,'txp','\w+','processTXPTempl');
			
			$level--;
			
			$out .= '</'.$namespace.':'.$tag.'>';
		} 
		
		// - - - - - - - -  - - - - - - - - - - - - - - - - - - - - -
		
		// print_r($trace);
		
		return $out;
	}	

//--------------------------------------------------------------------------------------
	function clean_html($html) {
		
		$html = preg_replace('/(<tr\b)/',"\n\t$1",$html);
		$html = preg_replace('/(<\/tr>)/',"\n\t$1",$html);
		$html = preg_replace('/(<td\b)/',"\n\t\t$1",$html);
		$html = preg_replace('/\r+(<\/td>)/',"$1",$html);
		
		$html = preg_replace('/(<h[1-6]\b)/',"\n\n$1",$html);
		$html = preg_replace('/(<\/h[1-6]>)/',"$1\n\n",$html);
		
		$html = preg_replace('/(<ul\b)/',"\n\n$1",$html);
		$html = preg_replace('/(<ul>)/',"$1\n\t",$html);
		$html = preg_replace('/(<\/ul>)/',"\n$1\n\n",$html);
		$html = preg_replace('/(<li)/',"\n\t$1",$html);
		
		$html = preg_replace('/(<\/(form|table)>)/',"\n</$2>\n",$html);
		$html = preg_replace('/(<body\b)/',"\n\n$1",$html);
		$html = preg_replace('/(<head\b)/',"\n$1",$html);
		$html = preg_replace('/(<(title|script|link)\b)/',"\n\t$1",$html);
		
		$html = preg_replace("/\{ENTITY_(\d+)\}/","&#$1;",$html);
		
		return $html;
	}
?>

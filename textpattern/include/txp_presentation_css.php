<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_css.php $
$LastChangedRevision: 3118 $
*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	if ($event == 'css') {
		
		require_privs('css');
		
		$steps = array_merge($steps,array(
			'line_numbers'
		));
	}

//------------------------------------------------------------------------------

	function css_list($message='')
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
		
		$html = pagetop(gTxt('css').$main_title,$message);
				
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$styles = new ContentList(); 
		$list = $styles->getList();
		
		$html.= $styles->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

//------------------------------------------------------------------------------

	function css_edit($message='')
	{	
		global $WIN, $event, $html, $smarty;
		
		$id = gps('id',0);
		$id = assert_int($id);
		
		$rs = safe_row("*","txp_css", "ID = '$id' AND Trash = 0");
		
		if (!$rs) return;
		
		extract($rs);
		
		if (!has_privs('css.edit') && !($author == $txp_user && has_privs('css.edit.own')))
		{
			form_list(gTxt('restricted_area'));
			return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$html = pagetop(gTxt('css').' &#8250; '.$Title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		// $Body = base64_decode($Body);
		$Body = preg_replace("/\&(\#[0-9]+\;)/","&amp;$1",doStrip($Body));
		
		$list = css_edit_list();
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

	function css_edit_list()
	{
		rebuild_txp_tree();
		
		$rows = safe_rows_tree("0","ID,Name,Type,Level","txp_css");
		
		return $rows;
	}
		

//-------------------------------------------------------------
/*
	function css_list($current, $default) {
	
		$out[] = startTable('list', 'left');

		$rs = safe_rows_start('name', 'txp_css', "1=1");

		if ($rs) {
			while ($a = nextRow($rs)) {
				extract($a);

				$edit = ($current != $name) ?	eLink('css', '', 'name', $name, $name) : htmlspecialchars($name);
				$delete = ($name != $default) ? dLink('css', 'css_delete', 'name', $name) : '';

				$out[] = tr(td($edit).td($delete,'','delete'));
			}

			$out[] =  endTable();

			return join('', $out);
		}
	}
*/
//-------------------------------------------------------------
/*
	function css_edit($message='') {
		
		global $step, $prefs;
		
		pagetop(gTxt("edit_css"),$message);

		$name = gps('name');

		$default_name = safe_field('css', 'txp_section', "name = 'default'");

		$name = (!$name or $step == 'css_delete') ? $default_name : $name;

		if (gps('copy') && trim(preg_replace('/[<>&"\']/', '', gps('newname'))) )
			$name = gps('newname');

		if ($step=='pour')
		{
			$buttons =
			gTxt('name_for_this_style').': '
			.fInput('text','newname','','edit','','',20).
			hInput('savenew','savenew');
			$thecss = '';

		} else {
			$buttons = '';
			$thecss = base64_decode(fetch("css",'txp_css','name',$name));
		}

		if ($step!='pour') {

			$copy = gTxt('copy_css_as').sp.fInput('text', 'newname', '', 'edit').sp.
				fInput('submit', 'copy', gTxt('copy'), 'smallerbox');
		} else {
			$left = '&nbsp;';
			$copy = '';
		}

		$right =
		hed(gTxt('all_stylesheets'),2).
		css_list($name, $default_name);
		
		echo
			tag(
				tag('','div',' class="column left"').
				tag(
					$right
				,'div',' class="column right"').
				tag(
					form(
						'<p>'.gTxt('you_are_editing_css').sp.strong(htmlspecialchars($name)).'</p>'.comment_line().comment('TEXTAREA').n.
						'<div id="box"><div id="scrollpane"><textarea spellcheck="false" id="code" class="code" name="css" cols="84" rows="36">'.htmlspecialchars($thecss).'</textarea></div></div>'.comment_line().
						fInput('submit','save',ucwords(gTxt('saved')),'publish saved',gTxt('save'),'','','','save').
						eInput('css').sInput('css_save').
						hInput('scroll',0,'scroll').
						hInput('name',$name).$copy
					)
				,'div',' class="column center"')
			,'div',' id="edit"');
	}
*/
// -------------------------------------------------------------
	function parseCSS($css) // parse raw css into a multidimensional array
	{
		$css = preg_replace("/\/\*.+\*\//Usi","",$css); // remove comments
		$selectors = preg_replace('/\s+/',' ',explode("}",strip_rn($css)));
		foreach($selectors as $selector) {
			if(trim($selector)) {
			list($keystr,$codestr) = explode("{",$selector);
				if (trim($keystr)) {
					$codes = explode(";",trim($codestr));
					foreach ($codes as $code) {
						if (trim($code)) {
							list($property,$value) = explode(":",$code,2);
							$out[trim($keystr)][trim($property)] = trim($value);
						}
					}
				}
			}
		}
		return (isset($out)) ? $out : array();
	}

// -------------------------------------------------------------
	function parsePostedCSS() //turn css info delivered by editor form into an array
	{
		$post = (MAGIC_QUOTES_GPC) ? doStrip($_POST) : $_POST;
		foreach($post as $a=>$b){
			if (preg_match("/^\d+$/",$a)) {
				$selector = $b;
			}
			if (preg_match("/^\d+-\d+(?:p|v)$/",$a)) {
				if(strstr($a,'p')) {
					$property = $b;
				} else {
					if(trim($property) && trim($selector)) {
						$out[$selector][$property] = $b;
					}
				}
			}
		}
		return (isset($out)) ? $out : array();
	}

// -------------------------------------------------------------

	function css_copy()
	{
		extract(gpsa(array('oldname', 'newname')));

		$css = doSlash(fetch('css', 'txp_css', 'name', $oldname));

		$rs = safe_insert('txp_css', "css = '$css', name = '".doSlash($newname)."'");

		css_edit(
			gTxt('css_created', array('{name}' => $newname))
		);
	}

// -------------------------------------------------------------

	function css_save_posted()
	{
		$name = gps('name');
		$css  = parsePostedCSS();
		$css  = doSlash(base64_encode(css_format($css)));

		safe_update('txp_css', "css = '$css'", "name = '".doSlash($name)."'");

		// update site last mod time
		update_lastmod();

		$message = gTxt('css_updated', array('{name}' => $name));

		css_edit($message);
	}

//------------------------------------------------------------------------------

	function css_save() 
	{
		global $WIN, $txp_user, $event, $path_to_site;
		
		$id = assert_int(gps('id'));
		
		extract(safe_row("AuthorID,Name,Type","txp_css","ID = $id"));
		
		if (!has_privs('css.edit') && !($AuthorID == $txp_user && has_privs('css.edit.own')))
		{
			css_list(gTxt('restricted_area'));
			return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$css = trim(gps('Body'));
		
		if ($Type != 'folder' and strlen($css)) {
				
			$Type = 'css';
		}
		
		$Body = doSlash($css);
		
		safe_update("txp_css",
			"Body	   = '$Body',
			 `Type`    = '$Type'",
			"ID = $id");
		
		$message = gTxt('css_updated', array('{name}' => $Name));
		
		update_lastmod($id);
		
		return css_edit($message);
	}
	
//-------------------------------------------------------------
/*
	function css_save($in=array())
	{
		global $WIN, $event;
		
		if (count($in)) {
			extract($in);
		} else {		
			extract(gpsa(array('name','css','savenew','newname','copy')));
		}
		
		$css = doSlash(base64_encode($css));
		$error = '';

		if ($savenew or $copy)
		{
			$newname = doSlash(trim(preg_replace('/[<>&"\']/','',$newname)));

			if ($newname and safe_field('name', 'txp_css', "name = '$newname'"))
			{
				$error = $message = gTxt('css_already_exists', array('{name}' => $newname));
			}

			elseif ($newname)
			{
				safe_insert('txp_css', "name = '".$newname."', css = '$css'");

				update_lastmod();

				$message = gTxt('css_created', array('{name}' => $newname));
			}

			else
			{
				$error = $message = array(gTxt('css_name_required'), E_ERROR);
			}

			if (!$in) 
				css_edit($message);
			else
				return $error;
		}

		else
		{
			safe_update('txp_css', "css = '$css'", "name = '".doSlash($name)."'");

			update_lastmod();

			$message = gTxt('css_updated', array('{name}' => $name));

			css_edit($message);
			
			$WIN['scroll'] = gps('scroll',0);
		}
	}
*/
// -------------------------------------------------------------
	function css_format($css,$out='')
	{
		foreach ($css as $selector => $propvals) {
			$out .= n.$selector.n.'{'.n;
			foreach($propvals as $prop=>$val) {
				$out .= t.$prop.': '.$val.';'.n;
			}
			$out .= '}'.n;
		}
		return trim($out);
	}

// -------------------------------------------------------------
	function addSel($css)
	{
		$selector = gps('selector');
		$css[$selector][' '] = '';
		return $css;
	}

// -------------------------------------------------------------
	function add_declaration($css)
	{
		$selector = gps('selector');
		$css[$selector][' '] = '';
		return $css;
	}

// -------------------------------------------------------------
	function delete_declaration($css)
	{
		$thedec = gps('declaration');
		$name = gps('name');
		$i = 0;
		foreach($css as $a=>$b) {
			$cursel = $i++;
			$ii = 0;
			foreach($b as $c=>$d) {
				$curdec = $ii++;
				if(($cursel.'-'.$curdec)!=$thedec) {
					$out[$a][$c]=$d;
				}
			}
 		}
		$css = base64_encode(css_format($out));
		safe_update("txp_css", "css='".doSlash($css)."'", "name='".doSlash($name)."'");

		// update site last mod time
		update_lastmod();

		return parseCSS(base64_decode(fetch('css','txp_css','name',$name)));
	}

//-------------------------------------------------------------

	function css_delete()
	{
		$name  = ps('name');
		$count = safe_count('txp_section', "css = '".doSlash($name)."'");

		if ($count)
		{
			$message = gTxt('css_used_by_section', array('{name}' => $name, '{count}' => $count));
		}

		else
		{
			safe_delete('txp_css', "name = '".doSlash($name)."'");

			$message = gTxt('css_deleted', array('{name}' => $name));
		}

		css_edit($message);
	}

?>

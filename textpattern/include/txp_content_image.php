<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_image.php $
$LastChangedRevision: 3267 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	global $WIN,$event,$steps,$extensions;
	
	$extensions = array(0,'.gif','.jpg','.png','.swf',0,0,0,0,0,0,0,0,'.swf');
	
	if ($event == 'image') {
	
		require_privs('image');
		
		$steps = array_merge($steps,array(
			'insert',
			'delete',
			'replace',
			'replace_thumbnail',
			'edit_r',
			'edit_t',
			'resize_r',
			'resize_t',
			'thumbnail_insert',
			'thumbnail_delete',
			'thumbnail_create',
			'add_existing_files',
			'effect'
		));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$WIN['imgid'] = 0;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
	}
	
	include txpath.'/lib/class.thumb.php';
	include txpath.'/lib/classImageManipulation.php';
	
	// TODO: Only Include PHP_JPEG_Metadata_Toolkit when needed!
	
	include txpath.'/lib/PHP_JPEG_Metadata_Toolkit/JPEG.php';
	include txpath.'/lib/PHP_JPEG_Metadata_Toolkit/XMP.php';
	include txpath.'/lib/PHP_JPEG_Metadata_Toolkit/EXIF.php';
	
// =============================================================================
	function image_list($message='') 
	{
		global $EVENT, $WIN, $PFX, $html, $app_mode, $img_dir;

		// ---------------------------------------------------------------------
		
		if (!$WIN['criteria']) {
		
			$WIN['criteria'] = array(
					
				'category'   => 'all',
				'status'     => 'all',
				'text' 		 => ''
			);
		}
		
		// ---------------------------------------------------------------------
		
		// $WIN['columns'] = array();
		
		if (!$WIN['columns']) {
			
			$WIN['columns'] = array(
					
				'Title'  	 => array('title' => 'Title',  	   	'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	 => array('title' => 'Image',  	   	'on' => 1, 'editable' => 0, 'pos' => 2),
				'Posted' 	 => array('title' => 'Posted', 	   	'on' => 1, 'editable' => 0, 'pos' => 3),
				'LastMod'    => array('title' => 'Modified',   	'on' => 0, 'editable' => 0, 'pos' => 4),
				'Name' 		 => array('title' => 'Name', 	   	'on' => 0, 'editable' => 1, 'pos' => 5),
				'Categories' => array('title' => 'Categories', 	'on' => 1, 'editable' => 1, 'pos' => 6),
				'Articles'   => array('title' => 'Articles', 	'on' => 0, 'editable' => 0, 'pos' => 7),
				
				'copyright'  => array('title' => 'Copyright',	'on' => 0, 'editable' => 1, 'pos' => 8),
				'caption'    => array('title' => 'Caption',		'on' => 0, 'editable' => 1, 'pos' => 9,  'sel' => "Body"),
				'alt'		 => array('title' => 'Alt Text',	'on' => 0, 'editable' => 1, 'pos' => 10),
				'Keywords'   => array('title' => 'Keywords',	'on' => 0, 'editable' => 1, 'pos' => 11),
				
				'r_size'	 => array('title' => 'Size',		'on' => 0, 'editable' => 0, 'pos' => 12, 'sel' => "CONCAT(t.w,' x ',t.h)"),
				't_size'	 => array('title' => 'Th. Size',	'on' => 0, 'editable' => 0, 'pos' => 13, 'sel' => "CONCAT(thumb_w,' x ',t.thumb_h)"),
				'r_size_w'	 => array('title' => 'Width',		'on' => 0, 'editable' => 1, 'pos' => 14, 'sel' => "t.w"),
				'r_size_h'	 => array('title' => 'Height',		'on' => 0, 'editable' => 1, 'pos' => 15, 'sel' => "t.h"),
				't_size_w'	 => array('title' => 'Th. Width',	'on' => 0, 'editable' => 1, 'pos' => 16, 'sel' => "t.thumb_w"),
				't_size_h'	 => array('title' => 'Th. Height',	'on' => 0, 'editable' => 1, 'pos' => 17, 'sel' => "t.thumb_h"),
				
				'AuthorID'	 => array('title' => 'Author',		'on' => 1, 'editable' => 1, 'pos' => 18),
				'Status'	 => array('title' => 'Status',		'on' => 0, 'editable' => 1, 'pos' => 19),
				'Position'   => array('title' => 'Position',	'on' => 0, 'editable' => 1, 'pos' => 20, 'short' => 'Pos.'),
				'Folder'     => array('title' => 'Folder', 		'on' => 0, 'editable' => 0, 'pos' => 21),
				
				'ext'   	 => array('title' => 'ext',			'on' => 1, 'editable' => 0, 'pos' => 0),
				'FilePath'   => array('title' => 'FilePath',	'on' => 1, 'editable' => 0, 'pos' => 0)
			);
			
			$WIN['columns']['Title']['sel']  = "IF(t.Title='',(CONCAT(t.Name,t.ext)), t.Title) AS Title";
			$WIN['columns']['Name']['sel']   = "CONCAT(t.Name,t.ext) AS Name";
			$WIN['columns']['Folder']['sel'] = "(SELECT f.Title FROM ".$PFX."textpattern AS i JOIN ".$PFX."textpattern AS f ON i.ParentID = f.ID WHERE t.ID = i.ImageID LIMIT 1) AS Folder";
			// $WIN['columns']['Folder']['sel'] = "t.ParentID";
		}
		
		// pre($WIN['columns']);
		
		if (gps('view') == 'div') {
			$WIN['columns']['Title']['on']      = -$WIN['columns']['Title']['on'];
			$WIN['columns']['Posted']['on']     = -$WIN['columns']['Posted']['on'];
			$WIN['columns']['Categories']['on'] = -$WIN['columns']['Categories']['on'];
			$WIN['columns']['AuthorID']['on']   = -$WIN['columns']['AuthorID']['on'];
			$WIN['headers'] = 'hide';
			$WIN['main']    = 'hide';
		}
		
		if (gps('view') == 'tr') {
			$WIN['columns']['Title']['on']      = abs($WIN['columns']['Title']['on']);
			$WIN['columns']['Posted']['on']     = abs($WIN['columns']['Posted']['on']);
			$WIN['columns']['Categories']['on'] = abs($WIN['columns']['Categories']['on']);
			$WIN['columns']['AuthorID']['on']   = abs($WIN['columns']['AuthorID']['on']);
			$WIN['headers'] = 'show';
			$WIN['main']    = 'show';
		}
		
		// ---------------------------------------------------------------------
		// add existing images from ftp folder
		
		if (!is_file(IMPATH_FTP.'_LOCK')) {
			
			event_add_existing_files('jpg,jpeg,png,gif');
		}
		// pre($WIN);
		// ---------------------------------------------------------------------
		// PAGE TOP
		
		$html = pagetop(gTxt('images'), $message);

		// ---------------------------------------------------------------------
		// image upload form
		
		if ($app_mode != 'async') {
			
			$html.= image_upload_form();
		}
		
		// ---------------------------------------------------------------------
		
		$images = new ContentList();
		
		$list = $images->getList();
		
		foreach ($list as $key => $item) {
			
			if ($item['Type'] == 'folder') {
			
				$list[$key]['r_size']   = NULL;	
				$list[$key]['t_size']   = NULL;
				$list[$key]['r_size_w'] = NULL;	
				$list[$key]['r_size_h'] = NULL;	
				$list[$key]['t_size_w'] = NULL;	
				$list[$key]['t_size_h'] = NULL;
				
				// BUG: Sometimes there are two images with same ImageID!
				/* 		May not be a problem anymore
				
				$image_id = $item['ImageID'];
				$list[$key]['Image'] = safe_field("CONCAT(Name,ext)","txp_image",
					"ImageID = $image_id AND Type = 'image' ORDER BY ID DESC");
				*/
				
			} elseif (isset($item['r_size'])) {
				
				if ($item['r_size'] == '0 x 0') {
					
					$id   = $item['ID'];
					$name = $item['Name'];
					$ext  = $item['ext'];
					
					$path = IMPATH.$item['FilePath'];
					
					if (is_file($path.DS.$name.$ext)) {
						
						list($rw,$rh) = getimagesize($path.DS.$name.$ext);
						list($tw,$th) = getimagesize($path.DS.$name.'_t'.$ext);
					
						safe_update("txp_image",
							"w = $rw, h = $rh,
							 thumb_w = $tw, thumb_h = $th",
							"ID = $id");
					
						$list[$key]['r_size'] = "$rw x $rh";
					}
				}
			}
		}
		
		$html.= $images->viewList($list);
		
		// ---------------------------------------------------------------------
		
		save_session($EVENT);
		save_session($WIN);
	}

// -----------------------------------------------------------------------------
	function image_multi_edit()
	{
		global $WIN;
		
		$method   = gps('edit_method');
		$selected = gps('selected',array());
		$old	  = array();
		$warning  = '';
		
		// -----------------------------------------------------
		// PRE-PROCESS
		// filtering out invalid actions
		
		if ($method == 'paste' or $method == 'new') {
			
			// unset destinations that are images
			
			$selected = array_map('assert_int', $selected);
			
			$selcount = count($selected);
			
			foreach ($selected as $key => $id) {
				
				if (getCount("txp_image","ID = $id AND `Type` = 'image'")) {
					
					unset($selected[$key]);
				}
			}
			
			if ($selcount != 0 and count($selected) == 0) {
				
				return image_list();
			}
		}
		
		if ($method == 'move') {
			
			// don't move anything into an image type
			
			$selected = expl(gps('checked'));
			$selected = array_map('assert_int', $selected);
			
			$destination = array_pop($selected);
			$selected[] = $destination;
			
			if (getCount("txp_image","ID = $destination AND `Type` = 'image'")) {
				
				return image_list();
			}
		}
		
		if ($method == 'save') {
		
			$selected = array_map('assert_int', $selected);
		
			$old = safe_column("ID,Name","txp_image","ID IN (".in($selected).")");
		}
		
		if ($method == 'trash') {
			
			$no_trash = array();
			
			// do not allow images or folders that contain images that 
			// are attached to articles to be trashed 
			
			$selected = array_map('assert_int', $selected);
			
			foreach ($selected as $key => $id) {
				
				$type = fetch('Type','txp_image','ID',$id);
				
				if ($type == 'image') {
					
					if (safe_count('textpattern',"ImageID = $id AND Trash = 0")) {
						unset($selected[$key]);
						$no_trash[] = $id;
					}
					
				} elseif ($type == 'folder') {
					
					if (safe_count_tree($id,
						"txp_image AS i JOIN textpattern AS t ON i.ID = t.ImageID",
						"i.Type = 'image' AND t.Trash = 0")) {
						unset($selected[$key]);
						$no_trash[] = $id;
					}
				}
			}
			
			if ($no_trash) {
				
				$warning = 'Images that are attached to articles must be removed from those articles before they can be put in the trash!';	
			}
		}
		
		// -----------------------------------------------------
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$selected  = $multiedit->selected;
		$changed   = $multiedit->changed;	
		
		// -----------------------------------------------------
		// POST-PROCESS
		
		if ($changed) {
		
			$images = safe_column("ID","txp_image","ID IN (".in($changed).") AND `Type` = 'image'");
			
			if ($images) {
			
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($method == 'duplicate') {
				
					$message = image_duplicate($images);
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($method == 'paste') {	
					
					$message = image_duplicate($images);
					
					add_folder_image();
					
					if ($multiedit->method == 'cut_paste') {
					
						refresh_folder_image();
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($method == 'save') {			
					
					$new = safe_column("ID,Name","txp_image","ID IN (".in($images).")");
					
					foreach ($new as $id => $name) {
						image_rename($name,$old[$id],$id);
					}
					
					$selected = array();
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				if (in_list($method,'move,group')) {
					
					add_folder_image();
					refresh_folder_image();
					
					$selected = array();
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - -
				
			if ($method == 'trash') {
				
				$trashed = $images;
				
				// add contents of trashed folders to trash list
				
				$folders = safe_column("ID","txp_image","ID IN (".in($changed).") AND `Type` = 'folder'");
				
				if ($folders) {
					
					$rows = safe_rows_tree($folders,"ID","txp_image","`Type` = 'image'",0,0);
					
					foreach ($rows as $row) {
					
						$trashed[] = $row['ID'];
					}
				}
				
				// remove trashed images from articles in textpattern table
				
				// safe_update("textpattern","ImageID = -ABS(ImageID)","ImageID IN (".impl($trashed).")");
				
				// refresh folder images
				
				if ($trashed) {
				
					$folders = safe_column("ParentID","txp_image","ID IN (".impl($trashed).")");
				
					refresh_folder_image($folders);
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$WIN['checked'] = $selected;
		
		if ($warning) $message .= '<span class="warning">'.$warning.'<a href="#" class="dismiss">OK</a></span>';
		
		image_list($message);
	}
	
// -----------------------------------------------------------------------------
	function image_edit_type(&$in,&$html) 
	{
		extract($in);
		
		if ($Type == 'folder') {
			
			$html[1]['excerpt'] = '';
		}
	}
	
// -----------------------------------------------------------------------------
	function image_edit($message='',$id='') 
	{
		global $WIN, $html, $app_mode, $txpcfg, $img_dir, $file_max_upload_size, $txp_user, $smarty;
		
		$id = gps('id',gps('ID',0));
		$id = assert_int($id);
		
		$rs = safe_row(
			"*, Name AS name, 
				Body AS caption, 
			    Keywords AS keywords,
			    NULL AS description,
			    unix_timestamp(Posted) AS uDate", 
			    "txp_image", 
			    "ID = $id AND Trash = 0");
		
		if (!$rs) return;
		
		extract($rs);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own'))) {
			
			image_list(gTxt('restricted_area'));
			
			return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($Type == 'folder') {
			
			$html = event_edit();
			
			return;
		}
		
		if ($app_mode != 'async') {
			
			$html = pagetop(gTxt('edit_image'),$message,$WIN['winid']);
		}
		
		$all_categories = safe_rows_tree('',
			"ID,Name AS name,Title AS title,Level AS level,ParentID AS parent",
			"txp_category");
		
		$categories = safe_column("name","txp_content_category","article_id = $id AND type = 'image'");
		$categories = ($categories) 
			? implode(', ',safe_column("Title","txp_category","Name IN (".in($categories).") AND Trash = 0"))
			: '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Previous/Next records
	
		$prevnext_links = '';
		
		// TODO: $WIN->get("prevnext/$ID","0,0");
		
		if (isset($WIN['prevnext'][$ID])) {
			
			list($prev_id,$next_id) = explode(',',$WIN['prevnext'][$ID]);
			
			if ($prev_id) {
				$prevnext_links .= prevnext_link(gTxt('prev'),'image','edit',$prev_id,gTxt('prev'));
			}
			
			if ($next_id) {
				$prevnext_links .= prevnext_link(gTxt('next'),'image','edit',$next_id,gTxt('next'));
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$smarty->assign('from',gps('from'));
		$smarty->assign('winid',$WIN['winid']);
	 // $smarty->assign('new_category',$new_category);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$smarty->assign('txt_image_title',gTxt('title'));
		$smarty->assign('txt_image_name',gTxt('file_name'));
		$smarty->assign('txt_category',gTxt('image_category'));
		$smarty->assign('txt_alt_text',gTxt('alt_text'));
		$smarty->assign('txt_caption',gTxt('caption'));
		$smarty->assign('txt_copyright',gTxt('copyright'));
		$smarty->assign('txt_save',gTxt('save'));
		$smarty->assign('txt_replace_image',gTxt('replace_image'));
		$smarty->assign('txt_replace_thumbnail',gTxt('replace_thumbnail'));
		$smarty->assign('txt_upload',gTxt('upload'));
	
		$smarty->assign('prevnext',$prevnext_links);
		
		$smarty->assign('regular',image_edit_r($id,$rs));
		$smarty->assign('thumb',image_edit_t($id,$rs));
		
		$smarty->assign('title',fetch("Title","txp_image","ID",$id));
		$smarty->assign('name',$name);
		$smarty->assign('ext',$ext);
	 // $smarty->assign('category',treeSelectInput('category',$categories,$category,'',0,''));
		$smarty->assign('category',$categories);
		$smarty->assign('alt',$alt);
		$smarty->assign('caption',$caption);
		$smarty->assign('copyright',$copyright);
		$smarty->assign('keywords',$keywords);
		$smarty->assign('description',$description);
		$smarty->assign('effect',(isset($effect)) ? $effect : '');
			
		$html .= $smarty->fetch('image/edit.tpl');
		
		save_session($WIN);
	}

// -----------------------------------------------------------------------------
	function image_edit_r($id=0,$data=null) 
	{
		global $img_dir,$smarty,$app_mode;
		 
		$id = assert_int(gps('id'),$id);
		 
		if (!$data) {
		 	
		 	$data = safe_row("*,Name AS name","txp_image","ID = '$id'");
		}
		
		extract($data);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$rw = $w;
		$rh = $h;
		
		$w = 0;
		$h = 0;
		
		if (is_file(IMPATH.$FilePath.'/'.$name.$ext)) {
			list($w,$h)   = getimagesize(IMPATH.$FilePath.'/'.$name.$ext);
			list($rw,$rh) = getimagesize(IMPATH.$FilePath.'/'.$name.'_r'.$ext);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		 
		$smarty->assign('id',$id);
		$smarty->assign('w',$w);
		$smarty->assign('h',$h);
		$smarty->assign('reg_src','../'.$img_dir.'/'.$FilePath.'/'.$name.'_r'.$ext.NOCACHE);
		$smarty->assign('reg_w',$rw);
		$smarty->assign('reg_h',$rh);
		$smarty->assign('reg_w_by',true);
		$smarty->assign('reg_h_by',false);
		$smarty->assign('reg_w_new',$rw);
		$smarty->assign('reg_h_new',$rh);
		
		$out = $smarty->fetch('image/edit_regular.tpl');
		
		if ($app_mode == 'async') {
			
			echo $out;
			
		} else {
			
			return $out;
		} 
	}

// -----------------------------------------------------------------------------
	function image_edit_t($id=0,$data=null) 
	{
		global $img_dir,$smarty,$app_mode;
		 
		$id = assert_int(gps('id'),$id);
		 
		if (!$data) {
		 	
		 	$data = safe_row("*,Name AS name","txp_image","ID = '$id'");
		}
		
		extract($data);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$rw = $w;
		$rh = $h;
		$tw = $thumb_w;
		$th = $thumb_h;
		
		$w = 0;
		$h = 0;
		
		if (is_file(IMPATH.$FilePath.'/'.$name.$ext)) {
			list($w,$h)   = getimagesize(IMPATH.$FilePath.'/'.$name.$ext);
			list($tw,$th) = getimagesize(IMPATH.$FilePath.'/'.$name.'_t'.$ext);
			list($rw,$rh) = getimagesize(IMPATH.$FilePath.'/'.$name.'_r'.$ext);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// maximum size of thumbnail 
		
		$thumb_w_max = $rw;
		$thumb_h_max = $rh;
		
		$dl = new DirList(IMPATH.$FilePath,'lastmod DESC');
	
		if ($alt_thumb = $dl->getFile('/_THUMB/')) {
			list($thumb_w_max,$thumb_h_max) = getimagesize(IMPATH.$FilePath.'/'.$alt_thumb);
		}
			
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// current thumbnail style
		
		$class1 = ($thumbnail == 1) ? ' sel' : '';
		$class2 = ($thumbnail == 2) ? ' sel' : '';
		$class3 = ($thumbnail == 3) ? ' sel' : '';
		$class4 = ($thumbnail == 4) ? ' sel' : '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$smarty->assign('id',$id);
		$smarty->assign('w',$w);
		$smarty->assign('h',$h);
		$smarty->assign('thumb_src','../'.$img_dir.'/'.$FilePath.'/'.$name.'_t'.$ext.NOCACHE);
		$smarty->assign('thumb',$thumbnail);
		$smarty->assign('thumb_w',$tw);
		$smarty->assign('thumb_h',$th);
		$smarty->assign('thumb_w_by',true);
		$smarty->assign('thumb_h_by',false);
		$smarty->assign('thumb_w_new',$tw);
		$smarty->assign('thumb_h_new',$th);
		$smarty->assign('thumb_w_max',$thumb_w_max);
		$smarty->assign('thumb_h_max',$thumb_h_max);
		$smarty->assign('thumb_custom',$thumbnail);
		$smarty->assign('horizontal',$rw >= $rh);
		$smarty->assign('class1',$class1);
		$smarty->assign('class2',$class2);
		$smarty->assign('class3',$class3);
		$smarty->assign('class4',$class4);
		
		$out = $smarty->fetch('image/edit_thumb.tpl');
		
		if ($app_mode == 'async') {
			
			echo $out;
			
		} else {
			
			return $out;
		} 
	}
	
// -----------------------------------------------------------------------------
	function image_replace() 
	{
		image_insert(true);
	}

// -----------------------------------------------------------------------------
	function image_replace_thumbnail() 
	{
		global $extensions;
		
		if (!has_privs('image.edit.own'))
		{
			return;
		}
		
		$id  = assert_int(gps('file_id',0));
		$url = gps('url');
		
		if ($url) {
		
			$file = get_image_from_url($url);
			
		} else {
			
			$file = $_FILES['thefile']['tmp_name'];
		}
		
		extract(safe_row('name,ext,thumbnail,thumb_w,thumb_h,FileDir,effect',
			'txp_image',"ID = $id"));
		
		$image_path = IMPATH.get_file_id_path($FileDir);
		$error_message = '';

		list($w,$h,$extension) = getimagesize($file);
		
		if (($file !== false) && $extensions[$extension]) {
			
			$alt_ext = $extensions[$extension];
			
			$newpath = $image_path.DS.$name.'_THUMB'.$alt_ext;
			
			if (shift_uploaded_file($file, $newpath) == false) {
			
				$error_message = $newpath.sp.gTxt('upload_dir_perms');
			
			} else {
				
				chmod($newpath,0755);
				
				$rt = new ImageManipulation($name.'_THUMB',$alt_ext,$image_path,0,1,'',1);
				$rt->ext_t = $ext;
				
				if ($thumbnail != '4') {
				
					image_resize_t($id,$thumb_w,$thumb_h);
					
				} else {
				
					if ($thumb_w > $thumb_h) {
						
						if ($w > $h) {
							image_resize_t($id,$thumb_w);
						} else {
							image_resize_t($id,0,$thumb_w);
						}
					
					} else {
						
						if ($w > $h) {
							image_resize_t($id,$thumb_h);
						} else {
							image_resize_t($id,0,$thumb_h);
						}
					}
				}
				/*
				$rt->thumbnail(150,150,0,'xx',90);
				$rt->thumbnail(100,100,0,'x',90);
				$rt->thumbnail(50,50,0,'y',90);
				$rt->thumbnail(20,20,0,'z',100);
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// reapply effect if there is one
				
				if (isset($effect) and $effect != 'none') {
					
					image_effect($id,'t',$effect); 
					image_effect($id,'xx',$effect);
					image_effect($id,'x',$effect);
					image_effect($id,'y',$effect);
					image_effect($id,'z',$effect);
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				*/
				echo "/$id";
			}
		}
	}
		
// -----------------------------------------------------------------------------
	function image_insert($parent_id=0,$in=array(),$replace=false,$existing='') 
	{	
		global $PFX, $WIN, $prefs, $txpcfg, $extensions, $txp_user, $tempdir, $app_mode;
		
		if (!has_privs('image.edit.own'))
		{
			image_list(gTxt('restricted_area'));
			return;
		}
		
		extract($txpcfg);
		
		$incoming = $in;
		
		// ---------------------------------------------------------
		// are we coming from a function or directly 
		// from a post?
		
		$from_post = !($parent_id or $incoming or $existing);
		
		// ---------------------------------------------------------
		// image may be coming from a remote url 
		
		$url = '';
		
		if (is_array($incoming)) {
			
			if (isset($incoming['url'])) {
			
				$url = $incoming['url'];
			
				unset($incoming['url']);
			}
			
		} elseif ($incoming) {
			
			$url = $incoming;
			
			$incoming = array();
		}
		
		if ($app_mode == 'async') {
		
			$url = gps('url',$url);
		}
		
		// ---------------------------------------------------------
		// thumbnail size 
		
		$thumb_w = get_pref('thumbnail_image_size',100);
		$thumb_h = get_pref('thumbnail_image_size',100);
		
		if (is_array($incoming)) {
			
			if (isset($incoming['thumb'])) {
			
				$thumb_w = $thumb_h = $incoming['thumb'];
			}
		}
		
		// ---------------------------------------------------------
		// replacement image 
		
		$id = 0;
		$old_name = '';
		$old_ext  = '';
		
		if (gps('file_id')) {
			
			// replace existing image with inserted image 
			
			$id = assert_int(gps('file_id',0));
		}
		
		// ---------------------------------------------------------
		// parent folder of inserted image 
		
		$path = array();
		
		if (!$id and !$parent_id) {
			
			// find a suitable place if no parent id is given 
			
			if (gps('from_event') == 'list') {
				
				// when an image file is dropped into the article list page 
				
				$from_id = assert_int(gps('from_id',0));
				
				// get the name of the main article on the list page
				
				$docname = fetch("Name","textpattern","ID",$from_id);
				
				if ($folder = safe_field("ID","txp_image","Name = '$docname' AND Trash = 0")) {
					
					// an existing folder with that name in images 
					
					$parent_id = $folder;
				
				} else {
					
					// create a new folder with that name in images
					
					$parent_id = fetch("ID","txp_image","ParentID",0);
					
					$path = array($docname);
				}
			
			} elseif (gps('from_event') == 'image' and gps('parent')) {
			
				$parent_id = assert_int(gps('parent',0));
				
				$parent = safe_row("ParentID,Type","txp_image","ID = $parent_id");
					
				if ($parent['Type'] != 'folder') {
						
					$parent_id = $parent['ParentID'];
				}
				
			} elseif (isset($WIN['content']) and $WIN['content'] != 'image') {
				
				$parent_id = fetch("ID","txp_image","ParentID",0);
			}
			
			if (!$parent_id and isset($WIN['id'])) {
				
				$parent_id = $WIN['id'];
			}
		}
		
		// ---------------------------------------------------------
		// get the image file 
		
		if ($url) {
			
			// new image from a URL 
			
			$file = get_image_from_url($url,$incoming);
			$name = explode('/',$file);
			$name = array_pop($name);
			
		} elseif ($existing) {
			
			// new image from FTP folder
			
			$file     = IMPATH_FTP.$existing;
			$path     = explode('/',$existing);
			$name     = array_pop($path);
			
		} elseif ($id) {
			
			// replacement image
			
		 // $category = fetch('category','txp_image','id',$id);
		 	$file     = $_FILES['thefile']['tmp_name'];
			$name     = $_FILES['thefile']['name'];
			$old_name = fetch('name','txp_image','id',$id);
			$old_ext  = fetch('ext','txp_image','id',$id);
		
		} else {
			
			// new image 
			
			$category = '';
			$file     = $_FILES['thefile']['tmp_name'];
			$name     = $_FILES['thefile']['name'];
		}
	
		if (!is_file($file)) {
			
			if ($app_mode == 'async') {
				
				if ($_FILES['thefile']['error'] == 1) {
					echo "Filesize exceeds maximum of ".ini_get('upload_max_filesize');
				}
			}
			
			return -1;
		}
		
		// ---------------------------------------------------------
		// file exists
		
		list($w,$h,$extension) = getimagesize($file);
		
		$square = ($w == $h) ? true : false;
		$wide   = ($w > $h)  ? true : false;
		$tall   = ($w < $h)  ? true : false;
		
		if ($wide || $square)
			$thumbnail = $crop = 2; // middle crop
		else
			$thumbnail = $crop = 1; // top crop
		
		if (($file !== false) && @$extensions[$extension]) {
			
			$ext = $extensions[$extension];
			$name = explode('.',$name);
			array_pop($name);
			$name = $title = implode('.',$name);
			$name = make_name($name);
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			
			$title = (isset($incoming['title']))
				? $incoming['title']
				: make_title($title);
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			
			$filename = $name . $ext;
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($id) {	// REPLACEMENT IMAGE	
				
				$rs = safe_update(
					"txp_image",
					"w         = '$w',
					 h         = '$h',
					 Name      = '$name',
					 ext       = '$ext',
					 AuthorId  = '$txp_user',
					 thumbnail = '$thumbnail',
					 LastMod   = NOW()",
					 "ID = '$id'"
				);
			
			} else {	// NEW IMAGE
				
				$parent_id = event_add_folder("txp_image",$parent_id,$path);
				
				$set = array(
					'Name'    	=> $name,
					'Title'     => $title,
					'ext'       => $ext,
					'w'         => $w,
					'h'         => $h,
					'thumbnail' => $thumbnail,
					'Type'		=> 'image',
					'FileDir'   => 'SELECT MAX(FileDir) + 1 FROM '.$PFX.'txp_image'
				);
				
				include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
				
				list($message,$id) = content_create($parent_id,$set,'txp_image','image');
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - -
			
			if (!$id) {
				
				$message = gTxt('file_upload_failed').' (db_add)';
				
				if ($app_mode == 'async') {
					echo $message; return;
				}
				
				image_list(array($message, E_ERROR));

			} else {
				
				$id = assert_int($id);
				$id_path = get_file_id_path(fetch('FileDir','txp_image','ID',$id));
				$image_path = IMPATH.$id_path;
				
				safe_update('txp_image',"FilePath = '$id_path', ImageID = $id","ID = $id");
				
				if (!is_dir($image_path)) {
					@mkdir($image_path,0777,true); 
				}
				
				if (!is_dir($image_path) or !is_writable($image_path)) {
					
					$id_path = '1';
					$image_path = IMPATH.$id_path;
					
					if (is_dir($image_path) and is_writable($image_path)) {
						
						safe_update('txp_image',"FileDir = '$id_path', FilePath = '$id_path'","ID = $id");
					
					} else {
						
						if ($app_mode == 'async') { echo 'ERROR'; }
						
						return;
					}
				}
				
				$shiftedfile = $image_path.'/'.$filename;
				
				shift_uploaded_file($file, $shiftedfile);
				
				if (!is_file($shiftedfile)) {
						
					$id_path = '1';
					$image_path = IMPATH.$id_path;
					
					if (is_dir($image_path) and is_writable($image_path)) {
					
						safe_update('txp_image',"FileDir = '$id_path', FilePath = '$id_path'","ID = $id");
						
						$shiftedfile = $image_path.'/'.$filename;
						shift_uploaded_file($file, $shiftedfile);
					}
				}
				
				if (!is_file($shiftedfile)) {
					
					safe_delete("txp_image","ID = '$id'");
					safe_alter("txp_image", "auto_increment=$id");
					
					// if (is_dir(IMPATH.$id)) rmdir(IMPATH.$id);
					
					$message = $shiftedfile.sp.gTxt('upload_dir_perms');
					
					if ($app_mode == 'async') {
						echo $message; return;
					}
					
					image_list(array($message, E_ERROR));
				
				} else {
					
					$original = $image_path.'/'.$name.$ext;
					$regular  = $image_path.'/'.$name.'_r'.$ext;
					
					// make regular size image
					
					if (is_file($original)) {
						
						copy($original,$regular);
						
						if ($old_name) {
							
							// match size of new image to the one it is replacing
							
							if (is_file($image_path.'/'.$old_name.'_r'.$old_ext)) {
								
								list($old_rw,$old_rh) = getimagesize($image_path.'/'.$old_name.'_r'.$old_ext);
							
								if ($old_rw > $old_rh) {
									
									if ($wide) {
										image_resize_r($id,$old_rw);
									} else {
										image_resize_r($id,0,$old_rw);
									}
								
								} else {
									
									if ($wide) {
										image_resize_r($id,$old_rh);
									} else {
										image_resize_r($id,0,$old_rh);
									}
								}
							}
						
						} else {
						
							image_resize_r($id);
						}
					}
					
					// make thumbnails (if there is no uploaded thumbnail existing)
					
					if (!is_file($image_path.'/'.$old_name.'_THUMB'.$ext)) {
						
						$tw = $thumb_w;	
						$th = $thumb_h;
					
						$tw = ($w >= $tw) ? $tw : $w;
						$th = ($h >= $tw) ? $tw : $h;
						
						$tw = ($wide) ? $th : $tw;
						$th = ($tall) ? $tw : $th;
						
						if (is_file($regular)) {
							
							// make largest admin thumbnail (150x150 pixels)
							
							$img = new ImageManipulation($name.'_r',$ext,$image_path);
							
							$img->thumbnail(150,150,$crop,'xx',90);	
							
							if (is_file($image_path.'/'.$name.'_xx'.$ext)) {
							
								// make smaller admin thumbnails
								
								$img = new ImageManipulation($name.'_xx',$ext,$image_path);
								
								$img->thumbnail(100,100,0,'x',90);
								$img->thumbnail(50,50,0,'y',90);
								$img->thumbnail(20,20,0,'z',100);
							
								// make publish thumbnail
							
								if ($tw <= 150) {
									$name_size = $name.'_xx'; $crop = 0;
								} else {
									$name_size = $name.'_r';
								}
								
								$img = new ImageManipulation($name_size,$ext,$image_path);
								
								$dim = $img->thumbnail($tw,$th,$crop,'t');
								
								$tw = $dim['w'];
								$th = $dim['h'];
							}
							
							safe_update("txp_image","thumb_w = '$tw', thumb_h = '$th'","ID = $id");
						
						} else {
							
							if ($app_mode == 'async') { 
								
								echo "Error: $regular does not exist"; return; 
							}
						}
												
					} else {
						
						foreach(array('THUMB','t','x','y','z') as $size) {
							rename(
								$image_path.'/'.$old_name.'_'.$size.$ext,
								$image_path.'/'.$name.'_'.$size.$ext
							);
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// png transparency
					
					if ($ext == '.png') {
					
						if (png_has_transparency($image_path.'/'.$name.$ext)) {
							safe_update("txp_image","transparency = 1","ID = $id");
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// metadata
					/*
					if ($ext == '.jpg') {
						
						add_jpeg_metadata(IMPATH.$image_path.'/'.$name.$ext,IMPATH.$image_path.'/'.$name.'_r'.$ext);
						add_jpeg_metadata(IMPATH.$image_path.'/'.$name.$ext,IMPATH.$image_path.'/'.$name.'_t'.$ext);
						
						if ($keywords = get_iptc_data_keywords(IMPATH.$image_path.'/'.$filename)) {
							$keywords = doSlash(preg_replace('/"/','',$keywords));
							safe_update("txp_image","keywords = '$keywords'","id = '$id'");
						}
						
						if ($description = get_iptc_data_description(IMPATH.$image_path.'/'.$filename)) {
							$description = doSlash($description);
							safe_update("txp_image","description = '$description'","id = '$id'");
						}
					}
					*/
					// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// add image to parent folders
					
					add_folder_image();
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					
					if ($url) {
						
						if ($from_post) { 
							
							echo $id; return;
						}
						
						return $id;
						
						// image_list($name);
						
					} elseif ($existing) {
						
						return $id; // back to image list page
						
					} else if ($app_mode == 'async') {
						
						if (is_file($image_path.'/'.$name.$ext)) {
							echo "/$id"; return;
						}
						
					} else {
						
						// continue to image edit page
						
						$_POST['from'] = ($replace) ? 'replace' : 'insert';
					
						$message = gTxt('image_uploaded', array('{name}' => $name));
						
						// alert for duplicate image ID
				
						if (safe_count("txp_image","Type = 'image' AND ImageID = $id") > 1) {
							$message = array("$name has duplicate image ID $id",E_ERROR);
						}
						
						// ???
						// image_edit($message,$id,'',0,$existing);
					}
				}
			}
		} else {
			
			if ($file === false) {
				
				$message = upload_get_errormsg($_FILES['thefile']['error']);
				
				if ($app_mode == 'async') { echo $message; return; }
					
				image_list(array($message,E_ERROR));
				
			} else {
				
				$massage = gTxt('only_graphic_files_allowed');
				
				if ($app_mode == 'async') { echo $message; return; }
				
				image_list(array($message,E_ERROR));
			}
		}
	}
	
// -------------------------------------------------------------
	function image_resize_r($id=0,$width=0,$height=0) 
	{
		global $event, $prefs, $app_mode;
		
		extract(gpsa(array('bywidth','byheight')));
		
		$id 	= assert_int(gps('id',$id));
		$width  = assert_int(gps('new_width',$width));
		$height = assert_int(gps('new_height',$height));
		
		$rs = safe_row("*,Name AS name","txp_image","ID = '$id'");
		
		if ($rs) {
			
			extract($rs);
			
			$image_path = IMPATH.$FilePath;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (!$width and !$height) {
				
				$width = $height = 0;
				
				$size = get_pref('regular_image_size',500);
				$size_for = get_pref('regular_image_size_for','longer');
				
				if ($size_for == 'width') {
					
					$width = $size;	
				
				} elseif ($size_for == 'height') {
					
					$height = $size;
						
				} elseif ($w < $h) {
				
					$width  = ($size_for == 'longer') ? 0 : $size;
					$height = ($size_for == 'longer') ? $size : 0; 
					
				} else {
					
					$width  = ($size_for == 'longer') ? $size : 0; 
					$height = ($size_for == 'longer') ? 0 : $size;
				}
			}
		
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$rt = new ImageManipulation($name,$ext,$image_path);
			
			// make regular size image
			
			$rt->resize($width,$height);
			
			safe_update("txp_image","LastMod = NOW()","ID = $id");
			
			$message = "regular image resized";
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// metadata
					
			if ($ext == '.jpg') {
				
				// add_jpeg_metadata(IMPATH.$image_path.'/'.$name.$ext,IMPATH.$image_path.'/'.$name.'_r'.$ext);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// reapply effect if there is one
			
			if (isset($effect) and $effect != 'none') {
				
				image_effect($id,'r',$effect); 
			}
		}
	}

// -------------------------------------------------------------
	function image_resize_t($id=0,$width=0,$height=0,$crop=0)
	{
		global $event,$app_mode;
		
		extract(gpsa(array('bywidth','byheight')));
		
		$id 	= assert_int(gps('id',$id));
		$width  = assert_int(gps('new_width',$width));
		$height = assert_int(gps('new_height',$height));
		$crop   = assert_int(gps('crop',$crop));
		
		$rs = safe_row("*,ext,ext AS ext_r,Name AS name","txp_image","ID = '$id'");
		
		if ($rs) {
			
			extract($rs);
			
			$image_path = IMPATH.$FilePath;
			
			if (!$crop) $crop = $thumbnail;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// use alternate thumbnail file if any  
			
			$dl = new DirList($image_path,'lastmod DESC');
			
			if ($alt_thumb = $dl->getFile('/_THUMB/')) {
				
				$name = get_file_name($alt_thumb); 
				$ext  = '.'.get_file_ext($alt_thumb);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$rt = new ImageManipulation($name,$ext,$image_path,0,1,'',$thumbnail);
			
			// make public thumbnail
			
			$rt->ext_t = $ext_r;
			$size = $rt->thumbnail($width,$height,$crop,'t');
				
			if ($size) {
				$width  = array_shift($size);
				$height = array_shift($size);
			}
			 
			// make admin thumbnails same as public unless public is rectangular
			
			$admin_crop = $crop;
			
			if ($admin_crop == 4) {
				
				if ($width > $height) {
					$admin_crop = 2;
				} else {
					$admin_crop = 1;
				}
			}
				
			$rt->thumbnail(150,150,$admin_crop,'xx',90);
			$rt->thumbnail(100,100,$admin_crop,'x',90);
			$rt->thumbnail(50,50,$admin_crop,'y',90);
			$rt->thumbnail(20,20,$admin_crop,'z',100);
			
			// save crop setting for this image
			
			safe_update("txp_image",
			 	"thumbnail = $crop,
				 thumb_w = $width,
				 thumb_h = $height,
				 LastMod = NOW()",
				"ID = $id");
			
			$message = "thumbnail resized";
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// metadata
					
			if ($ext == '.jpg') {
				
				// add_jpeg_metadata(IMPATH.$image_path.'/'.$name.$ext,IMPATH.$image_path.'/'.$name.'_t'.$ext);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// reapply effect if there is one
			
			if (isset($effect) and $effect != 'none') {
				
				image_effect($id,'t',$effect); 
			}
		}
	}

// -------------------------------------------------------------
	function image_save($id=0) 
	{
		extract(doSlash(gpsa(array('category','caption','alt','copyright','title','from'))));
		
		$name = make_name(gps('name'));
		$safename = doSlash($name); // not necessary
		
		if (!$id) $id = gps('id',gps('ID'));
		
		$id = assert_int($id);
		
		$author = fetch('AuthorID AS author','txp_image','ID',$id);
		
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}
		
		$set = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// rename all image file variations when name was changed
		
		if (isset($_POST['name'])) {
		
			$curr_name = fetch("Name","txp_image","ID",$id);
			
			if ($name and $name != $curr_name) {
				
				$name = image_rename($name,$curr_name,$id);
			
			} else {
			
				$name = $curr_name;
			}
			
			$set['Name'] = "'$name'";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		/*
		if (isset($_POST['category'])) {
			
			$categories = safe_column("name","txp_content_category",
				"article_id = $id AND type = 'image'");
			
			if (!in_array($category,$categories)) {
				
				$categories[] = $category;
				$pos = count($categories);
				
				safe_insert("txp_content_category",
					"article_id = $id, 
					 name = '$category',
					 type = 'image',
					 position = $pos");
				
				$set['Categories'] = doQuote(implode(',',$categories));
			}
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($_POST['alt'])) {
			
			$set['alt'] = "'".trim($alt)."'";
		}
		
		if (isset($_POST['caption'])) {
			
			$set['Body'] = "'".trim($caption)."'";
		}
		
		if (isset($_POST['copyright'])) {
			
			$set['copyright'] = "'".trim($copyright)."'";
		}
		
		$set['LastMod'] = "NOW()";
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		safe_update("txp_image",$set,"ID = '$id'");
		
		$set = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($_POST['name'])) {
			$set['Name'] = "'$name'";
		}
		
		if (isset($_POST['title'])) {
			$set['Title'] = "'$title'";
		}
		
		if ($set) {
		
			safe_update("txp_image",$set,"ID = $id");
		
			update_lastmod($id);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($from == 'list') {
			
			return;
		}
		
		$_POST['from'] = 'save';
		
		image_edit(gTxt('image_updated', array('{name}' => $name)));
	}

// -------------------------------------------------------------
	function image_delete($id)
	{
		$row = safe_row("FilePath,Name,ext","txp_image","ID = $id");
		
		if (count($row)) {
			
			extract($row);
			
			$sizes = array('','r','t','xx','x','y','z');
			
			foreach($sizes as $size) {
				
				$size = ($size) ? '_'.$size : '';
				
				$file = IMPATH.$FilePath.DS.$Name.$size.$ext;
				
				if (is_file($file)) unlink($file);
			}
		}
	}

// -------------------------------------------------------------
	function image_thumbnail_insert() 
	{
		global $txpcfg, $extensions, $txp_user, $img_dir, $path_to_site;
		
		extract($txpcfg);
		$id = assert_int(gps('id'));
		
		$author = fetch('author', 'txp_image', 'id', $id);
		if (!has_privs('image.edit') && !($author == $txp_user && has_privs('image.edit.own')))
		{
			image_list(gTxt('restricted_area'));
			return;
		}

		$_POST['from'] = 'thumbnail_insert';
		
		$file = $_FILES['thefile']['tmp_name'];
		extract(safe_row('FilePath,name,ext AS ext_r','txp_image','ID',$id));
		
		// $file = get_uploaded_file($file);
		
		list(,,$extension) = getimagesize($file);
		
		if (($file !== false) && $extensions[$extension]) {
			
			$ext = $extensions[$extension];

			if ($ext == $ext_r) {
				
				$newpath1 = IMPATH.$FilePath.'/'.$name.'_THUMB'.$ext; 	// original thumbnail 
				$newpath2 = IMPATH.$FilePath.'/'.$name.'_t'.$ext; 		// public thumbnail
					
				if(shift_uploaded_file($file, $newpath1) == false) {
				
					image_list($newpath1.sp.gTxt('upload_dir_perms'));
				
				} else {
					
					@copy($newpath1,$newpath2); 
					
					chmod($newpath1,0755);
					chmod($newpath2,0755);
					
					safe_update("txp_image", "thumbnail='4'", "id='$id'");
					
					$rt = new ImageManipulation($name,$ext,$FilePath,0,1,'',1);
					$rt->setID($id);
					$rt->thumbnail(100,100,0,'x',90);
					$rt->thumbnail(50,50,0,'y',90);
					$rt->thumbnail(20,20,0,'z',100);
					
					$message = gTxt('image_uploaded', array('{name}' => $name));
					image_edit($message,$id);
				}
			} else {
				image_edit(array("File must be ".strtoupper(substr($ext_r,1))." format",E_ERROR),$id);
			}
			
		} else {
			if ($file === false)
				image_edit(array(upload_get_errormsg($_FILES['thefile']['error']), E_ERROR),$id);
			else
				image_edit(array(gTxt('only_graphic_files_allowed'), E_ERROR),$id);
		}
	}

// -------------------------------------------------------------
	function image_thumbnail_delete() 
	{
		global $txpcfg;
		
		extract($txpcfg);
		
		$id = assert_int(gps('id'));
		
		$rs = safe_row("FilePath,name,ext","txp_image","id = $id");
		
		if ($rs) {
		
			extract($rs); 
			
			safe_update("txp_image","thumbnail = 0","id = '$id'");
			
			if(is_file(IMPATH.$FilePath.'/'.$name.'_THUMB'.$ext))
				unlink(IMPATH.$FilePath.'/'.$name.'_THUMB'.$ext);
			
			$crop = 0;
			
			$rt = new ImageManipulation($name,$ext,$FilePath);
			$rt->setID($id);
			$rt->thumbnail(0,100,$crop);
			$rt->thumbnail(100,100,0,'x',90);
			$rt->thumbnail(50,50,0,'y',90);
			$rt->thumbnail(20,20,0,'z',100);
			
			$_POST['from'] = 'thumbnail_delete';
			
			image_edit(messenger("custom thumbnail","","removed"),$id);
			
		} else image_list();
	}

//------------------------------------------------------------------------------
	function image_add_folder($parentid=0,$title='') 
	{
		global $app_mode;
		
		if (!$parentid) {
			$parentid = safe_field('ID','txp_image',"ParentID = 0 AND Trash = 0");
		}
		
		if (!$title) {
			$title = make_title(gps('title',$title));
		}
		
		list($message,$ID,$status) = content_create($parentid,array(
			'Title' => $title,
			'Type'	=> 'folder'
		),'txp_image','image');
		
		if ($app_mode == 'async') { echo "/$ID"; }
		
		return $ID;
	}
	
// =============================================================================
	function image_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changecategory'  => gTxt('changecategory'),
			'changeauthor'    => gTxt('changeauthor'),
			'delete'          => gTxt('delete'),
		);

		if (has_single_author('txp_image'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('image.delete.own') && !has_privs('image.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('image', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------
	function image_upload_form() 
	{	
		global $WIN, $file_max_upload_size, $smarty;
	
		$smarty->assign('txt_upload_file',gTxt('upload_file'));
		$smarty->assign('txt_upload_image',gTxt('upload_image'));
		$smarty->assign('txt_upload',gTxt('upload'));
		$smarty->assign('txt_create',gTxt('create'));
		$smarty->assign('txt_search',gTxt('search'));
		$smarty->assign('txt_delete',gTxt('delete'));
		
		$smarty->assign('pophelp_upload',popHelp('upload'));
		
		$smarty->assign('winid',$WIN['winid']);
		$smarty->assign('max_file_size',$file_max_upload_size);
	
		return $smarty->fetch('image/upload.tpl');
	}

// -------------------------------------------------------------
	function image_effect($id=0,$size='rtxyz',$effect='') 
	{	
		global $event, $prefs, $app_mode;
		
		$id      = assert_int(gps('id',$id));
		$effect  = gps('effect',$effect);
		$changed = false;
		
		$rs = safe_row("FilePath,Name,ext,effect AS current_effect","txp_image","ID = '$id'");
		
		if ($rs) {
			
			extract($rs);
			
			$image_path = IMPATH.$FilePath;
			
			if ($effect != 'none') {
				
				foreach (dirlist($image_path) as $file) {
					
					if (preg_match('/^'.$Name.'_['.$size.'][\_\.]/',$file)) {
						
						$filter = false;
						
						switch ($ext) {
							case '.png' : $img = imagecreatefrompng($file); break;
							case '.jpg' : $img = imagecreatefromjpeg($file); break;
							case '.gif' : $img = imagecreatefromgif($file); break;
						}
						
						if ($img) {
							
							if ($effect == 'grayscale') {
							
								$filter = imagefilter($img,IMG_FILTER_GRAYSCALE);
							
							} elseif ($effect == 'sepia') {
								
								$filter = imagefilter_sepia($img);
							}
						}
						
						if ($filter) {
							
							switch ($ext) {
								case '.png' : imagepng($img,$file); break;
								case '.jpg' : imagejpeg($img,$file); break;
								case '.gif' : imagegif($img,$file); break;
							}
							
							safe_update('txp_image',"effect = '$effect'","ID = $id");
							
							$changed = true;
						}
						
						if ($img) imagedestroy($img); 	
					}	
				}
				
			} else {
				
				// redo images without any effect
				
				safe_update('txp_image',"effect = 'none'","ID = $id");
				
				list($width,$height) = getimagesize($image_path.DS.$Name.'_r'.$ext);
				image_resize_r($id,$width,$height); 
				
				list($width,$height) = getimagesize($image_path.DS.$Name.'_t'.$ext);
				image_resize_t($id,$width,$height);
				
				$changed = true;
			}
		}
		
		if ($app_mode == 'async') {
			
			echo ($changed) ? 'OK' : '';
		}
	}

// -------------------------------------------------------------
	function imagefilter_sepia(&$image)
	{
		$width  = imagesx($image);
		$height = imagesy($image);
		
		for ($_x = 0; $_x < $width; $_x++) {
		
			for ($_y = 0; $_y < $height; $_y++) {
		
				$rgb = imagecolorat($image, $_x, $_y);
				$r = ($rgb>>16)&0xFF;
				$g = ($rgb>>8)&0xFF;
				$b = $rgb&0xFF;
				
				$y = $r*0.299 + $g*0.587 + $b*0.114;
				$i = 0.15*0xFF;
				$q = -0.001*0xFF;
				
				$r = $y + 0.956*$i + 0.621*$q;
				$g = $y - 0.272*$i - 0.647*$q;
				$b = $y - 1.105*$i + 1.702*$q;
				
				if($r<0||$r>0xFF){$r=($r<0)?0:0xFF;}
				if($g<0||$g>0xFF){$g=($g<0)?0:0xFF;}
				if($b<0||$b>0xFF){$b=($b<0)?0:0xFF;}
				
				$color = imagecolorallocate($image, $r, $g, $b);
				imagesetpixel($image, $_x, $_y, $color);
			}
		}
		
		return true;
	}

// -------------------------------------------------------------
	function get_category($path) 
	{	
		$path = explode('/',$path);
		$category = '';
		
		if (isset($path[1])) {
			$test = $path[0];
			$category = (safe_count("txp_category","name = '$test' and type = 'image'")) ? $test : '';	
		}
		
		if (isset($path[2])) {
			$test = $path[1];
			$category = (safe_count("txp_category","name = '$test' and type = 'image'")) ? $test : $category;	
		}
		
		return $category;
	}

// -------------------------------------------------------------
	function get_row($id) 
	{
		global $WIN;
		
		$from = gps('from');
		
		// coming from article edit page thumbnail
		
		if ($from == 'article') {	
			
			$row = 1;
			
			$rows = safe_column('ID','txp_image',"1 ORDER BY date desc");
			
			foreach($rows as $key) 
				$rows[$key] = $row++;
			
			return $WIN['row'] = $rows[$id];	
		}
		
		// coming from image save, image replace, thumbnail insert, or thumbnail delete 
		
		if (in_array($from,array('save','replace','thumbnail_insert','thumbnail_delete'))) {
			
			return $WIN['row'];
		}
		
		// coming from image insert
		
		if ($from == 'insert') {
			
			return $WIN['row'] = 1;
		}
		
		// coming from image edit page prev/next link or image list page
		
		if ($row = gps('row')) {
		
			$WIN['row'] = $row;
		}
		
		return $WIN['row'];
	}	

// -------------------------------------------------------------
	function image_duplicate($list) 
	{
		$rows = safe_rows("ID,ImageID,FilePath",
			"txp_image","ID IN (".in($list).")");
		
		foreach($rows as $row) {
			
			extract($row);
			
			$FileDir = fetch("MAX(FileDir) + 1","txp_image");
			
			$src_dir = IMPATH.$FilePath;
			$id_path = get_file_id_path($FileDir);
			$dst_dir = IMPATH.$id_path;
			
			if (!is_dir($dst_dir)) { 
				mkdir($dst_dir,0777,true);
			}
			
			$files = dirlist($src_dir);
			
			foreach($files as $file) {
				copy($src_dir.DS.$file,$dst_dir.DS.$file);
			}
			
			safe_update("txp_image",
				"ImageID = ID, FileDir = '$FileDir', FilePath = '$id_path'",
				"ID = $ID");
		}
	}

// -------------------------------------------------------------
	function image_rename($new_name,$old_name,$id) 
	{
		$new_name = make_name($new_name);
		
		if ($new_name == $old_name) { 
			
			return $new_name;
		}
		
		if ($row = safe_row("FilePath,ext","txp_image","ID = $id")) {
		
			extract($row);
		
			$src = IMPATH.$FilePath.'/'.$old_name.$ext;
			$dst = IMPATH.$FilePath.'/'.$new_name.$ext;
		
			if (!is_file($dst)) {
				
				copy($src,$dst);
				if (is_file($dst)) unlink($src);
			
				foreach(array('r','t','x','y','z') as $size) {
			
					$src = IMPATH.$FilePath.'/'.$old_name.'_'.$size.$ext;
					$dst = IMPATH.$FilePath.'/'.$new_name.'_'.$size.$ext;
					
					copy($src,$dst);
					if (is_file($dst)) unlink($src);
				}
				
				return $new_name;
			}
		
			return $old_name;
		}
	}

// -------------------------------------------------------------
	function delete_image_files($id,$name,$ext) 
	{
		$result = false;
		
		$FilePath = fetch('FilePath','txp_image','ID',$id);
		
		if (is_file(IMPATH.$FilePath.'/'.$name.$ext))
		{
			$result = unlink(IMPATH.$FilePath.'/'.$name.$ext);
		
			if(is_file(IMPATH.$FilePath.'/'.$name.'_r'.$ext))
				unlink(IMPATH.$FilePath.'/'.$name.'_r'.$ext);
		}
		
		return $result;
	}
	
// -------------------------------------------------------------
	function delete_thumbnail_files($id,$name,$ext) 
	{	
		$FilePath = fetch('FilePath','txp_image','ID',$id);
		
		foreach(array('THUMB','t','x','y','z') as $size) {
			
			if(is_file(IMPATH.$FilePath.'/'.$name.'_'.$size.$ext))
				unlink(IMPATH.$FilePath.'/'.$name.'_'.$size.$ext);
		}
	}

// -------------------------------------------------------------
	function get_image_from_url($url,$in=array())
	{
		$file = '';
		
		if (preg_match('/\.(jpg|png|gif|jpeg)$/',$url)) {
			
			$name = basename($url);
			
			if (preg_match('/\/images\/(uploads\/|content\/\d+\/)/',$url)) {
				
				$name = explode('.',$name);
				$ext  = array_pop($name);
				$name = implode('.',$name);
				$name = preg_replace('/_?r$/','',$name);
				$name = $name.'.'.$ext;
			
			} elseif (isset($in['name'])) {
				
				$name = explode('.',$name);
				$ext  = array_pop($name);
				$name = make_name($in['name']).'.'.$ext;
			}
			
			$file = txpath.'/tmp/'.$name;
			
			file_put_contents($file,file_get_contents($url));
		}
		
		return $file;
	}

// -------------------------------------------------------------
	function add_jpeg_metadata($original,$resized) 
	{
		$original_header_data = get_jpeg_header_data($original);
		$resized_header_data  = get_jpeg_header_data($resized);
		
		$resized_header_data = put_EXIF_JPEG(get_EXIF_JPEG($original),$resized_header_data);
		$resized_header_data = put_XMP_text($resized_header_data,get_XMP_text($original_header_data));
		
		if ($ps_data = get_Photoshop_IRB($original_header_data))
			$resized_header_data = put_Photoshop_IRB($resized_header_data,$ps_data);
		
		if (FALSE == put_jpeg_header_data($resized,$resized,$resized_header_data)) {
			echo "Error - Failure to write new JPEG : $resized";
		} else {
			// echo $resized.br;
			// echo Interpret_EXIF_to_HTML(get_EXIF_JPEG($resized),$resized);
			// echo Interpret_XMP_to_HTML(read_XMP_array_from_text(get_XMP_text($resized_header_data)));
			// echo Interpret_IRB_to_HTML(get_Photoshop_IRB($resized_header_data),$resized);
		}
	}
	
// -------------------------------------------------------------
	function get_iptc_data_keywords($image_path) 
	{
		$size = getimagesize($image_path,$info);
		
		if (isset($info["APP13"])) {    
			$iptc = iptcparse($info["APP13"]);
			if (isset($iptc['2#025'])) return implode(', ',$iptc['2#025']);
		}
		
		return '';
	}
	
// -------------------------------------------------------------
	function get_iptc_data_description($image_path) 
	{
		$size = getimagesize($image_path,$info);
		
		if (isset($info["APP13"])) {    
			$iptc = iptcparse($info["APP13"]);
			if (isset($iptc['2#120'])) return $iptc['2#120'][0];
		}
		
		return '';
	} 

?>

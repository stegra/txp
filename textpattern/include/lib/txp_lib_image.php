<?php

//--------------------------------------------------------------------------------------
	function event_add_image($id=0,$ImageID=0)
	{
		global $WIN, $event, $app_mode;
		
		$do_save = $event.'_save';
		
		if (!function_exists($do_save)) {
			$do_save = 'content_save';
		}
		
		if (!$id) {
			$id = $WIN['id'];
			$ImageID = assert_int(gps('ImageID',0));
		}
		
		/* if the image that is being added is a folder image 
		 * use the ImageID of the folder
		 */
		
			$FolderImageID = safe_field("ImageID","txp_image",
				"ID = $ImageID AND Type = 'folder' AND Trash = 0");
	
			if ($FolderImageID) {
		
				$ImageID = $FolderImageID;
			}
		
		$set = array(
			'ImageID' => $ImageID
		);
		
		$do_save($id,$set);
		
		if ($app_mode == 'async') {
			
			echo $ImageID; exit;
			
		} else {
			
			$_GET['step'] = 'edit';
			$_GET['save'] = '1';
			$_GET['ID']   = $id;
		}
	}
		
// -----------------------------------------------------------------------------
	function refresh_folder_image($id=0)
	{
		global $WIN;
		
		$where = array(
			"ImageID > 0",
			"ParentID != 0",
			"Type = 'folder'"
		);
		
		if ($id) {
			$where[] = "ID IN (".in(do_list($id)).")";
		}
		
		$ids = safe_column("ID,ImageID",$WIN['table'],doAnd($where)." ORDER BY Level DESC");
		
		foreach ($ids as $id => $image) {
			
			if (!safe_count($WIN['table'],"ParentID = $id AND ImageID = $image AND Trash = 0")) {
				
				safe_update($WIN['table'],"ImageID = 0","ID = $id");
				add_folder_image($id);
			}
		}
	}

//--------------------------------------------------------------------------------------
	function event_show_image($image_id=0)
	{
		global $WIN, $event, $prefs, $smarty, $app_mode;
		
		if (!$image_id) {
			
			$article_id = assert_int(gps('article_id',0));
			$image_id   = fetch('ImageID',$WIN['table'],"ID",$article_id);
		}
		
		$html = '';
		
		if ($image_id > 0) {
		
			$row = safe_row("id,name,ext,FilePath AS path",
				"txp_image","ID = $image_id AND Type = 'image'");
			
			if ($row) {
			
				$smarty->assign('img_id',$row['id']);
				$smarty->assign('img_dir',$prefs['img_dir']);
				$smarty->assign('img_path',$row['path']);
				$smarty->assign('name',$row['name']);
				$smarty->assign('ext',$row['ext']);
				$smarty->assign('type','');
				
				$tpl = $event.'/image_view.tpl';
				
				if (!$smarty->templateExists($tpl)) {
					$tpl = 'article/image_view.tpl';
				}
				
				$html = $smarty->fetch($tpl);
			}
		}
		
		if ($app_mode == 'async') {
			
			echo $html; exit;
		} 
		
		return $html;
	}

// -------------------------------------------------------------------------------------
	function event_remove_image() 
	{
		global $WIN, $smarty, $event;
		
		$id = assert_int(gps('article'));
		
		safe_update($WIN['table'],"ImageID = -ABS(ImageID)","ID = $id OR Alias = $id");
		
		exit;
	}

//--------------------------------------------------------------------------------------
/*	function article_image_add($image=0,$article=0,$new=0)
	{	
		global $WIN, $step, $smarty;
		
		$article = (!$article) ? assert_int(gps('article',$WIN['id'])) : $article;
		$image   = (!$image)   ? assert_int(gps('image',0)) : $image;
		
		$drop = assert_int(gps('drop',0));
		
		// default values
		$data = array(			
			'display_list'   => 'before',
			'imgtype_list'   => 't',
			'align_list'     => 'right',
			'display_single' => 'before',
			'imgtype_single' => 'r',
			'align_single'   => '-'
		); 
		
		if ($article and $image) {
		
			if ($old_data = fetch('ImageData','textpattern','ID',$article)) {
			
				$old_data = explode(':',$old_data);
				
				$data['display_list'] 	=  $old_data[0];
				$data['imgtype_list'] 	=  $old_data[1];
				$data['align_list'] 	=  $old_data[2];    
				$data['display_single'] =  $old_data[3];
				$data['imgtype_single'] =  $old_data[4];
				$data['align_single'] 	=  $old_data[5];  
			}
			
			$set = array(
				'ImageID'   => $image,
				'ImageData' => doQuote(implode(':',$data))
			);
			
			if ($new) {
				
				$row = safe_row("Title,Name,Status","txp_image","ID = '$image'");
				
				if ($row) {
					$set['Title']  = doQuote($row['Title']);
					$set['Name']   = doQuote($row['Name']);
					$set['Status'] = doQuote($row['Status']);
				}
			}
			
			safe_update("textpattern",$set,"ID = $article OR Alias = $article");
		}
		
		if ($drop) {
			
			$_GET['step'] = 'edit';
			$_GET['save'] = '1';
			$_GET['ID']   = $article;
			
			$WIN['image']['view'] = 'max';
			
			article_edit($article);
		
		} elseif ($image) {
		
			$name = fetch("name","txp_image","id",$image);
			$ext  = fetch("ext","txp_image","id",$image);
			
			$smarty->assign('img_dir',get_pref('img_dir'));
			$smarty->assign('img_id',$image);
			$smarty->assign('img_path',get_image_id_path($image));
			$smarty->assign('name',$name);
			$smarty->assign('ext',$ext);
			$smarty->assign('type',article_image_type($article));
			
			$smarty->assign('display_list',$data['display_list']);
			$smarty->assign('imgtype_list',$data['imgtype_list']);
			$smarty->assign('align_list',$data['align_list']);
			$smarty->assign('display_single',$data['display_single']);
			$smarty->assign('imgtype_single',$data['imgtype_single']);
			$smarty->assign('align_single',$data['align_single']);
			
			$image = $smarty->fetch('article/image_view.tpl');
			$mini  = $smarty->fetch('article/image_view_mini.tpl');
			
			echo $image.'###'.$mini.'###'.$image;
			
			exit;
		}
	}
*/
//--------------------------------------------------------------------------------------
	function article_save_image()
	{	
		$settings = array(
			'display_list','imgtype_list','align_list',
			'display_single','imgtype_single','align_single'
		);
		
		$article = gps('article');
		$image   = gps('image');
		$name    = gps('name');
		$value   = gps('value');
		
		if (in_array($name,$settings)) {
		
			if ($article) 
				$where = "article_id = '$article'";
			else if ($image) 
				$where = "id = '$image'";
			else
				exit;
			
			// safe_update("txp_article_image","$name = '$value'",$where);
		
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($article) {
			
				$imagedata = explode(':',fetch('ImageData','textpattern','ID',$article));
				
				$settings = array_flip($settings);
				$key = $settings[$name];
				
				$imagedata[$key] = $value;
				$imagedata = implode(':',$imagedata);
				
				safe_update("textpattern","ImageData = '$imagedata'","ID = $article OR Alias = $article");
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// note: updating the lastmod here causes the main write page not 
			// to save
			
			// if ($article) update_lastmod($article);
			
			exit;
		}
	}
	
//--------------------------------------------------------------------------------------
	function article_image_type($article,$image='') 
	{
		if (!$article) {
			return 'regular';
		}
		
		$status     = safe_field("Status","textpattern","ID = '$article'");
		// $is_gallery = safe_field("is_gallery","textpattern as t,txp_section as s","t.ID = '$article' AND t.Section = s.name");
		$is_gallery = "regular";
		
		return ($is_gallery && $status != 6) ? 'gallery' : 'regular'; 
	}

?>
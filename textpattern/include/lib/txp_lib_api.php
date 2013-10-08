<?php
	
	function add_image_to_library($image_src,$folder='',$tw=0,$th=0,$crop=0) 
	{
		global $app_mode;
		
		include txpath.'/include/txp_content_image.php';
		
		$save_app_mode = $app_mode;
		$app_mode = 'async';
		$folder_id = 0;
		
		if ($folder) {
			$folder_id = safe_field("ID","txp_image","Name = '$folder' AND Type = 'folder' AND Trash = 0");
		}
		
		if ($image_id = image_insert($folder_id,$image_src)) {
					
			if ($tw or $th) {
				image_resize_t($image_id,$tw,$th,$crop);
			}
		}
		
		$app_mode = $save_app_mode;
		
		return $image_id;
	}
	
	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
	
	function save_article($id,$article) 
	{
		global $app_mode;
		
		$save_app_mode = $app_mode;
		$app_mode = 'async';
		
		$_POST = array();
		
		include_once txpath.'/include/lib/txp_lib_ContentSave.php';
		
		content_save($id,$article,'article','textpattern');
		
		$app_mode = $save_app_mode;
	}
		
	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
	
	function add_article($path='',$article='') 
	{
		global $app_mode;
		
		if (!$article) return 0;
		
		$articles = (!isset($article[0]))
			? array($article)
			: $article; 
		
		$app_mode = 'async';
		$_POST = array();
		
		include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
		
		$id     = 0;
		$new_id = 0;
		
		if (is_int($path)) {
		
			$id = $path;
		
		} else {
		 
			$path   = trim($path,'/');
			$path   = ($path) ? explode('/',$path) : '';
			$id     = safe_field('ID','textpattern',"ParentID = 0 AND Trash = 0");
		
			while ($path and $id) {
				$folder = array_shift($path);
				$id = safe_field('ID','textpattern',"ParentID = $id AND Name = '$folder' AND Trash = 0");
			}
		}
		
		if ($id) {
			
			foreach ($articles as $article) { 
				list($message,$new_id) = content_create($id,$article,'textpattern');
			}
		}
		
		$app_mode = '';
		
		return $new_id;
	}
	
	/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
	
?>
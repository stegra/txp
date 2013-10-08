<?php

include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
include_once txpath.'/include/lib/txp_lib_ContentSave.php';

// -----------------------------------------------------------------------------

	function import($id,$table,$type,$file='',$title='')
	{
		global $PFX, $WIN, $event, $columns, $txp_user, $export_as_para, $custom_group_columns;
		
		extract(get_prefs());
		
		if ($id) {
			$file  = (!$file) ? get_import_file($table) : $file;
			$title = (!$title) ? fetch("Title",$table,"ID",$id) : $title;
			add_import_id_column($table,$type);
		}
		
		if (!$file) return;
		
		$export_as_para = array('Body','Excerpt','user_html','Form','css');
		$columns = getThings('describe '.$PFX.$table); 
		$path    = array(array('id' => $id,'title' => $title));
		$item    = array();
		$info	 = array();
		$count   = 0;
		
		$custom_group_columns = array();
		
		foreach (getThings('describe '.$PFX.'txp_group') as $key => $col) {
		
			if (substr($col,0,3) == 'by_') {
				$col = substr($col,3);
				$custom_group_columns[$col] = $col;
			}
		}
		
		if ($f = fopen($file,"r")) {
			
			while (($line = fgets($f, 4096)) !== false) {
				
				if (read_item(trim($line),$item)) {
					
					process_item($item,$table,$path,$type);
					
					$info[] = import_info($path);
					
					$item = array();
					
					$count++;
				}
			}
			
			if (!feof($f)) {
				echo "Error: unexpected fgets() fail\n";
			}
			
			fclose($f);
		}
		
		if ($type) {
			
			if ($event == 'utilities') {
				$WIN['table'] = $table;
				$WIN['content'] = $type;
			}
			
			// rebuild_txp_tree(0,0,$table);
			update_path($id,1,$table,$type,0);
		}
		
		if ($type) {
			
			if ($table != 'txp_image') {
			
				if (column_exists("txp_image","ImportID")) {
					safe_update("$table AS t",
					"t.ImageID = IFNULL((
							SELECT i.ID FROM ".$PFX."txp_image AS i 
							WHERE t.ImageID = i.ImportID LIMIT 1),t.ImageID)",
					"t.ImportID IS NOT NULL");
				}
			}
			
			if ($table != 'txp_file') {
			
				if (column_exists("txp_file","ImportID")) {
					safe_update("$table AS t",
					"t.FileID = IFNULL((
							SELECT f.ID FROM ".$PFX."txp_file AS f 
							WHERE t.FileID = f.ImportID LIMIT 1),t.FileID)",
					"t.ImportID IS NOT NULL");
				}
			}
		}
		
		return array($count,$info);	
	}

// -----------------------------------------------------------------------------

	function clear_items($table,$type) {
		
		if ($type) {
			
			safe_delete($table,"1=1",0);
			safe_delete("txp_content_category","type = '$type'",0);
			safe_delete("txp_content_value","type = '$type'",0);
			safe_delete("txp_group","type = '$type'",0);
			
			$id = fetch("IFNULL(MAX(ID)+1,1)",$table);
			safe_alter($table,"AUTO_INCREMENT = $id");
			
			$id = fetch("IFNULL(MAX(ID)+1,1)","txp_content_value");
			safe_alter("txp_content_value","AUTO_INCREMENT = $id");
			
			$id = fetch("IFNULL(MAX(ID)+1,1)","txp_group");
			safe_alter("txp_group","AUTO_INCREMENT = $id");
		
		} else {
			
			safe_delete($table,"1=1",0);
			safe_alter($table,"AUTO_INCREMENT = 1");
		}
	}

// -----------------------------------------------------------------------------

	function add_import_id_column($table,$type) 
	{
		if (!$type) return;
		
		if (!column_exists($table,'ImportID')) {
		
			safe_alter($table,"ADD COLUMN `ImportID` int NULL DEFAULT NULL AFTER ParentID");
			safe_index($table,"ImportID");
			
		} else {
		
			safe_update($table,"ImportID = NULL","1=1");
		}
	}
		
// -----------------------------------------------------------------------------

	function read_item($line,&$item) {
		
		global $export_as_para; 
		
		$end_item = false;
		
		if (substr($line,1,4) == 'doc') {
			
			if (preg_match('/^<([^\>]+?)>/',$line,$matches)) {
				
				$atts = explode(' ',$matches[1]);
				array_shift($atts);
				
				foreach ($atts as $key => $attr) {
					list($attr_name,$attr_value) = explode('=',$attr);
					$atts[$attr_name] = trim($attr_value,'"');
					unset($atts[$key]);
				}
			}
		}
		
		if (substr($line,1,5) == '/item') {
			
			$end_item = true;
		}
		
		if (substr($line,1,4) == 'item') {
			
			if (preg_match('/<\/item>$/',$line)) {
			
				$line = preg_replace('/<\/item>$/','',$line);
				
				$end_item = true;
			}
			
			$line = ltrim(rtrim($line,'>'),'<');
			$atts = explode(' ',$line);
			array_shift($atts);
			
			foreach ($atts as $attr) {
				list($name,$value) = explode('=',$attr);
				$item[$name] = trim($value,'"');
			}
		
		} elseif (count($item)) {
			
			if (preg_match('/^<([^\>]+?)>/',$line,$matches)) {
				
				$atts = explode(' ',$matches[1]);
				$name = trim(array_shift($atts),'/');
				
				$line  = substr($line,strlen($matches[0]));
				$value = preg_replace('/<\/'.$name.'>$/','',$line);
				
				foreach ($atts as $key => $attr) {
					list($attr_name,$attr_value) = explode('=',$attr);
					$atts[$attr_name] = trim($attr_value,'"');
					unset($atts[$key]);
				}
				
				if ($name == 'category') {
				
					$item[$name][] = $value;
				
				} elseif ($name == 'custom') {
					
					$id = $atts['id'];
					unset($atts['id']);
					
					$atts['value'] = $value;
					
					$item[$name][$id] = $atts;
				
				} elseif ($name == 'para') {
					
					if (isset($item['Body'])) {
						$item['Body'][] = $value;
					}
					
					if (isset($item['user_html'])) {
						$item['user_html'][] = $value;
					}
					
					if (isset($item['Form'])) {
						$item['Form'][] = $value;
					}
					
					if (isset($item['css'])) {
						$item['css'][] = $value;
					}
				
				} elseif (in_array($name,$export_as_para)) {
					
					if (!isset($item[$name])) {
						$item[$name] = array();
					}
				
				} else {
					
					$item[$name] = $value;
				}
			}			
		}
		
		return $end_item;
	}

// -----------------------------------------------------------------------------

	function process_item(&$item,&$table,&$path,$type) {
		
		global $export_as_para,$custom_group_columns;
		
		$exclude = array('ID','ParentID','Level','category','custom','user_id');
		$message = '';
		$id = 0;
		
		$set = array();
		
		foreach ($item as $name => $value) {
			
			if (!in_array($name,$exclude)) {
				
				if ($name == 'textile') {
				
					list($body,$excerpt) = explode(',',$value);
					$set['textile_body']    = $body;
					$set['textile_excerpt'] = $excerpt;
					$value = NULL;
				}
				
				if (in_array($name,$export_as_para)) {
				
					$value = implode('\n\n',$value);
				}
				
				if ($value !== NULL) {
				
					$value = html_entity_decode($value);
					$value = preg_replace('/\\\r/',"\r",$value);
					$value = preg_replace('/\\\n/',"\n",$value);
					$value = preg_replace('/\\\t/',"\t",$value);
					$value = preg_replace('/\\\s/'," ",$value);
					
					$set[$name] = $value;
				}
			}
		}
		
		if ($type == 'image') {
			
			if (!isset($set['thumb_w'])) $set['thumb_w'] = '100';
			if (!isset($set['thumb_h'])) $set['thumb_h'] = '100';
		}
		
		if ($type == 'article') {
			
			if (!isset($set['Annotate'])) 		$set['Annotate'] = '1';
			if (!isset($set['AnnotateInvite'])) $set['AnnotateInvite'] = 'Comment';
			if (!isset($set['ImageData'])) 		$set['ImageData'] = 'before:t:right:before:r:-';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$debug = 0;
		
		if ($type == 'article') {
			
			$debug = 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($type) {
			
			// regular content types: article,image,file,link,etc...
			
			$level = $item['Level'];
			
			if (isset($path[$level])) {
				
				$ParentID = $path[$level]['id'];
				$path = array_slice($path,0,$level+1);
			}
			
			list($message,$id) = content_create($ParentID,$set,$table,$type,$debug);
			
			safe_update($table,"ImportID = ".$item['ID'],"ID = $id");
		
		} else {
			
			unset($set['item']);
			
			if ($table == 'txp_page') {
				
				include_once txpath.'/include/txp_presentation_page.php';
				
				$set['copy']    = 1;
				$set['oldname'] = $set['name'];
				$set['newname'] = $set['name'];
				
				if (isset($set['user_html'])) {
					
					$set['html'] = $set['user_html']; 
				
					unset($set['user_html']);
				
					$message = page_save($set);
				}
			}
			
			if ($table == 'txp_form') {
				
				include_once txpath.'/include/txp_presentation_form.php';
				
				$set['savenew'] = 1;
				$set['oldname'] = $set['name'];
				
				if (isset($set['Form'])) {
				
					$message = form_save($set);
				}
			}
			
			if ($table == 'txp_css') {
				
				include_once txpath.'/include/txp_presentation_css.php';
				
				$set['savenew'] = 1;
				$set['newname'] = $set['name'];
				
				if (isset($set['css'])) {
				
					$message = css_save($set);
				}
			}
			
			if ($table == 'txp_users') {
				
				include_once txpath.'/include/txp_admin_admin.php';
				
				$message = author_save_new($set);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$save = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// categories
		
		if (isset($item['category'])) {
		
			foreach ($item['category'] as $key => $category) {
			
				$save['Category'][$key] = make_name($category);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// custom fields
		
		if (isset($item['custom']) and column_exists("txp_custom","ImportID")) {
			
			foreach ($item['custom'] as $field_id => $field) {
				
				$name  	  = $field['name'];
				$value 	  = $field['value'];
				$group 	  = $field['group'];
				$instance = $field['instance'];
				
				$group = explode(';',$field['group']);
				foreach ($custom_group_columns as $key => $col) {
					$custom_group_columns[$key] = array_shift($group);
				}
				$group = $custom_group_columns;
				
				if ($field_id = fetch("ID","txp_custom","ImportID",$field_id)) {
				
					$group['id'] 	 = fetch("ID",$table,"ImportID",$group['id']);
					$group['parent'] = fetch("ID",$table,"ImportID",$group['parent']);
					
					$group_id = add_custom_field($id,$field_id,$group,$instance);
					
					$field_value_id = apply_custom_fields($id,$field_id,$name,$group_id,$table,$type);
					
					if ($field_value_id) {
				
						$save['custom_value_'.$name.'_'.$field_value_id] = $value;
					}
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($save) {
			
			content_save($id,$save,$type,$table);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($id) {
			
			if (!($title = (isset($item['Title'])) ? $item['Title'] : '')) {
				$title = (isset($item['Name'])) ? $item['Name'] : '';
			}
			
			foreach ($save as $s) {
				$message .= (is_array($s)) ? implode(', ',$s) : $s;
			}
			
			$path[] = array(
				'id' 	  => $id, 
				'title'   => $title,
				'message' => $message
			);
		
		} else {
			
			$path[0] = array(
				'title'   => $item['name'],
				'message' => $message
			);	
		}
	}

// -----------------------------------------------------------------------------

	function import_info($path) {
	 
		$out = array();
		
		if (count($path) > 1) {
			
			array_shift($path);
			
			if (count($path) > 1) {
			
				array_shift($path);
			}	
		}
		
		foreach ($path as $item) {
			$title = html_entity_decode($item['title']);
			if ($title) $out[] = preg_replace('/\\\s/'," ",$title);
		}
		
		$out = implode(' / ',$out);
		
		if (isset($item) and $item['message']) {
			$out .= ' (<span class="error">'.$item['message'].'</span>)';
		}
		
		return $out;
	}

// -----------------------------------------------------------------------------

	function extract_import($name='export')
	{
		global $path_to_site;
		
		$out = array();
		
		// --------------------------------------------------
		// FOR TESING
		/*
		foreach (dirlist(IMP_PATH,'xml') as $file) {
		
			$filesize = filesize(IMP_PATH.$file);
		
			$out['db'][] = array($file,$filesize);
		}
		
		return $out;
		*/
		// --------------------------------------------------
		
		$file = safe_row(
				"ID AS ArticleID, 
				 FileID AS ID,
				 Name,
				 ext AS Ext",
				"txp_file",
				"Name = '$name' 
				 AND Trash = 0 
				 AND Status = 4
				 ORDER BY ID DESC LIMIT 1",'file');
		
		if (!$file) {
		
			return "Nothing to import."; 
		}
		
		extract($file); // fileArticleID, fileID, fileName, fileExt
			
		$filePath = get_file_id_path($fileID);
		$file     = FILE_PATH.$filePath.DS.$fileName.$fileExt;
		$design   = $path_to_site.DS.'images'.DS.'design'.DS;
		$js	      = $path_to_site.DS.'js'.DS;
		
		if (!is_file($file)) {
			
			return "Error: $file not found"; 
		}
		
		// ---------------------------------------------------------
		// copy archive to import directory
		
		if (!is_dir(IMP_PATH)) mkdir(IMP_PATH);
		
		copy($file,IMP_PATH.$fileName.$fileExt);
		
		if (!is_file(IMP_PATH.$fileName.$fileExt)) {
			
			return "Error: copy to ".IMP_PATH.$fileName.$fileExt; 
		}
		
		// ---------------------------------------------------------
		// set status to hidden so it can't be extracted again
		
		content_save_status($fileArticleID,HIDDEN,'file');
		
		// ---------------------------------------------------------
		// change directory to import directory
		// decompress archive if necessary
		
		chdir(IMP_PATH);
			
		if ($fileExt == '.tar.gz') {
			
			exec("gunzip ".$fileName.$fileExt,$null);
			
			if (!is_file(IMP_PATH.$fileName.'.tar')) {
			
				return "Error: unzipping ".IMP_PATH.$fileName.$fileExt;
			}
			
			$fileExt = ".tar";
		}
		
		// ---------------------------------------------------------
		// extract db,image,file archives from main archive
		
		$db_archive     = 'database.tar';
		$file_archive   = 'files.tar';
		$image_archive  = 'images_content.tar';
		$design_archive = 'images_design.tar';
		$js_archive     = 'javascript.tar';
		
		$xtar = "tar -pxf ";
			
		if ($fileExt == '.tar') {
			
			exec($xtar.$fileName.$fileExt,$null);
			
			// move files archive to files directory
			if (is_file($file_archive)) {
				
				rename(IMP_PATH.$file_archive,FILE_PATH.$file_archive);
			}
			
			// move content images archive to images directory
			if (is_file($image_archive)) {
				
				rename(IMP_PATH.$image_archive,IMG_PATH.$image_archive);
			}
			
			// move design images archive to images directory
			if (is_file($design_archive)) {
				
				rename(IMP_PATH.$design_archive,$design.$design_archive);
			}
			
			// move javascript archive to images directory
			if (is_file($js_archive)) {
				
				if (!is_dir($js)) mkdir($js,0777);
				
				rename(IMP_PATH.$js_archive,$js.$js_archive);
			}
			
			// delete archive
			unlink($fileName.$fileExt);
		}
		
		// ---------------------------------------------------------
		// content images
		
		if (!is_file(IMG_PATH.$image_archive)) {
			
			return "Error: moving ".IMG_PATH.$image_archive; 
		
		} else {
			
			chdir(IMG_PATH);
			
			// delete all existing images in number directories
			rmdirlist(IMG_PATH,'/^[0-9]+$/',TRUE);
			
			// extract images archive
			exec($xtar.$image_archive,$null);
			
			// delete images archive
			unlink(IMG_PATH.$image_archive);
			
			// get file names
			$list = dirlist(IMG_PATH,'/^[a-z0-9\-]+\.(jpg|png|gif)$/',1);
			
			foreach ($list as $file) {
			
				$filesize = filesize(IMG_PATH.$file);
				
				$out['images'][] = array($file,$filesize);
			}
		}
		
		// ---------------------------------------------------------
		// design images
		
		if (is_file($design.$design_archive)) {
			
			chdir($design);
			
			// extract images archive
			exec($xtar.$design_archive,$null);
			
			// delete images archive
			unlink($design.$design_archive);
			
			// get file names
			$list = dirlist($design,'jpg,png,gif',1);
			
			foreach ($list as $file) {
			
				$filesize = filesize($design.$file);
				
				$out['design'][] = array($file,$filesize);
			}
		}
		
		// ---------------------------------------------------------
		// javascript
		
		if (is_file($js.$js_archive)) {
			
			chdir($js);
			
			// extract javascript archive
			exec($xtar.$js_archive,$null);
			
			// delete javascript archive
			unlink($js.$js_archive);
			
			// get file names
			$list = dirlist($js,'*',1);
			
			foreach ($list as $file) {
			
				$filesize = filesize($js.$file);
				
				$out['js'][] = array($file,$filesize);
			}
		}
		
		// ---------------------------------------------------------
		// files
		
		if (!is_file(FILE_PATH.$file_archive)) {
			
			return "Error: moving ".FILE_PATH.$file_archive; 
		
		} else {
			
			chdir(FILE_PATH);
			
			// delete all existing files in number directories
			rmdirlist(FILE_PATH,'/^[0-9]+$/',TRUE);
			
			// extract files archive
			exec($xtar.$file_archive,$null);
				
			// delete files archive
			unlink(FILE_PATH.$file_archive);
			
			// get file names
			foreach (dirlist(FILE_PATH,'/^[0-9]+$/') as $dir) {
				
				foreach (dirlist(FILE_PATH.$dir,'*',1) as $file) {
					
					$filesize = filesize(FILE_PATH.$dir.DS.$file);
					
					$out['files'][] = array("$dir/$file",$filesize);
				}
			}
		}
		
		// ---------------------------------------------------------
		// database
		
		if (!is_file(IMP_PATH.$db_archive)) {
			
			return "Error: ".IMP_PATH.$db_archive.' is missing'; 
		
		} else {
			
			chdir(IMP_PATH);
			
			// extract db archive
			exec($xtar.$db_archive,$null);
			
			// delete db archive
			unlink(IMP_PATH.$db_archive);
			
			// get xml file names
			foreach (dirlist(IMP_PATH,'xml') as $file) {
			
				$filesize = format_bytes(filesize(IMP_PATH.$file));
			
				$out['db'][] = array($file,$filesize);
			}
		}
		
		return $out;
	}
		
// -----------------------------------------------------------------------------
	
	function get_import_file($table)
	{
		static $dir_id = 0;
		
		if (!$dir_id) {
		
			$dir_id = safe_field("ID","txp_file",
				"Name = 'export' 
				 AND Trash = 0 
				 ORDER BY ID DESC LIMIT 1");
		}
		
		if ($dir_id) {
		
			$file = safe_row(
				"ID,FileID",
			 	"txp_file",
				"ParentID = $dir_id 
				 AND Name = '$table' 
				 AND Trash = 0 
				 ORDER BY ID DESC LIMIT 1",
				 'file');
			
			if ($file) {
			
				extract($file); // fileID, fileFileID
			
				$filePath = get_file_id_path($fileFileID);
				$file = FILE_PATH.$filePath.DS.$table.'.xml';
				
				if (is_file($file)) return array($fileID,$file);
			}
		}
		
		return '';
	}

?>
	
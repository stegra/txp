<?php
/*
            _______________________________________
   ________|            Textpattern                |________
   \       |          Mod File Upload              |       /
    \      |   Michael Manfre (http://manfre.net)  |      /
    /      |_______________________________________|      \
   /___________)                               (___________\

	Textpattern Copyright 2004 by Dean Allen. All rights reserved.
	Use of this software denotes acceptance of the Textpattern license agreement

	"Mod File Upload" Copyright 2004 by Michael Manfre. All rights reserved.
	Use of this mod denotes acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_file.php $
$LastChangedRevision: 3200 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	if ($event == 'file') {
		
		require_privs('file');
		
		$steps = array_merge($steps,array(
			'insert',
			'replace',
			'create',
			'reset_count',
			'increment_count',
			'add_folder',
			'add_existing_files'
		));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$levels = array(
			1 => gTxt('private'),
			0 => gTxt('public')
		);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$file_statuses = array(
			2 => gTxt('hidden'),
			3 => gTxt('pending'),
			4 => gTxt('live'),
		);
	}

// =============================================================================
	function file_list($message='')
	{
		global $EVENT, $WIN, $html, $app_mode, $smarty;
		
		// ---------------------------------------------------------------------
		
		if (!$WIN['columns']) {
			
			$WIN['columns'] = array(
					
				'Title'  	 => array('title' => 'Title',  	   	'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	 => array('title' => 'Image',  	   	'on' => 1, 'editable' => 0, 'pos' => 2),
				'size' 	 	 => array('title' => 'Size', 	   	'on' => 1, 'editable' => 0, 'pos' => 3),
				'Type' 	 	 => array('title' => 'Type', 	   	'on' => 0, 'editable' => 0, 'pos' => 4),
				'Play'  	 => array('title' => 'Play', 		'on' => 0, 'editable' => 0, 'pos' => 5),
				'Posted' 	 => array('title' => 'Posted', 	   	'on' => 1, 'editable' => 0, 'pos' => 6),
				'condition'  => array('title' => 'Condition', 	'on' => 1, 'editable' => 0, 'pos' => 7, 'sel' => 'NULL'),
				'LastMod'    => array('title' => 'Modified',   	'on' => 0, 'editable' => 0, 'pos' => 8),
				'Name' 		 => array('title' => 'Name', 	   	'on' => 0, 'editable' => 1, 'pos' => 9),
				'FileName' 	 => array('title' => 'Filename', 	'on' => 0, 'editable' => 0, 'pos' => 10),
				'ext' 		 => array('title' => 'Ext.', 	   	'on' => 0, 'editable' => 1, 'pos' => 11),
				'Categories' => array('title' => 'Categories', 	'on' => 1, 'editable' => 1, 'pos' => 12),
				'Body' 		 => array('title' => 'Description', 'on' => 0, 'editable' => 1, 'pos' => 13),	
				'downloads'  => array('title' => 'Downloads', 	'on' => 1, 'editable' => 0, 'pos' => 14),
				'AuthorID'	 => array('title' => 'Author',		'on' => 0, 'editable' => 1, 'pos' => 15),
				'Status'	 => array('title' => 'Status',		'on' => 0, 'editable' => 1, 'pos' => 16),
				'ID'	 	 => array('title' => 'ID',			'on' => 0, 'editable' => 0, 'pos' => 17),
				'Position'   => array('title' => 'Position',	'on' => 0, 'editable' => 1, 'pos' => 18, 'short' => 'Pos.'),
				'filename'	 => array('title' => '', 			'on' => 1, 'editable' => 0, 'pos' => 0),
				'type'		 => array('title' => '', 			'on' => 1, 'editable' => 0, 'pos' => 0)
			);
			
			$WIN['columns']['Title']['sel'] = "t.Title AS Title";
			$WIN['columns']['Play']['sel']  = "CONCAT(t.Name,t.ext)";
		}
		
		// ---------------------------------------------------------------------
		// add existing files from ftp folder
		
		if (!is_file(FPATH_FTP.'_LOCK')) {
			
			event_add_existing_files('*');
		}
		
		// ---------------------------------------------------------------------
		// PAGE TOP
		
		$html = pagetop(gTxt('tab_file'), $message); 
		
		// ---------------------------------------------------------------------
		
		$files = new ContentList();
	
		$list = $files->getList();
			
		foreach ($list as $key => $item) {
			
			if (!in_list($item['type'],'folder,trash')) {
				$list[$key]['condition'] = get_file_condition($item['FileID'],$item['filename']);
			}
			
			if ($item['type'] == 'folder') {
				$list[$key]['condition'] = get_file_condition($item['ID']);
			}
			
			if (isset($item['size']) and $item['size']) {
				$list[$key]['size'] = str_replace(' ','&#160;',format_bytes($item['size']));
			}
			
			if (isset($item['downloads'])) {
				$list[$key]['downloads'] = ($item['downloads']) 
					? num_thousand_sep($item['downloads']) 
					: 'None';
			}
		}
		
		$html.= $files->viewList($list);
		
		// ---------------------------------------------------------------------
		
		if ($app_mode != 'async') {
		
			$html.= file_upload_form(gTxt('upload_file'), 'upload', 'file_insert');
		}
		// ---------------------------------------------------------------------
		
		save_session($EVENT);
		save_session($WIN);
	}

// -------------------------------------------------------------
	function file_edit_old($message='',$id='')
	{
		global $file_base_path, $levels, $file_statuses, $txp_user, $smarty;

		extract(gpsa(array('name', 'category', 'permissions', 'description', 'sort', 'dir', 'page', 'crit', 'search_method', 'publish_now')));
		
		if (!$id)
		{
			$id = gps('id');
		}
		$id = assert_int($id);

		// $categories = getTree('root', 'file');

		$rs = safe_row('*, 
			Name AS name, 
			Status AS status, 
			Body As description, 
			unix_timestamp(Posted) AS created, 
			unix_timestamp(LastMod) AS modified', 
		'txp_file', "ID = $id");
		
		if ($rs)
		{
			extract($rs);
			
			$path = new Path($id,'ROOT',"txp_file",$Path);
			$path = $path->getList('ID');
			
			if (!has_privs('file.edit') && !($author == $txp_user && has_privs('file.edit.own')))
			{
				file_list(gTxt('restricted_area'));
				return;
			}

			pagetop(gTxt('file'), $message);

			if ($permissions=='') $permissions='-1';
			if (!has_privs('file.publish') && $status >= 4) $status = 3;
			
			$filename = $name.$ext;
			
			$file_exists = file_exists(build_file_path($file_base_path,$filename,$FileID));
			$replace = ($file_exists) ? tr(tda(file_upload_form(gTxt('replace_file'),'upload','file_replace',$id),' class="replace"')) : '';

			$existing_files = get_filenames();

			$condition = get_file_condition($FileID,$filename); 
			
			$downloadlink = ($file_exists)
				? make_download_link($id, htmlspecialchars($filename),$filename) 
				: htmlspecialchars($filename);
			
			$downloadlink .= file_media_player($id,$filename,$ext);
			
			$categories = safe_column(
				"position,name",
				"txp_content_category",
				"article_id = $id AND type = 'file' ORDER BY position ASC"
			);
			
			$created =
				n.graf(checkbox('publish_now', '1', $publish_now, '', 'publish_now').'<label for="publish_now">'.gTxt('set_to_now').'</label>').

				n.graf(gTxt('or_publish_at').sp.popHelp('timestamp')).

				n.graf(gtxt('date').sp.
					tsi('year', '%Y', $rs['created']).' / '.
					tsi('month', '%m', $rs['created']).' / '.
					tsi('day', '%d', $rs['created'])
				).

				n.graf(gTxt('time').sp.
					tsi('hour', '%H', $rs['created']).' : '.
					tsi('minute', '%M', $rs['created']).' : '.
					tsi('second', '%S', $rs['created'])
				);

			$form = '';

			if ($file_exists) { 
			
				$form =	form(
					file_category_popups($categories).
					graf(gTxt('description').br.text_area('description','100','400',$description)) .
					fieldset(radio_list('status', $file_statuses, $status, 4), gTxt('status'), 'file-status').
					pluggable_ui('file_ui', 'extend_detail_form', '', $rs).
					graf(fInput('submit', '', gTxt('save'), 'publish')).

					eInput('file') .
					sInput('file_save').
					
					hInput('filename', $filename).
					hInput('id', $id) .
					hInput('sort', $sort).
					hInput('dir', $dir).
					hInput('page', $page).
					hInput('crit', $crit).
					hInput('search_method', $search_method)
				);
			}
			
			echo startTable('list', '', 'edit-pane'),
			tr(
				td(
					graf(gTxt('file_status').br.$condition) .
					graf(gTxt('file_name').br.$downloadlink) .
					graf(gTxt('file_download_count').br.$downloads) .
					$form
				)
			),
			$replace,
			endTable();
		}
	}

// -------------------------------------------------------------
	function file_create()
	{
		global $txp_user,$file_base_path;

		if (!has_privs('file.edit.own'))
		{
			file_list(gTxt('restricted_area'));
			return;
		}

		extract(doSlash(gpsa(array('filename','category','permissions','description'))));

		$size = filesize(build_file_path($file_base_path,$filename));
		$id = file_db_add($filename,$category,$permissions,$description,$size);

		if($id === false){
			file_list(array(gTxt('file_upload_failed').' (db_add)', E_ERROR));
		} else {
			$newpath = build_file_path($file_base_path,trim($filename));

			if (is_file($newpath)) {
				
				file_set_perm($filename);
				$fileext = get_file_ext($filename);
					
				if ($fileext == 'mp4' or $fileext == 'm4v' or $fileext == 'mov') {
					make_poster($filename);
				}
				
				$size = filesize($file_base_path.'/'.$filename);
				safe_update('txp_file',"size = $size","id = $id");
					
				file_list(gTxt('linked_to_file').' '.$filename);
			} else {
				file_list(gTxt('file_not_found').' '.$filename);
			}
		}
	}

// -------------------------------------------------------------
	function file_insert($parent_id=0,$replace=false,$existing='')
	{
		global $WIN,$txp_user,$file_base_path,$file_max_upload_size,$app_mode,$siteurl;
		
		inspect('export: '.FPATH.'_export/info.txt');
		
		if (!has_privs('file.edit.own'))
		{
			file_list(gTxt('restricted_area'));
			return;
		}

		extract(doSlash(gpsa(array('category','permissions','description'))));
		
		if (!$parent_id) {
		
			if (isset($WIN['content']) and $WIN['content'] != 'file') {
				
				$parent_id = fetch("ID","txp_file","ParentID",0);
			
			} elseif (isset($WIN['id'])) {
				
				$parent_id = $WIN['id'];
			}
		}
		
		$path = array();
		
		if ($existing) {
			
			$file     = FPATH_FTP.$existing;
			$path     = explode('/',$existing);
			$name     = array_pop($path);
			
		} else {
			
			$name = file_get_uploaded_name();
			$file = file_get_uploaded();
		}
		
		if ($file === false and $app_mode != 'async') {
			// could not get uploaded file
			file_list(array(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg($_FILES['thefile']['error']), E_ERROR));
			return;
		}
		
		if (!is_file($file)) {
			
			if ($app_mode == 'async') {
				
				if ($_FILES['thefile']['error'] == 1) {
					echo "Filesize exceeds maximum of ".ini_get('upload_max_filesize');
				}
			}
			
			return -1;
		}
		
		$size = filesize($file);
		if (!$existing and $file_max_upload_size < $size) {
			unlink($file);
			file_list(array(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg(UPLOAD_ERR_FORM_SIZE), E_ERROR));
			return;
		}
		
		$newname = sanitizeForFile($name);
		
		// ---------------------------------------------------------
		// put ogg file next to mp3 file that has the same name
		
		$ext = get_file_ext($newname);
		
		if ($ext == 'ogg' and $app_mode == 'async') {
			
			$filenames[] = get_file_name($newname).'.mp3';
			$filenames[] = get_file_name($newname).'.mp4';
			
			$fileid = safe_field('FileID','txp_file',
				"FileName IN (".in($filenames).") AND Trash = 0");
			
			if ($fileid) {
				
				$oggfile = build_file_path(FPATH,$newname,$fileid);
				
				shift_uploaded_file($file,$oggfile);
			
				if (is_file($oggfile)) {
					
					echo 1; 
					
				} else {
				
					echo 'ERROR';
				}
				
				return;
			}
		}
		
		// ---------------------------------------------------------
		
		$parent_id = event_add_folder("txp_file",$parent_id,$path);
		$id = file_db_add($parent_id,$name,$newname);
		
		if (!$id) {
			
			$message = gTxt('file_upload_failed').' (db_add)';
			
			if ($existing) {
					
				return array(0,$message);
			
			} else if ($app_mode == 'async') {
				
				echo $message; return;
			}
				
			file_list(array($message, E_ERROR));
		
		} else {

			$id = assert_int($id);
			
			$file_id = fetch('FileID','txp_file','ID',$id);
			$file_id_path = get_file_id_path($file_id);
			$newpath = build_file_path(FPATH,$newname,$file_id);
			
			// safe_update('txp_file',"FilePath = '$file_id_path'","ID = $id");
			
			shift_uploaded_file($file,$newpath);
			
			if (!is_file($newpath)) {
				
				$id_path = '1';
				$file_path = FPATH.$id_path;

				if (is_dir($file_path) and is_writable($file_path)) {
				
					$newpath = $file_path.'/'.$newname;
					shift_uploaded_file($file, $newpath);
				}
			}
			
			if (!is_file($newpath)) {
			
				safe_delete("txp_file","id = $id");
				safe_alter("txp_file", "auto_increment=$id");
				if ( isset( $GLOBALS['ID'])) unset( $GLOBALS['ID']);
				
				$message = $newpath.sp.gTxt('upload_dir_perms');
				
				if ($existing) {
				
					return array(0,$message);
				
				} else if ($app_mode == 'async') {
					
					echo $message; return;
				}
				
				file_list(array($message, E_ERROR));
				
			} else {
				
				file_set_perm($newpath);
				
				$fileext = get_file_ext($newname);
				
				if (in_array($fileext,array('mp4','m4v','mov'))) {
					make_poster($newname);
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				// add file size
				
				safe_update('txp_file',"size = $size","ID = $id");
				
				// update folder file sizes
				
				$path = new Path($id,'ROOT',"txp_file");
				$path = $path->getList('ID');
				
				if ($path) {
					safe_update('txp_file',"size = size + $size","ID IN (".$path.")");
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				// add export information 
				
				if ($existing and substr($newname,0,10) == 'export.tar') {
					
					if (is_file(FPATH.'_export/info.txt')) {
						
						$info = doSlash(read_file(FPATH.'_export/info.txt'));
						
						safe_update('txp_file',"Body = '$info'","ID = $id");
						
						unlink(FPATH.'_export/info.txt');
						rmdir(FPATH.'_export');
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				// add image for a file that is an image 
				
				if (in_list($fileext,'jpg,png,gif')) {
					
					$image_id = add_image_to_library('http://'.$siteurl.'/files/'.$file_id_path.'/'.$newname);
					
					// NOTE: this should be done with ContentSave
					
					safe_update('txp_file',"ImageID = $image_id","ID = $id");
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($existing) {
					
					return array($id,$newpath); // back to file list page
				
				} else if ($app_mode == 'async') {
						
					if (is_file($newpath)) {
						echo $id; return;
					}
						
				} else {
				
					$message = gTxt('file_uploaded', array('{name}' => htmlspecialchars($newname)));

					event_edit($message, $id);
				}
			}
		}
	}

// -------------------------------------------------------------
	function file_replace()
	{
		global $txp_user,$file_base_path;

		$id = assert_int(gps('id'));

		$rs = safe_row('filename, author','txp_file',"id = $id");

		if (!$rs) {
			file_list(messenger(array(gTxt('invalid_id'), E_ERROR),$id,''));
			return;
		}

		extract($rs);

		if (!has_privs('file.edit') && !($author == $txp_user && has_privs('file.edit.own')))
		{
			event_edit(gTxt('restricted_area'));
			return;
		}

		$file = file_get_uploaded();
		$name = file_get_uploaded_name();

		if ($file === false) {
			// could not get uploaded file
			file_list(gTxt('file_upload_failed') ." $name ".upload_get_errormsg($_FILES['thefile']['error']));
			return;
		}

		if (!$filename) {
			file_list(gTxt('invalid_filename'));
		} else {
			$newpath = build_file_path($file_base_path,$filename);

			if (is_file($newpath)) {
				rename($newpath,$newpath.'.tmp');
			}

			if(!shift_uploaded_file($file, $newpath)) {
				safe_delete("txp_file","id = $id");

				file_list($newpath.sp.gTxt('upload_dir_perms'));
				// rename tmp back
				rename($newpath.'.tmp',$newpath);

				// remove tmp upload
				unlink($file);
			} else {
				file_set_perm($newpath);
				if ($size = filesize($newpath))
					safe_update('txp_file', 'size = '.$size.', modified = now()', 'id = '.$id);

				$message = gTxt('file_uploaded', array('{name}' => htmlspecialchars($name)));

				event_edit($message, $id);
				// clean up old
				if (is_file($newpath.'.tmp'))
					unlink($newpath.'.tmp');
			}
		}
	}

// -------------------------------------------------------------
	function file_reset_count()
	{
		// TODO: accompanying user interface

		extract(doSlash(gpsa(array('id','filename','category','description'))));

		if ($id) {
			$id = assert_int($id);
			if (safe_update('txp_file','downloads = 0',"id = $id")) {
				event_edit(gTxt('reset_file_count_success'),$id);
			}
		} else {
			file_list(gTxt('reset_file_count_failure'));
		}
	}

// -------------------------------------------------------------
// TODO: check for repeated hits from the same user in order to 
//		 wildly increase download count

	function file_increment_count()
	{
		extract(doSlash(gpsa(array('id','ext'))));
		
		$id = assert_int($id);
		
		$FileID = fetch("FileID","textpattern","ID",$id);
		
		if ($FileID) {
		
			$row = safe_row("Name,ext","txp_file","ID = $FileID");
			
			/* 
				WHAT WAS THIS FOR?
				
			if ($row and $row['ext'] != ".$ext") {
				$Name = $row['Name'];
				$FileID = safe_field("ID","txp_file","Name = '$Name' AND ext = '.$ext'");
			} */
			
			$path = new Path($FileID,'ROOT',"txp_file");
			$path = $path->getList('ID');
			
			if ($path) {
				safe_update('txp_file',"downloads = downloads + 1","ID IN (".$path.")");
			}
		}
	}

//------------------------------------------------------------------------------
	function file_save($ID=0, $multiedit=null, $table='', $type='') 
	{
		global $WIN;
		
		$textpattern  = ($table) ? $table : $WIN['table'];
		$content_type = ($type) ? $type : $WIN['content'];
		
		content_save($ID,$multiedit,$type,$table);
		
 		$_GET['step'] = 'edit';
		
		event_edit();
	}

// -------------------------------------------------------------
	function file_save_old()
	{
		global $txp_user;

		extract(doSlash(gpsa(array('id','filename','description','status'))));
		
		$id = assert_int($id);
		
		$rs = safe_row('AuthorID,FileID', 'txp_file', "id=$id");
		if (!has_privs('file.edit') && !($rs['AuthorID'] == $txp_user && has_privs('file.edit.own')))
		{
			event_edit(gTxt('restricted_area'));
			return;
		}
		
		$set = array(
			'Body' 		=> $description,
			'Status'    => $status,
			'Category' 	=> gps('category')
		);
		
		if (content_save($id,$set,'file')) {
			
			$message = gTxt('file_updated', array('{name}' => $filename));
		
		} else {
			
			$message = gTxt('file_not_updated', array('{name}' => $filename));
		}
		
		event_edit($message);
	}

// =============================================================================
	function file_search_form($crit, $method)
	{
		$methods =	array(
			'id'			=> gTxt('ID'),
			'filename'		=> gTxt('file_name'),
			'type'			=> 'Type',
			'description' 	=> gTxt('description'),
			'category'		=> gTxt('file_category'),
			'author'		=> gTxt('author')
		);

		return search_form('file', 'file_list', $crit, $methods, $method, 'filename');
	}

// -------------------------------------------------------------
	function file_upload_form($label,$pophelp,$step,$id='')
	{
		global $file_max_upload_size;

		if (!$file_max_upload_size || intval($file_max_upload_size)==0) $file_max_upload_size = 2*(1024*1024);

		$max_file_size = (intval($file_max_upload_size) == 0) ? '': intval($file_max_upload_size);

		$form = new UploadForm('file', $step, $label, $pophelp, $id);
		$form->max_file_size = $max_file_size;
		
		return $form; 
	}

// -------------------------------------------------------------
	function file_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changecategory'  => gTxt('changecategory'),
			'changeauthor'    => gTxt('changeauthor'),
			'delete'          => gTxt('delete'),
		);

		if (has_single_author('txp_file'))
		{
			unset($methods['changeauthor']);
		}

		if (!has_privs('file.delete.own') && !has_privs('file.delete'))
		{
			unset($methods['delete']);
		}

		return event_multiedit_form('file', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------
	function file_edit_type(&$in,&$html) 
	{
		global $file_base_path;
		
		extract($in);
		
		$html[1]['excerpt'] = '';
		
		if ($Type != 'folder') {
		
			$out = array();
			
			$player = '';
			$filename = fetch('Filename','txp_file','ID',$ID);
			$condition = get_file_condition($FileID,$filename); 
			$file_exists = file_exists(build_file_path($file_base_path,$filename,$FileID));
			$oggfile = '';
			
			$downloadlink = ($file_exists) 
				? make_download_link($ID, htmlspecialchars($filename),$filename)
				: htmlspecialchars($filename);
			
			if ($file_exists and in_list($ext,'.mp3,.mp4')) {
				$ogg = get_file_name($filename).'.ogg';
				$oggfile = build_file_path($file_base_path,$ogg,$FileID);
				if (is_file($oggfile)) {
					$oggpath = get_file_id_path($FileID);
					$oggfile = '../files/'.$oggpath.'/'.$ogg;
					$downloadlink .= n.'<a class="oggfile" href="'.$oggfile.'">'.$ogg.'</a>';
				} else {
					$oggfile = '';
				}
			}
			
			if (in_list($ext,'.mp3,.mp4,.m4v,.mov')) {
				$player = file_media_player($FileID,$filename,$ext,$oggfile);
			}
		
			$out[] = graf(gTxt('file_status').br.$condition);
			$out[] = graf(gTxt('file_name').br.$downloadlink.n.$player);
			$out[] = graf(gTxt('file_download_count').br.$downloads);
				 
			$html[0]['special'] = '<div class="event-group1">'.n.implode(n,$out).n.'</div>';
			$html[1]['body']    = str_replace('>'.gTxt('body').'<','>'.gTxt('description').'<',$html[1]['body']);
			$html[1]['author']  = str_replace(gTxt('posted_by'),gTxt('added_by'),$html[1]['author']);
		}
	}
			
// -------------------------------------------------------------
	function file_delete($id)
	{
		global $file_base_path, $txp_user;

		$row = safe_row("FileID,Name,ext","txp_file","ID = $id");
		
		if (count($row)) {
		
			extract($row);
			
			$file_id_path = get_file_id_path($FileID);
								
			$file = FPATH.$file_id_path.DS.$Name.$ext;
			
			if (is_file($file)) unlink($file);
		}
	}

// -------------------------------------------------------------
	function file_db_add($parent_id, $title, $name)
	{	
		global $PFX;
		
		$type = get_file_type($name);
		
		$filename = doSlash($name);
		
		$name = explode('.',$name);
		$length = count($name);
		
		if ($length > 1) {
			
			$ext[] = array_pop($name);
			$length -= 1;
			
			if ($length > 1 and in_list($ext[0],'zip,gz')) {
			
				$ext[] = array_pop($name);
				$length -= 1;	
			}
		}
		
		$name  = doSlash(make_name(implode('.',$name)));
		$ext   = doSlash('.'.implode('.',array_reverse($ext)));
		
		$title = explode('.',$title);
		$title = array_slice($title,0,$length);
		$title = doSlash(make_title(implode('.',$title)));
		
		$fileid = safe_field("FileID","txp_file",
				"Name = '$name' 
				 AND ext != '$ext' 
				 AND Type != 'folder'");
		
		if (!$fileid) $fileid = 'SELECT MAX(FileID) + 1 FROM '.$PFX.'txp_file'; 
		
		$set = array(
			'Title' 	=> $title,
			'Name' 		=> $name,
			'FileName'	=> $filename,
			'ext' 		=> $ext,
			'Type' 		=> $type,
			'FileID'  	=> $fileid
		);
		
		include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
		
		list($message,$id) = content_create($parent_id,$set,"txp_file","file");
				
		if ($id) {
			$GLOBALS['ID'] = $id;
			return $GLOBALS['ID'];
		}

		return false;
	}

// -------------------------------------------------------------
	function file_get_uploaded()
	{
		return get_uploaded_file($_FILES['thefile']['tmp_name']);
	}

// -------------------------------------------------------------
	function file_get_uploaded_name()
	{
		return $_FILES['thefile']['name'];
	}

// -------------------------------------------------------------
	function file_set_perm($file)
	{
		return @chmod($file,0644);
	}

// -------------------------------------------------------------
	function make_download_link($id,$label='',$filename='')
	{
		$label = ($label) ? $label : gTxt('download');
		$url = filedownloadurl($id, $filename);
		return '<a title="Download File" href="'.$url.'">'.$label.'</a>';
	}

// -------------------------------------------------------------
	function file_category_popups($categories)
	{
		$pos = 0;
		$popups = array();
		
		foreach($categories as $pos => $name) {
			$popups[$pos] = category_popup('category[]', $name, 'category-'.$pos);
		}
		
		if ($popup = category_popup('category[]', '', 'category-'.$pos)) {
			$popups[++$pos] = $popup;
		}
		
		foreach($popups as $pos => $item) {
			$popups[$pos] = preg_replace('/>(\&\#160;){4}/','>',$item);
		}
		
		return ($popups) ? graf(gTxt('categories').br.implode(n,$popups)) : '';
	}
				
// -------------------------------------------------------------
	function get_filenames()
	{
		global $file_base_path;

		$dirlist = array();

		if (!is_dir($file_base_path))
			return $dirlist;

		if (chdir($file_base_path)) {
			$g_array = glob("*.*");
			if ($g_array) {
				foreach ($g_array as $filename) {
					if (is_file($filename)) {
						$dirlist[$filename] = $filename;
					}
				}
			}
		}

		$files = array();
		$rs = safe_rows("CONCAT(Name,ext) AS filename", "txp_file", "1=1");

		if ($rs) {
			foreach ($rs as $a) {
				$files[$a['filename']] = $a['filename'];
			}
		}

		return array_diff($dirlist,$files);
	}

// -------------------------------------------------------------
	function make_poster($file) 
	{
		global $file_base_path,$img_dir;
		
		if (!class_exists("ffmpeg_movie")) 
			return '';
		
		$movie  = new ffmpeg_movie($file_base_path.DS.$file, false);
		$frames = $movie->getFrameCount();
		$middle = ($frames > 0) ? round($frames / 2) : 0;
		
		$name = preg_replace('/\.[^\.]+$/','',$file);
		
		if ($middle) {
		
			$frame = $movie->getFrame($middle);
			$frame = $frame->toGDImage();
			
			imagejpeg($frame,IMPATH.$name.'.jpg');
			imagedestroy($frame);
		}
	}

// -------------------------------------------------------------
	function get_poster($file) 
	{
		global $img_dir;
		
		$name = preg_replace('/\.[^\.]+$/','',$file);
		
		if (is_file(IMPATH.$name.'.jpg'))
				return DS.$img_dir.DS.$name.'.jpg';
		
		return '';
	}

// -------------------------------------------------------------
	function get_file_condition(&$id,&$name='') 
	{	
		global $file_base_path;
		
		$missing = 0;
		
		if ($name) {
			
			if (!file_exists(build_file_path($file_base_path,$name,$id))) {
				
				if (!file_exists(build_file_path($file_base_path,$name,1))) {
					$missing = 1;
				}
			}
		
		} else {
			
			$path = '';
			
			if ($id != ROOTNODEID) {
				$path = safe_field("Path",'txp_file',"ID = $id");
				$path = ($path) ? $path.'/'.$id : $id;
			}
			
			if (column_exists('txp_file','FileName')) {
				$files = safe_rows('FileID AS id,FileName AS name','txp_file',
					"Path LIKE '$path%' AND Type NOT IN ('folder','trash') AND Trash = 0");
			} else {
				$files = safe_rows('FileID AS id,CONCAT(Name,ext) AS name','txp_file',
					"Path LIKE '$path%' AND Type NOT IN ('folder','trash') AND Trash = 0");
			}
			
			foreach($files as $file) {
				
				if (!file_exists(build_file_path($file_base_path,$file['name'],$file['id']))) {
					
					if (!file_exists(build_file_path($file_base_path,$file['name'],1))) {
					
						$missing += 1;
					}
				}
			}
		}
		
		$condition = '<span class="';
		$condition .= ($missing) ? 'not-ok' : 'ok';
		$condition .= '">';
		$condition .= (!$name and $missing) ? $missing.sp : '';
		$condition .= ($missing) ? gTxt('file_status_missing') : gTxt('file_status_ok');
		$condition .= '</span>';
			
		return $condition;
	}

// -------------------------------------------------------------
	function file_media_player($id,$name,$ext,$oggsrc='')
	{
		global $smarty;
		
		$ext  = ltrim($ext,'.');
		$path = get_file_id_path($id);
		$src  = "../files/$path/$name";
		
		// audio file play button
		
		if ($ext == 'mp3') {
			$smarty->assign('user_agent',user_agent());
			$smarty->assign('filesrc',$src);
			$smarty->assign('oggfilesrc',$oggsrc);
			$smarty->assign('filename',$name);
			$smarty->assign('extension',$ext);
			$smarty->assign('fileid',$id);
			$smarty->assign('fileidpath',$path);
			return $smarty->fetch('play1.tpl');
		}
		
		// html5 movie player or flash player
		
		if ($ext == 'mp4' or $ext == 'm4v' or $ext == 'mov') {
			$smarty->assign('user_agent',user_agent());
			$smarty->assign('filesrc',$src);
			$smarty->assign('filename',$name);
			$smarty->assign('extension',$ext);
			$smarty->assign('fileid',$id);
			$smarty->assign('fileidpath',$path);
			$smarty->assign('poster',get_poster($name));
			return $smarty->fetch('play3.tpl');
		}
		
		// flash movie player for flash files
		
		if ($ext == 'flv' or $ext == 'f4v') {
			$smarty->assign('user_agent','');
			$smarty->assign('filename',$name);
			$smarty->assign('extension',$ext);
			$smarty->assign('fileid',$id);
			$smarty->assign('fileidpath',$path);
			return $smarty->fetch('play3.tpl');
		}
		
		return '';
	}
?>
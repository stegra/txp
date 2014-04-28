<?php

// -------------------------------------------------------------
	function event_change_pageby()
	{
		global $event;
		
		event_change_pageby($WIN['content']);
		
		$next = $event.'_list';
		
		$next();
	}
	
// -------------------------------------------------------------
	function event_toggle_column()
	{
		global $event;
		
		$list = new ContentList();
		
		$list->toggle_column();
		
		$next = $event.'_list';
		
		$next();
	}

// -------------------------------------------------------------
	function event_move_column()
	{
		global $event;
		
		$list = new ContentList();
		
		$list->move_column();
		
		$next = $event.'_list';
		
		$next();
	}

// -------------------------------------------------------------
	function event_edit($message='')
	{
		$_GET['step'] = 'edit';
			
		content_edit($message);
	}

// -------------------------------------------------------------
	function event_save()
	{
		global $event;
		
		content_save();
		
		$next = $event.'_edit';
		
		if (!function_exists($next)) {
			
			$next = 'content_edit';
		}
		
		$next();
	}
	
// -------------------------------------------------------------
	function event_multi_edit() 
	{	
		global $WIN, $event;
		
		$method   = gps('edit_method');
		$selected = gps('selected',array());
		$checked  = $selected;
		$next     = $event.'_list';
		
		plugin_callback(1,$method,$selected);
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$checked   = $multiedit->selected;	
		$changed   = $multiedit->changed;	
		
		if ($method == 'save') {
			
			$checked = array();
		}
		
		
		plugin_callback(2,$method,$selected);
		
		$WIN['checked'] = $checked;
		
		$next($message);
	}
	
// -------------------------------------------------------------
	function event_add_folder($table='',$parent_id=0,$folder=array()) 
	{
		global $WIN, $app_mode;
		
		$table	   = (!$table) ? $WIN['table'] : $table;
		$parent_id = (!$parent_id) ? $WIN['id'] : $parent_id;
		$type	   = substr($table,4);
		
		$title = array_shift($folder);
		$title = make_title(gps('title',$title));
		$name  = make_name($title);
		
		if (!$name) {
			return $parent_id;
		}
		
		$id = safe_field("ID",
			$table,
			"ParentID = '$parent_id' 
				AND Name = '$name' 
				AND Type = 'folder' 
			 ORDER BY Posted DESC LIMIT 1");
		
		if (!$id) {
			
			$set = array(
				'Title' => $title,
				'Type'  => 'folder'
			);
			
			include_once txpath.'/include/lib/txp_lib_ContentCreate.php';
			
			list($message,$id) = content_create($parent_id,$set,$table,$type);
		} 
		
		if (count($folder)) {
			
			event_add_folder($table,$id,$folder);
		}
			
		if ($app_mode == 'async') { echo "/$id"; }
			
		return $id;
	}
		
// -------------------------------------------------------------
// add 10 existing files on each page load

	function event_add_existing_files($ext='',$type='',$count=0) 
	{
		global $WIN, $event, $app_mode, $txp_user;
		
		$ext   = gps('ext',$ext);
		$count = gps('count',$count);
		$type  = (!$type) ? $event : $type;
		
		if (!defined('FTP')) define('FTP',($type == 'image') ? IMPATH_FTP : FPATH_FTP);
		
		if (!defined('TOTAL_LIMIT'))  define('TOTAL_LIMIT',1000);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!is_file(FTP.'_LOCK')) {
			
			create_file(FTP.'_LOCK','OK');
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$files_to_add = 0;
			
			if ($files = dirlist(FTP,$ext,1)) {
				
				create_file(FTP.'_LOG');
				create_file(FTP.'_ERROR');
				
				$log   = explode(n,read_file(FTP.'_LOG'));
				$error = explode(n,read_file(FTP.'_ERROR'));
				
				foreach ($files as $key => $name) {
					
					if (!in_array($name,$error)) {
					
						$files_to_add++;
					}
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($files_to_add) {
			
				$log[] = date('Y/m/d H:i:s');
				$log[] = "------------------------------";
				$log[] = "Files to add: $files_to_add";
				$log[] = "------------------------------";
				
				write_to_file(FTP.'_LOG',implode(n,$log));
			
				/* $params = array(
					'event'    => $type,
					'step'     => 'add_existing_files',
					'win'	   => $WIN['winid'],
					'ext'	   => $ext,
					'user'     => $txp_user,
					'key'      => '123',
					'app_mode' => 'async'
				); */
				
				// curl_gps_async('GET', hu."admin/index.php", $params);
				
				// TESTING
				// event_add_existing_files($ext,$type);
				// delete_file(FTP.'_LOCK');
					
				// return;
			} 
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			// delete_file(FTP.'_LOCK');
			
			// return;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// insert files if any
		
		if ($files = dirlist(FTP,$ext,1)) {
			
			if (!defined('BATCH_LIMIT'))  
				 define('BATCH_LIMIT',10);
			
			if (!defined('BATCH_PAUSE'))  
				 define('BATCH_PAUSE',(count($files) <= BATCH_LIMIT) ? 0 : 2);	// 2 seconds
			
			if (!defined('INSERT_PAUSE')) 
				 define('INSERT_PAUSE',4);	// 4 seconds
		
			$log   = explode(n,read_file(FTP.'_LOG'));
			$error = explode(n,read_file(FTP.'_ERROR'));
			
			$log[] = 'EXT: '.$ext;
			
			$inserts = 0;
			
			while (count($files) and $inserts < BATCH_LIMIT) {
			
				$file = array_shift($files);
				
				if (!in_array($file,$error)) {
					
					if ($event != 'utilities')
						$insert = $event.'_insert';
					else
						$insert = 'file_insert';
						
					if (!function_exists($insert)) continue;
					
					list($id,$message) = $insert(0,false,false,$file);
					
					if (is_file(FTP.$file)) {
						
						$error[] = $file;
						
						$log[] = 'ERR: '.$id.' '.$file;
					
					} else {
						
						$log[] = 'ADD: '.$id.' '.$file;
					}
					
					if ($message) {
						$log[] = $message;
					}
					
					if ($id) {
						sleep(BATCH_PAUSE);
					}
					
					$inserts++;
				}
			}
			
			$log[] = "------------------------------";
			
			write_to_file(FTP.'_LOG',implode(n,$log));
			write_to_file(FTP.'_ERROR',implode(n,$error));
			
			$count += $inserts;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($count > TOTAL_LIMIT) {
			
			$files = array();
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// process the next batch of files if any
		/*
		if (count($files)) {
			
			$params = array(
				'event'    => $type,
				'step'     => 'add_existing_files',
				'ext'	   => $ext,
				'count'	   => $count,
				'user'     => $txp_user,
				'key'      => '123',
				'app_mode' => 'async'
			);
			
			sleep(BATCH_PAUSE);
			
			// curl_gps_async('GET', hu."admin/index.php", $params);
			// exit;
			
			// TESTING
			// event_add_existing_files($ext,$type,$count);
			return;
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// finished

		$log = explode(n,read_file(FTP.'_LOG'));
		
		$log[] = "Files added: $count";
		$log[] = "------------------------------";
		
		write_to_file(FTP.'_LOG',implode(n,$log));
		
		delete_file(FTP.'_LOCK');
	}

// -------------------------------------------------------------
	function save_note_status()
	{
		global $WIN;

		$key = gps('type').'.'.gps('noteid');
		
		if (isset($WIN['notes'][$key])) { 
		
			$WIN['notes'][$key]['status'] = gps('status');
			$WIN['notes'][$key]['minmax'] = gps('minmax');
			$WIN['notes'][$key]['width']  = gps('width');
			$WIN['notes'][$key]['height'] = gps('height');
			$WIN['notes'][$key]['x'] = gps('left');
			$WIN['notes'][$key]['y'] = gps('top');
			$WIN['notes'][$key]['z'] = gps('z');
			
			save_session($WIN);
		}
	}

// -------------------------------------------------------------
	function save_note_text()
	{
		global $WIN;
		
		$type = gps('type');
		$id   = gps('noteid');
		$key  = $type.'.'.$id;
		$text = gps('text');
		
		if (isset($WIN['notes'][$key])) { 
			
			include_once txpath.'/include/lib/txp_lib_ContentSave.php';
			
			$table = $WIN['notes'][$key]['table'];
			
			$set = array(
				'Body' 		   => $text,
				'Body_html'    => 1,
				'textile_body' => USE_TEXTILE
			);
			
			content_save($id, $set, $type, $table);
			
			$out['html'] = doStrip(fetch('Body_html',$table,"ID",$id));
			$out['text'] = doStrip(fetch('Body',$table,"ID",$id));
		
			if (!function_exists('json_encode')) include txpath.'/lib/txplib_json.php';

			echo json_encode($out);
		}
	}

//------------------------------------------------------------------------------
	function line_numbers() 
	{	
		global $WIN;
		
		$WIN['linenum'] = gps('state','off',array('off','on'));
		
		save_session($WIN);
	}

// -------------------------------------------------------------
	function curl_gps_async($method, $url, $params=array())
	{
		$url = parse_url($url);
		
		$host = $url["host"];
		$path = $url["path"];
		
		foreach ($params as $key => &$val) {
			if (is_array($val)) $val = impl($val);
			$params[$key] = $key.'='.$val;
		}
		
		$params = implode('&', $params);
		
		$fp = fsockopen($host, 80, $errno, $errstr, 30);
	
		if ($errno) {
			echo "Couldn't open a socket to ".$host.'/'.$path." (".$errstr.")";
		}
		
		$path .= ($method == 'GET' and $params) ? '?'.$params : '';
		
		$out  = "$method $path HTTP/1.1\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Connection: Close\r\n\r\n";
		$out .= ($method == 'POST' and $params) ? $params : '';
		
		fwrite($fp, $out);
		fclose($fp);
	}

// -----------------------------------------------------------------------------
	function textile_title_field($incoming, $use_textile)
	{
		if (!isset($incoming['Title'])) return $incoming;
		
		$incoming['Title_html'] = textile_simple(trim($incoming['Title']));
		
		return $incoming;
	}
	
// -----------------------------------------------------------------------------
	function textile_main_fields($incoming, $use_textile)
	{
		global $txpcfg;
		
		include_once txpath.'/lib/classTextile.php';
		$textile = new TextileTXP();
		
		if (isset($incoming['Title'])) {
		
			$incoming['Title_html'] = trim($incoming['Title']);
		}
		
		if (!isset($incoming['textile_body']) or !isset($incoming['Body'])) { 
			
			return $incoming; 
		}
		
		$body    = trim($incoming['Body']);
		$excerpt = trim($incoming['Excerpt']);
		
		if ($incoming['textile_body'] == LEAVE_TEXT_UNTOUCHED) {

			$incoming['Body_html'] = trim($incoming['Body']);

		} elseif ($incoming['textile_body'] == USE_TEXTILE) {
			
			// allow double quotes within textile link titles
			$body = str_replace(' ""',' "&#34;',$body);
			$body = str_replace('"":','&#34;":',$body);
			
			$incoming['Body_html']  = $textile->TextileThis($body);
			$incoming['Title_html'] = textile_simple(trim($incoming['Title']));
			
		} elseif ($incoming['textile_body'] == CONVERT_LINEBREAKS) {

			$incoming['Body_html'] = nl2br($body);
			
		} elseif ($incoming['textile_body'] == CONVERT_PARAGRAPHS) {
			
			$incoming['Body_html'] = nl2p($body);
		}
		
		if (!isset($incoming['textile_excerpt']) or !isset($incoming['Excerpt'])) { 
			
			return $incoming; 
		}
		
		if ($incoming['textile_excerpt'] == LEAVE_TEXT_UNTOUCHED) {

			$incoming['Excerpt_html'] = trim($excerpt);

		} elseif ($incoming['textile_excerpt'] == USE_TEXTILE) {
		
			// allow double quotes within textile link titles
			$excerpt = str_replace(' ""',' "&#34;',$excerpt);
			$excerpt = str_replace('"":','&#34;":',$excerpt);

			$incoming['Excerpt_html'] = $textile->TextileThis($excerpt);

		} elseif ($incoming['textile_excerpt'] == CONVERT_LINEBREAKS) {

			$incoming['Excerpt_html'] = nl2br($excerpt);
			
		} elseif ($incoming['textile_excerpt'] == CONVERT_PARAGRAPHS) {

			$incoming['Excerpt_html'] = nl2p($excerpt);
		}
		
		return $incoming;
	}

// -----------------------------------------------------------------------------
	function add_folder_image($id=0)
	{
		global $WIN;
		
		$where = array(
			"ImageID = 0",
		);
		
		if ($id) {
			$where[] = "ID IN (".in(do_list($id)).")";
		} else {
			$where[] = "Children > 0";
			$where[] = "ParentID != 0";
		}
		
		$ids = safe_column("ID",$WIN['table'],doAnd($where)." ORDER BY Level DESC");
		
		foreach ($ids as $id) {
			
			$image = safe_field("ImageID",$WIN['table'],
				"ParentID = $id AND ImageID > 0 AND Trash = 0 ORDER BY Posted ASC");
				
			if ($image) {
				safe_update($WIN['table'],"ImageID = $image","ID = $id");
			}
		}
	}
	
// -----------------------------------------------------------------------------
	function do_pings()
	{
		global $txpcfg, $prefs, $production_status;

		# only ping for Live sites
		if ($production_status !== 'live')
			return;

		include_once txpath.'/lib/classIXR.php';

		callback_event('ping');

		if ($prefs['ping_textpattern_com']) {
			$tx_client = new IXR_Client('http://textpattern.com/xmlrpc/');
			$tx_client->query('ping.Textpattern', $prefs['sitename'], hu);
		}

		if ($prefs['ping_weblogsdotcom']==1) {
			$wl_client = new IXR_Client('http://rpc.pingomatic.com/');
			$wl_client->query('weblogUpdates.ping', $prefs['sitename'], hu);
		}
	}

?>
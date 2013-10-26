<?php

	$content_tables = array(
		'article' 	=> 'textpattern',
		'image'   	=> 'txp_image',
		'file'    	=> 'txp_file',
		'link'    	=> 'txp_link',
		'custom'  	=> 'txp_custom',
		'category' 	=> 'txp_category',
		'comment' 	=> 'txp_discuss',
		'page' 		=> 'txp_page',
		'form' 		=> 'txp_form',
		'css' 		=> 'txp_css',
		'site' 		=> 'txp_site',
		'user' 		=> 'txp_users'
	);
		
// -----------------------------------------------------------------------------
// diagnostics

	function testing() 
	{
		echo "OK";
	}


// -----------------------------------------------------------------------------
// diagnostics

	function diagnostics() 
	{
		doDiagnostics();
	}

// -----------------------------------------------------------------------------

	function view_inspector() 
	{
		echo read_file(txpath.'/inspector/index.html');
	}
	
// -----------------------------------------------------------------------------

	function clear_inspector() 
	{
		echo write_to_file(txpath.'/inspector/index.html','');
	}

// -----------------------------------------------------------------------------
// fix seconds for posted date so that there are no dublicate posted dates

	function fix_posted_date() 
	{
		if (safe_count('textpattern t1',"(SELECT COUNT(*) FROM textpattern t2 WHERE t1.Posted = t2.Posted) != 1")) {
		
			$res = safe_column("ID","textpattern t1","(SELECT COUNT(*) FROM textpattern t2 WHERE t1.Posted = t2.Posted) != 1",1);
		
			foreach ($res as $id) {
				safe_update('textpattern',"Posted = ADDTIME(Posted,SEC_TO_TIME(SECOND(SEC_TO_TIME(Position))))","ID = $id",1); 
			}
		} else {
			
			pre('all posted dates are good');
		}
	}

// -----------------------------------------------------------------------------
// clear caches

	function clear_caches() 
	{	
		global $WIN;
		
		safe_update("txp_users","updated = 1","1",1);
		clear_session_data(1,1);
		clear_cache(1);
		
	 //	safe_update("txp_cache","html = ''","1 = 1",1);
	 // safe_update("txp_cache","file = 1","file IN (2,3)",1);
	 // safe_update("txp_cache","stop = 0","1=1",1);
	 //	safe_delete("txp_cache","file = 0",1);
	}	

// -----------------------------------------------------------------------------

	function update_cache_levels() 
	{
		global $event;
		
		$rows = safe_rows("id,page,level","txp_cache","file >= 0");
		
		if (!count($rows) and $event == 'utilities') {
			pre("there are no cached pages");
		}
		
		foreach ($rows as $row) {
			
			extract($row);
			
			$level = count(explode('/',$page)) - 1;
			
			safe_update('txp_cache',"level = '$level'","id = $id",1);
		}
	}

// -----------------------------------------------------------------------------
// trim dashes from url_title

	function clean_url_title() 
	{
		$res = safe_rows("ID,url_title","textpattern","1 = 1");
		
		foreach ($res as $row) {
			
			extract($row);
			
			if (preg_match('/^\-/',$url_title) or preg_match('/\-$/',$url_title)) {
			
				$url_title = trim($url_title,'-');
				
				safe_update('textpattern',"url_title = '$url_title'","ID = $ID",1);
			}
		}
	}

// -----------------------------------------------------------------------------
// fix parent positions
	
	function fix_parent_positions() 
	{
		$rows = safe_rows('ID,Parent','textpattern','Parent != 0');
		
		foreach ($rows as $row) {
			
			$parent_position = fetch('Position','textpattern','ID',$row['Parent']);
			
			if ($parent_position !== '') {
			
				safe_update('textpattern',"ParentPosition = $parent_position","ID = ".$row['ID'],1);
			
			}
		}
	}

// -----------------------------------------------------------------------------
// renumarate positions
	
	function renumarate_positions() 
	{	
		global $content_tables;
		
		foreach ($content_tables as $type => $table) {
		
			renumerate(0,1,1,$table);
		}
	}

// -----------------------------------------------------------------------------
// rebuild artice path indexes
	
	function rebuild_path_indexes() 
	{
		global $PFX, $WIN, $content_tables;
		
		foreach ($content_tables as $type => $table) {
			
			if (table_exists($table,$PFX)) {
				
				$WIN['table'] = $table;
				
				echo hed($table);
				
			 // trim_path_columns($table,2);
				
				getmicrotime('update_path');
				
				update_path(0,1,$table,$type,3);
				pre("Time: ".getmicrotime('update_path').' seconds');
				
				$rows = safe_count($table);
				getmicrotime('rebuild_txp_tree');
				rebuild_txp_tree(0,0,$table);
				pre("Rebuild B-Tree: ".getmicrotime('rebuild_txp_tree')." seconds for $rows rows");
				
				getmicrotime('update_childcount');
				$children   = "(SELECT ParentID FROM ".safe_pfx($table)." WHERE Trash = 0 AND Name != 'TRASH')";
				$childcount = "(SELECT COUNT(*) FROM $children AS `child` WHERE child.ParentID = ID)";
				safe_update($table,"Children = $childcount",1,1);
				pre("Update child count: ".getmicrotime('update_childcount').' seconds');
				
				// - - - - - - - - - - - - - - - - - - - - -
				// fill empty Name columns 
				
				$titles = safe_column('ID,Title',$table,"Name = ''");
				
				if (count($titles)) {
					foreach ($titles as $id => $title) {
						$name = make_name($title);
						safe_update($table,"Name = '$name'","ID = $id");
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - -
				
				update_parent_info($table,0,1);
			}
		}
	}

// -----------------------------------------------------------------------------
// rebuild page templates
	
	function rebuild_pages() 
	{
		safe_update("txp_page","Body_xsl = ''","1=1");
		
		echo "<pre>";
		
		update_user_html_cache();
		
		echo "</pre>";
	}
	
// -----------------------------------------------------------------------------
// update image articles
	
	function update_image_count() 
	{
		global $PFX;
		
		safe_update("txp_image AS i",
			"Articles = (SELECT COUNT(*) FROM ".$PFX."textpattern AS t WHERE i.ID = t.ImageID AND t.Trash = 0)",
			"i.Type = 'image'",1);
		
		if (!$PFX and column_exists('txp_site','ImageID')) {
			safe_update("txp_image AS i",
				"Articles = Articles + (SELECT COUNT(*) FROM txp_site AS t WHERE i.ID = t.ImageID AND t.Trash = 0)",
				"i.Type = 'image'",1);
		}
		
		update_summary_field('txp_image','Articles',1); 
	}

// -----------------------------------------------------------------------------
// update Categories field from txp_content_category table
	
	function update_categories() 
	{
		global $PFX,$content_tables;
		
		foreach ($content_tables as $type => $table) {
			
			if (table_exists($table,$PFX)) {
				
				$WIN['table'] = $table;
				
				echo hed($table);
				
				$ids = safe_column('ID',$table,"1=1");
				
				$total = 0;
				
				foreach ($ids as $id) {
					
					$name = "CONCAT(name,'.',position) AS name";
					
					$categories = safe_column(array('position',$name),'txp_content_category',"type = '$type' AND article_id = $id AND name != 'NONE' ORDER BY position ASC");
					
					if ($categories) {
						$categories = implode(',',$categories);
						safe_update($table,"Categories = '$categories'","ID = $id",1);
						$total += 1;
					}
				}
				
				pre("Total: $total");
			}
		}
		
		update_category_count();
	}
	
// -----------------------------------------------------------------------------
// update category count
/*	
	function update_category_count() 
	{
		global $PFX;
		
		// update category count
		
		safe_update("txp_category AS c",
			"Articles = (SELECT COUNT(*) FROM ".$PFX."txp_content_category AS cc WHERE c.Name = cc.name AND cc.type = 'article')",
			"c.Type != 'folder'",1);
		
		safe_update("txp_category AS c",
			"Images = (SELECT COUNT(*) FROM ".$PFX."txp_content_category AS cc WHERE c.Name = cc.name AND cc.type = 'image')",
			"c.Type != 'folder'",1);
			
		safe_update("txp_category AS c",
			"Files = (SELECT COUNT(*) FROM ".$PFX."txp_content_category AS cc WHERE c.Name = cc.name AND cc.type = 'file')",
			"c.Type != 'folder'",1);
			
		update_summary_field('txp_category','Articles',1);
		update_summary_field('txp_category','Images',1);
		update_summary_field('txp_category','Files',1); 
		
		// update Categories column in textpattern table 
		
		$ids = safe_column('ID','textpattern',"1=1");
		
		foreach ($ids as $id) {
			
			$name = "CONCAT(name,'.',position) AS name";
			
			$categories = safe_column(array('position',$name),'txp_content_category',"article_id = $id AND name != 'NONE' ORDER BY position ASC");
			
			if ($categories) {
				$categories = implode(',',$categories);
				safe_update('textpattern',"Categories = '$categories'","ID = $id",1);
			}
		}
	}
*/
// -----------------------------------------------------------------------------
// update file download count
	
	function update_file_summary()
	{
		global $file_base_path;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get all files that do not have a size and update size
		
		$files = safe_rows('ID,FileID,FileName','txp_file',
			"Type != 'folder' 
			 AND Trash = 0 
			 AND FileName != '' 
			 AND (size = 0 OR size IS NULL)");
		
		foreach ($files as $file) {
			
			extract($file);
			
			$file = build_file_path($file_base_path,$FileName,$FileID);
			
			if (is_file($file)) {
				$size = filesize($file);
				safe_update('txp_file',"size = $size","ID = $ID");
			}
		} 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$rows = safe_column('ID,Title','txp_file',"Type = 'folder' AND Trash = 0 ORDER BY Level DESC");
		
		foreach($rows as $id => $title) {
		
			$sum = safe_row("SUM(downloads) AS downloads, SUM(size) AS size",
				"txp_file","ParentID = $id AND Trash = 0");
			
			extract($sum);
			
			if (!$downloads) $downloads = 0;
			if (!$size) $size = 0;
			
			safe_update("txp_file","downloads = $downloads, size = $size","ID = $id");
			
			pre("$title: ".format_bytes($size)." / ".num_thousand_sep($downloads).' Downloads');
		}
	}
	
// -----------------------------------------------------------------------------
// update site content count
	
	function update_site_content_count() 
	{
		global $DB, $PFX;
		
		$db = $DB->db;
		
		$sites = safe_column("ID,Prefix","txp_site","DB = '$db' AND Type = 'site' AND Trash = 0");
		
		foreach ($sites as $id => $PFX) {
			
			echo "$PFX";
			
			if ($PFX) $PFX .= '_';
			
			$articles = safe_count('textpattern',"Trash = 0 AND ParentID != 0 AND Type != 'trash'",1);
			$images   = safe_count('txp_image',"Trash = 0 AND ParentID != 0 AND Type = 'image' AND ImageID != 0",1);
			$files    = safe_count('txp_file',"Trash = 0 AND ParentID != 0 AND Type NOT IN ('folder','trash')",1);
			
			$PFX = '';
			
			safe_update('txp_site',
				"Articles = $articles, Images = $images, Files = $files",
				"ID = $id",1);
		}
	}

// -----------------------------------------------------------------------------
// make excerpt from body
	
	function make_excerpts() 
	{
		$bodies = safe_column(
			array("ID","LEFT(Body,1500) AS Body"),
			'textpattern',
			"Body != '' AND custom_4 = 'X'",0,1);
		
		$count = 1;
			
		foreach ($bodies as $id => $body) {
			
			// remove img tags 
			
			$images = 0;
			if (preg_match('/<img\s/',$body)) {
				$body = preg_replace('/<img\s[^\>]+?'.'\/>/','',$body);
				$images = 1;
			}
			
			// remove html tags 
			
			$body = preg_replace('/<[a-zA-Z1-9]+\s?[^\>]+?'.'>/','',$body);
			$body = preg_replace('/<[a-zA-Z1-9]+>/','',$body);
			$body = preg_replace('/<\/[a-zA-Z1-9]+>/','',$body);
			
			$bodylen = strlen($body);
			
			$paragraphs = explode(n.n,$body);
			$paracount  = count($paragraphs);
			$excerpt    = array_shift($paragraphs);
			
			while ($paragraphs and strlen($excerpt) < 500) {
				
				$excerpt .= n.n.array_shift($paragraphs);
			}
			
			$excerptlen = strlen($excerpt);
			
			pre("$count:$id:$bodylen:$images:$paracount:$excerptlen");
			
			$html = nl2p(trim($excerpt));
			
			$excerpt = doSlash($excerpt);
			$html = doSlash($html);
			
			safe_update('textpattern',
				"Excerpt = '$excerpt', 
				 Excerpt_html = '$html', 
				 textile_excerpt = 3, 
				 custom_4 = 'X'",
				"ID = $id");
				
			$count += 1;
		}
	}
	
// -----------------------------------------------------------------------------
// clean_article_body_html
	
	function clean_article_body_html() 
	{
		$do_excerpt = 1;
		
		if ($do_excerpt) {
			
			$body_html = safe_column("ID,Excerpt_html",'textpattern',
				"Excerpt = '' AND Excerpt_html != '' AND custom_1 != '' AND custom_1 != 'X'",0,1);
		} else {		
		
			$body_html = safe_column("ID,Body_html",'textpattern',
				"Body_html != '' AND custom_1 != '' AND textile_body = 0",0,1);
		}
		
		$count = 1;
		
		foreach ($body_html as $id => $text) {
			
			$old_text = $text;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove comments 
		
			if (preg_match('/<\!\-\-/',$text)) {
				
				$old_text = explode(n,$text);
				$new_text = array();
				
				$comment  = false;
				$comment_count = 0;
				
				foreach ($old_text as $line) {
					
					if ($comment) {
						
						if (preg_match('/\-\->/',$line)) {
							
							$comment = false;
							$line = trim(preg_replace('/.*?\-\->/','',$line));
						
							if (preg_match('/<\!\-\-/',$line)) {
								$comment = true; 
								$comment_count += 1;
								$line = trim(preg_replace('/<\!\-\-.*/','',$line));
							}
						
							if (strlen($line)) $new_text[] = $line; 
						}
						
					} else {
						
						if (preg_match('/<\!\-\-/',$line)) {
						
							$comment = true; 
							$comment_count += 1;
							$line = trim(preg_replace('/<\!\-\-.*/','',$line));
							if (strlen($line)) $new_text[] = $line; 
						
						} else {
							
							$new_text[] = $line;
						}
					}
				}
			
				$old_text = $text;
				$text = implode(n,$new_text);
				
				pre("$comment_count comments removed");
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove img tags 
			
			if ($do_excerpt) {
				
				$images = 0;
				if (preg_match('/<img\s/',$text)) {
					$text = preg_replace('/<img\s[^\>]+?'.'\/>/','',$text);
					$images = 1;
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove html tags 
			
			if ($do_excerpt) {
				$text = preg_replace('/<[a-zA-Z1-9]+\s?[^\>]+?'.'>/','',$text);
				$text = preg_replace('/<[a-zA-Z1-9]+>/','',$text);
				$text = preg_replace('/<\/[a-zA-Z1-9]+>/','',$text);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove empty tags;
		
			$text = preg_replace('/(<([a-z1-6]+)\s?([^\>]+?)?'.'>)\s*(<\/\2>)/',"",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove spaces after opening tag and before closing tag
			
			// $text = preg_replace('/(<([a-z1-6]+)\s?([^\>]+?)?'.'>)\s+/',"$1",$text);
			// $text = preg_replace('/\s+(<\/[a-z1-6]+>)/',"$1",$text);
			
			// put back spaces before closing span tag 
			
			$text = preg_replace('/([^\>].)(<\/span>)/',"$1 $2",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// add space before opening tag and after closing tag
			
			$text = preg_replace('/(\w)(<a\s)/',"$1 $2",$text);
			$text = preg_replace('/(<\/a>)(\w)/',"$1 $2",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// add line break between close and open p tag
			
			$text = preg_replace('/>\s*<(p|blockquote|ul|ol)([>\s])/',">\n\n<$1$2",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove target from a tags 
			
			$text = preg_replace('/\s+target\="_self"/','',$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove line breaks tags
			
			$text = preg_replace('/[\r\n]/',' ',$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove p tags
			
			$text = preg_replace('/<p\s?[^\>]+?'.'>/','',$text);
			$text = preg_replace('/<p>/','',$text);
			$text = preg_replace('/<\/p>\s*/',"\n\n",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove span tags
			
			$text = preg_replace('/<span\s?[^\>]+?'.'>/','',$text);
			$text = preg_replace('/<span>/','',$text);
			$text = preg_replace('/<\/span>/','',$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove article tags
			
			$text = preg_replace('/<article>/','',$text);
			$text = preg_replace('/<\/article>/','',$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove '</i><i>' tags
			
			$text = preg_replace('/<\/i><i>/','',$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove br tags
			
			$text = preg_replace('/<br\s?\/>/',"\n",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// replace quote entities with quotes
			
			$text = preg_replace('/&[lr]?quot;/',"'",$text);
			$text = preg_replace('/&[lr]?squo;/',"'",$text);
			$text = preg_replace('/&[lr]?dquo;/','"',$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// replace nbsp entity with space
			
			$text = preg_replace('/&nbsp;/'," ",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			// remove extra spaces
			
			$text = preg_replace('/[ ][ ]+/'," ",$text);
			$text = preg_replace('/[ ]\t+/'," ",$text);
			$text = preg_replace('/\t+/'," ",$text);
			$text = preg_replace('/([\r\n])[ ]/',"$1",$text);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// add p and br tags to html
			
			$html = nl2p(trim($text));
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			/*
			if (preg_match('/<h3/',$text)) {
				
				preg_match_all('/<h3>.+<\/h3>/',$text,$matches);
				
				pre('---------------------------------------------------------');
				pre(fetch('Title','textpattern','ID',$id));
				pre($matches);
				// pre(htmlentities(implode('',$matches)));
			}
			*/
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
			
			// pre(htmlentities($html));
			
			$text = doSlash($text);
			$html = doSlash($html);
			
			pre("$count: $id");
			
			if ($do_excerpt) {
				safe_update('textpattern',"Excerpt = '$text', Excerpt_html = '$html', textile_excerpt = 3","ID = $id");
			} else {
				safe_update('textpattern',"Body = '$text', Body_html = '$html', textile_body = 3","ID = $id");
			}
			
			$count += 1;
		}
	}
	
// -----------------------------------------------------------------------------
// fix_title_field
	
	function fix_title_field() 
	{
		global $DB, $PFX, $content_tables;
		
		$db = $DB->db;
		
		$sites = safe_column("ID,Prefix","txp_site","DB = '$db' AND Type = 'site' AND Trash = 0");
		
		foreach ($sites as $id => $PFX) {
			
			echo hed($PFX);
			
			if ($PFX) $PFX .= '_';
			
			foreach ($content_tables as $type => $table) {
			
				if (table_exists($table,$PFX)) {
					
					$WIN['table'] = $table;
					
					$titles = safe_column("ID,Title",$table,"1=1",1);
					
					foreach ($titles as $id => $title) {
						
						$title = textile_title($title);
						
						safe_update($table,"Title_html = '$title'","ID = $id");
					}
				}
			}		
				
			$PFX = '';
		}
		
		foreach ($content_tables as $type => $table) {
				
			$WIN['table'] = $table;
			
			$titles = safe_column("ID,Title",$table,"1=1",1);
			
			foreach ($titles as $id => $title) {
					
				$title = textile_title($title);
				
				safe_update($table,"Title_html = '$title'","ID = $id");
			}
		}
	}
	
// -----------------------------------------------------------------------------
// reorganize files
	
	function reorganize_files() 
	{	
		global $file_base_path;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// create new FTP folder
		
		$new_ftp_dir = $file_base_path.DS.'_ftp'.DS;
		
		if (!is_dir($new_ftp_dir)) {
			
			mkdir($new_ftp_dir,0777);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// move library files to ID folders
		
		$rows = safe_rows('id,name','txp_file','1=1 ORDER BY ID ASC');
		
		foreach ($rows as $row) { 
			
			extract($row);
			
			$path = get_image_id_path($id);
			
			if (!is_dir($file_base_path.DS.$path)) {
				
				mkdir($file_base_path.DS.$path,0777,true);
			}
			
			$src = $file_base_path.DS.$filename;
			$dst = $file_base_path.DS.$path.DS.$filename;
			
			if (is_file($src) and !is_file($dst)) {
				
				pre('---------------');
				pre($src);
				pre($dst);
			
				copy($src,$dst);
					
				if (is_file($dst)) {
						
					unlink($src);
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// move remaining files to the FTP folder
		
		$files = dirlist($file_base_path);
		
		foreach ($files as $file) {
			
			if (is_file($file)) {
				
				$src = $file_base_path.DS.$file;
				$dst = $new_ftp_dir.$file;
				
				pre('---------------');
				pre($src);
				pre($dst);
				
				copy($src,$dst);
				
				if (is_file($dst)) {
				
					unlink($src);
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		pre('ok');
	}

// -----------------------------------------------------------------------------
// reorganize images
	
	function reorganize_images($site_dir='') 
	{	
		global $txpcfg;
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// create new FTP folder
		
		if (!$site_dir) {
			$site_dir = $txpcfg['path_to_site'];
		}
		
		$uploads_dir = $site_dir.'/images/uploads';
		$content_dir = $site_dir.'/images/content';
		$new_ftp_dir = $content_dir.'/_ftp'.DS;
		
		if (!is_dir($new_ftp_dir)) {
			
			mkdir($new_ftp_dir,0777,true);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// move library images to ID folders
		
		$rows = safe_rows('id,name,ext','txp_image',"ext != ''");
		
		$images_in_db = count($rows);
		$images_moved = 0;
		$files_moved  = 0;
		
		foreach ($rows as $row) { 
			
			$moved = 0;
			
			extract($row);
			
			$path = get_image_id_path($id,DS);
			
			if (!is_dir($content_dir.DS.$path)) {
				
				mkdir($content_dir.DS.$path,0777,true);
			}
			
			foreach (array('','r','t','x','y','z','THUMB') as $size) {
				
				$src = $uploads_dir.DS.$name.$size.$ext;
				$dst = $content_dir.DS.$path.DS.$name.($size ? '_' : '').$size.$ext;
				
				if (is_file($src) and !is_file($dst)) {
					
					copy($src,$dst);
					
					if (is_file($dst)) {
						
						unlink($src);
						
						if (column_exists('txp_image','ImageID')) {
							
							$title = make_title($name);
							
							safe_update('txp_image',
								"ImageID  = $id, 
								 FileDir  = $id, 
								 FilePath = '$path', 
								 Title    = '$title', 
								 Type     = 'image'",
								"ID = $id");
						}
						
						$moved += 1;
					}
				}
			}
			
			if ($moved) {
				
				$images_moved += 1;
				$files_moved += $moved;
				
				safe_update('txp_prefs',"val = 'images/content'","name = 'img_dir'");
			}
		}
		
		return array($images_in_db,$images_moved,$files_moved);
	}
	
// -----------------------------------------------------------------------------
// reorganize images
/*	
	function reorganize_images() 
	{	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// create new FTP folder
		
		$new_ftp_dir = IMG_PATH.'_ftp'.DS;
		
		if (!is_dir($new_ftp_dir)) {
			
			mkdir($new_ftp_dir,0777);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// move library images to ID folders
		
		$rows = safe_rows('id,name,ext','txp_image',"ext != ''");
		
		foreach ($rows as $row) { 
			
			extract($row);
			
			$path = get_image_id_path($id);
			
			if (!is_dir(IMG_PATH.$path)) {
				
				mkdir(IMG_PATH.$path,0777,true);
			}
			
			foreach (array('','r','t','x','y','z','THUMB') as $size) {
				
				$src = IMG_PATH.$name.$size.$ext;
				$dst = IMG_PATH.$path.DS.$name.($size ? '_' : '').$size.$ext;
				
				if (is_file($src) and !is_file($dst)) {
					
					copy($src,$dst);
					
					if (is_file($dst)) {
						
						unlink($src);
						
						if (column_exists('txp_image','ImageID')) {
							
							$title = make_title($name);
							
							safe_update('txp_image',
								"ImageID  = $id, 
								 FileDir  = $id, 
								 FilePath = '$path', 
								 Title    = '$title', 
								 Type     = 'image'",
								"ID = $id");
						}
						
						pre($dst); 
					}
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// move remaining images to the new FTP folder
		
		$files = dirlist(IMG_PATH);
		
		// pre($files);
		
		foreach ($files as $file) {
			
			if (is_file($file)) {
				
				$src = IMG_PATH.$file;
				$dst = $new_ftp_dir.$file;
				
				copy($src,$dst);
				
				if (is_file($dst)) {
					
					unlink($src);
					
					pre($dst); 
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// move images in old FTP folder to the new FTP folder
		
		$src_dir = rtrim(IMG_PATH,'/').'_FTP/';
		
		if (!is_dir($src_dir)) {
			
			$src_dir = rtrim(IMG_PATH,'/').'-ftp/';
		}
		
		if (!is_dir($src_dir)) {
			
			$src_dir = rtrim(IMG_PATH,'/').'_ftp/';
		}
		
		if (is_dir($src_dir)) {
		
			$files = dirlist($src_dir);
			
			// pre($files);
			
			foreach ($files as $file) {
				
				if (is_file($file)) {
					
					$src = $src_dir.$file;
					$dst = $new_ftp_dir.$file;
					
					copy($src,$dst);
					
					if (is_file($dst)) {
					
						unlink($src);
						
						pre($dst); 
					}
				}
			}
			
			// delete old FTP directory
			
			$files = dirlist($src_dir);
			
			if (count($files) == 0) {
				rmdir($src_dir);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		pre('ok');
	}
*/
//--------------------------------------------------------------------------------------

	function remove_category_folders($debug=0)
	{
		$types = array(
			'article' => 'Article',
			'image'	  => 'Image',
			'file'	  => 'File',
			'link'	  => 'Link'
		);
		
		$root = fetch("ID","txp_category","ParentID",0);
		
		foreach($types as $name => $title) {
			
			$id = safe_field("ID","txp_category","Title = '$title' AND Name = '$name' AND Type = 'folder' AND ParentID = $root",$debug);
			
			if ($id) {
				safe_update("txp_category","ParentID = $root","ParentID = $id",$debug);
				safe_delete("txp_category","ID = $id",$debug);
			}
		}
	}

//--------------------------------------------------------------------------------------
	
	function export_all($debug=0) 
	{	
		$microstart = getmicrotime();
		$total = 0;
		$out = array();
		
		$tables = array(
			'txp_image',
			'txp_file',
			'txp_link',
			'txp_category',
			'txp_custom',
			'textpattern',
			'txp_discuss',
			'txp_page',
			'txp_form',
			'txp_css',
			'txp_users'
		);
		
		foreach ($tables as $table) {
			
			$id = false;
			
			if (column_exists($table,"ParentID")) {
				$id = array(fetch("ID",$table,"ParentID",0));
			}
			
			list($count,$filename,$filesize) = export($id,$table);
			
			$out[$table] = str_pad($filename,25);
			
			if ($filesize) {
				$out[$table] .= str_pad($count,8);
				$out[$table] .= format_bytes($filesize);
			}
			
			$total += $count;
		}
		
		$out[] = "-------------------------------------------------------";
		$out[] = "Total: $total".n;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$tarfile = 'export.tar';	// export archive
		$txtfile = 'info.txt';		// export info
		
		if (is_file(EXP_PATH.$tarfile))    			unlink(EXP_PATH.$tarfile);
		if (is_file(FILE_FTP_PATH.$tarfile)) 		unlink(FILE_FTP_PATH.$tarfile);
		if (is_file(EXP_PATH.$tarfile.'.gz')) 		unlink(EXP_PATH.$tarfile.'.gz');
		if (is_file(FILE_FTP_PATH.$tarfile.'.gz')) 	unlink(FILE_FTP_PATH.$tarfile.'.gz');
		
		$out[] = export_files('database',EXP_PATH,$debug); 
		$out[] = export_files('images_content',EXP_PATH,$debug);
		$out[] = export_files('images_design',EXP_PATH,$debug);
		$out[] = export_files('files',EXP_PATH,$debug);
		$out[] = export_files('javascript',EXP_PATH,$debug);   
		
		if (is_file(EXP_PATH.'export.tar')) {
		
			exec("gzip ".EXP_PATH.$tarfile,$out);
			
			if (is_file(EXP_PATH.$tarfile.'.gz')) {
				rename (EXP_PATH.$tarfile.'.gz',FILE_FTP_PATH.$tarfile.'.gz');
			}
			
			write_to_file(EXP_PATH.$txtfile,implode(n,$out));
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$microdiff = (getmicrotime() - $microstart);
		
		$out[] = n."-------------------------------------------------------";
		$out[] = "Runtime: ".substr($microdiff,0,5)." seconds";
		
		pre(implode(n,$out));
	}

//--------------------------------------------------------------------------------------
	
	function import_all($debug=0) 
	{
		global $txp_user, $path_to_site, $file_dir;
		
		define("IMPORT",true);
		
		$tables = array(
			'txp_category' 	=> 'Categories',
			'txp_custom'   	=> 'Custom Fields',
			'txp_link'   	=> 'Links',
			'txp_image'		=> 'Images',
			'txp_file'		=> 'Files',
			'textpattern'   => 'Articles',
			'txp_discuss'   => 'Comments',
			'txp_page'      => 'Pages',
			'txp_form'      => 'Forms',
			'txp_css'       => 'Style',
			'txp_users'     => 'Users'
		);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$import = extract_import();
		
		if (!is_array($import)) {
			
			$class = (substr($import,0,5) == 'Error') ? ' class="error"' : '';
			
			echo graf($import,$class);
			
			return;
		}
		
		foreach ($import as $name => $item) {
			
			if (in_list($name,'images,files,db')) {
				
				foreach ($item as $key => $file) {
					
					$size = $file[1];
					$file = $file[0];
					
					$import[$name][$key] = array(
						'file'  => $file,
						'size'  => $size
					);
					
					if ($name == 'db') {
						
						$table = str_replace('.xml','',$file);
						
						if (isset($tables[$table])) {
							
							$title = $tables[$table];
							
							$tables[$table] = array(
								'file'  => $file,
								'size'  => $size,
								'table' => $table,
								'title' => $title
							);
						} else {
							
									
						}
					}
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$total = array(
			'count'	  => 0,
			'size'	  => 0,
			'runtime' => 0
		);
		
		$info = array();
		
		foreach ($tables as $table) {
			
			$type = '';
			
			if (!is_array($table)) continue;
			
			extract($table);
			
			if ($table == 'txp_users') {
				
				continue;
				
				/* if (!has_privs('admin.edit', $txp_user)) {
					echo "(Unauthorized)".n.n; continue;
				} */
			}
			
			$info[$table]['title'] = $title;
			
			if (column_exists($table,"ParentID")) {
				
				$type = ($table == 'textpattern') ? 'article' : substr($table,4);
			}
			
			clear_items($table,$type);
			add_import_id_column($table,$type);
			
			$microstart = getmicrotime();
			
			list($count,$items) = import(0,$table,$type,IMP_PATH.$file);
			
			// echo "<ul><li>".implode("</li>\n<li>",$items)."</li></ul>";
			
			$total['runtime'] += $microdiff = (getmicrotime() - $microstart);
			
			$info[$table]['count']   = $count;
			$info[$table]['size']    = format_bytes($size);
			$info[$table]['runtime'] = substr($microdiff,0,4).' sec';
			
			$total['count'] += $count;
			$total['size']  += $size;
			
			unlink(IMP_PATH.$file);
		}
		
		echo "<table>";
		
		echo "<tr>";
		echo "<th>Name</th>";
		echo "<th>Items</th>";
		echo "<th>Size</th>";
		echo "<th>Runtime</th>";
		echo "</tr>";
		
		foreach ($info as $key => $item) {
			
			echo "<tr>";
			
			foreach ($item as $class => $value) {
				echo '<td class="'.$class.'">'.$value.'</td>';
			}
			
			echo "</tr>";
		}
		
		echo '<tr class="total">';
		echo "<td></td>";
		echo "<td>".$total['count']."</td>";
		echo "<td>".format_bytes($total['size'])."</td>";
		echo "<td>".substr($total['runtime'],0,4)." sec</td>";
		echo "</tr>";
		
		echo "</table>";
	}

// -----------------------------------------------------------------------------

	function create_download() 
	{
		global $path_to_site;
		
		$src 	= txpath.DS.'setup'.DS.'www'.DS;
		$ftp	= $path_to_site.DS.'files'.DS.'_ftp'.DS;
		$dlpath = $path_to_site.DS.'files'.DS.'_download'.DS;
		$dlname = 'txp_'.date('Y_m_d');
		$dst 	= $dlpath.$dlname.DS;
		
		if (!is_dir($dst)) mkdir($dst);
		
		// ---------------------------------------------------------
		// copy www files to download folder
		
		copy($src.'setup.php',$dst.'setup.php');
		
		// ---------------------------------------------------------
		// create textpattern archive
		
		$archive = "textpattern.tar";
		$zip     = "textpattern.tar.gz";
		
		chdir($path_to_site);
		
		exec("tar -pcf $archive textpattern",$null);
		
		if (is_file($path_to_site.DS.$archive)) {
			
			exec("gzip $archive",$null);
			
			if (is_file($path_to_site.DS.$zip)) {
			
				if (is_file($path_to_site.DS.$archive)) {
					unlink($path_to_site.DS.$archive);
				}
				
				@chmod($path_to_site.DS.$zip, 0777);
			  
				rename($path_to_site.DS.$zip,$dst.$zip);
			}
		}
		
		// ---------------------------------------------------------
		// create download archive
		
		if (is_file($dst.$zip)) {
			
			chdir($dlpath);
			
			$tar = $dlname.'.tar';
			$zip = $dlname.'.tar.gz';
			
			exec("tar -pcf $tar $dlname",$null);
			exec("gzip $tar",$null);
		}
		
		if (is_file($dlpath.$zip)) { 
			
			// delete download folder
			rmdirlist($dlpath.$dlname);
			
			// delete archive file
			if (is_file($dlpath.$tar)) {
				unlink($dlpath.$tar);
			}
			
			// move dowwnload archive to ftp folder
			rename($dlpath.$zip,$ftp.$zip);
			@chmod($ftp.$zip, 0555);
		}
		
		if (is_file($ftp.$zip)) { 
		
			echo graf('<b>Archive created</b>');
			echo "<ul><li>".$ftp.$zip."</li></ul>";
			
			echo update_update_table();
		}
	}
// -----------------------------------------------------------------------------

	function log_by_day() 
	{
		define("IMPORT",true);
		
		echo "<pre>";
		
		$parent  = fetch("ID","txp_log","ParentID",0);
		$dates   = safe_column("Posted","txp_log","Type = 'page' AND Trash = 0 GROUP BY DATE(Posted) ORDER BY Posted");
		$folders = safe_column("DATE(Posted),ID","txp_log","Type = 'folder' AND Trash = 0 ORDER BY Posted ASC");
		
		foreach ($dates as $date) {
			
			list($date,$time) = explode(' ',$date);
			$title = date('D, F j',strtotime("$date $time") + tz_offset());
			$posted = $date.' 23:59:59';
			
			echo "$date - $time - $title ";
			
			if (isset($folders[$date])) {
				
				$id = $folders[$date];
					
			} else {
			
				$set = array(
					'Title'  => $title,
					'Posted' => $posted,
					'Type'   => 'folder'
				);
				
				// TODO: insert directly 
				
				list($msg,$id,$status) = content_create($parent,$set,'txp_log','log');
			}
			
			if ($id) {
				
				$ids = safe_column("ID","txp_log","Type IN ('page','folder') AND DATE(Posted) = '$date' AND ParentID = $parent ORDER BY ID ASC");
				
				$count = count($ids) - 1;
				
				echo "(".($count).")";
				
				// TODO: change ParentID directly
				
				if ($count) {
					$edit = new MultiEdit('txp_log');
					$edit->apply('cut_cancel',$ids);
					$edit->apply('move',$ids);
				}
			}
			
			echo n;
		}

		echo "</pre>";
	}
	
// -----------------------------------------------------------------------------
// set use_textile in preferences 

//	safe_update('txp_prefs',"val = 2","name = 'use_textile'",1);

// -----------------------------------------------------------------------------

?>
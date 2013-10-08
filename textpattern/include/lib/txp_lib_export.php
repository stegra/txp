<?php

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function export($id,$table='')
{
	global $event, $export_as_tag, $export_as_para;
	
	$count = 0;
	$filesize = 0;
	
	extract(get_prefs());
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (!$table) {
		
		$table = ($event == 'list') 
			? 'textpattern' 
			: 'txp_'.$event;
	}
	
	$content_tables = array(
		'txp_image','txp_file','txp_link','txp_category','txp_custom'
	);
	
	$export_as_tag = array(
		'Body','Excerpt','Categories','custom_fields','user_html','Form','css'
	);
	
	$export_as_para = array(
		'Body','Excerpt','user_html','Form','css'
	);
	
	$exclude = array(
		'LastModMicro','PostedRev','Title_html','Body_html', 'Excerpt_html',
		'Category1','Category2','comments_count','url_title','uid',
		'feed_time','ParentPosition','lft','rgt','Trash','PositionRev',        
		'ParentStatus','OldStatus','Path','Used','ImportID','last_access',
		'user_xsl','user_html_publish','Form_xsl','session','updated',
		'Articles','Images','Files','Links','Comments','Customs'
	);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// export directory and file
	
	$xmlfile = "$table.xml";
	
	if (!is_dir(EXP_DB_PATH)) {
		mkdir(EXP_DB_PATH,0777,true);
	}

	if (is_file(EXP_DB_PATH.$xmlfile)) {
		unlink(EXP_DB_PATH.$xmlfile);
	}
	
	$f = fopen(EXP_DB_PATH.$xmlfile,"a");
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// export document attributes
	
	$doc = array(
		'date' 		=> date('Y-m-d'),
		'time' 		=> date('H:i'),
		'siteurl' 	=> $siteurl,
		'imgdir'	=> $img_dir,
		'filedir'	=> $file_dir
	);
		
	foreach ($doc as $name => $value) {
	
		$doc[$name] = $name.'="'.$value.'"';
	}
		
	$doc = '<doc '.implode(' ',$doc).'>';
	$xml = '<?xml version="1.0" encoding="utf-8"?'.'>';
	
	fwrite($f,$xml.n.$doc.n);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$select = make_select($table,$id,$exclude);
	
	export_table($f,$table,$id,$select,$count);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	fwrite($f, n."</doc>");
	
	fclose($f);
	
	if ($event == 'utilities') {
		
		if (is_file(EXP_DB_PATH.$xmlfile)) {
			$filesize = filesize(EXP_DB_PATH.$xmlfile);
		}
		
		return array($count,$xmlfile,$filesize);
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function make_select($table,$id,$exclude) 
{
	global $PFX;
	
	$root_level = 0;
	$type = ($table == 'textpattern') ? 'article' : substr($table,4);
	
	$select = getThings('describe '.$PFX.$table); 
	
	foreach ($select as $key => $column) {
		
		unset($select[$key]);
		
		if (in_array($column,$exclude)) {
			unset($select[$column]); continue;
		}
		
		if (substr($column,0,7) == 'custom_') {
			unset($select[$column]); continue;
		}
		
		if (preg_match('/^P\d+$/',$column)) {
			unset($select[$column]); continue;
		}
		
		$select[$column] = "`".$column."`";
	}
	
	if (isset($select['ParentID'])) {
		
		if ($id) {
			$root_level = safe_field("Level",$table,"ID IN (".in($id).")");
		}
		
		$select['categories'] = '';
		$select['custom_fields'] = '';
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// modifications
	
	$mod = array(
		'Level'     		=> "(Level - $root_level) 				   AS Level",
		'Expires'   		=> "IF(Expires > 0,Expires,'') 		       AS Expires",
		'Alias'   			=> "IF(Alias != 0,Alias,'') 		       AS Alias",
		'ImageID'   		=> "IF(ImageID != 0,ImageID,'') 		   AS ImageID",
		'FileID'   			=> "IF(FileID != 0,FileID,'') 		       AS FileID",
		'LastMod'   		=> "IF(LastMod != Posted,LastMod,'') 	   AS LastMod",
		'LastModID' 		=> "IF(LastModID != AuthorID,LastModID,'') AS LastModID",
		'Status'    		=> "IF(Status != 4,Status,'') 		       AS Status",
		'Annotate'    		=> "IF(Annotate = 1,Annotate,'') 		   AS Annotate",
		'AnnotateInvite'    => "IF(AnnotateInvite != 'Comment',AnnotateInvite,'') AS AnnotateInvite",
		'ImageData'    		=> "IF(ImageData != 'before:t:right:before:r:-',ImageData,'') AS ImageData",
		'textile_body'    	=> "CONCAT(textile_body,',',textile_excerpt) AS textile",
		'textile_excerpt'	=> '',
		'thumb_w'    		=> "IF(thumb_w != 100,thumb_w,'') AS thumb_w",
		'thumb_h'    		=> "IF(thumb_h != 100,thumb_h,'') AS thumb_h",
		'transparency'   	=> "IF(transparency != 0,transparency,'') AS transparency"
	);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// categories
	
	$mod['categories'] = "(SELECT GROUP_CONCAT(c.title ORDER BY tc.position ASC) 
			FROM ".$PFX."txp_content_category AS tc JOIN ".$PFX."txp_category AS c 
			WHERE t.ID = tc.article_id
				AND tc.type = '".$type."'
				AND tc.name = c.name
				AND c.type = '".$type."') 
			AS Categories";
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// custom fields
	
	$columns = getThings('describe '.$PFX.'txp_group');
	
	foreach ($columns as $key => $col) {
	
		if (substr($col,0,3) == 'by_') {
			$columns[$col] = 'g.'.$col;
		}
		
		unset($columns[$key]);
	}
	
	// group: by_id, by_parent, by_class, by_category, by_sticky, etc...
	
	$group = implode(',',$columns); 
	
	$items = array(
		'tcv.field_id',
		'tcv.instance_id',
		'tcv.field_name',
		'tcv.text_val',
		"CONCAT_WS(';',".$group.")"
	);
	
	$items = implode(",'}:{',",$items);
			
	$mod['custom_fields'] = "(SELECT GROUP_CONCAT('&','{',".$items.",'}','&')
		FROM ".$PFX."txp_content_value AS tcv, ".$PFX."txp_group AS g  
		WHERE t.ID = tcv.article_id 
			AND tcv.status = 1 
			AND tcv.type = '".$type."'
			AND tcv.field_id = g.field_id
			AND tcv.group_id = g.group_id) 
		AS custom_fields";
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	foreach ($mod as $key => $val) {
	
		if (isset($select[$key])) {
			if ($val) 
				$select[$key] = $val;
			else	
				unset($select[$key]);
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	return $select;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function export_table(&$f,$table,$id,&$select,&$count) 
{
	global $export_as_tag, $export_as_para;
	
	$debug = 0;
	
	if ($id !== false) { 
		
		if (is_array($id)) 
			$where = "ID IN (". in($id).") AND `Trash` = 0";
		else
			$where = "ParentID = $id AND `Trash` = 0";
		
		$rows = safe_rows(
			implode(','.n,$select),
			"$table AS t",
			"$where ORDER BY Posted ASC, ID ASC",$debug);
		
	} else {
	
		$rows = safe_rows(implode(','.n,$select),"$table AS t","1=1",$debug);
	}
	
	foreach ($rows as $row) {
		
		$atts = array();
		$content = array();
		$count++;
		
		foreach ($row as $name => $value) {
			
			if (in_array($name,$export_as_tag)) {
				
				if (strlen($value)) {
					
					$value = doStrip($value);
					
					if ($name == 'css') {
						$value = base64_decode($value);
					}
				
					$value = preg_replace('/\r/','\r',$value);
					$value = preg_replace('/\n/','\n',$value);
					$value = preg_replace('/\t/','\t',$value);
					
					$content[$name] = $value;
				}
			
			} else {
				
				if ($name == 'Categories') {
					$value = preg_replace('/NONE/','',$value);
				}
				
				if (strlen($value)) $atts[$name] = $value;
				
				if ($name == 'Title' and isset($atts['Name'])) {
					if (make_name($value) == $atts['Name']) {
						unset($atts['Name']);	
					}
				}
			}
		}
		
		foreach ($atts as $name => $value) {
			$value = preg_replace('/\s/','\s',$value);
			$atts[$name] = $name.'="'.htmlentities($value).'"';
		}
		
		foreach ($content as $name => $value) {
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($name == 'Categories' and $value) {
				
				$categories = explode(',',$value);
				
				foreach ($categories as $key => $category) {
				
					$categories[$key] = '<category>'.htmlentities($category).'</category>';
				}
				
				$content[$name] = implode(n.t,$categories);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			} elseif ($name == 'custom_fields' and $value) {
				
				$custom = explode('&,&',trim($value,'&'));
				
				foreach ($custom as $key => $field) {
				
					list($field_id,$instance_id,$field_name,$field_value,$group) = explode('}:{',ltrim(rtrim($field,'}'),'{'));
				
					$custom[$key] = '<custom id="'.$field_id.'" name="'.$field_name.'" group="'.$group.'" instance="'.$instance_id.'">'.htmlentities($field_value).'</custom>';
				}
				
				$content[$name] = implode(n.t,$custom);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			} elseif (in_array($name,$export_as_para) and $value) {
			
				$text = explode('\n\n',$value);
				
				if (count($text) == 1) {
					$text = explode('\r\n\r\n',$text[0]);
				}
				
				foreach ($text as $key => $para) {
					$text[$key] = '<para>'.htmlentities($para).'</para>';
				}
				
				$content[$name] = '<'.$name.'>'.n.t.t.implode(n.t.t,$text).n.t.'</'.$name.'>';
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			} else {
			
				$content[$name] = '<'.$name.'>'.htmlentities($value).'</'.$name.'>';
			}
		}
		
		$content = (count($content)) ? n.t.implode(n.t,$content).n : '';
		$content = '<item '.implode(' ',$atts).'>'.$content.'</item>'.n;
		
		fwrite($f,$content);
		
		if ($id !== false) {
			
			export_table($f,$table,$row['ID'],$select,$count);
		}
	}
}

//--------------------------------------------------------------------------------------
	
function export_files($type,$expdir,$debug=0) 
{	
	global $path_to_site;
	
	$src = '';
	$out = array();
	
	if ($type == 'database') {
	
		$src   = EXP_DB_PATH;
		
	} elseif ($type == 'files') {
	
		$src   = FILE_PATH;
		$col   = "FileID";
		$table = "txp_file";
	
	} elseif ($type == 'images_content') {
	
		$src   = IMG_PATH;
		$col   = "ImageID";
		$table = "txp_image";
	
	} elseif ($type == 'images_design') {
	
		$src   = $path_to_site.DS.'images'.DS.'design';
	
	} elseif ($type == 'javascript') {
	
		$src   = $path_to_site.DS.'js';
		
	} else {
		
		return "Error: unknown type $type";
	}
	
	if (!strlen($src) or !is_dir($src)) {
		
		return ($type != 'javascript') ? "Error: $src not found" : ''; 
	}
	
	$archive = $type.".tar";
	
	if (is_file($expdir.$archive)) {
		unlink($expdir.$archive);
	}
	
	if (is_file($expdir.$archive.'.gz')) {
		unlink($expdir.$archive.'.gz');
	}
	
	// -------------------------------------------------------------
	
	chdir($src);
	
	if ($type == 'database') {
		
		$tar = "tar -pcf";
		
		exec("$tar $expdir/$archive *",$null);
		
		foreach (dirlist($src) as $file) {
			unlink($src.DS.$file);
		}
		
		rmdir($src);
		
	} elseif (in_list($type,'images_content,files')) {
		
		$rows = safe_rows(
			"$col AS ID, Name, ext AS Ext",
			$table,
			"$col != 0 AND Trash = 0 
			 GROUP BY $col,Name,ext",'file');
		
		foreach ($rows as $row) {
			
			extract($row); 	// fileID, fileName, fileExt
			
			$path = get_file_id_path($fileID);
			
			if (is_file($path.DS.$fileName.$fileExt)) {
			
				$tar = (!is_file($expdir.$archive)) 
					? "tar -pcf" 
					: "tar -prf";
				
				exec("$tar $expdir/$archive $path/*",$null);
			}
		}
	
	} else {
		
		exec("tar -pcf $expdir/$archive *",$null);
	}
	
	// -------------------------------------------------------------
	
	chdir($expdir);
	
	if (is_file($expdir.$archive)) {
		
		$out[] = str_pad($archive,33).format_bytes(filesize($archive));
		
		$tar = (!is_file('export.tar')) 
			? "tar -pcf" 
			: "tar -prf";
		
		exec("$tar export.tar $archive",$null);
		unlink($expdir.$archive);
	}
	
	return implode(n,$out);
}
	
?>
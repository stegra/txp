<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.7
	// =========================================================================
	// add FileName column to txp_table
	
	if (!column_exists('txp_file',"FileName")) {
		
		todo("add `FileName` columns to txp_file table");
		
		safe_addcol('txp_file','FileName',"varchar(128) NOT NULL DEFAULT ''",'FileID');
		
		// populate FileName column with the real file name
		
		$filepath = $txp_prefs['file_base_path'];
		$filecount = 0;
		$filefound = 0;
		
		$res = safe_rows("ID AS ArticleID,FileID,ext,Title","txp_file","Type NOT IN ('folder','trash')");
		
		foreach ($res as $item) {
			
			$filecount += 1;
			
			extract($item);
			
			$name = make_name($Title);
			$ext  = strtolower($ext);
			$filedir  = ($FileID) ? $filepath.'/'.get_file_id_path($FileID) : '';
			$filename = '';
			
			if ($filedir and is_dir($filedir)) {
				
				$list = dirlist($filedir,$ext);
				
				if ($list) {
				
					$filename = array_shift($list);
					
					$filefound += 1;
				}
			}
			
			safe_update(
				'txp_file',
				"Name = '$name',
				 FileName = '$filename', 
				 ext = '$ext'",
				"ID = $ArticleID"
			);
		}
		
		pre("$filecount/$filefound");
	}
?>
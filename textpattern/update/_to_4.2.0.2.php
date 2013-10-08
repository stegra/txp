<?php
	
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.2
	// =========================================================================
	// add `FileDir` and `FilePath` columns to txp_image table
	
	if (!column_exists('txp_image','FileDir')) {
		
		todo("add `FileDir` and `FilePath` columns to txp_image table");
		
		safe_addcol('txp_image','FileDir',"int NOT NULL DEFAULT 0",'FileID');
		safe_addcol('txp_image','FilePath',"varchar(128) NOT NULL DEFAULT ''",'FileDir');
		
		$res = safe_rows("ID,ImageID,Type","txp_image","ImageID != 0");
		
		foreach ($res as $row) {
			
			extract($row);
			
			if ($Type == 'image') {
				$FileDir  = $ImageID;
				$FilePath = get_file_id_path($ImageID);
				$ImageID  = $ID;
			} else {
				$FileDir  = 0;
				$FilePath = '';
				$ImageID  = safe_field("ID","txp_image","ImageID = $ImageID AND Type = 'image'");
			}
			
			safe_update('txp_image',
				"ImageID = '$ImageID', 
				 FileDir = '$FileDir', 
				 FilePath = '$FilePath'",
				"id = $ID",1); 
		}
	}
	
	// ---------------------------------------------------------------------
	// add `P2` column to all tree tables
	
	todo("add `P2` column");
	
	foreach ($tree_tables as $type => $table) {
	
		if (column_exists($table,'P1')) {
			
			safe_drop($table,'P1');
			
			safe_addcol($table,'P2',"int NULL",'',1);
		}
	}
	
	// ---------------------------------------------------------------------
	// add `Children` column to all tree tables
	
	todo("add `Children` column");
	
	foreach ($tree_tables as $type => $table) {
	
		if (!column_exists($table,'Children')) {
			
			safe_addcol($table,'Children',"int NOT NULL DEFAULT -1",'',1);
		}
	}
	
	// ---------------------------------------------------------------------
	// add `Articles` column to txp_image table
	
	if (!column_exists('txp_image','Articles')) {
	
		todo("add `Articles` column to txp_image table");
		
		safe_addcol('txp_image','Articles',"int NOT NULL DEFAULT 0",'Children');
		
		update_image_count();
	}
	
	// ---------------------------------------------------------------------
	// add `Articles` column to txp_category table
	
	if (!column_exists('txp_category','Articles')) {
	
		todo("add `Articles` column to txp_category table");
		
		safe_addcol('txp_category','Articles',"int NOT NULL DEFAULT 0");
		safe_addcol('txp_category','Images',"int NOT NULL DEFAULT 0");
		safe_addcol('txp_category','Files',"int NOT NULL DEFAULT 0");
		safe_addcol('txp_category','links',"int NOT NULL DEFAULT 0");
	}
	
	// ---------------------------------------------------------------------
	// add `ParentName` column to all tree tables
	
	todo("add `ParentName` column");
	
	foreach ($tree_tables as $type => $table) {
	
		if (!column_exists($table,'ParentName')) {
			
			safe_addcol($table,'ParentName',"varchar(255) NOT NULL DEFAULT ''",'',1);
			safe_addcol($table,'ParentClass',"varchar(255) NOT NULL DEFAULT ''",'',1);
			safe_addcol($table,'ParentPosted',"varchar(255) NOT NULL DEFAULT ''",'',1);
		}
	}
	
	// ---------------------------------------------------------------------
	// update image size in txp_image table
	
	todo("update image size in txp_image table");
	
	$img_dir = $txp_prefs['path_to_site'].DS.$txp_prefs['img_dir'];
	
	$rows = safe_rows("ID,Name,ext","txp_image","Type = 'image' AND w = 0",1);
	
	foreach ($rows as $row) {
		
		extract($row);
		
		$rw = $rh = $tw = $th = 0;
		
		$image_path = get_image_id_path($ID);
		
		$file = $img_dir.DS.$image_path.DS.$Name.'_r'.$ext;
		
		if (is_file($file)) {
			
			list($rw,$rh) = getimagesize($file);
		}
		
		$file = $img_dir.DS.$image_path.DS.$Name.'_t'.$ext;
		
		if (is_file($file)) {
			
			list($tw,$th) = getimagesize($file);
		}
		
		safe_update("txp_image","w = $rw, h = $rh, thumb_w = $tw, thumb_h = $th","ID = $ID",1);
	}

	// ---------------------------------------------------------------------
	// add `Categories` column to all tree tables
	
	todo("add `Categories` column");
	
	foreach ($tree_tables as $type => $table) {
	
		if (!column_exists($table,'Categories')) {
			
			safe_addcol($table,'Categories',"varchar(255) NOT NULL DEFAULT ''",'Category2',1);
			
			if (column_exists($table,'Category1')) {
			
				safe_update($table,"Categories = Category1",
					"Categories = '' AND Category1 != '' AND Category2 = ''",1);
				
				safe_update($table,"Categories = Category2",
					"Categories = '' AND Category1 = '' AND Category2 != ''",1);
				
				safe_update($table,"Categories = CONCAT(Category1,',',Category2)",
					"Categories = '' AND Category1 != '' AND Category2 != ''",1);
			}
			
			if ($table == 'textpattern') {
				
				if (safe_count('txp_content_category')) {
				
					$categories = "SELECT GROUP_CONCAT(tcc.name) FROM ".$PFX."txp_content_category AS tcc WHERE t.ID = tcc.article_id AND tcc.type = 'article' ORDER BY tcc.position ASC";
					
					$rows = safe_rows_start("ID,($categories) AS Categories","textpattern AS t","1=1");	
		
					while ($row = nextRow($rows)) {
					
						extract($row);
						
						if (strlen($Categories)) {
							safe_update("textpattern","Categories = '$Categories'","ID = $ID",1);
						}
					}
				}
			}
			
			add_category_count($type,1); 
		}
		
		safe_drop($table,'Category1');
		safe_drop($table,'Category2');
	}
	
	// ---------------------------------------------------------------------
	// add `value_count` column to txp_group table
	
	if (!column_exists('txp_group','value_count')) {
		
		todo("add `value_count` column to txp_group table");
		
		safe_addcol('txp_group','value_count',"int NOT NULL DEFAULT 0",'used');
	}
	
	// ---------------------------------------------------------------------
	// add `by_table` column to txp_group table
	
	if (!column_exists('txp_group','by_table')) {
		
		todo("add `by_table` column to txp_group table");
		
		safe_addcol('txp_group','by_table',"varchar(32) NOT NULL DEFAULT 'textpattern'",'field_default');
		
		safe_modcol('txp_group','type',"varchar(8) NOT NULL DEFAULT 'field'");
		safe_update('txp_group',"`type` = 'field'","1=1");
	}
	
	// ---------------------------------------------------------------------
	// add `tbl` column to txp_content_value table
	
	if (!column_exists('txp_content_value','tbl')) {
	
		todo("add `tbl` column to txp_content_value table");
		
		safe_alter('txp_content_value',"CHANGE `type` `tbl` varchar(32) NOT NULL DEFAULT ''");
		safe_update('txp_content_value',"`tbl` = 'textpattern'","tbl = 'article'");
	}
	
?>
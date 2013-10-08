<?php
	
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.1
	// =========================================================================
	// modify columns
	
	safe_modcol('textpattern','ParentID',		'int(11) 	  NOT NULL AFTER `ID`');
	safe_modcol('textpattern','Alias',			'int(11) 	  NOT NULL AFTER `ParentID`');
	safe_modcol('textpattern','Trash',			'tinyint(4)   NOT NULL DEFAULT 0');
	safe_modcol('textpattern','Class',			'varchar(128) NOT NULL');
	safe_modcol('textpattern','Status',			'tinyint(4)   NOT NULL DEFAULT 4');
	safe_modcol('textpattern',"Path",			'varchar(192) NOT NULL');
	safe_modcol('textpattern',"Name",			'varchar(128) NOT NULL');
	safe_modcol('textpattern',"Title",			'varchar(128) NOT NULL');
	safe_modcol('textpattern',"Title_html",		'varchar(128) NOT NULL');
	safe_modcol('textpattern',"AuthorID",		'varchar(32)  NOT NULL');
	safe_modcol('textpattern',"override_form",	'varchar(64)  NOT NULL');
	safe_modcol('textpattern',"override_page",	'varchar(64)  NOT NULL');
	safe_modcol('textpattern',"OldStatus",		"varchar(4)   NOT NULL DEFAULT '0.0'");
	safe_modcol('txp_file',	  "Type",	  		"varchar(12)  NOT NULL");
	safe_modcol('txp_file',	  "ext",	  		"varchar(12)  NOT NULL");
	safe_modcol('txp_content_value',"id",		"int 		  NOT NULL AUTO_INCREMENT");
	safe_modcol('txp_content_value',"text_val", "varchar(256) NOT NULL");
	
	// ---------------------------------------------------------------------
	// drop columns

	safe_drop('textpattern','path');
	safe_drop('textpattern','path2');
 // safe_drop('textpattern','Mirror');
 // safe_drop('textpattern','Parent');
	safe_drop('textpattern','Category3');
	safe_drop('textpattern','Category4');
	safe_drop('textpattern','SecretPath');
	safe_drop('textpattern','RevPath');
	safe_drop('textpattern','Parent_Title');
	safe_drop('textpattern','Parent_Name');
	safe_drop('textpattern','Parent_Class');
	safe_drop('textpattern','Parent_Category1');
	safe_drop('textpattern','Parent_Position');
	safe_drop('textpattern','InheritStatus');
	safe_drop('textpattern','Site');
	safe_drop('textpattern','IDPath');
	
	safe_drop('txp_page',	 'user_html_cache');
	safe_drop('txp_category','position');
	safe_drop('txp_category','page');
	safe_drop('txp_section', 'position');
	safe_drop('txp_group',	 'multiple');
	safe_drop('txp_group',	 'article_name');
	safe_drop('txp_article_category','type');
	
	safe_drop($tree_tables,'Site');
	
	// ---------------------------------------------------------------------
	// drop tables
	
 // safe_drop('txp_article_image');
 // safe_drop('txp_article_file');
 // safe_drop('txp_article_link');
	safe_drop('txp_article_path_cache');
	safe_drop('txp_log_time');
	safe_drop('txp_type');
	safe_drop('txp_content_type');
	safe_drop('textpattern_name');
	safe_drop('textpattern_id');
	safe_drop('textpattern_path');
	safe_drop('textpattern_category');
	safe_drop('textpattern_field');
	safe_drop('txp_image_edit');
	safe_drop('txp_article_path');
	safe_drop('txp_article_name');
	safe_drop('txp_path_name');
	safe_drop('txp_article_id');
	safe_drop('txp_path_class');
	safe_drop('txp_path_id');
	safe_drop('txp_content');
	safe_drop('txp_article_category');
	safe_drop('txp_class');

	// =========================================================================
	// ALTER TABLES
	// ---------------------------------------------------------------------
	// add `session` column to txp_users table
	
	if (!column_exists('txp_users','session')) {
	
		todo("add column `session` to txp_users table");
		
		safe_addcol('txp_users','session','text NULL');
	}
	
	// ---------------------------------------------------------------------
	// add `update` column to txp_users table
	
	if (!column_exists('txp_users','updated')) {
	
		todo("add column `updated` to txp_users table");
		
		safe_addcol('txp_users','updated','int NOT NULL DEFAULT 0');
	}
	
	// ---------------------------------------------------------------------
	// add column NamePath to presentation tables

	$presentation = 'txp_page,txp_form,txp_css';
	
	if (!column_exists($presentation,'NamePath')) {
		
		todo("add column `NamePath` to presentation tables");
		
		safe_addcol($presentation,'NamePath','varchar(256)','Name');
	}

	// =========================================================================
	// CREATE TABLES
	
	safe_drop('txp_cache');
	
 	/* safe_drop("txp_path",'','',0);
	safe_drop('txp_custom','','',0);
	safe_drop('txp_group','','',0);
	safe_drop('txp_content_value','','',0);
	safe_drop('txp_page,txp_form,txp_css','NamePath','',0);
	safe_drop('txp_content_category','','',0);
	safe_drop("txp_sticky",'','',0);
	safe_drop("txp_tag",'','',0);
	safe_drop("txp_tag_attr",'','',0);
	safe_drop("txp_site",'','',0);
	safe_drop("txp_window",'','',0); */
	
	// ---------------------------------------------------------------------
	// create table txp_path 
	
	if (!table_exists('txp_path')) {
	
		todo("create table txp_path");
		
		safe_create("txp_path",array(
			"`ID`		int			NOT NULL DEFAULT 0",
			"`Type`		varchar(12)	NOT NULL DEFAULT ''",
			"`Reverse`	tinyint		NOT NULL DEFAULT 0",
			"`Level`	tinyint		NOT NULL DEFAULT 0",
			"`P2`		int			NULL DEFAULT NULL",
			"`P3`		int			NULL DEFAULT NULL",
			"`P4`		int			NULL DEFAULT NULL",
			"INDEX		path (`Type`(1),Reverse,P2,P3,P4)"));
	}
	
	// ---------------------------------------------------------------------
	// create txp_cache table
	
	if (!table_exists('txp_cache')) {
		
		todo("create table txp_cache");
		
		safe_create("txp_cache",array(
			"`id` 		int 			NOT NULL AUTO_INCREMENT",
			"`name`  	varchar(255) 	NOT NULL DEFAULT ''",
			"`page` 	varchar(255) 	NOT NULL DEFAULT ''",
			"`idx` 		varchar(255)    NOT NULL DEFAULT ''",
			"`hash` 	int				NOT NULL DEFAULT 0",
			"`html` 	text 			NOT NULL DEFAULT ''",
			"`file` 	tinyint 		NOT NULL DEFAULT 0",
			"`ref` 		varchar(255) 	NOT NULL DEFAULT ''",
			"`level` 	tinyint 		NOT NULL DEFAULT 0",
			"`num` 		int 			NOT NULL DEFAULT 0",
			"`size` 	int 			NOT NULL DEFAULT 0",
			"`status` 	varchar(24) 	NOT NULL DEFAULT ''",
			"PRIMARY KEY (id)"));
		
		safe_index('txp_cache',"idx","idx(16)");
		safe_index('txp_cache',"hash","hash");
		safe_index('txp_cache',"file","file");
		safe_index('txp_cache',"name","name");
	}
	
	// ---------------------------------------------------------------------
	// create table txp_custom

	if (!table_exists('txp_custom')) {

		todo("create table txp_custom");
		
		safe_create("txp_custom",array(
		  "`id`			int		    NOT NULL AUTO_INCREMENT",
		  "`name`		varchar(32)	NOT NULL DEFAULT ''",
		  "`path`		varchar(128) NOT NULL DEFAULT ''",
		  "`title`		varchar(32)	NOT NULL DEFAULT ''",
		  "`type`		varchar(16)	NOT NULL DEFAULT ''",
		  "`input`		varchar(16)	NOT NULL DEFAULT 'textfield'",
		  "`options`	text		NOT NULL DEFAULT ''",
		  "`label`		tinyint		NOT NULL DEFAULT 0",
		  "`default`	varchar(32)	NOT NULL DEFAULT ''",
		  "`parent`		varchar(64)	NOT NULL DEFAULT ''",
		  "`help`		text		NOT NULL DEFAULT ''",
		  "`status`		tinyint  	NOT NULL DEFAULT 0",
		  "`lft`		smallint 	NOT NULL DEFAULT 0",
		  "`rgt`		smallint 	NOT NULL DEFAULT 0",
		  "PRIMARY KEY (id)",
		  "INDEX name (name(16))"));
	
		safe_insert('txp_custom',
			"name = 'root',title = 'Root',parent = '',type = '',input = ''",1);
		
		rebuild_tree('root',1,'','txp_custom');
	}
	
	// ---------------------------------------------------------------------
	// create table txp_group
	
	if (!table_exists('txp_group')) {

		todo("create table txp_group");
		
		safe_create("txp_group",array(
			"`id`				int		     NOT NULL AUTO_INCREMENT",
			"`type`				varchar(8)	 NOT NULL DEFAULT 'field'",
			"`group_id`			int		     NOT NULL DEFAULT 0",
			"`instance_id`		int		 	 NOT NULL DEFAULT 0",
			"`field_id`			int 		 NOT NULL DEFAULT 0",
			"`field_name`		varchar(32)  NOT NULL DEFAULT ''",
			"`field_parent`		int 		 NOT NULL DEFAULT 0",
			"`field_path` 		varchar(128) NOT NULL DEFAULT ''",
			"`field_input`		varchar(16)	 NOT NULL DEFAULT ''",
			"`field_options`	text		 NOT NULL DEFAULT ''",
			"`field_default`	varchar(256) NOT NULL DEFAULT ''",
			"`by_table`			varchar(32)  NOT NULL DEFAULT 'textpattern'",
			"`by_id`			int   		 NOT NULL DEFAULT 0",
			"`by_name`			varchar(128) NOT NULL DEFAULT ''",
			"`by_path`			varchar(255) NOT NULL DEFAULT ''",
			"`by_parent`		int   		 NOT NULL DEFAULT 0",
			"`by_class`			varchar(128) NOT NULL DEFAULT ''",
			"`by_section`		varchar(128) NOT NULL DEFAULT ''",
			"`by_category`		varchar(128) NOT NULL DEFAULT ''",
			"`by_sticky`		tinyint   	 NOT NULL DEFAULT 0",
			"`by_level`			tinyint   	 NOT NULL DEFAULT 0",
			"`status`			varchar(10)  NOT NULL DEFAULT ''",
			"`used`				int  		 NOT NULL DEFAULT 0",
			"`value_count` 		int 		 NOT NULL DEFAULT 0",
			"`last_mod`			datetime  	 NOT NULL DEFAULT '0000-00-00 00:00:00'",
			"PRIMARY KEY (id)",
			"INDEX (group_id,field_id,instance_id)",
			"INDEX field_id (field_id,instance_id)",
			"INDEX field_name (field_name(8))",
			"INDEX field_path (field_path(12))"));
	}
	
	// ---------------------------------------------------------------------
	// create table txp_content_value

	if (!table_exists('txp_content_value')) {
	
		todo("create table txp_content_value");
		
		safe_create("txp_content_value",array(
			"`id`			int 		NOT NULL AUTO_INCREMENT",
			"`type`		    varchar(8) 	NOT NULL DEFAULT 'article'",
			"`article_id`	int			NOT NULL DEFAULT 0",
			"`group_id`		int			NOT NULL DEFAULT 0",
			"`instance_id`	int			NOT NULL DEFAULT 0",
			"`field_id`		int 		NOT NULL DEFAULT 0",
			"`field_name`	varchar(64) NOT NULL DEFAULT ''",
			"`field_parent`	int 		NOT NULL DEFAULT 0",
			"`num_val`		decimal(10,2) NULL 	   DEFAULT NULL",
			"`text_val`		varchar(256)  NOT NULL DEFAULT ''",
			"`status`		tinyint 	  NOT NULL DEFAULT 0",
			"PRIMARY KEY (id)",
			"INDEX article_id (article_id)",
			"INDEX field_id (field_id,instance_id)",
			"INDEX group_id (group_id,field_id,instance_id)",
			"INDEX name_num (field_name(8),num_val)",
			"INDEX name_text (field_name(8),text_val(12))"));
	}

	// ---------------------------------------------------------------------
	// create table txp_sticky 
	
	if (!table_exists('txp_sticky')) {
	
		todo("create table txp_sticky");
		
		safe_create("txp_sticky",array(
		  "`id`   int			NOT NULL DEFAULT 0",
		  "`type` varchar(12)	NOT NULL DEFAULT 'article'",
		  "PRIMARY KEY (id,type)"));
		
		$values = "SELECT ID,'article' FROM ".safe_pfx('textpattern')." WHERE Status = 5";
		$query  = "INSERT INTO ".safe_pfx('txp_sticky')." (id,`type`) $values";
		safe_query($query,1);
	}

	// ---------------------------------------------------------------------
	// create table txp_tag 
	
	if (!table_exists('txp_tag')) {
		
		todo("create table txp_tag");
		
		safe_create("txp_tag",array(
		  "`group` 			varchar(64)	NOT NULL DEFAULT ''",
		  "`tag` 			varchar(64)	NOT NULL DEFAULT ''",
		  "`body`			tinyint 	NOT NULL DEFAULT 0",
		  "`code`			text 		NOT NULL DEFAULT ''",
		  "`description` 	text 		NOT NULL DEFAULT ''",
		  "`pos`			int 		NOT NULL DEFAULT 1",
		  "`lastmod`		datetime 	NOT NULL DEFAULT '0000-00-00 00:00:00'",
		  "PRIMARY KEY (tag)"));
	}

	// ---------------------------------------------------------------------
	// create table txp_tag_attr 
	
	if (!table_exists('txp_tag_attr')) {
	
		todo("create table txp_tag_attr");
		
		safe_create("txp_tag_attr",array(
		  "`tag` 		varchar(64)	NOT NULL DEFAULT ''",
		  "`attribute` 	varchar(64)	NOT NULL DEFAULT ''",
		  "`default`	varchar(64) NOT NULL DEFAULT ''",
		  "`comment`	text 		NOT NULL DEFAULT ''",
		  "`pos`		int 		NOT NULL DEFAULT 1",
		  "PRIMARY KEY (tag,attribute)"));
		
		// update_all_tags(1);
	}

	// ---------------------------------------------------------------------
	// create table txp_site 
	
	if (IS_MAIN_SITE and !$PFX and !table_exists('txp_site')) {
		
		todo("create table txp_site");
		
		safe_create("txp_site",array(
			"`ID`		int 			NOT NULL AUTO_INCREMENT",
			"`Title` 	varchar(128)	NOT NULL DEFAULT ''",
			"`Name`		varchar(64)		NOT NULL DEFAULT ''",
			"`DB`		varchar(64)		NOT NULL DEFAULT ''",
			"`DB_User` 	varchar(128) 	NOT NULL DEFAULT ''", 
			"`DB_Pass` 	varchar(128) 	NOT NULL DEFAULT ''", 
			"`DB_Host` 	varchar(128) 	NOT NULL DEFAULT ''", 
			"`DB_CharSet` varchar(32) 	NOT NULL DEFAULT 'utf8'",
			"`Prefix` 	varchar(32)		NOT NULL DEFAULT ''",
			"`Language` varchar(5)		NOT NULL DEFAULT 'en-gb'",
			"`Admin` 	varchar(128)	NOT NULL DEFAULT ''",
			"`Domain`	varchar(128)	NOT NULL DEFAULT ''",
			"`Hosting`	varchar(128)	NOT NULL DEFAULT ''",
			"`URL`		varchar(128)	NOT NULL DEFAULT ''",
			"`FTP`		varchar(255)	NOT NULL DEFAULT ''",
			"`SSH`		varchar(255)	NOT NULL DEFAULT ''",
			"`SiteDir`	varchar(255)	NOT NULL DEFAULT ''",
			"`TxpDir`	varchar(255)	NOT NULL DEFAULT ''",
			"`Posted`	datetime 		NOT NULL DEFAULT '0000-00-00 00:00:00'",
			"`Articles`	int 			NOT NULL DEFAULT 0",
			"`Images`	int 			NOT NULL DEFAULT 0",
			"`Files`	int 			NOT NULL DEFAULT 0",
			"`Copy`		int				NOT NULL DEFAULT 0",
			"`Version`	varchar(16) 	NOT NULL DEFAULT ''",
			"PRIMARY KEY (ID)"));
	}
	
	// ---------------------------------------------------------------------
	// create table txp_update
	
	if (IS_MAIN_SITE and !$PFX and !table_exists('txp_update')) {
		
		todo("create table txp_update");
		
		safe_create("txp_update",array(
		  "`File`		varchar(255) NOT NULL DEFAULT ''",
		  "`Hash`    	int          NOT NULL DEFAULT 0",
		  "`LastMod` 	datetime     NOT NULL DEFAULT '0000-00-00 00:00:00'",
		  "`Removed`	tinyint      NOT NULL DEFAULT 0"));
			  
		update_update_table();  
	}
	
	// ---------------------------------------------------------------------
	// create table txp_window 
	
	if (!table_exists('txp_window')) {
		
		todo("create table txp_window");
		
		safe_create("txp_window",array(
			"`user`		varchar(64)	NOT NULL DEFAULT ''",
			"`window`	int			NOT NULL DEFAULT 0",
			"`settings`	text		NOT NULL DEFAULT ''",
			"PRIMARY KEY (user,window)"));
		
		safe_update("txp_users","session = '',updated = 1","1=1",1);
	}

	// =========================================================================
	// CONVERT TABLES
	// ---------------------------------------------------------------------
	// textpattern
	
	if (!column_exists('textpattern',"ParentID")) {
		
		todo("convert table textpattern");
		
		// txp1 to txp2 column name changes
		
		$map['Parent'] = 'ParentID';	
		$map['Mirror'] = 'Alias';
		
		convert_table('textpattern',$map,$txp_prefs['sitename']);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add indexes
		
		safe_index('textpattern',"Class","Class(8)");
		safe_index('textpattern',"Name","Name(8)");
		safe_index('textpattern',"status_posted_expires_trash","Status,Posted,Expires,Trash");
		safe_index('textpattern',"status_position_expires_trash","Status,Position,Expires,Trash");
		safe_index('textpattern',"ParentID_Posted","ParentID,Posted");
		safe_index('textpattern',"ParentID_Position","ParentID,Position");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add new columns
		
		safe_addcol('textpattern','ImageData','varchar(512)','ImageID');
		safe_addcol('textpattern','override_page','varchar(255)','override_form');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add url_title to Name column
		
		safe_update('textpattern',"Name = url_title","url_title != ''");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// txp1 to txp2:  ImageID, ImageData and FileID
		
		if (table_exists('txp_article_image')) {
		
			safe_update('textpattern AS t',"ImageID = (SELECT i.image_id FROM txp_article_image AS i WHERE t.ID = i.article_id)","1",$debug);
			safe_update('textpattern AS t',"ImageID = (SELECT i.image_id FROM txp_article_image AS i WHERE t.Alias = i.article_id)","Alias != 0",$debug);
			safe_update('textpattern AS t',"ImageData = (SELECT CONCAT_WS(':',display_list,imgtype_list,align_list,display_single,imgtype_single,align_single) FROM txp_article_image AS i WHERE t.ID = i.article_id)","1",$debug);
			safe_update('textpattern AS t',"ImageData = (SELECT CONCAT_WS(':',display_list,imgtype_list,align_list,display_single,imgtype_single,align_single) FROM txp_article_image AS i WHERE t.Alias = i.article_id)","Alias != 0",$debug);
		}
		
		if (table_exists('txp_article_file')) {
		
			safe_update('textpattern AS t',"FileID = (SELECT i.file_id FROM txp_article_file AS i WHERE t.ID = i.article_id)","1",$debug);
			safe_update('textpattern AS t',"FileID = (SELECT i.file_id FROM txp_article_file AS i WHERE t.Alias = i.article_id)","Alias != 0",$debug);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// txp0 to txp2: ImageID
		
		if (column_exists('textpattern','Image')) {
		
			$rows = safe_rows("ID,Image","textpattern","1=1",1);
			
			foreach($rows as $row) { 
			
				extract($row);
				
				if (is_numeric($Image)) {
				
					safe_update('textpattern',"ImageID = '$Image', Image = ''","ID = $ID",1);
					
				} elseif ($Image) {
				
					if ($ImageID = safe_field("id","txp_image","concat(name,ext) = '$Image'")) {
						safe_update('textpattern',"ImageID = '$ImageID',Image = ''","ID = $ID",1);
					}
				}
			}
			
			if (safe_count('textpattern',"Image != ''") == 0) {
				safe_alter('textpattern',"drop column Image");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// make section folders
		
		$sections  = safe_rows('name,title','txp_section',"name != 'default'");
		$root_id   = fetch("ID","textpattern","ParentID",0);
		$root_name = fetch("Name","textpattern","ID",$root_id);
		
		todo("&#8226; make section folders");
		
		$folders = array();
		
		foreach($sections as $section) {
			
			extract($section);
			
			if (safe_count('textpattern',"Section = '$name'")) {
				
				$parent_id = safe_field("ID","textpattern",
					"ParentID = $root_id AND Section = '$name' AND Name = Section");
				
				if (!$parent_id) {
				
					$authorid = safe_field('AuthorID','textpattern',
						"Section = '$name' ORDER BY ID ASC");
					
					$set = array(
						'Name'       => $name,
						'Title'      => $title,
						'AuthorID'   => $authorid,
						'LastModID'	 => $authorid,
						'ParentName' => $root_name,
						'Status'     => 4
					);
				
					$insert = content_create($root_id,$set,'textpattern');
					
					if (is_array($insert) and isset($insert[1])) {
					
						$parent_id = $insert[1];
						$folders[$parent_id] = 0;
					}
				}
					
				if ($parent_id) {
					
					$where = "ParentID = $root_id AND ID != $parent_id AND Section = '$name'";
					
					$folders[$parent_id] = safe_count('textpattern',$where);
					
					safe_update('textpattern',
						"ParentID   = $parent_id,
						 ParentName = '$name'",
						$where);
						
					renumerate($parent_id); 
				}
				
				renumerate($root_id); 
			}
		}
		
		if ($folders) {
				
			foreach($folders as $id => $count) {
				
				$title = fetch('Title','textpattern','ID',$id);
				
				$folders[$id] = "<li>$title ($count)</li>";
			}
			
			pre("<ul>".implode(n,$folders)."<ul>");
		
		} else {
			
			pre('None');
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// fill sub-section folders
		
		todo("&#8226; fill sub-section folders");
		
		$folders = safe_column("ID,Name,Section,Title","textpattern",
			"Category1 = Name AND Category2 = '' AND Title != '' AND Trash = 0");
			
		foreach($folders as $ParentID => $item) {
			
			extract($item);
			
			$children = safe_column(
				"ID","textpattern",
				"Section = '$Section' 
				 AND Category1 = '$Name' 
				 AND Category1 != Name 
				 AND Trash = 0");
			
			foreach($children as $id) {	
				
				safe_update('textpattern',
					"ParentID = $ParentID,
					 ParentName = '$name'",
					"ID = $id");
			}	
			
			$folders[$ParentID] = "<li>".$Title.' ('.count($children).')</li>';
			
			renumerate($ParentID); 
		}
		
		renumerate($root_id);
		
		if ($folders) {
			
			pre("<ul>".implode(n,$folders)."<ul>");
		
		} else {
			
			pre('None');
		}
	}
	
	// ---------------------------------------------------------------------
	// txp_image
	
	if (!column_exists('txp_image',"ParentID")) {
		
		todo("convert table txp_image");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// remove extension from filename
		
		safe_update('txp_image',"name = TRIM(TRAILING ext FROM name)","1=1",1); 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$map['caption'] = 'Body';
		
		convert_table('txp_image',$map,'Images');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add new columns
		
		safe_addcol('txp_image',"copyright","varchar(100)","Keywords");
		safe_addcol('txp_image',"transparency","tinyint","thumb_h");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// delete images and move into images/design folder 
		
		safe_delete('txp_image',"Name IN ('divider','txp_slug105x45') AND ext = '.gif'");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add example image
		
		if (getCount("txp_image") == 2) {
		
			todo("add example image");
			
			$root_id = fetch("ID","txp_image","ParentID",0);
			
			$image_id = safe_insert("txp_image",
				"Name 		= 'big-sky',
				 Title		= 'Big Sky', 
				 LastMod	= NOW(),
				 Posted		= NOW(),
				 ParentID	= $root_id,
				 ImageID	= 1,
				 AuthorID	= 'textpattern',
				 Level		= 2,
				 Status		= 4,
				 Position   = 1,
				 ext		= '.jpg',
				`Type`		= 'image',
				`w`			= 400,
				`h`			= 270,
				thumb_w		= 100,
				thumb_h		= 100,
				thumbnail	= 2 
				",1);
			
			// add image to first post
			
			safe_update("textpattern","ImageID = '$image_id'","Name = 'welcome-to-your-site'",1);
		}
		
		if (table_exists('txp_article_image')) {
			
			// import images to textpattern table
				
			safe_drop('txp_article_image');
		}
	}

	// ---------------------------------------------------------------------
	// txp_file
	
	if (!column_exists('txp_file',"ParentID")) {
		
		todo("convert table txp_file");
		
		$map['description']	= 'Body';
		$map['filename']	= 'Name';
		
		convert_table('txp_file',$map,'Files');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add column `ext`
		
		safe_addcol('txp_file','ext','varchar(12)','Name');
		
		$rows = safe_rows("ID,Name","txp_file","ParentID != 0");
		
		foreach ($rows as $row) {
			
			extract($row);
			
			$Type = get_file_type($Name);
			
			if ($Type) {
				
				$info = pathinfo($Name);
				$ext  = (isset($info['extension'])) ? '.'.$info['extension'] : '';
				$Name = basename($Name,$ext);
				$ext  = strtolower($ext);
				
				safe_update('txp_file',"Name = '$Name',Type = '$Type',ext = '$ext'","id = $ID",1); 
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$sz = safe_field("MAX(size)","txp_file","1=1");
		$dl = safe_field("MAX(downloads)","txp_file","1=1");
			
		safe_update("txp_file","size = '$sz', downloads = '$dl'","ParentID = 0",1);
	}

	// ---------------------------------------------------------------------
	// txp_link
	
	if (!column_exists('txp_link',"ParentID")) {
		
		todo("convert table txp_link");
		
		$map['linkname']    = 'Title';
		$map['description'] = 'Body';
		
		convert_table('txp_link',$map,'Links');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// fill `Name` column
		
		$titles = safe_column('ID,Title','txp_link',"Name != ''");
	
		foreach ($titles as $id => $title) {
			
			$name = make_name($title);
			
			safe_update('txp_link',"Name = '$name'","ID = $id");
		}
	}
	
	// ---------------------------------------------------------------------
	// txp_discuss
	
	if (!column_exists('txp_discuss',"ParentID,Alias")) {
		
		todo("convert table txp_discuss");
		
		safe_alter('txp_discuss',"CHANGE `parentid` `article_id` int NOT NULL DEFAULT 0");
		
		$map['discussid'] = 'ID';
		$map['message']   = 'Body';
		$map['visible']   = 'Status';
		
		convert_table('txp_discuss',$map,'Comments');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// change `author` column to `Author`
		
		if (column_exists('txp_discuss','author')) {
			safe_alter('txp_discuss',"CHANGE `author` `Author` varchar(255) NOT NULL DEFAULT '' AFTER `Name`");
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// fill `Body_html` column
	
		$textile = new Textile();
		
		$rows = safe_rows('ID,Body','txp_discuss');
		
		foreach($rows as $row) { 
				
			extract($row);
			
			$Body = preg_replace("/(\<|\&lt;)br\s?\/?(\>|\&gt;)/","\r",doStrip($Body));
			$Body = preg_replace("/(\<|\&lt;)p(\>|\&gt;)/","",$Body);
			$Body = preg_replace("/(\<|\&lt;)\/p(\>|\&gt;)/","\r\n\n",$Body);
			
			$im = (!empty($txp_prefs['comments_disallow_images'])) ? 1 : '';
			
			$Body_html = doSlash(trim(nl2br($textile->TextileThis(strip_tags(deEntBrackets(
				$Body
			)),1,'',$im,'',(@$txpac['comment_nofollow'] ? 'nofollow' : '')))));
			
			$Body = doSlash($Body);
			
			safe_update('txp_discuss',"Body = '$Body',Body_html = '$Body_html'","ID = $ID");
		}
	}

	// ---------------------------------------------------------------------
	// txp_category
	
	if (!column_exists('txp_category',"ParentID")) {
		
		todo("convert table txp_category");
		
		convert_table('txp_category',$map,'Categories');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// convert `parent` column to `ParentID`
		
		safe_update("txp_category AS t JOIN txp_category AS p ON t.parent = p.Name AND t.Type = p.Type",
			"t.ParentID = p.ID","t.Parent NOT IN ('root','')",1);
		
		safe_delete("txp_category","Name = 'root' AND Title = 'root'",1);
		safe_update("txp_category","Posted = NOW(), LastMod = NOW()","ParentID != 0",1);
		safe_drop("txp_category","parent",'',1);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add columns
		
		safe_addcol('txp_category','frontpage',"tinyint NOT NULL DEFAULT 0","Type");
		
		safe_addcol('txp_category','Articles',"int NOT NULL DEFAULT 0","Categories");
		safe_addcol('txp_category','Images',"int NOT NULL DEFAULT 0","Articles");
		safe_addcol('txp_category','Files',"int NOT NULL DEFAULT 0","Images");
		safe_addcol('txp_category','Links',"int NOT NULL DEFAULT 0","Files");
		safe_addcol('txp_category','Comments',"int NOT NULL DEFAULT 0","Links");
		safe_addcol('txp_category','Plural',"varchar(128) NOT NULL DEFAULT ''","Title");
	}
		
	// ---------------------------------------------------------------------
	// txp_page
	
	if (!column_exists('txp_page',"ParentID")) {
		
		todo("convert table txp_page");
		
		$map['user_html'] = 'Body';
		
		convert_table('txp_page',$map,'Pages');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add `Body_xsl` column 
		
		safe_addcol('txp_page','Body_xsl',"text NOT NULL DEFAULT ''",'Body_html');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add `Pattern` column 
		
		safe_addcol('txp_page','Pattern',"varchar(255) NOT NULL DEFAULT ''");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add `NamePath` column 
		
		safe_addcol('txp_page','NamePath',"varchar(255) NOT NULL DEFAULT ''",'Name');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// fill `Title` column
		
		$names = safe_column('ID,Name','txp_page',"Title = ''");
	
		foreach ($names as $id => $name) {
			
			$title = make_title($name);
			
			safe_update('txp_page',"Title = '$title'","ID = $id");
		}
		
		safe_update('txp_page',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_page',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_page',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_page',"Type = 'xsl'","Type = '' AND Body_xsl NOT IN ('0','')",1);
		safe_update('txp_page',"Type = 'txp'","Type = '' AND Body_xsl = 0",1);
		safe_update('txp_page',"AuthorID = '$txp_user'","AuthorID = ''",1);
	}
	
	// ---------------------------------------------------------------------
	// txp_form
	
	if (!column_exists('txp_form',"ParentID")) {
		
		todo("convert table txp_form");
		
		$map['Form'] = 'Body';
		
		convert_table('txp_form',$map,'Forms');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add `Body_xsl` column 
		
		safe_addcol('txp_form','Body_xsl',"text NOT NULL DEFAULT ''",'Body_html');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add `NamePath` column 
		
		safe_addcol('txp_form','NamePath',"varchar(255) NOT NULL DEFAULT ''",'Name');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// fill `Title` column
		
		$names = safe_column('ID,Name','txp_form',"Title = ''");
	
		foreach ($names as $id => $name) {
			
			$title = make_title($name);
			
			safe_update('txp_form',"Title = '$title'","ID = $id");
		}
		
		safe_update('txp_form',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_form',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_form',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_form',"AuthorID = '$txp_user'","AuthorID = ''",1);
	}

	// ---------------------------------------------------------------------
	// txp_css
	
	if (!column_exists('txp_css',"ParentID")) {
		
		todo("convert table txp_css");
		
		$map['css'] = 'Body';
		
		convert_table('txp_css',$map,'Style');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add `NamePath` column 
		
		safe_addcol('txp_css','NamePath',"varchar(255) NOT NULL DEFAULT ''",'Name');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// fill `Title` column
		
		$names = safe_column('ID,Name','txp_css',"Title = ''");
	
		foreach ($names as $id => $name) {
			
			$title = make_title($name);
			
			safe_update('txp_css',"Title = '$title'","ID = $id");
		}
		
		safe_update('txp_css',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_css',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_css',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		safe_update('txp_css',"Type = 'css'","Type = '' AND ParentID != 0 AND Name != 'TRASH'",1);
		safe_update('txp_css',"AuthorID = '$txp_user'","AuthorID = ''",1);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// fill `Body` column
		
		$body = safe_column("ID,Body","txp_css","Body != ''");
			
		foreach($body as $id => $value) {
			
			$value = doSlash(base64_decode($value));
			safe_update("txp_css","Body = '$value'","ID = $id");
		}
	}

	// ---------------------------------------------------------------------
	// txp_custom
	
	if (!column_exists('txp_custom',"ParentID")) {
		
		todo("convert table txp_custom");
		
		$map['options'] = 'Body';
		$map['help'] 	= 'Excerpt';
		
		convert_table('txp_custom',$map,'Custom Fields');
		
		safe_update('txp_custom',"Posted = NOW() + ID, LastMod = NOW() + ID","Posted = 0",1);
		
		$root_id = fetch("ID",'txp_custom',"ParentID",0,1);
		safe_update('txp_custom',"Title = 'Custom Fields', Name = 'custom-fields', `Type` = 'folder',ParentID = 0,Level = 1, Status = 4","ID = 1",1);
		safe_update('txp_custom',"ParentID = 1","ParentID = '$root_id'",1);
		safe_delete('txp_custom',"ID = '$root_id'",1);
		
		safe_update("txp_group","field_parent = 0","field_parent = 1",1);
		safe_update("txp_content_value","field_parent = 0","field_parent = 1",1);
	}

	// ---------------------------------------------------------------------
	// txp_log
	
	if (!column_exists('txp_log',"ParentID")) {
		
		todo("convert table txp_log");
		
		convert_table('txp_log',$map,'Visitor Log');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add columns
		
		safe_addcol('txp_log','agent',"varchar(255) NOT NULL DEFAULT ''","ip");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add default value for `Status` column
		
		safe_modcol('txp_log','Status','smallint NOT NULL DEFAULT 200');
	}
	
	// =========================================================================
	// create table txp_content_category

	if (!table_exists('txp_content_category')) {
		
		todo("create table txp_content_category");
		
		safe_create("txp_content_category",array(
			"`article_id`	int			NOT NULL DEFAULT 0",
			"`name`			varchar(64)	NOT NULL DEFAULT ''",
			"`position`		tinyint		NOT NULL DEFAULT 1",
			"`type`			varchar(12)	NOT NULL DEFAULT ''",
			"INDEX name (name(8))",
			"INDEX id_type (article_id,type(2))"));
		
		add_category_values(1);
	}	
	
	// =========================================================================
	// ADD CUSTOM FIELDS
	
	if (table_exists('txp_content_value')) {
	
		if (!safe_count('txp_content_value')) {
		
			if (column_exists('textpattern','Category1')) {
			
				add_custom_fields();
				add_custom_field_values();
			}
		}
	}
	
	// =========================================================================
	// UPDATE PREFERENCES
	
	$pref_default = array(
		'prefs_id' 	=> 1,
		'name'		=> '',
		'val' 		=> '',
		'type' 		=> 2,
		'event' 	=> 'publish',
		'html' 		=> 'text_input',
		'position' 	=> 0
	);
	
	if (column_exists('txp_prefs','user_name')) {
		
		$pref_default['user_name'] = '';
	}
	
	// ---------------------------------------------------------------------
	// delete image last edits

	todo("update preferences");

	if (pref_exists('last_r_size')) {
		
		unset_pref('last_r_size');
		unset_pref('last_r_size');
		unset_pref('last_r_sizeby');
		unset_pref('last_t_size');
		unset_pref('last_t_sizeby');
		unset_pref('last_t_crop');
	}

	if (pref_exists('ap_imagesys')) {
	
		safe_delete('txp_prefs',"name like 'ap\_%'");
	}
	
	// ---------------------------------------------------------------------
	// add last_custom_field_import
		
	if (!pref_exists('last_custom_field_import')) {
		
		$pref = $pref_default;
		$pref['name'] = 'last_custom_field_import';
		$pref['val']  = 0;
	
		safe_insert('txp_prefs',$pref,1);
	}
		
	// ---------------------------------------------------------------------
	// add page_caching
	
	if (!pref_exists('page_caching')) {
		
		$pref = $pref_default;
		$pref['name'] = 'page_caching';
		$pref['val']  = 0;
		$pref['type'] = 1;
		$pref['html'] = 'yesnoradio';
		$pref['position'] = 220;
	
		safe_insert('txp_prefs',$pref,1);
	}
		
	// ---------------------------------------------------------------------
	// add mod_counter
	
	if (!pref_exists('mod_counter')) {
		
		$pref = $pref_default;
		$pref['name'] = 'mod_counter';
		$pref['val']  = 1;
		
		safe_insert('txp_prefs',$pref,1);
	}
			
	// ---------------------------------------------------------------------
	// add regular_image_size
	
	if (!pref_exists('regular_image_size')) {
		
		$pref = $pref_default;
		$pref['name']  = 'regular_image_size';
		$pref['val']   = 500;
		$pref['type']  = 1;
		$pref['event'] = 'image';
		$pref['position'] = 1;
		
		safe_insert('txp_prefs',$pref,1);
	}
	
	// ---------------------------------------------------------------------
	// add thumbnail_image_size
	
	if (!pref_exists('thumbnail_image_size')) {
		
		$pref = $pref_default;
		$pref['name']  = 'thumbnail_image_size';
		$pref['val']   = 100;
		$pref['type']  = 1;
		$pref['event'] = 'image';
		$pref['position'] = 3;
		
		safe_insert('txp_prefs',$pref,1);
	}
		
	// ---------------------------------------------------------------------
	// add regular_image_size_for
	
	if (!pref_exists('regular_image_size_for')) {
		
		$pref = $pref_default;
		$pref['name']  = 'regular_image_size_for';
		$pref['val']   = 'longer';
		$pref['type']  = 1;
		$pref['event'] = 'image';
		$pref['html']  = 'image_size_for';
		$pref['position'] = 2;
		
		safe_insert('txp_prefs',$pref,1);
		
		safe_update('txp_prefs',"position = 3","name = 'thumbnail_image_size'",1);
	}
	
	// ---------------------------------------------------------------------
	// set use_textile
	
	if (!pref_exists('use_textile',1)) {
		
		set_pref('use_textile',1);
	}
	
	// ---------------------------------------------------------------------
	// remove category_max

	if (pref_exists('category_max')) {
	
		unset_pref('category_max');
	}
	
	// ---------------------------------------------------------------------
	// set img_dir

	if (pref_exists('img_dir','images')) {
		
		set_pref('img_dir','images/content');
	}
	
	// ---------------------------------------------------------------------
	// set default_event
	
	if (pref_exists('default_event','article')) {
		
		set_pref('default_event','list');
	}
	
	// ---------------------------------------------------------------------
	// add file_dir
	
	if (!pref_exists('file_dir')) {
		
		$pref = $pref_default;
		$pref['name']  = 'file_dir';
		$pref['val']   = 'files';
		$pref['type']  = 1;
		$pref['event'] = 'admin';
		$pref['position'] = 40;
		
		safe_insert('txp_prefs',$pref,1);
	}
	
	// ---------------------------------------------------------------------
	// set site url
	
	$domain  = $_SERVER["HTTP_HOST"];
	$req     = $_SERVER["REQUEST_URI"];
	$dir 	 = reset(explode('/',trim($req,'/')));
	$siteurl = (str_begins_with($dir,'~')) ? $domain.'/'.$dir : $domain;	
	
	set_pref('siteurl',$siteurl);
	$txp_prefs['siteurl'] = $siteurl;
		
	// ---------------------------------------------------------------------
	// hide file_base_path
	
	if (safe_count('txp_prefs',"name='file_base_path' AND position != '0'")) {
	
		$file_base_path = $txp_prefs['path_to_site'].DS.'files';
		
		safe_update('txp_prefs',
			"val = '$file_base_path',type = 2,position = 0",
			"name = 'file_base_path'",1);
	}

	// ---------------------------------------------------------------------
	// add base
	
	if (!pref_exists('base')) {
	
		$base = 'http://'.$txp_prefs['siteurl'].DS.'admin/';
		
		if ($PFX and in_array("txp_prefs",getThings('SHOW TABLES',0))) {
			
			$r = safe_query("SELECT val FROM txp_prefs WHERE name = 'siteurl'");
			
			if (@mysql_num_rows($r) > 0) {
				$domain = mysql_result($r,0);
				mysql_free_result($r);
				$base = "http://$domain/admin/";
			}
		}
		
		$pref = $pref_default;
		$pref['name']  = 'base';
		$pref['val']   = $base;
		$pref['event'] = 'admin';
		
		safe_insert('txp_prefs',$pref,1);
	}

?>
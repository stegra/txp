<?php

$tree_tables = array(
	'article'  => 'textpattern',
	'image'    => 'txp_image',
	'file'     => 'txp_file',  	
	'link'     => 'txp_link',		
	'custom'   => 'txp_custom',		
	'category' => 'txp_category',	
	'discuss'  => 'txp_discuss',
	'page'	   => 'txp_page',
	'form'	   => 'txp_form',
	'css'	   => 'txp_css',
	'site'	   => 'txp_site',
	'log'	   => 'txp_log'
);

$map = array(
	'id'   		=> 'ID',
	'name' 		=> 'Name',
	'title' 	=> 'Title',
	'category' 	=> 'Category1',
	'posted'	=> 'Posted',
	'date'		=> 'Posted',
	'created'   => 'Posted',
	'time'		=> 'Posted',
	'lastmod'	=> 'LastMod',
	'modified'	=> 'LastMod',
	'status'	=> 'Status',
	'trash'		=> 'Trash',
	'type'		=> 'Type'
);
	
// -----------------------------------------------------------------------------

function convert_table($table,$map,$title,$debug=1) {
	
	global $PFX, $WIN, $txp_user;
	
	$columns = array(
		'ID'				=> "int 			NOT NULL",
		'ParentID'			=> "int 			NOT NULL DEFAULT 0",
		'Alias'				=> "int				NOT NULL DEFAULT 0",
		'Posted'			=> "datetime 		NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'Expires'			=> "datetime 		NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'AuthorID'			=> "varchar(32) 	NOT NULL DEFAULT ''",
		'LastMod'			=> "datetime 		NOT NULL DEFAULT '0000-00-00 00:00:00'",
		'LastModMicro'		=> "varchar(30) 	NOT NULL DEFAULT '0000-00-00 00:00:00 0000000000'",
		'LastModID'			=> "varchar(64) 	NOT NULL DEFAULT ''",
		'Trash'				=> "tinyint	 		NOT NULL DEFAULT 0",
		'Name'				=> "varchar(128) 	NOT NULL DEFAULT ''",
		'Type'				=> "varchar(8) 		NOT NULL DEFAULT ''",
		'Class'				=> "varchar(128) 	NOT NULL DEFAULT ''",
		'Title'				=> "varchar(128) 	NOT NULL DEFAULT ''",
		'Title_html'		=> "varchar(128) 	NOT NULL DEFAULT ''",
		'Body'				=> "mediumtext 		NOT NULL DEFAULT ''",
		'Body_html'			=> "mediumtext 		NOT NULL DEFAULT ''",
		'Excerpt'			=> "text 			NOT NULL DEFAULT ''",
		'Excerpt_html'		=> "mediumtext 		NOT NULL DEFAULT ''",
		'ImageID'			=> "int				NOT NULL DEFAULT 0",
		'FileID'			=> "int				NOT NULL DEFAULT 0",
		'Category1'			=> "varchar(255)	NOT NULL DEFAULT ''",
		'Category2'			=> "varchar(255)	NOT NULL DEFAULT ''",
		'Status'			=> "smallint		NOT NULL DEFAULT 4",
		'OldStatus'			=> "varchar(4)		NOT NULL DEFAULT '0.0'",
		'textile_body'		=> "int				NOT NULL DEFAULT 1",
		'textile_excerpt'	=> "int				NOT NULL DEFAULT 1",
		'Keywords'			=> "varchar(255)	NOT NULL DEFAULT ''",
		'WordCount'			=> "int				NOT NULL DEFAULT 0",
		'CharCount'			=> "int				NOT NULL DEFAULT 0",
		'uid'				=> "varchar(32)		NOT NULL DEFAULT ''",
		'Position'			=> "int				NOT NULL DEFAULT 1",
		'lft'				=> "int				NOT NULL DEFAULT 0",
		'rgt'				=> "int				NOT NULL DEFAULT 0",
		'Path'				=> "varchar(128)	NOT NULL DEFAULT ''",
		'Level'				=> "tinyint			NOT NULL DEFAULT 0",
		'Children'			=> "int				NOT NULL DEFAULT -1",
		'ParentStatus'		=> "tinyint			NOT NULL DEFAULT 0",
		'ParentPosition'	=> "int				NOT NULL DEFAULT 0",
		'ParentName'		=> "varchar(255)	NOT NULL DEFAULT ''",
		'ParentClass'		=> "varchar(255)	NOT NULL DEFAULT ''",
		'ParentPosted'		=> "datetime		NOT NULL DEFAULT '0000-00-00 00:00:00'"
	);
	
	$existing  = getColumns($table);
	$to_add    = array_reverse($columns);
	$to_rename = array();
	
	foreach ($existing as $column) {
		
		if (isset($map[$column])) {
			
			$column = $to_rename[$column] = $map[$column];
			
			unset($to_add[$column]);
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// add new columns
	
	foreach ($to_add as $column => $type) {
		
		if (!column_exists($table,$column)) {
		
			safe_alter($table,"ADD COLUMN `$column` $type FIRST",$debug);
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// rename columns
	
	foreach ($to_rename as $old => $new) {
		
		$type = $columns[$new];
		
		safe_alter($table,"CHANGE `$old` `$new` $type",$debug);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// reposition columns
	
	$position_column = array_keys($columns);
	$column_position = array_flip($position_column);
	
	foreach ($columns as $name => $type) {
		
		$pos = $column_position[$name];
		$col = ($pos) ? $position_column[$pos-1] : '';
		$position = ($col) ? "AFTER `$col`" : "FIRST"; 
			
		safe_modcol($table,$name,"$type $position",$debug);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	safe_alter($table,"ADD COLUMN `P2` int NULL DEFAULT NULL",$debug);
	safe_alter($table,"ADD COLUMN `P3` int NULL DEFAULT NULL",$debug);
	safe_alter($table,"ADD COLUMN `P4` int NULL DEFAULT NULL",$debug);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	safe_unindex($table);
	
	safe_modcol($table,'ID',"int NOT NULL PRIMARY KEY AUTO_INCREMENT");
	
	safe_index($table,"Alias");
	safe_index($table,"ParentID");
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// add root node
	
	$root_id = (safe_count($table)) 
		? safe_field("MAX(ID)+1",$table)
		: 1;
	
	$name = make_name($title);
	$title = doSlash($title);
	
	safe_insert($table,
		"ID 		= '$root_id',
		 Name 		= '$name',
		 Title		= '$title', 
		 Title_html = '$title',
		 LastMod	= NOW(),
		 Posted		= NOW(),
		 AuthorID	= 'textpattern',
		 Level		= 1,
		 Status		= 4,
		`Type` 		= 'folder',
		 Position	= 1
		",$debug);
		
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// add trash
	
	$trash_id = $root_id + 1;
	
	safe_insert($table,
		"ID			= '$trash_id',
		 Name 		= 'TRASH',
		 Title		= 'Trash', 
		 Title_html = 'Trash',
		 LastMod	= NOW(),
		 Posted		= NOW(),
		 AuthorID	= 'textpattern',
		 Level		= 2,
		 Status		= 2,
		`Type` 		= 'trash',
		 Position   = 0
		",$debug);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// ParentID
	
	safe_update($table,"ParentID = $root_id","ParentID = 0 AND ID != $root_id",$debug);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Position
	
	$parents = safe_column("ID",$table,"ParentID != 0 ORDER BY ID ASC");
	
	foreach($parents as $parent_id) {
	
		$children = safe_column("ID",$table,"ParentID = $parent_id ORDER BY Posted ASC");
		
		$count = 1;
	
		foreach ($children as $child_id) { 
			
			safe_update($table,"Position = $count","ID = $child_id");
			
			$count += 1;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Parent Info 
	
	$parent = array('Status','Position','Name','Class','Posted');	
	
	foreach($parent as $key => $column) {
	
		$parent[$key] = n.t."t.Parent".$column." = p.".$column;
	}
	
	safe_update("$table AS t JOIN $table AS p ON t.ParentID = p.ID",impl($parent),"1=1",$debug);
	safe_update($table,"ParentStatus = Status","ParentID = 0",$debug); 

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// OldStatus Live for Hidden 
	
	safe_update($table,"OldStatus = CONCAT(4,'.',Level)","Status = 2 AND Name != 'TRASH'",$debug);
}

// -----------------------------------------------------------------------------
// get db modification version from php file

	function get_db_update_version() {
		
		$version = '';
		$update  = txpath.'/update/_update.php';
		
		if (!is_file($update)) {
			echo "$update file does not exist";
			return;
		}
		
		$f = fopen($update,"r");
		
		while (!$version and !feof($f)) {
			
			$line = fgets($f);
			
			if (preg_match("/VERSION[\:\s]+([\d\.]+)/",$line,$matches)) {
				
				$version = $matches[1];
			}
		}
		
		fclose($f);
		
		return $version;
	}

// -----------------------------------------------------------------------------

	function update_tree_tables() 
	{
		global $WIN, $tree_tables;
		
		echo hed("update tree tables",3);
		
		$save_win_table = $WIN['table'];
		
		foreach($tree_tables as $type => $table) {
		
			if ($table == 'txp_log') continue;
			
			if (column_exists($table,'ParentID')) {
				
				$WIN['table'] = $table;
				
				rebuild_txp_tree(0,0,$table);
					
				update_path(0,1,$table,$type);
				
				$children   = "(SELECT ParentID FROM ".safe_pfx($table)." WHERE Trash = 0 AND Name != 'TRASH')";
				$childcount = "(SELECT COUNT(*) FROM $children AS c WHERE c.ParentID = ID)";
				safe_update($table,"Children = $childcount",1,1);
			}
		}
		
		$WIN['table'] = $save_win_table;
	}

// -----------------------------------------------------------------------------

	function print_config() 
	{	
		global $txpcfg;
		
		echo '<br/><table class="config">';
		
		foreach ($txpcfg as $key => $value) {
			if ($key != 'pass') {
				echo '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
			}
		}
		
		echo "</table>";
	}

// -----------------------------------------------------------------------------

	function todo($text) 
	{
		global $site_id;
		
		static $id = 0;
		
		$id += 1;
		
		echo comment_line();
		
		echo hed($text,3).n.n;
		
		echo '<script type="text/javascript">';
		echo "toDo($site_id,0);";
		echo '</script>'.n;
	}

// =============================================================================

	function add_category_values()
	{
		global $tree_tables;
		
		todo("add category values");
		
		foreach($tree_tables as $type => $table) {
			
			// populate txp_content_category table
			
		 // if (!column_exists($table,"Categories")) continue;
			if (!column_exists($table,"Category1")) continue;
			
			safe_delete('txp_content_category',"type = '$type'",1);
			
			$columns = array('ID','Category1','Category2');
			
			$columns[] = (column_exists($table,"Class")) ? 'Class' : "'' AS Class";
			
			$rows = safe_rows_start(impl($columns),$table,"1",0,1);
		
			while ($row = nextRow($rows)) {
				
				extract($row);
				
				$set = array(
					'article_id' => $ID, 
					'type' 		 => $type, 
				 	'position'   => 1
				);
				
				if ($Class and $Class != $Category1 and $Class != $Category2) {
					$set['name'] = $Class;
					safe_insert('txp_content_category',$set);
					$set['position'] += 1;
				}
				
				if ($Category1) {
					$set['name'] = $Category1;
					safe_insert('txp_content_category',$set);
					$set['position'] += 1;
				}
				
				if ($Category2) {
					$set['name'] = $Category2;
					safe_insert('txp_content_category',$set);
				}
			}
			
			add_category_count($type,1); 
		}
	}

// =============================================================================\

	function add_category_count($type,$debug=0) 
	{	
		global $PFX;
		
		// how many times each category appears for the given content type
		
		$count_col = ucwords($type).'s'; 	// Articles,Images,Files,Links
		
		if (column_exists("txp_category",$count_col)) {
		
			$rows = safe_rows("ID,Name","txp_category","1",$debug);
			
			foreach($rows as $row) {
				
				extract($row);
				
				$count = safe_count("txp_content_category","name = '$Name' AND `type` = '$type'",$debug);
				
				safe_update("txp_category","$count_col = $count","ID = $ID",$debug);
			}
			
			// total count for each type 
			
			safe_update(
					"txp_category",
					"$count_col = (SELECT COUNT(DISTINCT(article_id)) 
								   FROM ".$PFX."txp_content_category 
								   WHERE `type` = '$type')",
					"ParentID = 0",$debug);
		}
	}

// =============================================================================

	function add_custom_fields()
	{
		global $PFX,$prefs;
		
		$fields = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// old custom field xml 
		
		if (is_file(txpath.'/custom/custom_fields.xml')) {
			
			include(txpath.'/update/lib/classCustomFields.php');
			
			$CF = new CustomFields();
			
			// print_r($_SESSION['custom_fields']);
		
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			// get fields from xml
		
			foreach ($_SESSION['custom_fields'] as $field) {
				
				$type	 = $field['type'];
				$title   = $field['title'];
				$name    = make_name($title);
				$custom  = str_replace('_','',$field['field']);
				$group   = $field['group'];
				$select  = (isset($field['select'])) ? $field['select'] : '';
				$section = reset(explode('/',$group));
				
				$field = array(
					'name'	  => $custom,
					'title'   => $title,
					'type'    => '',
					'input'   => '',
					'options' => '',
					'group'   => $group,
					'section' => $section
				);
				
				if ($type == 'text') {
					
					$field['type']	  = 'text';
					$field['input']	  = 'textfield';
				} 
				
				if ($type == 'textarea') {
					
					$field['type']	  = 'text';
					$field['input']	  = 'textarea';
				} 
				
				if ($type == 'date') {
					
					$field['type']	  = 'date';
					$field['input']	  = 'date';
				} 
				
				if ($type == 'time') {
					
					$field['type']	  = 'time';
					$field['input']	  = 'time';
				} 
				
				if ($type == 'checkbox') {
					
					$field['type']	  = 'text';
					$field['input']	  = 'checkbox';
				} 
				
				if ($type == 'select') {
					
					$field['type']	  = 'text';
					$field['input']	  = 'select';
					$field['options'] = implode(n,$select);
				}
				
				$fields[$section][$group][] = $field;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// add fields to txp_custom table
		
		safe_delete('txp_custom','ID >= 4');
		safe_query("ALTER TABLE ".$PFX."txp_custom AUTO_INCREMENT = 4");
		
		$root_id   = fetch('ID','txp_custom','ParentID',0);
		$root_name = fetch('Name','txp_custom','ParentID',0);
		
		foreach ($fields as $folder => $groups) {
			
			$parent_id   = $root_id;
			$parent_name = $root_name;
			
			if ($folder !== '*') {
				
				$set = array(
					'Name'       => $folder,
					'Type'       => 'folder',
					'Title'      => fetch('title','txp_section','name',$folder),
					'input'	     => '',
					'ParentName' => $parent_name,
					'Status'     => 4
				);
				
				$insert = content_create($parent_id,$set,'txp_custom','custom',1);
				
				$parent_id   = $insert[1];
				$parent_name = $folder;
			}
			
			foreach ($groups as $group) {
			
				foreach ($group as $field) {
					
					$set = array(
						'Name'       => $field['name'],
						'Type'       => $field['type'],
						'input'      => $field['input'],
						'Title'      => $field['title'],
						'Body'       => $field['options'],
						'ParentName' => $parent_name,
						'Status'     => 4
					);
				
					content_create($parent_id,$set,'txp_custom','custom');
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// add fields to txp_group table
		
		$group_id = 1;
		
		safe_delete('txp_group','1');
		safe_query("ALTER TABLE ".$PFX."txp_group AUTO_INCREMENT = 1");
		
		foreach ($fields as $folder => $groups) {
		
			if ($folder == '*') $folder = $root_name;
			
			foreach ($groups as $group) {
			
				foreach ($group as $field) {
				
					$name  = $field['name'];
					$group = $field['group'];
					
					$field_id = safe_field('ID','txp_custom',
						"Name ='$name' AND ParentName = '$folder' AND Type NOT IN ('folder','trash')");
					
					$set = array(
						'group_id'	  => $group_id,
						'field_id'    => $field_id,
						'field_name'  => $name,
						'instance_id' => 1,
						'status'	  => 'active',
						'last_mod'	  => "now()"
					);
					
					$group = explode(':',$group);
					$group = explode('/',array_shift($group));
					$set['by_section']  = trim(array_shift($group),'*');
					$set['by_category'] = trim(impl($group),'*,');
					
					safe_insert('txp_group',$set);
				}
				
				$group_id += 1;
			}
		}
	}

// =============================================================================

	function add_custom_field_values()
	{
		global $PFX,$prefs;
		
		todo("add custom field values");
		
		safe_delete('txp_content_value','1');
		safe_query("ALTER TABLE ".$PFX."txp_content_value AUTO_INCREMENT = 1");
		
		$columns = array('ID,Section,Category1,Category2');
		$custom  = array();
		
		for ($i=1;$i<=10;$i++) { 
			
			if ($prefs['custom_'.$i.'_set']) {
				$columns[] = "custom_$i";
				$custom[]  = "custom$i";
			}
		}
		
		$field_total = 0;
		$article_total = 0;
		$usage = array();
		
		$where = "Section != ''";
		
		$rows = safe_rows_start(impl($columns),"textpattern",$where);
		
		while ($row = nextRow($rows)) {
			
			$id 	    = array_shift($row);
			$section    = array_shift($row);
			$category1  = array_shift($row);
			$category2  = array_shift($row);
			$categories = trim("$category1,$category2",',');
			
			$count = 0;
			$values = array();
			
			$groups = safe_column(
				'field_name,group_id,field_id',
				'txp_group',
				"field_name IN (".in($custom).")  
				 AND (by_section = '' OR (by_section != '' AND by_section = '$section')) 
				 AND (by_category = '' OR (by_category != '' AND by_category = '$categories'))",0,0);
			
			foreach($row as $name => $value) {
				
				$custom_num = str_replace('_','',$name);
				
				if (strlen($value) and isset($groups[$custom_num])) {
					
					extract($groups[$custom_num]);
					
					$values = array(
						'`article_id`'  => $id, 
						'`group_id`'	=> $group_id,
						'`field_id`'	=> $field_id,
						'`instance_id`' => 1, 
						'`field_name`'  => "'$custom_num'",
						'`status`'	  	=> 1,
						'`text_val`'	=> "'".doSlash($value)."'",
						'`num_val`'	    => "NULL"
					);
					
					if (is_numeric($value)) {
						$values['`num_val`'] = $value;
					}
					
					$row[$name] = '('.impl($values).')';
					
					$count += 1;
					
					if (isset($usage[$field_id])) {
						$usage[$field_id] += 1;
					} else {
						$usage[$field_id] = 1;
					}
				
				} else {
					
					unset($row[$name]);
				}
			}
			
			if ($values) {
				
				$table = $PFX.'txp_content_value'; 
				$columns = impl(array_keys($values));
				$values = implode(','.n,$row);
			
				if (safe_query("INSERT INTO $table ($columns) VALUES $values")) {
					
					$field_total += $count;
				}
				
				$article_total += 1;
			}
		}
		
		pre("$field_total field values were added for $article_total articles");
	}

?>
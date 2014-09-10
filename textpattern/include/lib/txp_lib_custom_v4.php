<?php

// -----------------------------------------------------------------------------

	function add_custom_field($article_id,$field_id,$group,$instance=0) {
		
		global $WIN,$event;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$all_groupby = getColumns('txp_group',null,'by_*');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$parent_article_id = fetch('ParentID',$WIN['table'],'ID',$article_id);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$group_id = 0;
		$groupby  = array();
		$append   = false;
		
		if (is_array($group)) {
			
			$groupby = $group;
		
		} else {
			
			$group_id = $group;
			$append = true;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$append) {
			
			// add field as a new group
			
			$groupby['category'] = expl($groupby['category']);
			$groupby['parent_category'] = expl($groupby['parent_category']);
		
			if ($event != 'utilities') {
			
				foreach($groupby['category'] as $key => $name) {
						
					$name = doSlash($name);
					
					if (!strlen($name)) {
						unset($groupby['category'][$key]);
						continue;
					}
					
					if (!getCount("txp_content_category","article_id = $article_id AND name = '$name'")) {
						unset($groupby['category'][$key]);
					}
				}
				
				foreach($groupby['parent_category'] as $key => $name) {
					
					$name = doSlash($name);
					
					if (!strlen($name)) {
						unset($groupby['category'][$key]);
						continue;
					}
					
					if (!getCount("txp_content_category","article_id = $parent_article_id AND name = '$name'")) {
						unset($groupby['category'][$key]);
					}
				}
			}
			
			// sorting in alhabetical order so that the order in 
			// which categories are entered does not matter
			sort($groupby['category']); 
			sort($groupby['parent_category']); 
			
			$groupby['category'] = implode(',',$groupby['category']);
			$groupby['parent_category'] = implode(',',$groupby['parent_category']);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($groupby['name']) {
				
				$groupby['name'] = fetch('Name',$WIN['table'],'ID',$article_id);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			
			if ($groupby['class'] or $groupby['category'] or $groupby['parent'] or $groupby['name']) {
			
				$groupby['id'] = 0;
			}
			
			foreach($groupby as $name => $value) {
				
				if ($value) {
					$groupby[$name] = "by_$name = '$value'";
				} else {
					unset($groupby[$name]);
				}
			}
		
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// if group/field/instance combination already
			// exists in group table do not add it again
			
			$where   = $groupby;
			$where[] = "field_id = $field_id";
			$where[] = "instance_id = $instance";
			
			if ($group_id = safe_field("group_id","txp_group",doAnd($where))) {
				
				return $group_id;
			}
		
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// get group id
			
			$group_by_columns = doAnd($groupby);
			
			if (!($group_id = safe_field("group_id","txp_group",$group_by_columns.' LIMIT 1'))) {
			
				$group_id = fetch("IFNULL(MAX(group_id)+1,1)","txp_group");
			}
			
		} else {
			
			// add field to an existing group
			
			$groupby = safe_row(
				impl($all_groupby),
				"txp_group",
				"group_id = $group_id");
			
			foreach($groupby as $name => $value) {
				
				if ($value) {
					$groupby[$name] = "$name = '$value'";
				} else {
					unset($groupby[$name]);
				}
			}
			
			$field = safe_row(
				"ParentID AS field_parent, Path AS field_path",
				"txp_custom",
				"ID = $field_id");
			
			extract($field);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$where = array(
			"group_id  = '$group_id'",
			"(field_id = '$field_id' OR field_parent = '$field_id')",
			"status    = 'removed'"
		);
		
		if (getCount("txp_group",doAnd($where))) {
			
			// mark field for restoration if it has previously been removed
			
			safe_update("txp_group","status = 'restore',last_mod = NOW()",doAnd($where));
		
		} else {
		
			// add field and its child fields to the txp_group table
			
			$select = array(
				"ID AS field_id",
				"Name AS field_name"
			);
			
			if (!$append) {
				$select[] = "IF(ID = $field_id,'',Path) AS field_path";
				$select[] = "IF(ID = $field_id,0,ParentID) AS field_parent";
			}
			
			$fields = safe_rows(
				implode(',',$select),
				"txp_custom",
				"(ID = '$field_id' OR ParentID = '$field_id') 
				 AND Trash = 0 
				 AND Status = 4 
				 ORDER BY lft ASC");
			
			if ($fields) {
				
				$root = fetch("ID","txp_custom","ParentID",0);
				
				foreach ($fields as $field) {
				
					extract($field);
					
					if ($field_parent == $root) {
						$field_parent = 0;
					}	
					
					$instance_id = safe_field(
						"IFNULL(MAX(instance_id)+1,1)",
						"txp_group",
						"group_id = '$group_id' AND field_id = '$field_id'");
					
					$group_by_columns = implode(', ',$groupby);
					$table = $WIN['table'];
					
					safe_insert("txp_group",
						"instance_id  =	'$instance_id',
						 group_id     = '$group_id',
						 field_id     = '$field_id',
						 field_name   = '$field_name',
						 field_path   = '$field_path',
						 field_parent = '$field_parent',
						 by_table	  = '$table',
						 $group_by_columns,
						 status   	  = 'add',
						 last_mod 	  = NOW()"
						 );
				}
				
				return $group_id;
			}
			
			return 0;
		}
	}

// -----------------------------------------------------------------------------
// append a child field to an existing group

	function append_custom_field($field_id,$field_parent,$table='',$content='') {
		
		if (!has_privs('article.edit')) return;
		
		$groups = safe_rows(
			"group_id,by_id,by_parent,by_class,by_category,by_sticky,status",
			"txp_group",
			"field_id = $field_parent");
			
		foreach($groups as $key => $group) {
			
			extract($group);
			
			
			if (!safe_count("txp_group","field_id = $field_id and group_id = $group_id")) {
			
				if (add_custom_field(0,$field_id,$group_id)) {
					
					$groups[$key] = $group_id;
				
				} else {
				
					unset($groups[$key]);
				}
			
			} else {
				
				unset($groups[$key]);
			}
		}
		
		return $groups;
	}
	
// -----------------------------------------------------------------------------
	
	function remove_custom_field($field_id=0,$table='') {
		
		global $WIN, $app_mode;
		
		if (!is_numeric($field_id)) return;
		if (!has_privs('article.edit')) return;
		
		$where  = array();
		$status = '';
		
		if ($app_mode == 'async') {
			
			// when field is removed from an article 
			// mark the group/field/instance as removed
			
			$field = gps('field');
			
			if (!preg_match('/^\d+\-\d+\-\d+$/',$field)) return;
			
			list($group_id,$field_id,$instance_id) = explode('-',$field);
			
			if ($group_id) {
			
				$where[] = "group_id = $group_id";
				$where[] = "field_id = $field_id";
				$where[] = "instance_id = $instance_id";
			
				$status = "remove";
			
			} else {
				
				// a field that does not have a group id must be removed 
				// from articles individually
				
				$id = $WIN['id'];
				
				safe_update('txp_content_value',
				    "status = 0",
					"article_id = $id 
					 AND group_id = 0 
					 AND field_id = $field_id 
					 AND status = 1");
			}
			
		} else {
			
			// when field is trashed from the 'custom' event
			// mark all instances of this field as removed
			
			$where[] = "(field_id = $field_id OR field_parent = $field_id)";
			
			$status = "trash";
		}
		
		if ($status) {
		
			$where[] = "status = 'active'";
			
			safe_update("txp_group",
				"status = '$status',last_mod = NOW()",
				doAnd($where));	
					 
			apply_custom_fields(0,$field_id,'',0,$table);
		}
		
		if ($app_mode == 'async') {
			
			echo $group_id.$field_id.$instance_id;
		}
	}

// -----------------------------------------------------------------------------
	
	function restore_custom_field($field_id,$table='') {
		
		if (!$field_id) return;
		if (!has_privs('article.edit')) return;
		
		$where  = "status = 'trashed'";
		$where .= " AND (field_id = $field_id OR field_parent = $field_id)";
		
		safe_update("txp_group","status = 'restore',last_mod = NOW()",$where);
			
		apply_custom_fields(0,$field_id,'',0,$table);
	}

// -----------------------------------------------------------------------------

	function apply_custom_fields($article_id = 0, 
								 $field_id = 0, 
								 $field_name = '', 
								 $group_id = 0, 
								 $table = '') 
	{
		global $PFX, $WIN, $event, $saved_fields, $saved_matching_articles;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$parent_article_id = 0;
		
		if ($article_id) {
		
			$parent_article_id = fetch("ParentID",$WIN['table'],"ID",$article_id);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$table   = (!$table) ? $WIN['table'] : $table;
		$content = $WIN['content'];
		$field_value_id = 0; 
		
		$where = "`type` = 'field' AND by_table = '$table'";
		$where.= ($field_id)   ? " AND g.field_id = '$field_id'" : "";
		$where.= ($field_name) ? " AND g.field_name = '$field_name'" : "";
		$where.= ($group_id)   ? " AND g.group_id = '$group_id'" : "";
		
		$columns = array(
			 'g.id',
			 'g.group_id',
			 'g.instance_id',
			 'g.field_id',
			 'g.field_name',
			 'g.field_parent',
			 'g.status'
		);
		
		foreach(getColumns('txp_group',null,'by_*') as $by_column) {
			$columns[] = 'g.'.$by_column;
		}
		
		if (!$article_id and column_exists("txp_group","used")) {
			$columns['used'] = 'g.used AS old_used';
			$columns['old_count'] = 'g.value_count AS old_value_count';
			$columns['new_count'] = "(SELECT COUNT(*) FROM ".$PFX."txp_content_value AS v 
			WHERE g.group_id = v.group_id AND g.field_id = v.field_id AND v.text_val != '' AND v.status = 1) AS new_value_count";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$fields = array();
		
		$key = $table.$field_id.$field_name.$group_id;
		
		if ($article_id) {
			
			if (is_array($saved_fields) and isset($saved_fields[$key])) {
					
				$fields = $saved_fields[$key];
			}
		}
		
		if (!$fields) {
		
			$fields = safe_rows(impl($columns),"txp_group AS g",$where,0,0);
			
			if ($article_id) {
			
				if (!is_array($saved_fields)) {
					
					$saved_fields = array();
				}
				
				$saved_fields[$key] = $fields;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach($fields as $field) {
			
			$tables = array("$table AS t");
			$key 	= array();
			$by  	= array();
			
			$matching_articles = array();
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			foreach($field as $name => $value) {
				
				if (str_begins_with($name,'by_')) {
					
					$key[$name] = (!strlen($value)) ? '*' : $value;
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			extract($field);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			// ID
			
			if ($article_id) {
				
				$key['by_id'] = $article_id;
				
				if ($by_id) {
					
					$key['by_id'] = ($by_id != $article_id) ? 0 : $by_id;
				}
				
				$by['id'] = "t.ID = ".$key['by_id']; 
			
			} elseif ($by_id) {
				
				$key['by_id'] = $by_id;
				
				$by['id'] = "t.ID = ".$key['by_id'];
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			// Class, ParentID, Sticky 
			
			if ($by_class)   $by['class']   = "t.Class = '$by_class'";
			if ($by_parent)  $by['parent']  = "t.ParentID = $by_parent";
			if ($by_sticky)  $by['sticky']  = "t.Status = 5";
			
			if (isset($by_section) and $by_section) {
				$by['section'] = "t.Section = '$by_section'";
			}
			
			if (isset($by_parent_class) and $by_parent_class) {
				
				// $by['parent_class'] = "t.ParentClass = '$by_parent_class'";
				
				$by['parent_class'] = "parent.Class = '$by_parent_class'";
				$tables['parent'] = "$table AS parent ON t.ParentID = parent.ID";
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			// Category
			
			if ($by_category) {
				
				foreach(expl($by_category) as $category) {
					
					$name = safe_field("name",
						"txp_content_category",
						"article_id = $article_id 
							AND name = '$category' 
							AND type = '$content'");
					
					$by['category'][] = "'$category' = '$name'";
				}
				
				$by['category'] = doAnd($by['category']);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
				
			$where = array_merge(array('1=1'),$by);
				
			$key = implode('-',$key);
			
			if (is_array($saved_matching_articles) and isset($saved_matching_articles[$key])) {
				
				$matching_articles = $saved_matching_articles[$key];
			
			} else {
				
				$matching_articles = safe_column("t.ID",implode(' JOIN ',$tables),doAnd($where),0,0);
				
				$saved_matching_articles[$key] = $matching_articles;
			}
			
			// pre('----------------------------');
			// pre("group:$group_id field:$field_id/$field_name instance:$instance_id (by id:$by_id, by_parent:$by_parent) $status");
			// pre($matching_articles);
			// $used = count($matching_articles);
			// pre("used: $used value count: $old_value_count/$new_value_count");
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($status == 'active' and $article_id) {
				
				if ($matching_articles) {
				
					$status = 'add';
				
				} else {
				
					$status = 'remove';
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$new_status = '';
			
			$tables[] = "txp_content_value AS v ON t.ID = v.article_id";
			
			if ($status == 'add' or $status == 'restore') {
				
				if ($matching_articles) {
				
					$where['instance'] = "v.instance_id = $instance_id";
					$where['table']    = "v.tbl = '$table'";
					$where['field']    = "v.field_id = $field_id";
					$where['group']    = "v.group_id = $group_id"; 
					
					if (safe_count(implode(' JOIN ',$tables),doAnd($where))) {
						
						$where['group'] = "v.status = 0"; 
						
						safe_update(
							implode(' JOIN ',$tables),
							"v.status = 1",doAnd($where));
					
					} else {
						
						foreach($matching_articles as $matching_article_id => $value_count) {
						
							$insert = array(
								'article_id'  	=>  $matching_article_id,
								'group_id'  	=>  $group_id,
								'field_id'  	=>  $field_id,
								'instance_id'  	=>  $instance_id,
								'field_name'  	=> 	$field_name,
								'field_parent'  =>  $field_parent,
								'tbl'  			=>  $table,
								'status'  		=>  1
							);
							
							$field_value_id = safe_insert("txp_content_value",doQuote($insert));
						}
					}
				}	
				
				$new_status = 'active';
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($status == 'remove' or $status == 'trash') {
				
				// the field was removed from an article in 'article' event 
				// or the field was trashed in 'custom' event  
				
				$where['group']    = "v.group_id = $group_id";
				$where['field']    = "v.field_id = $field_id";
				$where['instance'] = "v.instance_id = $instance_id";
				$where['status']   = "v.status = 1";
				
				$query = '';
				
				if ($matching_articles) {
					
					$where['val'] = "(NOT ISNULL(v.num_val) OR v.text_val != '')";
					
					if (safe_count(implode(' JOIN ',$tables),doAnd($where))) {
					
						// when field has any values  
						// set status to '0' in the value table and 
						// set status to 'removed' or 'trashed' in group table
						
						safe_update(
							implode(' JOIN ',$tables),
							"v.status = 0",doAnd($where));
						
						$new_status = ($status == 'remove') ? 'removed' : 'trashed';
						
					} else {
						
						unset($where['val']);
						
						// when field has no values or empty values
						// delete any instances from the value table
						
						safe_delete(
							implode(' JOIN ',$tables),
							doAnd($where),0,'v');
						
						// and from the group table
						
						foreach ($by as $key => $value) {
							unset($where[$key]);
						}
						
						$where['status'] = "status IN ('remove','trash')";
						$where['table']  = "by_table = '$table'";
						$where['id']     = "id = $id";
						
						$where = str_replace(' v.',' ',doAnd($where));
						
						safe_delete("txp_group",$where);
					}
				
				} elseif ($article_id) {
					
					unset($where['val']);
					
					$where['id'] = "t.ID = $article_id"; 
					
					safe_update(
						implode(' JOIN ',$tables),
						"v.status = 0",doAnd($where));
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// deactivate field values from articles that no longer have a matching group
			
				
			$ids = safe_column('id','txp_content_value',
				"tbl = '$table'
				 AND group_id = $group_id
				 AND field_id = $field_id
				 AND status = 1");
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// update txp_group table with new status
			
			if ($new_status) {
				
				safe_update("txp_group",
				    	"status = '$new_status',last_mod = NOW()",
						"id = $id 
						 AND status = '$status' 
					 	 AND by_table = '$table'");
			}		 
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// update usage count 
			
			if (!$article_id) {
				
				$set = array();
				
				if (isset($old_used) and $old_used != $used) {
					$set[] = "used = $used";
				}
				
				if (isset($old_value_count) and $old_value_count != $new_value_count) {
					$set[] = "value_count = $new_value_count";
				}
				
				if ($set) safe_update("txp_group",impl($set),"id = $id");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($_SESSION['window'])) {
		
			foreach ($_SESSION['window'] as $id => $win) {
				if (isset($win['list'])) {
					$_SESSION['window'][$id]['list']['custom'] = array();
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $field_value_id;
	}

// -----------------------------------------------------------------------------
// get the custom fields belonging to a particular article

	function getArticleCustomFields($article_id  = 0, $field_name  = '',
			 						$field_id    = 0, $instance_id = 0) 
	{
		global $WIN, $PFX;
		
		$table   = $WIN['table'];
		$content = $WIN['content'];
		
		$out = array();
		$where = array("g.type = 'field'");
		
		$where[] = "g.by_table = '$table'";
		$where[] = "g.status = 'active'";
		$where[] = ($field_name)
			? "f.name = '$field_name'"
			: "field_parent = $field_id";
		
		$orderby = "group_id ASC, f.position ASC, instance_id ASC";
		
		// temporary fix until all root node ids are replaced by 0
		$root = fetch("ID","txp_custom","ParentID",0);
		if (safe_count("txp_group","field_parent = $root")) {
			safe_update("txp_group","field_parent = 0","field_parent = $root");
		}
		
		$columns = array(
			 'g.id',
			 'field_id',
			 'group_id',
			 'instance_id',
			 'f.default AS field_default',
			 'f.position'
		);
		
		foreach(getColumns('txp_group',null,'by_*') as $by_column) {
			$columns[] = $by_column;
		}
			
		$groups = safe_rows(
			impl($columns),
			"txp_group AS g JOIN txp_custom AS f ON g.field_id = f.id",
			doAnd($where).' ORDER BY '.$orderby,0,0);
		
		// get fields that have no group_id
		
		$rows = safe_rows(
		    "id,
			 field_id,
			 group_id,
			 instance_id,
			 '' AS field_default,
			 1 AS position",
			 "txp_content_value",
			 "tbl = '$table' 
			  AND article_id = $article_id
			  AND group_id = 0
			  AND status = 1");
		
		if ($rows) {
			
			$groups = array_merge($groups,$rows);
		}
		
		foreach($groups as $group) {
			
			extract($group);
			
			$tables = array('textpattern');
			$where 	= array("t.ID = $article_id");
			$path  	= '';
			
			if ($group_id) {
				
				if ($by_class)   $where['by_class']   = "t.Class = '$by_class'";
				if ($by_id)      $where['by_id']      = "t.ID = $by_id";
				if ($by_parent)  $where['by_parent']  = "t.ParentID = $by_parent";
				
				if ($by_category) {
					
					foreach(expl($by_category) as $category) {
						
						$name = safe_field("name",
							"txp_content_category",
							"article_id = $article_id
								AND name = '$category' 
								AND type = '$content'");
						
						$where[] = "'$category' = '$name'";
					}
				}
				
				if ($by_sticky)  $where['by_sticky'] = "t.Status = 5";
				
				if (isset($by_section) and $by_section) 
					$where['by_section'] = "t.Section = '$by_section'";
					
				if (isset($by_parent_class) and $by_parent_class) 
					$where['by_parent_class'] = "t.ParentClass = '$by_parent_class'";
					
				if ($by_path) {
					
					$path = $by_path;
					
					if (!preg_match('/^\//',$path)) {
						$path = '//'.$path;
					}
				}
			}
			
			$match = safe_count_treex(0,$path,$tables,$where);
			
			if ($match) {
				
				$field_settings = safe_row(
					"name, title, ParentID AS parent, type, input, Body_html AS options, `default`, Excerpt AS help",
					"txp_custom","ID = $field_id",0,0);
				
				if ($field_settings['input'] == 'checkbox') {
				
					$default = explode(',',$field_settings['default']);
					foreach ($default as $key => $value) $default[$key] = "0:$value";
					$field_settings['default'] = implode(',',$default);
				}
				
				$field_value = safe_row(
					"id AS value_id, text_val", "txp_content_value",
					"field_id = $field_id
					 AND group_id = $group_id
					 AND instance_id = $instance_id 
					 AND article_id = $article_id
					 ORDER BY id ASC");
				
				// get multiple values from a checkbox field
				
				if ($field_value and $field_settings['input'] == 'checkbox') {
				
					$value_id = $field_value['value_id'];
					
					$id_text_val = safe_column(
						array("id","CONCAT(id,':',text_val) AS text_val"),
						"txp_content_value",
						"field_id = $field_id
					 	 AND group_id = $group_id
					 	 AND instance_id = $instance_id 
					 	 AND article_id = $article_id
					 	 ORDER BY id ASC");
					 	 
					$field_value['text_val'] = implode(',',$id_text_val);
				}
				
				if ($field_settings['input'] == 'radio') {
					
					$value_id = 0;
					$text_val = (strlen($field_default)) ? $field_default : $field_settings['default'];
						
					if ($field_value) {
						$value_id = $field_value['value_id'];
						$text_val = $field_value['text_val'];
					}
					
					$field_value['value_id'] = $value_id;
					$field_value['text_val'] = $value_id.':'.$text_val;
				}
				
				$childcount = getCount("txp_group",
					"instance_id = $instance_id
					 AND group_id = $group_id
					 AND field_parent = $field_id");
				
				$children = array(
					'childcount' => $childcount,
					'children'	 => ''
				);
				
				if ($childcount and !$field_name) {
					$children['children'] = getArticleCustomFields($article_id,'',$field_id,$instance_id);
				}
				
				if (!$field_value) {
					$field_value = array(
						'value_id' => 0,
						'text_val' => (strlen($field_default)) ? $field_default : $field_settings['default']
					);
				} 
				
				$field_settings['options'] = str_replace('\r\n',n,$field_settings['options']);
				
				unset($group['by_class']);
				unset($group['by_category']);
				unset($group['by_id']);
				unset($group['by_parent']);
				
				if (isset($group['by_section'])) unset($group['by_section']);
				if (isset($group['by_parent_class'])) unset($group['by_parent_class']);
				
				$group['id'] = $field_value['value_id'];

				$out[] = array_merge($group,$field_settings,$field_value,$children);
			}
		}
		
		return $out;
	}
	
// -----------------------------------------------------------------------------

	function displayArticleCustomFields($fields,$is_alias=0,$level=1) 
	{
		global $smarty;
		
		if (!$fields) return '';
		
		$out = array();
		
		foreach($fields as $field) {
			
			extract($field);
			
			$title    = doStrip($title);
			$text_val = doStrip($text_val);
			
			if ($type == 'folder' and !$children) continue;
			
			$options = array();
			
			if (in_list($input,'select,selectgroup,radio,checkbox')) {
			
				if ($field['options']) {
					
					$optgroup = '';
										
					foreach (explode(n,$field['options']) as $option) {
						
						$option = doStrip($option);
						$value = trim(array_shift(explode(':',$option)));
						$label = trim(array_pop(explode(':',$option)));

						if ($input == 'selectgroup') {
							
							$value = preg_replace('/^\s*\*\s+/','',$value,-1,$count);
							$label = preg_replace('/^\s*\*\s+/','',$label);
							
							if (!$count) {
							
								$options[$value] = array();
								$optgroup = $value;
							
							} elseif ($optgroup) {
								
								$options[$optgroup][$value] = $label;
							}
							
						} elseif (in_list($input,'radio,checkbox')) {
							
							$options[$value] = array(
								'value'   => $value,
								'label'   => $label,
								'checked' => false
							);
						
						} else {
							
							$options[$value] = $label;
						}
					}
				} else {
					
					// default options 
					
					if ($input == 'radio') {
						
						$options['yes'] = array(
							'value'   => 'yes',
							'label'   => 'Yes',
							'checked' => false
						);
						
						$options['no'] = array(
							'value'   => 'no',
							'label'   => 'No',
							'checked' => false
						);
					}
					
					if ($input == 'checkbox') {
						
						$options['yes'] = array(
							'value'   => 'yes',
							'label'   => 'Yes',
							'checked' => false
						);
					}
				}
				
				if (in_list($input,'radio,checkbox')) {
					
					$id = 0;
					
					foreach (explode(',',$field['text_val']) as $value) {
						
						$value    = explode(':',$value);
						$value_id = array_shift($value);
						$value    = trim(array_shift($value));
						
						if ($value) {
							$options[$value]['checked'] = true;
						}
						
						if ($value_id and $input == 'radio') { 
							$id = $value_id;
						}
					}
					
					foreach ($options as $key => $option) {
						
						$optvalue   = (isset($option['value'])) ? $option['value'] : 1;
						$optlabel   = (isset($option['label'])) ? $option['label'] : '';
						$checked = $option['checked'];
						
						$smarty->assign('name',$name);
						$smarty->assign('id',$id);
						$smarty->assign('value',$optvalue);
						$smarty->assign('label',$optlabel);
						$smarty->assign('checked',$checked);
						$smarty->assign('total',count($options));
						
						$options[$key] = $smarty->fetch('article/field_'.$input.'.tpl'); 
					}
					
					$options = implode(n,$options);
				}
			
			} elseif ($input == 'date') {
				
				$years = array();
				$days  = array();
				
				for ($y=1999; $y<=date('Y')+3; $y++) {
					$years[$y] = substr($y,2,2);
				}
				
				for ($y=1; $y<=31; $y++) {
					$days[str_pad($y,2,'0',STR_PAD_LEFT)] = $y;
				}
		
				$date  = $field['text_val'];
				$year  = date('Y');
				$month = '';
				$day   = '';
				
				if ($date) {
					list($year,$month,$day) = explode('/',$date);
				}
				
				$smarty->assign('years',$years);
				$smarty->assign('year',$year);
				$smarty->assign('days',$days);
				$smarty->assign('day',$day);
				
				$smarty->assign('id',$id);
				$smarty->assign('name',$name);
				$smarty->assign('field',$field_id);
				$smarty->assign('value',$text_val);
				
				$text_val = $smarty->fetch('article/field_'.$input.'.tpl');
			
			} elseif ($input == 'time') {
				
				$smarty->assign('id',$id);
				$smarty->assign('name',$name);
				$smarty->assign('field',$field_id);
				$smarty->assign('value',$text_val);
				
				$text_val = $smarty->fetch('article/field_'.$input.'.tpl');
				
			}
			
			$smarty->assign('id',$id);
			$smarty->assign('field',$field_id);
			$smarty->assign('group',$group_id);
			$smarty->assign('instance',$instance_id);
			$smarty->assign('name',$name);
			$smarty->assign('title',$title);
			$smarty->assign('value',$text_val);
			$smarty->assign('input',str_replace('none','',$input));
			$smarty->assign('options',$options);
			$smarty->assign('help',(($help) ? 1 : 0));
			$smarty->assign('childcount',$childcount);
			$smarty->assign('level',$level);
			$smarty->assign('is_alias',$is_alias);
			
			$key = $id.$field_id.$instance_id;
			
			$out[$key] = $smarty->fetch('article/field.tpl');
			
			$children = displayArticleCustomFields($children,$is_alias,$level+1);
			
			$out[$key] = str_replace('{ CHILDREN }',$children,$out[$key]);
		}
		
		return implode('',$out);
	}

// -----------------------------------------------------------------------------

	function displayAddCustomFieldForm($article_id)
	{
		global $WIN, $smarty, $prefs;
		
		$table   = $WIN['table'];
		$content = $WIN['content'];
		
		if ($prefs['production_status'] == 'live') return '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$fields = getArticleCustomFieldTree('',$article_id);
		
		foreach($fields as $key => $field) {
		
			$fields[$key]['id'] = $field['id'];
			
			if ($field['aliases']) { 
				$fields[$key]['title'] .= ' ('.$field['children'].')';
			}
		}
		
		$fields = treeSelectInput('custom_field_id',$fields,'','custom-field-apply-id',0,'id');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$parent_id = fetch("ParentID",$table,"ID",$article_id);
		
		$class = safe_field(
				"c.Title",
				"$table AS t 
				 JOIN txp_category AS c",
				"t.ID = $article_id 
				 AND t.Class = c.Name");
		
		$parent_class = safe_field(
				"c.Title",
				"$table AS t 
				 JOIN txp_category AS c",
				"t.ID = $parent_id 
				 AND t.Class = c.Name");
		
		$categories = safe_rows(
				"tc.Name,tc.Title",
				"txp_content_category AS tcc 
				 JOIN txp_category AS tc",
				"tcc.article_id = $article_id 
				 AND tcc.type   = '$content' 
				 AND tcc.name  != 'NONE'
				 AND tcc.name   = tc.Name
				 AND tc.Class  != 'yes'
				 ORDER BY tcc.position ASC");
		
		$parent_categories = safe_rows(
				"tc.Name,tc.Title",
				"txp_content_category AS tcc 
				 JOIN txp_category AS tc",
				"tcc.article_id = $parent_id 
				 AND tcc.type   = '$content' 
				 AND tcc.name  != 'NONE'
				 AND tcc.name   = tc.Name
				 AND tc.Class  != 'yes'
				 ORDER BY tcc.position ASC");
		
		$parent_categories = array();
				 
		$sticky = safe_field("Status",$table,
				"ID = $article_id AND Trash = 0 AND Status = 5");
				
		$title = safe_field("Title",$table,
				"ID = $article_id AND Trash = 0");
		
		$parent_title = safe_field("Title",$table,"ID = $parent_id");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$smarty->assign('field_select_pop',$fields);
		$smarty->assign('article_name',$title);
		$smarty->assign('article_class',$class);
		$smarty->assign('article_categories',$categories);
		$smarty->assign('article_sticky',$sticky);
		$smarty->assign('article_parent',maxwords($parent_title,25));
		$smarty->assign('article_parent_class',$parent_class);
		$smarty->assign('article_parent_categories',$parent_categories);
		
		return n.n.$smarty->fetch('article/field_add.tpl');
	}	
				
// -----------------------------------------------------------------------------

	function getArticleCustomFieldTree($root=0,$article_id=0)	
	{
		global $dump;
		static $out = array();
		
		$root = (!$root) ? fetch('id','txp_custom','ParentID',$root) : $root;
		
		$columns = array(
			'f.id','name','title','path','ParentID AS parent','f.type','input','Body AS options','`default`','Excerpt AS help','Level'
		);
		
		if ($article_id) { 
			
			$columns[] = "IFNULL(group_id,0) AS group_id";
			$fields = safe_rows(implode(',',$columns),
				"txp_custom AS f LEFT JOIN txp_content_value AS tcv ON f.id = tcv.field_id AND tcv.article_id = $article_id",
				"f.Trash = 0 AND f.Name != 'TRASH' AND f.Status = 4 AND f.ParentID = '$root' AND f.Alias = 0 GROUP BY f.id ORDER BY f.id ASC, tcv.id ASC");
		
		} else { 
			
			$fields = safe_rows(implode(',',$columns),"txp_custom AS f","Trash = 0 AND Name != 'TRASH' AND Status = 4 AND ParentID = '$root' AND Alias = 0 ORDER BY id ASC");
		}
		
		foreach($fields as $field) {
		
			extract($field);
			
			$out[$id] = array(
				'id' 	   => $id,
				'name' 	   => $name,
				'title'    => $title,
				'path'     => $path,
				'level'    => $Level, 
				'children' => getCount("txp_custom","ParentID = '$id' AND Trash = 0 AND Status = 4"),
				'aliases'  => getCount("txp_custom","ParentID = '$id' AND Trash = 0 AND Status = 4 AND Alias != 0"),
				'parent'   => $parent,
				'type'	   => $type,
				'input'	   => $input,
				'options'  => $options,
				'default'  => $default,
				'help'     => $help,
				'group_id' => (isset($group_id)) ? $group_id : 0
			);
				
			if ($id) {
			
				if (getCount('txp_custom',"ParentID = '$id' AND Trash = 0 AND Status = 4",0)) {
					getArticleCustomFieldTree($id,$article_id);
				}
			}
		}
		
		return $out;
	}

// -----------------------------------------------------------------------------
	
	function get_field_value_id($article_id,$field_name)
	{
		return safe_field("id","txp_content_value",
			"article_id = $article_id 
			 AND field_name = '$field_name'");
		/* 	 AND text_val IS NULL"); 
		 *   This was probably not even necessary, but to update a custom 
		 *	 field's value with AJAX we need the field_value_id.
		 */
	}
		
// -----------------------------------------------------------------------------
	
	function get_group_sql($group)
	{
		extract($group);
		
		$out = array("1 = 1");
		
		if ($article_class) $out['class'] = "t.Class = '$article_class'";
		if ($article_id)    $out['id']    = "t.ID    = '$article_id'";
		
		/* if ($article_category)  {
			
			$article_category = expl($article_category);
			
			foreach ($article_category as $key => $value) {
				$out['category'] = "t.Category".($key+1)." IN ('".implode("','",$article_category)."')";
			}
		} */
		
		return doAnd($out);
	}

?>
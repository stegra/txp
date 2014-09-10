<?php
	
	$sort   = gps('sort','id');
	$delete = assert_int(gps('delete',0));
	$values = '';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	safe_update('txp_group','field_parent = 0',"1=1");
	
	if ($delete) {
		
		$row = safe_row('group_id,instance_id,field_id','txp_group',"id = $delete");
	
		if ($row) {
		
			extract($row);
			
			safe_delete('txp_content_value',
				"group_id = $group_id 
				 AND instance_id = $instance_id 
				 AND field_id = $field_id");
				
			safe_delete('txp_group',"id = $delete");
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// add field as a new group 
	
	if (gps('new') == 'group') {
		
		$group_id = safe_field('group_id + 1','txp_group','1=1 ORDER BY group_id DESC');
		
		$field_id = assert_int(gps('field',0));
		$field_name = fetch('Name','txp_custom',"ID",$field_id);
		
		$by = array(
			'id'    		=> 0,
			'path'			=> '',
			'parent' 		=> 0,
			'name' 			=> '',
			'class' 		=> '',
			'parent_class'	=> '',
			'category'		=> ''
		);
		
		foreach ($by as $name => $value) {
			$by[$name] = "by_$name = '".doSlash($_POST['by'][$name])."'";
		}
		
		$by = implode(',',$by);
	
		if ($field_name) {
			
			safe_insert('txp_group',
				"group_id    = $group_id,
				 instance_id = 1,
				 field_id 	 = $field_id,
				 field_name	 = '$field_name',
				 status	 	 = 'active',
				 last_mod	 = NOW(),
				 $by");
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// add field to an existing group 
	
	if (gps('new') == 'field') {
		
		pre($_POST);
		
		$group_id = assert_int(gps('group',0));
		$field_id = assert_int(gps('field',0));
		
		$row = safe_row('*','txp_group',"group_id = $group_id");
		
		if ($row) {
			
			unset($row['id']);
			unset($row['field_default']);
			unset($row['value_count']);
			
			$row['group_id']   = $group_id;
			$row['field_id']   = $field_id;
			$row['field_name'] = fetch('Name','txp_custom',"ID",$field_id);
			$row['last_mod']   = 'NOW()';
			
			safe_insert('txp_group',$row);
		}
		
		echo "<hr/>";
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$groups = safe_column('group_id','txp_group',
		"Type = 'field' GROUP BY group_id ORDER BY group_id ASC");
		
	$fields = safe_column('ID,Title','txp_custom',
		"Trash = 0 AND Type NOT IN ('folder','trash') ORDER BY Name ASC");
		
	$classes = safe_column('Name,Title','txp_category',
		"Trash = 0 AND Class = 'yes' AND Type = 'article' ORDER BY Name ASC");
		
	$categories = safe_column('Name,Title','txp_category',
		"Trash = 0 AND Class != 'yes' AND Type = 'article' AND ParentID != 0 ORDER BY Name ASC");
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$value_count = "(SELECT COUNT(*) FROM ".$PFX."txp_content_value AS v
		 WHERE g.group_id = v.group_id 
		 AND g.instance_id = v.instance_id 
		 AND g.field_name = v.field_name
		 AND v.status = 1) AS value_count";
		 
	$by_id_title = "(SELECT Title FROM ".$PFX."textpattern AS t
		 WHERE g.by_id = t.ID) AS by_id_title";

	$by_parent_title = "(SELECT Title FROM ".$PFX."textpattern AS t
		 WHERE g.by_parent = t.ID) AS by_parent_title";
		 
	$rows = safe_rows("*,$value_count,0 AS member_count,$by_id_title,$by_parent_title",'txp_group AS g',
		"1=1 ORDER BY $sort ASC, group_id ASC, instance_id ASC");
	
	$by_parent_class = null;
	
	if (column_exists('txp_group','by_parent_class')) {
		
		$by_parent_class = '';
	}
	
	$group_members = array();
	
	foreach ($rows as $key => $row) {
	
		extract($row);
		
		if (!array_key_exists($group_id,$group_members)) {
			$group_members[$group_id] = group_member_count($group_id);
		}
		
		$odd_even = ($key % 2 == 0) ? 'even' : 'odd';
		
		if ($by_id == 0) $by_id = '';
		if ($by_parent == 0) $by_parent = '';
		
		$values .= '<tr class="'.$odd_even.'">';
		$values .= '<td>'.$id.'</td>';
		$values .= '<td>'.$group_id.'</td>';
		$values .= '<td>'.$instance_id.'</td>';
		$values .= '<td>'.$field_name.'</td>';
		
		$values .= '<td><a target="_new" href="/admin/index.php?event=article&step=edit&win=new&id='.$by_id.'">'.$by_id.'</a> '.$by_id_title.'</td>';
		$values .= '<td>'.$by_path.'</td>';
		$values .= '<td><a target="_new" href="/admin/index.php?event=article&step=edit&win=new&id='.$by_parent.'">'.$by_parent.'</a> '.$by_parent_title.'</td>';
		$values .= '<td>'.$by_name.'</td>';
		$values .= '<td>'.$by_class.'</td>';
		if (!is_null($by_parent_class)) $values .= '<td>'.$by_parent_class.'</td>';
		$values .= '<td>'.$by_category.'</td>';
		
		$values .= '<td>'.$group_members[$group_id].'</td>';
		$values .= '<td>'.$value_count.'</td>';
		$values .= '<td>'.$status.'</td>';
		
		$values .= '<td><a class="delete" title="Delete" href="?go=custom_field_groups&sort='.$sort.'&delete='.$id.'">x</a></td>';
		$values .= '</tr>'; 
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
?>

<html>
<head>
	<title>Custom Field Groups</title>
	<link rel="stylesheet" href="/textpattern/utilities/include/custom_fields/css/style.css?12351111111111111" type="text/css">
	<script src="/textpattern/js/lib/jquery-1.7.1.min.js" language="javascript"></script>
	<style type="text/css" rel="stylesheet">
		form select {
			width: 177px;
		} 
		
		form table td {
			padding-right: 20px;
			padding-left: 10px;
		} 
		
		form table tr.line td {
			padding: 0px;
		}
	</style>
</head>
<body class="groups">

	<h1>Custom Field Groups</h1>
	
	<div id="content">
		
		<?php if ($values) { ?>
		<table class='values'>
			<?php echo '<tr class="sort-'.$sort.'">' ?>
				<th class='id'><?php echo '<a title="Sort By ID" href="?go=custom_field_groups&sort=id">' ?>ID</a></th>
				<th class='group_id'><?php echo '<a title="Sort By Group ID" href="?go=custom_field_groups&sort=group_id">' ?>Group</a></th>
				<th class='instance_id'><?php echo '<a title="Sort By Instance ID" href="?go=custom_field_groups&sort=instance_id">' ?>Instance</a></th>
				<th class='field_name'><?php echo '<a title="Sort By Field Name" href="?go=custom_field_groups&sort=field_name">' ?>Name</a></th>
				
				<th class='by_id'><?php echo '<a title="Sort By ID" href="?go=custom_field_groups&sort=by_id">' ?>By ID</a></th>
				<th class='by_path'><?php echo '<a title="Sort By Path" href="?go=custom_field_groups&sort=by_path">' ?>By Path</a></th>
				<th class='by_parent'><?php echo '<a title="Sort By Parent" href="?go=custom_field_groups&sort=by_parent">' ?>By Parent</a></th>
				<th class='by_name'><?php echo '<a title="Sort By Name" href="?go=custom_field_groups&sort=by_name">' ?>By Name</a></th>
				<th class='by_class'><?php echo '<a title="Sort By Class" href="?go=custom_field_groups&sort=by_class">' ?>By Class</a></th>
				
				<?php if (!is_null($by_parent_class)) { ?>
					<th class='by_parent_class'><?php echo '<a title="Sort By Parent Class" href="?go=custom_field_groups&sort=by_parent_class">' ?>By Parent Class</a></th>
				<?php } ?>
				
				<th class='by_category'><?php echo '<a title="Sort By Category" href="?go=custom_field_groups&sort=by_category">' ?>By Category</a></th>
				
				<th class='values'>Members</th>
				<th class='values'>Values</th>
				<th class='values'><?php echo '<a title="Sort By Status" href="?go=custom_field_groups&sort=status">' ?>Status</a></th>
				
				<th>Delete</th>
			</tr>
			<?php echo $values; ?>
		</table>
		<?php } ?>
		
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		
		<br/><br/>
		
		<b>Create new group</b>
		
		<form method="post" action="index.php?go=custom_field_groups">
			
			<input type="hidden" name="new" value="group"/>
			
			<table>
			<tr class="line">
				<td colspan="2"><hr/></td>
			</tr>
			<tr>
				<td>By ID</td>
				<td><input type="text" name="by[id]" size="3" value="0"/></td>
			</tr>
			<tr>
				<td>By Path</td>
				<td><input type="text" name="by[path]" size="30" value=""/></td>
			</tr>
			<tr>
				<td>By Parent ID</td>
				<td><input type="text" name="by[parent]" size="3" value="0"/></td>
			</tr>
			<tr>
				<td>By Name</td>
				<td><input type="text" name="by[name]" size="30" value=""/></td>
			</tr>
			<tr>
				<td>By Class</td>
				<td>
					<select name="by[class]" >
						echo '<option value=""></option>'."\n";
						<?php
							foreach ($classes as $name => $title) {
								echo '<option value="'.$name.'">'.$title.'</option>'."\n";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>By Parent Class</td>
				<td>
					<select name="by[parent_class]">
						echo '<option value=""></option>'."\n";
						<?php
							foreach ($classes as $name => $title) {
								echo '<option value="'.$name.'">'.$title.'</option>'."\n";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>By Category</td>
				<td>
					<select name="by[category]">
						echo '<option value=""></option>'."\n";
						<?php
							foreach ($categories as $name => $title) {
								echo '<option value="'.$name.'">'.$title.'</option>'."\n";
							}
						?>
					</select>
				</td>
			</tr>
			<tr class="line">
				<td colspan="2"><hr/></td>
			</tr>
			<tr>
				<td>Field</td>
				<td>
					<select name="field">
						<?php
							foreach ($fields as $id => $title) {
								echo '<option value="'.$id.'">'.$title.'</option>'."\n";
							}
						?>
					</select>
				</td>
			</tr>
			<tr class="line">
				<td colspan="2"><hr/></td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" value="Create"/></td>
			</tr>
			</table>
			
		</form>
		
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		
		<br/><br/>
		
		<b>Add field to existing group</b>
		
		<form method="post" action="index.php?go=custom_field_groups">
			
			<input type="hidden" name="new" value="field"/>
			
			<table>
			<tr class="line">
				<td colspan="2"><hr/></td>
			</tr>
			<tr>
				<td>Field</td>
				<td>
					<select name="field">
						<?php
							foreach ($fields as $id => $title) {
								echo '<option value="'.$id.'">'.$title.'</option>'."\n";
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>Group</td>
				<td>
					<select name="group">
						<?php
							foreach ($groups as $id) {
								echo '<option value="'.$id.'">'.$id.'</option>'."\n";
							}
						?>
					</select>
				</td>
			</tr>
			<tr class="line">
				<td colspan="2"><hr/></td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" value="Add"/></td>
			</tr>
			</table>
	</div>
	
</body>
</html>

<?php
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	function group_member_count($group_id) {
		
		static $by_columns = '';
		
		if (!$by_columns) $by_columns = get_group_by_columns();
		
		$row = safe_row("$by_columns",'txp_group',"group_id = $group_id");
		
		$tables = array('textpattern');
		$where  = array("1=1");
		$path   = '';
		
		foreach($row as $by => $value) {
			
			if ($by == 'by_table') continue;
			
			if ($value) {
			
				if ($by == 'by_id') {
					
					$where[] = "t.ID = $value";
				} 
				
				if ($by == 'by_parent') {
					
					$where[] = "t.ParentID = $value";
				}
				
				if ($by == 'by_name') {
					
					$where[] = "t.Name = '$value'";
				}
				
				if ($by == 'by_class') {
					
					$where[] = "t.Class = '$value'";
				}
				
				if ($by == 'by_parent_class') {
					
					$where[] = "t.ParentClass = '$value'";
				}
				
				if ($by == 'by_category') {
					
					add_by_category($value,$where,$tables);
				}
				
				if ($by == 'by_path') {
					
					$path = $value;
						
					if (!preg_match('/^\//',$path)) {
						$path = '//'.$path;
					}
				}
			}
		}
		
		return safe_count_treex(0,$path,$tables,$where);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	function get_group_by_columns() {
		
		return getThing("SELECT GROUP_CONCAT(COLUMN_NAME) 
			FROM INFORMATION_SCHEMA.COLUMNS 
			WHERE TABLE_SCHEMA = 'txp' 
			AND TABLE_NAME = 'txp_group'
			AND COLUMN_NAME RLIKE '^by_'");
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	function add_by_category($category,&$where,&$tables) {
		
		$category 		= explode(',',$category);
		$category_type 	= 'article';
		$category_count = 1;
				
		foreach($category as $key => $value) {
			
			$tbl = 'category'.$category_count;
			
			$tables['category'][] = "LEFT JOIN txp_content_category AS `$tbl` ON t.id = $tbl.article_id AND $tbl.type = '$category_type'";
			
			$category[$key] = makeWhereSQL($tbl.'.name',$value);
			
			$category_count += 1;
		}
		
		$where['category'] = '('.implode(' ',$category).')';
		
		if (isset($tables['category'])) {
			$tables['category'] = implode(n,$tables['category']);
		}
	}
?>
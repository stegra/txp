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
	
	$value_count = "(SELECT COUNT(*) FROM ".$PFX."txp_content_value AS v
		 WHERE g.group_id = v.group_id 
		 AND g.instance_id = v.instance_id 
		 AND g.field_name = v.field_name
		 AND v.status = 1) AS value_count";
		 
	$by_id_title = "(SELECT Title FROM ".$PFX."textpattern AS t
		 WHERE g.by_id = t.ID) AS by_id_title";

	$by_parent_title = "(SELECT Title FROM ".$PFX."textpattern AS t
		 WHERE g.by_parent = t.ID) AS by_parent_title";
		 
	$rows = safe_rows("*,$value_count,$by_id_title,$by_parent_title",'txp_group AS g',
		"1=1 ORDER BY $sort ASC, group_id ASC, instance_id ASC");
	
	$by_parent_class = null;
	
	if (column_exists('txp_group','by_parent_class')) {
		
		$by_parent_class = '';
	}
	
	foreach ($rows as $key => $row) {
	
		extract($row);
		
		$odd_even = ($key % 2 == 0) ? 'even' : 'odd';
		
		if ($by_id == 0) $by_id = '';
		if ($by_parent == 0) $by_parent = '';
		
		$values .= '<tr class="'.$odd_even.'">';
		$values .= '<td>'.$id.'</td>';
		$values .= '<td>'.$group_id.'</td>';
		$values .= '<td>'.$instance_id.'</td>';
		$values .= '<td>'.$field_name.'</td>';
		
		$values .= '<td><a target="_new" href="/admin/index.php?event=article&step=edit&win=new&id='.$by_id.'">'.$by_id.'</a> '.$by_id_title.'</td>';
		$values .= '<td><a target="_new" href="/admin/index.php?event=article&step=edit&win=new&id='.$by_parent.'">'.$by_parent.'</a> '.$by_parent_title.'</td>';
		$values .= '<td>'.$by_name.'</td>';
		$values .= '<td>'.$by_class.'</td>';
		if (!is_null($by_parent_class)) $values .= '<td>'.$by_parent_class.'</td>';
		$values .= '<td>'.$by_category.'</td>';
		
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
				<th class='by_parent'><?php echo '<a title="Sort By Parent" href="?go=custom_field_groups&sort=by_parent">' ?>By Parent</a></th>
				<th class='by_name'><?php echo '<a title="Sort By Name" href="?go=custom_field_groups&sort=by_name">' ?>By Name</a></th>
				<th class='by_class'><?php echo '<a title="Sort By Class" href="?go=custom_field_groups&sort=by_class">' ?>By Class</a></th>
				
				<?php if (!is_null($by_parent_class)) { ?>
					<th class='by_parent_class'><?php echo '<a title="Sort By Parent Class" href="?go=custom_field_groups&sort=by_parent_class">' ?>By Parent Class</a></th>
				<?php } ?>
				
				<th class='by_category'><?php echo '<a title="Sort By Category" href="?go=custom_field_groups&sort=by_category">' ?>By Category</a></th>

				<th class='values'>Values</th>
				<th class='values'><?php echo '<a title="Sort By Status" href="?go=custom_field_groups&sort=status">' ?>Status</a></th>
				
				<th>Delete</th>
			</tr>
			<?php echo $values; ?>
		</table>
		<?php } ?>
	</div>
	
</body>
</html>
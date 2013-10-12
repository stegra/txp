<?php
	
	$field  = gps('field');
	$sort   = gps('sort','article_id');
	$delete = assert_int(gps('delete',0));
	$values = '';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if ($delete) {
		
		safe_update('txp_content_value',"status = 0","id = $delete");
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// get field 
	
	if ($field) {
		
		$rows = safe_rows('v.id,v.article_id,v.text_val,v.group_id,v.instance_id,t.Title,p.Title AS ParentTitle',
			'txp_content_value AS v 
			 JOIN textpattern AS t ON v.article_id = t.ID 
			 JOIN textpattern AS p ON t.parentid = p.ID',
			"v.tbl = 'textpattern' AND v.field_name = '$field' AND v.status = 1 ORDER BY $sort ASC,instance_id ASC");
		
		foreach ($rows as $key => $row) {
		
			extract($row);
			
			$odd_even = ($key % 2 == 0) ? 'even' : 'odd';
			
			$value = (strlen($text_val)) ? $text_val : '&mdash;';
			
			$values .= '<tr class="'.$odd_even.'">';
			$values .= '<td><a target="_new" href="/admin/index.php?event=article&step=edit&win=new&id='.$article_id.'">'.$article_id.'</a></td>';
			$values .= '<td>'.$ParentTitle.'</td>';
			$values .= '<td>'.$Title.'</td>';
			$values .= '<td>'.$value.'</td>';
			$values .= '<td>'.$group_id.'</td>';
			$values .= '<td>'.$instance_id.'</td>';
			$values .= '<td><a class="delete" title="Delete" href="?go=custom_field_values&field='.$field.'&sort='.$sort.'&delete='.$id.'">x</a></td>';
			$values .= '</tr>'; 
		}
	
	} else {
		
		rebuild_txp_tree(0,0,'txp_custom');
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// get all fields
	
	$fields = safe_rows('ID,Name,Title,Level,Type','txp_custom',
		"Trash = 0 AND Name != 'TRASH' AND ParentID != 0 ORDER BY lft");
	
	
	foreach($fields as $key => $item) {
		
		extract($item);
		
		$selected = ($Name == $field) ? 'selected' : '';
		
		$title = ($Type != 'folder')
			? '<a class="'.$selected.'" title="'.$Title.'" href="?go=custom_field_values&field='.$Name.'&sort='.$sort.'">'.$Title.'</a>'
			: $Title;
			
		$fields[$key] = '<tr><td class="level-'.$Level.'">'.$title.'</td><tr>';
	}
	
	$fields = implode(n,$fields);
	
	$fields = '<tr><td class="level-1"><a href="?go=custom_field_groups">GROUPS</a></td><tr>'.n.$fields;
	
?>

<html>
<head>
	<title>Custom Field Values</title>
	<link rel="stylesheet" href="/textpattern/utilities/include/custom_fields/css/style.css?12351111111111111" type="text/css">
	<script src="/textpattern/js/lib/jquery-1.7.1.min.js" language="javascript"></script>
</head>
<body>

	<h1>Custom Field Values</h1>
	
	<table class="fields">
		<?php echo $fields; ?>
	</table>
	
	<div id="content">
		
		<?php if ($values) { ?>
		<table class='values'>
			<?php echo '<tr class="sort-'.$sort.'">' ?>
				<th class='article_id'><?php echo '<a title="Sort By Article ID" href="?go=custom_field_values&field='.$field.'&sort=article_id">' ?>ID</a></th>
				<th class='parenttitle'><?php echo '<a title="Sort By Article Parent Title" href="?go=custom_field_values&field='.$field.'&sort=parenttitle">' ?>Parent</a></th>
				<th class='title'><?php echo '<a title="Sort By Article Title" href="?go=custom_field_values&field='.$field.'&sort=title">' ?>Title</a></th>
				<th class='text_val'><?php echo '<a title="Sort By Field Value" href="?go=custom_field_values&field='.$field.'&sort=text_val">' ?>Value</a></th>
				<th class='group_id'><?php echo '<a title="Sort By Group ID" href="?go=custom_field_values&field='.$field.'&sort=group_id">' ?>Group</a></th>
				<th class='instance_id'><?php echo '<a title="Sort By Instance ID" href="?go=custom_field_values&field='.$field.'&sort=instance_id">' ?>#</a></th>
				<th>Delete</th>
			</tr>
			<?php echo $values; ?>
		</table>
		<?php } ?>
	</div>
	
</body>
</html>
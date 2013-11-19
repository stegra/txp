<?php
	
	$field  = gps('field');
	$sort   = gps('sort','agent');
	$delete = assert_int(gps('delete',0));
	$values = '';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if ($delete) {
		
		safe_delete('txp_log_agent',"id = $delete");
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// get field 
	
	switch ($sort) {
		case 'agent' : $sortby = 'agent ASC, width ASC, count DESC'; break;
		case 'width' : $sortby = 'width DESC, agent ASC, count DESC'; break;
		case 'count' : $sortby = 'count DESC, agent ASC, width DESC'; break;
	}
	
	$rows = safe_rows('*','txp_log_agent',"1=1 ORDER BY $sortby");
	
	foreach ($rows as $key => $row) {
	
		extract($row);
		
		$odd_even = ($key % 2 == 0) ? 'even' : 'odd';
		
		$values .= '<tr class="'.$odd_even.'">';
		$values .= '<td>'.$agent.'</td>';
		$values .= '<td>'.$width.'</td>';
		$values .= '<td>'.$count.'</td>';
		$values .= '<td><a class="delete" title="Delete" href="?go=user_agent_log&sort='.$sort.'&delete='.$id.'">x</a></td>';
		$values .= '</tr>'; 
	}
?>

<html>
<head>
	<title>User Agent Log</title>
	<link rel="stylesheet" href="/textpattern/utilities/include/user_agent_log/css/style.css?12351111111111111" type="text/css">
	<script src="/textpattern/js/lib/jquery-1.7.1.min.js" language="javascript"></script>
</head>
<body>

	<h1><a href="?go=user_agent_log">User Agent Log</a></h1>
	
	<div id="content">
		
		<?php if ($values) { ?>
		<table class='values'>
			<?php echo '<tr class="sort-'.$sort.'">' ?>
				<th class='agent'><?php echo '<a title="Sort By Agent" href="?go=user_agent_log&sort=agent">' ?>Agent</a></th>
				<th class='width'><?php echo '<a title="Sort By Screen Size" href="?go=user_agent_log&sort=width">' ?>Screen</a></th>
				<th class='count'><?php echo '<a title="Sort By Count" href="?go=user_agent_log&sort=count">' ?>Count</a></th>
				<th>Delete</th>
			</tr>
			<?php echo $values; ?>
		</table>
		<?php } ?>
	</div>
	
</body>
</html>
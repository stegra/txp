<?php

// -------------------------------------------------------------------------------------
	
	$in_now    = gps('now');
	$in_file   = ps('file');
	$in_tables = ps('table',array());
	$out 	   = array();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$dbpath = get_db_backup_dir();
	
	if (!$dbpath) {
	
		$out[] = '<p class="error">Error: directory <b>'.$dbpath.'</b> not found';
	
	} else {
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($in_now) {
			
			backup_db(1);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$files = dirlist($dbpath);
		
		if(empty($files)) {
			
			$out[] = '<p class="error">Error: no backup files found</p>';
		}
		
		sort($files);
		
		$files = array_reverse($files);
		
		$select_file = array();
		
		foreach ($files as $file) {
			
			$sel = ($file == $in_file) ? ' selected="selected"' : '';
			
			$tbl  = ".+?";
			$date = "\d\d\d\d\-\d\d-\d\d";
			$time = "\d\d\-\d\d";
			
			preg_match('/^'.$tbl.'\-('.$date.')\-('.$time.')/',$file,$matches);
			
			if ($matches) {
				
				if (preg_match('/\.sql(\.gz)?$/',$file)) continue;
				
				$date = str_replace('-','/',$matches[1]);
				$time = ($matches[2] != '00-00') ? str_replace('-',':',$matches[2]) : '';
				
				$title = preg_replace('/\-00\-00/','',$file);
				
				$select_file[] = '<option name="file" value="'.$file.'"'.$sel.'>'.$date.' '.$time.'</option>';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$tables = getThings('SHOW TABLES',0);
		
		$exclude = array(
			'txp_cache',
			'txp_log',
			'txp_lang',
			'txp_plugin',
			'txp_path',
			'txp_field',
			'txp_section',
			'txp_discuss_ipban',
			'txp_discuss_nonce',
			'txp_update',
			'txp_window',
			'txp_sticky',
			'txp_tag',
			'txp_tag_attr'
		);
		
		if ($PFX) {
			$exclude[] = 'txp_site';
		}
		
		$select_table = array();
		$num = 1;
		
		foreach ($tables as $key => $table) {
			
			$match = (!$PFX) ? "txp_|textpattern" : $PFX;
			
			if (preg_match('/^('.$match.')/',$table)) {
				
				$table = preg_replace('/^'.$PFX.'/','',$table);
				
				if (in_array($table,$exclude)) continue;
				
				$sel = (in_array($table,$in_tables)) ? ' checked="checked"' : '';
				
				$count = getCount($table);
				
				$select_table[] = '<li class="item-'.($num++).'">'
					.'<input class="checkbox" type="checkbox" name="table[]" value="'.$table.'"'.$sel.'> '
					.$table
					.'<span class="count">'.$count.'</span>'
					.'</input></li>';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$in_tables = array_flip($in_tables);
		
		$in_tables = restore_db('',$in_file,$in_tables);
		
		foreach ($in_tables as $table => $total) {
		
			$out[] = "<p>$table: $total</p>";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($dump)) {
			
			foreach ($dump as $line) {
				$out[] = "<p>"
					.$line[0]
					.( (isset($line[1]) and $line[1]) ? '<br/><span class="error">'.$line[1].'</span>' : '')
					."</p>";
			}
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
	$out = implode("\n\n",$out);	
		
?>
<html>
<head>
	<title>Admin > Utilities > Restore Database</title>
	<link type="text/css" rel="stylesheet" href="<?php echo $base; ?>utilities/css/lib/jquery.jscrollpane.css" media="all" />
	<link href="<?php echo $base; ?>utilities/css/global.css" rel="Stylesheet" type="text/css" />
	<link href="<?php echo $base; ?>utilities/include/restore/css/global.css" rel="Stylesheet" type="text/css" />
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/jquery-1.5.2.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/jquery.mousewheel.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/lib/jquery.jscrollpane.js"></script>
	<script type="text/javascript" src="<?php echo $base; ?>utilities/js/global.js"></script>
</head>
<body>

<div id="header"></div>

<div id="content">

	<table class="main">
	<tr>
		<td class="left">
			
			<div class="control">
			
				<form action="index.php" method="post">
					
					<div class="top">
					
						<table>
						<tr>
							<td>Restore from:</td>
							<td>
								<select name="file">
									<?php echo implode('',$select_file); ?>
								</select>
							</td>
						</tr>
						</table>
					
					</div>
						
					<div class="scroll">
					
						<ul>
							<?php echo implode('',$select_table); ?>
						</ul>
						
					</div>
					
					<div class="footer">
					
						
						<p>
							<input type="hidden" name="go" value="restore"/>
							<input class="submit" type="submit" value="Restore"/>
						</p>
					
						<p class="new"><a href="index.php?go=restore&now=1">Create a new DB dump now.</a></a>
					
					</div>
						
				</form>
			
			</div>
			
		</td>
	
		<td class="right">
		
			<div class="console">
			
				<div class="scroll">
				
					<div class="pad">

						<?php echo $out ?>

					</div>
				
				</div>
				
			</div>
			
			<div class="footer"></div>
			
		</td>
	</tr>
	</table>

</div>

</body>
</html>
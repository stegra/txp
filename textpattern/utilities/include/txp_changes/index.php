<?php
	
	$days = 1;
	$sort = '';
	
	if (isset($_GET['days']) and preg_match('/^\d+$/',$_GET['days'])) {
	
		$days = intval($_GET['days']);
	}
	
	if (isset($_GET['sort']) and $_GET['sort'] == '1') {
		
		$sort = 'lastmod DESC';
	}
	
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Modified Files</title>
	<meta name="generator" content="BBEdit 10.1" />
	<style rel="stylesheet" type="text/css">
		td { 
			font-family: Courier;
			padding: 1px 10px;
		}
		
		form {
			border-bottom: 1px dotted #999;
			padding-bottom: 5px;
			margin-bottom: 10px;
		}
		
		p {
			padding: 0px;
			margin: 5px 11px;
			color: #777;
		}
		
	</style>
</head>
<body>
	<form method="GET">
		&#160;&#160; <input type="submit" value="Show"/> files modified within <input type="number" name="days" value="<?php echo $days; ?>" size="3" /> days
		 
		<?php if ($sort) { ?>
			<input type="checkbox" name="sort" value="1" checked="checked"/> 
		<?php } else { ?>
			<input type="checkbox" name="sort" value="1" /> 
		<?php } ?>	
			sorted by date
		<input type="hidden" name="go" value="txp_changes"/>
	</form>
	
<?php
	
	if (isset($_GET['days']) and $days > 0) {
		
		$dl = new DirList(txpath,$sort);
		$dl->recurse = true;
	 // $dl->setLastMod('2014/04/15 18:05');
		$dl->setLastMod($days);
		$dl->setReturn('lastmod');
		$dl->exclude = array(
			'xsl/page',
			'xsl/form',
			'txp_tpl_c',
			'error_log',
			'plugins/inspector/index.html',
			'test'
		);
		
		$list = $dl->getFiles();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		echo "<table>";
		
		foreach ($list as $file) {
			
			list($name,$lastmod) = explode(':',$file);
			$ext = get_file_ext($name); 
			
			echo "<tr>";
			echo "<td>/textpattern/$name</td>";
			echo "<td>$ext</td>";
			echo "<td>".date('D M d h:ia',$lastmod)."</td>";
			echo "</tr>";
		}
		
		echo "</table>";
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$count = count($list);
		
		if (!$count) {
			
			echo "<p>None</p>";
			
		} elseif ($count == 1) {
			
			echo "<p>1 File</p>";
		
		} else {
			
			echo "<p>$count Files</p>";
		}
	}
		
?>
</body>
</html>

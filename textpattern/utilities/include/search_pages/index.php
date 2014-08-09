<?php
	
	$keyword   = '';
	$txptag    = '';
	$attr_name = '';
	$attr_val  = '';
	
	if (isset($_GET['keyword'])) {
		
		$keyword = trim($_GET['keyword']);
	}
	
	if (isset($_GET['txptag'])) {
		
		$txptag = trim($_GET['txptag']);
	}
	
	if (isset($_GET['attr_name'])) {
		
		$attr_name = trim($_GET['attr_name']);
	}
	
	if (isset($_GET['attr_val'])) {
		
		$attr_val = trim($_GET['attr_val']);
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Search TXP Pages</title>
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
			margin: 5px 0px;
			color: #777;
			font-family: Courier;
			font-size: 12px;
		}
		
		#results {
			margin: 20px 10px;
		}
		
		h4 {
			font-size: 12px;
		}
		
		h4 a {
			text-decoration: none;
		}
		
		#results p span {
			background-color: yellow;
		}
		
	</style>
</head>
<body>
	
	<form method="GET">
		
		<input type="hidden" name="go" value="search_pages"/>
		
		<table>
			<tr>
				<td class="label">Keyword:</td>
				<td><input type="text" name="keyword" value="<?php echo $keyword; ?>"/></td>
			</tr>
			<tr>
				<td class="label">TXP tag name:</td>
				<td><input type="text" name="txptag" value="<?php echo $txptag; ?>"/></td>
			</tr>
			<tr>
				<td class="label">TXP attr name:</td>
				<td><input type="text" name="attr_name" value="<?php echo $attr_name; ?>"/></td>
			</tr>
			<tr>
				<td class="label">TXP attr value:</td>
				<td><input type="text" name="attr_val" value="<?php echo $attr_val; ?>"/></td>
			</tr>
			<tr>
				<td class="label"></td>
				<td><input type="submit" value="Search"/></td>
			</tr>
		</table>
		
	</form>
	
	<div id="results">
	
<?php
	
	
	if (isset($_GET['keyword'])) {
		
		$select = array('*');
		$where  = array('Trash = 0');
		$order  = ' ORDER BY lft ASC';
		$limit  = " LIMIT 40";
		
		if ($keyword) {
		
			$select[] = "MATCH (Body) AGAINST ('".doSlash($keyword)."') AS score";
			$where[]  = "Body RLIKE '".doSlash($keyword)."'";
			$order    = ' ORDER BY score DESC';
		}
		
		if ($txptag) {
			
			$where[]  = "Body RLIKE '".'<txp:'.doSlash($txptag)."'";
		}
		
		if ($attr_name) {
			
			$attr_name_search = ' '.doSlash($attr_name.'="');
			
			$where[]  = "Body RLIKE '".$attr_name_search."'";
		}
		
		$rs = safe_rows(impl($select),"txp_page",impl($where,' AND ').$order.$limit,0,0);
		
		if ($rs) {
			display_results($rs);
		}
	}
	
?>
	</div>
	
</body>
</html>

<?php

	function display_results($results) 
	{
		global $keyword,$txptag,$attr_name;
		
		$keyword = expl($keyword,' ');
		
		foreach($results as $i => $item) {
			
			$id 		= $item['ID'];
			$parentid 	= $item['ParentID'];
			$title  	= $item['Title'];
			$path		= $item['Path'];
			$body	    = $item['Body'];
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -	
			// document title 
			
			if ($path) {
			
				$path = explode('/',$path);
				
				foreach ($path as $key => $value) {
					$path[$key] = fetch('Title','txp_page',"ID",$value);
				}
				
				$path = implode(' / ',$path) . ' / ';
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$body = explode(n,$body);
			
			$lines = array();
			
			foreach($body as $key => $line) {
				
				$found = array();
				
				// - - - - - - - - - - - - - - - - - - - - - - - - -
				// keyword position
								
				foreach($keyword as $word) {
					
					$pos = strpos($line,$word);
					
					if ($pos !== false) {
					
						$found[$pos] = array(
							'start'	=> $pos,
							'end'	=> $pos+strlen($word)
						); 
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - -
				// txp tag position
				
				$txptag_found = false;
								
				if ($txptag) {
				
					$pos = strpos($line,'<txp:'.$txptag);
					
					if ($pos !== false) {
						
						$txptag_found = $pos;
						
						$found[$pos] = array(
							'start'	=> $pos+1,
							'end'	=> $pos+1+strlen('txp:'.$txptag)
						); 
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - -
				// txp attribute position
				
				$attr_name_found = false;
				
				if ($attr_name) {
				
					$pos = strpos($line,' '.$attr_name.'="');
					
					if ($pos !== false) {
						
						$attr_name_found = $pos;
						
						$found[$pos] = array(
							'start'	=> $pos+1,
							'end'	=> $pos+1+strlen($attr_name)
						); 
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($txptag and $txptag_found !== false) {
					
					if ($attr_name and $attr_name_found == false) {
						
						unset($found[$txptag_found]);
					}
				}	
				
				if ($attr_name and $attr_name_found !== false) {
					
					if ($txptag and $txptag_found == false) {
						
						unset($found[$attr_name_found]);
					}
				}	
				
				// - - - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($found) {
					ksort($found);
					$lines[$key] = $found;
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// print result item 
			
			if ($lines) {
			
				echo "<h4>$path".'<a target="_new" href="/admin/index.php?event=page&step=edit&win=new&mini=1&linenum=on&id='.$id.'">'.$title.'</a></h4>';
				
				foreach($lines as $key => $found) {
					
					$text  = $body[$key];
					$added = 0;
					
					foreach ($found as $place) {
						
						extract($place);
						$start += $added;
						$end   += $added;
						
						$text = substr_replace($text,'[span]'.substr($text,$start),$start);
						$text = substr_replace($text,'[/span]'.substr($text,$end+6),$end+6);
						
						$added += 13;
					}
					
					$text = htmlentities($text);
					$line = $key + 1;
					
					$text = str_replace('[span]','<span>',$text);
					$text = str_replace('[/span]','</span>',$text);
					
					echo "<p>$line: $text</p>";
				}
			}
		}
	}
?>
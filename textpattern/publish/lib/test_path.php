<?php
	
	include txpath.'/include/lib/txp_class_ContentList.php';
	
	// ---------------------------------------------------------------------
	// TEST
	
	$test1 = array(
		".",				// self
		"..",				// parent
		"/",				// root
		"*",				// children of context
		"/*",				// children of root
		"//*",				// all descendants of context
		"///*"				// all descendants of root
	);
	
	$test2 = array(
		"abc",
		"/abc",
		"//abc",
		"///abc",
		"!abc",
		"/!abc",
		"//!abc",
		"///!abc"
	);
	
	$test3 = array(
		"/",
		"..",
		"../abc",
		"../..",
		"../../textpattern",
		"/textpattern"
	);
	
	$test4 = array(
		"/years/2012/abc",
		"/years/2012/*",
		"/years/*/abc",
		"/years/*/*",
		"2012/abc",
		"2012/*",
		"*/abc",
		"*/*",
		"/years/2012/!abc",
		"/years/!2012/*",
		"/years/*/!abc",
		"2012/!abc",
		"!2012/*",
		"*/!abc"
	);
	
	$test5 = array(
		"/years//abc",
		"/test/years//abc",
		"/years//abc/xyz",
		"/years//abc//xyz",		// test error
		"years//abc",
		"test/years//abc",
		"years//abc/xyz",
		"years//abc//xyz",
		"years/abc/info",
		"years/abc/info/sdf",
		"years/abc/info/sdf/rtyu"
	);
	
	$test6 = array(
		"product:abc",
		"years/product:abc",
		"years/product:abc/info",
		"years/!product:abc",
		"years/*:abc",
		"years/product:*"
	);
	
	$test7 = array(
		"//*"
	);
	
	
	// $test = array_merge($test1,$test2,$test3,$test4,$test5,$test6);
	// $test = array_merge($test1,$test2);
	// $test = $test1;
	$test = $test7;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	extract(safe_rows_treex());
	
	if ($level == 1) $path = array();
	
	$smarty->assign('context_id',$id);
	$smarty->assign('context_name',$name);
	$smarty->assign('context_level',$level);
	$smarty->assign('context_path',implode('/',$path));
	
	$list = new ContentList(); 
	$list->root = 1;
	$list->open = 'ALL';
	$list = $list->getList();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$tables = array();
	$maxlines = 0;
	
	foreach ($test as $path) {
		
		$error = '';
		$total = 0;
		
		// -------------------------------------------------
		
		$columns = "t.ID,t.Title";
		$table   = array('textpattern');
		$where   = array(
			0 => "t.Class = 'section'",
			1 => "t.Status = 4"
		);
		
		// -------------------------------------------------
		// custom field
		/*
		$table[] = "txp_content_value AS `price` ON t.ID = price.article_id";
		$where[] = "price.tbl = 'textpattern'";
		$where[] = "price.status = 1";
		$where[] = "price.field_name = 'price'";
		$where[] = "price.num_val >= '11.00'";
			
		// -------------------------------------------------
		// category
		
		$table[] = "txp_content_category AS `category` ON t.ID = category.article_id";
		$where[] = "category.name = 'section'";
			
		// -------------------------------------------------
		// child count
		
		$where[] = "(SELECT COUNT(*) FROM textpattern AS `child` WHERE t.ID = child.ParentID) = 3";
			
		// -------------------------------------------------
		// file
		
		$table[] = "txp_file AS file ON t.FileID = file.ID";
		$where[] = "(file.Type IN ('pdf') OR file.ext IN ('.pdf'))";
		
		$where['order'] = 'ID ASC';
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$rows = safe_rows_treex(0,$path,$columns,$table,$where,1);
		
		// continue;
		
		if (is_array($rows)) {
			
			foreach ($rows as $key => $row) {
				
			 // $rows[$key] = $row['ID'];
				$rows[$key] = implode(', ',$row);
			}
			
			foreach ($list as $key => $row) {
				
				$list[$key]['Sel'] = '';
				$list[$key]['Context'] = '';
				
				if (in_array($row['ID'],$rows)) {
					$list[$key]['Sel'] = 'selected';
				}
				
				if ($row['ID'] == $id) {
					$list[$key]['Context'] = 'context';
				}
			}
			
			$total = count($rows);
			
		} else {
			
			$error = $rows;
			
			foreach ($list as $key => $row) {
				$list[$key]['Sel'] = '';
				$list[$key]['Context'] = '';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -	
		
		$query = '';
		
		if (is_array($dump)) {
			
			extract($dump);
			
			// unset($where['trash']);
			// unset($where['trashed']);
			
			$select = 'SELECT '.implode(',',$select).n;
			$from   = '  FROM '.implode(n.'  JOIN ',$from).n;
			$where  = ' WHERE '.implode(n.'   AND ',$where).n;
			$group  = ($group) ? ' GROUP BY '.$group.n : '';
			$order  = ($order) ? ' ORDER BY '.$order.n : '';
			$limit  = ($limit) ? ' LIMIT '.$limit.n : '';
			
			$query = $select.$from.$where.$group.$order.$limit;
			$query = colorcode1($query);
			$lines = count(explode(n,trim($query)));
			$maxlines = ($lines > $maxlines) ? $lines : $maxlines;
		}
		
		$dump = '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$smarty->assign('path',$path);
		$smarty->assign('error',$error);
		$smarty->assign('query',$query);
		$smarty->assign('maxlines',$maxlines);
		$smarty->assign('total',$total);
		$smarty->assign('list',$list);
		$smarty->assign('rows',$rows);
		
		$tables[] = $smarty->fetch('test/path_table.tpl');
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$smarty->assign('table_count',count($tables));
	$smarty->assign('tables',implode(n,$tables));
	
	echo $smarty->fetch('test/path.tpl');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	function colorcode1($text) {
		
		$sql = "SELECT|FROM|AS|JOIN|ON|WHERE|AND|GROUP\s+BY|ORDER\s+BY|DESC|ASC|LIMIT";
		
		$text = preg_replace("/\b(".$sql.")\b/","<span class=\"sql\">$1</span>",$text);
		
		$text = preg_replace("/([\s])(\d+)([\s\b\)])/","$1<span class=\"sqlval\">$2</span>$3",$text);
		$text = preg_replace("/(\'[^\']*?\')/","<span class=\"sqlval\">$1</span>",$text);
		$text = preg_replace("/(\s)1\=1(\s)/","$1<span class=\"sqlval\">1</span>=<span class=\"sqlval\">1</span>$2",$text);
		
		return $text;
	}
	
	
	
?>
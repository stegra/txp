<?php
	
	$op = '';
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// get single of multiple category tests
	
	if (preg_match('/\|/',$category)) {
		$category = explode('|',$category);
		$op = ' OR ';
	} elseif (preg_match('/,/',$category)) {
		$category = explode(',',$category);
		$op = ' AND ';
	} else {
		$category = array($category);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// examine each cetegory test for a back or forward path 
	// and get the value of the category 
	
	foreach ($category as $test) {
	
		$lookback  = 0;
		$lookahead = 0;
	
		array_shift($category);
		
		$test = explode('/',$test);
		
		foreach($test as $value) {
			
			array_shift($test);
			
			switch ($value) {
				case '[c]' 			: $category_value = $c; break;
				case '[category]'	: $category_value = $article_stack->get('category'); break;
				case '[Category]'	: $category_value = $article_stack->get('category'); break;
				case '..'			: $category_value = ''; $lookback++; break;
				case '*'			: $category_value = ''; $lookahead++; break;
				default				: $category_value = $value;
			}
			
			if ($category_value) {
			
				$test[] = array(
					'back'  => $lookback,
					'ahead' => $lookahead,
					'value' => $category_value
				);
				
				 $lookahead++;
			}
		}
		
		$category[] = $test;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// build SQL
	
	foreach ($category as $test) {
		
		array_shift($category);
		
		foreach($test as $item) {
		
			extract($item);
			array_shift($test);
			
			if ($back > 0) {
				
				$from  = array('textpattern AS p1');
				$where = array('t.ParentID = p1.ID');
				
				for ($i=1; $i < $back; $i++) {
					$from[]  = "textpattern AS p".($i+1);
					$where[] = "p".$i.".ParentID = p".($i+1).".ID";
				}
				
				$where[] = "(p".$back.".Category1 = '$value' OR p".$back.".Category2 = '$value')";
				
				$from  = implode(',',$from);
				$where = implode(' AND ',$where);
				
				$test[] = "((SELECT COUNT(t.ID) FROM $from WHERE $where) = 1)";
			
			} elseif ($ahead > 0) {
			
				$from  = array('textpattern AS c1');
				$where = array('t.ID = c1.ParentID');
				
				for ($i=1; $i < $ahead; $i++) {
					$from[]  = "textpattern AS c".($i+1);
					$where[] = "c".$i.".ID = c".($i+1).".ParentID";
				}
				
				$where[] = "(c".$ahead.".Category1 = '$value' OR c".$ahead.".Category2 = '$value')";
				
				$from  = implode(',',$from);
				$where = implode(' AND ',$where);
				
				$test[] = "((SELECT COUNT(t.ID) FROM $from WHERE $where) = 1)";
				
			} else {
			
				$test[] = "(t.Category1 = '$value' OR t.Category2 = '$value')";
			}
		}
		
		if ($test) $category[] = "(".implode(' AND ',$test).")";
	}
	
	$category = ($category) ? "(".implode($op,$category).")" : "";
	
?>
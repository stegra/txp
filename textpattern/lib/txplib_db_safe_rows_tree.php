<?php
	
	if (!defined('SLASH')) 	define('SLASH','(\/)');
	if (!defined('DSLASH')) define('DSLASH','(\/\/)');
	if (!defined('WORD')) 	define('WORD','(\w+)');
	if (!defined('ISNOT')) 	define('ISNOT','([\-\!])');
	
	// -------------------------------------------------------------------------
	// return a subtree as a one dimensional array
	
	function safe_subtree($context=0,$things='ID',$table='textpattern',$where='1=1') 
	{
		$tree = array();
		
		if (!$context) {
			$context = safe_field('ID',$table,"ParentID = 0 AND Trash = 0");
		}
		
		$rows = safe_rows(
			$things,
			"$table AS p",
			"p.ParentID = $context AND $where",
			0,0);
		
		foreach ($rows as $row) {
			
			$tree[] = $row;
			
			$subtree = safe_subtree($row['ID'],$things,$table,$where);
			
			if ($subtree) {
				$tree = array_merge($tree,$subtree);
			}
		}
		
		return $tree;
	}
	
	// -------------------------------------------------------------------------

	function safe_count_treex($context=0,$path='',$table='textpattern',$where='1=1',$flags='') 
	{
		if (!isset($where['group']) and !preg_match('/\/\//',$path)) {
			
			set_flag($flags,'COUNT');
			
			$rows = safe_rows_treex($context,$path,'COUNT(*) AS `count`',$table,$where,$flags);
			
			return ($rows) ? $rows[0]['count'] : 0; 
		}
		
		set_flag($flags,'SQL');
		set_flag($flags,'COUNT');
		
		$q = safe_rows_treex($context,$path,'t.ID',$table,$where,$flags);
		
		$row = getRow("SELECT COUNT(*) AS `count` FROM ($q) AS q");
		
		return $row['count'];
	}
	
	// -------------------------------------------------------------------------

	function safe_column_treex($context=0,$path='',$things='',$table='textpattern',$where='1=1',$sortcol='',$flags='') 
	{
		set_flag($flags,'COLUMN');
		
		if ($sortcol) $where['sortcol'] = $sortcol;
		
		return safe_rows_treex($context,$path,$things,$table,$where,$flags);
	}
	
	// -------------------------------------------------------------------------
	
	function safe_field_treex($context=0,$path='',$thing='',$table='textpattern',$where='1=1',$flags='') 
	{
		$row = safe_row_treex($context,$path,$thing,$table,$where,$flags);
		
		if ($row) {
			
			return array_shift($row);
		}
	}
	
	// -------------------------------------------------------------------------
	
	function safe_row_treex($context=0,$path='',$things='',$table='textpattern',$where='1=1',$flags='') 
	{
		$where = do_list($where);
		
		$where['limit'] = 1; 
		
		$rs = safe_rows_treex($context,$path,$things,$table,$where,$flags);
		
		if ($rs) {
			
			return nextRow($rs);
		}
	}
	
	// -------------------------------------------------------------------------
	// path: only one path allowed
	
	function safe_rows_treex($context=0,$path='',$things='ID',$table='textpattern',$where='1=1',$flags='') 
	{
		global $pretext, $dump, $thisarticle;
		
		// -------------------------------------------------------------
		
		$tables = do_list($table);
		$table  = $tables[0];
		
		$in_where = do_list($where);
		
		// -------------------------------------------------------------
		
		$flags = do_list($flags);
		
		$debug 		  	= in_array('DEBUG',$flags);
		$return_sql   	= in_array('SQL',$flags);
		$return_count 	= in_array('COUNT',$flags);
		$return_context = in_array('CONTEXT',$flags);
		$return_column  = in_array('COLUMN',$flags);
		
		// -------------------------------------------------------------
		// remove spaces and trailing slashes from path
		
		$path = trim(preg_replace('/(.+?)\/+$/',"$1",trim($path)));
		$path = preg_replace('/\s+/','',$path);
		
		// -------------------------------------------------------------
		// disallow more than one double slash in path
		// could result in incorrect matches!
		
		if (substr_count($path,'//') > 1) {
			
			return "Only one double slash '//' is allowed in path."; 
		}
		
		// -------------------------------------------------------------
		// path is absolute or relative 
		
		$path_is_relative = true;
		
		if (str_begins_with($path,'///')) {
			
			$path_is_relative = false;
			$path = substr($path,1);
		
		} elseif (str_begins_with($path,'//')) {
		
			$path_is_relative = true;
		
		} elseif (str_begins_with($path,'/')) {
			
			$path_is_relative = false;
			
			// remove leading single slash if followed by other things
			if (strlen($path) > 1) { 
				$path = ltrim($path,'/');
			}
		}
		
		// -------------------------------------------------------------
		// establish the context 
		
		$id = $root_id = fetch("ID",$table,"ParentID",0);
		$level = 1;
		
		if ($path_is_relative) {
			
			if (txpinterface == 'public') {
				$id    = $pretext['id'];
				$level = $pretext['level'];
			} 
			
			if ($context) {
				$id    = $context;
				$level = fetch('Level',$table,'ID',$id);
			}
		}
		
		$context = array(
			'id'    => $id,
			'level' => $level,
			'name'  => fetch('Name',$table,'ID',$id),
			'path'  => array($level => $id)
		);
		
		// re-index the path array with the levels for keys
		
		if ($level > 1) {
			$ids    = do_list(fetch("Path",$table,"ID",$id),'/');
			$ids[]  = $id;
			$levels = range(2,count($ids)+1);
			$ids    = array_values($ids);
			$context['path'] = array_combine($levels,$ids);
		}
		
		if ($return_context) { 
			
			return $context;
		}
		
		// -------------------------------------------------------------
		// process leading parent selections ".."
	 	
	 	if (str_begins_with($path,'../') or $path == '..') {
	 		
	 		$path  = explode('/',$path);
	 		$start = false;
	 		
	 		$new_id    = $context['id'];
	 		$new_level = $context['level'];
	 		$new_path  = $context['path'];
	 		$new_name  = $context['name'];
	 		
	 		while ($path and !$start) {
	 			
	 			if ($path[0] == '..') {
	 				
	 				array_shift($path);
	 				
	 				if ($context['level'] != 1) {
	 				
						if ($new_level <= 2) {
							
							$new_id    = $root_id; 
							$new_level = 1;
							$new_path  = array(1 => $root_id);
						
						} else {
						
							array_pop($new_path);
							
							$new_id     = end($new_path);
							$new_level -= 1;
						}
						
						$new_name = fetch('Name',$table,'ID',$new_id);
					}
					
	 			} else {
	 				
	 				$start = true;
	 			}
	 		}
	 		
	 		$path = implode('/',$path);
	 		
	 		$context['id']	  = $new_id;
	 		$context['level'] = $new_level;
	 		$context['path']  = $new_path;
	 		$context['name']  = $new_name;
	 	}
	 	
	 	// -------------------------------------------------------------
	 	// remove leading self selection "." if followed by more 
	 	
	 	$path = preg_replace('/^\.(\/)/','',$path);
	 	
	 	// -------------------------------------------------------------
	 	// return no results when context + path is beyond deepest level
	 	
	 	if ($path != '/') {
	 	
			$path = explode('/',$path);
			
			$maxlevel = safe_field("MAX(Level)",$table);
			
			if (($context['level'] + count($path)) > $maxlevel) {
				
				$path = '';
				$context['id'] = 0;
			
			} else {
			
				$path = implode('/',$path);
			}
		}
			 	
	 	// -------------------------------------------------------------
		// query parts
		
		$q = array(
			'select' => do_list($things),
			'from'   => $tables,
			'where'  => '1=1',
			'group'	 => '',
			'order'  => '',
			'limit'  => ''
		);
		
		foreach($q as $name => $value) {
			
			if (isset($in_where[$name])) {
				$q[$name] = $in_where[$name];
				unset($in_where[$name]);
			}
		}
		
		$q = parse_where($q);
		
		// exclude the trash and trashed items
		$q['where']['trash']   = "Type != 'trash'";	
		$q['where']['trashed'] = "Trash = 0";
		
		// -------------------------------------------------------------
		// simple cases 
		
		$where = array();
		
		if ($path == '') {
			
			$where[] = "1=1";
		
		} elseif ($path == '.') {
			
			$where[] = "t.ID = ".$context['id'];
		
		} elseif ($path == '/') {
		
			$where[] = "t.ID = ".$context['id'];
		}
		
		elseif ($path == '*') {
			
			$where[] = "t.ParentID = ".$context['id'];
		}
		
		elseif ($path == '//') {
		
			// is this valid or useful?
		}
		
		elseif ($path == '//*') {
		
			if ($context['level'] == 1) {
				
				$where[] = "t.ParentID != 0";
			
			} else {
				
				foreach ($context['path'] as $level => $id) {
				
					$where[] = "t.P".$level." = ".$id;
				}
			}
		
		} elseif ($path == '///*') {
			
			$where[] = "t.ParentID != 0";
		}
		
		// -------------------------------------------------------------
		// ancestor selections
		
		if ($path == '...') {
		
			$id_path = fetch('Path',$table,'ID',$context['id']);
			
			if ($id_path) {
				
				$id_path = explode('/',$id_path);
					
				$where[] = "(ID IN (".in($id_path).'))';
			}
		}
	
		// -------------------------------------------------------------
		// child selections
		
		if (!defined('SELVALUE')) 
			  define('SELVALUE','((\!?[\w\*]+\:)?\!?[\w\*]+)');
		
		if (!$where) {
			
			if (preg_match('/^'.DSLASH.'?'.SELVALUE.'$/',$path,$matches)) {
				
				$where['id'] = "ParentID = ".$context['id'];
				
				// - - - - - - - - - - - - - - - - - - - - - - - - -
				// name and optional namespace value
				
				$value = explode(':',$matches[2]);
				$value = array_reverse($value);
				
				$name = array_shift($value);
				$op   = comparison($name);
				$val  = ltrim($name,'!');
				$op   = ($val == '*') ? '!=' : $op;
				$where['name'] = "Name $op '$val'";
				
				if ($namespace = array_shift($value)) {
					$op  = comparison($namespace);
					$val = ltrim($namespace,'!');
					$op  = ($val == '*') ? '!=' : $op;
					$where['name'] .= n."   AND Class $op '$val'";
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - -
				
				if (str_begins_with($path,'//')) {
					
					unset($where['id']);
					
					if ($context['level'] > 1) {
					
						foreach ($context['path'] as $level => $id) {
					
							$where[] = "P".$level." = ".$id;
						}
					}
				}
			}
		}
		
		// -------------------------------------------------------------
		// descendent selections
		
		if (!$where) {
			
			$path  = explode('/',$path);
			$level = $context['level'];
			$skips = 0;
			$prev  = '';
			
			// check for starting double slash 
			
			if (count($path) >= 3) {
				if ($path[0] == '' and $path[1] == '') {
					array_shift($path); // remove first empty item
				}
			}
			
			array_unshift($path,end($context['path']));
			array_shift($q['from']);
			
			$from = array();
			
			foreach ($path as $key => $value) {
				
				$first = ($key == 0);
				$last  = (!isset($path[$key+1]));
				
				if ($value == '') { 
					$skips += 1;
					$prev  = '';
					continue;
				}
				
				// - - - - - - - - - - - - - - - - - - - - -
				// JOIN 
				
				$t = (!$last) ? "t".$level : "t";
				
				$from[$level] = str_pad("$table AS `$t`",strlen($table)+8,' ');
				
				if (!$first) {
					
					if ($prev != '') {
					
						$ID       = "t".($level-1).".ID";
						$ParentID = "$t.ParentID";
						
						$from[$level] .= " ON $ID = $ParentID";
					
					} else {
						
						$on = array();
						$x  = ($context['level'] > 1) ? $context['level'] : 2;
						
						for ($v = 2; $v < $level && $x < $level; $v++, $x++) {
							
							$ID = "t".$x.".ID";
							$P  = "$t.P".$v;
							
							$on[] = "$ID = $P";
						}
						
						if ($skips >= 2) {
						
							array_pop($on);
							
							// WARNING: if allowed, more than two skips 
							// may give incorrect results!
						}
						
						if (!count($on)) {
							
							// exceptional case when path starts with double slash
							
							$on[] = "t1.ID != t2.ID";
						}
						
						$indent = str_repeat(' ',(strlen($table) + 12));
						 
						$from[$level] .= " ON ".implode(n.$indent.' AND ',$on);
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - -
				// WHERE 
				
				// select by the level
				
				$Level = str_pad("$t.Level",8,' ');
				$is    = ($skips) ? ">=" : "=";
				$where[$level] = array('level' => "$Level $is $level");
				
				if ($first) { 	// and by the id
					
					$ID = str_pad("$t.ID",7,' ');
					
					$where[$level]['id'] = "$ID = $value";
				
				} else { 		// and by the name and optional namespace
				
					$value = explode(':',$value);
					$value = array_reverse($value);
					
					$name = array_shift($value);
					$op   = comparison($name);
					$val  = ltrim($name,'!');
					$op   = ($val == '*') ? '!=' : $op;
					$Name = str_pad("$t.Name",7,' ');
					$where[$level]['name'] = "$Name $op '$val'";
					
					if ($namespace = array_shift($value)) {
						$op  = comparison($namespace);
						$val = ltrim($namespace,'!');
						$op  = ($val == '*') ? '!=' : $op;
						$indent = str_repeat(' ',20);
						$where[$level]['name'] .= n.$indent." AND $t.Class $op '$val'";
					}
				}
				
				$where[$level] = '('.implode(' AND ',$where[$level]).')';
				
				$level += 1;
				$prev = $value;
			}
			
			$q['from'] = array_merge($from,$q['from']);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($skips) {
			
				$q['group'] = "t.ID";
			}
		}
		
		// -------------------------------------------------------------
		// give alias to main table if it does not have it
		
		if ($q['from'][0] == $table) {
		
			$q['from'][0] = "$table AS `t`"; 
		}
		
		// -------------------------------------------------------------
		// add table alias to column names 
		
		$q['where']['trash']   = 't.'.$q['where']['trash'];
		$q['where']['trashed'] = 't.'.$q['where']['trashed'];
		
		if ($q['order']) {
			
			$order = explode(',',$q['order']);
			
			foreach ($order as $key => $item) {
				
				if ($item != 'RAND()') {
				
					list($item_name,$item_dir) = explode(' ',trim($item)); 
					
					if (!preg_match('/\./',$item_name)) {
						
						if ($item_name != 'score') {
							$order[$key] = 't.'.$item_name.' '.$item_dir;
						}
					}
				}
			}
			
			$q['order'] = implode(', ',$order);
		}
		
		// -------------------------------------------------------------
		
		$q['where'] = array_merge($q['where'],$where,$in_where);
		
		// -------------------------------------------------------------
		// query debug
		
		if ($debug) { $dump = $q; }
		
		// -------------------------------------------------------------
		// execute query
		
		extract($q);
		
		$select = implode(',',$select);
		$table  = str_replace(' JOIN LEFT ',' LEFT ',implode(' JOIN ',$from));
		$where  = implode(' AND ',$where).' ';
		$order  = ($order and !$return_count) ? 'ORDER BY '.$order.' ' : '';
		$limit  = ($limit) ? 'LIMIT '.$limit : '';
		$group  = ($group) ? 'GROUP BY '.$group.' ' : '';
		
		if ($return_sql) {
			
			return 'SELECT '.$select.' FROM '.safe_pfx_j($table).' WHERE '.$where.$group.$order.$limit;
		
		} elseif ($return_count) {
			
			return safe_rows($select,$table,$where.$group.$order.$limit,0,0);
		
		} elseif ($return_column) {
			
			$sortcol = (isset($where['sortcol'])) ? $where['sortcol'] : '';
			
			return safe_column($select,$table,$where.$group.$order.$limit,$sortcol);
		
		} else {
			
			return safe_rows_start($select,$table,$where.$group.$order.$limit,0,0);
		}
	}
	
	// -------------------------------------------------------------------------
	
	function parse_where($query) {
	
		$parse = preg_split('/\s+/',$query['where']);
		
		$query['where'] = array();
		
		while ($parse) {
			
			if ($parse[0] == "ORDER") {
			
				array_shift($parse);
				array_shift($parse);
				$query['order'] = array_shift($parse);
			
			} elseif (in_list($parse[0],"ASC,DESC")) {
				
				$query['order'] .= ' '.array_shift($parse);
				
			} elseif ($parse[0] == "LIMIT") {
			
				array_shift($parse);
				$query['limit'] = array_shift($parse);
			
			} else {
				
				$query['where'][] = array_shift($parse);
			}
		}
		
		$query['where'] = array(implode(' ',$query['where']));
		
		return $query;
	}

?>

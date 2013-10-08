<?php

	if (!defined('SLASH')) 	define('SLASH','\/');
	if (!defined('DSLASH')) define('DSLASH','\/\/');
	if (!defined('WORD')) 	define('WORD','(\w+)');
	if (!defined('ISNOT')) 	define('ISNOT','[\-\!]');
	
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
//	Path
	
	function build_query_path($path,&$tables,$table) 
	{
		global $pretext, $thisarticle;
		
		if (in_list($path,'AND,OR,(,)')) return $path;
		
		include txpath.'/publish/lib/publish_query_builder_path_v6.php';
		
		return $out;
	}
	
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// 	Category
	
	function build_query_category($category) 
	{
		global $c, $article_stack;
	}
	
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Type
	
	function build_query_type($type) 
	{
		$type  = doSlash(do_list($type,'|'));
		$where = array();
		
		foreach ($type as $test) {
			$where[] = "t.`Type` LIKE '$test%'"; 
		}
		
		return '('.implode(' OR ',$where).')';
	}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Image
	
	function build_query_image($image) 
	{
		if ($image) {
		
			return "t.ImageID >= 1";
		}
		
		return "t.ImageID <= 0";
	}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// File
	
	function build_query_file($file,&$tables) 
	{
		switch (strtolower($file)) {
			case 'yes' : ;
			case '1'   : $file = "t.FileID >= 1"; break;
			case '0'   : ;
			case 'no'  : $file = "t.FileID <= 0";
			default    : 
			
				$tables[] = 'txp_file AS `file` ON t.FileID = file.ID';
			
				$file = explode(',',$file);
				$type = "file.Type IN (".in($file).")";
				
				foreach ($file as $key => $item) {
					$file[$key] = '.'.trim($item);
				}
				
				$ext = "file.ext IN (".in($file).")";
				$file = "($type OR $ext)";
		}
		
		return $file;
	}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Status
	
	function build_query_status($status,$searchsticky,$iscustom,$name="t.Status") 
	{
		global $pretext;
		
		if ($pretext['q'] and $searchsticky) {
		
			$status = '4,5';
		
		} elseif (strval($status) == '4|5' or strval($status) == '5|4') {
			
			$status = '4,5';
			
		} elseif ($pretext['id'] and !$iscustom) {
		
			// $status = '4,5';
		}
			
		return "($name IN ($status))";
	}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	function preg_split_att($value) {
		
		$value = preg_replace('/\s+and\s+|(\s+)?,(\s+)?/',' AND ',$value);
		$value = preg_replace('/\s+or\s+|(\s+)?\|(\s+)?/',' OR ',$value);
		
		$arr = preg_split('/(AND|OR|\(|\))/',$value,0,PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($arr as $key => $item) {
			
			$item = trim($item);
			
			if (!strlen($item)) 
				unset($arr[$key]);
			else
				$arr[$key] = $item;
		}
		
		return $arr;
	}

?>
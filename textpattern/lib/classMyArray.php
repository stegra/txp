<?php

	class MyArray {
		
		var $array = array();	// the array
		var $index = array();	// the index
		var $arr   = array(); 	// the array or the index
		
		// -------------------------------------------------------------
		
		function MyArray(&$val='') 
		{	
			if (is_array($val)) {
			
				$this->array =& $val;
			
			} elseif (strlen($val)) {
				
				$this->array = explode(',',$val);
			}
			
			$this->make_index($this->array);
			$this->sort_index();
		}
		
		// -------------------------------------------------------------
		
		function length($path) 
		{	
			$this->set_array($path);
			
			if (isset($this->arr[$path])) { 
				
				if (is_array($this->arr[$path])) {
					return count($this->arr[$path]);
				} else {
					return 1;
				}
			}
			
			return false;
		}
		
		// -------------------------------------------------------------
		
		function is_set($path) 
		{	
			$this->set_array($path);
			
			return isset($this->arr[$path]);
		}
		
		// -------------------------------------------------------------
		
		function get($path,$default='') 
		{	
			$this->set_array($path);
			
			if (isset($this->arr[$path])) {
				
				$value = $this->arr[$path];
				
				if (is_array($value) and $value) return $value;
				if (is_bool($value)) return $value;
				if (is_int($value))  return $value;
				if (strlen($value))  return $value;
			}
			
			return $default;
		}
		
		// -------------------------------------------------------------
		
		function push($path,$value) 
		{	
			$this->set_array($path);
			
			if (isset($this->arr[$path])) {
				
				if (is_array($this->arr[$path])) {
					
					$this->arr[$path][] = $value;
					
					$this->make_index($this->arr[$path],$path);
					$this->sort_index();
				}
			}
		}
		
		// -------------------------------------------------------------
		
		function set($path,$value) 
		{	
			$this->set_array($path);
			
			if (isset($this->arr[$path])) {
				
				// set existing value
				
				$this->arr[$path] = $value;
				
				if (is_array($value)) {
				
					$this->make_index($value,$path);
					$this->sort_index();
				}
			
			} else {
				
				// add new value
				
				$path = explode('/',$path);
				$new  = array_pop($path);
				$path = implode('/',$path);
				
				$this->set_array($path);
				
				if (isset($this->arr[$path])) {	
					
					if (is_array($this->arr[$path])) {
						
						$this->arr[$path][$new] = $value;
						
						$this->make_index($this->arr[$path],$path);
						$this->sort_index();
					}
				}
			}
		}
		
		// -------------------------------------------------------------
		
		function un_set($path) 
		{	
			$this->set_array($path);
			
			if (isset($this->arr[$path])) {
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				unset($this->arr[$path]);
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				foreach ($this->index as $key => $item) {
					
					if (str_begins_with($key,$path.'/')) {
						
						unset($this->index[$key]);
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - -
				
				$path = explode('/',$path);
				$last = array_pop($path);
				$path = implode('/',$path);
					
				if ($path) {
					
					$this->set_array($path);
					
					if (isset($this->arr[$path][$last])) {
						
						unset($this->arr[$path][$last]);
					}
				}
			}
		}
		// -------------------------------------------------------------
		
		function view() 
		{
			pre($this->array);
		}
		
		function view_index() 
		{	
			pre($this->index);
		}
		
		// -------------------------------------------------------------
		
		function make_index(&$arr,$key=array()) 
		{	
			if (!is_array($key)) { 
				$key = explode('/',$key);
			}
			
			foreach($arr as $i => $item) {
			
				$key[] = $i;
				
				if (is_array($item)) {
					
					$this->make_index($arr[$i],$key,$arr);
				
				} else {
					
					// add non-array leaf item
					 
					$this->index[implode('/',$key)] =& $arr[$i];
					
					// add parent array if is at least 2 levels deep
					
					$parent_key = array_slice($key,0,-1);
					
					if (count($parent_key) >= 2) {
						
						$this->index[implode('/',$parent_key)] =& $arr;
					}
				}
				
				array_pop($key);
			}	
		}
		
		// -------------------------------------------------------------
		// determine whether to use the index of the array 
		// or the array itself
		
		function set_array($path) {
				
			if (count(explode('/',$path)) == 1) {
			
				// if path is at level 1 use the array
				
				$this->arr =& $this->array;
			
			} else {
			
				// otherwise use the index
				
				$this->arr =& $this->index;
			}
		}
		
		// -------------------------------------------------------------
		
		function sort_index() 
		{	
			uksort($this->index,array('self','cmp'));
		}
		
		function cmp($a,$b)
		{	
			$a = explode('/',$a);
			$b = explode('/',$b);
			$a = array_map(array('self','pad'),$a);
			$b = array_map(array('self','pad'),$b);
			$a = implode('/',$a).'/';
			$b = implode('/',$b).'/';
			
			return strcasecmp($a,$b);
		}
		
		function pad($str)
		{
			return str_pad($str,10,'0',STR_PAD_LEFT);
		}
	}

// -----------------------------------------------------------------------------

	function testMyArray() 
	{
		$arr = array(
			'1' => array(
					'A' => array('OK1','OK2'),
					'B' => array('OK3','OK4'),
					'C' => array('OK5','OK6')
					),
			'2' => array('X','Y','Z')
		);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		$test = new MyArray($arr);
		
		pre('---------------------------------------------');
		pre('Array');
		
		$test->view();
		
		pre('---------------------------------------------');
		pre('Index');
		
		$test->view_index();
		
		pre('=============================================');
		
		pre($test->get('1/A/0'));
		pre($test->get('1'));
		
		pre($test->un_set('1/C'));
		
		// pre($test->push('1/C','XXX'));
		// pre($test->push('2','OK'));
		
		// pre($test->set('2/3','XXX'));
		
		// pre($test->set('1/A',array('X1','X2')));
		
		// pre($test->set('1/A/0','X1'));
		// pre($test->get('1/A/0'));
		
		// pre($test->push('1/A','X2'));
		
		// pre($test->set('1/A/2','X2'));
		// pre($test->get('1/A/2'));
		
		// pre($test->is_set('1/C'));
		// pre($test->un_set('1/C'));
		
		// pre($test->un_set('1/C'));
		
		// pre($test->length('1'));
		
		pre('=============================================');
		pre('Array');
		
		$test->view();
		
		pre('---------------------------------------------');
		pre('Index');
		
		$test->view_index();
		
		pre('---------------------------------------------');
		pre('arr');
		
		pre($arr);
	}

// -----------------------------------------------------------------------------

	// testMyArray();

?>
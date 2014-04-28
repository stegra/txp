<?php

// -------------------------------------------------------------
	// deprecated in 4.2.0
	function getAtt($name, $default=NULL)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'lAtts')), E_USER_NOTICE);
		global $theseatts;
		return isset($theseatts[$name]) ? $theseatts[$name] : $default;
	}

// -------------------------------------------------------------
	// deprecated in 4.2.0
	function gAtt(&$atts, $name, $default=NULL)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'lAtts')), E_USER_NOTICE);
		return isset($atts[$name]) ? $atts[$name] : $default;
	}

// -------------------------------------------------------------
// change: allow custom field names without 'custom.' prefix

	function lAtts($pairs, &$atts, $warn=1)
	{
		global $txp_current_tag,$production_status,$thisarticle,$dump;
		
		foreach($atts as $att_name => $att_value) {
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			// if value contains a variable 
			// example: category="$myvar" 
			
			if (preg_match_all('/\$(([a-z0-9\.\_]+\b)?)/',$att_value,$matches)) {
				
				foreach($matches[1] as $var_name) {
					
					$new_var = '';
					
					if ($var_name == 'this') {
						
						if ($att_name == 'parent' 
							or $att_name == 'id' 
							or $att_name == 'value') {
							
							$new_var = 'thisid';
						} else {
							$new_var = $att_name;
						}
					}
					
					$var_value = get_var_value($var_name,$new_var);
					
					if (!is_null($var_value)) { 
					
						if ($att_name == 'path') {
							
							$var_value = preg_replace('/[^a-z0-9\-\/\.\*]/','',$var_value);
						
						} elseif (!str_begins_with($var_name,'txp.')) {
						
						 // $var_value = make_name($var_value);
						 // $var_value = doSlash(strtolower(trim($var_value)));
						 	$var_value = doSlash(trim($var_value));
						}
						
						$att_value = preg_replace('/\$'.$var_name.'/',$var_value,$att_value);
					}
				}
			} 
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$name  = $att_name;
			$value = $att_value;
			$found = false;
					
			if (substr($name,0,7) == 'parent.') {
									  
				$name = substr($name,7);
				
				if (array_key_exists($name, $pairs)) {
				
					$pairs['parent.'.$name] = ($value or $value === '0') ? $value : '!*';
					
					$found = true;
				
				} elseif (array_key_exists('custom.'.$name, $pairs)) {
				
					$pairs['parent.custom.'.$name] = ($value or $value === '0') ? $value : '!*';
				
					$found = true;
				}
				
			} elseif (substr($name,0,6) == 'child.') {
			
				$name = substr($name,6);
				
				if (array_key_exists($name, $pairs)) {
					
					$pairs['child.'.$name] = $value;
					
					$found = true;
				
				} elseif (array_key_exists('custom.'.$name, $pairs)) {
				
					$pairs['child.custom.'.$name] = $value;
				
					$found = true;
				}
					
			} elseif (substr($name,0,4) == 'var.') {
									  
				variable(array(
					'name'  => substr($name,4),
					'value' => $value
				));
				
				$found = true;
			
			} elseif (substr($name,0,2) == 'q.') {
					
						  
				variable(array(
					'name'  => $name,
					'value' => $value
				));
				
				$found = true;
				
			} elseif (array_key_exists($name, $pairs)) {
					
				$pairs[$name] = ($value or $value === '0') ? $value : '!*';
				
				$found = true;
					
			} elseif (array_key_exists('custom.'.$name, $pairs)) {
					
				$pairs['custom.'.$name] = ($value or $value === '0') ? $value : '!*';
				
				$found = true;
			}
			
			if (!$found and $warn and $production_status != 'live') {
					
				// inspect(gTxt('unknown_attribute', array('{att}' => $name)));
				
				$tag = htmlentities($txp_current_tag);
				
				// trigger_error(gTxt('unknown_attribute', array('{att}' => $name)));
				
				trigger_error("Unknown attribute '$name' in $tag");
			}
		}
		
		return ($pairs) ? $pairs : false;
	}

// -------------------------------------------------------------
// get variables names in attributes
	
	function get_var_value($name,$new='') {
	
		global $tags,$variable,$thisarticle,$pretext,$txptagtrace,$txptrace,$dump;
		
		$var   = $name;
		$name  = ($new) ? $new : $name;
		$value = NULL;
		
		if (preg_match('/^[0-9]+$/',$name)) {
			
			// request url path item
			
			$value = path_tag(array(),'req',intval($name)); 
			
		} elseif (isset($variable[$name])) {
			
			$value = $variable[$name];
		
		} elseif (isset($thisarticle[$name])) {
		
			$value = $thisarticle[$name];
		
		} elseif (isset($thisarticle['custom_fields'][$name])) {
		
			$value = $thisarticle['custom_fields'][$name][0]['value'];
			
		} elseif (str_begins_with($name,'custom.')) {
			
			$name = substr($name,7);
			
			if (isset($thisarticle['custom_fields'][$name])) {
				
				$value = $thisarticle['custom_fields'][$name][0]['value'];
			}
			
		} elseif ($name == 'category') {
		
			$value  = $thisarticle['category1'];
			$value .= ($thisarticle['category2']) ? ','.$thisarticle['category2'] : '';
		
		} elseif ($name == 'article_num') {
			
			$value  = $thisarticle['count'];
		
		} elseif ($name == 'site') {
			
			$value = $pretext['site'];
		
		} elseif (str_begins_with($name,'txp.')) {
		
			$name = substr($name,4);
			
			if (isset($tags[$name])) { 
				$name = $tags[$name];
			}
			
			if (preg_match('/^[0-9]+$/',$name)) {
				
				$value = path_tag(array(),'req',intval($name)); 
			
			} elseif (isset($variable[$name])) {
				
				$value = $variable[$name]; 
			
			} elseif (function_exists($name)) {
				
				$value = $name(array());
			}
			
		} else {
				
			if (isset($pretext[$name]) and $pretext[$name] !== '') {
			
				$value = (preg_match('/txp:article(_custom)? /',end($txptrace))) ? '*' : '';
				$value = ($pretext[$name]) ? $pretext[$name] : $value;
			
			} else {
				
				if ($name == 's') 	 $name = 'section';
				if ($name == 'c') 	 $name = 'category1';
				if ($name == 't') 	 $name = 'name';
				if ($name == 'this') $name = 'thisid';
				
				if (isset($thisarticle[$name])) {
					
					if ($name == 'category1') { 
						$value  = $thisarticle['category1'];
						$value .= ($thisarticle['category2']) ? ','.$thisarticle['category2'] : '';
					} else {
						$value = $thisarticle[$name];
					}
				
				} else {
					
					$dump[]['error'] = 'Unknown variable '.$name;
				}
			}
		} 
		
	 // return (!is_null($value)) ? strtolower($value) : NULL;
		return (!is_null($value)) ? $value : NULL;
	}

// -----------------------------------------------------------------------------

	function makeWhereSQL($value,$mytest) {
		
		return processAtt($value,$mytest,'sql');
	}
	
	function evalAtt($value,$mytest) {
		
		return processAtt($value,$mytest,'php');
	}
	
// -----------------------------------------------------------------------------

	function processAtt($value,$mytest,$mode) {
		
		if (is_null($value)) 
			return false;
		
		$mytest = "(".$mytest.")";
		$sql_items  = array();
		$sql_count	= 0;
		
		if ($mode == 'sql') {
			$value = str_replace('(','[',$value);
			$value = str_replace(')',']',$value);
		}
		
		$inner_paren = "\(([^\(]+?)\)";
		
		// inspect("$value is $mytest",'h2');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		while (preg_match('/^(.*)'.$inner_paren.'(.*)/',$mytest,$matches)) {
			
			// inspect("test: $mytest");
			
			$result = NULL;
			$types  = array(',',' or ',' and ');
			
			if ($mytest == '(FALSE)') $result = 'FALSE';
			if ($mytest == '(TRUE)')  $result = 'TRUE';
			
			$before = $matches[1];
			$mytest = $matches[2];
			$after  = $matches[3];
			
			// inspect("test: $mytest");
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// single value
			
			if (!preg_match('/'.implode('|',$types).'/',$mytest)) {
				
				// inspect("SINGLE");
				
				if ($mode == 'php')
					$result = do_php_test($mytest,$value) ? 'TRUE' : 'FALSE';
				
				if ($mode == 'sql')
					$result = make_sql_where_item($mytest,$value);
				
				// inspect("result: $result");
				
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// multiple values
			
			while (is_null($result) and count($types)) {
			
				$type = array_shift($types);
				
				$list = do_list($mytest,$type);
				
				if (count($list) > 1) {
					
					// multiple values
					
					if ($type == ',') {
						
						// in a list
						
						// inspect("IN LIST");
						
						if ($mode == 'php')
							$result = do_php_test($list,$value) ? 'TRUE' : 'FALSE';
						
						if ($mode == 'sql') {
							$sql_items[] = make_sql_where_item($list,$value);
							$result = "{".$sql_count++."}";
						}
						
					} else {
						
						// and/or
						
						// inspect("AND/OR");
						
						foreach ($list as $i => $testval) {
						
							if ($mode == 'php')
								$list[$i] = do_php_test($testval,$value) ? 'TRUE' : 'FALSE';
							
							if ($mode == 'sql') {
								$sql_items[] = make_sql_where_item($testval,$value);
								$list[$i] = "{".$sql_count++."}";
							}
						}
						
						if ($mode == 'php') {
							
							$eval = implode($type,$list);
							$result = eval("return (".$eval.") ? 'TRUE' : 'FALSE';");
							// inspect("test: ($eval) is $result");
						} 
						
						if ($mode == 'sql') {
							
							$result = '('.implode(strtoupper(" $type "),$list).')';
						}
					}
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$mytest = $before.$result.$after;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		// inspect("test: $mytest");
		
		if ($sql_items) {
			
			$sql_items[] = $mytest;
			
			foreach($sql_items as $key => $value) {
				
				if (preg_match_all('/\{(\d+)\}/',$value,$matches)) {
					
					foreach($matches[1] as $i) {
						
						$replacement = (count($matches[1]) > 1) 
							? '('.$sql_items[$i].')'
							: $sql_items[$i];
						
						$value = preg_replace('/\{'.$i.'\}/',$replacement,$value);
						
						unset($sql_items[$i]);
					}
					
					$sql_items[$key] = $value;
				}
			}
			
			$mytest = array_pop($sql_items);
		}
		
		if ($mode == 'sql') {
			$mytest = str_replace('[','(',$mytest);
			$mytest = str_replace(']',')',$mytest);
		}
		
		// inspect("test out: $mytest");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return ($mode == 'php') ? ($mytest == 'TRUE') : '('.$mytest.')';
	}

// -----------------------------------------------------------------------------

	function do_php_test($testval,$val) {
	
		$test = NULL;
		
		$char_ops = implode('|',array_map('preg_quote',array(
			'>=','<=','<','>','=','!','!=')));
		
		if ($val !== true and $val !== false) { 
			$val = strtolower($val);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!is_array($testval)) { 
			
			$op = comparison($testval);
			
			$testval = trim(strtolower($testval));
			
			if (preg_match('/^\$[a-z]+$/',$testval)) {
				$testval = ($val == false) ? 'asdfghjkl'.rand(1000,9999) : '';
			}
			
			// inspect("testval: $op '$testval'");
			
		} else {
			
			$testval = array_map('strtolower',$testval);
			
			$op      = 'in';
			$test    = in_array($val,$testval); 
			
			$testval = array_map('string_quote',$testval);
			$testval = '('.in($testval).')';	
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (is_null($test) and preg_match('/^('.$char_ops.')$/',$op)) {
			
			switch ($op) {
				case '='  : $test = ($val == $testval); $op = '=='; break;
				case '!=' : $test = ($val != $testval); break;
				case '<'  : $test = ($val <  $testval); break;
				case '>'  : $test = ($val >  $testval); break;
				case '<=' : $test = ($val <= $testval); break;
				case '>=' : $test = ($val >= $testval); break;
			}			
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (is_null($test) and stripos($op,'like') !== false) {
		
			list($op,$type) = explode('/',$op);
		
			$testval = trim($testval,'*');
			
			if ($type == 'begin')  $test = (strpos($val,$testval) === 0);
			if ($type == 'middle') $test = (strpos($val,$testval) >= 0);
			if ($type == 'end')    $test = (strpos(strrev($val),strrev($testval)) === 0);
			
			if ($op == 'not like') $test = !$test;
			
			$op = "$op $type";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($val === true)  
			$val = 'TRUE';
		elseif ($val === false) 
			$val = 'FALSE';
		else
			$val = string_quote($val); 
		
		$testval = string_quote($testval);
		 
		// inspect("($val $op $testval) is " . (($test) ? 'TRUE' : 'FALSE'));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $test;
	}

// -----------------------------------------------------------------------------

	function make_sql_where_item($mytest,$name) {
		
		if ($mytest == '*')
			return '';
			
		if (!is_array($mytest))
			$mytest = array($mytest);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach($mytest as $key => $value) {
				
			$op = strtoupper(comparison($value));
			$op = current(explode('/',$op));
			
			if (count($mytest) > 1) {
				$op = ($op == '=') ? 'IN' : (($op == '!=') ? 'NOT IN' : $op);
			}
			
			$ops[$op][] = $value;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach($ops as $op => $values) {
				
			if (strpos($op,'IN') !== false) {
				
				$values = array_map('string_quote',$values);
				
				$ops[$op] = "$name $op (".in($values).")";
				
			} else {
				
				foreach ($values as $key => $value) {
				
					if (preg_match('/\{\d+\}/',$value)) {
						
						$values[$key] = ($op == '!=') ? "NOT $value" : "$value";
					
					} else {
					
						$values[$key] = "$name $op ".str_replace('*','%',string_quote($value));
					}
				}
				
				$ops[$op] = implode(' OR ',$values);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return implode(' OR ',$ops);
	}
	
// -----------------------------------------------------------------------------
// Remove the comparison operator from the string and return the operator.

	function comparison(&$value) {
		
		$op  = '=';
		$out = $op;
		
		$entities = implode('|',array_map('preg_quote',array(
			'&lt;','&gt;','&gte;','&lte;')));
		
		$text_ops = implode('|',array_map('preg_quote',array(
			'lte','gte','lt','gt','eq','neq','not')));
		
		$char_ops = implode('|',array_map('preg_quote',array(
			'>=','<=','<','>','=','!','!=')));
		
		$text_ops = "($text_ops)(?=[\s\d])";	// followed by a space or a digit
		$minus    = "\-(\s+)?(?=[a-zTF])"; 		// followed by zero or more spaces and a letter
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!is_array($value)) {
		
			$value  = trim($value);
			
			// inspect("comparison value: $value");
			
			$operator = "$entities|$text_ops|$char_ops|$minus";
			$val      = ".+";
			$match    = "/(?:^|\s)(".$operator.")?(".$val.")/";
			
			if (preg_match($match,$value,$matches)) {
				
				// inspect($matches);
				
				$value = trim(array_pop($matches));
				
				$string = trim(trim(trim($matches[1]),'&'),';');
				
				switch ($string) {
					case 'lt'	: $op = '<';  break;
					case 'gt'	: $op = '>';  break;
					case 'lte'	: $op = '<='; break;
					case 'gte'	: $op = '>='; break;
					case '!'	: $op = '!='; break;
					case 'not'	: $op = '!='; break;
					case 'neq'	: $op = '!='; break;
					case '-'	: $op = '!='; break;
					case 'eq'	: $op = '=';  break;
					default		: $op = '=';
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				if ($value == 'FALSE') {
					
					$out = ($op == '!=') ? true : false;
					
					// inspect("(".trim($string.' '.$value).") is " . (($out) ? 'TRUE' : 'FALSE'));
					
					return $out;
				}	
					
				if ($value == 'TRUE') {
					
					$out = ($op == '!=') ? false : true;
					
					// inspect("(".trim($string.' '.$value).") is " . (($out) ? 'TRUE' : 'FALSE'));
					
					return $out;
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// check for wildcards at the beginning or end
				
				if (preg_match('/^(\*[^\*]+?\*)|(\*[^\*]+?)|([^\*]+?\*)$/',$value,$matches)) {
		
					switch (count($matches)) {
						case 4 : $type = 'begin'; break; 
						case 2 : $type = 'middle'; break;
						case 3 : $type = 'end'; break;
					}
					
					if ($op == '=')  $op = "like/$type";
					if ($op == '!=') $op = "not like/$type";
					
					// inspect($op);
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$out = $op;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $out;
	}
	
// -----------------------------------------------------------------------------

	function string_quote($string) {
	
		return (!is_numeric($string)) ? "'$string'" : $string; 
	}

?>
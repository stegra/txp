<?php
	
// =============================================================================
// TODO: parent custom field

	function custom_field($atts,$thing=NULL)
	{
		global $thisarticle, $article_stack, $prefs;
		assert_article();
		
		static $custom_field_options = array();
		
		extract(lAtts(array(
			'name'    => '',
			'escape'  => 'html',
			'default' => '',
			'format'  => '',
			'wraptag' => '',
			'add'	  => 0,
			'parent'  => 0,
			'limit'	  => 1
		),$atts));

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
	 // $out           = array($default);
	    $out		   = array();
	    $name          = strtolower($name);
		$thisid        = $thisarticle['thisid'];
		$alias         = $thisarticle['alias'];
		$table         = $thisarticle['table'];
		$custom_field  = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($parent) {
			
			$custom_field = array(); // TODO
		
		} elseif (isset($thisarticle['custom_fields'][$name])) {
			
			$custom_field = $thisarticle['custom_fields'][$name];
		
		} elseif (preg_match('/^custom_?\d+$/',trim($name))) {
			
			$name = preg_replace('/(custom)(\d+)/',"$1_$2",trim($name));
			
			$custom_field = array(
				0 => array(
					'value' => safe_field($name,'textpattern',"ID = $thisid"),
					'info'	=> ":::"
				)
			);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$previous = ''; // TEMPORARY FIX for duplicate values
		$count = 1;
		
		foreach ($custom_field as $key => $field) {
			
			// TEMPORARY FIX for duplicate values
			// if ($previous == $field['info']) continue;
			
			if ($limit != '*' and $count > $limit) continue;
			 
			if ($value = $field['value']) { 
				
				$out[$key] = array(
					'value' => $value,
					'label' => $value
				);
				
				$info = explode(':',$field['info']);
					
				if ($info[1] == 'select' and $info[2] == 1 and $format != 'number') {
					
					extract(safe_row("field_id,group_id","txp_content_value",
						"tbl = '$table' AND article_id = $thisid AND field_name = '$name'"));
					
					if (isset($custom_field_options[$group_id.'_'.$field_id])) {
						
						$options = $custom_field_options[$group_id.'_'.$field_id];
						
					} else {	
						
						$options = fetch("Body_html","txp_custom","ID",$field_id);
						$options = explode(n,doStrip($options));
						
						$opt = array();
						foreach($options as $option) {
							$option = preg_split('/\:/',$option,2); 
							$val = trim(array_shift($option)); 
							$label = ($option) ? trim(array_shift($option)) : $val; 
							$opt[$val] = $label;
							// $opt[$val] = $val;
						}
						$options = $opt;
						
						$custom_field_options[$group_id.'_'.$field_id] = $opt;
					}
					
					if (isset($options[$value])) {
						
						$out[$key] = array(
							'value' => $value,
							'label' => $options[$value]
						);
					}
				} 
				
				// TEMPORARY FIX for duplicate values
				// $previous = $field['info'];
				$count += 1;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach($out as $key => $item) {
			
			$value = $item['value'];
			$label = $item['label'];
			
			if ($value or $value === 0 or $value === '0') {
			
				if ($add) {
					
					$value = $value + $add;
				}
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				if ($format == 'link') {
				
					$value = htmlentities($value);
				
				} elseif ($format == 'url') {
					
					$value = make_name($value);
					
				} elseif ($format == 'textile') {
				
					include_once txpath.'/lib/classTextile_mod.php';
					$textile = new TextileMod();
					$value = $textile->TextileThis($value);
				
				} elseif ($format == 'label') {
				
					$value = $label;
				
				} elseif ($format == 'number') {
				
				
				} elseif (strlen($format)) {
				
					if (preg_match('/^(\d\d\d\d\/\d\d\/\d\d|\d\d:\d\d)$/',$value)) {
						$value = date($format,strtotime($value));
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				$value = trim($value);
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				if ($thing) {
					
					$thisarticle['custom_field_value'] = $value;
					$thisarticle['custom_field_label'] = $label;
					
					$article_stack->push($thisarticle);
					
					$value = parse($thing);
					
					$thisarticle = $article_stack->pop();
					
					unset($thisarticle['custom_field_value']);
					unset($thisarticle['custom_field_label']);
					
				} elseif ($wraptag == 'a') {
					
					$href  = (!preg_match('/^(\/|http)/',$value)) ? 'http://'.$value : $value;
					$value = preg_replace('/^http:\/\//','',$value);
					$value = '<a href="'.$href.'">'.$value.'</a>';
				
				} elseif ($wraptag == 'youtube') {
					
					$youtube = new Element;
					
					$value = $youtube->replace(array('','www.youtube.com',$value));
				
				} elseif (in_list($wraptag,'ul,ol')) {
					
					$value = tag($value,'li');
				}
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				$out[$key] = $value;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// default value 
		
		if (!$out and strlen($default)) {
		
			$out[] = $default;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($thing) {
		
			return implode(n,$out);
			
		} elseif (in_list($wraptag,'ul,ol')) {
		
			$out = tag(implode(n,$out),$wraptag);
			$escape = '';
			
		} else {
			
			$out = implode(', ',$out);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return ($escape == 'html' ? htmlspecialchars($out) : $out);
	}

//--------------------------------------------------------------------------
	function custom_field_value($atts)
	{
		global $thisarticle;
		
		extract(lAtts(array(
			'label' => 0
		),$atts));
		
		if (isset($thisarticle['custom_field_value'])) {
			
			return ($label) 
				? $thisarticle['custom_field_label']
				: $thisarticle['custom_field_value'];
		}
	}

//--------------------------------------------------------------------------
	function if_custom_field_value($atts, $thing=NULL)
	{
		$atts['value'] = '*';
		
		return if_custom_field($atts,$thing);
	}
	
//--------------------------------------------------------------------------
	function if_custom_field($atts, $thing=NULL)
	{
		global $thisarticle, $prefs;
		assert_article();
		
		extract(lAtts(array(
			'name'  => '',
			'value' => '',
			'test'  => ''
		),$atts));
		
		$test   = (strlen($value)) ? $value : $test;
		$name   = strtolower($name);
		$thisid = $thisarticle['thisid'];
		$table  = $thisarticle['table'];
		$result = false;
		$values = array();
		$custom = $thisarticle['custom_fields'];
		
		if (isset($atts['parent.name'])) {
			
			$custom = array($name => array(0 => array('value' => '')));
			
			$parent_id = $thisarticle['parent'];
			
			$custom[$name][0]['value'] = safe_field("text_val",
				"txp_content_value",
				"tbl = '$table'
				 AND article_id = $parent_id 
				 AND type = 'article' 
				 AND status = 1"
			);
		}
		
		if (!isset($custom[$name])) {
			
			if (preg_match('/^custom_?\d+$/',trim($name))) {
				
				$name = preg_replace('/(custom)(\d+)/',"$1_$2",trim($name));
			
				$custom[$name] = array(
					'value' => safe_field($name,'textpattern',"ID = $thisid")
				);
			}
		}
		
		if (isset($custom[$name])) {
			
			if (!isset($atts['test']) and !isset($atts['value'])) {
			
				$result = true;
			
			} else {
				
				foreach ($custom[$name] as $field) {
					
					$values[] = $field['value'];
					
					if ($test == '*') {
					
						if (strlen(impl($values))) $result = true;
					
					} elseif (!in_list($test,"NONE,!*")) {	
					
						if (evalAtt($field['value'],$test) == true) {
							$result = true;
						}
					}
				}
				
				if (in_list($test,"NONE,!*")) {
				
					 if (strlen(impl($values)) == 0) $result = true;
				}
			}
		}
		
		return parse(EvalElse($thing, $result));
	}

// -------------------------------------------------------------

	function custom_field_input($atts,$thing=NULL) {
		
		global $thisarticle,$article_stack;
		assert_article();
		
		extract(lAtts(array(
			'name'  => '',
			'other' => ''
		),$atts));
		
		$thisid        = $thisarticle['thisid'];
		$table         = $thisarticle['table'];
		$custom_fields = $thisarticle['custom_fields'];
		$custom_field  = null;
		
		if (isset($custom_fields[$name])) {
		
			$custom_field = $custom_fields[$name][0]['info'];
		
		} else {
			
			$thisid = $article_stack->get("thisid",'..'); 
			$custom_field = $article_stack->get("custom_fields/$name/0/info",'..'); 
		}
		
		if ($custom_field) {
			
			$value = ps($name);
			$info  = explode(':',$custom_field);
			$type  = $info[1];
			
			if ($type == 'select') {
				
				$other = ps($other);
				$labels = $info[2];
				
				extract(safe_row("field_id,group_id","txp_content_value",
					"tbl = '$table' AND article_id = $thisid AND field_name = '$name'"));
				
				$options = explode(',',fetch("options","txp_custom","ID",$field_id));
				
				// NOTE: This should be class == 'form' instead!
				// TODO: Add 'class' to $thisarticle array
				
				if ($thisarticle['category1'] == 'form') {
				
					$rows = safe_column(
						"Position,Title",
						"textpattern",
						"ParentID = ".$thisarticle['thisid'].
						" AND Class = 'option'".
						" AND Status IN (4,5)".
						" AND Trash = 0".
						" ORDER BY Position ASC",1);
					
					if (count($rows)) {
						$options = $rows;
					}
				}
				
				foreach($options as $key => $option) {
				
					if ($labels) {
						list($val,$label) = explode(':',$option);
						$options[trim($val)] = trim($label);
					} else {
						$options[trim($option)] = trim($option);
					}
					
					unset($options[$key]);
				}
				
				return 
				 selectInput('custom_'.$name, $options, $value)
				.selectInputOther('custom_'.$name, $options , $other);
			}
			
			if ($type == 'textfield') {
				
				return fInput('text','custom_'.$name,$value,'text');
			}
		}
		
		return '';
	}

// =============================================================================

?>
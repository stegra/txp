<?php

// =============================================================================
// adds a single item to the array

	function item($atts,$thing = NULL) {
		
		global $item_array;
		
		extract(lAtts(array(
			'name'	 => 'items',
			'value'	 => '',
			'joiner' => '' 
		), $atts));
		
		$thing = parse($thing);
		$value = ($thing) ? $thing : $value;
		
		if (!is_array($item_array)) {
			
			$item_array = array();
		}
		
		if (!isset($item_array[$name])) {
			
			$item_array[$name] = array( 
				'joiner' => $joiner,
				'items'  => array()
			);
		}
		
		if (!empty($value)) {
		
			$item_array[$name]['items'][] = trim($value);
		}
	}

// -------------------------------------------------------------
// if array exists add the item to the array
// 		otherwise add the item as the joiner
// if there is no item to add then join all items

	function items($atts,$thing = NULL) {
		
		global $item_array;
		
		extract(lAtts(array(
			'name'	=> 'items',
			'value'	=> ''
		), $atts));
		
		if ($value or $thing) {
			
			if (!is_array($item_array) or !isset($item_array[$name])) {
				
				$thing = parse($thing);
				
				$atts['joiner'] = ($thing) ? $thing : $value;
				$atts['value']  = '';
				
				item($atts);
			
			} else {
			
				item($atts,$thing);
			}
		
		} else {
			
			return join_items($atts);
		}
	}
	
// -------------------------------------------------------------
// join all items in the array
// if no joiner is given then use the one in the array if any

	function join_items($atts,$thing = NULL) {
		
		global $item_array;
		
		extract(lAtts(array(
			'name'	 => 'items',
			'joiner' => '',
			'with'	 => ''
		), $atts));
		
		$joiner = ($with) ? $with : $joiner;
		
		if (is_array($item_array) and isset($item_array[$name])) {
			
			$thing = parse($thing);
			$joiner = ($thing) ? $thing : $joiner;
			
			if (!$joiner) {
				
				$joiner = $item_array[$name]['joiner'];
			}
			
			$items = n.implode($joiner.n,$item_array[$name]['items']).n;
			
			$item_array = null;
			
			return $items;
		}
	}
	
//------------------------------------------------------------------------
	function if_user_agent($atts, $thing) 
	{
		extract(lAtts(array(
			'agents'  => 'other'
		),$atts));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get agent candidates
		
		$agents = explode(',',strtolower(preg_replace('/\s+/',' ',$agents)));
		if (count($agents) == 1) $agents = explode(' or ',$agents[0]);
		
		$list = array();
		
		foreach($agents as $key => $value) {
			
			$value = explode(' ',trim($value));
			
			$list[$key]['name']     = $value[0];
			$list[$key]['version']  = 0;
			$list[$key]['range']    = 'gte';
			
			if (isset($value[2])) {
				
				$list[$key]['version'] = $value[2];
				$list[$key]['range']   = $value[1];
			
			} elseif (isset($value[1])) {
			
				$list[$key]['version'] = $value[1];
			}
		}
		
		$agents = $list;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get this agent name & version
		
		$htp_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$this_agent = array();
		
		if (strpos($htp_user_agent,'msie') == true)		$this_agent['msie'] = 0;
		if (strpos($htp_user_agent,'chrome') == true)	$this_agent['chrome'] = 0;
		if (strpos($htp_user_agent,'safari') == true)	$this_agent['safari'] = 0;
		if (strpos($htp_user_agent,'webkit') == true)	$this_agent['webkit'] = 0;
		if (strpos($htp_user_agent,'opera') == true)	$this_agent['opera'] = 0;
		if (strpos($htp_user_agent,'firefox') == true)	$this_agent['firefox'] = 0;
		if (strpos($htp_user_agent,'netscape') == true)	$this_agent['netscape'] = 0;
		if (strpos($htp_user_agent,'mozilla') == true)	$this_agent['mozilla'] = 0;
		
		// get this agent version
		
		foreach ($this_agent as $name => $version) {
			
			if ($name == 'safari') {
				$pattern = "/version\/(\d+\.\d+)/";
			} else {
				$pattern = "/".$name."[\s\/](\d+\.\d+)/";
			}
			
			if (preg_match($pattern,$htp_user_agent,$matches)) {
				
				$this_agent[$name] = $matches[1];
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$test = false;
		
		while (!$test and count($agents)) {
		
			extract(array_shift($agents));
			
			if (isset($this_agent[$name]) and !$test) {
			
				switch ($range) {
				
					case 'gte' : if ($this_agent[$name] >= $version) $test = true; break;
					case 'gt'  : if ($this_agent[$name] >  $version) $test = true; break;
					case 'lte' : if ($this_agent[$name] <= $version) $test = true; break;
					case 'lt'  : if ($this_agent[$name] <  $version) $test = true; break;
					case 'eq'  : if ($this_agent[$name] == $version) $test = true; break;
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		return parse(EvalElse($thing, $test));
	}

//------------------------------------------------------------------------
// search and replace using regular exppressions

	function replace($atts, $thing)
	{
		extract(lAtts(array(
			's'  => '',			// search string
			'r'  => '',			// replace string
			's2' => '',
			'r2' => '',
			's3' => '',
			'r3' => ''
		),$atts));
		
		$out = trim(parse($thing));
		
		$s   = preg_replace('/\//', '\/', $s);
		$out = preg_replace('/'.$s.'/', $r, $out);
	
		if ($s2 and $out) { 
			$s2  = preg_replace('/\//', '\/', $s2);
			$out = preg_replace('/'.$s2.'/', $r2, $out);
		}
		
		if ($s3 and $out) {
			$s3  = preg_replace('/\//', '\/', $s3);
			$out = preg_replace('/'.$s3.'/', $r3, $out);
		}
		
		return $out;
	}

// -------------------------------------------------------------
	function url_encode($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		
		extract(lAtts(array(
			'to'   => 'UTF-16',
			'from' => 'UTF-8'
		),$atts));
		
		$text = trim(parse($thing));
		
		$text = mb_convert_encoding($text, $to, $from);
		$text = urlencode($text);
					
		return $text;         
	}     
	
//------------------------------------------------------------------------
	function strip_space($atts, $thing)
	{	
		$thing = preg_replace('/^\s+|\s+$/','',parse($thing));
		return preg_replace('/\s\s+/',' ',$thing);
	}

//------------------------------------------------------------------------
	function split_words($atts, $thing)
	{	
		extract(lAtts(array(
			'tag'  	=> 'span',
			'words' => ''
		),$atts));
		
		$some_words = ($words) ? explode(',',$words) : null;
		
		$words = preg_split('/\s+/',trim(parse($thing)));
		
		if ($some_words) {
			
			$out = array();
			$key = 0;
			
			foreach ($words as $word) {
				
				$lowerword = make_name($word); 
				
				if (in_array($lowerword,$some_words)) {
					
					$key += 1;
					$out[$key] = array($lowerword,$word);
					$key += 1;
				
				} else {
					
					if (isset($out[$key])) {
						$out[$key] .= ' '.$word;
					} else {
						$out[$key] = $word;
					}
				}
			}
			
			foreach ($out as $key => $words) {
				
				if (is_array($words)) {
					$class = 'word '.$words[0];
					$words = $words[1];
				} else {
					$class = 'word-group';
				}
				
				$out[$key] = tag($words,$tag,' class="'.$class.'"');
			}
			
			return implode(' ',$out);
		}
		
		foreach ($words as $key => $word) {
			$class = 'word wd-'.($key+1).' '.make_name($word);
			$words[$key] = tag($word,$tag,' class="'.$class.'"');
		}
		
		return implode(' ',$words);
	}
	
// -----------------------------------------------------------------------------
	function table_row($atts) 
	{
		global $thisarticle;
		assert_article();;
		
		extract(lAtts(array(
			'col' => '5'
		),$atts));
		
		return ($thisarticle['count'] % $col == 0) ? '</tr><tr>' : '';
	}

//------------------------------------------------------------------------
	function n($atts)
	{	
		return n;
	}

//------------------------------------------------------------------------
	function sp($atts)
	{	
		return sp;
	}

//------------------------------------------------------------------------
	function amp($atts)
	{	
		return "&";
	}
	
//------------------------------------------------------------------------
	function line_tag($atts,$thing=NULL)
	{	
		if ($thing) {
			$content = parse($thing).' ';
		} else {
			$content = str_pad('', 120, "- ");
		}
		
		return n.n."<!-- ".$content."-->".n.n; 
	}

//------------------------------------------------------------------------
	function break_tag($atts)
	{	
		return "<br/>"; 
	}

// -----------------------------------------------------------------------------	
	function random($atts)
	{
		extract(lAtts(array(
			'min' => 100000,
			'max' => 999999
		),$atts));
		
		return rand($min,$max);
	}

// -----------------------------------------------------------------------------	
	function word_count($atts,$thing=NULL)
	{
		extract(lAtts(array(
			'group' => 1,
			'min'	=> 0,
			'max'	=> 0,
			'add'	=> 0,
			'name'  => ''
		),$atts));
		
		$count = str_word_count(strip_tags(parse($thing)));
		$count = ceil($count / $group);
		
		$min = intval($min);
		$max = intval($max);
		
		if ($max and $count > $max) $count = $max;
		if ($min and $count < $min) $count = $min;
		
		$count += $add;
		
		if ($name) {
		
			variable(array(
				'name'    => $name,
				'value'   => $count,
				'default' => 1
			));
			
			return;
		}
		
		return $count;
	}

//------------------------------------------------------------------------
// IE Conditional Comment 

	function ie($atts,$thing=NULL)
	{	
		extract(lAtts(array(
			'if' => ''
		),$atts));
		
		return n.'<!--[if '.$if.']>'.$thing.'<![endif]-->'.n;
	}

//------------------------------------------------------------------------
	function pretext_tag($atts)
	{
		global $pretext,$production_status;
		
		if ($production_status != 'live' or 
		   ($production_status == 'live' and PREVIEW)) {
			   
			return '<pre style="border-bottom: 1px dotted grey;">'.array_to_string($pretext).'</pre>';
		}
	}
	
// =============================================================================

?>

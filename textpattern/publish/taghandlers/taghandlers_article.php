<?php

// =============================================================================
// change: use pretext id for test

	function if_individual_article($atts, $thing)
	{
		global $is_article_list, $id;
		return parse(EvalElse($thing, ($is_article_list == false or $id)));
	}

// -------------------------------------------------------------
	function if_article_list($atts, $thing)
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == true)));
	}

//--------------------------------------------------------------------------------------
	function before($atts, $thing)
	{
		global $thisarticle;
		
		extract(lAtts(array(
			'step'  => 1,
			'limit' => 0
		),$atts));
		
		$count  = $thisarticle['count'];
		$tables = $thisarticle['query']['tables'];
		$where  = $thisarticle['query']['where'];
		
		extract($thisarticle['query']);
		
		$offset = $count - $step - 1;
		$limit = (!$limit) ? 1 : $limit;
		
		if ($offset >= 0) {
		
			$ids = safe_column('t.ID',$tables,$where." LIMIT $offset,$limit");
		
			if (count($ids)) {
			
				$atts = array('id' => implode(',',$ids));
			
				return article($atts,$thing);
			}
		}
		
	 /* $id      = $thisarticle['thisid'];
		$context = $thisarticle['context_id'];
		$before  = $thisarticle['neighbours']['before'];
		
		if ($id != $context) {
			
			$before = array_reverse($before);
			$limit  = (!$limit) ? $step : $limit;
			
			if (count($before)) {
				
				$before = array_reverse(array_slice($before,0,$step));
				$before = array_slice($before,0,$limit);
				
				if ($before[0] == $id) {
				
					return parse($thing);
				}	
			}
		} */
	}

//--------------------------------------------------------------------------------------
	function after($atts, $thing)
	{
		global $thisarticle;
		
		extract(lAtts(array(
			'step'  => 1,
			'limit' => 0
		 // include article
		),$atts));
		
		$count  = $thisarticle['count'];
		$tables = $thisarticle['query']['tables'];
		$where  = $thisarticle['query']['where'];
		
		extract($thisarticle['query']);
		
		$offset = $count + $step - 1;
		$limit = (!$limit) ? 1 : $limit;
		
		$ids = safe_column('t.ID',$tables,$where." LIMIT $offset,$limit");
		
		if (count($ids)) {
		
			$atts = array('id' => implode(',',$ids));
		
			return article($atts,$thing);
		}
		
	 /* $id      = $thisarticle['thisid'];
		$context = $thisarticle['context_id'];
		$after   = $thisarticle['neighbours']['after'];
		
		if ($id != $context) {
			
			$limit = (!$limit) ? $step : $limit;
			
			if ($count = count($after)) {
				
				$after = array_slice($after,$step-1,$count);
				$after = array_slice($after,0,$limit);
				
				if ($after[0] == $id) {
					
					return parse($thing);
				}	
			}
		} */
	}

//--------------------------------------------------------------------------------------
	function if_current_article($atts, $thing)
	{
		global $id,$thisarticle;
		assert_article();
		
		$thisid = $thisarticle['thisid'];
		
		$condition = ($id == $thisid) ? true : false;
		 
		return parse(EvalElse($thing, $condition));
	}
		
// -------------------------------------------------------------	
	function if_first_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		
		return parse(EvalElse($thing, !empty($thisarticle['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		
		return parse(EvalElse($thing, !empty($thisarticle['is_last'])));
	}

// -------------------------------------------------------------
	function if_not_first_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, empty($thisarticle['is_first'])));
	}

// -------------------------------------------------------------
	function if_not_last_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, empty($thisarticle['is_last'])));
	}

// -------------------------------------------------------------
	function article_pos() 
	{
		global $thisarticle; 
		assert_article();
		return ($thisarticle['position']) ? $thisarticle['position'] : '1';
	}

// -------------------------------------------------------------
	function parent_article_pos() 
	{
		global $thisarticle; 
		assert_article();
		
		$pos = fetch('Position',$thisarticle['table'],"ID",$thisarticle['parent']);
		
		return ($pos) ? $pos : '1';
	}

// -------------------------------------------------------------
	function if_article_pos($atts,$thing = NULL) 
	{
		extract(lAtts(array(
			'lt' => '',
			'gt' => '',
			'eq' => '',
		),$atts));
		
		$num = article_pos();
		
		if ($lt) $test = ($num < $lt);
		if ($gt) $test = ($num > $gt);
		if ($eq) $test = ($num == $eq);
		if ($lt && $gt) $test = (($num < $lt) && ($num > $gt));
		
		return parse(EvalElse($thing, $test));
	}

// -------------------------------------------------------------
// alias for article_count tag	

	function article_num($atts) 
	{	
		return article_count($atts);
	}

// -------------------------------------------------------------	
// alias for if_article_count tag	

	function if_article_num($atts,$thing = NULL) 
	{
		return if_article_count($atts);
	}
	
// -------------------------------------------------------------
// alias for article_count tag	

	function if_article_count($atts,$thing = NULL) 
	{
		return article_count($atts,$thing);
	}
	
// -------------------------------------------------------------
// alias for article_total tag	

	function if_article_total($atts,$thing = NULL) 
	{
		return article_total($atts,$thing);
	}

// -------------------------------------------------------------
	function article_count($atts,$thing = NULL) 
	{
		global $thisarticle, $is_article_list;
		global $unique_articles;
		global $thispage;
		
		assert_article();
		
		extract(lAtts(array(
			'pad' 	=> '',
			'lt'  	=> '',
			'gt'  	=> '',
			'eq'  	=> '',
			'mod' 	=> '',
			'group' => ''
		),$atts));
		
		$num = ($is_article_list) ? $thisarticle['count'] : 1;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// start numbering from 1 on each page 
		
		if ($num > 1 and is_array($thispage)) {
			
			$page   = $thispage['pg'];
			$pageby = $thispage['pageby'];
			
			if ($page > 1) {
				$num = $num - (($page - 1) * $pageby);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// group numbers by unique ids or parent ids
		
		if (in_list($group,'parent,thisid')) {
		
			$id = $thisarticle[$group];
			
			if (is_array($unique_articles)) {
				
				if (!isset($unique_articles[$id])) {
					$unique_articles[$id] = count($unique_articles) + 1;
				}
				
			} else {
			
				$unique_articles = array($id => 1);
			}
			
			$num = $unique_articles[$id];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if ($thing) {
			
			$test = ($count > 0);
		
			if ($lt)  $test = ($num < $lt);
			if ($gt)  $test = ($num > $gt);
			if ($eq)  $test = ($num == $eq);
			if ($mod) $test = ($num % $mod);
			if ($lt && $gt) $test = (($num < $lt) && ($num > $gt));
			
			return parse(EvalElse($thing, $test));
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if (is_numeric($pad) and $pad > 0) {
		
			$padsize = $pad;
			$padchar = ' ';
		
		} elseif ($pad) {
		
			$padsize = strlen($pad);
			$padchar = substr($pad,0,1);
		
		} else {
			
			return $num;
		}
		
		$num = str_pad($num,$padsize, $padchar, STR_PAD_LEFT);
		
		return ($padchar == 'o') ? preg_replace('/0/','o',$num) : $num;
	}
	
// -------------------------------------------------------------
	function article_total($atts,$thing = NULL) 
	{
		global $thisarticle;
		assert_article();
		
		extract(lAtts(array(
			'lt' => '',
			'gt' => '',
			'eq' => '',
		),$atts));
		
		$num = $thisarticle['total'];
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if ($thing) {
		
			$test = ($num > 0);
			
			if ($lt) $test = ($num < $lt);
			if ($gt) $test = ($num > $gt);
			if ($eq) $test = ($num == $eq);
			if ($lt && $gt) $test = (($num < $lt) && ($num > $gt));
			
			return parse(EvalElse($thing, $test));
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		return $num;
	}

// -------------------------------------------------------------------------------------
	function article_level($atts='')
	{
		global $thisarticle;
		
		assert_article();
		
		return $thisarticle['level'];
	}

//------------------------------------------------------------------------
	function if_article_level($atts, $thing=NULL)
	{
		global $thisarticle;
		
		extract(lAtts(array(
			'num' => 0,
		),$atts));
		
		$test = evalAtt($thisarticle['level'],$num);
		
		return parse(EvalElse($thing, $test));
	}

//------------------------------------------------------------------------
	function thisarticle($atts)
	{
		global $thisarticle,$production_status;
		
		if ($production_status != 'live' or 
		   ($production_status == 'live' and PREVIEW)) {
			
			return '<pre style="border-bottom: 1px dotted grey;">'.array_to_string($thisarticle).'</pre>';
		}
	}

//------------------------------------------------------------------------
	function article_edit_link($atts,$thing=NULL)
	{
		global $siteurl, $txp_user;
		
		extract(lAtts(array(
			'class'  => '',
			'title'	 => 'Edit',
			'event'  => 'article',
			'table'	 => 'textpattern',
			'path'	 => '',
			'column' => '',
			'open'	 => '',
			'sort'	 => '',
			'focus'  => ''
		),$atts));
		
		if (!$txp_user) return;
		
		if (cs('txp_sitemode_edit') == 'on' or isset($_GET['edit'])) {
			
			if (isset($atts['class'])) unset($atts['class']);
			if (isset($atts['title'])) unset($atts['title']);
				
			if (!$thing) {
				
				$href = 'http://'.$siteurl.article_edit_url($atts);	
				
				$class = (!$class) ? make_name($title) : $class;
				$link  = "Edit <span>&#187;</span>";
			
				return '<a class="edit '.$class.'" title="'.$title.'" href="'.$href.'">'.$link.'</a>';
			
			} else {
				
				$GLOBALS['article_edit_link'] = $atts;
					
				$thing = parse($thing);
				
				unset($GLOBALS['article_edit_link']);
				
				return $thing;
			}
		}
	}

//------------------------------------------------------------------------
	function article_edit_url($atts=null)
	{
		global $thisarticle;
		
		if (isset($GLOBALS['article_edit_link'])) {
		
			$atts = $GLOBALS['article_edit_link'];
		}
		
		extract(lAtts(array(
			'event'  => 'article',
			'table'	 => 'textpattern',
			'path'	 => '',
			'column' => '',
			'open'	 => '',
			'sort'	 => '',
			'focus'  => ''
		),$atts));
		
		$table = ($table != 'textpattern') ? 'txp_'.$table : $table;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$url = '/admin/index.php?&win=new&mini=1';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// event 
		
		if ($column or $sort or $open) $event = 'list';
		
		
		$url .= '&event='.$event;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// step 
		
		$step = 'edit';
		
		if ($event == 'list') $step = 'list';
		
		$url .= '&step='.$step;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// id 
		
		$id = $thisarticle['thisid'];
		
		if ($path) {
			
			// get ids from path 
			
			$path = expl(trim($path,'/'),'/');
			$parentid = $id = ROOTNODEID;
			
			while ($path and $parentid) {
				
				$name = make_name(array_shift($path));
				
				$id = safe_field('ID',$table,
					"Name = '$name' AND ParentID = $parentid AND Trash = 0");
				
				$parentid = ($id) ? $id : 0;
			}
		}
		
		$url .= '&id='.$id;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// sort by column  
		
		if ($sort == 'position') {
			$url .= '&sort=position&dir=asc';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// folders to open 
		
		if ($open) {
		
			$open = expl($open);
		
			foreach ($open as $key => $path) {
				
				$parentid = $id;
				$path = expl($path,'/');
				
				$open[$key] = array();
				
				while ($path and $parentid) {
				
					$name = array_shift($path);
					
					if ($name != '*') {
						
						$open_id = safe_field('ID',$table,
							"Name = '$name' AND ParentID = $parentid AND Trash = 0");
					} else {
						
						$open_id = safe_column('t.ID',"$table AS t JOIN $table AS c ON t.ID = c.ParentID",
							"t.ParentID = $parentid AND t.Trash = 0 AND c.Trash = 0 GROUP BY t.ID");
						
						$open_id = impl($open_id);
					}	
							
					$open[$key][] = $open_id;
					
					$parentid = ($open_id) ? $open_id : 0;
				}
				
				if ($open_id) {
					$open[$key] = impl($open[$key]);
				} else {
					unset($open[$key]);
				}
			}
			
			if ($open) {
				$url .= '&open='.impl($open);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		//	columns to show 
		
		if ($column) {
		
			$column = expl($column);
			
			foreach ($column as $key => $name) {
				
				$name = make_name($name);
				
				$field_id = safe_field('ID','txp_custom',
					"Name = '$name' AND Type NOT IN ('trash','folder') AND Trash = 0");
				
				if ($field_id) {
				
					$column[$key] = 'custom.'.$field_id;
				
				} else {
				
					$column[$key] = $name;
				}
			}
			
			$url .= '&columns='.impl($column);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// focus a form input field
		
		if ($focus) {
			
			$url .= '#'.$focus;
		}
				
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $url;
	}
	
// =============================================================================
	
?>
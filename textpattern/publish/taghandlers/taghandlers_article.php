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
		assert_article();
		
		extract(lAtts(array(
			'pad' => '',
			'lt'  => '',
			'gt'  => '',
			'eq'  => '',
			'mod' => ''
		),$atts));
		
		$num = ($is_article_list) ? $thisarticle['count'] : 1;
		
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
		global $siteurl;
		
		if (PREVIEW) {
			
			if ($thing) {
				
				return parse($thing);
			}
				
			$url = article_edit_url();
				
			return '<a class="edit" href="http://'.$siteurl.'/'.$url.'">Edit &#187;</a>';
		}
	}

//------------------------------------------------------------------------
	function article_edit_url($atts=null)
	{
		global $thisarticle;
		
		return 'admin/index.php?event=article&step=edit&win=new&mini=1&id='.$thisarticle['thisid'];
	}
	
// =============================================================================
	
?>
<?php

// -----------------------------------------------------------------------------
// not a tag function 

	function article_path_test($atts)
	{
		assert_article();
		
		$atts['mode'] = 'article';
		
		return path($atts);
	}
	
// =============================================================================
/*
 * This is the image_max_width_test_1 function line 1.
 * This is the image_max_width_test_1 function line 2.
 */
	function image_max_width_test_1($atts,$thing=NULL) 
    {
    	extract(lAtts(array(
    		'plus' => 0
    	),$atts));
    	
    	echo "OK 1"; // This is comment
    	
    	/* This is another comment */
    	
    	/* 
    		echo "X";
    	*/
    }

// -----------------------------------------------------------------------------
// new

	function article_path_test_2($atts)
	{
		assert_article();
		
		$atts['mode'] = 'article';
		
		return path($atts);
	}

// -----------------------------------------------------------------------------
// new

	function level_test($atts) 
	{
		global $pretext;
		
		return $pretext['level'];
	}

//------------------------------------------------------------------------------

	function if_level_test($atts,$thing=NULL)
	{
		global $pretext;
		
		extract(lAtts(array(
			'num' => 0
		),$atts));
		
		$test = evalAtt($pretext['level'],$num);
		
				// - - - - - - - - - - - - - - - - - - - - - -
				
				$value = trim($value);
				
				// - - - - - - - - - - - - - - - - - - - - - -
		
		return parse(EvalElse($thing, $test));
	}
	
// -----------------------------------------------------------------------------

	function language_test($atts) {
		
		global $lg;
		
		return $lg;
	}
	
// -----------------------------------------------------------------------------
// This is the image_max_height_test_2 function.

	function image_max_height_test_2($atts,$thing=NULL) 
    {
    	extract(lAtts(array(
    		'plus'  => 0,
    		'minus' => 0
    	),$atts));
    	
    	echo "OK 2";
    }

// -----------------------------------------------------------------------------
// This is the image_max_height_test_3 function.
 
	function image_max_height_test_3($atts,$thing=NULL) 
    {
    	extract(lAtts(array(
    		'plus'  => 0,
    		'minus' => 0
    	),$atts));
    	
    	echo "OK 3";
    }

// =============================================================================
// HELPER FUNCTIONS

	function some_other_function_test() 
	{
		global $pretext;
	}

// -----------------------------------------------------------------------------

	function yet_another_function_test() 
	{
		global $pretext;
	}
	
?>
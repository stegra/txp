<?php

class LastEdit
{
	var $category = '';
	
	var $r_size   = '';
	var $r_sizeby = '';
	
	var $t_size   = '';
	var $t_sizeby = '';
	var $t_crop   = '';
	
	// -------------------------------------------------------------------------
	
	function LastEdit($category='')
	{
		$rs = safe_row('*','txp_image_edit',"category='$category'");
		
		if ($rs) {
		
			$this->r_size   = $rs['last_r_size'];
			$this->r_sizeby = $rs['last_r_sizeby'];
			$this->t_size   = $rs['last_t_size'];
			$this->t_sizeby = $rs['last_t_sizeby'];
			$this->t_crop   = $rs['last_t_crop'];
		
		} else {
		
			$this->r_size   = 400;
			$this->r_sizeby = 'longest';
			$this->t_size   = 100; 
			$this->t_sizeby = '';
			$this->t_crop   = 2;
		}
		
		$this->category = $category;
	}
	
	// -------------------------------------------------------------------------
	
	function store()
	{
		if (safe_count('txp_image_edit',"category = '$this->category'")) { 
		
			safe_update('txp_image_edit',
				"last_r_size   = '$this->r_size', 
				 last_r_sizeby = '$this->r_sizeby',
				 last_t_size   = '$this->t_size', 
				 last_t_sizeby = '$this->t_sizeby',
				 last_t_crop   = '$this->t_crop'",
				"category = '$this->category'"
			);
		
		} else {
		
			safe_insert('txp_image_edit',
				"category      = '$this->category',
				 last_r_size   = '$this->r_size', 
				 last_r_sizeby = '$this->r_sizeby',
				 last_t_size   = '$this->t_size', 
				 last_t_sizeby = '$this->t_sizeby',
				 last_t_crop   = '$this->t_crop'"
			);
		}
	}
}

?>
<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.5
	// =========================================================================
	// add `effect` column to txp_image table
	
	if (!column_exists('txp_image','Effect')) {
		
		todo("add `effect` column to txp_image table");
		
		safe_addcol('txp_image','effect',"varchar(16) NOT NULL DEFAULT 'none'",'transparency');
	}
	
	// =========================================================================
	// drop txp_path table
	
	todo("drop table txp_path");
	
	safe_drop('txp_path');
	
?>
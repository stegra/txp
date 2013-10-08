<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.10
	// =========================================================================
	// add `by_parent_class` column to txp_group table
	
	if (!column_exists('txp_group','by_parent_class')) {
		
		todo("add `by_parent_class` column to txp_group table");
		
		safe_addcol('txp_group','by_parent_class',"varchar(128) NOT NULL DEFAULT ''",'by_parent');
	}
?>
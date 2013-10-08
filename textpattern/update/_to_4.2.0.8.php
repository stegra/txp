<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.8
	// =========================================================================
	// add Plural column to txp_category
	
	if (!column_exists('txp_category',"Plural")) {
		
		todo("add `Plural` column to txp_category table");
		
		safe_addcol('txp_category','Plural',"varchar(128) NOT NULL DEFAULT ''",'Title');
	}
?>
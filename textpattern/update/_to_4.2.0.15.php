<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.15
	// =========================================================================
	// ---------------------------------------------------------------------
	// add `Description` column to `textpattern` table
	
	todo("add `Description` column");
	
	if (!column_exists('textpattern','Description')) {
			
		safe_addcol('textpattern','Description',"varchar(512) NOT NULL DEFAULT '' AFTER `Keywords`",'',1);
	}	
	
?>

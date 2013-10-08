<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.4
	// =========================================================================
	// change Categories column to NOT NULL
	
	todo("change Categories column to NOT NULL");
	
	foreach ($tables as $table) {
	
		if (column_exists($table,'Categories')) {
			safe_alter($table,"MODIFY `Categories` varchar(255) NOT NULL DEFAULT ''");
		}
	}
	
	// =========================================================================
	// drop txp_path table
	
	todo("drop table txp_path");
	
	safe_drop('txp_path');
	
?>
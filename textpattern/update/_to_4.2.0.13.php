<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.13
	// =========================================================================
	// add `txp_log_agent` table
	
	if (!table_exists('txp_log_agent')) {
		
		todo("add `txp_log_agent` table");
		
		safe_create("txp_log_agent",array(
			"`id` 		int 			NOT NULL AUTO_INCREMENT",
			"`agent`  	varchar(255) 	NOT NULL DEFAULT ''",
			"`width` 	int 			NOT NULL DEFAULT 0",
			"`count` 	int 			NOT NULL DEFAULT 1",
			"PRIMARY KEY (id)"));
			
		safe_alter('txp_log',"MODIFY COLUMN agent int NOT NULL DEFAULT 0");
	}
	
	
	
?>

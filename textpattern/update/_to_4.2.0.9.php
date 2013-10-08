<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.9
	// =========================================================================
	// make trash of type trash
	
	foreach ($tree_tables as $type => $table) {
		
		if (table_exists($table)) {
		
			$ids = safe_column('ID',$table,
				"Name IN ('trash','TRASH') AND Level = 2 AND Type = ''");
			
			if ($ids) { 
			
				todo("trash type in $table");
			
				foreach ($ids as $id) {
				
					safe_update($table,"Type = 'trash'","ID = $id",1);
				}
			}
		}
	}
?>
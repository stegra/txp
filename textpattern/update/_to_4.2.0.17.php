<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.17
	// =========================================================================
	// add fulltext index for Body and Title
	
	todo("add `body` and 'title' fulltext index");
	
	foreach ($tree_tables as $type => $table) {
	
		if (!index_exists($table,'Body')) {
			
			safe_alter($table,"ADD FULLTEXT INDEX `Body` (`Body`)");
		}
		
		if (!index_exists($table,'Title')) {
			safe_alter($table,"ADD FULLTEXT INDEX `Title` (`Title`)");
		}
	}

?>
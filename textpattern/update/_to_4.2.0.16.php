<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.16
	// =========================================================================
	// add fulltext index `searchfields` for txp_page table
	
	if (!index_exists('txp_page','searchfields')) {
		
		todo("add fulltext index `searchfields` for txp_page table");
		
		safe_alter('txp_page',"ADD FULLTEXT INDEX `searchfields` (`Body`)");
	}
?>

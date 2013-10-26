<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.11
	// =========================================================================
	// add fulltext index `searchfields` for textpattern table
	
	if (!index_exists('textpattern','searchfields')) {
		
		todo("add fulltext index `searchfields` for textpattern table");
		
		safe_alter('textpattern',"ADD FULLTEXT INDEX `searchfields` (`Body`,`Title`)");
	}
?>
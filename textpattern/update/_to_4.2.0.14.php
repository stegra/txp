<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.14
	// =========================================================================
	// ---------------------------------------------------------------------
	// add `Language` column to all tree tables
	
	todo("add `Language` column");
	
	foreach ($tree_tables as $type => $table) {
	
		if (!column_exists($table,'Language')) {
			
			safe_addcol($table,'Language',"varchar(2) NOT NULL DEFAULT '' AFTER `Type`",'',1);
		}
	}
	
	// ---------------------------------------------------------------------
	// add base
	
	if (!pref_exists('languages')) {
	
		$pref = $pref_default;
		$pref['type'] = '1';
		$pref['name'] = 'languages';
		$pref['position'] = '360';
		
		safe_insert('txp_prefs',$pref,1);
	}
	
?>
<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.6
	// =========================================================================
	// convert txp_plugin table
	
	if (!column_exists('txp_plugin',"ParentID")) {
		
		todo("convert table txp_plugin");
		
		$map['author'] 		= 'AuthorID';
		$map['description'] = 'Body';
		$map['load_order']  = 'Position';
		
		convert_table('txp_plugin',$map,'Plugins');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// drop columns
		
		safe_drop('txp_plugin','help');
		safe_drop('txp_plugin','author_uri');
		safe_drop('txp_plugin','code');
		safe_drop('txp_plugin','code_restore');
		safe_drop('txp_plugin','code_md5');
		safe_drop('txp_plugin','flags');
	}
	
	// =========================================================================
	// allow NULL for text_val in txp_content_value
	
	safe_alter('txp_content_value',"MODIFY `text_val` varchar(256) NULL DEFAULT NULL");
	
?>
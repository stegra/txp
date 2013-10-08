<?php

	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
		
	// version 4.2.0.3
	// =========================================================================
	// convert txp_users table
	
	if (!column_exists('txp_users',"ParentID")) {
		
		todo("convert table txp_users");
		
		$map['user_id']  = 'ID';
		$map['RealName'] = 'Title';
		
		convert_table('txp_users',$map,'Users');
		
		safe_update('txp_users',"Type = 'user', AuthorID = 'textpattern', Posted = NOW(), LastMod = NOW()","Type = ''");
		safe_update('txp_users',"privs = 0","Type != 'user'");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$id = safe_field("ID","txp_users","Name = 'steve' AND Title LIKE '%Gratzer%'");
		
		if ($id) {
			
			safe_update("txp_users","Name = 'steffi', Title = 'Stephanie Gratzer'","ID = $id",1);
			
			foreach ($tables as $table) {
				if (column_exists($table,'AuthorID')) {
					safe_update($table,"AuthorID = 'steffi'","AuthorID = 'steve'");
					safe_update($table,"LastModID = 'steffi'","LastModID = 'steve'");
				}
			}
		}
	}

?>
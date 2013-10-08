<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL: http://svn.textpattern.com/current/textpattern/include/txp_diag.php $
$LastChangedRevision: 759 $

*/

	if (!defined('txpinterface')) define("txpinterface", "admin");

//-------------------------------------------------------------

	if ($event == 'utilities') {
		
		require_privs('diag');
		
		doUtilities();
	}

//-------------------------------------------------------------

	function doUtilities() {
		
		$nocache = rand(100000,999999);
		
		echo pagetop('Utilities','');
		
		echo '<iframe border="0" style="width:100%;height:550px;border:0px;" src="utilities/index.php?'.$nocache.'"></iframe>';
		
	}

?>

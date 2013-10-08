<?php	
	
	if (isset($txpcfg['smarty']))
		require $txpcfg['smarty'];
	else
		require txpath.'/lib/smarty/libs/Smarty.class.php';
	
	$smarty = new Smarty();
	$smarty->template_dir = txpath.'/txp_tpl';
	$smarty->compile_dir  = txpath.'/txp_tpl/txp_tpl_c';

?>
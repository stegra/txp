<?php
/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/index.php $
$LastChangedRevision: 3189 $
*/
	define("txpinterface", "public");
	
	$txpath = (!defined('txpath')) ? dirname(__FILE__).'/textpattern' : txpath;
	
	include $txpath.'/index.php';
?>

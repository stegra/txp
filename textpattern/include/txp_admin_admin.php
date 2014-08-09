<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_admin.php $
$LastChangedRevision: 3203 $

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	global $event,$levels;
	
	$priv_levels = array(
		1 => gTxt('publisher'),
		2 => gTxt('managing_editor'),
		3 => gTxt('copy_editor'),
		4 => gTxt('staff_writer'),
		5 => gTxt('freelancer'),
		6 => gTxt('designer'),
		0 => gTxt('none')
	);
		
	if ($event == 'admin') {
		
		require_privs('admin'); 
		
		/* $steps = array_merge($steps,array(
			'admin_multi_edit',
			'admin_change_pageby',
			'author_edit',
			'author_save',
			'author_save_new',
			'change_email',
			'change_pass'
		)); */
	}
	
	include_once txpath.'/lib/txplib_admin.php';
	
// =============================================================================
	function admin_list($message='')
	{	
		global $EVENT, $WIN, $html, $priv_levels;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['columns']) {
		
			$WIN['columns'] = array(
				
				'Title'  	  => array('title' => 'Real Name',  'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	  => array('title' => 'Image',  	'on' => 1, 'editable' => 0, 'pos' => 2),
				'Posted' 	  => array('title' => 'Added', 	    'on' => 0, 'editable' => 0, 'pos' => 3),
				'LastMod'     => array('title' => 'Modified',   'on' => 0, 'editable' => 0, 'pos' => 4),
				'Name' 		  => array('title' => 'User Name',  'on' => 1, 'editable' => 1, 'pos' => 5),
				'privs' 	  => array('title' => 'Privileges', 'on' => 1, 'editable' => 1, 'pos' => 6),
				'pass' 	  	  => array('title' => 'Password',   'on' => 0, 'editable' => 1, 'pos' => 7),
				'email' 	  => array('title' => 'Email',	    'on' => 1, 'editable' => 1, 'pos' => 8),
				'last_access' => array('title' => 'Last login', 'on' => 1, 'editable' => 0, 'pos' => 9),
				
				'Type' 		=> array('title' => 'Type', 	  'on' => 0, 'editable' => 1, 'pos' => 10),
				'Class' 	=> array('title' => 'Class', 	  'on' => 0, 'editable' => 1, 'pos' => 11),
				'Articles'  => array('title' => 'Articles',   'on' => 0, 'editable' => 0, 'pos' => 12),
				'Images'  	=> array('title' => 'Images', 	  'on' => 0, 'editable' => 0, 'pos' => 13),
				'Files'  	=> array('title' => 'Files', 	  'on' => 0, 'editable' => 0, 'pos' => 14),
				'Links'  	=> array('title' => 'Links', 	  'on' => 0, 'editable' => 0, 'pos' => 15),
				'AuthorID'	=> array('title' => 'Author', 	  'on' => 0, 'editable' => 1, 'pos' => 16),
				'Status'	=> array('title' => 'Status',	  'on' => 0, 'editable' => 1, 'pos' => 17),
				'Position'  => array('title' => 'Position',   'on' => 0, 'editable' => 1, 'pos' => 18, 'short' => 'Pos.')
			);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$main_title = safe_field("CONCAT(' &#8250; ',Title)",
			$WIN['table'],"ID = ".$WIN['id']." AND ParentID != 0");
		
		$html = pagetop(gTxt('site_administration').$main_title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$users = new ContentList(); 
		$list = $users->getList();
		
		foreach ($list as $key => $item) {
			
			// format last access date
				
			if ($item['last_access'] != '0000-00-00 00:00:00') { 
				$date = strtotime($item['last_access']);
				$list[$key]['last_access'] = $users->dateFormat($date);
			} else {
				$list[$key]['last_access'] = '';	
			} 
				
			if ($item['Type'] != 'user') {
			
				$list[$key]['privs'] = '';	
				
			} else {
				
				// privileges 
				
				if ($item['EDIT']) {
					
					$list[$key]['privs'] = array(
						'value'   => $list[$key]['privs'],
						'options' => $priv_levels
					);
				
				} else {
					
					$list[$key]['privs'] = get_priv_level($list[$key]['privs']);
				}
				
				// password
				
				if ($item['EDIT']) {
					
					$list[$key]['pass'] = '';
					
				} elseif (isset($list[$key]['pass']) and $list[$key]['pass']) {
					
					$list[$key]['pass'] = str_repeat("&#8226",8);
				}
			}
		}
			
		$html.= $users->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

// -------------------------------------------------------------
	function admin_multi_edit()
	{
		global $WIN,$tables;
		
		$method   = gps('edit_method');
		$selected = gps('selected',array());
		$old	  = array();
		$error	  = '';
		
		// -----------------------------------------------------
		// PRE-PROCESS
		// filtering out invalid actions
		
		if ($method == 'save') {
			
			$selected = array_map('assert_int', $selected);
		
			$old = safe_column("ID,pass,name,email","txp_users","ID IN (".in($selected).")");
		}
		
		// -----------------------------------------------------
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$selected  = $multiedit->selected;
		$changed   = $multiedit->changed;	
		
		// -----------------------------------------------------
		// POST-PROCESS
		
		if ($changed) {
		
			$changed = safe_column("ID","txp_users","ID IN (".in($changed).") AND `Type` = 'user'");
			
			if ($changed) {
				
				if ($method == 'save') {			
					
					$rows = safe_column("ID,Title,name,pass,email","txp_users","ID IN (".in($changed).")");
					
					foreach ($rows as $id => $new) {
						
						$set = array();
						
						$realname = $new['Title'];
						$name     = $new['name'];
						$password = $new['pass'];
						$email    = $new['email'];
						
						if (strlen($name)) {
							
							$oldname = $old[$id]['name'];
							
							if ($name != $oldname) {
								
								$dup = safe_count("txp_users","name = '$name' AND ID != $id AND Type = 'user'");
								
								if ($dup) {
									
									$set[] = "name = '$oldname'";
									$error = "Please enter a unique user name!";
									
								} else {
									
									foreach ($tables as $table) {
										if (column_exists($table,'AuthorID')) {
											safe_update($table,"AuthorID = '$name'","AuthorID = '$oldname'");
											safe_update($table,"LastModID = '$name'","LastModID = '$oldname'");
										}
									}
								}
							}
							
							if (!$error) {
	
								if (strlen($email)) {
								
									if (is_valid_email($email)) {
										
										if (!strlen($password)) {
											
											$password = $old[$id]['pass'];
											
											if (!strlen($password)) {
												
												$password = generate_password(6);
												$set[] = "pass = password(lower('".doSlash($password)."'))";
												send_password($realname,$name,$email,$password);	
												$message = gTxt('password_sent_to').sp.$new['email'];
											
											} else {
											
												$set[] = "pass = '$password'";
											}
											
										} elseif ($new['pass'] != $old[$id]['pass']) {
											
											$password = $new['pass'];
											
											safe_update('txp_users',"pass = password(lower('$password'))","ID = $id");
										}
									
									} else {
										
										$error = "Please enter a valid email address!";
									}
									
								} else {
									
									$error = "Please enter the email address!";
								}
							}
						
						} else {
						
							$error = "Please enter the user name!";
						}
						
						if ($set) {
						
							safe_update("txp_users",implode(',',$set),"ID = $id");
						}
					}
				}
				
				if ($method == 'new') {	
				
					$multiedit->apply('edit',$changed);
				}		
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$WIN['checked'] = $selected;
		
		$message = ($error) ? array($error,E_ERROR) : $message;
		
		admin_list($message);
	}
	
// -------------------------------------------------------------

	function privs($priv = '')
	{
		global $priv_levels;
		return selectInput('privs', $priv_levels, $priv);
	}

// -------------------------------------------------------------

	function get_priv_level($priv)
	{
		global $priv_levels;
		return $priv_levels[$priv];
	}

// -------------------------------------------------------------

?>
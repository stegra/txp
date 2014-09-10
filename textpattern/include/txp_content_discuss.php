<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_discuss.php $
$LastChangedRevision: 3185 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	if ($event == 'discuss') {
	
		require_privs('discuss');
		
		$steps = array_merge($steps,array(
			'ipban_add',
			'ipban_list',
			'ipban_unban'
		));
	}

//-------------------------------------------------------------
	function discuss_list($message = '')
	{
		global $PFX, $EVENT, $WIN, $html;

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['columns']) {
		
			$WIN['columns'] = array(
				
				'Title'  	   => array('title' => 'Title',			'on' => 1, 'editable' => 1, 'pos' => 1),
				'Image'  	   => array('title' => 'Image',			'on' => 1, 'editable' => 0, 'pos' => 2),
				'Posted' 	   => array('title' => 'Posted',		'on' => 1, 'editable' => 0, 'pos' => 3),
				'LastMod'      => array('title' => 'Modified',   	'on' => 0, 'editable' => 0, 'pos' => 4),
				'Class' 	   => array('title' => 'Class', 	   	'on' => 0, 'editable' => 0, 'pos' => 5),
				'Categories'   => array('title' => 'Categories', 	'on' => 0, 'editable' => 1, 'pos' => 6),
				'Author'	   => array('title' => 'Author', 	   	'on' => 1, 'editable' => 1, 'pos' => 7),
				'AuthorID'	   => array('title' => 'Author ID',     'on' => 1, 'editable' => 0, 'pos' => 0),
				'email'	   	   => array('title' => 'E-mail', 	   	'on' => 1, 'editable' => 1, 'pos' => 8),
				'web'	   	   => array('title' => 'Website', 	   	'on' => 0, 'editable' => 1, 'pos' => 9),
				'ip'	   	   => array('title' => 'IP', 	   		'on' => 0, 'editable' => 0, 'pos' => 10),
				'Status'	   => array('title' => 'Status',	   	'on' => 1, 'editable' => 1, 'pos' => 11),
				'article_id'   => array('title' => 'article_id',	'on' => 1, 'editable' => 1, 'pos' => 0),
				'parent_title' => array('title' => 'Regarding',		'on' => 1, 'editable' => 0, 'pos' => 12),
				'Position'     => array('title' => 'Position',		'on' => 0, 'editable' => 1, 'pos' => 13, 'short' => 'Pos.'),
			);
			
			$WIN['columns']['parent_title']['sel'] = "(SELECT p.Title FROM textpattern AS p WHERE t.article_id = p.ID) AS parent_title";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$main_title = safe_field("CONCAT(' &#8250; ',Title)",
			$WIN['table'],"ID = ".$WIN['id']." AND ParentID != 0");
		
		$html = pagetop(gTxt('list_discussions').$main_title,$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$comments = new ContentList();
		$list = $comments->getList();
		
		foreach ($list as $key => $item) {
			
			if ($item['article_id']) {
				$href = 'index.php?event=article&step=edit&id='.$item['article_id'].'&win='.$WIN['winid'];
				$list[$key]['parent_title'] = tag($item['parent_title'],'a',' href="'.$href.'"');
			}
		}
			
		$html.= $comments->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

// -------------------------------------------------------------
	function discuss_multi_edit()
	{
		global $WIN;
		
		$method   = gps('edit_method');
		$selected = gps('selected',array());
		
		// -----------------------------------------------------
		// PRE-PROCESS
		// filtering out invalid actions
		
		// -----------------------------------------------------
		
		$multiedit = new MultiEdit();
		$message   = $multiedit->apply($method,$selected);
		$selected  = $multiedit->selected;
		$changed   = $multiedit->changed;	
		
		// -----------------------------------------------------
		// POST-PROCESS
		
		if ($changed) {
			
			foreach ($changed as $id) {
				
				$article_id = fetch('article_id','txp_discuss','ID',$id);
				
				if ($article_id) {
				
					$count = safe_count('txp_discuss',
						"article_id = $article_id AND Status = 4 AND Trash = 0");
					safe_update('textpattern',"comments_count = $count","ID = $article_id");
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$WIN['checked'] = $selected;
		
		discuss_list($message);
	}
	
// -------------------------------------------------------------
/* 	function discuss_multi_edit()
	{
		//FIXME, this method needs some refactoring

		$selected = ps('selected');
		$method = ps('edit_method');
		$done = array();
		
		return discuss_list("No editing yet!");
		
		if ($selected and is_array($selected))
		{
			// Get all articles for which we have to update the count
			foreach($selected as $id)
				$ids[] = assert_int($id);
			$parentids = safe_column("DISTINCT article_id","txp_discuss","ID IN (".in($ids).")");

			$rs = safe_rows_start('*', 'txp_discuss', "ID IN (".in($ids).")");
			while ($row = nextRow($rs)) {
				extract($row);
				$id = assert_int($discussid);
				$parentids[] = $parentid;

				if ($method == 'delete') {
					// Delete and if succesful update commnet count
					if (safe_delete('txp_discuss', "ID = $id"))
						$done[] = $id;
				}
				elseif ($method == 'ban') {
					// Ban the IP and hide all messages by that IP
					if (!safe_field('ip', 'txp_discuss_ipban', "ip='".doSlash($ip)."'")) {
						safe_insert("txp_discuss_ipban",
							"ip = '".doSlash($ip)."',
							name_used = '".doSlash($name)."',
							banned_on_message = $id,
							date_banned = now()
						");
						safe_update('txp_discuss',
							"Status = ".SPAM,
							"ip = '".doSlash($ip)."'"
						);
					}
					$done[] = $id;
				}
				elseif ($method == 'spam') {
						if (safe_update('txp_discuss',
							"Status = ".SPAM,
							"ID = $id"
						))
							$done[] = $id;
				}
				elseif ($method == 'unmoderated') {
						if (safe_update('txp_discuss',
							"Status = ".MODERATE,
							"ID = $id"
						))
							$done[] = $id;
				}
				elseif ($method == 'visible') {
						if (safe_update('txp_discuss',
							"Status = ".VISIBLE,
							"ID = $id"
						))
							$done[] = $id;
				}

			}

			$done = join(', ', $done);

			if ($done)
			{
				// might as well clean up all comment counts while we're here.
				clean_comment_counts($parentids);

				$messages = array(
					'delete'		=> gTxt('comments_deleted', array('{list}' => $done)),
					'ban'			=> gTxt('ips_banned', array('{list}' => $done)),
					'spam'			=> gTxt('comments_marked_spam', array('{list}' => $done)),
					'unmoderated'	=> gTxt('comments_marked_unmoderated', array('{list}' => $done)),
					'visible'		=> gTxt('comments_marked_visible', array('{list}' => $done))
				);

				update_lastmod();

				return discuss_list($messages[$method]);
			}
		}

		return discuss_list();
	}
*/
//-------------------------------------------------------------
	function discuss_save($ID=0, $multiedit=null)
	{
		global $vars;
		
		$vars[] = 'email';
		$vars[] = 'web';
		
		content_save($ID,$multiedit);
		
		$id = assert_int(gps('ID',0));
		
		update_comments_count(0,$id);
		
		event_edit();
	}

// -------------------------------------------------------------
	function ipban_add()
	{
		extract(gpsa(array('ip', 'name', 'discussid')));
		$discussid = assert_int($discussid);

		if (!$ip)
		{
			return ipban_list(gTxt('cant_ban_blank_ip'));
		}

		$ban_exists = fetch('ip', 'txp_discuss_ipban', 'ip', $ip);

		if ($ban_exists)
		{
			$message = gTxt('ip_already_banned', array('{ip}' => $ip));

			return ipban_list($message);
		}

		$rs = safe_insert('txp_discuss_ipban', "
			ip = '".doSlash($ip)."',
			name_used = '".doSlash($name)."',
			banned_on_message = $discussid,
			date_banned = now()
		");

		// hide all messages from that IP also
		if ($rs)
		{
			safe_update('txp_discuss', "Status = ".SPAM, "ip = '".doSlash($ip)."'");

			$message = gTxt('ip_banned', array('{ip}' => $ip));

			return ipban_list($message);
		}

		ipban_list();
	}

// -------------------------------------------------------------
	function ipban_unban()
	{
		$ip = doSlash(gps('ip'));

		$rs = safe_delete('txp_discuss_ipban', "ip = '$ip'");

		if ($rs)
		{
			$message = gTxt('ip_ban_removed', array('{ip}' => $ip));

			ipban_list($message);
		}
	}

// -------------------------------------------------------------
	function ipban_list($message = '')
	{
		pageTop(gTxt('list_banned_ips'), $message);

		$rs = safe_rows_start('*, unix_timestamp(date_banned) as uBanned', 'txp_discuss_ipban',
			"1 = 1 order by date_banned desc");

		if ($rs and numRows($rs) > 0)
		{
			echo startTable('list').
				tr(
					hCell(gTxt('date_banned')).
					hCell(gTxt('IP')).
					hCell(gTxt('name_used')).
					hCell(gTxt('banned_for')).
					hCell()
				);

			while ($a = nextRow($rs))
			{
				extract($a);

				echo tr(
					td(
						safe_strftime('%d %b %Y %I:%M %p', $uBanned)
					, 100).

					td(
						$ip
					, 100).

					td(
						$name_used
					, 100).

					td(
						'<a href="?event=discuss'.a.'step=discuss_edit'.a.'discussid='.$banned_on_message.'">'.
							$banned_on_message.'</a>'
					, 100).

					td(
						'<a href="?event=discuss'.a.'step=ipban_unban'.a.'ip='.$ip.'">'.gTxt('unban').'</a>'
					)
				);
			}

			echo endTable();
		}

		else
		{
			echo graf(gTxt('no_ips_banned'),' class="indicator"');
		}
	}

//-------------------------------------------------------------
	function discuss_search_form($crit, $method)
	{
		$methods =	array(
			'id'			=> gTxt('ID'),
			'parent'  => gTxt('parent'),
			'name'		=> gTxt('name'),
			'message' => gTxt('message'),
			'email'		=> gTxt('email'),
			'website' => gTxt('website'),
			'ip'			=> gTxt('IP')
		);

		return search_form('discuss', 'list', $crit, $methods, $method, 'message');
	}

// -------------------------------------------------------------
	function discuss_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'visible'     => gTxt('show'),
			'unmoderated' => gTxt('hide_unmoderated'),
			'spam'        => gTxt('hide_spam'),
			'ban'         => gTxt('ban_author'),
			'delete'      => gTxt('delete'),
		);

		return event_multiedit_form('discuss', $methods, $page, $sort, $dir, $crit, $search_method);
	}

//-------------------------------------------------------------
	function short_preview($message)
	{
		$message = strip_tags($message);
		$message = preg_replace("/(\r\n+?)+/"," / ",$message);
		
		$offset = min(150, strlen($message));

		if (strpos($message, ' ', $offset) !== false)
		{
			$maxpos = strpos($message,' ',$offset);
			$message = substr($message, 0, $maxpos).'&#8230;';
		}

		return $message;
	}

//-------------------------------------------------------------
	function discuss_edit_type(&$in,&$html) 
	{
		global $WIN;
		
		extract($in);
		
		$html[1]['excerpt'] = '';
		
		if ($Type != 'folder') {
		
			$out = array();
			
			$article = fetch("Title","textpattern","ID",$article_id);
			$href    = "index.php?event=article&step=edit&id=$article_id&win=".$WIN['winid'];
			$article = '<a href="'.$href.'">'.$article.'</a>';
			
			$out[] = graf('Regarding'.br.$article);
			
			$out[] =  n.graf('<label for="name">'.gTxt('email').'</label>'.br.
				fInput('text', 'email', $email, 'edit'));
			
			$out[] =  n.graf('<label for="name">'.gTxt('url').'</label>'.br.
				fInput('text', 'web', $url, 'edit'));
			
			$html[0]['special'] = '<div class="event-group1">'.n.implode(n,$out).n.'</div>';
			$html[1]['title']   = str_replace('>'.gTxt('title').'<','>'.gTxt('comment_subject').'<',$html[1]['title']);
			$html[1]['body']    = str_replace('>'.gTxt('body').'<','>'.gTxt('comment_message').'<',$html[1]['body']);
		}
	}
?>

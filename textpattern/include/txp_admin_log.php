<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of
	the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/include/txp_log.php $
$LastChangedRevision: 3203 $

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');
	
	if ($event == 'log')
	{
		require_privs('log');
		
		$statuses = array(
			1 	=> gTxt('draft'),
			2 	=> gTxt('hidden'),
			3 	=> gTxt('pending'),
			4 	=> strong(gTxt('live')),
			5 	=> gTxt('sticky'),
			6 	=> gTxt('note'),
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Authorization Required',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Large',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			425 => 'No code',
			426 => 'Upgrade Required',
			500 => 'Internal Server Error',
			501 => 'Method Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Temporarily Unavailable',
			504 => 'Gateway Time-out',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended'
		);
		
		include txpath.'/lib/countries.php';
	}

//-------------------------------------------------------------

	function log_list($message='')
	{
		global $EVENT, $WIN, $html, $siteurl, $countries, $smarty;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$WIN['columns']) {
		
			$WIN['columns'] = array(
				
				'Title'  	=> array('title' => 'Title',  	'on' => 1, 'editable' => 1, 'pos' => 1),
				'Posted' 	=> array('title' => 'Time', 	'on' => 1, 'editable' => 0, 'pos' => 2),
				'host' 	 	=> array('title' => 'Host', 	'on' => 0, 'editable' => 0, 'pos' => 3),
				'ip' 	 	=> array('title' => 'IP', 	   	'on' => 0, 'editable' => 0, 'pos' => 4),
				'page'		=> array('title' => 'Page', 	'on' => 1, 'editable' => 0, 'pos' => 5),
				'refer'    	=> array('title' => 'Referer',  'on' => 0, 'editable' => 0, 'pos' => 6),
			 //	'location'  => array('title' => 'Location', 'on' => 1, 'editable' => 0, 'pos' => 7),
			 //	'City'	    => array('title' => 'City',		'on' => 0, 'editable' => 0, 'pos' => 8),
			 // 'Region'    => array('title' => 'Region',   'on' => 0, 'editable' => 0, 'pos' => 9),
			 // 'Country'   => array('title' => 'Country',  'on' => 0, 'editable' => 0, 'pos' => 10),
				'method'	=> array('title' => 'Method',	'on' => 0, 'editable' => 0, 'pos' => 11),
				'Status'	=> array('title' => 'Status',	'on' => 0, 'editable' => 0, 'pos' => 12)
			);
			
			// $WIN['columns']['location']['sel'] = "IF(Country!='',CONCAT_WS('/',Country,Region,City),'')";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// PAGE TOP
		
		$html = pagetop(gTxt('visitor_logs'),$message);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$log = new ContentList(); 
		$list = $log->getList();
		
		$site = explode('/',$siteurl);
		$site = array_shift($site);
		
		foreach ($list as $key => $item) {
			
			if ($item['Type'] == 'folder') {
				
				if (isset($item['page'])) {
					$list[$key]['page']  = ltrim($item['page'],'0');
				}
				
				if (isset($item['ip'])) {
					$list[$key]['ip']    = ltrim($item['ip'],'0');
				}
				
				if (isset($item['refer'])) {
					$list[$key]['refer'] = ltrim($item['refer'],'0');
				}
				
				if (isset($item['host'])) {
					$list[$key]['host'] = ltrim($item['host'],'0');
				}
				
				if (isset($item['location'])) {
					$list[$key]['location'] = count(safe_column("ID","txp_log",
						"Country != '' AND Type = 'page' AND Trash = 0 GROUP BY Country,Region,City"));
				}
				
				if (isset($item['Country'])) {
					$list[$key]['Country'] = count(safe_column("ID","txp_log",
						"Country != '' AND Type = 'page' AND Trash = 0 GROUP BY Country"));
				}
				
				if (isset($item['Region'])) {
					$list[$key]['Region'] = count(safe_column("ID","txp_log",
						"Country != '' AND Type = 'page' AND Trash = 0 GROUP BY Country,Region"));
				}
				
				if (isset($item['City'])) {
					$list[$key]['City'] = count(safe_column("ID","txp_log",
						"Country != '' AND Type = 'page' AND Trash = 0 GROUP BY Country,Region,City"));
				}
				
			} else {
				
				if (isset($item['page']) and strlen($item['page'])) {
				
					$href = 'http://'.$site.$item['page'];
					$page = preg_replace('/^\/~[a-z0-9]+\//','/',$item['page']);
					// $page = preg_replace('/^\/$/','/index.html',$page);
					
					$list[$key]['page'] = '<a title="'.$page.'" target="_new" href="'.$href.'">'.$page.'</a>';
				}
				
				if (isset($item['refer']) and strlen($item['refer'])) {
					
					$href = $item['refer'];
					
					$list[$key]['refer'] = '<a target="_new" href="'.$href.'">'.$item['refer'].'</a>';
				}
				
				if (isset($item['location']) and strlen($item['location'])) {
				
					list($country,$region,$city) = explode('/',$item['location']);
						
					$smarty->assign('country',$countries[$country]);
					$smarty->assign('countrycode',$country);
					$smarty->assign('region',$region);
					$smarty->assign('city',$city);
						
					$list[$key]['location'] = $smarty->fetch('list/list_item_td_loc.tpl');
				}
				
				if (isset($item['Country']) and strlen($item['Country'])) {
					
					$list[$key]['Country'] = $countries[$item['Country']];
				}
			}
		}
		
		$html.= $log->viewList($list);
		
		save_session($EVENT);
		save_session($WIN);
	}

//-------------------------------------------------------------

	function log_list_old($message = '')
	{
		global $log_list_pageby, $expire_logs_after;

		pagetop(gTxt('visitor_logs'), $message);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
		if ($sort === '') $sort = get_pref('log_sort_column', 'time');
		if ($dir === '') $dir = get_pref('log_sort_dir', 'desc');
		$dir = ($dir == 'asc') ? 'asc' : 'desc';

		$expire_logs_after = assert_int($expire_logs_after);

		safe_delete('txp_log', "time < date_sub(now(), interval $expire_logs_after day)");

		switch ($sort)
		{
			case 'ip':
				$sort_sql = 'ip '.$dir;
			break;

			case 'host':
				$sort_sql = 'host '.$dir;
			break;

			case 'page':
				$sort_sql = 'page '.$dir;
			break;

			case 'refer':
				$sort_sql = 'refer '.$dir;
			break;

			case 'method':
				$sort_sql = 'method '.$dir;
			break;

			case 'status':
				$sort_sql = 'status '.$dir;
			break;

			default:
				$sort = 'time';
				$sort_sql = 'time '.$dir;
			break;
		}

		set_pref('log_sort_column', $sort, 'log', 2, '', 0, PREF_PRIVATE);
		set_pref('log_sort_dir', $dir, 'log', 2, '', 0, PREF_PRIVATE);

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($search_method and $crit)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'ip'     => "ip like '%$crit_escaped%'",
				'host'   => "host like '%$crit_escaped%'",
				'page'   => "page like '%$crit_escaped%'",
				'refer'  => "refer like '%$crit_escaped%'",
				'method' => "method like '%$crit_escaped%'",
				'status' => "status like '%$crit_escaped%'"
			);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
				$limit = 500;
			}

			else
			{
				$search_method = '';
				$crit = '';
			}
		}

		else
		{
			$search_method = '';
			$crit = '';
		}

		$total = safe_count('txp_log', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.log_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' class="indicator"');
			}

			else
			{
				echo graf(gTxt('no_refers_recorded'), ' class="indicator"');
			}

			return;
		}

		$limit = max($log_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);
		
		custom_log_select();	// new

		echo n.log_search_form($crit, $search_method);

		$rs = safe_rows_start('*, unix_timestamp(time) as uTime', 'txp_log',
			"$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo n.n.'<form action="index.php" method="post" name="longform" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				startTable('list','','','','90%').

				n.tr(
					n.column_head('time', 'time', 'log', true, $switch_dir, $crit, $search_method, ('time' == $sort) ? $dir : '').
					column_head('IP', 'ip', 'log', true, $switch_dir, $crit, $search_method, (('ip' == $sort) ? "$dir " : '').'log_detail').
					column_head('host', 'host', 'log', true, $switch_dir, $crit, $search_method, ('host' == $sort) ? $dir : '').
					column_head('page', 'page', 'log', true, $switch_dir, $crit, $search_method, ('page' == $sort) ? $dir : '').
					column_head('referrer', 'refer', 'log', true, $switch_dir, $crit, $search_method, ('refer' == $sort) ? $dir : '').
					column_head('method', 'method', 'log', true, $switch_dir, $crit, $search_method, (('method' == $sort) ? "$dir " : '').'log_detail').
					column_head('status', 'status', 'log', true, $switch_dir, $crit, $search_method, (('status' == $sort) ? "$dir " : '').'log_detail').
					hCell()
				);

			while ($a = nextRow($rs))
			{
				extract($a, EXTR_PREFIX_ALL, 'log');

				if ($log_refer)
				{
					$log_refer = 'http://'.$log_refer;

					$log_refer = '<a href="'.htmlspecialchars($log_refer).'" target="_blank">'.htmlspecialchars(soft_wrap($log_refer, 30)).'</a>';
				}

				if ($log_page)
				{
					$log_anchor = preg_replace('/\/$/','',$log_page);
					$log_anchor = soft_wrap(substr($log_anchor,1), 30);

					$log_page = '<a href="'.htmlspecialchars($log_page).'" target="_blank">'.htmlspecialchars($log_anchor).'</a>';

					if ($log_method == 'POST')
					{
						$log_page = '<strong>'.$log_page.'</strong>';
					}
				}

				echo tr(

					n.td(
						gTime($log_uTime)
					, 85).

					td($log_ip, 20, 'log_detail').

					td(soft_wrap($log_host, 30)).

					td($log_page).
					td($log_refer).
					td(htmlspecialchars($log_method), 60, 'log_detail').
					td($log_status, 60, 'log_detail').

					td(
						fInput('checkbox', 'selected[]', $log_id)
					)

				);
			}

			echo n.n.tr(
				tda(
					toggle_box('log_detail'),
					' colspan="2" style="text-align: left; border: none;"'
				).
				tda(
					select_buttons().
					log_multiedit_form($page, $sort, $dir, $crit, $search_method)
				, ' colspan="6" style="text-align: right; border: none;"')
			).

			n.endTable().
			'</form>'.

			n.nav_form('log', $page, $numPages, $sort, $dir, $crit, $search_method, $total, $limit).

			n.pageby_form('log', $log_list_pageby);
		}
	}

//-------------------------------------------------------------

	function log_search_form($crit, $method)
	{
		$methods =	array(
			'ip'     => gTxt('IP'),
			'host'	 => gTxt('host'),
			'page'   => gTxt('page'),
			'refer'	 => gTxt('referrer'),
			'method' => gTxt('method'),
			'status' => gTxt('status')
		);

		return search_form('log', 'log_list', $crit, $methods, $method, 'page');
	}

//-------------------------------------------------------------

	function log_change_pageby()
	{
		event_change_pageby('log');
		log_list();
	}

// -------------------------------------------------------------

	function log_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'delete' => gTxt('delete')
		);

		return event_multiedit_form('log', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function log_multi_edit()
	{
		$deleted = event_multi_edit('txp_log', 'id');

		if ($deleted)
		{
			$message = gTxt('logs_deleted', array('{list}' => $deleted));

			return log_list($message);
		}

		return log_list();
	}

// -------------------------------------------------------------
// new

	function custom_log_select() 
	{
		global $event, $step, $custom_logs;
		
		if ($custom_logs) {
			echo 
			form(
				selectInput("step", $custom_logs, $step,'','document.changelog.submit()','','custom-log').
					hInput('event',$event),
				'text-align:center','','post','','','changelog');
		}
	}

?>

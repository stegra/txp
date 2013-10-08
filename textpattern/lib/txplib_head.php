<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/lib/txplib_head.php $
$LastChangedRevision: 3265 $
*/

// -------------------------------------------------------------
	function pagetop($pagetitle,$message="")
	{
		global $PFX,$WIN,$siteurl,$sitename,$txp_user,$area,$event,$events,$app_mode,$theme,$base,$smarty;
		
		if ($app_mode == 'async') return;
		$edit_steps = array('edit','save');
		
		// $message = (is_array($message)) ? implode('',$message) : $message;	// temporary
		
		$area 	= gps('area');
		$step   = gps('step');
		$step   = gps('open',$step);
		$step   = gps('close',$step);
		$bm 	= gps('bm');
		$event  = (!$event) ? 'article' : $event;
		$mode   = (in_array(gps('step'),$edit_steps) or $event == 'article') ? 'edit' : 'list';
	 // $error  = (preg_match('/error/i',$message)) ? 1 : 0;
		$error  = 0;
		$privs 	= safe_field("privs", "txp_users", "name = '".doSlash($txp_user)."'");
		$doctype = fetch('Type',$WIN['table'],"ID",$WIN['docid']);
		$docname = fetch('Name',$WIN['table'],"ID",$WIN['docid']);
		
		$GLOBALS['privs'] = $privs;

		$areas = areas(); 
		$area = false;
		
		foreach ($areas as $k => $v)
		{
			if (in_array($event, $v))
			{
				$area = $k;
				break;
			}
		}

		if (gps('logout'))
		{
			$body_id = 'page-logout';
		}
		elseif (!$txp_user)
		{
			$body_id = 'page-login';
		}
		else
		{
			$body_id = 'page-'.$event;
		}
		
		$body_class = array($area,$event,$mode);
		$body_class[] = ($WIN['mini']) ? 'window window-'.$area : '';
		$body_class[] = 'os-'.user_agent_os();
		$body_class[] = user_agent_os();
		$body_class[] = 'site-'.(($PFX) ? trim($PFX,'_') : 'txp');
		$body_class[] = ($WIN['name']) ? $event.'-name-'.$WIN['name'] : '';
		$body_class[] = ($WIN['class']) ? $event.'-class-'.$WIN['class'] : '';
		
		$event_js = txpath.DS.'js'.DS.'txp_event'.DS.'txp_event_'.$event.'.js';
		$event_js = (is_file($event_js)) ? 'js/txp_event/txp_event_'.$event.'.js' : '';
		
		$smarty->assign('lang',LANG);
		$smarty->assign('lang_dir',gTxt('lang_dir'));
		$smarty->assign('body_id',$body_id);
		$smarty->assign('body_class',trim(implode(' ',$body_class)));
		$smarty->assign('area',$area);
		$smarty->assign('area_title',gTxt('tab_'.$area));
		$smarty->assign('event',$event);
		$smarty->assign('step',(($step)?$step:'list'));
		$smarty->assign('method',gps('edit_method'));
		$smarty->assign('mode',$mode);
		$smarty->assign('view',$WIN['view']);
		$smarty->assign('mini',$WIN['mini']);
		$smarty->assign('window',$WIN['winid']);
		$smarty->assign('docid',$WIN['docid']);
		$smarty->assign('docname',$docname);
		$smarty->assign('doctype',$doctype);
		$smarty->assign('scroll',$WIN['scroll']);
		$smarty->assign('linenum',$WIN['linenum']);
		$smarty->assign('checked','['.impl($WIN['checked']).']');
		$smarty->assign('bm',$bm);
		$smarty->assign('sitename',$sitename);
		$smarty->assign('page_title',$pagetitle);
		$smarty->assign('event_js',$event_js);
		$smarty->assign('nocache',NOCACHE);
		$smarty->assign('cookie',trim(gTxt('cookies_must_be_enabled')));
		$smarty->assign('unset_cookie',gps('unset_cookie'));
		$smarty->assign('theme',$theme->name);
		$smarty->assign('base',$base);
		
		// echo '<script type="text/javascript" src="textpattern.js"></script>';
		
		$edit = array();

		if ($event == 'list')
		{
			$rs = safe_column('name', 'txp_section', "name != 'default'");
	
			$edit['section'] = $rs ? selectInput('Section', $rs, '', true) : '';
			
			// $rs = getTree('root', 'article');
			$rs = '';
	
			$edit['category1'] = $rs ? treeSelectInput('Category1', $rs, '') : '';
			$edit['category2'] = $rs ? treeSelectInput('Category2', $rs, '') : '';
	
			$edit['comments'] = onoffRadio('Annotate', safe_field('val', 'txp_prefs', "name = 'comments_on_default'"));
	
			$edit['status'] = selectInput('Status', array(
				1 => gTxt('draft'),
				2 => gTxt('hidden'),
				3 => gTxt('pending'),
				4 => gTxt('live'),
				5 => gTxt('sticky'),
			), '', true);
	
			$rs = safe_column('name', 'txp_users', "privs not in(0,6) order by name asc");
	
			$edit['author'] = $rs ? selectInput('AuthorID', $rs, '', true) : '';
		}
	
		if (in_array($event, array('image', 'file', 'link')))
		{
			// $rs = getTree('root', $event);
			$rs = '';
			$edit['category'] = $rs ? treeSelectInput('category', $rs, '') : '';
	
			$rs = safe_column('name', 'txp_users', "privs not in(0,6) order by name asc");
			$edit['author'] = $rs ? selectInput('author', $rs, '', true) : '';
		}
	
		if ($event == 'plugin')
		{
			$edit['order'] = selectInput('order', array(1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9), 5, false);
		}
	
		if ($event == 'admin')
		{
			$edit['privilege'] = privs();
			$rs = safe_column('name', 'txp_users', '1=1');
			$edit_assign_assets = $rs ? selectInput('assign_assets', $rs, '', true) : '';
		}
	
		// JavaScript
		
		$script = '';
		
		foreach($edit as $key => $val)
		{
			$script .= "case 'change".$key."':".n.
				t."pjs.innerHTML = '<span>".str_replace(array("\n", '-'), array('', '&#45;'), str_replace('</', '<\/', addslashes($val)))."<\/span>';".n.
				t.'break;'.n.n;
		}
		if (isset($edit_assign_assets))
		{
			$script .= "case 'delete':".n.
					t."pjs.innerHTML = '<label for=\"assign_assets\">".addslashes(gTxt('assign_assets_to'))."</label><span>".str_replace(array("\n", '-'), array('', '&#45;'), str_replace('</', '<\/', addslashes($edit_assign_assets)))."<\/span>';".n.
					t.'break;'.n.n;
		}
		
		if ($WIN['mini']) {
			$theme = theme::init('mini');
		}

		$smarty->assign('script',$script);
		
		$smarty->assign('theme_head',$theme->html_head($event,$mode));
		
		if (in_array($event,$events)) {	
			$smarty->assign('mode_script',$mode);
		} else {
			$smarty->assign('mode_script','');
		}
		
		callback_event('admin_side', 'head_end');
		callback_event('admin_side', 'pagetop');
		
		$theme->set_state($area, $event, $bm, $message);
		
		$smarty->assign('header',pluggable_ui('admin_side', 'header', $theme->header($event)));
		
		callback_event('admin_side', 'pagetop_end');
		
		$html = $smarty->fetch('page_head.tpl');
		
		return tidy_html_head($html);
	}

// -------------------------------------------------------------
	function areatab($label,$event,$tarea,$area)
	{
		$tc = ($area == $event) ? 'tabup' : 'tabdown';
		$atts=' class="'.$tc.'"';
		$hatts=' href="?event='.$tarea.'" class="plain"';
      	return tda(tag($label,'a',$hatts),$atts);
	}

// -------------------------------------------------------------
	function tabber($label,$tabevent,$event)
	{
		$tc   = ($event==$tabevent) ? 'tabup' : 'tabdown2';
		$out = '<td class="'.$tc.'"><a href="?event='.$tabevent.'" class="plain">'.$label.'</a></td>';
		return $out;
	}

// -------------------------------------------------------------

	function tabsort($area, $event)
	{
		if ($area)
		{
			$areas = areas();

			$out = array();

			foreach ($areas[$area] as $a => $b)
			{
				if (has_privs($b))
				{
					$out[] = tabber($a, $b, $event, 2);
				}
			}

			return ($out) ? join('', $out) : '';
		}

		return '';
	}

// -------------------------------------------------------------
	function areas()
	{
		global $privs, $plugin_areas, $path_to_site, $txp_user;

		$areas['content'] = array(
			gTxt('tab_organise') => 'category',
			gTxt('tab_write')    => 'article',
			gTxt('tab_list')     => 'list',
			gTxt('tab_image')    => 'image',
			gTxt('tab_file')	 => 'file',
			gTxt('tab_link')     => 'link',
			gTxt('tab_comments') => 'discuss',
			'Custom' 			 => 'custom'
		);

		$areas['presentation'] = array(
		 // gTxt('tab_sections') => 'section',
			gTxt('tab_pages')    => 'page',
		    gTxt('tab_forms')    => 'form',
		 //	'Elements' 			 => 'form',
			gTxt('tab_style')    => 'css'
		);

		$areas['admin'] = array(
		 // gTxt('tab_diagnostics') => 'diag',
			'Utilities' 			=> 'utilities',
			gTxt('tab_preferences') => 'prefs',
			gTxt('tab_site_admin')  => 'admin',
			gTxt('tab_logs')        => 'log',
			gTxt('tab_plugins')     => 'plugin',
			gTxt('tab_import')      => 'import'
		);
		
		if ($txp_user != 'steffi') {
		
			unset($areas['admin'][gTxt('tab_plugins')]);
		}
		
		if (IS_MAIN_SITE and is_dir($path_to_site.'/sites')) {
			$areas['admin']['Sites'] = 'sites';
		}
		
		$areas['extensions'] = array(
		);

		if (is_array($plugin_areas))
			$areas = array_merge_recursive($areas, $plugin_areas);

		return $areas;
	}

// -------------------------------------------------------------

	function navPop($inline = '')
	{
		$areas = areas();

		$out = array();

		foreach ($areas as $a => $b)
		{
			if (!has_privs( 'tab.'.$a))
			{
				continue;
			}

			if (count($b) > 0)
			{
				$out[] = n.t.'<optgroup label="'.gTxt('tab_'.$a).'">';

				foreach ($b as $c => $d)
				{
					if (has_privs($d))
					{
						$out[] = n.t.t.'<option value="'.$d.'">'.$c.'</option>';
					}
				}

				$out[] = n.t.'</optgroup>';
			}
		}

		if ($out)
		{
			$style = ($inline) ? ' style="display: inline;"': '';

			return '<form method="get" action="index.php" class="navpop"'.$style.'>'.
				n.'<select name="event" onchange="submit(this.form);">'.
				n.t.'<option>'.gTxt('go').'&#8230;</option>'.
				join('', $out).
				n.'</select>'.
				n.'</form>';
		}
	}

// -------------------------------------------------------------
	function button($label,$link)
	{
		return '<span style="margin-right:2em"><a href="?event='.$link.'" class="plain">'.$label.'</a></span>';
	}
?>

<?php

// -------------------------------------------------------------------------------------
// change: external javascript file based on the $event added in the pagetop() function
// change: meta tag for no-cache

	function pagetop($pagetitle,$message="",$window=0)
	{
		global $css_mode,$siteurl,$txp_user,$event,$smarty;
		
		$message = (is_array($message)) ? implode('',$message) : $message;

		$area 	= gps('area');
		$event 	= (!$event) ? 'article' : $event;
		$bm 	= gps('bm'); 	// what is this?
		$win    = gps('win');
		$window = ($win and $win != 'new') ? $win : $window;
		$error  = (preg_match('/error/i',$message)) ? 1 : 0;
		
		$privs = safe_field("privs", "txp_users", "`name`='$txp_user'");
		
		$GLOBALS['privs'] = $privs;

		$areas = areas();
		foreach ($areas as $k=>$v) {
			if (in_array($event, $v))
				$area = $k;
		}
		
		$tab_content	  		= (has_privs('tab.content')) 	  	? gTxt('tab_content') : '';
		$tab_presentation 		= (has_privs('tab.presentation')) 	? gTxt('tab_presentation') : '';
		$tab_admin		  		= (has_privs('tab.admin')) 			? gTxt('tab_admin') : '';
		$tab_view_site	  		= gTxt('tab_view_site');
		$tab_extensions   		= (has_privs('tab.extensions') and !empty($areas['extensions'])) ? gTxt('tab_extensions') : '';
		$tab_extensions_event   = ($tab_extensions) ? array_shift($areas['extensions']) : '';
		
		$smarty->assign('area',$area);
		$smarty->assign('area_title',gTxt('tab_'.$area));
		$smarty->assign('event',$event);
		$smarty->assign('window',$window);
		$smarty->assign('bm',$bm);
		$smarty->assign('page_title',$pagetitle);
		$smarty->assign('nocache',NOCACHE);
		$smarty->assign('cookie',trim(gTxt('cookies_must_be_enabled')));
		$smarty->assign('message',$message);
		$smarty->assign('error',$error);
		$smarty->assign('website_url',hu);
		$smarty->assign('tab_content',$tab_content);
		$smarty->assign('tab_presentation',$tab_presentation);
		$smarty->assign('tab_admin',$tab_admin);
		$smarty->assign('tab_extensions',$tab_extensions);
		$smarty->assign('tab_extensions_event',$tab_extensions_event);
		$smarty->assign('tab_view_site',$tab_view_site);
		$smarty->assign('content',event_tabs('content',$event));
		$smarty->assign('presentation',event_tabs('presentation',$event));
		$smarty->assign('admin',event_tabs('admin',$event));
		$smarty->assign('extensions',event_tabs('extensions',$event));
		$smarty->assign('r',"\r");
		
		$smarty->display('header.tpl');
	}	

// -------------------------------------------------------------------------------------
	function event_tabs($area,$event) 
	{
		$areas = areas();
		$out   = array();
		
		foreach($areas[$area] as $label => $tabevent) {
			
			$class = ($event == $tabevent) ? 'selected' : '';
			$self  = ($event == $tabevent) ? '&self=1'  : '';
			$href  = '?event='.$tabevent.$self;
			
			$out[] = array(
				'class' => $class,
				'href'  => $href,
				'label' => $label
			);
		}
		
		return $out;
	}

?>

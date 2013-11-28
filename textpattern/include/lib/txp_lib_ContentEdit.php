<?php

	include_once txpath.'/include/lib/txp_lib_vars.php';
	include_once txpath.'/include/lib/txp_lib_custom_v4.php';
	include_once txpath.'/include/lib/txp_lib_misc.php';
	
// -----------------------------------------------------------------------------
	
	function content_edit($message='')
	{
		global $PFX, $WIN, $event, $step, $vars, $txp_user, $txpcfg, $prefs, $smarty;
		
		extract($prefs);
		extract(gpsa(array('view','from_view','step')));
		
		$table   = $WIN['table'];
		$content = $WIN['content'];
		$columns = getThings('describe '.$PFX.$table);
		$concurrent = false;
		
		if(!empty($GLOBALS['ID'])) { // newly-saved article
			
			$ID = $GLOBALS['ID'];
			$step = 'edit';
			
		} else {  
			
			$ID = gps('ID',gps('id'));
		} 
		
		// $winid = gps('win',1);
		$winid = $WIN['winid'];
		
		include_once txpath.'/lib/classTextile.php';
		$textile = new TextileTXP();

		// switch to 'text' view upon page load and after article post
		if(!$view || gps('save') || gps('publish')) {
			$view = 'text';
		}
		
		if (!$step) $step = "create";
		
		if ($step == "edit"  
			&& $view=="text" 
			&& !empty($ID) 
			&& $from_view != "preview" 
			&& $from_view != 'html'
			&& !$concurrent)
		{
			$pull = true;          //-- it's an existing article - off we go to the db
			$ID = assert_int($ID);
			
			$sticky = 0;
			
			if (table_exists('txp_sticky')) {
				$sticky = "SELECT COUNT(*) FROM ".$PFX."txp_sticky AS s WHERE s.ID = t.ID AND s.type = '$content'";
			}
				
			$select = array(
				'*',
				'unix_timestamp(Posted) AS sPosted',
				'unix_timestamp(LastMod) AS sLastMod',
				'unix_timestamp(Expires) AS sExpires',
				"($sticky) AS Sticky"
			);
			
			$rs = safe_row(impl($select),"$table AS t","ID = $ID");
			
			extract($rs);
			
			if (table_exists('txp_sticky')) {
				if ($Status == 5) $Status = 4;
				if ($Status == 7) $Status = 3;
			}
			
			$reset_time = $publish_now = ($Status < 4) && ($sPosted <= time());
			
		} else {
			
			$pull = false;         //-- assume they came from post
			
			if (in_list($from_view,'preview,html'))
			{
				$store_out = array();
				$store = unserialize(base64_decode(ps('store')));

				foreach($vars as $var)
				{
					if (isset($store[$var])) $store_out[$var] = $store[$var];
				}
			}

			else
			{
				$store_out = gpsa($vars);

				if ($concurrent)
				{	
					$store_out['sLastMod'] = safe_field('unix_timestamp(LastMod) as sLastMod', $table, 'ID='.$ID);
				}
			}

			$rs = $store_out;
			extract($store_out);
		}
		
		$GLOBALS['step'] = $step;

		if ($step=='create') {
			$textile_body = $use_textile;
			$textile_excerpt = $use_textile;
			// $Alias = 0;	// from-txp1
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Categories
		
		$is_alias = (isset($Alias) and $Alias > 0) ? 'alias' : '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Categories
		
		$Categories = ($ID) 
			? safe_column(
				"position,name AS category",
				"txp_content_category",
				"article_id = $ID AND type = '$content' ORDER BY position ASC",'',0) 
			: array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Previous/Next records
		
		$prev_id = $next_id = 0;
		
		if ($step != 'create') {
			
			// $sess = new MyArray($_SESSION);
			// $prevnext = $sess->get("window/$winid/list/prevnext/$ID","0,0");
			
			$prevnext = "0,0";
			
			$list = ($event == 'article') ? 'list' : $event;
			
			if (isset($_SESSION['window'][$winid][$list]['prevnext'][$ID])) {
				$prevnext = $_SESSION['window'][$winid][$list]['prevnext'][$ID];
			}
			
			list($prev_id,$next_id) = explode(',',$prevnext);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$pagetitle = gTxt('write');
		
		if ($Title) $pagetitle .= " &#8250; ".$Title;
		
		$WIN['name']  = $Name;
		$WIN['class'] = $Class;
		
		echo pagetop($pagetitle,$message,$WIN['winid']);
		
	// ---------------------------------------------------------------------------------
	
		if (in_list($event,'file,link,custom')) {
		
			$textile_body = 1;
		}
		
	// ---------------------------------------------------------------------------------
		
		echo n.n.'<form name="article" data-article-id="'.$ID.'" method="post" action="index.php">'.n;

		if (!empty($store_out))
		{
			echo n.hInput('store', base64_encode(serialize($store_out)));
		}	
		
		echo
			n.hInput('ID',$ID),
			n.hInput('Position',$Position),
			n.eInput($event),
			n.sInput('save');

		echo
			n.'<input type="hidden" name="view" />',
			n.n.startTable('edit','',$is_alias);
			
	//-- alias of --------------------------------------------------
		
		// we don't allow aliases to be edited on the Write page
		// only on the articles page
		
		/* echo '<tr><td>&nbsp;</td><td colspan="2" height="20">';
		
		if ($Alias > 0) {
				
			echo '<p class="alias">';
			echo '<span>You are editing an alias of</span>';
			
			$origin = safe_row("Parent,Section,Parent_Title,Title",$table,"ID = $Alias");
			
			$originID   	= $Alias;
			$originParent   = $origin['Parent'];
			$originSection  = fetch_section_title($origin['Section']);
			$originParTitle = ($origin['Parent_Title']) ? $origin['Parent_Title'] : gTxt('untitled');
			$originTitle 	= ($origin['Title']) ? $origin['Title'] : gTxt('untitled');
			
			$originPath  = $originSection;
			$originPath .= ($originParent) ? '/'.$originParTitle : '';
			$originPath .= "/".$originTitle.""; 
			$originLink  = "<a title=\"$originPath\" href=\"?event=article&step=edit&ID=$originID\">".$originTitle."</a>"; 
			
			echo sp.strong($originLink);
			
			echo '</p>';
		}
			
		echo '</td></tr>'; */
		
		echo '<tr>'.n;
		
	//== COLUMN 1 ======================================================================
		
		$td = array();
		
	//-- path -----------------------------------------------------
	/*
		$td['path'] = comment_line().comment("Path to Article").n;
		
		$td['path'] .= '<div id="article-path">'.n;
		
		if ($ID) {
			
			$path = new Path($ID,'!SELF');
			$path = $path->getList('Title',' &#8250; ');
			
			if ($path) $td['path'] .= '<span>'.$path.' &#8250</span>';
		}
		
		$td['path'] .= '</div>';
	*/
	//-- image -----------------------------------------------------
		
		$td['image'] = comment_line().comment("Image").n;
		
		$img_mode = 'add';
		
		$smarty->assign('name','');
		$smarty->assign('images',array());
		
		$ImageID = ($ID) ? fetch("ImageID",$table,"ID",$ID) : 0;
		
		// check if the image actually exists in the txp_image table
		$ImageID = safe_field('ID','txp_image',"ID = $ImageID AND Trash = 0 AND Type = 'image'");
		$ImageID = ($ImageID) ? $ImageID : 0;
		
		if ($ID and $ImageID > 0) {
			
			$td['image'] .= '<div class="image view">'.n;
			$td['image'] .= n.hInput('ImageID',$ImageID,'article-image-id').n;
			
			$img_mode = 'view';
			$img_data = array('before','t','right','before','r','-');
			$img_type = 'gallery';
			
			/* if ($content == 'article') {
				
				if ($ImageData = fetch("ImageData",$table,"ID",$ID)) {
					$img_data = explode(':',$ImageData);
				}
				
				$img_type = article_image_type($ID);
			} */
			
			$smarty->assign('image_view',event_show_image($ImageID));
			
		} else {
			
			$td['image'] .= '<div class="image add">'.n;
		}
		
		$smarty->assign('mode',$img_mode);
		$smarty->assign('path',get_adminpath());
		$smarty->assign('article',$ID);
		$smarty->assign('winid',$winid);
		$smarty->assign('image_select','');
		
		$td['image'] .= $smarty->fetch('article/image.tpl');
		$td['image'] .= '</div>';
		
	//-- file ------------------------------------------------------
		
		$td['file'] = comment_line().comment("File").n;
		
	/*	$mode = 'add';
			
		$smarty->assign('filename','');
		$smarty->assign('name','');
		$smarty->assign('extension','');
		$smarty->assign('file_id','');
		
		$FileID = ($ID) ? fetch("FileID",$table,"ID",$ID) : 0;
		
		if ($ID and $FileID > 0) {
		
			if ($filename = fetch("CONCAT(name,ext)","txp_file","id",$FileID)) {
			
				$smarty->assign('filename',$filename);
				$smarty->assign('name',get_file_name($filename,12));
				$smarty->assign('extension',get_file_ext($filename));
				$smarty->assign('file_id',$FileID);
			
				$mode = 'view';
			
			} else {
				
				safe_update($table,"FileID = -$FileID","ID = $ID");
			}
		} 
		
		$files = safe_rows("id,CONCAT(name,ext) as filename","txp_file","1 order by id desc");
		
		if ($files) {
			foreach ($files as $key => $value) {
				$files[$key]['name'] = get_file_name($value['filename'],24);
				$files[$key]['extension'] = get_file_ext($value['filename']);
			}
		}
		
		$smarty->assign('mode',$mode);
		$smarty->assign('path',get_adminpath());
		$smarty->assign('article',$ID);
		$smarty->assign('files',$files);
		
		if ($event == 'article') {
			echo $smarty->fetch('article/file.tpl');
		}
	*/
	//-- markup help -----------------------------------------------
	
		$td['sidehelp'] = comment_line().comment("Markup Help").n;
		
		$td['sidehelp'] .= '<div class="sidehelp">'.n;
		$td['sidehelp'] .= pluggable_ui('article_ui', 'sidehelp', side_help($textile_body, $textile_excerpt));
		$td['sidehelp'] .= '</div>';
		
	//-- custom menu entries ---------------------------------------

		$td['custom_menu'] = comment_line().comment("Custom Menu Entries").n;
		$td['custom_menu'] .= pluggable_ui('article_ui', 'extend_col_1', '', $rs);

	//-- advanced options ------------------------------------------
		
		$td['advanced'] = comment_line().comment("Advanced Options").n;
		
		$td['advanced'] .= '<div class="advanced-options">'.n;
		$td['advanced'] .= '<h3 class="plain lever'.(get_pref('pane_article_advanced_visible') ? ' expanded' : '').'"><a href="#advanced">'.gTxt('advanced_options').'</a></h3>'.
			'<div id="advanced" class="toggle" style="display:'.(get_pref('pane_article_advanced_visible') ? 'block' : 'none').'">';

		// article name
		$td['advanced'] .= pluggable_ui('article_ui', 'article_name',
			n.graf('<label for="name">Name</label>'.sp.popHelp('article_name').br.
				fInput('text', 'Name', $Name, 'edit')),
			$rs);
		
		// file id
		$td['advanced'] .= n.graf('<label for="file">File ID</label>'.br.fInput('text', 'FileID', $FileID, 'edit'));
		
		// markup selection
		$td['advanced'] .= pluggable_ui('article_ui', 'markup',
			n.graf('<label for="markup-body">'.gTxt('article_markup').'</label>'.br.
				pref_text('textile_body', $textile_body, 'markup-body')).
			n.graf('<label for="markup-excerpt">'.gTxt('excerpt_markup').'</label>'.br.
				pref_text('textile_excerpt', $textile_excerpt, 'markup-excerpt')),
			$rs);

		if (isset($override_form)) {
		
			// form override
			$td['advanced'] .= ($allow_form_override)
				? pluggable_ui('article_ui', 'override', graf('<label for="override-form">'.gTxt('override_default_form').'</label>'.sp.popHelp('override_form').br.
					form_pop($override_form, 'override-form')), $rs)
				: '';
			
			// page override
			$td['advanced'] .= ($allow_form_override)
				? graf('Override page'.br.
					page_pop($override_page))
				: '';
		}
		
		// keywords
		$td['advanced'] .= pluggable_ui('article_ui', 'keywords',
			n.graf('<label for="keywords">'.gTxt('keywords').'</label>'.sp.popHelp('keywords').br.
				n.'<textarea id="keywords" name="Keywords" cols="18" rows="5">'.htmlspecialchars(str_replace(',' ,', ', $Keywords)).'</textarea>'),
			$rs);
		
		// url title
		/* echo pluggable_ui('article_ui', 'url_title',
			n.graf('<label for="url-title">'.gTxt('url_title').'</label>'.sp.popHelp('url_title').br.
				fInput('text', 'url_title', $url_title, 'edit', '', '', 22, '', 'url-title')),
			$rs); */
			
		$td['advanced'] .= '</div>'.n;
		$td['advanced'] .= '</div>';
		
	//-- recent articles -------------------------------------------
		
		$td['recent'] = comment_line().comment("Recent Articles").n;
		
		$td['recent'] .= '<div class="recent-articles">'.n;
		$td['recent'] .= '<h3 class="plain lever'.(get_pref('pane_article_recent_visible') ? ' expanded' : '').'"><a href="#recent">'.gTxt('recent_articles').'</a>'.'</h3>'.
			'<div id="recent" class="toggle" style="display:'.(get_pref('pane_article_recent_visible') ? 'block' : 'none').'">';

		$recents = safe_rows_start("Title, ID",$table,"1=1 order by LastMod desc limit 10");

		if ($recents)
		{
			$td['recent'] .= '<ul class="plain-list">';

			while($recent = nextRow($recents))
			{
				if (!$recent['Title'])
				{
					$recent['Title'] = gTxt('untitled').sp.$recent['ID'];
				}

				$td['recent'] .= n.t.'<li><a href="?event=article'.a.'step=edit'.a.'ID='.$recent['ID'].'">'.escape_title($recent['Title']).'</a></li>';
			}

			$td['recent'] .= '</ul>';
		}

		$td['recent'] .= '</div>';
		$td['recent'] .= '</div>';
			
	//-- aliases -------------------------------------------
	
		$td['aliases'] = comment_line().comment("Aliases").n;
	
		if ($ID and ($aliasCount = safe_count($table,"Alias = $ID"))) {
		
			$td['aliases'] .= '<h3 class="plain lever"><a href="#aliases">Aliases'." ($aliasCount)".'</a>'.'</h3>'.n.
			'<div id="aliases" class="toggle" style="display:none">';
			
			$aliases = safe_rows("ID,ParentID,Title",$table,"Alias = $ID");
			
			// pre($aliases);
			
			$td['aliases'] .= '<ul>';
			
			foreach($aliases as $alias) {
				
				$aliasID   	   = $alias['ID'];
				$aliasParentID = $alias['ParentID'];
				$aliasTitle    = ($alias['Title']) ? $alias['Title'] : gTxt('untitled');
				
				$path = new Path($aliasID,'ROOT');
				$aliasPath = $path->getList('Title',' / ');
				
				$aliasLink  = '<a title="'.$aliasPath.'" href="?event=article'.a.'step=edit'.a.'ID='.$aliasID.'">'.$aliasTitle.'</a>';
				
				$td['aliases'] .= '<li>'.$aliasLink.'</li>'.n;
			}
			
			$td['aliases'] .= '</ul>';
			$td['aliases'] .= '</div>';
		}
		
	//-- special event/type specific content placeholder ---------
		
		$td['special'] = '';
		
	//-- custom fields -------------------------------------------
	
		$td['custom'] = comment_line().comment("Custom Fields").n;
		
		if ($ID) {
			
			if (in_list($event,'article,discuss,link')) {
			
				$td['custom'] .= '<div class="custom-fields">'.n;
				
				$fields = ($is_alias) 
					? getArticleCustomFields($Alias)
					: getArticleCustomFields($ID);
				
				$td['custom'] .= displayArticleCustomFields($fields,$is_alias);
				
				// add field form
				
				if (in_list($event,'article,discuss') and !$is_alias) {
					$td['custom'] .= displayAddCustomFieldForm($ID);
				}
							
				$td['custom'] .= '</div>';
			}
		}
		
	//== MAIN COLUMN ===================================================================
  		
  		$td = array($td,array());
  		
  		//-- path -----------------------------------------------
  		
  		$path = '';
  		
  		if ($ID) {	
  			$path = new Path($ID,'ROOT,!SELF');
  			$path = $path->getList('Title',' &#8250; ');
  		} elseif (isset($_SESSION['window'][$winid])) {
  			$path = new Path($_SESSION['window'][$winid]['list']['id'],'ROOT,SELF');
  			$path = $path->getList('Title',' &#8250; ');
  		}
  		
  		$path .= ($path) ? ' &#8250' : '&#160;';
  		
  		//-- title input -----------------------------------------------
		
		$td[1]['title'] = '<p class="title"><span class="path">'.$path.'</span><span class="title"><label for="title">'.gTxt('title').'</label>'.' '.popHelp('title').br.'</span>'.
			'<input type="text" id="title" name="Title" value="'.escape_title($Title).'" class="edit" size="40" tabindex="1" /></p>';
	
		//-- body input ------------------------------------------------
		
    	$td[1]['body'] = '<p class="body"><span class="body"><label for="body">'.gTxt('body').'</label>'.sp.popHelp('body').br.
			'</span><textarea id="body" name="Body" cols="55" rows="20" tabindex="2">'.htmlspecialchars($Body).'</textarea>';
		
		//-- excerpt ---------------------------------------------------
	
		if ($articles_use_excerpts) {
			
			$td[1]['excerpt'] = '<p class="excerpt"><span class="excerpt"><label for="excerpt">'.gTxt('excerpt').'</label>'.sp.popHelp('excerpt').br.
				'</span><textarea id="excerpt" name="Excerpt" cols="55" rows="5" tabindex="3">'.htmlspecialchars($Excerpt).'</textarea>';
		}
	
		//-- author ----------------------------------------------------
		
		if ($step != "create") {
			
			$Author = ($content == 'comment') ? $Author : $AuthorID;
			
			$td[1]['author'] = '<p class="small">'.gTxt('posted_by').' <b>'.htmlspecialchars($Author).'</b> on '.safe_strftime('%d %b %Y at %l:%M %P',$sPosted);
			
			if($sPosted != $sLastMod) {
				$ModifiedBy = ($content == 'comment' and !$LastModID) ? $Author : $LastModID;
				$td[1]['author'] .= br.gTxt('modified_by').' '.htmlspecialchars($ModifiedBy).' on '.safe_strftime('%d %b %Y at %l:%M %P',$sLastMod);
			}
			
			$td[1]['author'] .= '</p>';
		}
		
		//--------------------------------------------------------------
		
		$td[1]['from'] = hInput('from_view',$view);

	//== LAYER TABS ====================================================================
		
		$td[2]['tabs'] = ($use_textile == USE_TEXTILE || $textile_body == USE_TEXTILE)
			? tag((tab('text',$view).tab('html',$view).tab('preview',$view)), 'ul')
			: '&#160;';
		
	//== COLUMN 2 ======================================================================
		
		if (gps('self')) {
				
			if (isset($WIN['last']['status'])) {
			
				$Status = $WIN['last']['status'];
				
				foreach ($WIN['last']['categories'] as $category) {
					$Categories[] = $category;
				}
			}
		}
		
		//-- prev/next article links -----------------------------------
		
		$td[3]['prevnext'] = comment_line().comment("Previous/Next Links").n;
			
		$td[3]['prevnext'] .= '<p id="prev-next">';
		
		if ($prev_id) {
			$td[3]['prevnext'] .= prevnext_link(gTxt('prev'),$event,'edit',$prev_id,gTxt('prev'));
		}
		
		if ($next_id) {
			$td[3]['prevnext'] .= prevnext_link(gTxt('next'),$event,'edit',$next_id,gTxt('next'));
		}			
		
		$td[3]['prevnext'] .= '&nbsp;</p>';
		
		//-- status radios ---------------------------------------------
	 		
	 	$td[3]['status'] = comment_line().comment("Status").n;
	 	$td[3]['status'] .= '<fieldset id="write-status">'.
				n.'<legend>'.gTxt('status').'</legend>'.
				n.status_radio($Status,$Sticky).
				n.'</fieldset>';
		
		//-- category selects ------------------------------------------
		
		$td[3]['categories'] = comment_line().comment("Categories").n;
		
		$pos = 0;
			
		foreach($Categories as $pos => $name) {
			$popup[$pos] = n.graf(category_popup('Category[]', $name, 'category-'.$pos,35));
		}
			
		$popup[++$pos] = n.graf(category_popup('Category[]', '', 'category-'.$pos,35));
			
		$category_popup = preg_replace('/>(&#160;){4}/','>',implode('',$popup));
			
		$td[3]['categories'] .= n.n.'<fieldset class="categorize" id="write-sort">';
		$td[3]['categories'] .= pluggable_ui(
				'article_ui','categories',
				n.'<legend>'.gTxt('categorize').'</legend>'.$category_popup,
				$rs);
			
		$td[3]['categories'] .= n.'</fieldset>';
		
		//-- MORE --------------------------------------------------------------
		
		$more = array();
		
		//-- comments --------------------------------------------------
		
		if ($event == 'article') {
			
			if ($step=="create") {
				//Avoiding invite disappear when previewing
				$AnnotateInvite = (!empty($store_out['AnnotateInvite']))? $store_out['AnnotateInvite'] : $comments_default_invite;
				if ($comments_on_default==1) { $Annotate = 1; }
			}

			if ($use_comments == 1)
			{
				$more[] = comment_line().comment("Comments");
				
				$invite[] = n.n.'<fieldset id="write-comments">'.
					n.'<legend>'.gTxt('comments').'</legend>';
	
				$comments_expired = false;
	
				if ($step != 'create' && $comments_disabled_after)
				{
					$lifespan = $comments_disabled_after * 86400;
					$time_since = time() - $sPosted;
	
					if ($time_since > $lifespan)
					{
						$comments_expired = true;
					}
				}
	
				if ($comments_expired)
				{
					$invite[] = n.n.graf(gTxt('expired'));
				}
	
				else
				{
					$invite[] = n.n.graf(
						onoffRadio('Annotate', $Annotate)
					).
	
					n.n.graf(
						'<label for="comment-invite">'.gTxt('comment_invitation').'</label>'.br.
						fInput('text', 'AnnotateInvite', $AnnotateInvite, 'edit', '', '', '', '', 'comment-invite')
					);
				}
	
				$invite[] = n.n.'</fieldset>';
				$more[] = pluggable_ui('article_ui', 'annotate_invite', join('', $invite), $rs);
			}
		}
		
		//-- timestamp ---------------------------------------------
		
		$more[] = comment_line().comment("Time Stamp");
		$more[] = '<fieldset id="write-timestamp">';
		$more[] = '<legend>'.gTxt('timestamp').'</legend>';
		
		if ($step == "create" and empty($GLOBALS['ID'])) {
		
			//Avoiding modified date to disappear
			$persist_timestamp = (!empty($store_out['year']))?
				safe_strtotime($store_out['year'].'-'.$store_out['month'].'-'.$store_out['day'].' '.$store_out['hour'].':'.$store_out['minute'].':'.$store_out['second'])
				: time();
			
			$more[] = graf(checkbox('publish_now', '1', $publish_now, '', 'publish_now').'<label for="publish_now">'.gTxt('set_to_now').'</label>').
				n.graf(gTxt('or_publish_at').sp.popHelp('timestamp')).
				n.graf(gtxt('date').sp.
					tsi('year', '%Y', $persist_timestamp).' / '.
					tsi('month', '%m', $persist_timestamp).' / '.
					tsi('day', '%d', $persist_timestamp)
				).
				n.graf(gTxt('time').sp.
					tsi('hour', '%H', $persist_timestamp).' : '.
					tsi('minute', '%M', $persist_timestamp).' : '.
					tsi('second', '%S', $persist_timestamp)
				);
				
		} else {
			
			if (!empty($year)) {
				$sPosted = safe_strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
			}

			$more[] = graf(checkbox('reset_time', '1', $reset_time, '', 'reset_time').'<label for="reset_time">'.gTxt('reset_time').'</label>').
				n.graf(gTxt('published_at').sp.popHelp('timestamp')).
				n.graf(gtxt('date').sp.
					tsi('year', '%Y', $sPosted).' / '.
					tsi('month', '%m', $sPosted).' / '.
					tsi('day', '%d', $sPosted)
				).
				n.graf(gTxt('time').sp.
					tsi('hour', '%H', $sPosted).' : ' .
					tsi('minute', '%M', $sPosted).' : '.
					tsi('second', '%S', $sPosted)
				).
				n.hInput('sPosted', $sPosted).
				n.hInput('sLastMod', $sLastMod).
				n.hInput('AuthorID', $AuthorID).
				n.hInput('LastModID', $LastModID);
		}
		
		$more[] = '</fieldset>';
		
		//-- expires -----------------------------------------------
		
		$more[] = comment_line().comment("Expiration");
		$more[] = '<fieldset id="write-expires">';
		$more[] = '<legend>'.gTxt('expires').'</legend>';
				
		if ($step == "create" and empty($GLOBALS['ID'])) {
		
			$persist_timestamp = (!empty($store_out['exp_year']))?
				safe_strtotime($store_out['exp_year'].'-'.$store_out['exp_month'].'-'.$store_out['exp_day'].' '.$store_out['exp_hour'].':'.$store_out['exp_minute'].':'.$store_out['second'])
				: NULLDATETIME;
			
			$more[] = 
				n.graf(gtxt('date').sp.
					tsi('exp_year', '%Y', $persist_timestamp).' / '.
					tsi('exp_month', '%m', $persist_timestamp).' / '.
					tsi('exp_day', '%d', $persist_timestamp)
				).
				n.graf(gTxt('time').sp.
					tsi('exp_hour', '%H', $persist_timestamp).' : '.
					tsi('exp_minute', '%M', $persist_timestamp).' : '.
					tsi('exp_second', '%S', $persist_timestamp)
				);
		
		} else {
			
			if (!empty($exp_year)) {
				if(empty($exp_month)) $exp_month=1;
				if(empty($exp_day)) $exp_day=1;
				if(empty($exp_hour)) $exp_hour=0;
				if(empty($exp_minute)) $exp_minute=0;
				if(empty($exp_second)) $exp_second=0;
				$sExpires = safe_strtotime($exp_year.'-'.$exp_month.'-'.$exp_day.' '.$exp_hour.':'.$exp_minute.':'.$exp_second);
			}
			
			$more[] = 
				n.graf(gtxt('date').sp.
					tsi('exp_year', '%Y', $sExpires).' / '.
					tsi('exp_month', '%m', $sExpires).' / '.
					tsi('exp_day', '%d', $sExpires)
				).
				n.graf(gTxt('time').sp.
					tsi('exp_hour', '%H', $sExpires).' : '.
					tsi('exp_minute', '%M', $sExpires).' : '.
					tsi('exp_second', '%S', $sExpires)
				).
				n.hInput('sExpires', $sExpires);
		
		}
		
		$more[] = '</fieldset>';
		
		// ---------------------------------------------------------
		
		$td[3]['more'] = comment_line().comment("More").n;
		$td[3]['more'] .= n.n.'<h3 class="plain lever'.(get_pref('pane_article_more_visible') ? ' expanded' : '').'">';
		$td[3]['more'] .= '<a href="#more">'.gTxt('more').'</a></h3>';
		$td[3]['more'] .= '<div id="more" class="toggle" style="display:'.(get_pref('pane_article_more_visible') ? 'block' : 'none').'">';
		$td[3]['more'] .= implode(n,$more);
		$td[3]['more'] .= '</div>';
		
		//-- submit button -----------------------------------------
		
		if ($step == "create" and empty($GLOBALS['ID'])) {
		
			// Publish
			
			$td[3]['save'] = comment_line().comment("Publish").n;
			$td[3]['save'] .= n.hInput('win',$winid).n;
			$td[3]['save'] .= (has_privs('article.publish')) ?
				fInput('submit','publish',gTxt('publish'),"publish", '', '', '', 4) :
				fInput('submit','publish',gTxt('save'),"publish", '', '', '', 4);
		
		} else {
		
			// Save 
			
			$td[3]['save'] = comment_line().comment("Save").n;
			$td[3]['save'] .= n.hInput('win',$winid).n;
				
			if (   ($Status >= 4 and has_privs('article.edit.published'))
				or ($Status >= 4 and $AuthorID==$txp_user and has_privs('article.edit.own.published'))
				or ($Status <  4 and has_privs('article.edit'))
				or ($Status <  4 and $AuthorID==$txp_user and has_privs('article.edit.own')))
				$td[3]['save'] .= fInput('submit','save',gTxt('save'),"publish saved", '', '', '', 4);
		}
				
	//==========================================================================
	// special event/type specific edit 
		 
		$event_edit_type = $event."_edit_type";
		
		if (function_exists($event_edit_type)) {
		
			$event_edit_type($rs,$td);
		}
		
	//==========================================================================
		
		echo comment_line('=').comment("COLUMN 1").n;
		echo '<td id="article-col-1">'.n.n;
		echo implode(n.n,$td[0]).n.n;
		echo '</td>'.n;
		
		echo comment_line('=').comment("MAIN COLUMN").n;
  		echo '<td id="article-main">'.n.n;
		echo implode(n.n,$td[1]).n.n;
		echo '</td>'.n;
		
		echo comment_line('=').comment("Layer Tabs").n;
		echo '<td id="article-tabs">'.n.n;
		echo implode(n.n,$td[2]).n.n;
		echo '</td>'.n;
		
		echo comment_line('=').comment("COLUMN 2").n;
		echo '<td id="article-col-2">'.n.n; 
		echo implode(n.n,$td[3]).n.n;
		echo '</td>'.n;

	//==========================================================================
		
		echo '</tr>'.n.n.'</table>'.n.n.'</form>';
    	echo comment_line().n;
    	echo '<div id="drop-frame"></div>'.n.n;
    	
    	// Assume users would not change the timestamp if they wanted to "publish now"/"reset time"
		echo script_js( <<<EOS
		$('#write-timestamp input.edit').change(
			function() {
				$('#publish_now').attr('checked', false);
				$('#reset_time').attr('checked', false);
			});
EOS
);
		
		echo comment_line().n;
		echo $smarty->fetch("upload_progress.tpl");
    	echo comment_line().n;
    	
	//------------------------------------------------------------------
		
		$WIN['last']['status'] = $Status;
		$WIN['last']['categories'] = array();
		
		foreach($Categories as $category) {
			$WIN['last']['categories'][] = $category;
		}
	
	//------------------------------------------------------------------
	
		save_session($WIN);
		
		session_write_close();
	}

// -----------------------------------------------------------------------------
	function checkIfNeighbour($whichway,$sPosted,$table='textpattern')
	{
		$dir = ($whichway == 'prev') ? '<' : '>'; 
		$ord = ($whichway == 'prev') ? 'desc' : 'asc'; 

		return safe_field("ID", $table, 
			"Posted $dir from_unixtime($sPosted) order by Posted $ord limit 1");
	}

//------------------------------------------------------------------------------
// remember to show markup help for both body and excerpt
// if they are different

	function side_help($textile_body, $textile_excerpt)
	{
		if ($textile_body == USE_TEXTILE or $textile_excerpt == USE_TEXTILE)
		{
			return n.hed(
				'<a href="#textile_help">'.gTxt('textile_help').'</a>'
			, 3, ' class="plain lever'.(get_pref('pane_article_textile_help_visible') ? ' expanded' : '').'"').

				n.'<div id="textile_help" class="toggle" style="display:'.(get_pref('pane_article_textile_help_visible') ? 'block' : 'none').'">'.

				n.'<ul class="plain-list small">'.
					n.t.'<li>'.gTxt('header').': <strong>h<em>n</em>.</strong>'.sp.
						popHelpSubtle('header', 400, 400).'</li>'.
					n.t.'<li>'.gTxt('blockquote').': <strong>bq.</strong>'.sp.
						popHelpSubtle('blockquote',400,400).'</li>'.
					n.t.'<li>'.gTxt('numeric_list').': <strong>#</strong>'.sp.
						popHelpSubtle('numeric', 400, 400).'</li>'.
					n.t.'<li>'.gTxt('bulleted_list').': <strong>*</strong>'.sp.
						popHelpSubtle('bulleted', 400, 400).'</li>'.
				n.'</ul>'.

				n.'<ul class="plain-list small">'.
					n.t.'<li>'.'_<em>'.gTxt('emphasis').'</em>_'.sp.
						popHelpSubtle('italic', 400, 400).'</li>'.
					n.t.'<li>'.'*<strong>'.gTxt('strong').'</strong>*'.sp.
						popHelpSubtle('bold', 400, 400).'</li>'.
					n.t.'<li>'.'??<cite>'.gTxt('citation').'</cite>??'.sp.
						popHelpSubtle('cite', 500, 300).'</li>'.
					n.t.'<li>'.'-'.gTxt('deleted_text').'-'.sp.
						popHelpSubtle('delete', 400, 300).'</li>'.
					n.t.'<li>'.'+'.gTxt('inserted_text').'+'.sp.
						popHelpSubtle('insert', 400, 300).'</li>'.
					n.t.'<li>'.'^'.gTxt('superscript').'^'.sp.
						popHelpSubtle('super', 400, 300).'</li>'.
					n.t.'<li>'.'~'.gTxt('subscript').'~'.sp.
						popHelpSubtle('subscript', 400, 400).'</li>'.
				n.'</ul>'.

				n.graf(
					'"'.gTxt('linktext').'":url'.sp.popHelpSubtle('link', 400, 500)
				, ' class="small"').

				n.graf(
					'!'.gTxt('imageurl').'!'.sp.popHelpSubtle('image', 500, 500)
				, ' class="small"').

				n.graf(
					'<a id="textile-docs-link" href="http://textpattern.com/textile-sandbox" target="_blank">'.gTxt('More').'</a>').

				n.'</div>';
		}
	}

//------------------------------------------------------------------------------
	function status_radio($Status,$Sticky=0)
	{
		global $event, $statuses;
		
		if ($event != 'article') unset($statuses[5]);
		
		$Status = (!$Status) ? 4 : $Status;

		foreach ($statuses as $a => $b)
		{
			$out[] = n.t.'<li>'.radio('Status', $a, ($Status == $a) ? 1 : 0, 'status-'.$a).
				'<label for="status-'.$a.'">'.$b.'</label></li>';
		}
		
		if ($event == 'article') {
			$out[] = checkbox('Sticky',1,$Sticky).gTxt('sticky');
		}
		
		return '<ul class="plain-list">'.join('', $out).n.'</ul>';
	}

//------------------------------------------------------------------------------
// change: moved to txplib_misc.php

//	function category_popup($name, $val, $id='', $truncate=0) {}

//------------------------------------------------------------------------------
	function section_popup($Section, $id)
	{
		$rs = safe_column('name', 'txp_section', "name != 'default'");

		if ($rs)
		{
			return selectInput('Section', $rs, $Section, false, '', $id);
		}

		return false;
	}

//------------------------------------------------------------------------------
	function tab($tabevent,$view)
	{
		$state = ($view==$tabevent) ? 'up' : 'down';
		$out = "<li id='tab-$tabevent$state'>";
		$out.=($tabevent!=$view) ? '<a href="javascript:document.article.view.value=\''.$tabevent.'\';document.article.submit();">'.gTxt($tabevent).'</a>' : gTxt($tabevent);
		$out.='</li>';
		return $out;
	}

// -----------------------------------------------------------------------------
	function page_pop($page)
	{
		$arr = array(' ');
		
		$rs = safe_column("name", "txp_page", "name != 'global' order by name");
		
		if($rs) {
			// return selectInput('override_page',$rs,$page,1);
			return selectInput('override_page', $rs, $page, true);
		}
	}
	
// -----------------------------------------------------------------------------
	function form_pop($form, $id)
	{
		$arr = array(' ');

		$rs = safe_column('name', 'txp_form', "type = 'article' and name != 'default' order by name");

		if ($rs)
		{
			return selectInput('override_form', $rs, $form, true, '', $id);
		}
	}
	
?>
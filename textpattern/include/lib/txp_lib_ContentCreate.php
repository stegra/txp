<?php
	
	include_once txpath.'/include/lib/txp_lib_vars.php';
	include_once txpath.'/include/lib/txp_lib_custom_v4.php';
	include_once txpath.'/include/lib/txp_lib_misc.php';

// -----------------------------------------------------------------------------
	
	function content_create_update_parent($ID,$table='')
	{
		global $WIN, $app_mode;
		
		$table = ($table) ? $table : $WIN['table'];
		
		renumerate($ID);
		
		// update parent child count
		safe_update($table,"Children = Children + 1","ID = $ID");
		
		update_parent_info($table,$ID);
		
		// update path
		if ($app_mode != 'async') {
			update_path($ID,'TREE');
		}
	}
	
// -----------------------------------------------------------------------------
	
	function content_create($ParentID, $in=array(), $table='', $type='', $debug=0)
	{
		global $PFX, $WIN, $event, $columns, $txp_user, $vars, $prefs, $app_mode;
		
		extract($prefs);
		
		$textpattern  = ($table) ? $table : $WIN['table'];
		$content_type = ($type) ? $type : $WIN['content'];
		$event_edit   = (function_exists($event.'_edit')) 
			? $event.'_edit'
			: 'event_edit'; 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$incoming = psa($vars);
		
		foreach ($in as $key => $value) {
			
			$incoming[$key] = $value;
			
			if (in_array($key,$vars)) {
				unset($in[$key]);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$in = textile_main_fields($incoming, $use_textile);
		$in = textile_title_field($in,$use_textile);
		
		if (isset($in['Body_html'])) {
			$in['Body_html'] = examineHTMLTags($in['Body_html'],false); 
		}
		
		if (isset($in['Excerpt_html'])) {
			$in['Excerpt_html'] = examineHTMLTags($in['Excerpt_html'],false); 
		}
		
		$Category = $in['Category'];
		unset($in['Category']);
		
		$in = doSlash($in);
		
		extract($in);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!strlen($Status)) 			$Status = ps('Status','4','1,2,3,4,5,6');
		if (!strlen($textile_body)) 	$textile_body = ps('textile_body',$use_textile,'0,1,2');
		if (!strlen($textile_excerpt)) 	$textile_excerpt = ps('textile_excerpt',$use_textile,'0,1,2');
		
		$publish_now = ps('publish_now','1','0,1');
		
		$Status 		 = assert_int($Status);
		$textile_body 	 = assert_int($textile_body);
		$textile_excerpt = assert_int($textile_excerpt);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (array_key_exists('Annotate',$_POST)) {
		
			$Annotate = (int) $Annotate;
		
		} else {
			
			$Annotate = ($comments_on_default == 1) ? 1 : 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$AuthorID)  $AuthorID = $txp_user;
		if (!$LastModID) $LastModID = $AuthorID;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Posted
		
		if ($Posted = trim($Posted)) {
			
			$when = (!preg_match('/^[a-z]/',strtolower($Posted))) 
				? doQuote($Posted) 
				: doStrip($Posted);
			
		} elseif ($publish_now==1) {
		
			$when = 'NOW()';
			$when_ts = time();
		
		} else {
			
			if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || !is_numeric($hour)  || !is_numeric($minute) || !is_numeric($second) ) {
				article_edit(array(gTxt('invalid_postdate'), E_ERROR));
				return;
			}

			$ts = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);

			if ($ts === false || $ts === -1) { // Tracking the PHP meanders on how to return an error
				$event_edit(array(gTxt('invalid_postdate'), E_ERROR));
				return;
			}

			$when = $when_ts = $ts - tz_offset($ts);
			$when = "from_unixtime($when)";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Last Modified
		
		if (!$LastMod and $Posted) {
		
			$LastMod = $when;
		
		} elseif (!$LastMod) {
		
			$LastMod = "NOW()";
		
		} else {
			
			$LastMod = (strtolower(trim($LastMod)) != 'now()')
				? doQuote($LastMod) 
				: $LastMod;
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Expiration
		
		if (empty($exp_year)) {
		
			$expires = 0;
			$whenexpires = NULLDATETIME;
		
		} else {
		
			if (empty($exp_month)) $exp_month=1;
			if (empty($exp_day)) $exp_day=1;
			if (empty($exp_hour)) $exp_hour=0;
			if (empty($exp_minute)) $exp_minute=0;
			if (empty($exp_second)) $exp_second=0;

			$ts = strtotime($exp_year.'-'.$exp_month.'-'.$exp_day.' '.$exp_hour.':'.$exp_minute.':'.$exp_second);
			$expires = $ts - tz_offset($ts);
			$whenexpires = "from_unixtime($expires)";
		}
		
		if ($expires) {
		
			if ($expires <= $when_ts) {
				article_edit(array(gTxt('article_expires_before_postdate'), E_ERROR));
				return;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Status
		
		if (!TXP_UPDATE) {
		
			if ($content_type == 'article') {
				
				if ($Status != 1) {		// (hidden)
				
					if ($production_status != 'live') {
						
						$Status = 4;	// (live)
					
					} elseif (!$Body and !$Excerpt) { 
					
						$Status = 3; 	// (pending)
					}
				}
					
			} elseif (!isset($in['Status'])) {
				
				$Status = 4;	// (live)
			}
			
			if (!has_privs('article.publish') && $Status >= 4) {
				
				$Status = 3; 	// (pending)
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Keywords
		
		$Keywords = doSlash(trim(preg_replace('/( ?[\r\n\t,])+ ?/s', ',', preg_replace('/ +/', ' ', ps('Keywords'))), ', '));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Title, Name, url_title
		
		$Title = trim($Title);
		$Title = str_replace('\r','',$Title);
		$Title = str_replace('\n','',$Title);
		
		// if (!strlen(trim($url_title))) $url_title = make_name($Title);
		if (!strlen(trim($Name))) $Name = make_name($Title);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Class & Category
		
		if (!$Class) {
		
			// $Classes = array();
			
			if (isset($Category) and is_array($Category)) {
				
				foreach ($Category as $key => $name) {
					
					if (getCount("txp_category","Name = '$name' AND `Class` = 'yes'")) {
						
						// $Classes[] = $name;
						unset($Category[$key]);
					}
				}
				
				// $Category = array_values(array_merge($Classes,$Category));
				
				// $Class = array_shift($Classes);
			
			} else {
				
				$Class = '';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Position
		
		if (!strlen($Position)) {
			
			$Position = ($Status == 2 or $Status == 6) ? 0 : 999999999;
		}
			
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Image
		
		if (($image or $ImageID) and $content_type == 'article') {
			$ImageID   = ($image) ? $image : $ImageID;
			$ImageData = implode(':',array($display_list,$imgtype_list,$align_list,$display_single,$imgtype_single,$align_single));
		} else {
			$ImageID   = ($content_type == 'image') ? $ImageID : 0;
			$ImageData = '';
		}
		
		$ImageID = (!strlen($ImageID)) ? 0 : $ImageID;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// File
		
		$FileID = ($FileID) ? $FileID : 0;
		$FileID = ($file) ? $file : $FileID;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		callback_event('article_insert');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$values = array(
			'Title'				=> "'$Title'",
			'Title_html'		=> "'$Title_html'",
			'Body'				=> "'$Body'",
			'Body_html'			=> "'$Body_html'",
			'ImageID'			=> " $ImageID",
			'FileID'			=> " $FileID",
			'Keywords'			=> "'$Keywords'",
			'Status'			=> "'$Status'",
			'Posted'			=> " $when",
			'LastMod'			=> " $LastMod",
			'LastModID'			=> "'$LastModID'",
			'AuthorID'			=> "'$AuthorID'",
			'Class'				=> "'$Class'",
			'ParentID'			=> "'$ParentID'",
			'textile_body' 		=> " $textile_body",
			'textile_excerpt' 	=> " $textile_excerpt",
			'Name'				=> "'$Name'",
			'Type'				=> "'$Type'",
			'Position'			=> "'$Position'",
			'Children'			=> "'0'",
			'uid'				=> "'".md5(uniqid(rand(),true))."'"
		);
		
		if (isset($in['Excerpt'])) 	    $values['Excerpt'] = "'$Excerpt'";
		if (isset($in['Excerpt_html'])) $values['Excerpt_html'] = "'$Excerpt_html'";
		
		if ($content_type == 'article') {
			
			$values['Expires']        = "$whenexpires";
			$values['Section']        = "'$Section'";
			$values['Annotate']       = "'$Annotate'";
			$values['AnnotateInvite'] = "'$AnnotateInvite'";
			$values['override_form']  = "'$override_form'";
			$values['feed_time'] 	  = "NOW()";
			$values['ImageData'] 	  = "'$ImageData'";
			$values['url_title'] 	  = "'$Name'";
		} 
		
		if (!isset($columns)) {
		
			$columns = array();
			$columns[$textpattern] = getThings('describe '.$PFX.$textpattern); 
		
		} elseif (!isset($columns[$textpattern])) {
		
			$columns[$textpattern] = getThings('describe '.$PFX.$textpattern); 
		}
		
		foreach ($in as $field => $value) {
			
			if (!in_array($field,$columns[$textpattern])) continue;
			if (isset($values[$field])) continue; 
			if ($field == 'ID') continue;
			
			if (preg_match('/^P\d+$/',$field)) {
			
				$values[$field] = (!$value) ? "NULL" : $value;
			
			} else { 
				
				$values[$field] = doQuote($value); 
			}
		}
		
		foreach ($values as $field => $value) {
			
			if (!in_array($field,$columns[$textpattern])) unset($values[$field]);
		}
		
	 // $ID = $GLOBALS['ID'] = safe_insert($textpattern,$values,$debug);
		$ID = safe_insert($textpattern,$values,$debug);
		
		if ($Status >= 4 and !IMPORT) {
			
			do_pings();
			
			update_lastmod();
			update_lastmod($ID);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Categories
		
		if (isset($Category) and is_array($Category)) {
			
			$Category = array_values(array_unique($Category));
			
			$count_col = ucwords($content_type).'s'; 
			
			if (!column_exists("txp_category",$count_col)) {
				
				$count_col = '';
			}
			
			$Categories = array();
			
			foreach ($Category as $key => $name) {
				
				$pos = $key + 1;
				
				if ($name) safe_insert(
					"txp_content_category",
					"article_id = $ID,
					 name        = '$name',
					 type        = '$content_type',
					 position    = $pos");
					 
				if ($count_col) {
				
					$count = getCount("txp_content_category","name = '$name' AND type = '$content_type'");
					safe_update("txp_category","$count_col = $count","Name = '$name'");
				}
				
				$Categories[] = $name.'.'.$pos;
			}
			
			$Categories = implode(',',$Categories);
			
			safe_update($textpattern,"Categories = '$Categories'","ID = $ID");
			
			if ($count_col) {
				
				safe_update(
					"txp_category",
					"$count_col = (SELECT COUNT(DISTINCT(article_id)) 
								   FROM ".$PFX."txp_content_category 
								   WHERE `type` = '$content_type')",
					"ParentID = 0");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!IMPORT and !TXP_UPDATE) { 
		
			apply_custom_fields($ID);
			
			update_path($ID,'SELF'); 
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!IMPORT and !TXP_UPDATE) { 
		
			content_create_update_parent($ParentID,$textpattern);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// update article/image/file count in txp_site table
		
		if ($ID and table_exists('txp_site')) {
			
			$type = $values['Type'];
			$set  = '';
			
			if ($textpattern == 'textpattern') {
				$set = 'Articles = Articles + 1';
			}
			
			if ($textpattern == 'txp_image' and $type == "'image'") {
				$set = 'Images = Images + 1';
			}
			
			if ($textpattern == 'txp_file' and $type != "'folder'") {
				$set = 'Files = Files + 1';
			}
			
			$pfx = $PFX;
			$PFX = '';
			
			if ($set) {
				$url  = 'http://'.$prefs['siteurl'];
				safe_update('txp_site',$set,"URL = '$url'");
			}
			
			$PFX = $pfx;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$_POST = array();
		
		clear_cache();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return array('',$ID,$Status);
	}
?>
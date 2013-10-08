<?php

	include_once txpath.'/include/lib/txp_lib_vars.php';
	include_once txpath.'/include/lib/txp_lib_custom_v4.php';
	include_once txpath.'/include/lib/txp_lib_misc.php';

// -----------------------------------------------------------------------------

	function content_table($type)
	{	
		$type = (!$type) ? $WIN['content'] : $type;
		
		$tables = array(
			'article'  	=> 'textpattern',
			'image' 	=> 'txp_image',
			'file' 		=> 'txp_file',
			'link' 		=> 'txp_link',
			'category' 	=> 'txp_category',
			'comment'	=> 'txp_discuss',
			'custom'	=> 'txp_custom',
			'page'		=> 'txp_page',
			'form'		=> 'txp_form',
			'css'		=> 'txp_css',
			'sites'		=> 'txp_site',
			'users'		=> 'txp_users',
			'plugin'	=> 'txp_plugin'
		);
		
		if (!isset($tables[$type])) {
			
			echo "Error: unknown content type $type";
		}
	
		return $tables[$type];
	}
	
// -----------------------------------------------------------------------------
	
	function content_save($ID=0, $multiedit=null, $type='', $table='')
	{
		global $PFX, $WIN, $event, $txp_user, $vars, $prefs;
		
		static $group_by_columns = array();
		
		extract($prefs);
		
		$content_type = (!$type)  ? $WIN['content'] : $type;
		$textpattern  = (!$table) ? content_table($content_type) : $table;
		
		$columns      = getThings('describe '.$PFX.$textpattern);
		$custom_vars  = array();
		$custom		  = array();
		$saved 		  = true;
		$changes      = false;
		$set 		  = array(); // values going into the DB
		$event_edit   = (function_exists($event.'_edit')) 
			? $event.'_edit'
			: 'event_edit'; 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$incoming = gpsa($vars); 
		
		$ID = (!$ID) ? $incoming['ID'] : $ID;
		
		// get incoming custom fields
		
		$cols = array(
			"CONCAT(field_name,'_',id)",
			"CONCAT('custom_value_',field_name,'_',id)");
			 
		if ($custom_vars = safe_column(
			$cols,"txp_content_value",
			"article_id = $ID 
			 AND status = 1 
			 AND tbl = '$textpattern'")) 
		{	
			$incoming = array_merge($incoming,psa($custom_vars));
		}
		
		// get incoming custom fields that may not be in the value table yet
		
		foreach($_POST as $key => $value) {
		
			if (substr($key,0,13) == 'custom_value_') {
				
				$name = substr($key,13);
				$custom[$name] = (is_array($value)) ? array_shift($value) : $value;
				
				unset($incoming[$key]);
			}
		}
		
		if (is_array($multiedit)) {
			
			foreach ($incoming as $key => $value) {
				
				if (isset($multiedit[$key])) {
					
					$incoming[$key] = $multiedit[$key];
				
				} else {
					
					unset($incoming[$key]);
				} 
			}
			
			foreach ($multiedit as $key => $value) {
				
				$incoming[$key] = $value;
				
				if (substr($key,0,13) == 'custom_value_') {
					
					$name = substr($key,13);
					$custom[$name] = (is_array($value)) ? array_shift($value) : $value;
					
					unset($incoming[$key]);
				}
			}
			
			unset($incoming['ID']);
		
		} else {
			
			foreach ($incoming as $key => $value) {
			
				if (!isset($_POST[$key])) {
					
					unset($incoming[$key]);
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
	    $categories = "SELECT GROUP_CONCAT(tcc.name ORDER BY tcc.position ASC) ".
		   "FROM ".$PFX."txp_content_category AS tcc ".
		   "WHERE tcc.article_id = $ID AND tcc.type = '$content_type' ".
		   "ORDER BY tcc.position ASC";
		
		$sticky = 0;
		
		if (table_exists('txp_sticky')) {
			$sticky = "SELECT COUNT(*) FROM ".$PFX."txp_sticky AS s WHERE s.ID = t.ID AND s.type = '$content_type'";
		}
		
		$select = array(
			"*",
			"unix_timestamp(LastMod) AS sLastMod",
			"($categories) AS Categories",
			"($sticky) AS Sticky"
		);
			
		$old = safe_row(impl($select),"$textpattern AS t","ID = '$ID'",0);
		
		if (! (    ($old['Status'] >= 4 and has_privs('article.edit.published'))
				or ($old['Status'] >= 4 and $old['AuthorID']==$txp_user and has_privs('article.edit.own.published'))
		    	or ($old['Status'] < 4  and has_privs('article.edit'))
				or ($old['Status'] < 4  and $old['AuthorID']==$txp_user and has_privs('article.edit.own'))))
		{
				// Not allowed, you silly rabbit, you shouldn't even be here.
				// Show default editing screen.
			
			$event_edit();
			return false;
		}
		
		if (!is_array($multiedit) and isset($incoming['sLastMod']) and strlen($incoming['sLastMod']) and $old['sLastMod'] != $incoming['sLastMod'])
		{	
			$event_edit(array(gTxt('concurrent_edit_by', array('{author}' => htmlspecialchars($old['LastModID']))), E_ERROR), TRUE);
			return false;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		plugin_callback(1,$ID);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$in = $incoming; 
		
		if (!is_array($multiedit) and $textpattern != 'txp_custom') {
			
			$in = textile_main_fields($in,$use_textile);
		}
		
		if (isset($in['Title'])) {
			
			$in = textile_title_field($in,$use_textile);
		}
		
		$Status   = (isset($in['Status']))   ? $in['Status'] : $old['Status'];
		$Annotate = (isset($in['Annotate'])) ? $in['Annotate'] : 0;
		
		$Status   = (int) $Status;
		$Annotate = (int) $Annotate;
		
		if (!has_privs('article.publish') && $Status>=4) $Status = 3;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Posted and Expires
		
		$expires = 0;
		
		if (!is_array($multiedit)) {
			
			if (isset($in['reset_time']) and $in['reset_time']) {
			
				$whenposted = "now()";
				$when_ts = time();
				$in['Posted'] = "now()";
				
			} elseif (isset($in['year']) and $in['year']) {
				
				$year   = $in['year'];
				$month  = $in['month'];
				$day    = $in['day'];
				$hour   = $in['hour'];
				$minute = $in['minute'];
				$second = $in['second'];
				
				if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day) || !is_numeric($hour)  || !is_numeric($minute) || !is_numeric($second) ) {
					$event_edit(array(gTxt('invalid_postdate'), E_ERROR));
					return;
				}
	
				$ts = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
				
				if ($ts === false || $ts === -1) {
					$event_edit(array(gTxt('invalid_postdate'), E_ERROR));
					return;
				}
				
				$when = $when_ts = $ts - tz_offset($ts);
				$whenposted = "from_unixtime($when)";
				
				if (strtotime($old['Posted']) != $when) {
					$in['Posted'] = "from_unixtime($when)";
				} else {
					unset($in['Posted']);
				}
			} else {
				unset($in['Posted']);
			}
			
			if (empty($in['exp_year'])) {
			
				$expires = 0;
				$whenexpires = NULLDATETIME;
				
			} else {
			
				$exp_year   = (!empty($in['exp_year'])) 	? $in['exp_year'] 	: 1;
				$exp_month  = (!empty($in['exp_month'])) 	? $in['exp_month'] 	: 1;
				$exp_day    = (!empty($in['exp_day'])) 		? $in['exp_day']  	: 1;	
				$exp_hour   = (!empty($in['exp_hour'])) 	? $in['exp_hour']  	: 0;
				$exp_minute	= (!empty($in['exp_minute'])) 	? $in['exp_minute']	: 0;
				$exp_second = (!empty($in['exp_second'])) 	? $in['exp_second']	: 0;
				
				$ts = strtotime($exp_year.'-'.$exp_month.'-'.$exp_day.' '.$exp_hour.':'.$exp_minute.':'.$exp_second);
				$expires = $ts - tz_offset($ts);
				$whenexpires = "from_unixtime($expires)";
			}
	
			if ($expires) {
				
				if ($expires <= $when_ts) {
					$event_edit(array(gTxt('article_expires_before_postdate'), E_ERROR));
					return;
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Title, url_title, Name
		
		if (isset($in['Title'])) {
			$Title = $in['Title'] = trim($in['Title']);
		} else {
			$Title = $old['Title'];
		}
		
		if (isset($in['Name'])) {
			$Name = $in['Name'] = trim($in['Name']);
		} else {
			$Name = $old['Name'];
		}
		
		$Position = (isset($in['Position'])) 
			? $in['Position'] 
			: $old['Position'];
		
		$Class = (isset($in['Class'])) 
			? $in['Class'] 
			: $old['Class'];
		
		// - - - - - - - - - - - - - - - - - - - - -
		
		$name_space = ($textpattern == 'txp_form') ? '_' : '-';
		
		if (isset($in['Title'])) {
		
			if ($old['Title'] != $in['Title']) {
				
				// title has changed
				
				if (preg_match('/\w+\-copy\-\d$/',$Name)) {
					
					$in['Name'] = make_name($Title,0,$name_space);
					$in['url_title'] = $in['Name'];
					
				} elseif ($production_status != 'live' or in_list($Status,'1,3,6')) {
					
					// change the name if 
					// (Debug or Testing) or (Draft, Pending or Note)
					
					$in['Name'] = make_name($Title,0,$name_space);
				}
			}
		
		} elseif (!strlen($Name)) {
			
			$in['Name'] = make_name($Title,0,$name_space);
			
		} elseif (isset($in['Name'])) {
			
			if ($old['Name'] != $in['Name']) {
				
				$in['Name'] = make_name($Name,0,$name_space);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Keywords
		
		if (isset($in['Keywords'])) {
		
			$in['Keywords'] = doSlash(trim(preg_replace('/( ?[\r\n\t,])+ ?/s', ',', preg_replace('/ +/', ' ', ps('Keywords'))), ', '));
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Category & Class
		
		$Category = array();
		
		if (isset($in['Category'])) {
		
			$Category = $in['Category'];
			
			if (!is_array($Category)) {
				
				// incoming categories as comma seperated list 
				
				$Category = (trim($Category)) ? explode(',',trim($Category)) : array();
			
			} else {
				
				$last = count($Category) - 1;
				if ($Category[$last] == 'NONE') unset($Category[$last]);
			}
			
			if (count($Category)) {
				
				if ($old['Categories'] != implode(',',$Category)) {
				
					$Classes = array();
					
					foreach ($Category as $key => $name) {
						
						if ($name and $name != 'NONE') {
							if (getCount("txp_category","Name = '$name' AND Class = 'yes'")) {
								$Classes[] = $name;
							 // unset($Category[$key]);
							}
						}
					}
					
				 // $Category = array_values(array_merge($Classes,$Category));
					
					$set['Class'] = doQuote(array_shift($Classes));
					
					$set['LastMod'] = "NOW()";
				
				} else {
					
					// no change in categories
					
					$Category = array();
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Class
		
		if (isset($in['Class'])) {
		
			if ($in['Class'] and $old['Class'] != $in['Class']) {
			
				$oldClass = $old['Class'];
				
				if ($old['Categories'] != implode(',',$Category)) {
					$Category = explode(',',$oldCategories);
				}
				
				if (in_array($oldClass,$Category)) {
					
					$keys = array_flip($Category);
					$key = $keys[$oldClass];
					
					if ($in['Class'] == 'NONE') {
						unset($Category[$key]);
					} else {
						$Category[$key] = $in['Class'];	
					}
				
				} elseif (!in_array($in['Class'],$Category)) {
					
					if ($in['Class'] != 'NONE') {
						array_unshift($Category,$in['Class']);
					}
				}
				
			} else {
				
				unset($in['Class']);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Type
		
		if (!is_array($multiedit) and !isset($_POST['Type'])) {
			
			if (isset($in['Type'])) unset($in['Type']);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Postition
		
		if ($Status == 2 or $Status == 6) {
		
			$in['Position'] = 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Sticky 
		
		if (isset($in['Sticky']) and table_exists("txp_sticky")) {
			
			if ($in['Sticky']) {
			 
				if ($Status == 4) $in['Status'] = $Status = 5;
				if ($Status == 3) $in['Status'] = $Status = 7;
				
			} else {
				
				if ($Status == 5) $in['Status'] = $Status = 4;
				if ($Status == 7) $in['Status'] = $Status = 3;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Old Status 
		
		$in['OldStatus'] = ($Status != $old['Status']) 
			? $old['Status'].'.'.$old['Level']
			: $old['OldStatus'];
			
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Unset values that have not changed
		
		foreach ($in as $key => $value) {
			
			/* if (in_list($key,'Body,Annotate,ImageID,FileID,Expires,LastMod')) {
				
				pre('-------------------------------------------');
				pre($key);
				pre("OLD:(".$old[$key].")");
				pre("NEW:($value)");
				
				if ($old[$key] == $value) 
					pre('SAME');
				else
					pre('DIFF');
			} */
			
			if ($key == 'Class' and $value == 'NONE') $value = '';
			  
			if (isset($old[$key])) {
			
					if ($old[$key] == $value) 
						unset($in[$key]);
					elseif ($value == '' and in_list($old[$key],'0,0000-00-00 00:00:00'))
						unset($in[$key]);
					else
						$in[$key] = $value;
			} else {
				
				$in[$key] = $value;
			}
		}
		
		if (isset($in['LastMod'])) unset($in['LastMod']);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		callback_event('article_update');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach ($in as $key => $value) {
		
			if (in_array($key,$columns)) {
				
				$set[$key] = doQuote(doSlash($value));
				
				if (!is_array($multiedit)) {
					
					if ($key == 'Posted')
						$set['Posted'] = $whenposted;
						
					if ($key == 'Expires')
						$set['Expires'] = $whenexpires;
				}
			}
		}
		
		if ($expires) {
		
			$set['Expires'] = $whenexpires;
		}
		
		if ($set) {
			
			$set['LastMod']   = "NOW()";
			$set['LastModID'] = "'$txp_user'";
			
			if (in_array('url_title',$columns)) {
				$set['url_title'] = "Name";
			}
			
			$saved = safe_update($textpattern,$set,"ID='$ID'",0);
			
			$changes = true;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($changes and $Status >= 4) {
		
			if ($old['Status'] < 4) {
				do_pings();
			}
			
			update_lastmod();
			update_lastmod($ID);
			
			// $message = gTxt("article_saved");
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Body Word Count
		
		if (isset($in['Body'])) {
			
			if (column_exists($textpattern,'WordCount')) {
			
				$body  = trim($in['Body']);
				$chars = strlen($body);
				$words = ($chars) ? count(explode(' ',preg_replace('/[\s\t]+/',' ',$body))) : 0;
				
				safe_update($textpattern,"WordCount = $words, CharCount = $chars","ID = $ID");
			}	
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Categories
		
		if ($Category) {
			
			$pos = 1;
			
			safe_delete("txp_content_category","article_id = $ID AND Type = '$content_type'");
			
			$Category = array_values(array_unique($Category));
			
			$count_col = ucwords($content_type).'s'; 
			
			if (!column_exists("txp_category",$count_col)) {
				
				$count_col = '';
			}
			
			foreach ($Category as $name) {
				
				if ($name and $name != 'NONE') {
				
					if (getCount("txp_category","Name = '$name' AND Trash = 0")) {
					
						safe_insert(
							"txp_content_category",
							"article_id = $ID,
							 name       = '$name',
							 type       = '$content_type',
							 position   = $pos");
					}
				
					if ($count_col) {
						
						$count = getCount("txp_content_category","name = '$name' AND type = '$content_type'");
						safe_update("txp_category","$count_col = $count","Name = '$name'");
					}
				
					$pos++;
				}
			}
			
			if ($count_col) {
				
				foreach (expl($old['Categories']) as $oldCat) {
					$count = getCount("txp_content_category","name = '$oldCat' AND type = '$content_type'");
					safe_update("txp_category","$count_col = $count","Name = '$oldCat'");
				}
			
				safe_update(
					"txp_category",
					"$count_col = (SELECT COUNT(DISTINCT(article_id)) 
								   FROM ".$PFX."txp_content_category 
								   WHERE `type` = '$content_type')",
					"ParentID = 0");
			}
						
			if ($content_type == 'article') {
			
				$categories = "SELECT GROUP_CONCAT(CONCAT_WS('.',tcc.name,tcc.position) ORDER BY tcc.name ASC) FROM ".$PFX."txp_content_category AS tcc WHERE tcc.article_id = $ID AND tcc.type = '$content_type'";
				safe_update($textpattern,"Categories = ($categories)","ID = $ID");
				
				// $categories = "SELECT GROUP_CONCAT(CONCAT_WS('.',tcc.name,tcc.position) ORDER BY tcc.name ASC) FROM ".$PFX."txp_content_category AS tcc WHERE tcc.article_id = t.ID AND tcc.type = '$content_type'";
				// safe_update("$textpattern AS t","Categories = ($categories)","1=1");
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Custom Field Values
		
		foreach ($custom as $name => $value) {
			
			if (!preg_match('/^.+_\d+$/',$name)) {
				
				continue;	// does not match custom field format
			}
			
			if ($value == '[]') {
				
				continue; 	// unchanged checkbox values 
			}
			
			// -------------------------------------------------
			
			$name     = explode('_',$name);
			$value_id = array_pop($name);
			$name     = implode('_',$name);
			
			// -------------------------------------------------
			
			if (!$value_id) {
				
				$value_id = apply_custom_fields($ID,0,$name);
			}
			
			// -------------------------------------------------
			
			if ($value_id) {
				
				assert_int($value_id);
				
				// ---------------------------------------------
				
				if (preg_match('/^\[.*\]$/',$value)) {
					
					// checkbox values example: [small,medium]
					
					update_checkbox_values($name,$value,$value_id);
				
				} elseif (strlen($value)) {
					
					$value = doSlash($value);
					 
					$type = fetch('Type',"txp_custom","Name",doSlash($name));
					
					$set = array("text_val = '$value'");
						
					if ($type == 'number') {
						$set[] = "num_val = '$value'";
					}
						
					safe_update("txp_content_value",impl($set),"id = $value_id");
				
				} else {
					
					safe_delete("txp_content_value","id = $value_id");
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($in['Position']) and $in['Position']) {
		
			safe_update($textpattern,"ParentPosition = $Position","ParentID = '$ID'");
			renumerate($old['ParentID']); 
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($in['ImageID'])) {
		
			if ($in['ImageID'] and $in['ImageID'] != $old['ImageID']) {
				
				if ($content_type == 'article') {
				
					$data = array(			
						'display_list'   => 'before',
						'imgtype_list'   => 't',
						'align_list'     => 'right',
						'display_single' => 'before',
						'imgtype_single' => 'r',
						'align_single'   => '-'
					);
					
					if ($old_data = fetch('ImageData','textpattern','ID',$ID)) {
						$data = explode(':',$old_data);
					}
					
					$data = implode(':',$data);
					
					safe_update($textpattern,"ImageData = '$data'","ID = $ID OR Alias = $ID");
				}
				
				$WIN['image']['view'] = 'max';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($in['FileID'])) {
			
			if ($in['FileID']) {
				
				if ($in['FileID'] != $old['FileID']) { 
					
					article_file_add($ID,$in['FileID']);
				}
				
			} elseif (strlen($in['FileID'])) {
				
				article_file_remove($ID);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Sticky 
		
		if (isset($in['Sticky']) and table_exists("txp_sticky")) {
			
			if ($old['Sticky'] != $in['Sticky']) {
			
				if ($in['Sticky']) {
					safe_insert("txp_sticky","id = $ID, `type` = '$content_type'");
				} else {
					safe_delete("txp_sticky","id = $ID AND `type` = '$content_type'");
				}
			}
		} 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// negative position for hidden status
		
		if ($old['Status'] != 2 && $Status == 2) {
			
			$pos = $old['Position'];
			safe_update($textpattern, "Position = -$pos", "ID = '$ID'");
			safe_update($textpattern,"ParentPosition = -$pos","ParentID = '$ID'");
			renumerate($old['ParentID']); 
		}
		
		// restore position to its positive value after unhiding
		
		if ($old['Status'] == 2 && $Status != 2) {
			
			safe_update($textpattern,"Position = ABS(Position)","ID = '$ID'");
			renumerate($old['ParentID']); 
			$position = fetch("Position","textpattern","ID",$ID);
			safe_update($textpattern,"ParentPosition = $position","ParentID = '$ID'");
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// change status of descendants
		
		if ($Status and $Status != $old['Status']) {
			
			$Level = $old['Level'];
			
			if ($Status <= 3) {
				
				// hide descendants
				
				safe_update_tree($ID,$textpattern,
					"OldStatus = CONCAT(Status,'.$Level'), Status = $Status",
					'Status <= 5');
				
				/* if ($content_type == 'article') {
					safe_delete("txp_path","ID = $ID");
				} */
			
			} elseif ($Status <= 5) {
			
				for ($i = 0; $i <= $Level; $i++) {
					$oldstatus[] = "4.$i";
					$oldstatus[] = "5.$i";
				}
				
				// show hidden descendants (of the current article)
				// that have been hidden by this article/level or 
				// from a higher level (by an ancestor of the current article)
					
				if ($old['Children'] != 0) {
						
					safe_update_tree($ID,$textpattern,
						"Status    = FLOOR(OldStatus),
						 OldStatus = CONCAT(Status,'.',Level)",
						"OldStatus IN (".in($oldstatus).") 
						 AND Status <= 3
						 AND Level >= $Level",0);
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// custom field assignment
		
		if (!$group_by_columns) {
			
			$group_by_columns = getThings('describe '.$PFX.'txp_group');
	
			foreach($group_by_columns as $key => $col) {
			
				if (substr($col,0,3) == 'by_') {
					
					$col = substr($col,3);
					$group_by_columns[$col] = '';
				}
				
				unset($group_by_columns[$key]);
			}
		}
		
		if (isset($in['custom_field_id'])) {
			
			if ($in['custom_field_id'] and $in['custom_field_id'] != 'NONE') {
			
				// $class = ($Class and $old['Class'] != $Class) ? $Class : $old['Class'];
				
				$group_by_columns['id']       = (gps('apply_to_id')) ? $ID : 0;
				$group_by_columns['parent']   = (gps('apply_to_parent')) ? $old['ParentID'] : 0;
				$group_by_columns['class']    = (gps('apply_to_class')) ? $Class : '';
				$group_by_columns['category'] = impl(gps('apply_to_category'));
				$group_by_columns['sticky']   = gps('apply_to_sticky',0);
				$group_by_columns['name']     = gps('apply_to_name',0);
				
				add_custom_field($ID,$in['custom_field_id'],$group_by_columns);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!IMPORT) {
			
			/* path does not change
			 * child count does not change
			 * level does not change 
			 */
			
			if (isset($in['Name'])) {
				
				if ($old['Name'] != $in['Name']) {
					
					// pre("update path $ID");
					
					update_path($ID,'TREE');
				}
			}
						
			apply_custom_fields($ID);
			
			// update parent info columns of children if any
			 
			if ($old['Children'] > 0) {
			
				update_parent_info($textpattern,$ID);
			}
			
			safe_delete("txp_content_value",
				"tbl = '$textpattern' AND article_id = $ID AND text_val = ''");
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// update all aliases when original is changed
		
		if (!$old['Alias']) {
			
			update_alias_articles($ID); 
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$_POST = array();
		
		clear_cache(); 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		plugin_callback(2,$ID);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return ($saved) ? $ID : 0; 
	} 
	
// -----------------------------------------------------------------------------

	function content_save_status($id,$status,$type='') 
	{	
		$set = array('Status' => $status);
		
		content_save($id,$set,$type);
	}

// -----------------------------------------------------------------------------
	
	function update_checkbox_values($name,$value,$id)
	{
		$table = "txp_content_value";
		
		$where = safe_row(
			"article_id,group_id,instance_id,field_id",
			$table,"id = $id");
		
		if ($value == '[NONE]') {
			
			safe_delete($table,$where);
		
		} else {
			
			$value  = ltrim(rtrim($value,']'),'[');
			$values = explode(',',$value);
			
			if ($set = safe_row("*",$table,$where)) {
				
				safe_delete($table,$where);
				
				$type = fetch('Type',"txp_custom","Name",$name);
				
				unset($set['id']);
				$set = doQuote($set);
				
				foreach ($values as $key => $value) {
				
					$value = doSlash($value);
								
					$set['text_val'] = doQuote($value);
					$set['num_val']  = ($type == 'number') ? doQuote($value) : "NULL";
					
					safe_insert($table,$set);
				}
			}
		}
	}
?>
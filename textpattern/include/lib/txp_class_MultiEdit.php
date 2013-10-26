<?php

	class MultiEdit {
		
		var $method   = '';
		var $table    = '';
		var $content  = '';
		var $changed  = array();
		var $selected = array();
		var $message  = array();
		var $trash_id = 0;
		
		var $methods = array(
			'open'  		=> '',
			'close' 		=> '',
			'new'			=> 'created',
			'edit'  		=> '',
			'save'			=> 'saved',
			'cut'			=> 'cut',
			'copy'			=> '',
			'paste' 		=> '',
			'move'			=> 'moved',
			'copy_paste' 	=> 'copied',
			'cut_paste'  	=> 'moved',
			'group'		 	=> 'modified',
			'ungroup'		=> 'modified',
			'duplicate'	 	=> 'duplicated',
			'alias'			=> 'aliased',
			'move_up'		=> 'repositioned',
			'move_down'		=> 'repositioned',
			'move_left'		=> 'repositioned',
			'move_right'	=> 'repositioned',
			'reposition'	=> 'repositioned',
			'export'		=> '',
			'import'		=> '',
			'touch'			=> '',
			'add_image'		=> 'modified',
			'trash'			=> 'trashed',
			'untrash'		=> 'removed from trash',
			'empty_trash'	=> '',
			'delete'		=> '',
			'edit_cancel'	=> '',
			'cut_cancel'	=> '',
			'copy_cancel'	=> '',
			'clear_clip'	=> '',
			'changestatus'	=> '',
			'changeauthor'	=> '',
			'keep_view_settings' => '',
			'hide_headers'	=> '',
			'hide_main'		=> '',
			'flat_view'		=> '',
			'folder_image'  => 'modified'
		);
		
		// ---------------------------------------------------------------------
		
		function MultiEdit($table='') 
		{	
			global $WIN;
			
			$this->table    = (!$table) ? $WIN['table'] : $table;
			$this->trash_id = fetch("ID",$this->table,"name","TRASH");
		}
		
		// ---------------------------------------------------------------------
		
		function apply($method=null,$selected=null) 
		{
			$do_method = '';
			$changed   = array();
			
			// - - - - - - - - - - - - - - - - - - - - -
			
			$method = (!$method) 
				? gps('edit_method')
				: $method;
			
			// - - - - - - - - - - - - - - - - - - - - -
			
			$selected = (is_null($selected)) 
				? gps('selected',$selected) 
				: $selected;
			
			if (!is_array($selected) and strlen($selected)) {
				$selected = explode(',',$selected);
				$selected = array_combine($selected,$selected);
			}
			
			if ($selected) {
				
				$selected = array_map('assert_int', $selected);
				
				if (!$this->selected) {
					$this->selected = $selected;
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - -
			
			if (isset($this->methods[$method])) {
				
				if ($method == 'paste') {
				
					if ($_SESSION['clipboard']['cut'])  $method = 'cut_paste';
					if ($_SESSION['clipboard']['copy']) $method = 'copy_paste';
				}
				
				if ($method == 'cut_paste') {
					
					if ($_SESSION['clipboard']['table'] != $this->table) {
						
						// change cut/paste to copy/paste between different tables
						
						$method = 'copy_paste';
						$_SESSION['clipboard']['copy'] = $_SESSION['clipboard']['cut'];
						$_SESSION['clipboard']['cut']  = array();
					}
				}
			
				$this->method = $method;
				
				if (in_list($method,'move_up,move_down,move_left,move_right')) {
				
					$method = 'reposition';
				}
				
				if (in_list($method,'cut_cancel,copy_cancel')) {
					
					$method = 'clear_clip';
				}
				
				$do_method = 'do_'.$method;
			}
			
			if ($do_method) {
				
				$changed = $this->$do_method($selected);
			}
			
			// - - - - - - - - - - - - - - - - - - - - -
			
			if ($changed) {
				
				update_lastmod($changed);
				
				$modification = $this->methods[$method];
					
				if ($modification) {
					
					clear_cache(); 
					
					if ($method != 'empty_trash') {
						$this->message[] = messenger('article', join(', ',$changed), $modification);
					}
				}
				
				foreach($changed as $id) { 
					
					$this->changed[$id] = $id;
				}
			}
			
			return array_pop($this->message);
		}
		
		// ---------------------------------------------------------------------
		
		function reselect($id)
		{	
			$this->selected[$id] = $id;
		}
		
		// ---------------------------------------------------------------------
		
		function deselect($id=0) 
		{	
			if ($id == 'all') {
			
				$this->selected = array();
			
			} elseif (isset($this->selected[$id])) {
				
				unset($this->selected[$id]);
			}
		}
		
		// ---------------------------------------------------------------------
		// keep view settings
		
		function do_keep_view_settings($selected) 
		{	
			global $WIN;
			
			$view = array(
				'view'    => $WIN['view'],
				'thumb'   => $WIN['thumb'],
				'sortby'  => $WIN['sortby'],
				'sortdir' => $WIN['sortdir'],
				'linkdir' => $WIN['linkdir'],
				'headers' => $WIN['headers'],
				'main' 	  => $WIN['main'],
				'open'	  => impl($WIN['open']),
				'custom'  => array()
			);
			
			foreach ($WIN['columns'] as $name => $column) {
				$view['columns'][$name] = $column;
			}
			
			if (isset($WIN['custom'])) {
			
				foreach ($WIN['custom'] as $id => $column) {
					if ($column['show']) $view['custom'][] = $id;
				}
				
				$view['custom'] = impl($view['custom']);
			}
						
			$view = serialize($view);
			
			$where = array(
				"`type` = 'view'",
				"by_table = '".$this->table."'",
				"by_id    = '".$WIN['docid']."'"
			);
			
			if ($id = safe_field("id","txp_group",doAnd($where))) {
				
				safe_update("txp_group",
					"view_settings = '$view', last_mod = now()",
					"id = $id");
			
			} else {
				
				$set = $where;
				$set[] = "view_settings = '$view'";
				$set[] = "last_mod = now()";
				
				safe_insert("txp_group",impl($set));
			} 
		}
		
		// ---------------------------------------------------------------------
		// hide table column headers
		
		function do_hide_headers($selected) 
		{	
			global $WIN;
			
			$WIN['headers'] = ($WIN['headers'] == 'show') ? 'hide' : 'show';
		}
		
		// ---------------------------------------------------------------------
		// hide main article row
		
		function do_hide_main($selected) 
		{	
			global $WIN;
			
			$WIN['main'] = ($WIN['main'] == 'show') ? 'hide' : 'show';
		}
		
		// ---------------------------------------------------------------------
		// flat view
		
		function do_flat_view($selected) 
		{	
			global $WIN;
			
			$WIN['flat'] = ($WIN['flat']) ? 0 : 1;
		}
		
		// ---------------------------------------------------------------------
		// touch item: update modified date without any changes
		
		function do_touch($selected) 
		{	
			foreach ($selected as $id) update_lastmod($id);
		}
		
		// -----------------------------------------------------
		// edit
			
		function do_edit($selected) 
		{ 
			global $WIN;
			
			if ($selected = $this->get_allowed($selected,'edit')) {
			
				$WIN['edit'] = array(0);
				// $WIN['editcol'] = null;
				
				foreach ($selected as $id) {
					$WIN['edit'][$id] = $id;
				}
			}
		}
			
		// -----------------------------------------------------
		// cancel edit
			
		function do_edit_cancel($selected)
		{ 	
			global $WIN;
			
			$WIN['edit'] = array(0);
			$WIN['editcol'] = '';
			
			$this->deselect('all');
		}
		
		// -----------------------------------------------------
		// save
		
		function do_save($selected)
		{
			global $WIN;
			
			$incoming = array();
			$changed  = array();
			
			if ($selected = array_intersect($WIN['edit'],$selected)) {
			
				if ($selected = $this->get_allowed($selected,'edit')) {
					
					foreach($_POST as $name => $array) {
						
						if ($name == 'selected') continue;
						
						if (is_array($array)) {
							
							foreach($array as $id => $value) {
								
								if ($name == 'Category' and strlen($value)) {
									$value = explode(',',$value);
									$last = count($value)-1;
									if (!$value[$last]) unset($value[$last]);
									$value = implode(',',$value);
								}
								
								if (in_array($id,$selected)) {
									$incoming[$id][$name] = $value;
								}
							}	
						}
					}
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			foreach ($incoming as $id => $array) {
				
				content_save($id,$array);
				
				$changed[$id] = $id;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$WIN['edit'] = array(0);
			$WIN['editcol'] = '';
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// clear clipboard
			
		function do_clear_clip($selected)
		{ 
			$_SESSION['clipboard']['cut']   = array();
			$_SESSION['clipboard']['copy']  = array();
			$_SESSION['clipboard']['table'] = '';
			
			$this->deselect('all');
		}
		
		// -----------------------------------------------------
		// open folder
		
		function do_open($selected) 
		{
			global $WIN;	
			
			$changed = array();
			
			foreach ($selected as $id) {
					
				if ($WIN['id'] != $id) {
					
					$WIN['open'][$id] = $id;
					
					$changed[$id] = $id;
				}
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// close folder
		
		function do_close($selected) 
		{
			global $WIN;	
			
			$changed = array();
			
			foreach ($selected as $id) {
					
				if (isset($WIN['open'][$id]) and $WIN['id'] != $id) {
				
					unset($WIN['open'][$id]);
					
					$changed[$id] = $id;
				}
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// move into last selected
			
		function do_move($selected) 
		{	
			// MISTAKE?
			// if ($selected = gps('checked')) {
				
				$selected = expl($selected);
				$destination = array_pop($selected);
				
				if ($destination and $selected) {
					
					// $this->apply('cut',$selected);
					// $this->apply('cut_paste',$destination); 
				}
			// }
		}
		
		// -----------------------------------------------------
		// cut one or more articles
		
		function do_cut($selected) 	
		{	
			global $WIN;
			
		 	$changed = array();
		 	
		 	if ($selected = $this->get_allowed($selected,'delete')) {
			
				if ($_SESSION['clipboard']['cut']) {
					$table = $this->table;
					$this->table = $_SESSION['clipboard']['table'];
					$this->apply('trash',$_SESSION['clipboard']['cut']);
					$this->table = $table;
				}
				
				$clipboard = array();
				
				foreach ($selected as $id) {
				
					if ($id != $this->trash_id and $WIN['id'] != $id) {
					
						$clipboard[$id] = $id;
						$changed[$id] = $id;
						$this->deselect($id);
					}
				}
				
				$_SESSION['clipboard']['table'] = $this->table;
				$_SESSION['clipboard']['cut']   = $clipboard;
				$_SESSION['clipboard']['copy']  = array();
			}
			
			// - - - - - - - - - - - - - - - - - - - - -
			// update children counts 
			
			$parents = array();
			
			if ($changed) {
				
				foreach ($changed as $id) {
					
					$parent = fetch('ParentID',$this->table,'ID',$id);
				
					if (!isset($parents[$parent])) {
						$parents[$parent] = 1;
					} else {
						$parents[$parent] += 1;
					}
				}
			}
			
			foreach ($parents as $id => $removed) {
				
				safe_update($this->table,"Children = Children - $removed","ID = $id");
			}
			
			// - - - - - - - - - - - - - - - - - - - - -
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// copy one or more articles
		
		function do_copy($selected) 	
		{
			if ($_SESSION['clipboard']['cut']) {
				$table = $this->table;
				$this->table = $_SESSION['clipboard']['table'];
				$this->apply('trash',$_SESSION['clipboard']['cut']);
				$this->table = $table;
			}
				
			$_SESSION['clipboard']['table'] = $this->table;
			$_SESSION['clipboard']['cut']   = array();
			$_SESSION['clipboard']['copy']  = array();
			
			foreach ($selected as $id) {
			
				$in_trash = fetch("Trash",$this->table,"id",$id);
				
				if ($id != $this->trash_id and !$in_trash)
					$_SESSION['clipboard']['copy'][$id] = $id;
			}
			
			$this->deselect('all');
		}
			
		// -----------------------------------------------------
		// move one or more articles into a folder
		// TODO: fix bug when pasting to multiple destinations, 
		// 		 only the last one gets the paste
			
		function do_cut_paste($selected) 
		{ 
			$table = $this->table;
			$changed = array();
			$parents = array();
			
			foreach ($selected as $dst) {
				
				$pos       = 999999999;
				$to_note   = fetch('Status',$table,"ID",$dst) == 6;
				$dst_trash = fetch("Trash",$table,"id",$dst);
				$to_trash  = ($dst == $this->trash_id or $dst_trash) ? true : false;
				
				if (!$to_note and !$to_trash) {
				
					$dst_title  = doSlash(fetch("Title",$table,"id",$dst));
					$dst_name   = fetch("Name",$table,"id",$dst);
					$dst_class  = fetch("Class",$table,"id",$dst);
					
					foreach($_SESSION['clipboard']['cut'] as $src) {	
					
						if ($src != $dst) {
						
							extract(safe_row("ParentID,Status",$table,"ID=$src"));
							
							if ($ParentID != $dst) {
								
								// if not already in destination folder
							
								$Trash = ($to_trash) ? $dst_trash : 0;   
								
								$Position = ($Status == 6) ? 0 : $pos++;	// note
								
								safe_update($table, 
									"ParentID         = '$dst',
									 Position		  =  $Position,
									 Trash			  =  0",
									 "ID = $src"
								);
								
								renumerate($ParentID);
								update_lastmod($src);
								
								$changed[$src] = $src;
								
								// checkbox article in new location
								$this->reselect($src);
							}
							
							if (!isset($parents[$dst]))
								$parents[$dst] = 1;
							else
								$parents[$dst] += 1;
						}
					}
				}
			}
			
			foreach ($parents as $id => $added) {
				
				safe_update($this->table,"Children = Children + $added","ID = $id");
				
				if ($changed) {
					
					update_path($changed,'TREE');
					update_parent_info($table,$id);
					
					renumerate($id);
					update_lastmod($id);
					$this->deselect($id); // uncheckbox new parent
				}
			}
			
			
			
			if ($changed and $id == $this->trash_id) {
				
				$this->deselect('all');
			}
			
			$_SESSION['clipboard']['cut'] = array();
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// paste one or more articles
		
		function do_copy_paste($selected) 
		{
			$changed = array();
			$parents = array();
				
			foreach ($selected as $dst) {
				
				$dst_trash = fetch("Trash",$this->table,"id",$dst);
				$to_trash  = ($dst == $this->trash_id or $dst_trash) ? true : false;
				
				if (!$to_trash) {
				
				 	foreach($_SESSION['clipboard']['copy'] as $src) {	
						
						if ($dup_id = $this->paste_copy($src,$dst)) {
							
							// update_path($dup_id,'SELF');
							// update_path($src,'TREE');
						
							// $parents[$dst] = $dst;
							$changed[$dup_id] = $dup_id;
						
							$this->reselect($dup_id); // checkbox new location	
						}	 	
						
						if (!isset($parents[$dst]))
							$parents[$dst] = 1;
						else
							$parents[$dst] += 1;
					}
				}
			}
			
			foreach ($parents as $id => $added) {
				
				if ($changed) {
					
					update_path($changed,'TREE');
					update_parent_info($this->table,$id);
					renumerate($id);
					update_lastmod($id);
					$this->deselect($id); // uncheckbox new parent
				}
			}
			
			if ($changed and $id == $this->trash_id) {
				
				$this->deselect('all');
			}
			
			$_SESSION['clipboard']['copy'] = array();
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// create new untitled article
		
		function do_new($selected,$values=array()) 	
		{	
			global $WIN;
			
			$changed = array();
			
			if (!$selected)
				$selected = array($WIN['id']);
			
			if ($selected = $this->get_allowed($selected,'edit')) {
				
				foreach ($selected as $id) {
				
					$WIN['open'][$id] = $id;
					
					if (in_list($WIN['content'],'image,file')) {
						$values['Type'] = 'folder';
					}
					
					if ($WIN['content'] == 'custom') {
						$values['Type'] = 'text';
					}
					
					if ($WIN['content'] == 'users') {
						$values['Type'] = 'user';
					}
					
					list($message,$new_id) = content_create($id,$values);
					
					$this->deselect('all'); 		// uncheckbox parent article
					$this->reselect($new_id); 	// checkbox new article
					
					$changed[$new_id] = $new_id;
				}
				
				$this->apply('edit',$this->selected);
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// ungroup one or more folders
		
		function do_ungroup($selected) 	
		{
			global $WIN;
			
			$changed = array();
			
			if ($selected = $this->get_allowed($selected,'edit')) {	
				
				if (!in_array($WIN['id'],$selected)) {
					
					$ids = in($selected);
					
					$ids = safe_column(
						"ID,ParentID",$this->table,
						"ID IN ($ids) ORDER BY Level DESC");
					
					foreach ($ids as $id => $ParentID) {
						
						$Position = fetch("Position",$this->table,"ID",$id);
						$Children = safe_column("ID",$this->table,
							"ParentID = $id ORDER BY Position ASC");
						
						if ($Children) {
						
							foreach ($Children as $Child) {
								
								if (!count($this->apply('cut',$Child))) continue;
								if (!count($this->apply('paste',$ParentID))) continue;
								
								$this->set_position($Child,$Position++,-1);
							}
							
							if (!safe_count($this->table,"ParentID = $id")) {
							
								$this->apply('trash',$id);
							}
						}
						
						renumerate($ParentID);
					}
				}
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// group one or more articles under a new article
		
		function do_group($selected) 	
		{
			global $WIN;
			
			$changed = array();
			
			if ($selected = $this->get_allowed($selected,'edit')) {	
				
				if (!in_array($WIN['id'],$selected)) {
				
					$ids = in($selected);
					$top = reset($selected);
					
					extract(safe_row("ParentID",$this->table,"ID IN ($ids) ORDER BY Level ASC LIMIT 1"));
					
					$position = fetch("Position",$this->table,"ID",$top);
					$status   = fetch("Status",$this->table,"ID",$top);
					
					$this->apply('new',$ParentID); 
					
					if ($this->changed) {
						
						$new = array_pop($this->changed);
						
						$this->apply('cut',$selected);
						$this->apply('paste',$new);
						
						if ($WIN['view'] == 'tr') {
							$this->apply('open',$new);
						}
						
						$this->set_position($new,$position);
						
						content_save($new,array('Status'=>$status));
						add_folder_image($new);
						
						$changed = $selected;
						
						$this->selected = $selected;
						$this->reselect($new);
					}
				}
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// make alias articles
		
		function do_alias($selected) 	
		{
			global $WIN;
			
			$changed = array();
			
			foreach ($selected as $id) {
					
				if ($id == $WIN['id']) continue;
				if ($id == $this->trash_id) continue;
				
				$existing_aliases = get_aliases($id);
				
				$rs = safe_row("*",$this->table,"ID=$id");
				
				$Posted      = $rs['Posted'];
				$ParentID    = $rs['ParentID'];
				$Alias       = $rs['Alias'];
				$ParentTitle = doStrip(fetch("Title",$this->table,"ID",$ParentID));
				
				$sec = min(59,count($existing_aliases) + 1);
				
				// if alias does not have a title let's put in the parent title
				// which makes more sense in a new location
				
				$rs['ID']		  = "ID";	
				$rs['Title'] 	 .= (!$rs['Title']) ? doSlash($ParentTitle) : '';
				$rs['Position']	  = 999999999;
				$rs['Alias']	  = ($Alias == 0) ? $id : $Alias;
				$rs['Posted']	  = "ADDTIME('$Posted','0 0:0:$sec')";
				$rs['Name']      .= (!$rs['Name']) ? doSlash(make_name($ParentTitle)) : '';
				
				if (isset($rs['url_title'])) {
					$rs['url_title'] .= (!$rs['url_title']) ? doSlash(make_name($ParentTitle)) : '';
				}
				
				list($message,$alias_id) = content_create($ParentID,$rs);
				
				$this->deselect($id);			// uncheckbox original article
				$this->reselect($alias_id);		// checkbox alias article
				
				$changed[$alias_id] = $alias_id;
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// duplicate one or more articles
		
		function do_duplicate($selected) 	
		{
			global $WIN;
			
			$changed = array();
			
			if ($selected = $this->get_allowed($selected,'edit')) {	
				
				foreach ($selected as $id) {
						
					if ($id == $WIN['id']) continue;
					if ($id == $this->trash_id) continue;
					
					$ParentID = fetch("ParentID",$this->table,"ID",$id);
					
					$this->apply('copy',$id);
					$this->apply('paste',$ParentID);
					
					if ($this->changed) {
					
						$copy_id = array_pop($this->changed);
					
						// update_path($copy_id,'SELF');
						// update_path($id,'TREE');
						renumerate($ParentID);
					
						$changed[$copy_id] = $copy_id;
						
						$this->deselect($id);			// uncheck original article
						$this->reselect($copy_id);		// check duplicate article
						
						if (column_exists($this->table,'Copy')) {
						
							safe_update($this->table,"Copy = $id","ID = $copy_id");
						}
					}
				} 
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// reposition
		
		function do_reposition($selected) 
		{
			global $WIN;
			
			$changed = array();
			$sortdir = $WIN['sortdir'];
			$method  = $this->method;
			$columns = gps('columns',1);
			
			if ($WIN['sortby'] == 'position') {
			
				$inc = 1 * $columns;
				
				if (($sortdir == 'asc' and $method == 'move_up') or
					($sortdir == 'desc' and $method == 'move_down')) {
					
					$inc = -1 * $columns;
				} 
				
				if (($sortdir == 'asc' and $method == 'move_left') or
					($sortdir == 'desc' and $method == 'move_right')) {
					
					$inc = -1;
				
				} elseif (in_list($method,'move_left,move_right')) {
					
					$inc = 1;
				}
				
				if ($selected = $this->get_allowed($selected,'edit')) {
				
					if (($sortdir == 'asc' and $inc >= 1) or
						($sortdir == 'desc' and $inc <= -1)) {
						
						$selected = array_reverse($selected);
					}
					
					foreach ($selected as $id) {
						
						if ($id == $WIN['id']) continue;
						if ($id == $this->trash_id) continue;
						
						$Position = fetch("Position",$this->table,"ID",$id);
						$ParentID = fetch("ParentID",$this->table,"ID",$id);
						$MaxPos   = safe_field("Position",$this->table,
							"ParentID = $ParentID AND Trash = 0 AND Status != 2 ORDER BY Position DESC");
						$Trash = fetch("Trash",$this->table,"ID",$id);
						
						if ($Trash) continue;
						
						if ($inc >= 1) 
							$Position = ($Position >= $MaxPos) ? 0.5 : $Position + $inc;
						else
							$Position = ($Position <= 1) ? $MaxPos + 1.1 : $Position + $inc;
						
						$row = safe_row("ID",$this->table,"ParentID = $ParentID AND Position = $Position AND Trash = 0");
						
						$this->set_position($id,$Position,$inc);
						
						$changed[$id] = $id;
						
						if ($inc >= 1 and $row) {
							update_lastmod($row['ID']);
						}
					}
				}
				
				return $changed;
			}
		}
		
		// -----------------------------------------------------
		// toggle folder image
		
		function do_folder_image($selected) 	
		{
			$changed = array();
			
			if ($selected = $this->get_allowed($selected,'edit')) {
			
				foreach ($selected as $id) {
					
					if (safe_count($this->table,"ID = $id AND ImageID > 0")) {
					
						safe_update($this->table,"ImageID = 0","ID = $id");
						
						$changed[$id] = $id;
					
					} else {
					
						add_folder_image($id);
						
						if (safe_count($this->table,"ID = $id AND ImageID > 0")) {
							
							$changed[$id] = $id;
						}
					}
				}
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// add image
		
		function do_add_image($selected) 	
		{	
			global $WIN;
			
			$image_id = assert_int(gps('image'),0);
			$selcol   = gps('selcol');
			$new      = false;
			
			if ($image_id) {
				
				if (!$selected or ($selected and !$selcol)) {
					
					$new = true;
					
					$this->apply('new',$selected);
					
					$selected = $this->changed;
				}
				
				if ($selected) {
			
					$set = array('ImageID' => $image_id);
					
					if ($new) {
						$row = safe_row("Title,Name,Status","txp_image","ID = '$image_id'");
						$set = array_merge($set,$row);			
					}
					
					foreach($selected as $id) {
						
						content_save($id, $set);
						
						if (isset($WIN['edit'][$id])) {
							unset($WIN['edit'][$id]);
						}
					}
				}
			}
		}
			
		// -----------------------------------------------------
		// export
		
		function do_export($selected) 	
		{	
			export($selected);
		}
			
		// -----------------------------------------------------
		// import
			
		function do_import($selected) 	
		{	
			global $WIN;
			
			$id = array_shift($selected);
			
			import($id,$WIN['table'],$WIN['content']);
		}
		
		// -----------------------------------------------------
		// trash
			
		function do_trash($selected) 	
		{
			global $PFX, $WIN, $prefs;
			
			$changed = array();
			$parents = array();
			
			if ($selected = $this->get_allowed($selected,'delete')) {
				
				foreach ($selected as $key => $id) {
					
					$ParentID = fetch("ParentID",$this->table,"ID",$id);
					
					if ($id != $WIN['id'] and $id != $this->trash_id) {
					
						safe_update($this->table, "Trash = 1", "ID = $id");
						safe_update_tree($id,$this->table,"Trash = 2");
						
						renumerate($ParentID,0,0,$this->table);
						update_lastmod($id);
						
						$changed[$id] = $id;
						
						if (!isset($parents[$ParentID]))
							$parents[$ParentID] = 1;
						else
							$parents[$ParentID] += 1;
					}
				}
			}
			
			$total_removed = 0;
			
			foreach ($parents as $id => $removed) {
			
				safe_update($this->table,"Children = Children - $removed","ID = $id");
				
				$total_removed += $removed;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			// update article/image/file count in txp_site table
			
			if ($total_removed and table_exists('txp_site')) {
			
				$set = '';
		
				if ($this->table == 'textpattern') {
					$count = getCount($this->table,
						"Trash = 0 AND ParentID != 0 AND Type != 'trash'");
					$set = "Articles = $count";
				}
				
				if ($this->table == 'txp_image') {
					$count = getCount($this->table,
						"Trash = 0 AND ParentID != 0 AND Type = 'image' AND ImageID != 0");
					$set = "Images = $count";
				}
				
				if ($this->table == 'txp_file') {
					$count = getCount($this->table,
						"Trash = 0 AND ParentID != 0 AND Type NOT IN ('folder','trash')");
					$set = "Files = $count";
				}
			
				$pfx = $PFX;
				$PFX = '';
				
				if ($set) {
					$url = 'http://'.$prefs['siteurl'];
					safe_update('txp_site',$set,"URL = '$url'");
				}
			
				$PFX = $pfx;
			}
					
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// trash
			
		function do_untrash($selected) 	
		{
			global $WIN;
			
			$changed = array();
			
			if ($selected = $this->get_allowed($selected,'delete')) {
			
				foreach ($selected as $key => $id) {
				
					$ParentID = fetch("ParentID",$this->table,"ID",$id);
					
					if ($id != $WIN['id'] and $id != $this->trash_id) {
					
						safe_update($this->table, "Trash = 0", "ID = $id");
						safe_update_tree($id,$this->table,"Trash = 0");
						
						renumerate($ParentID);
						update_lastmod($id);
						
						$changed[$id] = $id;
					}
				}
			}
			
			return $changed;
		}
		
		// -----------------------------------------------------
		// empty trash
		
		function do_empty_trash($selected) 	
		{
			$where = array("Trash >= 1");
			
			/* if ($selected) {
				$where[] = "ID IN (".in($selected).")";
			} */
			
			$selected = safe_column("ID",$this->table,doAnd($where),2);
			
			return $this->delete_from_trash($selected);
		}
		
		// ---------------------------------------------------------------------
		// delete from from trash
		
		function delete_from_trash($selected)
		{ 
			global $event;
			
			$changed = array();
			
			$selected = $this->get_allowed($selected,'delete');
			$selected = delete_alias_articles($selected); // add alias articles
			$selected = $this->get_allowed($selected,'delete');
			
			$delay = 1; // delay permanent deletion for 1 day
			$delay = 0; // no delay
			
			foreach ($selected as $id)
			{
				if (getCount($this->table,"ID = $id AND Trash IN (1,2)")) {
					
					// mark for permanent removal from trash
					
					safe_update($this->table,"Trash = Trash + 2","ID = $id");
					
					$changed[$id] = $id;
					
				} elseif (getCount($this->table,"ID = $id AND Trash IN (3,4) AND LastMod < SUBDATE(NOW(),$delay)")) {
				
					// delete permanently
					
					if ($event == 'file')  file_delete($id);
					if ($event == 'image') image_delete($id);
				
					safe_delete($this->table,"ID = $id AND Trash IN (3,4)");
					safe_delete("txp_content_category","article_id = $id");
				}
			}
			
			if ($changed) {
				
				safe_update('txp_discuss', "Status = ".MODERATE, "article_id IN (".in($changed).")");
				
				$parents = safe_column("ID,ParentID",$this->table,"ID IN (".in($changed).")");
				foreach($parents as $pid) renumerate($pid,0,0,$this->table);
				
				update_category_count();
			}
			
			return $changed;
		}
		
		// ---------------------------------------------------------------------
		
		function get_allowed($selected,$method) 
		{
			global $WIN, $txp_user;
			
			// $content = ($this->table == 'textpattern') ? 'article' : substr($this->table,4);
			$content = $WIN['content'];
			$selected = do_list($selected);
			
			$sort = (isset($WIN['sortby']))  ? $WIN['sortby']  : 'Posted';
			$dir  = (isset($WIN['sortdir'])) ? $WIN['sortdir'] : 'DESC';
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($method == 'delete') {
			
				if (!has_privs($content.'.delete'))
				{
					$allowed = array();
					
					if (has_privs($content.'.delete.own'))
					{
						foreach ($selected as $id)
						{
							$author = safe_field('AuthorID', $this->table, "ID = $id");
							
							if ($author == $txp_user)
							{
								$allowed[$id] = $id;
							}
						}
					}
		
					$selected = $allowed;
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($method == 'edit') {
				
				$db_selected = safe_column('ID,AuthorID,Status', $this->table, "ID in (".in($selected).") AND Trash = 0");
				
				$allowed = array();
				
				foreach ($selected as $id)
				{
					if (isset($db_selected[$id])) {
					
						$item = $db_selected[$id];
					
						if ( ($item['Status'] >= 4 and has_privs($content.'.edit.published'))
						  or ($item['Status'] >= 4 and $item['AuthorID'] == $txp_user and has_privs($content.'.edit.own.published'))
						  or ($item['Status'] < 4 and has_privs($content.'.edit'))
						  or ($item['Status'] < 4 and $item['AuthorID'] == $txp_user and has_privs($content.'.edit.own')))
						{
							$allowed[$id] = $id;
						}
					}
				}
	
				$selected = $allowed;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			return $selected;
		}
		
		// ---------------------------------------------------------------------
		
		function paste_copy($src_id,$dst_id,$top=true) 
		{
			$src_table = $_SESSION['clipboard']['table'];
			$dst_table = $this->table;
			
			if ($src_id == $dst_id and $src_table == $dst_table) {
			
				return 0;
			}
			
			$src = safe_row("*",$src_table,"ID = $src_id");
			$dst = safe_row("Title,Class,Name",$dst_table,"ID = $dst_id");
			
			if ($src) {
				
				extract($src);
				
				if ($top) {
				
					if ($ParentID == $dst_id) {
						$Title 		= $this->copy_title($Title,$dst_id);
						$Name  		= ($dst_table == 'textpattern') ? $this->copy_name($Name,$dst_id) : $Name;
					}
					
					$Position = ($Status == 2) ? 0 : 999999999;
				}
				
				$dst_title 	= $dst['Title'];
				$dst_class 	= $dst['Class'];
				$dst_name 	= $dst['Name'];
				
				$src['ID'] 			= "ID";
				$src['Title'] 		= $Title; 
				$src['Name'] 		= $Name; 	
				$src['Class'] 		= $Class; 
				$src['ParentID'] 	= $dst_id; 
				$src['Posted'] 		= "now()"; 		
				$src['LastMod'] 	= "now()"; 		
				$src['Position'] 	= $Position;
	
				list($message,$cpy_id) = content_create($dst_id,$src);
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// CUSTOM FIELDS
				
				// duplicate custom fields values
				
				$rows = safe_rows('*','txp_content_value',
					"tbl = '$src_table' 
					 AND article_id = $src_id 
					 AND status = 1");
				
				foreach ($rows as $row) {
					
					extract($row);
					
					$id = safe_field('id','txp_content_value',
						"tbl = '$dst_table' 
						 AND group_id = group_id
						 AND article_id = $cpy_id
						 AND field_id = $field_id");
						 
					if ($id) {
						
						safe_update('txp_content_value',
							"num_val = '$num_val',
							 text_val = '$text_val'",
							"id = $id");
					
					} else {
						
						unset($row['id']);
						
						$is_by_id = safe_count("txp_group",
							"by_id = $src_id 
							 AND group_id = $group_id 
							 AND field_id = $field_id");
							
						if (!$is_by_id) {
						
							$row['tbl']        = $dst_table;
							$row['article_id'] = $cpy_id;
						
							safe_insert('txp_content_value',$row);
						}
					}
				}
				
				// duplicate custom fields grouped by ID 
				
				$rows = safe_rows("*","txp_group",
					"by_id = $src_id AND status = 'active'");
				
				foreach ($rows as $row) {
					
					unset($row['id']);
					
					$row['by_id']    = $cpy_id;
					$row['group_id'] = $group_id = fetch("MAX(group_id) + 1","txp_group");
					
					safe_insert("txp_group",$row);
					
					// duplicate value if any 
					
					$row = safe_row("*","txp_content_value","article_id = $src_id");
					
					if ($row) { 
						
						unset($row['id']);
						
						$row['tbl']        = $dst_table;
						$row['article_id'] = $cpy_id;
						$row['group_id']   = $group_id;
					
						safe_insert('txp_content_value',$row);
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// children 
				
				if ($children = safe_column("ID",$src_table,"ParentID = $src_id")) {
				
					foreach ($children as $id) {
						$this->paste_copy($id,$cpy_id,false);
					}
				}
				
				return $cpy_id;
			}
		}
		
		// ---------------------------------------------------------------------
		
		function copy_title($title,$dst_id) 
		{
			$number   = 1;
			$newtitle = $title." Copy";
			
			// check if this name already exists in destination folder 
			/*
			if (getCount($this->table,"ParentID = $dst_id AND Title = '$newtitle'")) {
			
				$newtitle = $title." Copy ".++$number;
				
				// check it one more time
				
				if (getCount($this->table,"ParentID = $dst_id AND Title = '$newtitle'")) {
				
					$newtitle = preg_replace('/'.$number.'$/',++$number,$newtitle);
				}
			}
			*/
			return $newtitle;
		}
		
		// ---------------------------------------------------------------------
		
		function copy_name($name,$dst_id) 
		{	
			$number  = 0;
			$newname = $name."-copy-".++$number;
			$newname = $name."-copy";
			
			// check if this name already exists in destination folder 
			/*
			if (getCount($this->table,"ParentID = $dst_id AND Name = '$newname'")) {
			
				$newname = preg_replace('/'.$number.'$/',++$number,$newname);
				
				// check it one more time
				
				if (getCount($this->table,"ParentID = $dst_id AND Name = '$newname'")) {
				
					$newname = preg_replace('/'.$number.'$/',++$number,$newname);
				}
			}
			*/
			return $newname;
		}
		
		// ---------------------------------------------------------------------
		
		function set_position($id,$position,$inc=0) 
		{	
			$pos = $position;
			if ($inc <= 0) $pos = $position - 0.5;
			if ($inc > 0)  $pos = $position + 0.5;
			
			safe_update($this->table,"Position = $pos","ID = $id");
			safe_update($this->table,"ParentPosition = $position","ParentID = $id");
			
			update_lastmod($id);
			
			renumerate(fetch("ParentID",$this->table,"ID",$id));
			update_parent_info($this->table,$id);
			update_alias_articles($id);
		}
	}
?>
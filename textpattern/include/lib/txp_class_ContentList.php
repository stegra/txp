<?php

	class ContentList {
		
		var $content = '';
		var $table   = '';
		var $view	 = '';
		var $sortby  = '';
		var	$sortdir = '';
		var $root    = 0;
		var $flat    = false;
		var $more 	 = 0;
		
		var $selected = array();
		var $open	  = array();
		var $clip	  = array();
		var $edit	  = array();
		
		var $visible = array();
		var $custom  = array();
		
		var $include = array();
		var $exclude = array();
		
		var $list	 = array();
		
	// -------------------------------------------------------------------------
		function ContentList() 
		{ 	
			global $WIN, $event;
			
			if ($open = gps('open')) {
				
				foreach(expl($open) as $id) {
					$WIN['open'][$id] = $id;
				}
			}
					
			if ($close = gps('close')) { 
			
				unset($WIN['open'][$close]);
			}
			
			if ($thumb = gps('thumb')) {
				$WIN['thumb'] = $thumb;
			}
			
			if ($view = gps('view')) {
			
				if ($view == 'div') {
					$WIN['open']  = array(0);
					$WIN['thumb'] = 'x';
				}
			
				if ($view == 'tr') {
					$WIN['thumb'] = 'z';
				}
			}
			
			if ($more = gps('more')) { 
			
				if ($WIN['flat'] and is_numeric($more)) {
					$WIN['flat'] += intval($more);
					$this->more = intval($more);
 				}
			}
			
			$this->content	= $WIN['content'];
			$this->table	= $WIN['table'];
			$this->view		= $WIN['view'];			// ?
			$this->sortby   = $WIN['sortby'];
			$this->sortdir  = $WIN['sortdir'];
			$this->linkdir  = $WIN['linkdir'];
			$this->clip		= array(
				'copy'=> array(),
				'cut' => array()
			);
			
			if (isset($WIN['checked'])) {
				
				$this->root     = $WIN['id'];
				$this->flat 	= $WIN['flat'];
				$this->selected = $WIN['checked'];
				$this->open 	= $WIN['open'];
				$this->edit 	= $WIN['edit'];
				
				if (isset($_SESSION['clipboard'])) {
					$this->clip = $_SESSION['clipboard'];
				}
				
				$this->visible	= $this->get_visible_columns(); 
				$this->custom	= $this->get_custom_columns(); 
			}
			
			$this->deepest = safe_field("MAX(Level)",$this->table,"1=1"); 
			
			if ($columns = gps('columns')) {
				
				$columns = expl($columns);
				
				foreach($columns as $col) {
					$this->toggle_column($col);
				}
				
				$this->visible	= $this->get_visible_columns(); 
				$this->custom 	= $this->get_custom_columns(); 
			}
			
			if (gps('step') == 'hoist') {
				
				$where = array(
					"`type` = 'view'",
					"by_table = '".$this->table."'",
					"by_id    = '".$WIN['docid']."'"
				);
				
				if (column_exists("txp_group","view_settings")) {
					
					if ($view = safe_field("view_settings","txp_group",doAnd($where))) {
				
						$view = unserialize($view);	
						
						$WIN['sortby']  = $this->sortby  = $view['sortby'];
						$WIN['sortdir'] = $this->sortdir = $view['sortdir'];
						$WIN['linkdir'] = $this->linkdir = $view['linkdir'];
						
						foreach (expl($view['open']) as $id) {
							$this->open[$id] = $id;
						}
						
						$WIN['open'] = $this->open;
						
						foreach ($view['columns'] as $name => $col) {
							
							if ($col['on']) {
								
								if (!isset($this->visible[$name])) {
									
									$this->visible[$name] = $col;
								}
							
							} elseif (isset($this->visible[$name])) {
								
								unset($this->visible[$name]);
							}
						}
					}
				}
			}
		}
		
	// -------------------------------------------------------------
		function getList() 
		{	
			global $WIN;
			
			$this->exclude = array();
			$this->include['customfields'] = array();
			
			if (isset($_SESSION['clipboard'])) {
				if ($_SESSION['clipboard']['table'] == $this->table) {
					$this->exclude = $_SESSION['clipboard']['cut'];
				}
			}
					
			foreach($this->custom as $col) {
				if ($col['show']) { 
					$this->include['customfields'][] = $col['ID'];
				}
			}
			
			/* foreach($this->custom as $col) {
					
				if ($col['show']) {
					$name = 'custom.'.$col['name'];
					$this->include[$name] = array( 
						'title' => $col['title'],
						'on'	=> 1,
						'pos'	=> 99
					);
				}
			} */
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$q = $this->buildQuery();
			
			if (!$this->more) {
				// dont' get the main article for ajax load more
				$this->getMain($this->root,$q);
			}
			
			if ($ids = $this->applyFilters($this->root)) {
				
				$q = $this->buildQuery();
				
				$this->getTree($ids,$q);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// unset all selected items that are no longer visible
			// due to their parents being closed
			
			$ids = array();
			
			foreach ($this->list as $row) {
				$ids[] = $row['ID'];
			}
			
			foreach ($this->selected as $key => $id) {
				if (!in_array($id,$ids)) unset($this->selected[$key]);
			}
			
			$WIN['checked'] = $this->selected;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// previous and next neighbor ids for each visible article
			
			$prev = 0;
			
			$WIN['prevnext'] = array();
			
			foreach ($this->list as $key => $row) {
				
				$id = $row['ID'];
				
				$this->list[$key]['EDIT'] = (isset($WIN['edit'][$id])) ? 1 : 0;
				
				$WIN['prevnext'][$id] = "$prev,0";
				
				if ($prev) {
					$WIN['prevnext'][$prev] = str_replace(",0",",$id",$WIN['prevnext'][$prev]);
				}
				
				$prev = $id;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// neighbor ids for list prev/next links 
			
			$prev  = 0;
			$docid = $WIN['docid'];
			// unset($WIN['prevnextlist']);
			
			if (!isset($WIN['prevnextlist'])) {
				$WIN['prevnextlist'] = array();
			}
			 
			if (count($this->list) > 1) {
				
				$WIN['prevnextlist'][$docid] = array();
				
				foreach ($this->list as $key => $row) {
				
					if ($row['Level'] == 2) {
					
						$id = $row['ID'];
						
						$WIN['prevnextlist'][$docid][$id] = "$prev,0";
						
						if ($prev) {
							$WIN['prevnextlist'][$docid][$prev] = str_replace(",0",",$id",$WIN['prevnextlist'][$docid][$prev]);
						}
						
						$prev = $id;
					}
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			return $this->list;
		}
	
	// -------------------------------------------------------------
		function applyFilters($context) 
		{
			global $WIN;
			
			$ids = $context;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (!safe_count($this->table,"ID = '$context' AND ParentID = 0")) {
				
				return $ids;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// image filter 
			
			if (isset($WIN['filter']['image'])) {
				
				$ids = array();
				
				if ($WIN['filter']['image']['result']) {
					
					$ids = explode(',',$WIN['filter']['image']['result']);
				
				} else { 
				
					$image = $WIN['filter']['image']['search'];
					
					$type = fetch("Type","txp_image","ID",$image);
					$ids  = $image;
					
					if ($type == 'folder') {
						
						$ids = safe_rows_tree($image,"ID","txp_image","Type = 'image'"); 
						foreach ($ids as $key => $id) $ids[$key] = $id['ID']; 
						$ids = impl($ids);
						
						$this->sortby   = $WIN['sortby']  = "ImageID";
						$this->sortdir  = $WIN['sortdir'] = "DESC";
					}
					
					$ids = safe_rows_tree($context,"ID",$this->table,"ImageID IN ($ids)");
					foreach ($ids as $key => $id) $ids[$key] = $id['ID']; 
					
					$WIN['filter']['image']['result'] = implode(',',$ids);
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			return $ids;
		}
				
	// -------------------------------------------------------------
		function viewList($list=NULL) 
		{
			global $WIN, $event, $statuses, $site_http, $site_base_path, $smarty, $app_mode;
			
			extract(get_prefs('img_dir,file_dir'));
			
			if ($app_mode == 'async' and !gps('refresh_content')) return;
			
			$list = ($list) ? $list : $this->list;
			
			if ($scroll = gps('scroll')) {
				$WIN['scroll'] = $scroll;
			}
			
			$total = count($list) - 1;
			
			$maxlevel   = 1;
			$row_count  = 1;
			
			$path       = array();
			$ids		= array();
			$notes		= array();
			$columns	= $WIN['columns'];
			$authors    = safe_column("name","txp_users",1);
			$trash_cnt  = getCount($this->table,"Trash = 1");
			$tilt		= array('left','right');
			$column_count = count($this->visible) + count($this->include['customfields']);
			$main_item  = '';
			
			foreach($list as $item) {
				if ($item['Level'] > $maxlevel) 
					$maxlevel = $item['Level'];
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// classes
			
			$classtype = (in_list($this->content,'article,image,file,link')) 
				? $this->content
				: 'article';
			
			$classes = safe_column(
				"Name,Title",
				"txp_category",
				"`Class` = 'yes' AND Trash = 0");
			
			$classes = array_merge(array('NONE'=>''),$classes);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$titles = array();
			$path_titles = array();
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$smarty->assign('siteurl',$site_http);
			$smarty->assign('winid',$WIN['winid']);
			$smarty->assign('view',$WIN['view']);
			$smarty->assign('is_view_grid',$WIN['view'] == 'div');
			$smarty->assign('is_view_list',$WIN['view'] == 'tr');
			$smarty->assign('is_flat',$WIN['flat']);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			foreach($list as $item_key => $row) {
				
				$ID 	 = $row['ID'];
				$Type	 = $row['Type'];
				$ImageID = $row['ImageID'];
				$FileID  = $row['FileID'];
				$Alias   = $row['Alias'];
				$Status  = $row['Status'];
				$Level	 = $row['Level'];
				$Trash	 = $row['Trash'];
				$Path    = $row['Path'];
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$edit_row    = (isset($WIN['edit'][$ID])) ? true : false;
				$is_checked  = ($edit_row || ($this->selected && in_array($ID,$this->selected))) ? 1 : 0;
				$edit_column = $WIN['editcol'];
				
				$display_mode = ($edit_row) ? 'edit' : 'view';
				$edit_row     = ($edit_row and $edit_column) ? false : $edit_row;
				
				$no_edit = (isset($row['no_edit'])) ? $row['no_edit'] : array();
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$image_name  = '';
				$image_ext   = '';
				$image_id    = '';
				$image_path  = '';
				$image_trsp  = '';
				$image_width  = 20;
				$image_height = 20;
				$image_size   = '';
				
				if (isset($columns['Title']['path'])) {
					unset($columns['Title']['path']);
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// regular column values
				
				foreach($columns as $name => $col) {
								
					$edit  = $columns[$name]['edit'] = false;
					$value = (isset($row[$name])) ? $row[$name] : null;
					
					if (($edit_row or $edit_column == strtolower($name))
					    and !in_array($name,$no_edit)
					    and $is_checked 
					    and $col['editable']) {
						
						$edit = $columns[$name]['edit'] = true;
					}
					
					if (!$edit) {
						
						if (isset($col['options']) and isset($col['options'][$value])) {
							
							$value = $col['options'][$value];
						}
					} 
					
					$columns[$name]['value'] = $value;
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// class
					
					if ($name == 'Class') {
					
						if ($edit) {
						
							$columns['Class']['value']   = $row['ClassName'];	
							$columns['Class']['options'] = $classes;	
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// section
				
					if ($name == 'Section') {
						
						$columns['Section']['value'] = fetch('title','txp_section','name',$value);
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// categories
				
					if ($name == 'Categories') {
						
						$columns['Categories']['value'] = $this->get_categories_column_value($row,$edit);
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// language
				
					if ($name == 'Language') {
						
						$columns['Language']['value'] = $this->get_language_value($value,$edit);
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// body & excerpt
				
					if ($name == 'Body' or $name == 'Excerpt') {
						
						if (strlen($value)) {
						
							if ($edit) {
							
								$value = fetch($name,$this->table,"ID",$ID);
								$columns[$name]['value'] = doStrip($value);
								
							} else {
								
								$body_left = ($WIN['thumb'] == 'y') ? 115 : 40;
								
								if (strlen($value) == $body_left) {
									$words = preg_split('/\s+/',$value);
									array_pop($words);
									$value = implode(' ',$words).'...';
								}
								
								$value = htmlentities($value);
								
								if (strlen($value)) {
									$value = '<div>'.$value.'</div>';
								}
							}
							
							$columns[$name]['value'] = $value;
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// status
					
					if ($name == 'Status') {
					
						$Status = (!$Status) ? 1 : $Status;
						
						if ($edit) {
							
							$columns['Status']['value']   = $Status;
							$columns['Status']['options'] = $statuses;
						
						} elseif (isset($statuses[$Status])) {
							
							$columns['Status']['value'] = $statuses[$Status];
						
						} elseif ($Status == 7) {
						
							$columns['Status']['value'] = gTxt('pending');
						
						} elseif ($Status == 5) {
							
							$columns['Status']['value'] = gTxt('sticky');
							
						} else {
						
							$columns['Status']['value'] = $Status;
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// author id
					
					if ($name == 'AuthorID') {
						
						if ($edit) {
							$columns['AuthorID']['options'] = $authors;
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// posted date
					
					if ($name == 'Posted') {
						
						$columns['Posted']['value'] = $this->dateFormat($value);
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// last modified date
					
					if ($name == 'LastMod') {
						
						$columns['LastMod']['value'] = $this->dateFormat($value,1);
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// position
				
					if ($name == 'Position') {
					
						if ($edit) {
							
							$maxpos = $row['maxpos'];
							$positions = array();
							
							if ($maxpos > 999) $maxpos = 99;	// prevent fatal error if renumeration failed
							
							for ($i = 1; $i <= $maxpos; $i++) { $positions[$i] = $i; }
							
							$columns['Position']['options'] = $positions;
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// title
					
					if ($name == 'Title') {
					
						$titles[] = strlen($value);
						$path_titles[$ID] = $value;
						
						if ($edit) {
						
							$value = escape_title($value);
						
						} else {
						
							if (strlen($row['Title_html'])) {
							
								$value = $row['Title_html'];
							
							} elseif (!strlen($value)) {
							
								$body = fetch('LEFT(Body,50)',$this->table,"ID",$ID);
							
								if (strlen($body)) {
								
									// strip html tags if any
									$body = preg_replace('/\<[^\>]+?\/?\>/','',$body);
								
									$body = explode(' ',$body);
									
									while ($body and strlen($value) < 30) {	
										$value .= array_shift($body).' ';
									}
									
									$value = rtrim(rtrim($value),',:;');
									
									if (count($body)) $value .= '&#8230';
								}
							}
						}
						
						$columns['Title']['value'] = (!strlen($value)) ? gTxt('untitled') : $value;
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// thumbnail image
					
					if ($name == 'Image') {
						
						$image_size = $WIN['thumb'];
						
						if ($value and $ImageID > 0) {
							
							$value = explode('.',$value);
							
							if (count($value) == 2) { 
							
								list($image_name,$image_ext) = $value;
							
								$image_id   = $ImageID;
								$image_path = ($event == 'image' and $Type == 'image')
									? $row['FilePath']
									: fetch("FilePath","txp_image","ID",$ImageID);
								
								$image_trsp = $row['transparency'];
							}
							
							$value = implode('.',$value);
						}
						
						switch ($WIN['thumb']) {
							case 'xx' : $image_width = $image_height = 150; break;
							case 'x'  : $image_width = $image_height = 100; break;
							case 'y'  : $image_width = $image_height = 50; break;
							case 'z'  : $image_width = $image_height = 20; break;
						}
						
						if ($event == 'sites' and $WIN['view'] == 'div') {
							
							switch ($WIN['thumb']) {
								case 'xx' : $image_width = 225; $image_size = 't_225_150'; break;
								case 'x'  : $image_width = 150; $image_size = 't_150_100'; break;
								case 'y'  : $image_width = 75;  $image_size = 't_75_50'; break;
							}
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// other fields with value plus options for edit select menu
				
					if ($edit and is_array($value)) {
					
						$columns[$name]['value']   = $value['value'];
						$columns[$name]['options'] = $value['options'];
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
					// Path 
					
					if ($name == 'Title' and strlen($Path) and $WIN['flat']) {
						
						$path = explode('/',$Path);
						
						if ($path) {
						
							foreach ($path as $key => $item) {
								
								if (isset($path_titles[$item])) {
									$title = $path_titles[$item];
								} else {
									$title = fetch('Title',$this->table,"ID",$item);
									$path_titles[$item] = $title;
								}
								
								$path[$key] = $title;
							}
							
							$columns['Title']['path'] = implode('/',$path);
						}
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// alias
				
				$child_count = $row['child_count'];
				$descendant_count = 0;
				
				if ($row['Alias'] > 0) {
					$child_count = $row['alias_child_count'];
				}
				
				if (isset($row['descendant_count'])) {
					$descendant_count = $row['descendant_count'];
				}
			
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// filename
				
				$file_name = '';
				$file_ext  = '';
				
				if (isset($row['File'])) {
					list($file_name,$file_ext) = explode('.',$row['File']);
				}
				
				if (isset($row['FileExt'])) {
					$file_ext = ltrim($row['FileExt'],'.');
				}
				
				if (isset($row['Play'])) {
					
					$play = explode('.',$row['Play']);
					if (count($play) == 2) {
						$file_name = array_shift($play);
						$file_ext  = array_shift($play);
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// note
				
				if ($Status == 6) {
					$this->add_note($ID);
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$previd = 0;
				$nextid = 0;
				
				if ($Level == 1) {
				
					$parentid = $row['ParentID'];
				
					if (isset($WIN['prevnextlist'])) {
					
						if (isset($WIN['prevnextlist'][$parentid])) {
						
							if (isset($WIN['prevnextlist'][$parentid][$ID])) {
							
								list($previd,$nextid) = explode(',',$WIN['prevnextlist'][$parentid][$ID]);
							}
						}
					}
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$row_pos = $row_count - 2;
				
				if ($this->more) {
					
					$row_pos = ($WIN['flat'] - $this->more) + $row_count - 1;
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// title path to this article
			
				$title_path = new Path($ID);
				
				$title_path->setInc('!ROOT,!SELF');
				$title_path = $title_path->getList('Title','/').'/';
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$smarty->assign('id',$ID);
				$smarty->assign('parent_id',$row['ParentID']);
				$smarty->assign('title_id',(($Alias > 0) ? $Alias : $ID));
				$smarty->assign('title_path',ltrim($title_path,'/'));
				$smarty->assign('item_title',$row['Title']);
				$smarty->assign('item_name',$row['Name']);
				$smarty->assign('event',$WIN['event']);
				$smarty->assign('type',trim($Type,"'"));
				$smarty->assign('status',$row['Status']);
				$smarty->assign('content',$WIN['content']);
				$smarty->assign('level',$Level);
				$smarty->assign('maxlevel',$maxlevel);
				$smarty->assign('row_pos',$row_pos);
				$smarty->assign('child_count',$child_count);
				$smarty->assign('descendant_count',$descendant_count);
				$smarty->assign('trash_cnt',$trash_cnt);
				$smarty->assign('display_mode',$display_mode);
				$smarty->assign('tilt',$tilt[rand(0,1)]);
				$smarty->assign('more',$row['more']);
				$smarty->assign('previd',$previd);
				$smarty->assign('nextid',$nextid);
				
				$smarty->assign('img_dir',$img_dir);
				$smarty->assign('image_name',$image_name);
				$smarty->assign('image_ext',$image_ext);
				$smarty->assign('image_id',$image_id);
				$smarty->assign('image_path',$image_path);
				$smarty->assign('image_trsp',($image_trsp) ? 'transparency' : '');
				$smarty->assign('image_size',$image_size);
				$smarty->assign('image_width',$image_width);
				$smarty->assign('image_height',$image_height);
				$smarty->assign('thumb',$WIN['thumb']);
				$smarty->assign('file_name',$file_name);
				
				$file_exts = explode('.',$file_ext);
				$smarty->assign('file_ext1',array_shift($file_exts));
				$smarty->assign('file_ext2',array_pop($file_exts));
				$smarty->assign('file_path',(($FileID > 0) ? get_file_id_path($FileID) : ''));
				$smarty->assign('show_file_ext',(!$file_name and $file_ext) ? 1 : 0);
				
				$smarty->assign('is_root',(ROOTNODEID == $ID) ? 'root-article' : '');
				$smarty->assign('is_trash',(TRASH_ID == $ID) ? 1 : 0);
				$smarty->assign('is_checked',($is_checked) ? 'checked' : '');
				$smarty->assign('is_open',(in_array($ID,$WIN['open'])) ? 'open' : '');
				$smarty->assign('is_closed',(!in_array($ID,$WIN['open'])) ? 'closed' : '');
				$smarty->assign('is_alias',(($Alias > 0) ? 'alias' : ''));
				$smarty->assign('is_folder',(($child_count) ? 'folder' : ''));
				$smarty->assign('is_leaf',((!$child_count) ? 'leaf' : ''));
				$smarty->assign('is_note',(($Status == 6) ? 'note' : ''));
				$smarty->assign('is_first_row',(($row_count == 2) ? 'first' : ''));
				$smarty->assign('is_last_row',(($row_count == $total+1) ? 'last' : ''));
				$smarty->assign('in_trash',$Trash);
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$smarty->assign('column_data',$this->get_column_value($columns));
				$smarty->assign('custom_column_data',$this->get_custom_column_value($row,$edit_row,$edit_column));
				
				if ($Level == 1 and $WIN['main'] == 'show') {
					$main_item = $smarty->fetch('list/list_item_tr_main.tpl');
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$item = $smarty->fetch('list/list_item_'.$WIN['view'].'.tpl');
				
				$list[$item_key] = preg_replace('/="\s+/','="',$item);
					
				if ($Level > 1) $ids[] = $ID;
				
				$row_count++;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$smarty->assign('list_items_count',count($ids));
			$smarty->assign('column_count',$column_count);
			
			if ($WIN['view'] == 'tr') {
				$list[] = $smarty->fetch('list/list_item_tr_empty.tpl');
			}
			
			$list_items = implode(n,$list);
			
			if (gps('refresh_content') and $this->more) {
				
				return $list_items;	
			}
			
			$smarty->assign('list_item_main',$main_item);
			$smarty->assign('list_items',$list_items);
			
			$list_data = $smarty->fetch('list/list_data.tpl');
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (gps('refresh_content')) {
				
				return $list_data;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// path to main article
		
			$path = new Path($WIN['id']);
			
			if ($WIN['main'] == 'show')
				$path->setInc('ROOT,!SELF');
			else	
				$path->setInc('ROOT,SELF');
			
			$path = $path->getArr();
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// regular column select options
			
			$column_select = array();
			
			foreach ($WIN['columns'] as $name => $col) {
				
				if ($col['pos']) {
					$smarty->assign('name',$name);
					$smarty->assign('title',$col['title']);
					$smarty->assign('show',$col['on']);
					$column_select[] = $smarty->fetch('list/column_select.tpl');
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// custom column headers and select options
			
			$column_custom_select = array();
			$custom_headers = array();
			
			foreach ($this->custom as $col_id => $col) {
				
				$smarty->assign('col_custom_id',$col_id);
				$smarty->assign('col_custom_title',$col['Title']);
				$smarty->assign('col_custom_show',$col['show']);
				
				$column_custom_select[] = $smarty->fetch('list/column_custom_select.tpl');
				
				if ($col['show'] == 1) {
					$custom_headers[] = $col['Title'];
				}
			}
		
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$clipboard = '';
			
			if (count($this->clip['cut']))  $clipboard = 'cut';
			if (count($this->clip['copy'])) $clipboard = 'copy';
			
			$mode = (count($WIN['edit']) > 1) ? 'edit' : 'read';
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// average title length 
			
			$sum_title = 0;
			$avg_title = 10;
			
			if (count($titles)) {
			
				foreach ($titles as $length) {
					$sum_title += $length;
				}
				
				$avg_title = round(($sum_title/count($titles))/10)*10;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// new article fields
			
			$new_article_fields = '';
			
			if ($this->content == 'link') {
				$new_article_fields .= '<div><span>http://</span>'.finput('text','url','','edit text url').'</div>';
			}
			
			if (in_list($this->content,'article,file,link')) {
				
				$categories = category_popup('category','','new-article-category');
				$new_article_fields .= str_replace('"></option>','">Category</option>'.n.'<option value="NONE">------------------------</option>'.n,$categories);
				
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$smarty->assign('path',$path);
			$smarty->assign('list_data',$list_data);
			$smarty->assign('list_items_count',count($ids));
			$smarty->assign('avg_title',$avg_title);
			$smarty->assign('checked',impl($this->selected));
			$smarty->assign('editcol',$WIN['editcol']);
			$smarty->assign('clipboard',$clipboard);
			$smarty->assign('mode',$mode);
			$smarty->assign('linkdir',$this->linkdir);
			$smarty->assign('linkdir_title',($WIN['linkdir']=='asc') ? 'Up' : 'Down');
			$smarty->assign('sortby',$this->sortby);
			$smarty->assign('sortdir',$this->sortdir);
			$smarty->assign('is_trash',(($WIN['id'] == TRASH_ID) ? 1 : 0));
			$smarty->assign('trash_cnt',$trash_cnt);
			$smarty->assign('trash_id',TRASH_ID);
			$smarty->assign('window',$WIN['winid']);
			$smarty->assign('close',count($this->open) > 1);
			$smarty->assign('hide_headers',($WIN['headers']=='hide') ? 'hidden' : '');
			$smarty->assign('hide_main',($WIN['main']=='hide') ? 'hidden' : '');
			$smarty->assign('flat_view',$WIN['flat']);
			$smarty->assign('custom_headers',$custom_headers);
			$smarty->assign('column_headers',$this->get_column_headers($this->visible));
			$smarty->assign('column_count',$column_count);
			$smarty->assign('column_select',implode(n,$column_select));
			$smarty->assign('column_custom_select',implode(n,$column_custom_select));
			// $smarty->assign('has_export',count(dirlist($site_base_path.DS.$file_dir.DS.'_export')));
			$smarty->assign('has_export',1);
			$smarty->assign('new_article_fields',$new_article_fields);
			
			return $smarty->fetch('list/list.tpl');
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			// return array_to_string($list);
		}
		
	// -------------------------------------------------------------------------
		function get_visible_columns() 
		{		
			global $WIN;
			
			$columns = $WIN['columns'];
			$visible = array();
			$include = array();
			
			foreach($columns as $key => $col) {
				
				$columns[$key]['name'] = strtolower($key);
				
				if ($col['on'] > 0) {
				
					$include[$key] = $col['pos'];
					
					if ($col['pos']) $visible[$key] = $col['pos'];
				}
			}
			
			asort($include);
			
			foreach($include as $key => $pos) {
				
				$include[$key] = $columns[$key];
				
				if ($pos) $visible[$key] = $columns[$key];
			}
			
			$WIN['columns'] = $columns;
			$this->include  = $include;
			
			return $visible;
		}

	// -------------------------------------------------------------
		function get_custom_columns() 
		{
			global $WIN, $event; 
		
			$columns = array();
			
			if ($event == 'custom') return array();
			if (count($WIN['custom'])) return $WIN['custom'];
			
			$table = $WIN['table'];
			$type  = $WIN['content'];
			
			$custom_fields = safe_rows(
				"ID,Name,Title,`Type`,`input`,Body AS options,`default`",
				"txp_custom",
				"`Type` != 'folder' AND Trash = 0 ORDER BY ParentID ASC, Position ASC");
			
			foreach ($custom_fields as $key => $field) {
				
				$field_id = $field['ID'];
				
				if (!getCount("txp_group","type = 'field' AND by_table = '$table' AND field_id = $field_id AND status = 'active'")) {
						
					if (isset($columns[$field_id]))
						unset($columns[$field_id]);
				
				} elseif (!isset($columns[$field_id])) {
			
					$columns[$field_id] = $field;
					$columns[$field_id]['show'] = 0;
					$columns[$field_id]['value'] = '';
					$columns[$field_id]['rowid'] = 0;
					
					unset($columns[$field_id]['class']);
					unset($columns[$field_id]['frontpage']);
					unset($columns[$field_id]['children']);
					unset($columns[$field_id]['parent']);
					unset($columns[$field_id]['level']);
				}
			}
			
			return $WIN['custom'] = $columns;
		}

	// -------------------------------------------------------------
		function get_column_headers(&$columns) 
		{	
			global $WIN, $smarty;
			
			$position = 0;
			$out = array();
			
			foreach($columns as $key => $col) {
				
				if ($col['pos'] == 0) continue;
				
				$smarty->assign('name',$col['name']);
				$smarty->assign('thumb',$WIN['thumb']);
				$smarty->assign('is_selected',($WIN['selcol'] == $col['name']) ? 'selected' : '');
				
				if (isset($col['short']))
					$smarty->assign('title',$col['short']);
				else
					$smarty->assign('title',$col['title']);
				
				$smarty->assign('position',$position++);
				
				$out[$key] = $smarty->fetch('list/list_th.tpl');
			}
			
			return implode(n,$out);
		}

	// -------------------------------------------------------------------------
		function get_column_value(&$columns) 
		{		
			global $smarty, $event;
			
			$visible   = $this->visible;
			$positions = array();
			$prev      = '';
			$pos       = 0;
			
			$smarty->assign('col_image',isset($visible['image']));
			
			foreach($visible as $name => $vis) {
				$positions[] = $name;
			}
			
			foreach($visible as $name => $vis) {
				
				$col  = $columns[$name];
				$next = (isset($positions[$pos+1])) ? $positions[$pos+1] : '';
				
				$smarty->assign('name',$name);
				$smarty->assign('value',$col['value']);
				$smarty->assign('options','');
				$smarty->assign('prev_col',$prev);
				$smarty->assign('next_col',$next);
				$smarty->assign('pos',$pos++);
				$smarty->assign('is_edit_mode',false);
				$smarty->assign('td_view_mode','view');
				$smarty->assign('path','');
				
				if ($col['edit']) {
					$smarty->assign('is_edit_mode',true);
					$smarty->assign('td_view_mode','edit');
				}
				
				if (isset($col['options'])) {
					$smarty->assign('options',$col['options']);
				}
				
				if ($name == 'Title') {
					if (isset($col['path']) and $col['path']) {
						$smarty->assign('path',$col['path'].'/');
					}
				}
					
				$visible[$name] = $smarty->fetch('list/list_item_td.tpl');
				
				$event_tpl = 'list/event_'.$event.'/list_item_td.tpl';
				
				if ($smarty->templateExists($event_tpl)) {
					
					if ($td = trim($smarty->fetch($event_tpl))) {
						
						$visible[$name] = $td;
					}
				}
				
				$prev = $name;
			}
			
			return implode(n,$visible);
		}

	// -------------------------------------------------------------		
		function get_categories_column_value(&$row,$edit) 
		{
			static $all_titles = array();
			
			$ID         = $row['ID'];
			$categories = do_list($row['Categories']);
			
			$type = (in_list($this->content,'article,image,file,link')) 
				? $this->content
				: 'article';
			
			if (!$all_titles) {
				$all_titles = safe_column('Name,Title','txp_category',"Trash = 0");
			}	
			
			$parsed_categories = array();
				
			foreach($categories as $key => $value) {
				
				$value = explode('.',$value);
				$name  = array_shift($value);
				$pos   = ($value) ? array_shift($value) : $key;
				
				$parsed_categories[$pos] = $name;
			}
				
			$categories = $parsed_categories;
			
			if ($edit) {
				
				/* foreach ($categories as $key => $value) {
					$categories[$key] = array_pop(explode(':',$value));
				} */
				
				return category_popup("Category[".$ID."]",$categories);
				
			} else {
				
				if ($this->sortby != 'categories') {
				
					// sort categories by position
					ksort($categories);
				}
				
				foreach($categories as $key => $value) {
					
					if (isset($all_titles[$value])) {
						$categories[$key] = $all_titles[$value];
					}
				}
				
				return implode(', ',$categories);
			}
		}

	// -------------------------------------------------------------
		function get_custom_column_value(&$row,$edit_row,$edit_col) 
		{
			global $smarty;
			static $all_fields = array();
			
			
			$custom = $this->custom;
			$out    = array();
				
			$custom_fields = trim($row['custom_fields'],'&'); 
			$custom_fields = explode('&,&',$custom_fields); 
			
			if (!$all_fields) {
				$all_fields = safe_column(
					'ID,name,type,input,label,Body_html AS options',
					'txp_custom','Trash = 0');
			}
			
			foreach ($custom_fields as $field) {
					
				if ($field) {
				
					$name = $type = $input = $label = '';
					$options = array();
					
					list($rowid,$id,$value) = explode('}:{',rtrim(ltrim($field,'{'),'}'));
					
					if (isset($all_fields[$id])) {
						extract($all_fields[$id]);
					}
					
					$custom[$id]['value'] = htmlentities(doStrip($value));
					$custom[$id]['rowid'] = $rowid;
					
					if ($input == 'select') {
						
						foreach(explode(n,$options) as $opt) {
							
							
							$opt = explode(':',$opt);
							$val = array_shift($opt);
							
							if ($value == $val and count($opt)) {
								$custom[$id]['value'] = array_shift($opt);
							}
						}
					}
					
					if ($value and $input == 'time') {
						
						$hour  = (int)substr($value,0,2);
						$min   = substr($value,3,2);
						$ampm  = ($hour >= 12) ? 'pm' : 'am';
						
						$hour  = ($hour > 12) ? $hour - 12 : $hour; 
						$hour  = ($hour == 0) ? 12 : $hour;
						$min   = ($min == '00') ? '' : ':'.$min;
			
						$custom[$id]['value'] = $hour.$min.$ampm;
					}
				}
			}
			
			foreach ($custom as $id => $col) {
				
				if ($col['show']) {
					
					$name = $col['Name'];
					
					$smarty->assign('name',$col['Name']);
					$smarty->assign('value',$col['value']);
					$smarty->assign('rowid',$col['rowid']);
					$smarty->assign('is_edit_mode',false);
					
					if ($edit_row or $edit_col == $name) {
						$smarty->assign('is_edit_mode',true);
					}
				
					$out[] = $smarty->fetch('list/column_custom_value.tpl');
				}
			}
			
			return implode(n,$out);
		}
	
	// -------------------------------------------------------------------------
		function get_language_value($lg,$edit) 
		{
			global $language_codes;
			
			if (!$edit) {
			
				if (isset($language_codes[$lg])) {
					return $language_codes[$lg];
				}
			
			} else {
			
				return $lg;
			}
		}
		
	// -------------------------------------------------------------------------
		function toggle_column($name='') 
		{	
			global $WIN;
			
			if ($name = gps('column',$name)) {
				
				if (preg_match('/^custom\./',$name)) {
						
					$name = ltrim($name,'custom.'); 
					
					if (isset($WIN['custom'][$name])) {
						$WIN['custom'][$name]['show'] = ($WIN['custom'][$name]['show']) ? 0 : 1;
					}
				
				} else {
					
					foreach($WIN['columns'] as $key => $col) {
						
						if ($col['name'] == strtolower($name)) {
						
							$WIN['columns'][$key]['on'] = ($col['on']) ? 0 : 1;
						}
					}
				}
			}
		}

	// -------------------------------------------------------------
		function move_column($movecol='',$dir=0) 
		{	
			global $WIN;
			
			$columns   = $WIN['columns'];
			$col_names = array();
			$positions = array();
			
			if (!$dir) {
				
				foreach($columns as $col) {
					$col_names[] = $col['name'];
				}
				
				$movecol = get('column','',$col_names);
				$movedir = get('move','','left,right');
			
				if ($movedir == 'left')  $dir = -1;
				if ($movedir == 'right') $dir = 1;
			}
			
			foreach($columns as $col) {
				
				$pos = $col['pos'];
				$positions[$pos] = $col['on'];
			}
			
			ksort($positions);
			
			$last = array_pop(array_keys($positions));
			
			foreach($columns as $key => $col) {
				
				if ($col['name'] == $movecol) {
					
					$pos = $col['pos'];
					
					while (isset($positions[$pos+$dir]) 
							 and $positions[$pos+$dir] == 0) 
					{
						$pos += $dir;
					}
					
					$new = $pos + $dir + ($dir * 0.5);
					
					if ($new < 0) { 
						$columns[$key]['pos'] = $last + 1;
					} elseif ($new > $last) { 
						$columns[$key]['pos'] = 0;
					} else {
						$columns[$key]['pos'] = $new;
					}
				}
			}
			
			$WIN['columns'] = $columns;
			$WIN['selcol']  = $movecol;
		}

	// -------------------------------------------------------------
		function add_note($id) 
		{
			global $WIN;
			
			$key = $WIN['content'].'.'.$id;
			
			if (!isset($WIN['notes'][$key])) {
					
				$WIN['notes'][$key] = $WIN['reset']['note'];
				$WIN['notes'][$key]['id']    = $id;
				$WIN['notes'][$key]['table'] = $WIN['table'];
				$WIN['notes'][$key]['type']  = $WIN['content'];
			}
		}
	
	// -------------------------------------------------------------------------
		function buildQuery() 
		{	
			global $WIN, $PFX;
			
			$sort    = $this->sortby;
			$dir     = strtoupper($this->sortdir);
			$incl    = $this->include;
			$excl_t  = ($this->exclude) ? "t.ID NOT IN (".in($this->exclude).")" : '1=1';
			$excl_c  = ($this->exclude) ? "ch.ID NOT IN (".in($this->exclude).")" : '1=1';
			$trash_t = "t.Trash = 0";
			$trash_c = '{$trash_c}';
			
			$content_table = $this->table;
			$content_type  = $this->content;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// FROM
			
			$from = array('textpattern' => "$content_table AS t");
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// SELECT
			
			$select = array( 
				't.ID', 
				't.ParentID', 
				't.Path', 
				't.Alias', 
				't.ImageID',
				't.FileID',
				't.`Type`',
				't.Trash',
				't.Level'
			);
			
			$select['AuthorID']  	= "t.AuthorID";
			$select['Title']   	 	= "t.Title";
			$select['Title_html']   = "t.Title_html";
			$select['Name']   		= "t.Name";
			$select['Status']   	= "t.Status";
			$select['ClassName']    = "t.Class AS ClassName";
			$select['Posted']		= "UNIX_TIMESTAMP(t.Posted) AS Posted";
			$select['LastMod']		= "UNIX_TIMESTAMP(t.LastMod) AS LastMod";
			$select['Position'] 	= "IF(t.Position <= 0,0,FLOOR(t.Position)) AS Position";
			
			$select['Image']     	= n."NULL AS Image";
			$select['Categories'] 	= n."NULL AS Categories";
			$select['customfields']	= n."NULL AS custom_fields";
			
			// image 
			
			if (isset($incl['Image'])) {
				
				$select['Image'] = "CONCAT(i.Name,i.ext) AS Image, i.transparency";
				$from['image']   = "LEFT JOIN txp_image AS i ON t.ImageID = i.ID";
					
				/* if ($this->content != 'image') { 
				
					$select['Image'] = "CONCAT(i.Name,i.ext) AS Image, i.transparency";
					$from['image']   = "LEFT JOIN txp_image AS i ON t.ImageID = i.ID";
				
				} else {
				
					// $select['Image'] = "CONCAT(i.Name,i.ext) AS Image, i.transparency";
					// $from['image']   = "LEFT JOIN txp_image AS i ON t.ImageID = i.ImageID AND i.Type = 'image'";
					
					$select['Image'] = "IF(Type = 'image',CONCAT(t.Name,t.ext),'') AS Image, t.transparency";
				} */
			}
			
			// class
			
			if (isset($incl['Class'])) {
				
				if ($this->content != 'category') { 
					$select['Class'] = "IFNULL((SELECT c.title FROM txp_category AS c WHERE t.Class = c.name AND c.class = 'yes' AND Trash = 0),'') AS Class";
				} else {
					$select['Class'] = "IF(t.Class='yes','Yes','') AS Class";
				}
			}
			
			// categories
			/*
			if (isset($incl['Categories'])) {
				
				$select['Categories'] = n.sql_comment("Categories").n.n.
				"IFNULL((SELECT GROUP_CONCAT(c.title,':',c.name ORDER BY tc.position ASC) 
					FROM txp_content_category AS tc JOIN txp_category AS c 
					WHERE t.ID = tc.article_id
						AND tc.type = '".$this->content."'
						AND tc.name = c.name
						AND c.Trash = 0
						ORDER BY tc.position ASC),'') 
					AS Categories";
			} */
			
			if (isset($incl['Categories'])) { 
				$select['Categories'] = "t.Categories";
			}
			
			// body and excerpt 
			
			$text_left = ($WIN['thumb'] == 'y') ? 115 : 40;
			
			if (isset($incl['Body'])) {
				
				$select['Body'] = "LEFT(t.Body,$text_left) AS Body";
			}
			
			if (isset($incl['Excerpt'])) {
				
				$select['Excerpt'] = "LEFT(t.Excerpt,$text_left) AS Excerpt";
			}
			
			// custom field columns
			/*
			if (isset($incl['customfields']) and $incl['customfields']) {
				
				$custom = impl($incl['customfields']);
				
				$group = array('tcv.id','tcv.field_id','tcv.field_name','fld.type','fld.input','CONVERT(fld.label USING utf8)','tcv.text_val');
				$group = implode(",'}:{',",$group);
				
				$select[] = n.sql_comment("Custom Fields").n.n.
				"IFNULL((SELECT GROUP_CONCAT('&','{',".$group.",'}','&')
					FROM txp_content_value AS tcv JOIN txp_custom AS fld ON fld.id = tcv.field_id
					WHERE t.ID = tcv.article_id 
						AND tcv.status = 1 
						AND tcv.field_id IN ($custom) 
						AND tcv.tbl = '".$this->table."'
					ORDER BY tcv.id ASC),'')
					AS custom_fields";
			} */
			
			if (isset($incl['customfields']) and $incl['customfields']) {
				
				$custom = impl($incl['customfields']);
				
				$group = array('tcv.id','tcv.field_id','tcv.text_val');
				$group = implode(",'}:{',",$group);
				
				$select[] = n.sql_comment("Custom Fields").n.n.
				"IFNULL((SELECT GROUP_CONCAT('&','{',".$group.",'}','&')
					FROM txp_content_value AS tcv
					WHERE t.ID = tcv.article_id 
						AND tcv.status = 1 
						AND tcv.field_id IN ($custom) 
						AND tcv.tbl = '".$this->table."'
					ORDER BY tcv.id ASC),'')
					AS custom_fields";
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// event specific select
			
			foreach($incl as $name => $col) {
				
				if (isset($col['sel'])) {
					
					if (isset($select[$name])) {
						$select[$name] = $col['sel'];
					} elseif (strpos($col['sel'],' AS ') === false) {
						$select[$name] = $col['sel'].' AS `'.$name.'`';
					} else {
						$select[$name] = $col['sel'];
					}
				
				} elseif (!isset($select[$name])) {
					
					$select[$name] = 't.'.$name.' AS `'.$name.'`';
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// max value for position edit pulldown
			
			$select['maxpos'] = n.sql_comment("Maximum Position").n.n.
			"(SELECT Position 
				FROM $content_table AS mp 
				WHERE t.ParentID = mp.ParentID 
				ORDER BY Position 
				DESC LIMIT 1) AS maxpos";
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// sort column
			
			if (column_exists($content_table,$sort)) {
			
				if ($sort == 'Position') 
					$select['sort'] = n."IF(t.$sort <= 0,1,0) AS isnull";
				else
					$select['sort'] = n."IF(t.$sort IS NULL OR t.$sort = '',1,0) AS isnull";
			}
						
			$select['istrash'] = n."IF(t.ID = '".TRASH_ID."',1,0) AS istrash";
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// child count
			
			$select['child_count'] = n.sql_comment("Child Count").n.n.
			"(SELECT COUNT(ID) 
				FROM $content_table AS ch 
				WHERE ch.ParentID = t.ID 
					AND $excl_c 
					AND $trash_c) 
				AS child_count";
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// alias child count
			
			$alias_child_count = "(SELECT COUNT(ID) 
				FROM $content_table AS ch 
				WHERE ch.ParentID = t.Alias 
					AND $excl_c 
					AND $trash_c)";
			
			$select['alias_child_count'] = n.sql_comment("Alias Child Count").n.n.
				"IF(t.Alias,$alias_child_count,0) 
				AS alias_child_count";
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// reduce tab indentations
			
			foreach($select as $key => $item) {
				
				$item = explode(n,$item);
				$tabs = '';
				
				foreach($item as $i => $line) {
				
					if (preg_match('/^\t+/',$line,$matches)) {
						
						if (!$tabs) $tabs = $matches[0];
						
						$item[$i] = preg_replace('/'.$tabs.'/',t,$line);
					
					} else {
						
						$item[$i] = t.$line;	
					}
				}
				
				$select[$key] = implode(n,$item);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// WHERE
			
			$where = array($excl_t);
		
			$where['id'] = 't.ID = 0';
			$where['trash'] = 't.Trash = 0';
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// ORDER BY
			
			$sorthistory = (isset($WIN['sorthist'])) 
				? $WIN['sorthist'] 
				: array();
			
			$orderby = array("istrash ASC");
			
			if (column_exists($content_table,$sort)) {
			
				$orderby[] = "isnull ASC";
				$orderby[] = "t.$sort $dir";
			
			} elseif (isset($incl[$sort])) {
				
				$orderby[] = "$sort $dir";
			}
		
			/* if (!in_list($sort,'ID,Expires,Posted')) {
			
				$orderby[] = "Posted DESC, ID DESC";
			} */
			
			// second order sort 
			
			if ($sort == 'Posted') {
			
				// Posted may have duplicate values, second order by ID
				
				$orderby[] = "t.ID $dir";
			
			} elseif (count($sorthistory)) {
			
				$next = array_shift($sorthistory);
				$orderby[] = "t.".$next;
				
				// third order sort 
				
				if (count($sorthistory)) {
					$next = array_shift($sorthistory);
					$orderby[] = "t.".$next;
				}
				
				switch(strtolower($next)) {
					case 'posted asc'  : $orderby[] = "t.ID ASC";  break;
					case 'posted desc' : $orderby[] = "t.ID DESC"; break; 
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			return array(
				'select'  => $select,
				'from'    => $from,
				'where'   => $where,
				'orderby' => $orderby
			);
		}
	
	// -------------------------------------------------------------------------
		function getMain($id, &$q, $trash=0, $debug=0) 
		{	
			global $WIN;
			
			$trash_c = ($trash) ? "ch.Trash = 2" : "ch.Trash = 0";
			
			$q['select']['child_count']       = preg_replace('/\{\$trash_c\}/',"ch.Trash = 0",$q['select']['child_count']);
			$q['select']['alias_child_count'] = preg_replace('/\{\$trash_c\}/',"ch.Trash = 0",$q['select']['alias_child_count']);
			
			$q['where']['id'] = "t.ID = '$id'";
			$q['where']['trash'] = "t.Trash = 0";
			
			extract($q);
			
			$select  = n.add_pfx(implode(','.n,$select)).n.n;
			$from    = n.implode(n.' ',$from);
			$where   = n.implode(n.' AND ',$where).n.n.t;
		
			if ($row = safe_row($select,$from,$where,0,$debug)) {
				
				$ID = $row['ID'];
				
				if ($row['Level'] < $this->deepest) {
						
					$where = array('Trash IN (0,1)');
					if ($this->exclude) $where[] = "ID NOT IN (".in($this->exclude).")".n;
						
					$row['descendant_count'] = safe_count_tree($ID,$this->table,doAnd($where));
				}
						
				$row['Level'] = 1;
				$row['more']  = 0;
				$this->list[] = $row;
			}
		}
		
	// -------------------------------------------------------------------------
		function getTree($id, &$q, $viewtrash=0, $level=2, $children=0, $debug=0) 
		{	
			global $WIN;
			
			$max_children = 25;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// WHERE
			
			if ($level == 1) {
				
				$q['where']['id'] = "t.ID = '$id'";
				$q['where']['trash'] = "t.Trash = 0";
			
			} else {
				
				if ($this->flat and $level == 2) {
				
					$q['where']['id'] = get_path($this->root);
				
				} elseif (is_array($id)) {
					
					$q['where']['id'] = "t.ID IN (".in($id).")";
					
				} else {
					
					$q['where']['id'] = ($id) ? "t.ParentID = '$id'" : "t.ID = t.ID";
					
					if ($viewtrash == 0) {
					
						if ($id == fetch('ID',$this->table,"Name","TRASH")) {
							
							$q['where']['id'] = "t.ID = t.ID";
							
							$viewtrash = 1;
						}
					}
				}
				
				$q['where']['trash'] = "t.Trash = $viewtrash";
			} 
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// SELECT
			
			$trash_c = ($viewtrash) ? "ch.Trash = 2" : "ch.Trash = 0";
			
			// PROBLEM: replace is not gonna do anything the second time around
			
			$q['select']['child_count']       = preg_replace('/\{\$trash_c\}/',$trash_c,$q['select']['child_count']);
			$q['select']['alias_child_count'] = preg_replace('/\{\$trash_c\}/',$trash_c,$q['select']['alias_child_count']);

			if ($level == 2) {
				// pre($q['select']['child_count']);
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			extract($q);
			
			$select  = n.add_pfx(implode(','.n,$select)).n.n;
			$from    = n.implode(n.' ',$from);
			$where   = n.implode(n.' AND ',$where).n.n.t;
			$orderby = " ORDER BY ".implode(', ',$orderby);
			$limit   = " LIMIT 1000";
			
			if ($level == 2 and $this->flat) {
				
				$offset = 0;
				$limit  = $this->flat;
					
				if ($this->more) {
					$offset = $this->flat - $this->more;
					$limit  = $this->more;
				}
				
				$limit = " LIMIT $offset,$limit";
			
			} elseif ($level > 2) { 
				
				$limit = " LIMIT $max_children";
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// get rows
			
			if ($rows = safe_rows($select,$from,$where.$orderby.$limit,0,$debug)) {
				
				$last = count($rows) - 1;
				 
				foreach ($rows as $key => $row) {
			
					$ID    = $row['ID'];
					$Alias = $row['Alias'];
					$Trash = $row['Trash'];
					$Name  = $row['Name'];
					$Type  = $row['Type'];
					
					$getTrash = (($Name == 'TRASH' or $Type == 'trash') and $ID == $id);
					
					if (($Name == 'TRASH' or $Type == 'trash') and !$getTrash) continue;
					
					$row['descendant_count'] = 0;
					
					if ($Name == 'TRASH' or $Type == 'trash') {
						
						$row['descendant_count'] = getCount($this->table,"Trash IN (1,2)");
						$row['child_count'] = getCount($this->table,"Trash = 1");
					
					} elseif ($row['Level'] < $this->deepest) {
						
						$where = array('Trash IN (0,1)');
						if ($this->exclude) $where[] = "ID NOT IN (".in($this->exclude).")".n;
						
						$row['descendant_count'] = safe_count_tree($ID,$this->table,doAnd($where));
 					}
 					
 					$row['Level'] = $level;
					
					// - - - - - - - - - - - - - - - - - - - - - - -
					
					$row['more'] = 0;
					
					if ($key == $last) {
					
						if ($level > 2) {
							
							if ($children > $max_children) {
							
								$row['more'] = $children - $max_children;
								
								// pre("> $ID: $more_children more");
							}
							
						} elseif ($level == 2 and $this->flat) {
						
							$path = str_replace('t.','',trim($q['where']['id'],'()'));
							
							$count = getCount($this->table,"$path AND Trash = 0");
							
							if ($count > $this->flat) { 
								
								$row['more'] = $count - $this->flat;
							}
						}
					}
					
					// - - - - - - - - - - - - - - - - - - - - - - -
					
					$this->list[] = $row;
					
					// - - - - - - - - - - - - - - - - - - - - - - -
					
					if ($WIN['view'] == 'div') continue;
						
					if ($this->flat and $level == 2 ) continue;
					
					if ($level == 1 or $this->open == 'ALL' or in_array($ID,$this->open)) {
					
						if ($Name == 'TRASH' or $Type == 'trash') {
							$ID = 0;
							$viewtrash = 1;
						}
						
						if ($Trash == 1) $viewtrash = 2;
							
						if ($Alias > 0) $ID = $Alias;
						
						$this->getTree($ID,$q,$viewtrash,$level+1,$row['child_count']);
					}						
				}
			}
		}
		
		// -------------------------------------------------------------------------
		
		function dateFormat($date,$time='') {
			
			$now  = time() + tz_offset();
			$date = $date + tz_offset();
			
			if ($date <= $now) {
				$format = ($now - $date < 86400) ? "g:i A" : "M j";
				$format = ($now - $date < 31622507) ? $format : "y/m/d";
			} else {
				$format = ($date - $now < 31622507) ? "M j" : "y/m/d";
			}
			
			$time = ($time and $format == 'M j') 
				? '<span class="time">'.date("g:i A",$date).'</span>' 
				: '';
			
			return '<span class="date">'.date($format,$date).'</span> '.$time;
		}
	}

?>
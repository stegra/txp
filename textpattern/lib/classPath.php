<?php

	class Path {
		
		var $path		= array();
		var $table		= '';
		var $exc_root  	= 1;
		var $inc_self  	= 1;
		var $level		= 0;
		var $separator 	= '';
		var $none  		= '';
		var $id			= 0;
		var $column		= '';
		var $columns    = array(
			'ID','ParentID','Name','NameID','Class','ClassID','Status','Title'
		);
		
	// -------------------------------------------------------------------------
		
		function Path($id=0,$inc='',$table='',$path='') 
		{
			if (!is_numeric($id)) {
				$path = $id;
				$id = 0;
			}
			
			$this->setID($id);
			$this->setInc($inc);
			$this->setTable($table);
			$this->setPath($path,$id);
		}
		
	// -------------------------------------------------------------------------
		
		function setID($id) {
			
			$this->id = $id;
			$this->path = array();
		}
		
	// -------------------------------------------------------------------------
		
		function setInc($inc) {
			
			if (strlen($inc)) {
				
				$inc = explode(',',$inc); 
			
				if (in_array('ROOT',$inc))  $this->exc_root = 0;
				if (in_array('SELF',$inc))  $this->inc_self = 1;
				if (in_array('!ROOT',$inc)) $this->exc_root = 1;
				if (in_array('!SELF',$inc)) $this->inc_self = 0;
				
			} else {
				
				$this->exc_root = 1;
				$this->inc_self = 1;
			}
		}
	
	// -------------------------------------------------------------------------
		
		function length() {
			
			return count($this->get_path());
		}
		
	// -------------------------------------------------------------------------
		
		function setPath($path,$id=1) {
			
			if (!$path) {
				$this->path = array();
				return;
			}
			
			$path = explode('/',$path);
			
			foreach($path as $key => $item) {
				$path[$key] = array('ID' => $item);
			}
			
			if ($id) {
				
				// add root
				$root = fetch("ID",$this->table,"ParentID",0);
				if ($this->id != $root) {
					array_unshift($path,array('ID' => $root));
				}
				
				// add self
				array_push($path,array('ID' => $this->id));
			}
			
			$this->path = array_reverse($path);
		}
		
	// -------------------------------------------------------------------------
		
		function setTable($table) {
			
			global $WIN;
			
			$this->table = ($table) ? $table : $WIN['table'];
			
			$this->path = array();
		}
		
	// -------------------------------------------------------------------------
		
		function setSeparator($val) {
			
			$this->separator = $val;
		}
		
	// -------------------------------------------------------------------------
		
		function make_path($id) {
			
			global $PFX;
			
			if (!$id) return $this->path;
			
			$root_id = fetch("ID",$this->table,"ParentID",0);
			
			$NameID  = "(SELECT nameid.ID FROM ".$PFX.$this->table." AS nameid WHERE nameid.Name = t.name ORDER BY nameid.ID ASC LIMIT 1) AS NameID";
			$ClassID = "IFNULL((SELECT classid.ID FROM ".$PFX."txp_category AS classid WHERE classid.Name = t.Class AND classid.Class = 'yes' LIMIT 1),0) AS ClassID";

			$row = safe_row("t.ID,ParentID,Name,$NameID,Class,$ClassID,Status,Title",$this->table." AS t","t.ID = $id",0);
			
			if ($row) {
				
				$this->path[] = $row;
				$parentid = ($id != $root_id) ? $row['ParentID'] : 0;
			
			} else {
			
				$parentid = 0;
			}
			
			return $this->make_path($parentid);
		}
		
	// -------------------------------------------------------------------------
		
		function get_path($col='',$none='') {
			
			$column = ($col)  ? $col  : $this->column;
			$none   = ($none) ? $none : $this->none;
			
			$path = (!$this->path) ? $this->make_path($this->id) : $this->path;
			
			$path = array_reverse($path);
			
			if ($this->exc_root) {
				array_shift($path);
			}
			
			if (!$this->inc_self and count($path)) {
				array_pop($path);
			}
			
			if ($column) {
				
				foreach ($path as $key => $value)
					$path[$key] = ($value[$column]) ? $value[$column] : $none;
			}
			
			return $path;	
		}
		
	// -------------------------------------------------------------------------
		
		function getList($col,$sep=',',$none='') {
			
			if (!in_array($col,$this->columns)) return '';
			
			$separator = ($sep) ? $sep : $this->separator;
			
			$path = $this->get_path($col,$none);
				
			if ($separator) {
				return implode($separator,$path);
			}
			
			return '';
		}
		
	// -------------------------------------------------------------------------
		
		function getArr($col='',$none='') 
		{
			if ($col and !in_array($col,$this->columns)) 
				return array();
		
			return $this->get_path($col,$none);
		}
		
	// -------------------------------------------------------------------------
		
		function getID($col='Name',$sep='/') 
		{ 
			$path = array_reverse($this->path);
			
			foreach($path as $key => $item) {
				
				$id = 0;
				$value = doSlash($item['ID']);
				$previous = $key - 1;
				$status = (PREVIEW) ? '2,3,4,5,7' : '2,4,5';
				
				if ($col == 'Name' and $value != '*') {
					
					$where = array(
						'name'   => "Name  = '$value'",
						'trash'  => "Trash = 0",
						'Status' => "Status IN ($status) AND ParentStatus IN ($status)"
					);
					
					$level = $this->level 
						   + $this->exc_root 
						   + $this->inc_self 
						   + $key; 
					
					$where['level'] = ($this->level)
						? "Level >= $level"
						: "Level = $level";
					
					if (isset($path[$previous])) {
						$name = doSlash(current(explode(':',$path[$previous])));
						if ($name != '*') {
							$where['parent'] = "ParentName = '$name'";
						}
					}
					
					$debug = 0;
					
					$rows = safe_rows("ID,ParentID",$this->table,doAnd($where)." ORDER BY ParentStatus DESC, Status DESC",0,$debug);
					
					if ($rows) {
					
						$id = $rows[0]['ID'];
					
						if (count($rows) > 1 and isset($path[$previous-1])) {
							
							$name = doSlash(current(explode(':',$path[$previous-1])));
							
							if ($name != '*') {
								
								$match = false;
								
								foreach ($rows as $row) {
								
									$parent_id = $row['ParentID'];
								
									if (!$match and safe_count($this->table,"ID = $parent_id AND ParentName = '$name' AND ParentStatus IN (2,4,5)",0)) {
									
										$id = $row['ID']; 
										$match = true;
									}
								}
							}
						}
					}
				}
				
				if ($col == 'Class') {
				
					$field = safe_field("ID","txp_category","Name = '$value' AND Class = 'yes' AND Trash = 0");
					if ($field) $id = $field;
				}
				
				if ($col == 'ID') {
					
					$id = $value;
				}
				
				$path[$key] = "$value:$id";
			}
			
			if ($sep) return trim(implode($sep,$path),':');
			
			return $path;
		}
		
	// -------------------------------------------------------------------------
		
		function getSQL($tree=0,$status='ALL',$tbl='t') 
		{	
			$level = 2;
			$out = array();
			$comment = array();
			
			$tree = ($tree == 'TREE') ? 1 : $tree;
			$tree = ($tree == 'SELF') ? 0 : $tree;
			
			$reverse = 0;
			
			switch ($status) {
				
				case 'HIDDEN' : 
				
					$status = '0'; $comment['status'] = 'hidden';   
					$trash  = '0'; $comment['trash']  = 'not in trash';   
					break;
				
				case 'PUBLISHED' : 
				
					$status = '1'; $comment['status'] = 'published';   
					$trash  = '0'; $comment['trash']  = 'not in trash';
					break;
				
				case 'REV_PUBLISHED' : 
				
					$status  = '1'; $comment['status'] = 'published'; 
					$trash   = '0'; $comment['trash']  = 'not in trash';
					$reverse = 1; 
					break;
				
				case 'PREVIEW' : 
					
					$status = '0,1'; $comment['status'] = 'all'; 
					$trash  = '0';   $comment['trash']  = 'not in trash';
					break;
				
				case 'ALL' : 
					
					$status = '0,1'; $comment['status'] = 'all';
					$trash  = '0,1'; $comment['trash'] = 'all';
					break;
			}
			
			if ($tbl != 't') {
				
				$comment['rev'] = ($reverse) ? 'reverse' : 'forward';
				
				$out[] = str_pad($tbl.".Reverse = $reverse",25).sql_comment($comment['rev'],12);
				$out[] = str_pad($tbl.".Status IN ($status)",25).sql_comment($comment['status'],12);
				$out[] = str_pad($tbl.".Trash IN ($trash)",25).sql_comment($comment['trash'],12);
			}
			
			if ($this->path) {
				
				$path = $this->getID();
				
				$path = explode('/',$path);
				
				if ($reverse) {
					$last = null;
					$path = array_reverse($path);
				} else {
					$last = array_pop($path);
				}
				
				foreach ($path as $key => $value) {
					
					list($name,$id) = explode(':',$value);
					
					if ($id != 0) {
						
						if (comparison($value) == '!=') {
							$out[] = str_pad($tbl.".P".($level++)." != ".$id,25).sql_comment($name,12);
							$out[] = $tbl.".P".($level-1)." IS NOT NULL";
						} else {
							$out[] = str_pad($tbl.".P".($level++)." = ".$id,25).sql_comment($name,12);
						}
					}
				}
				
				if ($last) {
					
					list($name,$id) = explode(':',$last);
					
					if (strlen($name) and $name != '*') {
					
						$out[] = str_pad($tbl.".ID = ".$id,25).sql_comment($name,12);
					}
				}
				
				/* for ($i = $level; $i < LEVELS; $i++) {
					$out[] = str_pad($tbl.".P".$i." IS NULL",25);
				} */
			}
			
			return implode("\n AND ",$out);
		}
		
		// -------------------------------------------------------------------------
		
		function unique() {
			
			$length = count($this->path);
			
			$where = array();
			$where[] = "Type = 'name'";
			$where[] = "Level = '$length'";
			$where[] = $this->getSQL(0,'PUBLISHED','p');
			
			$rows = safe_rows("ID","txp_path AS p",doAnd($where),1);
			
			if (count($rows) == 1) {
				return $rows[0]['ID'];
			}
			
			return 0;
		}
	}
?>
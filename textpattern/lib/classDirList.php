<?php

	class DirList {
		
		var $dir		= '';
		var $extensions = '';
		var $regexp		= '';
		var $lastmod	= '';
		var $recurse    = false;
		var $hidden		= false;	
		var $filesonly	= false;
		var $dirsonly	= false;
		var $sortby		= '';
		var $sortdir	= '';
		var $exclude 	= array();
		var $return		= array();
		var $limit		= 0;
				
	// -------------------------------------------------------------------------
		
		function DirList($dir='',$sort='') 
		{
			$this->dir = $dir;	
			$this->setSort($sort);
		}
	
	// -------------------------------------------------------------------------
		
		function setDir($dir) 
		{
			$this->dir = $dir;
		}
	
	// -------------------------------------------------------------------------
		
		function setLastMod($time) 
		{
			if (preg_match('/^\d+$/',$time)) {
			
				$this->lastmod = mktime(0, 0, 0, date("m"),date("d")-$time-1,date("Y"));
			
			} else {
				
				$this->lastmod = strtotime($time);
			}
		}
		
	// -------------------------------------------------------------------------
		
		function setReturn($value) 
		{
			$this->return[] = $value;
		}
		
	// -------------------------------------------------------------------------
		
		function setSort($sort='') 
		{
			$sort = trim($sort);
			
			if (in_list($sort,'ASC,DESC')) {
				
				$this->sortby  = 'position';
				$this->sortdir = $sort;
			
			} else {
			
				$sort = preg_split('/\s+/',$sort);
			
				$this->sortby  = array_shift($sort);
				$this->sortdir = array_shift($sort);	
			}
		}
			
	// -------------------------------------------------------------------------
	// get files only, exclude directories 
		
		function getFiles() 
		{
			$save = $this->filesonly;
			$this->filesonly = true;
			
			$list = $this->getList();
			
			$this->filesonly = $save;
			
			return $list;
		}
		
	// -------------------------------------------------------------------------
	// get the first file 
		
		function getFile($select='') 
		{
			$save['limit']  = $this->limit;
			$save['regexp'] = $this->regexp;
			$this->limit = 1;
			
			if (preg_match('/^\/.*\/$/',$select)) {
				$this->regexp = trim($select,'/');
			} 
			
			$list = $this->getFiles();
			
			$this->limit  = $save['limit'];
			$this->regexp = $save['regexp'];
			
			return array_shift($list);
		}
		
	// -------------------------------------------------------------------------
		
		function getList($dir='',&$path=array(),&$list=array()) 
		{	
			$dir  = (!$dir) ? $this->dir : $dir;
			
			if (is_dir($dir)) {
				
				if (chdir($dir)) {
				
					$dh = opendir($dir);
					
					while (false !== ($filename = readdir($dh))) {
						
						$item = ltrim(implode('/',$path).DS.$filename,'/');
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						
						$continue = false;
						
						if ($filename == '.')  $continue = true;
						if ($filename == '..') $continue = true;
						
						foreach ($this->exclude as $key => $exclude) {
						
							if (preg_match('/'.preg_quote(trim($exclude),'/').'$/',$item)) {
							
								$continue = true;
							}
						}
						
						if ($continue) continue;
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						
						$item = $this->dir.'/'.$item;
						
						$out = true;
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						// CRITERIA
						
						if ($this->hidden == false) {
						
							if (substr($filename,0,1) == '.') $out = false;
						}
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						
						if ($this->filesonly) {
							
							if (is_dir($item)) $out = false;
						}
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						
						if ($this->dirsonly) {
						
							if (is_file($item)) $out = false;
						}
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						
						if ($this->regexp) {
						
							if (!preg_match('/'.$this->regexp.'/',$filename)) {
								
								$out = false;
							}
						}
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						
						if ($this->lastmod) {
							
							if (is_file($item)) {
								
								if (filemtime($item) < $this->lastmod) {
									$out = false;
								}
							}
						}
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						
						if ($this->extensions) {
							
							$file_ext = get_file_ext($filename);
							$file_ext = preg_replace('/[^\w\d]/','',$file_ext);
							
							if ($this->extensions == '*') {
								
								if (!strlen($file_ext)) $out = false;
								
								
							} elseif (!in_list($file_ext,$this->extensions)) {
							
								$out = false;
							}
						}
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						// SORT & RETURN
						
						if ($out) {
							
							$pos = str_pad(count($list),3,'0',STR_PAD_LEFT);
						
							if ($this->sortby == 'lastmod') {
								
								$key  = filemtime($item);
								$key .= '-'.$this->make_sortname($filename).'-'.$pos;
							
							} elseif ($this->sortby == 'filesize') {
								
								$key  = str_pad(filesize($item),12,'0',STR_PAD_LEFT);
								$key .= '-'.$this->make_sortname($filename).'-'.$pos;
								
							} elseif ($this->sortby == 'name') {
								
								$key  = $this->make_sortname($filename).'-'.$pos;
							
							} else {
								
								$key = ($pos != '0') ? ltrim($pos,'0') : '0';
							}
							
							// - - - - - - - - - - - - - - - - - - - - - - -
							
							$list[$key] = ltrim(implode('/',$path).DS.str_replace(':','-',$filename),'/');
							
							if (in_array('lastmod',$this->return)) {
								
								$list[$key] .= ':'.filemtime($item);
							}
							
							if (in_array('filesize',$this->return)) {
								
								$list[$key] .= ':'.filesize($item);
							}
						}
						
						// - - - - - - - - - - - - - - - - - - - - - - -
						// SUBDIRECTORIES 
						
						if ($this->recurse and is_dir($dir.DS.$filename)) {
							
							$path[] = $filename;
							
							$this->getList($dir.DS.$filename,$path,$list);
							
							array_pop($path);
						}
					}
					
					closedir($dh);
				}
			}
			
			if ($this->sortby) {
				
				if ($this->sortdir == 'DESC') {
					krsort($list);
				} else {
					ksort($list);
				}
			}
			
			if ($this->limit) {
				$list = array_slice($list,0,$this->limit);
			}
			
			return $list;
		}
	
		// -------------------------------------------------------------------------
		
		function make_sortname($filename) 
		{
			$name = str_replace('.','-',$filename);
			$name = make_name(trim($name,'-'));
			
			if (substr($filename,0,1) == '_') $name = '_'.$name;
			if (substr($filename,0,1) == '.') $name = '.'.$name;
			
			return $name;
		}
		
		// -------------------------------------------------------------------------
	}
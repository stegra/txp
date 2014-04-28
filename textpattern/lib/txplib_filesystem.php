<?php

	$file_extensions = array(
		'html'	=> 'text/html',
		'css' 	=> 'text/css',
		'txt' 	=> 'text/plain',
		'md' 	=> 'text/plain',
		'php'	=> 'text/html',
		'js'  	=> 'application/javascript',
		'jpg' 	=> 'image/jpeg',
		'gif' 	=> 'image/gif',
		'png' 	=> 'image/png',
		'svg' 	=> 'image/svg+xml',
		'mp3'	=> 'audio/mpeg',
		'mp4'	=> 'video/mp4',
		'pdf'	=> 'application/pdf',
		'xml'	=> 'application/xml',
		'xsl'	=> 'application/xml',
		'swf'	=> 'application/x-shockwave-flash',
		'eot' 	=> 'application/vnd.ms-fontobject',
		'ttf' 	=> 'application/octet-stream',
		'woff' 	=> 'application/x-woff'
	);
	
	include txpath.'/lib/classDirList.php';
		
// -------------------------------------------------------------
	function is_windows()
	{
		return in_list(PHP_OS,'WINNT,WIN32,Windows');
	}
	
// -----------------------------------------------------------------------------
	function txp_status_header($status='200 OK')
	{
		if (IS_FASTCGI)
			header("Status: $status");
		elseif ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0')
			header("HTTP/1.0 $status");
		else
			header("HTTP/1.1 $status");
	}

// -----------------------------------------------------------------------------
	function get_file_name($filename,$max=0,$dots='..') 
	{
		$pos = strrpos($filename,".");
		
		if ($pos === false) return $filename;
		
		$name = substr($filename,0,$pos);
		
		if ($max and $max < $pos) {
			$name = trim(substr($filename,0,$max));
			$name = trim(preg_replace('/_$/','',$name));
			return trim($name).$dots;
		}	
		
		return $name;
	}

// -----------------------------------------------------------------------------
	function get_file_ext($filename) 
	{
		if (!$filename) return '';
		
		$pos = strrpos($filename,".");
		
		if ($pos === false) return '';
		
		return strtolower(substr($filename,$pos+1));
	}

//------------------------------------------------------------------------------
	function get_file_type($filename) 
	{
	
		$documents = array(
			'pdf',
			'doc',
			'xls',
			'txt',
			'xml'
		);
		
		list($type) = explode('/',get_mime_type($filename));
		$ext = strtolower(substr(strrchr($filename, '.'), 1));
			
		if ($type != 'audio' and $type != 'video') {
		
			if (in_array($ext,$documents)) $type = 'document';
		}
		
		return $type;
	}
		
//------------------------------------------------------------------------------
	function get_mime_type($filename) 
	{ 
		$fileext = substr(strrchr($filename, '.'), 1);
		
		if (empty($fileext)) return (false);
		
		$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i"; 
		$lines = file(txpath."/lib/mime.types");
		
		foreach($lines as $line) { 
			
			if (substr($line, 0, 1) == '#') continue; // skip comments 
			
			$line = rtrim($line) . " "; 
			
			if (!preg_match($regex, $line, $matches)) continue; // no match to the extension 
			
			return ($matches[1]); 
		} 
		
		return (false); // no match at all 
	} 

// -----------------------------------------------------------------------------
	function get_file($kind=0) 
	{	
		global $txpcfg, $file_extensions;

		$req  = trim($_SERVER["REQUEST_URI"],'/');
		
		// pre($req);
		
		$req = explode('/',$req);
		
		$file_not_found = false;
		
		if ($req[0] == 'admin' or $req[0] == 'textpattern') {
			
			array_shift($req);
			$path = txpath;
		
		} else {
			
			$path = $txpcfg['path_to_site'];
		}
		
		if ($kind == 'img') {
			
			$dir  = '';
			$file = array_pop($req);
			$ext  = get_file_ext($file);
			$path = $path.'/'.implode('/',$req);
			
			if (!is_file($path.'/'.$file)) {
				
				make_image($path,$file,$ext);
				
				if (!is_file($path.'/'.$file)) {
				
					$file_not_found = true;
				}
			}
		
		} elseif ($kind == 'dir') {
			
			$dir  = array_pop($req);
			$path = ($req) ? $path.'/'.implode('/',$req) : $path;
			
			if (!is_dir($path.'/'.$dir)) {
				
				$file_not_found = true;
			}
			
		} else {
		
			$last = count($req) - 1;
			$dir  = '';
			$file = '';
			$ext  = '';
			
			while ($req and !$file_not_found) {
				
				$item = array_shift($req);
				
				// pre('----------------------');
				// pre($item);
				
				if (count($req) > 0) {
					
					if (preg_match('/^[A-Za-z]+([A-Za-z0-9\_\-]+)?[A-Za-z0-9]$/',$item) or preg_match('/^\d+$/',$item)) {
						
						$dir   = $item;
						$path .= '/'.$dir;
						
						if (!is_dir($path)) {
						
							$file_not_found = true;
						
						} else {
							
							// pre($path);
						}
					}
				
				} else {
					
					$item = str_replace('%20',' ',$item);
					
					if (preg_match('/^[A-Za-z]+([A-Za-z0-9\.\_\-\s]+)?\.[A-Za-z]+[1-9]?$/',$item)) {
						
						$file = $item;
						$dir  = ''; 
						$ext = explode('.',strtolower($file));
						$ext = (count($ext) > 1) ? end($ext) : '';
						
						if (!$ext and $item == 'README') {
							
							$ext = 'txt';
						}
							
						if (!isset($file_extensions[$ext])) {
							
							$file_not_found = true;
						
						} elseif (!is_file($path.'/'.$file)) {
						
							$file_not_found = true;
							
						} elseif (!is_readable($path.'/'.$file)) {
						
							$file_not_found = true;
						}
					
					} elseif (preg_match('/^[A-Za-z]+([A-Za-z0-9\_\-]+)?[A-Za-z0-9]$/',$item)) {
					
						$dir = $item;
						
						if (!is_dir($path.'/'.$dir)) {
						
							if ($item == 'README') {
							
								$file = $item;
								$ext  = 'txt';
								$dir  = '';
							
							} else {
						
								$file_not_found = true;
							}
						} else {
							
							$path .= '/'.$dir;
						}
						
					} elseif (strlen($item)) {
						
						$file_not_found = true;
					}
				}
			}
		}
		
		// exit;
		
		if ($file_not_found) {
			
			header('HTTP/1.0 404 Not Found');
			
			echo "<html><head><title>404 Not Found</title></head><body>";
			echo "<h1>File Not Found!</h1>";
			echo "<code>".$_SERVER["REQUEST_URI"]."</code>";
			echo "</body></html>";
			
			return;
		}
		
		txp_status_header();
		
		if ($dir) {
			
			header('Content-type: text/html');
			
			show_dir_index($path,$dir);
		
		} elseif ($ext == 'php') {
			
			header('Content-type: text/html');
			
			@include  $path.'/'.$file;
		
		} else {
			
			header('Content-Type: '.$file_extensions[$ext]);
			header('Content-Length: '.filesize($path.'/'.$file));
			header('Content-Disposition: filename="'.$file.'"');
			
			readfile($path.'/'.$file);
		}
	}

// -----------------------------------------------------------------------------
	function show_dir_index($path,$dir) 
	{	
		global $file_extensions;
		
		echo "<html><head><title>Index of $dir</title>";
		echo '<style type="text/css">';
		echo "	body 	{ padding: 10px 20px; }";
		echo "	h1 		{ font-weight: normal; color: #444; padding-left: 4px; margin-bottom: 10px;}";
		echo "	table 	{ border-collapse: collapse;}";
		echo "	td 		{ font-family: Verdana; font-size: 12px; border-bottom: 1px solid #CCC; padding: 4px;}";
		echo "	td.name { padding-right: 30px; min-width: 200px;}";
		echo "	td.size { padding-right: 30px; color: #444; }";
		echo "	a 		{ color: #963; text-decoration: none; }";
		echo "	a:hover { color: black; }";
		echo '</style>';
		echo "</head>";
		echo "<body>";
		echo "<h1>$dir</h1>";
		
		echo "<table>";
		
		$path = $path.'/'.$dir;		
		
		$list = dirlist($path);
		
		foreach ($list as $item) {
			
			echo "<tr>";
			
			if (is_dir($path.'/'.$item)) {
				
				echo '<td class="name"><a href="'.$item.'/">'.$item.'/</a></td>';
				echo '<td class="size"></td>';
			
			} else {
			
				$ext = explode('.',strtolower($item));
				$ext = (count($ext) > 1) ? end($ext) : '';
				
				if (!$ext and $item == 'README') $ext = 'txt';
				
				if (is_readable($path.'/'.$item) and isset($file_extensions[$ext])) {
				
					echo '<td class="name"><a href="'.$item.'">'.$item.'</a></td>';
				
				} else {
					
					echo '<td class="name">'.$item.'</td>';
				}
				
				echo '<td class="size">'.format_bytes(filesize($path.'/'.$item)).'</td>';
			}
				
			echo "</tr>";
			
		}
		
		echo "</table>";
		echo "</body></html>";
	}
	
// -----------------------------------------------------------------------------
	function read_file($path,$uncompress=0)
	{
		$out = '';
		
		$path = preg_replace('/\.\.\//','',$path);
		
		if ($uncompress) {
			
			if (is_file($path.'.gz'))
				system("gunzip $path.gz");
			else 
				return;
		}
		
		if (!is_file($path)) return;
		
		if (!$f = fopen($path, "r")) return;
		
		if (filesize($path) > 0) {
			$out = fread($f, filesize($path));
		}
		
		fclose($f);
		
		return preg_replace('/[\r\n]+$/','',$out);
	}

// -----------------------------------------------------------------------------
	
	function process_file_by_line($file,$callback,&$var=null)
	{
		$file = preg_replace('/\.\.\//','',$file);
		
		if (preg_match('/\.gz$/',$file)) {
			
			if (is_file($file)) {
				
				system("gunzip $file");
			}
			
			$file = preg_replace('/\.gz$/','',$file);
		
		} elseif (!is_file($file) and is_file($file.'.gz')) {
			
			system("gunzip $file.gz");
		}
		
		if (!is_file($file)) return;
		if (!function_exists($callback)) return;
		
		if (!$f = fopen($file, "r")) return;
		
		while (!feof($f)) {
		
			$line = fgets($f);
			
		 	if (!is_null($var))
		   		$callback($line,$var);
		   	else 
		   		$callback($line);
		}
		
		fclose($f);
	}
	
// -----------------------------------------------------------------------------
	function write_to_file($filename,$content,$compress=0,$append=0)
	{
		$dir = dirname($filename);
		$mode = ($append) ? 'a' : 'w';
		
		if (!is_dir($dir)) {
			return "Directory $dir does not exist";
		}
		
		if (!is_writable($dir)) {
			return "Directory $dir is not writable";
		}
		
		if (!$f = fopen($filename,$mode)) {
			return "Cannot open file $filename";
		}
		
		if (strlen($content) < 100000) {
			$content = preg_replace('/[\r\n]+$/','',$content);
		}
		
		fwrite($f, $content);
		
		fclose($f);
		
		if ($compress) {
			system("gzip $filename");
		}
		
		return '';
	}

// -----------------------------------------------------------------------------
	function create_file($filename,$content='')
	{
		if (!is_file($filename)) {
		
			write_to_file($filename,$content);
		}
	}

// -----------------------------------------------------------------------------
	function delete_file($filename)
	{
		if (is_file($filename)) {
		
			if (is_writable($filename) and is_writable(dirname($filename))) {
				unlink($filename);
			}
		}
	}

// -----------------------------------------------------------------------------
	function chmod_dir($path='.',$perm=0777,$level=0)
	{  
		$ignore = array('cgi-bin','.','..'); 
		$dh = @opendir($path); 
		
		while(false !== ($file = readdir($dh))) {
	  		
	  		if( !in_array($file,$ignore)) {
		
				if( is_dir("$path/$file")) {
				  
				  	if (realpath("$path/$file") == realpath($path)."/".$file) {

						chmod("$path/$file",$perm);
		
				  		chmod_dir("$path/$file",$perm,($level+1));
				  	}
		
				} else {
					
					chmod("$path/$file",$perm);
		
				}
			}
		}
		
		closedir($dh); 
	}

// -----------------------------------------------------------------------------
	function dirlist($dir,$extensions='',$recurse=0,&$list=array(),&$path=array()) 
	{	
		$dir = rtrim($dir,'/');
		$regexp = false;
		$mtime  = false;
		
		if ($extensions and !is_array($extensions)) {
			
			if (preg_match('/^mtime /',$extensions)) {
				
				$mtime = substr($extensions,6); 
				
			} elseif (preg_match('/^\/.*\/$/',$extensions)) {
			
				$regexp = $extensions;
					
			} else {
				
				$extensions = str_replace('.','',strtolower($extensions));
				$extensions = expl($extensions); 
			}
		}
		
		if (is_dir($dir)) {
			
			if (chdir($dir)) {
			
				$dh = opendir($dir);
				
				while (false !== ($filename = readdir($dh))) {
				
					if (substr($filename,0,1) == '.') continue;
					if (substr($filename,0,1) == '_') continue;
					
					$outfile = ltrim(implode('/',$path).DS.str_replace(':','-',$filename),'/');
					
					if ($mtime) {
						
						if (is_file($dir.DS.$filename)) {
							
							$fmtime = filemtime($dir.DS.$filename);
							
							if ($fmtime > $mtime) {
								$list[$fmtime.$outfile] = $outfile.':'.$fmtime;
							}
						}
						
					} elseif ($regexp) {
						
						if (preg_match($regexp,$filename)) {
						
							$list[] = $outfile;
						}
						
					} elseif ($extensions) {
						
						if (is_file($dir.DS.$filename)) {
							
							$file_ext = explode('.',$filename);
							$file_ext = (count($file_ext) > 1) ? array_pop($file_ext) : '';
							$file_ext = preg_replace('/[^\w\d]/','',strtolower($file_ext));
							
							if ($extensions[0] == '*' and strlen($file_ext)) {
							
								$list[] = $outfile;
							
							} elseif (in_array($file_ext,$extensions)) {
								
								$list[] = $outfile;
							}
						}
					
					} else {
						
						$list[] = $outfile;
					}
					
					if ($recurse and is_dir($dir.DS.$filename)) {
						
						$path[] = $filename;
						
						dirlist($dir.DS.$filename,$extensions,$recurse,$list,$path);
						
						array_pop($path);
					}
				}
					
				closedir($dh);
			}
				
		} else {
			
			/* echo "ERROR: $dir does not exist!"; */
		}
		
		return $list;
	}

// -----------------------------------------------------------------------------
	function remove_empty_subdir($path)
	{
		$empty = true;
		
		foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file)
		{
			$empty &= is_dir($file) && remove_empty_subdir($file);
		}
		
		return $empty && rmdir($path);
	}

// -----------------------------------------------------------------------------
	function rmdirlist($directory,$regexp=FALSE,$empty=FALSE)
	{
		// if the path has a slash at the end we remove it here
		if (substr($directory,-1) == '/') {
		
			$directory = substr($directory,0,-1);
		}
		
		// if the path is not valid or is not a directory ...
		if (!file_exists($directory) || !is_dir($directory)) {
		
			// ... we return false and exit the function
			return FALSE;
	
		// ... if the path is not readable
		} elseif (!is_readable($directory)) {
		
			// ... we return false and exit the function
			return FALSE;
	
		// ... else if the path is readable
		} else {
	
			// we open the directory
			$handle = opendir($directory);
	
			// and scan through the items inside
			while (FALSE !== ($item = readdir($handle))) {
			
				// if the filepointer is not the current directory
				// or the parent directory
				if ($item != '.' && $item != '..') {
				
					if (!$regexp or ($regexp and preg_match($regexp,$item))) {
					
						// we build the new path to delete
						$path = $directory.'/'.$item;
						
						// if the new path is a directory
						if (is_dir($path)) {
						
							// we call this function with the new path
							rmdirlist($path);
			
						// if the new path is a file
						} else {
							// we remove the file
							unlink($path);
						}
					}
				}
			}
			
			// close the directory
			closedir($handle);
			
			// if the option to empty is not set to true
			if ($empty == FALSE) {
			
				// try to delete the now empty directory
				if (!rmdir($directory)) {
				
					// return false if not possible
					return FALSE;
				}
			}
			
			// return success
			return TRUE;
		}
	}

// -----------------------------------------------------------------------------
	function copy_r( $path, $dest )
    {
        if( is_dir($path) )
        {
            @mkdir( $dest , 0777);
            @chmod( $dest , 0777);
            
            $objects = scandir($path);
            if( sizeof($objects) > 0 )
            {
                foreach( $objects as $file )
                {
                    if( $file == "." || $file == ".." )
                        continue;
                    
                    if( is_dir( $path.DS.$file ) )
                    {
                        copy_r( $path.DS.$file, $dest.DS.$file );
                    }
                    else
                    {
                        copy( $path.DS.$file, $dest.DS.$file );
                    }
                }
            }
            return true;
        }
        elseif( is_file($path) )
        {
            return copy($path, $dest);
        }
        else
        {
            return false;
        }
    }

// -----------------------------------------------------------------------------
	function get_uploaded_file($f, $dest='')
	{
		global $tempdir;

		if (!is_uploaded_file($f))
			return false;

		if ($dest) {
			$newfile = $dest;
		}
		else {
			$newfile = tempnam($tempdir, 'txp_');
			if (!$newfile)
				return false;
		}

		# $newfile is created by tempnam(), but move_uploaded_file
		# will overwrite it
		if (move_uploaded_file($f, $newfile))
			return $newfile;
	}

// -----------------------------------------------------------------------------
	function shift_uploaded_file($f, $dest)
	{
		// Rename might not work, but it's worth a try
		// if (@rename($f, $dest))
		// 	return true;

		if (@copy($f, $dest)) {
		
			if (is_file($f) and is_writable($f) and is_writable(dirname($f))) {
				unlink($f);
			}
		
			return true;
		}
	}

// -----------------------------------------------------------------------------
	function upload_get_errormsg($err_code)
	{
		$msg = '';
		switch ($err_code)
		{
				// Value: 0; There is no error, the file uploaded with success.
			case UPLOAD_ERR_OK         : $msg = '';break;
				// Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
			case UPLOAD_ERR_INI_SIZE   : $msg = gTxt('upload_err_ini_size');break;
				// Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
			case UPLOAD_ERR_FORM_SIZE  : $msg = gTxt('upload_err_form_size');break;
				// Value: 3; The uploaded file was only partially uploaded.
			case UPLOAD_ERR_PARTIAL    : $msg = gTxt('upload_err_partial');break;
				// Value: 4; No file was uploaded.
			case UPLOAD_ERR_NO_FILE    : $msg = gTxt('upload_err_no_file');break;
				// Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
			case UPLOAD_ERR_NO_TMP_DIR : $msg = gTxt('upload_err_tmp_dir');break;
				// Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
			case UPLOAD_ERR_CANT_WRITE : $msg = gTxt('upload_err_cant_write');break;
				// Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0.
			case UPLOAD_ERR_EXTENSION  : $msg = gTxt('upload_err_extension');break;
		}
		return $msg;
	}

// -------------------------------------------------------------------------------------

	function make_image($path,$file,$ext) {
    	
    	preg_match('/(.+?)_([trx])_((\d+)_?(\d+)?_?([a-z]+)?)\./',$file,$matches);
		
		$ext = '.'.$ext;
		
		array_shift($matches);
		$original[] = $name = array_shift($matches);
		$original[] = $size = array_shift($matches);
		$dim    = array_shift($matches);
		$width  = array_shift($matches);
		$height = ($matches) ? array_shift($matches) : 0;
		$effect = ($matches) ? array_shift($matches) : 'none';
		
		$original = implode('_',$original).$ext;
		
		$image = $path.'/'.$name.'_'.$size.$ext;
		
		if (is_file($image)) {
			
			include txpath.'/lib/classImageManipulation.php';
			
			$new_width  = $width;
			$new_height = $height;
			$crop = 4;
			
			if ($size == 't' and !$height) {
			
				$crop = 1;
			
			} elseif ($width or $height) {
				
				list($w,$h) = getimagesize($path.'/'.$name.$ext);
				
				if ($width == '0') {
					
					$new_width = $width = round($w * ($height/$h));
				}
				
				if ($height == '0') {
					
					$new_height = $height = round($h * ($width/$w));
				}
				
				if ($width >= $height) {
					
					$new_height = $h / ($w / $new_width);
					
					while ($new_height < $height) {
						$new_width += 1;
						$new_height = $h / ($w / $new_width);
					}
					
				} else {
					
					$new_width = $w / ($h / $new_height);
					
					while ($new_width < $width) {
						$new_height += 1;
						$new_width = $w / ($h / $new_height);
					}
				}
			
			} else {
				
				list($new_width,$new_height) = getimagesize($path.'/'.$name.'_r'.$ext);
			}
			
			$new_width  = floor($new_width);
			$new_height = floor($new_height);
			
			// resize image 
			
			$suffix = $size.'_'.$dim;
			
			$img = new ImageManipulation($name,$ext,$path);
			$img->thumbnail($new_width,$new_height,$crop,$suffix);
			
			if ($width or $height) {
			
				if ($new_width > $width or $new_height > $height) {
					
					// crop image
				
					$img = new ImageManipulation($name.'_'.$suffix,$ext,$path);
					$img->crop($width,$height,2,'');
				}
			}
			
			if ($effect != 'none') {
				
				
			}
		}
	}

// -------------------------------------------------------------------------------------

	function format_bytes($bytes) {
		
		if ($bytes >= 1000000000) 	return round($bytes / 1073741824, 2).' GB';
		if ($bytes >= 10000000) 	return round($bytes / 1048576, 0).' MB';
		if ($bytes >= 1000000)		return round($bytes / 1048576, 2).' MB';
		if ($bytes >= 10000)		return round($bytes / 1024, 0).' KB';
		if ($bytes >= 500)			return round($bytes / 1024, 2).' KB';
		
		return $bytes.' B';
	}

?>
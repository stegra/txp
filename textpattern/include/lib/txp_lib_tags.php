<?php

// -----------------------------------------------------------------------------

function update_all_tags($debug=0) {
	
	global $PFX;
	
	$groups = array(
		'Articles'		=> 'publish.php;publish/taghandlers/taghandlers_article.php',
	//  'Test' 			=> 'publish/taghandlers/taghandlers_test.php',
		'Images'		=> 'publish/taghandlers/taghandlers_image.php',
		'File Download'	=> 'publish/taghandlers/taghandlers_file.php',
		'Comments'		=> 'publish/taghandlers/taghandlers_comment.php',
		'Custom Fields'	=> 'publish/taghandlers/taghandlers_custom.php',
		'Utilities'		=> 'publish/taghandlers/taghandlers_utility.php',
		'Miscellaneous'	=> 'publish/taghandlers.php'
	);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// parse php files that have been modified
	
	$lastmod = safe_column('`group`,lastmod',"txp_tag","1=1");
	
	foreach ($groups as $title => $files) {
		
		$update_group = false;
		
		unset($groups[$title]);
		
		$files = explode(';',$files);
		
		foreach ($files as $key => $file) {
			
			$file = txpath.DS.$file;
			
			if (file_exists($file)) {
				
				if (!isset($lastmod[$title])) {
				
					$update_group = true;		// new group
				
				} elseif ($lastmod[$title] < date("Y-m-d H:i:s",filemtime($file))) {
					
					$update_group = true;		// existing group modified
				}
				
				$files[$key] = $file;
			
			} else {
				
				unset($files[$key]);
			}
		}
		
		if ($update_group) {
		
			foreach ($files as $file) {
			
				$tags = parse_file_for_tags($file,$debug);
				
				if (!isset($groups[$title])) {
					$groups[$title] = $tags;
				} else {
					$groups[$title] = array_merge($groups[$title],$tags);
				}
				
				safe_delete("txp_tag_attr",
					"tag IN (SELECT tag FROM txp_tag WHERE `group` = '$title')");
					
				safe_delete("txp_tag","`group` = '$title'");
			}
		}
	}
	
	if (!count($groups)) return;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// add tags to database
	
	$columns = array(
		'tag' => array('`group`','tag','code','description','pos','lastmod'),
		'att' => array('tag','attribute','`default`','comment','pos')
	);
	
	$insert = array(
		'tag' => array(),
		'att' => array()
	);
	
	$pos = 1;
	
	foreach ($groups as $group => $tags) {
	
		foreach ($tags as $tag => $item) {
			
			if (preg_match('/[A-Z]/',$tag)) continue; 
			
			$tag = preg_replace('/_tag$/','',$tag);
			
			// functions with uppercase letters are not directly tag handlers
			
			$description = $item['comment'];
			$code        = $item['code'];
			$lastmod     = date('Y-m-d H:i:s');
			
			$insert['tag'][$tag] = array(
				'group'		  => $group,
				'tag'         => $tag, 
				'code'        => $code,
				'description' => htmlentities($description),
				'pos'         => $pos++,
				'lastmod'     => $lastmod
			);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if (count($item['atts'])) {
				
				$attpos = 1;
				
				// copy links
				
				if (isset($item['atts']['LINK'])) {
					
					$link = $item['atts']['LINK'];
					
					if (isset($tags[$link])) {
					
						// atts from linked function 
					
						if (isset($tags[$link]['atts'])) {
					
							foreach($tags[$link]['atts'] as $att => $value) {
								
								if (!isset($item['atts'][$att])) {
									$item['atts'][$att] = $value;
								}
							}
						}
					
						// code from linked function 
						
						if (preg_match('/[A-Z]/',$link)) {
							
							if (isset($tags[$link]['code'])) {
								
								$insert['tag'][$tag]['code'] .= n.n.$tags[$link]['code'];
							}
						}
					}
					
					unset($item['atts']['LINK']);
				}
				
				foreach ($item['atts'] as $att => $value) {
					
					$insert['att'][] = impl(doQuote(doSlash(array(
						'tag'       => $tag,
						'attribute' => $att,
						'default'   => array_shift($value),
						'comment'   => htmlentities(array_shift($value)),
						'pos'		=> $attpos++
					))));
				}
			}
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// insert tags
	
	foreach($insert['tag'] as $key => $tag) {
		
		$insert['tag'][$key] = impl(doQuote(doSlash($tag)));
	}
	
	$col   = implode(",",$columns['tag']);
	$val   = implode("),\n(",$insert['tag']);
	$query = "INSERT INTO ".$PFX."txp_tag ($col) VALUES".n."($val)";
	
	safe_query($query,$debug);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// insert tag attributes
	
	$col   = implode(",",$columns['att']);
	$val   = implode("),\n(",$insert['att']);
	$query = "INSERT INTO ".$PFX."txp_tag_attr ($col) VALUES".n."($val)";
	
	safe_query($query,$debug);
}

// -----------------------------------------------------------------------------
/*
	safe_delete("txp_tag","1=1",1);
	safe_delete("txp_tag_attr","1=1",1);
	update_all_tags(0);
*/
// -----------------------------------------------------------------------------

function parse_file_for_tags($file,$debug=0) {
	
	$f = fopen($file,"r");
	
	$current   = '';
	$atts      = false;
	$tags	   = array();
	$code      = array();
	$comment   = array();
	$lines     = array('');
	
	$name      = '([Aa-z0-9_]+)';
	$params    = '(\(\$atts|\(\))';
	$function  = '/^function\s+'.$name.$params.'/';
	$value     = '(.*)';
	$attribute = "/^\'".$name."\'\s+\=\>\s+([\"\'])?".$value."/";
	$end_atts  = '^'.implode('|',array(
		'(\),\s*\$atts\)\);)',
		'(\);\s*\/\/ end of atts)'
	));
	$atts_link = '^\/\/ atts\.'.$name.'$';
	
	$boundary  = '/^\/\/ ?[\-\=]+$/'; 	// the boundary comment line between functions
	
	$start_comment = "/^\/\*$/";		// start of a comment block
	$end_comment   = "/^ ?\*\/$/";		// end of a comment block
	$line_comment  = "/^\/\/\s(.*)/";	// line by line comment
	
	$is_comment  = false;
	$is_function = false;
	
	$start_of_tags = false;
	$end_of_tags   = false;
	
	while (!feof($f) and !$end_of_tags) {
		
		$line = fgets($f);
   		$line = preg_replace('/^(    |\t)/','',$line);
   		
   		if (preg_match($start_comment,rtrim($line))) {
   			
   			$lines[] = 'start comment';
   			
   			$is_comment = true;
   			$code = array();
   			$comment = array();
   		
   		} elseif (preg_match($end_comment,rtrim($line))) {
   			
   			$lines[] = 'end comment';
   			
   			$is_comment = false;
   			
		} elseif (preg_match($boundary,rtrim($line))) {
   			
   			if (preg_match('/\=/',$line)) {
   				
   				if ($current) { 
   					$end_of_tags = true;
   				} else {
   					$start_of_tags = true;
   				}
   			}
   			
   			$lines[] = '---------------------------------------------';
   			
   			if ($current and !strlen($tags[$current]['code'])) {
   				
   				if ($code) {
   					$lines[count($lines)-1] .= n.t."add code to $current: ".count($code);
   					$tags[$current]['code'] = trim(implode($code,n));
   				}
   				
   				if ($comment) {
   					$lines[count($lines)-1] .= n.t."add comment to $current: ".count($comment);
   					$tags[$current]['comment'] = trim(implode($comment,n));
   				}
   			}
   			
   			$code    = array();
   			$comment = array();
   		
   		} elseif (preg_match($line_comment,rtrim($line),$matches)) {
   			
   			$lines[] = "line comment";
   			
   			$comment[] = trim($matches[1]);
   		
   		} elseif (!$is_comment) {
   			
   			if (preg_match($function,trim($line),$matches)) {
				
				// match function
				
				$lines[] = "function $matches[1]";
				
				if ($start_of_tags) {
				
					$current  = $matches[1];
					$tagatts  = $matches[2];
					
					$tags[$current]['atts'] = array();
					$tags[$current]['code'] = '';
					$tags[$current]['comment'] = '';
					
					$atts = ($tagatts == '()') ? false : true;
				}
			
			} elseif ($atts and preg_match($attribute,trim($line),$matches)) {
				
				// match attribute
				
				$lines[] = "attr $matches[1]";
				
				$attname = $matches[1];
				$quote   = $matches[2];
				
				$attcom  = explode('//',$matches[3]);
				$attval  = trim(array_shift($attcom));
				$attval  = rtrim(rtrim($attval,','),$quote);
				$attcom  = trim(array_shift($attcom));
				
				if ($current) {
					$tags[$current]['atts'][$attname] = array($attval,$attcom);
				}
			
			} elseif (preg_match('/'.$atts_link.'/',trim($line),$matches)) {
			
				if ($current) {
					$tags[$current]['atts']['LINK'] = $matches[1];
				}
			
			} elseif (preg_match('/'.$end_atts.'/',trim($line))) {
				
				// match end of attribute list
				
				$lines[] = "end of atts";
				
				$atts = false;	
			
			} else {
				
				$lines[] = (strlen(trim($line))) ? 'line' : '';
			}
			
			if ($start_of_tags) {
				$code[] = $line;
			}
		
		} else {
			
			$lines[] = 'comment';
			
			if ($start_of_tags and preg_match('/^ ?\*(\s)?(.*)/',rtrim($line),$matches)) {
				$comment[] = $matches[2];
			}
		}
	}
	
	// last tag
	
	if ($current and !strlen($tags[$current]['code'])) {
   				
		if ($code) {
			$lines[count($lines)-1] .= n.t."add code to $current: ".count($code);
			$tags[$current]['code'] = trim(implode($code,n));
		}
		
		if ($comment) {
			$lines[count($lines)-1] .= n.t."add comment to $current: ".count($comment);
			$tags[$current]['comment'] = trim(implode($comment,n));
		}
	}
 			
	fclose($f);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	/*
	if ($debug) {
		
		echo "<pre>";
		echo basename($file);
		echo n.'---------------------------------------------------';
		foreach ($lines as $num => $line) {
			if ($num) echo n.str_pad($num,4,' ',STR_PAD_LEFT).': '.$line;
		}
		echo n.'---------------------------------------------------';
		echo "</pre>";
	}
	*/
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	return $tags;
}

?>
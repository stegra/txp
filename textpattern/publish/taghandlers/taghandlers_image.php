<?php

// =============================================================================
	function image($atts,$thing=NULL,$get='') 
	{
		global $site_dir,$img_dir,$article_stack,$content_type_stack,$thisarticle;
		global $is_article_list,$path_to_site,$siteurl,$tag_counter;
		
		static $image_counter = 0;
		
		$sizes = array('o','r','t','x','y','z');
		
		if (!defined('IMPATH')) define("IMPATH",$path_to_site.'/'.$img_dir.'/');
    	
    	extract(lAtts(array(
			'id'        => '',
			'name'      => '',
			'thumbnail' => '',
			'class'     => '',
			'style'     => '',
			'align'     => '',
			'poplink'   => '',
			'wraptag'   => '',
			'type'      => 'before-body', // deprecated
			'place' 	=> 'before-body', // no longer necessary (image placement is detected automaticaly)
			'size'   	=> '',	
			'padding'   => 1,	
			'border'	=> 1,	
			'link'		=> '',	
			'form'		=> '',	
			'getmax'	=> '',	// get maximum width & height for group of images
			'width'		=> '',	
			'height'	=> '',	
			'title'		=> '',	
			'alt'		=> '',	
			'rel'		=> '',	
			'href'		=> ''	
		),$atts)); 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (empty($id)) {
			
			if (!isset($thisarticle['image_id'])) return '';
			
			// if the image ID was not given then then use 
			// the image associated with the current article
			$display_list   = null;
			$display_single = null;
			$imgtype_list   = null;
			$imgtype_single = null;
			$align_list		= null;
			$align_single   = null;
			
			$article_id     = $thisarticle['thisid'];
			$image_id 	    = $thisarticle['image_id'];
			
			if ($image_id) {
				
				/* if (isset($thisarticle['image_data'])) {
					
					$data = explode(':',$thisarticle['image_data']);
					
					$display_list	= $data[0];
					$imgtype_list 	= $data[1];
					$align_list		= $data[2];
					$display_single	= $data[3];
					$imgtype_single	= $data[4];
					$align_single	= $data[5];
				} */
			}
			
			if ($type != "album" ) {
			
				if (isset($atts['type'])) {
					
					$place = $type; // for backwards compatibility
				
				} elseif (!isset($atts['place'])) {
					
					if ($article_stack->get('body_tag_encounter')) {
						
						$place = "after-body";
					}
				}
				
				if ($is_article_list) {
					if (!$size)  $size  = $imgtype_list; 
					if (!$align) $align = ($align_list != '-') ? $align_list : ''; 
					if ($display_list == 'x') $image_id = 0;
				} else {
					if (!$size)  $size  = $imgtype_single; 
					if (!$align) $align = ($align_single != '-') ? $align_single : ''; 
					if ($display_single == 'x') $image_id = 0;
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($thing) {
		
			$form = $thing;
		
		} elseif ($form) {
		
			$form = fetch_form($form,'image');
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get image by ID
		
		if ($image_id) {
			
			$res = safe_row("
			FilePath,name,ext,w,h,
			Categories,
			Body AS caption,
			alt AS alt_text,
			copyright,
			keywords,
			thumbnail AS crop",
			"txp_image",
			"id='$image_id' limit 1");
			
		} else {
		
			return parse(EvalElse($form, false));
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// display image if there was one
		
		if ($res) {
			
			extract($res); 
			
			// ALT text 
			
			if (strlen($alt_text)) {
			
				if (substr($alt_text,0,1) == '+') {
					
					// append image alt text 
					
					$alt .= ' '.trim(substr($alt_text,1));
				
				} else {
					
					// replace with image alt text
					
					$alt = $alt_text;
				}
			}
			
			$count = (isset($tag_counter['image']))
				? $tag_counter['image'] += 1
				: $tag_counter['image']  = 1;
			
			// $count = ++$image_counter;
			
			$ow = $w;	// original size image width
			$oh = $h;	// original size image height
			
			if (!$size) $size = ($thumbnail) ? 't' : 'r';
			
			$s = ($size != 'o') ? '_'.$size : '';
			
			$found_ext = '';
			$image_path_name = IMPATH.$FilePath.DS.$name.$s;
			
			if (is_file($image_path_name.$ext)) {
			
				$found_ext = $ext;
				
			} else {
				
				foreach (do_list('.jpg,.png,.gif') as $alt_ext) {
					
					if (is_file($image_path_name.$alt_ext)) {
					
						$found_ext = $alt_ext;
					}
				}
			}
			
			if ($found_ext) {
				
				$ext = $found_ext;
				
				list($w,$h) = getimagesize($image_path_name.$ext);
			
			} else {
			
				return comment('missing: '.$image_path_name.$ext);
			}
			
			$resize = false;
			
			if (isset($atts['width']) and isset($atts['height'])) {
				
				if (in_list($width,'0,*')) {
					
					$width = round($w * ($height/$h));
				}
				
				if (in_list($height,'0,*')) {
					
					$height = round($h * ($width/$w));
				}
				
				$w = $width;
				$h = $height;
				
				$resize = true;
			
			} elseif ($width and $width <= $w) {
			
				$h = (!$height) ? round($h * ($width / $w)) : $height;
				$w = $width;
			
			} elseif ($height and $height <= $h) {
				$w = (!$width) ? round($w * ($height / $h)) : $width;
				$h = $height;
			}
			
			if ($w > $ow) $w = $ow;
			if ($h > $oh) $h = $oh;
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($getmax) {
				
				$reset = (article_num() == 1) ? 1 : 0;
				
				image_max_width('',$w,$reset);
				image_max_height('',$h,$reset);
				
				return;
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			// $src = hu.$img_dir.'/'.$FilePath.'/'.$name.'_'.$size.$ext;
			
			$src = $img_dir.'/'.$FilePath.'/'.$name.$s;
			$src = ($site_dir) 
				? 'http://'.$_SERVER['SERVER_NAME']."/sites/$site_dir/$src"
				: hu.$src;
			
			if ($resize) {
				$src .= '_'.$w.'_'.$h;
			}
			
			$src .= $ext;
			
			$align  = ($align == '*') ? '' : $align; 
			$class  = ($class) ? "$class image{$count}" : "image{$count}";
			$title  = ($title) ? ' title="'.$title.'"' : "";
			$rel    = ($rel)   ? ' rel="'.$rel.'"' : "";
			
			// image caption (this is not really necessary anymore)
			// $caption = ($form != 'this' && $thing) ? trim(parse($thing)) : $caption;
			// $caption = ($caption == '[category]') ? fetch_category_title($category) : $caption;
			
			// mouseover action if image is a link
			$mouseover = ($link || $poplink) 
				? ' onmouseover="border(\'image'.$image_id.$size.'\')" onmouseout="border(\'image'.$image_id.$size.'\',\'out\')"' 
				: '';
			
			// popuplink script
			$onclick = ($poplink) 
				? ' onclick="window.open(this.href,\'popupwindow\',\'width='.$ow.',height='.$oh.',scrollbars,resizable\'); return false;"' 
				: '';
			
			switch ($crop) {
				case 1 : $crop = 'left'; break;
				case 2 : $crop = 'center'; break;
				case 3 : $crop = 'right'; break;
				case 4 : $crop = ''; break;
			}
				
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			 $thisimage = array(
				'id'		=> $image_id,
				'path'		=> $FilePath,
				'count'		=> $count,
				'src' 		=> $src,
				'width'		=> $w,
				'height'	=> $h,
				'size'		=> $size,
				'align'		=> $align,
				'padding'	=> $padding,
				'border'	=> $border,
				'class'		=> $class,
				'style'		=> $style,
				'alt'		=> trim($alt),
				'caption'	=> $caption,
				'copyright'	=> $copyright,
				'keywords'	=> $keywords,
				'original'	=> array('width' => $ow, 'height' => $oh),
				'name'		=> $name,
				'ext'		=> $ext,
				'sizes'		=> $sizes,
				'title'		=> safe_field("Title","txp_image","ID = $image_id"),
				'crop'		=> $crop,
				'resize'	=> $resize
			);
			
			$thisarticle['image'] = $thisimage;			// phase out
			
			$article_stack->set('image',$thisimage);
			$content_type_stack->push('image');
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$out = '';
			
			if ($get) {
				
				$func = 'image_'.$get;
				
				if (function_exists($func)) return $func();
			
			} elseif ($form) {
				
				$out = preg_replace("/\s+/",' ',parse(EvalElse($form, true)));
				
				if ($link) {
					$out = '<a href="'.$href.'"'.$rel.$onclick.$title.'>'.$out.'</a>';
				}
			
			} else {
			
				$out = image_tag();
				
				if ($link) {
					$out = '<a href="'.$href.'"'.$rel.$onclick.$title.'>'.$out.'</a>';
				}
				
				if ($caption) {
					$out = $out.image_caption();
				}
				
				if ($type != 'album') {
					$out = '<div class="image '.$align.'"'.$mouseover.'>'.n.$out.n.'</div>'.n;
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$content_type_stack->pop();
			
			if(!empty($wraptag)) return tag($out,$wraptag);
					
			return $out;
		}
	}

// -----------------------------------------------------------------------------
// experimental function 

/*	function image_id($atts) 
	{
		global $article_stack;
	
		extract(lAtts(array(
			'stack' => ''
		),$atts));
		
		$image = $article_stack->get('image',$stack);
		
		return $image['id'];
	}
*/
// -----------------------------------------------------------------------------
	function image_id() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['id'];
    }

// -----------------------------------------------------------------------------
	function image_path() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['path'];
    }

// -----------------------------------------------------------------------------
	function image_count() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['count'];
    }

// -----------------------------------------------------------------------------
	function image_crop() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['crop'];
    }
    
// -----------------------------------------------------------------------------
	function image_src($atts) 
    {
    	global $site_dir, $img_dir, $thisarticle;
    	
    	extract(lAtts(array(
    		'size' => ''
    	),$atts));
    	
    	$size  = strtolower($size);
    	
    	if (!isset($thisarticle['image'])) {
    		image(array('type'=>'album'),'','none');
    	}
    	
    	$id_path = $thisarticle['image']['path'];
    	$name    = $thisarticle['image']['name'];
    	$ext     = $thisarticle['image']['ext'];
    	$sizes   = $thisarticle['image']['sizes'];
    	
    	if (in_array($size,$sizes)) {
    	
    		$size = ($size == 'o') ? '' : '_'.$size;
    		
    		$src = $img_dir.'/'.$id_path.'/'.$name.$size.$ext;
    		
    		return ($site_dir) ? '/~'.$site_dir.'/'.$src : '/'.$src;
    	}
    	
    	return $thisarticle['image']['src'];
    }

// -----------------------------------------------------------------------------
	function image_name() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['name'];
    }
    
// -----------------------------------------------------------------------------
	function image_original_src($atts) 
    {
    	$atts['size'] = 'o';
    	
    	return image_src($atts);
    }

// -----------------------------------------------------------------------------
	function image_width($atts=array()) 
    {
    	global $thisarticle;
    	
    	extract(lAtts(array(
    		'scale' => '',
    		'plus'  => 0,
    		'minus' => 0,
    		'max'   => 0
    	),$atts));
    	
    	if (isset($thisarticle['image']))
    		$width = $thisarticle['image']['width'];
    	else
    		$width = image(array('type'=>'album'),'','width');
    		
    	if ($scale) $width = round($width * $scale / 100);
    	
    	$width = abs($width + $plus - $minus);
    	
		return ($max > 0 and $max < $width) ? $max : $width;
    }

// -----------------------------------------------------------------------------
	function image_height($atts=array()) 
    {
    	global $thisarticle;
    	
    	extract(lAtts(array(
    		'scale' => '',
    		'plus'  => 0,
    		'minus' => 0,
    		'max'   => 0
    	),$atts));
    	
    	if (isset($thisarticle['image']))
    		$height = $thisarticle['image']['height'];
    	else
    		$height = image(array('type'=>'album'),'','height');
    		
    	if ($scale) $height = round($height * $scale / 100);  
		
    	$height = abs($height + $plus - $minus);
    	
		return ($max > 0 and $max < $height) ? $max : $height;
    }

// -----------------------------------------------------------------------------
	function image_original_width() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['original']['width'];
    }

// -----------------------------------------------------------------------------
	function image_original_height() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['original']['height'];
    }

// -----------------------------------------------------------------------------
	function image_max_width($atts,$w=0,$reset=0) 
    {
    	static $width = 0;
    	
    	extract(lAtts(array(
    		'plus' => 0
    	),$atts));
    	
    	if ($reset) { $width = 0; return; }
    	
    	if ($w > $width) $width = $w;
    	
    	return $width + $plus;
    }

// -----------------------------------------------------------------------------
	function image_max_height($atts,$h=0,$reset=0) 
    {
    	static $height = 0;
    	
    	extract(lAtts(array(
    		'plus' => 0
    	),$atts));
    	
    	if ($reset) { $height = 0; return; }
    	
    	if ($h > $height) $height = $h;
    	
    	return $height + $plus;
    }
 
// -----------------------------------------------------------------------------
// calculate the relative left position for horizontal centering 

	function image_left($atts) 
    {
    	global $thisarticle;
    	
    	extract(lAtts(array(
    		'maxwidth' => 0
    	),$atts));
    	
    	if ($maxwidth == 0) return 0;
    	
    	if (isset($thisarticle['image']))
    		$width = $thisarticle['image']['width'];
    	else
    		$width = image(array('type'=>'album'),'','width');
    		
    	if ($width >= $maxwidth) 
    		return '00';
    	else
    		return round(($maxwidth - $width) / 2); 
    	
    }

// -----------------------------------------------------------------------------
// calculate the relative top position for vertical centering 

	function image_top($atts) 
    {
    	global $thisarticle;
    	
    	extract(lAtts(array(
    		'maxheight' => 0
    	),$atts));
    	
    	if ($maxheight == 0) return 0;
    	
    	if (isset($thisarticle['image']))
    		$height = $thisarticle['image']['height'];
    	else
    		$height = image(array('type'=>'album'),'','height');
    		
    	if ($height >= $maxheight) 
    		return '00';
    	else
    		return round(($maxheight - $height) / 2); 
    	
    }
    
// -----------------------------------------------------------------------------
	function image_size($atts) 
    {
    	$width  = image_width($atts);
    	$height = image_height($atts);
    	
    	return 'width="'.width.'" height="'.$height.'"';
    }

// -----------------------------------------------------------------------------
// NOTE: This could be handled by the txp:if_var tag

	function if_image_size($atts,$thing=NULL) 
    {
    	global $thisarticle; 
    	
    	extract(lAtts(array(
    		'width'  => '0',
    		'height' => '0'
    	),$atts));
    	
    	if (!$width and !$height) return '';
    	
    	$prefix = htmlspecialchars('>,<,>=,<=,!=,!');
    	$prefix = str_replace(',','|',preg_quote($prefix));
    	$prefix = "/^($prefix)\d/";
    	
    	if ($width) { 
    		
    		$op = '==';
    		
    		if (preg_match($prefix,$width,$matches)) {
    			$op = $matches[1];
    			$width = ltrim($width,$op); 
    			$op  = htmlspecialchars_decode($op);
    			$op .= ($op == '!') ? '=' : '';
    		}
    		
    		$size = image_width($atts);
    		eval('$width = ($size '.$op.' $width);');
    	}
    	
    	if ($height) { 
    	
    		$op = '==';
    		
    		if (preg_match($prefix,$height,$matches)) {
    			$op = $matches[1];
    			$height = ltrim($height,$op);
    			$op  = htmlspecialchars_decode($op);
    			$op .= ($op == '!') ? '=' : '';
    		}
    		
    		$size = image_height($atts);
    		eval('$height = ($size '.$op.' $height);');
    	}
    	
    	return parse(EvalElse($thing, ($width or $height)));
    }
    
// -----------------------------------------------------------------------------
	function image_align() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['align'];
    }

// -----------------------------------------------------------------------------
	function image_class() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['class'];
    }

// -----------------------------------------------------------------------------
	function image_style() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['style'];
    }

// -----------------------------------------------------------------------------
	function image_alt() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['alt'];
    }

// -----------------------------------------------------------------------------
	function image_copyright() 
    {
    	global $thisarticle;
    	
    	$copyright = $thisarticle['image']['copyright'];
    	
    	return ($copyright) ? '&copy; '.$copyright : '';
    }

// -----------------------------------------------------------------------------
	function image_tag() 
    {
    	global $thisarticle; 
    	
    	extract($thisarticle['image']);
    	
    	$imgatts = array(
			'id'  	 => 'img'.$id,
			'src' 	 => $src,
			'width'  => $width, 
			'height' => $height, 
			'border' => 0, 
			'alt'	 => $alt,
			'class'	 => $class,
			'style'  => $style
		);
		
		return tag('','img',$imgatts);
	}

// -----------------------------------------------------------------------------
	function image_caption() 
    {
    	global $thisarticle; 
    	
    	extract($thisarticle['image']);
    	
    	if ($align != 'center') 
    		$width = $width + (2 * $padding) + (2 * $border).'px';
    	else
    		$width = $width + (2 * $border).'px';
    	
    	return ($caption && $size == 'r') 
    		? '<div class="caption-box" style="width:'.$width.'">
			   <div class="caption">'.$caption.'</div>'.n.'</div>'.n
			: '';	 
	}

// -----------------------------------------------------------------------------
	function if_image_caption($atts,$thing=NULL) 
    {
    	global $thisarticle; 
    	
    	$test = $thisarticle['image']['caption'];
    	
    	return parse(EvalElse($thing, $test));
    }

// -----------------------------------------------------------------------------
	function image_caption_text() 
    {
    	global $thisarticle; 
    	
    	extract($thisarticle['image']);
    	
    	return $caption;
	}

// -----------------------------------------------------------------------------
	function image_keywords() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['keywords'];
    }

// -----------------------------------------------------------------------------
	function if_image_keywords($atts,$thing=NULL) 
    {
    	global $thisarticle; 
    	
    	$test = $thisarticle['image']['keywords'];
    	
    	return parse(EvalElse($thing, $test));
    }

// -----------------------------------------------------------------------------
	function image_title() 
    {
    	global $thisarticle;
    	
    	return $thisarticle['image']['title'];
    }

// -----------------------------------------------------------------------------
	function thumbnail($atts) 
    {
    	$atts['thumbnail'] = '1';
    	
    	return image($atts);
    }

// =============================================================================
    
?>

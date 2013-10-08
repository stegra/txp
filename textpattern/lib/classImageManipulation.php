<?php
// classImageManipulation
// v0.4 2004-11-04

// Current feature set:
// Resize
// Thumbnail
// Update TXP database

// TODO:
// Rotation of image's.
// Watermarking
// Add NetPBM support for everything stuff

class ImageManipulation
{
	// Naming Conventions
	// VAR => original file
	// VAR_r => resized original file.
	// VAR_t => thumbnail original file
	// VAR_n => the new dimensions/filename/whatever

	var $fn; 	// filename
	var $fn_r;
	var $fn_t;

	var $ext;	// extension
	var $ext_r;
	var $ext_t;

	var $dim = NULL; // dimensions, key'd array 'w'=> width, 'h'=> height
	var $dim_r = NULL;
	var $dim_t = NULL;

	var $fullpath;	// Fullpath $txpath+$path_from_root+$img_dir+[additional directories]
	var $impath; 	// path to ImageMagick

	var $id; // ID of image, use this for updating a database
	var $table = "txp_image";
	var $imagesys = 0; // 0=>GD, 1=>IM, 2=>NetPBM
	var $keeporiginal = false; // When resizing, keep original file or not
							   // TRUE: resize filename == $fn.r.$ext_r

	var $resize_sys = array("resize_GD","resize_IM","resize_NetPBM");
	var $thumbnail_sys = array("thumbnail_GD","thumbnail_IM","thumbnail_NetPBM");

	var $did_resize = false;
	var $did_thumbnail = false;
	
	var $thumb = '';		// 't' when generating thumbnails or resizing from custom 
							// thumbnail instead of the original image

	// -----------------------------------------------------------------------------
	// Constructor
	// filename = filename of image to manipulate
	// path = path relative to TXP imagedir
	function ImageManipulation($fn,$ext,$path='',$isys=0,$keep=true,$imp='',$thumb=0)
	{
		global $txpcfg,$path_from_root,$img_dir;

		// $this->thumb = ($thumb == 4) ? 'THUMB' : '';
		
		$this->setFilename($fn);
		$this->setExt($ext);
		$this->setPath($path);
		$this->setImageSystem($isys,$imp);
		$this->setKeepOriginal($keep);
		
		list($this->dim['w'],$this->dim['h']) = getimagesize($this->fullpath.$this->fn.$this->thumb.$this->ext);
	}

	// -----------------------------------------------------------------------------
	// w_n == new width of resized image, set 0 to lock h_n
	// h_n == new height of resized image, set 0 to lock w_n
	// quality == resulting quality of JPEG image
	function resize($w_n, $h_n=0, $quality=95)
	{
		// No upscaling of images!
		// if( ($w_n >= $this->dim['w']) || ($h_n >= $this->dim['h']) ) {
		if( ($w_n > $this->dim['w']) || ($h_n > $this->dim['h']) ) {
			$this->dim_r = $this->dim; 
			$this->did_resize = false;
			$rs = true;
		} else {
			$this->dim_r = $this->calcDim($this->dim['w'],$this->dim['h'],$w_n, $h_n);

			$funct = $this->resize_sys[$this->imagesys];
			$rs = $this->$funct($quality);

			if($rs) {
				$this->did_resize = true;
			} else {
				return 0;
			}
			
			if(!$this->keeporiginal) {  // If we arn't keeping the original, make the resize the orignal.
				$this->dim = $this->dim_r;
				$this->fn_r = $this->fn;
				$this->ext = $this->ext_r;
			}
		}
		
		return array('w' => $this->dim_r['w'], 'h' => $this->dim_r['h']);
	}

	// -----------------------------------------------------------------------------
	function crop($w_n, $h_n=0, $crop=0, $type='t', $quality=95)
	{
		if ($w_n == 0 && $crop != 4) $w_n = $h_n;
		if ($h_n == 0 && $crop != 4) $h_n = $w_n;
		
		$this->dim_t = (!empty($this->dim_r))
				 		? $this->calcDim($this->dim_r['w'],$this->dim_r['h'],$w_n, $h_n)
				 		: $this->calcDim($this->dim['w'],$this->dim['h'],$w_n, $h_n);
		
		$type = ($type) ? '_'.$type : '';
		$this->fn_t = preg_replace('/_[rtx]+$/','',$this->fn).$type;
		
		$funct = $this->thumbnail_sys[$this->imagesys];
		$rs = $this->$funct($crop,$quality,true);

		if($rs) {
			$this->did_thumbnail = true;
			return array('w' => $this->dim_t['w'], 'h' => $this->dim_t['h']);
		} else {
			return 0;
		}
	}
	
	// -----------------------------------------------------------------------------
	function thumbnail($w_n, $h_n=0, $crop=0, $type='t', $quality=95)
	{
		if ($w_n == 0 && $crop != 4) $w_n = $h_n;
		if ($h_n == 0 && $crop != 4) $h_n = $w_n;
		
		$this->dim_t = (!empty($this->dim_r))
				 		? $this->calcDim($this->dim_r['w'],$this->dim_r['h'],$w_n, $h_n)
				 		: $this->calcDim($this->dim['w'],$this->dim['h'],$w_n, $h_n);
		
		$type = ($type) ? '_'.$type : '';
		
		$this->fn_t = preg_replace('/_[rtx]+$/','',$this->fn).$type;
		$this->fn_t = preg_replace('/_THUMB_/','_',$this->fn_t);
		
		$funct = $this->thumbnail_sys[$this->imagesys];
		$rs = $this->$funct($crop,$quality);

		if($rs) {
			$this->did_thumbnail = true;
			return array('w' => $this->dim_t['w'], 'h' => $this->dim_t['h']);
		} else {
			return 0;
		}
	}
	
	// -----------------------------------------------------------------------------
	function getImageSystem()
	{
		switch($this->imagesys) {
			case 0  : $sys = "GD";     break;
			case 1  : $sys = "IM";  	break;
			case 2  : $sys = "NetPBM"; break;
			default : $sys = "GD";     break;
		}
		return $sys;
	}

	// -----------------------------------------------------------------------------
	function getDimensions($type=0)
	{
		switch($type) {
			case 0 : return $this->dim;   break;
			case 1 : return $this->dim_r; break;
			case 2 : return $this->dim_t; break;
			default: return $this->dim;   break;
		}
	}

	// -----------------------------------------------------------------------------
	function setImageMagickPath($imp='')
	{
		if(empty($imp)) {
			$shell_result = ''; // `whereis convert`;
			if(!empty($shell_result)) {;
				preg_match('/(\/.*\/)convert[ ]/',$shell_result,$match);
				$this->impath = (!empty($match[1])) ? $match[1] : '/usr/local/bin/'; // assume default when whereis returns nothing.
			} else {
				$this->impath = '';
			}
		} else {
			$this->impath = $imp;
		}
	}

	// -----------------------------------------------------------------------------
	function setImageSystem($sys,$imp='')
	{
		$sys = ($sys >= 0 && $sys <= 2) ? $sys : 0;
		if($sys == "IM")
			$this->setImageMagickPath($imp); 		
		$this->imagesys = $sys;
	}

	// -----------------------------------------------------------------------------
	function setPath($path)
	{
		global $txpcfg,$path_to_site,$path_from_root,$img_dir;
		$this->path = $path;
		// $this->fullpath = str_replace('//','/',$txpcfg['doc_root'].$path_from_root.$img_dir.'/'.$path.'/');
		// $this->fullpath = str_replace('//','/',$path_to_site.$path_from_root.$img_dir.'/'.$path.'/');
		// $this->fullpath = IMPATH.$path.DS;
		
		$this->fullpath = $path.DS;
	}

	// -----------------------------------------------------------------------------
	function setFilename($fn)
	{
		$this->fn = $fn;
		$this->fn_r = preg_replace('/_[rt]$/','',$fn).'_r';
		$this->fn_t = preg_replace('/_[rt]$/','',$fn).'_t';
	}

	// -----------------------------------------------------------------------------
	function setExt($ext)
	{
		$this->ext = $this->ext_r = $this->ext_t = $ext;
	}

	// -----------------------------------------------------------------------------
	function setKeepOriginal($keep)
	{
		$this->keeporiginal = (!empty($keep)) ? true : false;
	}

	// Database Functions ----------------------------------------------------------
	// -----------------------------------------------------------------------------
	
	function setID($id) {
		$this->id = $id;
	}

	function setTable($table) {
		$this->table = $table;
	}

	function updateDB($otxp=false) // Give parameter "true" if you are updating the stock TXP image table
	{
		$sql = array();

		if($this->table == 'txp_image' || $otxp) {
			if($this->did_resize) {
				$sql[] = "w='". $this->dim_r['w']. "'";
				$sql[] = "h='". $this->dim_r['h']. "'";
			}

			if($this->did_thumbnail) {
				$sql[] = "thumbnail='1'";
			}
		} else {
			$sql[] = "w='". $this->dim['w'] ."'";
			$sql[] = "h='". $this->dim['h'] ."'";

			if($this->keeporiginal) {
				$sql[] = ($this->did_resize) ? "original='1'" : "original='0'";
			} else {
				$sql[] = "original='0'";
			}

			if($this->did_resize) { $sql[] = "resize='1'"; }
			if($this->did_thumbnail) { $sql[] = "thumbnail='1'"; }
		}

		return safe_update($this->table,implode(',',$sql),"id='".$this->id."'",1);
	}

//----------------------------------------------------------------------------------
//	Private Functions
//----------------------------------------------------------------------------------

	// Created by Caged : http://www.purephotoshop.com
	function getGDversion()
	{
		if(!function_exists('imagecreatetruecolor')){
			$function_create = 'imagecreate';
			$function_copy = 'imagecopyresized';
		} else {
			$function_create = 'imagecreatetruecolor';
			$function_copy = 'imagecopyresampled';
		}
		return array('copy' => $function_copy, 'create' => $function_create);
	}

	// -----------------------------------------------------------------------------
	function calcDim($w, $h,$w_n, $h_n)
	{
		if($w_n == 0) {
			$finX = round($h_n * ($w / $h));
			$finY = $h_n;
		} else {
			$finX = $w_n;
			$finY = ($h_n == 0) ? round($w_n * ($h / $w)) : $h_n;
		}

		return array('w' => $finX, 'h' => $finY);
	}

	// ---- Thumbnail Functions ----------------------------------------------------
	// -----------------------------------------------------------------------------
	function thumbnail_GD($crop,$quality,$crop_only=false)
	{
		// Get Version of GD
		$gd = $this->getGDVersion();
		
		$path = str_replace('//','/',$this->fullpath.$this->fn.$this->thumb.$this->ext);
		$thumbpath = str_replace('//','/',$this->fullpath.$this->fn_t.$this->ext_t);
		$alpha = false;
		
		if ($this->ext == '.jpg')
			$source_id = imageCreateFromJPEG($path);
		elseif ($this->ext == '.gif')
			$source_id = imageCreateFromGIF($path);
		elseif ($this->ext == '.png') {
			$source_id = imageCreateFromPNG($path);
			if (png_has_transparency($path) and function_exists('imagealphablending')) {
				$alpha = true;
			}
		} else return;
		
		// This is for proper square thumbnails
		if($this->dim_t['w'] == $this->dim_t['h']) {
			$tempw = ($this->dim['w'] > $this->dim['h']) ? 0 : $this->dim_t['w'];
			$temph = ($this->dim['w'] > $this->dim['h']) ? $this->dim_t['h'] : 0;
		} else {
			$tempw = $this->dim_t['w'];
			$temph = $this->dim_t['h'];
		}
		$dest = $this->calcDim($this->dim['w'],$this->dim['h'],$tempw,$temph);

		// Create a new image object (not neccessarily true colour)
		$target_id = $gd['create']($dest['w'], $dest['h']);

		if ($alpha) imagealphablending($target_id, false);

		$dest_w = $dest['w'];
		$dest_h = $dest['h'];
		
		if ($crop_only) {
			$dest_w = $this->dim['w'];
			$dest_h = $this->dim['h'];
		}
		
		// Resize the original picture and copy it into the just created image object.
		$target_pic = $gd['copy']($target_id,$source_id,
									0,0,0,0,
									$dest_w,$dest_h,
									$this->dim['w'],$this->dim['h']);
		
		if ($alpha) imagesavealpha($target_id, true);
			
		// make square thumbnail
		
		if($this->dim_t['w'] == $this->dim_t['h']) { 
			
			$temp_id = $gd['create']($this->dim_t['w'],$this->dim_t['h']);
			
			if ($alpha) imagealphablending($temp_id, false);
			
			if ($dest['w'] > $dest['h']) {
				
				if ($crop == 0) $crop = 2;
				if ($crop == 1) $srcX = 0;								// left
				if ($crop == 2) $srcX = ($dest['w'] - $dest['h']) / 2;  // middle
				if ($crop == 3) $srcX = ($dest['w'] - $dest['h']);		// right
				if ($crop == 4) $srcX = 0;
				
				$srcY = 0;
			}
			
			if ($dest['w'] < $dest['h']) {
				
				if ($crop == 0) $crop = 1;
				if ($crop == 1) $srcY = 0;								// top
				if ($crop == 2) $srcY = ($dest['h'] - $dest['w']) / 2;	// middle
				if ($crop == 3) $srcY = ($dest['h'] - $dest['w']);		// bottom
				if ($crop == 4) $srcY = 0;
				
				$srcX = 0;
			}
			
			if ($dest['w'] == $dest['h']) {
				
				$srcY = $srcX = 0;
			}
			
			$gd['copy']($temp_id, $target_id,0,0,$srcX,$srcY,$this->dim_t['w'],$this->dim_t['h'],$this->dim_t['w'],$this->dim_t['h']);
			
			if ($alpha) imagesavealpha($temp_id, true);
			
			$target_id = $temp_id;
		}
		
		if ($this->ext_t == '.jpg')
			imagejpeg($target_id,$thumbpath,$quality);
		elseif ($this->ext_t == '.gif')
			imagegif($target_id,$thumbpath);
		elseif ($this->ext_t == '.png')
			imagepng($target_id,$thumbpath,0);

		imagedestroy($target_id);
		imagedestroy($source_id);
		
		if (is_file($thumbpath)) {
			
			list($this->dim_t['w'],$this->dim_t['h']) = getimagesize($thumbpath);
			
			return true;
		}
		
		return false;
	}

	// -------------------------------------------------------------------------
	function thumbnail_IM($crop,$quality,$crop_only=false)
	{
		// Path information
		$path = str_replace('//','/',$this->fullpath.$this->fn.$this->thumb.$this->ext);
		$e_path = escapeshellarg($path);
		$thumbpath = str_replace('//','/',$this->fullpath.$this->fn_t.$this->ext);
		$e_thumbpath = escapeshellarg($thumbpath);

		// Square Thumbnails
		if($this->dim_t['w'] == $this->dim_t['h']) {
			$tempw = ($this->dim['w'] > $this->dim['h']) ? 0 : $this->dim_t['w'];
			$temph = ($this->dim['w'] > $this->dim['h']) ? $this->dim_t['h'] : 0;
		} else {
			$tempw = $this->dim_t['w'];
			$temph = $this->dim_t['h'];
		}
		$dest = $this->calcDim($this->dim['w'],$this->dim['h'],$tempw,$temph);

		$dimensions = $dest['w'].'x'.$dest['h'];

		// ImageMagick
		$cropoffsetx = ($this->dim['w'] > $this->dim['h']) ? ($dest['w'] - $dest['h'])/2 : 0;
		$cropoffsety = ($this->dim['w'] > $this->dim['h']) ? 0 : ($dest['h'] - $dest['w'])/2;

		$cropstr = ($this->dim_t['w'] == $this->dim_t['h']) ? ' -crop '.$this->dim_t['w'].'x'.$this->dim_t['h'].'+'.$cropoffsetx.'+'.$cropoffsety.' ' : '';
		$execstr = $this->impath."convert -size $dimensions $e_path -quality $quality -resize $dimensions! ".$cropstr." +profile '*' $e_thumbpath";
		exec($execstr,$retval);
		$retval = implode('\n',$retval);
		
		// return chmod($thumbpath,0777);
		// rewrite w/ better error handling
		
		return;
	}

	// ---- Resize Functions ---------------------------------------------------
	// -------------------------------------------------------------------------
	function resize_GD($quality) // works
	{
		//  Get GD Version
		$gd = $this->getGDVersion();
		// Path information
		$path = $this->fullpath.$this->fn.$this->ext;
		$path_r = ($this->keeporiginal) ? $this->fullpath.$this->fn.'_r'.$this->ext : $path;
		$alpha = false;
		
		if ($this->ext == '.jpg')
			$source_id = imageCreateFromJPEG($path);
		elseif ($this->ext == '.gif')
			$source_id = imageCreateFromGIF($path);
		elseif ($this->ext == '.png') {
			$source_id = imageCreateFromPNG($path);
			if (png_has_transparency($path) and function_exists('imagealphablending')) {
				$alpha = true;
			}
		} else return;
		
		if ($this->dim_r['w'] < $this->dim['w']) {
		
			// Create a new image object (not neccessarily true colour)
			$target_id = $gd['create']($this->dim_r['w'], $this->dim_r['h']);
	
			// Resize the original picture and copy it into the just created image object.
			$target_pic = $gd['copy']($target_id,$source_id,
									  0,0,0,0,
									  $this->dim_r['w'],$this->dim_r['h'],
									  $this->dim['w'],$this->dim['h']);
		} else {
			$target_id = $source_id;
		}	
		
		if ($this->ext == '.jpg')
			imagejpeg($target_id,$path_r,$quality);
		elseif ($this->ext == '.gif')
			imagegif($target_id,$path_r);
		elseif ($this->ext == '.png') {
			if ($alpha) {
				imageAlphaBlending($target_id, true);
				imageSaveAlpha($target_id, true);
			}
			imagePng($target_id,$path_r,0);
		}
		
		imagedestroy($target_id);
		if ($target_id != $source_id) imagedestroy($source_id);
		
		return is_file($path_r);
	}

	// -------------------------------------------------------------------------
	// Private Function DO NOT CALL DIRECTLY!
	function resize_IM($quality) // works
	{
		// fix path
		$e_path = escapeshellarg($this->fullpath.$this->fn.$this->ext);
		$e_path_r = ($this->keeporiginal)
					? escapeshellarg($this->fullpath.$this->fn_r.$this->ext)
					: escapeshellarg($this->fullpath.$this->fn.$this->ext);

		$path = ($this->keeporiginal)
				? $this->fullpath.$this->fn_r.$this->ext
				: $this->fullpath.$this->fn.$this->ext;

		$dimensions = $this->dim_r['w'].'x'.$this->dim_r['h'];

		// ImageMagick
		$execstr = $this->impath."convert -size $dimensions $e_path -quality $quality -resize $dimensions $e_path_r";
		exec($execstr,$retval);
		$retval = implode('\n',$retval);
		
		// return chmod($path_r,0777);
		// rewrite w/ better error handling
		
		return;
	}
}
// END class ImageManipulation
?>

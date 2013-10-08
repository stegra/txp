<?php

//==============================================================================
// change: get article file
// change: allow thing
// change: fetch only form type file
// change: fetch file using type and extension

	function file_download($atts, $thing = NULL)
	{
		global $thisfile, $thisarticle;

		extract(lAtts(array(
			'filename' => '',
			'form'     => 'files',
			'id'       => '',
			'type'     => '',
			'ext'      => ''
		), $atts));

		$from_form = false;
		$where = array();
		$out = '';

		if ($id)
		{
			$where['id'] = 'ID = '.intval($id);
		}

		elseif ($filename)
		{
			$where['name'] = "Name = '".doSlash($filename)."'";
		}

		else
		{
			if (empty($thisfile) and isset($thisarticle['file_id'])) {
			
				$where['id'] = 'ID = '.$thisarticle['file_id'];
				
				if ($type) 
					$where['type'] = "type IN ('".join("','", doSlash(do_list($type)))."')";
		
				if ($ext) 
					$where['ext'] = "ext IN ('".join("','", doSlash(do_list($ext)))."')";
				
			} else {
			
				assert_file();

				$from_form = true;
			}
		}
		
		if ($thisarticle['table'] == 'txp_file') {
			
			$where['id'] = 'ID = '.$thisarticle['thisid'];
		}
		
		if ($where) 
			$thisfile = fileDownloadFetchInfo(doAnd($where));
		
		if ($thing) {
			
			$out = parse(EvalElse($thing, $thisfile));
		
		} elseif ($thisfile) {
			$out = parse_form($form,'file');
		}
		
		// cleanup: this wasn't called from a form,
		// so we don't want this value remaining
		if (!$from_form) $thisfile = '';
		
		return $out;
	}

//--------------------------------------------------------------------------
// change: file counter
// change: select only files with the same author as the current article authorid
//		   or with a specific given authors

	function file_download_list($atts, $thing = NULL)
	{
		global $thisarticle, $thisfile;

		extract(lAtts(array(
			'break'    => br,
			'category' => '',
			'class'    => __FUNCTION__,
			'form'     => 'files',
			'id'       => '',
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'offset'   => 0,
			'sort'     => 'filename asc',
			'wraptag'  => '',
			'status'   => '4',
			'author'   => '',
			'type'     => '',
			'ext'      => ''
		), $atts));

		if (!is_numeric($status))
			$status = getStatusNum($status);
			
		$article_author = (isset($thisarticle['authorid'])) ? $thisarticle['authorid'] : '';
		$file_author    = ($author == '1') ? $article_author : $author;

		$where = array('1=1');
		
		if ($category)    $where[] = "category IN ('".join("','", doSlash(do_list($category)))."')";
		if ($id) 		  $where[] = "id IN ('".join("','", doSlash(do_list($id)))."')";
		if ($status) 	  $where[] = "status = '".doSlash($status)."'";
		if ($file_author) $where[] = "author IN ('".join("','", doSlash(do_list($file_author)))."')";
		if ($type)		  $where[] = "type IN ('".join("','", doSlash(do_list($type)))."')";
		if ($ext)		  $where[] = "ext IN ('".join("','", doSlash(do_list($ext)))."')";

		$qparts = array(
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : '',
		);

		$rs = safe_rows_start('*', 'txp_file', join(' and ', $where).' '.join(' ', $qparts));

		if ($rs)
		{
			$out = array();
			$file_count = 0;
			
			while ($a = nextRow($rs))
			{
				$thisfile = file_download_format_info($a);
				$thisfile['num'] = $file_num++;
				
				$out[] = ($thing) ? parse($thing) : parse_form($form);

				$thisfile = '';
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

//--------------------------------------------------------------------------
// change: get article file

	function file_download_link($atts, $thing = NULL)
	{
		global $thisfile, $permlink_mode, $thisarticle;

		extract(lAtts(array(
			'filename' => '',
			'id'       => '',
		), $atts));
		
		$from_form = false;
		$where = array();
		
		if ($id)
		{
			$where[] = 'id = '.intval($id);
		}

		elseif ($filename)
		{
			$where[] = "filename = '".doSlash($filename)."'";
		}

		else
		{
			if (empty($thisfile) and isset($thisarticle['file_id'])) {
			
				$where[] = 'ID = '.$thisarticle['file_id'];
				
				if ($type) 
					$where[] = "Type IN ('".join("','", doSlash(do_list($type)))."')";
		
				if ($ext) 
					$where[] = "ext IN ('".join("','", doSlash(do_list($ext)))."')";
				
			} else {
			
				assert_file();

				$from_form = true;
			}
		}
		
		if ($where) 
			$thisfile = fileDownloadFetchInfo(doAnd($where));
		
		
		if ($thisfile)
		{
			$url = filedownloadurl($thisfile['id'], $thisfile['filename']);
			
			$out = ($thing) ? href(parse($thing), $url) : $url;

			// cleanup: this wasn't called from a form,
			// so we don't want this value remaining
			if (!$from_form)
			{
				$thisfile = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------

	function file_download_format_info($file)
	{
		if (($unix_ts = @strtotime($file['created'])) > 0)
			$file['created'] = $unix_ts;
		if (($unix_ts = @strtotime($file['modified'])) > 0)
			$file['modified'] = $unix_ts;
		
		$file['id'] = $file['ID'];
		$file['filename'] = $file['Name'].$file['ext'];
		
		unset($file['ID']);
		
		return $file;
	}

//--------------------------------------------------------------------------

	function file_download_size($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'decimals' => 2,
			'format'   => '',
		), $atts));

		if (is_numeric($decimals) and $decimals >= 0)
		{
			$decimals = intval($decimals);
		}

		else
		{
			$decimals = 2;
		}

		if (@$thisfile['size'])
		{
			$size = $thisfile['size'];

			if (!in_array($format, array('B','KB','MB','GB','PB')))
			{
				$divs = 0;

				while ($size >= 1024)
				{
					$size /= 1024;
					$divs++;
				}

				switch ($divs)
				{
					case 1:
						$format = 'KB';
					break;

					case 2:
						$format = 'MB';
					break;

					case 3:
						$format = 'GB';
					break;

					case 4:
						$format = 'PB';
					break;

					case 0:
					default:
						$format = 'B';
					break;
				}
			}

			$size = $thisfile['size'];

			switch ($format)
			{
				case 'KB':
					$size /= 1024;
				break;

				case 'MB':
					$size /= (1024*1024);
				break;

				case 'GB':
					$size /= (1024*1024*1024);
				break;

				case 'PB':
					$size /= (1024*1024*1024*1024);
				break;

				case 'B':
				default:
					// do nothing
				break;
			}

			return number_format($size, $decimals).$format;
		}

		else
		{
			return '';
		}
	}

//--------------------------------------------------------------------------

	function file_download_created($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['created']) {
			return fileDownloadFormatTime(array(
				'ftime'  => $thisfile['created'],
				'format' => $format
			));
		}
	}

//--------------------------------------------------------------------------

	function file_download_modified($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['modified']) {
			return fileDownloadFormatTime(array(
				'ftime'  => $thisfile['modified'],
				'format' => $format
			));
		}
	}

//--------------------------------------------------------------------------

	function file_download_id()
	{
		global $thisfile;
		assert_file();
		
		return $thisfile['id'];
	}

//--------------------------------------------------------------------------

	function file_download_src($atts)
	{
		global $thisfile, $file_base_path, $path_to_site, $site_dir;
		assert_file();
		
		extract(lAtts(array(
			'ext' => ''
		),$atts));
		
		$files = trim(str_replace($path_to_site,'',$file_base_path),'/');
		$id_path  = get_file_id_path($thisfile['FileID']);
		$filename = ($ext) 
			? get_file_name($thisfile['FileName']).'.'.$ext
			: $thisfile['FileName'];
		
		$src = $files.'/'.$id_path.'/'.$filename;
    	return ($site_dir) ? '/~'.$site_dir.'/'.$src : '/'.$src;
    }

//--------------------------------------------------------------------------

	function file_download_title($atts)
	{
		global $thisfile;
		assert_file();
		
		return $thisfile['Title'];
	}
	
//--------------------------------------------------------------------------

	function file_download_name($atts)
	{
		global $thisfile;
		assert_file();
		
		extract(lAtts(array(
			'ext' => '1'
		),$atts));
		
		$filename = get_file_name($thisfile['FileName']);
		
		if (isset($atts['ext'])) {
		
			if ($ext == '0') {
				
				return $filename;		
			}
			
			if ($ext == '1') {
				
				return $thisfile['FileName'];
			}
			
			if (preg_match('/^[a-z0-9]+$/',$ext)) {
			
				return $filename.'.'.$ext;
			}
		}
		
		return $thisfile['FileName'];
	}

// -------------------------------------------------------------------------
// new

	function file_download_num($atts)
	{
		global $thisfile;
		assert_file();
		
		if (isset($thisfile['num'])) return $thisfile['num'];
	
		return '1';
	}

// -------------------------------------------------------------------------
// new

	function file_download_ext($atts)
	{
		global $thisfile;
		assert_file();
		return ltrim($thisfile['ext'],'.');
	} 

// -------------------------------------------------------------------------
// new

	function file_download_type($atts)
	{
		global $thisfile;
		assert_file();
		return $thisfile['type'];
	} 

// -------------------------------------------------------------------------

	function if_file_download_ext($atts, $thing = NULL)
	{
		global $thisfile, $file_base_path;
		assert_file();
		
		extract(lAtts(array(
			'value' => ''
		),$atts));
		
		$test = false;
		$list = do_list($value);
		
		if (in_array($thisfile['ext'],$list)) {
			
			$test = true;
		
		} else {
			
			$path = get_file_id_path($thisfile['FileID']);
			
			$filename = get_file_name($thisfile['FileName']);
			$filename = $file_base_path.'/'.$path.'/'.$filename;
			
			foreach($list as $ext) {
				$test = is_file($filename.'.'.$ext) or $test;
			}
		}
			
		return parse(EvalElse($thing, $test));
	} 

// -------------------------------------------------------------------------

	function if_file_download_type($atts,$thing = NULL)
	{
		global $thisfile;
		assert_file();
		
		extract(lAtts(array(
			'value' => ''
		),$atts));
		
		return parse(EvalElse($thing, $thisfile['type'] == $value));
	}

// -------------------------------------------------------------------------

	function if_file_download_audio($atts,$thing = NULL)
	{
		global $thisfile;
		assert_file();
		
		return parse(EvalElse($thing, $thisfile['type'] == 'audio'));
	}
	
// -------------------------------------------------------------------------

	function if_file_download_video($atts,$thing = NULL)
	{
		global $thisfile;
		assert_file();
		
		return parse(EvalElse($thing, $thisfile['type'] == 'video'));
	}
	
//--------------------------------------------------------------------------

	function file_download_category($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'class'   => '',
			'title'   => 0,
			'wraptag' => '',
		), $atts));

		if ($thisfile['category'])
		{
			$category = ($title) ?
				fetch_category_title($thisfile['category'], 'file') :
				$thisfile['category'];

			return ($wraptag) ? doTag($category, $wraptag, $class) : $category;
		}
	}

//--------------------------------------------------------------------------

	function file_download_downloads()
	{
		global $thisfile;
		assert_file();
		return $thisfile['downloads'];
	}

//--------------------------------------------------------------------------

	function file_download_description($atts)
	{
		global $thisfile;
		assert_file();

		extract(lAtts(array(
			'class'   => '',
			'escape'  => 'html',
			'wraptag' => '',
		), $atts));

		if ($thisfile['description'])
		{
			$description = ($escape == 'html') ?
				htmlspecialchars($thisfile['description']) : $thisfile['description'];

			return ($wraptag) ? doTag($description, $wraptag, $class) : $description;
		}
	}

//--------------------------------------------------------------------------

	function file_download_poster($atts)
	{
		global $thisfile,$img_dir;
		assert_file();
		
		$name = preg_replace('/\.[^\.]+$/','',$thisfile['filename']);
		
		if (is_file(IMPATH.$name.'.jpg'))
				return DS.$img_dir.DS.$name.'.jpg';
		
		return '';
	}
	
// =============================================================================

	function fileDownloadFetchInfo($where)
	{
		if (!trim($where)) return false;
		
		$category = "(SELECT tc.Title 
			FROM txp_content_category AS tcc JOIN txp_category AS tc
			ON tcc.name = tc.Name 
			WHERE tcc.article_id = f.ID LIMIT 1) AS category";
		
		$rs = safe_row("*,$category", 'txp_file AS f', $where,0,0);

		if ($rs)
		{	
		
			return file_download_format_info($rs);
		}

		return false;
	}

//------------------------------------------------------------------------------
// All the time related file_download tags in one
// One Rule to rule them all... now using safe formats

	function fileDownloadFormatTime($params)
	{
		global $prefs;

		extract($params);

		if (!empty($ftime))
		{
			return !empty($format) ?
				safe_strftime($format, $ftime) : safe_strftime($prefs['archive_dateformat'], $ftime);
		}
		return '';
	}

?>
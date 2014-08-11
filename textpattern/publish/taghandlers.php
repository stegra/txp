<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/publish/taghandlers.php $
$LastChangedRevision: 3256 $

*/

	include_once txpath.'/lib/txplib_smarty.php';
	include_once txpath.'/publish/taghandlers/taghandlers_article.php';
	include_once txpath.'/publish/taghandlers/taghandlers_image.php';
	include_once txpath.'/publish/taghandlers/taghandlers_file.php';
	include_once txpath.'/publish/taghandlers/taghandlers_comment.php';
	include_once txpath.'/publish/taghandlers/taghandlers_custom.php';
	include_once txpath.'/publish/taghandlers/taghandlers_utility.php';
	include_once txpath.'/include/lib/txp_lib_custom_v4.php';
	
	$GLOBALS['tags'] = array(
		'link' 			=> 'tpt_link',
		'name' 			=> 'name_tag',
		'parent_name' 	=> 'parent_article_name',
		'var' 			=> 'variable',
		'if_var'		=> 'if_variable',
		'if'			=> 'if_tag',
		'path'			=> 'path_tag',
		'class'			=> 'class_tag',
		'line'			=> 'line_tag',
		'message'		=> 'comment_message',
		'br'			=> 'break_tag',
		'truncate'		=> 'truncate_tag',
		'pretext'		=> 'pretext_tag'
	);
		
// =============================================================================

	function page_title($atts)
	{
		global $parentid, $thisarticle, $id, $q, $c, $s, $pg, $sitename;

		extract(lAtts(array(
			'separator' => ' : '
		), $atts));

		$out = htmlspecialchars($sitename.$separator);

		if ($parentid) {
			$parent_id = (int) $parent_id;
			$out .= gTxt('comments_on').' '.escape_title(safe_field('Title', 'textpattern', "ID = $parentid"));
		} elseif ($thisarticle['title']) {
			$out .= escape_title($thisarticle['title']);
		} elseif ($q) {
			$out .= gTxt('search_results').htmlspecialchars($separator.$q);
		} elseif ($c) {
			$out .= htmlspecialchars(fetch_category_title($c));
		} elseif ($s and $s != 'default') {
			$out .= htmlspecialchars(fetch_section_title($s));
		} elseif ($pg) {
			$out .= gTxt('page').' '.$pg;
		} else {
			$out = htmlspecialchars($sitename);
		}

		return $out;
	}

// -------------------------------------------------------------

	function css($atts)
	{
		global $css,$pretext,$prefs;
		
		extract(lAtts(array(
			'format' => 'url',
			'media'  => 'screen',
			'n'      => $css,
			'p'      => 0,
			'rel'    => 'stylesheet',
			'title'  => ''
		), $atts));
		
		if ($p and isset($pretext['req_path'])) {
			
			$path = explode('/',$pretext['req_path']);
			
			if (isset($path[$p-1])) {
				
				$path = doSlash($path[$p-1]);
				
				if (safe_count("txp_css","Name = '$path'")) {
				
					$n = $path;
				
				} elseif ($pretext['page'] == 'default') {
				
					$n = 'default';
				}
			}
		
		} elseif ($n) {
			
			$p = explode('/',$n);
			$n = array_pop($p);
			
			$where = array(
				"Name= '".doSlash($n)."'",
				"NamePath = '".doSlash(implode('/',$p))."'",
				"Trash = 0"
			);
			
			if (safe_count("txp_css",implode(' AND ',$where))) {
				
				array_push($p,$n);
				$n = implode('/',$p);
				
			} else {
				
				$n = '';	
			}
			
		} else {
			
			$n = 'default';
		}
		
		$url = ($n) ? hu.'css.php?n='.$n : '';
		
		if ($format == 'link') {
			
			if ($url) {
				
				return '<link rel="'.$rel.'" type="text/css"'.
					($media ? ' media="'.$media.'"' : '').
					($title ? ' title="'.$title.'"' : '').
					' href="'.$url.'" />';
			} 
			
			return '';
		}

		return $url;
	}

// -------------------------------------------------------------

	function output_form($atts, $thing = NULL)
	{
		global $yield;

		extract(lAtts(array(
			'form' => '',
		), $atts));
		
		if (!$form)
		{
			trigger_error(gTxt('form_not_specified'));
		}
		else
		{
			$yield[] = $thing !== NULL ? parse($thing) : NULL;
			$out = parse_form($form);
			array_pop($yield);
			return $out;
		}
	}

// -------------------------------------------------------------

	function yield()
	{
		global $yield;

		$inner = end($yield);
		
		return isset($inner) ? $inner : '';
	}

// -------------------------------------------------------------

	function feed_link($atts, $thing = NULL)
	{
		global $s, $c;

		extract(lAtts(array(
			'category' => $c,
			'flavor'   => 'rss',
			'format'   => 'a',
			'label'    => '',
			'limit'    => '',
			'section'  => ( $s == 'default' ? '' : $s),
			'title'    => gTxt('rss_feed_title'),
			'wraptag'  => '',
		), $atts));

		$url = pagelinkurl(array(
			$flavor    => '1',
			'section'  => $section,
			'category' => $category,
			'limit'    => $limit
		));

		if ($flavor == 'atom')
		{
			$title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
		}

		$title = htmlspecialchars($title);

		if ($format == 'link')
		{
			$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$txt = ($thing === NULL ? $label : parse($thing));
		$out = '<a href="'.$url.'" title="'.$title.'">'.$txt.'</a>';

		return ($wraptag) ? tag($out, $wraptag) : $out;
	}

// -------------------------------------------------------------

	function link_feed_link($atts)
	{
		global $c;

		extract(lAtts(array(
			'category' => $c,
			'flavor'   => 'rss',
			'format'   => 'a',
			'label'    => '',
			'title'    => gTxt('rss_feed_title'),
			'wraptag'  => '',
		), $atts));

		$url = pagelinkurl(array(
			$flavor => '1',
			'area'  =>'link',
			'c'     => $category
		));

		if ($flavor == 'atom')
		{
			$title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
		}

		$title = htmlspecialchars($title);

		if ($format == 'link')
		{
			$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$out = '<a href="'.$url.'" title="'.$title.'">'.$label.'</a>';

		return ($wraptag) ? tag($out, $wraptag) : $out;
	}

// -------------------------------------------------------------
// target attribute no longer needed

	function linklist($atts, $thing = NULL)
	{
		global $thislink;
		
		extract(lAtts(array(
			'break'    => '',
			'category' => '',
			'class'    => __FUNCTION__,
			'form'     => 'plainlinks',
			'label'    => '',
			'labeltag' => '',
			'limit'    => 0,
			'offset'   => 0,
			'sort'     => 'Title ASC',
			'wraptag'  => '',
		), $atts));

		$qparts = array(
			($category) ? "category IN ('".join("','", doSlash(do_list($category)))."')" : '1=1',
			" AND ParentID != 0 AND Trash = 0 AND Name != 'TRASH'",
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, ID AS id, Title AS linkname, Body_html AS description, unix_timestamp(Posted) AS uDate', 'txp_link', join(' ', $qparts));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				extract($a);
				
				$thislink = array(
					'id'          => $id,
					'linkname'    => $linkname,
					'url'         => $url,
					'description' => $description,
					'date'        => $uDate,
					'category'    => $category,
				);
				
				$out[] = ($thing) ? parse($thing) : parse_form($form);

				$thislink = '';
			}
			
			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return false;
	}

// -------------------------------------------------------------

	function tpt_link($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'rel' => '',
		), $atts));

		return tag(
			htmlspecialchars($thislink['linkname']), 'a',
			($rel ? ' rel="'.$rel.'"' : '').
			' href="'.doSpecial($thislink['url']).'"'
		);
	}

// -------------------------------------------------------------

	function linkdesctitle($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'rel' => '',
		), $atts));
		
		$description = ($thislink['description']) ?
			' title="'.htmlspecialchars($thislink['description']).'"' :
			'';

		return tag(
			htmlspecialchars($thislink['linkname']), 'a',
			($rel ? ' rel="'.$rel.'"' : '').
			' href="'.doSpecial($thislink['url']).'"'.$description
		);
	}

// -------------------------------------------------------------

	function link_name($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'escape' => 'html',
		), $atts));

		return ($escape == 'html') ?
			htmlspecialchars($thislink['linkname']) :
			$thislink['linkname'];
	}

// -------------------------------------------------------------

	function link_url()
	{
		global $thislink, $thisarticle;
		
		if (!empty($thislink)) {
			
			return doSpecial($thislink['url']);
		}
		
		if (isset($thisarticle['url'])) {
			
			return doSpecial($thisarticle['url']);
		} 
	}

// -------------------------------------------------------------

	function link_description($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'class'    => '',
			'escape'   => 'html',
			'label'    => '',
			'labeltag' => '',
			'wraptag'  => '',
		), $atts));

		if ($thislink['description'])
		{
			$description = ($escape == 'html') ?
				htmlspecialchars($thislink['description']) :
				$thislink['description'];

			return doLabel($label, $labeltag).doTag($description, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function link_date($atts)
	{
		global $thislink, $dateformat;
		assert_link();

		extract(lAtts(array(
			'format' => $dateformat,
			'gmt'    => '',
			'lang'   => '',
		), $atts));

		return safe_strftime($format, $thislink['date'], $gmt, $lang);
	}

// -------------------------------------------------------------

	function link_category($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'class'    => '',
			'label'    => '',
			'labeltag' => '',
			'title'    => 0,
			'wraptag'  => '',
		), $atts));

		if ($thislink['category'])
		{
			$category = ($title) ?
				fetch_category_title($thislink['category'], 'link') :
				$thislink['category'];

			return doLabel($label, $labeltag).doTag($category, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function link_id()
	{
		global $thislink;
		assert_link();
		return $thislink['id'];
	}

// -------------------------------------------------------------
	function email($atts, $thing = NULL)
	{
		extract(lAtts(array(
			'email'    => '',
			'linktext' => gTxt('contact'),
			'title'    => '',
		),$atts));

		if ($email) {
			if ($thing !== NULL) $linktext = parse($thing);
			// obfuscate link text?
			if (is_valid_email($linktext)) $linktext = eE($linktext);

			return '<a href="'.eE('mailto:'.$email).'"'.
				($title ? ' title="'.$title.'"' : '').">$linktext</a>";
		}
		return '';
	}

// -------------------------------------------------------------
	function password_protect($atts)
	{
		ob_start();

		extract(lAtts(array(
			'login' => '',
			'pass'  => '',
		),$atts));

		$au = serverSet('PHP_AUTH_USER');
		$ap = serverSet('PHP_AUTH_PW');
		//For php as (f)cgi, two rules in htaccess often allow this workaround
		$ru = serverSet('REDIRECT_REMOTE_USER');
		if ($ru && !$au && !$ap && substr( $ru,0,5) == 'Basic' ) {
			list ( $au, $ap ) = explode( ':', base64_decode( substr( $ru,6)));
		}
		if ($login && $pass) {
			if (!$au || !$ap || $au!= $login || $ap!= $pass) {
				header('WWW-Authenticate: Basic realm="Private"');
				txp_die(gTxt('auth_required'), '401');
			}
		}
	}

// -------------------------------------------------------------

	function recent_articles($atts)
	{
		global $prefs;
		extract(lAtts(array(
			'break'    => br,
			'category' => '',
			'class'    => __FUNCTION__,
			'label'    => gTxt('recent_articles'),
			'labeltag' => '',
			'limit'    => 10,
			'section'  => '',
			'sort'     => 'Posted desc',
			'sortby'   => '', // deprecated
			'sortdir'  => '', // deprecated
			'wraptag'  => '',
			'no_widow' => @$prefs['title_no_widow'],
		), $atts));

		// for backwards compatibility
		// sortby and sortdir are deprecated
		if ($sortby)
		{
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortby')), E_USER_NOTICE);

			if (!$sortdir)
			{
				$sortdir = 'desc';
			}
			else
			{
				trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortdir')), E_USER_NOTICE);
			}

			$sort = "$sortby $sortdir";
		}

		elseif ($sortdir)
		{
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'sortdir')), E_USER_NOTICE);
			$sort = "Posted $sortdir";
		}

		// $category   = join("','", doSlash(do_list($category)));
		// $categories = ($category) ? "and (Category1 IN ('".$category."') or Category2 IN ('".$category."'))" : '';
		$section = ($section) ? " and Section IN ('".join("','", doSlash(do_list($section)))."')" : '';
		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.') ';

		$rs = safe_rows_start('*, id as thisid, unix_timestamp(Posted) as posted', 'textpattern',
			"Status = 4 $section $categories and Posted <= now()$expired order by ".doSlash($sort).' limit 0,'.intval($limit));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$a['Title'] = ($no_widow) ? noWidow(escape_title($a['Title'])) : escape_title($a['Title']);
				$out[] = href($a['Title'], permlinkurl($a));
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function recent_comments($atts, $thing = NULL)
	{
		global $prefs;
		global $thisarticle, $thiscomment;
		extract(lAtts(array(
			'break'    => br,
			'class'    => __FUNCTION__,
			'form'     => '',
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'offset'   => 0,
			'sort'     => 'posted desc',
			'wraptag'  => ''
		), $atts));

		$sort = preg_replace('/\bposted\b/', 'd.posted', $sort);
		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= t.Expires or t.Expires = '.NULLDATETIME.') ';

		$rs = startRows('select d.Name AS name, d.email, d.url AS web, d.Body_html AS message, d.ID AS discussid, unix_timestamp(d.Posted) AS time, '.
				't.ID as thisid, unix_timestamp(t.Posted) as posted, t.Title as title, t.Section as section, t.url_title '.
				'from '. safe_pfx('txp_discuss') .' as d inner join '. safe_pfx('textpattern') .' as t on d.article_id = t.ID '.
				'where t.Status >= 4'.$expired.' and d.Status = '.VISIBLE.' order by '.doSlash($sort).' limit '.intval($offset).','.intval($limit));
		if ($rs)
		{
			$out = array();
			$old_article = $thisarticle;
			while ($c = nextRow($rs))
			{
				if (empty($form) && empty($thing))
				{
					$out[] = href(
						htmlspecialchars($c['name']).' ('.escape_title($c['title']).')',
						permlinkurl($c).'#c'.$c['discussid']
					);
				}
				else
				{
					$thiscomment['name'] = $c['name'];
					$thiscomment['email'] = $c['email'];
					$thiscomment['web'] = $c['web'];
					$thiscomment['message'] = $c['message'];
					$thiscomment['discussid'] = $c['discussid'];
					$thiscomment['time'] = $c['time'];

					// allow permlink guesstimation in permlinkurl(), elsewhere
					$thisarticle['thisid'] = $c['thisid'];
					$thisarticle['posted'] = $c['posted'];
					$thisarticle['title'] = $c['title'];
					$thisarticle['section'] = $c['section'];
					$thisarticle['url_title'] = $c['url_title'];

					$out[] = ($thing) ? parse($thing) : parse_form($form);
				}
			}

			if ($out)
			{
				unset($GLOBALS['thiscomment']);
				$thisarticle = $old_article;
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

/* ------------------------------------------------------------- */
/* function related_articles($atts, $thing = NULL)
	{
		global $thisarticle, $prefs;

		assert_article();

		extract(lAtts(array(
			'break'    => br,
			'class'    => __FUNCTION__,
			'form'	   => '',
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'match'    => 'Category1,Category2',
			'no_widow' => @$prefs['title_no_widow'],
			'section'  => '',
			'sort'     => 'Posted desc',
			'wraptag'  => '',
		), $atts));

		if (empty($thisarticle['category1']) and empty($thisarticle['category2']))
		{
			return;
		}

		$match = do_list($match);

		if (!in_array('Category1', $match) and !in_array('Category2', $match))
		{
			return;
		}

		$id = $thisarticle['thisid'];

		$cats = array();

		if ($thisarticle['category1'])
		{
			$cats[] = doSlash($thisarticle['category1']);
		}

		if ($thisarticle['category2'])
		{
			$cats[] = doSlash($thisarticle['category2']);
		}

		$cats = join("','", $cats);

		$categories = array();

		if (in_array('Category1', $match))
		{
			$categories[] = "Category1 in('$cats')";
		}

		if (in_array('Category2', $match))
		{
			$categories[] = "Category2 in('$cats')";
		}

		$categories = 'and ('.join(' or ', $categories).')';

		$section = ($section) ? " and Section IN ('".join("','", doSlash(do_list($section)))."')" : '';

		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.') ';
		$rs = safe_rows_start('*, unix_timestamp(Posted) as posted, unix_timestamp(LastMod) as uLastMod, unix_timestamp(Expires) as uExpires', 'textpattern',
			'ID != '.intval($id)." and Status = 4 $expired  and Posted <= now() $categories $section order by ".doSlash($sort).' limit 0,'.intval($limit));

		if ($rs)
		{
			$out = array();
			$old_article = $thisarticle;

			while ($a = nextRow($rs))
			{
				$a['Title'] = ($no_widow) ? noWidow(escape_title($a['Title'])) : escape_title($a['Title']);
				$a['uPosted'] = $a['posted']; // populateArticleData() and permlinkurl() assume quite a bunch of posting dates...

				if (empty($form) && empty($thing))
				{
					$out[] = href($a['Title'], permlinkurl($a));
				}
				else
				{
					populateArticleData($a);
					$out[] = ($thing) ?  parse($thing) : parse_form($form);
				}
			}
			$thisarticle = $old_article;

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}
*/
// -------------------------------------------------------------

	function popup($atts)
	{
		global $s, $c;

		extract(lAtts(array(
			'label'        => gTxt('browse'),
			'wraptag'      => '',
			'section'      => '',
			'this_section' => 0,
			'type'         => 'c',
		), $atts));

		if ($type == 's')
		{
			$rs = safe_rows_start('name, title', 'txp_section', "name != 'default' order by name");
		}

		else
		{
			$rs = safe_rows_start('name, title', 'txp_category', "type = 'article' and name != 'root' order by name");
		}

		if ($rs)
		{
			$out = array();

			$current = ($type == 's') ? $s : $c;

			$sel = '';
			$selected = false;

			while ($a = nextRow($rs))
			{
				extract($a);

				if ($name == $current)
				{
					$sel = ' selected="selected"';
					$selected = true;
				}

				$out[] = '<option value="'.$name.'"'.$sel.'>'.htmlspecialchars($title).'</option>';

				$sel = '';
			}

			if ($out)
			{
				$section = ($this_section) ? ( $s == 'default' ? '' : $s) : $section;

				$out = n.'<select name="'.$type.'" onchange="submit(this.form);">'.
					n.t.'<option value=""'.($selected ? '' : ' selected="selected"').'>&nbsp;</option>'.
					n.t.join(n.t, $out).
					n.'</select>';

				if ($label)
				{
					$out = $label.br.$out;
				}

				if ($wraptag)
				{
					$out = tag($out, $wraptag);
				}

				return '<form method="get" action="'.hu.'">'.
					'<div>'.
					( ($type != 's' and $section and $s) ? n.hInput('s', $section) : '').
					n.$out.
					n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript>'.
					n.'</div>'.
					n.'</form>';
			}
		}
	}

// -------------------------------------------------------------
// output href list of site categories

	function category_list($atts, $thing = NULL)
	{
		global $s, $c, $thiscategory, $content_type_stack;

		extract(lAtts(array(
			'active_class' => '',
			'break'        => br,
			'categories'   => '',
			'class'        => __FUNCTION__,
			'exclude'      => '',
			'form'         => '',
			'label'        => '',
			'labeltag'     => '',
			'parent'       => '',
			'section'      => '',
			'children'     => '1',
			'sort'         => '',
			'this_section' => 0,
			'type'         => 'article',
			'wraptag'      => '',
		), $atts));

		$sort = doSlash($sort);
		$rs = null;

		if ($categories)
		{
			$categories = do_list($categories);
			$categories = join("','", doSlash($categories));

			$rs = safe_rows_start('name, title', 'txp_category',
				"type = '".doSlash($type)."' and name in ('$categories') order by ".($sort ? $sort : "field(name, '$categories')"));
		}

		else
		{
			/* if ($children)
			{
				$shallow = '';
			} else {
				// descend only one level from either 'parent' or 'root', plus parent category
				$shallow = ($parent) ? "and (parent = '".doSlash($parent)."' or name = '".doSlash($parent)."')" : "and parent = 'root'" ;
			} */

			if ($exclude)
			{
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));

				$exclude = "and name not in('$exclude')";
			}

			if ($parent)
			{
				$parent_id = fetch("ID","txp_category","Name",doSlash($parent));
				
				if ($parent_id)
				{
					$rs = safe_rows_start('name, title', 'txp_category',
						"ParentID = $parent_id AND Trash = 0 AND ParentID != 0 AND Name != 'TRASH' 
						$exclude ORDER BY ".($sort ? $sort : 'name ASC'));
				}
			}

			else
			{
				$rs = safe_rows_start('name, title', 'txp_category',
					"Trash = 0 AND ParentID != 0 AND Name != 'TRASH' 
					$exclude ORDER BY ".($sort ? $sort : 'name ASC'));
			}
		}
		
		if ($rs)
		{
			$out = array();
			$count = 0;
			$last = numRows($rs);

			if (isset($thiscategory)) $old_category = $thiscategory;
			
			$content_type_stack->push('category');
			
			while ($a = nextRow($rs))
			{
				++$count;
				extract($a);

				if ($name)
				{
					$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

					if (empty($form) && empty($thing))
					{
						$out[] = tag(htmlspecialchars($title), 'a',
							( ($active_class and (0 == strcasecmp($c, $name))) ? ' class="'.$active_class.'"' : '' ).
							' href="'.pagelinkurl(array('s' => $section, 'c' => $name)).'"'
						);
					}
					else
					{	
						$break = '';
						$thiscategory = array('name' => $name, 'title' => $title, 'type' => $type);
						$thiscategory['is_first'] = ($count == 1);
						$thiscategory['is_last'] = ($count == $last);
						
						$out[] = ($thing) ? parse($thing) : parse_form($form);
					}
				}
			}
			
			$content_type_stack->pop();
			
			$thiscategory = (isset($old_category) ? $old_category : NULL);

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------
// output href list of site sections

	function section_list($atts, $thing = NULL)
	{
		global $sitename, $s, $thissection;

		extract(lAtts(array(
			'active_class'    => '',
			'break'           => br,
			'class'           => __FUNCTION__,
			'default_title'   => $sitename,
			'exclude'         => '',
			'form'            => '',
			'include_default' => '',
			'label'           => '',
			'labeltag'        => '',
			'sections'        => '',
			'sort'            => '',
			'wraptag'         => '',
		), $atts));
		
		$sort = doSlash($sort);
		
		$rs = array();
		if ($sections)
		{
			$sections = do_list($sections);

			$sections = join("','", doSlash($sections));

			$rs = safe_rows('name, title', 'txp_section', "name in ('$sections') order by ".($sort ? $sort : "field(name, '$sections')"));
		}

		else
		{
			
			if ($exclude)
			{
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));

				$exclude = "and name not in('$exclude')";
			}

			$rs = safe_rows('name, title', 'txp_section', "name != 'default' $exclude order by ".($sort ? $sort : 'name ASC'));
		}
		
		if ($include_default)
		{
			array_unshift($rs, array('name' => 'default', 'title' => $default_title));
		}
		
		if ($rs)
		{
			$out = array();
			$count = 0;
			$last = count($rs);

			if (isset($thissection)) $old_section = $thissection;
			foreach ($rs as $a)
			{
				++$count;
				extract($a);
				
				if (empty($form) && empty($thing))
				{
					$url = pagelinkurl(array('s' => $name));
					
					$out[] = tag(htmlspecialchars($title), 'a',
						( ($active_class and (0 == strcasecmp($s, $name))) ? ' class="'.$active_class.'"' : '' ).
						' href="'.$url.'"'
					);
				}
				else
				{
					$thissection = array('name' => $name, 'title' => ($name == 'default') ? $default_title : $title);
					$thissection['is_first'] = ($count == 1);
					$thissection['is_last'] = ($count == $last);
					$out[] = ($thing) ? parse($thing) : parse_form($form);
				}
			}
			$thissection = (isset($old_section) ? $old_section : NULL);

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------
	function search_input($atts) // input form for search queries
	{
		global $q, $permlink_mode;
		extract(lAtts(array(
			'form'    => 'search_input',
			'wraptag' => 'p',
			'size'    => '15',
			'html_id' => '',
			'label'   => gTxt('search'),
			'button'  => '',
			'section' => '',
		),$atts));

		if ($form) {
			if (getCount('txp_form',"Name = '$form' AND Trash = 0")) {
				return parse_form($form,'misc');
			}
		}

		$sub = (!empty($button)) ? '<input type="submit" value="'.$button.'" />' : '';
		$id =  (!empty($html_id)) ? ' id="'.$html_id.'"' : '';
		$out = fInput('text','q',$q,'','','',$size);
		$out = (!empty($label)) ? $label.br.$out.$sub : $out.$sub;
		$out = ($wraptag) ? tag($out,$wraptag) : $out;

		if (!$section) {
			return '<form method="get" action="'.hu.'"'.$id.'>'.
				n.$out.
				n.'</form>';
		}

		if ($permlink_mode != 'messy') {
			return '<form method="get" action="'.pagelinkurl(array('s' => $section)).'"'.$id.'>'.
				n.$out.
				n.'</form>';
		}

		return '<form method="get" action="'.hu.'"'.$id.'>'.
			n.hInput('s', $section).
			n.$out.
			n.'</form>';
	}

// -------------------------------------------------------------
	function search_term($atts)
	{
		global $q;
		if(empty($q)) return '';

		extract(lAtts(array(
			'escape' => 'html'
		),$atts));

		return ($escape == 'html' ? htmlspecialchars($q) : $q);
	}

// -------------------------------------------------------------
// link to next article, if it exists

	function link_to_next($atts, $thing = NULL)
	{
		global $id, $next_id, $next_title;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (intval($id) == 0)
		{
			global $thisarticle, $s;

			assert_article();

			extract(getNextPrev(
				@$thisarticle['thisid'],
				@strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']),
				@$s
			));
		}

		if ($next_id)
		{
			$url = permlinkurl_id($next_id);

			if ($thing)
			{
				$thing = parse($thing);
				$next_title = escape_title($next_title);

				return '<a rel="next" href="'.$url.'"'.
					($next_title != $thing ? ' title="'.$next_title.'"' : '').
					'>'.$thing.'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------
// link to next article, if it exists

	function link_to_prev($atts, $thing = NULL)
	{
		global $id, $prev_id, $prev_title;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (intval($id) == 0)
		{
			global $thisarticle, $s;

			assert_article();

			extract(getNextPrev(
				$thisarticle['thisid'],
				@strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']),
				@$s
			));
		}

		if ($prev_id)
		{
			$url = permlinkurl_id($prev_id);

			if ($thing)
			{
				$thing = parse($thing);
				$prev_title = escape_title($prev_title);

				return '<a rel="prev" href="'.$url.'"'.
					($prev_title != $thing ? ' title="'.$prev_title.'"' : '').
					'>'.$thing.'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function next_title()
	{
		return escape_title($GLOBALS['next_title']);
	}

// -------------------------------------------------------------

	function prev_title()
	{
		return escape_title($GLOBALS['prev_title']);
	}

// -------------------------------------------------------------

	function site_name()
	{
		return htmlspecialchars($GLOBALS['sitename']);
	}

// -------------------------------------------------------------

	function site_slogan()
	{
		return htmlspecialchars($GLOBALS['site_slogan']);
	}

// -------------------------------------------------------------

	function link_to_home($atts, $thing = NULL)
	{
		extract(lAtts(array(
			'class' => false,
		), $atts));

		if ($thing)
		{
			$class = ($class) ? ' class="'.$class.'"' : '';
			return '<a rel="home" href="'.hu.'"'.$class.'>'.parse($thing).'</a>';
		}

		return hu;
	}

// -------------------------------------------------------------
// change: allow $thing
// change: reverse direction when sorting is by Posted ASC

	function newer($atts, $thing = NULL)
	{
		global $thispage, $pretext, $permlink_mode;

		extract(lAtts(array(
			'showalways' => 0,
			'link'       => 1
		), $atts));
		
		$numPages = $thispage['numPages'];
		$pg = $thispage['pg'];
		$sortdir = $thispage['sortdir'];
		
		$left  = 1;
		$right = $numPages;
		
		if ($sortdir == 'asc') {
			$left  = 0;
			$right = $numPages - 1;
		}
			
		if ($numPages > 1 and $pg > $left and $pg <= $right)
		{
			// WHAT IS THIS FOR?
			// from page 2 go to 0 instead of 1?
			// $nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);
			$nextpg = $pg - 1;
			
			if ($sortdir == 'asc') 
				$nextpg = $pg + 1;
				
			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			/* $url = pagelinkurl(array(
				'month'  => @$pretext['month'],
				'pg'     => $nextpg,
				's'      => @$pretext['s'],
				'c'      => @$pretext['c'],
				'q'      => @$pretext['q'],
				'author' => $author
			)); */ 
			
			$url = preg_replace('/~.+/','',hu);
			$url = $url.$pretext['request_uri'];
			
			if ($pretext['qs']) {
				if (preg_match('/pg\=/',$pretext['qs'])) {
					$url = preg_replace('/pg\=\d+/','pg='.$nextpg,$url);
				} else {
					$url .= '&pg='.$nextpg;
				}
			} else {
				$url .= '?pg='.$nextpg;
			}
			
			if ($thing)
			{
				if ($link) return '<a class="newer" href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
				
				$thispage['pagedir'] = 'newer';
				$out = parse(EvalElse($thing,true));
				$thispage['pagedir'] = '';
				
				return $out;
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : parse(EvalElse($thing,false));
	}

// -------------------------------------------------------------
// change: allow $thing
// change: reverse direction when sorting is by Posted ASC

	function older($atts, $thing = NULL)
	{
		global $thispage, $pretext, $permlink_mode;
		
		extract(lAtts(array(
			'showalways' => 0,
			'link'       => 1
		), $atts));
		
		$numPages = $thispage['numPages'];
		$pg = $thispage['pg'];
		$sortdir = $thispage['sortdir'];
		
		$left  = 0;
		$right = $numPages - 1;
		
		if ($sortdir == 'asc') {
			$left  = 1;
			$right = $numPages;
		}
		
		if ($numPages > 1 and $pg > $left and $pg <= $right)
		{
			$nextpg = $pg + 1;
			
			if ($sortdir == 'asc')
				$nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);
			
			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			/* $url = pagelinkurl(array(
				'month'  => @$pretext['month'],
				'pg'     => $nextpg,
				's'      => @$pretext['s'],
				'c'      => @$pretext['c'],
				'q'      => @$pretext['q'],
				'author' => $author
			)); */
			
			$url = preg_replace('/~.+/','',hu);
			$url = $url.$pretext['request_uri'];
			
			if ($pretext['qs']) {
				if (preg_match('/pg\=/',$pretext['qs'])) {
					$url = preg_replace('/pg\=\d+/','pg='.$nextpg,$url);
				} else {
					$url .= '&pg='.$nextpg;
				}
			} else {
				$url .= '?pg='.$nextpg;
			}
			
			if ($thing)
			{
				if ($link) { 
					
					return '<a class="older" href="'.$url.'"'.
						(empty($title) ? '' : ' title="'.$title.'"').
						'>'.parse($thing).'</a>';
				}
				
				$thispage['pagedir'] = 'older';
				$out = parse(EvalElse($thing,true));; 
				$thispage['pagedir'] = '';
				
				return $out;
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : parse(EvalElse($thing,false));
	}

// -------------------------------------------------------------
/* 
 * Without any attributes, this tag return the next, previous or 
 * current page numer relative to the current page number.
 * 
 * NEW: With 'pageby' attribute plus any other <txp:article> selection attributes
 * it returns the page number of the current article within the given selection.
 *
 * Example: 
 *
 *	<txp:page path="../*" sort="position asc" pageby="5"/>
 */
 
	function page($atts)
	{
		global $thisarticle, $thispage, $pretext;
		
		if (isset($atts['pageby'])) {
			
			if (isset($thispage)) {
				$saved_thispage = $thispage;
				$thispage = array();
			}
			
			// fill $thispage with the given article query
			$atts['return'] = 'NOTHING';
			article($atts,'OK');
			
			$pg = find_page_number($thisarticle['thisid']);
			
			if (isset($saved_thispage)) {
				$thispage = $saved_thispage;
			}
		
		} else {
		
			if (isset($thispage)) {
			
				extract($thispage);
				 
				if ($pagedir == 'newer' && $pg > 1) 		 return $pg - 1;
				if ($pagedir == 'older' && $pg != $numPages) return $pg + 1;
			
			} else {
				
				$pg = ($pretext['pg']) ? $pretext['pg'] : 1;
			}
		}
		
		return $pg;
	}

// -------------------------------------------------------------

	function page_count($atts)
	{
		$atts['count'] = true;
		
		return pages($atts);
	}
	
// -------------------------------------------------------------

	function pages($atts, $thing = NULL)
	{
		global $thispage, $pretext;
		
		extract(lAtts(array(
			'break'   => ' | ',
			'wraptag' => '',
			'class'   => '',
			'count'	  => false
		),$atts));
		
		if (!is_array($thispage)) return;
		
		extract($thispage);
		
		if ($count) return $numPages;
		if ($numPages == 1) return;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -  
		
		$search = '';
		
		if ($pretext['q']) {
			$search  = '/search_for_'.urlencode($pretext['q']);
			$search .= (gps('include')) ? '_include_'.gps('include') : '';
			$s       = '';
		}	
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -  
		
		if ($thing) {
			
			$pg = $thispage['pg'];
			
			for ($i = 1; $i <= $numPages; $i++) {
				
				$thispage['pg'] = $i;
				$out[] = parse($thing);
			}
			
			$thispage['pg'] = $pg;
			
			return implode($break,$out);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$out = array();
		
		for ($i = 1; $i <= $numPages; $i++) {
			
			$title = $i;
			$href  = '';
			
			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}
			
			$href = pagelinkurl(array(
				'month'  => @$pretext['month'],
				'pg'     => $i,
				's'      => @$pretext['s'],
				'c'      => @$pretext['c'],
				'q'      => @$pretext['q'],
				'author' => $author
			));
			
			$link_class = 'page-'.$i;
			
			if ($i == $pg) $link_class .= ' selected';
			
			if ($wraptag == 'select')
				$out[$href] = $i;
			else
				$out[] = tag(str_replace("& ","&#38; ", $title),'a',' href="'.$href.'" class="'.$link_class.'"');
		}
		
		if (count($out)) {
		
			if ($wraptag == 'select')
				return selectInput('page',$out,$pg,0,' onchange="location=this.value"');
			
			return doWrap($out,$wraptag,$break,$class);
		}
		
		return '';
	}
		
// -------------------------------------------------------------

	function text($atts)
	{
		extract(lAtts(array(
			'item' => '',
		),$atts));
		
		return ($item) ? gTxt($item) : '';
	}

// -------------------------------------------------------------

	function id()
	{
		return article_id();
	}

// -------------------------------------------------------------

	function parent_id()
	{
		return parent_article_id();
	}
	
// -------------------------------------------------------------

	function article_id()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['thisid'];
	}

// -------------------------------------------------------------------------------------
// new: get the id of the original article if article is an alias

	function alias_id($atts) 
	{
		global $thisarticle;
		
		return ($thisarticle['alias']) 
			? $thisarticle['alias'] 
			: $thisarticle['thisid'];
	}
	
// -------------------------------------------------------------
// new

	function parent_article_id() 
	{
		global $thisarticle;
		
		assert_article();
		
		return $thisarticle['parent'];
	}

// -------------------------------------------------------------

	function article_url_title()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['url_title'];
	}

// -------------------------------------------------------------

	function article_name() 
	{
		global $thisarticle;
		
		return $thisarticle['name'];
	}

// -------------------------------------------------------------

	function name_tag() 
	{
		global $thisarticle;
		
		return $thisarticle['name'];
	}

// -------------------------------------------------------------
// new

	function parent_article_name() 
	{
		global $thisarticle;
		
		assert_article();
		
		if ($parent = $thisarticle['parent']) {
			
			return fetch("name","textpattern","id",$parent);
		}
		
		return '';
	}

// -------------------------------------------------------------

	function if_article_id($atts, $thing)
	{
		global $thisarticle, $pretext, $txptrace, $dump;
		
		$dump[]['h2'] = htmlentities(ltrim(end($txptrace)));

		assert_article();

		extract(lAtts(array(
			'id' => $pretext['id'],
		), $atts));

		if ($id) {
			
			$test = evalAtt($id,$thisarticle['thisid']);
			
			return parse(EvalElse($thing, $test));
		}
	}

// -------------------------------------------------------------

	function posted($atts)
	{
		global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

		assert_article();

		extract(lAtts(array(
			'class'   => '',
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
			'wraptag' => ''
		), $atts));

		if ($format)
		{
			$out = safe_strftime($format, $thisarticle['posted'], $gmt, $lang);
		}

		else
		{
			if ($id or $c or $pg)
			{
				$out = safe_strftime($archive_dateformat, $thisarticle['posted']);
			}

			else
			{
				$out = safe_strftime($dateformat, $thisarticle['posted']);
			}
		}

		return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
	}

// -------------------------------------------------------------

	function expires($atts)
	{
		global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

		assert_article();

		if($thisarticle['expires'] == 0)
		{
			return;
		}

		extract(lAtts(array(
			'class'   => '',
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
			'wraptag' => '',
		), $atts));

		if ($format)
		{
			$out = safe_strftime($format, $thisarticle['expires'], $gmt, $lang);
		}

		else
		{
			if ($id or $c or $pg)
			{
				$out = safe_strftime($archive_dateformat, $thisarticle['expires']);
			}

			else
			{
				$out = safe_strftime($dateformat, $thisarticle['expires']);
			}
		}

		return ($wraptag) ? doTag($out, $wraptag, $class) : $out;
	}

// -------------------------------------------------------------

	function if_expires($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, $thisarticle['expires']));
	}

// -------------------------------------------------------------

	function if_expired($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing,
			$thisarticle['expires'] && ($thisarticle['expires'] <= time() )));
	}

// -------------------------------------------------------------

	function modified($atts)
	{
		global $thisarticle, $id, $c, $pg, $dateformat, $archive_dateformat;

		assert_article();

		extract(lAtts(array(
			'format'  => '',
			'gmt'     => '',
			'lang'    => ''
		), $atts));

		if ($format)
		{
			return safe_strftime($format, $thisarticle['modified'], $gmt, $lang);
		}

		else
		{
			if ($id or $c or $pg)
			{
				return safe_strftime($archive_dateformat, $thisarticle['modified']);
			}

			else
			{
				return safe_strftime($dateformat, $thisarticle['modified']);
			}
		}
	}

// -------------------------------------------------------------

	function author($atts)
	{
		global $thisarticle, $s;

		assert_article();

		extract(lAtts(array(
			'link'         => '',
			'section'      => '',
			'this_section' => 0,
		), $atts));

		$author_name = get_author_name($thisarticle['authorid']);

		$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

		return ($link) ?
			href($author_name, pagelinkurl(array('s' => $section, 'author' => $author_name))) :
			$author_name;
	}

//------------------------------------------------------------------------
// new
	
	function authorid()
	{
		global $thisarticle;
		
		assert_article();
		
		return $thisarticle['authorid'];
	}
	
// -------------------------------------------------------------

	function if_author($atts, $thing)
	{
		global $author;

		extract(lAtts(array(
			'name' => '',
		), $atts));

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}

// -------------------------------------------------------------

	function if_article_author($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'name' => '',
		), $atts));

		$author = $thisarticle['authorid'];

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}

// -------------------------------------------------------------
// change: get title from article_stack

	function title($atts)
	{
		global $thisarticle, $thiscategory, $article_stack, $prefs;
		
		extract(lAtts(array(
			'no_widow' => @$prefs['title_no_widow'],
			'stack'    => '',
			'split'    => '',
			'textile'  => ''
		), $atts));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		$title = ($stack) 
			? $article_stack->get('title',$stack)
			: $thisarticle['title'];
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($atts['textile'])) {
			
			if ($textile == '0') {
				
				$title = preg_replace('/<\/?[a-z]+>/','',$title);
			}
		}
		
		// $title = escape_title($title);
		
		if ($split == 'word') {
				
			$title = explode(' ',$title);
			
			foreach($title as $key => $word) {
				$title[$key] = '<span class="word-'.($key+1).'" id="word-'.make_name($word).'">'.$word.'</span>';
			}
			
			$title = implode(' ',$title);
		}
		
		if ($no_widow) {
			
			$title = noWidow($title);
		}
		
		return $title;
	}

// -------------------------------------------------------------
// new

	function article_title() 
	{
		global $thisarticle;
	
		return $thisarticle['title'];
	}

// -------------------------------------------------------------
// new

	function parent_title() 
	{
		global $thisarticle;
		assert_article();
		
		if ($thisarticle['parent'])
			return $thisarticle['parent_title'];
		else	
			return $thisarticle['title'];
	}

// -------------------------------------------------------------

	function body($atts, $thing=NULL)
	{
		global $thisarticle, $is_article_body, $article_stack;
		assert_article();
		
		extract(lAtts(array(
			'textile'  => '',
			'maxwords' => 0,
			'wraptag'  => '',
			'class'	   => ''
		),$atts));
		
		$article_stack->set('body_tag_encounter',true);
		
		$is_article_body = 1;
		$body = trim(parse($thisarticle['body']));
		$is_article_body = 0;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($maxwords) {
			
			$words = preg_split('/\s+/',trim($body));
			
			if (count($words) > $maxwords) {
				
				$words = array_slice($words,0,$maxwords);
				$body = implode(' ',$words).'&#8230;';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($thing and !$body) {
			return parse($thing);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($atts['textile'])) {
			
			$thisid = $thisarticle['thisid'];
			
			if ($textile == '0') {
			
				$body = fetch('Body','textpattern','ID',$thisid);
				
				// check for {$txp.*} 
				if (preg_match('/\{\$txp\./',$body)) {
					
					include_once txpath.'/include/lib/txp_lib_misc.php';
				
					$body = examineHTMLTags($body,false);
				} 
			}
			
			if ($textile == '1') {
			
				$body = fetch('Body','textpattern','ID',$thisid);
			  
				$body = htmlentities($body, ENT_NOQUOTES, "utf-8");
			  
				$body = nl2br(trim($body));
			}
			
			if ($textile == '2') {
			
				include_once txpath.'/lib/classTextile_mod.php';
				$textile = new TextileMod();
			
				$body = fetch('Body','textpattern','ID',$thisid);
				$body = $textile->TextileThis($body);
			}
			
			$is_article_body = 1;
			$body = trim(parse($body));
			$is_article_body = 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($class and !$wraptag) {
			
			$wraptag = 'div';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($wraptag) {
			
			$atts = ($class) ? ' class="'.$class.'"' : '';
			
			$body = tag($body,$wraptag,$atts);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $body;
	}

// -------------------------------------------------------------
	function excerpt($atts, $thing=NULL)
	{
		global $thisarticle, $is_article_body, $article_stack;
		assert_article();
		
		extract(lAtts(array(
			'textile'  => '',
			'maxwords' => 0
		),$atts));
		
		$article_stack->set('body_tag_encounter',true);
		
		$is_article_body = 1;
		$excerpt = parse($thisarticle['excerpt']);
		$is_article_body = 0;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($maxwords) {
			
			$words = preg_split('/\s+/',trim($excerpt));
			
			if (count($words) > $maxwords) {
				
				$words = array_slice($words,0,$maxwords);
				$excerpt = implode(' ',$words).'&#8230;';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($atts['textile'])) {
			
			$thisid = $thisarticle['thisid'];
			
			if ($textile == '0') {
			
				$excerpt = fetch('Excerpt','textpattern','ID',$thisid);
				
				// check for {$txp.*} 
				if (preg_match('/\{\$txp\./',$excerpt)) {
					
					include_once txpath.'/include/lib/txp_lib_misc.php';
				
					$excerpt = examineHTMLTags($excerpt,false);
				} 
			}
			
			if ($textile == '1') {
			
				$excerpt = fetch('Excerpt','textpattern','ID',$thisid);
			  
				$excerpt = htmlentities($excerpt, ENT_NOQUOTES, "utf-8");
			  
				$excerpt = nl2br(trim($excerpt));
			}
			
			if ($textile == '2') {
			
				include_once txpath.'/lib/classTextile_mod.php';
				$textile = new TextileMod();
			
				$excerpt = fetch('Excerpt','textpattern','ID',$thisid);
				$excerpt = $textile->TextileThis($excerpt);
			}
			
			$is_article_body = 1;
			$excerpt = parse($excerpt);
			$is_article_body = 0;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return trim($excerpt);
	}

//--------------------------------------------------------------
// new
// TODO: OR, IN, NOT evaluation

	function if_title($atts, $thing = NULL)
	{
		global $article_stack;
		
		extract(lAtts(array(
			'name'  => '',
			'stack' => ''
		),$atts));
		
		$title = strtolower(trim($article_stack->get('title',$stack)));
		
		if ($name = strtolower(trim($name))) { 
			
			$title = ($name == $title);
		}
		
		return parse(EvalElse($thing, $title));
	}

//------------------------------------------------------------------------

	function if_name($atts, $thing = NULL)
	{
		$atts['name'] = 'name';
		
		return if_variable($atts,$thing,'article');
	}
	
//------------------------------------------------------------------------

	function if_body($atts, $thing = NULL)
	{
		$atts['name'] = 'body';
		
		return if_variable($atts,$thing,'article');
	}

//------------------------------------------------------------------------

	function if_no_body($atts, $thing = NULL)
	{
		global $thisarticle;
		
		return (!strlen(trim($thisarticle['body']))) ? parse($thing) : '';
	}

//------------------------------------------------------------------------

	function if_no_excerpt($atts, $thing = NULL)
	{
		global $thisarticle;
		
		return (!strlen(trim($thisarticle['excerpt']))) ? parse($thing) : '';
	}
	
// -------------------------------------------------------------

	function category1($atts, $thing = NULL)
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'title'        => 0,
			'section'      => '',
			'this_section' => 0,
			'wraptag'      => '',
		), $atts));
		
		$categories = explode(',',$thisarticle['categories']);
		$category1  = array_shift($categories);
		
		if ($category1)
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $category1;

			$label = htmlspecialchars(($title) ? fetch_category_title($category) : $category);

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'">'.$label.'</a>';
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function category2($atts, $thing = NULL)
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'title'        => 0,
			'section'      => '',
			'this_section' => 0,
			'wraptag'      => '',
		), $atts));
		
		$categories = explode(',',$thisarticle['categories']);
		$category1  = array_shift($categories);
		$category2  = array_shift($categories);
		
		if ($category2)
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $category2;

			$label = htmlspecialchars(($title) ? fetch_category_title($category) : $category);

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'">'.$label.'</a>';
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function if_categories($atts,$thing)
	{
		global $thisarticle;
		
		extract(lAtts(array(
			'exclude' => ''
		), $atts));
		
		$categories = explode(',',$thisarticle['categories']);
		$categories = array_flip($categories);
		
		if ($exclude) {
			foreach (do_list($exclude) as $category) {
				if (isset($categories[$category])) unset($categories[$category]);
			}
		}
		
		return parse(EvalElse($thing,count($categories)));	
	}
		
// -------------------------------------------------------------

	function categories($atts)
	{
		global $thisarticle,$siteurl;
		static $titles = array();
		
		extract(lAtts(array(
			'title'   => 1,
			'link'	  => 0,		// number of page href parts to use for the link href
			'sep'	  => ', ',
			'exclude' => '',
			'limit'	  => 0
		), $atts));
		
		if (!strlen($thisarticle['categories'])) return '';
		
		$categories = explode(',',$thisarticle['categories']);
		$exclude = explode(',',$exclude);
		
		foreach($categories as $key => $category) {
			
			if ($exclude and in_array($category,$exclude)) {
				
				unset($categories[$key]);
			}
		}
		
		if ($limit) {
			$categories = array_slice($categories,0,$limit);
		}
					
		if ($title) {
			
			foreach($categories as $key => $category) {
				
				if (!isset($titles[$category])) {
					
					$title = $titles[$category] = safe_field("Title","txp_category","Name = '$category'");
				
				} else {
					
					$title = $titles[$category];
				}
				
				if ($link) {
					
					$path = path_tag(array(),'req',$link);
					
					$title = '<a href="http://'.$siteurl.'/'.$path.'/'.$category.'/index.html">'.$title.'</a>';
				}
				
				$categories[$key] = $title;
			}
		}
		
		return implode($sep,$categories);
	}

// -------------------------------------------------------------

	function category_name($atts, $thing = NULL)
	{
		return category($atts,$thing);
	}

// -------------------------------------------------------------

	function category_title($atts, $thing = NULL)
	{
		$atts['title'] = 1;
		
		return category($atts,$thing);
	}
	
// -------------------------------------------------------------

	function category($atts, $thing = NULL)
	{
		global $s, $c, $thiscategory;

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'name'         => '',
			'plural'	   => '',
			'section'      => $s, // fixme in crockery
			'this_section' => 0,
			'title'        => 0,
			'type'         => 'article',
			'url'          => 0,
			'wraptag'      => '',
			'req'	   	   => 0
		), $atts));

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($name)
		{
			$category = $name;
		}
		
		elseif ($plural)
		{
			$category = safe_field('Name','txp_category',"Plural = '$plural' AND Trash = 0");
		}
		
		elseif ($req)
		{
			$category = $c;
		}
		
		elseif (!empty($thiscategory['name']))
		{
			$category = $thiscategory['name'];
			$type = $thiscategory['type'];
		}
	
		else
		{
			$category = $c;
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		if ($category)
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$label = htmlspecialchars( ($title or $name) ? fetch_category_title($category, $type) : $category );
			
			$href = pagelinkurl(array('s' => $section, 'c' => $category));

			if ($thing)
			{
				$out = '<a href="'.$href.'"'.
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label, $href, ($class and !$wraptag) ? ' class="'.$class.'"' : '');
			}

			elseif ($url)
			{
				$out = $href;
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------
/* 
 * This tag returns the number of items that belong to 
 * each group specified by a @groupby attribute.
 *
 * Example: 
 *
 *	<txp:article groupby="category">  
 *		<txp:if_var name="group_count" value="1">
 *			There is 1 <txp:category_title/> item.<br/>
 *		<txp:else/>
 *			There are <txp:group_count/> <txp:category_title/> items.<br/>
 *		</txp:if_var>
 *	</txp:article>
 */
	function group_count($atts)
	{
		global $thisarticle;
		
		if (isset($thisarticle['group_count'])) {
		
			return $thisarticle['group_count'];
		}
	}

// -------------------------------------------------------------

	function page_section($atts, $thing = NULL)
	{
		global $pretext;

		extract(lAtts(array(
			'title' => 0
		), $atts));
		
		$path = explode('/',$pretext['path']);
		$section_name  = doSlash(array_shift($path));
		$section_title = safe_field('Title','textpattern',
			"Name = '$section_name' AND Class = 'section' AND Trash = 0");
		
		if ($section_title) {
		
			return ($title) ? $section_title : $section_name;
		}
	}

// -------------------------------------------------------------------------------------
// new: return section title

	function section_title($name='')
	{
		global $thisarticle,$s;
	
		if (!$name) {
			$name = path_tag(array(),'req',1);
		}
		
		$title = safe_field("Title","textpattern",
			"Name = '$name' AND Class = 'section' AND Trash = 0 AND Status IN (4,5)");
		
		return ($title) ? $title : $name;
	}

// -------------------------------------------------------------

	function section($atts, $thing = NULL)
	{
		global $thisarticle, $s, $thissection;

		extract(lAtts(array(
			'class'   => '',
			'link'		=> 0,
			'name'		=> '',
			'title'		=> 0,
			'url'		=> 0,
			'wraptag' => '',
		), $atts));
		
		if ($name)
		{
			$sec = $name;
		}

		elseif (!empty($thissection['name']))
		{
			$sec = $thissection['name'];
		}

		elseif (!empty($thisarticle['section']))
		{
			$sec = $thisarticle['section'];
		}

		else
		{
			$sec = $s;
		}

		if ($sec)
		{	
			$label = htmlspecialchars( ($title) ? fetch_section_title($sec) : $sec );

			$href = pagelinkurl(array('s' => $sec));
			
			if ($thing)
			{
				$out = '<a href="'.$href.'"'.
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label, $href, ($class and !$wraptag) ? ' class="'.$class.'"' : '');
			}

			elseif ($url)
			{
				$out = $href;
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------------------------------

	function class_tag($atts) {
		
		global $thisarticle;
		
		return $thisarticle['class'];
	}

//------------------------------------------------------------------------

	function if_class($atts, $thing = NULL)
	{
		$atts['name'] = 'class';
		
		return if_variable($atts,$thing,'article');
	}
	
// -------------------------------------------------------------------------------------
// new

	function search_query($atts, $thing='')
	{
		global $q;
		
		if (is_array($atts)) extract($atts);
		
		$default = ($default) ? gTxt($default) : '';
		
		if ($thing) {
			
			return preg_replace('/('.preg_quote($q).')/i',"<b>$1</b>",parse($thing));
		}
		
		return ($q) ? $q : $default;
	}

// -------------------------------------------------------------
	function keywords()
	{
		global $thisarticle;
		assert_article();
		
		$keywords = str_replace(',',', ',$thisarticle['keywords']);
		
		return htmlspecialchars($keywords);
	}

// -------------------------------------------------------------
	function meta_keywords($atts, $thing = NULL)
	{
		global $thisarticle;
		assert_article();
		
		$content = str_replace(',',', ',$thisarticle['keywords']);
		
		if ($thing) {
			
			$thing = parse($thing);
			
			if (str_begins_with($thing,'+')) {
				
				$content = $content.', '.trim($thing,'+ ');
			
			} elseif (str_ends_with($thing,'+')) {
				
				$content = trim($thing,'+ ').', '.$content;
			}
			
			$content = trim($content,', ');
		}
		
		if (!strlen($content)) {
			
			$content = fetch('Keywords','textpattern','ID',ROOTNODEID);
		
		} elseif (str_begins_with($content,'+')) {
			
			$content = fetch('Keywords','textpattern','ID',ROOTNODEID).', '.trim($content,'+ ');
			$content = trim($content,', ');
		
		} elseif (str_ends_with($content,'+')) {
			
			$content = trim($content,'+ ').', '.fetch('Keywords','textpattern','ID',ROOTNODEID);
			$content = trim($content,', ');
		}
		
		return '<meta name="keywords" content="'.$content.'" />';
	}
	
// -------------------------------------------------------------
	function if_keywords($atts, $thing = NULL)
	{
		global $thisarticle;
		assert_article();
		extract(lAtts(array(
			'keywords' => ''
		), $atts));
		
		$condition = empty($keywords) ?
			$thisarticle['keywords'] :
			array_intersect(do_list($keywords), do_list($thisarticle['keywords']));

		return parse(EvalElse($thing, !empty($condition)));
	}

// -------------------------------------------------------------
	function description($atts)
	{
		global $thisarticle;
		assert_article();
		
		return htmlspecialchars($thisarticle['description']);
	}

// -------------------------------------------------------------
	function meta_description($atts, $thing = NULL)
	{
		global $thisarticle;
		assert_article();
		
		$content = $thisarticle['description'];
		
		if ($thing) {
			
			$content = parse($thing);
		}
		
		if (!strlen($content)) {
			
			if (column_exists('textpattern','Description')) {
				
				$content = fetch('Description','textpattern','ID',ROOTNODEID);
			}
		}
		
		return '<meta name="description" content="'.$content.'" />';
	}
	
// -------------------------------------------------------------
	function if_description($atts, $thing = NULL)
	{
		global $thisarticle;
		assert_article();
		
		return parse(EvalElse($thing,strlen($thisarticle['description'])));
	}
	
// -------------------------------------------------------------

	function if_article_image($atts, $thing = NULL)
	{
	    global $thisarticle;
	    assert_article();

	    return parse(EvalElse($thing, $thisarticle['article_image']));
	}

// -------------------------------------------------------------

	function article_image($atts)
	{
		global $thisarticle, $img_dir;

		assert_article();

		extract(lAtts(array(
			'align'     => '', // deprecated in 4.2.0
			'class'     => '',
			'escape'    => 'html',
			'html_id'   => '',
			'style'     => '',
			'thumbnail' => 0,
			'wraptag'   => '',
		), $atts));

		if ($align)
			trigger_error(gTxt('deprecated_attribute', array('{name}' => 'align')), E_USER_NOTICE);

		if ($thisarticle['article_image'])
		{
			$image = $thisarticle['article_image'];
		}

		else
		{
			return;
		}

		if (is_numeric($image))
		{
			$rs = safe_row('*', 'txp_image', 'id = '.intval($image));

			if ($rs)
			{
				if ($thumbnail)
				{
					if ($rs['thumbnail'])
					{
						extract($rs);

						if ($escape == 'html')
						{
							$alt = htmlspecialchars($alt);
							$caption = htmlspecialchars($caption);
						}

						$out = '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'"'.
							($caption ? ' title="'.$caption.'"' : '').
							( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
							( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
							($style ? ' style="'.$style.'"' : '').
							($align ? ' align="'.$align.'"' : '').
							' />';
					}

					else
					{
						return '';
					}
				}

				else
				{
					extract($rs);

					if ($escape == 'html')
					{
						$alt = htmlspecialchars($alt);
						$caption = htmlspecialchars($caption);
					}

					$out = '<img src="'.hu.$img_dir.'/'.$id.$ext.'" width="'.$w.'" height="'.$h.'" alt="'.$alt.'"'.
						($caption ? ' title="'.$caption.'"' : '').
						( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
						( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
						($style ? ' style="'.$style.'"' : '').
						($align ? ' align="'.$align.'"' : '').
						' />';
				}
			}

			else
			{
				trigger_error(gTxt('unknown_image'));
				return;
			}
		}

		else
		{
			$out = '<img src="'.$image.'" alt=""'.
				( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
				( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
				($style ? ' style="'.$style.'"' : '').
				($align ? ' align="'.$align.'"' : '').
				' />';
		}

		return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
	}

// -------------------------------------------------------------
	function search_result_score($atts)
	{
		global $thisarticle;
		
		return $thisarticle['score'];
	}

// -------------------------------------------------------------
	function search_result_title($atts)
	{
		global $thisarticle;
		
		// return permlink($atts, '<txp:title />');
		
		return $thisarticle['title'];
	}

// -------------------------------------------------------------
	function search_result_excerpt($atts)
	{
		global $thisarticle, $pretext;

		assert_article();

		extract(lAtts(array(
			'break'   => ' &#8230;',
			'hilight' => 'strong',
			'limit'   => 5,
		), $atts));

		$q = $pretext['q'];

		$result = preg_replace('/\s+/', ' ', strip_tags(str_replace('><', '> <', $thisarticle['body'])));
		preg_match_all('/(\G|\s).{0,50}'.preg_quote($q).'.{0,50}(\s|$)/iu', $result, $concat);

		for ($i = 0, $r = array(); $i < min($limit, count($concat[0])); $i++)
		{
			$r[] = trim($concat[0][$i]);
		}

		$concat = join($break.n, $r);
		$concat = preg_replace('/^[^>]+>/U', '', $concat);
#TODO
		$concat = preg_replace('/('.preg_quote($q).')/i', "<$hilight>$1</$hilight>", $concat);

		return ($concat) ? trim($break.$concat.$break) : '';
	}

// -------------------------------------------------------------
	function search_result_url($atts)
	{
		global $thisarticle;
		assert_article();

		$l = permlinkurl($thisarticle);
		return permlink($atts, $l);
	}

// -------------------------------------------------------------
	function search_result_date($atts)
	{
		assert_article();
		return posted($atts);
	}

// -------------------------------------------------------------
	function search_result_count($atts)
	{
		global $thispage;
		$t = @$thispage['grand_total'];
		extract(lAtts(array(
			'text'     => ($t == 1 ? gTxt('article_found') : gTxt('articles_found')),
		),$atts));

		return $t . ($text ? ' ' . $text : '');
	}

// -------------------------------------------------------------
	function image_index($atts)
	{
		global $s,$c,$p,$img_dir,$path_to_site;
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
			'c'        => $c, // Keep the option to override categories due to backward compatiblity
			'limit'    => 0,
			'offset'   => 0,
			'sort'     => 'name ASC'
		),$atts));

		$qparts = array(
			"category = '".doSlash($c)."' and thumbnail = 1",
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*', 'txp_image',  join(' ', $qparts));

		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				$impath = $img_dir.'/'.$id.'t'.$ext;
				$imginfo = getimagesize($path_to_site.'/'.$impath);
				$dims = (!empty($imginfo[3])) ? ' '.$imginfo[3] : '';
				$url = pagelinkurl(array('c'=>$c, 's'=>$s, 'p'=>$id));
				$out[] = '<a href="'.$url.'">'.
					'<img src="'.hu.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';

			}
			if (count($out)) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function image_display($atts)
	{
		if (is_array($atts)) extract($atts);
		global $s,$c,$p,$img_dir;
		if($p) {
			$rs = safe_row("*", "txp_image", 'id='.intval($p).' limit 1');
			if ($rs) {
				extract($rs);
				$impath = hu.$img_dir.'/'.$id.$ext;
				return '<img src="'.$impath.
					'" style="height:'.$h.'px;width:'.$w.'px" alt="'.$alt.'" />';
			}
		}
	}

// -------------------------------------------------------------------------------------
	function if_alias($atts, $thing) {
	
		global $thisarticle;
		assert_article();
		
		return parse(EvalElse($thing,$thisarticle['alias']));
	}
	
// -------------------------------------------------------------
/*	function meta_keywords()
	{
		global $id_keywords;
		return ($id_keywords)
		?	'<meta name="keywords" content="'.htmlspecialchars($id_keywords).'" />'
		:	'';
	}
*/
// -------------------------------------------------------------
/*	function meta_author()
	{
		global $id_author;
		return ($id_author)
		?	'<meta name="author" content="'.htmlspecialchars($id_author).'" />'
		:	'';
	}
*/
// -------------------------------------------------------------

	function permlink($atts, $thing = NULL)
	{
		global $thisarticle;

		extract(lAtts(array(
			'class' => '',
			'id'		=> '',
			'style' => '',
			'title' => '',
		), $atts));

		if (!$id)
		{
			assert_article();
		}

		$url = ($id) ? permlinkurl_id($id) : permlinkurl($thisarticle);

		if ($url)
		{
			if ($thing === NULL)
			{
				return $url;
			}

			return tag(parse($thing), 'a', ' rel="bookmark" href="'.$url.'"'.
				($title ? ' title="'.$title.'"' : '').
				($style ? ' style="'.$style.'"' : '').
				($class ? ' class="'.$class.'"' : '')
			);
		}
	}

// -------------------------------------------------------------

	function permlinkurl_id($id)
	{
		global $permlinks;
		if (isset($permlinks[$id])) return $permlinks[$id];

		$id = (int) $id;

		$rs = safe_row(
			"ID as thisid, Section as section, Title as title, url_title, unix_timestamp(Posted) as posted",
			'textpattern',
			"ID = $id"
		);

		return permlinkurl($rs);
	}

// -------------------------------------------------------------
	function permlinkurl($article_array)
	{
		global $permlink_mode, $prefs, $permlinks;

		if (isset($prefs['custom_url_func'])
		    and is_callable($prefs['custom_url_func'])
		    and ($url = call_user_func($prefs['custom_url_func'], $article_array, PERMLINKURL)) !== FALSE)
		{
			return $url;
		}

		if (empty($article_array)) return;

		extract($article_array);

		if (empty($thisid)) $thisid = $ID;

		if (isset($permlinks[$thisid])) return $permlinks[$thisid];

		if (!isset($title)) $title = $Title;
		if (empty($url_title)) $url_title = stripSpace($title);
		// if (empty($section)) $section = $Section; // lame, huh?
		if (!isset($posted)) $posted = $Posted;

		$section = urlencode($section);
		$url_title = urlencode($url_title);
		
		switch($permlink_mode) {
			case 'section_id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					$out = hu."$section/$thisid/$url_title";
				}else{
					$out = hu."$section/$thisid/";
				}
				break;
			case 'year_month_day_title':
				list($y,$m,$d) = explode("-",date("Y-m-d",$posted));
				$out =  hu."$y/$m/$d/$url_title";
				break;
			case 'id_title':
				if ($prefs['attach_titles_to_permalinks'])
				{
					$out = hu."$thisid/$url_title.html";
				}else{
					$out = hu."$thisid/";
				}
				break;
			case 'section_title':
				$out = hu."$section/$url_title";
				break;
			case 'title_only':
				// $out = hu."$url_title.html";
				$out = hu.path_tag(array(),'req').".html";
				break;
			case 'messy':
				$out = hu."index.php?id=$thisid";
				break;
		}
		return $permlinks[$thisid] = $out;
	}

// -------------------------------------------------------------
	function lang()
	{
		return LANG;
	}

// -------------------------------------------------------------

	function breadcrumb($atts)
	{
		global $pretext,$sitename;

		extract(lAtts(array(
			'wraptag' => 'p',
			'sep' => '&#160;&#187;&#160;',
			'link' => 1,
			'label' => $sitename,
			'title' => '',
			'class' => '',
			'linkclass' => 'noline',
		),$atts));

		// bc, get rid of in crockery
		if ($link == 'y') {
			$linked = true;
		} elseif ($link == 'n') {
			$linked = false;
		} else {
			$linked = $link;
		}

		if ($linked) $label = doTag($label,'a',$linkclass,' href="'.hu.'"');

		$content = array();
		extract($pretext);
		if(!empty($s) && $s!= 'default')
		{
			$section_title = ($title) ? fetch_section_title($s) : $s;
			$section_title_html = escape_title($section_title);
			$content[] = ($linked)? (
					doTag($section_title_html,'a',$linkclass,' href="'.pagelinkurl(array('s'=>$s)).'"')
				):$section_title_html;
		}

		$category = empty($c)? '': $c;

		foreach (getTreePath($category, 'article') as $cat) {
			if ($cat['name'] != 'root') {
				$category_title_html = $title ? escape_title($cat['title']) : $cat['name'];
				$content[] = ($linked)?
					doTag($category_title_html,'a',$linkclass,' href="'.pagelinkurl(array('c'=>$cat['name'])).'"')
						:$category_title_html;
			}
		}

		// add the label at the end, to prevent breadcrumb for home page
		if ($content)
		{
			$content = array_merge(array($label), $content);

			return doTag(join($sep, $content), $wraptag, $class);
		}
	}

// -------------------------------------------------------------------------------------
// new

	function path_tag($atts,$mode='page',$position=0,$limit=0) 
	{
		global $pretext,$thisarticle;
		
		extract(lAtts(array(
			'mode'     => $mode,
			'sep'      => '/',
			'position' => $position,
			'title'	   => 0,
			'link'	   => 0,
			'limit'	   => $limit	
		),$atts));
		
		if ($mode == 'req') {
			
			if (!$title) {
			
				$path = explode('/',$pretext['req_path']);
				
			} else {
				
				$path = explode('/',$pretext['ids']);
				$names = array();
				
				foreach($path as $key => $id) {
					
					extract(safe_row('Name,Title','textpattern',"ID = $id"));
					
					if ($link) {
						$names[] = $Name;
						$path[$key] = doTag($Title,'a','',' href="/'.implode('/',$names).'/index.html"');
					} else {
						$path[$key] = doTag($Title,'span');
					}
				}
			}
		}
		
		if ($mode == 'page') {
			
			assert_article();
			
			$path = explode('/',$thisarticle['path']);
			
			if (SITE_ID != ROOTNODEID) array_shift($path);
		}
		
		if ($mode == 'article') {
			
			assert_article();
			
			if (!$title) {
			
				$path = explode('/',$thisarticle['path']);
				
				array_pop($path);
				
				if (SITE_ID != ROOTNODEID) array_shift($path);
			
			} else {
				
				$ParentID = $thisarticle['parent'];
				
				$path = array();
				
				while ($ParentID != ROOTNODEID) {
					
					extract(safe_row("ParentID,Name,Title","textpattern","ID = $ParentID"));
					
					$path[] = array('name'=>$Name,'title'=>$Title);
				}
				
				$path = array_reverse($path);
				
				$link_path = '';
				
				foreach($path as $key => $item) {
				
					if ($link) {
						
						$link_path .= '/'.$item['name'];
						
						$path[$key] = doTag($item['title'],'a','',' href="'.$link_path.'/index.html"');
					
					} else {
						
						$path[$key] = $item['title'];
					}
				}
			}
		}
		
		if ($limit) {
			
			assert_int($limit);
			
			$path = array_slice($path,0,$limit);
		}
		
		if ($position) {
			
			assert_int($position);
			
			while (!isset($path[$position-1]) and $position > 0) {
				$position -= 1; 
			} 
			
			if ($position) { 
				
				return $path[$position-1];
			}
		} 
		
		return implode($sep,$path);
	}

// -------------------------------------------------------------------------------------
// new

	function article_path($atts)
	{
		assert_article();
		
		$atts['mode'] = 'article';
		
		return path($atts);
	}

// -------------------------------------------------------------------------------------

	function selected($atts)
	{
		global $pretext, $thisarticle;
		
		extract(lAtts(array(
			'mode' => 'class',
			'page' => $pretext['id'],
			'sel'  => $thisarticle['thisid']
		),$atts));
		
		$selected = ($mode == 'menu') ? 'selected="yes"' : "selected";
		
		return ($page == $sel) ? $selected : '';	
	}
	
// -------------------------------------------------------------------------------------
// new

	function level($atts) 
	{
		global $pretext;
		
		return $pretext['level'];
	}

//------------------------------------------------------------------------

	function if_level($atts, $thing=NULL)
	{
		global $pretext;
		
		extract(lAtts(array(
			'num' => 0,
		),$atts));
		
		$test = evalAtt($pretext['level'],$num);
		
		return parse(EvalElse($thing, $test));
	}

//------------------------------------------------------------------------

	function if_excerpt($atts, $thing=NULL)
	{
		global $thisarticle;
		assert_article();
		# eval condition here. example for article excerpt
		$excerpt = trim($thisarticle['excerpt']);
		$condition = (!empty($excerpt))? true : false;
		return parse(EvalElse($thing, $condition));
	}

//--------------------------------------------------------------------------
// Searches use default page. This tag allows you to use different templates if searching
//--------------------------------------------------------------------------

	function if_search($atts, $thing)
	{
		global $pretext;
		return parse(EvalElse($thing, !empty($pretext['q'])));
	}

//--------------------------------------------------------------------------

	function if_search_results($atts, $thing)
	{
		global $thispage, $pretext;

		if(empty($pretext['q'])) return '';

		extract(lAtts(array(
			'min' => 1,
			'max' => 0,
		),$atts));

		$results = (int)$thispage['grand_total'];
		return parse(EvalElse($thing, $results >= $min && (!$max || $results <= $max)));
	}

//--------------------------------------------------------------------------
	function if_category($atts, $thing)
	{
		global $c;

		extract(lAtts(array(
			'name' => FALSE,
		),$atts));

		if ($name === FALSE)
		{
			return parse(EvalElse($thing, !empty($c)));
		}
		else
		{
			return parse(EvalElse($thing, in_list($c, $name)));
		}
	}

//--------------------------------------------------------------------------

	function if_article_category($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'name'   => '',
			'number' => '',
		), $atts));

		$cats = array();

		if ($number) {
			if (!empty($thisarticle['category'.$number])) {
				$cats = array($thisarticle['category'.$number]);
			}
		} else {
			if (!empty($thisarticle['category1'])) {
				$cats[] = $thisarticle['category1'];
			}

			if (!empty($thisarticle['category2'])) {
				$cats[] = $thisarticle['category2'];
			}

			$cats = array_unique($cats);
		}

		if ($name) {
			return parse(EvalElse($thing, array_intersect(do_list($name), $cats)));
		} else {
			return parse(EvalElse($thing, ($cats)));
		}
	}

// -------------------------------------------------------------
	function if_first_category($atts, $thing)
	{
		global $thiscategory;
		assert_category();
		return parse(EvalElse($thing, !empty($thiscategory['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_category($atts, $thing)
	{
		global $thiscategory;
		assert_category();
		return parse(EvalElse($thing, !empty($thiscategory['is_last'])));
	}

//--------------------------------------------------------------------------
	function if_section($atts, $thing)
	{
		global $pretext;
		extract($pretext);
		
		extract(lAtts(array(
			'name' => FALSE,
		),$atts));

		$section = ($s == 'default' ? '' : $s);

		if ($section)
			return parse(EvalElse($thing, $name === FALSE or in_list($section, $name)));
		else
			return parse(EvalElse($thing, $name !== FALSE and (in_list('', $name) or in_list('default', $name))));

	}

//--------------------------------------------------------------------------
	function if_article_section($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'name' => '',
		),$atts));

		$section = $thisarticle['section'];

		return parse(EvalElse($thing, in_list($section, $name)));
	}

// -------------------------------------------------------------
	function if_first_section($atts, $thing)
	{
		global $thissection;
		assert_section();
		return parse(EvalElse($thing, !empty($thissection['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_section($atts, $thing)
	{
		global $thissection;
		assert_section();
		return parse(EvalElse($thing, !empty($thissection['is_last'])));
	}

//------------------------------------------------------------------------
// new
	
	function if_sticky($atts, $thing)
	{
		global $thisarticle;
		assert_section();
		
		$sticky = ($thisarticle['status'] == 5) ? 1 : 0;
	
		return parse(EvalElse($thing, $sticky));
	}

//--------------------------------------------------------------------------
	function php($atts, $thing)
	{
		global $is_article_body, $thisarticle, $prefs;

		ob_start();
		if (empty($is_article_body)) {
			if (!empty($prefs['allow_page_php_scripting']))
				eval($thing);
			else
				trigger_error(gTxt('php_code_disabled_page'));
		}
		else {
			if (!empty($prefs['allow_article_php_scripting'])) {
				if (has_privs('article.php', $thisarticle['authorid']))
					eval($thing);
				else
					trigger_error(gTxt('php_code_forbidden_user'));
			}
			else
				trigger_error(gTxt('php_code_disabled_article'));
		}
		return ob_get_clean();
	}

// -------------------------------------------------------------
	function site_url($atts)
	{
		global $prefs;
		
		return 'http://'.$prefs['siteurl'];
	}

// -------------------------------------------------------------
	function server_name($atts)
	{
		return $_SERVER['SERVER_NAME'];
	}

// -------------------------------------------------------------
	function request_uri($atts)
	{
		return $_SERVER['REQUEST_URI'];
	}

// -------------------------------------------------------------
	function img($atts)
	{
		extract(lAtts(array(
			'src' => '',
		), $atts));

		$img = rtrim(hu, '/').'/'.ltrim($src, '/');

		$out = '<img src="'.$img.'" />';

		return $out;
	}

// -----------------------------------------------------------------------------
	function error_message($atts)
	{
		global $txp_error_message;
		
		extract(lAtts(array(
			'class'		=> 'error',
			'wraptag'	=> 'div',
		), $atts));
		
		if ($txp_error_message)
		{
			return tag($txp_error_message,$wraptag,' class="'.$class.'"');
		}
	}

// -------------------------------------------------------------
/*	function error_message()
	{
		return @$GLOBALS['txp_error_message'];
	}
*/
// -----------------------------------------------------------------------------
	function success_message($atts,$thing=NULL)
	{
		global $txp_error_message;
		
		if (gps('parentid') and !$txp_error_message) {
			return parse($thing);
		}
	}

// -------------------------------------------------------------
	function error_status()
	{
		return @$GLOBALS['txp_error_status'];
	}

// -------------------------------------------------------------
	function if_status($atts, $thing)
	{
		global $pretext;

		extract(lAtts(array(
			'status' => '200',
		), $atts));

		$page_status = !empty($GLOBALS['txp_error_code'])
			? $GLOBALS['txp_error_code']
			: $pretext['status'];

		return parse(EvalElse($thing, $status == $page_status));
	}

// -------------------------------------------------------------
	function page_url($atts)
	{
		global $pretext;

		extract(lAtts(array(
			'type' => 'request_uri',
		), $atts));

		return @htmlspecialchars($pretext[$type]);
	}

// -------------------------------------------------------------
	function if_different($atts, $thing)
	{
		static $last;

		$key = md5($thing);

		$cond = EvalElse($thing, 1);

		$out = parse($cond);
		if (empty($last[$key]) or $out != $last[$key]) {
			return $last[$key] = $out;
		}
		else
			return parse(EvalElse($thing, 0));
	}

// -------------------------------------------------------------------------------------
	function if_plugin($atts, $thing)
	{
		global $plugins, $plugins_ver;
		
		extract(lAtts(array(
			'name'    => '',
			'ver'     => '',
		),$atts));

		return parse(EvalElse($thing, @in_array($name, $plugins) and (!$ver or version_compare($plugins_ver[$name], $ver) >= 0)));
	}

// -------------------------------------------------------------

	function rsd()
	{
		global $prefs;
		
		return ($prefs['enable_xmlrpc_server']) 
			? '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.hu.'rpc/" />' 
			: '';
	}

// -------------------------------------------------------------
// change: allow incrementing or decrementing an existing variable

	function variable($atts, $thing = NULL)
	{
		global $thisarticle, $variable, $pretext, $txptrace, $dump;
		
		$dump[]['h2'] = htmlentities(ltrim(end($txptrace)));
		
		extract(lAtts(array(
			'name'	  => '',
			'value'	  => '',
			'default' => '',
			'init'	  => ''
		), $atts));
		
		if ($value == '!*') $value = '';
		
		$thing = trim($thing);
		
		if (empty($name))
		{
			trigger_error(gTxt('variable_name_empty'));
			return;
		}
		
		if (!isset($atts['value']) and !isset($atts['init']) and empty($thing))
		{
			if (is_numeric($name))
				return path_tag(array(),'req',intval($name)); 
				
			if (isset($variable[$name])) 
				return $variable[$name];
			
			if (isset($pretext[$name])) 
				return $pretext[$name];
				
			if (isset($thisarticle[$name])) 
				return $thisarticle[$name];
			
			return 'undefined';
		}
		
		// value is set
		
		if (!empty($thing))
		{
			$thing = trim(parse($thing));
			if (!empty($thing)) $value = $thing;
		}
		
		if (isset($variable[$name])) {
		
			if (is_numeric($variable[$name])) {
				
				if (is_numeric($value)) { 
					
					if (preg_match('/^(\+|\-)/',$value)) {
						
						$variable[$name] += $value;
						
						return;
					}
					
				} else {
					
					if (preg_match('/^\+\+$/',$value)) {
						
						$variable[$name] += 1;
						
						return;
					}
					
					if (preg_match('/^\-\-$/',$value)) {
						
						$variable[$name] -= 1;
						
						return;
					}
				}
			} else {
				
				if (preg_match('/^\+./',$value)) {
					
					$variable[$name] .= ltrim($value,'+');
						
					return;
				}
			}
		}
		
		if (isset($atts['init'])) {
		
			$variable[$name] = $init;
		
		} elseif (!$value and $default) {
			
			$variable[$name] = $default;
		
		} else {
			
			$variable[$name] = $value;
		}
	}
	
// -------------------------------------------------------------
	function if_tag($atts, $thing = NULL)
	{
		extract(lAtts(array(
			'true'	=> '',
			'false'	=> ''
		), $atts));
		
		$atts['value'] = ($false) ? $false  : $true;
		$type 		   = ($false) ? 'false' : 'true';
		
		unset($atts['true']);
		unset($atts['false']);
		
		return if_variable($atts,$thing,$type);
	}
	
// -------------------------------------------------------------
	function if_variable($atts, $thing = NULL, $type = '')
	{
		global $variable, $pretext, $txptrace, $dump, $thisarticle;
		
		$dump[]['h2'] = htmlentities(ltrim(end($txptrace)));
		
		extract(lAtts(array(
			'name'	=> '',
			'value'	=> ''
		), $atts));
		
		if (empty($name) and empty($value)) {
			trigger_error(gTxt('variable_name_empty'));
			return;
		}
		
		$var = NULL;
		
		if (empty($name)) {
			
			$var = ($type == 'false') ? false : true;
		
		} elseif ($type == 'variable' and isset($variable[$name])) {
		
			$var = $variable[$name];
		
		} elseif ($type == 'pretext' and isset($pretext[$name])) {
		
			$var = $pretext[$name];
		
		} elseif ($type == 'article' and isset($thisarticle[$name])) {
		
			$var = $thisarticle[$name];
		
		} elseif (!$type) {
			
			if (is_numeric($name)) {
			
				$var = path_tag(array(),'req',intval($name)); 
				
			} elseif (isset($variable[$name])) {
				
				$var = $variable[$name];
				
			} elseif (isset($pretext[$name])) {
			
				$var = $pretext[$name];
			
			} elseif (isset($thisarticle[$name])) {
			
				$var = $thisarticle[$name];
			}
		}
		
		if (isset($atts['value'])) {
		
			if ($value == '!*') {
				
				$test = (strlen($var) == 0);
				
			} else {
				
				$test = evalAtt($var,$value);	
			}
			
		} else {
			
			$test = ($var and $var != '*') ? true : false;
		}
		
		return parse(EvalElse($thing, $test));
	}

// -------------------------------------------------------------
	function language($atts) {
		
		global $thisarticle;
		
		return $thisarticle['language'];
	}

// -------------------------------------------------------------
	function language_title($atts) {
		
		global $thisarticle;
		
		switch ($thisarticle['language']) {
			case 'en' : return 'English';
			case 'de' : return 'German';
			case 'fi' : return 'Finnish';
		}
				
		return '';
	}

// -------------------------------------------------------------
// NOTE: This does not really need its own tag.
//		 Can use <txp:if_var name="lg" value="en"> instead

	function if_language($atts, $thing = NULL) {
		
		global $lg;
		
		extract(lAtts(array(
			'name'	=> ''
		), $atts));
		
		$test = (!$name) ? $lg : $name == $lg;
		
		return parse(EvalElse($thing, $test));
	}

// -------------------------------------------------------------

	function screen_size($atts) 
	{
		global $pretext;
		
		$logid = $pretext['logid'];
		
		$width = safe_field('width',"txp_log AS l JOIN txp_log_agent AS a ON l.agent = a.id","l.ID = $logid");
		
		return ($width) ? $width : 0;
	}
	
// -------------------------------------------------------------

	function if_screen_size($atts, $thing = NULL) {
		
		extract(lAtts(array(
			'value'	=> ''
		), $atts));
		
		$size = screen_size($atts);
		
		if (!$size) {
			
			$test = true;
		
		} else {
		
			$test = evalAtt($size,$value);
		}
		
		return parse(EvalElse($thing, $test));
	}

// -------------------------------------------------------------

	function code($atts, $thing = NULL) {
		
		$thing = str_replace('<br />','',$thing);
		$thing = str_replace('<strong style="text-align:left;">','[BOLD]',$thing);
		$thing = str_replace('<strong>','[BOLD]',$thing);
		$thing = str_replace('</strong>','[/BOLD]',$thing);
		$thing = str_replace('&#8221;','"',$thing);
		
		$thing = htmlentities(trim($thing));
		
		$thing = str_replace('&amp;gt;','&gt;',$thing);
		$thing = str_replace('[BOLD]','<b>',$thing);
		$thing = str_replace('[/BOLD]','</b>',$thing);
		$thing = str_replace('<b>txp:','<b>&lt;txp:',$thing);
		
		return tag($thing,'code');
	}
		
// =============================================================================

	function eE($txt) // convert email address into unicode entities
	{
		for ($i=0;$i<strlen($txt);$i++) {
			$ent[] = "&#".ord(substr($txt,$i,1)).";";
		}
		if (!empty($ent)) return join('',$ent);
	}
	
// -----------------------------------------------------------------------------
	function doWrap($list, $wraptag, $break, $class = '', $breakclass = '', $atts = '', $breakatts = '', $id = '')
	{
		if (!$list)
		{
			return '';
		}

		if ($id)
		{
			$atts .= ' id="'.$id.'"';
		}

		if ($class)
		{
			$atts .= ' class="'.$class.'"';
		}

		if ($breakclass)
		{
			$breakatts.= ' class="'.$breakclass.'"';
		}
		
		if ($wraptag == 'ul' or $wraptag == 'ol') {
			$break = 'li';
		}

		// non-enclosing breaks
		if (!preg_match('/^\w+$/', $break) or $break == 'br' or $break == 'hr')
		{
			if ($break == 'br' or $break == 'hr')
			{
				$break = "<$break $breakatts/>".n;
			}

			return ($wraptag) ?	tag(join($break, $list), $wraptag, $atts) :	join($break, $list);
		}

		return ($wraptag) ?
			tag(n.t.tag(join("</$break>".n.t."<{$break}{$breakatts}>", $list), $break, $breakatts).n, $wraptag, $atts) :
			tag(n.join("</$break>".n."<{$break}{$breakatts}>".n, $list).n, $break, $breakatts);
	}

// -----------------------------------------------------------------------------
	function doTag($content, $tag, $class = '', $atts = '', $id = '')
	{
		if ($id)
		{
			$atts .= ' id="'.$id.'"';
		}

		if ($class)
		{
			$atts .= ' class="'.$class.'"';
		}

		if (!$tag)
		{
			return $content;
		}

		return ($content) ? tag($content, $tag, $atts) : "<$tag $atts />";
	}

// -----------------------------------------------------------------------------
	function doLabel($label='', $labeltag='')
	{
		if ($label) {
			return (empty($labeltag)? $label.'<br />' : tag($label, $labeltag));
		}
		return '';
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatPermLink($ID,$Section)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		return permlinkurl_id($ID);
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function formatCommentsInvite($AnnotateInvite,$Section,$ID)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		global $comments_mode;

		$dc = safe_count('txp_discuss','article_id='.intval($ID).' AND Status='.VISIBLE);

		$ccount = ($dc) ?  '['.$dc.']' : '';
		if (!$comments_mode) {
			return '<a href="'.permlinkurl_id($ID).'/#'.gTxt('comment').
				'">'.$AnnotateInvite.'</a>'. $ccount;
		} else {
			return "<a href=\"".hu."?parentid=$ID\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\">".$AnnotateInvite.'</a> '.$ccount;
		}

	}
// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function doPermlink($text, $plink, $Title, $url_title)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		global $url_mode;
		$Title = ($url_title) ? $url_title : stripSpace($Title);
		$Title = ($url_mode) ? $Title : '';
		return preg_replace("/<(txp:permlink)>(.*)<\/\\1>/sU",
			"<a href=\"".$plink.$Title."\" title=\"".gTxt('permanent_link')."\">$2</a>",$text);
	}

// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	function doArticleHref($ID,$Title,$url_title,$Section)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		$conTitle = ($url_title) ? $url_title : stripSpace($Title);
		return ($GLOBALS['url_mode'])
		?	tag($Title,'a',' href="'.hu.$Section.'/'.$ID.'/'.$conTitle.'"')
		:	tag($Title,'a',' href="'.hu.'index.php?id='.$ID.'"');
	}

?>
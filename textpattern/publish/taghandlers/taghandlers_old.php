<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/publish/taghandlers.php $
$LastChangedRevision: 3256 $

*/
	include_once txpath.'/lib/txplib_smarty.php';
	include_once txpath.'/publish/taghandlers_image.php';
	include_once txpath.'/include/lib/txp_lib_custom_v4.php';

// -------------------------------------------------------------

	function page_title($atts)
	{
		global $parentid, $thisarticle, $id, $q, $c, $s, $pg, $sitename;

		extract(lAtts(array(
			'separator' => ': ',
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
		global $css,$pretext;

		extract(lAtts(array(
			'format' => 'url',
			'media'  => 'screen',
			'n'      => $css,
			'p'      => 0,
			'rel'    => 'stylesheet',
			'title'  => '',
		), $atts));
		
		if ($p and isset($pretext['path'])) {
			
			$path = explode('/',$pretext['path']);
			
			if (isset($path[$p-1])) {
				
				$path = doSlash($path[$p-1]);
				
				if (safe_count("txp_css","name='$path'")) {
				
					$n = $path;
				
				} elseif ($pretext['page'] == 'default') {
				
					$n = 'default';
				}
			}
		
		} elseif ($n) {
		
			if (!safe_count("txp_css","name='".doSlash($n)."'")) {
				
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
			" AND ParentID != 0 AND Name != 'TRASH'",
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, ID AS id, Name AS linkname, Body_html AS description, unix_timestamp(Posted) AS uDate', 'txp_link', join(' ', $qparts));

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
		global $thislink;
		assert_link();

		return doSpecial($thislink['url']);
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
	function eE($txt) // convert email address into unicode entities
	{
		for ($i=0;$i<strlen($txt);$i++) {
			$ent[] = "&#".ord(substr($txt,$i,1)).";";
		}
		if (!empty($ent)) return join('',$ent);
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

		$category   = join("','", doSlash(do_list($category)));
		$categories = ($category) ? "and (Category1 IN ('".$category."') or Category2 IN ('".$category."'))" : '';
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
			'wraptag'  => '',
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

// -------------------------------------------------------------
/*
	function related_articles($atts, $thing = NULL)
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
		global $s, $c, $thiscategory;

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

		if ($categories)
		{
			$categories = do_list($categories);
			$categories = join("','", doSlash($categories));

			$rs = safe_rows_start('name, title', 'txp_category',
				"type = '".doSlash($type)."' and name in ('$categories') order by ".($sort ? $sort : "field(name, '$categories')"));
		}

		else
		{
			if ($children)
			{
				$shallow = '';
			} else {
				// descend only one level from either 'parent' or 'root', plus parent category
				$shallow = ($parent) ? "and (parent = '".doSlash($parent)."' or name = '".doSlash($parent)."')" : "and parent = 'root'" ;
			}

			if ($exclude)
			{
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));

				$exclude = "and name not in('$exclude')";
			}

			if ($parent)
			{
				$qs = safe_row('lft, rgt', 'txp_category', "type = '".doSlash($type)."' and name = '".doSlash($parent)."'");

				if ($qs)
				{
					extract($qs);

					$rs = safe_rows_start('name, title', 'txp_category',
						"(lft between $lft and $rgt) and type = '".doSlash($type)."' and name != 'default' $exclude $shallow order by ".($sort ? $sort : 'lft ASC'));
				}
			}

			else
			{
				$rs = safe_rows_start('name, title', 'txp_category',
					"type = '".doSlash($type)."' and name not in('default','root') $exclude $shallow order by ".($sort ? $sort : 'name ASC'));
			}
		}

		if ($rs)
		{
			$out = array();
			$count = 0;
			$last = numRows($rs);

			if (isset($thiscategory)) $old_category = $thiscategory;
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
						$thiscategory = array('name' => $name, 'title' => $title, 'type' => $type);
						$thiscategory['is_first'] = ($count == 1);
						$thiscategory['is_last'] = ($count == $last);
						$out[] = ($thing) ? parse($thing) : parse_form($form);
					}
				}
			}
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
			$rs = fetch('form','txp_form','name',$form);
			if ($rs) {
				return parse($rs);
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
			$nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);
			
			if ($sortdir == 'asc') 
				$nextpg = $pg + 1;
				
			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$url = pagelinkurl(array(
				'month'  => @$pretext['month'],
				'pg'     => $nextpg,
				's'      => @$pretext['s'],
				'c'      => @$pretext['c'],
				'q'      => @$pretext['q'],
				'author' => $author
			));

			if ($thing)
			{
				if ($link) return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
				
				$thispage['pagedir'] = 'newer';
				$out = parse($thing);
				$thispage['pagedir'] = '';
				
				return $out;
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
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

			$url = pagelinkurl(array(
				'month'  => @$pretext['month'],
				'pg'     => $nextpg,
				's'      => @$pretext['s'],
				'c'      => @$pretext['c'],
				'q'      => @$pretext['q'],
				'author' => $author
			));

			if ($thing)
			{
				if ($link) return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
				
				$thispage['pagedir'] = 'older';
				$out = parse($thing);
				$thispage['pagedir'] = '';
				
				return $out;
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------
// new

	function page()
	{
		global $thispage;
	
		if (!is_array($thispage)) return;
		
		extract($thispage);
		
		if ($pagedir == 'newer' && $pg > 1) 		 return $pg - 1;
		if ($pagedir == 'older' && $pg != $numPages) return $pg + 1;
		
		return $pg;
	}

// -------------------------------------------------------------
// new

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
			
			$sel = ($i == $pg) ? ' class="selected"' : ''; 
			
			if ($wraptag == 'select')
				$out[$href] = $i;
			else
				$out[] = tag(str_replace("& ","&#38; ", $title),'a',' href="'.$href.'"'.$sel);
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

	function article_id()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['thisid'];
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
// new

	function article_name() 
	{
		global $thisarticle;
		
		assert_article();
		
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

	function comments_count()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['comments_count'];
	}

// -------------------------------------------------------------
	function comments_invite($atts)
	{
		global $thisarticle,$is_article_list,$comments_mode;
		assert_article();
		
		if (!$thisarticle['comments_invite'])
			$comments_invite = @$GLOBALS['prefs']['comments_default_invite'];

		extract(lAtts(array(
			'class'		=> __FUNCTION__,
			'showcount'	=> true,
			'textonly'	=> false,
			'showalways'=> false,  //FIXME in crockery. This is only for BC.
			'wraptag'   => '',
		), $atts));
		
		extract($thisarticle);
		
		$invite_return = '';
		if (($annotate or $comments_count) && ($showalways or $is_article_list) ) {

			$ccount = ($comments_count && $showcount) ?  ' ['.$comments_count.']' : '';
			if ($textonly)
				$invite_return = $comments_invite.$ccount;
			else
			{
				if (!$comments_mode) {
					$invite_return = doTag($comments_invite, 'a', $class, ' href="'.permlinkurl($thisarticle).'#'.gTxt('comment').'" '). $ccount;
				} else {
					$invite_return = "<a href=\"".hu."?parentid=$thisid\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\"".(($class) ? ' class="'.$class.'"' : '').'>'.$comments_invite.'</a> '.$ccount;
				}
			}
			if ($wraptag) $invite_return = doTag($invite_return, $wraptag, $class);
		}

		return $invite_return;
	}

// -------------------------------------------------------------
	function contact_form($atts,$thing=NULL)
	{
		$atts['contact'] = 1;
		$atts['form'] = 'contact_form';
		
		return comments_form($atts,$thing);
	}
	
// -------------------------------------------------------------
// change: using comments as a contact form

	function comments_form($atts,$thing=NULL)
	{
		global $thisarticle, $has_comments_preview;

		extract(lAtts(array(
			'class'        => __FUNCTION__,
			'form'         => 'comment_form',
			'isize'        => '25',
			'msgcols'      => '25',
			'msgrows'      => '5',
			'msgstyle'     => '',
			'show_preview' => empty($has_comments_preview),
			'wraptag'      => '',
			'contact'	   => 0	
		), $atts));

		assert_article();

		extract($thisarticle);
		
		$out = '';
		$ip = serverset('REMOTE_ADDR');
		$blacklisted = is_blacklisted($ip);

		if (!checkCommentsAllowed($thisid)) {
			$out = graf(gTxt("comments_closed"), ' id="comments_closed"');
		} elseif (!checkBan($ip)) {
			$out = graf(gTxt('you_have_been_banned'), ' id="comments_banned"');
		} elseif ($blacklisted) {
			$out = graf(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), ' id="comments_blacklisted"');
		} elseif (gps('commented')!=='') {
			$out = gTxt("comment_posted");
			if (gps('commented')==='0')
				$out .= " ". gTxt("comment_moderated");
			$out = graf($out, ' id="txpCommentInputForm"');
		} else {
			# display a comment preview if required
			if (ps('preview') and $show_preview)
				$out = comments_preview(array());
			$out .= commentForm($thisid,$atts,$thing);
		}

		return (!$wraptag ? $out : doTag($out,$wraptag,$class) );
	}

// -------------------------------------------------------------

	function comment_subject_input($atts,$thing=NULL) {
		
		global $thisarticle;
		
		$atts['name']  = 'subject';
		$atts['other'] = 'subject_other';

		if ($input = custom_field_input($atts,$thing)) {
		
			return $input;
		}
		
		// if article is class form and has chidren option
		
		return fInput('text','subject','','text');
	}

// -------------------------------------------------------------

	function comment_custom_input($atts,$thing='') {
		
		global $thisarticle;
		
		extract(lAtts(array(
			'name'  => '',
			'title' => ''
		),$atts));
		
		$fields = getArticleCustomFields($thisarticle['parent'],$name);
		
		return displayArticleCustomFields($fields);
	}
	
// -------------------------------------------------------------

	function comments_error($atts)
	{
		extract(lAtts(array(
			'break'		=> 'br',
			'class'		=> __FUNCTION__,
			'wraptag'	=> 'div',
		), $atts));

		$evaluator =& get_comment_evaluator();

		$errors = $evaluator->get_result_message();

		if ($errors)
		{
			return doWrap($errors, $wraptag, $break, $class);
		}
	}

// -------------------------------------------------------------
	function if_comments_error($atts, $thing)
	{
		$evaluator =& get_comment_evaluator();
		return parse(EvalElse($thing,(count($evaluator -> get_result_message()) > 0)));
	}

// -------------------------------------------------------------
	function contact_success($atts,$thing='') {
		
		assert_article();
		
		if (ps('sent')) return parse($thing);
		
		return '';
	}

// -------------------------------------------------------------
	function contact_name($atts) 
	{
		assert_article();
		
		if ($id = ps('sent')) {
			$id = (preg_match('/^\d+$/',$id)) ? $id : 0;
			return fetch('name','txp_discuss','ID',$id);
		}
	}
	
// -------------------------------------------------------------
	# DEPRECATED - provided only for backwards compatibility
	# this functionality will be merged into comments_invite
	# no point in having two tags for one functionality
	function comments_annotateinvite($atts, $thing)
	{
		trigger_error(gTxt('deprecated_tag'), E_USER_NOTICE);

		global $thisarticle, $pretext;

		extract(lAtts(array(
			'class'		=> __FUNCTION__,
			'wraptag'	=> 'h3',
		),$atts));

		assert_article();

		extract($thisarticle);

		extract(
			safe_row(
				"Annotate,AnnotateInvite,unix_timestamp(Posted) as uPosted",
					"textpattern", 'ID = '.intval($thisid)
			)
		);

		if (!$thing)
			$thing = $AnnotateInvite;

		return (!$Annotate) ? '' : doTag($thing,$wraptag,$class,' id="'.gTxt('comment').'"');
	}

// -------------------------------------------------------------
// change: fetch only form type comment
// change: allow thing
// change: return comments only if there is a minimum or maximum 
//         total number of comments

	function comments($atts,$thing=NULL)
	{
		global $thisarticle, $prefs;
		extract($prefs);

		extract(lAtts(array(
			'form'       => 'comments',
			'wraptag'    => ($comments_are_ol ? 'ol' : ''),
			'break'      => ($comments_are_ol ? 'li' : 'div'),
			'class'      => __FUNCTION__,
			'breakclass' => '',
			'limit'      => 0,
			'offset'     => 0,
			'sort'       => 'posted ASC',
			'min'		 => 0,
			'max'		 => 0
		),$atts));

		assert_article();

		extract($thisarticle);

		if (!$comments_count) return '';

		$qparts = array(
			'article_id='.intval($thisid).' and Status=4',
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, 
			ID as discussid, 
			Body_html AS message, 
			Author AS name, 
			url AS web, 
			unix_timestamp(posted) AS time',
			'txp_discuss', join(' ', $qparts));
		
		$total = (($min or $max) and $limit)
				? getCount("txp_discuss","article_id='$id' and Status=".VISIBLE)
				: count($rs);
		
		if ($min and !$max and $total < $min) return '';
		if ($max and !$min and $total > $max) return '';
		if ($min and $max and $total < $min and $total > $max) return '';
		
		$form = ($thing) ? $thing : fetch_form($form,'comment');
		
		$out = '';

		if ($rs) {
			$comments = array();

			while($vars = nextRow($rs)) {
				$GLOBALS['thiscomment'] = $vars;
				$comments[] = parse($form).n;
				unset($GLOBALS['thiscomment']);
			}

			$out .= (!$thing) 
				? doWrap($comments,$wraptag,$break,$class,$breakclass)
				: implode(n,$comments);
		}

		return $out;
	}

// -------------------------------------------------------------
	function comments_preview($atts)
	{
		global $has_comments_preview;

		if (!ps('preview'))
			return;

		extract(lAtts(array(
			'form'		=> 'comments',
			'wraptag'	=> '',
			'class'		=> __FUNCTION__,
		),$atts));

		assert_article();

		$preview = psa(array('name','email','web','message','parentid','remember'));
		$preview['time'] = time();
		$preview['discussid'] = 0;
		$preview['name'] = strip_tags($preview['name']);
		$preview['email'] = clean_url($preview['email']);
		if ($preview['message'] == '')
		{
			$in = getComment();
			$preview['message'] = $in['message'];

		}
		$preview['message'] = markup_comment(substr(trim($preview['message']), 0, 65535)); // it is called 'message', not 'novel'
		$preview['web'] = clean_url($preview['web']);

		$GLOBALS['thiscomment'] = $preview;
		$comments = parse_form($form).n;
		unset($GLOBALS['thiscomment']);
		$out = doTag($comments,$wraptag,$class);

		# set a flag, to tell the comments_form tag that it doesn't have to show a preview
		$has_comments_preview = true;

		return $out;
	}

// -------------------------------------------------------------
	function if_comments_preview($atts, $thing)
	{
		return parse(EvalElse($thing, ps('preview') && checkCommentsAllowed(gps('parentid')) ));
	}

// -------------------------------------------------------------
	function comment_permlink($atts, $thing)
	{
		global $thisarticle, $thiscomment;

		assert_article();
		assert_comment();

		extract($thiscomment);
		extract(lAtts(array(
			'anchor' => empty($thiscomment['has_anchor_tag']),
		),$atts));

		$dlink = permlinkurl($thisarticle).'#c'.$discussid;

		$thing = parse($thing);

		$name = ($anchor ? ' id="c'.$discussid.'"' : '');

		return tag($thing,'a',' href="'.$dlink.'"'.$name);
	}

// -------------------------------------------------------------
	function comment_id()
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['discussid'];
	}

// -------------------------------------------------------------

	function comment_name($atts)
	{
		global $thiscomment, $prefs;

		assert_comment();

		extract($prefs);
		extract($thiscomment);

		extract(lAtts(array(
			'link' => 1,
		), $atts));

		$name = htmlspecialchars($name);

		if ($link)
		{
			$web      = str_replace('http://', '', $web);
			$nofollow = (@$comment_nofollow ? ' rel="nofollow"' : '');

			if ($web)
			{
				return '<a href="http://'.htmlspecialchars($web).'"'.$nofollow.'>'.$name.'</a>';
			}

			if ($email && !$never_display_email)
			{
				return '<a href="'.eE('mailto:'.$email).'"'.$nofollow.'>'.$name.'</a>';
			}
		}

		return $name;
	}

// -------------------------------------------------------------
	function comment_email()
	{
		global $thiscomment;

		assert_comment();

		return htmlspecialchars($thiscomment['email']);
	}

// -------------------------------------------------------------
	function comment_web()
	{
		global $thiscomment;

		assert_comment();

		return htmlspecialchars($thiscomment['web']);
	}

// -------------------------------------------------------------

	function comment_time($atts)
	{
		global $thiscomment, $comments_dateformat;

		assert_comment();

		extract(lAtts(array(
			'format' => $comments_dateformat,
			'gmt'    => '',
			'lang'   => '',
		), $atts));

		return safe_strftime($format, $thiscomment['time'], $gmt, $lang);
	}

// -------------------------------------------------------------
	function comment_message()
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['message'];
	}

// -------------------------------------------------------------
	function comment_anchor()
	{
		global $thiscomment;

		assert_comment();

		$thiscomment['has_anchor_tag'] = 1;
		return '<a id="c'.$thiscomment['discussid'].'"></a>';
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
		global $thisarticle, $article_stack, $prefs, $t;
		assert_article();
		
		extract(lAtts(array(
			'no_widow' => @$prefs['title_no_widow'],
			'stack'    => '',
			'split'    => ''
		), $atts));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($thisarticle) {
		
			$title = ($stack) 
				? $article_stack->get('title',$stack)
				: $thisarticle['title'];
		
		} elseif ($t) {
		
			$title = fetch("Title","textpattern","url_title",$t);
			
		} else {
			
			return '';
		}
			
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$title = escape_title($title);
			
		if ($split == 'word') {
				
			$title = explode(' ',$title);
			
			foreach($title as $key => $word) {
				$title[$key] = '<span class="word-'.($key+1).'" id="word-'.make_name($word).'">'.$word.'</span>';
			}
			
			$title = implode(' ',$title);
		}
		
		if ($no_widow)
			$title = noWidow($title);
			
		return $title;
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
			'textile' => ''
		),$atts));
		
		$article_stack->set('body_tag_encounter',true);
		
		$is_article_body = 1;
		$body = trim(parse($thisarticle['body']));
		$is_article_body = 0;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($thing and !$body) {
			return parse($thing);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (isset($atts['textile'])) {
			
			$thisid = $thisarticle['thisid'];
			
			if ($textile == '0') {
			
				$body = fetch('Body','textpattern','ID',$thisid);
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
		
		return $body;
	}

// -------------------------------------------------------------
	function excerpt()
	{
		global $thisarticle, $is_article_body, $article_stack;
		assert_article();
		
		$article_stack->set('body_tag_encounter',true);
		
		$is_article_body = 1;
		$out = parse($thisarticle['excerpt']);
		$is_article_body = 0;
		
		return trim($out);
	}

//--------------------------------------------------------------
// new
	
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
// if
	function if_body($atts, $thing = NULL)
	{
		global $thisarticle;
	    assert_article();
	    
		$body = trim($thisarticle['body']);
		
		return parse(EvalElse($thing, $body));
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

		if ($thisarticle['category1'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category1'];

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

		if ($thisarticle['category2'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category2'];

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

	function categories($atts)
	{
		global $thisarticle;
		static $titles = array();
		
		if (!strlen($thisarticle['categories'])) return '';
		
		$categories = explode(',',$thisarticle['categories']);
			
		extract(lAtts(array(
			'title' => 0,
			'sep'	=> ' '
		), $atts));
		
		if ($title) {
			
			foreach($categories as $key => $category) {
				
				if (!isset($titles[$category])) {
					$titles[$category] = safe_field("Title","txp_category","Name = '$category' AND Type = 'article'");
				}
				
				$categories[$key] = $titles[$category];
			}
		}
		
		return implode($sep,$categories);
	}

// -------------------------------------------------------------

	function category($atts, $thing = NULL)
	{
		global $s, $c, $thiscategory;

		extract(lAtts(array(
			'class'        => '',
			'link'         => 0,
			'name'         => '',
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
			$label = htmlspecialchars( ($title) ? fetch_category_title($category, $type) : $category );

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

		return htmlspecialchars($thisarticle['keywords']);
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
	function search_result_title($atts)
	{
		return permlink($atts, '<txp:title />');
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
			'sort'     => 'name ASC',
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

// -------------------------------------------------------------
	function if_comments($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, ($thisarticle['comments_count'] > 0)));
	}

// -------------------------------------------------------------
	function if_comments_allowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, checkCommentsAllowed($thisarticle['thisid'])));
	}

// -------------------------------------------------------------
	function if_comments_disallowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, !checkCommentsAllowed($thisarticle['thisid'])));
	}

// -------------------------------------------------------------
// change: use pretext id for test

	function if_individual_article($atts, $thing)
	{
		global $is_article_list, $id;
		return parse(EvalElse($thing, ($is_article_list == false or $id)));
	}

// -------------------------------------------------------------
	function if_article_list($atts, $thing)
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == true)));
	}

// -------------------------------------------------------------------------------------
// new

	function if_alias($atts, $thing) {
	
		global $thisarticle;
		assert_article();
		
		return parse(EvalElse($thing,$thisarticle['alias']));
	}
	
// -------------------------------------------------------------
	function meta_keywords()
	{
		global $id_keywords;
		return ($id_keywords)
		?	'<meta name="keywords" content="'.htmlspecialchars($id_keywords).'" />'
		:	'';
	}

// -------------------------------------------------------------
	function meta_author()
	{
		global $id_author;
		return ($id_author)
		?	'<meta name="author" content="'.htmlspecialchars($id_author).'" />'
		:	'';
	}

// -------------------------------------------------------------

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

// -------------------------------------------------------------

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

// -------------------------------------------------------------
	function doLabel($label='', $labeltag='')
	{
		if ($label) {
			return (empty($labeltag)? $label.'<br />' : tag($label, $labeltag));
		}
		return '';
	}

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
				$out = hu."$url_title.html";
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

	function path_tag($atts) 
	{
		global $pretext,$thisarticle;
		
		extract(lAtts(array(
			'mode' => 'page',
			'sep'  => '/'
		),$atts));
		
		if ($mode == 'req') {
			
			return $pretext['path'];
		}
		
		if ($mode == 'page') {
			
			assert_article();
			
			$path = explode('/',$thisarticle['path']);
			
			if (SITE_ID != ROOTNODEID) array_shift($path);
		}
		
		if ($mode == 'article') {
			
			assert_article();
			
			$path = explode('/',$thisarticle['path']);
			
			array_pop($path);
			
			if (SITE_ID != ROOTNODEID) array_shift($path);
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
// new

	function level($atts='')
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

// -------------------------------------------------------------------------------------

	function article_level($atts='')
	{
		global $thisarticle;
		
		assert_article();
		
		return $thisarticle['level'];
	}

//------------------------------------------------------------------------

	function if_article_level($atts, $thing=NULL)
	{
		global $thisarticle;
		
		extract(lAtts(array(
			'num' => 0,
		),$atts));
		
		$test = evalAtt($thisarticle['level'],$num);
		
		return parse(EvalElse($thing, $test));
	}

//--------------------------------------------------------------------------------------
// new

	function if_current_article($atts, $thing)
	{
		global $id,$thisarticle;
		assert_article();
		
		$thisid = $thisarticle['thisid'];
		
		$condition = ($id == $thisid) ? true : false;
		 
		return parse(EvalElse($thing, $condition));
	}
	
//------------------------------------------------------------------------

	function if_excerpt($atts, $thing)
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

//--------------------------------------------------------------------------
// TODO: parent custom field

	function custom_field($atts)
	{
		global $thisarticle, $prefs;
		assert_article();
		
		static $custom_field_options = array();
		
		extract(lAtts(array(
			'name'    => '',
			'escape'  => 'html',
			'default' => '',
			'format'  => '',
			'wraptag' => '',
			'add'	  => 0,
			'parent'  => 0
		),$atts));

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$out           = array($default);
		$name          = strtolower($name);
		$thisid        = $thisarticle['thisid'];
		$custom_field  = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($parent) {
			
			$custom_field = array(); // TODO
		
		} elseif (isset($thisarticle['custom_fields'][$name])) {
			
			$custom_field = $thisarticle['custom_fields'][$name];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach ($custom_field as $key => $field) {
			
			if ($value = $field['value']) { 
				
				$out[$key] = $value;
				
				$info = explode(':',$field['info']);
					
				if ($info[1] == 'select' and $info[2] == 1) {
					
					extract(safe_row("field_id,group_id","txp_content_value","article_id = $thisid AND field_name = '$name'"));
					
					if (isset($custom_field_options[$group_id.'_'.$field_id])) {
						
						$options = $custom_field_options[$group_id.'_'.$field_id];
					
					} else {	
						
						$options = explode(',',fetch("options","txp_custom","ID",$field_id));
					
						foreach($options as $key => $option) {
							list($val,$label) = explode(':',$option);
							$options[trim($val)] = trim($label);
							unset($options[$key]);
						}
						
						$custom_field_options[$group_id.'_'.$field_id] = $options;
					}
					
					if (isset($options[$value])) $out[$key] = $options[$value];
				} 
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach($out as $key => $value) {
		
			if ($value or $value === 0 or $value === '0') {
			
				if ($add) {
					
					$value = $value + $add;
				}
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				if ($format == 'link') 
					$value = htmlentities($value);
					
				if ($format == 'url') 
					$value = make_name($value);
					
				if ($format == 'textile') {
					include_once txpath.'/lib/classTextile_mod.php';
					$textile = new TextileMod();
					$value = $textile->TextileThis($value);
				}
				
				if ($format != '') {
					if (preg_match('/\d\d\d\d\/\d\d\/\d\d/',$date = substr($value,0,10)))
						$value = date($format,strtotime($date));
					if (preg_match('/\d\d:\d\d/',$time = substr($value,0,5)))
						$value = date($format,strtotime($time));
				}
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				$value = trim($value);
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				if ($wraptag == 'a') {
					
					$href  = (!preg_match('/^(\/|http)/',$value)) ? 'http://'.$value : $value;
					$value = preg_replace('/^http:\/\//','',$value);
					$value = '<a href="'.$href.'">'.$value.'</a>';
				}
				
				if ($wraptag == 'youtube') {
					
					$youtube = new Element;
					
					$value = $youtube->replace(array('','www.youtube.com',$value));
				}
				
				if (in_list($wraptag,'ul,ol')) {
					
					$value = tag($value,'li');
				}
				
				// - - - - - - - - - - - - - - - - - - - - - -
				
				$out[$key] = $value;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (in_list($wraptag,'ul,ol')) {
		
			$out = tag(implode(n,$out),$wraptag);
			$escape = '';
			
		} else {
		
			$out = implode(', ',$out);
		}
				
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return ($escape == 'html' ? htmlspecialchars($out) : $out);
	}

//--------------------------------------------------------------------------
	function if_custom_field($atts, $thing=NULL)
	{
		global $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'name'  => '',
			'value' => '',	// deprecated, use test
			'test'  => '',
		),$atts));
		
		$test   = (strlen($value)) ? $value : $test;
		$name   = strtolower($name);
		$thisid = $thisarticle['thisid'];
		$result = false;
		$values = array();
		
		if (isset($thisarticle['custom_fields'][$name])) {
			
			if (!isset($atts['test'])) {
			
				$result = true;
			
			} else {
				
				foreach ($thisarticle['custom_fields'][$name] as $field) {
					
					$values[] = $field['value'];
					
					if ($test == '*') {
					
						if (strlen(impl($values))) $result = true;
					
					} elseif (!in_list($test,"NONE,!*")) {	
					
						if (evalAtt($field['value'],$test) == true) {
							$result = true;
						}
					}
				}
				
				if (in_list($test,"NONE,!*")) {
				
					 if (strlen(impl($values)) == 0) $result = true;
				}
			}
		}
		
		return parse(EvalElse($thing, $result));
	}

// -------------------------------------------------------------

	function custom_field_input($atts,$thing=NULL) {
		
		global $thisarticle,$article_stack;
		assert_article();
		
		extract(lAtts(array(
			'name'  => '',
			'other' => ''
		),$atts));
		
		$thisid        = $thisarticle['thisid'];
		$custom_fields = $thisarticle['custom_fields'];
		$custom_field  = null;
		
		if (isset($custom_fields[$name])) {
		
			$custom_field = $custom_fields[$name][0]['info'];
		
		} else {
			
			$thisid = $article_stack->get("thisid",'..'); 
			$custom_field = $article_stack->get("custom_fields/$name/0/info",'..'); 
		}
		
		if ($custom_field) {
			
			$value = ps($name);
			$info  = explode(':',$custom_field);
			$type  = $info[1];
			
			if ($type == 'select') {
				
				$other = ps($other);
				$labels = $info[2];
				
				extract(safe_row("field_id,group_id","txp_content_value","article_id = $thisid AND field_name = '$name'"));
				
				$options = explode(',',fetch("options","txp_custom","ID",$field_id));
				
				// NOTE: This should be class == 'form' instead!
				// TODO: Add 'class' to $thisarticle array
				
				if ($thisarticle['category1'] == 'form') {
				
					$rows = safe_column(
						"Position,Title",
						"textpattern",
						"ParentID = ".$thisarticle['thisid'].
						" AND Class = 'option'".
						" AND Status IN (4,5)".
						" AND Trash = 0".
						" ORDER BY Position ASC",1);
					
					if (count($rows)) {
						$options = $rows;
					}
				}
				
				foreach($options as $key => $option) {
				
					if ($labels) {
						list($val,$label) = explode(':',$option);
						$options[trim($val)] = trim($label);
					} else {
						$options[trim($option)] = trim($option);
					}
					
					unset($options[$key]);
				}
				
				return 
				 selectInput('custom_'.$name, $options, $value)
				.selectInputOther('custom_'.$name, $options , $other);
			}
			
			if ($type == 'textfield') {
				
				return fInput('text','custom_'.$name,$value,'text');
			}
		}
		
		return '';
	}

//--------------------------------------------------------------------------
/*	function custom_field($atts)
	{
		global $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'name' => @$prefs['custom_1_set'],
			'escape' => 'html',
			'default' => '',
		),$atts));

		$name = strtolower($name);
		if (!empty($thisarticle[$name]))
			$out = $thisarticle[$name];
		else
			$out = $default;

		return ($escape == 'html' ? htmlspecialchars($out) : $out);
	}
*/
//--------------------------------------------------------------------------
/*	function if_custom_field($atts, $thing)
	{
		global $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'name' => @$prefs['custom_1_set'],
			'val' => NULL,
		),$atts));

		$name = strtolower($name);
		if ($val !== NULL)
			$cond = (@$thisarticle[$name] == $val);
		else
			$cond = !empty($thisarticle[$name]);

		return parse(EvalElse($thing, $cond));
	}
*/
// -------------------------------------------------------------
	function site_url()
	{
		return hu;
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

// -------------------------------------------------------------
	function error_message()
	{
		return @$GLOBALS['txp_error_message'];
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
	function if_first_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_last'])));
	}

// -------------------------------------------------------------
// new

	function if_not_first_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, empty($thisarticle['is_first'])));
	}

// -------------------------------------------------------------
// new

	function if_not_last_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, empty($thisarticle['is_last'])));
	}

// -------------------------------------------------------------
// new

	function article_pos() 
	{
		global $thisarticle; 
		assert_article();
		return ($thisarticle['position']) ? $thisarticle['position'] : '1';
	}

// -------------------------------------------------------------
// new
	
	function if_article_pos($atts,$thing = NULL) 
	{
		extract(lAtts(array(
			'lt' => '',
			'gt' => '',
			'eq' => '',
		),$atts));
		
		$num = article_pos();
		
		if ($lt) $test = ($num < $lt);
		if ($gt) $test = ($num > $gt);
		if ($eq) $test = ($num == $eq);
		if ($lt && $gt) $test = (($num < $lt) && ($num > $gt));
		
		return parse(EvalElse($thing, $test));
	}

// -------------------------------------------------------------
// aliases for article count and total functions 	

	function article_num($atts) 
	{
		return article_count($atts);
	}
	
	function if_article_num($atts,$thing = NULL) 
	{
		return if_article_count($atts);
	}
	
	function if_article_count($atts,$thing = NULL) 
	{
		return article_count($atts,$thing);
	}
	
	function if_article_total($atts,$thing = NULL) 
	{
		return article_total($atts,$thing);
	}

// -------------------------------------------------------------
// new 	

	function article_count($atts,$thing = NULL) 
	{
		global $thisarticle, $is_article_list;
		assert_article();
		
		extract(lAtts(array(
			'pad' => '',
			'lt'  => '',
			'gt'  => '',
			'eq'  => '',
			'mod' => ''
		),$atts));
		
		$num = ($is_article_list) ? $thisarticle['count'] : 1;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if ($thing) {
			
			$test = ($count > 0);
		
			if ($lt)  $test = ($num < $lt);
			if ($gt)  $test = ($num > $gt);
			if ($eq)  $test = ($num == $eq);
			if ($mod) $test = ($num % $mod);
			if ($lt && $gt) $test = (($num < $lt) && ($num > $gt));
			
			return parse(EvalElse($thing, $test));
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if (is_numeric($pad) and $pad > 0) {
		
			$padsize = $pad;
			$padchar = ' ';
		
		} elseif ($pad) {
		
			$padsize = strlen($pad);
			$padchar = substr($pad,0,1);
		
		} else {
		
			return $num;
		}
		
		$num = str_pad($num,$padsize, $padchar, STR_PAD_LEFT);
		
		return ($padchar == 'o') ? preg_replace('/0/','o',$num) : $num;
	}
	
// -------------------------------------------------------------
// new 	

	function article_total($atts,$thing = NULL) 
	{
		global $thisarticle;
		assert_article();
		
		extract(lAtts(array(
			'lt' => '',
			'gt' => '',
			'eq' => '',
		),$atts));
		
		$num = $thisarticle['total'];
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		if ($thing) {
		
			$test = ($num > 0);
			
			if ($lt) $test = ($num < $lt);
			if ($gt) $test = ($num > $gt);
			if ($eq) $test = ($num == $eq);
			if ($lt && $gt) $test = (($num < $lt) && ($num > $gt));
			
			return parse(EvalElse($thing, $test));
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		return $num;
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
			$where[] = 'ID = '.intval($id);
		}

		elseif ($filename)
		{
			$where[] = "Name = '".doSlash($filename)."'";
		}

		else
		{
			if (empty($thisfile) and isset($thisarticle['file_id'])) {
			
				$where[] = 'ID = '.$thisarticle['file_id'];
				
				if ($type) 
					$where[] = "type IN ('".join("','", doSlash(do_list($type)))."')";
		
				if ($ext) 
					$where[] = "ext IN ('".join("','", doSlash(do_list($ext)))."')";
				
			} else {
			
				assert_file();

				$from_form = true;
			}
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

//-------------------------------------------------------------------------
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

//--------------------------------------------------------------------------

	function file_download_id()
	{
		global $thisfile;
		assert_file();
		return $thisfile['ID'];
	}

//--------------------------------------------------------------------------

	function file_download_src($atts)
	{
		global $thisfile, $file_base_path, $path_to_site, $sitedir;
		assert_file();
		
		extract(lAtts(array(
			'ext' => trim($thisfile['ext'],'.')
		),$atts));
		
		$files = trim(str_replace($path_to_site,'',$file_base_path),'/');
		$path  = get_file_id_path($thisfile['FileID']);
		
		return '/'.$sitedir.$files.'/'.$path.'/'.$thisfile['Name'].'.'.$ext;
	}
	
//--------------------------------------------------------------------------

	function file_download_name($atts)
	{
		global $thisfile;
		assert_file();
		
		extract(lAtts(array(
			'ext' => '1'
		),$atts));
		
		if (isset($atts['ext'])) {
			
			if ($ext == '0') return $thisfile['Name'];		
			
			if ($ext == '1') return $thisfile['Name'].$thisfile['ext'];
			
			if (preg_match('/^[a-z0-9]+$/',$ext)) {
			
				return $thisfile['Name'].'.'.$ext;
			}
		}
		
		return $thisfile['Name'].$thisfile['ext'];
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

// -------------------------------------------------------------

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
			
			$filename = $file_base_path.'/'.$path.'/'.get_file_name($thisfile['Name']);
			
			foreach($list as $ext) {
				$test = is_file($filename.'.'.$ext) or $test;
			}
		}
			
		return parse(EvalElse($thing, $test));
	} 

// -------------------------------------------------------------

	function if_file_download_type($atts,$thing = NULL)
	{
		global $thisfile;
		assert_file();
		
		extract(lAtts(array(
			'value' => ''
		),$atts));
		
		return parse(EvalElse($thing, $thisfile['type'] == $value));
	}

// -------------------------------------------------------------

	function if_file_download_audio($atts,$thing = NULL)
	{
		global $thisfile;
		assert_file();
		
		return parse(EvalElse($thing, $thisfile['type'] == 'audio'));
	}
	
// -------------------------------------------------------------

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
	
// -------------------------------------------------------------

	function hide()
	{
		return '';
	}

// -------------------------------------------------------------

	function rsd()
	{
		global $prefs;
		return ($prefs['enable_xmlrpc_server']) ? '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.hu.'rpc/" />' : '';
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
			'default' => ''
		), $atts));

		$thing = trim($thing);
		
		if (empty($name))
		{
			trigger_error(gTxt('variable_name_empty'));
			return;
		}
		
		if (!isset($atts['value']) and empty($thing))
		{
			if (isset($variable[$name])) 
				return $variable[$name];
			
			if (isset($pretext[$name])) 
				return $pretext[$name];
				
			if (isset($thisarticle[$name])) 
				return $thisarticle[$name];
				
			return 'undefined';
		}
		
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
						
						return '';
					}
					
				} else {
					
					if (preg_match('/^\+\+$/',$value)) {
						
						$variable[$name] += 1;
						
						return '';
					}
					
					if (preg_match('/^\-\-$/',$value)) {
						
						$variable[$name] -= 1;
						
						return '';
					}
				}
			} else {
				
				if (preg_match('/^\+./',$value)) {
					
					$variable[$name] .= ltrim($value,'+');
						
					return '';
				}
			}
		}
			
		if (!$value and $default) {
			
			$variable[$name] = $default;
		
		} else {
		
			$variable[$name] = $value;
		}
	}

// -------------------------------------------------------------
	function if_variable($atts, $thing = NULL)
	{
		global $variable, $pretext, $txptrace, $dump;
		
		$dump[]['h2'] = htmlentities(ltrim(end($txptrace)));
		
		extract(lAtts(array(
			'name'	=> '',
			'value'	=> ''
		), $atts));
		
		if (empty($name)) {
			trigger_error(gTxt('variable_name_empty'));
			return;
		}
		
		$var = NULL;
		
		if (isset($variable[$name])) {
			
			$var = $variable[$name];
			
		} elseif (isset($pretext[$name])) {
		
			$var = $pretext[$name];
		}
		
		if (isset($atts['value'])) {
			
			$test = evalAtt($var,$value);
			
		} else {
			
			$test = ($var and $var != '*') ? true : false;
		}
		
		return parse(EvalElse($thing, $test));
	}

// -------------------------------------------------------------
// adds a single item to the array

	function item($atts,$thing = NULL) {
		
		global $item_array;
		
		extract(lAtts(array(
			'name'	 => 'items',
			'value'	 => '',
			'joiner' => '' 
		), $atts));
		
		$thing = parse($thing);
		$value = ($thing) ? $thing : $value;
		
		if (!is_array($item_array)) {
			
			$item_array = array();
		}
		
		if (!isset($item_array[$name])) {
			
			$item_array[$name] = array( 
				'joiner' => $joiner,
				'items'  => array()
			);
		}
		
		if (!empty($value)) {
		
			$item_array[$name]['items'][] = $value;
		}
	}

// -------------------------------------------------------------
// if array exists add the item to the array
// 		otherwise add the item as the joiner
// if there is no item to add then join all items

	function items($atts,$thing = NULL) {
		
		global $item_array;
		
		extract(lAtts(array(
			'name'	=> 'items',
			'value'	=> ''
		), $atts));
		
		if ($value or $thing) {
			
			if (!is_array($item_array) or !isset($item_array[$name])) {
				
				$thing = parse($thing);
				
				$atts['joiner'] = ($thing) ? $thing : $value;
				$atts['value']  = '';
				
				item($atts);
			
			} else {
			
				item($atts,$thing);
			}
		
		} else {
			
			return join_items($atts);
		}
	}
	
// -------------------------------------------------------------
// join all items in the array
// if no joiner is given then use the one in the array if any

	function join_items($atts,$thing = NULL) {
		
		global $item_array;
		
		extract(lAtts(array(
			'name'	 => 'items',
			'joiner' => ''
		), $atts));
		
		if (is_array($item_array) and isset($item_array[$name])) {
			
			$thing = parse($thing);
			$joiner = ($thing) ? $thing : $joiner;
			
			if ($joiner) {
				
				$item_array[$name]['joiner'] = $joiner;
				
			} else {
			
				$joiner = $item_array[$name]['joiner'];
			}
			
			return n.implode(n.$joiner.n,$item_array[$name]['items']).n;
		}
	}
	
//------------------------------------------------------------------------
// new

	function if_user_agent($atts, $thing) 
	{
		extract(lAtts(array(
			'agents'  => 'other'
		),$atts));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get agent candidates
		
		$agents = explode(',',strtolower(preg_replace('/\s+/',' ',$agents)));
		if (count($agents) == 1) $agents = explode(' or ',$agents[0]);
		
		$list = array();
		
		foreach($agents as $key => $value) {
			
			$value = explode(' ',trim($value));
			
			$list[$key]['name']     = $value[0];
			$list[$key]['version']  = 0;
			$list[$key]['range']    = 'gte';
			
			if (isset($value[2])) {
				
				$list[$key]['version'] = $value[2];
				$list[$key]['range']   = $value[1];
			
			} elseif (isset($value[1])) {
			
				$list[$key]['version'] = $value[1];
			}
		}
		
		$agents = $list;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get this agent name & version
		
		$htp_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$this_agent = array();
		
		if (strpos($htp_user_agent,'msie') == true)		$this_agent['msie'] = 0;
		if (strpos($htp_user_agent,'chrome') == true)	$this_agent['chrome'] = 0;
		if (strpos($htp_user_agent,'safari') == true)	$this_agent['safari'] = 0;
		if (strpos($htp_user_agent,'webkit') == true)	$this_agent['webkit'] = 0;
		if (strpos($htp_user_agent,'opera') == true)	$this_agent['opera'] = 0;
		if (strpos($htp_user_agent,'firefox') == true)	$this_agent['firefox'] = 0;
		if (strpos($htp_user_agent,'netscape') == true)	$this_agent['netscape'] = 0;
		if (strpos($htp_user_agent,'mozilla') == true)	$this_agent['mozilla'] = 0;
		
		// get this agent version
		
		foreach ($this_agent as $name => $version) {
			
			if ($name == 'safari') {
				$pattern = "/version\/(\d+\.\d+)/";
			} else {
				$pattern = "/".$name."[\s\/](\d+\.\d+)/";
			}
			
			if (preg_match($pattern,$htp_user_agent,$matches)) {
				
				$this_agent[$name] = $matches[1];
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$test = false;
		
		while (!$test and count($agents)) {
		
			extract(array_shift($agents));
			
			if (isset($this_agent[$name]) and !$test) {
			
				switch ($range) {
				
					case 'gte' : if ($this_agent[$name] >= $version) $test = true; break;
					case 'gt'  : if ($this_agent[$name] >  $version) $test = true; break;
					case 'lte' : if ($this_agent[$name] <= $version) $test = true; break;
					case 'lt'  : if ($this_agent[$name] <  $version) $test = true; break;
					case 'eq'  : if ($this_agent[$name] == $version) $test = true; break;
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		return parse(EvalElse($thing, $test));
	}

//------------------------------------------------------------------------
// new - search and replace using regular exppressions

	function replace($atts, $thing)
	{
		extract(lAtts(array(
			's'  => '',			// search string
			'r'  => '',			// replace string
			's2' => '',
			'r2' => '',
			's3' => '',
			'r3' => ''
		),$atts));
		
		$source = trim(parse($thing));
		
		$s   = preg_replace('/\//', '\/', $s);
		$out = preg_replace('/'.$s.'/', $r, $source);
		
		if ($s2 and $out) { 
			$s2  = preg_replace('/\//', '\/', $s2);
			$out = preg_replace('/'.$s2.'/', $r2, $out);
		}
		
		if ($s3 and $out) {
			$s3  = preg_replace('/\//', '\/', $s3);
			$out = preg_replace('/'.$s3.'/', $r3, $out);
		}
		
		return $out;
	}

// -------------------------------------------------------------
// new
	
	function url_encode($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		
		extract(lAtts(array(
			'to'   => 'UTF-16',
			'from' => 'UTF-8'
		),$atts));
		
		$text = trim(parse($thing));
		
		$text = mb_convert_encoding($text, $to, $from);
		$text = urlencode($text);
					
		return $text;         
	}     
	
//------------------------------------------------------------------------
// new

	function strip_space($atts, $thing)
	{	
		$thing = preg_replace('/^\s+|\s+$/','',parse($thing));
		return preg_replace('/\s\s+/',' ',$thing);
	}

// -------------------------------------------------------------------------------------
// new

	function table_row($atts) 
	{
		global $thisarticle;
		assert_article();;
		
		extract(lAtts(array(
			'col' => '5'
		),$atts));
		
		return ($thisarticle['count'] % $col == 0) ? '</tr><tr>' : '';
	}

//------------------------------------------------------------------------
// new

	function n($atts)
	{	
		return n;
	}

//------------------------------------------------------------------------
// new

	function line($atts)
	{	
		return n.n."<!-- ".str_pad('', 120, "- ")."-->".n.n;
	}

// -------------------------------------------------------------------------------------
	
	function random($atts)
	{
		extract(lAtts(array(
			'min' => 100000,
			'max' => 999999
		),$atts));
		
		return rand($min,$max);
	}

// -------------------------------------------------------------
// NOTE: This does not really need its own tag.
//		 Can use <txp:var name="lg"> instead

	function language($atts) {
		
		global $lg;
		
		return $lg;
	}

// -------------------------------------------------------------
// NOTE: This does not really need its own tag.
//		 Can use <txp:if_var name="lg" value="en"> instead

	function if_language($atts, $thing = NULL) {
		
		global $lg;
		
		extract(lAtts(array(
			'name'	=> '',
		), $atts));
		
		$test = (!$name) ? $lg : $name == $lg;
		
		return parse(EvalElse($thing, $test));
	}
?>

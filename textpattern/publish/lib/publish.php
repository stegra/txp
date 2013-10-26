<?php

	include txpath.'/publish/taghandlers.php';
	include txpath.'/publish/lib/publish_query_builder.php';

	function doArticles($atts, $iscustom, $thing = NULL) {
		
		global $PFX, $pretext, $prefs, $thisarticle, $thispage, $thiscategory, 
			$article_stack, $txptrace, $txptagtrace, $txp_current_atts;
		
		$thisid = $thisarticle['thisid'];
		
		extract($pretext);
		extract($prefs);
		
		static $run = 1;
		
		if (isset($atts['debug']) and $atts['debug']) {
			inspect(htmlentities(ltrim(end($txptrace))),'h2','doArticles($run)');
		}
		
		getmicrotime('do_articles');
		
		$all_custom_fields = getCustomFields();
		
		$regular_atts = array(
			'form'      => 'default',
			'listform'  => '',
			'noneform'	=> '',
			'searchform'=> '',
			'limit'     => 10,
			'offset'    => 0,
			'pageby'    => '',
			'page'		=> '', 		// page offset in conjunction with pageby
			'category'  => '',
			'category1' => '',		// unused
			'category2' => '',		// unused
			'site'		=> '',		// override current site, or select from all sites
			'ignore'	=> '',		// for ignore get/post variables (unused)
			'min'		=> 0,		// return articles if more than min number found
			'max'		=> 0,		// return articles if less than max number found
			'debug'		=> 0,		// show sql
			'random'	=> 0,		// number of articles to select randomly
			'level'		=> '',		// hirarchy level
			'position'	=> '',		
			'path'    	=> '',		
			'class'    	=> '',		
			'name'		=> '',		// new find by name only
			'groupby'   => '',		// new
			'myname'	=> '',		// name of this article tag we can use to refer to from nested article tags
			'cache'	    => '',		// name of this article for caching
			'after'		=> 0,	 	// return a number of articles after article $id
			'before'	=> 0,		// return a number of articles before article $id
			'loop'		=> 0,		// loop around from last to first in one direction (1) or both (2)
			'image'		=> '',		// return only articles with or without an image
			'file'		=> '',		// return only articles with or without files
			'children'	=> '',		// return only articles that have specified number of children
			'alias'		=> '',		// return articles that are aliases
			'parent'	=> '',		// to get child articles
			'return'	=> '',		// return a specific value instead of the whole article
			'section'   => '',		// unused
			'table'		=> '',		// select items from other content tables
			'search'	=> 0,		// do search from query
			'excerpted' => '',
			'author'    => '',
			'sort'      => '',
			'sortby'    => '', 		// deprecated in 4.0.4
			'sortdir'   => '', 		// deprecated in 4.0.4
			'month'     => '',
			'keywords'  => '',
			'frontpage' => '',
			'id'        => '',
			'time'      => 'past',
			'status'    => '4',
			'pgonly'    => 0,
			'searchall' => 1,
			'searchsticky'  => 0,
			'allowoverride' => (!$q),
			'wraptag'	=> '',
			'break'		=> '',
			'label'		=> '',
			'labeltag'	=> ''
		); // end of atts
		
		$theAtts = lAtts($regular_atts + $all_custom_fields, $atts);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// if an article ID is specified, treat it as a custom list
		$iscustom = (!empty($theAtts['id'])) ? true : $iscustom;
		
		//for the txp:article tag, some attributes are taken from globals;
		//override them before extract
		if (!$iscustom) {	
			// $theAtts['path']  = ($unique) ? '/'.$path : '';
			// $theAtts['path']  = ($path) ? '/'.$path : $theAtts['path'];
			// $theAtts['type']  = ($t)  ? $t  : $theAtts['type'];
			$theAtts['class']    = (!$theAtts['class'] && $cl)    ? $cl : $theAtts['class'];
			$theAtts['category'] = (!$theAtts['category'] && $c)  ? $c  : $theAtts['category'];
			
			// $theAtts['section']   = ($s && $s!='default')? $s : '';
			// $theAtts['author']    = (!empty($author)? $author: '');
			// $theAtts['month']     = (!empty($month)? $month: '');
			// $theAtts['frontpage'] = ($s && $s=='default')? true: false;
			// $theAtts['excerpted'] = '';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($theAtts['ignore']) {
			
			$ignored = do_list($theAtts['ignore']);
			$ignored = array_flip($ignored);
			
			// if (isset($ignored['id']))  { $ignored['id']  = $id; 	$id  = ''; }
			// if (isset($ignored['c']))   { $ignored['c']   = $c;  	$c   = ''; }
			// if (isset($ignored['t']))   { $ignored['t']   = $t;  	$t   = ''; }
			// if (isset($ignored['pg']))  { $ignored['pg']  = $pg; 	$pg  = 1;  }
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		extract($theAtts);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$custom_fields = array();
		
		$parent_atts = array(
			'id'	 => 0,
			'status' => null,
			'class'  => null,
			'name'   => null
		);
		
		foreach ($theAtts as $key => $value) {
			
			if (!isset($regular_atts[$key])) {
				
				$parent = '';
				
				if (substr($key,0,7) == 'parent.') {
					
					$parent = 'parent.';
					$key = substr($key,7);
					
					$parent_atts[$key] = $value;
				}
				
				if (array_key_exists($key, $all_custom_fields)) {
					
					if (array_key_exists($parent.$key, $atts)) {
						
						$key = str_replace('custom.','',$key);
						
						$custom_fields[$parent.$key] = $value;
					
					} else {
						
						$key = str_replace('custom.','',$key);
						
						if (array_key_exists($parent.$key, $atts) and !isset($regular_atts[$key])) {
							
							$custom_fields[$parent.$key] = $value;
						}
					}
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($cache) {
			
			if ($articles = trycache($cache)) {
			
				inspect("cache: $cache",'line','doArticles($run)');
				
				return $articles;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		/*
		if ($site) {
						
			if ($site == '*')
				$SITE['id'] = 0;
			else
				$SITE['id'] = safe_field("ID","textpattern",
				"Name = '$site' AND Class = 'site' AND Status = 4 AND Trash = 0 AND ParentID != 0");
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// treat sticky articles differently wrt search filtering, etc
		
		if ($status == '*') 
			$status = "4,5";
		else
			$status = in_array(strtolower($status), array('sticky', '5')) ? '5' : '4';
		
		$issticky = ($status == 5);
		
		if (PREVIEW) {
			
			switch ($status) {
				case '4' : $status = '3,4'; break;
				case '5' : $status = '5,7'; break;
				default  : $status = '3,4,5,7';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// TABLE 
		
		if ($table) {
			
			$table = (in_list($table,'image,file,link,category,discuss,custom,page,form,css,site')) 
				? "txp_".$table 
				: "textpattern";
				
		} else {
			
			$table = (isset($thisarticle)) ? $thisarticle['table'] : 'textpattern';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// SEARCH
		
		// if ($q && !$iscustom && !$issticky)
		// {
			/* include_once txpath.'/publish/search.php';

			$s_filter = ($searchall ? filterSearch() : '');
			$q = doSlash($q);

            		// searchable article fields are limited to the columns of
            		// the textpattern table and a matching fulltext index must exist.
			$cols = do_list($searchable_article_fields);
			if (empty($cols) or $cols[0] == '') $cols = array('Title', 'Body');

			$match = ', match (`'.join('`, `', $cols)."`) against ('$q') as score";
			for ($i = 0; $i < count($cols); $i++)
			{
				$cols[$i] = "`$cols[$i]` rlike '$q'";
			}
			$cols = join(" or ", $cols);
			$search = " and ($cols) $s_filter";

			// searchall=0 can be used to show search results for the current section only
			if ($searchall) $section = '';
			if (!$sort) $sort = 'score desc'; */
		// }
		// else {
			
			// $match = $search = '';
			
			// if (!$sort) $sort = 'Posted DESC';
		// }
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Building query parts
		
		$sql = array();
		
		$sql['SELECT']   = array();
		$sql['FROM']     = array();
		$sql['WHERE']    = array();
		$sql['GROUP BY'] = array();
		$sql['ORDER BY'] = array();
		$sql['LIMIT']    = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - -
		
		$tables  = array($table);
		$columns = array('t.*','t.ID',
			'unix_timestamp(t.Posted) AS uPosted',
			'unix_timestamp(t.Expires) AS uExpires',
			'unix_timestamp(t.LastMod) AS uLastMod'
		);
		$where   = array();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// WHERE
		/*
		$frontpage = ($frontpage and (!$q or $issticky)) ? filterFrontPage() : '';
		$category  = join("','", doSlash(do_list($category)));
		$category  = (!$category)  ? '' : " and (Category1 IN ('".$category."') or Category2 IN ('".$category."'))";
		$section   = (!$section)   ? '' : " and Section IN ('".join("','", doSlash(do_list($section)))."')";
		$excerpted = ($excerpted=='y')  ? " and Excerpt !=''" : '';
		$author    = (!$author)    ? '' : " and AuthorID IN ('".join("','", doSlash(do_list($author)))."')";
		$month     = (!$month)     ? '' : " and Posted like '".doSlash($month)."%'";
		$id        = (!$id)        ? '' : " and ID IN (".join(',', array_map('intval', do_list($id))).")";
		*/
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Search
		
		if ($search and $q) {
			
			// include_once txpath.'/publish/search.php';
			
			$q = doSlash($q);
			
			$cols = array('t.Title','t.Body');

			$columns[] = 'MATCH ('.join(', ', $cols).") AGAINST ('$q') AS score";
			for ($i = 0; $i < count($cols); $i++)
			{
				$cols[$i] = "$cols[$i] RLIKE '$q'";
			}
			
			$where['search'] = '('.join(" OR ", $cols).')';
			
			if (!$sort) $sort = 'score DESC';
			
		} else {
			
			if (!$sort) $sort = 'Posted DESC';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Section
		
		if ($section) {
			$where['section'] = "Section IN ('".join("','", doSlash(do_list($section)))."')";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Status
		
		$where['status'] = build_query_status($status,$searchsticky,$iscustom);
		
		// Parent Status 
		
		if ($parent_atts['status']) {
			
			$where['parent_status'] = build_query_status($parent_atts['status'],$searchsticky,$iscustom,'t.ParentStatus');
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Time
		
		if (PREVIEW) $time = 'any';
		 
		switch ($time) {
			case 'any'   : break;
			case 'future': $where['time'] = "(t.Posted > now())"; break;
			default      : $where['time'] = "(t.Posted <= now())";
		}
		
		if (!$publish_expired_articles) {
			$where['expires'] = "(t.Expires > now() OR t.Expires = ".NULLDATETIME.")";
		}
				
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// Site
		/*
		if ($SITE['id']) {
			
			if ($s_path = $SITE['path']) {
			
				foreach ($s_path as $key => $val) {
					$s_path[$key] = "P".($key+2)." = ".$val;
				}
				
				$where['site'] = "(".doAnd($s_path).")";
			}
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// ID
		
		if (isset($atts['id']) and $id) {
			
			if ($id != '*') {
				
				$ids = do_list($id);
				
				if (count($ids) > 1) {
					
					$where['id'] = "(t.ID IN (".in($ids)."))";
				
				} else {
				
					$where['id'] = makeWhereSQL('t.ID',$id);
				}
			}
		}
		
		// Parent ID
		
		if ($parent_atts['id']) {
			
			if ($parent_atts['id'] != '*') {
			
				$ids = do_list($parent_atts['id']);
				
				if (count($ids) > 1) {
					
					$where['parent_id'] = "(t.ParentID IN (".in($ids)."))";
				
				} else {
				
					$where['parent_id'] = makeWhereSQL('t.ParentID',$parent_atts['id']);
				}
			}
		}
			
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Path
		
		if (in_atts('path') and $path) {
			
			$path = preg_split_att($path); 
			$path = array_shift($path);
			
		} else {
			
			$path = "///*";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Level
		
		if (in_atts('level') and $level) {
			
			$where['level'] = makeWhereSQL('t.Level',$level);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Position
		
		if ($position) {
			
			$where['position'] = makeWhereSQL('t.Position',$position);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Class
		
		if ($class) { 
			
			if ($class == 'NONE') {
			
				$where['class'] = "t.Class = ''";
			
			} else {
				
				$where['class'] = makeWhereSQL('t.Class',$class);
			}
		}
		
		// Parent Class
		
		if ($parent_atts['class']) {
		
			if ($parent_atts['class'] == 'NONE') {
			
				$where['parent_class'] = "t.ParentClass = ''";
			
			} else {
				
				$where['parent_class'] = makeWhereSQL('t.ParentClass',$parent_atts['class']);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Name
		
		if ($name and $name != '!*') {
			
			$where['name'] = makeWhereSQL("t.Name",$name);
		}
		
		// Parent Name 
		
		if ($parent_atts['name']) {
		
			if ($parent_atts['name'] != '!*') {
			
				$where['parent_name'] = makeWhereSQL("t.ParentName",$parent_atts['name']);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Image
		
		if (in_atts('image')) {
		
			$where['image'] = build_query_image($image);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// File
		
		if (in_atts('file')) {
		
			$where['file'] = build_query_file($file,$tables);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Category
		
		if (in_atts('category') and $category) {
			
			if ($category == '*') {
				
				$where['category'] = "t.Categories != ''";
				
			} elseif ($category == '!*' or $category == 'NONE') {
				
				$where['category'] = "t.Categories = ''";
				
			} else {
			
				$category = preg_split_att($category);
				$category_type = 'article';
				
				$category_type = ($table == 'textpattern')
					? 'article' : str_replace('txp_','',$table);
				
				foreach($category as $key => $value) {
				
					if (in_list($value,'AND,OR,(,)')) continue;
					
					if ($value == 'NONE' or strlen($value) == 0) {
						
						$category[$key] = "t.Categories = ''";
					
					} elseif ($value == '*') {
					
						$category[$key] = "t.Categories != ''";
					
					} elseif (substr($value,0,3) == '../') {
						
						$value = substr($value,3);
						
						$tables['pcategory'] = "LEFT JOIN txp_content_category AS `pcategory` ON t.parentid = pcategory.article_id AND pcategory.type = '$category_type'";
						
						$category[$key] = makeWhereSQL('pcategory.name',$value);
						
					} else {	
						
						$tables['category'] = "LEFT JOIN txp_content_category AS `category` ON t.id = category.article_id AND category.type = '$category_type'";
		
						$category[$key] = makeWhereSQL('category.name',$value);
					}
				}
				
				$where['category'] = '('.implode(' ',$category).')';
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Select Child Articles
		
		// TO BE REMOVED 
		/*
		if ($parent and $parent != '*') {
		
			foreach (do_list($parent) as $parent) {
				
				$exclude = (comparison($parent) == '!=');
					
				if (!is_numeric($parent))
					$parent = fetch("ID",$table,"url_title",doSlash($parent));
					
				if (is_numeric($parent)) {
					  $where['parent'][] = ($exclude) 
						? "(t.ParentID != 0 AND t.ParentID != $parent)" 
						: "t.ParentID = $parent";
				}	
			}
			
			if (isset($where['parent'])) 
				$where['parent'] = '('.implode(' OR ',$where['parent']).')';
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Alias
		
			if ($alias) {
					
				if (is_numeric($alias)) {
					
					$where['alias'] = (comparison($parent) == '!=') 
						? "t.Alias != 0 AND t.Alias != $alias" 
						: "t.Alias = $alias";
					
				}
			
			} elseif ($alias == '0') {
				
				$where['alias'] = "t.Alias = 0";
			}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Select articles having the specified number of children
		
		if (in_atts('children')) {
			
			switch (strtolower($children)) {
				case 'yes' : $children = "gte 1"; break;
				case 'no'  : $children = "0";
			}
				
		 // $where['children'] = makeWhereSQL("(SELECT COUNT(*) FROM ".$PFX.$table." AS `child` WHERE t.ID = child.ParentID)",$children);
			$where['children'] = makeWhereSQL("t.Children",$children);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Custom Fields
		
		// find custom field value for just the field name or the full path to a child field
		// by field name example: custom.start="123"				
		// by field path example: custom.action.start="123"
		
		$prev_custom = '';
		
		if ($custom_fields) {
			
			foreach ($custom_fields as $field_name => $field_value) {
				
				$parent = '';
				
				if (substr($field_name,0,7) == 'parent.') {
					
					$parent = 'parent_';
					$field_name = substr($field_name,7);
				}
				
				$type = $all_custom_fields['custom.'.$field_name];
				
				$find_field_type_by = 'name';
				
				$custom = $parent.preg_replace('/[\.\-]/','_',$field_name);
				
				$in = ($parent) ? 't.ParentID' : 't.ID,t.Alias';
				
				$tables[$custom] = (!$prev_custom or $parent) 
					? "txp_content_value AS `$custom` ON $custom.article_id IN ($in)"
					: "txp_content_value AS `$custom` ON $prev_custom.article_id = $custom.article_id";
				
				$where[] = "($custom.tbl = '$table')";
				$where[] = "($custom.status = 1)";
				$where[] = "($custom.field_name = '$field_name')";

				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// date
				
				if ($type == 'date') {
					
					$today     = date('Y/m/d');
					$tomorrow  = date('Y/m/d',strtotime("+1 day"));
					$yesterday = date('Y/m/d',strtotime("-1 day")); 
					
					$field_value = preg_replace('/today/',$today,$field_value);
					$field_value = preg_replace('/tomorrow/',$tomorrow,$field_value);
					$field_value = preg_replace('/yesterday/',$yesterday,$field_value);
				}
				
				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				// by field path
				/*
				if (strpos($field_name,'.')) {
					
					$find_field_type_by = 'path';
					
					$gr = 'gr_'.str_replace('.','_',$field_name);
					
					$tables[$gr] = "txp_group AS $gr ON $gr.id = $custom.group_id";
					$where[$custom.'status'] = "$gr.status = 1";
					$where[$custom.'name']   = "$gr.field_path = '$field_name'";
				}
				*/
				// - - - - - - - - - - - - - - - - - - - - - - - - - - -
				
				$value_column = safe_field("IF(type='number','num_val','text_val') AS mytype","txp_custom",
					"$find_field_type_by = '$field_name' GROUP BY type ORDER BY mytype DESC LIMIT 1");
				
				$where[$custom.'val'] = makeWhereSQL("$custom.$value_column",$field_value);
				
				$prev_custom = $custom;
			}
			
			if (!$groupby) $groupby = "ID";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// Keywords for no-custom articles

		if ($keywords) {
			
			$keys = doSlash(do_list($keywords));
			
			foreach ($keys as $key) {
				$keyparts[] = "FIND_IN_SET('".$key."',Keywords)";
			}
			
			$where['keywords'] = "(" . join(' OR ',$keyparts) . ")";
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// GROUP BY
		
		if ($groupby) {
		
			if (strtolower($groupby) == 'category') {
			
				$columns[] = "category.name AS Category";
				$tables[]  = "txp_content_category AS `category` ON t.ID = category.article_id";
				$groupby   = " GROUP BY category.name"; 
				
			} else {
			
				$subquery_where = '';
				
				if (!is_array($groupby_fields = do_list($groupby))) 
					$groupby_fields = array($groupby);
				
				foreach($groupby_fields as $field)
					$subquery_where .= " AND $field != ''"; 
					
				$groupby = " GROUP BY t.$groupby ";
			}
			
			$columns[] = "COUNT(t.ID) AS group_count";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// ORDER BY for custom fields
		
		$sort = sortvals($sort);
		
		foreach ($sort as $key => $orderby) {
			
			if (array_key_exists($orderby['col'], $all_custom_fields) or 
				array_key_exists('custom.'.$orderby['col'], $all_custom_fields)) {
				
				$sortfield = str_replace('custom.','',$orderby['col']);
				
				$sortcol = (fetch("type","txp_custom","name",$sortfield) == 'number')
					? 'num_val' 
					: "text_val";
				
				$custom = preg_replace('/[\.\-]/','_',$sortfield);
				
				if (!isset($tables[$custom])) {
				
					$tables[$custom] = (!$prev_custom) 
						? "LEFT JOIN txp_content_value AS `$custom` ON $custom.article_id IN (t.ID,t.Alias)"
						: "LEFT JOIN txp_content_value AS `$custom` ON $prev_custom.article_id = $custom.article_id";
					
					$tables[$custom] .= " AND $custom.tbl = '$table'";
					$tables[$custom] .= " AND $custom.status = 1";
					$tables[$custom] .= " AND $custom.field_name = '$sortfield'";
				}
				
				$sort[$key]['col'] = $custom.'.'.$sortcol;
				$sort[$key]['custom'] = true;
				
				$prev_custom = $custom;
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$sortcol = (isset($columns['sortcol'])) 
			? $columns['sortcol'] = implode(', ',$columns['sortcol']) 
			: '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		foreach($sort as $key => $item) {
			
			$custom = array_pop($item);
			
			if (strtolower($item['col']) == 'category') {
				$item['col'] = 'Categories';
			}
			
			if (!$custom and $item['col'] != 'score' ) {
			 	$sort[$key]['col'] = 't.'.$item['col'];
			}
			
			$item = implode(' ',$item);
			
			$sort[$key] = $item;
		}
		
		$sort = implode(',',$sort);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// choose a number random articles 
		
		if ($random) {
		
			$sort  = 'RAND()';
			$limit = $random;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// TEST: indexed sorting
		/*
		if (isset($where['path'])) {
		
			$sort_items  = explode(',',$sort);
			$sort_item_1 = explode(' ',array_shift($sort_items));
			$sort_col_1  = strtolower($sort_item_1[0]);
			$sort_dir_1  = (isset($sort_item_1[1])) ? strtoupper($sort_item_1[1]) : 'ASC';
			
			if ($sort_col_1 == 'posted') {
				$tables['t'] .= " USE INDEX (Path_Level_Posted_".$sort_dir_1.")";
				$sort = implode(',',$sort_items);
			}
			
			if ($sort_col_1 == 'position') {
				$tables['t'] .= " USE INDEX (Path_Level_Position_".$sort_dir_1.")";
				$sort = implode(',',$sort_items);
			}
		}
		*/		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		foreach($where as $key => $item) {
			
			if (!$item) unset($where[$key]);
		}
		
		if ($groupby) {
			$where['group'] = substr(trim($groupby),9);
		}
		
		$where['order'] = $sort;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get the total number of items matched by the query without a limit
		
		$total_matched = safe_count_treex($thisid,$path,$tables,$where,'DEBUG');
		
		$test_sql_1 = get_test_sql();
			
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// do not paginate if we are on a custom list
		// for custom list do paginate when pageby attribue is given
		// no pagination for custom list with limit attribute 
		
		// if ((!$iscustom and !$issticky) or $pageby)
		
		if ($pageby) 
		{	
			if ($page) $pg = $page;
			
			// $pageby = (empty($pageby)) ? $limit : $pageby;
			
			if (!isset($atts['limit'])) {
				$limit = $pageby;
			}
			
			$total = $total_matched - $offset;
			$numPages = ceil($total/$pageby);
			$pg = (!$pg) ? 1 : $pg;
			$pgoffset = $offset + (($pg - 1) * $pageby);
			
			// send paging info to txp:newer and txp:older
			$pageout['pg']       = $pg;
			$pageout['numPages'] = $numPages;
			$pageout['pageby']   = $pageby;
			$pageout['pagedir']  = '';
			$pageout['sortdir']  = sortdir($sort);
			$pageout['s']        = $s;
			$pageout['c']        = $c;
			$pageout['grand_total'] = $total_matched;
			$pageout['total']    = $total;
			
			if (empty($thispage))
				$thispage = $pageout;
			if ($pgonly)
				return;
				
		} elseif ($page) {
			
			$pgoffset = $offset + (($page - 1) * $thispage['pageby']);
			
		} else {
			$pgoffset = $offset;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get the form name
		
		// if a listform is specified, $thing is for doArticle() - hence ignore here.
		if (!empty($listform)) $thing = '';
		
		if ($q and !$iscustom and !$issticky)
			$fname = ($searchform ? $searchform : 'search_results');
		else
			$fname = ($listform ? $listform : $form);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$sql['LIMIT'] = intval($pgoffset).', '.intval($limit);
		
		// $sort = str_replace('.','_',$sort);
		$orderby = ($sort) ? ' ORDER BY ' . doSlash($sort) : '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add the executed query without the limit to $thispage
		
		if ($pageby) {
			
			$thispage['query'] = safe_rows_treex($thisid,$path,'t.ID',$tables,$where,'SQL');
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get items before or after current item
		
		// TODO: use a binary search to find the current id offset instead of 
		// getting all the ids
		
		if ($before or $after) {
			
			$ids_offset = (intval($pgoffset) >= $limit) ? intval($pgoffset) - $limit : intval($pgoffset);
			$ids_limit  = 1000; // intval($limit) * 2; // or 1000 ?
		
		 /* $ids = safe_column("t.ID",$tables, 
				$where . $groupby . $orderby . " LIMIT $ids_offset, $ids_limit", 2, $sortcol, 0); */
			
			$ids = safe_column_treex($thisid,$path,"t.ID",$tables,$where,$sortcol,'DEBUG');
			
			$limit  = $before;
			$offset = 0;
		
			if (isset($atts['limit'])) {
				$limit = $atts['limit'];
			}
			
			if (isset($atts['offset'])) {
				$offset = $atts['offset'];
			}
			
			$before_or_after = getNeighbours($after,$before,$thisid,$ids,$loop,$limit,$offset);
			
			/* EXPERIMENT */
			
			/* if (!isset($ids[$thisid])) {
			
				// check for an ELSE clause
				
				if (EvalElse(trim($thing),false)) {
					
					// find no articles so the ELSE clause can be evaluated 
					
					$before_or_after = array(0);
				}
			} */
			
			$where[] = "t.ID IN (".impl($before_or_after).")"; 
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// return nothing or alternate values 
		
		if ($return == 'NOTHING') { 
			
			// do not execute the query
			// we are only interested in setting $thispage
			
			return ''; 
		}
		
		if ($min or $max) {
			
			// TODO: return parse(EvalElse($thing,true));
			
			if ($min and !$max and $total_matched < $min) { 
			
				return '';
			}
			
			if ($max and !$min and $total_matched > $max) { 
			
				return '';
			}
			
			if ($min and $max and $total_matched < $min and $total_matched > $max) { 
			
				return '';
			}
		}
		
		if ($return == 'total' or $return == 'count') {
			
			if ($thing) {
			
				// inspect('Runtime: '.getmicrotime('do_articles'),'line','doArticles($run)');
				if ($debug) inspect_query($test_sql_1,$total_matched);
				
				return parse(EvalElse($thing,$total_matched));
			}
			
			// inspect('Runtime: '.getmicrotime('do_articles'),'line','doArticles($run)');
			if ($debug) inspect_query($test_sql_1,$total_matched);
			
			return $total_matched;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// execute the query
		
		/* $rs = safe_rows_start($columns,$tables,
			$where . $groupby . $orderby . ' LIMIT ' . $sql['LIMIT']); */
		
		$where['limit'] = $sql['LIMIT'];
		
		$rs = safe_rows_treex($thisid,$path,$columns,$tables,$where,'DEBUG');
		
		$total_returned = numRows($rs);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// inspect the query 
		
		if ($debug) {
			
			 inspect_query(get_test_sql(),$total_matched,$total_returned);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($rs) {
		
			$count = $pgoffset;
			
			$articles = array();
			
			while($a = nextRow($rs)) {
				
				$id = $a['ID'];
				
				$a['atts'] = $atts;
				
				populateArticleData($a,$table);
				
				$thisarticle['count']  	   		   = ++$count;
				$thisarticle['total']  	   		   = $total_matched;
				$thisarticle['table']  	   		   = $table;
				$thisarticle['is_first']   		   = ($count == 1);
				$thisarticle['is_last']    		   = ($count == $total_returned);
				$thisarticle['body_tag_encounter'] = false;
				$thisarticle['query']['tables']    = $tables;
				$thisarticle['query']['where']     = doAnd($where).$groupby.$orderby;
				
				if ($groupby) {
					
					$thisarticle['group_count'] = $a['group_count'];
					
					if (strtolower($theAtts['groupby']) == 'category') {
						$thiscategory['name']  = $a['Category'];
						$thiscategory['type']  = 'article';
					}
				}
				
				$article_stack->push($thisarticle,$myname);
				
				if (@constant('txpinterface') === 'admin' and gps('Form')) {
					
					$articles[] = parse(gps('Form'));
				
				} elseif ($table == 'textpattern' and $allowoverride and $a['override_form']) {
					
					$articles[] = parse_form($a['override_form']);
				
				} else {
					
					$articles[] = ($thing) 
						? parse(EvalElse($thing,true))
						: parse_form($fname);
				}
				
				$article_stack->pop();
				
				$thisarticle = $article_stack->top();
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			if ($count) {
				
				$articles = doLabel($label, $labeltag).doWrap($articles, $wraptag, $break,'');
			
				if ($cache) cacheit($articles,$cache);
				
				return trim($articles);
			
			} elseif ($noneform) {
			
				return parse(fetch_form($noneform,'article'));
			
			} elseif (!count($articles) and $thing) {
				
				return parse(EvalElse($thing,false));
			}
				
			return '';
		}
	}

// -------------------------------------------------------------------------------------
// Keep all the article tag-related values in one place,
// in order to do easy bugfix and easily the addition of
// new article tags.

	function populateArticleData($rs,$table='textpattern') {
	
		global $thisarticle;
		
		$thisarticle = array();
		
		extract($rs);
		
		// what are the atts for?
		
		if (isset($atts)) {
			unset($atts['limit'],$atts['debug'],$atts['thing']);
		} else {
			$atts = array();
		}
		
		trace_add("[".gTxt('Article')." $ID]");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$path = new Path($ID);
		$path = $path->getArr();
		
		foreach($path as $key => $item) {
			if ($item['Status'] == 2) 
				unset($path[$key]);
			else
				$path[$key] = $item['Name'];
		}
		
		$path = implode('/',$path);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$subtree = ($Name == ROOTNODE) ? '' : $rs['Path'];
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$Categories = explode(',',$Categories);
		
		foreach ($Categories as $key => $value) {
			
			$value = explode('.',$value);
			$Categories[$key] = array_shift($value);
		}
		
		$Categories = implode(',',$Categories);
				
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$thisarticle['thisid']           = $ID;
		$thisarticle['posted']           = $uPosted;
		$thisarticle['expires']     	 = $uExpires;
		$thisarticle['modified']		 = $uLastMod;
		$thisarticle['authorid']         = $AuthorID;
		$thisarticle['body']             = $Body_html;
		$thisarticle['excerpt']          = $Excerpt_html;
		$thisarticle['title']            = (strlen($Title_html)) ? $Title_html : $Title;
		$thisarticle['name']        	 = (isset($article_name)) ? $article_name : $Name;
		$thisarticle['class']       	 = $Class;
		$thisarticle['categories']       = $Categories;
		$thisarticle['keywords']         = $Keywords;
		$thisarticle['image_id']    	 = ($ImageID > 0) ? $ImageID : 0;
		$thisarticle['file_id']    	 	 = $FileID;
		$thisarticle['position']		 = $Position;
		$thisarticle['parent']			 = $ParentID;
		$thisarticle['parent_title']	 = fetch("Title_html","textpattern","ID",$ParentID);
		$thisarticle['status'] 			 = $Status;
		$thisarticle['path'] 			 = $path;
		$thisarticle['subtree'] 		 = $subtree;
		$thisarticle['level'] 		 	 = $Level;
		$thisarticle['alias'] 		 	 = $Alias;
		$thisarticle['atts'] 			 = $atts;
		
		if ($table == 'textpattern') {
			$thisarticle['annotate']         = $Annotate;
			$thisarticle['comments_invite']  = $AnnotateInvite;
			$thisarticle['url_title']        = $url_title;
			$thisarticle['section']          = $Section;
			$thisarticle['image_data']    	 = $ImageData;
			$thisarticle['comments_count']   = $comments_count;
			$thisarticle['override_form']    = $override_form;
		}
		
		if ($table == 'txp_link') {
			$thisarticle['url'] = $url;
		}
		
		if (isset($rs['score'])) {
			$thisarticle['score'] = $score;
		}

		$thisarticle['custom_fields'] = '';
		
		$columns = array(
			'fname'  => 'tcv.field_name AS fname',
			'ftype'  => 'f.type AS ftype', 
			'finput' => 'f.input AS finput', 
			'flabel' => 'f.label AS flabel', 
			'fvalue' => 'tcv.text_val AS fvalue'
		);
		
		$rows = safe_rows(
			impl($columns),
			"txp_content_value AS tcv JOIN txp_custom AS f ON f.id = tcv.field_id",
			"tcv.article_id IN ($ID,$Alias) AND tcv.tbl = '$table' AND tcv.status = 1 ORDER BY tcv.id ASC"
		);
		
		foreach ($rows as $row) {
			
			extract($row);
			
			if (!isset($thisarticle['custom_fields'][$fname])) {
				$thisarticle['custom_fields'][$fname][0] = array(
					'info'  => "$ftype:$finput:$flabel",
					'value' => doStrip($fvalue));
			} else {
				$thisarticle['custom_fields'][$fname][] = array(
					'info'  => "$ftype:$finput:$flabel",
					'value' => doStrip($fvalue));
			}
		}
		
	 /* if ($custom_fields) {
			
			if (substr($custom_fields,-1) != '&') {
				
				// GROUP_CONCAT length limit was exceeded 
				// fetch custom fields for this article again
					
				$rows = safe_rows(
					impl($columns),
					"txp_content_value AS tcv JOIN txp_custom AS f ON f.id = tcv.field_id",
					"tcv.article_id IN ($ID,$Alias) AND tcv.tbl = '$table' AND tcv.status = 1 ORDER BY tcv.id ASC"
				);
				
			} else {
				
				$rows = array();
				
				$custom_fields = trim($custom_fields,'&');
				
				foreach (explode('&,&',$custom_fields) as $field) {
					
					$field = explode('}:{',rtrim(ltrim($field,'{'),'}'));
					
					foreach ($columns as $key => $col) {
						$columns[$key] = array_shift($field);
					}
					
					$rows[] = $columns;
				}
			}
			
			foreach ($rows as $row) {
			
				extract($row);
				
				if (!isset($thisarticle['custom_fields'][$fname])) {
					$thisarticle['custom_fields'][$fname][0] = array(
						'info'  => "$ftype:$finput:$flabel",
						'value' => doStrip($fvalue));
				} else {
					$thisarticle['custom_fields'][$fname][] = array(
						'info'  => "$ftype:$finput:$flabel",
						'value' => doStrip($fvalue));
				}
			}
		} */
		
		$GLOBALS['articles'][] = $thisarticle;
	}

// -------------------------------------------------------------------------------------
	function processTags($tag, $atts, $thing = NULL, $namespace='txp') {
	
		global $pretext,$production_status, $txptrace, $txptagtrace, $txptracelevel;
		global $variable, $txp_current_tag, $txp_current_atts, $tags;

		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($production_status !== 'live')
		{
			$old_tag = $txp_current_tag;

			$txp_current_tag = '<'.$namespace.':'.$tag.$atts.(isset($thing) ? '>' : '/>');

			trace_add($txp_current_tag);
			++$txptracelevel;

			if ($production_status === 'debug')
			{
				maxMemUsage($txp_current_tag);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// tag aliases
		
		if (isset($tags[$tag])) { 
			
			$tag = $tags[$tag];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (function_exists($tag)) {
			
			$txp_current_atts = splat($atts);
			
			if (isset($txp_current_atts['debug'])) {
				unset($txp_current_atts['debug']);
			}
			
			@$txptagtrace[] = $tag;
			
			if ($thing) 
				$out = $tag(splat($atts), $thing);
			else
				$out = $tag(splat($atts));
			
		} elseif (isset($variable[$tag])) {
			
			// NOTE: This should be not allowed.
			
			$out = $variable[$tag];
		
		} elseif (isset($GLOBALS['pretext'][$tag])) {
			
			// deprecated, remove in crockery
			
			$out = htmlspecialchars($pretext[$tag]);

			trigger_error($tag.' '.gTxt('deprecated_tag'), E_USER_NOTICE);
		
		} else {
			
			$out = '';
			trigger_error($tag.' '.gTxt('unknown_tag'), E_USER_WARNING);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($production_status !== 'live')
		{
			--$txptracelevel;

			if (isset($thing))
			{
				trace_add('</txp:'.$tag.'>');
			}

			$txp_current_tag = $old_tag;
		}
				
		// - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $out;
	}
	
	// -------------------------------------------------------------------------------------

	function getCustomFields() {
		
		$columns = array("CONCAT('custom.',name)","Type");
		$fields = safe_column($columns,"txp_custom","ParentID != 0 AND Status = 4");
		
		return $fields;
	}

// -------------------------------------------------------------------------------------
	function sortvals($sort) {
	
		$out = array();
		
		foreach(expl($sort) as $sort) {
		
			$sort = trim(preg_replace('/\s+/',' ',$sort));
			
			$sort = explode(' ',$sort);
			
			if (isset($sort[0])) {
				
				$sortby  = $sort[0];
				$sortdir = isset($sort[1]) ? $sort[1] : 'ASC';
				
				$out[] = array('col' => $sortby, 'dir' => $sortdir, 'custom' => false);
			}
		}
		
		return $out;
	}

	// -------------------------------------------------------------------------------------

	function in_atts($name,$value=null) {
		
		global $txp_current_atts;
		
		if (!isset($txp_current_atts[$name])) {
		
			return false;
		}
		
		if (!is_null($value)) {
			
			return ($txp_current_atts[$name] == $value);
		}
		
		return true;
	}

	// -------------------------------------------------------------------------
	
	function get_test_sql() {
		
		global $dump;
		
		$query = '';
		
		if (is_array($dump)) {
			
			extract($dump);
			
			// unset($where['trash']);
			// unset($where['trashed']);
			
			$select = '   SELECT '.implode(','.n.'          ',$select).n;
			$from   = '     FROM '.implode(n.'     JOIN ',$from).n;
			$from   = str_replace(' JOIN LEFT ',' LEFT ',$from);
			$from   = str_replace('     LEFT ','LEFT ',$from);
			$from   = str_replace(' AND ',n.'          AND ',$from);
			$where  = '    WHERE '.implode(n.'      AND ',$where).n;
			$group  = ($group) ? ' GROUP BY '.$group.n : '';
			$order  = ($order) ? ' ORDER BY '.$order.n : '';
			$limit  = ($limit) ? '    LIMIT '.$limit.n : '';
			
			$query = $select.$from.$where.$group.$order.$limit;
			$query = colorcode($query);
		}
		
		$dump = '';
		
		return '<pre class="query">'.$query.'</pre>';
	}
	
	// -------------------------------------------------------------------------
	
	function colorcode($text) {
		
		// -----------------------------------------
		// SQL specific key words 
		
		$sql = "SELECT|FROM|AS|LEFT|JOIN|ON|IN|WHERE|AND|OR|GROUP\s+BY|ORDER\s+BY|DESC|ASC|LIMIT";
		
		$text = preg_replace("/\b(".$sql.")\b/","<span class=\"sql\">$1</span>",$text);
		
		// -----------------------------------------
		// strings and numbers
		
		// IN (values...) 
		$text = preg_replace_callback(
			"/ IN \(([^\)]+)\)/",'colorcode_invals',$text);
		
		// numbers
		$text = preg_replace("/([\s])([\d\.]+)([\s\b\)])/","$1<span class=\"sqlval\">$2</span>$3",$text);
		
		// strings in single quotes
		$text = preg_replace("/(\'[^\']*?\')/","<span class=\"sqlval\">$1</span>",$text);
		
		// 1=1
		$text = preg_replace("/(\s)1\=1(\s)/","$1<span class=\"sqlval\">1</span>=<span class=\"sqlval\">1</span>$2",$text);
		
		return $text;
	}
	
	// -------------------------------------------------------------------------

	function colorcode_invals($matches) {
		$in = explode(',',$matches[1]);
		foreach ($in as $key => $item) {
			if (is_numeric($item) or substr($item,0,1) == "'") {
				$in[$key] = '<span class="sqlval">'.$item.'</span>';
			}
		}
		return " IN (".impl($in).")";
	}

	// -------------------------------------------------------------------------
	
	function inspect_query($sql,$matched,$returned=null) {
		
		static $run = 1;
		
		// if ($match) array_push($sql['SELECT'],$match);
		// if ($search) array_push($sql['WHERE'],$search);
	
		inspect($sql,'line',"doArticles($run)");
		inspect("Total Matched: $matched",'line',"doArticles($run)");
		if ($returned !== null) {
			inspect("Total Returned: $returned",'line',"doArticles($run)");
		}
		
		inspect('Runtime: '.getmicrotime('do_articles'),'line',"doArticles($run)");
		
		$run++;
	}
?>
<?php

// =============================================================================
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
			'article_id = '.intval($thisid).' AND Status = 4 AND Trash = 0',
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, 
			ID as discussid, 
			Body_html AS message, 
			Author AS name, 
			unix_timestamp(posted) AS time',
			'txp_discuss', join(' ', $qparts));
		
		$total = (($min or $max) and $limit)
				? getCount("txp_discuss","article_id='$id' AND Trash = 0 AND Status=".VISIBLE)
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

// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
	function comments_count()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['comments_count'];
	}

// -----------------------------------------------------------------------------
	function if_comments($atts, $thing)
	{
		global $thisarticle;
		
		extract(lAtts(array(
			'min' => 0
		), $atts));
		
		if (!$min) $min = 1; 

		return parse(EvalElse($thing, ($thisarticle['comments_count'] >= $min)));
	}

// -----------------------------------------------------------------------------
	function if_comments_allowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, checkCommentsAllowed($thisarticle['thisid'])));
	}

// -----------------------------------------------------------------------------
	function if_comments_disallowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, !checkCommentsAllowed($thisarticle['thisid'])));
	}
	
// -----------------------------------------------------------------------------
	function contact_form($atts,$thing=NULL)
	{
		$atts['contact'] = 1;
		$atts['form'] = 'contact-form';
		$atts['show_preview'] = '0';
		
		return comments_form($atts,$thing);
	}
	
// -----------------------------------------------------------------------------
// change: using comments as a contact form

	function comments_form($atts,$thing=NULL)
	{
		global $thisarticle, $has_comments_preview;

		extract(lAtts(array(
			'class'        => __FUNCTION__,
			'form'         => 'comment-form',
			'isize'        => '25',
			'msgcols'      => '25',
			'msgrows'      => '5',
			'msgstyle'     => '',
			'show_preview' => empty($has_comments_preview),
			'wraptag'      => '',
			'contact'	   => 0	
		), $atts));

		assert_article();

		$thisid = $thisarticle['thisid'];
		
		$out = '';
		$ip = serverset('REMOTE_ADDR');
		$blacklisted = ($prefs['production_status'] == 'live')
			? is_blacklisted($ip)
			: false;

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

// -----------------------------------------------------------------------------
	function comment_subject_input($atts,$thing=NULL) {
		
		global $thisarticle;
		
		$atts['name']  = 'subject';
		$atts['other'] = 'subject_other';

		if ($input = custom_field_input($atts,$thing)) {
		
			return $input;
		}
		
		// if article is class form and has chidren option
		
		$subject = '';
		
		if (isset($thisarticle['comment_subject'])) {
			$subject = $thisarticle['comment_subject'];
		}
		
		return fInput('text','subject',$subject,'text');
	}

// -----------------------------------------------------------------------------
	function comment_custom_input($atts,$thing='') {
		
		global $thisarticle;
		
		extract(lAtts(array(
			'name'  => '',
			'title' => ''
		),$atts));
		
		$fields = getArticleCustomFields($thisarticle['parent'],$name);
		
		return displayArticleCustomFields($fields);
	}
	
// -----------------------------------------------------------------------------
	function comments_error($atts)
	{
		extract(lAtts(array(
			'break'		=> 'br',
			'class'		=> __FUNCTION__,
			'wraptag'	=> 'div',
		), $atts));

		$evaluator =& get_comment_evaluator();

		$errors = $evaluator->get_result_message();
		
		if (ps('error')) { 
			
			$errors[] = ps('error');
		}

		if ($errors)
		{
			return doWrap($errors, $wraptag, $break, $class);
		}
	}

// -----------------------------------------------------------------------------
	function if_comments_error($atts, $thing)
	{
		$evaluator =& get_comment_evaluator();
		return parse(EvalElse($thing,(count($evaluator -> get_result_message()) > 0)));
	}

// -----------------------------------------------------------------------------
	function contact_success($atts,$thing='') {
		
		assert_article();
		
		if (ps('sent')) return parse($thing);
		
		return '';
	}

// -----------------------------------------------------------------------------
	function contact_name($atts) 
	{
		assert_article();
		
		if ($id = ps('sent')) {
			$id = (preg_match('/^\d+$/',$id)) ? $id : 0;
			return fetch('Author','txp_discuss','ID',$id);
		}
	}
	
// -----------------------------------------------------------------------------
/* DEPRECATED - provided only for backwards compatibility
 * this functionality will be merged into comments_invite
 * no point in having two tags for one functionality
 */
	function comments_annotateinvite($atts, $thing)	{
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

// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
	function if_comments_preview($atts, $thing)
	{
		return parse(EvalElse($thing, ps('preview') && checkCommentsAllowed(gps('parentid')) ));
	}

// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
	function comment_id()
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['discussid'];
	}

// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
	function comment_email()
	{
		global $thiscomment;

		assert_comment();

		return htmlspecialchars($thiscomment['email']);
	}

// -----------------------------------------------------------------------------
	function comment_web()
	{
		global $thiscomment;

		assert_comment();

		return htmlspecialchars($thiscomment['web']);
	}

// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
	function comment_message()
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['message'];
	}

// -----------------------------------------------------------------------------
	function comment_anchor()
	{
		global $thiscomment;

		assert_comment();

		$thiscomment['has_anchor_tag'] = 1;
		return '<a id="c'.$thiscomment['discussid'].'"></a>';
	}

// -----------------------------------------------------------------------------
	function comment_captcha()
	{
		global $smarty;

		return $smarty->fetch('misc/captcha.tpl');
	}

// =============================================================================
	
?>
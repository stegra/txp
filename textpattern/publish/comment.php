<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/publish/comment.php $
$LastChangedRevision: 3266 $

*/

// -------------------------------------------------------------
	function fetchComments($id)
	{
		$rs = safe_rows(
			"*, unix_timestamp(posted) AS time",
			"txp_discuss", 'article_id = '.intval($id).' AND Status = '.VISIBLE.' ORDER BY Posted ASC'
		);

		if ($rs) return $rs;
	}

// -------------------------------------------------------------
	function discuss($id)
	{
		$rs = safe_row('*, unix_timestamp(Posted) as uPosted, unix_timestamp(LastMod) as uLastMod, unix_timestamp(Expires) as uExpires', 'textpattern', 'ID='.intval($id).' and Status >= 4');
		if ($rs) {
			populateArticleData($rs);
			$result = parse_form('comments_display');
			return $result;
		}

		return '';
	}


// -------------------------------------------------------------
	function getNextNonce($check_only = false)
	{
		static $nonce = '';
		if (!$nonce && !$check_only)
			$nonce = md5( uniqid( rand(), true ) );
		return $nonce;
	}
	function getNextSecret($check_only = false)
	{
		static $secret = '';
		if (!$secret && !$check_only)
			$secret = md5( uniqid( rand(), true ) );
		return $secret;
	}

// -------------------------------------------------------------
// change: added subject
// change: display error message on same page

	function commentForm($id, $atts=NULL, $thing=NULL)
	{
		global $prefs,$dump,$thisarticle;
		extract($prefs);

		extract(lAtts(array(
			'isize'	    => '25',
			'msgrows'   => '5',
			'msgcols'   => '25',
			'msgstyle'  => '',
			'form'      => 'comment_form',
			'contact'   => 0,
			'action'	=> '',
			'preview'	=> 0,
			'backpage'	=> ''
		),$atts, 0));
		
		$namewarn 		= false;
		$emailwarn 		= false;
		$subjectwarn 	= false;
		$commentwarn 	= false;
				
	 // $name  			= pcs('name');
	 // $email 			= clean_url(pcs('email'));
	 //	$web   			= clean_url(pcs('web'));
		
		$name  			= ps('name');
		$email 			= clean_url(ps('email'));
		$web   			= clean_url(ps('web'));
		$subject        = clean_url(ps('subject'));
		$error		 	= ps('error');
		$sender   	 	= ps('sent');
		$n_message 		= 'message';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ( $sender and !$preview) {
			
			/* if (getCount('txp_form',"name = 'contact' AND type = 'article' AND Status = 4 AND Trash = 0")) {
				
				$html = safe_field('Body','txp_form',"name = 'contact' AND type = 'article' AND Status = 4 AND Trash = 0");
				
				if (!preg_match('/\<txp\:(contact|comment)\_success/',$html)) {
					return "<p>Thank you, XXX $name.<br/> Your message was sent.</p>";
				}
			} */
			
			return '';
		}
		
		$backpage_attr = $backpage;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		extract( doDeEnt ( psa( array(
			'checkbox_type',
			'remember',
			'forget',
			'parentid',
			'preview',
			'message',
			'submit',
			'backpage'
		) ) ) );
		
		if (!$backpage) $backpage = $backpage_attr;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($message == '')
		{	//Second or later preview will have randomized message-field name
			$in = getComment();
			$message = doDeEnt($in['message']);
		}
		
		// if ( $preview or $contact or $error ) {
		
			$nonce   = getNextNonce();
			$secret  = getNextSecret();
		
			safe_insert("txp_discuss_nonce", "issue_time=now(), nonce='".doSlash($nonce)."', secret='".doSlash($secret)."'");
			$n_message = md5('message'.$secret);
		// }
		
		if (isset($atts['preview']) and $atts['preview']) {
			$preview = 1;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ( $preview or $error ) {
			
			$dump[][] = "contact: $contact";
			
			$name     = trim(ps('name'));
			$email    = trim(clean_url(ps('email')));
			$is_email = filter_var($email,FILTER_VALIDATE_EMAIL);
			$web      = trim(clean_url(ps('web')));
			$subject  = trim(ps('custom_subject',$subject));
			$subject_other = trim(ps('custom_subject_other'));
			
			$namewarn    = ($comments_require_name and !$name);
			$emailwarn   = ($comments_require_email and !$is_email);
			$subjectwarn = ($contact and(!$subject and !$subject_other));
			$commentwarn = (!$message);
			
			$evaluator =& get_comment_evaluator();
			if ($namewarn) 	  $evaluator -> add_estimate(RELOAD,1,gTxt('comment_name_required'));
			if ($emailwarn)   $evaluator -> add_estimate(RELOAD,1,gTxt('comment_email_required'));
			if ($subjectwarn) $evaluator -> add_estimate(RELOAD,1,gTxt('comment_subject_required'));
			if ($commentwarn) $evaluator -> add_estimate(RELOAD,1,gTxt('comment_required'));
		}
		else
		{
			$rememberCookie = cs('txp_remember');
			if($rememberCookie === '')
			{
				$checkbox_type = 'remember';
				$remember = 1;
			}
			else if($rememberCookie == 1)
				$checkbox_type = 'forget';
			else
				$checkbox_type = 'remember';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		// If the form fields are filled (anything other than blank), pages
		// really should not be saved by a public cache. rfc2616/14.9.1
		if ($name || $email || $web || $subject) {
			header('Cache-Control: private');
		}

		$parentid = (!$parentid) ? $id : $parentid;

		$url = basename($GLOBALS['pretext']['request_uri']);
		
		// Experimental clean urls with only 404-error-document on apache
		// possibly requires messy urls for POST requests.
		if (defined('PARTLY_MESSY') and (PARTLY_MESSY))
		{
			$url = hu.'?id='.intval($parentid);
		}
		
		$form_name 	  = ($contact) ? 'contact' : 'comment';
		$form_id 	  = "txpCommentInputForm";
		$form_action  = ($contact) ? htmlspecialchars($url) : htmlspecialchars($url).'#cpreview';
		$form_action  = ($action)  ? $action : $form_action; 
		$submit_label = ($contact) ? gTxt('send') : gTxt('submit');
		
		$out  = n.n.'<a name="comment-form"></a>';
		
		$out .= n.n.'<form name="'.$form_name.'" id="'.$form_id.'" method="post" action="'.$form_action.'">';
		$out .= n.'<div class="comments-wrapper">'.n.n; // prevent XHTML Strict validation gotchas
		
		$msgstyle = ($msgstyle ? ' style="'.$msgstyle.'"' : '');
		$msgrows = ($msgrows and is_numeric($msgrows)) ? ' rows="'.intval($msgrows).'"' : '';
		$msgcols = ($msgcols and is_numeric($msgcols)) ? ' cols="'.intval($msgcols).'"' : '';

		$textarea = n.'<textarea id="message" name="'.$n_message.'"'.$msgcols.$msgrows.$msgstyle.
			' class="txpCommentInputMessage'.(($commentwarn) ? ' comments_error"' : '"').
			'>'.htmlspecialchars(substr(trim($message), 0, 65535)).'</textarea>';

		// by default, the submit button is visible but disabled
		// $comment_submit_button = fInput('submit', 'submit', $submit_label, 'button disabled', '', '', '', '', 'txpCommentSubmit', true);

		// if all fields checkout, the submit button is active/clickable
		/* if ($preview or $contact) {
			$comment_submit_button = fInput('submit', 'submit', $submit_label, 'button', '', '', '', '', 'txpCommentSubmit', false);
		} */
		
		$comment_submit_button = fInput('submit', 'submit', $submit_label, 'button', '', '', '', '', 'txpCommentSubmit', false);

		if ($checkbox_type == 'forget')
		{
			// inhibit default remember
			if ($forget == 1)
			{
				destroyCookies();
			}

			$checkbox = checkbox('forget', 1, $forget, '', 'forget').' '.tag(gTxt('forget'), 'label', ' for="forget"');
		}

		else
		{
			// inhibit default remember
			if ($remember != 1)
			{
				destroyCookies();
			}

			$checkbox = checkbox('remember', 1, $remember, '', 'remember').' '.tag(gTxt('remember'), 'label', ' for="remember"');
		}

		$checkbox .= ' '.hInput('checkbox_type', $checkbox_type);

		$vals = array(
			'comment_name_input'		=> fInput('text', 'name', htmlspecialchars($name), 'comment_name_input'.($namewarn ? ' comments_error' : ''), '', '', $isize, '', 'name'),
			'comment_email_input'		=> fInput('email', 'email', htmlspecialchars($email), 'comment_email_input'.($emailwarn ? ' comments_error' : ''), '', '', $isize, '', 'email'),
			/* 'comment_subject_input'		=> fInput('text', 'subject', htmlspecialchars($subject), 'comment_subject_input'.($subjectwarn ? ' comments_error' : ''), '', '', $isize, '', 'subject'), */
			'comment_web_input'			=> fInput('text', 'web', htmlspecialchars($web)	, 'comment_web_input', '', '', $isize, '', 'web'),
			'comment_message_input' 	=> $textarea.'<!-- plugin-place-holder -->',
			'comment_remember'			=> $checkbox,
			'comment_preview'			=> fInput('submit', 'preview', gTxt('preview'), 'button', '', '', '', '', 'txpCommentPreview', false),
			'comment_submit'			=> $comment_submit_button
		);
		
		$vals['comment_preview'] = '';
		$thisarticle['comment_subject'] = $subject;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$Form = ($thing) ? $thing : safe_field('Body','txp_form',"Name = '$form' AND Status = 4 AND Trash = 0");
		
		foreach ($vals as $a => $b)
		{
			$Form = preg_replace('/<txp:'.$a.'\s*\/>/',$b,$Form);
		}

		$Form = parse($Form);

		$out .= $Form.
			n.hInput('parentid', $parentid);

		$split = rand(1, 31);
		
		$out .= n.hInput(substr($nonce, 0, $split), substr($nonce, $split));
		// $out .= ($preview or $contact) ? n.hInput(substr($nonce, 0, $split), substr($nonce, $split)) : '';
		$out .= ($contact) ? n.hInput('contact',1) : '';
		
		if (!$preview) {
		
			$out .= ($backpage) 
				? n.hInput('backpage', htmlspecialchars($backpage)) 
				: n.hInput('backpage', htmlspecialchars($url));
		
		} else {
			
			$out .= n.hInput('backpage', htmlspecialchars($backpage));
		}
		
		$out = str_replace( '<!-- plugin-place-holder -->', callback_event('comment.form'), $out);

		$out .= n.n.'</div>'.n."</form>";
		$out .= ($error) ? n.n.'<script>document.location.href="#comment-form";</script>' : '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $out;
	}

// -------------------------------------------------------------
	function popComments($id)
	{
		global $sitename,$s,$thisarticle;
		$preview = gps('preview');
		$h3 = ($preview) ? hed(gTxt('message_preview'),3) : '';
		$discuss = discuss($id);
		ob_start('parse');
		$out = fetch_form('popup_comments');
		$out = str_replace("<txp:popup_comments />",$discuss,$out);

		return $out;

	}

// -------------------------------------------------------------
	function setCookies($name,$email,$web='')
	{
		$cookietime = time() + (365*24*3600);
		
		ob_start();
		setcookie("txp_name",  $name,  $cookietime, "/");
		setcookie("txp_email", $email, $cookietime, "/");
		setcookie("txp_web",   $web,   $cookietime, "/");
		setcookie("txp_last",  date("H:i d/m/Y"),$cookietime,"/");
		setcookie("txp_remember", '1', $cookietime, "/");
	}

// -------------------------------------------------------------
	function destroyCookies()
	{
		$cookietime = time()-3600;
		ob_start();
		setcookie("txp_name",  '', $cookietime, "/");
		setcookie("txp_email", '', $cookietime, "/");
		setcookie("txp_web",   '', $cookietime, "/");
		setcookie("txp_last",  '', $cookietime, "/");
		setcookie("txp_remember", '0', $cookietime + (365*25*3600), "/");
	}

// -------------------------------------------------------------
	function getComment()
	{
		global $dump;
		
		// comment spam filter plugins: call this function to fetch comment contents

		$c = psa( array(
			'parentid',
			'name',
			'email',
			'web',
			'message',
			'backpage',
			'remember',
			'contact',
			'subject',
			'captcha'
		) );

		$n = array();
		$custom_fields = array();
		
		$dump[][] = "<hr/>";
		
		foreach (stripPost() as $k => $v)
		{	
			if (preg_match('#^[A-Fa-f0-9]{32}$#', $k.$v))
			{
				$n[] = doSlash($k.$v);
			}
			
			if (preg_match('/^custom_/', $k))
			{
				$k = preg_replace('/^custom_/','',$k);
				$dump[][] = "$k:$v";
				$custom_fields[$k] = $v;
			}
		}
		
		$c['custom_fields'] = $custom_fields;
		
		$c['nonce'] = '';
		$c['secret'] = '';
		if (!empty($n)) {
			$rs = safe_row('nonce, secret', 'txp_discuss_nonce', "nonce in ('".join("','", $n)."')");
			$c['nonce'] = $rs['nonce'];
			$c['secret'] = $rs['secret'];
		}
		
		$c['message'] = ps(md5('message'.$c['secret']));
		
		return $c;
	}

// -------------------------------------------------------------
	function saveSignup($parentid=0) {
		
		$name     = strip_tags(gps('name'));
		$email    = filter_var(gps('email'),FILTER_VALIDATE_EMAIL);
		$parentid = (!$parentid) ? assert_int(gps('parentid')) : $parentid;
		$ip 	  = serverset('REMOTE_ADDR');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (!$email) {
			
			setcookie("txp_email",gps('email'),time()+5);
			setcookie("txp_name",$name,time()+5);
			
			return "Please enter a valid email address.";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// check for already existing email
		
		setcookie("txp_email",$email,time()+5);
		setcookie("txp_name",$name,time()+5);
		
		if ($isdup = safe_count("txp_discuss",
			"email = '$email' AND article_id = $parentid")) {
			
			return "Already signed up!";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$rootid = fetch("ID","txp_discuss","ParentID",0);
		
		// TODO: use txp_lib_ContentCreate.php
		
		safe_insert(
			"txp_discuss",
			"ParentID   = $rootid,
			 article_id	= $parentid,
			 Title		= 'Sign me up!',
			 Name		= 'sign-me-up',
			 Author		= '".doSlash($name)."',
			 email	  	= '$email',
			 ip			= '".doSlash($ip)."',
			 Status		= '4',
			 Posted		= now()"
		);
	}
	
// -------------------------------------------------------------
	function saveComment($comment_type='comment')
	{
		global $siteurl,$comments_moderate,$comments_sendmail,$txpcfg,
			$comments_disallow_images,$prefs,$pretext;
	
		$ref = serverset('HTTP_REFERRER');
		$in = getComment();
		
		$evaluator =& get_comment_evaluator();
		$contact_sent = 0;
		
		extract($in);
		
		if (!checkCommentsAllowed($parentid))
			txp_die(gTxt('comments_closed'), '403');

		$ip = serverset('REMOTE_ADDR');

		if (!checkBan($ip))
			txp_die(gTxt('you_have_been_banned'), '403');
			
		if (strlen($web)) {
			txp_die("website url field is disabled",'403');	// SPAM
		}
			
		if (preg_match('/http\:\/\//',$message)) {
			$_POST['error'] = "HTTP is not allowed!"; return; // maybe SPAM
		}
			
		$blacklisted = is_blacklisted($ip);
		if ($blacklisted)
			txp_die(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), '403');
		
		if (!$contact) {
		
			if (empty($captcha)) {
			
				$_POST['error'] = "Please enter the CAPTCHA word!"; return;
			
			} else {
				
				if (empty($_SESSION['captcha']) || trim(strtolower($captcha) != $_SESSION['captcha'])) {
					$_POST['error'] = "Invalid CAPTCHA. Please try again."; return;
				}
				
				unset($_SESSION['captcha']);
			}
		}
		
		$web = clean_url($web);
		$email = clean_url($email);
		$is_email = filter_var($email,FILTER_VALIDATE_EMAIL);
		if ($remember == 1 || ps('checkbox_type') == 'forget' && ps('forget') != 1)
			setCookies($name, $email, $web);
		else
			destroyCookies();

		$name = doSlash(strip_tags(deEntBrackets($name)));
		$web = doSlash(strip_tags(deEntBrackets($web)));
		$email = doSlash(strip_tags(deEntBrackets($email)));
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$pretext['backpage'] = $backpage;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// subject custom field
		
		if (isset($custom_fields['subject'])) { 
		
			$subject = $custom_fields['subject'];
			$other   = 'subject_other';
			
			if ($subject == 'other') {
				
				if (isset($custom_fields[$other]) and $custom_fields[$other]) {
					
					$subject = $custom_fields[$other];
					unset($custom_fields[$other]);
				}
			}
			
			unset($custom_fields['subject']);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		// other custom fields
		
		foreach($custom_fields as $k => $v) {
			
			if (is_array($v)) {
				
				$custom_fields[$k] = ucwords($k).': '.n.t.implode(n.t,$v);
				
			} elseif (strtolower(trim($v)) == 'other') {
			
				$other = $k.'_other';
				
				if (isset($custom_fields[$other]) and $custom_fields[$other]) {
					
					$custom_fields[$k] = ucwords($k).': '.$custom_fields[$other];
					unset($custom_fields[$other]);
					
				} else {
					
					$custom_fields[$k] = ucwords($k).': '.$v;
				}
			
			} else {
			
				if (strlen($v)) {
					$custom_fields[$k] = ucwords(str_replace('-',' ',$k)).': '.$v;
				} else {
					unset($custom_fields[$k]);
				}
			}
		}
		
		$custom_fields = implode(n,$custom_fields);
		
		if ($custom_fields) {
			$message .= n.n.$custom_fields;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -		
		
		$subject = trim(substr(trim($subject),0,1000));
		$message = trim(substr(trim($message),0,65535));
		$message2db = doSlash(markup_comment($message));

		$isdup = safe_row("Body_html AS message, Name AS name", "txp_discuss",
			"Name = '$name' AND Body_html = '$message2db' AND ip = '".doSlash($ip)."'");
		
		if (   ($prefs['comments_require_name'] && !trim($name))
			|| ($prefs['comments_require_email'] && !$is_email)
			|| (!trim($message)))
		{
			$evaluator -> add_estimate(RELOAD,1); // The error-messages are added in the preview-code
		}
		
		if ($isdup)
			$evaluator -> add_estimate(RELOAD,1); // FIXME? Tell the user about dupe?
		
		if ( ($evaluator->get_result() != RELOAD) && checkNonce($nonce) ) {
			
			callback_event('comment.save');
			$visible = $evaluator->get_result();
			if ($visible != RELOAD) {
				$parentid = assert_int($parentid);
				
				$rootid = fetch("ID","txp_discuss","ParentID",0);
				
				$author = $name;
				
				if ($contact) {
					
					$title  = $subject;
	
				} else {
					
					$title  = maxwords($message,30);
					$title  = preg_replace('/[\,\;\:\.]$/','',$title);
					$title .= (strlen($title) < strlen($message)) ? '...' : '';
				}
				
				$name = make_name($title);
				
				$rs = safe_insert(
					"txp_discuss",
					"article_id	= $parentid,
					 ParentID   = $rootid,
					 title 		= '$title',
					 Name		= '$name',
					 Author  	= '$author',
					 email	  	= '$email',
					 ip			= '".doSlash($ip)."',
					 Body		= '".doSlash($message)."',
					 Body_html	= '$message2db',
					 Status		= ".intval($visible).",
					 Posted		= now()"
				);
				
				if ($rs) {
					
					safe_update("txp_discuss_nonce", "used = 1", "nonce='".doSlash($nonce)."'");
					
					if ($prefs['comment_means_site_updated']) {
						update_lastmod();
					}
					
					/* if (preg_match('/http:\/\//',$message)) {
						$message = preg_replace('/(http:\/\/[^\s]+)/','<a href="'."$1".'">'."$1".'</a>',$message);
					} */
					
					if ($contact)
						mail_contact($subject, $message, $author, $email, $web, $parentid);
					else
						mail_comment($message, $author, $email, $web, $parentid, $rs);
					
					$updated = update_comments_count($parentid);

					$backpage = substr($backpage, 0, $prefs['max_url_len']);
					$backpage = preg_replace("/[\x0a\x0d#].*$/s",'',$backpage);
					if (preg_match("/^(http|\/)/",$backpage)) {
						$backpage = preg_replace("#(https?://[^/]+)/.*$#","$1",hu).$backpage;
					}
					
					if (defined('PARTLY_MESSY') and (PARTLY_MESSY))
					{	
						$backpage = permlinkurl_id($parentid);
					}
					
					if (!$contact) {
						$backpage .= ((strstr($backpage,'?')) ? '&' : '?') . 'commented='.(($visible==VISIBLE) ? '1' : '0');
					}
					
					txp_status_header('302 Found');
					
					if ($signup = assert_int(gps('signup',0))) {
						 saveSignup($signup);
					}
					
					if ($contact and $rs) {
						
						$_POST['sent'] = $rs;
						
						return;
					}
					
					if ($comments_moderate) {
						header('Location: '.$backpage.'#txpCommentInputForm');
					} else {
						header('Location: '.$backpage.'#c'.$rs);
					}
				
					log_hit('302');
					$evaluator->write_trace();
					exit;
				}
			}
		}
		
		$_POST['preview'] = RELOAD;	// Force another Preview
		
		//$evaluator->write_trace();
	}

// -------------------------------------------------------------
	class comment_evaluation {
		var $status;
		var $message;
		var $txpspamtrace = array();
		var $status_text = array();

		function comment_evaluation() {
			global $prefs;
			extract(getComment());
			$this->status = array( SPAM  => array(),
								   MODERATE => array(),
								   VISIBLE  => array(),
								   RELOAD  => array()
								);
			$this->status_text = array(	SPAM => gTxt('spam'),
									MODERATE => gTxt('unmoderated'),
									VISIBLE  => gTxt('visible'),
									RELOAD  => gTxt('reload')
								);
			$this->message = $this->status;
			$this -> txpspamtrace[] = "Comment on $parentid by $name (".safe_strftime($prefs['archive_dateformat'],time()).")";
			if ($prefs['comments_moderate'])
				$this->status[MODERATE][]=0.5;
			else
				$this->status[VISIBLE][]=0.5;
		}

		function add_estimate($type = SPAM, $probability = 0.75, $msg='') {
			global $production_status;

			if (!array_key_exists($type, $this->status))
				trigger_error(gTxt('unknown_spam_estimate'), E_USER_WARNING);

			$this -> txpspamtrace[] = "   $type; ".max(0,min(1,$probability))."; $msg";
			//FIXME trace is only viewable for RELOADS. Maybe add info to HTTP-Headers in debug-mode

			$this->status[$type][] = max(0,min(1,$probability));
			if (trim($msg)) $this->message[$type][] = $msg;
		}

		function get_result($result_type='numeric') {
			$result = array();
			foreach ($this->status as $key => $value)
				$result[$key] = array_sum($value)/max(1,count($value));
			arsort($result, SORT_NUMERIC);
			reset($result);
			return (($result_type == 'numeric') ? key($result) : $this->status_text[key($result)]);
		}
		function get_result_message() {
			return $this->message[$this->get_result()];
		}
		function write_trace() {
			global $prefs;
			$file = $prefs['tempdir'].DS.'evaluator_trace.php';
			if (!file_exists($file)) {
				$fp = fopen($file,'wb');
				if ($fp)
					fwrite($fp,"<?php return; ?".">\n".
					"This trace-file tracks saved comments. (created ".safe_strftime($prefs['archive_dateformat'],time()).")\n".
					"Format is: Type; Probability; Message (Type can be -1 => spam, 0 => moderate, 1 => visible)\n\n");
			} else {
				$fp = fopen($file,'ab');
			}
			if ($fp) {
				fwrite($fp, implode("\n", $this->txpspamtrace ));
				fwrite($fp, "\n  RESULT: ".$this->get_result()."\n\n");
				fclose($fp);
			}
		}
	}

	function &get_comment_evaluator() {
	    static $instance;

	    // If the instance is not there, create one
	    if(!isset($instance)) {
	        $instance = new comment_evaluation();
	    }
	    return $instance;
	}

// -------------------------------------------------------------
	function checkNonce($nonce)
	{
		if (!$nonce && !preg_match('#^[a-zA-Z0-9]*$#',$nonce))
			return false;
			// delete expired nonces
		safe_delete("txp_discuss_nonce", "issue_time < date_sub(now(),interval 10 minute)");
			// check for nonce
		return (safe_row("*", "txp_discuss_nonce", "nonce='".doSlash($nonce)."' and used = 0")) ? true : false;
	}

// -------------------------------------------------------------
	function checkBan($ip)
	{
		return (!fetch("ip", "txp_discuss_ipban", "ip", $ip)) ? true : false;
	}

// -------------------------------------------------------------
	function checkCommentsAllowed($id)
	{
		global $use_comments, $comments_disabled_after, $thisarticle;

		$id = intval($id);

		if (!$use_comments || !$id)
			return false;

		if (isset($thisarticle['thisid']) && ($thisarticle['thisid'] == $id) && isset($thisarticle['annotate']))
		{
			$Annotate = $thisarticle['annotate'];
			$uPosted  = $thisarticle['posted'];
		}
		else
		{
			extract(
				safe_row(
					"Annotate,unix_timestamp(Posted) as uPosted",
						"textpattern", "ID = $id"
				)
			);
		}

		if ($Annotate != 1)
			return false;

		if($comments_disabled_after) {
			$lifespan = ( $comments_disabled_after * 86400 );
			$timesince = ( time() - $uPosted );
			return ( $lifespan > $timesince );
		}

		return true;
	}

// -------------------------------------------------------------
		function comments_help()
	{
		return ('<a id="txpCommentHelpLink" href="http://rpc.textpattern.com/help/index.php?item=textile_comments&amp;language='.LANG.'" onclick="window.open(this.href, \'popupwindow\', \'width=300,height=400,scrollbars,resizable\'); return false;">'.gTxt('textile_help').'</a>');
	}

// -------------------------------------------------------------
	function mail_comment($message, $cname, $cemail, $cweb, $parentid, $discussid)
	{
		global $sitename, $comments_sendmail;

		if (!$comments_sendmail) return;
		$evaluator =& get_comment_evaluator();
		if ($comments_sendmail == 2 && $evaluator->get_result() == SPAM) return;

		$parentid = assert_int($parentid);
		$discussid = assert_int($discussid);
		$article = safe_row("Section, Posted, ID, url_title, AuthorID, Title", "textpattern", "ID = $parentid");
		extract($article);
		
		$columns = (column_exists('txp_users','RealName')) 
			? 'RealName, email'
			: 'Title AS RealName, email';
			
		extract(safe_row($columns, "txp_users", "name = '".doSlash($AuthorID)."'"));

		$out = gTxt('greeting')." $RealName,".n.n;
		$out .= str_replace('{title}',$Title,gTxt('comment_recorded')).n;
		$out .= permlinkurl_id($parentid).n;
		if (has_privs('discuss', $AuthorID))
			$out .= hu.'textpattern/index.php?event=discuss&step=edit&id='.$discussid.n;
		$out .= gTxt('status').": ".$evaluator->get_result('text').'. '.implode(',',$evaluator->get_result_message()).n;
		$out .= n;
		$out .= gTxt('comment_name').": $cname".n;
		$out .= gTxt('comment_email').": $cemail".n;
		$out .= gTxt('comment_web').": $cweb".n;
		$out .= gTxt('comment_comment').": $message";

		$subject = strtr(gTxt('comment_received'),array('{site}' => $sitename, '{title}' => $Title));

		$success = txpMail($email, $subject, $out, $cemail);
	}

// -------------------------------------------------------------------------------------
// new: email message format 

	function mail_contact($subject, $message, $cname, $cemail, $cweb, $parentid) 
	{
		global $sitename,$txp_user,$pretext;
		
		$parentid = assert_int($parentid);
		
		$myName = $txp_user;
		extract(safe_row("AuthorID,Title", "textpattern", "ID = '$parentid'"));
		
		$columns = (column_exists('txp_users','RealName')) 
			? 'RealName, email'
			: 'Title AS RealName, email';
		
		$row = safe_row($columns, "txp_users", "name = '$AuthorID'");
		
		$email = $from_email = $row['email'];
		$RealName = $from_RealName = $row['RealName'];
		
		$admin = safe_row($columns, "txp_users", "name = 'admin'");
		
		if ($admin) {
			
			$from_email = $admin['email'];
			$from_RealName = $admin['RealName'];
		}
		
		list($to_RealName,$email)    = get_custom_contact_email($email,'recipient');
		list($cc_RealName,$cc_email) = get_custom_contact_email('','cc');
		
		$cname = preg_replace('/[\r\n]/', ' ', $cname);
		$cemail = preg_replace('/[\r\n]/', ' ', $cemail);

		$out  = "$message\r\n\r\n";
		$out .= gTxt('comment_name').": $cname\r\n";
		$out .= gTxt('comment_email').": $cemail";
		
		$headers = "From: $from_RealName <$from_email>\r\n"
			."Reply-To: $cname <$cemail>\r\n"
			."X-Mailer: Textpattern\r\n"
			."Content-Transfer-Encoding: 8bit\r\n"
			."Content-Type: text/plain; charset=\"UTF-8\"\r\n";
			
		$headers = ($cc_email) ? "CC: $cc_RealName <$cc_email>\r\n".$headers : $headers;
		
		mail($email, $subject, $out, $headers);
	}

// -------------------------------------------------------------
	# deprecated, use fInput instead
	function input($type,$name,$val,$size='',$class='',$tab='',$chkd='')
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'fInput')), E_USER_NOTICE);
		$o = array(
			'<input type="'.$type.'" name="'.$name.'" id="'.$name.'" value="'.$val.'"',
			($size)	? ' size="'.$size.'"'	  : '',
			($class) ? ' class="'.$class.'"'	: '',
			($tab)	 ? ' tabindex="'.$tab.'"'	: '',
			($chkd)	? ' checked="checked"'	: '',
			' />'.n
		);
		return join('',$o);
	}

// -------------------------------------------------------------

	function get_custom_contact_email($email,$field) {
		
		$name = '';
		
		if (ps($field)) {
			
			$name = ps($field,'','/^[a-z0-9\-]+$/');
			
			if ($name) {
			
				// look for a user named '$item' in the txp_users table 
				
				$row = safe_row('email,title',"txp_users","Name = '$name' AND Trash = 0 AND Status IN (4,5)");
				
				if ($row) {
					
					$name  = $row['title'];
					$email = $row['email'];
				}
				
				// look for an article named '$item' in the textpattern table
				// that also has a custom field named 'email';
				
				$row = safe_row('c.text_val AS email,t.title',
					"textpattern AS t JOIN txp_content_value AS c ON t.ID = c.article_id",
					"t.Name = '$name' 
					 AND t.Trash = 0 
					 AND t.Status IN (4,5) 
					 AND c.field_name = 'email'
					 AND c.text_val != ''
					 AND c.status = 1");
				
				if ($row) {
					
					$name  = $row['title'];
					$email = $row['email'];
				}
			}
		};
		
		return array($name,$email);
	}
?>

<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 
                (M O S T L Y)  D E P R E C A T E D  AS  OF  1.0RC4

 *	IMPORTANT:  Most settings in this file  (all that is in  $txpac)  have moved 
	into the  Database. This file remains here  mainly for  not breaking updates 
 *	for people that are coming from older revisions (up to and including 1.0RC3)! 
	If you would like to change any of these settings, you can do so in Advanced 
 *	Preferences	of your Textpattern admin panel.
	
 *	Only the Permission-Settings at the bottom this file are still actively used,
	and these will be moved to the db before the next release 

$HeadURL: http://svn.textpattern.com/current/textpattern/lib/admin_config.php $
$LastChangedRevision: 787 $
 
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


// Textpattern admin options
// unless stated otherwise, 0 = false, 1 = true
$txpac = array(
// -------------------------------------------------------------
// bypass the Txp CSS editor entirely

	'edit_raw_css_by_default'     => 1,


// -------------------------------------------------------------
// php scripts on page templates will be parsed 

	'allow_page_php_scripting'    => 1,

// -------------------------------------------------------------
// php scripts in article bodies will be parsed 

	'allow_article_php_scripting' => 1,

// -------------------------------------------------------------
// use Textile on link titles and descriptions 

	'textile_links'               => 0,


// -------------------------------------------------------------
// in the article categories listing in the organise tab

	'show_article_category_count' => 1,


// -------------------------------------------------------------
// xml feeds display comment count as part of article title

	'show_comment_count_in_feed'  => 1,


// -------------------------------------------------------------
// include articles or full excerpts in feeds
// 0 = full article body
// 1 = excerpt
		
	'syndicate_body_or_excerpt'   => 1,


// -------------------------------------------------------------
// include (encoded) author email in atom feeds
		
	'include_email_atom'          => 1,
	

// -------------------------------------------------------------
// each comment received updates the site Last-Modified header 

	'comment_means_site_updated'  => 1,


// -------------------------------------------------------------
// comment email addresses are encoded to hide from spambots
// but if you never want to see them at all, set this to 1

	'never_display_email'         => 0,


// -------------------------------------------------------------
// comments must enter name and/or email address

	'comments_require_name'       => 1,
	'comments_require_email'      => 1,


// -------------------------------------------------------------
// show 'excerpt' pane in write tab

	'articles_use_excerpts'       => 1,


// -------------------------------------------------------------
// show form overrides on article-by-article basis

	'allow_form_override'         => 1,


// -------------------------------------------------------------
// whether or not to attach article titles to permalinks
// e.g., /article/313/IAteACheeseSandwich

	'attach_titles_to_permalinks' => 1,


// -------------------------------------------------------------
// if attaching titles to permalinks, which format?
// 0 = SuperStudlyCaps
// 1 = hyphenated-lower-case

	'permalink_title_format'      => 1,


// -------------------------------------------------------------
// number of days after which logfiles are purged
		
	'expire_logs_after'           => 7,


// -------------------------------------------------------------
// plugins on or off
		
	'use_plugins'                 => 1,

// -------------------------------------------------------------
// use custom fields for articles - must be in 'quotes', and 
// must contain no spaces
		
	'custom_1_set'                => 'custom1',
	'custom_2_set'                => 'custom2',
	'custom_3_set'                => '',
	'custom_4_set'                => '',
	'custom_5_set'                => '',
	'custom_6_set'                => '',
	'custom_7_set'                => '',
	'custom_8_set'                => '',
	'custom_9_set'                => '',
	'custom_10_set'               => '',

// -------------------------------------------------------------
// ping textpattern.com when an article is published
		
	'ping_textpattern_com'        => 1,

// -------------------------------------------------------------
// use DNS lookups in referrer log
		
	'use_dns'        => 1,

// -------------------------------------------------------------
// load plugins in the admin interface
		
	'admin_side_plugins'        => 1,

// -------------------------------------------------------------
// use rel="nofollow" on comment links
		
	'comment_nofollow'        => 1,
	
// -------------------------------------------------------------
// use encoded email on atom feeds id, instead of domain name
// (if you plan to move this install to another domain, you should use this)
		
	'use_mail_on_feeds_id'	   =>0,

// -------------------------------------------------------------
// maximum url length before it should be considered malicious
		
	'max_url_len'               => 200,

// -------------------------------------------------------------
// Spam DNS RBLs

	'spam_blacklists'          => 'sbl.spamhaus.org',

);

// -------------------------------------------------------------
$txp_permissions = array(
	'admin'                       => '1,2,3,4,5,6',
	'admin.edit'                  => '1',
	'admin.list'                  => '1,2,3',
	'article.delete.own'          => '1,2,3,4',
	'article.delete'              => '1,2',
	'article.edit'                => '1,2,3',
	'article.edit.published'      => '1,2,3',
	'article.edit.own'            => '1,2,3,4,5,6',
	'article.edit.own.published'  => '1,2,3,4',
	'article.publish'             => '1,2,3,4',
	'article.php'                 => '1,2',
	'article'                     => '1,2,3,4,5,6',
	'list'                        => '1,2,3,4,5,6', //likely the same as for article.
	'category'                    => '1,2,3,4',
	'category.edit'               => '1,2,3',
	'category.edit.published'     => '1,2,3',
	'category.edit.own'           => '1,2,3',
	'category.edit.own.published' => '1,2,3',
	'category.delete'             => '1,2',
	'category.delete.own'         => '1,2,3',
	'css'                    	  => '1,2,3,4',
	'css.edit'               	  => '1,2,3',
	'css.edit.published'     	  => '1,2,3',
	'css.edit.own'           	  => '1,2,3',
	'css.edit.own.published'      => '1,2,3',
	'css.delete'                  => '1,2',
	'css.delete.own'              => '1,2,3',
	'diag'                        => '1,2',
	'discuss'                     => '1,2,3,4',
	'file'                        => '1,2,3,4,6',
	'file.edit'                	  => '1,2,6',
	'file.edit.published'         => '1,2,6',
	'file.edit.own'               => '1,2,3,4,6',
	'file.edit.own.published'     => '1,2,3,4,6',
	'file.delete'                 => '1,2',
	'file.delete.own'             => '1,2,3,4,6',
	'file.publish'                => '1,2,3,4,6',
	'form'                    	  => '1,2,3,4',
	'form.edit'               	  => '1,2,3',
	'form.edit.published'     	  => '1,2,3',
	'form.edit.own'           	  => '1,2,3',
	'form.edit.own.published'     => '1,2,3',
	'form.delete'                 => '1,2',
	'form.delete.own'             => '1,2,3',
	'image'                       => '1,2,3,4,6',
	'image.edit'                  => '1,2,3,6',
	'image.edit.published'        => '1,2,3,6',
	'image.edit.own'              => '1,2,3,4,6',
	'image.edit.own.published'    => '1,2,3,4,6',
	'image.delete'                => '1,2',
	'image.delete.own'            => '1,2,3,4,6',
	'import'                      => '1,2',
	'link'                        => '1,2,3,4',
	'link.edit'                   => '1,2,3',
	'link.edit.published'         => '1,2,3',
	'link.edit.own'               => '1,2,3',
	'link.edit.own.published'     => '1,2,3',
	'link.delete'                 => '1,2',
	'link.delete.own'             => '1,2,3',
	'custom'                      => '1,2,3,4',
	'custom.edit'                 => '1,2,3',
	'custom.edit.published'       => '1,2,3',
	'custom.edit.own'             => '1,2,3',
	'custom.edit.own.published'   => '1,2,3',
	'custom.delete'               => '1,2',
	'custom.delete.own'           => '1,2,3',
	'comment'                     => '1,2,3,4',
	'comment.edit'                => '1,2,3',
	'comment.edit.published'      => '1,2,3',
	'comment.edit.own'            => '1,2,3',
	'comment.edit.own.published'  => '1,2,3',
	'comment.delete'              => '1,2',
	'comment.delete.own'          => '1,2,3',
	'log'                         => '1,2,3', // more?
	'page'                    	  => '1,2,3,4',
	'page.edit'               	  => '1,2,3',
	'page.edit.published'     	  => '1,2,3',
	'page.edit.own'           	  => '1,2,3',
	'page.edit.own.published'     => '1,2,3',
	'page.delete'                 => '1,2',
	'page.delete.own'             => '1,2,3',
	'log'                         => '1,2,3,4',
	'log.edit'                    => '1,2,3',
	'log.edit.published'          => '1,2,3',
	'log.edit.own'                => '1,2,3',
	'log.edit.own.published'      => '1,2,3',
	'log.delete'                  => '1,2',
	'log.delete.own'              => '1,2,3',
	'sites'                       => '1,2,3,4',
	'sites.edit'                  => '1,2,3',
	'sites.edit.published'        => '1,2,3',
	'sites.edit.own'              => '1,2,3',
	'sites.edit.own.published'    => '1,2,3',
	'sites.delete'                => '1,2',
	'sites.delete.own'            => '1,2,3',
	'users'                       => '1,2,3,4',
	'users.edit'                  => '1,2,3',
	'users.edit.published'        => '1,2,3',
	'users.edit.own'              => '1,2,3',
	'users.edit.own.published'    => '1,2,3',
	'users.delete'                => '1,2',
	'users.delete.own'            => '1,2,3',
	'plugin'                      => '1,2,3,4',
	'plugin.edit'                 => '1,2,3',
	'plugin.edit.published'       => '1,2,3',
	'plugin.edit.own'             => '1,2,3',
	'plugin.edit.own.published'   => '1,2,3',
	'plugin.delete'               => '1,2',
	'plugin.delete.own'           => '1,2,3',
	'prefs'                       => '1,2',
	'utilities'                   => '1,2,3,4,5,6',
	'section'                     => '1,2,3,6',
	'tab.admin'                   => '1,2,3,4,5,6',
	'tab.content'                 => '1,2,3,4,5,6',
	'tab.extensions'              => '1,2',
	'tab.presentation'            => '1,2,3,6',
	'tag'                         => '1,2,3,4,5,6'
);

$GLOBALS['normalizeChars'] = array(
    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 
    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 
    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ő'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 
    'Û'=>'U', 'Ü'=>'U', 'Ű'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 
    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 
    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ő'=>'o', 'ø'=>'o', 'ù'=>'u', 
    'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
);

?>

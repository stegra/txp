<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/theme/remora/remora.php $
$LastChangedRevision: 3214 $
*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

theme::based_on('remora');

class mini_theme extends remora_theme
{
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	function html_head($event)
	{
		$out  = '';
		$path = $this->dirpath('mini').DS.'js';
		
		$scripts = dirlist($path,'js');
		
		foreach($scripts as $script) {
		
			$out .= '<script src="'.$this->url.'js/'.$script.'" type="text/javascript"></script>'.n;
		}
		
		return parent::html_head($event).n.$out.n;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	function footer()
	{
		return '<div id="end_page"></div>';
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	function manifest()
	{
		global $prefs;
		return array(
			'author' 		=> 'Stephanie Gratzer',
			'author_uri' 	=> 'http://textpattern.com/',
			'version' 		=> $prefs['version'],
			'description' 	=> 'Textpattern Mini Theme',
			'help' 			=> '',
		);
	}
}
?>

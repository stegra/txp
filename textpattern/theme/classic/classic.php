<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/theme/classic/classic.php $
$LastChangedRevision: 3191 $
*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

class classic_theme extends theme
{
	function html_head($event,$mode='view')
	{
		global $step,$base;
		$path  = $this->dirpath('classic');
		$links = array($this->url.'textpattern.css');
		
		$css1 = 'css'.DS.'page_'.$event.'_'.$step.'.css';
		$css2 = 'css'.DS.'page_'.$step.'.css';
		$css3 = 'css'.DS.'page_'.$event.'.css';
		
		if ($event == 'image' and $step == 'save') {
		
			$css1 = 'css'.DS.'page_image_edit.css';
		
		} elseif (in_list($step,'image_edit,image_save')) {
			
			$css1 = 'css'.DS.'page_image_edit.css';
		
		} elseif (in_list($event,'page,form,css')) {
		
			$links = array($this->url."css/page_".$event.".css");
		
		} elseif ($mode != 'edit' and $event == 'sites') {
		
			$links = array($this->url."css/page_".$event.".css");
		}
		
		if (in_list($event,'log')) {
			
			$links = array($this->url."css/page_".$event.".css");
			
		} elseif ($mode == 'edit' and $event != 'image') {
		
			if (is_file($path.DS.$css1)) {
				$links[] = $this->url.$css1;
		    } else {
		    	$links[] = $this->url."css/page_list.css";
				$links[] = $this->url."css/content_edit.css";
			}	
			
		} elseif ($mode == 'list' and $event == 'custom') {
			
			$links[] = $this->url."css/page_custom.css";
				
		} elseif (is_file($path.DS.$css1)) {
			
			$links[] = $this->url.$css1;
		
		} elseif (is_file($path.DS.$css2)) {
			
			$links[] = $this->url.$css2;
		
		} elseif (is_file($path.DS.$css3)) {
			
			$links[] = $this->url.$css3;
		
		} elseif ($mode == 'list') {
			
			$links[] = $this->url."css/page_list.css";
		}
		
		if (is_file($path.DS.$css3)) {
			
			if (!in_array($this->url.$css3,$links)) {
				$links[] = $this->url.$css3;
			}
		}
		
		foreach($links as $key => $href) {
		
			$links[$key] = '<link rel="stylesheet" type="text/css" href="'.$base.$href.'"/>';
		}
		
		return implode(n,$links);
	}

	function header($event)
	{
		global $WIN;
		
		$window = "&win=".$WIN['winid'];
		
		$out[] = '<table id="pagetop" align="center" >'.n.
		  '<tr id="branding">'.n.'<td align="center"><h1 id="textpattern">Textpattern</h1></td>'.n.
		  /* '<!--<td id="navpop">'.navPop(1).'</td>-->'.n.'</tr>'.n.*/
		  (comment_line()).
		  '<tr id="nav-primary"><td align="center" class="tabs" colspan="2">';

 		if (!$this->is_popup)
 		{
 			$out[] = '<table cellpadding="3" cellspacing="0" align="center">'.n.
			'<tr><td id="messagepane">&nbsp;'.$this->announce($this->message).'</td>';
			 
			$secondary = '';
 			foreach ($this->menu as $name => $tab)
 			{	
				$tc = ($tab['active']) ? 'tabup' : 'tabdown';
				$atts=' class="'.$name.' '.$tc.'"';
				$hatts=' href="?event='.$tab['event'].$window.'" class="plain"';
				$label = ($tab['active']) ? '<b>'.$tab['label'].'</b>' : $tab['label'];
      			$out[] = tda(tag($label, 'a', $hatts), $atts);
      			
      			if ($tab['active'] && !empty($tab['items']))
				{
					$secondary = '</td></tr>'.(comment_line()).'<tr id="nav-secondary"><td align="center" class="tabs" colspan="2">'.n.
					'<table cellpadding="3" cellspacing="0" align="center">'.n.
					'<tr>';
					foreach ($tab['items'] as $num => $item)
					{
						$tc = ($item['active']) ? 'tabup' : 'tabdown2';
						$self = ($event == $item['event']) ? '&self=1' : '';
						$label = ($item['active']) ? '<b>'.$item['label'].'</b>' : $item['label'];
						$secondary .= '<td class="item'.$num.' '.$tc.'"><a href="?event='.$item['event'].$self.$window.'" class="plain">'.$label.'</a></td>';
					}
					$secondary .= '</tr></table>';
				}
			}
			
			$out[] = '<td id="view-site" class="view-site tabdown"><a href="'.hu.'index.html?preview" class="plain" target="_blank">'.gTxt('tab_preview_site').'</a></td>';
			$out[] = '</tr></table>';
	 		$out[] = $secondary;
 		}
		$out[] = '</td></tr></table>';
 		return join(n, $out);
	}

	function footer()
	{
		global $txp_user;

		$out[] = '<div id="footer">'.n.graf('Textpattern &#183; '.txp_version).
			n.t.'<a href="http://textpattern.com/" id="mothership"><img src="'.$this->url.'txp_img/carver.gif" width="60" height="48" border="0" alt="" /></a>'.n;

		if ($txp_user)
		{
			$out[] = t.graf(gTxt('logged_in_as').' <a title="'.gTxt('logout').'" href="index.php?logout=1">'.htmlspecialchars($txp_user).'</a>', ' id="moniker"');
		}

		$out[] = '</div>';

		return join(n, $out);;
	}

	function announce($thing)
	{
 		// $thing[0]: message text
 		// $thing[1]: message type, defaults to "success" unless empty or a different flag is set

		if ($thing === '') return '';

		if (!is_array($thing) || !isset($thing[1]))
 		{
 			$thing = array($thing, 0);
 		}

 		switch ($thing[1])
 		{
 			case E_ERROR:
 				$class = 'error';
 				break;
 			case E_WARNING:
 				$class = 'warning';
 				break;
 			default:
 				$class = 'success';
 				break;
 		} 
 		
 		return $html = "<span id='message' class='$class'>".gTxt($thing[0]).'</span>'; 
 		
 		// Try to inject $html into the message pane no matter when announce()'s output is printed
 		// FIXME: javascript does not work
 		
 		$js = addslashes($html);
 		$js = "$(document).ready( function(){
	 		$('#messagepane').html('".$js."');
			$('#messagepane #message.error').fadeOut(800).fadeIn(800);
			$('#messagepane #message.warning').fadeOut(800).fadeIn(800);
		} )";
		
		return script_js(str_replace('</', '<\/', $js), $html);
	}

	function manifest()
	{
		global $prefs;
		return array(
			'author' 		=> 'Team Textpattern',
			'author_uri' 	=> 'http://textpattern.com/',
			'version' 		=> $prefs['version'],
			'description' 	=> 'Textpattern Classic Theme',
			'help' 			=> 'http://textpattern.com/admin-theme-help',
		);
	}
}
?>

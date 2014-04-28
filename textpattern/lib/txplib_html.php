<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/lib/txplib_html.php $
$LastChangedRevision: 3255 $
*/

// -------------------------------------------------------------------------------------
// change: html formatting

	function end_page($event)
	{
		global $WIN,$html,$event,$txp_user,$app_mode,$theme,$smarty;
		
		if ($app_mode != 'async' && $event != 'tag') {
			
			// $smarty->assign('txp_user',$txp_user);
			// $smarty->assign('text_logged_in_as',gTxt('logged_in_as'));
			// $smarty->assign('text_logout',gTxt('logout'));
			
			$smarty->assign('event',$event);
			$smarty->assign('window',$WIN['winid']);
			$smarty->assign('notes',print_notes());
			$smarty->assign('footer',pluggable_ui('admin_side', 'footer', $theme->footer()));
			
			callback_event('admin_side', 'body_end');
			
			if ($html) 
				$html .= $smarty->fetch('page_end.tpl');
			else
				$smarty->display('page_end.tpl');
		}
	}

// -------------------------------------------------------------
/*	function end_page()
	{
		global $txp_user, $event, $app_mode, $theme;

		if ($app_mode != 'async' && $event != 'tag') {
			echo pluggable_ui('admin_side', 'footer', $theme->footer());
			callback_event('admin_side', 'body_end');
			echo n.'</body>'.n.'</html>';
		}
	}
*/
// -------------------------------------------------------------

	function column_head($value, $sort = '', $event = '', $is_link = '', $dir = '', $crit = '', $method = '', $class = '')
	{
		return column_multi_head( array(
					array ('value' => $value, 'sort' => $sort, 'event' => $event, 'is_link' => $is_link,
						   'dir' => $dir, 'crit' => $crit, 'method' => $method)
				), $class);
	}

// -------------------------------------------------------------------------------------
// change: added step param
// change: added current sort param

	function column_head_old($value, $sort='', $current_event='', $islink='', $dir='', $step='list',$current_sort='')
	{
		$class = ($sort == $current_sort) ? 'sort' : '';
		
		$o = '<td class="small"><nobr><strong>';
			if ($islink) {
				$o.= '<a class="'.$class.'" href="index.php';
				$o.= ($sort) ? "?sort=$sort":'';
				$o.= ($dir) ? a."dir=$dir":'';
				$o.= ($current_event) ? a."event=$current_event":'';
				$o.= a."step=$step".'">';
			}
		$o .= gTxt($value);
			if ($islink) { $o .= "</a>"; }
		$o .= '</strong></nobr></td>';
		return $o;
	}

// -------------------------------------------------------------

	function column_multi_head($head_items, $class='')
	{
		$o = n.t.'<th'.($class ? ' class="'.$class.'"' : '').'><nobr>';
		$first_item = true;
		foreach ($head_items as $item)
		{
			if (empty($item)) continue;
			extract(lAtts(array(
				'value'		=> '',
				'sort'		=> '',
				'event'		=> '',
				'is_link'	=> '',
				'dir'		=> '',
				'crit'		=> '',
				'method'	=> '',
			),$item));

			$o .= ($first_item) ? '' : ', '; $first_item = false;

			if ($is_link)
			{
				$o .= '<a href="index.php?step=list';

				$o .= ($event) ? a."event=$event" : '';
				$o .= ($sort) ? a."sort=$sort" : '';
				$o .= ($dir) ? a."dir=$dir" : '';
				$o .= ($crit) ? a."crit=$crit" : '';
				$o .= ($method) ? a."search_method=$method" : '';

				$o .= '">';
			}

			$o .= gTxt($value);

			if ($is_link)
			{
				$o .= '</a>';
			}
		}
		$o .= '</nobr></th>';

		return $o;
	}

// -------------------------------------------------------------
	function hCell($text='',$caption='',$atts='')
	{
		$text = ('' === $text) ? sp : $text;
		return tag($text,'th',$atts);
	}

// -------------------------------------------------------------
	function sLink($event,$step,$linktext,$class='')
	{
		$c = ($class) ? ' class="'.$class.'"' : '';
		return '<a href="?event='.$event.a.'step='.$step.'"'.$c.'>'.$linktext.'</a>';
	}

// -------------------------------------------------------------------------------------
// change: added target param
// change: added third thing val combined param

	function eLink($event,$step='',$thing='',$value='',$linktext,$thing2='',$val2='',$thingval3='',$target='')
	{
		return join('',array(
			'<a', 
			($target) ? ' target="'.$target.'"' : '',
			            ' href="?event='.$event,
			($step)   ? a.'step='.$step : '',
			($thing)  ? a.''.$thing.'='.urlencode($value) : '',
			($thing2) ? a.''.$thing2.'='.urlencode($val2) : '',
			($thingval3) ? a.''.$thingval3 : '',
			'">'.$linktext.'</a>'
		));
	}

// -------------------------------------------------------------
/*	function eLink($event,$step='',$thing='',$value='',$linktext,$thing2='',$val2='')
	{
		return join('',array(
			'<a href="?event='.$event,
			($step) ? a.'step='.$step : '',
			($thing) ? a.''.$thing.'='.urlencode($value) : '',
			($thing2) ? a.''.$thing2.'='.urlencode($val2) : '',
			'">'.escape_title($linktext).'</a>'
		));
	}
*/
// -------------------------------------------------------------
	function wLink($event,$step='',$thing='',$value='')
	{
		return join('',array(
			'<a href="index.php?event='.$event,
			($step) ? a.'step='.$step : '',
			($thing) ? a.''.$thing.'='.urlencode($value) : '',
			'" class="dlink">'.sp.'!'.sp.'</a>'
		));
	}

// -------------------------------------------------------------
// change: added title and class attribute to form tag

	function dLink($event, $step, $thing, $value, $verify = '', $thing2 = '', $thing2val = '', $get = '', $remember = null) {
		if ($remember) {
			list($page, $sort, $dir, $crit, $search_method) = $remember;
		}

		if ($get) {
			$url = '?event='.$event.a.'step='.$step.a.$thing.'='.urlencode($value);

			if ($thing2) {
				$url .= a.$thing2.'='.urlencode($thing2val);
			}

			if ($remember) {
				$url .= a.'page='.$page.a.'sort='.$sort.a.'dir='.$dir.a.'crit='.$crit.a.'search_method='.$search_method;
			}

			return join('', array(
				'<a href="'.$url.'" class="dlink" onclick="return verify(\'',
				($verify) ? gTxt($verify) : gTxt('confirm_delete_popup'),
				'\')">×</a>'
			));
		}

		return join('', array(
			'<form method="post" title="'.gTxt('delete').'" class="delete" action="index.php" onsubmit="return confirm(\''.gTxt('confirm_delete_popup').'\');">',
			 fInput('submit', '', '×', 'smallerbox'),
			 eInput($event).
			 sInput($step),
			 hInput($thing, $value),
			 ($thing2) ? hInput($thing2, $thing2val) : '',
			 ($remember) ? hInput('page', $page) : '',
			 ($remember) ? hInput('sort', $sort) : '',
			 ($remember) ? hInput('dir', $dir) : '',
			 ($remember) ? hInput('crit', $crit) : '',
			 ($remember) ? hInput('search_method', $search_method) : '',
			'</form>'
		));
	}

// -------------------------------------------------------------
	function aLink($event,$step,$thing,$value,$thing2,$value2)
	{
		$o = '<a href="?event='.$event.a.'step='.$step.
			a.$thing.'='.urlencode($value).a.$thing2.'='.urlencode($value2).'"';
		$o.= ' class="alink">+</a>';
		return $o;
	}

// -------------------------------------------------------------------------------------
// change: allow for using on edit image page

	function prevnext_link($name,$event,$step,$id,$titling='')
	{
		global $WIN;
		
		$id = ($event == 'image') ? 'id='.$id : 'ID='.$id;
		
		return '<a href="?event='.$event.a.'step='.$step.a.$id.a.'win='.$WIN['winid'].
			'" class="navlink" title="'.$titling.'">'.$name.'</a> ';
	}

// -------------------------------------------------------------
/*	function prevnext_link($name,$event,$step,$id,$titling='')
	{
		return '<a href="?event='.$event.a.'step='.$step.a.'ID='.$id.
			'" class="navlink" title="'.$titling.'">'.$name.'</a> ';
	}
*/
// -------------------------------------------------------------------------------------
// old change: step param (?)
/*
	function PrevNextLink($event,$topage,$label,$type,$sort='',$dir='',$step='list')
	{
		return join('',array(
			'<a href="?event='.$event.a.'step='.$step.a.'page='.$topage,
			($sort) ? a.'sort='.$sort : '',
			($dir) ? a.'dir='.$dir : '',
			'" class="navlink">',
			($type=="prev") ? '&#8249;'.sp.$label : $label.sp.'&#8250;',
			'</a>'
		));
	}
*/
// -------------------------------------------------------------

	function PrevNextLink($event, $page, $label, $type, $sort = '', $dir = '', $crit = '', $search_method = '')
	{
		return '<a href="?event='.$event.a.'step=list'.a.'page='.$page.
			($sort ? a.'sort='.$sort : '').
			($dir ? a.'dir='.$dir : '').
			($crit ? a.'crit='.$crit : '').
			($search_method ? a.'search_method='.$search_method : '').
			'" class="navlink">'.
			($type == 'prev' ? '&#8249;'.sp.$label : $label.sp.'&#8250;').
			'</a>';
	}

// -------------------------------------------------------------

	function nav_form($event, $page, $numPages, $sort, $dir, $crit, $search_method, $total=0, $limit=0)
	{
		global $theme;
		if ($crit && $total > 1)
		{
			$out[] = $theme->announce(
				gTxt('showing_search_results',
					array(
						'{from}'	=> (($page - 1) * $limit) + 1,
						'{to}' 		=> min($total, $page * $limit),
						'{total}' 	=> $total
						)
					)
				);
		}

		if ($numPages > 1)
		{
			$option_list = array();

			for ($i = 1; $i <= $numPages; $i++)
			{
				if ($i == $page)
				{
					$option_list[] = '<option value="'.$i.'" selected="selected">'."$i/$numPages".'</option>';
				}

				else
				{
					$option_list[] = '<option value="'.$i.'">'."$i/$numPages".'</option>';
				}
			}

			$nav = array();

			$nav[] = ($page > 1) ?
				PrevNextLink($event, $page - 1, gTxt('prev'), 'prev', $sort, $dir, $crit, $search_method).sp :
				tag('&#8249; '.gTxt('prev'), 'span', ' class="navlink-disabled"').sp;

			$nav[] = '<select name="page" class="list" onchange="submit(this.form);">';
			$nav[] = n.join(n, $option_list);
			$nav[] = n.'</select>';
			$nav[] = '<noscript> <input type="submit" value="'.gTxt('go').'" class="smallerbox" /></noscript>';

			$nav[] = ($page != $numPages) ?
				sp.PrevNextLink($event, $page + 1, gTxt('next'), 'next', $sort, $dir, $crit, $search_method) :
				sp.tag(gTxt('next').' &#8250;', 'span', ' class="navlink-disabled"');

			$out[] = '<form class="prev-next" method="get" action="index.php">'.
				n.eInput($event).
				( $sort ? n.hInput('sort', $sort).n.hInput('dir', $dir) : '' ).
				( $crit ? n.hInput('crit', $crit).n.hInput('search_method', $search_method) : '' ).
				join('', $nav).
				'</form>';
		}
		else
		{
			$out[] = graf($page.'/'.$numPages, ' class="prev-next"');
		}

		return join(n, $out);
	}

// -------------------------------------------------------------
	function startSkelTable()
	{
		return
		'<table width="300" cellpadding="0" cellspacing="0" style="border:1px #ccc solid">';
	}

// -------------------------------------------------------------
	function startTable($type,$align='',$class='',$p='',$w='')
	{
		if ('' === $p) $p = ($type=='edit') ? 3 : 0;
		$align = (!$align) ? 'center' : $align;
		$class = ($class) ? ' class="'.$class.'"' : '';
		$width = ($w) ? ' width="'.$w.'"' : '';
		return '<table cellpadding="'.$p.'" cellspacing="0" border="0" id="'.
			$type.'" align="'.$align.'"'.$class.$width.'>'.n;
	}

// -------------------------------------------------------------
	function endTable ()
	{
		return n.'</table>'.n;
	}

// -------------------------------------------------------------
	function stackRows()
	{
		foreach(func_get_args() as $a) { $o[] = tr($a); }
		return join('',$o);
	}

// -------------------------------------------------------------
// change: added style param

	function td($content='',$width='',$class='',$id='',$style='')
	{
		$content = ('' === $content) ? '&#160;' : $content;
		
		$atts[] = ($width)  ? ' width="'.$width.'"' : '';
		$atts[] = ($class)  ? ' class="'.$class.'"' : '';
		$atts[] = ($id)  	? ' id="'.$id.'"' 		: '';
		$atts[] = ($style)  ? ' style="'.$style.'"' : '';
		
		return t.tag($content,'td',join('',$atts)).n;
	}

// -------------------------------------------------------------
/*	function td($content='',$width='',$class='',$id='')
	{
		$content = ('' === $content) ? '&#160;' : $content;
		$atts[] = ($width)  ? ' width="'.$width.'"' : '';
		$atts[] = ($class)  ? ' class="'.$class.'"' : '';
		$atts[] = ($id)  ? ' id="'.$id.'"' : '';
		return t.tag($content,'td',join('',$atts)).n;
	}
*/
// -------------------------------------------------------------
	function tda($content,$atts='')
	{
		return tag($content,'td',$atts);
	}

// -------------------------------------------------------------
	function tdtl($content,$atts='')
	{
		return tag($content,'td',' style="vertical-align:top;text-align:left;padding:8px"'.$atts);
	}

// -------------------------------------------------------------
	function tr($content,$atts='')
	{
		return tag($content,'tr',$atts);
	}

// -------------------------------------------------------------
	function tdcs($content,$span,$width="",$class='')
	{
		return join('',array(
			t.'<td align="left" valign="top" colspan="'.$span.'"',
			($width) ? ' width="'.$width.'"' : '',
			($class) ? ' class="'.$class.'"' : '',
			">$content</td>\n"
		));
	}

// -------------------------------------------------------------
	function tdrs($content,$span,$width="")
	{
		return join('',array(
			t.'<td align="left" valign="top" rowspan="'.$span.'"',
			($width) ? ' width="'.$width.'"' : '',">$content</td>".n
		));
	}

// -------------------------------------------------------------

	function fLabelCell($text, $help = '', $label_id = '')
	{
		$help = ($help) ? popHelp($help) : '';

		$cell = gTxt($text).' '.$help;

		if ($label_id)
		{
			$cell = '<label for="'.$label_id.'">'.$cell.'</label>';
		}

		return tda($cell,' class="noline" style="text-align: right; vertical-align: middle;"');
	}

// -------------------------------------------------------------

	function fInputCell($name, $var = '', $tabindex = '', $size = '', $help = '', $id = '')
	{
		$pop = ($help) ? sp.popHelp($name) : '';

		return tda(
			fInput('text', $name, $var, 'edit', '', '', $size, $tabindex, $id).$pop
		,' class="noline"');
	}

// -------------------------------------------------------------
	function tag($content,$tag,$atts='')
	{
		if (is_array($atts)) {
			
			foreach ($atts as $name => $value) {
				if (strlen($value)) {
					$atts[$name] = $name.'="'.$value.'"';
				} else {
					unset($atts[$name]);	
				}
			}
			
			$atts = ' '.implode(' ',$atts);
		}
		
		if ($tag == 'img') {
			
			return '<'.$tag.$atts.'/>';
		}
		
		return ('' !== $content) ? '<'.$tag.$atts.'>'.$content.'</'.$tag.'>' : '';
	}

// -------------------------------------------------------------
	function graf ($item,$atts='')
	{
		return tag($item,'p',$atts);
	}

// -------------------------------------------------------------
	function hed($item,$level=2,$atts='')
	{
		return tag($item,'h'.$level,$atts);
	}

// -------------------------------------------------------------
	function href($item,$href='#',$atts='')
	{
		return tag($item,'a',$atts.' href="'.$href.'"');
	}

// -------------------------------------------------------------
	function strong($item)
	{
		return tag($item,'strong');
	}

// -------------------------------------------------------------
	function span($item)
	{
		return tag($item,'span');
	}

// -------------------------------------------------------------
	function htmlPre($item)
	{
		return '<pre>'.tag($item,'code').'</pre>';
	}

// -------------------------------------------------------------
	function comment($item)
	{
		return '<!-- '.$item.' -->'.n;
	}
	
// -------------------------------------------------------------
	function comment_line($string='- ',$repeat=120)
	{
		return n.n.'<!-- '.trim(str_pad('',$repeat,$string)).' -->'.n;
	}

// -------------------------------------------------------------
	function line($string='-',$repeat=120)
	{
		return n.n.trim(str_pad('',$repeat,$string)).n;
	}
	
// -------------------------------------------------------------
	function small($item)
	{
		return tag($item,'small');
	}

// -------------------------------------------------------------
	function assRow($array, $atts ='')
	{
		foreach($array as $a => $b) $o[] = tda($a,' width="'.$b.'"');
		return tr(join(n.t,$o), $atts);
	}

// -------------------------------------------------------------
	function assHead()
	{
		$array = func_get_args();
		foreach($array as $a) $o[] = hCell(gTxt($a));
		return tr(join('',$o));
	}

// -------------------------------------------------------------
// change: added title attribute

	function popHelp($help_var, $width = '', $height = '')
	{
		return '<a title="'.gTxt('help').'" '.
			' href="http://rpc.textpattern.com/help/?item='.$help_var.a.'language='.LANG.'" class="pophelp">?</a>';
	}

/*
	function popHelp($help_var, $width = '', $height = '')
	{
		return '<a title="'.gTxt('help').'" target="_blank"'.
			' href="http://rpc.textpattern.com/help/?item='.$help_var.a.'language='.LANG.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;" class="pophelp">?</a>';
	}
*/
// -------------------------------------------------------------------------------------
// new

	function popModHelp($help,$winW='',$winH='') 
	{
		extract(get_prefs('path_to_site'));
		
		if (substr($help,0,7) == 'custom/') 
			$helpfile = $path_to_site.'/textpattern/help/'.$help.'.html';
		else
			$helpfile = txpath.'/help/'.$help.'.html';	
		
		if (is_file($helpfile)) {
		
			return join('',array(
				' <a target="_blank" href="help/'.$help.'.html"',
				' onclick="',
				"window.open(this.href, 'popupwindow', 'width=",
				($winW) ? $winW : 400,
				',height=',
				($winH) ? $winH : 400,
				',scrollbars,resizable\'); return false;" class="pophelp">?</a>'
			));
			
		} else
			
			return '';
	}

// -------------------------------------------------------------
// change: added title attribute

	function popHelpSubtle($help_var, $width = '', $height = '')
	{
		return '<a title="'.gTxt('help').'" target="_blank"'.
			' href="http://rpc.textpattern.com/help/?item='.$help_var.a.'language='.LANG.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;">?</a>';
	}

// -------------------------------------------------------------

	function popTag($var, $text, $width = '', $height = '')
	{
		return '<a target="_blank"'.
			' href="?event=tag'.a.'tag_name='.$var.'"'.
			' onclick="popWin(this.href'.
			($width ? ', '.$width : '').
			($height ? ', '.$height : '').
			'); return false;">'.$text.'</a>';
	}

// -------------------------------------------------------------

	function popTagLinks($type)
	{
		global $txpcfg;

		include txpath.'/lib/taglib.php';

		$arname = $type.'_tags';

		$out = array();

		$out[] = n.'<ul class="plain-list small">';

		if (!isset($$arname)) return '';
		
		foreach ($$arname as $a)
		{
			$out[] = n.t.tag(popTag($a,gTxt('tag_'.$a)), 'li');
		}

		$out[] = n.'</ul>';

		return join('', $out);
	}

//-------------------------------------------------------------
	function messenger($thing, $thething='', $action='')
	{
		return gTxt($thing).sp.strong($thething).sp.gTxt($action);
	}

// -------------------------------------------------------------

	function pageby_form($event, $val)
	{
		$vals = array(
			15  => 15,
			25  => 25,
			50  => 50,
			100 => 100
		);

		$select_page = selectInput('qty', $vals, $val,'', 1);

		// proper localisation
		$page = str_replace('{page}', $select_page, gTxt('view_per_page'));

		return form(
			'<div style="margin: auto; text-align: center;">'.
				$page.
				eInput($event).
				sInput($event.'_change_pageby').
				'<noscript> <input type="submit" value="'.gTxt('go').'" class="smallerbox" /></noscript>'.
			'</div>'
		);
	}

// -------------------------------------------------------------

class Form {
	
	public  $event;
	public  $step;
	public  $class;
	public  $id;
	public  $label;
	public  $pophelp;
	public  $input = array();
	
	public function __construct($event, $step, $class, $id, $label, $pophelp='') { 
		
		$this->event   = $event;
		$this->step    = $step;
		$this->label   = $label;
		$this->class   = $class;
		$this->id 	   = $id;
		$this->pophelp = $pophelp;
		$this->input   = array();
	}
	
	public function addInput($name,$type,$label,$class='',$id='',$value='') {
	
		$id = ($id) ? $id : $name.count($this->input);
		
		$this->input[] = 
		    '<label for="'.$id.'">'.$label.'</label>'.n.
			'<input id="'.$id.'" class="'.$class.'" type="'.$type.'" name="'.$name.'" value="'.$value.'"/>';
	}
	
	public function __toString() {
		
		$class = ($this->class) ? ' class="'.$this->class.'"' : '';
		$out = array();
		
		$out[] = '<form'.$class.' method="post" action="index.php">';
		$out[] = implode(n,$this->input);
		$out[] = '</form>';
		
		return implode(n,$out);
	}
}

// -------------------------------------------------------------

class UploadForm extends Form {
	
	public $max_file_size = '1000000';
	public $label_id = '';
	public $class = 'upload';
	
	function __construct($event, $step, $label, $pophelp, $id='') {
		
		$this->event   = $event;
		$this->step    = $step;
		$this->label   = $label;
		$this->pophelp = $pophelp;
		$this->id 	   = $id;
	}
	
	function __toString() {
		
		$class = ($this->class) ? ' class="'.$this->class.'"' : '';
		$label_id = ($this->label_id) ? $this->label_id : $this->event.'-upload';
	
		return n.n.'<form'.$class.' method="post" enctype="multipart/form-data" action="index.php">'.
		n.'<div>'.
		
		(!empty($this->max_file_size)? n.hInput('MAX_FILE_SIZE', $this->max_file_size): '').
		
		n.eInput($this->event).
		n.sInput($this->step).
		n.hInput('id', $this->id).
		n.implode(n,$this->input).
		n.graf(
			'<label for="'.$label_id.'">'.$this->label.'</label>'.sp.sp.n.
				fInput('file', 'thefile', '', 'edit', '', '', '', '', $label_id).sp.n.
				fInput('submit', '', gTxt('upload'), 'smallerbox')
		).

		n.'</div>'.
		n.'</form>';
	}
}

// -------------------------------------------------------------

class SearchForm extends Form {
	
	 public $crit, $methods, $method, $default_method;
	 
	 function __toString() {
	 	
	 	$method = ($this->method) ? $this->method : $this->default_method;
	 	
	 	return n.n.form(
			graf(
				'<label for="'.$this->event.'-search">'.gTxt('search').'</label>'.sp.
				selectInput('search_method', $this->methods, $method, '', '', $this->event.'-search').sp.
				fInput('text', 'crit', $this->crit, 'edit', '', '', '15').
				eInput($this->event).
				sInput($this->step).
				fInput('submit', 'search', gTxt('go'), 'smallerbox')
			)
		, '', '', 'get', 'search-form');
		
	 }
}

// -------------------------------------------------------------
// change: line break after label

	function upload_form($label, $pophelp, $step, $event, $id = '', $max_file_size = '1000000', $label_id = '', $class = 'upload-form')
	{
		global $sort, $dir, $page, $search_method, $crit;

		$class = ($class) ? ' class="'.$class.'"' : '';

		$label_id = ($label_id) ? $label_id : $event.'-upload';

		$argv = func_get_args();
		return pluggable_ui($event.'_ui', 'upload_form',
			n.n.'<form'.$class.' method="post" enctype="multipart/form-data" action="index.php">'.
			n.'<div>'.

			(!empty($max_file_size)? n.hInput('MAX_FILE_SIZE', $max_file_size): '').
			n.eInput($event).
			n.sInput($step).
			n.hInput('id', $id).

			n.hInput('sort', $sort).
			n.hInput('dir', $dir).
			n.hInput('page', $page).
			n.hInput('search_method', $search_method).
			n.hInput('crit', $crit).

			n.graf(
				'<label for="'.$label_id.'">'.$label.'</label>'.sp.popHelp($pophelp).sp.
					fInput('file', 'thefile', '', 'edit', '', '', '', '', $label_id).sp.
					fInput('submit', '', gTxt('upload'), 'smallerbox')
			).

			n.'</div>'.
			n.'</form>',
			$argv);
	}

//-------------------------------------------------------------

	function search_form($event, $step, $crit, $methods, $method, $default_method)
	{
		$method = ($method) ? $method : $default_method;

		return n.n.form(
			graf(
				'<label for="'.$event.'-search">'.gTxt('search').'</label>'.sp.
				selectInput('search_method', $methods, $method, '', '', $event.'-search').sp.
				fInput('text', 'crit', $crit, 'edit', '', '', '15').
				eInput($event).
				sInput($step).
				fInput('submit', 'search', gTxt('go'), 'smallerbox')
			)
		, '', '', 'get', 'search-form');
	}

//-------------------------------------------------------------

	function pref_text($name, $val, $id = '')
	{
		$id = ($id) ? $id : $name;

		$vals = array(
			USE_TEXTILE          => gTxt('use_textile'),
			CONVERT_LINEBREAKS   => gTxt('convert_linebreaks'),
			CONVERT_PARAGRAPHS   => 'Convert paragraphs',
			LEAVE_TEXT_UNTOUCHED => gTxt('leave_text_untouched')
		);

		return selectInput($name, $vals, $val, '', '', $id);
	}

//-------------------------------------------------------------
	function dom_attach($id, $content, $noscript='', $wraptag='div', $wraptagid='')
	{

		$c = addcslashes($content, "\r\n\"\'");
		$c = preg_replace('@<(/?)script@', '\\x3c$1script', $c);
		$js = <<<EOF
var e = document.getElementById('{$id}');
var n = document.createElement('{$wraptag}');
n.innerHTML = '{$c}';
n.setAttribute('id','{$wraptagid}');
e.appendChild(n);
EOF;

		return script_js($js, $noscript);
	}

//-------------------------------------------------------------
	function script_js($js, $noscript='')
	{
		$out = '<script type="text/javascript">'.n.
			'<!--'.n.
			trim($js).n.
			'// -->'.n.
			'</script>'.n;
		if ($noscript)
			$out .= '<noscript>'.n.
				trim($noscript).n.
				'</noscript>'.n;
		return $out;
	}

//-------------------------------------------------------------
	function toggle_box($classname, $form=0) {

		$name = 'cb_toggle_'.$classname;
		$i =
			'<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.
			(cs('toggle_'.$classname) ? 'checked="checked" ' : '').
			'class="checkbox" onclick="toggleClassRemember(\''.$classname.'\');" />'.
			' <label for="'.$name.'">'.gTxt('detail_toggle').'</label> '.
			script_js("setClassRemember('".$classname."');addEvent(window, 'load', function(){setClassRemember('".$classname."');});");
		if ($form)
			return n.form($i);
		else
			return n.$i;
	}

//-------------------------------------------------------------
	function cookie_box($classname, $form=1) {

		$name = 'cb_'.$classname;
		$val = cs('toggle_'.$classname) ? 1 : 0;

		$i =
			'<input type="checkbox" name="'.$name.'" id="'.$name.'" value="1" '.
			($val ? 'checked="checked" ' : '').
			'class="checkbox" onclick="setClassRemember(\''.$classname.'\','.(1-$val).');submit(this.form);" />'.
			' <label for="'.$name.'">'.gTxt($classname).'</label> ';

		if ($form) {
			$args = empty($_SERVER['QUERY_STRING']) ? '' : '?'.htmlspecialchars($_SERVER['QUERY_STRING']);
			return '<form class="'.$name.'" method="post" action="index.php'.$args.'">'.$i.eInput(gps('event')).n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript></form>';
		} else {
			return n.$i;
		}
	}

//-------------------------------------------------------------
	function fieldset($content, $legend='', $id='') {
		$a_id = ($id ? ' id="'.$id.'"' : '');
		return tag(trim(tag($legend, 'legend').n.$content), 'fieldset', $a_id);
	}

//--------------------------------------------------------------------------------------
// new

	function date_selector($name='',$date='',$year='')
	{
		$start_year   = ($year) ? $year : 1999;
		$current_year = date('Y');
		$date		  = substr($date,0,10);
		
		$year  = ($date) ? substr($date,0,4) : $current_year;
		$month = ($date) ? substr($date,5,2) : '';
		$day   = ($date) ? substr($date,8,2) : '';
		
		$months = array(
			'01' => 'Jan',
			'02' => 'Feb',
			'03' => 'Mar',
			'04' => 'Apr',
			'05' => 'May',
			'06' => 'Jun',
			'07' => 'Jul',
			'08' => 'Aug',
			'09' => 'Sep',
			'10' => 'Oct',
			'11' => 'Nov',
			'12' => 'Dec'
		);
		
		for ($i=1; $i<=31; $i++) {
			if ($i < 10)
				$days['0'.$i] = $i;
			else
				$days[$i] = $i;
		}
			
		$years = array();
		
		for ($y=$start_year; $y<=$current_year+3; $y++) {
			$years[$y] = substr($y,2,2);
		}
		
		$onchange = "dateSelect('$name')";
		
		/* OLD
		$out = fInput('hidden', $name, $date,'edit ').n
			.selectInput($name.'-month',$months,$month,1,$onchange,'','datetime').n
			.selectInput($name.'-day',$days,$day,1,$onchange,'','datetime').n 
			.selectInput($name.'-year',$years,$year,1,$onchange,'','datetime'). n;
		*/
		
		$out = fInput('hidden', $name, $date,'edit','','','','',$name).n
			.selectInput($name.'-month',$months,$month,1,$onchange,$name.'-month','datetime').n
			.selectInput($name.'-day',$days,$day,1,$onchange,$name.'-day','datetime').n 
			.selectInput($name.'-year',$years,$year,1,$onchange,$name.'-year','datetime'). n;

		return $out;
	}
	
//--------------------------------------------------------------------------------------
	function time_selector($name='',$time='')
	{
		$hour = '-';
		$min  = '-';
		$pm   = 1;
		
		if ($time) {
			
			$time  = substr($time,0,5);
			
			$hour  = (int)substr($time,0,2);
			$min   = (int)substr($time,3,2);
			
			$hour  = ($hour > 12) ? $hour - 12 : $hour; 
			$hour  = ($hour == 0) ? 12 : $hour;
			
			$pm	   = ($hour >= 12) ? 1 : 0;
		}
		
		$hours = array(
			'-'	 => '',
			'1'  => '1',
			'2'  => '2',
			'3'  => '3',
			'4'  => '4',
			'5'  => '5',
			'6'  => '6',
			'7'  => '7',
			'8'  => '8',
			'9'  => '9',
			'10' => '10',
			'11' => '11',
			'12' => '12'
		);
		
		$minutes = array(
			'-'	 => '',
			'00' => '00',
			'15' => '15',
			'30' => '30',
			'45' => '45'
		);
		
		$ampm = array('am','pm');
		
		$onchange = "timeSelect('$name')";
		
		$out = fInput('hidden', $name, $time,'edit').n
			.selectInput($name.'-hour',$hours,$hour,0,$onchange,'','datetime').n
			.selectInput($name.'-min',$minutes,$min,0,$onchange,'','datetime').n 
			.selectInput($name.'-pm',$ampm,$pm,0,$onchange,'','datetime'). n;
				
		return $out;
	}

//--------------------------------------------------------------------------------------

	Class Element 
	{ 
		public $excerpt = false;
		public $width   = 640;
		public $height  = 400;
		
		function replace($matches) { 
		
			if ($matches[1] == 'www.youtube.com') {
			
				$src    = 'http://www.youtube.com/v/'.$matches[2].'&hl=en_US&fs=1';
				
				if ($this->excerpt) {
					$this->width  = 320;
					$this->height = 197;
				}
				
				return '<object width="'.$this->width.'" height="'.$this->height.'">
					<param name="movie" value="'.$src.'"></param>
					<param name="allowFullScreen" value="true"></param>
					<param name="allowscriptaccess" value="always"></param>
					<embed src="'.$src.'" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$this->width.'" height="'.$this->height.'"></embed>
				</object>';
			}
					
			return '';
		} 
	} 

//--------------------------------------------------------------------------------------
// test indent_tag

$testhtml = '

	<html>
		<head><title>Content</title></head>
		<body>
			<div>ABC</div>
			
			<input type="hidden" name="event" value="image"/>
			<input type="hidden" name="step" value="image_insert"/>
			<hr />
			<img src="asas.jpg"/>
		</body>
	</html>
	
';

$tagname = "([a-z1-6\-]+)";
$tagatts = "([^\>]+?)?";

$testhtml = preg_replace_callback('/'.'\s*'.'<([\/\!]?)'.$tagname.'\s?'.$tagatts.'(\/?)'.'>'.'\s*'.'/','indent_tag',$testhtml);

// echo pre(htmlentities($testhtml));

// exit;

//--------------------------------------------------------------------------------------
	function indent_tag($matches) {
	
		static $indent = array(n);
		static $script = false;
		
		// pre($matches);
		
		$block2   = array('html','head','style','script','body','div','ul','ol','select','table','form','tr');
		$block1   = array();
		$inline   = array('span','br','a','i','u','b','strong','em','del','sup','sub');
		$oneline  = array('h1','h2','h3','h4','h5','h6','li');
		$noindent = array('html','head','body');

		// - - - - - - - - - - - - - - - - - - - - - - - - - -
		
	 // $tag     = trim($matches[0]);
	    $tag     = $matches[0];
		$open    = $matches[1] == '';
		$close   = $matches[1] == '/';
		$comment = $matches[1] == '!';
		$name    = $matches[2];
		$closed  = ($matches[3] == '/' or $matches[4] == '/');
		
		if ($name == 'br') return $tag;
		
		if ($name == 'script' and $close) $script = false; 
		
		if ($script) return $tag;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (in_array($name,$noindent)) { 
		
			$indent = array(n);
		}
		
		if (in_array($name,$inline)) { 
		
			// $tag = ($open) ? ' '.$tag : $tag.' ';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - -
			
		if ($open) {
		
			if (!in_array($name,$inline)) {
			
				$tag = implode('',$indent).$tag;
				
				if (in_array($name,$block2)) {
					
					$tag = n.$tag;
				}
				
				if (in_array($name,$oneline)) {
					
					$tag = n.$tag;
				}
				
				if ($name == 'tr') {
					
					$tag = $tag.n.n;
				}
				
				if (!$closed) {
					array_push($indent,t);
				}
			}
		}
		
		if ($close) {
			
			if (!in_array($name,$inline)) {
			
				if (!in_array($name,$noindent)) {
			
					array_pop($indent);
				}
				
				if (in_array($name,$block1)) {
				
					$tag = implode('',$indent).$tag;
				}
				
				if (in_array($name,$block2)) {
				
					$tag = n.implode('',$indent).$tag;
				}
			}
		}
		
		if ($comment) {
			
			$tag = n.implode('',$indent).$tag;
		} 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($name == 'script' and $open)  $script = true; 
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return $tag;
	}

//--------------------------------------------------------------------------------------
	function compact_style(&$html) {
	
		$start = '<style(([^>]+)?)>';
		$end   = '<\/style>';
		
		$old = "/{$start}[^\<]+{$end}/";
		
		$new = (preg_match($old,$html,$matches)) ? $matches[0] : '';
		
		if ($new) {
			$new = preg_replace("/[\s]+/", " ", $new);
			$new = preg_replace("/($start|(} ))/", "$1\r\t\t", $new);
			$new = preg_replace("/($end)/", "\r\t$1", $new);
		}
		
		return preg_replace($old,$new,$html);
	}

//--------------------------------------------------------------------------------------
// add path and level to to body class attribute
// if it does not already have a class

	function add_body_class(&$html) {
		
		global $pretext,$prefs;
		
		$atts  = array();
		$class = array();
		
		if (preg_match('/\<body[^\>]*\>/',$html,$match)) {
			
			preg_match_all('/\s+([a-z\-]+)\=\"(.+?)\"/',$match[0],$matches);
			
			if (count($matches[0])) {
				$atts = array_flip($matches[1]);
				foreach($atts as $name => $key) {
					if ($name == 'class') $class[] = $matches[2][$key];
					$atts[$name] = $matches[2][$key];
				}
			}
			
			if (isset($prefs['languages']) and $prefs['languages']) {
			
				$class[] = 'lg-'.$pretext['lg'];
			}
			
			if (PREVIEW) {
				$class[] = 'preview';
			}
			
			if ($pretext['path']) {
				$class[] = trim(str_replace('/',' ',$pretext['path']));
			}
			
			$path = array_reverse(explode('/',$pretext['path']));
			
			// ---------------------------------------------------------
			// page type 
			
			if ($path[0] == 'index') {
				
				array_shift($path);
				
				$class[] = 'article-list';
				
			} else {
			
				$class[] = 'individual-article';
			}
			
			// ---------------------------------------------------------
			// path level 
			
			$class[] = 'level-'.count($path);
			
			// ---------------------------------------------------------
			// path of page template
			
			$page_id = $pretext['page'];
			
			$page = safe_row('Name,ParentID,Level','txp_page',"ID = $page_id");
			
			$page_path = array($page['Name']);
			
			if ($page['Level'] > 2) {
				
				$page_path[] = fetch('Name','txp_page','ID',$page['ParentID']);
			}
			
			$page_path = array_reverse($page_path);
			
			$class[] = 'page-'.implode('-',$page_path);
			
			// ---------------------------------------------------------
			
			$atts['class'] = implode(' ',$class); 
			
			foreach($atts as $name => $value) {
				$atts[$name] = $name.'="'.trim($value).'"';
			}
			
			$atts = implode(' ',$atts);
			
			$html = preg_replace('/\<body[^\>]*\>/','<body '.$atts.'>',$html);
		}
		
		return $html;
	}

//--------------------------------------------------------------------------------------

	function tidy_html_head(&$html) {
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// indent tags
		
		$tag_name = "(head|script|link|meta|title)";
		$tag_atts = "([^\>]+?)?";

		$html = preg_replace_callback(
			'/'.'\s*'.'<([\/\!]?)'.$tag_name.'\s?'.$tag_atts.'(\/?)'.'>'.'\s*'.'/',
			'indent_tag',$html);

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// remove double spacing
		
		$html = preg_replace('/\n\t?\n\t/',"\n\t",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// script tags
		
		$html = preg_replace('/>\s+(<\/script>)\s+/',">$1\n\t",$html);
		$html = preg_replace('/(javascript">)/',"$1\n",$html);
		$html = preg_replace('/\}\s+(<\/script>)/',"}\n\t\n\t$1",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// head tag
		
		$html = preg_replace('/>\s+(<\/?head>)/',">\n$1",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// clear browser of cached javascript and css
		
		if (is_file(txpath.'/tmp/.clear-browser-cache')) {
			$clear_cache = date('ymdHi',filemtime(txpath.'/tmp/.clear-browser-cache'));
			$html = preg_replace('/\.(js|css)\"/','.$1?'.$clear_cache.'"',$html);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		return $html;
	}
	
//--------------------------------------------------------------------------------------

	function tidy_html(&$html) {
	
		global $event, $path_to_site, $siteurl, $pretext, $prefs;
		
		if (in_list($event,'page,form,css')) return $html;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 		// remove empty attributes
		
		$html = preg_replace('/\s(class|id|style|xmlns)=[\"\'](\s+)?[\"\']/','',$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
 		// remove empty lists/items
		
		$html = preg_replace('/<(ul|ol|li)>(\s+)?<\/\1>/','',$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// add log id to body tag
		
		if (isset($pretext['logid'])) {
			
			$html = preg_replace('/\<body(\s|\>)/','<body data-logid="'.$pretext['logid'].'"'."$1",$html);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// add a class attribute for body tag
		
		$html = add_body_class($html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// close img and hr tags
		
		$html = preg_replace('/\<(img|hr|link|meta)([^\>]+)?(?<!\/)\>/','<\1\2/>',$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// remove repeating slashes from hrefs
		
		$html = preg_replace_callback(
			'/(href=")(http:\/\/)?(.+?)(")/',
			create_function(
            	'$matches',
            	'return $matches[1].$matches[2].preg_replace("/[\/]+/","/",$matches[3]).$matches[4];'
        	),
        	$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// strip spaces and line breaks from class attributes
		
		$html = preg_replace_callback(
			'/(class=")([\w\d-\s]+?)(")/',
			create_function(
            	'$matches',
            	'return $matches[1].trim(preg_replace("/\s+/"," ",$matches[2])).$matches[3];'
        	),
        	$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// indent html tags
		
		$tag_name = "([a-z1-6\-]+)";
		$tag_atts = "([^\>]+?)?";

		$html = preg_replace_callback(
			'/'.'\s*'.'<([\/\!]?)'.$tag_name.'\s?'.$tag_atts.'(\/?)'.'>'.'\s*'.'/',
			'indent_tag',$html);

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// remove html tags from title tag content
		
		$start = strpos($html,'<title>') + 7;
		$end   = strpos($html,'</title>') - 1;
		$title = substr($html,$start,$end-$start);
		$title = preg_replace('/<\/?[a-z]+>/','',$title);
		$html  = substr_replace($html,$title,$start,$end-$start);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// put line break after title tag
		
		$html = preg_replace('/(<\/title>)/',"$1\n\t",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// put line break after comment line
		
		$html = preg_replace('/(\-\-\>)/',"$1\n",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// put line breaks after opening script tags
		
		$html = preg_replace('/(<script[^\>]*>)/',"$1\n\n\t",$html);
		
		// put line breaks after closing script tags in the head 
		
		$html = preg_replace('/(>\s*<\/script>)\n+/',"$1\n",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		
		$html = compact_style($html);
		
		$html = preg_replace('/(<style)/',"\n\t$1",$html);
		$html = preg_replace('/\t\r\t(<\/style>)/',"$1",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// trim spaces inside li and p tags
		
		$html = preg_replace('/(<(li|p)[^\>]*>)\s+</',"$1<",$html);
		$html = preg_replace('/>\s+(<\/(li|p)>)/',">$1",$html);
		
		// put line breaks between link tags
		
		$html = preg_replace('/(\/>)(<link)/',"$1\n\t$2",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// remove spaces from empty tags
		
		$html = preg_replace('/(<([a-z1-6]+)\s?([^\>]+?)?'.'>)\s+(<\/\2>)/',"$1$4",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// remove empty lines between comment lines
		
		$html = preg_replace('/(\-\-\>)(\n\n\n)(\t+)(\<\!\-\-)/',"$1\n$3$4",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// remove double spaced lines
		
		$html = preg_replace('/\n(\t*)\n(\t*)\n/',"\n$1\n",$html);

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// clean up after Textile
		
		// remove spaces before and after quotes
		$html = preg_replace('/(&\#82(20|16);)\s+/',"$1",$html);
		$html = preg_replace('/\s+(&\#82(21|17);)/',"$1",$html);
		
		// remove space between anchor tag and punctuation
		$html = preg_replace('/(<\/a>)\s([\.\?\!])/',"$1$2",$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$html = preg_replace_callback('/href\=\"\/admin\/([a-z0-9\_\/]+)"/','expand_admin_url',$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// add base url to all admin links
		
		if ($prefs['base']) {
		
			$html = preg_replace('/(src|href)\=(\"|\')\/admin\//',"$1=$2".$prefs['base'],$html);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// add site url to links
		
		// $html = preg_replace('/(src|href)\=\"\//',"$1=\"http://".$siteurl.'/',$html);
		
		$html = preg_replace('/(rel|href|src|action|content)\=\"\/(\~[a-z0-9\-]+\/)?/',"$1=\"http://".$siteurl.'/',$html);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
		// add 'preview' to url 
		/*
		if (txpinterface == 'public' and PREVIEW) {
			
			$html = preg_replace_callback('/(\.html)(\?|\#|\")/',create_function(
           		'$matches',
            	"return (\$matches[2] == '?') 
            		? \$matches[1].\$matches[2].'preview&' 
            		: \$matches[1].'?preview'.\$matches[2];"
        	),$html);
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// custom clean 
		/*
		if (is_file($path_to_site.'/textpattern/custom/include/clean.php')) {
			
			include $path_to_site.'/textpattern/custom/include/clean.php';
			
			$html = custom_clean($html);
		}
		*/
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// remove all css and scripts for .htm requests

		if (isset($pretext) and preg_match('/\.htm$/',$pretext['req'])) {
			
			// link, style tags and inline styles
			$html = preg_replace('/<link(.+?)>\s?/','',$html);
			$html = preg_replace('/ style=".+?"/','',$html);
			
			// add old school grey background
			$html = preg_replace('/<body(.+)?'.'>/','<body style="background-color:#EEE"\1>',$html);
			
			// change html extensions
			$html = preg_replace('/\.html"/','.htm"',$html);
			
			// remove multi-line script and style tags
			if (class_exists('DOMDocument')) {
			
				$domDocument = new DOMDocument();
				
				$domDocument->loadHTML($html);
				
				$domDocument = removeTagName($domDocument,'script');
				$domDocument = removeTagName($domDocument,'style');
				
				$html = $domDocument->saveHTML();
			}
		}
			
		return $html;
	}

//--------------------------------------------------------------------------------------
// simple textile

	function expand_admin_url($matches) 
	{	
		global $siteurl;
		
		$path = $matches[1];
		
		$regexp = array(
			'/^([a-z]+)$/'								=> 'index.php?event=$1', 									
			'/^([a-z]+)\/([a-z]+)$/'					=> 'index.php?event=$1&step=$2', 							
			'/^([a-z]+)\/([a-z]+)\/([a-z]+)$/'			=> 'index.php?event=$1&step=$2&sort=$3&dir=asc',			
			'/^([a-z]+)\/(\d+)$/'						=> 'index.php?event=$1&id=$2',							
			'/^([a-z]+)\/([a-z]+)\/(\d+)$/'				=> 'index.php?event=$1&step=$2&id=$3',					
			'/^([a-z]+)\/([a-z]+)\/(\d+)\/([a-z]+)$/'	=> 'index.php?event=$1&step=$2&id=$3&sort=$4&dir=asc'	
		);
		
		foreach ($regexp as $find => $replace) {
			
			if ($path != $matches[1]) break;
			
			$path = preg_replace($find,$replace,$matches[1]);
		}
		
		return 'href="http://'.$siteurl.'/admin/'.$path.'&win=new&mini=1" class="admin-link"';
	}
	
//--------------------------------------------------------------------------------------
// simple textile

	function textile_simple($text) 
	{	
		$text = escape_title($text);
		
		$tags = array(
			'*'  => 'b',
			'_'  => 'i',
		 /* '-'	 => 'del', */
		 /*	'+'  => 'ins', */
			'^'	 => 'sup',
			'~'  => 'sub',
			'@'  => 'code'
		);
		
		$content = '([^\*]+?)';
		
		foreach ($tags as $search => $replace) {
			
			$start = '\\'.$search;
			$end   = '\\'.$search;
			
			$replace = "<$replace>".'$1'."</$replace>";
			
			$text = preg_replace('/'.$start.$content.$end.'/',$replace,$text);
		}
		
		return $text;
	}

//--------------------------------------------------------------------------------------

	function nl2p($text) 
	{
		$paragraphs = explode(n.n,$text);
			
		foreach ($paragraphs as $key => $para) {
			
			$lines = explode(n,$para);
			
			$paragraphs[$key] = implode('<br/>'.n,$lines); 
		}
		
		return '<p>'.implode('</p>'.n.n.'<p>',$paragraphs).'</p>';
	} 

?>


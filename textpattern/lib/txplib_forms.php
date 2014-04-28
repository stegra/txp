<?php

/*
$HeadURL: https://textpattern.googlecode.com/svn/releases/4.2.0/source/textpattern/lib/txplib_forms.php $
$LastChangedRevision: 3256 $
*/

// -----------------------------------------------------------------------------

	function radioSet($vals, $field, $var, $tabindex = '', $id = '')
	{
		$id = ($id) ? $id.'-'.$field : $field;

		foreach ($vals as $a => $b)
		{
			$out[] = '<input type="radio" id="'.$id.'-'.$a.'" name="'.$field.'" value="'.$a.'" class="radio"';
			$out[] = ($a == $var) ? ' checked="checked"' : '';
			$out[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
			$out[] = ' /><label for="'.$id.'-'.$a.'">'.$b.'</label> ';
		}

		return join('', $out);
	}

// -----------------------------------------------------------------------------
// new
// maybe the same as radioSet()

	function radioSelectInput($name="", $array="", $value="", $onchange='',$class='') 
	{
		$class = ($class) ? "radio $class" : "radio";
		
		$out = '<div class="'.$class.'">';
		
		foreach ($array as $avalue => $alabel) {
			$selected = ($avalue == $value || $alabel == $value)
			?	' checked="yes"'
			:	'';
			$alabel = str_replace('&amp;#160;','&#160;',htmlspecialchars($alabel));
			$alabel = str_replace('&amp;minus;','&minus;',$alabel);
			$out .= t.'<div class="option"><input type="radio" name="'.$name.'" value="'.htmlspecialchars($avalue).'"'.$selected.'/>'.
					'<span>'.$alabel.'</span></div>'.n;
		}
		
		$out .= '</div>'.n;
		
		return $out;
	}
	
// -----------------------------------------------------------------------------

	function yesnoRadio($field, $var, $tabindex = '', $id = '')
	{
		$vals = array(
			'0' => gTxt('no'),
			'1' => gTxt('yes')
		);
		return radioSet ($vals, $field, $var, $tabindex, $id);
	}

// -----------------------------------------------------------------------------

	function onoffRadio($field, $var, $tabindex = '', $id = '')
	{
		$vals = array(
			'0' => gTxt('off'),
			'1' => gTxt('on')
		);

		return radioSet ($vals, $field, $var, $tabindex, $id);
	}
	
// -----------------------------------------------------------------------------
// change: add $class param before the $check_type param
// 		   leave &#160; and &minus; chars intact
// regular parameter list: ($name='', $array='', $value='', $blank_first='', $onchange='', $id='', $check_type=false)
	
	function selectInput($name='', $array='', $value='', $blank_first='', $onchange='', $id='', $class='', $check_type=false)
	{
		$out = array();

		$selected = false;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// for plugin compatibility: if 7th param is a boolean or integer
		// then it is for check_type otherwise a string for class
		
		if (is_bool($class) or is_int($class)) {
			$check_type = $class;
		} else { 
			$class = ($class) ? "list $class" : "list";
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($blank_first == '2' and isset($array[''])) {
		
			$array[''] = '';
			$blank_first = 0;
		}
		
		foreach ($array as $avalue => $alabel)
		{
			if ($check_type) {
				if ($avalue === $value || $alabel === $value) {
					$sel = ' selected="selected"';
					$selected = true;
				} else {
					$sel = '';
				}
			}

			else {
				if ($avalue == $value || $alabel == $value) {
					$sel = ' selected="selected"';
					$selected = true;
				} else {
					$sel = '';
				}
			}
			
			$alabel = str_replace('&amp;#160;','&#160;',htmlspecialchars($alabel));
			$alabel = str_replace('&amp;minus;','&minus;',$alabel);
			
			$out[] = n.t.'<option value="'.htmlspecialchars($avalue).'"'.$sel.'>'.$alabel .'</option>';
		}
		
		return '<select'.( $id ? ' id="'.$id.'"' : '' ).' name="'.$name.'" class="list"'.
			($onchange == 1 ? ' onchange="submit(this.form);"' : $onchange).
			'>'.
			($blank_first ? n.t.'<option value=""'.($selected == false ? ' selected="selected"' : '').'></option>' : '').
			( $out ? join('', $out) : '').
			n.'</select>';
	}

// -----------------------------------------------------------------------------
	
	function selectInputOther($name, $array, $value="", $class='',$id='') {
		
		foreach ($array as $avalue => $alabel) {
		
			if (strtolower(trim($avalue)) == 'other') {
			
				return br.fInput("text",$name.'_other',$value,$class,'','','','',$id);
			}
		}
	}
	
// -----------------------------------------------------------------------------
// change: added $onchange param
// 		   added $col param
		   
	function treeSelectInput($select_name = '', $array = '', $value = '', $id = '', $truncate = 0, $col='',$onchange='')
	{
		if (!$array) return '';
		
		$out = array();
		$pos = is_array($value) ? array_flip($value) : array();
		
		$col = ($col) ? $col : 'name';
		
		foreach ($array as $i => $a)
		{
			$check = '';
			$sel   = '';
			$class = '';
			$position = 0;
			
			if (!$a or $a['parent'] == '')
			{
				continue;
			}

			extract($a);

			if (is_array($value) and in_array($$col,$value)) {
				
				$class = 'selected';
				$position = $pos[$$col];
				
			} elseif ($$col == $value) { 
			
				$sel = ' selected="selected"';
			}

			$sp = ($level > 2) ? str_repeat(sp.sp.sp,$level-2) : '';
			// $sp = ($level > 2) ? str_repeat('***',$level-2) : '';

			if (($truncate > 3) && (strlen(utf8_decode($title)) > $truncate)) {
				$htmltitle = ' title="'.htmlspecialchars($title).'"';
				$title = preg_replace('/^(.{0,'.($truncate - 3).'}).*$/su','$1',$title);
				$hellip = '&#8230;';
			} else {
				$htmltitle = $hellip = '';
			}
			
			$out[] = n.t.'<option id="opt-'.rand().'" class="'.$class.'" title="'.htmlspecialchars($title).'" data-level="'.$level.'" data-pos="'.$position.'" value="'.htmlspecialchars($$col).'"'.$htmltitle.$sel.'>'.$sp.htmlspecialchars($title).$hellip.$check.'</option>';
		}
		
		// if (!$option_1_value) $option_1_value[] = 'NONE';
		
		return n.'<select'.( $id ? ' id="'.$id.'" ' : '' ).' name="'.$select_name.'" onchange="'.$onchange.'" class="list">'.
			n.t.'<option title="" value="NONE"></option>'.
			( $out ? join('', $out) : '').
			n.'</select>';
	}

// -----------------------------------------------------------------------------

	function fInput($type, 		          // generic form input
					$name,
					$value,
					$class='',
					$title='',
					$onClick='',
					$size='',
					$tab='',
					$id='',
					$disabled = false)
	{
		$o  = '<input type="'.$type.'"';
		$o .= ' value="'.htmlspecialchars($value).'"';
		$o .= strlen($name)? ' name="'.$name.'"' : '';
		$o .= ($size)     ? ' size="'.$size.'"' : '';
		$o .= ($class)    ? ' class="'.$class.'"' : '';
		$o .= ($title)    ? ' title="'.$title.'"' : '';
		$o .= ($onClick)  ? ' onclick="'.$onClick.'"' : '';
		$o .= ($tab)      ? ' tabindex="'.$tab.'"' : '';
		$o .= ($id)       ? ' id="'.$id.'"' : '';
		$o .= ($disabled) ? ' disabled="disabled"' : '';
		$o .= " />";
		return $o;
	}

// -----------------------------------------------------------------------------
// deprecated in 4.2.0

	function cleanfInput($text)
	{
		trigger_error(gTxt('deprecated_function_with', array('{name}' => __FUNCTION__, '{with}' => 'escape_title')), E_USER_NOTICE);
		return escape_title($text);
	}

// -----------------------------------------------------------------------------

	function hInput($name,$value,$id='')		// hidden form input
	{
		return fInput('hidden',$name,$value,'','','','','',$id);
	}

// -----------------------------------------------------------------------------

	function sInput($step)				// hidden step input
	{
		return hInput('step',$step);
	}

// -----------------------------------------------------------------------------

	function eInput($event)				// hidden event input
	{
		return hInput('event',$event);
	}

// -----------------------------------------------------------------------------

	function checkbox($name, $value, $checked = '1', $tabindex = '', $id = '')
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="checkbox" />';

		return join('', $o);
	}

// -----------------------------------------------------------------------------

	function checkbox2($name, $value, $tabindex = '', $id = '')
	{
		$o[] = '<input type="checkbox" name="'.$name.'" value="1"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($value == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="checkbox" />';

		return join('', $o);
	}

// -----------------------------------------------------------------------------

	function radio($name, $value, $checked = '1', $id = '', $tabindex = '')
	{
		$o[] = '<input type="radio" name="'.$name.'" value="'.$value.'"';
		$o[] = ($id) ? ' id="'.$id.'"' : '';
		$o[] = ($checked == '1') ? ' checked="checked"' : '';
		$o[] = ($tabindex) ? ' tabindex="'.$tabindex.'"' : '';
		$o[] = ' class="radio" />';

		return join('', $o);
	}

// -----------------------------------------------------------------------------
// change: add name parameter

	function form($contents, $style = '', $onsubmit = '', $method = 'post', $class = '', $fragment = '', $name='')
	{
		return n.'<form method="'.$method.'" action="index.php'.($fragment ? '#'.$fragment.'"' : '"').
			($name  ? ' name="'.$name.'"' : '').
			($class ? ' class="'.$class.'"' : '').
			($style ? ' style="'.$style.'"' : '').
			($onsubmit ? ' onsubmit="return '.$onsubmit.'"' : '').
			'>'.$contents.'</form>'.n;
	}

// -----------------------------------------------------------------------------

	function fetch_editable($name,$event,$identifier,$id)
	{
		$q = fetch($name,'txp_'.$event,$identifier,$id);
		return htmlspecialchars($q);
	}

// -----------------------------------------------------------------------------
// change: - added of class parameter
// 		   - no style if class is given
//		   - added rows & cols parameters

	function text_area($name, $h, $w, $thing='', $id='', $class='', $rows=4, $cols=80)
	{
		$id    = ($id) ? ' id="'.$id.'"' : '';
		$style = (!$class) ? 'height:'.$h.'px;width:'.$w.'px;' : '';
		$rows  = ($rows) ? ' rows="'.$rows.'"' : '';
		$cols  = ($cols) ? ' cols="'.$cols.'"' : '';
		
		return '<textarea'.$id.' class="'.$class.'" name="'.$name.'"'.$rows.$cols.' style="'.$style.'">'.htmlspecialchars($thing).'</textarea>';
	}

// -----------------------------------------------------------------------------

	function type_select($options)
	{
		return '<select name="type">'.n.type_options($options).'</select>'.n;
	}

// -----------------------------------------------------------------------------

	function type_options($array)
	{
		foreach($array as $a=>$b) {
			$out[] = t.'<option value="'.$a.'">'.gTxt($b).'</option>'.n;
		}
		return join('',$out);
	}

// -----------------------------------------------------------------------------

	function radio_list($name, $values, $current_val='', $hilight_val='')
	{
		// $values is an array of value => label pairs
		foreach ($values as $k => $v)
		{
			$id = $name.'-'.$k;
			$out[] = n.t.'<li>'.radio($name, $k, ($current_val == $k) ? 1 : 0, $id).
				'<label for="'.$id.'">'.($hilight_val == $k ? strong($v) : $v).'</label></li>';
		}

		return '<ul class="plain-list">'.join('', $out).n.'</ul>';
	}

// -----------------------------------------------------------------------------
/*
	function tsi_old($name,$datevar,$time,$tab='')
	{
		$size = ($name=='year') ? 4 : 2;

		return '<input type="text" name="'.$name.'" value="'.
			date($datevar,$time+tz_offset())
		.'" size="'.$size.'" maxlength="'.$size.'" class="edit" tabindex="'.$tab.'" />'."\n";
	}
*/
// -----------------------------------------------------------------------------

	function tsi($name,$datevar,$time,$tab='')
	{
		$size = ($name=='year' or $name=='exp_year') ? 4 : 2;
		$s = ($time == 0)? '' : safe_strftime($datevar, $time);
		return n.'<input type="text" name="'.$name.'" value="'.
			$s
		.'" size="'.$size.'" maxlength="'.$size.'" class="edit"'.(empty($tab) ? '' : ' tabindex="'.$tab.'"').' title="'.gTxt('article_'.$name).'" />';
	}
?>

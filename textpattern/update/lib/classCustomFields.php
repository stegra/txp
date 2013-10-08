<?php

class CustomFields
{
	var $group = '';
	
// ---------------------------------------------------------------------------------
	function CustomFields($section='',$category1='',$category2='',$parent='',$status='',$title='')
	{
		$this->import(txpath.'/custom/custom_fields.xml');
		$this->setGroup($section,$category1,$category2,$parent,$status,$title);
	}

// ---------------------------------------------------------------------------------
	function import($xmlfile) 
	{ 
		$xmldoc = get_xml($xmlfile);
		$xsldoc = $this->custom_fields_xsl_doc;
		
		if ($xmldoc = xslt($xmlfile,$xsldoc,'customfields')) {
			
			// PHP 5
			
			if (class_exists('DomDocument')) {				
				
				$xmldom = new DomDocument('<?xml version="1.0" encoding="utf-8"?'.'><root/>');
				
				if (method_exists($xmldom, 'loadXML')) {
				
					$xmldom->loadXML($xmldoc);
				
					$items = $xmldom->getElementsByTagName("Item"); 
					
					foreach($items as $item) 
					{
						$array = array();
						
						foreach($item->childNodes as $node) 
						{
							$name = strtolower($node->nodeName);
							$text = $node->textContent;
							
							if ($name == 'type')  	$type = $text;
						 // if ($name == 'group')  	$text = $this->getGroupRegExp($text);
							if ($name == 'select')	$text = $this->getSelectArray($text);
							if ($name != '#text')   $array[$name] = $text;
						}
						
						$array['value'] = '';
						
						$_SESSION['custom_fields'][] = $array;
					}
				
					return;
				}
			}																
				
			// PHP 4.4 or NO DOM		
			
			$xmldoc = preg_replace('/>\s</','><',$xmldoc);
				
			preg_replace_callback("/(?<=\<Item\>)[\s\w\W]*?(?=\<\\/Item\>)/",array($this,'doCustomFields'),$xmldoc);
				
		}
	}

	// - - - - - - - - - - - - - - - - - - - - -
	
	function doCustomFields($matches) {
		
		$_SESSION['custom_fields'][] = ''; 
		
		preg_replace_callback("/<(\w+)>(.+?)?<\/\w+>/",array($this,'doCustomFieldItems'),$matches[0]);
		
		$last = count($_SESSION['custom_fields']) - 1;
		$_SESSION['custom_fields'][$last]['value'] = '';
	}

	// - - - - - - - - - - - - - - - - - - - - -
	
	function doCustomFieldItems($matches) {
		
		$last  = count($_SESSION['custom_fields']) - 1;
		$name  = strtolower($matches[1]);
		$value = (isset($matches[2])) ? $matches[2] : '';
		$value = ($name == 'group')   ? $this->getGroupRegExp($value) : $value;
		$value = ($name == 'select')  ? $this->getSelectArray($value) : $value;
		
		$_SESSION['custom_fields'][$last][$name] = $value;
	}

// ---------------------------------------------------------------------------------
	function getGroupRegExp($text) { 
		
		$text = preg_replace('/^\+\//', '([\w-]+)/',$text);		// 	+/
		$text = preg_replace('/\/\+/', 	'/([\w-]+)',$text);		//	/+
		
		$text = preg_replace('/^\*\//', '([\w-]+)?/',$text);	//	*/
		$text = preg_replace('/\/\*/', 	'/([\w-]+)?',$text);	//	/*
		
		$text = preg_replace('/^-\//',	'/',$text);				//	-/
		$text = preg_replace('/\/-\//',	'//',$text);			//	/-/
		$text = preg_replace('/\/-$/', 	'//',$text);			//	/-
		$text = preg_replace('/\/-$/', 	'//',$text);			//	/-
		$text = preg_replace('/\/-:/', 	'//:',$text);			//	/-
		$text = preg_replace('/\/-:/', 	'//:',$text);			//	/-
		
		$text = preg_replace('/:parent/', 	':0',$text);		
		$text = preg_replace('/:child/', 	':1',$text);
		$text = preg_replace('/:sticky/', 	':5',$text);
		
		// ending with parent title
		
		$text = preg_replace('/:\*:\*:\*:(.+)$/',	":[01]:[1-5]:[\w-]+:$1",$text);
		$text = preg_replace('/:(.+):\*:\*:(.+)$/',	":$1:[1-5]:[\w-]+:$2",$text);
		
		// ending with title
		
		$text = preg_replace('/:\*:(.+):(.+)$/',	":[01]:$1:$2",$text);
		$text = preg_replace('/:(.+):\*:(.+)$/',	":$1:[1-5]:$2",$text);
		$text = preg_replace('/:\*:\*:(.+)$/', 		":[01]:[1-5]:$1",$text);
		
		// ending with status
		
		$text = preg_replace('/:\*:(.+)$/',			":[01]:$1",$text);	
		$text = preg_replace('/:(.+):\*$/',			":$1:[1-5]",$text);
		$text = preg_replace('/:\*:\*$/',			":[01]:[1-5]",$text);
		
		// ending with level
		
		$text = preg_replace('/:\*$/',				":[01]",$text);		
		
		$text = preg_replace('/\//', 	'\/',$text);	// escape slash
		$text = '/^'.$text.'/';
	
		return $text;
	}
						
// ---------------------------------------------------------------------------------
	function setGroup($s='',$c1='',$c2='',$parent='',$status='',$title='') {
	
		global $event;
		
		$level = ($parent) ? 1 : 0;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($event == 'article') {
		
			if (!$s) $s = getDefaultSection();
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$parent_title = ($parent) ? ":".fetch("url_title","textpattern","ID",$parent) : '';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// if c1 or c2 is part of a unique group and s is not given then set 
		// s to the section name in that unique group.
			
		if ($event == 'list') {
		
			if ($s == 'all' && ($c1 == 'all' || $c1 == 'any') && safe_count("txp_category","name='$c2'")) {
				
				$groups = safe_rows("Section,Category1","textpattern","Category2 = '$c2' GROUP BY Section,Category1,Category2");
				
				if (count($groups) == 1) {
					$s  = $groups[0]['Section'];
					$c1 = $groups[0]['Category1'];
				}
			} elseif ($s == 'all' && safe_count("txp_category","name='$c1'")) {
				
				$groups = safe_rows("Section","textpattern","Category1 = '$c1' GROUP BY Section,Category1");
				
				if (count($groups) == 1) {
					$s = $groups[0]['Section'];
				}
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$this->group = "$s/$c1/$c2:$level:$status:$title".$parent_title;
	}

// ---------------------------------------------------------------------------------
	function getGroup() { 
	
		return $this->group;
	}
	
// ---------------------------------------------------------------------------------
	function addValues($values) {

		if (!isset($_SESSION['custom_fields'])) return;
		
		extract($values);
		
		$custom = $_SESSION['custom_fields'];
		
		foreach ($custom as $item => $array) {
			
			extract($array);
			
			$pattern = $group;
			
			if (preg_match($pattern,$this->group))
				$custom[$item]['value'] = ${$field};
		}
		
		$_SESSION['custom_fields'] = $custom;
	}

// ---------------------------------------------------------------------------------
	function clearValues() {

		if (!isset($_SESSION['custom_fields'])) return;
		
		foreach ($_SESSION['custom_fields'] as $item => $array) {
			
			$_SESSION['custom_fields'][$item]['value'] = '';
		}
	}

// ---------------------------------------------------------------------------------
	function setField($name,$key,$val) {
	
		if (!isset($_SESSION['custom_fields'])) return;
		
		foreach ($_SESSION['custom_fields'] as $item => $array) {
			
			extract($array);
			
			$pattern = preg_replace('/:\d/','',$group);
			
			if ((preg_match($pattern,$this->group) && $field == $name))
				$_SESSION['custom_fields'][$item][$key] = $val;
		}
	}

// ---------------------------------------------------------------------------------
	function getFieldType($name) {
	
		if (!isset($_SESSION['custom_fields'])) return;
		
		foreach ($_SESSION['custom_fields'] as $item => $array) {
			
			extract($array);
			
			$pattern = $group;
			
			if ((preg_match($pattern,$this->group) && $field == $name))
				return (isset($_SESSION['custom_fields'][$item]['type'])) ? $_SESSION['custom_fields'][$item]['type'] : '';
		}
	}

// ---------------------------------------------------------------------------------
	function getFields() {
	
		$out = array();
		
		if (!isset($_SESSION['custom_fields'])) return $out;
		
		foreach ($_SESSION['custom_fields'] as $item) {
			
			extract($item);
			
			$pattern = preg_replace('/:\d/','',$group);
			
			if (preg_match($pattern,$this->group))
				$out[$field] = $item;
		}
		
		return $out;
	}

// ---------------------------------------------------------------------------------
	function getColumns() {
	
		$out = array();
		
		if (!isset($_SESSION['custom_fields'])) return $out;
		
		foreach ($_SESSION['custom_fields'] as $item) {
			
			extract($item);
			
			$pattern = preg_replace('/:\d/','',$group);
			
			if (preg_match($pattern,$this->group) && $showcolumn)
				$out[$field] = $item;
		}
		
		return $out;
	}

// ---------------------------------------------------------------------------------
	function printFields() {
	
		if (!isset($_SESSION['custom_fields'])) return;
		
		foreach ($_SESSION['custom_fields'] as $item) {
			
			extract($item);
			
			$pattern = $group; 
			
			if (preg_match($pattern,$this->group))
				echo $this->printField($title, $field, $value, $item);
		}
	}
	
// ---------------------------------------------------------------------------------
	function printField($title, $name, $content, $properties) 
	{	
		$help = null;
		$type = null;
		
		if ($properties) extract($properties);
		
		$title = $title . popModHelp('custom/'.$help);
		
		if ($type == 'textarea') {
			
			return graf($title . br . text_area($name, 80, 100, $content, '', 'edit custom')) . n;
		
		} elseif ($type == 'date') {
			
			return graf($title . br . date_selector($name,$content)) . n;
		
		} elseif ($type == 'time') {
			
			return graf($title . br . time_selector($name,$content)) . n;
		
		} elseif ($type == 'checkbox') {
									
			return graf($title . sp . checkbox2($name,$content)) . n;
		
		} elseif (preg_match('/^select/',$type) and isset($select)) {
			
			$title = (isset($hidetitle) && $hidetitle) ? '' : $title.br;
			
			return graf($title . selectInput($name,$select,$content)) . n;
		
		} elseif ($type == 'radio') {
			
			$title = (isset($hidetitle) && $hidetitle) ? '' : $title.br;
			
			return graf($title . radioSelectInput($name,$select,$content)) . n;
			
		} else {
		
			if ($type == 'number')
				$content = preg_replace('/^(\d{10}(\.\d+)? )/','',$content);
			
			return graf($title . br . fInput('text', $name, $content,'edit')) . n;
		}
	}

// ---------------------------------------------------------------------------------
	function getSelectArray($list) 
	{
		$out = array();
		
		if (!strlen($list)) return;
		
		foreach (explode(',',$list) as $key => $value) {
			
			list($value,$title) = explode(':',$value);
			$out[$value] = ($title) ? $title : $value;
		}
		
		return $out;
	}

// =================================================================================

	var $custom_fields_xsl_doc = <<<XSLDOC
<?xml version="1.0" encoding="iso-8859-1" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
				xmlns:txp="http://www.textpattern.com/">   

<xsl:output method="xml" encoding="iso-8859-1"/>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template match="/">

	<CustomFields>
	
		<xsl:apply-templates select="//Item"/>
	
	</CustomFields>
	
</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template match="Item">

	<Item>
		
		<xsl:if test="@type">
			<Type><xsl:value-of select="@type"/></Type>
		</xsl:if>
		
		<xsl:apply-templates/>
		
		<xsl:if test="not(Column)">
			<xsl:call-template name="Column"/>
		</xsl:if>
		
	</Item>
	
</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template match="Title">

	<xsl:call-template name="Column"/>

</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template match="Column">

	<xsl:call-template name="Column"/>

</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template match="Select">
	
	<Select>
		<xsl:for-each select="Option">
			<xsl:choose>
		    	<xsl:when test="@value"><xsl:value-of select="@value"/></xsl:when>
		    	<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>
		    </xsl:choose>
		    <xsl:text>:</xsl:text>
			<xsl:value-of select="."/>
			<xsl:if test="position() != last()">,</xsl:if>
		</xsl:for-each>
	</Select>

</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template match="*">

	<xsl:element name="{name()}">
		<xsl:value-of select="."/>
	</xsl:element>

</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template match="Title">

	<Title><xsl:value-of select="."/></Title>
	
	<HideTitle>
		<xsl:choose>
			<xsl:when test="@hide = 'yes'">1</xsl:when>
			<xsl:when test="@hide = '1'">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</HideTitle>
		
</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

<xsl:template name="Column">

	<Column>
		<xsl:choose>
			<xsl:when test="parent::Item and text()"><xsl:value-of select="."/></xsl:when>
			<xsl:when test="parent::Item"><xsl:value-of select="../Title"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="Title"/></xsl:otherwise>
		</xsl:choose>
	</Column>
	
	<ShowColumn>
		<xsl:choose>
			<xsl:when test="@show = 'yes'">1</xsl:when>
			<xsl:when test="@show = '1'">1</xsl:when>
			<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
	</ShowColumn>
		
</xsl:template>

<!-- + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + + -->

</xsl:stylesheet>
XSLDOC;

}

?>
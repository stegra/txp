<?php

	if (isset($txpcfg['xslpath'])) 
		define("xslpath",$txpcfg['xslpath']);
	else
		define("xslpath",txpath.'/xsl');
		
	$xsl_call_tags = array();

// -------------------------------------------------------------------------------------
	function xslt($xml,$xsl,$source='page',$xslfile='') 
	{
		if (exists('XSLTProcessor'))
			$use = 'LocalXSLTProcessor';
		else
			$use = 'RemoteXSLTProcessor';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($source == 'customfields')
			$output = 'xml';
		else
			$output = 'html';
		
		// - - - - - - - - - - - - - - - - - - - - - - - - -
		
		return xslt_processor($xml,$xsl,$xslfile,$use,$output);
	}
	
// -------------------------------------------------------------------------------------
	function xslt_processor($xml,$xsl,$xslfile,$use,$output)
	{
		global $siteurl;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($use == 'LocalXSLTProcessor') { 					
			
			if (!$xml) $xml = "<xml></xml>";
			
			$xmldoc = new DOMDocument;
			if (preg_match('/\.xml$/',$xml) and is_file($xml))
				$xmldoc->load($xml);
			else
				$xmldoc->loadXML($xml);
			
			$xsldoc = new DOMDocument;
			
			if (preg_match('/\.xsl$/',$xsl)) {
				
				if (!is_file($xsl)) return '';
				
				$xsldoc->load($xsl);
					
			} else {
				
				$xsldoc->loadXML($xsl);
			}
			
			$proc = new XSLTProcessor;
			$proc->importStyleSheet($xsldoc);
			
			$result = $proc->transformToDoc($xmldoc);
			$result = $result->saveHTML();
			
			if ($output == 'html') {
				// new lines
				$result = preg_replace("/\n/","\r",$result);
			}
			
			$result = fix_tags($result);
			
			return $result;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		if ($use == 'RemoteXSLTProcessor') {
			
			$search = preg_replace('/\//','\/',txpath); 
			
			if (preg_match('/\.xml$/',$xml) and is_file($xml)) {
				$xml = preg_replace('/'.preg_quote(txpath,'/').'\//','',$xml);
				$xml = "http://$siteurl/textpattern/$xml";
			} else {
				$xml = urlencode($xml);
			}
			
			$xslfile = preg_replace('/'.preg_quote(txpath,'/').'\//','',$xslfile);
			$xslfile = "http://$siteurl/textpattern/$xslfile";
			
			$result = curl_get_file_contents("http://www.steffigra.com/service/services/xslt2.php?xml=$xml&xsl=$xslfile"); 

			if ($output == 'html') {
				// new lines
				$result = preg_replace("/\n/","\r",$result);
			}
			
			$result = fix_tags($result);
			
			return $result;
		}
	}
	
// -------------------------------------------------------------------------------------
	function get_xml($xml) 
	{
		if (isfile($xml)) return read_file($xml);
		
		return '';
	}

// -------------------------------------------------------------------------------------
	function make_xml($xml) 
	{
		$xml = preg_replace('/&#38;/','<amp/>',$xml);
		$xml = preg_replace('/&#38;/','<amp/>',$xml);
		$xml = preg_replace('/&([^#\d])/','<amp/>$1',$xml);
		
		// add document element tag with txp namespace declaration 
		$xml = '<Article xmlns:txp="http://www.textpattern.com/">'.n.trim($xml).n.'</Article>';
		// add xml declaration 
		$xml = '<?xml version="1.0"?'.'>'.n.$xml;
		
		return $xml;
	}

// -------------------------------------------------------------------------------------
	function get_xsl($name='') 
	{
		// $type  = gps('type','page','page,form');
		$type  = gps('type','page');
		$name = ($name) ? $name : gps('name');
		$name = preg_replace('/DEFAULT/','*',$name);
		
		if ($type == 'page') {
			
			$pages = safe_column('name','txp_page','user_xsl IS NOT NULL');
			
			if (in_array($name,$pages)) {
				return doStrip(fetch('user_xsl','txp_page','name',$name));
			}
 		}
 		
		if ($type == 'form') {
		
			if ($row = safe_row('Form_xsl','txp_form',"name = '$name' and type = 'xsl'")) {
				
				return doStrip($row['Form_xsl']);
			}
		}
		
		if ($type == 'file') { 
			
			return valid(make_xsl(xslpath.'/'.$name,'file',$name,1),$name);
		}

		// return make_xsl($name,'file',1); */
		
		return '';
	}

// -------------------------------------------------------------------------------------
	function make_xsl($xsl,$type='page',$name,$replace_import=false) 
	{
		global $siteurl,$xslpath,$path_to_site,$xsl_call_tags;
		
		$xslpath = (is_dir($path_to_site.DS.'xsl'))
			? $path_to_site.DS.'xsl'.DS
			: $path_to_site.DS.'textpattern'.DS.'xsl'.DS;
		
		if ($type == 'file') { 
			$xsl = (isfile($xsl)) ? read_file($xsl) : '';
		}
		
		$xml_declaration = '<?xml version="1.0" encoding="utf-8" ?'.'>';
		$output_tag      = '<xsl:output method="html" encoding="utf-8"/>';
		
		$stylesheet_tag_open  = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"'.n.
								'                xmlns:txp="http://www.textpattern.com/">';
		$stylesheet_tag_close = '</xsl:stylesheet>';	
		
		$template_match_tag  = '<xsl:template match="/">'.n.t;
		$template_match_tag .= '<xsl:call-template name="html"/>'.n; 
		$template_match_tag .= '</xsl:template>';
		
		if ($xsl) {
			
			// convert xslt tags with 'txp' namespace to 'xsl' namespace
			$xsl_tags = implode('|',array(
				'import','template','call\-template','with\-param','param','element','attribute'));
			$xsl = preg_replace('/<(\/?)txp:('.$xsl_tags.')(\s|>)/',"<$1xsl:$2$3",$xsl);
			
			if ($name == 'global') {
				
				// xsl:template match tag
				
				$xsl = (!preg_match('/<xsl:import/',$xsl))
					? $template_match_tag.n.n.$xsl
					: preg_replace('/(<xsl:import.+\/>\r?\n\r?\n)/',"$1$template_match_tag".n.n,$xsl);
			}
			
			// xsl:stylesheet tag
			$xsl = $stylesheet_tag_open.n.n.$xsl.n.$stylesheet_tag_close;
			
			// xml decleration 
			$xsl = $xml_declaration.n.$xsl;
			
			// xsl:output
			$xsl = (preg_match('/<xsl:import/',$xsl))
				? preg_replace('/(<xsl:import.+\/>\r?\n\r?\n)/',"$1$output_tag".n.n,$xsl)
				: preg_replace('/(<xsl:stylesheet[\w\W\s]+?\>\r?\n\r?\n)/',"$1$output_tag".n.n,$xsl);
	
			$pattern = '/(<xsl:import\s+)href\=\"([\w\/\-_\*\.\:]+?)(\.xsl)?\"[\s+]?\/>/';
			
			$xsl = ($replace_import) ? preg_replace_callback($pattern,'replace_import',$xsl) : $xsl;
			
			// $xsl = preg_replace_callback('/((&nbsp;)+)/','fix_nbsp_entity',$xsl);	
			
			$xsl = preg_replace('/&nbsp;/','&#160;',$xsl);
			$xsl = preg_replace("/&amp;/","&#38;",$xsl);
			$xsl = preg_replace("/ & /"," &#38; ",$xsl);
			
			// xsl:attribute tags
			
			if (preg_match_all('/<xsl:attribute/',$xsl,$matches)) {
				
				foreach($matches[0] as $match) {
					
					// put tag on one line
					
					$pattern = '/(<xsl:attribute.+)\r\n/';
					
					$xsl = preg_replace($pattern,"$1",$xsl);
					$xsl = preg_replace($pattern,"$1",$xsl);
					$xsl = preg_replace($pattern,"$1",$xsl);
				}
				
				$tag_open    = '<xsl:attribute.+?\>';
				$tag_close   = '<\/xsl:attribute>';
				$tag_content = '.+?';
				
				$pattern = "/($tag_open)($tag_content)($tag_close)/";
				
				$xsl = preg_replace_callback($pattern,'fix_attribute_contents',$xsl);
			}
			
			// xsl:template tags alled from xsl:attribute tag
			
			if ($xsl_call_tags) {
			
				// put tag on one line
				
				$pattern = '/(<xsl:template name=")('.implode('|',$xsl_call_tags).')(.+)\r\n?/';
				
				for ($i=1;$i<10;$i++) 
					$xsl = preg_replace($pattern,"$1$2$3",$xsl);
				
				foreach($xsl_call_tags as $name) {
					
					$tag_open    = '<xsl:template name="'.$name.'">';
					$tag_close   = '<\/xsl:template>';
					$tag_content = '.+?';
				
					$pattern = "/($tag_open)($tag_content)($tag_close)/";
					
					$xsl = preg_replace_callback($pattern,'fix_template_contents',$xsl);
				}
				
				// TODO: find template tags in other pages
			}
			
			// pre(doSpecial($xsl));
		}
		
		return $xsl;
	}

// -------------------------------------------------------------------------------------
// returns doc if doc is valid
// returns error xsl doc if doc is invalid

	function valid($doc,$name='') 
	{
		include_once txpath.'/lib/classXMLCheck.php';
		
		$check = new XML_check();

		if($check->check_string($doc)) 
			return $doc;
		
		$error = $check->get_full_error();
		
		$ext   = (preg_match('/<xsl:/',$doc)) ? 'xsl' : 'xml';
		$error = strtoupper("$name.$ext ").$error;
		
		if ($ext == 'xsl' and is_file($doc = xslpath.'/error.xsl')) {
			$doc = read_file($doc);
			$doc = preg_replace('/ERROR/u',$error,$doc);
		} else {
			$doc = '';
		}
		
		return $doc;
	}

// -------------------------------------------------------------------------------------
// returns false if doc is valid
// returns error if doc is invalid

	function invalid($doc1,$name1='',$doc2='',$name2='') 
	{
		include_once txpath.'/lib/classXMLCheck.php';
		
		$check = new XML_check();
		
		if(!$check->check_string($doc1)) 
			return strtoupper($name1).' '.$check->get_full_error();
		
		if($doc2 and !$check->check_string($doc2)) 
			return strtoupper($name2).' '.$check->get_full_error();
		
		return false;
	}

// -------------------------------------------------------------------------------------
	function exists($name) 
	{
		if ($name == 'xsltlib')       return function_exists('domxml_xslt_stylesheet_file');
		if ($name == 'sablotron')     return function_exists('xslt_create');
		if ($name == 'XSLTProcessor') return class_exists('XSLTProcessor');
		
		return false;
	}
	
// -------------------------------------------------------------------------------------
// fix tags in html that is returned from the xslt processor

	function fix_tags($doc) 
	{	
		// txp tags: close tags that are supposed to be closed
		$doc = preg_replace('/<txp:([^>]+)><\/txp:[^>]+>/','<txp:\1/>',$doc);
		
		// txp tags: replace bracket character code with char
		$doc = preg_replace('/\%5B/','[',$doc);
		$doc = preg_replace('/\%5D/',']',$doc);
		
		// txp tags within html tags: brackets and spaces for closed tags
		$doc = preg_replace_callback('/\[txp:[^\]]+?\/\]/','fix_txp_tags',$doc);
		
		// txp tags within html tags: brackets and spaces for open tags
		$doc = preg_replace_callback('/\[\/?txp:[^\]]+?\]/','fix_txp_tags',$doc);
		
		// misc single tags: close with end slash
		$doc = preg_replace('/<(input|meta|link|img|br|hr)([^>]+[^\/])?\>/','<\1\2/>',$doc);
		
		// script tags: add line break
		$doc = preg_replace('/<\/script>/','</script>'.n,$doc);
		
		$doc = preg_replace('/ xmlns:txp="http:\/\/www.textpattern.com\/"/','',$doc);
		
		// comment tags: line breaks
		$doc = preg_replace('/(<!--.+?-->)/',n.n.'\1'.n.n,$doc);
		
		return $doc;
	}

// -------------------------------------------------------------------------------------
	function fix_txp_tags($matches) 
	{	
		$tag = $matches[0];
		
		$tag = preg_replace('/^\</','{',$tag);	// replace '<' with '{'
		$tag = preg_replace('/\>$/','}',$tag);	// replace '>' with '}'
		
		$tag = preg_replace('/^\[/','<',$tag);	// replace '[' with '<'
		$tag = preg_replace('/\]$/','>',$tag);	// replace ']' with '>'
		$tag = preg_replace('/\%20/',' ',$tag); // space
		
		return $tag;
	}

// -------------------------------------------------------------------------------------
	function fix_attribute_contents($matches) 
	{	
		global $xsl_call_tags;
		
		$open    = $matches[1];
		$content = $matches[2];
		$close   = $matches[3];
		
		// fix self closing txp tags
		
		$txp_tag_pattern = '/(<\/?txp:\w+(?:\s+[\w\.]+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"\/>]+))*\s*\/>)/';
		$content = preg_replace_callback($txp_tag_pattern,'fix_txp_tags',trim($content));
		
		preg_match_all('/<xsl:call\-template name="(.+?)"\/?\>/',$content,$matches);
		
		if (isset($matches[1])) {
			foreach($matches[1] as $match) {
				if (!in_array($match,$xsl_call_tags))
					$xsl_call_tags[] = $match;
			}
		}
		
		return $open.$content.$close.n;
	}

// -------------------------------------------------------------------------------------
	function fix_template_contents($matches) 
	{	
		$open    = $matches[1];
		$content = $matches[2];
		$close   = $matches[3];
		
		// fix self closing txp tags
		
		$txp_tag_pattern = '/(<\/?txp:\w+(?:\s+[\w\.]+\s*=\s*(?:"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'|[^\s\'"\/>]+))*\s*\/>)/';
		$content = preg_replace_callback($txp_tag_pattern,'fix_txp_tags',trim($content));
		
		return $open.$content.$close.n;
	}

// -------------------------------------------------------------------------------------
	function replace_import($matches) 
	{	
		global $xslpath, $event;
		
		$href  = $matches[2];
		$href  = preg_replace('/\*/','DEFAULT',$href);
		$href .= (!isset($matches[3])) ? '.xsl' : $matches[3];
		
		if (strpos($href,'http:') === false) {
			
			if (preg_match('/^\/?(form|page)\//',$href)) {
				$href = $xslpath.ltrim($href,'/');
			} else {
				$href = $xslpath.'page/'.str_replace('/','_',$href); 
			}
		}
		
		return $matches[1].'href="'.$href.'"/>';
	}

// -------------------------------------------------------------------------------------
	function isfile($string) 
	{	
		if (preg_match('/(\.xml|\.xsl)$/',$string)) return is_file($string);
	}
?>

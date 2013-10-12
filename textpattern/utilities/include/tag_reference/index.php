<?php

// -----------------------------------------------------------------
// update all tags if changes occured

update_all_tags();

// -----------------------------------------------------------------
// get a list of all tags

$groups = safe_column('`group`',"txp_tag","1=1");

$columns = array(
	"tag AS tagname",
	"CHAR_LENGTH(TRIM(code)) AS code",
	"description"
);

foreach ($groups as $group) {

	$groups[$group] = safe_rows(
		impl($columns),"txp_tag","`group` = '$group' ORDER BY pos ASC"
	);
}

// Miscellaneous tags

$misc = $groups['Miscellaneous'];
unset($groups['Miscellaneous']);

// If tags

$groups['If Tags'] = safe_rows(
	impl($columns),"txp_tag","tag LIKE 'if_%' ORDER BY tag ASC"
);

// -----------------------------------------------------------------
// incoming tag name if any

if ($tag = gps('tag')) {
	
	$tag = safe_row("`group`,tag AS tagname,code,description","txp_tag","tag = '$tag'");
	
	$description = explode(n,$tag['description']);
	$examples = array();
	
	foreach ($description as $key => $line) {
	
		if (trim(substr(strtolower($line),0,7) == 'example')) {
		
			$examples[] = array();
			unset($description[$key]);
		
		} elseif ($count = count($examples)) {
			
			$examples[$count-1][] = preg_replace('/\t/','    ',$line);
			unset($description[$key]);
		}
	}
	
	foreach ($examples as $key => $lines) {
		
		$examples[$key] = 'Example:'.n.n.tag(implode(n,$lines),'code');
	}
	
	// create links to other tag pages 
	$description = preg_replace(
		'/(&lt;txp:([a-z0-9_]+)&gt;)/',
		"<a class=\"code\" href=\"index.php?go=tag_reference&tag=$2\">$1</a>",
		implode(br.n,$description));
	
	$tag['description'] = $description;
	$tag['examples']    = implode(n,$examples);
	$tag['code']    	= htmlentities(preg_replace('/\t/','    ',$tag['code']));
	
	$tagname = $tag['tagname'];
	
	$tag['atts'] = safe_rows("attribute,`default`,comment","txp_tag_attr","tag = '$tagname' ORDER BY pos ASC");
	
	$longest = 0;
	
	foreach ($tag['atts'] as $att) {
		$length = strlen($att['attribute']);
		if ($length > $longest) $longest = $length;
	}
	
	foreach ($tag['atts'] as $key => $att) {
		$tag['atts'][$key]['attribute'] = str_replace(' ','&#160;',str_pad($att['attribute'],$longest,' '));
	}
}

?>
<html>
<head>
	<title>Tag Reference</title>
	<link rel="stylesheet" href="/textpattern/utilities/include/tag_reference/css/style.css" type="text/css">
	<script src="/textpattern/js/lib/jquery-1.7.1.min.js" language="javascript"></script>
	<script src="/textpattern/utilities/include/tag_reference/js/script.js" language="javascript"></script>
</head>
<body>

	<h1>Tag Reference</h1>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<ul class="groups">
	
	<?php foreach($groups as $group => $tags) { 
		
		$status = ($tag and $tag['group'] == $group) ? 'open' : 'closed';
		
		?>
		
		<li class="group <?php echo $status; ?> <?php echo $group; ?>">
			
			<a class="group" href="#<?php echo $group; ?>"><span>+</span> <?php echo $group; ?></a>
			
			<ul>
			
			<?php foreach($tags as $item) { extract($item); ?>
			
				<li class="<?php echo ($tagname == $tag['tagname']) ? 'selected' : ''; ?>">
					<a href="index.php?go=tag_reference&tag=<?php echo $tagname; ?>"><?php echo str_replace('_',' ',$tagname); ?></a>
					<?php if (!strlen($code)) echo ' (NO CODE)'; ?>
				</li>
			
			<?php } ?>
			
			</ul>
			
		</li>
	
	<?php } ?>
	
	<?php foreach($misc as $item) { extract($item); ?>
			
		<li class="misc <?php echo ($tagname == $tag['tagname']) ? 'selected' : ''; ?>">
			<a href="index.php?go=tag_reference&tag=<?php echo $tagname; ?>"><?php echo str_replace('_',' ',$tagname); ?></a>
			<?php if (!strlen($code)) echo ' (NO CODE)'; ?>
		</li>
	
	<?php } ?>
	
	</ul>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<div id="content">
	
	<?php if ($tag) { extract($tag); ?>
		
		<div class="tag tag-1">
			
			<h2><code>&lt;txp:<?php echo $tagname; ?>&gt;</code></h2>
			
			<?php if (strlen($description)) { ?>
				<p class="description"><?php echo $description; ?></p>
			<?php } ?>
			
			<?php if (strlen($examples)) { ?>
				<p class="examples"><?php echo $examples; ?></p>
			<?php } ?>
			
			<?php if (count($atts)) { ?>
				
				<table>
				<tr>
					<th class="name">Attribute</th>
					<th class="default">Default</th>
					<th></th>
				</tr>
				<tr><td class="line" colspan="3"></td></tr>
					
				<?php foreach($atts as $item) { extract($item); ?>
					<tr>
						<td valign="top" class="name"><b><?php echo $attribute; ?></b></td>
						<td valign="top" class="default"><?php echo $default; ?></td>
						<td valign="top" class="info"><?php echo $comment; ?></td>
					</tr>
				<?php } ?>
				
				</table>
				
			<?php } ?>
			
			<?php if (strlen($code)) { ?>
				
				<pre class="code"><?php echo $code; ?></pre>
			
			<?php } else { ?>
				
				<p class="error">NO PHP CODE</p>
			
			<?php } ?>
		
		</div>
		
	<?php } ?>
	
	</div>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
</body>
</html>


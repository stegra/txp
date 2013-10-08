<form action="index.php" data-mode="{$mode}" data-sort="{$sortby}" data-sortdir="{$sortdir}" action="" method="post" id="longform" name="longform" onsubmit="return verify('{$trash_cnt}')">

	<table width="100%" cellpadding="2" cellspacing="0" border="1" id="list" align="center">
	
	<!-- path - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<tr class="path">
		<td colspan="20">
			{section name=i loop=$path}
				<a href="?event=list&id={$path[i].ID}">{$path[i].Title}</a> &#8250; 
			{/section} &nbsp;
		</td>
	</tr>

	<!-- column titles - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	{if $col_headers}
	
	<tr class="sort-{$sortby|lower}">
		<th class="title"><a title="{$linkdir_title}" href="index.php?sort=Title&dir={$linkdir}&event=list">Title</a></th>
		
		{if $col_image}
		<th class="thumb">
			{if $thumb eq 'z'}<a class="bigger" title="Bigger thumbnails" href="index.php?event=list&thumb=y">Image</a>{/if}
			{if $thumb eq 'y'}<a class="smaller" title="Smaller thumbnails" href="index.php?event=list&thumb=z">Image</a>{/if}
		</th>
		{/if}
		
		{if $col_posted}<th class="posted"><a title="{$linkdir_title}" href="index.php?sort=Posted&dir={$linkdir}&event=list">Posted</a></th>{/if}
		{if $col_modified}<th class="posted"><a title="{$linkdir_title}" href="index.php?sort=LastMod&dir={$linkdir}&event=list">Modified</a></th>{/if}
		{if $col_class}<th class="class"><a title="{$linkdir_title}" href="index.php?sort=Class&dir={$linkdir}&event=list">Class</a></th>{/if}
		{if $col_categories}<th class="category1"><a title="{$linkdir_title}" href="index.php?sort=Category1&dir={$linkdir}&event=list">Categories</a></th>{/if}
		{section name=i loop=$custom_headers}<th>{$custom_headers[i]}</th>{/section}
		{if $col_file}<th>File</th>{/if}
		{if $col_play}<th>Play</th>{/if}
		{if $col_author}<th>Author</th>{/if}
		{if $col_status}<th class="status"><a title="{$linkdir_title}" href="index.php?sort=Status&dir={$linkdir}&event=list">Status</a></th>{/if}
		{if $col_position}<th class="position"><a title="{$linkdir_title}" href="index.php?sort=Position&dir={$linkdir}&event=list">Pos.</a></th>{/if}
		<th class="chbox"><input type="checkbox" id="select-all" title="Select All" class="article"/></th>
	</tr>
	
	{/if}
	
	<!-- main article - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

	{strip}
	<tr class="main-article">

		<td class="title"{if $col_image and !$image_name} colspan="2"{/if}>
			<span class="arrow"></span>
			<h4><a title="Edit {$title}" href="index.php?event=article&step=edit&ID={$id}">{$title}</a>
			{if $fileext}<span class="file">{$fileext}</span>{/if}</h4>
		</td>
		
		{if $col_image and $image_name}
			<td class="thumb">
				{if $trash}
					<div style="background-image:url('/{$img_dir}/{$image_id}/{$image_name}{if $trash_cnt}_filled{/if}_y.{$image_ext}')"><a title="Edit Image" data-id="{$id}" href="index.php?event=image&step=image_edit&id={$image_id}"><img src="/{$img_dir}/{$image_id}/{$image_name}{if $trash_cnt}_filled{/if}_y.{$image_ext}" class="mini trash" width="50" height="50" border="0"/></a></div>
				{else}
					<div style="background-image:url('/{$img_dir}/{$image_id}/{$image_name}_y.{$image_ext}')"><a title="Edit Image" data-id="{$id}" href="index.php?event=image&step=image_edit&id={$image_id}"><img src="/{$img_dir}/{$image_id}/{$image_name}_y.{$image_ext}" class="mini" width="50" height="50" border="0"/></a></div>				
				{/if}
			</td>
		{/if}
		
		{if $col_posted}<td class="posted">{$posted}</td>{/if}
		{if $col_modified}<td class="posted">{$modified}</td>{/if}
		
		{if $col_class}
			{if $class}
				<td class="type">{$class}</td>
			{else}
				<td class="blank">&mdash;</td>
			{/if}
		{/if}
		
		{if $col_categories}
			{if $categories}
				<td class="category">{$categories}</td> 
				<!-- <td class="category">{$category1}{if $category2}, {$category2}{/if}</td> -->
			{else}
				<td class="blank">&mdash;</td>
			{/if}
		{/if}
		
		{$custom_field_values}
		
		{if $col_file}<td>{$filename}</td>{/if}
		
		{if $col_play}
			<td>
			{if $fileext eq 'mp3'}
				{include file="play2.tpl"}
			{/if}
			</td>
		{/if}
		
		{if $col_author}<td>{$author}</td>{/if}
		
		{if $col_status}<td>{$status}</td>{/if}
		
		{if $col_position}
		<td>
			{if $position}
				{$position}
			{else}
				<span class="blank">&mdash;</span>
			{/if}
		</td>
		{/if}
		
		<td class="edit">
			<span class="edit"><a title="Edit {$title}" href="index.php?event=article&step=edit&ID={$id}">Edit</a></span>
		</td>
	</tr>
	{/strip}

	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	{$list_items}

	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

	{if $list_items_count lt 11}
		{section name=empty start=$list_items_count+1 loop=12}
			<tr class="even empty-{$smarty.section.empty.index}"><td colspan="20">&nbsp;</td></tr>
		{/section}
	{/if}
			
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<tr class="footer">
		
		<td colspan="20" align="right" valign="top" class="select">
			
			<div class="alt-select">
			
				<select id="column" name="column" class="list">
					
					<option class="label" value="none" data-key="">Columns:</option>
					<option class="line" value="">-------------</option>
					<option value="image">Image {if $col_image}&#10003;{/if}</option>
					<option value="posted">Posted {if $col_posted}&#10003;{/if}</option>
					<option value="modified">Modified {if $col_modified}&#10003;{/if}</option>
					<option value="class">Class {if $col_class}&#10003;{/if}</option>
					<option value="categories">Categories {if $col_categories}&#10003;{/if}</option>
					<option value="file">File {if $col_file}&#10003;{/if}</option>
					<option value="play">Play {if $col_file}&#10003;{/if}</option>
					<option value="author">Author {if $col_author}&#10003;{/if}</option>
					<option value="status">Status {if $col_status}&#10003;{/if}</option>
					<option value="position">Position {if $col_position}&#10003;{/if}</option>
					{if $custom_select}
						<option class="line" value="">-------------</option>
						{$custom_select}
					{/if}
					<option class="line" value="">-------------</option>
					<option value="headers">Headers {if $col_headers}&#10003;{/if}</option>
				
				</select>	
			
			</div>
			
			<div class="alt-select">
			
				<select id="action" name="edit_method" class="list" onchange="poweredit(this); return false;">
					
					<option class="label" value="none" data-key="">With selected:</option>
					<option class="line" value="">---------------------</option>
					
					<option value="edit" data-key="&#8984;E">Edit</option>
					{if $edit}<option value="save" data-key="&#8984;S" selected="yes">Save</option>{/if}
					{if $edit}<option value="edit_cancel" data-key="ESC">Cancel Edit</option>{/if}
					
					{if !$copy}
						<option value="cut" data-key="&#8984;X">Cut</option>
						{if $cut}<option value="cut_cancel" data-key="ESC">Cancel Cut</option>{/if}
						{if $cut}<option value="paste" data-key="&#8984;V">Paste</option>{/if}
					{/if}
					
					{if !$cut}
						<option value="copy" data-key="&#8984;C">Copy</option>
						{if $copy}<option value="copy_cancel" data-key="ESC">Cancel Copy</option>{/if}
						{if $copy}<option value="paste" selected="yes" data-key="&#8984;V">Paste</option>{/if}
					{/if}
					
					<option value="duplicate" data-key="&#8984;D">Duplicate</option>
					<option value="new" data-key="&#8984;&#8629;">New</option>
					<option value="alias" data-key="&#8984;L">Alias</option>
					<option value="group" data-key="&#8984;G">Group</option>
					
					{if $sortby eq 'Position'}
						<option value="move_up" data-key="&#8984;&#8593;">Move Up</option>
						<option value="move_down" data-key="&#8984;&#8595;">Move Down</option>
					{/if}
					
					<option value="open" data-key="&#8984;+">Open</option>
					{if $close}<option value="close" data-key="&#8984;&#8722;">Close</option>{/if}
					
					<!-- <option value="window">Window</option> -->
					<option value="trash" data-key="&#8984;DEL">Move to Trash</option>
					{if $trash_cnt}<option class="show" value="empty_trash" data-key="">Empty Trash</option>{/if}
					<option class="line" value="">---------------------</option>
					<option class="show" value="changestatus" data-key="">Change status...</option>
					<option class="show" value="changecomments" data-key="">Change comments...</option>
					<option class="show" value="changeauthor" data-key="">Change author...</option>
				</select>
			
			</div>
			
			<input type="hidden" name="win" value="{$window}"/>
			<input type="hidden" name="main_id" value="{$id}"/>
			<input type="hidden" name="checked" value="{$checked}"/>
			<input type="hidden" name="event" value="list" />
			<input type="hidden" name="step" value="list_multi_edit" />
			<input type="submit" name="" value="Go" class="smallerbox"/>
			
		</td>
		
	</tr>
	</table>

</form>

<ul id="context-menu">
	<li><a id="cut" href="#">Cut <span>&#8984;X</span></a></li>
	{if $cut}<li><a id="cut-paste" href="#"><b>Paste</b> <span>&#8984;V</span></a></li>{/if}
	{if $cut}<li><a id="cut-cancel">Cancel Cut <span>ESC</span></a></li>{/if}
	<li><a id="copy" href="#">Copy <span>&#8984;C</span></a></li>
	{if $copy}<li><a id="copy-paste"><b>Paste</b> <span>&#8984;V</span></a></li>{/if}
	{if $copy}<li><a id="copy-cancel">Cancel Copy <span>ESC</span></a></li>{/if}
	<li><a id="duplicate" href="#">Duplicate <span>&#8984;D</span></a></li>
	<li><a id="edit" href="#">Edit <span>&#8984;E</span></a></li>
	<li><a id="new" href="#">New <span>&#8984;&#8629;</span></a></li>
	<li><a id="group" href="#">Group <span>&#8984;G</span></a></li>
	<li><a id="alias" href="#">Make Alias <span>&#8984;L</span></a></li>
	<li><a id="window" href="#">Open in New Window</a></li>
	<li><a id="trash" href="#">Move to Trash <span>&#8984;DEL</span></a></li>
</ul>

<div class="test"></div>
<div class="checked"></div>

<!-- ======================================================================================================================== -->

{$notes}

<!-- ======================================================================================================================== -->

<input id="clickoff" size="1" type="text" style="opacity:0;font-size:1px;width:1px;height:1px;"/>
<tr id="article-{$id}" class="{$type} {$note} {if $edit}edit{/if} {if $checked}checked{/if}">
	
	<td class="date">
		<a href="?event=article&step=edit&ID={$id}&image_view=min&row={$row}">{$adate}</a>
	</td>
	
	<td width="200" class="{$type}">
		{if $opened}<a href="index.php?event=list&close={$id}" onclick="folder('close','{$id}'); return false;"><img class="openclose" src="txp_img/arrow_open.gif" width="9" height="9" alt="[-]" border="0"></a>{/if}
		{if $closed}<a href="index.php?event=list&open={$id}" onclick="folder('open','{$id}'); return false;"><img class="openclose" src="txp_img/arrow_close.gif" width="9" height="9" alt="[+]" border="0"></a>{/if}
		
		{if $note}<a href="#" onclick="note('open','{$id}');return false;" title="View note"><img class="note" src="txp_img/note_icon.png" width="10" height="10" alt="[!]" border="0"></a>{/if}
		
		{if $image}
			<a href="?event=article&step=edit&ID={$id}&image_view=max&row={$row}">
			<img src="{$image}" class="mini" width="{$tsize}" height="{$tsize}" border="0"/></a>
		{/if}
		
		{if $type eq 'parent' or $note}<div>{/if}
		
		{capture name=title}{if $note}Edit note{else}Edit article{/if}{/capture}
		
		{if $edit}
			<input name="title[]" class="title{if $image} title-{$tsize}{/if}" type="text" value="{$title}"/>
		{else}
			{if $title}
				<a class="title" href="?event=article&step=edit&ID={$id}&image_view=min&row={$row}" title="{$smarty.capture.title}">{$title}</a>
				{if $children and $closed}<span class="children">{$children}</span>{/if}
				{if $fileext and !$filecol}<span class="file">{$fileext}</span>{/if}
			{else}
				<a class="title" href="?event=article&step=edit&ID={$id}&image_view=min&row={$row}" title="{$smarty.capture.title}" class="blank">&minus;</a>
			{/if}
		{/if}
		
		{if $type eq 'parent' or $note}</div>{/if}
	</td>
	
	<td width="75">{$section}</td>
	
	{if $category_max gte 1}
		{if $category1}
			<td width="75" class="category">{$category1}</td>
		{else}
			<td width="75" class="blank">&minus;</td>
		{/if}
	{/if}
	
	{if $category_max gte 2}
		{if $category2}
			<td width="75" class="category">{$category2}</td>
		{else}
			<td width="75" class="blank">&minus;</td>
		{/if}
	{/if}
	
	{if $category_max gte 3}
		{if $category3}
			<td width="75" class="category">{$category3}</td>
		{else}
			<td width="75" class="blank">&minus;</td>
		{/if}
	{/if}
	
	{if $category_max gte 4}
		{if $category4}
			<td width="75" class="category">{$category4}</td>
		{else}
			<td width="75" class="blank">&minus;</td>
		{/if}
	{/if}
	
	{$custom}
	
	{if $filecol}
		{if $filename}
			<td width="75" class="file">{$filename}</td>
		{else}
			<td width="75" class="blank">&minus;</td>
		{/if}
	{/if}
	
	<td>{$author}</td>
	
	{if $edit}
		<td width="45" class="status">{html_options name=status[] options=$statuses selected=$status}</td>
	{else}
		<td width="45">{$status}</td>
	{/if}
	
	{if $position}
		{if $edit}
			<td>{html_options name=position[] options=$positions selected=$position}</td>
		{else}
			<td>{$position}</td>
		{/if}
	{else}
		<td class="blank">&minus;{if $edit} <input type="hidden" name="position[]" value="0">{/if}</td>
	{/if}
	
	<td class="chbox"><input type="checkbox" name="selected[]" {if $checked}checked="yes"{/if} value="{$id}" id="{$id}" onchange="last_check(this.value)"/></td>

</tr>

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->



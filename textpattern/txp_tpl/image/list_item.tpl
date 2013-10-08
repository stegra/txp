<tr>
	<td><nobr>{$adate}</nobr></td>
	<td>{$size}</td>
	
	{if $can_edit}
		<td><a href="?event=image&step=image_edit&id={$id}&row={$row}">{$name}</a></td>
	{else}
		<td>{$name}</td>
	{/if}
		
	{if $category}
		<td><nobr>{$category}</nobr></td>
	{else}
		<td class="blank">&minus;</td>
	{/if}
	
	{if $show_copyright}
		{if $copyright}
			<td>{$copyright|truncate:30}</td>
		{else}
			<td class="blank">&minus;</td>
		{/if}
	{/if}
	
	{if $show_caption}
		{if $caption}
			{if $tsize == 20}<td>{$caption|truncate:30}</td>{else}<td width="165">{$caption|truncate:70}</td>{/if}
		{else}
			<td class="blank">&minus;</td>
		{/if}
	{/if}
	
	{if $show_alt}
		{if $alt}
			{if $tsize == 20}<td>{$alt|truncate:30}</td>{else}<td width="165">{$alt|truncate:70}</td>{/if}
		{else}
			<td class="blank">&minus;</td>
		{/if}
	{/if}
	
	{if $show_keywords}
		{if $keywords}
			{if $tsize == 20}<td>{$keywords|truncate:30}</td>{else}<td width="165">{$keywords|truncate:70}</td>{/if}
		{else}
			<td class="blank">&minus;</td>
		{/if}
	{/if}
	
	{if $show_author}
		<td>{$author}</td>
	{/if}	
		
	{if $can_edit}
		<td><a href="?event=image&step=image_edit&id={$id}&row={$row}"><img src="{$src}" width="{$tsize}" height="{$tsize}" border="0" class="small"/></a></td>
	{else}
		<td><img src="{$src}" width="{$tsize}" height="{$tsize}" border="0" class="small"/></td>
	{/if}
	
	<td width="10">{if $can_edit}<input type="checkbox" value="{$id}" name="selected[]" />{/if}</td>
	
</tr>
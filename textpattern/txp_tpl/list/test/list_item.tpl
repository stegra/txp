{capture name=title}{strip}
	{if $title neq 'Untitled'}{$title}{else}&mdash;{/if}
{/strip}{/capture}

{cycle assign="oddeven" values="odd,even"}
{strip}
<tr id="article-{$id}" class="{if $checked}checked{/if} {$folder} {$alias} {if $edit}edit{else}view{/if} level-{$level} {$oddeven} {$first_row}{$last_row}">
	
	
	<!-- {section name=spacer start=2 loop=$level}
		<td class="indent level-{$smarty.section.spacer.index}"></td>
	{/section} -->
	
	
	
	<td class="indent" colspan="{$level - 1}" align="right" valign="top">
		
		{if $child_count}
			{if $opened}<a class="folder open" title="Close" href="index.php?event=list&close={$id}">[&minus;]</a>{/if}
			{if $closed}<a class="folder closed" title="Open" href="index.php?event=list&open={$id}">[+]</a>{/if}
		{else}
			<span class="bullet">&#8250;</span>
		{/if}
		
	</td>
	
	<td class="title" colspan="{$maxlevel - $level + 1}" valign="top">
		
		{if $edit}
			<input name="title[{$id}]" class="title" type="text" value="{$title}"/>
		{else}
			{if !$in_trash}
				<a class="title top" title="{$title}" rel="{$id}" href="?event=list&id={$title_id}">{$smarty.capture.title}</a>
			{else}
				{$smarty.capture.title}
			{/if}
			{if $child_count and $closed}&nbsp;<span class="children">{$child_count}</span>{/if}
			{if $fileext}&nbsp;<span class="file">{$fileext}</span>{/if}
		{/if}
		
	</td>
	
	{if $col_image}
		<td class="thumb">
			{if $image_name}
				{if $trash}
					<div style="background-image:url('/{$img_dir}/{$image_id}/{$image_name}{if $trash_cnt}_filled{/if}_{$thumb}.{$image_ext}')"><a title="Edit Image" href="index.php?event=image&step=image_edit&id={$image_id}"><img src="/{$img_dir}/{$image_id}/{$image_name}{if $trash_cnt}_filled{/if}_{$thumb}.{$image_ext}" class="mini trash" width="{$tsize}" height="{$tsize}" border="0"/></a></div>
				{else}
					<div style="background-image:url('/{$img_dir}/{$image_id}/{$image_name}_{$thumb}.{$image_ext}')"><a title="Edit Image" href="index.php?event=image&step=image_edit&id={$image_id}"><img src="/{$img_dir}/{$image_id}/{$image_name}_{$thumb}.{$image_ext}" class="mini" width="{$tsize}" height="{$tsize}" border="0"/></a></div>				
				{/if}
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
	
	{if $col_file}
		{if $filename}
			<td>{$filename}</td>
		{else}
			<td class="blank">&mdash;</td>
		{/if}
	{/if}
	
	{if $col_play}
		<td>
		{if $fileext eq 'mp3'}
			{include file="play2.tpl"}
		{/if}
		</td>
	{/if}
	
	{if $col_author}
		{if $edit}
			<td>{html_options name="author[{$id}]" options="$authors" selected="$author"}</td>
		{else}
			<td>{$author}</td>
		{/if}
	{/if}
	
	{if $col_status}
		{if $edit}
			<td class="status">{html_options name="status[{$id}]" options="$statuses" selected="$status"}</td>
		{else}
			<td>{$status}</td>
		{/if}
	{/if}
	
	{if $col_position}
		{if $position}
			{if $edit}
				<td>{html_options name="position[{$id}]" options="$positions" selected="$position"}</td>
			{else}
				<td>{$position}</td>
			{/if}
		{else}
			<td class="blank">&mdash;</td>
		{/if}
	{/if}
		
	<td class="chbox"><input type="checkbox" name="selected[{$id}]" {if $checked}checked="yes"{/if} value="{$id}" class="article" id="{$id}"/></td>
</tr>
{/strip}
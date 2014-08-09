{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}

{if $name eq 'Title'}

	{capture name=title}{strip}
		{if $value neq 'Untitled'}{$value}{else}&mdash;{/if}
	{/strip}{/capture}
	
	{if $level eq 1}
		
		{capture name=title_colspan}{strip}
			{if $col_image and !$image_name and $next_col eq 'image'}2{else}1{/if}
		{/strip}{/capture}
	
		<td class="title {$td_view_mode} col col-{$pos}" colspan="{$smarty.capture.title_colspan}">
			
			<div class="pad">
			
			<span class="arrow"><a href="#"></a></span>
			
			{if $previd} 
				<a class="prev" title="Previous" href="?event={$event}&win={$winid}&id={$previd}">&#171;</a>
			{/if}
			
			<span class="h4">
				{if $is_edit_mode}
					<input name="{$name}[{$id}]" class="text title" type="text" value="{$value}"/>
				{elseif $event eq 'list'}
					<a title="Edit {$value}" href="index.php?event=article&step=edit&win={$winid}&id={$id}">{$value}</a>
				{else}
					<a title="Edit {$value}" href="index.php?event={$event}&step=edit&win={$winid}&id={$id}">{$value}</a>
				{/if}
				
				{if $show_file_ext}<span class="file">{$file_ext1}</span>{/if}
			</span> 
			
			</div>
			
		</td>
	
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		
	{else}
	
		<td class="title {$td_view_mode} col col-{$pos}">
			
			<div class="pad">
			
			{if $level gt 2}
				<span class="in">{section name=indent loop=$level-2}.....{/section}&nbsp;</span>
			{/if}
	
			{if $child_count and !$is_flat}
				<span class="button">[</span>
				{if $is_open}<a class="folder open" title="Close" href="index.php?event={$event}&win={$winid}&close={$id}">&minus;</a>{/if}
				{if $is_closed}<a class="folder closed" title="Open" href="index.php?event={$event}&win={$winid}&open={$id}">+</a>{/if}
				<span class="button">]&nbsp;</span>
			{else}
				<span class="bullet">&#8250;&nbsp;</span>
			{/if}
			
			{if $is_edit_mode}
				<input name="{$name}[{$id}]" class="text title" type="text" value="{$value}"/>
			{else}
				
				{if $is_leaf}<span class="title">{/if}
				
				{if !$in_trash}
					
					{if $event eq 'list'}
						<a class="title top" title="{$title_path}{$value}" rel="{$id}" href="index.php?event=article&step=edit&id={$id}">{$smarty.capture.title}</a>
					{else}
						<a class="title top" title="{$title_path}{$value}" rel="{$id}" href="?event={$event}&step=edit&win={$winid}&id={$id}">{$smarty.capture.title}</a>
					{/if}
					
				{else}
					{$smarty.capture.title}
				{/if}
				
				{if $child_count and ($is_closed or $is_flat)}
					<span class="children">{$child_count}</span>
				{/if}
				
				{if $show_file_ext}
					<span class="file">{$file_ext1}</span>
					{if $file_ext1 neq $file_ext2}
						<span class="file">{$file_ext2}</span>
					{/if}
				{/if}
				
				{if $is_leaf}</span>{/if}
				
				{if $more}
					<span class="more">
					{if $level eq 2}
						<a class="load-more" href="#" title="Load more">LOAD MORE</a>
					{else}
						<a href="index.php?event=list&step=hoist&id={$parent_id}" title="View all items">{$more} MORE</a>
					{/if}
					</span>
				{/if}
			
			{/if}
			
			</div>
			
		</td>
		
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		
	{/if}
	
{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}

{elseif $name eq 'Image'}

	{if $level eq 1}
	
		{if $prev_col eq 'title' and $image_name or $prev_col neq 'title' }
		
		<td class="thumb view col col-{$pos} {$image_trsp}">
			
			{if $image_name}
				{if $is_view_grid}
					<div id="image-{$id}" class="image" draggable="true"><a draggable="false" title="Edit Image" rel="mini" data-id="{$id}" href="index.php?event=image&step=edit&id={$image_id}&win=new&mini=1"><img id="article-image-{$image_id}" draggable="false" src="../{$img_dir}/{$image_path}/{$image_name}_z.{$image_ext}" class="mini" width="20" height="20" border="0"/></a></div>				
				{else}
					{if $is_root}
						<div id="image-{$id}" class="image" draggable="true"><a draggable="false" title="Edit Image" rel="mini" data-id="{$id}" href="index.php?event=image&step=edit&id={$image_id}&win=new&mini=1"><img id="article-image-{$image_id}" draggable="false" src="../{$img_dir}/{$image_path}/{$image_name}_t.{$image_ext}" class="mini" border="0"/></a></div>	
					{else}
						<div id="image-{$id}" class="image" draggable="true"><a draggable="false" title="Edit Image" rel="mini" data-id="{$id}" href="index.php?event=image&step=edit&id={$image_id}&win=new&mini=1"><img id="article-image-{$image_id}" draggable="false" src="../{$img_dir}/{$image_path}/{$image_name}_y.{$image_ext}" class="mini" width="50" height="50" border="0"/></a></div>				
					{/if}																											       
				{/if}
			{/if}
			
			{if $is_trash}
				
				{capture name=trash}{strip}
					{if $child_count gt 3}3{else}{$child_count}{/if}
				{/strip}{/capture}
				
				<div id="image-{$id}" class="image trash" draggable="false"><a draggable="false" title="Trash" data-id="{$id}" href="#"><img id="article-image-{$image_id}" draggable="false" src="/admin/theme/classic/txp_img/trash_icon_{$smarty.capture.trash}.png" class="mini" width="50" height="50" border="0"/></a></div>	
			{/if}
			
		</td>
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		{/if}
	
	{else}
		
		<td class="thumb thumb-{$thumb} {$is_note} view col col-{$pos} {$image_trsp}">
			
			{if $image_name}
				<div id="image-{$id}" class="image" draggable="true"><a draggable="false" title="Edit Image" rel="mini" href="index.php?event=image&step=edit&id={$image_id}&win=new&mini=1"><img id="article-image-{$image_id}" draggable="false" src="../{$img_dir}/{$image_path}/{$image_name}_{$image_size}.{$image_ext}" class="mini" width="{$image_width}" height="{$image_height}" border="0"/></a></div>				
			{/if}
			
			{if $is_note}
				<div id="item-{$id}" class="image" draggable="true"><a draggable="false" title="View Note" rel="mini" class="note" href="{$id}_{$content}"><img id="article-image-{$image_id}" draggable="false" src="/admin/theme/classic/txp_img/icon_note_{$thumb}.png" class="mini" width="{$image_width}" height="{$image_height}" border="0"/></a></div>
			{/if}
		
		</td>
		
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

	{/if}

{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}

{elseif $name eq 'Categories'}
	
	{if $value}
		<td class="categories multi-select {$td_view_mode} col col-{$pos}">{$value}</td> 
	{else}
		<td class="categories view col col-{$pos} blank">&mdash;</td>
	{/if}
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{elseif $name eq 'Play'}
	
	<td class="{$name|lower} view col col-{$pos}">
	
		{if $file_ext1 eq 'mp3'}
			{include file="play_button.tpl"}
		{/if}
	
	</td>
		
{elseif $name eq 'Type' and !$is_edit_mode and $value eq 'folder'}
	
	<td class="{$name|lower} view col col-{$pos}">Group</td>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{elseif $name eq 'Body' and $is_edit_mode}
	
	<td class="{$name|lower} edit col col-{$pos}">
		<div class="textarea"><textarea name="{$name}[{$id}]">{$value}</textarea></div>
	</td>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{elseif $name eq 'Excerpt' and $is_edit_mode}
	
	<td class="{$name|lower} edit col col-{$pos}">
		<div class="textarea"><textarea name="{$name}[{$id}]">{$value}</textarea></div>
	</td>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}

{elseif $is_edit_mode}
	
	<td class="{$name|lower} edit col col-{$pos}">
		
		{if $options}
			{html_options name="{$name}[{$id}]" options="$options" selected="$value"}
		{else}
			<input name="{$name}[{$id}]" class="text" type="text" value="{$value}"/>
		{/if}
	
	</td>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}

{else}
	
	{if $value}
		<td class="{$name|lower} {$td_view_mode} col col-{$pos}">{$value}</td>
	{else}
		<td class="{$name|lower} view col col-{$pos} blank">&mdash;</td>
	{/if}
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->	

{/if}

{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}

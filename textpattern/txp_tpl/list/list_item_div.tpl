<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
<!-- {$id} / {$item_title} / {$item_name} -->

{if $level gt 1}
	
	<div id="article-{$id}" data-pos="{$row_pos}" class="
		
		data 
		child
		grid
		row-{$row_pos}
		level-{$level}
		size-{$thumb}
		{$display_mode}
		{$is_first_row}
		{$is_last_row}
		{$is_checked}
		{$is_open} 
		{$is_closed} 
		{$is_folder} 
		{$is_alias}">
		
		<div id="image-{$id}" class="panel size-{$thumb}" draggable="true">

			<table width="100%">
			<tr><td>
			
				<a draggable="false" title="{$item_title} {if $child_count}({$child_count}){/if}" href="index.php?event={$event}&step=list&id={$id}&win={$winid}">
					
					{if $image_name}<img draggable="false" src="../{$img_dir}/{$image_path}/{$image_name}_{$image_size}.{$image_ext}" class="mini" width="{$image_width}" height="{$image_height}" border="0"/>{/if}
					
					<span class="border" id="article-image-{$image_id}"></span>
					
					<span class="title">
						{if $item_title}{$item_title}{else}&mdash;&mdash;&mdash;{/if}
					</span>
					
					{if $child_count}<span class="folder"></span>{/if}
				</a>
			
			</td><td align="right">
			
				<span class="chbox">
					<input style="display:none" type="checkbox" name="selected[{$id}]" {if $is_checked}checked="yes"{/if} value="{$id}" class="article" id="{$id}"/>
				</span>
			
			</td></tr>
			</table>
			
		</div>
		
		{if $child_count}		
			
			<div class="panel behind one {$tilt} size-{$thumb}"><a href="#"></a></div>
			
			{if $child_count gt 1}	
				
				{capture name=tilt}{strip}
					{if $tilt eq 'right'}left{else}right{/if}
				{/strip}{/capture}
		
				<div class="panel behind two {$smarty.capture.tilt} size-{$thumb}"><a href="#"></a></div>					
			{/if}
			
		{/if}
		
	</div>
	
{/if}


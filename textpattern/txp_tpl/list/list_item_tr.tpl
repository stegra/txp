<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
<!-- {$id} / {$item_title} / {$item_name} -->

{cycle assign="odd_even" values="even,odd"}

{if $level gt 1}

	<tr id="article-{$id}" data-pos="{$row_pos}" class="
		
		data 
		child
		row-{$row_pos}
		level-{$level}
		type-{$type}
		{$display_mode}
		{$odd_even}
		{$is_first_row}
		{$is_last_row}
		{$is_checked}
		{$is_open} 
		{$is_closed} 
		{$is_folder} 
		{$is_leaf} 
		{$is_alias}">
		
		{$column_data}
		
		{$custom_column_data}
		
		<td class="chbox">
			<input type="checkbox" name="selected[{$id}]" {if $is_checked}checked="yes"{/if} value="{$id}" class="article" id="{$id}"/>
		</td>

	</tr>

{/if}


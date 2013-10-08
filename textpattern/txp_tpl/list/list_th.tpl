{if $name eq 'image'}

	<th id="image" class="thumb size-{$thumb} {$is_selected}" data-pos="{$position}"></th>

{elseif $name eq 'File' or $name eq 'Play'}
	
	<th id="{$name|lower}" class="{$is_selected}" data-pos="{$position}"><nobr>{$title}</nobr></th>

{else}
	
	<th id="{$name|lower}" class="{$name|lower} {$is_selected}" data-pos="{$position}">
		<nobr><a title="{$linkdir_title}" href="index.php?event={$event}&win={$winid}&sort={$name}&dir={$linkdir}">{$title}</a></nobr>
	</th>
	
{/if}





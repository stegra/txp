<!-- MAIN ITEM - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{$list_item_main}

<!-- LIST ITEMS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<tr class="hr" id="hr2"><td colspan="20"><div><span></span></div></td></tr>

{if $is_view_grid}
	
	<tr class="grid">
		
		<td class="grid" colspan="19">

			<div class="pad">
				{$list_items}
				<div class="clear"></div>
			</div>
				
		</td>
		
		<td class="margin"></td>
	
	</tr>

{else}

	{$list_items}
	
{/if}

<tr class="hr" id="hr3"><td colspan="20"><div><span></span></div></td></tr>
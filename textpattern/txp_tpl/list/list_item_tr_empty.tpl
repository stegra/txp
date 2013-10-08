{if $list_items_count lt 11}
	{section name=empty start=$list_items_count+1 loop=12}
		<tr class="data even col-{$column_count-1} empty empty-{$smarty.section.empty.index}">
			{section name=columns start=0 loop=$column_count}<td class="col col-{$smarty.section.columns.index}">&nbsp;</td>{/section}
			<td class="chbox" style="padding:0px"></td>
		</tr>
	{/section}
{/if}
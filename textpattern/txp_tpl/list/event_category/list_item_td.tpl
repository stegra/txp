{if $name eq 'Class' and $is_edit_mode}
	
	<td class="class edit col col-{$pos}">
		<select name="Class[{$id}]">
			<option value="no">No</option>
			<option value="yes" {if $value eq 'yes'}selected="selected"{/if}>Yes</option>
		</select>
	</td>

{/if}
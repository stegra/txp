<form method="post" action="index.php">

<table cellpadding="3" cellspacing="0" border="0" id="edit" align="center" class="edit-pane">
<tr>
	<td class="noline" style="text-align: right; vertical-align: middle;">{$txt_category_name} </td>
	<td class="noline"><input type="text" value="{$name}" name="name" size="20" class="edit" tabindex="1" /></td>
</tr>
<tr>
	<td class="noline" style="text-align: right; vertical-align: middle;">{$txt_category_title} </td>
	<td class="noline"><input type="text" value="{$title}" name="title" size="30" class="edit" tabindex="1" /></td>
</tr>
<tr>
	<td class="noline" style="text-align: right; vertical-align: middle;">Parent </td>
	<td>{$parent}</td>
</tr>

{if $evname eq 'article'}
<tr>
	<td class="noline" style="text-align: right; vertical-align: middle;">Class </td>
	<td>{$class}</td>
</tr>
<tr>
	<td class="noline" style="text-align: right; vertical-align: middle;">On frontpage </td>
	<td>{$frontpage}</td>
</tr>
{/if}

<tr>{$category_ui}</tr>
<tr><input type="hidden" name="id" value="{$id}"/></tr>
<tr>
	<td>&#160;</td>
	<td align="left" valign="top" colspan="2"><br />
		<input type="submit" value="Save" class="smallerbox" />
	</td>
</tr>
	<input type="hidden" name="event" value="category"/>
	<input type="hidden" name="step" value="{$step}"/>
	<input type="hidden" name="old_name" value="{$name}"/>
</table>

</form>
<table class="custom-fields" cellpadding="0" cellspacing="0" border="0" id="list" align="center">
<tr>
	<td align="left" valign="top" colspan="3"><h1>{$txt_custom}</h1></td>
</tr>
<tr>
	<td align="left" valign="top" colspan="3">
		<a href="?event=prefs&step=prefs_list" class="navlink">{$txt_site_prefs}</a>
		<a href="?event=prefs&step=advanced_prefs" class="navlink">{$txt_advanced_preferences}</a> 
		<a href="?event=prefs&step=list_languages" class="navlink">{$txt_manage_languages}</a> 
		<a href="?event=prefs&step=custom_fields" class="navlink-active">{$txt_custom}</a>
	</td>
</tr>
<tr>
	<td width="350" colspan="3" style="border:0">
		<form method="post" class="create" action="index.php">
			<input type="text" value="" name="name" size="20" class="edit" />
			<input type="submit" value="{$txt_create}" class="smallerbox" />
			<input type="hidden" value="prefs" name="event" />
			<input type="hidden" value="custom_fields_create" name="step" />
		</form>
	</td>
</tr>

{$items}

</table>
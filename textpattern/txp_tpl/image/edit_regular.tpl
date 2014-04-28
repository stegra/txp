<div class="picture"><img src="{$reg_src}" width="{$reg_w}" height="{$reg_h}" alt="" /></div>

{if $w}
<form name="resize_r">
	<div class="size">{$reg_w} x {$reg_h}</div>
	<div id="regular-resize-box" class="resize">

		<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<input type="hidden" name="id" value="{$id}" />
				<input type="hidden" name="event" value="image" />
				<input type="hidden" name="step" value="image_resize_r" />
			</td>
			<td valign="middle"><input type="checkbox" name="bywidth" {if $reg_w_by}checked="yes"{/if} title="Lock in width" onClick="document.resize_r.byheight.checked=false"></td>
			<td valign="middle"><input id="new_width_r" onChange="txp.edit.image.calcResize('r','width',{$w},{$h})" type="text" name="new_width" value="{$reg_w_new}" size="4" class="edit image" /></td>
			<td valign="middle">x</td>
			<td valign="middle"><input id="new_height_r" onChange="txp.edit.image.calcResize('r','height',{$w},{$h})" type="text" name="new_height" value="{$reg_h_new}" size="4" class="edit image" /></td>
			<td valign="middle"><input type="checkbox" name="byheight" {if $reg_h_by}checked="yes"{/if} title="Lock in height" onClick="document.resize_r.bywidth.checked=false"></td>
			<td><img class="crop" src="txp_img/spacer.gif" width="5" height="1" border="0" alt="" /></td>
			<td valign="middle"><input type="button" onclick="txp.edit.image.resizeImage('r')" name="" value="Resize" class="smallerbox resize" /></td>
			<td valign="middle"><a title="Help" href="help/resize.html" class="pophelp">?</a></td>
		</tr>
		</table>
	
	</div>
</form>
{/if}
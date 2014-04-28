<div class="picture" style="height:{$thumb_h + 2}px">
	<img id="thumbnail-image" src="{$thumb_src}" width="{$thumb_w}"  height="{$thumb_h}" alt="" />
</div>

{if $w}
<form name="resize_t">
	<div class="size">{$thumb_w} x {$thumb_h}</div>
	<div id="thumbnail-resize-box" class="resize">

		<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			{if $horizontal}
			<td width="21" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(1,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop1',1);" onMouseOut="txp.edit.image.border('crop1',0);"><img class="crop {$class1}" id="crop1" src="txp_img/crop_h1.gif" width="17" height="11" border="0" alt="Crop left"   title="Crop left" /></a></td>
			<td width="21" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(2,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop2',1);" onMouseOut="txp.edit.image.border('crop2',0);"><img class="crop {$class2}" id="crop2" src="txp_img/crop_h2.gif" width="17" height="11" border="0" alt="Crop middle" title="Crop middle" /></a></td>
			<td width="21" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(3,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop3',1);" onMouseOut="txp.edit.image.border('crop3',0);"><img class="crop {$class3}" id="crop3" src="txp_img/crop_h3.gif" width="17" height="11" border="0" alt="Crop right"  title="Crop right" /></a></td>
			<td width="21" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(4,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop4',1);" onMouseOut="txp.edit.image.border('crop4',0);"><img class="crop {$class4}" id="crop4" src="txp_img/crop_h4.gif" width="17" height="11" border="0" alt="Do not crop" title="Do not crop" /></a></td>
			{else}			
			<td width="15" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(1,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop1',1);" onMouseOut="txp.edit.image.border('crop1',0);"><img class="crop {$class1}" id="crop1" src="txp_img/crop_v1.gif" width="11" height="17" border="0" alt="Crop top"    title="Crop top" /></a></td>
			<td width="15" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(2,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop2',1);" onMouseOut="txp.edit.image.border('crop2',0);"><img class="crop {$class2}" id="crop2" src="txp_img/crop_v2.gif" width="11" height="17" border="0" alt="Crop middle" title="Crop middle" /></a></td>
			<td width="15" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(3,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop3',1);" onMouseOut="txp.edit.image.border('crop3',0);"><img class="crop {$class3}" id="crop3" src="txp_img/crop_v3.gif" width="11" height="17" border="0" alt="Crop bottom" title="Crop bottom" /></a></td>
			<td width="15" class="crop" align="center" valign="middle"><a href="#" onClick="txp.edit.image.selCrop(4,{$w},{$h},{$thumb_w_max},{$thumb_h_max});" onMouseOver="txp.edit.image.border('crop4',1);" onMouseOut="txp.edit.image.border('crop4',0);"><img class="crop {$class4}" id="crop4" src="txp_img/crop_v4.gif" width="11" height="17" border="0" alt="Do not crop" title="Do not crop" /></a></td>
			{/if}
			
			<td><img class="crop" src="txp_img/spacer.gif" width="1" height="1" border="0" alt="" /></td>
			<td valign="middle">
				<input type="hidden" name="crop" value="{$thumb}" />
				<input type="hidden" name="id" value="{$id}" />
				<input type="hidden" name="event" value="image" />
				<input type="hidden" name="step" value="image_resize_t" />
			</td>
			<td valign="middle"><input type="checkbox" name="bywidth" {if $thumb_w_by}checked="yes"{/if} title="Lock in width" onClick="document.resize_t.byheight.checked=false"></td>
			<td valign="middle"><input id="new_width_t" onChange="txp.edit.image.calcResize('t','width',{$thumb_w_max},{$thumb_h_max})" type="text" name="new_width" value="{$thumb_w_new}" size="4" class="edit image" /></td>
			<td valign="middle">x</td>
			<td valign="middle"><input id="new_height_t" onChange="txp.edit.image.calcResize('t','height',{$thumb_w_max},{$thumb_h_max})" type="text" name="new_height" value="{$thumb_h_new}" size="4" class="edit image" /></td> 
			<td valign="middle"><input type="checkbox" name="byheight" {if $thumb_h_by}checked="yes"{/if} title="Lock in height" onClick="document.resize_t.bywidth.checked=false"></td>
			<td><img class="crop" src="txp_img/spacer.gif" width="5" height="1" border="0" alt="" /></td>
			<td valign="middle"><input type="button" onclick="txp.edit.image.resizeImage('t')" name="" value="Resize" class="smallerbox resize" /></td>
			<td valign="middle"><a title="Help" href="help/resize.html" class="pophelp">?</a></td>
		</tr>
		</table>
		
	</div>
</form>
{/if}

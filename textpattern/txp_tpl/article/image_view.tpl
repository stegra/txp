<a class="remove" title="Remove this image" href="#">Remove</a>

<a class="edit" href="?event=image&step=edit&id={$img_id}&mini=1&win=new" title="Edit this image">
<img id="article-image" src="../{$img_dir}/{$img_path}/{$name}_x{$ext}" class="small" width="100" height="100" border="0"/></a>

{if $type eq 'regular'}
<!--
<h4 class="list">Display in list</h4>

<select name="display_list" class="list" onChange="save_image_setting(this)">
	<option value="before" {if $display_list eq 'before'}selected="yes"{/if}>Before text</option>
	<option value="after"  {if $display_list eq 'after'}selected="yes"{/if}>After text</option>
	<option value="x"      {if $display_list eq 'x'}selected="yes"{/if}>No display</option>
</select>

<select name="imgtype_list" class="list" onChange="save_image_setting(this)">
	<option value="t" {if $imgtype_list eq 't'} selected="yes"{/if}>Thumbnail</option>
	<option value="r" {if $imgtype_list eq 'r'} selected="yes"{/if}>Regular size</option>
</select>

<select name="align_list" class="list" onChange="save_image_setting(this)">
	<option value="right"  {if $align_list eq 'right'}selected="yes"{/if}>Right</option>
	<option value="left"   {if $align_list eq 'left'}selected="yes"{/if}>Left</option>
	<option value="center" {if $align_list eq 'center'}selected="yes"{/if}>Center</option>
	<option value="x"      {if $align_list eq 'x'}selected="yes"{/if}>-</option>
</select>

<h4 class="single">Display in single</h4>

<select name="display_single" class="list" onChange="save_image_setting(this)">
	<option value="before" {if $display_single eq 'before'}selected="yes"{/if}>Before text</option>
	<option value="after"  {if $display_single eq 'after'}selected="yes"{/if}>After text</option>
	<option value="x"      {if $display_single eq 'x'}selected="yes"{/if}>No display</option>
</select>

<select name="imgtype_single" class="list" onChange="save_image_setting(this)">
	<option value="t" {if $imgtype_single eq 't'}selected="yes"{/if}>Thumbnail</option>
	<option value="r" {if $imgtype_single eq 'r'}selected="yes"{/if}>Regular size</option>
</select>

<select name="align_single" class="list" onChange="save_image_setting(this)">
	<option value="right"  {if $align_single eq 'right'}selected="yes"{/if}>Right</option>
	<option value="left"   {if $align_single eq 'left'}selected="yes"{/if}>Left</option>
	<option value="center" {if $align_single eq 'center'}selected="yes"{/if}>Center</option>
	<option value="-"      {if $align_single eq '-'}selected="yes"{/if}>-</option>
</select>
-->
{/if}
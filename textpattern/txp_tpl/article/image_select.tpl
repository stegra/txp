<div class="select">
		
	Show: 
	<select name="category" class="list" onchange="get_images(this)">
		{html_options options=$categories selected=$category}
	</select>

	<input class="checkbox" type="checkbox" name="unused" onchange="get_images(this)" {if $unused eq 'on'}checked="checked"{/if}>Unused 

</div>
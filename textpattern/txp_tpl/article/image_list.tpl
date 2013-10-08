{section name=i loop=$images}
	<li><a class="add" id="{$images[i].id}" title="{$images[i].name}{$images[i].ext}" onclick="add_image(this.id)" href="#"><img src="/{$img_dir}/{$images[i].path}/{$images[i].name}_y{$images[i].ext}" class="small" width="50" height="50" border="0"/></a></li>
{/section}
<table cellpadding="0" cellspacing="0" border="0" id="edit" align="center">
<tr>
	<td colspan="2" class="prevnext"><p>{$prevnext}</p></td>
</tr>
<tr>
	<td class="left" rowspan="2" valign="top">
		
		<form action="index.php" method="post">
		
			<input type="hidden" name="id" value="{$id}" />
			<input type="hidden" name="win" value="{$winid}" />
			<input type="hidden" name="event" value="image" />
			<input type="hidden" name="step" value="save" />
			
			<p>{$txt_image_title}<br /><input type="text" name="title" value="{$title}" class="edit title" /></p>
			
			<p>{$txt_image_name}<br /><input type="text" name="name" value="{$name}" class="edit name" />{$ext}</p>
			
			{if $category}
			<!-- <p class="category">{$txt_category}<br />{$category}</p> -->
			{/if}
			
			<p>{$txt_alt_text}<br /><input type="text" name="alt" value="{$alt}" size="50" class="edit alt-text" /></p>
			
			<p>{$txt_caption}<br /><textarea class="caption" name="caption" cols="80" rows="5" style="">{$caption}</textarea></p>
			
			<p>{$txt_copyright}<br /><input type="text" name="copyright" value="{$copyright}" size="50" class="edit copyright" /></p>
			
			{if $keywords}
			<p class="metadata">Keywords<br /><span>{$keywords}</span></p>
			{/if}
			
			{if $description}
			<p class="metadata">Description<br /><span>{$description}</span></p>
			{/if}
			
			<p><input type="submit" name="" value="{$txt_save}" class="publish" /></p>
			
			{if $effect}
			<div class="effect-filters">
				
				<p>Effect Filter</p>
				
				<ul class="{$effect}">
					<li class="none {if $effect eq 'none'}current{/if}">
						<a class="filter" href="#none">None</a>
					</li>
					<li class="grayscale {if $effect eq 'grayscale'}current{/if}">
						<a class="filter" href="#grayscale">Grayscale</a>
					</li>
					<li class="sepia {if $effect eq 'sepia'}current{/if}">
						<a class="filter" href="#sepia">Old Photo</a>
					</li>
				</ul>
				
			</div>
			{/if}
			
		</form>
		
		<!-- 
		
		<form class="replace-image" enctype="multipart/form-data" action="index.php" method="post">
			
			<input type="hidden" name="id" value="{$id}" />
			<input type="hidden" name="event" value="image" />
			<input type="hidden" name="step" value="image_replace" />
			
			<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
			
			<p>
				<a href="#" onClick="toggleDisplay('replace-image');return false;">{$txt_replace_image}</a>
				<span id="replace-image">
					<input type="file" name="thefile" value="" class="edit file" /><br/>
					<input type="submit" name="" value="{$txt_upload}" class="smallerbox replace" />
				</span>
				
			</p>
		</form>
		
		<form class="replace-thumb" enctype="multipart/form-data" action="index.php" method="post">
			
			<input type="hidden" name="id" value="{$id}" />
			<input type="hidden" name="event" value="image" />
			<input type="hidden" name="step" value="thumbnail_insert" />
			
			<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
			
			<p>
				<a href="#" onClick="toggleDisplay('replace-thumb');return false;">{$txt_replace_thumbnail}</a> 
				<a target="_blank" href="help/replace_thumbnail.html" onclick="window.open(this.href,'popupwindow','width=400,height=400,scrollbars,resizable'); return false;" class="pophelp">?</a>
				<span id="replace-thumb"><input type="file" name="thefile" value="" class="edit file" /><br/>
				<input type="submit" name="" value="{$txt_upload}" class="smallerbox replace" /></span>
			</p>
		</form>
		
		{if $thumb_custom}
			<p>
				<a href="?event=image&step=thumbnail_delete&id={$id}" onClick="return confirm('Really delete thumbnail?')">Remove thumbnail</a> 
				<a target="_blank" href="help/remove_thumbnail.html" onclick="window.open(this.href,'popupwindow','width=400,height=400,scrollbars,resizable'); return false;" class="pophelp">?</a>
			</p>
		{/if}
		
		-->
		
	</td>
	<td class="top" valign="top">
		<div class="image-box" id="regular-image-box">{$regular}</div>
	</td>
</tr>
<tr>
	<td class="bottom" valign="top">
		<div class="image-box" id="thumbnail-image-box">{$thumb}</div>
	</td>
</tr>
</table>

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{include file='upload_progress.tpl'}

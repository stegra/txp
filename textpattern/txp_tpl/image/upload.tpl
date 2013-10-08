<hr/>

<form class="upload" method="post" enctype="multipart/form-data" action="index.php">

	<input type="hidden" name="event" value="image"  />
	<input type="hidden" name="step" value="step_insert"  />
	<input type="hidden" name="win" value="{$winid}"  />
	<input type="hidden" name="MAX_FILE_SIZE" value="{$max_file_size}"  />
				
	<table border="0" class="upload" align="center">
	<tr class="upload">
		<td class="title"><nobr>{$txt_upload_image}:</nobr></td> 
		<td class="file"><input type="file" value="" name="thefile" class="edit file" id="image-upload" /></td> 
		<td><nobr><input type="submit" value="{$txt_upload}" class="smallerbox" /> {$pophelp_upload}</nobr></td> 
	</tr>
	</table>

</form>		
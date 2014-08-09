<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<div class="body">
	<span class="body"><label for="body">Description</label>&#160;<a title="Help" target="_blank" href="http://rpc.textpattern.com/help/?item=body&#38;language=en-gb" onclick="popWin(this.href); return false;" class="pophelp">?</a><br /></span>
	<textarea id="body" name="Body" cols="55" rows="3" tabindex="2">{$body}</textarea>
</div>

<div class="field">
	<label for="location_site_path">Full server path to this site</label><br />
	<input type="text" value="{$location_site_path}" name="SiteDir" class="edit" tabindex="6" />
</div>

<div class="field">
	<label for="location_site_url">Site URL</label> &#160;
	{if $status neq 1}[<a title="View Site" target="{$name}_view" href="{$location_site_url}/index.html">view site</a>]{/if}
	<br />
	<input type="text" value="{$location_site_url}" name="URL" class="edit" tabindex="8" />
</div>
	
<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<div class="db twocols">

	<div class="field pos1">
		<label for="db_name">MySQL database</label><br />
		<!-- {html_options name=DB options=$databases selected=$db_name} -->
		<input type="text" value="{$db_name}" name="DB" class="edit" tabindex="4" />
	</div>

	<div class="field pos2">
		<label for="db_prefix">Table prefix</label><br />
		<input type="text" value="{$db_prefix}" name="Prefix" class="edit" tabindex="5" />
	</div>
	
	<div class="clear left"></div>
	
</div>

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
<!--

<div class="location">

	<h3>Site Access</h3>
	
	<div class="field">
		<label for="location_site_hosting">Hosting Provider</label><br />
		<input type="text" value="{$location_site_hosting}" name="location_site_hosting" class="edit" tabindex="9" />
	</div>
		
	<div class="field onecol pos5">
		<label for="admin_ftp">FTP</label>
		<input type="text" value="{$admin_ftp}" name="admin_ftp" class="edit" tabindex="14"/>
	</div>
	
	<div class="field onecol pos6">
		<label for="admin_ssh">SSH</label>
		<input type="text" value="{$admin_ssh}" name="admin_ssh" class="edit" tabindex="15"/>
	</div>
	
</div>

-->
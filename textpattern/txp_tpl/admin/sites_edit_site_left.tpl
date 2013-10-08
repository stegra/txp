<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
<!-- Site Info -->

<div class="site-info">
	
	<p>
		Name<br/>
		
		{if $status neq 1 and $name}
			<b>{$name}</b>
			<input type="hidden" name="Name" value="{$name}"/>
		{else}
			<input type="text" value="{$name}" name="Name" class="edit" tabindex="1" />
		{/if}
	</p>
	
	<p>
		Language<br/>
		
		{if $status neq 1 and $site_lang}
			<b>{$site_langs.$site_lang}</b>
			<input type="hidden" name="Language" value="{$site_lang}"/>
		{else}
			{html_options name=Language options=$site_langs selected=$site_lang}
		{/if}	
	</p>
	
	<p>
		Admin<br/>
		
		{if $status neq 1 and $admin_user}
			<a title="Login" target="{$name}_admin" href="{$location_site_url}/admin/index.php?login={$admin_user}">
			<b>{$admin_user}</b></a>
			<input type="hidden" name="Admin" value="{$admin_user}"/>
		{else}
			{html_options name=Admin options=$admin_users selected=$admin_user}
		{/if}
	</p>
	
	{if $status neq 1}
	
		<p>Articles<br/>{if $info_articles}<b>{$info_articles}</b>{else}<span class="none">None</span>{/if}</p>
		
		<p>Images<br/>{if $info_images}<b>{$info_images}</b>{else}<span class="none">None</span>{/if}</p>
		
		<p>Files<br/>{if $info_files}<b>{$info_files}</b>{else}<span class="none">None</span>{/if}</p>
		
		<!-- 
		{if $admin_ssh}
		<p>SSH<br/><a title="Secure Shell" class="ssh" href="ssh://{$admin_ssh}/">{$admin_ssh}</a></p>
		{/if}
		
		{if $admin_ftp}
		<p>FTP<br/><a title="sftp://{$admin_ftp}" class="ftp" href="sftp://{$admin_ftp}">{$info_ftp}</a></p>
		{/if}
		
		<p><a title="View Site" target="{$name}_view" href="{$location_site_url}/index.html">View Site</a></p>
		-->
	{/if}
	
</div>
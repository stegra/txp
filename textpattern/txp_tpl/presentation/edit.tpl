<div id="edit">
	
	<!-- 
	
	<div class="column left"></div> 
	
	-->
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<div class="column right">
		
		<div id="tag-builder">
		 	
		 	{if $event neq 'css'} 
		 	
			<form>
		
			<select name="tag_builder">
				
				<option value="">Tag Builder</option>
				<option value="">--------------------------------</option>
				
				<optgroup label="Article output">
					<option value="article">Articles (single or list)</option>
					<option value="article_custom">Articles (custom list)</option>
				</optgroup>

				<optgroup label="Article navigation">
					<option value="link_to_next">Next article link</option>
					<option value="link_to_prev">Previous article link</option>
					<option value="next_title">Next article title</option>
					<option value="prev_title">Previous article title</option>
					<option value="newer">Newer articles link</option>
					<option value="older">Older articles link</option>
				</optgroup>
				
				<optgroup label="Site navigation">
					<option value="category_list">Category list</option>
					<option value="link_to_home">Homepage link</option>
					<option value="popup">Popup list</option>
					<option value="recent_articles">Recent articles</option>
					<option value="recent_comments">Recent comments</option>
					<option value="related_articles">Related articles</option>
					<option value="search_input">Search input form</option>
				</optgroup>
				
				<optgroup label="XML feeds">
					<option value="feed_link">Articles feed link</option>
					<option value="link_feed_link">Links feed link</option>
				</optgroup>
				
				<optgroup label="Miscellaneous">
					<option value="email">E-mail link (spam-proof)</option>
					<option value="site_slogan">Site slogan</option>
					<option value="password_protect">Password protection</option>
					<option value="output_form">Output form</option>
					<option value="linklist">Links list</option>
					<option value="css">CSS link (head)</option>
					<option value="sitename">Site name</option>
					<option value="page_title">Page title</option>
					<option value="lang">Language</option>
					<option value="breadcrumb">Breadcrumb</option>
				</optgroup>
				
				<optgroup label="File downloads">
					<option value="file_download">File download</option>
					<option value="file_download_list">File download list</option>
				</optgroup>
			
			</select>
			
			</form>
			
			{/if}
		
		</div>
		
		<div class="list" id="page-list">
			
			<table cellpadding="0" cellspacing="0" border="0" id="list">
			
				{section name=i loop=$list}
					<tr class="row-{$smarty.section.i.index + 1} level-{$list[i].Level}{if $list[i].ID eq $id} selected{/if}">
						<td class="name"><a href="?event={$event}&step=edit&id={$list[i].ID}&win={$winid}">{$list[i].Name}</a></td>
						<td class="type {if $list[i].Type}{$list[i].Type}{else}none{/if}">{if $list[i].Type eq 'folder'}dir{else}{if $list[i].Type}{$list[i].Type}{else}&mdash;{/if}{/if}</td>
					</tr>
				{/section}
				
				{if $total lt 17}
					{section name=empty start=$total+1 loop=18}
					<tr class="level-1"><td>&nbsp;</td><td>&nbsp;</td></tr>
					{/section}
				{/if}
				
			</table>
		
		</div>
				
	</div>
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<div class="column center">
	
		<form method="post" action="index.php">
	
			<p>
			
				You are editing the 
				
				<strong>{$path|lower}</strong>
				
				{if $type eq 'folder'} 
					folder.
				{else}
					{if $event eq 'page'}page.{/if} 
					{if $event eq 'form'}form.{/if} 
					{if $event eq 'css'}style.{/if} 
				{/if}
				
			</p>
		
			<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
			<!-- TEXTAREA -->
			
			<div id="box">
				
				<div id="scrollpane">
					<textarea spellcheck="false" id="code" class="code" name="Body" cols="84" rows="36">{$html}</textarea>
				</div>
			
				<a href="#" title="Line Numbers" id="toggle-line-numbers">1&#183;2&#183;3</a>
			
			</div>
	
			<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
			<input type="submit" name="save" value="Saved" class="publish saved" title="Save" id="save" />
			<input type="hidden" name="event" value="{$event}" />
			<input type="hidden" name="step" value="save" />
			<input type="hidden" name="scroll" value="0" id="scroll" />
			<input type="hidden" name="id" value="{$id}" />
		
		</form>
	
	</div>
	
</div>
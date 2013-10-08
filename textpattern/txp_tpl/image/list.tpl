{capture name="searchform"}

<form id="search" name="search" action="index.php" method="post">

	<input type="hidden" name="event" value="image" />
	<input type="hidden" name="step" value="image_list" />
	
	{if $view_mode_grid}<table class="search">{/if}
	
	<tr class="search">
		<td class="title label">{$txt_search}:</td> 
		<td>
			<nobr>
				
				<select name="category" class="list" onchange="if (this.value) submit(this.form)">
					<option value="all">{$txt_category}</option>
					<option value="">-------------</option>
					{html_options options=$categories selected=$category}
					{if $uncategorized}
					<option value="">-------------</option>
					<option value="any" {if $category eq 'any'}selected="selected"{/if}>Categorized</option>
					<option value="blank" {if $category eq 'blank'}selected="selected"{/if}>Uncategorized</option>
					{/if}
				</select>
				
				<select name="status" class="list" onchange="if (this.value) submit(this.form)">
					<option value="all">Status</option>
					<option value="">-------------</option>
					<option value="used" {if $status eq 'used'}selected="selected"{/if}>Used</option>
					<option value="unused" {if $status eq 'unused'}selected="selected"{/if}>Unused</option>
				</select>
			
				<input class="text" type="text" size="15" value="{$searchtext}" name="searchtext"/>
				<input type="submit" name="" value="Go" class="smallerbox search" style="width: 31px; padding: 2px 7px;margin: 0;"/>
			
			</nobr>
		</td>
		<td>
			<input id="clearsearch" type="hidden"  name="clearsearch" value=""/>
			<input type="hidden" name="win" value="{$window}"/>
			{if $searchtext or $category neq 'all' or $status neq 'all'} 
			<input type="button" value="Clear" class="smallerbox clearsearch search" style="padding: 2px;"/>
			{/if}
			
		</td>
	</tr>
	
	{if $view_mode_grid}</table>{/if}
	
</form>

{/capture}

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<hr/>

{if $warning}<p class="warning">{$warning}</p>{/if}

<table id="frame-view-{$view_mode}" cellpadding="0" cellspacing="0" border="0" align="center">
<tr>
	<td style="border:0">
	
		<table border="0" class="upload-search">
		
			<form method="post" enctype="multipart/form-data" action="index.php">
				
				<input type="hidden" name="event" value="image"  />
				<input type="hidden" name="step" value="image_insert"  />
				<input type="hidden" name="MAX_FILE_SIZE" value="{$max_file_size}"  />
				
				<tr class="upload">
					<td width="50%" class="title"><nobr>{$txt_upload_image}:</nobr></td> 
					<td width="10%" class="file"><input type="file" value="" name="thefile" class="edit file" id="image-upload" /></td> 
					<td width="50%"><nobr><input type="submit" value="{$txt_upload}" class="smallerbox" /> {$pophelp_upload}</nobr></td> 
				</tr>
				
			</form>
						
			{if $existing_files}
			
			<form action="index.php" method="post">
			
				<input type="hidden" name="event" value="image" />
				<input type="hidden" name="step" value="image_insert" />
					
				<tr class="existing">
					<td class="title"><nobr>{$txt_existing_image}</nobr></td> 
					<td><select class="existing" name="filename" class="list" onchange="">
						<option value=""></option>
						{html_options options=$existing_files}
						</select></td> 
					<td><input type="submit" name="" value="{$txt_create}" class="smallerbox" /></td>
				</tr>
				
			</form>
	
			{/if}
			
			{if $view_mode_list}
			
				{$smarty.capture.searchform}
			
			{/if}
			
		</table>
		
		<hr/>
		
	</td>
</tr>
<tr>
	<td>
		
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		
		{if $view_mode_list}
			
			<form name="longform" method="post" action="index.php" onsubmit="return verify('Delete?')">
			
			<table width="100%" cellpadding="0" cellspacing="0" border="0" id="list" class="{$view_mode}">
			
			<tr>
				<th><nobr><a class="{$class1}" href="index.php?event=image&sort=date&dir={$linkdir}">{$txt_date}</a></nobr></th>
				<th><nobr>{$txt_size}</nobr></th>
				<th><nobr><a class="{$class2}" href="index.php?event=image&sort=name&dir={$linkdir}">{$txt_name}</a></nobr></th>
				<th><nobr><a class="{$class3}" href="index.php?event=image&sort=category&dir={$linkdir}">{$txt_category}</a></nobr></th>
				{if $show_copyright}<th class="copyright"><nobr><a class="{$class5}" href="index.php?event=image&sort=copyright&dir={$linkdir}">Copyright</a></nobr></th>{/if}
				{if $show_caption}<th><nobr><a class="{$class6}" href="index.php?event=image&sort=caption&dir={$linkdir}">Caption</a></nobr></th>{/if}
				{if $show_alt}<th><nobr><a class="{$class7}" href="index.php?event=image&sort=alt&dir={$linkdir}">Alt text</a></nobr></th>{/if}
				{if $show_keywords}<th><nobr><a class="{$class8}" href="index.php?event=image&sort=keywords&dir={$linkdir}">Keywords</a></nobr></th>{/if}
				{if $show_author}<th><nobr><a class="{$class4}" href="index.php?event=image&sort=author&dir={$linkdir}">{$txt_user}</a></nobr></th>{/if}
				
				<th colspan="2">&#160;</th>
				
			</tr>
			
			{$images}
		
		{/if}
		
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		
		{if $view_mode_grid}
			
			{$smarty.capture.searchform}
			
			<form name="longform" method="post" action="index.php" onsubmit="return verify('Delete?')">
			
			<table width="100%" cellpadding="0" cellspacing="0" border="0" id="list" class="{$view_mode}">
			
			<tr>
				<td colspan="2" class="grid">
					
					<hr/>
					
					<div class="pad">
						{$images} {$images}
					</div>
					
					<hr/>
					
				</td>
			</tr>
		
		{/if}
		
		<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
		
		<tr class="footer">
			
			<td colspan="99">
			
				<div class="left">
				
					<div class="view-mode">
						<span class="label">View mode:</span> 
						<a class="grid" title="Grid View" href="index.php?event=image&view=grid">Grid</a> | 
						<a class="list" title="List View" href="index.php?event=image&view=list">List</a>
					</div>
					
					<hr/>
					
					<div class="thumb-size thumb-size-{$thumb}">
						<span class="label">Thumb size:</span> 
						<a class="z" href="index.php?event=image&thumb=z" title="{$txt_smaller}">Sm</a> | 
						<a class="y" href="index.php?event=image&thumb=y" title="{$txt_smaller}">Md</a> | 
						<a class="x" href="index.php?event=image&thumb=x" title="{$txt_smaller}">Lg</a>
					</div>
				
				</div>
				
				<hr/>
				
				<div class="right">
				
					<div class="with-selected" colspan="{if $view_mode_grid}1{else}99{/if}">
						
						{if $can_edit}
						
							<nobr>
							
							<span class="select">
								Select: 
								<input type="button" value="All" name="selall" class="smallerboxsp" title="select all" onclick="selectall();" />
								<input type="button" value="None" name="selnone" class="smallerboxsp" title="select none" onclick="deselectall();" />
								<input type="button" value="Range" name="selrange" class="smallerboxsp" title="select range" onclick="selectrange();" />
							</span>
							
							<select name="edit_method" class="list" id="withselected" onchange="poweredit(this); return false;">
								<option value="" selected="selected">With selected:</option>
								<option value="">-----------------</option>
								<option value="changecategory">Change category</option>
								{if $show_change_author}<option value="changeauthor">Change author</option>{/if}
								{if $show_delete}<option value="delete">{$txt_delete}</option>{/if}
								
							</select>
							<input type="hidden" value="image" name="event" />
							<input type="hidden" value="image_multi_edit" name="step" />
							<input type="hidden" value="1" name="page" />
							<input type="hidden" value="id" name="sort" />
							<input type="hidden" value="desc" name="dir" />
							<input type="submit" value="Go" class="smallerbox" />
							<nobr>
						
						{/if}
					
					</div>
					
					<hr/>
					
					{if $view_mode_grid}
					
						<div class="sort-by">
							
							<form name="sort" action="index.php" method="post">
								
								Sort by
								
								<select name="sort" class="list" onchange="if (this.value) submit(this.form)">
									<option value="name" {if $sortby eq 'name'}selected="selected"{/if}>Name</option>
									<option value="category" {if $sortby eq 'category'}selected="selected"{/if}>Category</option>
									<option value="date" {if $sortby eq 'date'}selected="selected"{/if}>Date Added</option>
									<option value="lastmod" {if $sortby eq 'lastmod'}selected="selected"{/if}>Last Modified</option>
								<select>
								
								<input type="hidden" name="event" value="image" />
								<input type="hidden" name="step" value="image_list" />
								
								{if $linkdir eq 'desc'}<a class="dir" href="index.php?event=image&dir=desc" title="in descending order">ASC</a>{/if}
								{if $linkdir eq 'asc'}<a class="dir" href="index.php?event=image&dir=asc" title="in ascending order">DESC</a>{/if}
							
							</form>
							
						</div>
					
						<hr/>
					
					{/if}
					
				</div>
				
			</td>
		</tr>
		{if $view_mode_grid}</form>{/if}
		</table>
		{if $view_mode_list}</form>{/if}
	</td>
</tr>
<tr>
	<td class="prev-next" colspan="99">
		{if $images}<p>{$prevnext}</p>{/if}
	</td>
</tr>

<tr>
	<td class="view-per-page" colspan="99">
	
		<form action="index.php" method="post">

			<input type="hidden" name="event" value="image" />
			<input type="hidden" name="step" value="image_change_pageby" />
			<input type="hidden" name="win" value="{$window}"/>
			
			{$txt_view}:
			<select name="qty" class="list" onchange="submit(this.form)">
				{html_options options=$limits selected=$mylimit}
			</select>
			{$txt_per_page}
		
		</form>
	</td>
</tr>

</table>


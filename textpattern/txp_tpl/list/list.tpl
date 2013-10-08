<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<hr/>

<form action="index.php" data-sort="{$sortby}" data-sortdir="{$sortdir}" action="" method="post" id="longform" name="longform" onsubmit="return txp.verify('{$trash_cnt}')">

	<table class="view-{$view} thumb-{$thumb} avg-title-{$avg_title}" width="" cellpadding="2" cellspacing="0" border="1" id="list" align="center">
	
	<!-- PATH - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<tr class="path">
		
		<td colspan="20">
			{section name=i loop=$path}
				<a href="?event={$event}&win={$winid}&id={$path[i].ID}">{$path[i].Title}</a> &#8250; 
			{/section} &nbsp;
		</td>
	</tr>

	<!-- COLUMN HEADERS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<tr class="headers {$hide_headers} sort-{$sortby|lower}">
		
		{$column_headers}
		
		{section name=i loop=$custom_headers}<th><nobr>{$custom_headers[i]}</nobr></th>{/section}
		
		<th class="chbox"><span><input type="checkbox" id="select-all" title="Select All" class="article"/></span></th>
	
	</tr>
	
	<!-- LIST DATA - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	{$list_data}
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
	<tr class="footer">
	
		<td colspan="20" align="right" valign="top" class="select">
			
			<div class="pad">
			
			<div class="trash">
				<a class="fill-{if $trash_cnt gt 3}3{else}{$trash_cnt}{/if}" title="Trash ({$trash_cnt})" href="index.php?event={$event}&id={$trash_id}&view_trash=1">Trash</a>
			</div>
			
			<div class="view-mode view-{$view}">
				<span>View mode:</span> 
				<a class="tr" title="List" href="index.php?event={$event}&win={$winid}&view=tr">List</a> <span>|</span>
				<a class="div" title="Grid" href="index.php?event={$event}&win={$winid}&view=div">Grid</a>
			</div>
			
			<div class="thumb-size size-{$thumb} {if $is_view_grid}grid{/if}">
				
				<span>Size:</span> 
				
				{if $is_view_grid}
				<a class="xx" href="index.php?event={$event}&win={$winid}&thumb=xx">4</a>
				<a class="x" href="index.php?event={$event}&win={$winid}&thumb=x">3</a>
				{/if}
				
				<a class="y" href="index.php?event={$event}&win={$winid}&thumb=y">2</a>
				<a class="z" href="index.php?event={$event}&win={$winid}&thumb=z">1</a>
			</div>
			
			<div class="alt-select">
			
				<select id="column" name="column" class="list">
					
					<option class="label" value="none" data-key="">Columns:</option>
					<option class="line" value="">-------------</option>
					
					{$column_select}
					
					{if $column_custom_select}
						<option class="line" value="">-------------</option>
						{$column_custom_select}
					{/if}
					
				</select>	
			
			</div>
			
			<div class="alt-select">
			
				<select id="action" name="edit_method" class="list">
					
					<option class="label" value="none" data-key="">With selected:</option>
					<option class="line" value="">---------------------</option>
					
					<option value="edit" data-key="&#8984;E">Edit</option>
					
					{if $mode eq 'edit'}
						<option value="save" data-key="&#8984;S" selected="yes">Save</option>
						<option value="edit_cancel" data-key="ESC">Cancel Edit</option>
					{/if}
					
					<option value="cut" data-key="&#8984;X">Cut</option>
					<option value="copy" data-key="&#8984;C">Copy</option>
					
					{if $clipboard}
						<option value="paste" data-key="&#8984;V">Paste</option>
						{if $clipboard eq 'cut'}<option value="clear_clip" data-key="ESC">Cancel Cut</option>{/if}
						{if $clipboard eq 'copy'}<option value="clear_clip" data-key="ESC">Cancel Copy</option>{/if}
					{/if}
					
					<option value="duplicate" data-key="&#8984;D">Duplicate</option>
					<option value="new" data-key="&#8984;&#8629;">New</option>
					
					<option value="alias" data-key="&#8984;L">Alias</option>
					<option value="group" data-key="&#8984;G">Group</option>
					<option value="ungroup" data-key="&#8984;U">Ungroup</option>
					
					{if $sortby eq 'position'}
						<option id="position-move-up" value="move_up" data-key="&#8984;&#8593;">Move Up</option>
						<option id="position-move-down" value="move_down" data-key="&#8984;&#8595;">Move Down</option>
						{if $is_view_grid}
							<option id="position-move-left" value="move_left" data-key="&#8984;&#8592;">Move Left</option>
							<option id="position-move-right" value="move_right" data-key="&#8984;&#8594;">Move Right</option>
						{/if}
					{/if}
					
					<option value="open" data-key="&#8984;+">Open</option>
					
					{if $close}
						<option value="close" data-key="&#8984;&#8722;">Close</option>
					{/if}
					
					{* DISABLED
					<option value="export" data-key="">Export</option>
					{if !$edit and $has_export}<option value="import" data-key="">Import</option>{/if} 
					*}
					
					<option value="touch" data-key="&#8984;T">Touch</option>
					
					{if !$in_trash and !$is_trash}
						<option value="trash" data-key="&#8984;DEL">Move to Trash</option>
					{/if}
					
					{if $is_trash}
						<option value="untrash" data-key="">Remove from Trash</option>
					{/if}
					
					{if $event eq 'sites'}
						<option class="line" value="">---------------------</option>
						<option value="view_site" data-key="">View Site</option>
						<option value="archive_site" data-key="">Make Site Archive</option>
						<option value="update_db" data-key="">Update Database</option>
					{/if}
					
					<option class="line" value="">---------------------</option>
					<option class="show" value="changestatus" data-key="">Change status...</option>
					<option class="show" value="changecomments" data-key="">Change comments...</option>
					<option class="show" value="changeauthor" data-key="">Change author...</option>
					
					<option class="line" value="">---------------------</option>
					<option class="toggle" value="keep_view_settings">Keep View Settings</option>
					<option class="toggle" value="hide_headers">Hide Column Headers {if $hide_headers}&#10003;{/if}</option>
					<option class="toggle" value="hide_main">Hide Main Item {if $hide_main}&#10003;{/if}</option>
					<option class="toggle" value="flat_view">Flat View {if $flat_view}&#10003;{/if}</option>
					
				</select>
			
			</div>
			
			<input type="hidden" name="win" value="{$window}"/>
			<input type="hidden" name="editcol" value="{$editcol}"/>
			<input type="hidden" name="checked" value="{$checked}"/>
			<input type="hidden" name="event" value="{$event}" />
			<input type="hidden" name="step" value="multi_edit" />
			<input type="hidden" name="scroll" value="" />
			<input type="hidden" name="columns" value="1" />
			<input type="submit" name="" value="Go" id="go" class="smallerbox"/>
			
			</div>
			
		</td>
		
	</tr>
	</table>

</form>

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<ul class="context-menu" id="main-context-menu">

	<li id="cut"><a href="#">Cut <span>&#8984;X</span></a></li>
	<li id="copy" ><a href="#">Copy <span>&#8984;C</span></a></li>
	{if $clipboard}
		<li id="cut-paste"><a href="#"><b>Paste</b> <span>&#8984;V</span></a></li>
		{if $clipboard eq 'cut'}<li id="cut-cancel"><a href="#">Cancel Cut <span>ESC</span></a></li>{/if}
		{if $clipboard eq 'copy'}<li id="copy-cancel"><a href="#">Cancel Copy <span>ESC</span></a></li>{/if}
	{/if}
	<li id="duplicate"><a href="#">Duplicate <span>&#8984;D</span></a></li>
	<li id="edit"><a href="#">Edit <span>&#8984;E</span></a></li>
	<li id="new"><a href="#">New <span>&#8984;&#8629;</span></a></li>
	<li id="group"><a href="#">Group <span>&#8984;G</span></a></li>
	<li id="alias"><a href="#">Make Alias <span>&#8984;L</span></a></li>
	<li id="hoist"><a href="#">Hoist <span>&#8984;H</span></a></li>
	{if $event eq 'sites'}
		<li id="view_site"><a href="#">View Site <span>&#8984;W</span></a></li>
	{/if}	
	<li id="window"><a href="#">Open in New Window</a></li>
	<li id="edit_window"><a href="#">Edit in New Window</a></li>
	<li id="trash"><a href="#">Move to Trash <span>&#8984;DEL</span></a></li>
	
</ul>

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<ul class="context-menu" id="trash-context-menu">
	<li><a id="open" href="#">Open</a></li>
	{if $trash_cnt}<li><a id="empty" href="#">Empty Trash</a></li>{/if}
</ul>

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{include file='upload_progress.tpl'}

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
<!-- EVENT SPECIFIC ITEMS -->

<!-- EVENT_SPECIFIC_ITEMS -->
	
<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<div class="test"></div>
<div class="checked"></div>

<!-- ======================================================================================================================== -->

<div id="clickoff">
	<input size="1" type="text" style="opacity:0;font-size:1px;width:1px;height:1px;"/>
</div>

<div id="drop-frame"></div>
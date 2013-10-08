<!-- =============================================================================== -->

{if $pages}
<div style="height:27px">
	<p align="center">
		{if $page gt 1}<a href="?event=list&step=list&page={$page-1}&sort={$sort}&dir={$dir}" class="navlink">&#8249; Prev</a>{/if}
		<small>{$page}/{$pages}</small> 
		{if $page lt $pages}<a href="?event=list&step=list&page={$page+1}&sort={$sort}&dir={$dir}" class="navlink">Next &#8250;</a>{/if}
	</p>
</div>
{/if}

<!-- =============================================================================== -->

<table cellpadding="0" cellspacing="0" border="0" id="list" align="center">

<form name="search" action="index.php" method="post">

<tr>
	<td class="search input" colspan="2">
		Search
		<input type="text" name="keywords" value="{$keywords}" size="15" class="edit search" />
		<input type="hidden" name="event" value="list" />
		<input type="hidden" name="step" value="list" />
	</td>
	<td class="search">
		<select name="section" class="list search" onchange="submit(this.form)">
			<option value="all">*</option>
			{html_options options=$sections selected=$section}
		</select>
	</td>
	<td class="search">
		<select name="category1" class="list search" onchange="submit(this.form)">
			<option value="all">*</option>
			{html_options options=$category1s selected=$category1}
		</select>
	</td>
	<td class="search">
		<select name="category2" class="list search" onchange="submit(this.form)">
			<option value="all">*</option>
			{html_options options=$category2s selected=$category2}
		</select>
	</td>
	
	{if $category_max gte 3}
	<td class="search">
		<select name="category3" class="list search" onchange="submit(this.form)">
			<option value="all">*</option>
			{html_options options=$category3s selected=$category3}
		</select>
	</td>
	{/if}
	
	{if $category_max gte 4}
	<td class="search">
		<select name="category4" class="list search" onchange="submit(this.form)">
			<option value="all">*</option>
			{html_options options=$category4s selected=$category4}
		</select>
	</td>
	{/if}
	
	<!-- <td></td> -->
	
	{$custom_search}
	
	<td class="search">
		<select name="author" class="list search" onchange="submit(this.form)">
			<option value="all">*</option>
			{html_options options=$authors selected=$author}
		</select>
	</td>
	<td class="search">
		<select name="status" class="list search" onchange="submit(this.form)">
			<option value="all">*</option>
			{html_options options=$statuses selected=$status}
		</select>
	</td>
	<td class="search">
		<select name="position" class="list search position" onchange="submit(this.form)">
			<option value="all">*</option>
			<option value="1"{if $position eq '1'} selected="yes"{/if}>1</option>
		</select>
	</td>
	<td class="search">
		<input type="hidden" name="search_click" value="0" />
		<input type="submit" name="search" value="Go" class="smallerbox search" id="search-click" />
	</td>
</tr>

</form>

<!-- =============================================================================== -->

<form action="index.php" method="post" name="longform" onsubmit="return verify('Are you sure?')">

<tr class="sort-{$sort|lower}">
	{if $date eq 'Posted'}<th class="posted"><a href="index.php?sort=Posted&dir={$linkdir}&event=list" title="{$linkdir_title}">Posted</a></th>{/if}
	{if $date eq 'LastModMicro'}<th class="lastmodmicro"><a href="index.php?sort=LastModMicro&dir={$linkdir}&event=list" title="{$linkdir_title}">Modified</a></th>{/if}
	<th class="title">
		{if $thumb eq 'z'}<a href="index.php?event=list&thumb=y" title="Bigger thumbnails"><img src="/admin/txp_img/thumb_bigger.gif" width="9" height="9" border="0"/></a>{/if}
		{if $thumb eq 'y'}<a href="index.php?event=list&thumb=z" title="Smaller thumbnails"><img src="/admin/txp_img/thumb_smaller.gif" width="9" height="9" border="0"/></a>{/if}
		<a href="index.php?sort=Title&dir={$linkdir}&event=list">Title</a>
	</th>
	<th class="section"><a href="index.php?sort=Section&dir={$linkdir}&event=list">Section</a></th>
	{if $category_max gte 1}<th class="category1"><nobr><a href="index.php?sort=Category1&dir={$linkdir}&event=list">Category 1</a></nobr></th>{/if}
	{if $category_max gte 2}<th class="category2"><nobr><a href="index.php?sort=Category2&dir={$linkdir}&event=list">Category 2</a></nobr></th>{/if}
	{if $category_max gte 3}<th class="category3"><nobr><a href="index.php?sort=Category3&dir={$linkdir}&event=list">Category 3</a></nobr></th>{/if}
	{if $category_max gte 4}<th class="category4"><nobr><a href="index.php?sort=Category4&dir={$linkdir}&event=list">Category 4</a></nobr></th>{/if}

	<!-- <th class="file">File</th> -->

	{section name=i loop=$customs}
		<th class="{$customs[i].field}"><a href="index.php?sort={$customs[i].field}&dir={$linkdir}&event=list">{$customs[i].column}</a></th>
	{/section}
	{if $filecol}<th class="file">File</th>{/if}
	<th>Author</th>
	<th class="status"><a href="index.php?sort=Status&dir={$linkdir}&event=list">Status</a></th>
	<th class="position"><a href="index.php?sort=Position&dir={$linkdir}&event=list">Pos.</a></th>
	<th>&#160;</th>
</tr>

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

{$articles}

<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->

<tr>
	<td colspan="2" class="datetype" style="border:0px">
		{if $date eq 'Posted'}<a href="index.php?event=list&step=list_list&date=LastModMicro" title="Show modified date">Show modified date</a>{/if}
		{if $date eq 'LastModMicro'}<a href="index.php?event=list&step=list_list&date=Posted" title="Show posted date">Show posted date</a>{/if}
	</td>
	<!-- <td colspan="{math equation="x+y+z-2" x=$customs|@count y=7 z=$category_max}" class="select" style="text-align:right;border:0px"> -->
	<td colspan="20" class="select" style="text-align:right;border:0px">
	
		Select: 
		
		<input type="button" name="selall" value="All {$rows}" class="smallerboxsp" title="select all" onclick="selectall();" />
		<input type="button" name="selnone" value="None" class="smallerboxsp" title="select none" onclick="deselectall();" />
		<input type="button" name="selrange" value="Range" class="smallerboxsp" title="select range" onclick="selectrange();" />
		
		With selected: 
		
		<select name="method" class="list" onchange="">
			<option value=""></option>
			<option value="edit">Edit</option>
			{if !$edit}<option value="delete">Delete</option>{/if}
			{if $edit}<option value="save" selected="yes">Save</option>{/if}
			{if $edit}<option value="cancel">Cancel</option>{/if}
			<option value="open">Open</option>
			{if $close}<option value="close">Close</option>{/if}
			{if !$edit}<option value="move">Move</option>{/if}
			{if !$edit}<option value="move_out">Move Out</option>{/if}
			{if !$edit}<option value="duplicate">Duplicate</option>{/if}
			{if !$edit}<option value="mirror">Mirror</option>{/if}
			{if !$edit}<option value="touch">Touch</option>{/if}
		</select>
		
		<input type="hidden" name="checked" value="{$checked}"/>
		<input type="hidden" name="last_checked" value=""/>
		<input type="hidden" name="event" value="list" />
		<input type="hidden" name="step" value="list_multi_edit" />
		<input type="submit" name="" value="Go" class="smallerbox" />
	</td>
</tr>

</table>

</form>

<!-- =============================================================================== -->

<form action="index.php" method="post">
	
	<p align="center">
		
		View
		
		<select name="qty" class="list" onchange="submit(this.form)">
			{html_options options=$qty selected=$limit}
		</select>
		
		per page
		
		<input type="hidden" name="event" value="list" />
		<input type="hidden" name="step" value="list_change_pageby" />
	</p>

</form>

<!-- =============================================================================== -->

{if $show}
<form action="index.php" method="post">

	<p align="center">
		
		Show column for:
		
		{section name=i loop=$show}
			<input type="checkbox" name="{$show[i].field}" value="1" onclick="submit(this.form)" {$show[i].showcolumn|replace:'1':'checked="yes"'|replace:'0':''}/>{$show[i].title}
		{/section}
		
		<input type="hidden" name="event" value="list" />
		<input type="hidden" name="step" value="list_change_column" />
	</p>

</form>
{/if}

<!-- =============================================================================== -->

{if $notes}{$notes}{/if}

<!-- =============================================================================== -->



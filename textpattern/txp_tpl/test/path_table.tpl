<div class="test">
	
	<pre class="path">{$path}</pre>
	
	<div class="message">
	
		{if $error}
			<p class="error">Error: {$error}</p>
		{/if}
		{if $query}
			<pre class="query">{$query}</pre>
		{/if}
	
	</div>
	
	<table>
		<tr>
			<th class="total">
				{if $total}  
					{$total} {if $total eq 1}Result{else}Results{/if} 
				{else}
					No Results
				{/if}
			</th>
			<th class="name">Name</th> 
			<th class="class">Class</th> 
			<th class="id">ID</th>
			<th class="level">Level</th>
		</tr>
		
		<tr class="line"><td colspan="5"><div></div></td></tr>
		
		{section name=item loop=$rows}
			<tr class=""><td colspan="5"><div>{$rows[item]}</div></td></tr>
			<tr class="line"><td colspan="5"><div></div></td></tr>
		{/section}	
		
		{section name=item loop=$list}
		<tr class="level-{$list[item].Level} {$list[item].Sel} {$list[item].Context}">
			<td class="title"><span>&#8226;</span> {$list[item].Title}</td>
			<td class="name">{$list[item].Name}</td>
			<td class="class">
				{if $list[item].ClassName}{$list[item].ClassName}{else}<span>&mdash;</span>{/if}
			</td>
			<td class="id">{$list[item].ID}</td>
			<td class="level">{$list[item].Level}</td>
		</tr>
		<tr class="line"><td colspan="5"><div></div></td></tr>
		{/section}
		
	</table>
	
</div>
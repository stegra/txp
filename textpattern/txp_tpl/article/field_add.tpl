<!-- Add Custom Field - - - - - - - - - - - - - - - - - - - - - - - - - -->

<h3 class="plain lever"><a href="#add-custom-field" title="Add Custom Field">Add Custom Field</a></h3>

<div id="add-custom-field" class="toggle" style="display:none">
		
	<p class="field">
		<label for="custom-apply-id">Add Custom Field</label>
		{$field_select_pop}
	</p>
	
	<p class="apply">	
		
		<input type="hidden" name="apply_to_id" value="1"/> 
		
		<label for="custom-apply-id">
			To <b>this</b> 
			<!-- <span>{if $article_class}{$article_class}{else}item{/if}</span> --> 
			plus any other...
		</label><br/>
		
		{if $article_name}
			
			<input id="custom-apply-name" type="checkbox" name="apply_to_name" value="1" class="edit"/> 
			<label for="custom-apply-name"><b><span>{$article_name}</span></b></label><br/>
			
		{/if}
		
		{if $article_class}
		
			<input id="custom-apply-class" type="checkbox" name="apply_to_class" value="1" class="edit"/> 
			<label for="custom-apply-class">
				{if $article_name eq $article_class}class{/if}
				<b><span>{$article_class}</span></b>
			</label><br/>
		
		{/if}
		
		{section name=i loop=$article_categories}
			<input id="custom-apply-category" type="checkbox" name="apply_to_category[]" value="{$article_categories[i].Name}" class="edit"/> 
			<label for="custom-apply-category"><b><span>{$article_categories[i].Title}</span></b></label><br/>
		{/section}
		
		{if $article_sticky}
			
			<input id="custom-apply-sticky" type="checkbox" name="apply_to_sticky" value="1" class="edit"/> 
			<label for="custom-apply-sticky"><b><span>Sticky</span></b></label><br/>
		
		{/if}
		
		{if $article_parent}
			
			<input id="custom-apply-parent" type="checkbox" name="apply_to_parent" value="1" class="edit"/> 
			<label for="custom-apply-parent">in <b><span>{$article_parent}</span></b></label><br/>
			
		{/if}
		
	</p>
	
</div>
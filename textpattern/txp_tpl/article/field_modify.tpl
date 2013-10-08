<h3 class="plain lever"><a href="#add-custom-field" title="Add Custom Field">Add Custom Field</a></h3>

<div id="add-custom-field" class="toggle" style="display:none">
		
	<p class="field">
		<label for="custom-apply-id">Add Custom Field</label>
		{$field_select_pop}
	</p>
	
	<p class="apply">	
		
		{if $article_class}
		<input id="custom-apply-class" type="checkbox" name="custom_class" value="1" class="edit checkbox"/> <label for="custom-apply-class">To any {$article_class_title}</label><br/>
		{/if}
		
		{if $article_category1}
		<input id="custom-apply-category1" type="checkbox" name="custom_category[]" value="{$article_category1}" class="edit checkbox"/> <label for="custom-apply-category-1">To any {$article_category1_title}</label><br/>
		{/if}
		
		{if $article_category2}
		<input id="custom-apply-category2" type="checkbox" name="custom_category[]" value="{$article_category2}" class="edit checkbox"/> <label for="custom-apply-category-2">To any {$article_category2_title}</label><br/>
		{/if}
		
		<!-- <input id="custom-apply-all" type="checkbox" name="custom_all" value="1" class="edit checkbox"/> <label for="custom-apply-all">To any Article</label><br/> -->
		
	</p>

</div>
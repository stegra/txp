{capture name="tpl"}

<div class="checkbox total-{$total}">

	<input 
		class="checkbox custom_value_{$name}_{$id}" 
		type="checkbox" 
		value="{$value|escape:'html'}"
		name="custom_value_{$name}_{$id}"
		{if $checked}checked="checked"{/if}
		{* {if $is_alias}disabled="disabled"{/if} *}
	>{$label}</input>
	
</div>

{/capture}

{$smarty.capture.tpl|strip:" "}



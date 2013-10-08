{capture name="tpl"}

<div class="radio total-{$total}">

	<input 
		class="radio custom_value_{$name}_{$id}" 
		type="radio" 
		value="{$value|escape:'html'}"
		name="custom_value_{$name}_{$id}"
		{if $checked}checked="checked"{/if}
		{* {if $is_alias}disabled="disabled"{/if} *}
	>{$label}</input>
	
</div>

{/capture}

{$smarty.capture.tpl|strip:" "}



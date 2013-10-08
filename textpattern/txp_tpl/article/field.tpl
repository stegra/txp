{capture name="sublevel"}{strip}
	{if $level gt 1}sublevel{/if}
{/strip}{/capture}

{capture name="children"}{strip}
	{if $childcount}with-children{/if}
{/strip}{/capture}

{capture name="remove"}{strip}
	{if $childcount}Remove this field group{else}Remove this field{/if}
{/strip}{/capture}

{capture name="disabled"}{strip}
	{if $is_alias}disabled="disabled"{/if}
{/strip}{/capture}

{capture name="pophelp"}{strip}
	{if $help}
	<a title="Help" target="_blank" href="/index.php?pophelp=custom-field&#38;id={$field}&#38;language=en-gb" onclick="popWin(this.href); return false;" class="pophelp">?</a>
	{/if}
{/strip}{/capture}


<!-- {if $input}{$input}: {/if}{$title} - - - - - - - - - - - - -->

<div class="level-{$level} {$smarty.capture.sublevel}" id="field-{$group}{$field}{$instance}">

	<fieldset class="level-{$level} {$smarty.capture.children}">
	
		<legend class="level-{$level} {$smarty.capture.children}">
			<a href="#modify">{$title}</a> {$smarty.capture.pophelp} 
			<a class="remove" data-id="{$group}-{$field}-{$instance}" title="{$smarty.capture.remove}" href="#">x</a>
		</legend>
		
		{if $input eq 'textfield'}
			<input id="custom-{$name}-{$id}" type="text" name="custom_value_{$name}_{$id}" value="{$value|escape:'html'}" class="edit" {$smarty.capture.disabled}/>
		{/if}
		
		{if $input eq 'textarea'}
			<textarea id="custom-{$name}-{$id}" name="custom_value_{$name}_{$id}" class="edit" {$smarty.capture.disabled}>{$value}</textarea>
		{/if}
		
		{if $input eq 'select' or $input eq 'selectgroup'}
			<select id="custom-{$name}-{$id}" name="custom_value_{$name}_{$id}" {$smarty.capture.disabled}>
				{html_options options=$options selected=$value}
			</select>
		{/if}
		
		{if $input eq 'checkbox'}
			<div class="checkbox">
				{$options}
				<input id="custom_value_{$name}_{$id}" type="hidden" name="custom_value_{$name}_{$id}" value="[]"/>
			</div>
		{/if}
		
		{if $input eq 'radio'}
			<div class="radio">{$options}</div>
		{/if}
		
		{if $input eq 'date'}
			<div class="date">{$value}</div>
		{/if}
		
		{if $input eq 'time'}
			<div class="time">{$value}</div>
		{/if}
		
		{if $input eq 'color'}
			<div class="color">
				<input id="custom-{$name}-{$id}" type="text" name="custom_value_{$name}_{$id}" value="{$value|escape:'html'}" class="edit" {$smarty.capture.disabled}/>
			</div>
		{/if}
	
		{ CHILDREN }
		
	</fieldset>

</div>

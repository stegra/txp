{assign var="show_options" value='hidden'}
{assign var="show_default" value=''}

{if $input eq 'select'}{assign var="show_options" value=''}{/if}
{if $input eq 'radio'}{assign var="show_options" value=''}{/if}
{if $input eq 'checkbox'}{assign var="show_options" value=''}{/if}

{if $input eq 'date'}{assign var="show_default" value='hidden'}{/if}
{if $input eq 'time'}{assign var="show_default" value='hidden'}{/if}
{if $input eq 'none'}{assign var="show_default" value='hidden'}{/if}

<tr class="field-header">
	<td>
		<table>
			<td class="title level-{$level}">
				<div><a href="#" rel="field-{$name}">{$title}</a> <small>({$count})</small></div>
			</td>
			<td class="info">
				<div>
					{if $options}
						<i>{$options|replace:',':', '}</i>
					{else}
						{$type}
					{/if}
				</div>
			</td>
			<td class="delete">{$delete_link}</td>
		</table>
	</td>
</tr>
<tr class="settings" id="field-{$name}" style="display:{$show_settings}">
	<td>
		
		<form method="post" action="index.php">
			<table>
			<tr id="name">
				<td class="noline label">Name:</td>
				<td class="noline"><input type="text" value="{$name}" name="name" size="20" class="edit"/></td>
			</tr>
			<tr id="title">
				<td class="noline label">Title:</td>
				<td class="noline"><input type="text" value="{$title}" name="title" size="20" class="edit"/></td>
			</tr>
			<tr id="input">
				<td class="noline label">Input method:</td>
				<td class="noline">{$input_pop} {$input_help}</td>
			</tr>
			<tr id="options" class="{$show_options}">
				<td class="noline label">Input value options:</td>
				<td class="noline">
					<input type="text" value="{$options}" name="options" size="20" class="edit"/> {$options_help}
				</td>
			</tr>
			<tr id="default" class="{$show_default}">
				<td class="noline label">Default value if any:</td>
				<td class="noline">
					<input type="text" value="{$default}" name="default" size="20" class="edit"/> {$default_help}
				</td>
			</tr>
			<tr id="parent">
				<td class="noline label">Parent:</td>
				<td class="noline">
					{$parent_pop} {$parent_help}
				</td>
			</tr>
			<tr id="help">
				<td class="noline label">Help text:</td>
				<td class="noline"><textarea name="help" class="edit">{$help}</textarea> {$parent_help}</td>
			</tr>
			<tr>
				<td class="noline"></td>
				<td class="noline">
					<input type="submit" value="{$txt_save_button}" class="smallerbox" />
					<input type="hidden" value="prefs" name="event" />
					<input type="hidden" value="custom_fields_save" name="step" />
					<input type="hidden" value="{$name}" name="old_name" />
					<input type="hidden" value="{$id}" name="id" />
				</td>
			</tr>
			</table>
		</form>
	</td>
</tr>
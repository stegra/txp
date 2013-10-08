<div id="note-{$note_id}_{$note_type}" class="note {if $note_status eq 'open'}open{/if} {$note_minmax}" data-type="{$note_type}" data-status="{$note_status}" data-minmax="{$note_minmax}" style="{$note_position}">
	<div class="header">
		<div class="title"><h5>{$note_title}</h5></div>
		<div class="buttons">
			<a class="close" title="Close" href="#max">x</a>
			<a class="min" title="Minimize" href="#min">&minus;</a>
			<a class="max" title="Maximize" href="#max">+</a>
		</div>
		<div class="clear"></div>
	</div>
	<div class="body" style="{$note_size}">
		<div class="content">
			<div class="read">{$note_html}</div>
			<textarea>{$note_text}</textarea>
		</div>
	</div>
</div>
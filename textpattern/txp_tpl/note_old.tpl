{capture name=note_size}{strip}
	{if $note_length lt 201}size-1{/if}
	{if $note_length gt 200 and $note_length lt 301}size-2{/if}
	{if $note_length gt 300 and $note_length lt 401}size-3{/if}
	{if $note_length gt 400 and $note_length lt 501}size-4{/if}
	{if $note_length gt 500}size-5{/if}
{/strip}{/capture}

<div class="notepad notepad-{$smarty.capture.note_size} notepad-status-{$note_status}" id="note-{$note_id}">

	<div class="min" id="note-{$note_id}-min">
		<div class="header"><h3>{$note_title}</h3><span class="minmax">[<a href="#" onclick="note('max','{$note_id}');return false;" title="Maximize">+</a>]</span> <span class="openclose">[<a href="#" class="openclose" onclick="note('close','{$note_id}');return false;" title="Close">x</a>]</span></div>
	</div>
	
	<div class="max" id="note-{$note_id}-max">
		<div class="header"><h3>{$note_title}</h3><span class="minmax">[<a href="#" onclick="note('min','{$note_id}');return false;" title="Minimize">&minus;</a>]</span> <span class="openclose">[<a href="#" onclick="note('close','{$note_id}');return false;" title="Close">x</a>]</span></div>
		<div class="content">{$note_html}</div>
	</div>
	
</div>
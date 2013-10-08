<div class="media-player">
	<audio class="unplayed" id="{$fileid}" controls="controls">
		<source src="{$filesrc}" type="audio/mpeg">
		{if $oggfilesrc}
			<source src="{$oggfilesrc}" type="audio/ogg">
		{/if}
	</audio>
</div>
  
{* 
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" class="play" title="Play" type="application/x-shockwave-flash" width="17" height="17" data="/textpattern/lib/player/xspf_player_button/musicplayer1.swf?song_url={$filesrc|replace:' ':'%20'}">
	<embed src="/textpattern/lib/player/xspf_player_button/musicplayer1.swf?song_url={$filesrc|replace:' ':'%20'}" width="17" height="17"/></object>	
*}

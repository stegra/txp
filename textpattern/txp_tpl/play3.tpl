{assign var="width"  value=$width|default:150}
{assign var="height" value=$height|default:110}

{if $user_agent eq 'safari'}

	<div class="play-video" style="width:{$width}px;height:{$height}px;background-image:url('{$poster}')">
		<div class="start">&nbsp;</div>
		<video id="{$fileid}" class="unplayed" src="{$filesrc}" autobuffer="false" controls="controls" width="{$width}" height="{$height}"></video>
	</div>
	
{else}

	<div>
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" type="application/x-shockwave-flash" data="/textpattern/lib/player/mediaplayer/player.swf" width="$width" height="$height">
		<param name="movie" value="/textpattern/lib/player/mediaplayer/player.swf" />
		<param name="allowfullscreen" value="true" />
		<param name="allowscriptaccess" value="always" />
		<param name="flashvars" value="file={$filesrc}" />
		<embed src="/textpattern/lib/player/mediaplayer/player.swf" width="{$width}" height="{$height}" allowscriptaccess="always" allowfullscreen="true" flashvars="file={$filesrc}"/>
	</object>
	</div>

{/if}
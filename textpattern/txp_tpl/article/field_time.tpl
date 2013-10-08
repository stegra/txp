{capture name="tpl"}

<input id="custom-{$name}-{$id}" type="text" name="custom_value_{$name}_{$id}" value="{$value}"/>

<script type="text/template">

	<select class="list datetime hour">
		<option value=""></option>	
		<option value="1">1</option>
		<option value="2">2</option>
		<option value="3">3</option>
		<option value="4">4</option>
		<option value="5">5</option>
		<option value="6">6</option>
		<option value="7">7</option>
		<option value="8">8</option>
		<option value="9">9</option>
		<option value="10">10</option>
		<option value="11">11</option>
		<option value="12">12</option>
	</select>
	
	<select class="list datetime min">
		<option value=""></option>	
		<option value="00">00</option>
		<option value="05">05</option>
		<option value="10">10</option>
		<option value="15">15</option>
		<option value="20">20</option>
		<option value="25">25</option>
		<option value="30">30</option>
		<option value="35">35</option>
		<option value="40">40</option>
		<option value="45">45</option>
		<option value="50">50</option>
		<option value="55">55</option>
	</select>
	
	<select class="list datetime ampm">
		<option value="am">am</option>
		<option value="pm">pm</option>
	</select>
	
</script>

{/capture}

{$smarty.capture.tpl|strip:" "}



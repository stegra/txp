{capture name="tpl"}

<input id="custom-{$name}-{$id}" type="text" name="custom_value_{$name}_{$id}" value="{$value}"/>

<script type="text/template">

	<select class="list datetime month">
		<option value=""></option>	
		<option value="01">Jan</option>
		<option value="02">Feb</option>
		<option value="03">Mar</option>
		<option value="04">Apr</option>
		<option value="05">May</option>
		<option value="06">Jun</option>
		<option value="07">Jul</option>
		<option value="08">Aug</option>
		<option value="09">Sep</option>
		<option value="10">Oct</option>
		<option value="11">Nov</option>
		<option value="12">Dec</option>
	</select>
	
	<select class="list datetime day">
		<option value=""></option>	
		{html_options options=$days selected=$day}
	</select>
	
	<select class="list datetime year">
		<option value=""></option>	
		{html_options options=$years selected=$year}
	</select>
	
</script>

{/capture}

{$smarty.capture.tpl|strip:" "}



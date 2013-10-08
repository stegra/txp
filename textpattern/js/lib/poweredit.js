// <select id="action" name="edit_method" class="list" onchange="poweredit(this); return false;">

function poweredit(elm)
{
	var something = elm.options[elm.selectedIndex].value;
	
	// Add another chunk of HTML
	var pjs = document.getElementById('js');

	if (pjs == null)
	{
		// var br = document.createElement('br');
		// elm.parentNode.appendChild(br);

		pjs = document.createElement('p');
		pjs.setAttribute('id','js');
		elm.parentNode.appendChild(pjs);
	}

	if (pjs.style.display == 'none' || pjs.style.display == '')
	{
		pjs.style.display = 'block';
	}

	if (something != '')
	{
		switch (something)
		{
		
		{/literal}{$script}{literal}
		
		default: pjs.style.display = 'none'; break;
		}
	}

	return false;
}

// -----------------------------------------------------------------------------

addEvent(window, 'load', cleanSelects);
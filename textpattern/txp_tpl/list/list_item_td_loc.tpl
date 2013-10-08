<nobr><img style="float:left" title="{$country}" src="/admin/theme/classic/txp_img/flags_12/{$countrycode|lower}.png" width="16" height="12"/>
&nbsp;
{if $city}{$city}{/if}{if $region and ($country eq 'US' or $country eq 'CA')}{if $city}, {/if}{$region}{/if}</nobr>
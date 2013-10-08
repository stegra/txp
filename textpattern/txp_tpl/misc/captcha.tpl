<img src="/captcha.png" id="captcha" />

<div class="label">Please enter this word:</div>
<input type="text" name="captcha" id="captcha-form" autocomplete="off" /><br/>

<a href="#" onclick="document.getElementById('captcha').src='/captcha.png?'+Math.random(); document.getElementById('captcha-form').focus();return false;" id="change-image">Change word</a>

<span class="line"></span>
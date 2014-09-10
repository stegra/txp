<?php
	
	function captcha_validate($captcha) {
		
		if (array_key_exists('captcha',$_SESSION)) {
		
			if ($_SESSION['captcha'] == $captcha) {
		
				echo "OK"; return;
			}
		}
	
		echo "ERROR";
	}
	
?>

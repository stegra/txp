<?php
	
	// not being used

	class Session {
		
		function set($path,$value='') {
			
			array_val($_SESSION,$path,$value);
		}
		
		function get($path,$default='') {
			
			$session = $_SESSION;
			$value   = '';
			
			$path = explode('/',$path);
			
			while ($session and $path) {
				
				$key = trim(array_shift($path));
				
				$value = $session = (isset($session[$key])) 
					? $session[$key] 
					: array();
			}
			
			return ($value) ? $value : $default;
		}
	}
	
?>
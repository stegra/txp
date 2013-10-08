<?php

	class Stack {
		
		var $stack = array();
		var $top   = -1;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function Stack($item='') { 
			
			if ($item) {
				$this->push($item);
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function push($item,$value='') {
			
			if (!strlen($value)) {
			
				$this->stack[] = $item;
			
			} else {
				
				$this->stack[] = array($item => $value);
			}
			
			$this->top++;
			
			return $item;
		}
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function pop() {
		
			$item = array_pop($this->stack);
			
			$this->top--;
			
			return $item;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function get($name) {
			
			if (isset($this->stack[$this->top][$name])) {
			
				return $this->stack[$this->top][$name];
			}
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function set($name,$value) {
			
			$this->stack[$this->top][$name] = $value;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function top() {
			
			if ($this->top < 0) return array();
			
			return $this->stack[$this->top];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		function show() {
			
			return array_to_string($this->stack);
		}
	}

?>
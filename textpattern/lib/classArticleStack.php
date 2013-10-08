<?php

	class ArticleStack {
	
		var $stack = array();
		var $names = array();
		var $top   = -1;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function ArticleStack() { }
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function push($article,$name='') {
		
			$this->stack[] = $article;
			$this->names[] = $name;
			
			$this->top++;
			
			return $article;
		}
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function pop() {
		
			$article = array_pop($this->stack);
			array_pop($this->names);
			
			$this->top--;
			
			return $article;
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		function get($name,$context='') {
			
			$level = $this->top;
			
			if ($level < 0) return '';
			
			if ($context) {
				
				if (is_numeric($context)) {
			
					$level = ($context - 1 <= $this->top) ? $context - 1 : $this->top;
				
				} elseif (preg_match('/^\.[\.\/]+$/',$context)) {
				
					$down = 0;
				
					foreach (explode('/',$context) as $item)
						if ($item == '..') $down++;
				
					$level = ($this->top - $down >= 0) ? $this->top - $down : 0;
				
				} elseif (in_array($context, $this->names)) {
			
					$names = array_flip($this->names);
					$level = $names[$context];
				}
			}
			
			// - - - - - - - - - - - - - - - - - - - - - - - - -
			
			$out  = $this->stack[$level];
			
			if ($name and $path = explode('/',trim($name))) {
			
				foreach ($path as $key => $name) {
					
					$out = (is_array($out) and isset($out[$name])) 
						? $out[$name] 
						: null;	
				}
			}
			
			return $out;
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
	
		function bottom() {
			
			return $this->stack[0];
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		function show() {
			
			$out = array();
			
			if ($this->top < 0) return;
			
			foreach($this->stack as $article) {
				$out[] = $article['thisid'];
			}
			
			return join(',',$out).n;
		}
	}

?>
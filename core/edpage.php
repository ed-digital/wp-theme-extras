<?php
	
	class EDPage {
		
		public static $instance = null;
		
		public $template = "";
		public $base = "";
		
		public $content = array();
		
		public $isXHR = false;
		
		public function __construct($main) {
			
			// Save the instance
			self::$instance = $this;
			
			$this->template = $main;
			$this->base = basename($this->template, '.php');
			
			if($this->base === 'index') {
				$this->base = false;
			}
			
			if(isset($_GET['xhr-page'])) {
				unset($_GET['xhr-page']);
				$this->isXHR = true;
			}
			
		}
		
		public static function get() {
			if(self::$instance) {
				return self::$instance;
			}
		}
		
		public function __get($prop) {
			return @$this->$prop;
		}
		
		public function executeMain($name, $template) {
			
			global $post;
			
 			ob_start();
 			
 			$this->_endCapture = false;
			
			include($template);
			
			if($this->_endCapture == false) {
	 			$this->content[$name] = ob_get_contents();
	 			ob_end_clean();
	 		}
			
		}
		
		// Stops capture from executeMain.
		public function end() {
			ob_end_flush();
			$this->_endCapture = true;
		}
		
		public function showContent($name = "main") {
			
			if(isset($this->content[$name])) {
				echo $this->content[$name];
			}
			
		}
		
		public function executeBase() {
			
			global $post;
			
			$str = "base";
			
			$templates = [$str.".php"];
			
			if($this->base) {
				array_unshift($templates, sprintf($str . '-%s.php', $this->base));
			}
			if($post && $post->post_type) {
				array_unshift($templates, sprintf($str . '-%s.php', $post->post_type));
			}
			if($post && $post->post_name) {
				array_unshift($templates, sprintf($str . '-%s.php', $post->post_name));
			}
			
			$template = locate_template($templates);
			
			include($template);
			
		}
		
	}
	
	add_filter('template_include', function($main) {
		
		if(ED()->config['useBase'] == false) return $main;
		
		// Check for other filters returning null
		if(!is_string($main)) {
			return $main;
		}
		
		$page = new EDPage($main);
		
		return ED()->edPath."/wrapper/bootstrap.php";

	});
	
?>
<?php
	
	class EDPage {
		
		public static $instance = null;
		
		public $template = "";
		public $base = "";
		
		public $originalPost = null;
		
		public $content = array();
		
		public $isXHR = false;
		
		public function __construct($main) {
			
			global $post;
			$this->originalPost = $post;
			
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
			
			add_action('wp_footer', [$this, '_printPageStateEl']);
			
		}
		
		public static function get() {
			if(self::$instance) {
				return self::$instance;
			}
		}
		
		public function __get($prop) {
			return @$this->$prop;
		}
		
		public function executeMain($__name, $__template) {
			
      global $post;
      
      $customTemplate = ED()->getTemplate(get_page_template_slug($this->originalPost));

      if ($customTemplate) {
        $this->content[$__name] = $customTemplate['part']->call();
      } else {
        ob_start();
        
        $this->_endCapture = false;
       
       if ($__template) {
         include($__template);
       }
       
       if($this->_endCapture == false) {
          $this->content[$__name] = ob_get_contents();
          ob_end_clean();
        }
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
		
		// Prints out an empty div in the footer, which contains page state info
		public function _printPageStateEl() {
			?>
			<pagestate data-state="<?=htmlspecialchars(json_encode($this->getState()));?>"></pagestate>
			<?
		}
		
		public function getState() {
			
		//	dump($this);
			if (get_query_var('static') === 'customRoute') {
				return [
					"template" => preg_replace("/.php$/", "", str_replace(ED()->themePath."/", "", $this->template)),
					"type" => "page",
					"slug" => preg_replace("/(^\/|\/$)/", "", $_SERVER['REQUEST_URI'])
				];
			} else {
				return [
					"template" => $this->base,
					"type" => $this->originalPost ? $this->originalPost->post_type : "",
					"slug" => $this->originalPost ? $this->originalPost->post_name : ""
				];
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
	
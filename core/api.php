<?
	
	class ED_API {
		
		private static $forbiddenMethodNames = null;
		
		protected $methods = array();
		protected $controllers = array();
		
		final function __construct() {
			
			// Build an index of forbidden method names, to prevent sneaky people from calling the built-in methods
			if(!self::$forbiddenMethodNames) {
				$methods = get_class_methods("ED_API");
				foreach($methods as $method) {
					self::$forbiddenMethodNames[strtolower($method)] = true;
				}
			}
			
			// Call the generic initter
			$this->init();
			
		}
		
		public function init() {
			// Override me
		}
		
		public function addMethod($args, $callback) {
			$this->methods[$args] = $callback;
		}
		
		public function addController($name, $controller) {
			if(!preg_match("/^[A-Z\.\_\-]+$/i", $name)) {
				throw new Exception("Invalid characters in API Controller name '".$name."'. Use 'A-Z0-9._-' only.");
			}
			if(!is_a($controller, "ED_API")) {
				throw new Exception("A child API controller must be an instance of 'ED_API'.");
			}
			$this->controllers[$name] = $controller;
		}
		
		public function callMethod($name, $arguments = array(), $isAjax = false) {
			
			// Locate the callable
			$method = $this->locateMethod($name);
			
			if($method) {
				
				// Run the method
				return call_user_func($method, $arguments, $isAjax);
				
			} else {
				
				// Not found
				throw new Exception("No API method named '".$name."' exists.");
				
			}
			
		}
		
		private function locateMethod($name) {
			
			$name = (string)$name;
			
			if(!$name) return;
			
			// Check for a local method declaration first
			if(isset($this->methods[$name])) {
				return $this->methods[$name];
			}
			
			// Check if a method with that name exists on this API instance, and that it's not a forbidden method
			if(method_exists($this, $name) && !isset(self::$forbiddenMethodNames[strtolower($name)])) {
				return array($this, $name);
			}
			
			// Break off the first chunk
			$methodParts = explode("/", $name, 2);
			if(count($methodParts) !== 2) {
				return;
			}
			
			// Is there a child controller with the correct name?
			if(isset($this->controllers[$methodParts[0]])) {
				$child = $this->controllers[$methodParts[0]];
				return $child->locateMethod($methodParts[1]);
			}
			
		}
		
		public function __get($name) {
			
			if(isset($this->$name)) {
				return $this->$name;
			} else if(isset($this->controllers[$name])) {
				return $this->controllers[$name];
			}
			
		}
		
	}
	
?>
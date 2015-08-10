<?
	
	final class EDJSONAPIListener {
		
		private function __construct() {}
		
		static function handle() {
		
			$url = $_SERVER['REQUEST_URI'];
			
			if(strpos($url, "/json-api") === 0) {
				
				// Ensure POST is being used
				if($_SERVER['REQUEST_METHOD'] !== "POST") {
					self::serveError("InvalidHTTPVerb", "JSON-API calls must be use POST");
				}
				
				// Extract method name from the URL
				preg_match("/\/json-api\/([A-Z0-9\/\-\.]+)(\?|$)/i", $url, $match);
				
				if(!$match) {
					self::serveError("InvalidAPIURL", "Invalid URL format");
				}
				
				// Clean the method name
				$method = trim($match[1], "/");
				$httpBody = file_get_contents('php://input');
				$args = json_decode($httpBody, false);
				
				// Run the method
				try {
					$result = ED()->API->callMethod($method, $args, true);
					self::serveResult($result);
				} catch(Exception $e) {
					self::serveError($e->getCode(), $e->getMessage(), @$e->info);
				}
				
				die();
				
			}
		
		}
		
		static function serveError($code, $message, $info = null) {
			
			echo json_encode(array(
				"result" => null,
				"error" => array(
					"code" => $code,
					"message" => $message,
					"info" => $info
				)
			));
			
			die();
			
		}
		
		static function serveResult($result) {
			
			echo json_encode(array(
				"result" => $result,
				"error" => null
			));
			
			die();
			
		}
		
	}
	
	// Hook into parse_request
	add_action('parse_request', array("EDJSONAPIListener", "handle"));
	
?>
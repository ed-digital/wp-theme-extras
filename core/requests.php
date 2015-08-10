<?
	
/*
	add_filter('query_vars', function($req) {
		echo "query_vars:<br><pre>";
		print_r(func_get_args());
		echo "</pre>";
		return $req;
	});
	
	add_filter('request', function($req) {
		echo "request:<br><pre>";
		print_r(func_get_args());
		echo "</pre>";
		return $req;
	});
	
	add_filter('do_parse_request', function($req) {
		echo "do_parse_request:<br><pre>";
		print_r(func_get_args());
		echo "</pre>";
		return $req;
	});
*/

/*
	add_action('parse_request', function($req) {
		
		$url = $_SERVER['REQUEST_URI'];
		
		if(strpos($url, "json") !== false) {
			
 			die();
 			
		}
		
	});
*/
	
?>
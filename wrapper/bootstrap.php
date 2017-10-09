<?php
	/*
		Don't touch this file
	*/
	
	$pageObject = EDPage::get();
	
	if (get_query_var("static") == "customRoute" && is_string(get_query_var("view"))) {
		$route = ED()->routes[(int)get_query_var("view")];
		$page = EDPage::get();
		
		add_filter("wp_title", function($title) use($route) {
			if ($route[2]) {
				return " | " . $route[2];
			} else {
				return " | " . $title;
			}
		}, 2, 2);
		
		if ($route[0] == 'template') {
			$pageObject->template = $route[1];
		} else if ($route[0] == 'function') {
			$callback = $route[1];
			$callback();
			die();
		}
	}
	$pageObject->executeMain("main", $pageObject->template);
	$pageObject->executeBase();
?>
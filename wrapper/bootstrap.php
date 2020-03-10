<?php

	$pageObject = EDPage::get();
	
	if (get_query_var("static") == "customRoute" && is_string(get_query_var("view"))) {
		status_header(200);
		$route = ED()->routes[(int)get_query_var("view")];
		$page = EDPage::get();
		
		add_filter("wp_title", function($title, $sep) use($route) {
			if ($route[2]) {
				return " ".$sep." ".$route[2];
			} else {
				return " ".$sep." ".$sep . $title;
			}
		}, 2, 2);
		
		$pageObject->args = $route[3];
		
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
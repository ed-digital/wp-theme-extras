<?php

	add_filter("redirect_canonical", function($url, $requested) {
		if (get_query_var('static') === 'customRoute') {
			return false;
		} else {
			return $url;
		}
	}, 2, 2);
	
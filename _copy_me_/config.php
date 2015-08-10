<?
	 
	ED()->setConfig([
		"useBase" => true,
		"includeSiteKit" => true
	]);
	
	/* Install Instagram and Twitter modules */
/*
	ED()->useModule("instagram");
	ED()->useModule("twitter", array(
		"tokens" => array(
			'oauth_access_token' => "2156908838-s7BDJ4aMTPwQdvL5Z5h4XSds7uFqAwAfQtfF14E",
			'oauth_access_token_secret' => "IiP04fOWXgpQfEqGTV8BgbdqgetXdFikkNsFa50p0m6NY",
			'consumer_key' => "Sa7uLHCDJYQtCwhKDoTOIMKI8",
			'consumer_secret' => "BjnbCOAEsrrwgxvWi6S0wm2zHY5mUA1EJjWyBREzVC9cZJCIsz"
		)
	));
*/
	
	ED()->registerMenu("main", "Main Header Menu");
	
	// Large sizes
	add_image_size("full-size", 1400, 1000, false);
	add_image_size("slider", 1220, 800, true);
	
	// Post thumbnail
	add_image_size("post-thumb", 590, 400, true);
	
?>
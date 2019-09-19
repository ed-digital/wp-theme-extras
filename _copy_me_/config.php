<?
	
	ED()->setConfig([
    // Enable the use of base.php
    "useBase" => true,
    // Prevent WP from producing absolute URLs — great for staging sites, although not strictly necessary. Can cause issues with some plugins.
    "useRelativeURLs" => true,
    // Load components from the 'components' folder
    "loadComponents" => true
	]);
	
	ED()->registerMenu("main", "Main Header Menu");
	
	// Image Sizes
  add_image_size("full-size", 1400, 1000, false);
  add_image_size("preview", 300, 300, false);
	
?>
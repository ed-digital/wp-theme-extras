<?
	
	register_post_type('event', array(
		'labels' => array(
			'name' => __( 'Events' ),
			'singular_name' => __( 'Event' )
		),
		'public' => true,
		'has_archive' => true
	));
	
	ED()->definePostColumns("event", array(
		"slug" => array(
			"order" => 1,
			"label" => "Slug",
			"render" => function($ID) {
				echo get_post($ID)->post_name;
			}
		),
		"meow" => array(
			"order" => 10,
			"label" => "Meow",
			"render" => function($post) {
				echo "meow is something";
			}
		),
		"date" => null
	));
	
?>
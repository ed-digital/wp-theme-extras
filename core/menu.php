<?
  add_filter('nav_menu_css_class', function($classes, $item) {
    
    // Remove existing 'current-menu-item'
    $classes = array_filter($classes, function($val) {
      return $val !== "current-menu-item";
    });
    
    // Add new
    if (preg_match("/^\/[a-z0-9]+/", $item->url) && strpos($_SERVER['REQUEST_URI'], $item->url) === 0) {
      $classes[] = "current-menu-item";
    }
    
    // If the item contains custom HTML, add the html-item class
    // dump($item);
    if (preg_match("/<\/[a-z]+>/", $item->title)) {
      $classes[] = "html-item";
    }
    
    return $classes;
  }, 1, 2);
?>
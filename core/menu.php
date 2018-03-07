<?
  
  add_filter('wp_nav_menu_objects', function($items) {
    
    // Remove the default current-menu-item
    foreach ($items as $item) {
      $classes = array_filter($item->classes, function($val) {
        return $val !== "current-menu-item";
      });
    }
    
    $queue = [0];
    
    while (count($queue)) {
      // Get the next queue item
      $parent = array_shift($queue);
      
      // Figure out the best match for items in this menu branch
      $bestMatch = null;
      $bestMatchLength = 0;
      $url = $_SERVER['REQUEST_URI'];
      foreach ($items as $item) {
        if ($item->menu_item_parent == $parent) {
          if (strpos($url, $item->url) === 0) {
            $len = strlen($item->url);
            if ($len > $bestMatchLength) {
              $bestMatchLength = $len;
              $bestMatch = $item;
            }
          }
        }
      }
      
      // If we have a match, mark that item as active, and add it's children to the queue
      if ($bestMatch) {
        $queue[] = $bestMatch->ID;
        $bestMatch->classes[] = 'current-menu-item';
      }
    }
    
    return $items;
  });
  
?>
<?
/* 
Standard wordpress helpers, mostly about nicely formatting gross wordpress datastructures

WPHelpers::formatted_menu returns a wp menu as an array of items with children
eg. WPHelpers::formatted_menu('main_menu');
  -> 
  Link = [
    'ID' => the related post id,
    'active' => if the post id matches the currently viewed post,
    'link_id' => the link object's id,
    'children' => Link[],
    ...rest of the normal link object properties
  ]
*/
if (!class_exists('WPHelpers')) {
  class WPHelpers {
  
    static function formatted_menu ($slug = 'Main menu') {
      $items = [];
  
      global $post;

      $links = wp_get_nav_menu_items(self::getMenuSlugAtLocation($slug));
      if (!$links) {
        $links = wp_get_nav_menu_items($slug);
      }
  
      foreach ($links as $item) {
        $item->children = [];
        $item->link_id = $item->ID;
        $item->ID = $item->object_id;
        $item->active = $post->ID == $item->object_id;
        $items[$item->link_id] = $item;
      }
  
      foreach ($items as $k => $item) {
        if ($item->menu_item_parent == 0) {
          continue;
        }
  
        $parent = $item->menu_item_parent;
        $items[$parent]->children[] = $item;
      }
  
      foreach ($items as $k => $item) {
        if (count($items[$k]->children)) {
          $items[$k]->has_children = true;
          usort(
            $items[$k]->children,
            "self::sortByMenuOrder"
          );    
        }
      }
  
  
      /* Remove items at the root of the array that have parents */
      $items = array_filter(
        $items,
        function ($item) {
          return !$item->menu_item_parent;
        }
      );
  
      usort($items, "self::sortByMenuOrder");
  
      foreach ($items as $item) {
        self::addLevel($item);
      }
  
      return $items;
    }

    static function getMenuSlugAtLocation ($location) {
      $theme_locations = get_nav_menu_locations();
      $menu_obj = get_term($theme_locations[$location], 'nav_menu');
      return $menu_obj->slug;
    }
  
    static function getFrontPage () {
      return get_post(get_option('page_on_front'));
    }
  
    /* Recursive function used by formatted menu */
    static function addLevel ($item, $prevLevel = 0) {
      $has_active = $item->active;
      
      if (count($item->children)) {
        foreach ($item->children as $k => $v) {
          $child_active = self::addLevel($v, $prevLevel + 1);
          $has_active = $has_active || $child_active;
        }
      }
    
      $item->level = $prevLevel;
      $item->has_active_child = $has_active && !$item->active;
      return $has_active;
    }
  
    static function sortByMenuOrder ($a, $b) {
      return $a->menu_order <=> $b->menu_order;
    }
  
    static function getTemplate ($p = null) {
      $id = $p ?? $post;
      if (is_object($id)) {
        $id = $id->ID;
      }
  
      $templateSlug = get_page_template_slug($id);
  
      return preg_replace("/template-|\.php/", "", $templateSlug);
    }
  }
} else {
  error_log('Tried including helper "WPHelpers" but the class already exists');
}
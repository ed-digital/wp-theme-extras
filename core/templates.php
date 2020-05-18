<?

/* 
Example useage

CustomTemplates::register("map-listing-page", "Listing Page with Map");
CustomTemplates::register(
  'faq-page',
  [
    'label' => 'FAQ Page',
    'template' => [
      array('pages/faq-listing', [])
    ],
    'template_lock' => 'all'
  ]
);
CustomTemplates::register(
  'performance-calendar',
  [
    'label' => 'Performance Calendar',
    'disableGutenberg' => false,
    'supports' => ['title']
  ]
);

*/
class CustomTemplates {
  static $templates = null;

  static function getInstance () {
    if (!self::$instance) {
      self::$instance = new CustomTemplates();
    }
  }

  static function register($name, $labelOrOpts = null) {
    if (!self::$templates) {
      self::$templates = [];
    }
    /* By default the theme name will be the id */
    $labelOrOpts = $labelOrOpts ?? $name;
    /* Form labelOrOpts into an array */
    $opts;
    
    if (is_string($labelOrOpts)) {
      $opts = [ 'name' => $name, 'label' => $labelOrOpts ];
    } else {
      $opts = array_merge([ 'name' => $name ], $labelOrOpts);
    }
    self::$templates[$name] = $opts;
  }  
}

add_filter(
  'theme_page_templates', 
  function($templates) {
    $values = [];
    foreach ((array)CustomTemplates::$templates as $k => $template) {
      $values[$k] = $template['label'];
    }
    return array_merge(
      $templates, 
      $values
    );
  }
);
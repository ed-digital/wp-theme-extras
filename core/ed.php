<?php

  class ED {

    private static $instance;
    public $modules = [];
    public $moduleFieldGroups = null;
    public $postTypeColumns = [];
    public $postTypeColumnManagers = [];

    public $themeURL = null;
    public $themePath = null;
    public $edPath = null;
    public $sitePath = null;
    public $siteURL = null;

    public $API = null;
    public $controllers = [];

    public $blockWhitelist = [];

    public $routes = [];

    public $config = [
      "useBase" => true,
      "includeSiteKit" => false,
      "disableAdminBar" => false,
      "autoloadIncludeJS" => false,
      "useRelativeURLs" => false
    ];

    protected function __construct() { }

    static function getInstance() {
      if(!self::$instance) {
        self::$instance = new ED();
        self::$instance->init();
      }
      return self::$instance;
    }

    public function isPluginGitControlled() {
      return file_exists($this->edPath.".git");
    }

    protected function init() {

      $this->themeURL = get_stylesheet_directory_uri();
      $this->themePath = get_stylesheet_directory();
      $this->sitePath = preg_replace("/\/wp-content\/themes\/[^\/]+$/", "", $this->themePath);
      $this->siteURL = get_site_url();
      $this->edPath = realpath(__DIR__."/../");

      // Add the 'view' query var, for custom routing
      add_filter('query_vars', function($vars) {
        $vars[] = "view";
        $vars[] = "static";
        return $vars;
      });

      // Load core files
      require_once(__DIR__ ."/../helpers/index.php");
      $this->loadDir(__DIR__, true);
      $this->API = new ED_API();

      if($this->themePath == $this->edPath) {
        add_action('admin_notices', array($this, '_showInvalidSetup'));
        return;
      }

      // Include stuff
      $this->loadDir($this->themePath."/includes");

      add_action("init", array($this, "wpInit"));

      // Add filter which filters out disabled templates
      add_filter("theme_page_templates", function($list) {
        $newList = array();
        foreach($list as $file => $title) {
          preg_match("/^templates\/([^\.]+)\.([^\.]+)\.php$/", $file, $match);
          if($match && $this->moduleInstalled($match[1]) == false) {
            continue;
          }
          $newList[$file] = $title;
        }
        return $newList;
      });

      add_action('admin_head', array($this, "_hookListingColumns"));
      add_action('admin_notices', array($this, '_showPluginWarnings'));

      add_filter('wpseo_metabox_prio', function() {
        return 'low';
      });

      if($this->config["useRelativeURLs"] == true) {
        // Load relative URL sub-plugin
        include_once(__dir__."/../lib/relative-url/relative-url.php");
      }
    }

    public function wpInit() {
      if($this->config['disableAdminBar']) {
        add_filter('show_admin_bar', '__return_false');
      }

      foreach($this->routes as $route) {
        add_rewrite_rule($route[4], $route[5], $route[6]);
      }

      // If a hash of the routes doesn't match the one in the DB, flush the routes
      $hash = md5(json_encode($this->routes));
      if ($hash !== get_option("routes_hash")) {
        flush_rewrite_rules();
        update_option("routes_hash", $hash, true);
      }
    }

    public function _showInvalidSetup() {

      echo "<div class='error'><p>You've activated the wrong theme! The 'ED Digital. Parent Theme' should never be activated directly, but should be activated as a parent theme.</p></div>";

    }

    public function _showPluginWarnings() {

      // Ensure plugins are loaded
      $requiredPlugins = array(
        "Advanced Custom Fields" => array("function", "get_field")
      );

      $missingPlugins = array();

      foreach($requiredPlugins as $label => $def) {

        if($def[0] == "function" && function_exists($def[1])) {
          continue;
        } else if($def[0] == "class" && class_exists($def[1])) {
          continue;
        }

        $missingPlugins[] = $label;

      }

      if($missingPlugins) {
        echo "<div class='error'><p>The following plugin(s) are missing: ".implode(", ", $missingPlugins)."</p></div>";
      }

    }

    public function setConfig($key, $val = null) {
      if(is_array($key)) {
        $this->config = array_merge($this->config, $key);
      } else if(is_string($key)) {
        $this->config[$key] = $val;
      }
    }

    public function getURL($localPath, $includeDomain = true) {
      return str_replace($this->sitePath, $includeDomain ? $this->siteURL : "/", $localPath);
    }

    public function locateFile($path) {

      $paths = [
        $this->themePath."/".$path,
        $this->edPath."/".$path,
        $path
      ];

      foreach($paths as $item) {
        if(file_exists($item)) {
          return $item;
        }
      }

    }

    public function addCSS($src, $append = "") {

      if(is_string($src)) {
        $src = array($src);
      }

      foreach($src as $path) {
        $path = preg_replace("/^([\/]+)/", "", $path);
        $location = strpos($path, "http") === 0 ? $path : $this->locateFile($path);
        if($location) {
          wp_enqueue_style(md5($path), $this->getURL($location).$append, null, @filemtime($this->locateFile($path)));
        }
      }

    }

    public function addJS($src, $deps = array(), $append = "") {

      if(is_string($src)) {
        $src = array($src);
      }

      foreach($src as $path) {
        $url = null;
        $path = preg_replace("/^([\/]+)/", "", $path);
        $version = "";
        if(strpos($path, "http") === 0) {
          $url = $path;
        } else {
          $location = $this->locateFile($path);
          if($location) {
            $version = @filemtime($location);
            $url = $this->getURL($location);
          }
        }
        if($url) {
          @wp_enqueue_script(md5($path), $url . $append, $deps, $version, true);
        }
      }

    }

    public function defineModule($name, $settings = array()) {

      $this->modules[$name] = (array)$settings;

    }

    public function moduleTitle($name, $title = null) {
      if($title) {
        $this->modules[$name]['title'] = $title;
      }

      if($this->modules[$name]['title']) {
        return $this->modules[$name]['title'];
      } else {
        return $name;
      }
    }

    public function useModule($name, $arguments = array()) {

      // Load all php files in the module folder (load module.php first)
      @include_once($this->edPath."/modules/".$name."/module.php");
      @include_once($this->themePath."/modules/".$name."/module.php");
      $this->loadDir("modules/".$name, true);

      if(isset($this->modules[$name])) {
        $this->modules[$name] = array_merge($this->modules[$name], $arguments);
      }

    }

    public function moduleInstalled($name) {

      return isset($this->modules[$name]);

    }

    public function getModuleSetting($module, $settingName = null) {
      $settings = @$this->modules[$module];
      if(!$settings) {
        return null;
      }
      if(func_num_args() == 1) {
        return $settings;
      } else {
        return isset($settings[$settingName]) ? $settings[$settingName] : null;
      }
    }

    /*
      Include one or more templates by name. Allows child theme to override parent them.
    */
    public function templates() {

      $templates = func_get_args();

      foreach($templates as $template) {
        get_template_part($template);
      }

    }

    /*
      Runs the specified template, using the data provided. Allows child theme to override parent them.
      $_data should be an associative array, which will be converted into variable scope for use within the template file.
    */
    public function runTemplate($_templateName, $_data = array()) {

      $basePaths = array(
        // First, check the site path
        $this->themePath."/".$_templateName.".php",
        // Then ed plugin path
        $this->edPath."/".$_templateName.".php"
      );

      // Search each path
      $templatePath = null;
      foreach($basePaths as $path) {
        if(file_exists($path)) {
          $templatePath = $path;
          break;
        }
      }

      // Not using locate_template because it doesn't search plugins.
      // $templatePath = locate_template($_templateName.".php", false);

      if(!$templatePath) {
        throw new Exception("Unable to locate template '".$_templateName."'");
      }

      if(is_array($_data)) {
        extract($_data);
      }

      include($templatePath);

    }

    /* 
    Get a list of file names that match $filter
    */
    public function getFiles ($directory, $recursive = true, $filter = "Paths::match_php_file") {
      $paths = glob("$directory/*");
      $collected = [];

      foreach ($paths as $path) {
        if (is_dir($path) && $recursive) {
          $collected = array_merge(
            $collected,
            self::getFiles($path, true, $filter)
          );
        } else {
          if ($filter($path)) {
            $collected[] = $path;
          }
        }
      }

      return $collected;
    }

    public function loadDir($path) {

      // Grab a list of files for both parent/child themes
      $files = glob($path."/*.php");

      foreach($files as $file) {
        require_once($file);
      }
    }

    /*
      Define post columns for one or more post type
      Arguments:
      - postTypes, either a string or array of strings
      - columns, an associative array of columns.
        - set the value of a column to NULL to remove it.
        - otherwise each column should be an associative array containing
          - 'order', a number. 0 is before the checkbox, 1 is before the title, 10 is at the end etc. defaults to 99
          - 'label', to be shown in the table column
          - 'render', a callback which renders the column. the post ID is handed to the render function if available
    */
    public function definePostColumns($postTypes, $columns) {

      // We want either a string
      if(is_string($postTypes)) {
        $postTypes = array($postTypes);
      } else if(!is_array($postTypes)) {
        throw new Exception("Invalid post type '".$postTypes."'");
      }

      // Save the columns to each post type
      foreach($postTypes as $type) {
        $this->postTypeColumns[$type] = array_merge(isset($this->postTypeColumns[$type]) ? $this->postTypeColumns[$type] : array(), $columns);
      }

    }

    public function _hookListingColumns() {

      $postTypes = get_post_types();

      foreach($postTypes as $name => $label) {
        $manager = new EDColumnManager($name, isset($this->postTypeColumns[$name]) ? $this->postTypeColumns[$name] : array());
        $this->postTypeColumnManagers[$name] = $manager;
        add_filter('manage_edit-'.$name.'_columns', array($manager, 'alterColumnLayout'), 16);
        add_action('manage_'.$name.'_posts_custom_column', array($manager, 'printColumn'), 16);
      }
    }

    public function defineController($name, $def) {

    }

        public function genShareLink($socialNetwork='facebook') {
            switch(strtolower($socialNetwork)) {
                case 'facebook':
                case 'fb':
                    return 'http://www.facebook.com/sharer.php?u='.get_permalink().'&amp;t='.get_the_title();
                    break;
                case 'twitter':
                case 'tw':
                    return 'http://twitter.com/home/?status='.get_the_title().' - '.get_permalink();
                    break;
                case 'linkedin':
                case 'in':
                    return 'http://www.linkedin.com/shareArticle?mini=true&amp;title='.get_the_title().'&amp;url='.get_permalink();
                    break;
                case 'google+':
                case 'g+':
                    return 'https://plus.google.com/share?url='.get_permalink();
                    break;
                default:
                    return '';
            }
        }

        public function youtubeID($url) {
            // get youtube id from longform or share form youtube url
            preg_match("/v\=([A-Za-z0-9_-]+)/i",
              $url, $match);

            if(empty($match))
              $videoID = basename(parse_url($url)['path']);
            else
              $videoID = $match[1];

            return $videoID;
        }

        public function youtubeImage($url) {
            return "http://img.youtube.com/vi/".$this->youtubeID($url)."/maxresdefault.jpg";
        }

    public function registerMenu($name, $label) {
      $menus = array();
      $menus[$name] = $label;
      register_nav_menus($menus);
    }

    public function showMenu($name) {
      wp_nav_menu(array(
        "theme_location" => $name
      ));
    }

    public function getSection($postObject = null) {

      if(!$postObject) {
        global $post;
        $postObject = $post;
      }

      if($postObject->post_parent == 0) return $postObject;

      $ancestors = @get_ancestors($postObject->ID, $post->post_type);

      return @get_post(end($ancestors));

    }

    public function printDataArgs($args) {

      foreach($args as $key => $val) {

        echo " data-".strtolower(preg_replace("/([a-z])([A-Z])/", '$1-$2', $key))."=\"";

        if($val === null) {
          //echo "";
        } else if($val === true) {
          echo "true";
        } else if($val === false) {
          echo "false";
        } else if(is_string($val) || is_numeric($val)) {
          echo htmlspecialchars($val);
        } else {
          echo htmlspecialchars(json_encode($val));
        }

        echo "\"";

      }

    }

    public function addRoute ($path, $title, $template, $args = null) {
      $path = trim($path, "/") . '$';
      $routeIndex = count($this->routes);
      $template = ED()->themePath."/".preg_replace("/\.php$/", "", $template).".php";
      $this->routes[] = [
        'template',
        $template,
        $title,
        $args,
        $path,
        'index.php?static=customRoute&view='.$routeIndex,
        'top'
      ];
    }

    public function addFunctionRoute ($path, $callback) {
      $path = trim($path, "/") . '$';
      $routeIndex = count($this->routes);
      $this->routes[] = [
        'function',
        $callback,
        "",
        null,
        $path,
        'index.php?static=customRoute&view='.$routeIndex,
        'top'
      ];
    }

    public function ensureTable ($createStatement) {
      ensureDatabaseTable($createStatement);
    }

    public function disableDefaultBlocks() {
      $this->defaultBlocksDisabled = true;
    }

    /*
      Expected the same arguments as "acf_register_block_type", however with a "part" attribute, which must be a string
    */
    public function addBlock ($def) {
      if (!$def['render_callback']) {
        if (!$def['part']) {
          throw new Exception("No 'part' attribute was found when registering a block.");
        }
        $def['render_callback'] = function(...$args) use ($def) {
          $fields = get_fields();
          echo Part($def['part'], $fields);
        };
      }

      /* When the theme is installed the site may not have acf installed */
      if (function_exists('acf_register_block_type')) {
        return acf_register_block_type($def);
      } else {
        return $def;
      }
    }

    public function whitelistBlocks (...$blockNames) {
      foreach ($blockNames as $blockName) {
        $this->whitelistBlock($blockName);
      }
    }
    public function whitelistBlock($blockName) {
      $this->blockWhitelist[] = $blockName;
    }

    public function addBodyClass ($className) {
      add_filter('body_class', function($classes) use ($className) {
        $classes[] = $className;
        return $classes;
      });
    }

  }

  function ED() {
    return ED::getInstance();
  }

  ED();

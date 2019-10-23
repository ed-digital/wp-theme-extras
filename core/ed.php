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

    public $routes = [];

    public $config = [
      "useBase" => true,
      "includeSiteKit" => false,
      "disableAdminBar" => false,
      "autoloadIncludeJS" => false,
      "loadComponents" => false,
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
      $this->edPath = $this->sitePath."/wp-content/plugins/ed/";

      // Add the 'view' query var, for custom routing
      add_filter('query_vars', function($vars) {
        $vars[] = "view";
        $vars[] = "static";
        return $vars;
      });

      // Load core files
      $this->loadDir("core", true);
      $this->API = new ED_API();

      if($this->themePath == $this->edPath) {
        add_action('admin_notices', array($this, '_showInvalidSetup'));
        return;
      }

      // Load the theme config file
      $configPath = $this->themePath."/config.php";
      if(!file_exists($configPath)) {
        throw new Exception("No child theme config.php found.");
      }
      include($configPath);

      // Include stuff
      $this->loadDir("includes", true);

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
      add_action('admin_init', array($this, "_hookACFJSON"));
      add_action('admin_notices', array($this, '_showPluginWarnings'));

      add_filter('plugin_row_meta', array($this, "addPluginLinks"), 0, 4);

      add_filter('wpseo_metabox_prio', function() {
        return 'low';
      });

      if($this->config["useRelativeURLs"] == true) {
        // Load relative URL sub-plugin
        include_once(__dir__."/../lib/relative-url/relative-url.php");
      }

      if($this->config["loadComponents"] == true) {
        ComponentRegistry::loadComponents($this->themePath."/components");
      }
    }

    public function wpInit() {
      $this->includePostTypes();
      $this->enqueueFiles();

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

    public function includePostTypes() {
      $this->loadDir("includes/post-types");
    }

    public function enqueueFiles() {

      if(is_admin()) {

        // Automatically include all widget and lib files
        $lessFiles = array_merge(
          glob($this->themePath."/assets/admin/less/*.less"),
          glob($this->themePath."/assets/admin/less/*.css")
        );
        foreach($lessFiles as $file) {
          $this->addCSS($this->getURL($file));
        }

        // Automatically include all widget and lib files
        $jsFiles = array_merge(
          glob($this->themePath."/assets/admin/js/*.js")
        );
        foreach($jsFiles as $file) {
          $this->addJS($this->getURL($file), [
            "jquery-ui-widget"
          ]);
        }

      } else {

        // ED Sitekit
        if($this->config['includeSiteKit'] == true) {

          $this->addJS("assets/js/ed-sitekit.js", array(
            "jquery-ui-widget"
          ));

        }

        // Automatically include all widget and lib files
        if($this->config['autoloadIncludeJS'] == true) {
          $jsFiles = array_merge(
            glob($this->themePath."/assets/js/widgets/*.js"),
            glob($this->themePath."/assets/js/lib/*.js")
          );
          foreach($jsFiles as $file) {
            $this->addJS($this->getURL($file));
          }
        }

      }

    }

    public function addPluginLinks($meta, $file, $data, $status) {

      if($file == "ed/edplugin.php") {
        $meta[] = "<a href='http://ed-wp-plugin.ed.com.au/release/ed-".$data['Version']."-blank-theme.zip'>Download Blank Theme</a>";
        $meta[] = "<a href='https://bitbucket.org/ed_digital/ed-wp-plugin/wiki/Home'>View Wiki</a>";
        if($this->isPluginGitControlled()) {
          $meta[] = "Found <code>.git</code> folder, updates disabled!";
        }
      }

      return $meta;

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
          @wp_enqueue_script(md5($path), $url . $append, $deps, $version);
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

    public function _hookACFJSON() {

      foreach($this->modules as $name => $options) {

         // Load ACF field info from file if the user is currently in the ACF interface
         if(isset($_GET['post_type']) && $_GET['post_type'] == "acf-field-group") {

           // Get all json files in this directory
           $dir = get_template_directory() . '/modules/'.$name.'/acf-json';

           if(!is_dir($dir)) continue;

           $files = scandir($dir);

           if(!$this->moduleFieldGroups) {
             $this->moduleFieldGroups = array();
           }

           foreach($files as $filePath) {

             // Ensure the files are following the ACF naming convention
             preg_match("/(group_[a-z0-9]+)/", $filePath, $match);

             if($match) {

               $src = $dir."/".$filePath;

               $key = $match[0];
               $dateModified = filemtime($src);

               $this->moduleFieldGroups[$key] = (object)array(
                 "modified" => $dateModified,
                 "module" => $name,
                 "path" => $src
               );

               $result = json_decode(file_get_contents($src), true);
               $result['file_mtime'] = $dateModified;

               $existing = acf_get_field_group($result['key']);
               if(!$existing) {
                 $result['local'] = 'json';
                 $result['original_import_modified_date'] = $dateModified;
                 $result['fresh'] = true;
                 acf_local()->add_field_group($result);
              }

               // Re-import if requested
               if(isset($_GET['reimport']) && isset($_GET['group_key']) && $result['key'] == $_GET['group_key']) {

                 $result['ID'] = (int)$_GET['reimport'];
                 $result['original_import_modified_date'] = $dateModified;
                 $result['fresh'] = true;
                 ob_start();
                 acf_import_field_group($result);

                 ob_end_clean();
                 header("Location: edit.php?post_type=acf-field-group&reimportcomplete=true");
                 die();

               } else if(isset($_GET['reimportcomplete'])) {
                 acf_add_admin_notice('Field group re-imported from ThemED.');
               }

             }
           }

         }

       }

       if(isset($_POST['acf_field_group']) && isset($_POST['acf_field_group']['key'])) {
         $key = $_POST['acf_field_group']['key'];
         $current = acf_get_field_group($key);
         $_POST['acf_field_group']['original_import_modified_date'] = @$current['original_import_modified_date'];
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
      Loads all .php files in the specified directory in both the child and parent theme, optionally ignoring the parent themes version of the file if the file exists in the child theme
      Uses require_once
    */
    public function loadDir($path, $ignoreParentIfChild = false) {

      $path = trim($path, "/");

      $parentPath = $this->edPath;
      $childPath = get_stylesheet_directory();

      // Grab a list of files for both parent/child themes
      $parentFiles = str_ireplace($parentPath, "", glob($parentPath."/".$path."/*.php"));
      $childFiles = str_ireplace($childPath, "", glob($childPath."/".$path."/*.php"));

      // If we're allowing files to be overridden, filter out parent files which HAVE been overridden
      if($ignoreParentIfChild) {
        // Remove files from the parent which exist in child theme
        $parentFiles = array_diff($parentFiles, $childFiles);
      }

      // Include each of the files
      foreach($parentFiles as $file) {
        $this->requireOnce($parentPath.$file);
      }
      foreach($childFiles as $file) {
        $this->requireOnce($childPath.$file);
      }

    }

    private function requireOnce($path) {
      require_once($path);
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

    public function printBreadcrumbs($options = array()) {
      EDBreadcrumbs::printBreadcrumbs($options);
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

    /*
      Expected the same arguments as "acf_register_block_type", however with a "component" attribute, which must be a string
    */
    public function addBlock ($def) {
      if (!$def['render_callback']) {
        if (!$def['component']) {
          throw new Exception("No 'component' attribute was found when registering a block.");
        }
        $def['render_callback'] = function(...$args) use ($def) {
          $fields = get_fields();
          echo C($def['component'], $fields);
        };
      }
      return acf_register_block_type($def);
    }

  }

  function dump() {
    if(error_reporting() === 0) return;

    echo "<pre># ";
    foreach(func_get_args() as $item) {
      if(is_array($item) || is_object($item)) {
        print_r($item);
      } else {
        echo json_encode($item);
      }
      echo " ";
    }
    echo "</pre>";
  }

  function ED() {
    return ED::getInstance();
  }

  ED();

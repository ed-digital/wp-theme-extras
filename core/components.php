<?php

  class ComponentRuntime {
    static $depth;
    static $stack;
    static $index;

    static function reset() {
      self::$depth = 0;
      self::$index = 0;
      self::$stack = [];
    }

    static function execute($file, $_args, $name = null) {
      array_push(self::$stack, $name);
      self::$depth++;
      self::$index++;
      ob_start();
      self::include($file, $_args);
      $output = ob_get_contents();
      ob_end_clean();
      self::$depth--;
      array_pop(self::$stack);
      return $output;
    }

    static function include($file, $_args) {
      $arg = function($name) use ($_args) {
        if (is_array($_args)) return $_args[$name];
        if (is_object($_args)) return $_args->$name;
        return null;
      };
      include($file);
    }
  }

  ComponentRuntime::reset();

  function is_dev() {
    return strpos($_SERVER['SERVER_NAME'], '.local') !== 0;
  }

  class ComponentRegistry {
    static $components;
    static $directory;

    static function defaultDir () {
      return ED()->themePath . '/parts';
    }

    private function __construct() {

    }

    static function _glob_recursive($pattern, $flags = 0){
      $files = glob($pattern, $flags);
      
      $globbed = glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT);
      foreach ($globbed as $dir){
        $files = array_merge($files, self::_glob_recursive($dir.'/'.basename($pattern), $flags));
      }
      return $files;
    }
    
    static function loadComponents($folder = null) {
      $folder = $folder ?? self::defaultDir();
      $components = self::_glob_recursive($folder."/**/*.php");
      foreach ($components as $file) {
        $name = cleanComponentName(preg_replace("/(^\/|.php$)/i", "", str_replace($folder, "", $file)));
        $segments = explode("/", $name);
        $root = self::$components;
        $lastSeg = null;
        foreach ($segments as $k => $seg) {
          if ($k === count($segments) - 1) {
            $root->$seg = (object)[
              'isComponent' => true,
              'file' => $file
            ];
            self::$directory[$name] = $file;
            if ($seg === $lastSeg) {
              // File has the same name as it's parent folder
              $root->isComponent = true;
              $root->file = $file;
              self::$directory[preg_replace("/\/[^\/]+$/", "", $name)] = $file;
            }
          } else {
            if (!$root->$seg) $root->$seg = (object)[];
            $root = $root->$seg;
          }
          $lastSeg = $seg;
        }
      }

      self::buildStylesheetIndex();
    }

    static function buildStylesheetIndex() {

      $types = [
        '.scss' => ['@import "', '";'],
        '.less' => ['@import url("', '");']
      ];
      
      foreach ($types as $ext => $wraps) {
        $file = self::defaultDir() . "/index$ext";
        if (!file_exists($file)) continue;
        $contents = file_get_contents($file);

        $files = self::_glob_recursive(self::defaultDir() . "/**/*".$ext);
        
        $lines = [];
        foreach ($files as $item) {
          $lines[] = $wraps[0] . str_replace(self::defaultDir()."/", "./", $item) . $wraps[1];
        }

        $newContents = implode("\n", $lines);

        if ($newContents !== $contents) {
          file_put_contents($file, $newContents);
        }
      }
      
    }

    // static function getTemplateFunction($file, $folder) {
    //   $result = include_once($file);
    //   if (!is_callable($result)) {
    //     if (is_array($result) && $result["render_callback"]) {
    //       return $result["render_callback"];
    //     } else {
    //       $name = str_replace($folder."/", "", $file);
    //       throw new Exception("The component file '{$name}' did not return a function, or a block");
    //     }
    //   } else {
    //     return $result;
    //   }
    // }
  }

  ComponentRegistry::$components = (object)[];
  ComponentRegistry::$directory = [];

  class ComponentLookup {
    protected $root;
    public $path;
    public function __construct($root, $path) {
      $this->root = $root;
      $this->path = $path;
    }

    public function __get($name) {
      $name = cleanComponentName($name);
      $pointer = $this->root->$name;
      if (!$pointer) {
        // No component or namespace
        throw new Error("Unknown component namespace namespace '{$this->getPath($name)}'");
      } else {
        // A namespace
        return new ComponentLookup($pointer, $this->path.$name."/");
      }
    }

    public function __call($name, $args = []) {
      $name = cleanComponentName($name);
      $fullName = $this->getPath($name);
      $pointer = $this->root->$name;
      if ($pointer) {
        if ($pointer->isComponent) {
          return ComponentRuntime::execute($pointer->file, @$args[0], $name);
          // if (count($args) < $pointer->totalRequiredArgs) {
          //   throw new Exception("Expected at least {$pointer->totalRequiredArgs} arguments for component '{$fullName}'");
          // }
          // call_user_func_array($pointer->function, $args);
        } else {
          // Cannot call a namespace
          throw new Exception("Attempted to use namespace '{$fullName}' as a component");
        }
      } else {
        throw new Exception("Unknown component '{$fullName}'");
      }
    }

    public function getPath($name = null) {
      return $this->path.$name;
    }
    
  }

  function cleanComponentName($name) {
    return preg_replace("/[^\/a-z0-9\_]/", "", strtolower($name));
  }

  global $rootComponentLookup;
  $rootComponentLookup = new ComponentLookup(ComponentRegistry::$components, "");

  function C($name = null, $args = []) {
    if ($name) {
      $file = ComponentRegistry::$directory[cleanComponentName($name)];
      if ($file) {
        return ComponentRuntime::execute($file, $args, $name);
      } else {
        throw new Exception("Unknown component '{$name}'");
      }
    } else {
      global $rootComponentLookup;
      return $rootComponentLookup;
    }
  }
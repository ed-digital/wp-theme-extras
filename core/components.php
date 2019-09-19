<?php

  class ComponentRegistry {
    static $components;
    private function __construct() {

    }
    
    static function loadComponents($folder) {
      $components = glob($folder."/**/*.php");
      foreach ($components as $file) {
        $name = preg_replace("/(^\/|.php$)/i", "", str_replace($folder, "", $file));
        $segments = explode("/", strtolower($name));
        $root = self::$components;
        foreach ($segments as $k => $seg) {
          if ($k === count($segments) - 1) {
            $function = self::getTemplateFunction($file, $folder);
            $reflection = new ReflectionFunction($function);
            $root->$seg = (object)[
              '__component' => true,
              '__file' => $file,
              'totalRequiredArgs' => $reflection->getNumberOfRequiredParameters(),
              'function' => $function
            ];
          } else {
            if (!$root->$seg) $root->$seg = (object)[];
            $root = $root->$seg;
          }
        }
      }
    }

    static function getTemplateFunction($file, $folder) {
      $result = include_once($file);
      if (!is_callable($result)) {
        $name = str_replace($folder."/", "", $file);
        throw new Exception("The component file '{$name}' did not return a function");
      } else {
        return $result;
      }
    }
  }

  ComponentRegistry::$components = (object)[];

  class ComponentLookup {
    protected $root;
    public $path;
    public function __construct($root, $path) {
      $this->root = $root;
      $this->path = $path;
    }

    public function __get($name) {
      $name = strtolower($name);
      $pointer = $this->root->$name;
      if (!$pointer) {
        // No component or namespace
        throw new Error("Unknown component namespace namespace '{$this->getPath($name)}'");
      } else {
        if ($pointer->__component) {
          // Found a component at this address
          return $pointer->function;
        } else {
          // A namespace
          return new ComponentLookup($pointer, $this->path.$name."/");
        }
      }
    }

    public function __call($name, $args = []) {
      $name = strtolower($name);
      $fullName = $this->getPath($name);
      $pointer = $this->root->$name;
      if ($pointer) {
        if ($pointer->__component) {
          if (count($args) < $pointer->totalRequiredArgs) {
            throw new Exception("Expected at least {$pointer->totalRequiredArgs} arguments for component '{$fullName}'");
          }
          ob_start();
          call_user_func_array($pointer->function, $args);
          $output = ob_get_contents();
          ob_end_clean();
          return $output;
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

  global $rootComponentLookup;
  $rootComponentLookup = new ComponentLookup(ComponentRegistry::$components, "");

  function C() {
    global $rootComponentLookup;
    return $rootComponentLookup;
  }
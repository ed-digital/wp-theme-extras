<?php
class PartRuntime {
  private static $state = null;
  private static $children;
  static $comment = null;
  static $dir = null;
  
  static function setup() {
    if (self::$children) {
      self::$children = [];
    }
    /*  */
    if (self::$comment === null) {
      self::$comment = is_dev();
    }
    /*  */
    if (self::$state === null) {
      self::$state = (object)[
        'depth' => 0,
        'used' => (object)[],
        'stack' => []
      ];
    }
  }

  static function buildStyleIndexSheet () {

    $types = [
      '.scss' => ['@import "', '";'],
      '.less' => ['@import url("', '");']
    ];
    
    foreach ($types as $ext => $wraps) {
      $file = self::$dir . "/index$ext";
      if (!file_exists($file)) continue;
      $childrens = file_get_contents($file);

      $files = self::_glob_recursive(self::$dir . "/**/*".$ext);
      
      $lines = [];
      foreach ($files as $item) {
        $lines[] = $wraps[0] . str_replace(self::$dir."/", "./", $item) . $wraps[1];
      }

      $newContents = implode("\n", $lines);

      if ($newContents !== $childrens) {
        file_put_contents($file, $newContents);
      }
    }
    
  }

  public static function currentDepth () {
    return get(self::$state, 'depth');
  }

  public static function usedCount ($name) {
    return get(self::$state, "used.$name", 0);
  }

  public static function start ($name, $children) {
    self::$state->depth += 1;

    /* How many times this component has been called on this particular page */
    $currentIndex = self::usedCount($name);
    self::$state->used->{$name} = $currentIndex + 1;

    $id = $name . $currentIndex;
  
    self::$state->stack[] = $id;
    self::$children[$id] = $children;

    return $id;
  }

  public function end () {
    self::$state->depth -= 1;
    $id = array_pop(self::$state->stack);
    unset(self::$children[$id]);
  }

  static function run($file, $props = [], $children = '', $config = [], $meta = []) {

    $name = get($meta, 'name');

    PartRuntime::setup();
    $id = PartRuntime::start($name, $children);

    /* Errors are swallowed if they happen inside an ob buffer */
    $error = null;
    $comment = PartRuntime::$comment && get($config, 'comment') !== false;

    ob_start();

    /* HTML comments are shown during dev */
    if ($comment) echo "<!-- " . PartRuntime::currentDepth() . " <{$name}> -->";
    
    /* Call the part */
    try {
      self::include($file, $props);
    } catch (Exception $err) {
      $error = $err;
    }

    if ($error) {
      $props = json_encode($props, JSON_PRE);
      /* Visible error in dev mode */
      console::error("Part $name errored: {$error['message']} with props: $props");
      /* Inivisible error in production */
      $props = esc_html($props);
      return "<!-- Part $name errored: {$error['message']} with props: $props -->";
    }

    /* HTML comments are shown during dev */
    if ($comment) echo "<!-- " . PartRuntime::currentDepth() . " </$name> -->";
    $output = ob_get_contents();
    ob_end_clean();

    PartRuntime::end();
  
    return $output;
  }

  static function include($file, $args) {
    $arg = createGetter($args);
    include($file);
  }
}

PartRuntime::$dir = ED()->themePath . '/parts/';

class PartProps {
  function __construct ($props) {
    foreach ($props as $key => $val) {
      $this->$key = $val;
    }
  }

  function __toString () {
    return Element::attrs($this);
  }
}

function props () {
  $id = Arr::last(PartRuntime::$stack);

  return new PartProps([
    'id' => $id,
  ]);
}

function children () {
  $id = Arr::last(PartRuntime::$state->stack);
  return get(PartRuntime::$children, $id, '');
}

class PartLookup {
  public function __construct($path = null) {
    $this->pointer = $path ? explode('/', $path) : [];
    $this->name = $path ? Arr::last($this->pointer) : "";
  }

  public static function getPathName($str) {
    return strtolower(
        preg_replace_callback(
        "/[^ ]([A-Z][a-z])/",
        function ($matches) {
          return str_replace($matches[1], '-' . $matches[1], $matches[0]);
        },
        $str
      )
    );
  }

  public function __get($part) {
    $name = PartLookup::getPathName($part);
    $this->name = $name;
    $this->pointer[] = $name;
    return $this;
  }

  public function call($props = [], $children = '', $config = [], $meta = []) {
    $path = $this->getPath();

    $DEFAULT_CONFIG = [];
    $DEFAULT_META = [
      'name' => $this->name,
      'path' => $path
    ];

    $config = array_merge($DEFAULT_CONFIG, $config);
    $meta = array_merge($DEFAULT_META, $meta);

    return PartRuntime::run($path, $props, $children, $config, $meta);
  }

  public function __call($name, $args = []) {
    $partName = PartLookup::getPathName($name);

    /* Copy the __get functionality */
    $this->name = $partName;
    $this->pointer[] = $partName;

    $props = get($args, 0, []);
    $children = get($args, 1, '');
    $config = get($args, 2, []);
    $meta = get($args, 3, []);


    return $this->call($props, $children, $config, $meta);
  }

  public function getPath() {
    $path = PartRuntime::$dir . PartLookup::getPathName(
      implode('/', $this->pointer)
    );
    
    if (is_dir($path)) {
      $path = $path . '/' . $this->name . '.php';
    } else if (file_exists($path . '.php')) {
      /* Looks like we got the right path */
      return $path . '.php';
    } else {
      throw new Exception("Part \"$path\" doesnt exist.");
    }

    return $path;
  }
}

function Part ($path = null, $props = [], $children = '', $config = [], $meta = []) {
  if ($path) {
    $lookup = new PartLookup($path);
    return $lookup->call($props, $children, $config, $meta);
  } else {
    return new PartLookup();
  }
}

function P(...$args) {
  return Part(...$args);
}
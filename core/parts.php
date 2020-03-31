<?php
class PartRuntime {
  private static $state = null;
  private static $children = null;
  static $config = null;
  static $dir = null;

  static function current () {
    return Arr::last(self::$state->stack);
  }
  
  static function setup() {
    if (self::$children === null) {
      self::$children = [];
    }

    if (self::$config === null) {
      self::$config = [
        'comment' => is_dev()
      ];
    }

    /*  */
    if (self::$state === null) {
      self::$state = (object)[
        'depth' => 0,
        'used' => (object)[],
        'stack' => [],
        'file_to_name' => []
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
      $contents = file_get_contents($file);

      $files = ED()->getFiles(self::$dir, true, function ($file) use ($ext) {
        return Paths::has_extension($file, $ext);
      });
      
      $lines = [];
      foreach ($files as $item) {
        if ($item === $file) continue;
        $lines[] = $wraps[0] . str_replace(self::$dir."/", "", $item) . $wraps[1];
      }

      $newContents = implode("\n", $lines);

      if ($newContents !== $contents) {
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

  static function run($fileOrFunction, $props = [], $children = '', $config = [], $meta = []) {


    $name = get($meta, 'name');

    PartRuntime::setup();
    PartRuntime::start($name, $fileOrFunction, $children);

    $config = array_merge([], self::$config, $config);

    /* Errors are swallowed if they happen inside an ob buffer */
    $error = null;
    $comment = get($config, 'comment') !== false;

    ob_start();

    /* HTML comments are shown during dev */
    if ($comment) echo "<!-- " . PartRuntime::currentDepth() . " <{$name}> -->";
    
    /* Call the part */
    try {
      if ( is_callable($fileOrFunction) ) {
        $fileOrFunction(createGetter($props));
      } elseif ( is_string($fileOrFunction) ) {
        self::include($fileOrFunction, $props);
      }
    } catch (Exception $err) {
      $error = $err;
    } catch (Error $err) {
      $error = $err;
    }

    if ($error) {
      $partName = get($meta, 'name');
      $message = $error->getMessage();
      $info = @$error->info;

      /* Visible error in dev mode */
      if (is_dev($message)) {
        ob_clean();
        ?>
        <div class="part-error" style="padding: 0px 16px; margin: 8px 0px; border: currentColor 1px solid;">
          <pre style="text-align:left; line-height: 1.5;white-space: normal;">
            <div style="margin-bottom: 8px;"><strong>Part "<?= $partName ?>" errored</strong></div>
            <div style="margin-bottom: 8px;"><?=$error->getMessage();?></div>
            <div style="margin-bottom: 8px;"><?=$error->getTraceAsString(); ?></div>
            <? if ($info) { ?><div><?=$info?></div><? } ?><div>Part props:</div>
          </pre>
          <? dump($props); ?>
        </div>
        <?
      } else {
        ob_clean();
        ?>
        <!-- " . $message . " with props: $props -->
        <?
      }
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


class PartMeta {
  function __construct ($props) {
    foreach ($props as $key => $val) {
      $this->$key = $val;
    }
  }

  function __toString () {
    return Element::attrs($this);
  }
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

    $DEFAULT_META = [
      'name' => $this->name,
      'path' => $path
    ];

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

PartRuntime::$dir = ED()->themePath . '/parts/';
PartRuntime::buildStyleIndexSheet();



function Part ($pathOrCallback = null, $props = [], $children = '', $config = [], $meta = []) {
  if ($pathOrCallback) {
    if ( is_callable($pathOrCallback) ) {
      $name = 'Anonymous' . uniqid();
      $meta = array_merge(['path' => null, 'name' => $id ]);
      return PartRuntime::run($pathOrCallback, $props, $children, $config, $meta);
    } elseif ( is_string($pathOrCallback) ) {
      $lookup = new PartLookup($pathOrCallback);
      return $lookup->call($props, $children, $config, $meta);
    }
  } else {
    return new PartLookup();
  }
}

function P(...$args) {
  return Part(...$args);
}


/* Part contextual functions */
function partMeta () {
  $id = PartRuntime::current();

  /* PartMeta automatically converts to html attributes when casted to string */
  return new PartMeta([
    'id' => $id,
  ]);
}


function children () {
  $id = Arr::last(PartRuntime::$state->stack);
  return get(PartRuntime::$children, $id, '');
}
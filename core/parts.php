<?php

/* 
PartRuntime::$state {
  stack => string(id)[],
  depth => number,
  used => [ string(id) => number ],
  children => [ string(id) => string ],
  context => [ string(id) => any ]
}

Partruntime::$state {
  // Keeps trach of the current stack depth
  depth => number,
  // Keeps track of how many times each part has been used in order to create the ids
  used => [ string(id) => number ],
  stack => [
    string(id) => [
      id => string
      props => [ string => any ],
      children => [ string => (string | callback) ] | string | callback,
      context => [ string => any ]
    ]
  ]
}
*/

class PartRuntime {
  static $state = null;
  static $config = null;
  static $dir = null;
  
  static function getCurrent () {
    if (self::currentDepth() === -1) return null;
    return self::$state->stack[self::currentDepth() - 1];
  }

  static function setContext($context) {
    PartRuntime::setup();
    $stack = self::getCurrent();

    if ($stack === null) {
      self::$state->base_context = array_merge(self::$state->base_context, $context);
    } else {
      $stack->context = array_merge($stack->context, $context);
    }
  }

  static function getContext($key) {
    for ($i = count(self::$state->stack) - 1; $i >= -1; $i--) {
      if ($i === -1) {
        $val = get(self::$state->base_context, $key);
      } else {
        $fiber = self::$state->stack[$i];
        $context = $fiber->context;
        $val = get($context, $key);
      }
      if ($val !== null) {
        return $val;
      }
    }
    return null;
  }
  
  static function setup() {

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
        'base_context' => [],
        'stack' => [],
      ];
    }
  }

  static function buildStyleIndexSheet () {
    if (!is_dev()) return;

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

  // Current stack depth
  public static function currentDepth () {
    return get(self::$state, 'depth');
  }

  // Amount of times a particular part has been used (by part name)
  public static function usedCount ($name) {
    return get(self::$state, "used.$name", 0);
  }

  public static function id () {
    return self::getCurrent()->id;
  }

  // Setup a Part
  public static function start ($name, $props, $children) {
    self::$state->depth += 1;

    /* How many times this component has been called on this particular page */
    $currentIndex = self::usedCount($name);
    self::$state->used->{$name} = $currentIndex + 1;

    $id = $name . '_' . $currentIndex;
  
    self::$state->stack[] = (object)[
      'id' => $id,
      'depth' => self::$state->depth,
      'props' => $props,
      'children' => $children,
      'context' => [],
      'part' => $name
    ];

    return $id;
  }

  // Complete a part
  public static function end () {
    array_pop(self::$state->stack);
    self::$state->depth -= 1;
  }

  public static function run($fileOrFunction, $props = [], $children = '', $config = [], $meta = []) {
    $name = get($meta, 'name');

    PartRuntime::setup();
    PartRuntime::start($name, $props, $children);

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
      /* Visible error in dev mode */
      if (is_dev($message)) {
        $partName = get($meta, 'name');
        printPartError($error, $partName, $props);
      }

      return '';
    }

    /* HTML comments are shown during dev */
    if ($comment) echo "<!-- " . PartRuntime::currentDepth() . " </$name> -->";
    if (is_admin()) {
      $id = uniqid();
      ?>
      <script id='<?= $id ?>'>
        ;(function () {
          var script = document.getElementById("<?= $id ?>")
          var container = script.previousElementSibling
          if (window.Site) { 
            Site.initWidgets();
            Site.transitionWidgetsIn(Site.$(document.documentElement), null, null, function () {});
          }
          script.remove()
        })()
      </script>
      <?php
    }
    $output = ob_get_contents();
    ob_end_clean();

    PartRuntime::end();
  
    return $output;
  }

  public static function include($file, $args) {
    global $post;
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

  /* 
  AAAHeading -> aaa-heading
  SydneyFilmFestival -> sydney-film-festival
  Image -> image
  */
  public static function getPathName($str) {
    /* 
    "Not whitespace" adds the character before the group match into the matches array
    [^ ]
    */
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

  public static function getPartName($path) {
    return preg_replace_callback(
      "/-([a-z])/",
      function ($matches) {
        return str_replace($matches[0], ucfirst($matches[1]), $matches[0]);
      },
      $path
    );
  }

  public function __get($part) {
    $name = PartLookup::getPathName($part);
    $this->name = $name;
    $this->pointer[] = $name;
    return $this;
  }

  public function call($props = [], $children = '', $config = [], $meta = []) {
    try {
      $path = $this->getPath();
    } catch (Exception $err) {
      if (is_dev()) {
        printPartError($err, $this->name, $props);
        return '';
      }
    }

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
      throw new Exception("Part <span class='visible'>\"{$this->name}\"</span><span class='hidden'>\"$path\"</span> doesnt exist.");
    }

    return $path;
  }
}

PartRuntime::$dir = ED()->themePath . '/parts/';
PartRuntime::buildStyleIndexSheet();


function Part ($pathOrCallback = null, $props = [], $children = '', $config = [], $meta = []) {
  /* 
  When Part is called with arguments 
  - If the first arg is a string it creates a file lookup on that string
  - If the first arg is an anonymous fn it runs the fn as if it was a part
  */
  if ($pathOrCallback) {
    /* Run the callback as if it was a part file */
    if (is_callable($pathOrCallback)) {
      $callback = $pathOrCallback;
      if (!isset($meta['name'])) {
        $meta['name'] = 'Anonymous_' . uniqid();
      }
      $meta = array_merge($meta, [ 'path' => null ]);
      return PartRuntime::run($callback, $props, $children, $config, $meta);
    } elseif (is_string($pathOrCallback)) {
      $path = $pathOrCallback;
      /* Run the part at $pathOrCallback with the rest of the args */
      $lookup = new PartLookup($path);
      return $lookup->call($props, $children, $config, $meta);
    }
  } else {
    /* When Part is called with no arguments it returns a lookup object */
    return new PartLookup();
  }
}

function P(...$args) {
  return Part(...$args);
}

/* Pretty part errors */
function printPartError($error, $partName, $props) {
  $id = uniqid('err-');
  ob_clean();
  $trace = $error->getTrace();
  // dump($trace);
  ?>
  <style>
    .part-error {
      --bg: #292D3E;
      --comment: #697098;
      --fn-name: #82AAFF;
      --cls-name: #ffcb6b;
      --variable: #bec5d4;
      --param: #7986E7;
      --punctuation: #bfc7d5;
      --string: #C3E88D;
      --color: var(--punctuation);
      background: var(--bg);
      color: var(--color);
      padding: 25px 20px;
      margin: 40px 20px;
      border: currentColor 1px solid;
      font-weight: 400;
      overflow: auto;
      border-radius: 30px;
      font-size: 15px;
      box-shadow: 0px 8px 25px 0px rgba(0, 0, 0, 0.5);
    }
    
    .part-error strong {
      /* color: white; */
    }

    .part-error .code {
      font-family: hasklig, monospace;
    }

    .part-error .string {
      color: var(--string);
    }

    .part-error .function {
      color: var(--fn-name);
    }

    .part-error .class {
      color: var(--cls-name);
    }

    .part-error .punctuation {
      color: var(--punctuation);
    }

    .part-error .param {
      color: var(--param);
    }

    .part-error .var {
      color: var(--variable);
    }

    .part-error .comment {
      color: var(--comment);
    }

    .part-error a {
      color: var(--comment);
    }

    .part-error a:hover {
      color: var(--color);
    }

    .part-error .hidden-toggle {
      position: absolute;
      right: 0;
      top: 0;
      cursor: pointer;
    }

    .part-error .hidden-toggle svg {
      position: absolute;
      top: 0;
      right: 0;
      fill: var(--comment);
    }

    .part-error .hidden-toggle:hover svg {
      fill: var(--color);
    }

    .part-error.hidden .hidden {
      display:none;
    }

    .part-error:not(.hidden) .visible {
      display: none;
    }

    .part-error a[data-log]:hover {
      color: var(--color);
    }

    .part-error .error {
      text-align: left;
      line-height: 1.5;
      white-space: normal;
      position: relative;
    }

    .part-error .file {
      font-family: Consolas, Menlo, monospace;
    }

    .part-error .line {
      margin-bottom: 8px;
    }
  </style>
  <div class="part-error hidden" id="<?=$id?>">
    <div class="error">
      <div class="hidden-toggle">
        <svg class="visible" xmlns="http://www.w3.org/2000/svg" fill="currentColor" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0zm0 0h24v24H0zm0 0h24v24H0zm0 0h24v24H0z" fill="none"/><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
        <svg class="hidden" xmlns="http://www.w3.org/2000/svg" fill="currentColor" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
      </div>
      <div class="line"><strong>Part "<?= $partName ?>" errored</strong></div>
      <div class="line">
      <a href="vscode://file/<?= ltrim($error->getFile(), '/'); ?>:<?= $error->getLine(); ?>">
            <?= preg_replace("/.*\/wp-content\/.*?\/.*?\//", './', $error->getFile()) ?>:<?= $error->getLine(); ?>
          </a><span>&nbsp;––&nbsp;</span><span class="code"><span class="function">Part()</span><span class="punctuation">-></span><span class="class"><?=$partName?></span>( <a class="param" href="#" data-log="<?=esc_attr(JSON::stringify($props));?>">...$props</a> )</span><span>&nbsp;:&nbsp;</span><span class="string"><?=$error->getMessage();?></span></span></div>
      <div class="line">
        <div class="comment">Trace:</div>
        <? 
        foreach ($trace as $k => $err) { 
          $hidden = strpos($err['file'], 'wp-content') === false || (strpos($err['file'], 'vendor') !== false);
          $label = preg_replace("/.*\/wp-content\/.*?\/.*?\//", './', $err['file']);
          ?>
        <div <?= $hidden ? "class='hidden'" : '' ?>>
          <a href="vscode://file/<?= ltrim($err["file"], '/'); ?>:<?= $err['line']; ?>">
            <?= $label; ?>:<?= $err['line']; ?>
          </a><span>&nbsp;––&nbsp;</span>
          <span class="code">
          <span class="class"><?= $err['class'] ?? ''; ?></span><span class="punctuation"><?= $err['type'] ?? ''; ?></span><span class="function"><?= $err['function'] ?></span>( 
            <a class="param" href="#" data-log="<?=esc_attr(JSON::stringify($err['args']));?>">...$args</a> )
          </span></span>
        </div>
        <? } 
        ?>
      </div>
    </div>
  </div>
  <script data-error="<?=$id?>">
    ;(function () {
      var error = document.querySelector("#<?=$id?>")
      var loggers = error.querySelectorAll('[data-log]')
      var toggle = error.querySelector(".hidden-toggle")
      toggle.onclick = function (e) {
        e.preventDefault()
        error.classList.toggle('hidden')
      }
      for (let element of loggers) {
        element.onclick = function (e) {
          e.preventDefault()
          console.log(JSON.parse(element.dataset.log))
        }
      }
    })()
  </script>
  <?
}


/* Part functions that rely on contextual data */

function partMeta () {
  $id = PartRuntime::getCurrentID();

  /* PartMeta automatically converts to html attributes when casted to string */
  return new PartMeta([
    'id' => $id,
  ]);
}

/* 
First param is always the name of the slot defaulting to "defaul"
 */
function slot ($slotName = null, ...$args) {
  $slotName = $slotName ?? "default";
  $children = PartRuntime::getCurrent()->children;

  if ((is_object($children) && !is_callable($children)) || is_array($children)) {
    $children = get($children, $slotName ?? "default", "");
  }
  if (is_callable($children)) {
    ob_start();
    echo Part($children, [], null, [], [ 'name' => "Slot_$slotName" ]);
    return ob_get_clean();
  }
  return $children;
}

function prop ($key = null, $default = null) {
  $stack = PartRuntime::getCurrent();
  return get($stack->props, $key, $default);
}

function setContext ($context) {
  return PartRuntime::setContext($context);
}

function getContext ($key = '') {
  return PartRuntime::getContext($key);
}
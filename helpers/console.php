<?php
/* 
  Helper methods for interacting with the dump command
  in dev mode
    "log" and "error" messages are shown
    "notice" and "warning" messages are hidden
  in prod mode
    all messages are hidden

  to enable log levels in production you can add a 
    "?log=warn,log,error,notice param to the url
    or just enable dev mode with "?dev=true"
*/

function dump() {
  if(error_reporting() === 0) return;

  echo "<pre># ";
  foreach(func_get_args() as $item) {
    if(is_array($item) || is_object($item)) {
      echo htmlentities(print_r($item, true));
    } else {
      echo esc_html(json_encode($item));
    }
    echo " ";
  }
  echo "</pre>";
}

if (!class_exists('Console')) {
  class Console {
    static $log = true;
    static $notice = true;
    static $warning = true;
    static $error = true;

    public static function error_log () { 
      foreach(func_get_args() as $item) {
        if(is_array($item) || is_object($item)) {
          print_r($item);
        } else {
          echo json_encode($item);
        }
        echo " ";
      }
    }
  
    /* Log to the client */
    public static function log (...$args) {
      if (self::$log) {
        dump(...$args);
      }
    }
  
    public static function notice (...$args) {
      if (self::$notice) {
        dump(...$args);
      }
    }
  
    public static function warn (...$args) {
      if (self::$warning) {
        dump(...$args);
      }
    }
  
    public static function error (...$args) {
      if (self::$error) {
        dump(...$args);
      }
    }
  
    public static function str ($arg) {
      if (is_dev()) {
        return json_encode($arg, JSON_PRETTY_PRINT);
      }
    }
  }
  
  $logs = Arr::map(
    explode(',', get($_GET, 'logs', '')),
    function ($item) { return trim($item); } 
  );
  
  Console::$log = is_dev() || Arr::includes($logs, 'log');
  Console::$notice = Arr::includes($logs, 'notice', false);
  Console::$warning = Arr::includes($logs, 'warning', false);
  Console::$error = is_dev() || Arr::includes($logs, 'error');
} else {
  error_log('Tried including helper "console" but the class already exists');
}
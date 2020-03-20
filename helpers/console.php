<?php

if (!class_exists('Console')) {
  class Console {
    static $log = true;
    static $notice = true;
    static $warning = true;
    static $error = true;
  
  
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
}
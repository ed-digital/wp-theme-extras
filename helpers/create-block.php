<?php

class Blocks {
  static $running = false;
  static $next_config = null;
  static function create ($config) {
    if (Blocks::$running) {
      Blocks::$next_config = $config;
      throw new Error("Controlled break");
    }
  }
  static function getConfig ($file) {
    Blocks::$running = true;
    ob_start();
    try {
      include($file);
    } catch (Exception $e) {
      /* Ignore errors when including blocks for registry time */
    } catch (Error $e) {
      if ($e->getMessage() !== 'Controlled break') {
        /* Ignore errors when including blocks for registry time */
      }
    }
    ob_get_clean();
    $config = Blocks::$next_config;
    Blocks::$next_config = null;
    Blocks::$running = false;
    return $config;
  }

  function __construct ($config) {
    Blocks::create($config);
  }
}

function createBlock ($config) {
  global $ED_NEXT_BLOCK;
  $ED_NEXT_BLOCK = $config;
}
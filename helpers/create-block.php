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
      console::error_log("exception including $block", $e);
    } catch (Error $e) {
      console::error_log("error including $block", $e);
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
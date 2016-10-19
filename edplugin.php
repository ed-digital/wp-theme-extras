<?php
/*
Plugin Name: ED. Plugin
Plugin URI: http://ed.com.au
Description: Provides a bunch of cool stuff for ED. Developers
Version: 1.3.56
Author: Daniel Lever
Text Domain: edplugin
License: GPLv2 or later.
Copyright: ED. Digital
*/

// Boot up
require_once(__dir__."/core/ed.php");

// Load WP-LESS sub-plugin
include_once(__dir__."/lib/wp-less/bootstrap.php");

// load underscore.php
include_once(__dir__."/lib/underscore.php");
Underscore::noConflict("_");
Underscore::noConflict("___");

// Ignore notices, unless they're in the theme dir.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
  if($errno & E_NOTICE === 0 || strpos($errfile, WP_CONTENT_DIR) === 0) {
    // Not a notice, or inside wp-content directory.
    return false;
  }
});

if(ED()->isPluginGitControlled() === false) {
	require_once("plugin-update-checker/plugin-update-checker.php");
	$myUpdateChecker = PucFactory::buildUpdateChecker('http://ed-wp-plugin.ed.com.au/info.json', __FILE__, "ed", 0);
}

<?php
/*
Plugin Name: ED. Plugin
Plugin URI: http://ed.com.au
Description: Provides a bunch of cool stuff for ED. Developers
Version: 1.2.23
Author: Daniel Lever
Text Domain: edplugin
License: GPLv2 or later.
Copyright: ED. Digital
*/

require_once("plugin-update-checker/plugin-update-checker.php");

$myUpdateChecker = PucFactory::buildUpdateChecker('http://ed-wp-plugin.ed.com.au/info.json', __FILE__, "ed", 0);

// Boot up
require_once(__dir__."/core/ed.php");

// Load WP-LESS sub-plugin
include_once(__dir__."/lib/wp-less/bootstrap.php");


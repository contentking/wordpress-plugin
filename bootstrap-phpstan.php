<?php

define('ABSPATH', '.');

/** Fake WP functions */
function add_action($par1, $par2, $par3 = NULL, $par4 = NULL) {}
function add_filter($par1, $par2) {}
function get_locale() {}
function load_textdomain($par1, $par2) {}
function plugin_dir_url($par1) {}
function register_activation_hook($par1, $par2) {}
function register_deactivation_hook($par1, $par2) {}

require_once __DIR__ . '/app/vendor/autoload.php';
require_once __DIR__ . '/app/class-wp-contentking.php';

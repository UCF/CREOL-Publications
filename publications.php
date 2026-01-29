<?php
/*
Plugin Name: Publications
Description: Gets and displays CREOL Publications.
Version: 0.0.1
Author: UCF Web Communications
License: GPL3
GitHub Plugin URI: https://github.com/UCF/CREOL-Publications
*/

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'ALL_YEARS', 0 );
define( 'ALL_TYPES', 0 );
define( 'ALL_AUTHORS', 0 );

require_once 'api/publications-rest-controller.php';
require_once 'includes/publications-feed.php';
require_once 'includes/publications-functions.php';
require_once 'includes/publications-layout.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/publications-shortcode.php';

function publications_enqueue_select2() {
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'publications_enqueue_select2');

add_shortcode( 'publications', 'publications_form_display');

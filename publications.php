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

add_shortcode( 'publications', 'publications_form_display');

<?php
// Register the new REST endpoint for HTML output.
function creol_register_publications_html_endpoint() {
    register_rest_route('publications/v1', '/html', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'creol_get_publications_html',
        'args'     => array(
            'pubyr'   => array(
                'required'          => false,
                'validate_callback' => 'absint',
            ),
            'type'    => array(
                'required'          => false,
                'validate_callback' => 'absint',
            ),
            'pubAuth' => array(
                'required'          => false,
                'validate_callback' => 'absint',
            ),
            'pg'      => array(
                'required'          => false,
                'validate_callback' => 'absint',
            ),
            'search'  => array(
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
}
add_action('rest_api_init', 'creol_register_publications_html_endpoint');

// Use output buffering to capture your styled HTML.
function creol_get_publications_html( $request ) {
    $pubyr   = $request->get_param('pubyr') ? $request->get_param('pubyr') : ALL_YEARS;
    $type    = $request->get_param('type') ? $request->get_param('type') : ALL_TYPES;
    $pubAuth = $request->get_param('pubAuth') ? $request->get_param('pubAuth') : ALL_AUTHORS;
    $page    = $request->get_param('pg') ? $request->get_param('pg') : 1;
    $search  = $request->get_param('search') ? $request->get_param('search') : "";
    
    // Capture the output of publications_display().
    ob_start();
    publications_display($pubyr, $type, $pubAuth, $page, $search);
    $html = ob_get_clean();
    
    return new WP_REST_Response($html, 200);
}
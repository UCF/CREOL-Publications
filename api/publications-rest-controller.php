<?php
// Hook into rest_api_init to register our endpoint.
function creol_register_publications_endpoint() {
    register_rest_route('publications/v1', '/list', array(
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'creol_get_publications',
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
add_action('rest_api_init', 'creol_register_publications_endpoint');

// The callback that fetches publication info via the external API.
function creol_get_publications( $request ) {
    $pubyr   = $request->get_param('pubyr') ? $request->get_param('pubyr') : ALL_YEARS;
    $type    = $request->get_param('type') ? $request->get_param('type') : ALL_TYPES;
    $pubAuth = $request->get_param('pubAuth') ? $request->get_param('pubAuth') : ALL_AUTHORS;
    $page    = $request->get_param('pg') ? $request->get_param('pg') : 1;
    $search  = $request->get_param('search') ? $request->get_param('search') : "";
    
    // Build the API URL.
    $api_url = 'https://api.creol.ucf.edu/PublicationsJson.asmx/PublicationInfo?pubyr=' . $pubyr 
        . '&pubType=' . $type 
        . '&pubAuth=' . $pubAuth 
        . '&pg=' . $page 
        . '&pubsearch=' . urlencode($search);
        
    $publications = get_json_nocache( $api_url );
    
    if ( empty( $publications ) ) {
        return new WP_REST_Response(array('message' => 'No publications found.' ), 200);
    }
    
    // Optionally, you might add pagination info here.
    return new WP_REST_Response($publications, 200);
}
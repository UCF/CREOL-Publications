<?php

function get_json_nocache( $url ) {
    $args = array(
        'timeout' => 60,
    );

    $request = wp_remote_get( $url, $args );
    $body = wp_remote_retrieve_body( $request );
    $items = json_decode( $body );
     error_log('API Response for ' . $url . ': ' . $body);

    // Defensive: check if $items and $items->response exist
    if ( isset($items->response) ) {
        return $items->response;
    } else {
        error_log("API returned invalid response for URL: $url. Body: $body");
        return array(); // or false, or handle as you wish
    }
}

function get_plain_text( $url ) {
    $args = array(
        'timeout' => 60,
    );

    $request = wp_remote_get( $url, $args );

    $response_body = wp_remote_retrieve_body( $request );

    return $response_body;
}

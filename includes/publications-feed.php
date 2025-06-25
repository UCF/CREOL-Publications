<?php

function get_json_nocache( $url ) {
    $args = array(
        'timeout' => 30,
        'headers' => array(
            'Accept' => 'application/json'
        )
    );

    $request = wp_remote_get( $url, $args );
    
    if (is_wp_error($request)) {
        error_log('WP Error in get_json_nocache: ' . $request->get_error_message());
        return array();
    }

    $body = wp_remote_retrieve_body($request);
    error_log('API Response for ' . $url . ': ' . $body);

    $data = json_decode($body);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return array();
    }

    return $data;
}

function get_plain_text( $url ) {
    $args = array(
        'timeout' => 60,
    );

    $request = wp_remote_get( $url, $args );

    $response_body = wp_remote_retrieve_body( $request );

    return $response_body;
}

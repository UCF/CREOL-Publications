<?php

function get_json_nocache( $url ) {
    $args = array(
        'timeout' => 60,
    );

    $request = wp_remote_get( $url, $args );

    $items = json_decode( wp_remote_retrieve_body( $request ) );


	// $items = json_decode( wp_remote_retrieve_body( $request ) );

	$items = array( $items->response )[0];

	return $items;
}

function get_plain_text( $url ) {
    $args = array(
        'timeout' => 60,
    );

    $request = wp_remote_get( $url, $args );

    $response_body = wp_remote_retrieve_body( $request );

    return $response_body;
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CREOL_Publications_Shortcode {
    public function __construct() {
        add_shortcode( 'creol_publications', array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode($atts) {
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );
        $defaults = array(
            'ucf_id' => '',
            'limit' => 0,
            'cache_ttl' => 0
        );
        $atts = wp_parse_args( $atts, $defaults );

        $ucf_id = sanitize_text_field( $atts['ucf_id'] );
        $limit = intval( $atts['limit'] );
        $cache_ttl = intval( $atts['cache_ttl'] );

        // Build API URL
        $params = array( 'WWWPublications' );
        if ( $ucf_id != '') {
            $params[] = 'UcfID=' . rawurlencode( $ucf_id );
        }
        if ( $limit > 0 ) {
            $params[] = 'Count=' . $limit;
        }
        
        $api_url = 'https://api.creol.ucf.edu/SqlToJson.asmx/GetData?' . implode( '&', $params );
        
        // Fetch data with optional caching
        $publications = $this->fetch_publications( $api_url, $cache_ttl );
        
        if ( is_wp_error( $publications ) ) {
            return '<p>Error fetching publications: ' . esc_html( $publications->get_error_message() ) . '</p>';
        }
        
        if ( empty( $publications ) ) {
            return '<p>There are currently no publications listed for this author.</p>';
        }
        
        // Render as plain text list
        return $this->render_plain_text( $publications );
    }
    
    private function fetch_publications( $api_url, $cache_ttl = 0 ) {
        // Generate cache key based on URL
        $cache_key = 'creol_pubs_' . md5( $api_url );
        
        // Try to get cached data if caching is enabled
        if ( $cache_ttl > 0 ) {
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }
        
        // Fetch from API
        $args = array(
            'timeout' => 60,
        );
        
        $request = wp_remote_get( $api_url, $args );
        
        if ( is_wp_error( $request ) ) {
            return $request;
        }
        
        $response_code = wp_remote_retrieve_response_code( $request );
        if ( $response_code !== 200 ) {
            return new WP_Error( 'api_error', 'API returned status code ' . $response_code );
        }
        
        $body = wp_remote_retrieve_body( $request );
        $data = json_decode( $body );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', 'Invalid JSON response from API' );
        }
        
        if ( ! isset( $data->response ) || ! is_array( $data->response ) ) {
            return new WP_Error( 'data_error', 'Unexpected API response structure' );
        }
        
        $publications = $data->response;
        
        // Cache the results if caching is enabled
        if ( $cache_ttl > 0 ) {
            set_transient( $cache_key, $publications, $cache_ttl );
        }
        
        return $publications;
    }
    
    private function render_plain_text( $publications ) {
        $output = '<div class="creol-publications-list">';
        
        foreach ( $publications as $pub ) {
            $output .= '<div class="publication-item" style="margin-bottom: 1.5em;">';
            
            // Authors
            if ( ! empty( $pub->Authors ) ) {
                $output .= esc_html( $pub->Authors );
            }
            
            // Title
            if ( ! empty( $pub->Title ) ) {
                $output .= ' "<i>' . esc_html( $pub->Title ) . '"</i>';
            }
            
            // Reference
            if ( ! empty( $pub->Reference ) ) {
                $output .= ' ' . esc_html( $pub->Reference );
            }
            
            // Year
            if ( ! empty( $pub->PublicationYear ) ) {
                $output .= ' ' . esc_html( $pub->PublicationYear );
            }
            
            // DOI
            if ( ! empty( $pub->DOI ) ) {
                $doi_url = 'https://doi.org/' . $pub->DOI;
                $output .= ' <a href="' . esc_url( $doi_url ) . '" target="_blank" rel="noopener noreferrer" class="fas fa-link"></a>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }

}

new CREOL_Publications_Shortcode();
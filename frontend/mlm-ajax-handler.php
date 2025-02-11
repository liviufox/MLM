<?php
/**
 * Frontend AJAX Handler for MLM Map Plugin
 *
 * This file handles AJAX requests such as location uploads.
 *
 * @package MLM_Map_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Process location upload via AJAX.
 */
function mlm_handle_location_upload() {
    // Verify the nonce for security. The nonce should be generated on the frontend and passed with the request.
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'mlm_location_upload_nonce' ) ) {
        wp_send_json_error( 'Invalid security token.' );
    }

    // Validate required fields.
    if ( empty( $_POST['location_name'] ) ) {
        wp_send_json_error( 'Location Name is required.' );
    }
    
    // Sanitize and retrieve form values.
    $location_name        = sanitize_text_field( $_POST['location_name'] );
    $location_description = isset( $_POST['location_description'] ) ? sanitize_textarea_field( $_POST['location_description'] ) : '';
    $location_address     = isset( $_POST['location_address'] ) ? sanitize_text_field( $_POST['location_address'] ) : '';
    
    // Additional fields (if any) can be processed similarly.
    
    // Insert a new location post.
    $post_data = array(
        'post_title'   => $location_name,
        'post_content' => $location_description,
        'post_status'  => 'publish', // Change to 'pending' if you want to review submissions.
        'post_type'    => 'mlm_location_object',
    );
    
    $post_id = wp_insert_post( $post_data );
    
    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( 'Failed to save location. Please try again later.' );
    }
    
    // Save additional post meta.
    update_post_meta( $post_id, 'location_address', $location_address );
    
    // If a location category is set, assign it.
    if ( isset( $_POST['location_category'] ) && ! empty( $_POST['location_category'] ) ) {
        $term_id = intval( $_POST['location_category'] );
        wp_set_object_terms( $post_id, $term_id, 'mlm_location_type' );
    }
    
    // Optionally process file uploads or other custom fields here.
    
    wp_send_json_success( 'Location uploaded successfully.' );
}
add_action( 'wp_ajax_mlm_location_upload', 'mlm_handle_location_upload' );
add_action( 'wp_ajax_nopriv_mlm_location_upload', 'mlm_handle_location_upload' );

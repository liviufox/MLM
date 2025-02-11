<?php
/**
 * Handles Location Upload Form Submission (Backend)
 *
 * This file processes the form submission from the location upload form.
 *
 * @package MLM_Map_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mlm_handle_location_upload_form() {
    // Check if the form was submitted.
    if ( isset( $_POST['mlm_location_upload_submit'] ) ) {
        // Verify nonce for security.
        if ( ! isset( $_POST['mlm_location_upload_nonce'] ) || ! wp_verify_nonce( $_POST['mlm_location_upload_nonce'], 'mlm_location_upload' ) ) {
            wp_die( __( 'Security check failed.', 'mlm-map-plugin' ) );
        }
        
        // Process and sanitize form inputs.
        $location_name        = isset( $_POST['location_name'] ) ? sanitize_text_field( $_POST['location_name'] ) : '';
        $location_description = isset( $_POST['location_description'] ) ? sanitize_textarea_field( $_POST['location_description'] ) : '';
        $location_address     = isset( $_POST['location_address'] ) ? sanitize_text_field( $_POST['location_address'] ) : '';
        
        if ( empty( $location_name ) ) {
            wp_die( __( 'Location Name is required.', 'mlm-map-plugin' ) );
        }
        
        // Insert a new location post.
        $post_data = array(
            'post_title'   => $location_name,
            'post_content' => $location_description,
            'post_status'  => 'publish', // Change to 'pending' if review is desired.
            'post_type'    => 'mlm_location_object',
        );
        
        $post_id = wp_insert_post( $post_data );
        
        if ( is_wp_error( $post_id ) ) {
            wp_die( __( 'Failed to save location. Please try again later.', 'mlm-map-plugin' ) );
        }
        
        // Save additional post meta.
        update_post_meta( $post_id, 'location_address', $location_address );
        
        // Assign location category if provided.
        if ( isset( $_POST['location_category'] ) && ! empty( $_POST['location_category'] ) ) {
            $term_id = intval( $_POST['location_category'] );
            wp_set_object_terms( $post_id, $term_id, 'mlm_location_type' );
        }
        
        // Optionally, process file uploads or other custom fields here.
        
        // Redirect back with a success message.
        wp_redirect( add_query_arg( 'mlm_location_upload', 'success', wp_get_referer() ) );
        exit;
    }
}
add_action( 'admin_post_mlm_location_upload', 'mlm_handle_location_upload_form' );
add_action( 'admin_post_nopriv_mlm_location_upload', 'mlm_handle_location_upload_form' );

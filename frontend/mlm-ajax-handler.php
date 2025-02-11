<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add AJAX actions
add_action('wp_ajax_save_location', 'mlm_save_location');
add_action('wp_ajax_nopriv_save_location', 'mlm_save_location');

function mlm_save_location() {
    // Debug
    error_log('MLM: save_location AJAX handler called');
    error_log('POST data: ' . print_r($_POST, true));

    // Verify nonce
    if (!check_ajax_referer('mlm_location_nonce', 'nonce', false)) {
        error_log('MLM: Nonce verification failed');
        wp_send_json_error('Security check failed');
        return;
    }

    // Validate required fields
    $required_fields = array('title', 'latitude', 'longitude', 'location_type');
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            error_log("MLM: Missing required field: $field");
            wp_send_json_error("Missing required field: $field");
            return;
        }
    }

    // Sanitize and collect data
    $location_data = array(
        'title' => sanitize_text_field($_POST['title']),
        'description' => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
        'latitude' => floatval($_POST['latitude']),
        'longitude' => floatval($_POST['longitude']),
        'location_type' => sanitize_text_field($_POST['location_type']),
        'user_id' => get_current_user_id()
    );

    // Debug
    error_log('MLM: Sanitized location data: ' . print_r($location_data, true));

    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'mlm_locations';
    
    $result = $wpdb->insert(
        $table_name,
        $location_data,
        array(
            '%s', // title
            '%s', // description
            '%f', // latitude
            '%f', // longitude
            '%s', // location_type
            '%d'  // user_id
        )
    );

    if ($result === false) {
        error_log('MLM: Database error: ' . $wpdb->last_error);
        wp_send_json_error('Failed to save location: Database error');
        return;
    }

    $location_id = $wpdb->insert_id;
    error_log('MLM: Location saved successfully. ID: ' . $location_id);

    // Handle image upload if present
    if (!empty($_FILES['location_image'])) {
        $uploaded_file = $_FILES['location_image'];
        $upload_overrides = array('test_form' => false);
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            error_log('MLM: File upload successful: ' . print_r($movefile, true));
            
            // Update location with image URL
            $wpdb->update(
                $table_name,
                array('image_url' => $movefile['url']),
                array('id' => $location_id),
                array('%s'),
                array('%d')
            );
        } else {
            error_log('MLM: File upload error: ' . $movefile['error']);
        }
    }

    // Send success response
    wp_send_json_success(array(
        'message' => 'Location saved successfully',
        'location_id' => $location_id
    ));
}

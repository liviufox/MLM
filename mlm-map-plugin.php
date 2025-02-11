<?php
/**
 * Plugin Name: MLM Map Plugin
 * Description: A plugin to manage locations and custom location types with custom fields.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: mlm-map-plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =====================================================
   Define Constants
===================================================== */
define( 'MLM_MAP_PLUGIN_VERSION', '1.0' );
define( 'MLM_MAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MLM_MAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/* =====================================================
   Enqueue Frontend Scripts and Styles
===================================================== */
function mlm_enqueue_scripts() {
    wp_enqueue_style( 'mlm-style', MLM_MAP_PLUGIN_URL . 'css/mlm-style.css', array(), MLM_MAP_PLUGIN_VERSION );
    
    // Enqueue Google Maps API with the API key from settings.
    $api_key = get_option( 'mlm_google_api_key', '' );
    wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=places', array(), null, true );
    wp_enqueue_script( 'mlm-custom-js', MLM_MAP_PLUGIN_URL . 'js/mlm-custom.js', array( 'jquery', 'google-maps' ), MLM_MAP_PLUGIN_VERSION, true );
    wp_enqueue_script( 'mlm-location-upload-js', MLM_MAP_PLUGIN_URL . 'js/mlm-location-upload.js', array( 'jquery' ), MLM_MAP_PLUGIN_VERSION, true );
    
    // Localize the script with the AJAX URL and a nonce for security.
    wp_localize_script( 'mlm-custom-js', 'mlm_ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mlm_location_upload_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'mlm_enqueue_scripts' );

/* =====================================================
   Business hours shortcode
===================================================== */
include_once MLM_MAP_PLUGIN_DIR . 'frontend/mlm-business-hours-shortcode.php';

/* =====================================================
   Enqueue Admin Scripts and Styles
===================================================== */
if ( is_admin() ) {
    function mlm_admin_enqueue_scripts() {
        wp_enqueue_style( 'mlm-admin-style', MLM_MAP_PLUGIN_URL . 'css/mlm-admin.css', array(), MLM_MAP_PLUGIN_VERSION );
        wp_enqueue_script( 'mlm-admin-js', MLM_MAP_PLUGIN_URL . 'js/mlm-admin.js', array( 'jquery' ), MLM_MAP_PLUGIN_VERSION, true );
        
    }
    add_action( 'admin_enqueue_scripts', 'mlm_admin_enqueue_scripts' );
}

/* =====================================================
   Include Plugin Files
===================================================== */
// Admin Files (only loaded in the admin area)
if ( is_admin() ) {
    require_once MLM_MAP_PLUGIN_DIR . 'admin/settings.php';
    require_once MLM_MAP_PLUGIN_DIR . 'admin/location-upload-handler.php';
}

// Frontend Files
require_once MLM_MAP_PLUGIN_DIR . 'frontend/mlm-map-shortcode.php';
require_once MLM_MAP_PLUGIN_DIR . 'frontend/mlm-ajax-handler.php';

/* =====================================================
   Register Custom Post Type: Locations
===================================================== */
function mlm_register_location_post_type() {
    $labels = array(
        'name'               => __( 'Locations', 'mlm-map-plugin' ),
        'singular_name'      => __( 'Location', 'mlm-map-plugin' ),
        'add_new'            => __( 'Add New', 'mlm-map-plugin' ),
        'add_new_item'       => __( 'Add New Location', 'mlm-map-plugin' ),
        'edit_item'          => __( 'Edit Location', 'mlm-map-plugin' ),
        'new_item'           => __( 'New Location', 'mlm-map-plugin' ),
        'view_item'          => __( 'View Location', 'mlm-map-plugin' ),
        'search_items'       => __( 'Search Locations', 'mlm-map-plugin' ),
        'not_found'          => __( 'No locations found', 'mlm-map-plugin' ),
        'not_found_in_trash' => __( 'No locations found in Trash', 'mlm-map-plugin' ),
        'menu_name'          => __( 'Locations', 'mlm-map-plugin' ),
    );
    
    $args = array(
        'labels'       => $labels,
        'public'       => true,
        'has_archive'  => true,
        'supports'     => array( 'title', 'editor', 'thumbnail', 'comments' ),
        'rewrite'      => array( 'slug' => 'locations' ),
    );
    
    register_post_type( 'mlm_location_object', $args );
}
add_action( 'init', 'mlm_register_location_post_type' );

/* =====================================================
   Register Custom Taxonomy: Location Types
===================================================== */
function mlm_register_location_taxonomy() {
    $labels = array(
        'name'              => _x( 'Location Types', 'taxonomy general name', 'mlm-map-plugin' ),
        'singular_name'     => _x( 'Location Type', 'taxonomy singular name', 'mlm-map-plugin' ),
        'search_items'      => __( 'Search Location Types', 'mlm-map-plugin' ),
        'all_items'         => __( 'All Location Types', 'mlm-map-plugin' ),
        'parent_item'       => __( 'Parent Location Type', 'mlm-map-plugin' ),
        'parent_item_colon' => __( 'Parent Location Type:', 'mlm-map-plugin' ),
        'edit_item'         => __( 'Edit Location Type', 'mlm-map-plugin' ),
        'update_item'       => __( 'Update Location Type', 'mlm-map-plugin' ),
        'add_new_item'      => __( 'Add New Location Type', 'mlm-map-plugin' ),
        'new_item_name'     => __( 'New Location Type Name', 'mlm-map-plugin' ),
        'menu_name'         => __( 'Location Types', 'mlm-map-plugin' ),
    );
    
    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'location-type' ),
    );
    
    register_taxonomy( 'mlm_location_type', array( 'mlm_location_object' ), $args );
}
add_action( 'init', 'mlm_register_location_taxonomy' );

/* =====================================================
   Admin Menus: Map Settings and Location Type Fields
===================================================== */
function mlm_add_admin_menus() {
    // Top-level menu for Map Settings.
    add_menu_page(
        __( 'Map Settings', 'mlm-map-plugin' ),
        __( 'Map Settings', 'mlm-map-plugin' ),
        'manage_options',
        'mlm-settings',
        'mlm_settings_page',
        'dashicons-location-alt'
    );
    
    // Submenu for Location Type Fields.
    add_submenu_page(
        'mlm-settings',
        __( 'Location Type Fields', 'mlm-map-plugin' ),
        __( 'Location Type Fields', 'mlm-map-plugin' ),
        'manage_options',
        'mlm-location-type-fields',
        'mlm_location_type_fields_admin_page'
    );
}
add_action( 'admin_menu', 'mlm_add_admin_menus' );

/* =====================================================
   Load Admin-Only Files
------------------------------------------------------
   This block checks if the current request is coming
   from the WordPress admin area (dashboard). If so, it
   includes the admin-specific file 'taxonomy-fields.php'
   from our plugin directory. This file adds extra fields
   (like the Icon URL field) to the built-in taxonomy 
   management screens for the 'mlm_location_type' taxonomy.
   
   Using is_admin() ensures that this code is only loaded 
   on the backend, preventing unnecessary code execution 
   on the frontend. The require_once function guarantees 
   the file is included only once, avoiding potential 
   redeclaration errors.
===================================================== */
if ( is_admin() ) {
    require_once MLM_MAP_PLUGIN_DIR . 'admin/taxonomy-fields.php';
}

/* =====================================================
   Admin-Only File Inclusion
-----------------------------------------------------
   This code checks whether the current request is made from
   the WordPress admin area by using the is_admin() function.
   If it returns true (i.e., we are in the admin dashboard),
   the code then includes the file "taxonomy-fields.php" from the
   plugin's admin directory. This file extends the default taxonomy
   add/edit screens for the "mlm_location_type" taxonomy by adding
   extra fields such as the Icon URL (with a media uploader) and a
   dynamic table for custom field definitions.
   
   Including this file only on admin pages improves performance
   and ensures that the extra fields are loaded only where needed.
===================================================== */
if ( is_admin() ) {
    require_once MLM_MAP_PLUGIN_DIR . 'admin/taxonomy-fields.php';
}


/* =====================================================
   Register Shortcode for Map Display
===================================================== */
function mlm_init_shortcode() {
    add_shortcode( 'mlm_map', 'mlm_map_shortcode' );
}
add_action( 'init', 'mlm_init_shortcode' );

/* =====================================================
   AJAX Callback: Fetch Term Specific Fields
===================================================== */
/**
 * AJAX callback to fetch term-specific fields for a location type.
 */
function mlm_get_term_fields_callback() {
    // Check for a valid type_id.
    if ( empty( $_POST['type_id'] ) ) {
        wp_send_json_error( 'Invalid type ID.' );
    }
    
    $type_id = intval( $_POST['type_id'] );
    
    // Retrieve custom field definitions for this term if available.
    $fields_definitions = get_term_meta( $type_id, 'mlm_fields_definitions', true );
    
    ob_start();

if ( ! empty( $fields_definitions ) ) {
    foreach ( $fields_definitions as $field ) {
        // For clarity:
        // $field['label']   -> the label for the custom field
        // $field['metakey'] -> meta key name (like 'typeofconstructionsites')
        // $field['type']    -> text, checkbox, radio, select, textarea, etc.
        // $field['values']  -> text lines containing options (for checkboxes, radio, or select)

        // Print a label
        echo '<p style="margin-bottom:8px;"><strong>' . esc_html( $field['label'] ) . '</strong></p>';

        switch ( $field['type'] ) {
            case 'checkbox':
                // Example: each line in $field['values'] might be "Residential|Residential"
                // Let's split by new line and then split by "|"
                $lines = preg_split('/[\r\n]+/', $field['values']);
                if ( is_array($lines) ) {
                    foreach ( $lines as $line ) {
                        $line = trim( $line );
                        if ( empty( $line ) ) {
                            continue;
                        }
                        $parts = explode('|', $line);
                        $value = isset($parts[0]) ? trim($parts[0]) : '';
                        // If second part is missing, we just show the same text
                        $display_text = isset($parts[1]) ? trim($parts[1]) : $value;

                        echo '<label style="display:inline-block; margin-right:10px;">';
                        echo '<input type="checkbox" name="' . esc_attr( $field['metakey'] ) . '[]" value="' . esc_attr( $value ) . '" />';
                        echo ' ' . esc_html( $display_text ) . '</label>';
                    }
                }
                break;

            case 'radio':
                // Similar to checkbox, but only one can be selected
                $lines = preg_split('/[\r\n]+/', $field['values']);
                if ( is_array($lines) ) {
                    foreach ( $lines as $line ) {
                        $line = trim( $line );
                        if ( empty( $line ) ) {
                            continue;
                        }
                        $parts = explode('|', $line);
                        $value = isset($parts[0]) ? trim($parts[0]) : '';
                        $display_text = isset($parts[1]) ? trim($parts[1]) : $value;

                        echo '<label style="display:inline-block; margin-right:10px;">';
                        echo '<input type="radio" name="' . esc_attr( $field['metakey'] ) . '" value="' . esc_attr( $value ) . '" />';
                        echo ' ' . esc_html( $display_text ) . '</label>';
                    }
                }
                break;

            case 'select':
                // Output a <select> with <option>
                $lines = preg_split('/[\r\n]+/', $field['values']);
                echo '<select name="' . esc_attr( $field['metakey'] ) . '">';
                if ( is_array($lines) ) {
                    foreach ( $lines as $line ) {
                        $line = trim( $line );
                        if ( empty( $line ) ) {
                            continue;
                        }
                        $parts = explode('|', $line);
                        $value = isset($parts[0]) ? trim($parts[0]) : '';
                        $display_text = isset($parts[1]) ? trim($parts[1]) : $value;

                        echo '<option value="' . esc_attr($value) . '">' . esc_html($display_text) . '</option>';
                    }
                }
                echo '</select>';
                break;

            case 'textarea':
                echo '<textarea name="' . esc_attr( $field['metakey'] ) . '" rows="4" cols="40"></textarea>';
                break;

            case 'text':
            case 'number':
            default:
                // If it's "text" or "number", we’ll output a simple input
                $type_attr = ($field['type'] === 'number') ? 'number' : 'text';
                echo '<input type="' . esc_attr($type_attr) . '" name="' . esc_attr( $field['metakey'] ) . '" />';
                break;
        }

        // Add a little spacing after each field
        echo '<br><br>';
    }
} else {
    echo '<p>No additional fields available for this type.</p>';
}

$response_html = ob_get_clean();

// Return the final HTML to the AJAX call
wp_send_json_success( $response_html );
}
add_action( 'wp_ajax_mlm_get_term_fields', 'mlm_get_term_fields_callback' );
add_action( 'wp_ajax_nopriv_mlm_get_term_fields', 'mlm_get_term_fields_callback' );

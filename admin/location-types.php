<?php
/**
 * Admin interface for managing custom field definitions for Location Types.
 *
 * This file is loaded in the admin area and is included from the main plugin file.
 *
 * @package MLM_Map_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the admin page for editing custom field definitions for a given location type.
 */
function mlm_location_type_fields_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'mlm-map-plugin' ) );
    }
    
    // Get the current action and term_id from the query string.
    $action  = isset( $_GET['action'] ) ? $_GET['action'] : '';
    $term_id = isset( $_GET['term_id'] ) ? intval( $_GET['term_id'] ) : 0;
    
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Location Type Fields', 'mlm-map-plugin' ) . '</h1>';
    
    if ( 'edit' === $action && $term_id ) {
        $term = get_term( $term_id, 'mlm_location_type' );
        if ( is_wp_error( $term ) || ! $term ) {
            echo '<div class="error"><p>' . esc_html__( 'Invalid term.', 'mlm-map-plugin' ) . '</p></div>';
        } else {
            // Process form submission.
            if ( isset( $_POST['submit'] ) ) {
                check_admin_referer( 'mlm_save_location_type_fields_' . $term_id );
    
                $name        = sanitize_text_field( $_POST['term_name'] );
                $description = sanitize_textarea_field( $_POST['term_description'] );
                $fields_json = wp_unslash( $_POST['fields_definitions'] );
    
                // Decode JSON into an array. If invalid, keep the raw string.
                $fields_array = json_decode( $fields_json, true );
                if ( json_last_error() !== JSON_ERROR_NONE ) {
                    $fields_array = $fields_json;
                }
    
                // Update the term.
                wp_update_term( $term_id, 'mlm_location_type', array(
                    'name'        => $name,
                    'description' => $description,
                ) );
    
                // Save the custom fields definitions as term meta.
                update_term_meta( $term_id, 'mlm_fields_definitions', $fields_array );
    
                echo '<div class="updated"><p>' . esc_html__( 'Location type updated.', 'mlm-map-plugin' ) . '</p></div>';
    
                // Reload the term after update.
                $term = get_term( $term_id, 'mlm_location_type' );
            }
    
            // Retrieve existing custom fields definitions.
            $fields_definitions = get_term_meta( $term_id, 'mlm_fields_definitions', true );
            $fields_json        = '';
            if ( ! empty( $fields_definitions ) ) {
                $fields_json = json_encode( $fields_definitions, JSON_PRETTY_PRINT );
            }
            ?>
            <h2><?php printf( esc_html__( 'Edit Location Type: %s', 'mlm-map-plugin' ), esc_html( $term->name ) ); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'mlm_save_location_type_fields_' . $term_id ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="term_name"><?php esc_html_e( 'Name', 'mlm-map-plugin' ); ?></label></th>
                        <td>
                            <input name="term_name" type="text" id="term_name" value="<?php echo esc_attr( $term->name ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="term_description"><?php esc_html_e( 'Description', 'mlm-map-plugin' ); ?></label></th>
                        <td>
                            <textarea name="term_description" id="term_description" rows="5" class="large-text"><?php echo esc_textarea( $term->description ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fields_definitions"><?php esc_html_e( 'Custom Fields Definitions (JSON)', 'mlm-map-plugin' ); ?></label></th>
                        <td>
                            <textarea name="fields_definitions" id="fields_definitions" rows="10" class="large-text code"><?php echo esc_textarea( $fields_json ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Enter the custom fields definitions in JSON format. For example, a JSON array of field definitions with keys like "label", "metakey", "type", and "values".', 'mlm-map-plugin' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( esc_html__( 'Save Changes', 'mlm-map-plugin' ) ); ?>
            </form>
            <p>
                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=mlm_location_type&post_type=mlm_location_object' ) ); ?>">
                    <?php esc_html_e( 'Back to Location Types', 'mlm-map-plugin' ); ?>
                </a>
            </p>
            <?php
        }
    } else {
        echo '<p>' . esc_html__( 'To manage custom fields for a location type, please use the built-in Location Types screen (under Locations) and click "Edit" on a location type.', 'mlm-map-plugin' ) . '</p>';
    }
    
    echo '</div>';
}

<?php
/**
 * Plugin Settings Page for MLM Map Plugin
 *
 * This file defines the settings page for the plugin, such as saving the Google Maps API key.
 *
 * @package MLM_Map_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mlm_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'mlm-map-plugin' ) );
    }
    
    // Process form submission for settings.
    if ( isset( $_POST['mlm_settings_nonce'] ) && wp_verify_nonce( $_POST['mlm_settings_nonce'], 'mlm_save_settings' ) ) {
        $api_key = sanitize_text_field( $_POST['mlm_google_api_key'] );
        update_option( 'mlm_google_api_key', $api_key );
        echo '<div class="updated"><p>' . esc_html__( 'Settings saved', 'mlm-map-plugin' ) . '</p></div>';
    }
    
    $api_key = get_option( 'mlm_google_api_key', '' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Map Settings', 'mlm-map-plugin' ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'mlm_save_settings', 'mlm_settings_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Google Maps API Key', 'mlm-map-plugin' ); ?></th>
                    <td>
                        <input type="text" name="mlm_google_api_key" value="<?php echo esc_attr( $api_key ); ?>" style="width:400px;" />
                        <p class="description"><?php esc_html_e( 'Enter your Google Maps API key.', 'mlm-map-plugin' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

<?php
/**
 * Admin interface for managing Location Categories.
 *
 * This page provides a custom interface for adding, editing, and deleting
 * terms from your custom taxonomy 'mlm_location_type'.
 *
 * @package MLM_Map_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the Manage Location Categories admin page.
 */
function mlm_manage_location_categories_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mlm-map-plugin' ) );
    }
    
    // Process "Add New Category" form submission.
    if ( isset( $_POST['mlm_add_category_nonce'] ) && wp_verify_nonce( $_POST['mlm_add_category_nonce'], 'mlm_add_category' ) ) {
        $new_category = sanitize_text_field( $_POST['new_category'] );
        if ( ! empty( $new_category ) ) {
            $inserted = wp_insert_term( $new_category, 'mlm_location_type' );
            if ( is_wp_error( $inserted ) ) {
                echo '<div class="error"><p>' . esc_html( $inserted->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="updated"><p>' . esc_html__( 'Category added successfully.', 'mlm-map-plugin' ) . '</p></div>';
            }
        }
    }
    
    // Process deletion if requested.
    if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['term_id'] ) ) {
        $term_id = intval( $_GET['term_id'] );
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'mlm_delete_category_' . $term_id ) ) {
            $deleted = wp_delete_term( $term_id, 'mlm_location_type' );
            if ( is_wp_error( $deleted ) ) {
                echo '<div class="error"><p>' . esc_html( $deleted->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="updated"><p>' . esc_html__( 'Category deleted successfully.', 'mlm-map-plugin' ) . '</p></div>';
            }
        } else {
            echo '<div class="error"><p>' . esc_html__( 'Nonce verification failed. Category was not deleted.', 'mlm-map-plugin' ) . '</p></div>';
        }
    }
    
    // Retrieve all terms (even those without posts)
    $terms = get_terms( array(
        'taxonomy'   => 'mlm_location_type',
        'hide_empty' => false,
    ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Manage Location Categories', 'mlm-map-plugin' ); ?></h1>
        
        <!-- Add New Category Form -->
        <h2><?php esc_html_e( 'Add New Category', 'mlm-map-plugin' ); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'mlm_add_category', 'mlm_add_category_nonce' ); ?>
            <input type="text" name="new_category" placeholder="<?php esc_attr_e( 'Category Name', 'mlm-map-plugin' ); ?>" required />
            <?php submit_button( esc_html__( 'Add Category', 'mlm-map-plugin' ) ); ?>
        </form>
        
        <!-- List Existing Categories -->
        <h2><?php esc_html_e( 'Existing Categories', 'mlm-map-plugin' ); ?></h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'ID', 'mlm-map-plugin' ); ?></th>
                    <th><?php esc_html_e( 'Name', 'mlm-map-plugin' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'mlm-map-plugin' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        echo '<tr>';
                        echo '<td>' . esc_html( $term->term_id ) . '</td>';
                        echo '<td>' . esc_html( $term->name ) . '</td>';
                        echo '<td>';
                        // Link to the built-in edit page for terms.
                        $edit_link = admin_url( 'edit-tags.php?taxonomy=mlm_location_type&post_type=mlm_location_object&tag_ID=' . intval( $term->term_id ) . '&action=edit' );
                        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'mlm-map-plugin' ) . '</a> | ';
                        
                        // Delete link with nonce.
                        $delete_link = add_query_arg( array(
                            'page'    => 'mlm-location-categories',
                            'action'  => 'delete',
                            'term_id' => $term->term_id,
                            '_wpnonce'=> wp_create_nonce( 'mlm_delete_category_' . $term->term_id )
                        ), admin_url( 'admin.php' ) );
                        echo '<a href="' . esc_url( $delete_link ) . '" onclick="return confirm(\'' . esc_js( __( 'Are you sure you want to delete this category?', 'mlm-map-plugin' ) ) . '\');">' . esc_html__( 'Delete', 'mlm-map-plugin' ) . '</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">' . esc_html__( 'No categories found.', 'mlm-map-plugin' ) . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}
 
// Register the submenu page for location categories.
function mlm_add_location_categories_menu() {
    add_submenu_page(
        'mlm-settings', // Parent slug (the top-level menu)
        __( 'Location Categories', 'mlm-map-plugin' ),
        __( 'Location Categories', 'mlm-map-plugin' ),
        'manage_options',
        'mlm-location-categories',
        'mlm_manage_location_categories_page'
    );
}
add_action( 'admin_menu', 'mlm_add_location_categories_menu' );

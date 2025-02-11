<?php
/**
 * Frontend Map Shortcode
 *
 * This file contains the mlm_map_shortcode() function which outputs the map interface,
 * location upload form, and a tabbed interface for selecting a Location Type.
 *
 * Custom fields defined for each location type are loaded via AJAX into a dedicated container.
 *
 * @package MLM_Map_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mlm_map_shortcode() {
    

    ob_start();
    ?>
    <div class="mlm_map_wrapper">
        <!-- SEARCH CONTAINER -->
        <div class="mlm_search_container">
            <span class="dashicons dashicons-search searchicon"></span>
            <input type="search" placeholder="<?php esc_attr_e( 'Enter the address', 'mlm-map-plugin' ); ?>" class="searchinput" id="mlm_map_search_input" />
            <span class="dashicons dashicons-no clear_search" id="clear_search"></span>
        </div>
        
        <!-- MAP CONTAINER -->
        <div class="mlm_map_container" style="position:relative;">
            <div id="mlm_map_canvas" style="width: 100%; height: 400px;"></div>
            <!-- CROSSHAIR OVERLAY -->
            <div class="crosshair" id="mlm_crosshair"></div>
        </div>
        
        <!-- INSTRUCTIONS AND CONFIRM BUTTON -->
        <div class="mlm_map_instructions" style="text-align:center; margin-top:10px;">
            <div class="instruction-green-container">
                <p id="map_instructions">
                    <?php esc_html_e( 'Move the map until the target marker is directly over the new location, zoom in until hybrid view is enabled to confirm the location', 'mlm-map-plugin' ); ?>
                </p>
            </div>
            <button id="confirm_location" disabled>
                <?php esc_html_e( 'Confirm Location', 'mlm-map-plugin' ); ?>
            </button>
        </div>
        
        <!-- EXTRA FIELDS (HIDDEN UNTIL CONFIRMATION) -->
        <div class="mlm_extra_fields" style="display:none; margin-top:20px;">
            <h3><?php esc_html_e( 'Location Details', 'mlm-map-plugin' ); ?></h3>
            <form id="mlm_location_form">
                <!-- Location Name (Mandatory) -->
                <p>
                    <label for="location_name">
                        <?php esc_html_e( 'Location Name', 'mlm-map-plugin' ); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="location_name" name="location_name" required />
                </p>
                
                <!-- Location Description -->
                <p>
                    <label for="location_description"><?php esc_html_e( 'Location Description', 'mlm-map-plugin' ); ?></label>
                    <textarea id="location_description" name="location_description"></textarea>
                </p>
                
                <!-- LOCATION TYPE SELECTOR -->
                <div class="mlm_form_row">
                    <label><?php esc_html_e( 'Location Type', 'mlm-map-plugin' ); ?> <span class="required">*</span></label>
                    <div class="mlm_message">
                        <span class="dashicons dashicons-sticky"></span>
                        <?php esc_html_e( 'Select the type of location you wish to add. Only one type can be selected. Please choose the location type carefully. It cannot be changed later!', 'mlm-map-plugin' ); ?>
                    </div>
                    <?php
                    // Get parent categories (terms with no parent) from the mlm_location_type taxonomy.
                    $parent_categories = get_terms( array(
                        'taxonomy'   => 'mlm_location_type',
                        'hide_empty' => false,
                        'parent'     => 0,
                    ) );
                    
                    if ( ! empty( $parent_categories ) && ! is_wp_error( $parent_categories ) ) {
                        echo '<div class="mlm_tab">';
                        $cat_counter = 0;
                        foreach ( $parent_categories as $ptype ) {
                            if ( $cat_counter === 0 ) {
                                echo '<a class="mlm_tablinks" href="javascript:void(0);" onclick="openMlmTab(event, \'' . esc_js( $ptype->slug ) . '\')" id="mlm_defaultTabOpen">' . esc_html( $ptype->name ) . '</a>';
                            } else {
                                echo '<a class="mlm_tablinks" href="javascript:void(0);" onclick="openMlmTab(event, \'' . esc_js( $ptype->slug ) . '\')">' . esc_html( $ptype->name ) . '</a>';
                            }
                            $cat_counter++;
                        }
                        echo '</div>';
                        
                        // Loop through each parent category to display its child categories.
                        foreach ( $parent_categories as $ptype ) {
                            echo '<div id="' . esc_attr( $ptype->slug ) . '" class="mlm_tabcontent">';
                            
                            $child_categories = get_terms( array(
                                'taxonomy'   => 'mlm_location_type',
                                'orderby'    => 'name',
                                'order'      => 'ASC',
                                'hide_empty' => false,
                                'parent'     => $ptype->term_id,
                            ) );
                            
                            if ( ! empty( $child_categories ) && ! is_wp_error( $child_categories ) ) {
                                foreach ( $child_categories as $ctype ) {
                                    // Default icon path; adjust if needed.
                                    // Set a default icon URL
                                    // Check if this term has a custom icon in meta:
                                         $term_icon = get_term_meta( $ctype->term_id, 'mlm_location_type_icon', true );

                                           if ( ! empty( $term_icon ) ) {
                                             // Found a stored icon for this term
                                             $type_icon = esc_url( $term_icon );
                                           } else {
                                              // Otherwise, fallback to default
                                             $type_icon = MLM_MAP_PLUGIN_URL . 'admin/img/default.png';
                                                 }
                                    echo '<div class="cat_row">';
                                    echo '<img src="' . esc_url( $type_icon ) . '" alt="' . esc_attr( $ctype->name ) . '" />';
                                    // Pre-selection: ensure $mlm_form_type and $current_location_child_id are set if needed.
                                    $checked = ( isset( $mlm_form_type ) && $mlm_form_type == $ctype->term_id ) ? 'checked' : '';
                                    echo '<input type="radio" name="mlm_form_type" class="mlm_form_type" value="' . esc_attr( $ctype->term_id ) . '" data-locationchildid="' . ( isset( $current_location_child_id ) ? esc_attr( $current_location_child_id ) : '' ) . '" ' . $checked . ' required />';
                                    echo '<span>' . esc_html( $ctype->name ) . '
                                   <img src="' . MLM_MAP_PLUGIN_URL . 'admin/img/spinner.gif" style="display:none;" />
                                   </span>';
                                    echo '</div>';
                                }
                            }
                            echo '</div>';
                        }
                    }
                    ?>
                    <!-- Hidden field for storing the selected icon URL -->
                    <input type="hidden" id="mlm_form_icon" name="mlm_form_icon" value="" />
                    <!-- Container for dynamic term fields loaded via AJAX -->
                    <div class="mlm_form_type_fields">
    <?php
    // Check if custom fields exist for this term
    $fields_definitions = get_term_meta( $ctype->term_id, 'mlm_fields_definitions', true );

    if ( ! empty( $fields_definitions ) ) {
        foreach ( $fields_definitions as $field ) {
            echo '<label>' . esc_html( $field['label'] ) . '</label>';
            if ( 'checkbox' === $field['type'] ) {
                $values = explode('|', $field['values']);
                foreach ( $values as $value ) {
                    echo '<label><input type="checkbox" name="' . esc_attr( $field['metakey'] ) . '[]" value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</label>';
                }
            }
            // Add support for other field types if necessary (e.g., text, radio)
        }
    } else {
        echo '<p>' . esc_html__( 'No additional fields available for this type.', 'mlm-map-plugin' ) . '</p>';
    }
    ?>
</div>
                </div>
                
                <!-- Auto-Populated Address Field -->
                <p>
                    <label for="location_address"><?php esc_html_e( 'Address', 'mlm-map-plugin' ); ?></label>
                    <input type="text" id="location_address" name="location_address" readonly />
                </p>
                
                <!-- Contact Details -->
                <fieldset>
                    <legend><?php esc_html_e( 'Contact Details', 'mlm-map-plugin' ); ?></legend>
                    <p>
                        <label for="location_website"><?php esc_html_e( 'Website', 'mlm-map-plugin' ); ?></label>
                        <input type="text" id="location_website" name="location_website" />
                    </p>
                    <p>
                        <label for="location_email"><?php esc_html_e( 'Email', 'mlm-map-plugin' ); ?></label>
                        <input type="email" id="location_email" name="location_email" />
                    </p>
                    <p>
                        <label for="location_phone"><?php esc_html_e( 'Phone', 'mlm-map-plugin' ); ?></label>
                        <input type="text" id="location_phone" name="location_phone" />
                    </p>
                    <p>
                        <label for="location_phone2"><?php esc_html_e( 'Second Phone', 'mlm-map-plugin' ); ?></label>
                        <input type="text" id="location_phone2" name="location_phone2" />
                    </p>
                    <p>
                        <label for="location_facebook"><?php esc_html_e( 'Facebook', 'mlm-map-plugin' ); ?></label>
                        <input type="text" id="location_facebook" name="location_facebook" />
                    </p>
                </fieldset>
                
                <!-- Business Hours -->
                <!-- Render the business hours form using the new shortcode -->
                <h3><?php esc_html_e('Business Hours', 'mlm-map-plugin'); ?></h3>
                   <?php echo do_shortcode('[mlm_business_hours]'); ?>
                
                <!-- Custom Fields Container (if additional fields need to be inserted manually) -->
                <div id="custom_fields">
                    <!-- Custom fields from other sources may be inserted here. -->
                </div>
                
                <!-- Pictures Upload -->
                <p>
                    <label><?php esc_html_e( 'Pictures', 'mlm-map-plugin' ); ?></label>
                    <input type="file" 
                    id="location_pictures" 
                    name="location_media[]" 
                    accept="image/*,video/*" 
                    multiple 
                    data-max-files="5" />
                </p>
                
                
                <!-- Preview Container -->
                <div id="location_pictures_preview"></div>
                
                <!-- Terms and Conditions -->
                <p>
                    <label>
                        <input type="checkbox" id="terms_conditions" name="terms_conditions" required />
                        <?php esc_html_e( 'I agree to the terms and conditions', 'mlm-map-plugin' ); ?>
                    </label>
                </p>
                
                <!-- Submit Button -->
                <p>
                    <input type="submit" value="<?php esc_attr_e( 'Submit Location', 'mlm-map-plugin' ); ?>" />
                </p>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

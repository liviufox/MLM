<?php
/**
 * Adds extra fields to the mlm_location_type taxonomy add and edit screens.
 *
 * This file extends the default WordPress taxonomy management screens for
 * the "mlm_location_type" taxonomy by adding an "Icon URL" field (with a media uploader)
 * and a dynamic "Custom Fields" interface. The custom fields let you define additional data
 * for each location type (such as Label, Meta Key, Field Type, and Values) that can later
 * be used in the front-end location upload form.
 *
 * The extra fields are saved as term meta under:
 *    - "mlm_location_type_icon" for the Icon URL.
 *    - "mlm_fields_definitions" for the custom field definitions.
 *
 * This version also enables drag-and-drop reordering of the custom fields rows
 * using jQuery UI Sortable.
 *
 * @package MLM_Map_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =====================================================
   Add Extra Fields to the "Add New Location Type" Form
----------------------------------------------------- */
/*
   This function adds extra input fields to the default "Add New" form for the mlm_location_type taxonomy.
   It includes:
     - An Icon URL field with an "Upload Icon" button and preview.
     - A Custom Fields table that starts with one row. Each row contains:
         - Label
         - Meta Key (alphanumeric)
         - Type (text, number, radio, checkbox, select, or textarea)
         - Values (each option on a new line; if using "Option|Text", separate with a pipe)
         - An "Action" column with a link to remove the row.
   At the end, we enable drag-and-drop reordering on the custom fields table body.
*/
function mlm_location_type_add_form_fields_callback( $taxonomy ) {
    ?>
    <div class="form-field term-group">
        <label for="mlm_location_type_icon"><?php _e('Icon URL', 'mlm-map-plugin'); ?></label>
        <input type="text" id="mlm_location_type_icon" name="mlm_location_type_icon" value="">
        <button type="button" class="upload_image_button button"><?php _e('Upload Icon', 'mlm-map-plugin'); ?></button>
        <div id="mlm_location_type_icon_preview" style="margin-top:10px; display:none;"></div>
    </div>
    <div class="form-field term-group">
        <label><?php _e('Custom Fields', 'mlm-map-plugin'); ?></label>
        <table id="mlm_custom_fields_table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th><?php _e('Label', 'mlm-map-plugin'); ?></th>
                    <th><?php _e('Meta Key (alphanumeric)', 'mlm-map-plugin'); ?></th>
                    <th><?php _e('Type', 'mlm-map-plugin'); ?></th>
                    <th><?php _e('Values (Each option on a new line; Option|Text separated by pipe)', 'mlm-map-plugin'); ?></th>
                    <th><?php _e('Action', 'mlm-map-plugin'); ?></th>
                </tr>
            </thead>
            <tbody id="mlm_custom_fields_tbody">
                <tr>
                    <td><input type="text" name="mlm_custom_fields[label][]" value="" /></td>
                    <td><input type="text" name="mlm_custom_fields[metakey][]" value="" /></td>
                    <td>
                        <select name="mlm_custom_fields[type][]">
                            <option value="text"><?php _e('Text', 'mlm-map-plugin'); ?></option>
                            <option value="number"><?php _e('Number', 'mlm-map-plugin'); ?></option>
                            <option value="radio"><?php _e('Radio', 'mlm-map-plugin'); ?></option>
                            <option value="checkbox"><?php _e('Checkbox', 'mlm-map-plugin'); ?></option>
                            <option value="select"><?php _e('Select', 'mlm-map-plugin'); ?></option>
                            <option value="textarea"><?php _e('Textarea', 'mlm-map-plugin'); ?></option>
                        </select>
                    </td>
                    <td><textarea name="mlm_custom_fields[values][]" style="width:100%;"></textarea></td>
                    <td><a href="#" class="mlm_remove_field" style="color:red; text-decoration:none;">X</a></td>
                </tr>
            </tbody>
        </table>
        <button type="button" id="mlm_add_custom_field" class="button"><?php _e('Add New Field', 'mlm-map-plugin'); ?></button>
    </div>
    <script>
    jQuery(document).ready(function($){
        var mediaUploader;
        // Media uploader for the Icon URL field.
        $('.upload_image_button').on('click', function(e){
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: '<?php _e("Choose Icon", "mlm-map-plugin"); ?>',
                button: {
                    text: '<?php _e("Choose Icon", "mlm-map-plugin"); ?>'
                },
                multiple: false
            });
            mediaUploader.on('select', function(){
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#mlm_location_type_icon').val(attachment.url);
                $('#mlm_location_type_icon_preview').html('<img src="'+attachment.url+'" style="max-width:100px; height:auto;" /><br><a href="#" id="remove_icon"><?php _e("Remove Icon", "mlm-map-plugin"); ?></a>').show();
            });
            mediaUploader.open();
        });
        // Remove Icon link functionality.
        $(document).on('click', '#remove_icon', function(e){
            e.preventDefault();
            $('#mlm_location_type_icon').val('');
            $('#mlm_location_type_icon_preview').hide();
        });
        
        // Add new custom field row.
        $('#mlm_add_custom_field').on('click', function(e){
            e.preventDefault();
            var newRow = '<tr>' +
                '<td><input type="text" name="mlm_custom_fields[label][]" value="" /></td>' +
                '<td><input type="text" name="mlm_custom_fields[metakey][]" value="" /></td>' +
                '<td><select name="mlm_custom_fields[type][]">' +
                    '<option value="text"><?php _e("Text", "mlm-map-plugin"); ?></option>' +
                    '<option value="number"><?php _e("Number", "mlm-map-plugin"); ?></option>' +
                    '<option value="radio"><?php _e("Radio", "mlm-map-plugin"); ?></option>' +
                    '<option value="checkbox"><?php _e("Checkbox", "mlm-map-plugin"); ?></option>' +
                    '<option value="select"><?php _e("Select", "mlm-map-plugin"); ?></option>' +
                    '<option value="textarea"><?php _e("Textarea", "mlm-map-plugin"); ?></option>' +
                '</select></td>' +
                '<td><textarea name="mlm_custom_fields[values][]" style="width:100%;"></textarea></td>' +
                '<td><a href="#" class="mlm_remove_field" style="color:red; text-decoration:none;">X</a></td>' +
            '</tr>';
            $('#mlm_custom_fields_tbody').append(newRow);
        });
        
        // Remove custom field row.
        $(document).on('click', '.mlm_remove_field', function(e){
            e.preventDefault();
            $(this).closest('tr').remove();
        });
        
        // Enable drag-and-drop sorting for the add form custom fields table.
        $("#mlm_custom_fields_tbody").sortable({
            placeholder: "ui-state-highlight",
            cursor: "move"
        });
    });
    </script>
    <?php
}
add_action( 'mlm_location_type_add_form_fields', 'mlm_location_type_add_form_fields_callback', 10, 2 );

/* =====================================================
   Add Extra Fields to the "Edit Location Type" Form
----------------------------------------------------- */
/*
   This function adds extra fields to the default "Edit" form for a location type.
   It outputs the Icon URL field (with media uploader) and the dynamic Custom Fields table.
   It also enables drag-and-drop sorting for the custom fields rows in the edit form.
*/
function mlm_location_type_edit_form_fields_callback( $term, $taxonomy ) {
    $icon = get_term_meta( $term->term_id, 'mlm_location_type_icon', true );
    $custom_fields = get_term_meta( $term->term_id, 'mlm_fields_definitions', true );
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="mlm_location_type_icon"><?php _e('Icon URL', 'mlm-map-plugin'); ?></label></th>
        <td>
            <input type="text" id="mlm_location_type_icon" name="mlm_location_type_icon" value="<?php echo esc_attr($icon); ?>">
            <button type="button" class="upload_image_button button"><?php _e('Upload Icon', 'mlm-map-plugin'); ?></button>
            <?php if ( $icon ) : ?>
                <div class="image_preview" style="margin-top:10px;">
                    <img src="<?php echo esc_url( $icon ); ?>" style="max-width:100px; height:auto;">
                    <br><a href="#" id="remove_icon"><?php _e('Remove Icon', 'mlm-map-plugin'); ?></a>
                </div>
            <?php else : ?>
                <div class="image_preview" style="margin-top:10px; display:none;"></div>
            <?php endif; ?>
            <script>
            jQuery(document).ready(function($){
                var mediaUploader;
                $('.upload_image_button').on('click', function(e){
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: '<?php _e("Choose Icon", "mlm-map-plugin"); ?>',
                        button: {
                            text: '<?php _e("Choose Icon", "mlm-map-plugin"); ?>'
                        },
                        multiple: false
                    });
                    mediaUploader.on('select', function(){
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#mlm_location_type_icon').val(attachment.url);
                        $('.image_preview').html('<img src="'+attachment.url+'" style="max-width:100px; height:auto;" /><br><a href="#" id="remove_icon"><?php _e("Remove Icon", "mlm-map-plugin"); ?></a>').show();
                    });
                    mediaUploader.open();
                });
                $(document).on('click', '#remove_icon', function(e){
                    e.preventDefault();
                    $('#mlm_location_type_icon').val('');
                    $('.image_preview').hide();
                });
                
                // Enable drag-and-drop sorting for the edit form custom fields table.
                $("#mlm_custom_fields_tbody_edit").sortable({
                    placeholder: "ui-state-highlight",
                    cursor: "move"
                });
            });
            </script>
        </td>
    </tr>
    <tr class="form-field term-group-wrap">
        <th scope="row"><?php _e('Custom Fields', 'mlm-map-plugin'); ?></th>
        <td>
            <table id="mlm_custom_fields_table_edit" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th><?php _e('Label', 'mlm-map-plugin'); ?></th>
                        <th><?php _e('Meta Key (alphanumeric)', 'mlm-map-plugin'); ?></th>
                        <th><?php _e('Type', 'mlm-map-plugin'); ?></th>
                        <th><?php _e('Values (Each option on a new line; Option|Text separated by pipe)', 'mlm-map-plugin'); ?></th>
                        <th><?php _e('Action', 'mlm-map-plugin'); ?></th>
                    </tr>
                </thead>
                <tbody id="mlm_custom_fields_tbody_edit">
                    <?php if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) : ?>
                        <?php foreach ( $custom_fields as $field ) : ?>
                        <tr>
                            <td><input type="text" name="mlm_custom_fields[label][]" value="<?php echo esc_attr( $field['label'] ); ?>" /></td>
                            <td><input type="text" name="mlm_custom_fields[metakey][]" value="<?php echo esc_attr( $field['metakey'] ); ?>" /></td>
                            <td>
                                <select name="mlm_custom_fields[type][]">
                                    <option value="text" <?php selected( $field['type'], 'text' ); ?>><?php _e('Text', 'mlm-map-plugin'); ?></option>
                                    <option value="number" <?php selected( $field['type'], 'number' ); ?>><?php _e('Number', 'mlm-map-plugin'); ?></option>
                                    <option value="radio" <?php selected( $field['type'], 'radio' ); ?>><?php _e('Radio', 'mlm-map-plugin'); ?></option>
                                    <option value="checkbox" <?php selected( $field['type'], 'checkbox' ); ?>><?php _e('Checkbox', 'mlm-map-plugin'); ?></option>
                                    <option value="select" <?php selected( $field['type'], 'select' ); ?>><?php _e('Select', 'mlm-map-plugin'); ?></option>
                                    <option value="textarea" <?php selected( $field['type'], 'textarea' ); ?>><?php _e('Textarea', 'mlm-map-plugin'); ?></option>
                                </select>
                            </td>
                            <td><textarea name="mlm_custom_fields[values][]" style="width:100%;"><?php echo esc_textarea( $field['values'] ); ?></textarea></td>
                            <td><a href="#" class="mlm_remove_field" style="color:red; text-decoration:none;">X</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td><input type="text" name="mlm_custom_fields[label][]" value="" /></td>
                            <td><input type="text" name="mlm_custom_fields[metakey][]" value="" /></td>
                            <td>
                                <select name="mlm_custom_fields[type][]">
                                    <option value="text"><?php _e('Text', 'mlm-map-plugin'); ?></option>
                                    <option value="number"><?php _e('Number', 'mlm-map-plugin'); ?></option>
                                    <option value="radio"><?php _e('Radio', 'mlm-map-plugin'); ?></option>
                                    <option value="checkbox"><?php _e('Checkbox', 'mlm-map-plugin'); ?></option>
                                    <option value="select"><?php _e('Select', 'mlm-map-plugin'); ?></option>
                                    <option value="textarea"><?php _e('Textarea', 'mlm-map-plugin'); ?></option>
                                </select>
                            </td>
                            <td><textarea name="mlm_custom_fields[values][]" style="width:100%;"></textarea></td>
                            <td><a href="#" class="mlm_remove_field" style="color:red; text-decoration:none;">X</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="button" id="mlm_add_custom_field_edit" class="button"><?php _e('Add New Field', 'mlm-map-plugin'); ?></button>
        </td>
    </tr>
    <script>
    jQuery(document).ready(function($){
        var mediaUploader;
        $('.upload_image_button').on('click', function(e){
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: '<?php _e("Choose Icon", "mlm-map-plugin"); ?>',
                button: {
                    text: '<?php _e("Choose Icon", "mlm-map-plugin"); ?>'
                },
                multiple: false
            });
            mediaUploader.on('select', function(){
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#mlm_location_type_icon').val(attachment.url);
                $('.image_preview').html('<img src="'+attachment.url+'" style="max-width:100px; height:auto;" /><br><a href="#" id="remove_icon"><?php _e("Remove Icon", "mlm-map-plugin"); ?></a>').show();
            });
            mediaUploader.open();
        });
        $(document).on('click', '#remove_icon', function(e){
            e.preventDefault();
            $('#mlm_location_type_icon').val('');
            $('.image_preview').hide();
        });
        
        // Enable drag-and-drop sorting for the edit form custom fields table.
        $("#mlm_custom_fields_tbody_edit").sortable({
            placeholder: "ui-state-highlight",
            cursor: "move"
        });
        
        // Add new custom field row for edit form.
        $('#mlm_add_custom_field_edit').on('click', function(e){
            e.preventDefault();
            var newRow = '<tr>' +
                '<td><input type="text" name="mlm_custom_fields[label][]" value="" /></td>' +
                '<td><input type="text" name="mlm_custom_fields[metakey][]" value="" /></td>' +
                '<td><select name="mlm_custom_fields[type][]">' +
                    '<option value="text"><?php _e("Text", "mlm-map-plugin"); ?></option>' +
                    '<option value="number"><?php _e("Number", "mlm-map-plugin"); ?></option>' +
                    '<option value="radio"><?php _e("Radio", "mlm-map-plugin"); ?></option>' +
                    '<option value="checkbox"><?php _e("Checkbox", "mlm-map-plugin"); ?></option>' +
                    '<option value="select"><?php _e("Select", "mlm-map-plugin"); ?></option>' +
                    '<option value="textarea"><?php _e("Textarea", "mlm-map-plugin"); ?></option>' +
                '</select></td>' +
                '<td><textarea name="mlm_custom_fields[values][]" style="width:100%;"></textarea></td>' +
                '<td><a href="#" class="mlm_remove_field" style="color:red; text-decoration:none;">X</a></td>' +
            '</tr>';
            $('#mlm_custom_fields_tbody_edit').append(newRow);
        });
        $(document).on('click', '.mlm_remove_field', function(e){
            e.preventDefault();
            $(this).closest('tr').remove();
        });
    });
    </script>
    <?php
}
add_action( 'mlm_location_type_edit_form_fields', 'mlm_location_type_edit_form_fields_callback', 10, 2 );

/* =====================================================
   Save Extra Taxonomy Fields
----------------------------------------------------- */
/*
   This function saves the extra fields when a term is created or edited.
   It updates:
     - The Icon URL (saved under "mlm_location_type_icon")
     - The custom fields definitions (saved under "mlm_fields_definitions")
*/
function mlm_save_location_type_custom_meta( $term_id ) {
    if ( isset( $_POST['mlm_location_type_icon'] ) ) {
        update_term_meta( $term_id, 'mlm_location_type_icon', esc_url_raw( $_POST['mlm_location_type_icon'] ) );
    }
    if ( isset( $_POST['mlm_custom_fields'] ) && is_array( $_POST['mlm_custom_fields'] ) ) {
        $labels    = isset( $_POST['mlm_custom_fields']['label'] ) ? $_POST['mlm_custom_fields']['label'] : array();
        $metakeys  = isset( $_POST['mlm_custom_fields']['metakey'] ) ? $_POST['mlm_custom_fields']['metakey'] : array();
        $types     = isset( $_POST['mlm_custom_fields']['type'] ) ? $_POST['mlm_custom_fields']['type'] : array();
        $valuesArr = isset( $_POST['mlm_custom_fields']['values'] ) ? $_POST['mlm_custom_fields']['values'] : array();
    
        $custom_fields = array();
        $count = count( $labels );
        for ( $i = 0; $i < $count; $i++ ) {
            if ( ! empty( $labels[$i] ) && ! empty( $metakeys[$i] ) ) {
                $custom_fields[] = array(
                    'label'   => sanitize_text_field( $labels[$i] ),
                    'metakey' => sanitize_key( $metakeys[$i] ),
                    'type'    => sanitize_text_field( $types[$i] ),
                    'values'  => sanitize_textarea_field( $valuesArr[$i] ),
                );
            }
        }
        update_term_meta( $term_id, 'mlm_fields_definitions', $custom_fields );
    }
}
add_action( 'created_mlm_location_type', 'mlm_save_location_type_custom_meta', 10, 2 );
add_action( 'edited_mlm_location_type', 'mlm_save_location_type_custom_meta', 10, 2 );
?>

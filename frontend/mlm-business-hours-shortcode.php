<?php
/**
 * Improved Business Hours Shortcode
 *
 * Generates a form for selecting business hours with single rows per day
 * and a "Closed" checkbox for each day.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mlm_business_hours_shortcode() {
    ob_start();
    
    // Days of the week
    $days = [
        'monday'    => __( 'Monday', 'mlm-map-plugin' ),
        'tuesday'   => __( 'Tuesday', 'mlm-map-plugin' ),
        'wednesday' => __( 'Wednesday', 'mlm-map-plugin' ),
        'thursday'  => __( 'Thursday', 'mlm-map-plugin' ),
        'friday'    => __( 'Friday', 'mlm-map-plugin' ),
        'saturday'  => __( 'Saturday', 'mlm-map-plugin' ),
        'sunday'    => __( 'Sunday', 'mlm-map-plugin' ),
    ];

    ?>
    <form id="mlm-business-hours-form">
        <table class="mlm-business-hours-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Day', 'mlm-map-plugin' ); ?></th>
                    <th><?php esc_html_e( 'Closed', 'mlm-map-plugin' ); ?></th>
                    <th><?php esc_html_e( 'Start Time', 'mlm-map-plugin' ); ?></th>
                    <th><?php esc_html_e( 'End Time', 'mlm-map-plugin' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $days as $day_slug => $day_name ) : ?>
                    <tr>
                        <td><?php echo esc_html( $day_name ); ?></td>
                        <td>
                            <input type="checkbox" name="<?php echo esc_attr( $day_slug ); ?>_closed" class="mlm-closed-checkbox" data-day="<?php echo esc_attr( $day_slug ); ?>">
                        </td>
                        <td>
                            <input type="time" name="<?php echo esc_attr( $day_slug ); ?>_start" class="mlm-time-input mlm-time-start" data-day="<?php echo esc_attr( $day_slug ); ?>">
                        </td>
                        <td>
                            <input type="time" name="<?php echo esc_attr( $day_slug ); ?>_end" class="mlm-time-input mlm-time-end" data-day="<?php echo esc_attr( $day_slug ); ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle enabling/disabling time inputs based on "Closed" checkbox
            const checkboxes = document.querySelectorAll('.mlm-closed-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const day = this.dataset.day;
                    const timeInputs = document.querySelectorAll(`.mlm-time-input[data-day="${day}"]`);

                    if (this.checked) {
                        timeInputs.forEach(input => {
                            input.disabled = true;
                            input.value = ""; // Clear values when marked as closed
                        });
                    } else {
                        timeInputs.forEach(input => {
                            input.disabled = false; // Re-enable the inputs when unchecked
                        });
                    }
                });
            });
        });
    </script>

    <style>
        .mlm-business-hours-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
        }

        .mlm-business-hours-table th,
        .mlm-business-hours-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        .mlm-business-hours-table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
    </style>
    <?php

    return ob_get_clean();
}

add_shortcode( 'mlm_business_hours', 'mlm_business_hours_shortcode' );
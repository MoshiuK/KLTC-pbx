<?php
/**
 * Vehicle spec meta boxes.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Central field definition — shared by the meta box, save handler, and demo importer.
 */
function apexdrive_spec_fields() {
	return array(
		'price'        => array( 'label' => __( 'Price (USD)', 'apexdrive-inventory' ), 'type' => 'number' ),
		'sale_price'   => array( 'label' => __( 'Sale Price (optional)', 'apexdrive-inventory' ), 'type' => 'number' ),
		'year'         => array( 'label' => __( 'Year', 'apexdrive-inventory' ), 'type' => 'number' ),
		'mileage'      => array( 'label' => __( 'Mileage', 'apexdrive-inventory' ), 'type' => 'number' ),
		'transmission' => array( 'label' => __( 'Transmission', 'apexdrive-inventory' ), 'type' => 'select', 'options' => array( 'Automatic', 'Manual', 'CVT', 'Dual-Clutch' ) ),
		'drivetrain'   => array( 'label' => __( 'Drivetrain', 'apexdrive-inventory' ), 'type' => 'select', 'options' => array( 'FWD', 'RWD', 'AWD', '4WD' ) ),
		'engine'       => array( 'label' => __( 'Engine', 'apexdrive-inventory' ), 'type' => 'text' ),
		'mpg'          => array( 'label' => __( 'MPG (city/hwy)', 'apexdrive-inventory' ), 'type' => 'text' ),
		'exterior'     => array( 'label' => __( 'Exterior Color', 'apexdrive-inventory' ), 'type' => 'text' ),
		'interior'     => array( 'label' => __( 'Interior Color', 'apexdrive-inventory' ), 'type' => 'text' ),
		'vin'          => array( 'label' => __( 'VIN', 'apexdrive-inventory' ), 'type' => 'text' ),
		'stock_no'     => array( 'label' => __( 'Stock #', 'apexdrive-inventory' ), 'type' => 'text' ),
		'owners'       => array( 'label' => __( 'Previous Owners', 'apexdrive-inventory' ), 'type' => 'number' ),
		'status'       => array( 'label' => __( 'Status', 'apexdrive-inventory' ), 'type' => 'select', 'options' => array( 'available', 'pending', 'sold' ) ),
		'featured'     => array( 'label' => __( 'Featured on homepage', 'apexdrive-inventory' ), 'type' => 'checkbox' ),
		'carfax_url'   => array( 'label' => __( 'History Report URL (Carfax etc.)', 'apexdrive-inventory' ), 'type' => 'url' ),
		'features'     => array( 'label' => __( 'Features (one per line)', 'apexdrive-inventory' ), 'type' => 'textarea' ),
		'gallery'      => array( 'label' => __( 'Gallery image IDs (comma-separated)', 'apexdrive-inventory' ), 'type' => 'text' ),
	);
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'apexdrive_specs', __( 'Vehicle Specs', 'apexdrive-inventory' ), 'apexdrive_render_specs_box', 'vehicle', 'normal', 'high' );
} );

function apexdrive_render_specs_box( $post ) {
	wp_nonce_field( 'apexdrive_save_specs', 'apexdrive_specs_nonce' );
	echo '<style>.apex-specs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}.apex-specs-grid label{font-weight:600;display:block;margin-bottom:4px}.apex-specs-grid .apex-field--wide{grid-column:1/-1}.apex-specs-grid input[type=text],.apex-specs-grid input[type=number],.apex-specs-grid input[type=url],.apex-specs-grid select,.apex-specs-grid textarea{width:100%}</style>';
	echo '<div class="apex-specs-grid">';

	foreach ( apexdrive_spec_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, '_apexdrive_' . $key, true );
		$id    = 'apexdrive_' . $key;
		$wide  = 'textarea' === $field['type'] ? ' apex-field--wide' : '';

		echo '<div class="apex-field' . esc_attr( $wide ) . '">';
		echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';

		switch ( $field['type'] ) {
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '">';
				echo '<option value="">—</option>';
				foreach ( $field['options'] as $option ) {
					printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $option ), selected( $value, $option, false ) );
				}
				echo '</select>';
				break;
			case 'checkbox':
				printf( '<input type="checkbox" id="%1$s" name="%1$s" value="1"%2$s>', esc_attr( $id ), checked( $value, '1', false ) );
				break;
			case 'textarea':
				printf( '<textarea id="%1$s" name="%1$s" rows="5">%2$s</textarea>', esc_attr( $id ), esc_textarea( $value ) );
				break;
			default:
				printf( '<input type="%3$s" id="%1$s" name="%1$s" value="%2$s">', esc_attr( $id ), esc_attr( $value ), esc_attr( $field['type'] ) );
		}
		echo '</div>';
	}

	echo '</div>';
}

add_action( 'save_post_vehicle', function ( $post_id ) {
	if ( ! isset( $_POST['apexdrive_specs_nonce'] ) || ! wp_verify_nonce( $_POST['apexdrive_specs_nonce'], 'apexdrive_save_specs' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( apexdrive_spec_fields() as $key => $field ) {
		$id = 'apexdrive_' . $key;
		if ( 'checkbox' === $field['type'] ) {
			update_post_meta( $post_id, '_apexdrive_' . $key, isset( $_POST[ $id ] ) ? '1' : '' );
			continue;
		}
		if ( ! isset( $_POST[ $id ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $id ] );
		switch ( $field['type'] ) {
			case 'number':
				$clean = '' === $raw ? '' : (string) floatval( $raw );
				break;
			case 'url':
				$clean = esc_url_raw( $raw );
				break;
			case 'textarea':
				$clean = sanitize_textarea_field( $raw );
				break;
			default:
				$clean = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, '_apexdrive_' . $key, $clean );
	}
} );

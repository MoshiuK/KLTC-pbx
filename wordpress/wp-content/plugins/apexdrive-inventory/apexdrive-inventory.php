<?php
/**
 * Plugin Name:       ApexDrive Inventory
 * Description:       Vehicle inventory engine for used-car dealerships — vehicle post type, specs, taxonomies, AJAX filtering, lead capture, and demo data.
 * Version:           1.0.0
 * Author:            Knox Media Group
 * License:           GPL-2.0-or-later
 * Text Domain:       apexdrive-inventory
 */

defined( 'ABSPATH' ) || exit;

define( 'APEXDRIVE_INV_VERSION', '1.0.0' );
define( 'APEXDRIVE_INV_DIR', plugin_dir_path( __FILE__ ) );

require_once APEXDRIVE_INV_DIR . 'includes/post-types.php';
require_once APEXDRIVE_INV_DIR . 'includes/meta-boxes.php';
require_once APEXDRIVE_INV_DIR . 'includes/ajax-filter.php';
require_once APEXDRIVE_INV_DIR . 'includes/leads.php';
require_once APEXDRIVE_INV_DIR . 'includes/demo-data.php';

/**
 * Flush rewrite rules on (de)activation so /inventory/ URLs work immediately.
 */
register_activation_hook( __FILE__, function () {
	apexdrive_register_post_types();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/**
 * Helper: read one vehicle spec with a default.
 */
function apexdrive_spec( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, '_apexdrive_' . $key, true );
	return ( '' === $value || null === $value ) ? $default : $value;
}

/**
 * Helper: formatted price ("$18,995" or "Call for price").
 */
function apexdrive_price( $post_id ) {
	$price = (float) apexdrive_spec( $post_id, 'price', 0 );
	if ( $price <= 0 ) {
		return __( 'Call for price', 'apexdrive-inventory' );
	}
	return '$' . number_format( $price );
}

/**
 * Helper: formatted mileage ("42,310 mi").
 */
function apexdrive_mileage( $post_id ) {
	$miles = (int) apexdrive_spec( $post_id, 'mileage', 0 );
	return $miles > 0 ? number_format( $miles ) . ' mi' : '—';
}

<?php
/**
 * Vehicle post type + taxonomies.
 */

defined( 'ABSPATH' ) || exit;

function apexdrive_register_post_types() {
	register_post_type( 'vehicle', array(
		'labels' => array(
			'name'               => __( 'Vehicles', 'apexdrive-inventory' ),
			'singular_name'      => __( 'Vehicle', 'apexdrive-inventory' ),
			'add_new'            => __( 'Add Vehicle', 'apexdrive-inventory' ),
			'add_new_item'       => __( 'Add New Vehicle', 'apexdrive-inventory' ),
			'edit_item'          => __( 'Edit Vehicle', 'apexdrive-inventory' ),
			'all_items'          => __( 'All Vehicles', 'apexdrive-inventory' ),
			'search_items'       => __( 'Search Vehicles', 'apexdrive-inventory' ),
			'not_found'          => __( 'No vehicles found.', 'apexdrive-inventory' ),
		),
		'public'       => true,
		'has_archive'  => 'inventory',
		'rewrite'      => array( 'slug' => 'inventory', 'with_front' => false ),
		'menu_icon'    => 'dashicons-car',
		'menu_position'=> 5,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest' => true,
	) );

	$taxonomies = array(
		'vehicle_make'      => __( 'Makes', 'apexdrive-inventory' ),
		'vehicle_body'      => __( 'Body Types', 'apexdrive-inventory' ),
		'vehicle_fuel'      => __( 'Fuel Types', 'apexdrive-inventory' ),
		'vehicle_condition' => __( 'Condition Tags', 'apexdrive-inventory' ),
	);

	foreach ( $taxonomies as $slug => $label ) {
		register_taxonomy( $slug, 'vehicle', array(
			'label'        => $label,
			'hierarchical' => true,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => str_replace( '_', '-', $slug ), 'with_front' => false ),
		) );
	}
}
add_action( 'init', 'apexdrive_register_post_types' );

/**
 * Admin list columns: photo, price, year, mileage, status.
 */
add_filter( 'manage_vehicle_posts_columns', function ( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['apex_photo'] = __( 'Photo', 'apexdrive-inventory' );
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['apex_price']   = __( 'Price', 'apexdrive-inventory' );
			$new['apex_year']    = __( 'Year', 'apexdrive-inventory' );
			$new['apex_mileage'] = __( 'Mileage', 'apexdrive-inventory' );
			$new['apex_status']  = __( 'Status', 'apexdrive-inventory' );
		}
	}
	return $new;
} );

add_action( 'manage_vehicle_posts_custom_column', function ( $column, $post_id ) {
	switch ( $column ) {
		case 'apex_photo':
			echo get_the_post_thumbnail( $post_id, array( 60, 40 ) );
			break;
		case 'apex_price':
			echo esc_html( apexdrive_price( $post_id ) );
			break;
		case 'apex_year':
			echo esc_html( apexdrive_spec( $post_id, 'year', '—' ) );
			break;
		case 'apex_mileage':
			echo esc_html( apexdrive_mileage( $post_id ) );
			break;
		case 'apex_status':
			$status = apexdrive_spec( $post_id, 'status', 'available' );
			printf( '<span class="apex-status apex-status--%s">%s</span>', esc_attr( $status ), esc_html( ucfirst( $status ) ) );
			break;
	}
}, 10, 2 );

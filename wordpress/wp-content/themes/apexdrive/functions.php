<?php
/**
 * ApexDrive theme setup.
 */

defined( 'ABSPATH' ) || exit;

define( 'APEXDRIVE_VERSION', '1.0.0' );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'automatic-feed-links' );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'apexdrive' ),
		'footer'  => __( 'Footer Menu', 'apexdrive' ),
	) );

	add_image_size( 'vehicle-card', 640, 400, true );
	add_image_size( 'vehicle-hero', 1280, 800, true );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'apexdrive-style', get_stylesheet_uri(), array(), APEXDRIVE_VERSION );
	wp_enqueue_script( 'apexdrive-main', get_template_directory_uri() . '/assets/js/main.js', array(), APEXDRIVE_VERSION, true );

	wp_localize_script( 'apexdrive-main', 'apexdrive', array(
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'filterNonce' => wp_create_nonce( 'apexdrive_filter' ),
		'leadNonce'   => wp_create_nonce( 'apexdrive_lead' ),
	) );
} );

/**
 * Customizer: dealership identity settings.
 */
add_action( 'customize_register', function ( $wp_customize ) {
	$wp_customize->add_section( 'apexdrive_dealer', array(
		'title'    => __( 'Dealership Info', 'apexdrive' ),
		'priority' => 30,
	) );

	$settings = array(
		'apexdrive_phone'   => array( 'label' => __( 'Phone Number', 'apexdrive' ), 'default' => '(555) 012-3456' ),
		'apexdrive_address' => array( 'label' => __( 'Address', 'apexdrive' ), 'default' => '2100 Motorway Blvd, Knoxville, TN' ),
		'apexdrive_hours'   => array( 'label' => __( 'Hours', 'apexdrive' ), 'default' => 'Mon–Sat 9am–7pm' ),
		'apexdrive_tagline' => array( 'label' => __( 'Hero Tagline', 'apexdrive' ), 'default' => 'Every vehicle inspected, verified, and priced with live market data. No games, no pressure — just the right car.' ),
	);

	foreach ( $settings as $id => $config ) {
		$wp_customize->add_setting( $id, array(
			'default'           => $config['default'],
			'sanitize_callback' => 'sanitize_text_field',
		) );
		$wp_customize->add_control( $id, array(
			'label'   => $config['label'],
			'section' => 'apexdrive_dealer',
			'type'    => 'text',
		) );
	}
} );

/**
 * Helper: customizer value with default.
 */
function apexdrive_option( $key, $default = '' ) {
	return get_theme_mod( 'apexdrive_' . $key, $default );
}

/**
 * Fallback menu when none is assigned yet.
 */
function apexdrive_fallback_menu() {
	echo '<ul>';
	printf( '<li><a href="%s">%s</a></li>', esc_url( home_url( '/' ) ), esc_html__( 'Home', 'apexdrive' ) );
	if ( post_type_exists( 'vehicle' ) ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( get_post_type_archive_link( 'vehicle' ) ), esc_html__( 'Inventory', 'apexdrive' ) );
	}
	echo '</ul>';
}

/**
 * Gallery image IDs for a vehicle: featured image first, then the gallery meta.
 */
function apexdrive_vehicle_gallery_ids( $post_id ) {
	$ids = array();
	if ( has_post_thumbnail( $post_id ) ) {
		$ids[] = get_post_thumbnail_id( $post_id );
	}
	if ( function_exists( 'apexdrive_spec' ) ) {
		$raw = apexdrive_spec( $post_id, 'gallery' );
		foreach ( array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) ) as $id ) {
			if ( ! in_array( $id, $ids, true ) && wp_attachment_is_image( $id ) ) {
				$ids[] = $id;
			}
		}
	}
	return $ids;
}

/**
 * Make archive queries respect filter params from the URL (shareable filtered links)
 * by reusing the plugin's query builder.
 */
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'vehicle' ) ) {
		return;
	}
	if ( ! function_exists( 'apexdrive_build_filter_query' ) ) {
		return;
	}
	$args = apexdrive_build_filter_query( $_GET, max( 1, (int) $query->get( 'paged' ) ) );
	foreach ( array( 'tax_query', 'meta_query', 'meta_key', 'orderby', 'order', 's', 'posts_per_page' ) as $key ) {
		if ( isset( $args[ $key ] ) ) {
			$query->set( $key, $args[ $key ] );
		}
	}
} );

/**
 * Admin notice if the companion plugin is missing.
 */
add_action( 'admin_notices', function () {
	if ( ! post_type_exists( 'vehicle' ) ) {
		echo '<div class="notice notice-warning"><p><strong>ApexDrive:</strong> ' .
			esc_html__( 'The "ApexDrive Inventory" plugin is not active — vehicle listings will not appear until it is activated.', 'apexdrive' ) .
			'</p></div>';
	}
} );

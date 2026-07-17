<?php
/**
 * AJAX inventory filtering — powers the live search on the inventory page.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build a WP_Query args array from filter input (also used server-side on
 * the archive page so filtered URLs are shareable / SEO-crawlable).
 */
function apexdrive_build_filter_query( $input, $paged = 1 ) {
	$args = array(
		'post_type'      => 'vehicle',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'paged'          => max( 1, (int) $paged ),
	);

	$tax_query  = array();
	$meta_query = array();

	foreach ( array( 'make' => 'vehicle_make', 'body' => 'vehicle_body', 'fuel' => 'vehicle_fuel' ) as $param => $taxonomy ) {
		if ( ! empty( $input[ $param ] ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => sanitize_title( $input[ $param ] ),
			);
		}
	}

	if ( ! empty( $input['price_max'] ) ) {
		$meta_query[] = array(
			'key'     => '_apexdrive_price',
			'value'   => (float) $input['price_max'],
			'type'    => 'NUMERIC',
			'compare' => '<=',
		);
	}
	if ( ! empty( $input['price_min'] ) ) {
		$meta_query[] = array(
			'key'     => '_apexdrive_price',
			'value'   => (float) $input['price_min'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( ! empty( $input['year_min'] ) ) {
		$meta_query[] = array(
			'key'     => '_apexdrive_year',
			'value'   => (int) $input['year_min'],
			'type'    => 'NUMERIC',
			'compare' => '>=',
		);
	}
	if ( ! empty( $input['mileage_max'] ) ) {
		$meta_query[] = array(
			'key'     => '_apexdrive_mileage',
			'value'   => (int) $input['mileage_max'],
			'type'    => 'NUMERIC',
			'compare' => '<=',
		);
	}

	if ( ! empty( $input['s'] ) ) {
		$args['s'] = sanitize_text_field( $input['s'] );
	}

	switch ( $input['sort'] ?? '' ) {
		case 'price_asc':
			$args['meta_key'] = '_apexdrive_price';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
			break;
		case 'price_desc':
			$args['meta_key'] = '_apexdrive_price';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		case 'mileage_asc':
			$args['meta_key'] = '_apexdrive_mileage';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'ASC';
			break;
		case 'year_desc':
			$args['meta_key'] = '_apexdrive_year';
			$args['orderby']  = 'meta_value_num';
			$args['order']    = 'DESC';
			break;
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
	}

	if ( $tax_query ) {
		$args['tax_query'] = count( $tax_query ) > 1 ? array_merge( array( 'relation' => 'AND' ), $tax_query ) : $tax_query;
	}
	if ( $meta_query ) {
		$args['meta_query'] = count( $meta_query ) > 1 ? array_merge( array( 'relation' => 'AND' ), $meta_query ) : $meta_query;
	}

	return $args;
}

/**
 * AJAX endpoint: returns rendered vehicle cards + result count.
 * The card markup itself lives in the theme (template-parts/vehicle-card.php)
 * with a plain-HTML fallback so the plugin works with any theme.
 */
function apexdrive_ajax_filter_vehicles() {
	check_ajax_referer( 'apexdrive_filter', 'nonce' );

	$paged = isset( $_POST['paged'] ) ? (int) $_POST['paged'] : 1;
	$query = new WP_Query( apexdrive_build_filter_query( wp_unslash( $_POST ), $paged ) );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$card = locate_template( 'template-parts/vehicle-card.php' );
			if ( $card ) {
				load_template( $card, false );
			} else {
				printf(
					'<article class="apex-card"><a href="%s">%s — %s</a></article>',
					esc_url( get_permalink() ),
					esc_html( get_the_title() ),
					esc_html( apexdrive_price( get_the_ID() ) )
				);
			}
		}
	}
	wp_reset_postdata();

	wp_send_json_success( array(
		'html'      => ob_get_clean(),
		'found'     => (int) $query->found_posts,
		'max_pages' => (int) $query->max_num_pages,
		'paged'     => $paged,
	) );
}
add_action( 'wp_ajax_apexdrive_filter', 'apexdrive_ajax_filter_vehicles' );
add_action( 'wp_ajax_nopriv_apexdrive_filter', 'apexdrive_ajax_filter_vehicles' );

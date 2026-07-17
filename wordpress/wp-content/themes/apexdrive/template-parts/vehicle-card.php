<?php
/**
 * Single vehicle card — used on the front page, archive grid, and AJAX results.
 */

defined( 'ABSPATH' ) || exit;

$vehicle_id = get_the_ID();
$status     = function_exists( 'apexdrive_spec' ) ? apexdrive_spec( $vehicle_id, 'status', 'available' ) : 'available';
$featured   = function_exists( 'apexdrive_spec' ) ? apexdrive_spec( $vehicle_id, 'featured' ) : '';
$sale       = function_exists( 'apexdrive_spec' ) ? (float) apexdrive_spec( $vehicle_id, 'sale_price', 0 ) : 0;
$price      = function_exists( 'apexdrive_spec' ) ? (float) apexdrive_spec( $vehicle_id, 'price', 0 ) : 0;
$make_terms = get_the_terms( $vehicle_id, 'vehicle_make' );
$body_terms = get_the_terms( $vehicle_id, 'vehicle_body' );
?>
<article <?php post_class( 'vehicle-card reveal' ); ?>>
	<a class="vehicle-card-media" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
		<?php if ( 'sold' === $status ) : ?>
			<span class="badge badge-sold"><?php esc_html_e( 'Sold', 'apexdrive' ); ?></span>
		<?php elseif ( 'pending' === $status ) : ?>
			<span class="badge badge-pending"><?php esc_html_e( 'Sale Pending', 'apexdrive' ); ?></span>
		<?php elseif ( $featured ) : ?>
			<span class="badge badge-featured"><?php esc_html_e( 'Featured', 'apexdrive' ); ?></span>
		<?php endif; ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'vehicle-card', array( 'loading' => 'lazy' ) ); ?>
		<?php else : ?>
			<span class="no-photo" aria-hidden="true">🚘</span>
		<?php endif; ?>
	</a>

	<div class="vehicle-card-body">
		<h3 class="vehicle-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="vehicle-card-sub">
			<?php
			$sub = array();
			if ( $make_terms && ! is_wp_error( $make_terms ) ) {
				$sub[] = $make_terms[0]->name;
			}
			if ( $body_terms && ! is_wp_error( $body_terms ) ) {
				$sub[] = $body_terms[0]->name;
			}
			if ( function_exists( 'apexdrive_spec' ) ) {
				$stock = apexdrive_spec( $vehicle_id, 'stock_no' );
				if ( $stock ) {
					$sub[] = __( 'Stock', 'apexdrive' ) . ' ' . $stock;
				}
			}
			echo esc_html( implode( ' · ', $sub ) );
			?>
		</p>

		<?php if ( function_exists( 'apexdrive_spec' ) ) : ?>
			<div class="vehicle-card-specs">
				<span>📅 <?php echo esc_html( apexdrive_spec( $vehicle_id, 'year', '—' ) ); ?></span>
				<span>🛣️ <?php echo esc_html( apexdrive_mileage( $vehicle_id ) ); ?></span>
				<span>⚙️ <?php echo esc_html( apexdrive_spec( $vehicle_id, 'transmission', '—' ) ); ?></span>
				<span>🔧 <?php echo esc_html( apexdrive_spec( $vehicle_id, 'drivetrain', '—' ) ); ?></span>
			</div>
		<?php endif; ?>

		<div class="vehicle-card-foot">
			<span class="price">
				<?php if ( $sale > 0 && $sale < $price ) : ?>
					<span class="price-was">$<?php echo esc_html( number_format( $price ) ); ?></span>
					<span class="price-sale">$<?php echo esc_html( number_format( $sale ) ); ?></span>
				<?php elseif ( function_exists( 'apexdrive_price' ) ) : ?>
					<?php echo esc_html( apexdrive_price( $vehicle_id ) ); ?>
				<?php endif; ?>
			</span>
			<a class="btn btn-ghost" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Details', 'apexdrive' ); ?></a>
		</div>
	</div>
</article>

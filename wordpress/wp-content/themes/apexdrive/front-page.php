<?php
/**
 * Front page: hero + quick search, featured vehicles, trust tiles, CTA.
 */

get_header();

$has_inventory = post_type_exists( 'vehicle' );
$inventory_url = $has_inventory ? get_post_type_archive_link( 'vehicle' ) : home_url( '/' );
?>

<section class="hero">
	<div class="hero-grid-lines" aria-hidden="true"></div>
	<div class="container">
		<div class="hero-inner">
			<span class="kicker"><?php esc_html_e( 'Certified Pre-Owned · Transparent Pricing', 'apexdrive' ); ?></span>
			<h1><?php esc_html_e( 'Find your next car', 'apexdrive' ); ?> <span class="glow"><?php esc_html_e( 'the smart way', 'apexdrive' ); ?></span></h1>
			<p class="lede"><?php echo esc_html( apexdrive_option( 'tagline', 'Every vehicle inspected, verified, and priced with live market data. No games, no pressure — just the right car.' ) ); ?></p>

			<?php if ( $has_inventory ) : ?>
				<form class="search-bar" action="<?php echo esc_url( $inventory_url ); ?>" method="get">
					<select name="make" aria-label="<?php esc_attr_e( 'Make', 'apexdrive' ); ?>">
						<option value=""><?php esc_html_e( 'Any Make', 'apexdrive' ); ?></option>
						<?php
						$makes = get_terms( array( 'taxonomy' => 'vehicle_make', 'hide_empty' => true ) );
						if ( ! is_wp_error( $makes ) ) :
							foreach ( $makes as $term ) :
								?>
								<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; endif; ?>
					</select>
					<select name="body" aria-label="<?php esc_attr_e( 'Body Type', 'apexdrive' ); ?>">
						<option value=""><?php esc_html_e( 'Any Body Type', 'apexdrive' ); ?></option>
						<?php
						$bodies = get_terms( array( 'taxonomy' => 'vehicle_body', 'hide_empty' => true ) );
						if ( ! is_wp_error( $bodies ) ) :
							foreach ( $bodies as $term ) :
								?>
								<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; endif; ?>
					</select>
					<select name="price_max" aria-label="<?php esc_attr_e( 'Max Price', 'apexdrive' ); ?>">
						<option value=""><?php esc_html_e( 'Any Price', 'apexdrive' ); ?></option>
						<?php foreach ( array( 15000, 20000, 25000, 30000, 40000, 50000 ) as $cap ) : ?>
							<option value="<?php echo esc_attr( $cap ); ?>"><?php echo esc_html( 'Under $' . number_format( $cap ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="search" name="s" placeholder="<?php esc_attr_e( 'Model, keyword…', 'apexdrive' ); ?>" aria-label="<?php esc_attr_e( 'Keyword', 'apexdrive' ); ?>">
					<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Search', 'apexdrive' ); ?></button>
				</form>
			<?php endif; ?>

			<div class="hero-stats">
				<div class="hero-stat"><b data-count="150">150+</b><span><?php esc_html_e( 'Vehicles in stock', 'apexdrive' ); ?></span></div>
				<div class="hero-stat"><b data-count="172">172</b><span><?php esc_html_e( 'Point inspection', 'apexdrive' ); ?></span></div>
				<div class="hero-stat"><b>4.9★</b><span><?php esc_html_e( 'Customer rating', 'apexdrive' ); ?></span></div>
				<div class="hero-stat"><b>90-day</b><span><?php esc_html_e( 'Powertrain warranty', 'apexdrive' ); ?></span></div>
			</div>
		</div>
	</div>
</section>

<?php if ( $has_inventory ) : ?>
	<section class="section">
		<div class="container">
			<div class="section-head">
				<div>
					<span class="kicker"><?php esc_html_e( 'Hand-picked', 'apexdrive' ); ?></span>
					<h2><?php esc_html_e( 'Featured Inventory', 'apexdrive' ); ?></h2>
				</div>
				<a class="btn btn-ghost" href="<?php echo esc_url( $inventory_url ); ?>"><?php esc_html_e( 'View all vehicles →', 'apexdrive' ); ?></a>
			</div>

			<div class="vehicle-grid">
				<?php
				$featured = new WP_Query( array(
					'post_type'      => 'vehicle',
					'posts_per_page' => 6,
					'meta_query'     => array(
						array( 'key' => '_apexdrive_featured', 'value' => '1' ),
					),
				) );
				// Fall back to newest arrivals if nothing is flagged featured yet.
				if ( ! $featured->have_posts() ) {
					$featured = new WP_Query( array( 'post_type' => 'vehicle', 'posts_per_page' => 6 ) );
				}
				while ( $featured->have_posts() ) :
					$featured->the_post();
					get_template_part( 'template-parts/vehicle-card' );
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<section class="section">
	<div class="container">
		<div class="section-head">
			<div>
				<span class="kicker"><?php esc_html_e( 'Why ApexDrive', 'apexdrive' ); ?></span>
				<h2><?php esc_html_e( 'Car buying, upgraded', 'apexdrive' ); ?></h2>
			</div>
		</div>
		<div class="tiles">
			<div class="tile reveal">
				<div class="tile-icon" aria-hidden="true">🔍</div>
				<h3><?php esc_html_e( '172-Point Inspection', 'apexdrive' ); ?></h3>
				<p><?php esc_html_e( 'Every car is digitally inspected by certified techs — full report attached to each listing.', 'apexdrive' ); ?></p>
			</div>
			<div class="tile reveal">
				<div class="tile-icon" aria-hidden="true">📊</div>
				<h3><?php esc_html_e( 'Live Market Pricing', 'apexdrive' ); ?></h3>
				<p><?php esc_html_e( 'Prices benchmarked daily against regional market data. What you see is what you pay.', 'apexdrive' ); ?></p>
			</div>
			<div class="tile reveal">
				<div class="tile-icon" aria-hidden="true">⚡</div>
				<h3><?php esc_html_e( 'Instant Financing', 'apexdrive' ); ?></h3>
				<p><?php esc_html_e( 'Estimate payments online, get pre-approved in minutes with our lending partners.', 'apexdrive' ); ?></p>
			</div>
			<div class="tile reveal">
				<div class="tile-icon" aria-hidden="true">🛡️</div>
				<h3><?php esc_html_e( '90-Day Warranty', 'apexdrive' ); ?></h3>
				<p><?php esc_html_e( 'Complimentary powertrain coverage on every vehicle, plus a 7-day money-back guarantee.', 'apexdrive' ); ?></p>
			</div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="cta-band reveal">
			<h2><?php esc_html_e( 'Ready for a test drive?', 'apexdrive' ); ?></h2>
			<p><?php esc_html_e( 'Book online in 30 seconds — we’ll have the car warmed up and waiting.', 'apexdrive' ); ?></p>
			<a class="btn btn-primary" href="<?php echo esc_url( $inventory_url ); ?>"><?php esc_html_e( 'Browse Inventory', 'apexdrive' ); ?></a>
		</div>
	</div>
</section>

<?php get_footer(); ?>

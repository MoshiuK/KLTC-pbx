<?php
/**
 * Single vehicle: gallery, spec sheet, financing calculator, test-drive form.
 */

get_header();

while ( have_posts() ) :
	the_post();
	$vehicle_id = get_the_ID();
	$gallery    = apexdrive_vehicle_gallery_ids( $vehicle_id );
	$price      = function_exists( 'apexdrive_spec' ) ? (float) apexdrive_spec( $vehicle_id, 'price', 0 ) : 0;
	$status     = function_exists( 'apexdrive_spec' ) ? apexdrive_spec( $vehicle_id, 'status', 'available' ) : 'available';
	?>

	<section class="vehicle-hero">
		<div class="container">
			<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'apexdrive' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'apexdrive' ); ?></a> /
				<a href="<?php echo esc_url( get_post_type_archive_link( 'vehicle' ) ); ?>"><?php esc_html_e( 'Inventory', 'apexdrive' ); ?></a> /
				<?php the_title(); ?>
			</nav>

			<div class="vehicle-title-row">
				<div>
					<h1><?php the_title(); ?></h1>
					<?php if ( function_exists( 'apexdrive_spec' ) ) : ?>
						<div class="vehicle-meta-line">
							<span>📅 <?php echo esc_html( apexdrive_spec( $vehicle_id, 'year', '—' ) ); ?></span>
							<span>🛣️ <?php echo esc_html( apexdrive_mileage( $vehicle_id ) ); ?></span>
							<span>⚙️ <?php echo esc_html( apexdrive_spec( $vehicle_id, 'transmission', '—' ) ); ?></span>
							<span>🔧 <?php echo esc_html( apexdrive_spec( $vehicle_id, 'drivetrain', '—' ) ); ?></span>
							<?php $stock = apexdrive_spec( $vehicle_id, 'stock_no' ); ?>
							<?php if ( $stock ) : ?><span><?php echo esc_html( __( 'Stock', 'apexdrive' ) . ' #' . $stock ); ?></span><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
				<div>
					<span class="price"><?php echo function_exists( 'apexdrive_price' ) ? esc_html( apexdrive_price( $vehicle_id ) ) : ''; ?></span>
					<?php if ( 'sold' === $status ) : ?>
						<span class="badge badge-sold" style="position:static;display:inline-block;margin-left:10px;"><?php esc_html_e( 'Sold', 'apexdrive' ); ?></span>
					<?php elseif ( 'pending' === $status ) : ?>
						<span class="badge badge-pending" style="position:static;display:inline-block;margin-left:10px;"><?php esc_html_e( 'Sale Pending', 'apexdrive' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<div class="container vehicle-layout">
		<div>
			<div class="gallery-main" id="gallery-main">
				<?php if ( $gallery ) : ?>
					<?php echo wp_get_attachment_image( $gallery[0], 'vehicle-hero', false, array( 'id' => 'gallery-main-img' ) ); ?>
				<?php else : ?>
					<span class="no-photo" style="display:grid;place-items:center;height:100%;font-size:4rem;" aria-hidden="true">🚘</span>
				<?php endif; ?>
			</div>

			<?php if ( count( $gallery ) > 1 ) : ?>
				<div class="gallery-thumbs" id="gallery-thumbs">
					<?php foreach ( $gallery as $i => $img_id ) : ?>
						<button type="button" class="<?php echo 0 === $i ? 'is-active' : ''; ?>" data-full="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'vehicle-hero' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Photo %d', 'apexdrive' ), $i + 1 ) ); ?>">
							<?php echo wp_get_attachment_image( $img_id, 'thumbnail' ); ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="vehicle-description">
				<h2><?php esc_html_e( 'About this vehicle', 'apexdrive' ); ?></h2>
				<?php the_content(); ?>
			</div>

			<?php
			$features = function_exists( 'apexdrive_spec' ) ? apexdrive_spec( $vehicle_id, 'features' ) : '';
			$feature_lines = array_filter( array_map( 'trim', explode( "\n", (string) $features ) ) );
			if ( $feature_lines ) :
				?>
				<div class="spec-panel">
					<h3><?php esc_html_e( 'Features & Equipment', 'apexdrive' ); ?></h3>
					<ul class="feature-list">
						<?php foreach ( $feature_lines as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>

		<aside>
			<?php if ( function_exists( 'apexdrive_spec' ) ) : ?>
				<div class="spec-panel">
					<h3><?php esc_html_e( 'Specifications', 'apexdrive' ); ?></h3>
					<table class="spec-table">
						<?php
						$specs = array(
							__( 'Year', 'apexdrive' )        => apexdrive_spec( $vehicle_id, 'year' ),
							__( 'Mileage', 'apexdrive' )     => apexdrive_mileage( $vehicle_id ),
							__( 'Engine', 'apexdrive' )      => apexdrive_spec( $vehicle_id, 'engine' ),
							__( 'Transmission', 'apexdrive' )=> apexdrive_spec( $vehicle_id, 'transmission' ),
							__( 'Drivetrain', 'apexdrive' )  => apexdrive_spec( $vehicle_id, 'drivetrain' ),
							__( 'MPG', 'apexdrive' )         => apexdrive_spec( $vehicle_id, 'mpg' ),
							__( 'Exterior', 'apexdrive' )    => apexdrive_spec( $vehicle_id, 'exterior' ),
							__( 'Interior', 'apexdrive' )    => apexdrive_spec( $vehicle_id, 'interior' ),
							__( 'Owners', 'apexdrive' )      => apexdrive_spec( $vehicle_id, 'owners' ),
							__( 'VIN', 'apexdrive' )         => apexdrive_spec( $vehicle_id, 'vin' ),
						);
						foreach ( $specs as $label => $value ) :
							if ( '' === (string) $value ) {
								continue;
							}
							?>
							<tr><td><?php echo esc_html( $label ); ?></td><td><?php echo esc_html( $value ); ?></td></tr>
						<?php endforeach; ?>
					</table>
					<?php $carfax = apexdrive_spec( $vehicle_id, 'carfax_url' ); ?>
					<?php if ( $carfax ) : ?>
						<p style="margin-top:14px;"><a class="btn btn-ghost btn-block" href="<?php echo esc_url( $carfax ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View History Report ↗', 'apexdrive' ); ?></a></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="calc-panel" data-price="<?php echo esc_attr( $price ); ?>">
				<h3><?php esc_html_e( 'Estimate Your Payment', 'apexdrive' ); ?></h3>
				<label for="calc-down"><?php esc_html_e( 'Down Payment ($)', 'apexdrive' ); ?></label>
				<input type="number" id="calc-down" min="0" step="500" value="<?php echo esc_attr( max( 0, round( $price * 0.1, -2 ) ) ); ?>">
				<div class="calc-row">
					<div>
						<label for="calc-apr"><?php esc_html_e( 'APR %', 'apexdrive' ); ?></label>
						<input type="number" id="calc-apr" min="0" max="30" step="0.1" value="6.9">
					</div>
					<div>
						<label for="calc-term"><?php esc_html_e( 'Term (months)', 'apexdrive' ); ?></label>
						<input type="number" id="calc-term" min="12" max="96" step="12" value="60">
					</div>
				</div>
				<div class="calc-result">
					<b id="calc-payment">$0</b>
					<span><?php esc_html_e( 'estimated per month', 'apexdrive' ); ?></span>
				</div>
				<p class="calc-note"><?php esc_html_e( 'Estimate only. Excludes tax, title, and fees. Subject to credit approval.', 'apexdrive' ); ?></p>
			</div>

			<div class="lead-panel">
				<h3><?php esc_html_e( 'Book a Test Drive', 'apexdrive' ); ?></h3>
				<form class="lead-form" data-lead-type="test-drive" data-vehicle="<?php echo esc_attr( $vehicle_id ); ?>">
					<input type="text" name="name" placeholder="<?php esc_attr_e( 'Your name *', 'apexdrive' ); ?>" required>
					<input type="email" name="email" placeholder="<?php esc_attr_e( 'Email *', 'apexdrive' ); ?>" required>
					<input type="tel" name="phone" placeholder="<?php esc_attr_e( 'Phone', 'apexdrive' ); ?>">
					<textarea name="message" rows="3" placeholder="<?php esc_attr_e( 'Preferred day & time, questions…', 'apexdrive' ); ?>"></textarea>
					<input class="hp-field" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
					<button type="submit" class="btn btn-primary btn-block"><?php esc_html_e( 'Request Test Drive', 'apexdrive' ); ?></button>
					<p class="form-msg" role="status"></p>
				</form>
			</div>
		</aside>
	</div>

<?php endwhile; ?>

<?php get_footer(); ?>

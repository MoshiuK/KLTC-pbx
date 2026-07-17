<?php
/**
 * Inventory archive — filter sidebar + AJAX-refreshed grid.
 * Initial render is server-side (from URL params via pre_get_posts),
 * so filtered URLs are shareable and crawlable; JS takes over from there.
 */

get_header();

$current = array_map( 'sanitize_text_field', wp_unslash( $_GET ) );
?>

<section class="section">
	<div class="container">
		<div class="section-head">
			<div>
				<span class="kicker"><?php esc_html_e( 'Live Inventory', 'apexdrive' ); ?></span>
				<h2><?php esc_html_e( 'Browse Our Vehicles', 'apexdrive' ); ?></h2>
			</div>
		</div>

		<div class="inventory-layout">
			<aside class="filter-panel">
				<h3>
					<?php esc_html_e( 'Filters', 'apexdrive' ); ?>
					<button type="button" id="filter-reset"><?php esc_html_e( 'Reset', 'apexdrive' ); ?></button>
				</h3>
				<form id="inventory-filters">
					<div class="filter-group">
						<label for="f-search"><?php esc_html_e( 'Keyword', 'apexdrive' ); ?></label>
						<input type="search" id="f-search" name="s" value="<?php echo esc_attr( $current['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Camry', 'apexdrive' ); ?>">
					</div>

					<div class="filter-group">
						<label for="f-make"><?php esc_html_e( 'Make', 'apexdrive' ); ?></label>
						<select id="f-make" name="make">
							<option value=""><?php esc_html_e( 'All Makes', 'apexdrive' ); ?></option>
							<?php
							$makes = get_terms( array( 'taxonomy' => 'vehicle_make', 'hide_empty' => true ) );
							if ( ! is_wp_error( $makes ) ) :
								foreach ( $makes as $term ) :
									?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current['make'] ?? '', $term->slug ); ?>>
										<?php echo esc_html( $term->name . ' (' . $term->count . ')' ); ?>
									</option>
								<?php endforeach; endif; ?>
						</select>
					</div>

					<div class="filter-group">
						<label for="f-body"><?php esc_html_e( 'Body Type', 'apexdrive' ); ?></label>
						<select id="f-body" name="body">
							<option value=""><?php esc_html_e( 'All Body Types', 'apexdrive' ); ?></option>
							<?php
							$bodies = get_terms( array( 'taxonomy' => 'vehicle_body', 'hide_empty' => true ) );
							if ( ! is_wp_error( $bodies ) ) :
								foreach ( $bodies as $term ) :
									?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current['body'] ?? '', $term->slug ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</option>
								<?php endforeach; endif; ?>
						</select>
					</div>

					<div class="filter-group">
						<label for="f-fuel"><?php esc_html_e( 'Fuel', 'apexdrive' ); ?></label>
						<select id="f-fuel" name="fuel">
							<option value=""><?php esc_html_e( 'All Fuel Types', 'apexdrive' ); ?></option>
							<?php
							$fuels = get_terms( array( 'taxonomy' => 'vehicle_fuel', 'hide_empty' => true ) );
							if ( ! is_wp_error( $fuels ) ) :
								foreach ( $fuels as $term ) :
									?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $current['fuel'] ?? '', $term->slug ); ?>>
										<?php echo esc_html( $term->name ); ?>
									</option>
								<?php endforeach; endif; ?>
						</select>
					</div>

					<div class="filter-group">
						<label><?php esc_html_e( 'Price Range', 'apexdrive' ); ?></label>
						<div class="filter-row">
							<input type="number" name="price_min" min="0" step="500" placeholder="<?php esc_attr_e( 'Min $', 'apexdrive' ); ?>" value="<?php echo esc_attr( $current['price_min'] ?? '' ); ?>">
							<input type="number" name="price_max" min="0" step="500" placeholder="<?php esc_attr_e( 'Max $', 'apexdrive' ); ?>" value="<?php echo esc_attr( $current['price_max'] ?? '' ); ?>">
						</div>
					</div>

					<div class="filter-group">
						<label for="f-year"><?php esc_html_e( 'Min Year', 'apexdrive' ); ?></label>
						<input type="number" id="f-year" name="year_min" min="1990" max="2030" placeholder="<?php esc_attr_e( 'e.g. 2018', 'apexdrive' ); ?>" value="<?php echo esc_attr( $current['year_min'] ?? '' ); ?>">
					</div>

					<div class="filter-group">
						<label for="f-mileage"><?php esc_html_e( 'Max Mileage', 'apexdrive' ); ?></label>
						<select id="f-mileage" name="mileage_max">
							<option value=""><?php esc_html_e( 'Any Mileage', 'apexdrive' ); ?></option>
							<?php foreach ( array( 20000, 40000, 60000, 80000, 100000 ) as $cap ) : ?>
								<option value="<?php echo esc_attr( $cap ); ?>" <?php selected( $current['mileage_max'] ?? '', (string) $cap ); ?>>
									<?php echo esc_html( __( 'Under', 'apexdrive' ) . ' ' . number_format( $cap ) . ' mi' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<button type="submit" class="btn btn-primary btn-block"><?php esc_html_e( 'Apply Filters', 'apexdrive' ); ?></button>
				</form>
			</aside>

			<div>
				<div class="inventory-toolbar">
					<span class="inventory-count">
						<b id="inventory-count"><?php echo esc_html( (int) $wp_query->found_posts ); ?></b>
						<?php esc_html_e( 'vehicles found', 'apexdrive' ); ?>
					</span>
					<select id="inventory-sort" name="sort" aria-label="<?php esc_attr_e( 'Sort', 'apexdrive' ); ?>">
						<option value=""><?php esc_html_e( 'Newest Arrivals', 'apexdrive' ); ?></option>
						<option value="price_asc" <?php selected( $current['sort'] ?? '', 'price_asc' ); ?>><?php esc_html_e( 'Price: Low to High', 'apexdrive' ); ?></option>
						<option value="price_desc" <?php selected( $current['sort'] ?? '', 'price_desc' ); ?>><?php esc_html_e( 'Price: High to Low', 'apexdrive' ); ?></option>
						<option value="mileage_asc" <?php selected( $current['sort'] ?? '', 'mileage_asc' ); ?>><?php esc_html_e( 'Lowest Mileage', 'apexdrive' ); ?></option>
						<option value="year_desc" <?php selected( $current['sort'] ?? '', 'year_desc' ); ?>><?php esc_html_e( 'Newest Model Year', 'apexdrive' ); ?></option>
					</select>
				</div>

				<div class="vehicle-grid inventory-grid" id="inventory-grid" data-max-pages="<?php echo esc_attr( (int) $wp_query->max_num_pages ); ?>">
					<?php if ( have_posts() ) : ?>
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/vehicle-card' );
						endwhile;
						?>
					<?php else : ?>
						<div class="inventory-empty"><?php esc_html_e( 'No vehicles match those filters. Try widening your search.', 'apexdrive' ); ?></div>
					<?php endif; ?>
				</div>

				<div class="load-more-wrap">
					<button class="btn btn-ghost" id="load-more" <?php echo $wp_query->max_num_pages > 1 ? '' : 'hidden'; ?>>
						<?php esc_html_e( 'Load More Vehicles', 'apexdrive' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</section>

<?php get_footer(); ?>

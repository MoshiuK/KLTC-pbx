<footer class="site-footer">
	<div class="container">
		<div class="footer-grid">
			<div class="footer-about">
				<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="brand-mark" aria-hidden="true">▲</span>
					<span><?php echo esc_html( get_bloginfo( 'name' ) ?: 'Apex' ); ?><em>Drive</em></span>
				</a>
				<p><?php echo esc_html( get_bloginfo( 'description' ) ?: __( 'Tech-driven used car dealership. Transparent pricing, inspected inventory, instant financing.', 'apexdrive' ) ); ?></p>
			</div>

			<div>
				<h4><?php esc_html_e( 'Shop', 'apexdrive' ); ?></h4>
				<ul>
					<?php if ( post_type_exists( 'vehicle' ) ) : ?>
						<li><a href="<?php echo esc_url( get_post_type_archive_link( 'vehicle' ) ); ?>"><?php esc_html_e( 'All Inventory', 'apexdrive' ); ?></a></li>
						<?php
						$makes = get_terms( array( 'taxonomy' => 'vehicle_make', 'number' => 5, 'hide_empty' => true ) );
						if ( ! is_wp_error( $makes ) ) :
							foreach ( $makes as $make ) :
								?>
								<li><a href="<?php echo esc_url( get_term_link( $make ) ); ?>"><?php echo esc_html( $make->name ); ?></a></li>
							<?php endforeach; endif; ?>
					<?php endif; ?>
				</ul>
			</div>

			<div>
				<h4><?php esc_html_e( 'Dealership', 'apexdrive' ); ?></h4>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => 'apexdrive_fallback_menu',
				) );
				?>
			</div>

			<div>
				<h4><?php esc_html_e( 'Visit Us', 'apexdrive' ); ?></h4>
				<ul>
					<li><?php echo esc_html( apexdrive_option( 'address', '2100 Motorway Blvd, Knoxville, TN' ) ); ?></li>
					<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', apexdrive_option( 'phone', '(555) 012-3456' ) ) ); ?>"><?php echo esc_html( apexdrive_option( 'phone', '(555) 012-3456' ) ); ?></a></li>
					<li><?php echo esc_html( apexdrive_option( 'hours', 'Mon–Sat 9am–7pm' ) ); ?></li>
				</ul>
			</div>
		</div>

		<div class="footer-bottom">
			<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'apexdrive' ); ?></span>
			<span><?php esc_html_e( 'Prices exclude tax, title, and dealer fees.', 'apexdrive' ); ?></span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

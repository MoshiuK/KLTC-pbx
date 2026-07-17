<?php
/**
 * Generic fallback template (blog index, taxonomy archives, search).
 */

get_header();
?>

<section class="section">
	<div class="container">
		<div class="section-head">
			<div>
				<?php if ( is_tax() || is_archive() ) : ?>
					<span class="kicker"><?php esc_html_e( 'Inventory', 'apexdrive' ); ?></span>
					<h2><?php the_archive_title(); ?></h2>
				<?php elseif ( is_search() ) : ?>
					<h2><?php printf( esc_html__( 'Search results for “%s”', 'apexdrive' ), esc_html( get_search_query() ) ); ?></h2>
				<?php else : ?>
					<h2><?php esc_html_e( 'Latest Posts', 'apexdrive' ); ?></h2>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( have_posts() ) : ?>
			<div class="vehicle-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					if ( 'vehicle' === get_post_type() ) {
						get_template_part( 'template-parts/vehicle-card' );
					} else {
						?>
						<article <?php post_class( 'vehicle-card reveal' ); ?>>
							<div class="vehicle-card-body">
								<h3 class="vehicle-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p class="vehicle-card-sub"><?php echo esc_html( get_the_date() ); ?></p>
								<?php the_excerpt(); ?>
							</div>
						</article>
						<?php
					}
				endwhile;
				?>
			</div>
			<div class="load-more-wrap">
				<?php the_posts_pagination(); ?>
			</div>
		<?php else : ?>
			<div class="inventory-empty"><?php esc_html_e( 'Nothing found.', 'apexdrive' ); ?></div>
		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>

<?php
/**
 * 404 template.
 */

get_header();
?>

<div class="container error-404">
	<div class="code">404</div>
	<h1><?php esc_html_e( 'Wrong turn.', 'apexdrive' ); ?></h1>
	<p style="color:var(--muted);margin:14px 0 28px;"><?php esc_html_e( 'That page drove off the lot. Let’s get you back on the road.', 'apexdrive' ); ?></p>
	<a class="btn btn-primary" href="<?php echo esc_url( post_type_exists( 'vehicle' ) ? get_post_type_archive_link( 'vehicle' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Browse Inventory', 'apexdrive' ); ?></a>
</div>

<?php get_footer(); ?>

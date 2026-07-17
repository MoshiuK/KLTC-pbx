<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" id="site-header">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="brand-mark" aria-hidden="true">▲</span>
				<span><?php echo esc_html( get_bloginfo( 'name' ) ?: 'Apex' ); ?><em>Drive</em></span>
			<?php endif; ?>
		</a>

		<nav class="main-nav" id="main-nav" aria-label="<?php esc_attr_e( 'Primary', 'apexdrive' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => 'apexdrive_fallback_menu',
			) );
			?>
		</nav>

		<div class="header-cta">
			<a class="header-phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', apexdrive_option( 'phone', '(555) 012-3456' ) ) ); ?>">
				<small><?php esc_html_e( 'Call or Text', 'apexdrive' ); ?></small>
				<?php echo esc_html( apexdrive_option( 'phone', '(555) 012-3456' ) ); ?>
			</a>
			<?php if ( post_type_exists( 'vehicle' ) ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( get_post_type_archive_link( 'vehicle' ) ); ?>"><?php esc_html_e( 'Browse Cars', 'apexdrive' ); ?></a>
			<?php endif; ?>
			<button class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="<?php esc_attr_e( 'Toggle menu', 'apexdrive' ); ?>">☰</button>
		</div>
	</div>
</header>

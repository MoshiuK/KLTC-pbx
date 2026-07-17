<?php
/**
 * Lead capture — test-drive requests and general inquiries.
 * Leads are stored as a private post type and emailed to the site admin.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'apex_lead', array(
		'labels' => array(
			'name'          => __( 'Leads', 'apexdrive-inventory' ),
			'singular_name' => __( 'Lead', 'apexdrive-inventory' ),
		),
		'public'       => false,
		'show_ui'      => true,
		'menu_icon'    => 'dashicons-email-alt',
		'menu_position'=> 6,
		'supports'     => array( 'title', 'editor' ),
		'capabilities' => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap' => true,
	) );
} );

/**
 * AJAX handler for the lead form (test drive / inquiry).
 */
function apexdrive_ajax_submit_lead() {
	check_ajax_referer( 'apexdrive_lead', 'nonce' );

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$type    = isset( $_POST['lead_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_type'] ) ) : 'inquiry';
	$vehicle = isset( $_POST['vehicle_id'] ) ? (int) $_POST['vehicle_id'] : 0;

	// Honeypot — bots fill every field; humans never see this one.
	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success( array( 'message' => __( 'Thanks! We will be in touch shortly.', 'apexdrive-inventory' ) ) );
	}

	if ( '' === $name || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please provide your name and a valid email address.', 'apexdrive-inventory' ) ), 400 );
	}

	$vehicle_label = $vehicle ? get_the_title( $vehicle ) : __( 'General', 'apexdrive-inventory' );
	$body  = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nType: {$type}\nVehicle: {$vehicle_label}\n\n{$message}";

	$lead_id = wp_insert_post( array(
		'post_type'    => 'apex_lead',
		'post_status'  => 'private',
		'post_title'   => sprintf( '[%s] %s — %s', strtoupper( $type ), $name, $vehicle_label ),
		'post_content' => $body,
	) );

	if ( $lead_id ) {
		update_post_meta( $lead_id, '_apexdrive_lead_email', $email );
		update_post_meta( $lead_id, '_apexdrive_lead_phone', $phone );
		update_post_meta( $lead_id, '_apexdrive_lead_vehicle', $vehicle );
	}

	wp_mail(
		get_option( 'admin_email' ),
		sprintf( __( 'New %1$s lead: %2$s', 'apexdrive-inventory' ), $type, $vehicle_label ),
		$body,
		array( 'Reply-To: ' . $email )
	);

	wp_send_json_success( array( 'message' => __( 'Thanks! We will be in touch shortly.', 'apexdrive-inventory' ) ) );
}
add_action( 'wp_ajax_apexdrive_lead', 'apexdrive_ajax_submit_lead' );
add_action( 'wp_ajax_nopriv_apexdrive_lead', 'apexdrive_ajax_submit_lead' );

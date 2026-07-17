<?php
/**
 * One-click demo data — seeds makes, body types, fuels, and 12 sample vehicles
 * so the site looks alive before real inventory is entered.
 *
 * Tools → ApexDrive Demo Data, or: wp eval 'apexdrive_seed_demo_data();'
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_management_page(
		__( 'ApexDrive Demo Data', 'apexdrive-inventory' ),
		__( 'ApexDrive Demo Data', 'apexdrive-inventory' ),
		'manage_options',
		'apexdrive-demo',
		'apexdrive_render_demo_page'
	);
} );

function apexdrive_render_demo_page() {
	if ( isset( $_POST['apexdrive_seed'] ) && check_admin_referer( 'apexdrive_seed_demo' ) ) {
		$count = apexdrive_seed_demo_data();
		printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( __( 'Seeded %d demo vehicles.', 'apexdrive-inventory' ), $count ) ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'ApexDrive Demo Data', 'apexdrive-inventory' ); ?></h1>
		<p><?php esc_html_e( 'Creates sample makes, body types, fuel types, and 12 demo vehicles. Safe to run once; running again creates duplicates.', 'apexdrive-inventory' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'apexdrive_seed_demo' ); ?>
			<p><button class="button button-primary" name="apexdrive_seed" value="1"><?php esc_html_e( 'Seed Demo Vehicles', 'apexdrive-inventory' ); ?></button></p>
		</form>
	</div>
	<?php
}

function apexdrive_seed_demo_data() {
	$vehicles = array(
		array( 'title' => '2021 Tesla Model 3 Long Range', 'make' => 'Tesla', 'body' => 'Sedan', 'fuel' => 'Electric', 'price' => 28995, 'year' => 2021, 'mileage' => 31200, 'transmission' => 'Automatic', 'drivetrain' => 'AWD', 'engine' => 'Dual Motor Electric', 'mpg' => '134/126 MPGe', 'exterior' => 'Pearl White', 'interior' => 'Black', 'featured' => '1' ),
		array( 'title' => '2019 Toyota Camry SE', 'make' => 'Toyota', 'body' => 'Sedan', 'fuel' => 'Gasoline', 'price' => 18495, 'year' => 2019, 'mileage' => 44780, 'transmission' => 'Automatic', 'drivetrain' => 'FWD', 'engine' => '2.5L I4', 'mpg' => '28/39', 'exterior' => 'Celestial Silver', 'interior' => 'Black', 'featured' => '1' ),
		array( 'title' => '2020 Ford F-150 XLT SuperCrew', 'make' => 'Ford', 'body' => 'Truck', 'fuel' => 'Gasoline', 'price' => 32900, 'year' => 2020, 'mileage' => 51340, 'transmission' => 'Automatic', 'drivetrain' => '4WD', 'engine' => '3.5L EcoBoost V6', 'mpg' => '18/24', 'exterior' => 'Oxford White', 'interior' => 'Gray', 'featured' => '1' ),
		array( 'title' => '2022 Honda CR-V EX-L', 'make' => 'Honda', 'body' => 'SUV', 'fuel' => 'Gasoline', 'price' => 27450, 'year' => 2022, 'mileage' => 18960, 'transmission' => 'CVT', 'drivetrain' => 'AWD', 'engine' => '1.5L Turbo I4', 'mpg' => '27/32', 'exterior' => 'Modern Steel', 'interior' => 'Ivory Leather', 'featured' => '1' ),
		array( 'title' => '2018 BMW 330i xDrive', 'make' => 'BMW', 'body' => 'Sedan', 'fuel' => 'Gasoline', 'price' => 21995, 'year' => 2018, 'mileage' => 58200, 'transmission' => 'Automatic', 'drivetrain' => 'AWD', 'engine' => '2.0L Turbo I4', 'mpg' => '23/33', 'exterior' => 'Alpine White', 'interior' => 'Cognac Leather' ),
		array( 'title' => '2021 Jeep Wrangler Unlimited Sahara', 'make' => 'Jeep', 'body' => 'SUV', 'fuel' => 'Gasoline', 'price' => 36750, 'year' => 2021, 'mileage' => 27430, 'transmission' => 'Automatic', 'drivetrain' => '4WD', 'engine' => '3.6L V6', 'mpg' => '19/24', 'exterior' => 'Firecracker Red', 'interior' => 'Black' ),
		array( 'title' => '2020 Chevrolet Equinox LT', 'make' => 'Chevrolet', 'body' => 'SUV', 'fuel' => 'Gasoline', 'price' => 19875, 'year' => 2020, 'mileage' => 39800, 'transmission' => 'Automatic', 'drivetrain' => 'FWD', 'engine' => '1.5L Turbo I4', 'mpg' => '26/31', 'exterior' => 'Summit White', 'interior' => 'Jet Black' ),
		array( 'title' => '2019 Audi Q5 Premium Plus', 'make' => 'Audi', 'body' => 'SUV', 'fuel' => 'Gasoline', 'price' => 26900, 'year' => 2019, 'mileage' => 46100, 'transmission' => 'Dual-Clutch', 'drivetrain' => 'AWD', 'engine' => '2.0L Turbo I4', 'mpg' => '22/27', 'exterior' => 'Mythos Black', 'interior' => 'Rock Gray' ),
		array( 'title' => '2022 Hyundai Ioniq 5 SEL', 'make' => 'Hyundai', 'body' => 'SUV', 'fuel' => 'Electric', 'price' => 31200, 'year' => 2022, 'mileage' => 15600, 'transmission' => 'Automatic', 'drivetrain' => 'RWD', 'engine' => 'Electric Motor', 'mpg' => '132/98 MPGe', 'exterior' => 'Cyber Gray', 'interior' => 'Dark Pebble' ),
		array( 'title' => '2017 Honda Civic Hatchback Sport', 'make' => 'Honda', 'body' => 'Hatchback', 'fuel' => 'Gasoline', 'price' => 15490, 'year' => 2017, 'mileage' => 67900, 'transmission' => 'Manual', 'drivetrain' => 'FWD', 'engine' => '1.5L Turbo I4', 'mpg' => '30/39', 'exterior' => 'Rallye Red', 'interior' => 'Black' ),
		array( 'title' => '2021 Toyota RAV4 Hybrid XLE', 'make' => 'Toyota', 'body' => 'SUV', 'fuel' => 'Hybrid', 'price' => 29350, 'year' => 2021, 'mileage' => 24870, 'transmission' => 'CVT', 'drivetrain' => 'AWD', 'engine' => '2.5L I4 Hybrid', 'mpg' => '41/38', 'exterior' => 'Blueprint', 'interior' => 'Light Gray' ),
		array( 'title' => '2018 Ford Mustang GT Premium', 'make' => 'Ford', 'body' => 'Coupe', 'fuel' => 'Gasoline', 'price' => 30995, 'year' => 2018, 'mileage' => 41500, 'transmission' => 'Manual', 'drivetrain' => 'RWD', 'engine' => '5.0L V8', 'mpg' => '15/25', 'exterior' => 'Shadow Black', 'interior' => 'Ebony Leather' ),
	);

	$features = "Backup Camera\nBluetooth\nApple CarPlay / Android Auto\nLane Keep Assist\nAdaptive Cruise Control\nHeated Seats\nKeyless Entry\nAlloy Wheels";
	$count = 0;

	foreach ( $vehicles as $i => $v ) {
		$post_id = wp_insert_post( array(
			'post_type'    => 'vehicle',
			'post_status'  => 'publish',
			'post_title'   => $v['title'],
			'post_content' => sprintf(
				"One-owner %s in excellent condition. Fully inspected by our certified technicians, clean history report, and backed by our 90-day powertrain warranty. Financing available with rates as low as 4.9%% APR.",
				$v['title']
			),
			'post_excerpt' => sprintf( 'Clean title • Inspected • %s • %s', $v['drivetrain'], $v['transmission'] ),
		) );

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			continue;
		}

		wp_set_object_terms( $post_id, $v['make'], 'vehicle_make' );
		wp_set_object_terms( $post_id, $v['body'], 'vehicle_body' );
		wp_set_object_terms( $post_id, $v['fuel'], 'vehicle_fuel' );
		wp_set_object_terms( $post_id, array( 'Clean Title', 'Inspected' ), 'vehicle_condition' );

		$meta = array(
			'price'        => $v['price'],
			'year'         => $v['year'],
			'mileage'      => $v['mileage'],
			'transmission' => $v['transmission'],
			'drivetrain'   => $v['drivetrain'],
			'engine'       => $v['engine'],
			'mpg'          => $v['mpg'],
			'exterior'     => $v['exterior'],
			'interior'     => $v['interior'],
			'vin'          => strtoupper( substr( md5( $v['title'] ), 0, 17 ) ),
			'stock_no'     => 'AX-' . str_pad( (string) ( 1001 + $i ), 4, '0', STR_PAD_LEFT ),
			'owners'       => 1,
			'status'       => 'available',
			'featured'     => $v['featured'] ?? '',
			'features'     => $features,
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, '_apexdrive_' . $key, $value );
		}

		$count++;
	}

	return $count;
}

<?php
/**
 * Industries offered during setup — drives CPT labels, booking type rows, and help links.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Static catalog of booking verticals.
 */
final class IndustryCatalog {

	/**
	 * Core definitions keyed by stable slug fragment (also used in JSON settings).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions() {
		return array(
			'car_rental'            => self::item(
				__( 'Cars / vehicle rental', 'flex-multiple-listing-and-booking-system' ),
				__( 'Fleet vehicles, daily or hourly pickup & return.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Car rentals', 'flex-multiple-listing-and-booking-system' ),
				'car-rental',
				'ulbm_car_booking',
				__( 'Car booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Car bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'hotel_accommodation'   => self::item(
				__( 'Hotels & accommodation', 'flex-multiple-listing-and-booking-system' ),
				__( 'Rooms, suites, hostels, vacation rentals.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Stays & rooms', 'flex-multiple-listing-and-booking-system' ),
				'hotel-stays',
				'ulbm_hotel_booking',
				__( 'Room booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Room bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'events_tickets'        => self::item(
				__( 'Events & tickets', 'flex-multiple-listing-and-booking-system' ),
				__( 'Seats, venues, performances, conferences.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Event bookings', 'flex-multiple-listing-and-booking-system' ),
				'events',
				'ulbm_event_booking',
				__( 'Event booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Event bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'appointments_services' => self::item(
				__( 'Appointments & services', 'flex-multiple-listing-and-booking-system' ),
				__( 'Salons, clinics, consultants, one-to-one sessions.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Appointments', 'flex-multiple-listing-and-booking-system' ),
				'appointments',
				'ulbm_appointment_booking',
				__( 'Appointment', 'flex-multiple-listing-and-booking-system' ),
				__( 'Appointments', 'flex-multiple-listing-and-booking-system' )
			),
			'tours_activities'      => self::item(
				__( 'Tours & activities', 'flex-multiple-listing-and-booking-system' ),
				__( 'Guided tours, excursions, outdoor experiences.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Tours', 'flex-multiple-listing-and-booking-system' ),
				'tours',
				'ulbm_tour_booking',
				__( 'Tour booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Tour bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'equipment_rental'      => self::item(
				__( 'Equipment rental', 'flex-multiple-listing-and-booking-system' ),
				__( 'Cameras, bikes, tools, AV gear.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Equipment', 'flex-multiple-listing-and-booking-system' ),
				'equipment',
				'ulbm_equipment_booking',
				__( 'Equipment booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Equipment bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'classes_courses'       => self::item(
				__( 'Classes & courses', 'flex-multiple-listing-and-booking-system' ),
				__( 'Workshops, training seats, recurring cohorts.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Classes', 'flex-multiple-listing-and-booking-system' ),
				'classes',
				'ulbm_class_booking',
				__( 'Class booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Class bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'restaurant_tables'     => self::item(
				__( 'Restaurant tables', 'flex-multiple-listing-and-booking-system' ),
				__( 'Reservations by time slot and party size.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Table reservations', 'flex-multiple-listing-and-booking-system' ),
				'restaurant-tables',
				'ulbm_restaurant_booking',
				__( 'Table booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Table bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'workspace_desks'       => self::item(
				__( 'Coworking / desks', 'flex-multiple-listing-and-booking-system' ),
				__( 'Hot desks, meeting rooms, day offices.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Workspace', 'flex-multiple-listing-and-booking-system' ),
				'workspace',
				'ulbm_workspace_booking',
				__( 'Desk booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Desk bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'boats_charters'        => self::item(
				__( 'Boats & charters', 'flex-multiple-listing-and-booking-system' ),
				__( 'Marina rentals, captained charters, yacht days.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Boat charters', 'flex-multiple-listing-and-booking-system' ),
				'boats',
				'ulbm_boat_booking',
				__( 'Boat booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Boat bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'spa_wellness'          => self::item(
				__( 'Spa & wellness', 'flex-multiple-listing-and-booking-system' ),
				__( 'Massage, spa packages, thermal slots.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Spa & wellness', 'flex-multiple-listing-and-booking-system' ),
				'spa-wellness',
				'ulbm_spa_booking',
				__( 'Spa booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Spa bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'sports_facilities'     => self::item(
				__( 'Sports facilities', 'flex-multiple-listing-and-booking-system' ),
				__( 'Courts, pitches, lanes, studios by the hour.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Sports bookings', 'flex-multiple-listing-and-booking-system' ),
				'sports',
				'ulbm_sport_booking',
				__( 'Facility booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Facility bookings', 'flex-multiple-listing-and-booking-system' )
			),
			'transport_shuttles'    => self::item(
				__( 'Transport & shuttles', 'flex-multiple-listing-and-booking-system' ),
				__( 'Airport rides, shuttles, route seats.', 'flex-multiple-listing-and-booking-system' ),
				__( 'Shuttle & rides', 'flex-multiple-listing-and-booking-system' ),
				'shuttles',
				'ulbm_transport_booking',
				__( 'Shuttle booking', 'flex-multiple-listing-and-booking-system' ),
				__( 'Shuttle bookings', 'flex-multiple-listing-and-booking-system' )
			),
		);
	}

	/**
	 * Curated third-party options (informational — not bundled).
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function professional_integrations() {
		return array(
			array(
				'title' => __( 'WordPress booking plugins', 'flex-multiple-listing-and-booking-system' ),
				'items' => array(
					array(
						'name' => 'WooCommerce Bookings',
						'url'  => 'https://woocommerce.com/products/woocommerce-bookings/',
						'note' => __( 'Deep WooCommerce integration for bookable products.', 'flex-multiple-listing-and-booking-system' ),
					),
					array(
						'name' => 'Amelia',
						'url'  => 'https://wpamelia.com/',
						'note' => __( 'Appointments & events with calendars and payments.', 'flex-multiple-listing-and-booking-system' ),
					),
					array(
						'name' => 'Bookly',
						'url'  => 'https://www.bookly-online.com/',
						'note' => __( 'Scheduling with staff, services, and reminders.', 'flex-multiple-listing-and-booking-system' ),
					),
					array(
						'name' => 'Booking Calendar',
						'url'  => 'https://wordpress.org/plugins/booking/',
						'note' => __( 'Availability calendars and reservation forms.', 'flex-multiple-listing-and-booking-system' ),
					),
				),
			),
			array(
				'title' => __( 'SaaS & platforms', 'flex-multiple-listing-and-booking-system' ),
				'items' => array(
					array(
						'name' => 'Cal.com',
						'url'  => 'https://cal.com/',
						'note' => __( 'Open scheduling infrastructure with routing forms.', 'flex-multiple-listing-and-booking-system' ),
					),
					array(
						'name' => 'SimplyBook.me',
						'url'  => 'https://simplybook.me/',
						'note' => __( 'Online booking for services with sites & widgets.', 'flex-multiple-listing-and-booking-system' ),
					),
					array(
						'name' => 'FareHarbor',
						'url'  => 'https://fareharbor.com/',
						'note' => __( 'Tours & activities distribution (operators).', 'flex-multiple-listing-and-booking-system' ),
					),
					array(
						'name' => 'Checkfront',
						'url'  => 'https://www.checkfront.com/',
						'note' => __( 'Tours, rentals, and activities booking platform.', 'flex-multiple-listing-and-booking-system' ),
					),
				),
			),
		);
	}

	/**
	 * Valid industry keys.
	 *
	 * @return string[]
	 */
	public static function valid_keys() {
		return array_keys( self::definitions() );
	}

	/**
	 * Single definition or null.
	 *
	 * @param string $key Industry key.
	 * @return array<string, mixed>|null
	 */
	public static function get( $key ) {
		$all = self::definitions();
		return $all[ $key ] ?? null;
	}

	/**
	 * Build one catalog row.
	 *
	 * @param string $select_label   Checkbox label.
	 * @param string $description    Short help text.
	 * @param string $type_name       Booking type display name.
	 * @param string $booking_slug    Unique slug in ulbm_booking_types.
	 * @param string $post_type       CPT name.
	 * @param string $singular        CPT singular label.
	 * @param string $plural          CPT plural label.
	 * @return array<string, mixed>
	 */
	private static function item( $select_label, $description, $type_name, $booking_slug, $post_type, $singular, $plural ) {
		return array(
			'select_label' => $select_label,
			'description'  => $description,
			'type_name'      => $type_name,
			'booking_slug'   => $booking_slug,
			'post_type'      => $post_type,
			'singular'       => $singular,
			'plural'         => $plural,
			'module_key'     => 'generic',
		);
	}
}

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
				__( 'Cars / vehicle rental', 'flex-booking-system' ),
				__( 'Fleet vehicles, daily or hourly pickup & return.', 'flex-booking-system' ),
				__( 'Car rentals', 'flex-booking-system' ),
				'car-rental',
				'fbs_car_booking',
				__( 'Car booking', 'flex-booking-system' ),
				__( 'Car bookings', 'flex-booking-system' )
			),
			'hotel_accommodation'   => self::item(
				__( 'Hotels & accommodation', 'flex-booking-system' ),
				__( 'Rooms, suites, hostels, vacation rentals.', 'flex-booking-system' ),
				__( 'Stays & rooms', 'flex-booking-system' ),
				'hotel-stays',
				'fbs_hotel_booking',
				__( 'Room booking', 'flex-booking-system' ),
				__( 'Room bookings', 'flex-booking-system' )
			),
			'events_tickets'        => self::item(
				__( 'Events & tickets', 'flex-booking-system' ),
				__( 'Seats, venues, performances, conferences.', 'flex-booking-system' ),
				__( 'Event bookings', 'flex-booking-system' ),
				'events',
				'fbs_event_booking',
				__( 'Event booking', 'flex-booking-system' ),
				__( 'Event bookings', 'flex-booking-system' )
			),
			'appointments_services' => self::item(
				__( 'Appointments & services', 'flex-booking-system' ),
				__( 'Salons, clinics, consultants, one-to-one sessions.', 'flex-booking-system' ),
				__( 'Appointments', 'flex-booking-system' ),
				'appointments',
				'fbs_appointment_booking',
				__( 'Appointment', 'flex-booking-system' ),
				__( 'Appointments', 'flex-booking-system' )
			),
			'tours_activities'      => self::item(
				__( 'Tours & activities', 'flex-booking-system' ),
				__( 'Guided tours, excursions, outdoor experiences.', 'flex-booking-system' ),
				__( 'Tours', 'flex-booking-system' ),
				'tours',
				'fbs_tour_booking',
				__( 'Tour booking', 'flex-booking-system' ),
				__( 'Tour bookings', 'flex-booking-system' )
			),
			'equipment_rental'      => self::item(
				__( 'Equipment rental', 'flex-booking-system' ),
				__( 'Cameras, bikes, tools, AV gear.', 'flex-booking-system' ),
				__( 'Equipment', 'flex-booking-system' ),
				'equipment',
				'fbs_equipment_booking',
				__( 'Equipment booking', 'flex-booking-system' ),
				__( 'Equipment bookings', 'flex-booking-system' )
			),
			'classes_courses'       => self::item(
				__( 'Classes & courses', 'flex-booking-system' ),
				__( 'Workshops, training seats, recurring cohorts.', 'flex-booking-system' ),
				__( 'Classes', 'flex-booking-system' ),
				'classes',
				'fbs_class_booking',
				__( 'Class booking', 'flex-booking-system' ),
				__( 'Class bookings', 'flex-booking-system' )
			),
			'restaurant_tables'     => self::item(
				__( 'Restaurant tables', 'flex-booking-system' ),
				__( 'Reservations by time slot and party size.', 'flex-booking-system' ),
				__( 'Table reservations', 'flex-booking-system' ),
				'restaurant-tables',
				'fbs_restaurant_booking',
				__( 'Table booking', 'flex-booking-system' ),
				__( 'Table bookings', 'flex-booking-system' )
			),
			'workspace_desks'       => self::item(
				__( 'Coworking / desks', 'flex-booking-system' ),
				__( 'Hot desks, meeting rooms, day offices.', 'flex-booking-system' ),
				__( 'Workspace', 'flex-booking-system' ),
				'workspace',
				'fbs_workspace_booking',
				__( 'Desk booking', 'flex-booking-system' ),
				__( 'Desk bookings', 'flex-booking-system' )
			),
			'boats_charters'        => self::item(
				__( 'Boats & charters', 'flex-booking-system' ),
				__( 'Marina rentals, captained charters, yacht days.', 'flex-booking-system' ),
				__( 'Boat charters', 'flex-booking-system' ),
				'boats',
				'fbs_boat_booking',
				__( 'Boat booking', 'flex-booking-system' ),
				__( 'Boat bookings', 'flex-booking-system' )
			),
			'spa_wellness'          => self::item(
				__( 'Spa & wellness', 'flex-booking-system' ),
				__( 'Massage, spa packages, thermal slots.', 'flex-booking-system' ),
				__( 'Spa & wellness', 'flex-booking-system' ),
				'spa-wellness',
				'fbs_spa_booking',
				__( 'Spa booking', 'flex-booking-system' ),
				__( 'Spa bookings', 'flex-booking-system' )
			),
			'sports_facilities'     => self::item(
				__( 'Sports facilities', 'flex-booking-system' ),
				__( 'Courts, pitches, lanes, studios by the hour.', 'flex-booking-system' ),
				__( 'Sports bookings', 'flex-booking-system' ),
				'sports',
				'fbs_sport_booking',
				__( 'Facility booking', 'flex-booking-system' ),
				__( 'Facility bookings', 'flex-booking-system' )
			),
			'transport_shuttles'    => self::item(
				__( 'Transport & shuttles', 'flex-booking-system' ),
				__( 'Airport rides, shuttles, route seats.', 'flex-booking-system' ),
				__( 'Shuttle & rides', 'flex-booking-system' ),
				'shuttles',
				'fbs_transport_booking',
				__( 'Shuttle booking', 'flex-booking-system' ),
				__( 'Shuttle bookings', 'flex-booking-system' )
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
				'title' => __( 'WordPress booking plugins', 'flex-booking-system' ),
				'items' => array(
					array(
						'name' => 'WooCommerce Bookings',
						'url'  => 'https://woocommerce.com/products/woocommerce-bookings/',
						'note' => __( 'Deep WooCommerce integration for bookable products.', 'flex-booking-system' ),
					),
					array(
						'name' => 'Amelia',
						'url'  => 'https://wpamelia.com/',
						'note' => __( 'Appointments & events with calendars and payments.', 'flex-booking-system' ),
					),
					array(
						'name' => 'Bookly',
						'url'  => 'https://www.bookly-online.com/',
						'note' => __( 'Scheduling with staff, services, and reminders.', 'flex-booking-system' ),
					),
					array(
						'name' => 'Booking Calendar',
						'url'  => 'https://wordpress.org/plugins/booking/',
						'note' => __( 'Availability calendars and reservation forms.', 'flex-booking-system' ),
					),
				),
			),
			array(
				'title' => __( 'SaaS & platforms', 'flex-booking-system' ),
				'items' => array(
					array(
						'name' => 'Cal.com',
						'url'  => 'https://cal.com/',
						'note' => __( 'Open scheduling infrastructure with routing forms.', 'flex-booking-system' ),
					),
					array(
						'name' => 'SimplyBook.me',
						'url'  => 'https://simplybook.me/',
						'note' => __( 'Online booking for services with sites & widgets.', 'flex-booking-system' ),
					),
					array(
						'name' => 'FareHarbor',
						'url'  => 'https://fareharbor.com/',
						'note' => __( 'Tours & activities distribution (operators).', 'flex-booking-system' ),
					),
					array(
						'name' => 'Checkfront',
						'url'  => 'https://www.checkfront.com/',
						'note' => __( 'Tours, rentals, and activities booking platform.', 'flex-booking-system' ),
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
	 * @param string $booking_slug    Unique slug in fbs_booking_types.
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

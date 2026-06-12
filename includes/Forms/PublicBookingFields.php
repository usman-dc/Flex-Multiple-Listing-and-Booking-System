<?php
/**
 * Public booking form field groups: contact + industry-specific questions.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Forms;

use FlexBooking\PostTypes\BookingTypePostTypeRegistry;
use FlexBooking\Setup\IndustryCatalog;

defined( 'ABSPATH' ) || exit;

/**
 * Declares fields rendered on [ulbm_booking_form] per catalog industry key.
 */
final class PublicBookingFields {

	/**
	 * Resolve industry key from booking type row.
	 *
	 * @param array<string, mixed>|null $booking_type Row from ulbm_booking_types or null.
	 * @return string Industry key or 'generic'.
	 */
	public static function industry_from_type( $booking_type ) {
		if ( ! is_array( $booking_type ) ) {
			return 'generic';
		}
		$settings = array();
		if ( ! empty( $booking_type['settings'] ) ) {
			$decoded = json_decode( (string) $booking_type['settings'], true );
			if ( is_array( $decoded ) ) {
				$settings = $decoded;
			}
		}
		$key = isset( $settings['industry'] ) ? sanitize_key( (string) $settings['industry'] ) : '';
		if ( $key && in_array( $key, IndustryCatalog::valid_keys(), true ) ) {
			return $key;
		}

		if ( ! empty( $booking_type['slug'] ) ) {
			$from_slug = self::industry_from_slug( (string) $booking_type['slug'] );
			if ( 'generic' !== $from_slug ) {
				return $from_slug;
			}
		}

		return 'generic';
	}

	/**
	 * Resolve industry from booking type slug.
	 *
	 * @param string $slug Booking type slug.
	 * @return string
	 */
	public static function industry_from_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		foreach ( IndustryCatalog::definitions() as $key => $def ) {
			if ( (string) ( $def['booking_slug'] ?? '' ) === $slug ) {
				return $key;
			}
		}

		return 'generic';
	}

	/**
	 * Resolve industry from listing post type.
	 *
	 * @param string $post_type Post type name.
	 * @return string
	 */
	public static function industry_from_post_type( $post_type ) {
		$post_type = sanitize_key( (string) $post_type );
		if ( '' === $post_type ) {
			return 'generic';
		}

		foreach ( IndustryCatalog::definitions() as $key => $def ) {
			if ( (string) ( $def['post_type'] ?? '' ) === $post_type ) {
				return $key;
			}
		}

		$match = BookingTypePostTypeRegistry::booking_type_for_post_type( $post_type );
		if ( $match && ! empty( $match['slug'] ) ) {
			return self::industry_from_slug( (string) $match['slug'] );
		}

		return 'generic';
	}

	/**
	 * Sidebar widget heading per industry.
	 *
	 * @param string $industry Industry key.
	 * @return string
	 */
	public static function widget_title_for_industry( $industry ) {
		$titles = array(
			'car_rental'            => __( 'Book This Vehicle', 'flex-multiple-listing-and-booking-system' ),
			'hotel_accommodation'   => __( 'Book Your Stay', 'flex-multiple-listing-and-booking-system' ),
			'events_tickets'        => __( 'Book Tickets', 'flex-multiple-listing-and-booking-system' ),
			'appointments_services' => __( 'Book Appointment', 'flex-multiple-listing-and-booking-system' ),
			'tours_activities'      => __( 'Book This Tour', 'flex-multiple-listing-and-booking-system' ),
			'equipment_rental'      => __( 'Rent Equipment', 'flex-multiple-listing-and-booking-system' ),
			'classes_courses'       => __( 'Enroll Now', 'flex-multiple-listing-and-booking-system' ),
			'restaurant_tables'     => __( 'Reserve a Table', 'flex-multiple-listing-and-booking-system' ),
			'workspace_desks'       => __( 'Book Workspace', 'flex-multiple-listing-and-booking-system' ),
			'boats_charters'        => __( 'Book Charter', 'flex-multiple-listing-and-booking-system' ),
			'spa_wellness'          => __( 'Book Treatment', 'flex-multiple-listing-and-booking-system' ),
			'sports_facilities'     => __( 'Book Facility', 'flex-multiple-listing-and-booking-system' ),
			'transport_shuttles'    => __( 'Book Transfer', 'flex-multiple-listing-and-booking-system' ),
		);

		$industry = sanitize_key( (string) $industry );

		return $titles[ $industry ] ?? __( 'Book Now', 'flex-multiple-listing-and-booking-system' );
	}

	/**
	 * Marketplace schedule field config per industry.
	 *
	 * @param string $industry Industry key.
	 * @return array<string, mixed>
	 */
	public static function schedule_for_industry( $industry ) {
		$industry = sanitize_key( (string) $industry );

		$map = array(
			'car_rental' => array(
				'start_label'   => __( 'Pick-up date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'Return date', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => true,
				'price_unit'    => 'day',
				'unit_label'    => __( 'days', 'flex-multiple-listing-and-booking-system' ),
			),
			'equipment_rental' => array(
				'start_label'   => __( 'Pick-up date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'Return date', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => true,
				'price_unit'    => 'day',
				'unit_label'    => __( 'days', 'flex-multiple-listing-and-booking-system' ),
			),
			'transport_shuttles' => array(
				'start_label'   => __( 'Pick-up date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'Return date', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => true,
				'price_unit'    => 'day',
				'unit_label'    => __( 'days', 'flex-multiple-listing-and-booking-system' ),
			),
			'hotel_accommodation' => array(
				'start_label'   => __( 'Check-in', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'Check-out', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => true,
				'show_end_date' => true,
				'price_unit'    => 'night',
				'unit_label'    => __( 'nights', 'flex-multiple-listing-and-booking-system' ),
			),
			'boats_charters' => array(
				'start_label'   => __( 'Charter start', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'Charter end', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => true,
				'price_unit'    => 'day',
				'unit_label'    => __( 'days', 'flex-multiple-listing-and-booking-system' ),
			),
			'workspace_desks' => array(
				'start_label'   => __( 'Start date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'End date', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => true,
				'price_unit'    => 'day',
				'unit_label'    => __( 'days', 'flex-multiple-listing-and-booking-system' ),
			),
			'appointments_services' => array(
				'start_label'   => __( 'Appointment date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'End date', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => false,
				'price_unit'    => 'session',
				'unit_label'    => __( 'sessions', 'flex-multiple-listing-and-booking-system' ),
			),
			'spa_wellness' => array(
				'start_label'   => __( 'Preferred date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => '',
				'show_guests'   => false,
				'show_end_date' => false,
				'price_unit'    => 'session',
				'unit_label'    => __( 'sessions', 'flex-multiple-listing-and-booking-system' ),
			),
			'tours_activities' => array(
				'start_label'   => __( 'Tour date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'Return date', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => false,
				'price_unit'    => 'person',
				'unit_label'    => __( 'guests', 'flex-multiple-listing-and-booking-system' ),
			),
			'events_tickets' => array(
				'start_label'   => __( 'Event date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => '',
				'show_guests'   => false,
				'show_end_date' => false,
				'price_unit'    => 'ticket',
				'unit_label'    => __( 'tickets', 'flex-multiple-listing-and-booking-system' ),
			),
			'restaurant_tables' => array(
				'start_label'   => __( 'Reservation date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => '',
				'show_guests'   => false,
				'show_end_date' => false,
				'price_unit'    => 'booking',
				'unit_label'    => __( 'bookings', 'flex-multiple-listing-and-booking-system' ),
			),
			'classes_courses' => array(
				'start_label'   => __( 'Class date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => '',
				'show_guests'   => false,
				'show_end_date' => false,
				'price_unit'    => 'seat',
				'unit_label'    => __( 'seats', 'flex-multiple-listing-and-booking-system' ),
			),
			'sports_facilities' => array(
				'start_label'   => __( 'Booking date', 'flex-multiple-listing-and-booking-system' ),
				'end_label'     => __( 'End date', 'flex-multiple-listing-and-booking-system' ),
				'show_guests'   => false,
				'show_end_date' => true,
				'price_unit'    => 'hour',
				'unit_label'    => __( 'hours', 'flex-multiple-listing-and-booking-system' ),
			),
		);

		$defaults = array(
			'start_label'   => __( 'Start date', 'flex-multiple-listing-and-booking-system' ),
			'end_label'     => __( 'End date', 'flex-multiple-listing-and-booking-system' ),
			'show_guests'   => true,
			'show_end_date' => true,
			'price_unit'    => 'night',
			'unit_label'    => __( 'nights', 'flex-multiple-listing-and-booking-system' ),
		);

		return array_merge( $defaults, $map[ $industry ] ?? array() );
	}

	/**
	 * Fields skipped in marketplace sidebar (collected elsewhere).
	 *
	 * @param string $industry Industry key.
	 * @return string[]
	 */
	public static function marketplace_skip_fields( $industry ) {
		$skip = array( 'guests_count' );

		if ( 'hotel_accommodation' === sanitize_key( $industry ) ) {
			$skip[] = 'guests_count';
		}

		return array_unique( $skip );
	}

	/**
	 * Contact fields (always shown).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function contact_fields() {
		return array(
			array(
				'name'     => 'customer_first_name',
				'label'    => __( 'First name', 'flex-multiple-listing-and-booking-system' ),
				'type'     => 'text',
				'required' => true,
				'col'      => 'col-md-6',
			),
			array(
				'name'     => 'customer_last_name',
				'label'    => __( 'Last name', 'flex-multiple-listing-and-booking-system' ),
				'type'     => 'text',
				'required' => true,
				'col'      => 'col-md-6',
			),
			array(
				'name'     => 'customer_email',
				'label'    => __( 'Email', 'flex-multiple-listing-and-booking-system' ),
				'type'     => 'email',
				'required' => true,
				'col'      => 'col-md-6',
			),
			array(
				'name'     => 'customer_phone',
				'label'    => __( 'Mobile / phone', 'flex-multiple-listing-and-booking-system' ),
				'type'     => 'tel',
				'required' => true,
				'col'      => 'col-md-6',
			),
		);
	}

	/**
	 * Extra fields keyed by industry catalog slug.
	 *
	 * @param string $industry Industry key.
	 * @return array<int, array<string, mixed>>
	 */
	public static function extra_fields_for_industry( $industry ) {
		$industry = sanitize_key( (string) $industry );

		$map = array(
			'car_rental'            => array(
				array(
					'name'     => 'vehicle_category',
					'label'    => __( 'Vehicle type', 'flex-multiple-listing-and-booking-system' ),
					'type'     => 'select',
					'required' => true,
					'col'      => 'col-md-6',
					'options'  => array(
						'compact' => __( 'Compact', 'flex-multiple-listing-and-booking-system' ),
						'sedan'   => __( 'Sedan', 'flex-multiple-listing-and-booking-system' ),
						'suv'     => __( 'SUV', 'flex-multiple-listing-and-booking-system' ),
						'van'     => __( 'Van / minibus', 'flex-multiple-listing-and-booking-system' ),
						'luxury'  => __( 'Luxury', 'flex-multiple-listing-and-booking-system' ),
					),
				),
				array(
					'name'        => 'pickup_location',
					'label'       => __( 'Pickup location', 'flex-multiple-listing-and-booking-system' ),
					'type'        => 'text',
					'required'    => true,
					'col'         => 'col-md-6',
					'placeholder' => __( 'City, airport, or address', 'flex-multiple-listing-and-booking-system' ),
				),
				array(
					'name'        => 'dropoff_location',
					'label'       => __( 'Return / drop-off location', 'flex-multiple-listing-and-booking-system' ),
					'type'        => 'text',
					'required'    => true,
					'col'         => 'col-md-6',
					'placeholder' => __( 'Same as pickup or other address', 'flex-multiple-listing-and-booking-system' ),
				),
				array(
					'name'        => 'route_notes',
					'label'       => __( 'Route or trip notes', 'flex-multiple-listing-and-booking-system' ),
					'type'        => 'textarea',
					'required'    => false,
					'col'         => 'col-12',
					'placeholder' => __( 'Planned route, stops, one-way, etc.', 'flex-multiple-listing-and-booking-system' ),
				),
				array(
					'name'        => 'license_number',
					'label'       => __( 'Driver license ID (optional)', 'flex-multiple-listing-and-booking-system' ),
					'type'        => 'text',
					'required'    => false,
					'col'         => 'col-md-6',
					'placeholder' => '',
				),
			),
			'hotel_accommodation'   => array(
				array( 'name' => 'guests_count', 'label' => __( 'Number of guests', 'flex-multiple-listing-and-booking-system' ), 'type' => 'number', 'required' => true, 'col' => 'col-md-4', 'attrs' => array( 'min' => 1 ) ),
				array(
					'name'     => 'room_preference',
					'label'    => __( 'Room preference', 'flex-multiple-listing-and-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-4',
					'options'  => array(
						'standard' => __( 'Standard', 'flex-multiple-listing-and-booking-system' ),
						'deluxe'   => __( 'Deluxe', 'flex-multiple-listing-and-booking-system' ),
						'suite'    => __( 'Suite', 'flex-multiple-listing-and-booking-system' ),
					),
				),
				array( 'name' => 'arrival_time', 'label' => __( 'Estimated arrival', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-4', 'placeholder' => __( 'e.g. 15:00', 'flex-multiple-listing-and-booking-system' ) ),
				array( 'name' => 'special_requests', 'label' => __( 'Special requests', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'events_tickets'        => array(
				array( 'name' => 'ticket_tier', 'label' => __( 'Ticket type / tier', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'seating_preference', 'label' => __( 'Seating preference', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'event_notes', 'label' => __( 'Notes', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'appointments_services' => array(
				array( 'name' => 'service_focus', 'label' => __( 'Service / topic', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array(
					'name'     => 'preferred_contact',
					'label'    => __( 'Preferred follow-up', 'flex-multiple-listing-and-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-6',
					'options'  => array(
						'email' => __( 'Email', 'flex-multiple-listing-and-booking-system' ),
						'phone' => __( 'Phone', 'flex-multiple-listing-and-booking-system' ),
					),
				),
				array( 'name' => 'appointment_notes', 'label' => __( 'Notes for provider', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'tours_activities'      => array(
				array( 'name' => 'party_size', 'label' => __( 'Party size', 'flex-multiple-listing-and-booking-system' ), 'type' => 'number', 'required' => true, 'col' => 'col-md-4', 'attrs' => array( 'min' => 1 ) ),
				array( 'name' => 'dietary_notes', 'label' => __( 'Dietary or accessibility notes', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'equipment_rental'    => array(
				array( 'name' => 'equipment_category', 'label' => __( 'Equipment type', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => true, 'col' => 'col-md-6' ),
				array( 'name' => 'usage_location', 'label' => __( 'Where it will be used', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'equipment_notes', 'label' => __( 'Notes', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'classes_courses'       => array(
				array(
					'name'     => 'skill_level',
					'label'    => __( 'Experience level', 'flex-multiple-listing-and-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-6',
					'options'  => array(
						'beginner'     => __( 'Beginner', 'flex-multiple-listing-and-booking-system' ),
						'intermediate' => __( 'Intermediate', 'flex-multiple-listing-and-booking-system' ),
						'advanced'     => __( 'Advanced', 'flex-multiple-listing-and-booking-system' ),
					),
				),
				array( 'name' => 'class_notes', 'label' => __( 'Notes', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'restaurant_tables'     => array(
				array( 'name' => 'party_size', 'label' => __( 'Party size', 'flex-multiple-listing-and-booking-system' ), 'type' => 'number', 'required' => true, 'col' => 'col-md-4', 'attrs' => array( 'min' => 1 ) ),
				array( 'name' => 'occasion', 'label' => __( 'Occasion', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-8' ),
				array( 'name' => 'dietary_restrictions', 'label' => __( 'Allergies / dietary', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'workspace_desks'       => array(
				array(
					'name'     => 'desk_type',
					'label'    => __( 'Space type', 'flex-multiple-listing-and-booking-system' ),
					'type'     => 'select',
					'required' => true,
					'col'      => 'col-md-6',
					'options'  => array(
						'hot_desk'     => __( 'Hot desk', 'flex-multiple-listing-and-booking-system' ),
						'meeting_room' => __( 'Meeting room', 'flex-multiple-listing-and-booking-system' ),
						'day_office'   => __( 'Day office', 'flex-multiple-listing-and-booking-system' ),
					),
				),
				array( 'name' => 'attendees', 'label' => __( 'Number of attendees', 'flex-multiple-listing-and-booking-system' ), 'type' => 'number', 'required' => false, 'col' => 'col-md-6', 'attrs' => array( 'min' => 1 ) ),
			),
			'boats_charters'        => array(
				array(
					'name'     => 'charter_style',
					'label'    => __( 'Charter style', 'flex-multiple-listing-and-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-6',
					'options'  => array(
						'skippered' => __( 'With captain', 'flex-multiple-listing-and-booking-system' ),
						'bareboat'  => __( 'Bareboat', 'flex-multiple-listing-and-booking-system' ),
					),
				),
				array( 'name' => 'passengers', 'label' => __( 'Passengers', 'flex-multiple-listing-and-booking-system' ), 'type' => 'number', 'required' => false, 'col' => 'col-md-6', 'attrs' => array( 'min' => 1 ) ),
				array( 'name' => 'boat_notes', 'label' => __( 'Notes', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'spa_wellness'          => array(
				array( 'name' => 'treatment_focus', 'label' => __( 'Treatment or package', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'health_notes', 'label' => __( 'Health / allergy notes', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'sports_facilities'     => array(
				array( 'name' => 'facility_type', 'label' => __( 'Court / lane / facility', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'team_name', 'label' => __( 'Team or group name', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
			),
			'transport_shuttles'    => array(
				array( 'name' => 'pickup_point', 'label' => __( 'Pickup point', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => true, 'col' => 'col-md-6' ),
				array( 'name' => 'dropoff_point', 'label' => __( 'Drop-off point', 'flex-multiple-listing-and-booking-system' ), 'type' => 'text', 'required' => true, 'col' => 'col-md-6' ),
				array( 'name' => 'luggage_notes', 'label' => __( 'Luggage / passengers notes', 'flex-multiple-listing-and-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
		);

		if ( isset( $map[ $industry ] ) ) {
			return $map[ $industry ];
		}

		return array(
			array(
				'name'     => 'booking_notes',
				'label'    => __( 'Additional details', 'flex-multiple-listing-and-booking-system' ),
				'type'     => 'textarea',
				'required' => false,
				'col'      => 'col-12',
			),
		);
	}

	/**
	 * Combined groups for templates.
	 *
	 * @param array<string, mixed>|null $booking_type Booking type row or null.
	 * @return array{industry: string, contact: array, extra: array, title: string}
	 */
	public static function groups_for_type( $booking_type, $listing_post_type = '' ) {
		$industry = self::industry_from_type( $booking_type );
		if ( 'generic' === $industry && '' !== (string) $listing_post_type ) {
			$industry = self::industry_from_post_type( (string) $listing_post_type );
		}

		$def   = IndustryCatalog::get( $industry );
		$title = $def ? (string) $def['select_label'] : __( 'Booking details', 'flex-multiple-listing-and-booking-system' );

		$groups = array(
			'industry'      => $industry,
			'contact'       => self::contact_fields(),
			'extra'         => self::extra_fields_for_industry( $industry ),
			'title'         => $title,
			'widget_title'  => self::widget_title_for_industry( $industry ),
			'schedule'      => self::schedule_for_industry( $industry ),
			'skip_marketplace' => self::marketplace_skip_fields( $industry ),
		);

		return apply_filters( 'ulbm_public_booking_field_groups', $groups, $booking_type );
	}
}

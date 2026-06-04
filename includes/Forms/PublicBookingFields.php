<?php
/**
 * Public booking form field groups: contact + industry-specific questions.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Forms;

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
		return 'generic';
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
				'label'    => __( 'First name', 'flex-booking-system' ),
				'type'     => 'text',
				'required' => true,
				'col'      => 'col-md-6',
			),
			array(
				'name'     => 'customer_last_name',
				'label'    => __( 'Last name', 'flex-booking-system' ),
				'type'     => 'text',
				'required' => true,
				'col'      => 'col-md-6',
			),
			array(
				'name'     => 'customer_email',
				'label'    => __( 'Email', 'flex-booking-system' ),
				'type'     => 'email',
				'required' => true,
				'col'      => 'col-md-6',
			),
			array(
				'name'     => 'customer_phone',
				'label'    => __( 'Mobile / phone', 'flex-booking-system' ),
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
					'label'    => __( 'Vehicle type', 'flex-booking-system' ),
					'type'     => 'select',
					'required' => true,
					'col'      => 'col-md-6',
					'options'  => array(
						'compact' => __( 'Compact', 'flex-booking-system' ),
						'sedan'   => __( 'Sedan', 'flex-booking-system' ),
						'suv'     => __( 'SUV', 'flex-booking-system' ),
						'van'     => __( 'Van / minibus', 'flex-booking-system' ),
						'luxury'  => __( 'Luxury', 'flex-booking-system' ),
					),
				),
				array(
					'name'        => 'pickup_location',
					'label'       => __( 'Pickup location', 'flex-booking-system' ),
					'type'        => 'text',
					'required'    => true,
					'col'         => 'col-md-6',
					'placeholder' => __( 'City, airport, or address', 'flex-booking-system' ),
				),
				array(
					'name'        => 'dropoff_location',
					'label'       => __( 'Return / drop-off location', 'flex-booking-system' ),
					'type'        => 'text',
					'required'    => true,
					'col'         => 'col-md-6',
					'placeholder' => __( 'Same as pickup or other address', 'flex-booking-system' ),
				),
				array(
					'name'        => 'route_notes',
					'label'       => __( 'Route or trip notes', 'flex-booking-system' ),
					'type'        => 'textarea',
					'required'    => false,
					'col'         => 'col-12',
					'placeholder' => __( 'Planned route, stops, one-way, etc.', 'flex-booking-system' ),
				),
				array(
					'name'        => 'license_number',
					'label'       => __( 'Driver license ID (optional)', 'flex-booking-system' ),
					'type'        => 'text',
					'required'    => false,
					'col'         => 'col-md-6',
					'placeholder' => '',
				),
			),
			'hotel_accommodation'   => array(
				array( 'name' => 'guests_count', 'label' => __( 'Number of guests', 'flex-booking-system' ), 'type' => 'number', 'required' => true, 'col' => 'col-md-4', 'attrs' => array( 'min' => 1 ) ),
				array(
					'name'     => 'room_preference',
					'label'    => __( 'Room preference', 'flex-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-4',
					'options'  => array(
						'standard' => __( 'Standard', 'flex-booking-system' ),
						'deluxe'   => __( 'Deluxe', 'flex-booking-system' ),
						'suite'    => __( 'Suite', 'flex-booking-system' ),
					),
				),
				array( 'name' => 'arrival_time', 'label' => __( 'Estimated arrival', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-4', 'placeholder' => __( 'e.g. 15:00', 'flex-booking-system' ) ),
				array( 'name' => 'special_requests', 'label' => __( 'Special requests', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'events_tickets'        => array(
				array( 'name' => 'ticket_tier', 'label' => __( 'Ticket type / tier', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'seating_preference', 'label' => __( 'Seating preference', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'event_notes', 'label' => __( 'Notes', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'appointments_services' => array(
				array( 'name' => 'service_focus', 'label' => __( 'Service / topic', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array(
					'name'     => 'preferred_contact',
					'label'    => __( 'Preferred follow-up', 'flex-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-6',
					'options'  => array(
						'email' => __( 'Email', 'flex-booking-system' ),
						'phone' => __( 'Phone', 'flex-booking-system' ),
					),
				),
				array( 'name' => 'appointment_notes', 'label' => __( 'Notes for provider', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'tours_activities'      => array(
				array( 'name' => 'party_size', 'label' => __( 'Party size', 'flex-booking-system' ), 'type' => 'number', 'required' => true, 'col' => 'col-md-4', 'attrs' => array( 'min' => 1 ) ),
				array( 'name' => 'dietary_notes', 'label' => __( 'Dietary or accessibility notes', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'equipment_rental'    => array(
				array( 'name' => 'equipment_category', 'label' => __( 'Equipment type', 'flex-booking-system' ), 'type' => 'text', 'required' => true, 'col' => 'col-md-6' ),
				array( 'name' => 'usage_location', 'label' => __( 'Where it will be used', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'equipment_notes', 'label' => __( 'Notes', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'classes_courses'       => array(
				array(
					'name'     => 'skill_level',
					'label'    => __( 'Experience level', 'flex-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-6',
					'options'  => array(
						'beginner'     => __( 'Beginner', 'flex-booking-system' ),
						'intermediate' => __( 'Intermediate', 'flex-booking-system' ),
						'advanced'     => __( 'Advanced', 'flex-booking-system' ),
					),
				),
				array( 'name' => 'class_notes', 'label' => __( 'Notes', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'restaurant_tables'     => array(
				array( 'name' => 'party_size', 'label' => __( 'Party size', 'flex-booking-system' ), 'type' => 'number', 'required' => true, 'col' => 'col-md-4', 'attrs' => array( 'min' => 1 ) ),
				array( 'name' => 'occasion', 'label' => __( 'Occasion', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-8' ),
				array( 'name' => 'dietary_restrictions', 'label' => __( 'Allergies / dietary', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'workspace_desks'       => array(
				array(
					'name'     => 'desk_type',
					'label'    => __( 'Space type', 'flex-booking-system' ),
					'type'     => 'select',
					'required' => true,
					'col'      => 'col-md-6',
					'options'  => array(
						'hot_desk'     => __( 'Hot desk', 'flex-booking-system' ),
						'meeting_room' => __( 'Meeting room', 'flex-booking-system' ),
						'day_office'   => __( 'Day office', 'flex-booking-system' ),
					),
				),
				array( 'name' => 'attendees', 'label' => __( 'Number of attendees', 'flex-booking-system' ), 'type' => 'number', 'required' => false, 'col' => 'col-md-6', 'attrs' => array( 'min' => 1 ) ),
			),
			'boats_charters'        => array(
				array(
					'name'     => 'charter_style',
					'label'    => __( 'Charter style', 'flex-booking-system' ),
					'type'     => 'select',
					'required' => false,
					'col'      => 'col-md-6',
					'options'  => array(
						'skippered' => __( 'With captain', 'flex-booking-system' ),
						'bareboat'  => __( 'Bareboat', 'flex-booking-system' ),
					),
				),
				array( 'name' => 'passengers', 'label' => __( 'Passengers', 'flex-booking-system' ), 'type' => 'number', 'required' => false, 'col' => 'col-md-6', 'attrs' => array( 'min' => 1 ) ),
				array( 'name' => 'boat_notes', 'label' => __( 'Notes', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'spa_wellness'          => array(
				array( 'name' => 'treatment_focus', 'label' => __( 'Treatment or package', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'health_notes', 'label' => __( 'Health / allergy notes', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
			'sports_facilities'     => array(
				array( 'name' => 'facility_type', 'label' => __( 'Court / lane / facility', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
				array( 'name' => 'team_name', 'label' => __( 'Team or group name', 'flex-booking-system' ), 'type' => 'text', 'required' => false, 'col' => 'col-md-6' ),
			),
			'transport_shuttles'    => array(
				array( 'name' => 'pickup_point', 'label' => __( 'Pickup point', 'flex-booking-system' ), 'type' => 'text', 'required' => true, 'col' => 'col-md-6' ),
				array( 'name' => 'dropoff_point', 'label' => __( 'Drop-off point', 'flex-booking-system' ), 'type' => 'text', 'required' => true, 'col' => 'col-md-6' ),
				array( 'name' => 'luggage_notes', 'label' => __( 'Luggage / passengers notes', 'flex-booking-system' ), 'type' => 'textarea', 'required' => false, 'col' => 'col-12' ),
			),
		);

		if ( isset( $map[ $industry ] ) ) {
			return $map[ $industry ];
		}

		return array(
			array(
				'name'     => 'booking_notes',
				'label'    => __( 'Additional details', 'flex-booking-system' ),
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
	public static function groups_for_type( $booking_type ) {
		$industry = self::industry_from_type( $booking_type );
		$def      = IndustryCatalog::get( $industry );
		$title    = $def ? (string) $def['select_label'] : __( 'Booking details', 'flex-booking-system' );

		$groups = array(
			'industry' => $industry,
			'contact'  => self::contact_fields(),
			'extra'    => self::extra_fields_for_industry( $industry ),
			'title'    => $title,
		);

		return apply_filters( 'ulbm_public_booking_field_groups', $groups, $booking_type );
	}
}

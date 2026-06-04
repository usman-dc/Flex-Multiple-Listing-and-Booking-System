<?php
/**
 * Canonical database schema version and table definitions for dbDelta.
 *
 * Indexing targets high-read paths: bookings by status/date, availability by resource/date,
 * transactions by booking and gateway.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Stores schema version and DDL fragments.
 */
final class Schema {

	public const VERSION = '1.1.1';

	public const PREFIX = 'ulbm_';

	/**
	 * Fully qualified table names for current site prefix.
	 *
	 * @return array<string, string> Logical key => full table name.
	 */
	public static function tables() {
		global $wpdb;

		$p = $wpdb->prefix . self::PREFIX;

		return array(
			'booking_types'          => $p . 'booking_types',
			'bookings'               => $p . 'bookings',
			'booking_items'          => $p . 'booking_items',
			'booking_meta'           => $p . 'booking_meta',
			'availability'           => $p . 'availability',
			'availability_rules'     => $p . 'availability_rules',
			'pricing_rules'          => $p . 'pricing_rules',
			'form_definitions'       => $p . 'form_definitions',
			'form_fields'            => $p . 'form_fields',
			'transactions'           => $p . 'transactions',
			'customers'              => $p . 'customers',
			'notifications'          => $p . 'notifications',
			'notification_queue'     => $p . 'notification_queue',
			'calendars'              => $p . 'calendars',
			'calendar_sync'          => $p . 'calendar_sync',
			'activity_logs'          => $p . 'activity_logs',
			'vendors'                => $p . 'vendors',
			'locations'              => $p . 'locations',
			'staff'                  => $p . 'staff',
			'services'               => $p . 'services',
			'coupons'                => $p . 'coupons',
			'vendor_commissions'     => $p . 'vendor_commissions',
			'webhooks'               => $p . 'webhooks',
			'webhook_deliveries'     => $p . 'webhook_deliveries',
			'listing_reviews'        => $p . 'listing_reviews',
		);
	}

	/**
	 * Flat list of table names for uninstall.
	 *
	 * @return string[]
	 */
	public static function table_names() {
		return array_values( self::tables() );
	}

	/**
	 * Validated table name for $wpdb->prepare( …, %i, … ) (WP 6.2+).
	 *
	 * @param string $logical_key Key from tables().
	 * @return string Full table name or empty if invalid.
	 */
	public static function table( $logical_key ) {
		$tables = self::tables();
		$key    = (string) $logical_key;

		if ( ! isset( $tables[ $key ] ) ) {
			return '';
		}

		$name = $tables[ $key ];
		global $wpdb;

		$expected = $wpdb->prefix . self::PREFIX;
		if ( 0 !== strpos( $name, $expected ) ) {
			return '';
		}

		$suffix = substr( $name, strlen( $wpdb->prefix ) );
		if ( ! preg_match( '/^' . preg_quote( self::PREFIX, '/' ) . '[a-z0-9_]+$/', $suffix ) ) {
			return '';
		}

		return $name;
	}

	/**
	 * Charset collate helper.
	 *
	 * @return string
	 */
	public static function charset_collate() {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}

	/**
	 * Build CREATE TABLE statements keyed by logical name.
	 *
	 * @return array<string, string>
	 */
	public static function ddl() {
		$t        = self::tables();
		$collate  = self::charset_collate();
		$longtext = 'longtext';

		$ddl = array();

		$ddl['booking_types'] = "CREATE TABLE {$t['booking_types']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			slug varchar(191) NOT NULL,
			description {$longtext} NULL,
			module_key varchar(64) NOT NULL DEFAULT 'generic',
			settings {$longtext} NULL,
			form_id bigint(20) unsigned NULL,
			status varchar(20) NOT NULL DEFAULT 'publish',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY status (status),
			KEY module_key (module_key)
		) $collate;";

		$ddl['bookings'] = "CREATE TABLE {$t['bookings']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_uid varchar(64) NOT NULL,
			booking_type_id bigint(20) unsigned NOT NULL,
			customer_id bigint(20) unsigned NULL,
			wp_user_id bigint(20) unsigned NULL,
			vendor_id bigint(20) unsigned NULL DEFAULT NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			payment_status varchar(32) NOT NULL DEFAULT 'unpaid',
			currency char(3) NOT NULL DEFAULT 'USD',
			total decimal(18,4) NOT NULL DEFAULT 0,
			tax_total decimal(18,4) NOT NULL DEFAULT 0,
			discount_total decimal(18,4) NOT NULL DEFAULT 0,
			deposit_total decimal(18,4) NOT NULL DEFAULT 0,
			start_datetime datetime NOT NULL,
			end_datetime datetime NOT NULL,
			source varchar(32) NOT NULL DEFAULT 'web',
			meta {$longtext} NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_uid (booking_uid),
			KEY booking_type_id (booking_type_id),
			KEY customer_id (customer_id),
			KEY wp_user_id (wp_user_id),
			KEY vendor_id (vendor_id),
			KEY status (status),
			KEY payment_status (payment_status),
			KEY start_datetime (start_datetime),
			KEY end_datetime (end_datetime)
		) $collate;";

		$ddl['booking_items'] = "CREATE TABLE {$t['booking_items']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			resource_key varchar(191) NOT NULL DEFAULT 'default',
			label varchar(191) NOT NULL DEFAULT '',
			qty int(11) NOT NULL DEFAULT 1,
			unit_price decimal(18,4) NOT NULL DEFAULT 0,
			line_total decimal(18,4) NOT NULL DEFAULT 0,
			meta {$longtext} NULL,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY resource_key (resource_key)
		) $collate;";

		$ddl['booking_meta'] = "CREATE TABLE {$t['booking_meta']} (
			meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			meta_key varchar(191) DEFAULT NULL,
			meta_value {$longtext} NULL,
			PRIMARY KEY  (meta_id),
			KEY booking_id (booking_id),
			KEY meta_key (meta_key(191))
		) $collate;";

		$ddl['availability'] = "CREATE TABLE {$t['availability']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_type_id bigint(20) unsigned NOT NULL,
			resource_key varchar(191) NOT NULL DEFAULT 'default',
			date_local date NOT NULL,
			start_time time NULL,
			end_time time NULL,
			is_available tinyint(1) NOT NULL DEFAULT 1,
			qty_available int(11) NOT NULL DEFAULT 1,
			price_override decimal(18,4) NULL,
			meta {$longtext} NULL,
			PRIMARY KEY  (id),
			KEY booking_type_resource (booking_type_id, resource_key),
			KEY date_local (date_local),
			KEY availability_lookup (booking_type_id, resource_key, date_local)
		) $collate;";

		$ddl['availability_rules'] = "CREATE TABLE {$t['availability_rules']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_type_id bigint(20) unsigned NOT NULL,
			resource_key varchar(191) NOT NULL DEFAULT 'default',
			rule_type varchar(32) NOT NULL,
			rule_payload {$longtext} NOT NULL,
			priority int(11) NOT NULL DEFAULT 10,
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY booking_type_id (booking_type_id),
			KEY rule_type (rule_type),
			KEY active_priority (active, priority)
		) $collate;";

		$ddl['pricing_rules'] = "CREATE TABLE {$t['pricing_rules']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_type_id bigint(20) unsigned NOT NULL,
			resource_key varchar(191) NOT NULL DEFAULT 'default',
			name varchar(191) NOT NULL DEFAULT '',
			rule_type varchar(32) NOT NULL,
			payload {$longtext} NOT NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			priority int(11) NOT NULL DEFAULT 10,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY booking_type_id (booking_type_id),
			KEY rule_type (rule_type),
			KEY active_priority (active, priority)
		) $collate;";

		$ddl['form_definitions'] = "CREATE TABLE {$t['form_definitions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL,
			version varchar(16) NOT NULL DEFAULT '1',
			settings {$longtext} NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $collate;";

		$ddl['form_fields'] = "CREATE TABLE {$t['form_fields']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			step_index int(11) NOT NULL DEFAULT 0,
			field_key varchar(64) NOT NULL,
			field_type varchar(32) NOT NULL,
			label varchar(191) NOT NULL DEFAULT '',
			config {$longtext} NULL,
			sort_order int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY form_field (form_id, field_key),
			KEY form_id (form_id),
			KEY step_index (step_index)
		) $collate;";

		$ddl['transactions'] = "CREATE TABLE {$t['transactions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			gateway varchar(32) NOT NULL,
			gateway_txn_id varchar(191) NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			amount decimal(18,4) NOT NULL DEFAULT 0,
			currency char(3) NOT NULL DEFAULT 'USD',
			meta {$longtext} NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY gateway (gateway),
			KEY status (status),
			KEY gateway_txn_id (gateway_txn_id)
		) $collate;";

		$ddl['customers'] = "CREATE TABLE {$t['customers']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wp_user_id bigint(20) unsigned NULL,
			email varchar(191) NOT NULL,
			first_name varchar(191) NOT NULL DEFAULT '',
			last_name varchar(191) NOT NULL DEFAULT '',
			phone varchar(64) NOT NULL DEFAULT '',
			meta {$longtext} NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email),
			KEY wp_user_id (wp_user_id)
		) $collate;";

		$ddl['notifications'] = "CREATE TABLE {$t['notifications']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			channel varchar(32) NOT NULL,
			event_key varchar(64) NOT NULL,
			template {$longtext} NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY channel_event (channel, event_key)
		) $collate;";

		$ddl['notification_queue'] = "CREATE TABLE {$t['notification_queue']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_id bigint(20) unsigned NOT NULL,
			channel varchar(32) NOT NULL,
			payload {$longtext} NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'queued',
			scheduled_at datetime NOT NULL,
			sent_at datetime NULL,
			PRIMARY KEY  (id),
			KEY status_scheduled (status, scheduled_at),
			KEY booking_id (booking_id)
		) $collate;";

		$ddl['calendars'] = "CREATE TABLE {$t['calendars']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_type_id bigint(20) unsigned NOT NULL,
			resource_key varchar(191) NOT NULL DEFAULT 'default',
			name varchar(191) NOT NULL DEFAULT '',
			provider varchar(32) NOT NULL DEFAULT 'internal',
			external_id varchar(191) NULL,
			settings {$longtext} NULL,
			PRIMARY KEY  (id),
			KEY booking_type_id (booking_type_id),
			KEY provider (provider)
		) $collate;";

		$ddl['calendar_sync'] = "CREATE TABLE {$t['calendar_sync']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			calendar_id bigint(20) unsigned NOT NULL,
			sync_direction varchar(16) NOT NULL DEFAULT 'import',
			last_sync_at datetime NULL,
			cursor {$longtext} NULL,
			PRIMARY KEY  (id),
			KEY calendar_id (calendar_id)
		) $collate;";

		$ddl['activity_logs'] = "CREATE TABLE {$t['activity_logs']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			context varchar(32) NOT NULL DEFAULT 'booking',
			object_id bigint(20) unsigned NOT NULL,
			action varchar(64) NOT NULL,
			actor_user_id bigint(20) unsigned NULL,
			payload {$longtext} NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY context_object (context, object_id),
			KEY created_at (created_at)
		) $collate;";

		$ddl['vendors'] = "CREATE TABLE {$t['vendors']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wp_user_id bigint(20) unsigned NOT NULL,
			business_name varchar(191) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'pending',
			meta {$longtext} NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY wp_user_id (wp_user_id),
			KEY status (status)
		) $collate;";

		$ddl['locations'] = "CREATE TABLE {$t['locations']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_type_id bigint(20) unsigned NOT NULL,
			name varchar(191) NOT NULL,
			address {$longtext} NULL,
			lat decimal(10,7) NULL,
			lng decimal(10,7) NULL,
			meta {$longtext} NULL,
			PRIMARY KEY  (id),
			KEY booking_type_id (booking_type_id)
		) $collate;";

		$ddl['staff'] = "CREATE TABLE {$t['staff']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_type_id bigint(20) unsigned NOT NULL,
			wp_user_id bigint(20) unsigned NULL,
			display_name varchar(191) NOT NULL DEFAULT '',
			email varchar(191) NOT NULL DEFAULT '',
			meta {$longtext} NULL,
			PRIMARY KEY  (id),
			KEY booking_type_id (booking_type_id),
			KEY wp_user_id (wp_user_id)
		) $collate;";

		$ddl['services'] = "CREATE TABLE {$t['services']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			booking_type_id bigint(20) unsigned NOT NULL,
			name varchar(191) NOT NULL,
			duration_minutes int(11) NOT NULL DEFAULT 60,
			buffer_before int(11) NOT NULL DEFAULT 0,
			buffer_after int(11) NOT NULL DEFAULT 0,
			base_price decimal(18,4) NOT NULL DEFAULT 0,
			meta {$longtext} NULL,
			PRIMARY KEY  (id),
			KEY booking_type_id (booking_type_id)
		) $collate;";

		$ddl['coupons'] = "CREATE TABLE {$t['coupons']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code varchar(64) NOT NULL,
			discount_type varchar(16) NOT NULL DEFAULT 'percent',
			discount_value decimal(18,4) NOT NULL DEFAULT 0,
			max_uses int(11) NULL,
			uses_count int(11) NOT NULL DEFAULT 0,
			valid_from datetime NULL,
			valid_to datetime NULL,
			meta {$longtext} NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY active (active)
		) $collate;";

		$ddl['vendor_commissions'] = "CREATE TABLE {$t['vendor_commissions']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			vendor_id bigint(20) unsigned NOT NULL,
			booking_id bigint(20) unsigned NOT NULL,
			amount decimal(18,4) NOT NULL DEFAULT 0,
			status varchar(32) NOT NULL DEFAULT 'pending',
			meta {$longtext} NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY vendor_id (vendor_id),
			KEY booking_id (booking_id),
			KEY status (status)
		) $collate;";

		$ddl['webhooks'] = "CREATE TABLE {$t['webhooks']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			target_url text NOT NULL,
			secret varchar(191) NOT NULL DEFAULT '',
			events {$longtext} NOT NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY active (active)
		) $collate;";

		$ddl['webhook_deliveries'] = "CREATE TABLE {$t['webhook_deliveries']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			webhook_id bigint(20) unsigned NOT NULL,
			event_key varchar(64) NOT NULL,
			payload {$longtext} NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'pending',
			response_code smallint NULL,
			attempts smallint NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY webhook_id (webhook_id),
			KEY status (status)
		) $collate;";

		$ddl['listing_reviews'] = "CREATE TABLE {$t['listing_reviews']} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			listing_id bigint(20) unsigned NOT NULL,
			wp_user_id bigint(20) unsigned NULL,
			author_name varchar(191) NOT NULL DEFAULT '',
			author_email varchar(191) NOT NULL DEFAULT '',
			rating smallint(5) unsigned NOT NULL DEFAULT 5,
			content longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY listing_id (listing_id),
			KEY status (status),
			KEY listing_status (listing_id, status)
		) $collate;";

		return $ddl;
	}
}

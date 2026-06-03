<?php
/**
 * Lightweight service locator / DI container for core services.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Simple PSR-11 style container (minimal).
 */
final class Container {

	/**
	 * Registered factories and instances.
	 *
	 * @var array<string, mixed>
	 */
	private $entries = array();

	/**
	 * Bind a factory.
	 *
	 * @param string   $id       Identifier.
	 * @param callable $factory  Factory returning instance.
	 * @return void
	 */
	public function set( $id, $factory ) {
		$this->entries[ $id ] = $factory;
	}

	/**
	 * Resolve service.
	 *
	 * @param string $id Identifier.
	 * @return mixed
	 * @throws \InvalidArgumentException Missing binding.
	 */
	public function get( $id ) {
		if ( ! isset( $this->entries[ $id ] ) ) {
			throw new \InvalidArgumentException( 'Service not found: ' . $id );
		}

		$entry = $this->entries[ $id ];

		if ( is_callable( $entry ) ) {
			$this->entries[ $id ] = $entry( $this );
		}

		return $this->entries[ $id ];
	}

	/**
	 * Whether service is bound.
	 *
	 * @param string $id Identifier.
	 * @return bool
	 */
	public function has( $id ) {
		return isset( $this->entries[ $id ] );
	}
}

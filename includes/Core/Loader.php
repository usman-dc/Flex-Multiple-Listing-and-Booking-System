<?php
/**
 * Central WordPress hook registrar.
 *
 * @package FlexBookingSystem
 */

namespace FlexBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Registers actions and filters queued by the plugin.
 */
final class Loader {

	/**
	 * Actions to add.
	 *
	 * @var array<int, array{0:string,1:callable,2:int,3:int}>
	 */
	private $actions = array();

	/**
	 * Filters to add.
	 *
	 * @var array<int, array{0:string,1:callable,2:int,3:int}>
	 */
	private $filters = array();

	/**
	 * Queue action.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $component     Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Args count.
	 * @return void
	 */
	public function add_action( $hook, $component, $priority = 10, $accepted_args = 1 ) {
		$this->actions[] = array( $hook, $component, $priority, $accepted_args );
	}

	/**
	 * Queue filter.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $component     Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Args count.
	 * @return void
	 */
	public function add_filter( $hook, $component, $priority = 10, $accepted_args = 1 ) {
		$this->filters[] = array( $hook, $component, $priority, $accepted_args );
	}

	/**
	 * Register all queued hooks with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		foreach ( $this->filters as $f ) {
			add_filter( $f[0], $f[1], $f[2], $f[3] );
		}
		foreach ( $this->actions as $a ) {
			add_action( $a[0], $a[1], $a[2], $a[3] );
		}
	}
}

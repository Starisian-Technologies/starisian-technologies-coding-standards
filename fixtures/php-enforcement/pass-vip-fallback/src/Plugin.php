<?php

declare(strict_types=1);

/**
 * Fixture: no phpcs.xml — fallback must use WordPressVIPMinimum, not --standard=WordPress.
 * This file is clean under WordPressVIPMinimum and must produce zero PHPCS violations.
 */

namespace Example\WpPlugin;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Constructor.
	 *
	 * @param string $version Plugin version string.
	 */
	public function __construct( string $version ) {
		$this->version = $version;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		\add_action( 'init', [ $this, 'on_init' ] );
	}

	/**
	 * Init hook callback.
	 *
	 * @return void
	 */
	public function on_init(): void {
		// Intentionally empty — fixture only.
	}

	/**
	 * Return the plugin version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}
}

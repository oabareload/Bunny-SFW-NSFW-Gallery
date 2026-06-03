<?php
/**
 * Bunny SFW&NSFW Gallery — Admin asset enqueuing.
 *
 * Loads bunny-admin.css (shared base) and admin.css (plugin-specific)
 * only on plugin admin pages.
 *
 * @package BunnyNSFW
 * @since   0.4.2
 */

namespace BunnyNSFW;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_Assets {

	/**
	 * Admin page slugs that belong to this plugin.
	 *
	 * @var string[]
	 */
	private static array $plugin_pages = array(
		'bunny-gallery-settings',
		'bunny-normalization-settings',
	);

	/**
	 * Registers the hook.
	 */
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', array( static::class, 'enqueue' ) );
	}

	/**
	 * Enqueues styles only on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue( string $hook_suffix ): void {
		$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array( $current_page, self::$plugin_pages, true ) ) {
			return;
		}

		// Shared Bunny Admin UI base (header, tabs, nav, wrappers).
		wp_enqueue_style(
			'bunny-admin',
			BUNNY_NSWF_PLUGIN_URL . 'assets/css/bunny-admin.css',
			array(),
			BUNNY_NSWF_VERSION
		);

		// Plugin-specific admin styles.
		wp_enqueue_style(
			'bunny-gallery-admin',
			BUNNY_NSWF_PLUGIN_URL . 'assets/css/admin.css',
			array( 'bunny-admin' ),
			BUNNY_NSWF_VERSION
		);
	}
}
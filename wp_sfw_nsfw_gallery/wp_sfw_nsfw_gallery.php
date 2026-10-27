<?php
/**
 * Plugin Name:       Bunny SFW&NSFW Gallery
 * Plugin URI:        https://bunnychase.net/bunny-sfw-nsfw-gallery
 * Description:       Gutenberg gallery block with SFW/NSFW protection system based on cookies.
 * Version:           1.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            BunnyChase
 * Author URI:        https://bunnychase.net
 * License:           GPL v2 or later
 * Text Domain:       bunny-sfw-nsfw-gallery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BUNNY_NSWF_VERSION',     '1.2.0' );
define( 'BUNNY_NSWF_PLUGIN_FILE', __FILE__ );
define( 'BUNNY_NSWF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BUNNY_NSWF_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

add_filter(
    'wp_consent_api_registered_' . plugin_basename( __FILE__ ),
    '__return_true'
);

function register_consent_api_cookie(): void {
    if ( function_exists( 'wp_add_cookie_info' ) ) {
        $cookie_name = function_exists( 'bunny_nsfw_get_configured_cookie_name' )
            ? bunny_nsfw_get_configured_cookie_name()
            : 'bunny_nsfw_age';

        wp_add_cookie_info(
            $cookie_name,
            'Bunny SFW&NSFW Gallery',
            'functional',
            '365 days',
            'Remembers that the user passed the NSFW age gate.',
            ''
        );
    }
}
add_action( 'plugins_loaded', 'register_consent_api_cookie', 10 );

require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-loader.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/settings.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-plugin.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-activator.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-deactivator.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/helpers.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/admin/class-admin-assets.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-image-normalizer.php';

register_activation_hook(   BUNNY_NSWF_PLUGIN_FILE, [ 'BunnyNSFW\\Activator',   'activate'   ] );
register_deactivation_hook( BUNNY_NSWF_PLUGIN_FILE, [ 'BunnyNSFW\\Deactivator', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    BunnyNSFW\Plugin::get_instance();
    BunnyNSFW\Admin_Assets::init();
    BunnyNSFW\Image_Normalizer::init();
} );

<?php
/**
 * Plugin Name:       Bunny SFW&NSFW Gallery
 * Plugin URI:        https://bunnychase.net/bunny-sfw-nsfw-gallery
 * Description:       Gutenberg gallery block with SFW/NSFW protection system based on cookies.
 * Version:           0.5.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            BunnyChase
 * Author URI:        https://bunnychase.net
 * License:           GPL v2 or later
 * Text Domain:       bunny-sfw-nsfw-gallery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BUNNY_NSWF_VERSION',     '0.5.0' );
define( 'BUNNY_NSWF_PLUGIN_FILE', __FILE__ );
define( 'BUNNY_NSWF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BUNNY_NSWF_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-loader.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/settings.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-plugin.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-activator.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/class-deactivator.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/helpers.php';
require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/admin/class-admin-assets.php';

register_activation_hook(   BUNNY_NSWF_PLUGIN_FILE, [ 'BunnyNSFW\\Activator',   'activate'   ] );
register_deactivation_hook( BUNNY_NSWF_PLUGIN_FILE, [ 'BunnyNSFW\\Deactivator', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    BunnyNSFW\Plugin::get_instance();
    BunnyNSFW\Admin_Assets::init();
} );

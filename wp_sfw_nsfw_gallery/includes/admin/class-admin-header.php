<?php
/**
 * Bunny SFW&NSFW Gallery — Reusable admin header.
 *
 * Renders the shared admin chrome using the bunny-* UI system.
 * Each plugin keeps its own copy — no cross-plugin dependency.
 *
 * Usage:
 *   require_once BUNNY_NSWF_PLUGIN_PATH . 'includes/admin/class-admin-header.php';
 *   BunnyNSFW\Admin_Header::render( 'bunny-gallery-settings' );
 *
 * @package BunnyNSFW
 * @since   0.4.2
 */

namespace BunnyNSFW;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the shared admin header and tab navigation.
 */
final class Admin_Header {

	/**
	 * Tab definitions: page_slug => label.
	 * Add future tabs here without touching any page template.
	 *
	 * @var array<string,string>
	 */
	private static array $tabs = array(
		'bunny-gallery-settings'       => 'Settings',
		'bunny-normalization-settings' => 'Normalization',
	);

	/**
	 * Renders the full header + nav block.
	 *
	 * @param string $current_tab  Slug of the currently active admin page.
	 * @param string $page_label   Optional override for the subtitle text.
	 *                             Defaults to the tab label for $current_tab.
	 * @return void
	 */
	public static function render( string $current_tab, string $page_label = '' ): void {
		$version = defined( 'BUNNY_NSWF_VERSION' ) ? BUNNY_NSWF_VERSION : '';
		$label   = $page_label ?: ( self::$tabs[ $current_tab ] ?? '' );
		?>
		<div class="bunny-header">
			<div class="bunny-header-inner">
				<span class="bunny-logo">🐰</span>
				<div class="bunny-title-stack">
					<h1 class="bunny-plugin-name">Bunny SFW&amp;NSFW Gallery</h1>
					<?php if ( $label ) : ?>
						<span class="bunny-page-subtitle"><?php echo esc_html( $label ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $version ) : ?>
					<span class="bunny-version-badge">v<?php echo esc_html( $version ); ?></span>
				<?php endif; ?>
			</div>
			<nav class="bunny-nav" aria-label="Plugin navigation">
				<?php foreach ( self::$tabs as $slug => $tab_label ) : ?>
					<a
						href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"
						class="bunny-nav-item<?php echo $slug === $current_tab ? ' bunny-nav-active' : ''; ?>"
					>
						<?php echo esc_html( $tab_label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
		<?php
	}
}
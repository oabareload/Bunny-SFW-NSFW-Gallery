<?php
/**
 * Bunny SFW&NSFW Gallery — Settings globales
 *
 * @package BunnyNSFW
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BUNNY_GALLERY_OPTION', 'bunny_gallery_defaults' );

require_once __DIR__ . '/class-image-settings.php';

// -----------------------------------------------------------------------------
// FALLBACKS HARDCODED
// -----------------------------------------------------------------------------

function bunny_gallery_hardcoded_defaults(): array {
    return [
        'columns'            => 5,
        'blur'               => true,
        'blur_intensity'     => 12,
        'link_behavior'      => 'none',
        'target_blank'       => false,
        'nsfw_message'       => 'Este contenido es solo para adultos.',
        'sfw_title'          => '',
        'nsfw_title'         => '',
        'image_size'         => 'large',
        'aspect_ratio'       => 'square',
        // 0.3.1
        'nsfw_display_style' => 'minimal',  // minimal | overlay | hidden
        'unlock_button_text' => 'Ver contenido (+18)',
        // 0.4.0 — Lightbox
        'show_lightbox_thumbnails' => true,
        'lightbox_theme'           => 'dark',   // dark | light | auto
        'lightbox_accent_color'    => '#7c6aff',
        'lightbox_caption_fields'  => [],       // array: alt|title|caption|description
        'lightbox_caption_mode'    => 'minimal', // hidden | minimal | full
    ];
}

// -----------------------------------------------------------------------------
// HELPERS
// -----------------------------------------------------------------------------

function bunny_get_setting( $block_value, string $key ) {
    if ( ! is_null( $block_value ) && $block_value !== '' ) {
        return $block_value;
    }
    $saved = get_option( BUNNY_GALLERY_OPTION, [] );
    if ( array_key_exists( $key, $saved ) && $saved[ $key ] !== '' ) {
        return $saved[ $key ];
    }
    $defaults = bunny_gallery_hardcoded_defaults();
    return $defaults[ $key ] ?? null;
}

function bunny_gallery_get_effective_defaults(): array {
    $saved    = get_option( BUNNY_GALLERY_OPTION, [] );
    $fallback = bunny_gallery_hardcoded_defaults();
    return array_merge( $fallback, array_filter( $saved, fn( $v ) => $v !== '' && ! is_null( $v ) ) );
}

// -----------------------------------------------------------------------------
// REGISTRO DE SETTINGS
// -----------------------------------------------------------------------------

function bunny_gallery_register_settings(): void {
    register_setting(
        'bunny_gallery_group',
        BUNNY_GALLERY_OPTION,
        [
            'type'              => 'array',
            'sanitize_callback' => 'bunny_gallery_sanitize_options',
            'default'           => bunny_gallery_hardcoded_defaults(),
        ]
    );

    add_settings_section( 'bunny_section_gallery',  'Galería',         '__return_false', 'bunny-gallery-settings' );
    add_settings_section( 'bunny_section_nsfw',     'Protección NSFW', '__return_false', 'bunny-gallery-settings' );
    add_settings_section( 'bunny_section_titles',   'Títulos',         '__return_false', 'bunny-gallery-settings' );
    add_settings_section( 'bunny_section_lightbox', 'Lightbox',        '__return_false', 'bunny-gallery-settings' );

    // Galería
    add_settings_field( 'columns',       'Columnas por defecto',     'bunny_field_columns',       'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'image_size',    'Tamaño de imagen',         'bunny_field_image_size',    'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'aspect_ratio',  'Aspect ratio',             'bunny_field_aspect_ratio',  'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'link_behavior', 'Comportamiento de enlace', 'bunny_field_link_behavior', 'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'target_blank',  'Abrir en nueva pestaña',   'bunny_field_target_blank',  'bunny-gallery-settings', 'bunny_section_gallery' );

    // NSFW
    add_settings_field( 'nsfw_display_style', 'Estilo de protección NSFW', 'bunny_field_nsfw_display_style', 'bunny-gallery-settings', 'bunny_section_nsfw' );
    add_settings_field( 'blur',               'Blur NSFW activado',        'bunny_field_blur',               'bunny-gallery-settings', 'bunny_section_nsfw' );
    add_settings_field( 'blur_intensity',     'Intensidad del blur',       'bunny_field_blur_intensity',     'bunny-gallery-settings', 'bunny_section_nsfw' );
    add_settings_field( 'nsfw_message',       'Mensaje NSFW',              'bunny_field_nsfw_message',       'bunny-gallery-settings', 'bunny_section_nsfw' );
    add_settings_field( 'unlock_button_text', 'Texto del botón desbloquear', 'bunny_field_unlock_button_text', 'bunny-gallery-settings', 'bunny_section_nsfw' );

    // Títulos
    add_settings_field( 'sfw_title',  'Título galería SFW',  'bunny_field_sfw_title',  'bunny-gallery-settings', 'bunny_section_titles' );
    add_settings_field( 'nsfw_title', 'Título galería NSFW', 'bunny_field_nsfw_title', 'bunny-gallery-settings', 'bunny_section_titles' );

    // Lightbox (0.4.0)
    add_settings_field( 'show_lightbox_thumbnails', 'Miniaturas en lightbox',  'bunny_field_lightbox_thumbs',         'bunny-gallery-settings', 'bunny_section_lightbox' );
    add_settings_field( 'lightbox_theme',           'Tema del lightbox',       'bunny_field_lightbox_theme',          'bunny-gallery-settings', 'bunny_section_lightbox' );
    add_settings_field( 'lightbox_accent_color',    'Color de acento',         'bunny_field_lightbox_accent',         'bunny-gallery-settings', 'bunny_section_lightbox' );
    add_settings_field( 'lightbox_caption_fields',  'Campos de caption',       'bunny_field_lightbox_caption_fields', 'bunny-gallery-settings', 'bunny_section_lightbox' );
    add_settings_field( 'lightbox_caption_mode',    'Modo de caption',         'bunny_field_lightbox_caption_mode',   'bunny-gallery-settings', 'bunny_section_lightbox' );
}
add_action( 'admin_init', 'bunny_gallery_register_settings' );

// -----------------------------------------------------------------------------
// SANITIZACIÓN
// -----------------------------------------------------------------------------

function bunny_gallery_sanitize_options( $input ): array {
    $d     = bunny_gallery_hardcoded_defaults();
    $clean = [];

    $cols             = absint( $input['columns'] ?? $d['columns'] );
    $clean['columns'] = max( 1, min( 12, $cols ) );

    $clean['blur']         = ! empty( $input['blur'] );
    $clean['target_blank'] = ! empty( $input['target_blank'] );

    $intensity               = absint( $input['blur_intensity'] ?? $d['blur_intensity'] );
    $clean['blur_intensity'] = max( 0, min( 20, $intensity ) );

    $allowed_links          = [ 'none', 'lightbox', 'file', 'attachment' ];
    $clean['link_behavior'] = in_array( $input['link_behavior'] ?? '', $allowed_links, true )
                              ? $input['link_behavior'] : $d['link_behavior'];

    $allowed_sizes       = [ 'thumbnail', 'medium', 'large', 'full' ];
    $clean['image_size'] = in_array( $input['image_size'] ?? '', $allowed_sizes, true )
                           ? $input['image_size'] : $d['image_size'];

    $allowed_ratios        = [ 'square', 'portrait', 'landscape', 'original' ];
    $clean['aspect_ratio'] = in_array( $input['aspect_ratio'] ?? '', $allowed_ratios, true )
                             ? $input['aspect_ratio'] : $d['aspect_ratio'];

    $allowed_styles              = [ 'minimal', 'overlay', 'hidden' ];
    $clean['nsfw_display_style'] = in_array( $input['nsfw_display_style'] ?? '', $allowed_styles, true )
                                   ? $input['nsfw_display_style'] : $d['nsfw_display_style'];

    $clean['nsfw_message']       = sanitize_text_field( $input['nsfw_message']       ?? $d['nsfw_message'] );
    $clean['unlock_button_text'] = sanitize_text_field( $input['unlock_button_text'] ?? $d['unlock_button_text'] );
    $clean['sfw_title']          = sanitize_text_field( $input['sfw_title']           ?? '' );
    $clean['nsfw_title']         = sanitize_text_field( $input['nsfw_title']          ?? '' );

    // 0.4.0 — Lightbox
    $clean['show_lightbox_thumbnails'] = ! empty( $input['show_lightbox_thumbnails'] );

    $allowed_themes          = [ 'dark', 'light', 'auto' ];
    $clean['lightbox_theme'] = in_array( $input['lightbox_theme'] ?? '', $allowed_themes, true )
                               ? $input['lightbox_theme'] : $d['lightbox_theme'];

    $accent = sanitize_hex_color( $input['lightbox_accent_color'] ?? '' );
    $clean['lightbox_accent_color'] = $accent ?: $d['lightbox_accent_color'];

    $allowed_caption_fields = [ 'alt', 'title', 'caption', 'description' ];
    $raw_fields = $input['lightbox_caption_fields'] ?? [];
    if ( ! is_array( $raw_fields ) ) $raw_fields = [];
    $clean['lightbox_caption_fields'] = array_values( array_intersect( $raw_fields, $allowed_caption_fields ) );

    $allowed_caption_modes          = [ 'hidden', 'minimal', 'full' ];
    $clean['lightbox_caption_mode'] = in_array( $input['lightbox_caption_mode'] ?? '', $allowed_caption_modes, true )
                                      ? $input['lightbox_caption_mode'] : $d['lightbox_caption_mode'];

    return $clean;
}

// -----------------------------------------------------------------------------
// MENÚ
// -----------------------------------------------------------------------------

function bunny_gallery_add_menu(): void {
	add_menu_page(
		'Bunny Gallery',
		'Bunny Gallery',
		'manage_options',
		'bunny-gallery-settings',
		'bunny_gallery_settings_page',
		'dashicons-format-gallery',
		58
	);
}
add_action( 'admin_menu', 'bunny_gallery_add_menu' );

// -----------------------------------------------------------------------------
// PÁGINA DE AJUSTES
// -----------------------------------------------------------------------------

function bunny_gallery_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$opts = get_option( BUNNY_GALLERY_OPTION, [] );
	$d    = bunny_gallery_hardcoded_defaults();
	$opt  = BUNNY_GALLERY_OPTION;

	// Resolved current values.
	$columns       = (int) ( $opts['columns']       ?? $d['columns'] );
	$blur          = ! empty( $opts['blur'] );
	$blur_intensity = (int) ( $opts['blur_intensity'] ?? $d['blur_intensity'] );
	$link_behavior = $opts['link_behavior']  ?? $d['link_behavior'];
	$target_blank  = ! empty( $opts['target_blank'] );
	$image_size    = $opts['image_size']     ?? $d['image_size'];
	$aspect_ratio  = $opts['aspect_ratio']   ?? $d['aspect_ratio'];
	$nsfw_style    = $opts['nsfw_display_style'] ?? $d['nsfw_display_style'];
	$nsfw_message  = $opts['nsfw_message']   ?? $d['nsfw_message'];
	$unlock_text   = $opts['unlock_button_text'] ?? $d['unlock_button_text'];
	$sfw_title     = $opts['sfw_title']      ?? '';
	$nsfw_title    = $opts['nsfw_title']     ?? '';
	$lb_thumbs     = ! empty( $opts['show_lightbox_thumbnails'] ) || ( ! isset( $opts['show_lightbox_thumbnails'] ) && $d['show_lightbox_thumbnails'] );
	$lb_theme      = $opts['lightbox_theme'] ?? $d['lightbox_theme'];
	$lb_accent     = $opts['lightbox_accent_color'] ?? $d['lightbox_accent_color'];
	$lb_cap_fields = is_array( $opts['lightbox_caption_fields'] ?? null ) ? $opts['lightbox_caption_fields'] : $d['lightbox_caption_fields'];
	$lb_cap_mode   = $opts['lightbox_caption_mode'] ?? $d['lightbox_caption_mode'];

	require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin-header.php';
	?>
	<div class="wrap bunny-wrap" id="bunny-settings-wrap">

		<?php BunnyNSFW\Admin_Header::render( 'bunny-gallery-settings' ); ?>

		<div class="bunny-page-content">

			<p class="bunny-gallery-admin-desc">
				<?php esc_html_e( 'Global defaults for new blocks. A block with its own value always takes priority.', 'bunny-sfw-nsfw-gallery' ); ?>
			</p>

			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'bunny-sfw-nsfw-gallery' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php" class="bunny-norm-form">
				<?php settings_fields( 'bunny_gallery_group' ); ?>

				<?php /* ---- CARD: Galería ---- */ ?>
				<div class="bunny-card">
					<div class="bunny-card-header">
						<span class="bunny-card-icon">🖼️</span>
						<h2 class="bunny-card-title"><?php esc_html_e( 'Galería', 'bunny-sfw-nsfw-gallery' ); ?></h2>
					</div>
					<div class="bunny-card-body">

						<div class="bunny-field-row">
							<div class="bunny-field-label">
								<?php esc_html_e( 'Columnas por defecto', 'bunny-sfw-nsfw-gallery' ); ?>
								<em>1 – 12</em>
							</div>
							<div class="bunny-field-control">
								<input type="number" min="1" max="12" step="1"
								       name="<?php echo $opt; ?>[columns]"
								       value="<?php echo esc_attr( $columns ); ?>">
								<p class="bunny-field-desc"><?php esc_html_e( 'Número de columnas en el grid de la galería. Puede sobreescribirse por bloque.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Tamaño de imagen', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<select name="<?php echo $opt; ?>[image_size]">
									<option value="thumbnail" <?php selected( $image_size, 'thumbnail' ); ?>><?php esc_html_e( 'Thumbnail (~150px)', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="medium"    <?php selected( $image_size, 'medium' ); ?>><?php esc_html_e( 'Medium (~300px)', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="large"     <?php selected( $image_size, 'large' ); ?>><?php esc_html_e( 'Large (~1024px)', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="full"      <?php selected( $image_size, 'full' ); ?>><?php esc_html_e( 'Full (original)', 'bunny-sfw-nsfw-gallery' ); ?></option>
								</select>
								<p class="bunny-field-desc"><?php esc_html_e( 'El lightbox siempre muestra la imagen a tamaño completo.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Aspect ratio', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<select name="<?php echo $opt; ?>[aspect_ratio]">
									<option value="square"    <?php selected( $aspect_ratio, 'square' ); ?>><?php esc_html_e( 'Square (1:1)', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="portrait"  <?php selected( $aspect_ratio, 'portrait' ); ?>><?php esc_html_e( 'Portrait (2:3)', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="landscape" <?php selected( $aspect_ratio, 'landscape' ); ?>><?php esc_html_e( 'Landscape (16:9)', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="original"  <?php selected( $aspect_ratio, 'original' ); ?>><?php esc_html_e( 'Original (sin recorte)', 'bunny-sfw-nsfw-gallery' ); ?></option>
								</select>
								<p class="bunny-field-desc"><?php esc_html_e( 'Proporción visual de las imágenes en el grid. No afecta el archivo original.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Comportamiento de enlace', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<select name="<?php echo $opt; ?>[link_behavior]">
									<option value="none"       <?php selected( $link_behavior, 'none' ); ?>><?php esc_html_e( 'Sin enlace', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="lightbox"   <?php selected( $link_behavior, 'lightbox' ); ?>><?php esc_html_e( 'Abrir en lightbox', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="file"       <?php selected( $link_behavior, 'file' ); ?>><?php esc_html_e( 'Archivo de media', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="attachment" <?php selected( $link_behavior, 'attachment' ); ?>><?php esc_html_e( 'Página de adjunto', 'bunny-sfw-nsfw-gallery' ); ?></option>
								</select>
							</div>
						</div>

						<div class="bunny-field-row bunny-field-row--check">
							<div class="bunny-field-control">
								<label class="bunny-field-check-wrap">
									<input type="checkbox" name="<?php echo $opt; ?>[target_blank]" value="1"<?php checked( $target_blank ); ?>>
									<span class="bunny-field-check-text">
										<strong><?php esc_html_e( 'Abrir en nueva pestaña', 'bunny-sfw-nsfw-gallery' ); ?></strong>
										<span class="bunny-field-desc"><?php esc_html_e( 'Solo aplica para "Archivo de media" o "Página de adjunto".', 'bunny-sfw-nsfw-gallery' ); ?></span>
									</span>
								</label>
							</div>
						</div>

					</div>
				</div>

				<?php /* ---- CARD: Protección NSFW ---- */ ?>
				<div class="bunny-card">
					<div class="bunny-card-header">
						<span class="bunny-card-icon">🔒</span>
						<h2 class="bunny-card-title"><?php esc_html_e( 'Protección NSFW', 'bunny-sfw-nsfw-gallery' ); ?></h2>
					</div>
					<div class="bunny-card-body">

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Estilo de protección NSFW', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<select name="<?php echo $opt; ?>[nsfw_display_style]">
									<option value="minimal" <?php selected( $nsfw_style, 'minimal' ); ?>><?php esc_html_e( 'Minimal — blur + badge flotante + modal elegante', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="overlay" <?php selected( $nsfw_style, 'overlay' ); ?>><?php esc_html_e( 'Overlay — capa semitransparente con botón centrado', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="hidden"  <?php selected( $nsfw_style, 'hidden' ); ?>><?php esc_html_e( 'Hidden — blur fuerte + capa oscura sólida', 'bunny-sfw-nsfw-gallery' ); ?></option>
								</select>
								<p class="bunny-field-desc"><strong>Minimal</strong>: imágenes visibles con blur, badge flotante, modal al hacer clic. <strong>Overlay</strong>: comportamiento anterior. <strong>Hidden</strong>: contenido casi oculto.</p>
							</div>
						</div>

						<div class="bunny-field-row bunny-field-row--check">
							<div class="bunny-field-control">
								<label class="bunny-field-check-wrap">
									<input type="checkbox" name="<?php echo $opt; ?>[blur]" value="1"<?php checked( $blur ); ?>>
									<span class="bunny-field-check-text">
										<strong><?php esc_html_e( 'Blur NSFW activado', 'bunny-sfw-nsfw-gallery' ); ?></strong>
										<span class="bunny-field-desc"><?php esc_html_e( 'Aplica blur a las imágenes NSFW hasta confirmación de edad.', 'bunny-sfw-nsfw-gallery' ); ?></span>
									</span>
								</label>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label">
								<?php esc_html_e( 'Intensidad del blur', 'bunny-sfw-nsfw-gallery' ); ?>
								<em>0 – 20px</em>
							</div>
							<div class="bunny-field-control">
								<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
									<input type="range" min="0" max="20" step="1"
									       name="<?php echo $opt; ?>[blur_intensity]"
									       id="bunny_blur_intensity"
									       value="<?php echo esc_attr( $blur_intensity ); ?>"
									       style="width:200px;"
									       oninput="document.getElementById('bunny_blur_val').textContent=this.value+'px';document.getElementById('bunny_blur_preview').style.filter='blur('+this.value+'px)';">
									<span id="bunny_blur_val" style="font-weight:600;min-width:36px;"><?php echo esc_html( $blur_intensity ); ?>px</span>
									<img id="bunny_blur_preview"
									     src="<?php echo esc_url( admin_url( 'images/wordpress-logo.svg' ) ); ?>"
									     style="width:80px;height:80px;object-fit:contain;filter:blur(<?php echo esc_attr( $blur_intensity ); ?>px);background:#f0f0f1;border-radius:6px;padding:8px;transition:filter 0.1s;"
									     alt="Preview blur">
								</div>
								<p class="bunny-field-desc">CSS variable <code>--bunny-blur</code>.</p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Mensaje NSFW', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<textarea name="<?php echo $opt; ?>[nsfw_message]" rows="2" class="large-text"><?php echo esc_textarea( $nsfw_message ); ?></textarea>
								<p class="bunny-field-desc"><?php esc_html_e( 'Mensaje en el overlay/modal de verificación de edad.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Texto del botón desbloquear', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<input type="text" class="regular-text"
								       name="<?php echo $opt; ?>[unlock_button_text]"
								       value="<?php echo esc_attr( $unlock_text ); ?>"
								       placeholder="Ver contenido (+18)">
								<p class="bunny-field-desc"><?php esc_html_e( 'Texto del botón de desbloqueo en overlay y modal.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

					</div>
				</div>

				<?php /* ---- CARD: Títulos ---- */ ?>
				<div class="bunny-card">
					<div class="bunny-card-header">
						<span class="bunny-card-icon">🏷️</span>
						<h2 class="bunny-card-title"><?php esc_html_e( 'Títulos', 'bunny-sfw-nsfw-gallery' ); ?></h2>
					</div>
					<div class="bunny-card-body">

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Título galería SFW', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<input type="text" class="large-text"
								       name="<?php echo $opt; ?>[sfw_title]"
								       value="<?php echo esc_attr( $sfw_title ); ?>"
								       placeholder="Ej: Galería de imágenes">
								<p class="bunny-field-desc"><?php esc_html_e( 'Dejar vacío para no mostrar título.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Título galería NSFW', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<input type="text" class="large-text"
								       name="<?php echo $opt; ?>[nsfw_title]"
								       value="<?php echo esc_attr( $nsfw_title ); ?>"
								       placeholder="Ej: Contenido para adultos">
								<p class="bunny-field-desc"><?php esc_html_e( 'Dejar vacío para no mostrar título.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

					</div>
				</div>

				<?php /* ---- CARD: Lightbox ---- */ ?>
				<div class="bunny-card">
					<div class="bunny-card-header">
						<span class="bunny-card-icon">✨</span>
						<h2 class="bunny-card-title"><?php esc_html_e( 'Lightbox', 'bunny-sfw-nsfw-gallery' ); ?></h2>
					</div>
					<div class="bunny-card-body">

						<div class="bunny-field-row bunny-field-row--check">
							<div class="bunny-field-control">
								<label class="bunny-field-check-wrap">
									<input type="checkbox" name="<?php echo $opt; ?>[show_lightbox_thumbnails]" value="1"<?php checked( $lb_thumbs ); ?>>
									<span class="bunny-field-check-text">
										<strong><?php esc_html_e( 'Miniaturas en lightbox', 'bunny-sfw-nsfw-gallery' ); ?></strong>
										<span class="bunny-field-desc"><?php esc_html_e( 'Muestra un carril de miniaturas en la parte inferior del lightbox. Usa el tamaño thumbnail de WordPress.', 'bunny-sfw-nsfw-gallery' ); ?></span>
									</span>
								</label>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Tema del lightbox', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<select name="<?php echo $opt; ?>[lightbox_theme]">
									<option value="dark"  <?php selected( $lb_theme, 'dark' ); ?>><?php esc_html_e( 'Dark — fondo oscuro elegante', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="light" <?php selected( $lb_theme, 'light' ); ?>><?php esc_html_e( 'Light — fondo claro (estilo iOS Photos)', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="auto"  <?php selected( $lb_theme, 'auto' ); ?>><?php esc_html_e( 'Auto — sigue prefers-color-scheme del sistema', 'bunny-sfw-nsfw-gallery' ); ?></option>
								</select>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Color de acento', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<input type="color" name="<?php echo $opt; ?>[lightbox_accent_color]" value="<?php echo esc_attr( $lb_accent ); ?>">
								<p class="bunny-field-desc"><?php esc_html_e( 'Color usado en miniatura activa, hover, counter y focus states.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Campos de caption', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<fieldset style="display:flex;gap:16px;flex-wrap:wrap;">
									<legend class="screen-reader-text"><?php esc_html_e( 'Campos de caption', 'bunny-sfw-nsfw-gallery' ); ?></legend>
									<?php foreach ( [ 'alt' => 'ALT', 'title' => 'Título', 'caption' => 'Caption', 'description' => 'Descripción' ] as $k => $label ) : ?>
									<label class="bunny-field-check-wrap" style="flex-direction:row;align-items:center;gap:6px;">
										<input type="checkbox"
										       name="<?php echo $opt; ?>[lightbox_caption_fields][]"
										       value="<?php echo esc_attr( $k ); ?>"
										       <?php checked( in_array( $k, $lb_cap_fields, true ) ); ?>>
										<span><?php echo esc_html( $label ); ?></span>
									</label>
									<?php endforeach; ?>
								</fieldset>
								<p class="bunny-field-desc"><?php esc_html_e( 'Campos a mostrar bajo la imagen. Si no se selecciona ninguno, el caption queda oculto.', 'bunny-sfw-nsfw-gallery' ); ?></p>
							</div>
						</div>

						<div class="bunny-field-row">
							<div class="bunny-field-label"><?php esc_html_e( 'Modo de caption', 'bunny-sfw-nsfw-gallery' ); ?></div>
							<div class="bunny-field-control">
								<select name="<?php echo $opt; ?>[lightbox_caption_mode]">
									<option value="hidden"  <?php selected( $lb_cap_mode, 'hidden' ); ?>><?php esc_html_e( 'Hidden — nunca mostrar caption', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="minimal" <?php selected( $lb_cap_mode, 'minimal' ); ?>><?php esc_html_e( 'Minimal — título + una línea de texto', 'bunny-sfw-nsfw-gallery' ); ?></option>
									<option value="full"    <?php selected( $lb_cap_mode, 'full' ); ?>><?php esc_html_e( 'Full — todos los campos seleccionados', 'bunny-sfw-nsfw-gallery' ); ?></option>
								</select>
							</div>
						</div>

					</div>
				</div>

				<div class="bunny-form-actions">
					<?php submit_button( __( 'Save settings', 'bunny-sfw-nsfw-gallery' ), 'primary', 'submit', false ); ?>
				</div>

			</form>

		</div>
	</div>
	<?php
}

// -----------------------------------------------------------------------------
// FIELD RENDERERS
// Registered via add_settings_field for sanitize_callback. The page above
// renders its own HTML and never calls do_settings_sections(), so these
// functions are intentionally empty.
// -----------------------------------------------------------------------------

function bunny_field_columns(): void {}
function bunny_field_image_size(): void {}
function bunny_field_aspect_ratio(): void {}
function bunny_field_link_behavior(): void {}
function bunny_field_target_blank(): void {}
function bunny_field_nsfw_display_style(): void {}
function bunny_field_blur(): void {}
function bunny_field_blur_intensity(): void {}
function bunny_field_nsfw_message(): void {}
function bunny_field_unlock_button_text(): void {}
function bunny_field_sfw_title(): void {}
function bunny_field_nsfw_title(): void {}
function bunny_field_lightbox_thumbs(): void {}
function bunny_field_lightbox_theme(): void {}
function bunny_field_lightbox_accent(): void {}
function bunny_field_lightbox_caption_fields(): void {}
function bunny_field_lightbox_caption_mode(): void {}

// =============================================================================
// IMAGE NORMALIZATION SETTINGS (0.6.0)
// =============================================================================

 


define( 'BUNNY_NORMALIZATION_OPTION', 'bunny_normalization_settings' );

function bunny_normalization_hardcoded_defaults(): array {
    return [
        'enabled'       => false,
        'ratio_mode'    => 'auto',
        'method'        => 'pad',
        'fill_mode'     => 'solid_color',
        'bg_color'      => 'white',
        'sample_corner' => 'top_left',
        'tolerance'     => 0.0,
        'keep_original' => true,
        'formats'       => [
            [ 'enabled' => true,  'name' => '1:1',    'ratio' => 1.0    ],
            [ 'enabled' => true,  'name' => '4:5',    'ratio' => 0.8    ],
            [ 'enabled' => true,  'name' => '1.91:1', 'ratio' => 1.91   ],
            [ 'enabled' => false, 'name' => '9:16',   'ratio' => 0.5625 ],
            [ 'enabled' => true,  'name' => '4.74:1', 'ratio' => 4.74   ],
        ],
        'debug_enabled' => false,
    ];
}

function bunny_normalization_register_settings(): void {
    register_setting(
        'bunny_normalization_group',
        BUNNY_NORMALIZATION_OPTION,
        [
            'type'              => 'array',
            'sanitize_callback' => 'bunny_normalization_sanitize_options',
            'default'           => bunny_normalization_hardcoded_defaults(),
        ]
    );

    add_settings_section(
        'bunny_section_normalization',
        'Image Normalization',
        '__return_false',
        'bunny-normalization-settings'
    );

    add_settings_field( 'norm_enabled',       'Enable Image Normalization', 'bunny_field_norm_enabled',       'bunny-normalization-settings', 'bunny_section_normalization' );
    add_settings_field( 'norm_ratio_mode',    'Ratio Mode',                 'bunny_field_norm_ratio_mode',    'bunny-normalization-settings', 'bunny_section_normalization' );
    add_settings_field( 'norm_method',        'Processing Method',          'bunny_field_norm_method',        'bunny-normalization-settings', 'bunny_section_normalization' );
    add_settings_field( 'norm_fill_mode',     'Background Fill Mode',       'bunny_field_norm_fill_mode',     'bunny-normalization-settings', 'bunny_section_normalization' );
    add_settings_field( 'norm_bg_color',      'Background Color',           'bunny_field_norm_bg_color',      'bunny-normalization-settings', 'bunny_section_normalization' );
    add_settings_field( 'norm_sample_corner', 'Sample Corner',              'bunny_field_norm_sample_corner', 'bunny-normalization-settings', 'bunny_section_normalization' );
    add_settings_field( 'norm_tolerance',     'Ratio Tolerance',            'bunny_field_norm_tolerance',     'bunny-normalization-settings', 'bunny_section_normalization' );
    add_settings_field( 'norm_keep_original', 'Keep Original',              'bunny_field_norm_keep_original', 'bunny-normalization-settings', 'bunny_section_normalization' );
}
add_action( 'admin_init', 'bunny_normalization_register_settings' );

function bunny_normalization_sanitize_options( $input ): array {
    $d     = bunny_normalization_hardcoded_defaults();
    $clean = [];

    $clean['enabled']       = ! empty( $input['enabled'] );
    $clean['keep_original'] = ! empty( $input['keep_original'] );
    $clean['debug_enabled'] = ! empty( $input['debug_enabled'] );

    // One-shot: clear debug log if requested (not persisted).
    if ( ! empty( $input['clear_log'] ) ) {
        $uploads  = wp_upload_dir();
        $log_path = $uploads['basedir'] . '/bunny-gallery-debug.log';
        if ( file_exists( $log_path ) ) {
            @unlink( $log_path );
        }
    }

    // Formats: exactly 5 fixed rows — name/ratio always come from defaults,
    // only 'enabled' is user-editable.
    $clean_formats = [];
    $raw_formats   = $input['formats'] ?? [];
    for ( $i = 0; $i < 5; $i++ ) {
        $def_fmt  = $d['formats'][ $i ];
        $raw_fmt  = $raw_formats[ $i ] ?? [];
        $clean_formats[ $i ] = [
            'enabled' => ! empty( $raw_fmt['enabled'] ),
            'name'    => $def_fmt['name'],
            'ratio'   => $def_fmt['ratio'],
        ];
    }
    $clean['formats'] = $clean_formats;

    // Dynamically validate ratio mode based on allowed formats
    $allowed_ratios = [ 'auto' ];
    foreach ( $clean_formats as $fmt ) {
        if ( $fmt['name'] !== '' ) {
            $allowed_ratios[] = $fmt['name'];
        }
    }
    $clean['ratio_mode'] = in_array( $input['ratio_mode'] ?? '', $allowed_ratios, true )
                           ? $input['ratio_mode'] : $d['ratio_mode'];

    $allowed_methods = [ 'pad', 'crop', 'smart_crop' ];
    $clean['method'] = in_array( $input['method'] ?? '', $allowed_methods, true )
                       ? $input['method'] : $d['method'];

    $allowed_fill_modes  = [ 'solid_color', 'corner_sample', 'dominant_color' ];
    $clean['fill_mode']  = in_array( $input['fill_mode'] ?? '', $allowed_fill_modes, true )
                           ? $input['fill_mode'] : $d['fill_mode'];

    $allowed_colors    = [ 'white', 'black', 'transparent' ];
    $clean['bg_color'] = in_array( $input['bg_color'] ?? '', $allowed_colors, true )
                         ? $input['bg_color'] : $d['bg_color'];

    $allowed_corners        = [ 'top_left', 'top_right', 'bottom_left', 'bottom_right', 'average_corners' ];
    $clean['sample_corner'] = in_array( $input['sample_corner'] ?? '', $allowed_corners, true )
                              ? $input['sample_corner'] : $d['sample_corner'];

    $tol                = (float) ( $input['tolerance'] ?? 0 );
    $clean['tolerance'] = max( 0.0, min( 1.0, $tol ) );

    return $clean;
}

function bunny_normalization_add_menu(): void {
    add_submenu_page(
        'bunny-gallery-settings',
        'Image Normalization',
        'Normalization',
        'manage_options',
        'bunny-normalization-settings',
        'bunny_normalization_settings_page'
    );
}
add_action( 'admin_menu', 'bunny_normalization_add_menu' );

/**
 * Handle View Log & Clear Log actions early, before any HTML output.
 * This prevents the "headers already sent" error.
 */
function bunny_normalization_early_actions(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $page = $_GET['page'] ?? '';
    if ( $page !== 'bunny-normalization-settings' ) {
        return;
    }

    // View Log — serve as plain text download
    if ( isset( $_GET['bunny_view_log'] ) ) {
        $uploads  = wp_upload_dir();
        $log_path = $uploads['basedir'] . '/bunny-gallery-debug.log';
        if ( file_exists( $log_path ) ) {
            header( 'Content-Type: text/plain; charset=utf-8' );
            header( 'Content-Disposition: inline; filename="bunny-gallery-debug.log"' );
            readfile( $log_path );
            exit;
        } else {
            wp_die( 'Log file does not exist.' );
        }
    }

}
add_action( 'admin_init', 'bunny_normalization_early_actions' );

function bunny_normalization_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $uploads  = wp_upload_dir();
    $log_path = $uploads['basedir'] . '/bunny-gallery-debug.log';
    $log_url  = add_query_arg( [ 'bunny_view_log' => 1 ], admin_url( 'admin.php?page=bunny-normalization-settings' ) );

    $opts = get_option( BUNNY_NORMALIZATION_OPTION, [] );
    $d    = bunny_normalization_hardcoded_defaults();

    $enabled       = ! empty( $opts['enabled'] );
    $ratio_mode    = $opts['ratio_mode']    ?? $d['ratio_mode'];
    $method        = $opts['method']        ?? $d['method'];
    $fill_mode     = $opts['fill_mode']     ?? $d['fill_mode'];
    $bg_color      = $opts['bg_color']      ?? $d['bg_color'];
    $sample_corner = $opts['sample_corner'] ?? $d['sample_corner'];
    $tolerance     = isset( $opts['tolerance'] ) ? (float) $opts['tolerance'] : $d['tolerance'];
    $keep_orig     = isset( $opts['keep_original'] ) ? (bool) $opts['keep_original'] : $d['keep_original'];
    $formats       = ! empty( $opts['formats'] ) ? $opts['formats'] : $d['formats'];
    $debug_enabled = ! empty( $opts['debug_enabled'] );

    $log_exists    = file_exists( $log_path );
    $log_size      = $log_exists ? size_format( filesize( $log_path ) ) : '0 B';

    $opt = BUNNY_NORMALIZATION_OPTION;

    $pad_display    = ( $method !== 'pad' ) ? 'display:none;' : '';
    $color_display  = ( $method !== 'pad' || $fill_mode !== 'solid_color' )   ? 'display:none;' : '';
    $corner_display = ( $method !== 'pad' || $fill_mode !== 'corner_sample' ) ? 'display:none;' : '';

    require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin-header.php';
    ?>
    <div class="wrap bunny-wrap" id="bunny-settings-wrap">
        <?php BunnyNSFW\Admin_Header::render( 'bunny-normalization-settings', 'Image Normalization' ); ?>
        <div class="bunny-page-content">

            <p class="bunny-gallery-admin-desc"><?php esc_html_e( 'Automatically normalizes images to a consistent aspect ratio on upload. Normalization runs before WordPress generates thumbnails, so all derived sizes are already consistent.', 'bunny-sfw-nsfw-gallery' ); ?></p>

            <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'bunny-sfw-nsfw-gallery' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php" class="bunny-norm-form">
                <?php settings_fields( 'bunny_normalization_group' ); ?>

                <?php /* ---- CARD: General ---- */ ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">&#9881;&#65039;</span><h2 class="bunny-card-title"><?php esc_html_e( 'General', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">
                        <div class="bunny-field-row bunny-field-row--check"><div class="bunny-field-control"><label class="bunny-field-check-wrap">
                            <input type="checkbox" name="<?php echo $opt; ?>[enabled]" value="1"<?php checked( $enabled ); ?>>
                            <span class="bunny-field-check-text"><strong><?php esc_html_e( 'Enable Image Normalization', 'bunny-sfw-nsfw-gallery' ); ?></strong><span class="bunny-field-desc"><?php esc_html_e( 'When enabled, images are normalized on upload before WordPress generates any thumbnails. Disabled by default.', 'bunny-sfw-nsfw-gallery' ); ?></span></span>
                        </label></div></div>
                        <div class="bunny-field-row bunny-field-row--check"><div class="bunny-field-control"><label class="bunny-field-check-wrap">
                            <input type="checkbox" name="<?php echo $opt; ?>[keep_original]" value="1"<?php checked( $keep_orig ); ?>>
                            <span class="bunny-field-check-text"><strong><?php esc_html_e( 'Keep Original', 'bunny-sfw-nsfw-gallery' ); ?></strong><span class="bunny-field-desc"><?php esc_html_e( 'Saves a copy of the original file before normalizing (e.g. photo_original.jpg). Enabled by default.', 'bunny-sfw-nsfw-gallery' ); ?></span></span>
                        </label></div></div>
                    </div>
                </div>

                <?php /* ---- CARD: Normalization Formats ---- */ ?>
                <?php $fixed_formats = bunny_normalization_hardcoded_defaults()['formats']; ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">&#128205;</span><h2 class="bunny-card-title"><?php esc_html_e( 'Normalization Formats', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">
                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Target Ratios', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <?php for ( $i = 0; $i < 5; $i++ ) :
                                    $fe = (bool) ( $formats[ $i ]['enabled'] ?? $fixed_formats[ $i ]['enabled'] );
                                    $fn = $fixed_formats[ $i ]['name'];
                                    $fr = $fixed_formats[ $i ]['ratio'];
                                    $fr_display = number_format( (float) $fr, 4 );
                                ?>
                                <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--bunny-border,#e0e0e0);">
                                    <input type="checkbox" name="<?php echo $opt; ?>[formats][<?php echo $i; ?>][enabled]" value="1" <?php checked( $fe ); ?> style="flex-shrink:0;">
                                    <span style="width:120px;font-weight:600;"><?php echo esc_html( $fn ); ?></span>
                                    <span style="width:130px;color:#666;"><?php echo esc_html( $fr_display ); ?></span>
                                </div>
                                <?php endfor; ?>
                                <p class="bunny-field-desc" style="margin-top:8px;"><?php esc_html_e( 'Enable or disable each format. Enabled formats are used when Ratio Mode is Auto. Ratio is width &divide; height as a decimal.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php /* ---- CARD: Aspect Ratio ---- */ ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">&#128208;</span><h2 class="bunny-card-title"><?php esc_html_e( 'Aspect Ratio', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">

                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Ratio Mode', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <select name="<?php echo $opt; ?>[ratio_mode]">
                                    <option value="auto" <?php selected( $ratio_mode, 'auto' ); ?>><?php esc_html_e( 'Auto — detect closest ratio automatically', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <?php foreach ( $formats as $fmt ) :
                                        if ( empty( $fmt['name'] ) || empty( $fmt['ratio'] ) ) continue; ?>
                                    <option value="<?php echo esc_attr( $fmt['name'] ); ?>" <?php selected( $ratio_mode, $fmt['name'] ); ?>><?php echo esc_html( $fmt['name'] . ' (' . $fmt['ratio'] . ')' ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="bunny-field-desc"><?php esc_html_e( 'Auto picks the nearest enabled format. A fixed format forces all images to that target ratio.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Ratio Tolerance', 'bunny-sfw-nsfw-gallery' ); ?><em>0.00 – 1.00</em></div>
                            <div class="bunny-field-control">
                                <input type="number" min="0" max="1" step="0.01" name="<?php echo $opt; ?>[tolerance]" value="<?php echo esc_attr( number_format( $tolerance, 2 ) ); ?>">
                                <p class="bunny-field-desc"><?php esc_html_e( 'Acceptable difference between the image\'s real ratio and the target. 0 = always normalize. Example: 0.05 skips images already within 5% of the target.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                    </div>
                </div>

                <?php /* ---- CARD: Processing ---- */ ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">&#128444;&#65039;</span><h2 class="bunny-card-title"><?php esc_html_e( 'Processing', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">

                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Processing Method', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <select name="<?php echo $opt; ?>[method]" id="bunny_norm_method">
                                    <option value="pad"        <?php selected( $method, 'pad' ); ?>><?php esc_html_e( 'Pad — expand canvas, never crop', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="crop"       <?php selected( $method, 'crop' ); ?>><?php esc_html_e( 'Crop — geometric center crop', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="smart_crop" <?php selected( $method, 'smart_crop' ); ?>><?php esc_html_e( 'Smart Crop — center-biased crop', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                </select>
                                <p class="bunny-field-desc"><?php esc_html_e( 'Pad: adds background space — no pixels removed. Crop: removes from the sides. Smart Crop: same, biased toward the center.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                        <div class="bunny-field-row" id="bnrow_fill_mode" style="<?php echo $pad_display; ?>">
                            <div class="bunny-field-label"><?php esc_html_e( 'Background Fill Mode', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <select name="<?php echo $opt; ?>[fill_mode]" id="bunny_norm_fill_mode">
                                    <option value="solid_color"    <?php selected( $fill_mode, 'solid_color' ); ?>><?php esc_html_e( 'Solid Color', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="corner_sample"  <?php selected( $fill_mode, 'corner_sample' ); ?>><?php esc_html_e( 'Corner Sample', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="dominant_color" <?php selected( $fill_mode, 'dominant_color' ); ?>><?php esc_html_e( 'Dominant Color', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                </select>
                                <p class="bunny-field-desc"><?php esc_html_e( 'Solid Color: fixed color. Corner Sample: reads a corner pixel region. Dominant Color: downsamples to 50×50 and averages all pixels.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                        <div class="bunny-field-row" id="bnrow_bg_color" style="<?php echo $color_display; ?>">
                            <div class="bunny-field-label"><?php esc_html_e( 'Background Color', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <select name="<?php echo $opt; ?>[bg_color]">
                                    <option value="white"       <?php selected( $bg_color, 'white' ); ?>><?php esc_html_e( 'White', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="black"       <?php selected( $bg_color, 'black' ); ?>><?php esc_html_e( 'Black', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="transparent" <?php selected( $bg_color, 'transparent' ); ?>><?php esc_html_e( 'Transparent (PNG / WebP only)', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                </select>
                                <p class="bunny-field-desc"><?php esc_html_e( 'Only applies when Fill Mode is Solid Color. Transparent works only for PNG and WebP — JPEG falls back to white.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                        <div class="bunny-field-row" id="bnrow_sample_corner" style="<?php echo $corner_display; ?>">
                            <div class="bunny-field-label"><?php esc_html_e( 'Sample Corner', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <select name="<?php echo $opt; ?>[sample_corner]">
                                    <option value="top_left"        <?php selected( $sample_corner, 'top_left' ); ?>><?php esc_html_e( 'Top Left', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="top_right"       <?php selected( $sample_corner, 'top_right' ); ?>><?php esc_html_e( 'Top Right', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="bottom_left"     <?php selected( $sample_corner, 'bottom_left' ); ?>><?php esc_html_e( 'Bottom Left', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="bottom_right"    <?php selected( $sample_corner, 'bottom_right' ); ?>><?php esc_html_e( 'Bottom Right', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                    <option value="average_corners" <?php selected( $sample_corner, 'average_corners' ); ?>><?php esc_html_e( 'Average of all four corners', 'bunny-sfw-nsfw-gallery' ); ?></option>
                                </select>
                                <p class="bunny-field-desc"><?php esc_html_e( 'A 4×4 px region is averaged at the chosen corner to avoid single-pixel noise.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                    </div>
                </div>

                <?php /* ---- CARD: Advanced & Diagnostics ---- */ ?>
                <div class="bunny-card">
                    <div class="bunny-card-header">
                        <span class="bunny-card-icon">🔍</span>
                        <h2 class="bunny-card-title"><?php esc_html_e( 'Advanced & Diagnostics', 'bunny-sfw-nsfw-gallery' ); ?></h2>
                    </div>
                    <div class="bunny-card-body">
                        <div class="bunny-field-row bunny-field-row--check">
                            <div class="bunny-field-control">
                                <label class="bunny-field-check-wrap">
                                    <input type="checkbox" name="<?php echo $opt; ?>[debug_enabled]" value="1"<?php checked( $debug_enabled ); ?>>
                                    <span class="bunny-field-check-text">
                                        <strong><?php esc_html_e( 'Enable Debug Log', 'bunny-sfw-nsfw-gallery' ); ?></strong>
                                        <span class="bunny-field-desc"><?php esc_html_e( 'Records a detailed diagnostics log for every normalized image. Disable in production unless active debugging is required.', 'bunny-sfw-nsfw-gallery' ); ?></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Log Details', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <p style="margin: 0 0 8px 0;">
                                    <strong><?php esc_html_e( 'Log Path:', 'bunny-sfw-nsfw-gallery' ); ?></strong>
                                    <code><?php echo esc_html( $log_path ); ?></code>
                                </p>
                                <p style="margin: 0 0 12px 0;">
                                    <strong><?php esc_html_e( 'Log Size:', 'bunny-sfw-nsfw-gallery' ); ?></strong>
                                    <code><?php echo esc_html( $log_size ); ?></code>
                                </p>

                                <div style="display:flex;gap:10px;align-items:center;">
                                    <?php if ( $log_exists ) : ?>
                                        <a href="<?php echo esc_url( $log_url ); ?>" target="_blank" class="button button-secondary"><?php esc_html_e( 'View Log', 'bunny-sfw-nsfw-gallery' ); ?></a>
                                    <?php endif; ?>
                                </div>

                                <label class="bunny-field-check-wrap" style="margin-top:10px;">
                                    <input type="checkbox" name="<?php echo $opt; ?>[clear_log]" value="1">
                                    <span class="bunny-field-check-text"><?php esc_html_e( 'Clear log on next save', 'bunny-sfw-nsfw-gallery' ); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bunny-form-actions"><?php submit_button( __( 'Save settings', 'bunny-sfw-nsfw-gallery' ), 'primary', 'submit', false ); ?></div>

            </form>
        </div>
    </div>

    <script>
    (function () {
        'use strict';
        function bnSync() {
            var method    = document.getElementById('bunny_norm_method');
            var fill      = document.getElementById('bunny_norm_fill_mode');
            var rowFill   = document.getElementById('bnrow_fill_mode');
            var rowColor  = document.getElementById('bnrow_bg_color');
            var rowCorner = document.getElementById('bnrow_sample_corner');
            if ( ! method || ! fill ) return;
            var isPad = method.value === 'pad';
            var fMode = fill.value;
            if ( rowFill   ) rowFill.style.display   = isPad ? '' : 'none';
            if ( rowColor  ) rowColor.style.display  = ( isPad && fMode === 'solid_color'   ) ? '' : 'none';
            if ( rowCorner ) rowCorner.style.display = ( isPad && fMode === 'corner_sample' ) ? '' : 'none';
        }
        document.addEventListener('DOMContentLoaded', function () {
            var method = document.getElementById('bunny_norm_method');
            var fill   = document.getElementById('bunny_norm_fill_mode');
            if ( method ) method.addEventListener('change', bnSync);
            if ( fill   ) fill.addEventListener('change',   bnSync);
            bnSync();
        });
    })();
    </script>
    <?php
}

function bunny_field_norm_enabled(): void {}
function bunny_field_norm_ratio_mode(): void {}
function bunny_field_norm_method(): void {}
function bunny_field_norm_fill_mode(): void {}
function bunny_field_norm_bg_color(): void {}
function bunny_field_norm_sample_corner(): void {}
function bunny_field_norm_tolerance(): void {}
function bunny_field_norm_keep_original(): void {}

// =============================================================================
// IMAGE SETTINGS (0.7.0)
// Resize limit, big image threshold, intermediate sizes.
// =============================================================================

define( 'BUNNY_IMAGE_SETTINGS_OPTION', 'bunny_image_settings' );

function bunny_image_settings_register(): void {
    register_setting(
        'bunny_image_settings_group',
        BUNNY_IMAGE_SETTINGS_OPTION,
        [
            'type'              => 'array',
            'sanitize_callback' => 'bunny_image_settings_sanitize',
            'default'           => BunnyNSFW\Image_Settings::defaults(),
        ]
    );
}
add_action( 'admin_init', 'bunny_image_settings_register' );

function bunny_image_settings_sanitize( $input ): array {
    $d     = BunnyNSFW\Image_Settings::defaults();
    $clean = [];

    $clean['resize_enabled']    = ! empty( $input['resize_enabled'] );
    $clean['resize_max_width']  = max( 1, (int) ( $input['resize_max_width']  ?? $d['resize_max_width'] ) );
    $clean['resize_max_height'] = max( 1, (int) ( $input['resize_max_height'] ?? $d['resize_max_height'] ) );

    $clean['big_image_enabled']   = ! empty( $input['big_image_enabled'] );
    $clean['big_image_threshold'] = max( 1, (int) ( $input['big_image_threshold'] ?? $d['big_image_threshold'] ) );

    $allowed_sizes   = [ 'medium', 'medium_large', 'large', '1536x1536', '2048x2048' ];
    $raw_disabled    = is_array( $input['disabled_sizes'] ?? null ) ? $input['disabled_sizes'] : [];
    $clean['disabled_sizes'] = array_values( array_intersect( $raw_disabled, $allowed_sizes ) );

    // Optional automation toggles
    $clean['upload_rename_enabled'] = ! empty( $input['upload_rename_enabled'] );
    $clean['auto_alt_enabled']      = ! empty( $input['auto_alt_enabled'] );

    return $clean;
}

function bunny_image_settings_add_menu(): void {
    add_submenu_page(
        'bunny-gallery-settings',
        'Image Settings',
        'Image Settings',
        'manage_options',
        'bunny-image-settings',
        'bunny_image_settings_page'
    );
}
add_action( 'admin_menu', 'bunny_image_settings_add_menu' );

function bunny_image_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $opts = get_option( BUNNY_IMAGE_SETTINGS_OPTION, [] );
    $d    = BunnyNSFW\Image_Settings::defaults();
    $opt  = BUNNY_IMAGE_SETTINGS_OPTION;

    $resize_enabled    = ! empty( $opts['resize_enabled'] );
    $resize_max_w      = (int) ( $opts['resize_max_width']  ?? $d['resize_max_width'] );
    $resize_max_h      = (int) ( $opts['resize_max_height'] ?? $d['resize_max_height'] );
    $big_enabled       = ! empty( $opts['big_image_enabled'] );
    $big_threshold     = (int) ( $opts['big_image_threshold'] ?? $d['big_image_threshold'] );
    $disabled_sizes    = is_array( $opts['disabled_sizes'] ?? null ) ? $opts['disabled_sizes'] : [];

    require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin-header.php';
    ?>
    <div class="wrap bunny-wrap" id="bunny-settings-wrap">
        <?php BunnyNSFW\Admin_Header::render( 'bunny-image-settings', 'Image Settings' ); ?>
        <div class="bunny-page-content">

            <p class="bunny-gallery-admin-desc"><?php esc_html_e( 'Upload-time image constraints. All operations run after Image Normalization so the final file on disk is the normalized, then resized version.', 'bunny-sfw-nsfw-gallery' ); ?></p>

            <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'bunny-sfw-nsfw-gallery' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php" class="bunny-norm-form">
                <?php settings_fields( 'bunny_image_settings_group' ); ?>

                <?php /* ---- CARD: Image Limits ---- */ ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">&#128190;</span><h2 class="bunny-card-title"><?php esc_html_e( 'Image Limits', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">

                        <div class="bunny-field-row bunny-field-row--check"><div class="bunny-field-control"><label class="bunny-field-check-wrap">
                            <input type="checkbox" name="<?php echo $opt; ?>[resize_enabled]" value="1"<?php checked( $resize_enabled ); ?>>
                            <span class="bunny-field-check-text">
                                <strong><?php esc_html_e( 'Enable Resize Limit', 'bunny-sfw-nsfw-gallery' ); ?></strong>
                                <span class="bunny-field-desc"><?php esc_html_e( 'Downscales images that exceed the configured dimensions after upload. Runs after normalization (priority 20). Aspect ratio is preserved.', 'bunny-sfw-nsfw-gallery' ); ?></span>
                            </span>
                        </label></div></div>

                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Max Width', 'bunny-sfw-nsfw-gallery' ); ?><em>px</em></div>
                            <div class="bunny-field-control">
                                <input type="number" min="1" max="9999" step="1" name="<?php echo $opt; ?>[resize_max_width]" value="<?php echo esc_attr( $resize_max_w ); ?>">
                                <p class="bunny-field-desc"><?php esc_html_e( 'Maximum pixel width. Images narrower than this are not touched.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Max Height', 'bunny-sfw-nsfw-gallery' ); ?><em>px</em></div>
                            <div class="bunny-field-control">
                                <input type="number" min="1" max="9999" step="1" name="<?php echo $opt; ?>[resize_max_height]" value="<?php echo esc_attr( $resize_max_h ); ?>">
                                <p class="bunny-field-desc"><?php esc_html_e( 'Maximum pixel height. Images shorter than this are not touched.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                    </div>
                </div>

                <?php /* ---- CARD: Big Image Threshold ---- */ ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">&#128259;</span><h2 class="bunny-card-title"><?php esc_html_e( 'Big Image Threshold', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">

                        <div class="bunny-field-row bunny-field-row--check"><div class="bunny-field-control"><label class="bunny-field-check-wrap">
                            <input type="checkbox" name="<?php echo $opt; ?>[big_image_enabled]" value="1"<?php checked( $big_enabled ); ?>>
                            <span class="bunny-field-check-text">
                                <strong><?php esc_html_e( 'Enable Big Image Threshold', 'bunny-sfw-nsfw-gallery' ); ?></strong>
                                <span class="bunny-field-desc"><?php esc_html_e( 'Overrides WordPress\'s built-in big_image_size_threshold filter. WordPress auto-scales originals larger than this value before saving them.', 'bunny-sfw-nsfw-gallery' ); ?></span>
                            </span>
                        </label></div></div>

                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Threshold Value', 'bunny-sfw-nsfw-gallery' ); ?><em>px</em></div>
                            <div class="bunny-field-control">
                                <input type="number" min="1" max="9999" step="1" name="<?php echo $opt; ?>[big_image_threshold]" value="<?php echo esc_attr( $big_threshold ); ?>">
                                <p class="bunny-field-desc"><?php esc_html_e( 'Images with either dimension larger than this value are scaled down by WordPress core before any plugin processing. Default: 1920.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>

                    </div>
                </div>

                <?php /* ---- CARD: Intermediate Sizes ---- */ ?>
                <?php
                $all_sizes = [ 'medium' => 'medium', 'medium_large' => 'medium_large', 'large' => 'large', '1536x1536' => '1536×1536', '2048x2048' => '2048×2048' ];
                ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">&#128248;</span><h2 class="bunny-card-title"><?php esc_html_e( 'Intermediate Sizes', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">
                        <div class="bunny-field-row">
                            <div class="bunny-field-label"><?php esc_html_e( 'Disable Sizes', 'bunny-sfw-nsfw-gallery' ); ?></div>
                            <div class="bunny-field-control">
                                <fieldset style="display:flex;flex-direction:column;gap:8px;">
                                    <legend class="screen-reader-text"><?php esc_html_e( 'Intermediate sizes to disable', 'bunny-sfw-nsfw-gallery' ); ?></legend>
                                    <?php foreach ( $all_sizes as $size_key => $size_label ) :
                                        $is_disabled = in_array( $size_key, $disabled_sizes, true );
                                    ?>
                                    <label class="bunny-field-check-wrap" style="flex-direction:row;align-items:center;gap:8px;">
                                        <input type="checkbox" name="<?php echo $opt; ?>[disabled_sizes][]" value="<?php echo esc_attr( $size_key ); ?>"<?php checked( $is_disabled ); ?>>
                                        <span><?php echo esc_html( $size_label ); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </fieldset>
                                <p class="bunny-field-desc" style="margin-top:8px;"><?php esc_html_e( 'Checked sizes will not be generated on upload. Thumbnail and full sizes are always generated and cannot be disabled here.', 'bunny-sfw-nsfw-gallery' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php /* ---- CARD: Automation ---- */ ?>
                <div class="bunny-card">
                    <div class="bunny-card-header"><span class="bunny-card-icon">🤖</span><h2 class="bunny-card-title"><?php esc_html_e( 'Automation', 'bunny-sfw-nsfw-gallery' ); ?></h2></div>
                    <div class="bunny-card-body">

                        <div class="bunny-field-row bunny-field-row--check"><div class="bunny-field-control"><label class="bunny-field-check-wrap">
                            <input type="checkbox" name="<?php echo $opt; ?>[upload_rename_enabled]" value="1"<?php checked( ! empty( $opts['upload_rename_enabled'] ) ); ?>>
                            <span class="bunny-field-check-text"><strong><?php esc_html_e( 'Safe rename uploaded images', 'bunny-sfw-nsfw-gallery' ); ?></strong>
                                <span class="bunny-field-desc"><?php esc_html_e( 'Rewrite uploaded image filenames to use post slug + unique suffix to avoid collisions. Disabled by default.', 'bunny-sfw-nsfw-gallery' ); ?></span>
                            </span>
                        </label></div></div>

                        <div class="bunny-field-row bunny-field-row--check"><div class="bunny-field-control"><label class="bunny-field-check-wrap">
                            <input type="checkbox" name="<?php echo $opt; ?>[auto_alt_enabled]" value="1"<?php checked( ! empty( $opts['auto_alt_enabled'] ) ); ?>>
                            <span class="bunny-field-check-text"><strong><?php esc_html_e( 'Auto-fill ALT from post title', 'bunny-sfw-nsfw-gallery' ); ?></strong>
                                <span class="bunny-field-desc"><?php esc_html_e( 'Set attachment ALT to the parent post title + " - BunnyChase" when adding attachments. Disabled by default.', 'bunny-sfw-nsfw-gallery' ); ?></span>
                            </span>
                        </label></div></div>

                    </div>
                </div>

                <div class="bunny-form-actions"><?php submit_button( __( 'Save settings', 'bunny-sfw-nsfw-gallery' ), 'primary', 'submit', false ); ?></div>

            </form>
        </div>
    </div>
    <?php
}



<?php
/**
 * Bunny SFW&NSFW Gallery — Settings globales
 *
 * @package BunnyNSFW
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BUNNY_GALLERY_OPTION', 'bunny_gallery_defaults' );

// -----------------------------------------------------------------------------
// FALLBACKS HARDCODED
// -----------------------------------------------------------------------------

function bunny_gallery_hardcoded_defaults(): array {
    return [
        'columns'            => 3,
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
    $clean['columns'] = max( 1, min( 6, $cols ) );

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
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap" id="bunny-settings-wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-format-gallery" style="font-size:28px;width:28px;height:28px;color:#1d8348;"></span>
            Bunny SFW&amp;NSFW Gallery
        </h1>
        <p class="description">Valores por defecto para bloques nuevos. Un bloque con valor propio siempre lo prioriza.</p>
        <hr>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'bunny_gallery_group' );
            do_settings_sections( 'bunny-gallery-settings' );
            submit_button( 'Guardar ajustes' );
            ?>
        </form>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// FIELD RENDERERS
// -----------------------------------------------------------------------------

function bunny_field_columns(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['columns'] ?? bunny_gallery_hardcoded_defaults()['columns'];
    echo '<input type="number" min="1" max="6" step="1" name="' . BUNNY_GALLERY_OPTION . '[columns]" value="' . esc_attr( (int) $val ) . '" class="small-text">';
    echo '<p class="description">Entre 1 y 6 columnas.</p>';
}

function bunny_field_image_size(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['image_size'] ?? bunny_gallery_hardcoded_defaults()['image_size'];
    $options = [ 'thumbnail' => 'Thumbnail (~150px)', 'medium' => 'Medium (~300px)', 'large' => 'Large (~1024px)', 'full' => 'Full (original)' ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[image_size]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">El lightbox siempre muestra la imagen a tamaño completo.</p>';
}

function bunny_field_aspect_ratio(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['aspect_ratio'] ?? bunny_gallery_hardcoded_defaults()['aspect_ratio'];
    $options = [ 'square' => 'Square (1:1)', 'portrait' => 'Portrait (2:3)', 'landscape' => 'Landscape (16:9)', 'original' => 'Original (sin recorte)' ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[aspect_ratio]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function bunny_field_link_behavior(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['link_behavior'] ?? bunny_gallery_hardcoded_defaults()['link_behavior'];
    $options = [ 'none' => 'Sin enlace', 'lightbox' => 'Abrir en lightbox', 'file' => 'Archivo de media', 'attachment' => 'Página de adjunto' ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[link_behavior]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function bunny_field_target_blank(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['target_blank'] ?? bunny_gallery_hardcoded_defaults()['target_blank'];
    echo '<input type="checkbox" name="' . BUNNY_GALLERY_OPTION . '[target_blank]" value="1"' . checked( (bool) $val, true, false ) . '>';
    echo '<p class="description">Solo aplica para "Archivo de media" o "Página de adjunto".</p>';
}

function bunny_field_nsfw_display_style(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['nsfw_display_style'] ?? bunny_gallery_hardcoded_defaults()['nsfw_display_style'];
    $options = [
        'minimal' => 'Minimal — blur + badge flotante + modal elegante (tipo Patreon/Pixiv)',
        'overlay' => 'Overlay — capa semitransparente con botón centrado (tipo X/Twitter)',
        'hidden'  => 'Hidden — blur fuerte + capa oscura sólida',
    ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[nsfw_display_style]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
    echo '<p class="description"><strong>Minimal</strong>: imágenes visibles con blur, badge flotante, modal al hacer clic. <strong>Overlay</strong>: comportamiento anterior. <strong>Hidden</strong>: contenido casi oculto.</p>';
}

function bunny_field_blur(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['blur'] ?? bunny_gallery_hardcoded_defaults()['blur'];
    echo '<input type="checkbox" name="' . BUNNY_GALLERY_OPTION . '[blur]" value="1"' . checked( (bool) $val, true, false ) . '>';
    echo '<p class="description">Aplica blur a las imágenes NSFW hasta confirmación de edad.</p>';
}

function bunny_field_blur_intensity(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = isset( $opts['blur_intensity'] ) ? (int) $opts['blur_intensity'] : bunny_gallery_hardcoded_defaults()['blur_intensity'];
    ?>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <input type="range" min="0" max="20" step="1"
               name="<?php echo BUNNY_GALLERY_OPTION; ?>[blur_intensity]"
               id="bunny_blur_intensity"
               value="<?php echo esc_attr( $val ); ?>"
               style="width:200px;"
               oninput="document.getElementById('bunny_blur_val').textContent=this.value+'px';document.getElementById('bunny_blur_preview').style.filter='blur('+this.value+'px)';">
        <span id="bunny_blur_val" style="font-weight:600;min-width:36px;"><?php echo esc_html( $val ); ?>px</span>
        <img id="bunny_blur_preview"
             src="<?php echo esc_url( admin_url( 'images/wordpress-logo.svg' ) ); ?>"
             style="width:80px;height:80px;object-fit:contain;filter:blur(<?php echo esc_attr( $val ); ?>px);background:#f0f0f1;border-radius:6px;padding:8px;transition:filter 0.1s;"
             alt="Preview blur">
    </div>
    <p class="description">Entre 0 y 20px. CSS variable <code>--bunny-blur</code>.</p>
    <?php
}

function bunny_field_nsfw_message(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['nsfw_message'] ?? bunny_gallery_hardcoded_defaults()['nsfw_message'];
    echo '<textarea name="' . BUNNY_GALLERY_OPTION . '[nsfw_message]" rows="2" class="large-text">' . esc_textarea( $val ) . '</textarea>';
    echo '<p class="description">Mensaje en el overlay/modal de verificación de edad.</p>';
}

function bunny_field_unlock_button_text(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['unlock_button_text'] ?? bunny_gallery_hardcoded_defaults()['unlock_button_text'];
    echo '<input type="text" class="regular-text" name="' . BUNNY_GALLERY_OPTION . '[unlock_button_text]" value="' . esc_attr( $val ) . '" placeholder="Ver contenido (+18)">';
    echo '<p class="description">Texto del botón de desbloqueo en overlay y modal.</p>';
}

function bunny_field_sfw_title(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['sfw_title'] ?? '';
    echo '<input type="text" class="large-text" name="' . BUNNY_GALLERY_OPTION . '[sfw_title]" value="' . esc_attr( $val ) . '" placeholder="Ej: Galería de imágenes">';
    echo '<p class="description">Dejar vacío para no mostrar título.</p>';
}

function bunny_field_nsfw_title(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['nsfw_title'] ?? '';
    echo '<input type="text" class="large-text" name="' . BUNNY_GALLERY_OPTION . '[nsfw_title]" value="' . esc_attr( $val ) . '" placeholder="Ej: Contenido para adultos">';
    echo '<p class="description">Dejar vacío para no mostrar título.</p>';
}

// 0.4.0 — Lightbox field renderers

function bunny_field_lightbox_thumbs(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['show_lightbox_thumbnails'] ?? bunny_gallery_hardcoded_defaults()['show_lightbox_thumbnails'];
    echo '<input type="checkbox" name="' . BUNNY_GALLERY_OPTION . '[show_lightbox_thumbnails]" value="1"' . checked( (bool) $val, true, false ) . '>';
    echo '<p class="description">Muestra un carril de miniaturas en la parte inferior del lightbox. Usa el tamaño thumbnail de WordPress.</p>';
}

function bunny_field_lightbox_theme(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['lightbox_theme'] ?? bunny_gallery_hardcoded_defaults()['lightbox_theme'];
    $options = [ 'dark' => 'Dark — fondo oscuro elegante', 'light' => 'Light — fondo claro (estilo iOS Photos)', 'auto' => 'Auto — sigue prefers-color-scheme del sistema' ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[lightbox_theme]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function bunny_field_lightbox_accent(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['lightbox_accent_color'] ?? bunny_gallery_hardcoded_defaults()['lightbox_accent_color'];
    echo '<input type="color" name="' . BUNNY_GALLERY_OPTION . '[lightbox_accent_color]" value="' . esc_attr( $val ) . '">';
    echo '<p class="description">Color usado en miniatura activa, hover, counter y focus states.</p>';
}

function bunny_field_lightbox_caption_fields(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['lightbox_caption_fields'] ?? bunny_gallery_hardcoded_defaults()['lightbox_caption_fields'];
    if ( ! is_array( $current ) ) $current = [];
    $fields  = [ 'alt' => 'ALT', 'title' => 'Título', 'caption' => 'Caption', 'description' => 'Descripción' ];
    echo '<fieldset><legend class="screen-reader-text">Campos de caption</legend>';
    foreach ( $fields as $k => $label ) {
        $checked = in_array( $k, $current, true ) ? ' checked' : '';
        echo '<label style="margin-right:16px;">';
        echo '<input type="checkbox" name="' . BUNNY_GALLERY_OPTION . '[lightbox_caption_fields][]" value="' . esc_attr( $k ) . '"' . $checked . '> ';
        echo esc_html( $label );
        echo '</label>';
    }
    echo '</fieldset>';
    echo '<p class="description">Campos a mostrar bajo la imagen. Si no se selecciona ninguno, el caption queda oculto.</p>';
}

function bunny_field_lightbox_caption_mode(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['lightbox_caption_mode'] ?? bunny_gallery_hardcoded_defaults()['lightbox_caption_mode'];
    $options = [
        'hidden'  => 'Hidden — nunca mostrar caption',
        'minimal' => 'Minimal — título + una línea de texto',
        'full'    => 'Full — todos los campos seleccionados',
    ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[lightbox_caption_mode]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

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
        'columns'        => 3,
        'blur'           => true,
        'blur_intensity'  => 12,
        'link_behavior'  => 'none',
        'target_blank'   => false,
        'nsfw_message'   => 'Este contenido es solo para adultos.',
        'sfw_title'      => '',
        'nsfw_title'     => '',
        'image_size'     => 'large',
        'aspect_ratio'   => 'square',
    ];
}

// -----------------------------------------------------------------------------
// HELPER CENTRAL DE RESOLUCIÓN
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

    add_settings_section( 'bunny_section_gallery',  'Galería',              '__return_false', 'bunny-gallery-settings' );
    add_settings_section( 'bunny_section_nsfw',     'Protección NSFW',      '__return_false', 'bunny-gallery-settings' );
    add_settings_section( 'bunny_section_titles',   'Títulos',              '__return_false', 'bunny-gallery-settings' );

    // Galería
    add_settings_field( 'columns',       'Columnas por defecto',     'bunny_field_columns',       'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'image_size',    'Tamaño de imagen',         'bunny_field_image_size',    'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'aspect_ratio',  'Aspect ratio',             'bunny_field_aspect_ratio',  'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'link_behavior', 'Comportamiento de enlace', 'bunny_field_link_behavior', 'bunny-gallery-settings', 'bunny_section_gallery' );
    add_settings_field( 'target_blank',  'Abrir en nueva pestaña',   'bunny_field_target_blank',  'bunny-gallery-settings', 'bunny_section_gallery' );

    // NSFW
    add_settings_field( 'blur',           'Blur NSFW activado',   'bunny_field_blur',           'bunny-gallery-settings', 'bunny_section_nsfw' );
    add_settings_field( 'blur_intensity', 'Intensidad del blur',  'bunny_field_blur_intensity', 'bunny-gallery-settings', 'bunny_section_nsfw' );
    add_settings_field( 'nsfw_message',   'Mensaje overlay NSFW', 'bunny_field_nsfw_message',   'bunny-gallery-settings', 'bunny_section_nsfw' );

    // Títulos
    add_settings_field( 'sfw_title',  'Título galería SFW',  'bunny_field_sfw_title',  'bunny-gallery-settings', 'bunny_section_titles' );
    add_settings_field( 'nsfw_title', 'Título galería NSFW', 'bunny_field_nsfw_title', 'bunny-gallery-settings', 'bunny_section_titles' );
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

    $clean['blur']          = ! empty( $input['blur'] );
    $clean['target_blank']  = ! empty( $input['target_blank'] );

    $intensity               = absint( $input['blur_intensity'] ?? $d['blur_intensity'] );
    $clean['blur_intensity'] = max( 0, min( 20, $intensity ) );

    $allowed_links          = [ 'none', 'lightbox', 'file', 'attachment' ];
    $clean['link_behavior'] = in_array( $input['link_behavior'] ?? '', $allowed_links, true )
                              ? $input['link_behavior'] : $d['link_behavior'];

    $allowed_sizes        = [ 'thumbnail', 'medium', 'large', 'full' ];
    $clean['image_size']  = in_array( $input['image_size'] ?? '', $allowed_sizes, true )
                            ? $input['image_size'] : $d['image_size'];

    $allowed_ratios       = [ 'square', 'portrait', 'landscape', 'original' ];
    $clean['aspect_ratio'] = in_array( $input['aspect_ratio'] ?? '', $allowed_ratios, true )
                             ? $input['aspect_ratio'] : $d['aspect_ratio'];

    $clean['nsfw_message'] = sanitize_text_field( $input['nsfw_message'] ?? $d['nsfw_message'] );
    $clean['sfw_title']    = sanitize_text_field( $input['sfw_title']    ?? '' );
    $clean['nsfw_title']   = sanitize_text_field( $input['nsfw_title']   ?? '' );

    return $clean;
}

// -----------------------------------------------------------------------------
// MENÚ PROPIO (nivel superior, no bajo Settings)
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
        <p class="description">
            Valores por defecto para bloques nuevos. Un bloque con valor propio siempre lo prioriza.
        </p>
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
    echo '<input type="number" min="1" max="6" step="1"
                 name="' . BUNNY_GALLERY_OPTION . '[columns]"
                 value="' . esc_attr( (int) $val ) . '"
                 class="small-text">';
    echo '<p class="description">Entre 1 y 6 columnas.</p>';
}

function bunny_field_image_size(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['image_size'] ?? bunny_gallery_hardcoded_defaults()['image_size'];
    $options = [
        'thumbnail' => 'Thumbnail (~150px)',
        'medium'    => 'Medium (~300px)',
        'large'     => 'Large (~1024px)',
        'full'      => 'Full (original)',
    ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[image_size]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Tamaño usado en el grid. El lightbox siempre muestra la imagen a tamaño completo.</p>';
}

function bunny_field_aspect_ratio(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['aspect_ratio'] ?? bunny_gallery_hardcoded_defaults()['aspect_ratio'];
    $options = [
        'square'    => 'Square (1:1)',
        'portrait'  => 'Portrait (2:3)',
        'landscape' => 'Landscape (16:9)',
        'original'  => 'Original (sin recorte)',
    ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[aspect_ratio]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function bunny_field_link_behavior(): void {
    $opts    = get_option( BUNNY_GALLERY_OPTION, [] );
    $current = $opts['link_behavior'] ?? bunny_gallery_hardcoded_defaults()['link_behavior'];
    $options = [
        'none'       => 'Sin enlace',
        'lightbox'   => 'Abrir en lightbox',
        'file'       => 'Archivo de media (URL directa)',
        'attachment' => 'Página de adjunto',
    ];
    echo '<select name="' . BUNNY_GALLERY_OPTION . '[link_behavior]">';
    foreach ( $options as $k => $label ) {
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function bunny_field_target_blank(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['target_blank'] ?? bunny_gallery_hardcoded_defaults()['target_blank'];
    echo '<input type="checkbox"
                 name="' . BUNNY_GALLERY_OPTION . '[target_blank]"
                 value="1"' . checked( (bool) $val, true, false ) . '>';
    echo '<p class="description">Solo aplica para enlaces de tipo "Archivo de media" o "Página de adjunto".</p>';
}

function bunny_field_blur(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['blur'] ?? bunny_gallery_hardcoded_defaults()['blur'];
    echo '<input type="checkbox"
                 name="' . BUNNY_GALLERY_OPTION . '[blur]"
                 value="1"' . checked( (bool) $val, true, false ) . '>';
    echo '<p class="description">Aplica blur a las imágenes NSFW hasta que el visitante confirme su edad.</p>';
}

function bunny_field_blur_intensity(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = isset( $opts['blur_intensity'] ) ? (int) $opts['blur_intensity'] : bunny_gallery_hardcoded_defaults()['blur_intensity'];
    ?>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <input
            type="range" min="0" max="20" step="1"
            name="<?php echo BUNNY_GALLERY_OPTION; ?>[blur_intensity]"
            id="bunny_blur_intensity"
            value="<?php echo esc_attr( $val ); ?>"
            style="width:200px;"
            oninput="
                document.getElementById('bunny_blur_val').textContent = this.value + 'px';
                document.getElementById('bunny_blur_preview').style.filter = 'blur(' + this.value + 'px)';
            "
        >
        <span id="bunny_blur_val" style="font-weight:600;min-width:36px;"><?php echo esc_html( $val ); ?>px</span>
        <img
            id="bunny_blur_preview"
            src="<?php echo esc_url( admin_url( 'images/wordpress-logo.svg' ) ); ?>"
            style="width:80px;height:80px;object-fit:contain;filter:blur(<?php echo esc_attr( $val ); ?>px);background:#f0f0f1;border-radius:6px;padding:8px;transition:filter 0.1s;"
            alt="Preview blur"
        >
    </div>
    <p class="description">Entre 0 y 20px. Se aplica como variable CSS <code>--bunny-blur</code>.</p>
    <?php
}

function bunny_field_nsfw_message(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['nsfw_message'] ?? bunny_gallery_hardcoded_defaults()['nsfw_message'];
    echo '<textarea name="' . BUNNY_GALLERY_OPTION . '[nsfw_message]"
                   rows="2" class="large-text">' . esc_textarea( $val ) . '</textarea>';
    echo '<p class="description">Texto que aparece en el overlay de verificación de edad.</p>';
}

function bunny_field_sfw_title(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['sfw_title'] ?? '';
    echo '<input type="text" class="large-text"
                 name="' . BUNNY_GALLERY_OPTION . '[sfw_title]"
                 value="' . esc_attr( $val ) . '"
                 placeholder="Ej: Galería de imágenes">';
    echo '<p class="description">Título por defecto para galerías SFW. Dejar vacío para no mostrar título.</p>';
}

function bunny_field_nsfw_title(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['nsfw_title'] ?? '';
    echo '<input type="text" class="large-text"
                 name="' . BUNNY_GALLERY_OPTION . '[nsfw_title]"
                 value="' . esc_attr( $val ) . '"
                 placeholder="Ej: Contenido para adultos">';
    echo '<p class="description">Título por defecto para galerías NSFW. Dejar vacío para no mostrar título.</p>';
}

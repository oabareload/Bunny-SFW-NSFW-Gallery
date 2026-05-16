<?php
/**
 * Bunny SFW&NSFW Gallery — Settings globales
 *
 * Registra la página de ajustes en Settings → Bunny SFW&NSFW Gallery.
 * Expone el helper bunny_get_setting() para resolver la cadena:
 *   block attribute → plugin setting → hardcoded fallback
 *
 * @package BunnyNSFW
 * @since   0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Clave única en wp_options donde se guarda el array de defaults.
define( 'BUNNY_GALLERY_OPTION', 'bunny_gallery_defaults' );

// -----------------------------------------------------------------------------
// FALLBACKS HARDCODED
// Último recurso si no hay setting global ni atributo de bloque.
// -----------------------------------------------------------------------------

function bunny_gallery_hardcoded_defaults(): array {
    return [
        'columns'        => 3,
        'blur'           => true,
        'link_behavior'  => 'none',   // 'none' | 'lightbox' | 'file' | 'attachment'
        'target_blank'   => false,
        'nsfw_message'   => 'Este contenido es solo para adultos.',
    ];
}

// -----------------------------------------------------------------------------
// HELPER CENTRAL DE RESOLUCIÓN
//
// Usar en render_callback para cada atributo:
//   bunny_get_setting( $attributes['columns'] ?? null, 'columns' )
//
// Regla:
//   - $block_value !== null && !== ''  →  usar valor del bloque
//   - setting global guardado          →  usar setting
//   - fallback hardcoded               →  último recurso
// -----------------------------------------------------------------------------

function bunny_get_setting( $block_value, string $key ) {
    // 1. Valor propio del bloque (incluye false y 0, excluye null y '').
    if ( ! is_null( $block_value ) && $block_value !== '' ) {
        return $block_value;
    }

    // 2. Setting global guardado en la base de datos.
    $saved = get_option( BUNNY_GALLERY_OPTION, [] );
    if ( array_key_exists( $key, $saved ) && $saved[ $key ] !== '' ) {
        return $saved[ $key ];
    }

    // 3. Fallback hardcoded.
    $defaults = bunny_gallery_hardcoded_defaults();
    return $defaults[ $key ] ?? null;
}

// Devuelve el array resuelto (setting global mergeado con fallback).
// Usado por wp_localize_script para pasar defaults al editor JS.
function bunny_gallery_get_effective_defaults(): array {
    $saved    = get_option( BUNNY_GALLERY_OPTION, [] );
    $fallback = bunny_gallery_hardcoded_defaults();
    return array_merge( $fallback, array_filter( $saved, fn( $v ) => $v !== '' ) );
}

// -----------------------------------------------------------------------------
// REGISTRO DE SETTINGS (Settings API de WordPress)
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

    add_settings_section(
        'bunny_gallery_main',
        '',
        '__return_false',
        'bunny-gallery-settings'
    );

    add_settings_field( 'columns',       'Columnas por defecto',      'bunny_field_columns',       'bunny-gallery-settings', 'bunny_gallery_main' );
    add_settings_field( 'blur',          'Blur NSFW activado',        'bunny_field_blur',          'bunny-gallery-settings', 'bunny_gallery_main' );
    add_settings_field( 'link_behavior', 'Comportamiento de enlace',  'bunny_field_link_behavior', 'bunny-gallery-settings', 'bunny_gallery_main' );
    add_settings_field( 'target_blank',  'Abrir en nueva pestaña',    'bunny_field_target_blank',  'bunny-gallery-settings', 'bunny_gallery_main' );
    add_settings_field( 'nsfw_message',  'Mensaje overlay NSFW',      'bunny_field_nsfw_message',  'bunny-gallery-settings', 'bunny_gallery_main' );
}
add_action( 'admin_init', 'bunny_gallery_register_settings' );

// -----------------------------------------------------------------------------
// SANITIZACIÓN
// -----------------------------------------------------------------------------

function bunny_gallery_sanitize_options( $input ): array {
    $defaults = bunny_gallery_hardcoded_defaults();
    $clean    = [];

    $cols            = absint( $input['columns'] ?? $defaults['columns'] );
    $clean['columns'] = max( 1, min( 6, $cols ) );

    $clean['blur']          = ! empty( $input['blur'] );
    $clean['target_blank']  = ! empty( $input['target_blank'] );

    $allowed_links          = [ 'none', 'lightbox', 'file', 'attachment' ];
    $clean['link_behavior'] = in_array( $input['link_behavior'] ?? '', $allowed_links, true )
                              ? $input['link_behavior']
                              : $defaults['link_behavior'];

    $clean['nsfw_message']  = sanitize_text_field( $input['nsfw_message'] ?? $defaults['nsfw_message'] );

    return $clean;
}

// -----------------------------------------------------------------------------
// PÁGINA DE AJUSTES
// -----------------------------------------------------------------------------

function bunny_gallery_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap">
        <h1>Bunny SFW&amp;NSFW Gallery &mdash; Ajustes globales</h1>
        <p class="description">
            Estos valores se aplican como <strong>defaults</strong> para bloques nuevos.
            Un bloque con valor propio siempre lo prioriza sobre estos ajustes.
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

function bunny_gallery_add_menu(): void {
    add_options_page(
        'Bunny Gallery &mdash; Ajustes',
        'Bunny SFW&amp;NSFW Gallery',
        'manage_options',
        'bunny-gallery-settings',
        'bunny_gallery_settings_page'
    );
}
add_action( 'admin_menu', 'bunny_gallery_add_menu' );

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
    echo '<p class="description">Entre 1 y 6. Los bloques sin valor propio usarán este número.</p>';
}

function bunny_field_blur(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['blur'] ?? bunny_gallery_hardcoded_defaults()['blur'];
    echo '<input type="checkbox"
                 name="' . BUNNY_GALLERY_OPTION . '[blur]"
                 value="1"' . checked( (bool) $val, true, false ) . '>';
    echo '<p class="description">Aplica blur a las imágenes NSFW hasta que el visitante confirme su edad.</p>';
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
        echo '<option value="' . esc_attr( $k ) . '"' . selected( $current, $k, false ) . '>'
             . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function bunny_field_target_blank(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['target_blank'] ?? bunny_gallery_hardcoded_defaults()['target_blank'];
    echo '<input type="checkbox"
                 name="' . BUNNY_GALLERY_OPTION . '[target_blank]"
                 value="1"' . checked( (bool) $val, true, false ) . '>';
    echo '<p class="description">Solo aplica cuando el enlace es "Archivo de media" o "Página de adjunto".</p>';
}

function bunny_field_nsfw_message(): void {
    $opts = get_option( BUNNY_GALLERY_OPTION, [] );
    $val  = $opts['nsfw_message'] ?? bunny_gallery_hardcoded_defaults()['nsfw_message'];
    echo '<textarea name="' . BUNNY_GALLERY_OPTION . '[nsfw_message]"
                   rows="2" class="large-text">'
         . esc_textarea( $val ) . '</textarea>';
    echo '<p class="description">Texto que aparece en el overlay de verificación de edad.</p>';
}

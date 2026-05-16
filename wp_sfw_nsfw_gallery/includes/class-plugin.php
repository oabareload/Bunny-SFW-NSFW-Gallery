<?php
/**
 * Bunny SFW&NSFW Gallery — Plugin core
 *
 * Registra el bloque, los assets y el render_callback.
 * Usa bunny_get_setting() para resolver la cadena:
 *   block attribute → plugin setting → hardcoded fallback
 *
 * @package BunnyNSFW
 * @since   0.0.1
 */

namespace BunnyNSFW;

if ( ! defined( 'ABSPATH' ) ) exit;

class Plugin {

    private static $instance = null;

    // Contador estático para generar IDs de galería únicos por request.
    private static int $gallery_count = 0;

    public static function get_instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_block' ] );
    }

    // -------------------------------------------------------------------------
    // REGISTRO DEL BLOQUE Y ASSETS
    // -------------------------------------------------------------------------

    public function register_block(): void {
        $this->register_assets();

        register_block_type( 'bunny/nsfw-gallery', [
            'editor_script'   => 'bunny-nsfw-block-editor',
            'script'          => 'bunny-nsfw-block-frontend',
            'style'           => 'bunny-nsfw-block-style',
            'render_callback' => [ $this, 'render_block' ],
        ] );
    }

    private function register_assets(): void {
        // --- Editor ---
        wp_register_script(
            'bunny-nsfw-block-editor',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/nsfw-gallery/block.js',
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-editor' ],
            BUNNY_NSWF_VERSION,
            false
        );

        // Pasar defaults efectivos al editor para pre-rellenar atributos nuevos.
        wp_localize_script(
            'bunny-nsfw-block-editor',
            'bunnyGalleryDefaults',
            bunny_gallery_get_effective_defaults()
        );

        // --- Frontend ---
        wp_register_script(
            'bunny-nsfw-block-frontend',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/nsfw-gallery/frontend.js',
            [],
            BUNNY_NSWF_VERSION,
            true   // cargar en el footer
        );

        // --- Estilos ---
        wp_register_style(
            'bunny-nsfw-block-style',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/nsfw-gallery/style.css',
            [],
            BUNNY_NSWF_VERSION
        );
    }

    // -------------------------------------------------------------------------
    // RENDER CALLBACK
    //
    // Cada atributo se resuelve con bunny_get_setting():
    //   valor del bloque  →  setting global  →  fallback hardcoded
    //
    // data-gallery-id garantiza que el lightbox JS quede completamente
    // aislado por instancia, incluso con múltiples galerías en la misma página.
    // -------------------------------------------------------------------------

    public function render_block( array $attributes ): string {
        self::$gallery_count++;
        $gallery_id = 'bunny-gallery-' . self::$gallery_count;

        // Resolución de atributos —————————————————————————————————————————————

        $images        = $attributes['images']      ?? [];
        $mode          = $attributes['mode']         ?? 'sfw';

        // null como centinela: "el bloque no tiene valor propio guardado todavía"
        $columns       = \bunny_get_setting( $attributes['columns']      ?? null, 'columns' );
        $blur          = \bunny_get_setting( $attributes['blur']         ?? null, 'blur' );
        $link_behavior = \bunny_get_setting( $attributes['linkTo']       ?? null, 'link_behavior' );
        $target_blank  = \bunny_get_setting( $attributes['targetBlank']  ?? null, 'target_blank' );
        $nsfw_message  = \bunny_get_setting( $attributes['message']      ?? null, 'nsfw_message' );

        $target  = $target_blank ? '_blank' : '_self';
        $columns = max( 1, min( 6, (int) $columns ) );

        // Generación del HTML —————————————————————————————————————————————————

        ob_start(); ?>
        <div
            class="bunny-gallery-wrapper"
            data-gallery-id="<?php echo esc_attr( $gallery_id ); ?>"
            data-mode="<?php echo esc_attr( $mode ); ?>"
            data-blur="<?php echo $blur ? '1' : '0'; ?>"
            data-link="<?php echo esc_attr( $link_behavior ); ?>"
            data-message="<?php echo esc_attr( $nsfw_message ); ?>"
            style="--bunny-cols: <?php echo $columns; ?>;"
        >
            <div class="bunny-gallery"
                 style="grid-template-columns: repeat(<?php echo $columns; ?>, 1fr);">

                <?php foreach ( $images as $id ) :
                    $url  = wp_get_attachment_image_url( $id, 'large' );
                    $full = wp_get_attachment_url( $id );
                    $alt  = get_post_meta( $id, '_wp_attachment_image_alt', true );

                    if ( ! $url ) continue;
                ?>

                    <div
                        class="bunny-gallery-item"
                        data-full="<?php echo esc_url( $full ); ?>"
                        data-alt="<?php echo esc_attr( $alt ); ?>"
                    >
                        <?php if ( in_array( $link_behavior, [ 'file', 'attachment' ], true ) ) :
                            $href = $link_behavior === 'file'
                                ? $full
                                : get_attachment_link( $id );
                        ?>
                            <a href="<?php echo esc_url( $href ); ?>"
                               target="<?php echo esc_attr( $target ); ?>"
                               rel="<?php echo $target_blank ? 'noopener noreferrer' : ''; ?>">
                                <img src="<?php echo esc_url( $url ); ?>"
                                     alt="<?php echo esc_attr( $alt ); ?>"
                                     loading="lazy" />
                            </a>
                        <?php else : ?>
                            <img src="<?php echo esc_url( $url ); ?>"
                                 alt="<?php echo esc_attr( $alt ); ?>"
                                 loading="lazy" />
                        <?php endif; ?>
                    </div>

                <?php endforeach; ?>

            </div>
            <?php // El overlay NSFW y el lightbox los inyecta frontend.js. ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

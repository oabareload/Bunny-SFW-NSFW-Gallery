<?php
/**
 * Bunny SFW&NSFW Gallery — Plugin core
 *
 * @package BunnyNSFW
 * @since   0.0.1
 */

namespace BunnyNSFW;

if ( ! defined( 'ABSPATH' ) ) exit;

class Plugin {

    private static $instance = null;
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

    public function register_block(): void {
        $this->register_assets();
        register_block_type( 'bunny/nsfw-gallery', [
            'editor_script'   => 'bunny-nsfw-block-editor',
            'script'          => 'bunny-nsfw-block-frontend',
            'style'           => 'bunny-nsfw-block-style',
            'render_callback' => [ $this, 'render_block' ],
        ] );
        register_block_type( 'bunny/content-section', [
            'editor_script'   => 'bunny-content-section-editor',
            'script'          => 'bunny-content-section-frontend',
            'style'           => 'bunny-content-section-style',
            'render_callback' => [ $this, 'render_content_section' ],
        ] );
    }

    private function register_assets(): void {
        wp_register_script(
            'bunny-nsfw-block-editor',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/nsfw-gallery/block.js',
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
            BUNNY_NSWF_VERSION,
            false
        );
        wp_localize_script( 'bunny-nsfw-block-editor', 'bunnyGalleryDefaults', bunny_gallery_get_effective_defaults() );

        wp_register_script(
            'bunny-nsfw-block-frontend',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/nsfw-gallery/frontend.js',
            [],
            BUNNY_NSWF_VERSION,
            true
        );

        // Exponer settings del lightbox al frontend JS
        // Nota: wp_localize_script serializa booleanos como "" / "1".
        // Usamos strings explícitos que el JS puede comparar sin ambigüedad.
        $d = bunny_gallery_get_effective_defaults();
        wp_localize_script( 'bunny-nsfw-block-frontend', 'bunnyGalleryLightbox', [
            'show_lightbox_thumbnails' => $d['show_lightbox_thumbnails'] ? '1' : '0',
            'lightbox_theme'           => $d['lightbox_theme'],
            'lightbox_accent_color'    => $d['lightbox_accent_color'],
            'lightbox_caption_fields'  => (array) $d['lightbox_caption_fields'],
            'lightbox_caption_mode'    => $d['lightbox_caption_mode'],
        ] );
        wp_register_style(
            'bunny-nsfw-block-style',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/nsfw-gallery/style.css',
            [],
            BUNNY_NSWF_VERSION
        );

        // Content Section block
        wp_register_script(
            'bunny-content-section-editor',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/content-section/block.js',
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
            BUNNY_NSWF_VERSION,
            false
        );
        wp_register_script(
            'bunny-content-section-frontend',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/content-section/frontend.js',
            [],
            BUNNY_NSWF_VERSION,
            true
        );
        wp_register_style(
            'bunny-content-section-style',
            BUNNY_NSWF_PLUGIN_URL . 'blocks/content-section/style.css',
            [],
            BUNNY_NSWF_VERSION
        );
    }

    public function render_block( array $attributes ): string {
        self::$gallery_count++;
        $gallery_id = 'bunny-gallery-' . self::$gallery_count;

        $images        = $attributes['images']   ?? [];
        $mode          = $attributes['mode']      ?? 'sfw';

        $columns            = \bunny_get_setting( $attributes['columns']      ?? null, 'columns' );
        $blur               = \bunny_get_setting( $attributes['blur']         ?? null, 'blur' );
        $blur_intensity     = \bunny_get_setting( $attributes['blurIntensity'] ?? null, 'blur_intensity' );
        $link_behavior      = \bunny_get_setting( $attributes['linkTo']       ?? null, 'link_behavior' );
        $target_blank       = \bunny_get_setting( $attributes['targetBlank']  ?? null, 'target_blank' );
        $nsfw_message       = \bunny_get_setting( $attributes['message']      ?? null, 'nsfw_message' );
        $image_size         = \bunny_get_setting( $attributes['imageSize']    ?? null, 'image_size' );
        $aspect_ratio       = \bunny_get_setting( $attributes['aspectRatio']  ?? null, 'aspect_ratio' );
        $sfw_title          = \bunny_get_setting( $attributes['sfwTitle']     ?? null, 'sfw_title' );
        $nsfw_title         = \bunny_get_setting( $attributes['nsfwTitle']    ?? null, 'nsfw_title' );
        $show_title         = $attributes['showTitle'] ?? true;
        $nsfw_display_style = \bunny_get_setting( null, 'nsfw_display_style' ); // solo global
        $unlock_button_text = \bunny_get_setting( null, 'unlock_button_text' ); // solo global

        $columns        = max( 1, min( 12, (int) $columns ) );
        $blur_intensity = max( 0, min( 20, (int) $blur_intensity ) );
        $target         = $target_blank ? '_blank' : '_self';
        $title          = $mode === 'nsfw' ? $nsfw_title : $sfw_title;

        $ratio_map = [ 'square' => '1 / 1', 'portrait' => '2 / 3', 'landscape' => '16 / 9', 'original' => 'auto' ];
        $css_ratio = $ratio_map[ $aspect_ratio ] ?? '1 / 1';

        ob_start(); ?>
        <div
            class="bunny-gallery-wrapper"
            data-gallery-id="<?php echo esc_attr( $gallery_id ); ?>"
            data-mode="<?php echo esc_attr( $mode ); ?>"
            data-blur="<?php echo $blur ? '1' : '0'; ?>"
            data-link="<?php echo esc_attr( $link_behavior ); ?>"
            data-message="<?php echo esc_attr( $nsfw_message ); ?>"
            data-display-style="<?php echo esc_attr( $nsfw_display_style ); ?>"
            data-unlock-text="<?php echo esc_attr( $unlock_button_text ); ?>"
            style="--bunny-cols:<?php echo $columns; ?>;--bunny-blur:<?php echo $blur_intensity; ?>px;--bunny-ratio:<?php echo esc_attr( $css_ratio ); ?>;"
        >
            <?php if ( $show_title && ! empty( $title ) ) : ?>
            <h2 class="bunny-gallery-title bunny-gallery-title--<?php echo esc_attr( $mode ); ?>">
                <?php echo esc_html( $title ); ?>
            </h2>
            <?php endif; ?>

            <div class="bunny-gallery" style="grid-template-columns: repeat(<?php echo $columns; ?>, 1fr);">
                <?php foreach ( $images as $id ) :
                    $url  = wp_get_attachment_image_url( $id, $image_size );
                    $full = wp_get_attachment_url( $id );
                    $alt  = get_post_meta( $id, '_wp_attachment_image_alt', true );
                    if ( ! $url ) continue;
                ?>
                    <div class="bunny-gallery-item"
                         data-full="<?php echo esc_url( $full ); ?>"
                         data-thumb="<?php echo esc_url( wp_get_attachment_image_url( $id, 'thumbnail' ) ?: $url ); ?>"
                         data-alt="<?php echo esc_attr( $alt ); ?>"
                         data-title="<?php echo esc_attr( get_the_title( $id ) ); ?>"
                         data-caption="<?php
                             $attachment = get_post( $id );
                             echo $attachment ? esc_attr( $attachment->post_excerpt ) : '';
                         ?>"
                         data-description="<?php
                             $attachment = get_post( $id );
                             echo $attachment ? esc_attr( $attachment->post_content ) : '';
                         ?>"
                    >
                        <?php if ( in_array( $link_behavior, [ 'file', 'attachment' ], true ) ) :
                            $href = $link_behavior === 'file' ? $full : get_attachment_link( $id );
                        ?>
                            <a href="<?php echo esc_url( $href ); ?>" target="<?php echo esc_attr( $target ); ?>" rel="<?php echo $target_blank ? 'noopener noreferrer' : ''; ?>">
                                <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
                            </a>
                        <?php else : ?>
                            <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_content_section( array $attributes ): string {
        $image_id      = $attributes['imageId']      ?? 0;
        $image_size    = $attributes['imageSize']    ?? 'large';
        $image_height  = $attributes['imageHeight']  ?? 'medium';
        $image_width   = $attributes['imageWidth']   ?? '33';
        $image_pos     = $attributes['imagePosition'] ?? 'left';
        $title         = $attributes['title']        ?? '';
        $content       = $attributes['content']      ?? '';
        $show_title    = $attributes['showTitle']    ?? true;
        $lightbox      = $attributes['lightbox']     ?? true;

        $height_map = [ 'small' => '200px', 'medium' => '320px', 'large' => '480px' ];
        $img_height = $height_map[ $image_height ] ?? '320px';

        $allowed_widths = [ '25', '33', '40', '50' ];
        $img_width = in_array( (string) $image_width, $allowed_widths, true ) ? (string) $image_width : '33';

        $img_url  = $image_id ? wp_get_attachment_image_url( $image_id, $image_size ) : '';
        $img_full = $image_id ? wp_get_attachment_url( $image_id ) : '';
        $img_alt  = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';

        ob_start(); ?>
        <div class="bunny-content-section bunny-content-section--<?php echo esc_attr( $image_pos ); ?>"
             style="--bunny-cs-img-height:<?php echo esc_attr( $img_height ); ?>;--bunny-cs-img-width:<?php echo esc_attr( $img_width ); ?>"
        >
            <?php if ( $img_url ) : ?>
            <div class="bunny-cs-image-wrap"
                 <?php if ( $lightbox && $img_full ) : ?>
                     data-lightbox="1"
                     data-full="<?php echo esc_url( $img_full ); ?>"
                 <?php endif; ?>
            >
                <img
                    src="<?php echo esc_url( $img_url ); ?>"
                    alt="<?php echo esc_attr( $img_alt ); ?>"
                    class="bunny-cs-image<?php echo ( $lightbox && $img_full ) ? ' bunny-cs-image--lightbox' : ''; ?>"
                    loading="lazy"
                />
            </div>
            <?php endif; ?>
            <div class="bunny-cs-text">
                <?php if ( $show_title && ! empty( $title ) ) : ?>
                <h2 class="bunny-cs-title"><?php echo esc_html( $title ); ?></h2>
                <?php endif; ?>
                <?php if ( ! empty( $content ) ) : ?>
                <div class="bunny-cs-content"><?php echo wp_kses_post( $content ); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

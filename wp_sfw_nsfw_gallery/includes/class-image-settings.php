<?php
/**
 * Bunny SFW&NSFW Gallery — Image Settings
 *
 * Handles upload-time image constraints previously managed via external snippets:
 * - Max resize on upload
 * - Big image threshold
 * - Intermediate sizes control
 *
 * All hooks fire at priority 20 so they run AFTER Image_Normalizer (priority 10).
 *
 * @package BunnyNSFW
 * @since   0.6.2
 */

namespace BunnyNSFW;

if ( ! defined( 'ABSPATH' ) ) exit;

class Image_Settings {

    const OPTION = 'bunny_image_settings';

    public static function defaults(): array {
        return [
            // Resize limit
            'resize_enabled'   => false,
            'resize_max_width'  => 1920,
            'resize_max_height' => 1920,
            // Big image threshold
            'big_image_enabled'   => false,
            'big_image_threshold' => 1920,
            // Intermediate sizes to DISABLE (checked = disabled)
            'disabled_sizes' => [],
            // Automation snippets (disabled by default)
            'upload_rename_enabled' => false,
            'auto_alt_enabled'      => false,
        ];
    }

    public static function init(): void {
        $opts = get_option( self::OPTION, [] );
        $d    = self::defaults();

        // ------------------------------------------------------------------
        // Resize limit — priority 20, runs after Image_Normalizer (priority 10)
        // ------------------------------------------------------------------
        if ( ! empty( $opts['resize_enabled'] ) ) {
            $max_w = (int) ( $opts['resize_max_width']  ?? $d['resize_max_width'] );
            $max_h = (int) ( $opts['resize_max_height'] ?? $d['resize_max_height'] );
            $max_w = max( 1, $max_w );
            $max_h = max( 1, $max_h );

            add_filter( 'wp_handle_upload', static function ( array $file ) use ( $max_w, $max_h ): array {
                $mime = $file['type'] ?? ( function_exists( 'mime_content_type' ) ? mime_content_type( $file['file'] ) : '' );
                if ( strpos( $mime, 'image' ) === false ) {
                    return $file;
                }

                $size = @getimagesize( $file['file'] );
                if ( ! $size ) {
                    return $file;
                }

                if ( $size[0] <= $max_w && $size[1] <= $max_h ) {
                    return $file;
                }

                $editor = wp_get_image_editor( $file['file'] );
                if ( is_wp_error( $editor ) ) {
                    return $file;
                }

                $editor->resize( $max_w, $max_h, false );
                $editor->save( $file['file'] );

                return $file;
            }, 20, 1 );
        }

        // ------------------------------------------------------------------
        // Big image threshold
        // ------------------------------------------------------------------
        if ( ! empty( $opts['big_image_enabled'] ) ) {
            $threshold = (int) ( $opts['big_image_threshold'] ?? $d['big_image_threshold'] );
            $threshold = max( 1, $threshold );

            add_filter( 'big_image_size_threshold', static function () use ( $threshold ): int {
                return $threshold;
            } );
        }

        // ------------------------------------------------------------------
        // Intermediate sizes — disable checked sizes
        // ------------------------------------------------------------------
        $disabled = $opts['disabled_sizes'] ?? [];
        if ( ! empty( $disabled ) && is_array( $disabled ) ) {
            add_filter( 'intermediate_image_sizes_advanced', static function ( array $sizes ) use ( $disabled ): array {
                foreach ( $disabled as $size ) {
                    unset( $sizes[ $size ] );
                }
                return $sizes;
            } );
        }

        // ------------------------------------------------------------------
        // Optional: safe rename uploaded files (upload filename sanitizer)
        // ------------------------------------------------------------------
        if ( ! empty( $opts['upload_rename_enabled'] ) ) {
            add_filter( 'wp_handle_upload_prefilter', static function ( $file ) {
                $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );

                if ( ! in_array( strtolower( $ext ), [ 'jpg', 'jpeg', 'png', 'webp' ], true ) ) {
                    return $file;
                }

                $post_id = $_POST['post_id'] ?? $_REQUEST['post_id'] ?? 0;

                if ( ! $post_id && isset( $GLOBALS['post']->ID ) ) {
                    $post_id = $GLOBALS['post']->ID;
                }

                if ( ! $post_id ) {
                    $base = sanitize_title( pathinfo( $file['name'], PATHINFO_FILENAME ) );
                    $file['name'] = $base . '-' . time() . '.' . $ext;
                    return $file;
                }

                $slug = get_post_field( 'post_name', $post_id );

                if ( ! $slug ) {
                    $post_title = get_the_title( $post_id );

                    if ( $post_title ) {
                        $slug = sanitize_title( $post_title );
                    } else {
                        $slug = 'bunnychase-image';
                    }
                }

                $unique = uniqid();

                $file['name'] = $slug . '-' . $unique . '.' . $ext;

                return $file;
            } );
        }

        // ------------------------------------------------------------------
        // Optional: auto-fill attachment ALT from parent post title
        // ------------------------------------------------------------------
        if ( ! empty( $opts['auto_alt_enabled'] ) ) {
            add_action( 'add_attachment', static function ( $attachment_id ) {
                $post_id = $_POST['post_id'] ?? $_REQUEST['post_id'] ?? 0;

                if ( ! $post_id && ! empty( $GLOBALS['post']->ID ) ) {
                    $post_id = $GLOBALS['post']->ID;
                }

                if ( ! $post_id ) return;

                $title = get_the_title( $post_id );

                if ( ! $title ) return;

                $alt = sanitize_text_field( $title ) . ' - BunnyChase';

                update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
            }, 10, 1 );
        }
    }
}

Image_Settings::init();

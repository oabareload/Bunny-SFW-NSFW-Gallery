<?php
/**
 * Bunny SFW&NSFW Gallery — Image Normalizer
 *
 * Intercepts uploaded images via wp_handle_upload (before WordPress generates
 * thumbnails) and normalizes them to a target aspect ratio using the method
 * configured in settings: pad, crop, or smart crop.
 *
 * @package BunnyNSFW
 * @since   0.6.0
 */

namespace BunnyNSFW;

if ( ! defined( 'ABSPATH' ) ) exit;

class Image_Normalizer {

    /**
     * Supported MIME types for normalization.
     */
    const SUPPORTED_TYPES = [ 'image/jpeg', 'image/png', 'image/webp' ];

    /**
     * Target ratios: key => width/height value.
     */
    const TARGET_RATIOS = [
        '1:1'    => 1.0,
        '4:5'    => 0.8,
        '1.91:1' => 1.91,
    ];

    /**
     * Registers the wp_handle_upload filter.
     * This fires after the file is moved to uploads/ but BEFORE
     * wp_generate_attachment_metadata, so all thumbnails are derived
     * from the already-normalized image.
     */
    public static function init(): void {
        add_filter( 'wp_handle_upload', [ self::class, 'maybe_normalize' ], 10, 2 );
    }

    /**
     * Entry point called by WordPress after every upload.
     *
     * @param array  $upload  { file: string, url: string, type: string }
     * @param string $context 'upload' | 'sideload'
     * @return array Unmodified array — normalization overwrites the file on disk.
     */
    public static function maybe_normalize( array $upload, string $context = 'upload' ): array {
        $opts = get_option( 'bunny_normalization_settings', [] );

        if ( empty( $opts['enabled'] ) ) {
            return $upload;
        }

        $mime = $upload['type'] ?? '';
        if ( ! in_array( $mime, self::SUPPORTED_TYPES, true ) ) {
            return $upload;
        }

        $file = $upload['file'] ?? '';
        if ( ! $file || ! is_readable( $file ) ) {
            return $upload;
        }

        $ratio_mode = $opts['ratio_mode'] ?? 'auto';
        $method     = $opts['method']     ?? 'pad';
        $bg_color   = $opts['bg_color']   ?? 'white';
        $tolerance  = (float) ( $opts['tolerance'] ?? 0 );
        $keep_orig  = ! empty( $opts['keep_original'] );

        // Determine target ratio. Returns null when already within tolerance.
        $target_ratio = self::resolve_target_ratio( $file, $ratio_mode, $tolerance );
        if ( $target_ratio === null ) {
            return $upload;
        }

        // Save original backup before any modification.
        if ( $keep_orig ) {
            self::save_original_backup( $file );
        }

        // Normalize in place — overwrites the uploaded file on disk.
        self::normalize_image( $file, $mime, $target_ratio, $method, $bg_color );

        return $upload;
    }

    /**
     * Determines the target aspect ratio for this image.
     * Returns null if normalization should be skipped (within tolerance).
     *
     * @param string $file        Absolute path to the image.
     * @param string $ratio_mode  'auto' | '1:1' | '4:5' | '1.91:1'
     * @param float  $tolerance   Max allowed delta (0 = always normalize).
     * @return float|null Target ratio (width/height), or null to skip.
     */
    private static function resolve_target_ratio( string $file, string $ratio_mode, float $tolerance ): ?float {
        $size = @getimagesize( $file );
        if ( ! $size || ! $size[0] || ! $size[1] ) {
            return null;
        }

        $actual_ratio = $size[0] / $size[1];

        if ( $ratio_mode !== 'auto' && isset( self::TARGET_RATIOS[ $ratio_mode ] ) ) {
            $target = self::TARGET_RATIOS[ $ratio_mode ];
            $delta  = abs( $actual_ratio - $target );
            // Already at target (float-safe) or within tolerance.
            if ( $delta < 0.0001 ) {
                return null;
            }
            if ( $tolerance > 0.0 && $delta <= $tolerance ) {
                return null;
            }
            return $target;
        }

        // Auto mode: pick the closest ratio.
        $best_key   = null;
        $best_delta = PHP_FLOAT_MAX;
        foreach ( self::TARGET_RATIOS as $key => $ratio ) {
            $delta = abs( $actual_ratio - $ratio );
            if ( $delta < $best_delta ) {
                $best_delta = $delta;
                $best_key   = $key;
            }
        }

        if ( $best_key === null ) {
            return null;
        }

        $target = self::TARGET_RATIOS[ $best_key ];

        if ( $best_delta < 0.0001 ) {
            return null; // Already exact.
        }
        if ( $tolerance > 0.0 && $best_delta <= $tolerance ) {
            return null; // Within tolerance.
        }

        return $target;
    }

    /**
     * Copies the file to a _original backup in the same directory.
     * Example: photo.jpg → photo_original.jpg
     * Does not overwrite an existing backup.
     *
     * @param string $file Absolute path to the uploaded image.
     */
    private static function save_original_backup( string $file ): void {
        $dir  = dirname( $file );
        $base = pathinfo( $file, PATHINFO_FILENAME );
        $ext  = pathinfo( $file, PATHINFO_EXTENSION );
        $dest = $dir . DIRECTORY_SEPARATOR . $base . '_original.' . $ext;

        if ( ! file_exists( $dest ) ) {
            copy( $file, $dest );
        }
    }

    /**
     * Loads, normalizes, and saves the image using GD.
     *
     * @param string $file         Absolute path.
     * @param string $mime         MIME type.
     * @param float  $target_ratio Width/height target.
     * @param string $method       'pad' | 'crop' | 'smart_crop'
     * @param string $bg_color     'white' | 'black' | 'transparent'
     */
    private static function normalize_image(
        string $file,
        string $mime,
        float  $target_ratio,
        string $method,
        string $bg_color
    ): void {
        $src = self::load_image( $file, $mime );
        if ( $src === null ) {
            return;
        }

        $orig_w = imagesx( $src );
        $orig_h = imagesy( $src );

        if ( ! $orig_w || ! $orig_h ) {
            imagedestroy( $src );
            return;
        }

        switch ( $method ) {
            case 'crop':
                $result = self::apply_crop( $src, $orig_w, $orig_h, $target_ratio );
                break;
            case 'smart_crop':
                $result = self::apply_smart_crop( $src, $orig_w, $orig_h, $target_ratio );
                break;
            default: // pad
                $result = self::apply_pad( $src, $orig_w, $orig_h, $target_ratio, $bg_color, $mime );
                break;
        }

        imagedestroy( $src );

        if ( $result === null ) {
            return;
        }

        self::save_image( $result, $file, $mime );
        imagedestroy( $result );
    }

    /**
     * Loads a GD image resource from disk.
     * Corrects EXIF orientation for JPEG files.
     *
     * @return \GdImage|null
     */
    private static function load_image( string $file, string $mime ): ?\GdImage {
        switch ( $mime ) {
            case 'image/jpeg':
                $img = @imagecreatefromjpeg( $file );
                break;
            case 'image/png':
                $img = @imagecreatefrompng( $file );
                break;
            case 'image/webp':
                $img = @imagecreatefromwebp( $file );
                break;
            default:
                return null;
        }

        if ( ! ( $img instanceof \GdImage ) ) {
            return null;
        }

        // Correct EXIF orientation so we work on visually-correct pixels.
        if ( $mime === 'image/jpeg' && function_exists( 'exif_read_data' ) ) {
            $exif = @exif_read_data( $file ) ?: [];
            $img  = self::correct_orientation( $img, (int) ( $exif['Orientation'] ?? 1 ) );
        }

        return $img;
    }

    /**
     * Rotates the GD resource to match the EXIF orientation tag.
     *
     * @param \GdImage $img
     * @param int      $orientation EXIF Orientation value (1–8).
     * @return \GdImage
     */
    private static function correct_orientation( \GdImage $img, int $orientation ): \GdImage {
        switch ( $orientation ) {
            case 3:
                $img = imagerotate( $img, 180, 0 );
                break;
            case 6:
                $img = imagerotate( $img, -90, 0 );
                break;
            case 8:
                $img = imagerotate( $img, 90, 0 );
                break;
        }
        return $img;
    }

    /**
     * PAD: Expands the canvas to the target ratio.
     * The original image is centered; empty space is filled with the background color.
     * Never removes pixels.
     *
     * @param \GdImage $src
     * @return \GdImage|null
     */
    private static function apply_pad(
        \GdImage $src,
        int      $orig_w,
        int      $orig_h,
        float    $target_ratio,
        string   $bg_color,
        string   $mime
    ): ?\GdImage {
        $actual_ratio = $orig_w / $orig_h;

        if ( $actual_ratio > $target_ratio ) {
            // Image is wider than target: expand height.
            $new_w = $orig_w;
            $new_h = (int) round( $orig_w / $target_ratio );
        } else {
            // Image is taller than target: expand width.
            $new_h = $orig_h;
            $new_w = (int) round( $orig_h * $target_ratio );
        }

        $canvas = imagecreatetruecolor( $new_w, $new_h );
        if ( ! ( $canvas instanceof \GdImage ) ) {
            return null;
        }

        $supports_alpha = in_array( $mime, [ 'image/png', 'image/webp' ], true );

        if ( $supports_alpha && $bg_color === 'transparent' ) {
            imagealphablending( $canvas, false );
            imagesavealpha( $canvas, true );
            $fill = imagecolorallocatealpha( $canvas, 0, 0, 0, 127 );
        } else {
            imagealphablending( $canvas, true );
            $fill = ( $bg_color === 'black' )
                ? imagecolorallocate( $canvas, 0, 0, 0 )
                : imagecolorallocate( $canvas, 255, 255, 255 );
        }

        imagefilledrectangle( $canvas, 0, 0, $new_w - 1, $new_h - 1, $fill );

        // Center the source image on the canvas.
        $dst_x = (int) round( ( $new_w - $orig_w ) / 2 );
        $dst_y = (int) round( ( $new_h - $orig_h ) / 2 );
        imagecopy( $canvas, $src, $dst_x, $dst_y, 0, 0, $orig_w, $orig_h );

        return $canvas;
    }

    /**
     * CROP: Geometric center crop.
     * Removes pixels symmetrically from the longer dimension.
     *
     * @param \GdImage $src
     * @return \GdImage|null
     */
    private static function apply_crop(
        \GdImage $src,
        int      $orig_w,
        int      $orig_h,
        float    $target_ratio
    ): ?\GdImage {
        $actual_ratio = $orig_w / $orig_h;

        if ( $actual_ratio > $target_ratio ) {
            // Wider than target: crop left and right.
            $crop_h = $orig_h;
            $crop_w = (int) round( $orig_h * $target_ratio );
        } else {
            // Taller than target: crop top and bottom.
            $crop_w = $orig_w;
            $crop_h = (int) round( $orig_w / $target_ratio );
        }

        $src_x = (int) round( ( $orig_w - $crop_w ) / 2 );
        $src_y = (int) round( ( $orig_h - $crop_h ) / 2 );

        $canvas = imagecreatetruecolor( $crop_w, $crop_h );
        if ( ! ( $canvas instanceof \GdImage ) ) {
            return null;
        }

        imagecopy( $canvas, $src, 0, 0, $src_x, $src_y, $crop_w, $crop_h );

        return $canvas;
    }

    /**
     * SMART CROP: Center-biased crop (no AI, no external APIs).
     * The site's content is centered renders and figures, so the center
     * of the image is the region of interest. Delegates to apply_crop.
     *
     * @param \GdImage $src
     * @return \GdImage|null
     */
    private static function apply_smart_crop(
        \GdImage $src,
        int      $orig_w,
        int      $orig_h,
        float    $target_ratio
    ): ?\GdImage {
        return self::apply_crop( $src, $orig_w, $orig_h, $target_ratio );
    }

    /**
     * Saves a GD resource back to disk in the original format.
     * No format conversion — WebP stays WebP, JPEG stays JPEG, PNG stays PNG.
     *
     * @param \GdImage $img
     * @param string   $file Absolute path to overwrite.
     * @param string   $mime MIME type.
     */
    private static function save_image( \GdImage $img, string $file, string $mime ): void {
        switch ( $mime ) {
            case 'image/jpeg':
                imagejpeg( $img, $file, 92 );
                break;
            case 'image/png':
                imagesavealpha( $img, true );
                imagepng( $img, $file, 9 );
                break;
            case 'image/webp':
                imagewebp( $img, $file, 92 );
                break;
        }
    }
}

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

        $ratio_mode    = $opts['ratio_mode']    ?? 'auto';
        $method        = $opts['method']        ?? 'pad';
        $fill_mode     = $opts['fill_mode']     ?? 'solid_color';
        $bg_color      = $opts['bg_color']      ?? 'white';
        $sample_corner = $opts['sample_corner'] ?? 'top_left';
        $tolerance     = (float) ( $opts['tolerance'] ?? 0 );
        $keep_orig     = ! empty( $opts['keep_original'] );
        $debug_enabled = ! empty( $opts['debug_enabled'] );

        // Extract original image info for debug/processing
        $orig_size  = @getimagesize( $file );
        $orig_w     = $orig_size ? (int) $orig_size[0] : 0;
        $orig_h     = $orig_size ? (int) $orig_size[1] : 0;
        $orig_ratio = $orig_h > 0 ? (float) ( $orig_w / $orig_h ) : 0.0;

        // Determine target ratio. Returns null when already within tolerance.
        $target_ratio = self::resolve_target_ratio( $file, $ratio_mode, $tolerance );

        // Compute diagnostics info if debug is enabled
        $executed_method = 'none (skipped - within tolerance)';
        $target_canvas_w = $orig_w;
        $target_canvas_h = $orig_h;

        if ( $target_ratio !== null ) {
            if ( $method === 'crop' || $method === 'smart_crop' ) {
                $executed_method = ( $method === 'smart_crop' ) ? 'apply_smart_crop()' : 'apply_crop()';
                if ( $orig_ratio > $target_ratio ) {
                    $target_canvas_h = $orig_h;
                    $target_canvas_w = (int) round( $orig_h * $target_ratio );
                } else {
                    $target_canvas_w = $orig_w;
                    $target_canvas_h = (int) round( $orig_w / $target_ratio );
                }
            } else {
                $executed_method = 'apply_pad()';
                if ( $orig_ratio > $target_ratio ) {
                    $target_canvas_w = $orig_w;
                    $target_canvas_h = (int) round( $orig_w / $target_ratio );
                } else {
                    $target_canvas_h = $orig_h;
                    $target_canvas_w = (int) round( $orig_h * $target_ratio );
                }
            }
        }

        if ( $target_ratio === null ) {
            if ( $debug_enabled ) {
                $log_entry = sprintf(
                    "File: %s | Orig: %dx%d (Ratio: %.4f) | Mode: %s | Selected Ratio: none (skipped) | Method: %s | Executed: %s | Fill Mode: %s | Target: %dx%d | Saved: %dx%d",
                    basename( $file ),
                    $orig_w,
                    $orig_h,
                    $orig_ratio,
                    $ratio_mode,
                    $method,
                    $executed_method,
                    $fill_mode,
                    $target_canvas_w,
                    $target_canvas_h,
                    $orig_w,
                    $orig_h
                );
                self::log( $log_entry );
            }
            return $upload;
        }

        // Save original backup before any modification.
        if ( $keep_orig ) {
            self::save_original_backup( $file );
        }

        // Normalize in place — overwrites the uploaded file on disk.
        self::normalize_image( $file, $mime, $target_ratio, $method, $fill_mode, $bg_color, $sample_corner );

        // Extract saved dimensions
        $saved_size = @getimagesize( $file );
        $saved_w    = $saved_size ? (int) $saved_size[0] : 0;
        $saved_h    = $saved_size ? (int) $saved_size[1] : 0;

        if ( $debug_enabled ) {
            $log_entry = sprintf(
                "File: %s | Orig: %dx%d (Ratio: %.4f) | Mode: %s | Selected Ratio: %.4f | Method: %s | Executed: %s | Fill Mode: %s | Target: %dx%d | Saved: %dx%d",
                basename( $file ),
                $orig_w,
                $orig_h,
                $orig_ratio,
                $ratio_mode,
                $target_ratio,
                $method,
                $executed_method,
                $fill_mode,
                $target_canvas_w,
                $target_canvas_h,
                $saved_w,
                $saved_h
            );
            self::log( $log_entry );
        }

        return $upload;
    }

    /**
     * Builds the active ratio map from normalization_formats settings.
     * Returns an associative array [ label => float ] of enabled formats.
     * Falls back to hardcoded defaults if no formats are configured.
     *
     * @return array<string, float>
     */
    private static function active_ratios(): array {
        $norm_opts = get_option( 'bunny_normalization_settings', [] );
        $formats   = $norm_opts['formats'] ?? [];

        $active = [];
        foreach ( $formats as $fmt ) {
            if ( empty( $fmt['enabled'] ) ) {
                continue;
            }
            $name  = trim( $fmt['name'] ?? '' );
            $ratio = (float) ( $fmt['ratio'] ?? 0 );
            if ( $name !== '' && $ratio > 0 ) {
                $active[ $name ] = $ratio;
            }
        }

        // Fallback: hardcoded defaults so the system always has something.
        if ( empty( $active ) ) {
            $active = [
                '1:1'    => 1.0,
                '4:5'    => 0.8,
                '1.91:1' => 1.91,
                '4.74:1' => 4.74,
            ];
        }

        return $active;
    }


    /**
     * Determines the target aspect ratio for this image.
     * Returns null if normalization should be skipped (within tolerance).
     *
     * @param string $file        Absolute path to the image.
     * @param string $ratio_mode  'auto' | a format name from active_ratios()
     * @param float  $tolerance   Max allowed delta (0 = always normalize).
     * @return float|null Target ratio (width/height), or null to skip.
     */
    private static function resolve_target_ratio( string $file, string $ratio_mode, float $tolerance ): ?float {
        $size = @getimagesize( $file );
        if ( ! $size || ! $size[0] || ! $size[1] ) {
            return null;
        }

        $actual_ratio  = $size[0] / $size[1];
        $active_ratios = self::active_ratios();

        // Fixed mode: look up the named ratio in the active set.
        if ( $ratio_mode !== 'auto' ) {
            $target = $active_ratios[ $ratio_mode ] ?? null;
            if ( $target === null ) {
                return null; // Named format not found or disabled.
            }
            $delta = abs( $actual_ratio - $target );
            if ( $delta < 0.0001 ) {
                return null;
            }
            if ( $tolerance > 0.0 && $delta <= $tolerance ) {
                return null;
            }
            return $target;
        }

        // Auto mode: pick the closest enabled ratio.
        $best_target = null;
        $best_delta  = PHP_FLOAT_MAX;
        foreach ( $active_ratios as $ratio ) {
            $delta = abs( $actual_ratio - $ratio );
            if ( $delta < $best_delta ) {
                $best_delta  = $delta;
                $best_target = $ratio;
            }
        }

        if ( $best_target === null ) {
            return null;
        }

        if ( $best_delta < 0.0001 ) {
            return null;
        }
        if ( $tolerance > 0.0 && $best_delta <= $tolerance ) {
            return null;
        }

        return $best_target;
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
     * @param string $file          Absolute path.
     * @param string $mime          MIME type.
     * @param float  $target_ratio  Width/height target.
     * @param string $method        'pad' | 'crop' | 'smart_crop'
     * @param string $fill_mode     'solid_color' | 'corner_sample' | 'dominant_color'
     * @param string $bg_color      'white' | 'black' | 'transparent'
     * @param string $sample_corner 'top_left' | 'top_right' | 'bottom_left' | 'bottom_right' | 'average_corners'
     */
    private static function normalize_image(
        string $file,
        string $mime,
        float  $target_ratio,
        string $method,
        string $fill_mode,
        string $bg_color,
        string $sample_corner
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
                $result = self::apply_pad(
                    $src, $orig_w, $orig_h, $target_ratio,
                    $fill_mode, $bg_color, $sample_corner, $mime
                );
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
     * The original image is centered; empty space is filled according to fill_mode.
     * Never removes pixels.
     *
     * @param \GdImage $src
     * @param string   $fill_mode     'solid_color' | 'corner_sample' | 'dominant_color'
     * @param string   $bg_color      'white' | 'black' | 'transparent'
     * @param string   $sample_corner 'top_left' | 'top_right' | 'bottom_left' | 'bottom_right' | 'average_corners'
     * @param string   $mime
     * @return \GdImage|null
     */
    private static function apply_pad(
        \GdImage $src,
        int      $orig_w,
        int      $orig_h,
        float    $target_ratio,
        string   $fill_mode,
        string   $bg_color,
        string   $sample_corner,
        string   $mime
    ): ?\GdImage {
        $actual_ratio = $orig_w / $orig_h;

        if ( $actual_ratio > $target_ratio ) {
            $new_w = $orig_w;
            $new_h = (int) round( $orig_w / $target_ratio );
        } else {
            $new_h = $orig_h;
            $new_w = (int) round( $orig_h * $target_ratio );
        }

        $canvas = imagecreatetruecolor( $new_w, $new_h );
        if ( ! ( $canvas instanceof \GdImage ) ) {
            return null;
        }

        // Resolve fill color based on mode.
        $supports_alpha = in_array( $mime, [ 'image/png', 'image/webp' ], true );

        switch ( $fill_mode ) {

            case 'corner_sample':
                [ $r, $g, $b ] = self::sample_fill_color( $src, $orig_w, $orig_h, $sample_corner );
                imagealphablending( $canvas, true );
                $fill = imagecolorallocate( $canvas, $r, $g, $b );
                break;

            case 'dominant_color':
                [ $r, $g, $b ] = self::dominant_fill_color( $src, $orig_w, $orig_h );
                imagealphablending( $canvas, true );
                $fill = imagecolorallocate( $canvas, $r, $g, $b );
                break;

            default: // solid_color
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
                break;
        }

        imagefilledrectangle( $canvas, 0, 0, $new_w - 1, $new_h - 1, $fill );

        // Restore alpha blending for the imagecopy step if we used transparent mode.
        if ( $fill_mode === 'solid_color' && $supports_alpha && $bg_color === 'transparent' ) {
            imagealphablending( $canvas, true );
        }

        // Center the source image on the canvas.
        $dst_x = (int) round( ( $new_w - $orig_w ) / 2 );
        $dst_y = (int) round( ( $new_h - $orig_h ) / 2 );
        imagecopy( $canvas, $src, $dst_x, $dst_y, 0, 0, $orig_w, $orig_h );

        // Re-enable alpha save for final output if transparent.
        if ( $fill_mode === 'solid_color' && $supports_alpha && $bg_color === 'transparent' ) {
            imagealphablending( $canvas, false );
            imagesavealpha( $canvas, true );
        }

        return $canvas;
    }

    /**
     * CORNER SAMPLE: Reads one or more corner pixels from the source image
     * and returns an averaged RGB value to use as the pad fill color.
     *
     * Strategy: read a small NxN sample region at each requested corner,
     * average all sampled pixels to produce a stable, non-noisy result.
     * Sample size is 4px — enough to avoid single-pixel noise without
     * measurable performance cost even for 6000px images.
     *
     * @param \GdImage $src
     * @param int      $w
     * @param int      $h
     * @param string   $corner 'top_left'|'top_right'|'bottom_left'|'bottom_right'|'average_corners'
     * @return int[] [r, g, b]
     */
    private static function sample_fill_color(
        \GdImage $src,
        int      $w,
        int      $h,
        string   $corner
    ): array {
        $s = 4; // sample box side in pixels

        // Corner anchor points: [x_start, y_start]
        $corners = [
            'top_left'     => [ 0,      0      ],
            'top_right'    => [ $w - $s, 0      ],
            'bottom_left'  => [ 0,      $h - $s ],
            'bottom_right' => [ $w - $s, $h - $s ],
        ];

        $targets = ( $corner === 'average_corners' )
            ? array_values( $corners )
            : [ $corners[ $corner ] ?? $corners['top_left'] ];

        $total_r = 0;
        $total_g = 0;
        $total_b = 0;
        $count   = 0;

        foreach ( $targets as [ $ox, $oy ] ) {
            // Clamp coordinates to image bounds.
            $ox = max( 0, min( $w - 1, $ox ) );
            $oy = max( 0, min( $h - 1, $oy ) );
            $ex = min( $w - 1, $ox + $s - 1 );
            $ey = min( $h - 1, $oy + $s - 1 );

            for ( $x = $ox; $x <= $ex; $x++ ) {
                for ( $y = $oy; $y <= $ey; $y++ ) {
                    $rgb     = imagecolorat( $src, $x, $y );
                    $total_r += ( $rgb >> 16 ) & 0xFF;
                    $total_g += ( $rgb >>  8 ) & 0xFF;
                    $total_b +=   $rgb         & 0xFF;
                    $count++;
                }
            }
        }

        if ( $count === 0 ) {
            return [ 255, 255, 255 ]; // fallback: white
        }

        return [
            (int) round( $total_r / $count ),
            (int) round( $total_g / $count ),
            (int) round( $total_b / $count ),
        ];
    }

    /**
     * DOMINANT COLOR: Computes an approximate dominant color using
     * downscale + uniform grid sampling. No clustering, no histograms,
     * no external dependencies — pure GD.
     *
     * Strategy:
     * 1. Resample the image to a fixed 50×50 thumbnail using GD's
     *    bicubic-equivalent (imagecopyresampled). This collapses the
     *    pixel space from potentially millions to 2500 samples and
     *    acts as a natural color smoother.
     * 2. Sample every Nth pixel on a uniform grid (step=2 → 625 reads
     *    from 2500, enough for statistical stability).
     * 3. Average R, G, B across all samples.
     *
     * For renders and figures on a consistent background this produces
     * the dominant background/fill tone reliably and in <1ms even in
     * batches of 20+ images.
     *
     * @param \GdImage $src
     * @param int      $orig_w
     * @param int      $orig_h
     * @return int[] [r, g, b]
     */
    private static function dominant_fill_color(
        \GdImage $src,
        int      $orig_w,
        int      $orig_h
    ): array {
        $thumb_size = 50;
        $thumb      = imagecreatetruecolor( $thumb_size, $thumb_size );

        if ( ! ( $thumb instanceof \GdImage ) ) {
            return [ 255, 255, 255 ];
        }

        // White base to avoid transparency artifacts on JPEG/PNG.
        $white = imagecolorallocate( $thumb, 255, 255, 255 );
        imagefilledrectangle( $thumb, 0, 0, $thumb_size - 1, $thumb_size - 1, $white );
        imagealphablending( $thumb, true );

        imagecopyresampled( $thumb, $src, 0, 0, 0, 0, $thumb_size, $thumb_size, $orig_w, $orig_h );

        $total_r = 0;
        $total_g = 0;
        $total_b = 0;
        $count   = 0;
        $step    = 2; // sample every 2nd pixel on each axis → 625 samples

        for ( $x = 0; $x < $thumb_size; $x += $step ) {
            for ( $y = 0; $y < $thumb_size; $y += $step ) {
                $rgb     = imagecolorat( $thumb, $x, $y );
                $total_r += ( $rgb >> 16 ) & 0xFF;
                $total_g += ( $rgb >>  8 ) & 0xFF;
                $total_b +=   $rgb         & 0xFF;
                $count++;
            }
        }

        imagedestroy( $thumb );

        if ( $count === 0 ) {
            return [ 255, 255, 255 ];
        }

        return [
            (int) round( $total_r / $count ),
            (int) round( $total_g / $count ),
            (int) round( $total_b / $count ),
        ];
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
            $crop_h = $orig_h;
            $crop_w = (int) round( $orig_h * $target_ratio );
        } else {
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
     * Delegates to apply_crop — center is the region of interest.
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

    /**
     * Writes a debug message to wp-content/uploads/bunny-gallery-debug.log.
     */
    private static function log( string $message ): void {
        $uploads  = wp_upload_dir();
        $log_file = $uploads['basedir'] . '/bunny-gallery-debug.log';
        $timestamp = date( 'Y-m-d H:i:s' );
        @file_put_contents( $log_file, "[{$timestamp}] {$message}\n", FILE_APPEND );
    }
}

# Bunny SFW&NSFW Gallery

WordPress Gutenberg plugin for SFW/NSFW image galleries with cookie-based age verification, image normalization on upload, and upload-time image controls.

---

## Description

Bunny SFW&NSFW Gallery provides two Gutenberg blocks and a full admin settings suite covering gallery defaults, NSFW protection styles, lightbox configuration, image normalization, and upload-time image constraints.

All blocks are server-side rendered (no build system required). The plugin is designed for WordPress 6.5+ with PHP 8.2+.

---

## Features

### Blocks

**Bunny Gallery (`bunny/nsfw-gallery`)**
- SFW / NSFW mode toggle per block
- Global NSFW protection switch: enable or disable age verification and protection blur without affecting other gallery features
- Configurable columns (1–12), image size, aspect ratio and link behaviour
- Three NSFW protection styles: Minimal (blur + floating badge + modal), Overlay (semi-transparent layer + centered button), Hidden (solid dark layer + strong blur)
- Cookie-based unlock with cascading unlock across all NSFW galleries on the page
- Configurable age-gate verification cookie name (defaults to `bunny_nsfw_age`) with strict RFC 6265 token sanitization
- Re-lock button visible when the consent cookie exists
- Native lightbox: per-instance isolation, prev/next, keyboard navigation (← → Escape), touch swipe, fade transition, counter pill, optional thumbnail rail (auto-scroll, drag-to-scroll with mouse, active-state indicator), zoom + pan (buttons, click-to-toggle, mouse drag, touch pan/pinch), caption fields, dark/light/auto theme, accent colour
- Per-block title (SFW/NSFW) with show/hide toggle
- Global defaults with per-block override; fallback chain: block → global setting → hardcoded default

**Bunny Content Section (`bunny/content-section`)**
- Two-column layout (image + rich text), image position left or right
- Responsive: collapses to single column on ≤768px, image always first
- Configurable image size, visual height (small 200px / medium 320px / large 480px), image width ratio (25 / 33 / 40 / 50 %)
- RichText: paragraphs, lists, links, bold, italic
- Optional lightbox for the image (reuses the gallery lightbox system; falls back to a standalone minimal lightbox when no gallery block is on the same page)
- Show/hide title toggle

### Image Normalization

Accessed via **Bunny Gallery → Normalization**. Normalizes images to a consistent aspect ratio on upload, before WordPress generates any thumbnails.

- Enable / disable toggle (disabled by default)
- **Ratio Mode:** Auto (picks closest enabled format) or fixed (forces a specific format)
- **Normalization Formats:** five configurable slots (1:1, 4:5, 1.91:1, 9:16, 4.74:1) — enable or disable each independently
- **Tolerance:** 0.00–1.00 — skip images already within the configured delta of the target ratio
- **Processing Method:** Pad (expand canvas, no pixels removed), Crop (centre crop), Smart Crop (centre-biased crop)
- **Background Fill Mode** (Pad only): Solid Color (white / black / transparent), Corner Sample (4×4 px region average at chosen corner), Dominant Color (50×50 downsample + pixel average via GD)
- **Keep Original:** saves a `_original` copy of the file before normalizing (enabled by default)
- Supports JPEG, PNG, WebP; EXIF orientation correction for JPEG; pure GD, no external dependencies

### Image Settings

Accessed via **Bunny Gallery → Image Settings**. Upload-time constraints applied after normalization.

- **Resize Limit:** downscales images exceeding configured max width/height (priority 20, runs after normalization); aspect ratio preserved; validates both `resize()` and `save()` results
- **Big Image Threshold:** overrides WordPress core `big_image_size_threshold`; informative admin notice shown when threshold is lower than resize max width
- **Intermediate Sizes:** disable generation of selected sizes (medium, medium_large, large, 1536×1536, 2048×2048) per upload
- **Safe Filename Rename** *(optional, disabled by default):* rewrites uploaded image filenames to post slug + unique suffix; all `post_id` reads sanitized with `absint()`
- **Automatic ALT Generation** *(optional, disabled by default):* sets attachment ALT to parent post title + " - BunnyChase" on `add_attachment`; all `post_id` reads sanitized with `absint()`

### Diagnostics

Accessed via **Bunny Gallery → Normalization → Advanced & Diagnostics**.

- **Debug Log:** records a detailed entry per normalized image — filename, original dimensions, original ratio, ratio mode, selected ratio, method, executed function, fill mode, target canvas dimensions, saved dimensions
- **Log Viewer:** opens `bunny-gallery-debug.log` as plain text in a new tab (served before any HTML output to avoid headers-already-sent errors)
- **Log Size indicator:** shows current log file size in the admin UI
- **Clear Log on Save:** deletes the log file on the next settings save; flag is not persisted

---

## Requirements

- **WordPress:** 6.5 or higher
- **PHP:** 8.2 or higher (compatible with PHP 8.4)
- **GD extension:** required for Image Normalization
- No external dependencies, no build system, no npm

---

## Installation

1. Copy the `wp_sfw_nsfw_gallery` folder into `/wp-content/plugins/`
2. Activate the plugin from the WordPress admin panel
3. *(Optional)* Configure global defaults under **Bunny Gallery** in the admin sidebar
4. *(Optional)* Configure image normalization under **Bunny Gallery → Normalization**
5. *(Optional)* Configure upload constraints under **Bunny Gallery → Image Settings**
6. Insert the **Bunny Gallery** or **Bunny Content Section** block in any post or page

---

## Usage

### Bunny Gallery block

1. Insert the **Bunny Gallery** block in the editor
2. Click the placeholder to open the Media Library and select images
3. Toggle NSFW mode in the block inspector if the content requires it
4. Configure columns, image size, aspect ratio, link behaviour and titles in the inspector sidebar
5. Publish — global defaults apply automatically to fields without a per-block value

### Bunny Content Section block

1. Insert the **Bunny Content Section** block in the editor
2. Click the image placeholder to select an image from the Media Library
3. Write the title and body content directly in the block
4. Configure image position, size, height, lightbox and title visibility in the inspector sidebar
5. Publish

### Image Normalization

1. Go to **Bunny Gallery → Normalization**
2. Enable normalization and configure the desired method and formats
3. Upload images — normalization runs automatically before WordPress generates thumbnails

### Image Settings

1. Go to **Bunny Gallery → Image Settings**
2. Enable the desired constraints (resize limit, big image threshold, intermediate size disabling)
3. Optionally enable filename renaming and automatic ALT generation under Automation

---

## Settings reference

### Global Gallery Settings (`bunny_gallery_defaults`)

| Setting | Description | Default |
|---|---|---|
| Columns | Grid column count (1–12) | `5` |
| Image size | `thumbnail` / `medium` / `large` / `full` | `large` |
| Aspect ratio | `square` / `portrait` / `landscape` / `original` | `square` |
| Link behaviour | `none` / `lightbox` / `file` / `attachment` | `none` |
| Open in new tab | Applies to `file` and `attachment` only | `false` |
| NSFW display style | `minimal` / `overlay` / `hidden` | `minimal` |
| Blur enabled | Applies blur to NSFW images until age confirmation | `true` |
| Blur intensity | 0–20 px, CSS variable `--bunny-blur` | `12` |
| NSFW message | Text shown in the overlay / modal | *(default string)* |
| Unlock button text | Text of the unlock button | *(default string)* |
| SFW title | Default title for SFW galleries | *(empty)* |
| NSFW title | Default title for NSFW galleries | *(empty)* |
| Show lightbox thumbnails | Thumbnail rail at the bottom of the lightbox | `true` |
| Lightbox theme | `dark` / `light` / `auto` | `dark` |
| Lightbox accent colour | Colour picker for active thumbnail, counter, focus | `#7c6aff` |
| Caption fields | `alt` / `title` / `caption` / `description` | *(none)* |
| Caption mode | `hidden` / `minimal` / `full` | `minimal` |

### Normalization Settings (`bunny_normalization_settings`)

| Setting | Description | Default |
|---|---|---|
| Enabled | Activates normalization on upload | `false` |
| Keep Original | Saves `_original` backup before normalizing | `true` |
| Ratio Mode | `auto` or a fixed format name | `auto` |
| Tolerance | 0.00–1.00 delta to skip near-matching images | `0.00` |
| Method | `pad` / `crop` / `smart_crop` | `pad` |
| Fill Mode | `solid_color` / `corner_sample` / `dominant_color` | `solid_color` |
| Background Color | `white` / `black` / `transparent` | `white` |
| Sample Corner | Corner region to average for fill colour | `top_left` |
| Debug Log | Records a diagnostic entry per normalized image | `false` |

### Image Settings (`bunny_image_settings`)

| Setting | Description | Default |
|---|---|---|
| Resize enabled | Downscales images exceeding max dimensions | `false` |
| Max width | Maximum pixel width for resize | `1920` |
| Max height | Maximum pixel height for resize | `1920` |
| Big image enabled | Overrides `big_image_size_threshold` | `false` |
| Big image threshold | Threshold value in pixels | `1920` |
| Disabled sizes | Intermediate sizes to skip on upload | *(none)* |
| Upload rename enabled | Rewrites filenames to post slug + unique suffix | `false` |
| Auto ALT enabled | Sets ALT from parent post title on upload | `false` |

---

## File structure

```
wp_sfw_nsfw_gallery/
├── wp_sfw_nsfw_gallery.php          # Plugin header, constants, bootstrap
├── includes/
│   ├── settings.php                 # Options API, menus, sanitization, settings pages
│   ├── class-plugin.php             # Block registration, render callbacks
│   ├── class-image-normalizer.php   # Upload-time normalization (GD)
│   ├── class-image-settings.php     # Upload-time constraints (resize, threshold, sizes)
│   ├── class-loader.php             # Hook registration
│   ├── class-activator.php          # Activation hook
│   ├── class-deactivator.php        # Deactivation hook
│   ├── helpers.php                  # Utility functions
│   └── admin/
│       ├── class-admin-header.php   # Shared admin header + tab navigation
│       └── class-admin-assets.php   # Admin CSS enqueue (scoped to plugin screens)
├── assets/
│   └── css/
│       ├── bunny-admin.css          # Shared admin UI tokens and layout
│       └── admin.css                # Plugin-specific admin styles
└── blocks/
    ├── shared/
    │   └── lightbox-zoom.js         # Vanilla JS: zoom/pan compartido (window.BunnyLightboxZoom)
    ├── nsfw-gallery/
    │   ├── block.js                 # Gutenberg editor (no JSX, no build)
    │   ├── frontend.js              # Vanilla JS: NSFW styles, BunnyLightbox, cookie
    │   └── style.css                # Grid, badge, modal, overlay, lightbox styles
    └── content-section/
        ├── block.js                 # Gutenberg editor (no JSX, no build)
        ├── frontend.js              # Vanilla JS: lightbox (shared or standalone)
        └── style.css                # Two-column layout, image, text, responsive
```

---

## Attribute resolution chain

```
block attribute (value saved in the block)
        ↓  if null or empty
global plugin setting (wp_options → bunny_gallery_defaults)
        ↓  if not set
hardcoded fallback (bunny_gallery_hardcoded_defaults())
```

---

## Cookie & Protection

```
<configured_cookie_name> = 1 (default: bunny_nsfw_age)
```

- **Duration:** 365 days.
- **Attributes:** `path=/; SameSite=Lax`.
- **Purpose:** Set when the user confirms age on any NSFW gallery. When present, all NSFW galleries on the page unlock in cascade.
- **Configurable Name:** The cookie name can be customized via **Bunny Gallery → Settings → Protección NSFW**. Only RFC 6265 alphanumeric, hyphen, and underscore characters are permitted. Invalid or empty values safely fallback to `bunny_nsfw_age`.
- **Disable Protection:** The entire gallery age-gate protection system can be disabled in settings. When disabled, images are displayed immediately without age confirmation, without blur, without floating badges/overlays, and without unnecessary cookie operations. The frontend engine employs strict idempotency guards and a unified single-source-of-truth verification to ensure repeated initializations, deferred events, or subsequent renders never re-lock content or intercept image clicks. Existing installations without this setting in the database preserve protection enabled by default.
- **WP Consent API:** Registered as `functional` when WordPress Consent API is available, dynamically using the configured cookie name.
- **Re-lock Button:** The **"🔒 Volver a bloquear contenido NSFW"** button appears only when the cookie exists and protection is enabled.
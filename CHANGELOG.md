# Changelog

## 1.2.0 — 2026-10-27

- Added an admin setting to toggle the NSFW protection system on or off.
- When NSFW protection is disabled, age confirmation requests, overlays, badges, and protection blurs are bypassed without executing redundant cookie checks.
- Added a configuration setting for the gallery's age-verification cookie name, retaining `bunny_nsfw_age` as the default.
- Added strict RFC 6265 token validation and sanitization for the cookie name, falling back to the default on empty or invalid inputs.
- Updated helper functions and WordPress Consent API registration to dynamically use the configured cookie name.
- Bumped the plugin version to `1.2.0`.

## 1.1.3 — 2026-08-19

- Added WordPress Consent API registration for `bunny_nsfw_age`.
- Classified the age-gate cookie as `functional` and documented its purpose.
- Bumped the plugin version to `1.1.3`.

## 1.1.2

- Release version recorded in the plugin metadata.

## 1.1.1 — 2026-08-12

- Added mouse drag-to-scroll for the lightbox thumbnail rail.
- Fixed accidental thumbnail selection after dragging.
- Improved zoom and pan boundary calculations for differently sized images.
- Fixed mouse drag state and touch pinch-to-pan transitions in `BunnyLightboxZoom`.

## 1.1.0 — 2026-08-11

- Improved lightbox thumbnail auto-scrolling and horizontal scrolling.
- Added zoom and pan controls for the NSFW Gallery and Content Section lightboxes.
- Added the shared `blocks/shared/lightbox-zoom.js` module.
- Improved touch navigation while zoomed and reset zoom when changing images.

## 1.0.1 — 2025-06-18

- Added the `customTitle` block attribute while preserving legacy title attributes.
- Simplified the title inspector UI and documented title resolution order.

## 1.0.0 — First Stable Release

- Added configurable image normalization formats and upload-time image settings.
- Added resize limits, big image threshold controls, intermediate size controls, optional filename renaming, and automatic ALT generation.
- Improved image selection and Content Section lightbox compatibility.

## 0.6.2

- Added the Image Settings admin page.
- Added optional safe filename renaming and automatic ALT generation.

## 0.6.1

- Added background fill modes, corner sampling, dominant color sampling, and related admin controls.

## 0.6.0

- Added the Image Normalization system using GD.
- Added normalization settings and the `wp_handle_upload` integration.

## 0.5.0

- Added the `bunny/content-section` block with responsive layout and lightbox integration.

## 0.4.0–0.4.5

- Added the lightbox redesign, themes, captions, thumbnail rail, touch navigation, titles, and related stability fixes.

## 0.3.0–0.3.1

- Added gallery titles, image and aspect-ratio controls, NSFW display styles, cascading unlock, and the re-lock button.

## 0.2.0

- Added global settings and the native per-instance lightbox.

## 0.0.1

- Initial Gutenberg SFW/NSFW gallery release with blur, overlays, and cookie-based cascading unlock.

# Bunny SFW&NSFW Gallery

Plugin de WordPress para Gutenberg que permite crear galerías con control SFW/NSFW basado en cookies, lightbox nativo aislado por instancia y sistema de defaults globales.

---

## Versión actual

**0.6.2** — Image Settings UI, optional upload snippets

---

## Características

- Bloque Gutenberg dinámico (render en PHP, sin build system)
- **Bunny Gallery** — galería SFW/NSFW con blur, lightbox, overlays y cookie
- **Bunny Content Section** — sección imagen + título + texto enriquecido con layout responsive
- Modo SFW / NSFW por bloque (galería)
- **Títulos de galería** por modo (SFW / NSFW), override por bloque
- **Tamaño de imagen** configurable: thumbnail, medium, large, full
- **Aspect ratio** configurable: square, portrait, landscape, original
- Blur configurable con **intensidad en px** (0–20, CSS variable `--bunny-blur`)
- **3 estilos de protección NSFW**: minimal, overlay, hidden
- **Modal elegante** (modo minimal) con backdrop, ESC y cancelar
- **Badge flotante** sobre las imágenes en modo minimal (tipo Patreon/Pixiv)
- **Botón de re-bloqueo** visible cuando la cookie existe
- Texto del botón desbloquear configurable globalmente
- Desbloqueo sin recarga, con propagación en cascada a galerías de la misma página
- **Lightbox nativo aislado por instancia** — botones prev/next fijos al viewport
- Fade al cambiar imagen en lightbox
- Soporte táctil (swipe izquierda/derecha en lightbox)
- Navegación por teclado en lightbox (←, →, Escape)
- Counter visual: `3 / 12`
- **Settings globales** en menú propio: Bunny Gallery
- Cadena de resolución: bloque → setting global → fallback hardcoded

## Bloques disponibles

### Bunny Gallery

Galería de imágenes con sistema SFW/NSFW, blur, lightbox nativo, overlays y desbloqueo por cookie. Ver sección Settings globales para opciones de configuración.

### Bunny Content Section

Sección de contenido con imagen, título y texto enriquecido.

**Layout desktop:** dos columnas (imagen + texto), configurable imagen izquierda o derecha.  
**Layout tablet/mobile:** colapsa automáticamente a columna única — imagen arriba, texto abajo.  
**Opciones en inspector:**
- Posición de imagen (izquierda / derecha)
- Tamaño de imagen (thumbnail / medium / large / full)
- Altura visual (small 200px / medium 320px / large 480px)
- Abrir imagen en lightbox (on/off)
- Mostrar título (on/off)

**Texto:** soporta párrafos, listas, enlaces, negritas y cursivas vía RichText de Gutenberg.  
**Lightbox:** reutiliza el mismo sistema de lightbox del plugin — mismo estilo, sin duplicar código.

---

## Settings globales

Ubicación: **Bunny Gallery** (menú lateral de WordPress Admin)

### Sección: Galería

| Setting              | Descripción                                              | Default   |
|----------------------|----------------------------------------------------------|-----------|
| Columnas             | Número de columnas del grid (1–12)                       | `5`       |
| Tamaño de imagen     | `thumbnail` / `medium` / `large` / `full`                | `thumbnail`   |
| Aspect ratio         | `square` / `portrait` / `landscape` / `original`         | `portrait`  |
| Comportamiento enlace| `none` / `lightbox` / `file` / `attachment`              | `lightbox`    |
| Nueva pestaña        | Abre enlaces en `_blank` (solo file/attachment)          | `true`   |

### Sección: Protección NSFW

| Setting                | Descripción                                              | Default                                |
|------------------------|----------------------------------------------------------|----------------------------------------|
| Estilo de protección   | `minimal` / `overlay` / `hidden`                         | `minimal`                              |
| Blur NSFW activado     | Aplica blur hasta confirmación de edad                   | `true`                                 |
| Intensidad del blur    | Slider 0–20px, con preview visual                        | `12`                                   |
| Mensaje NSFW           | Texto del overlay / modal de verificación                | `Este contenido es solo para adultos.` |
| Texto botón desbloquear| Texto del botón en overlay y modal                       | `Ver contenido (+18)`                  |

### Sección: Títulos

| Setting      | Descripción                                   | Default |
|--------------|-----------------------------------------------|---------|
| Título SFW   | Título por defecto para galerías SFW          | vacío   |
| Título NSFW  | Título por defecto para galerías NSFW         | vacío   |

### Sección: Lightbox

| Setting                    | Descripción                                                        | Default   |
|----------------------------|---------------------------------------------------------------------|-----------|
| Miniaturas en lightbox     | Carrusel horizontal inferior con thumbnails WP de la galería actual | `true`    |
| Tema del lightbox          | `dark` / `light` / `auto` (sigue `prefers-color-scheme`)           | `dark`    |
| Color de acento            | Color picker — thumbnail activo, counter y focus states            | `#7c6aff` |
| Campos de caption          | Checkboxes: `alt` / `title` / `caption` / `description`            | ninguno   |
| Modo de caption            | `hidden` / `minimal` (título + 1 línea) / `full` (todos los campos) | `minimal` |

---

### Sección: Image Settings

| Setting                      | Descripción                                                                                  | Default |
|------------------------------|----------------------------------------------------------------------------------------------|---------|
| Resize images on upload      | Redimensiona imágenes que excedan las dimensiones máximas al subir (después de Normalization) | `false` |
| Max width / Max height       | Dimensiones máximas usadas por el redimensionado                                              | `1920`  |
| Big image threshold enabled  | Habilita el umbral de "big image" de WordPress                                              | `false` |
| Big image threshold (px)     | Valor del umbral grande                                                                         | `1920`  |
| Disable intermediate sizes   | Lista de tamaños intermedios a desactivar (thumbnail, medium, large, full)                   | `[]`    |
| Safe rename uploaded images  | (Opcional) Reescribe el nombre de archivo al subir: usa el slug del post + sufijo único. Deshabilitado por defecto. | `false` |
| Auto-fill ALT from post title| (Opcional) Al agregar un attachment, establece el ALT usando el título del post padre + " - BunnyChase". Deshabilitado por defecto. | `false` |


## Estilos de protección NSFW

| Estilo    | Comportamiento                                                             |
|-----------|----------------------------------------------------------------------------|
| `minimal` | Blur en imágenes + badge flotante. Click abre modal elegante (tipo Patreon)|
| `overlay` | Capa semitransparente con botón centrado (tipo X/Twitter)                  |
| `hidden`  | Capa sólida oscura + blur fuerte. Contenido casi invisible                 |

---

## Cookie utilizada

```
bunny_nsfw_age = 1
```

Duración: 365 días. Se establece al confirmar edad en cualquier galería NSFW.  
Al estar presente, todas las galerías NSFW de la página se desbloquean en cascada.  
El botón **"🔒 Volver a bloquear contenido NSFW"** aparece solo cuando la cookie existe.

---

## Comportamiento de linkTo

| Valor        | Resultado                                                 |
|--------------|-----------------------------------------------------------|
| `none`       | La imagen no tiene enlace                                 |
| `lightbox`   | Abre la imagen a pantalla completa con el lightbox nativo |
| `file`       | Enlace directo al archivo de media (URL original)         |
| `attachment` | Enlace a la página de adjunto de WordPress                |

---

## Lightbox

- Aislado por instancia: cada `.bunny-gallery-wrapper` tiene su propio objeto `BunnyLightbox`
- Una galería SFW y una NSFW en el mismo post nunca comparten imágenes
- **Botones prev/next fijos al viewport** — no se mueven al cambiar imagen
- **Fade de imagen** al navegar (opacity 0→1)
- Swipe táctil (delta > 50px)
- Teclado: ← → navegan, Escape cierra (solo activo cuando el lightbox está abierto)
- Counter visual en la parte inferior: `N / Total`
- Solo se activa cuando `data-link="lightbox"`

---

## Variables CSS emitidas por bloque

| Variable        | Valor                          | Uso                                 |
|-----------------|--------------------------------|-------------------------------------|
| `--bunny-cols`  | número de columnas             | grid-template-columns               |
| `--bunny-blur`  | intensidad en px (ej: `12px`)  | filter: blur() en imágenes NSFW     |
| `--bunny-ratio` | aspect-ratio CSS (ej: `1 / 1`) | aspect-ratio en .bunny-gallery-item |

---

## Estructura de archivos

```
wp_sfw_nsfw_gallery/
├── wp_sfw_nsfw_gallery.php     # Plugin header, constantes, bootstrap
├── includes/
│   ├── settings.php            # Options API, menú, bunny_get_setting()
│   ├── class-plugin.php        # register_block, render_callback (galería + content section)
│   ├── class-loader.php        # Registro de hooks
│   ├── class-activator.php     # Hook de activación
│   ├── class-deactivator.php   # Hook de desactivación
│   └── helpers.php             # Funciones auxiliares
└── blocks/
    ├── nsfw-gallery/
    │   ├── block.js            # Editor Gutenberg (sin JSX, sin build)
    │   ├── frontend.js         # Vanilla JS: NSFW display styles + BunnyLightbox
    │   └── style.css           # Estilos: grid, minimal badge, modal, overlay, lightbox
    └── content-section/
        ├── block.js            # Editor Gutenberg (sin JSX, sin build)
        ├── frontend.js         # Vanilla JS: lightbox single-image para Content Section
        └── style.css           # Estilos: layout responsive dos columnas, imagen, texto
```

---

## Cadena de resolución de atributos

```
block attribute (valor guardado en el bloque)
        ↓  si null o vacío
plugin setting (wp_options → bunny_gallery_defaults)
        ↓  si no existe
hardcoded fallback (bunny_gallery_hardcoded_defaults())
```

---

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- Sin dependencias externas ni build system

---

## Instalación

1. Copiar la carpeta `wp_sfw_nsfw_gallery` en `/wp-content/plugins/`
2. Activar el plugin desde el panel de administración
3. (Opcional) Configurar defaults en **Bunny Gallery** (menú lateral)
4. Insertar el bloque **Bunny Gallery** en cualquier entrada o página

---

## Uso básico

1. Insertar bloque "Bunny Gallery" en el editor
2. Seleccionar imágenes con el selector de medios
3. Activar modo NSFW si el contenido lo requiere
4. Configurar título, columnas, aspect ratio, tamaño y enlace en el inspector lateral
5. Publicar — los defaults globales se aplican automáticamente a campos sin valor propio

---

## Changelog

### 0.5.0 — Nuevo bloque: Bunny Content Section

- **Nuevo bloque:** `bunny/content-section` — sección imagen + título + texto enriquecido
- **Layout desktop:** grid dos columnas (ratio 1fr/2fr), imagen posicionable izquierda o derecha
- **Layout responsive:** colapsa a columna única en tablet/mobile (≤768px), imagen siempre arriba
- **Altura visual configurable:** small (200px) / medium (320px) / large (480px) via CSS variable `--bunny-cs-img-height`
- **Tamaño de imagen:** thumbnail / medium / large / full (mismo sistema que la galería)
- **RichText:** soporta párrafos, listas, enlaces, negritas y cursivas
- **Lightbox:** reutiliza el overlay del sistema existente — sin duplicar código
- **InspectorControls:** posición de imagen, tamaño, altura visual, toggle lightbox, toggle mostrar título
- **Render PHP:** `render_content_section()` en `class-plugin.php`, salida sanitizada con `wp_kses_post`
- Nuevos archivos: `blocks/content-section/block.js`, `frontend.js`, `style.css`
- Modificados: `wp_sfw_nsfw_gallery.php`, `includes/class-plugin.php`, `readme.md`

### 0.4.5 — Fix blur intensity control empty on block open

- **Fix:** el control `RangeControl` de intensidad del blur aparecía vacío al seleccionar el bloque — el slider no mostraba ningún valor hasta que el usuario lo movía
- **Causa:** `blurIntensity` podía ser `undefined` en bloques donde el atributo no estaba serializado (valor coincidente con default o bloque antiguo); `RangeControl` recibe `undefined` y no renderiza ningún valor
- **Solución:** `value` del control ahora usa `blurIntensity !== undefined && blurIntensity !== null ? blurIntensity : D.blur_intensity` — mismo patrón que el control de columnas
- Archivo modificado: `blocks/nsfw-gallery/block.js`

### 0.4.4 — Toggle mostrar/ocultar título por bloque

- **Nuevo:** Atributo `showTitle` (boolean, default `true`) por bloque — controla si se renderiza el título de la galería
- **Nuevo:** `ToggleControl` "Mostrar título" en el panel **Títulos** del inspector de Gutenberg
- **Comportamiento OFF:** el `<h2 class="bunny-gallery-title">` directamente no se imprime — sin `display:none`, sin espacio vacío, cero nodos en el DOM
- **Preview:** el editor reacciona al toggle de forma inmediata, igual que el frontend
- **Por bloque:** SFW y NSFW comparten el mismo toggle dentro de cada bloque; no afecta otros bloques
- Archivos modificados: `block.js`, `includes/class-plugin.php`

-### 0.6.2 — 2026-06
- **New:** Admin page `Image Settings` — consistent header and UI with other plugin pages.
- **New (optional):** "Safe rename uploaded images" — rewrites uploaded image filenames to use the post slug + unique suffix to avoid collisions. Disabled by default.
- **New (optional):** "Auto-fill ALT from post title" — sets attachment ALT to the parent post title plus " - BunnyChase" when adding attachments. Disabled by default.
- **UX:** Image Settings UI follows the same `bunny-*` cards and header used by other pages.
-
### 0.6.1 — 2025-06
- **Nuevo:** Setting `Background Fill Mode` — reemplaza el color fijo con tres modos: `solid_color`, `corner_sample`, `dominant_color`
- **Nuevo:** `corner_sample` — lee una región 4×4px del corner elegido (top_left / top_right / bottom_left / bottom_right / average_corners) y promedia el color resultante
- **Nuevo:** `dominant_color` — reduce la imagen a un thumbnail 50×50 mediante `imagecopyresampled` y promedia todos los píxeles en un grid con step=2 (625 muestras); puro GD, sin clustering, sin librerías externas
- **Nuevo:** Setting `Sample Corner` — visible únicamente cuando fill_mode es `corner_sample`; oculto en otros modos
- **Mejora:** Setting `Background Color` ahora se muestra/oculta según fill_mode activo; solo visible en `solid_color`
- **Mejora:** Show/hide dinámico en admin sin dependencias externas — JS vanilla inline, opera a nivel de `<tr>` completo
- Compatibilidad completa con JPG, PNG, WebP, Smart Crop y Keep Original

### 0.6.0 — 2025-06
- **Nuevo:** Sistema de normalización automática de imágenes al subir (`Image_Normalizer`)
- **Nuevo:** Hook `wp_handle_upload` — la normalización ocurre antes de que WordPress genere thumbnails; todos los tamaños derivados salen normalizados
- **Nuevo:** Setting `Enable Image Normalization` (OFF por defecto)
- **Nuevo:** Setting `Ratio Mode` — Auto / 1:1 / 4:5 / 1.91:1
- **Nuevo:** Setting `Processing Method` — Pad / Crop / Smart Crop
- **Nuevo:** Setting `Background Color` — White / Black / Transparent
- **Nuevo:** Setting `Ratio Tolerance` — 0.00–1.00; con 0 siempre normaliza
- **Nuevo:** Setting `Keep Original` (ON por defecto) — guarda copia `photo_original.jpg` antes de normalizar
- **Nuevo:** Página `Normalization` como submenú y pestaña del header Bunny Admin
- **Nuevo:** `includes/class-image-normalizer.php` — GD puro, EXIF orientation, soporte JPG/PNG/WebP

### 0.4.3 — Gutenberg fixes & columns UX

- **Fix:** eliminado `wp-editor` de las dependencias del editor script — causaba un `Notice` en `wp-admin/widgets.php` al ser incompatible con el nuevo editor de widgets (`wp-edit-widgets` / `wp-customize-widgets`); migrado correctamente a `wp-block-editor`
- **Fix:** el bloque Gutenberg ahora puede seleccionarse correctamente haciendo clic en el área de preview — recuperado el outline azul de selección y la toolbar contextual; causa raíz: falta de integración con `useBlockProps` de la API v3
- **Mejora:** default global de columnas cambiado de `3 → 5`
- **Mejora:** máximo de columnas aumentado de `6 → 12` — actualizado en `RangeControl`, sanitización PHP y campo de settings
- **Fix UX:** el control de columnas ya no aparece vacío en el inspector — se garantiza siempre un valor numérico válido con `parseInt(columns, 10) || D.columns`

### 0.4.2 — Bunny Admin UI

- **Bunny Admin UI system:** adopted the shared `bunny-*` admin UI convention used across all Bunny plugins. The admin page now renders a consistent sticky header with the plugin logo, name, version badge, page subtitle, and tab navigation bar.
- **New `assets/css/bunny-admin.css`:** plugin-agnostic stylesheet — sticky header, tab nav, version badge, page-content wrapper, responsive breakpoints. Loaded as a WordPress style dependency before `admin.css`.
- **New `assets/css/admin.css`:** plugin-specific admin styles (settings form headings, section borders, field radii). `--bsg-*` variables alias the shared `--bunny-*` tokens.
- **New `includes/admin/class-admin-header.php`:** `BunnyNSFW\Admin_Header` — mirrors the pattern from QPS and WPAM. Renders header + nav from a `$tabs` array; active tab detected by slug argument. Ready for future tabs without touching page templates.
- **New `includes/admin/class-admin-assets.php`:** `BunnyNSFW\Admin_Assets` — enqueues `bunny-admin.css` and `admin.css` scoped to plugin screens only.
- **`bunny_gallery_settings_page()`:** replaced inline `<h1>` and bare `<div class="wrap">` with `bunny-wrap`, `Admin_Header::render()`, and `bunny-page-content`. No logic, fields, sections, or sanitization changed.
- **No functional changes:** all block registration, render callbacks, Settings API fields, sanitization, frontend scripts, and cookie logic untouched.

### 0.4.1 — 2025-05
- **Fix:** `show_lightbox_thumbnails = false` no desactivaba el thumbnails rail — el carrusel seguía apareciendo
- **Fix (PHP):** `wp_localize_script` serializa `bool` como string vacío `""` al pasar `false`; ahora se emite `'1'` / `'0'` de forma explícita
- **Fix (JS):** La condición `!== false` no atrapaba el string vacío; reemplazada por comparación estricta `=== '1'`
- **Mejora:** El elemento `thumbsEl` ya no se crea ni se monta en el DOM cuando el setting está desactivado — cero nodos, cero listeners, cero espacio reservado
- **Mejora:** `data-thumbs` en el overlay refleja el estado real → el CSS no reserva el espacio inferior de 110px innecesariamente

### 0.4.0 — 2025-05
- **Nuevo:** Rediseño completo del lightbox UI — estilo moderno premium (inspirado en Patreon / Pixiv / iOS Photos)
- **Nuevo:** Botones prev/next/close reemplazados por icon buttons SVG con `backdrop-filter: blur`, border sutil y border-radius rectangular
- **Nuevo:** Layout 100% `position: fixed` — botones estables, no se mueven con el tamaño de imagen
- **Nuevo:** Counter convertido a pill UI con número actual en accent color
- **Nuevo:** Animación fade-in al abrir el lightbox; fade de opacidad al cambiar imagen
- **Nuevo:** Setting global `lightbox_theme` — `dark` / `light` / `auto` (respeta `prefers-color-scheme`)
- **Nuevo:** Setting global `lightbox_accent_color` — color picker, aplicado a thumbnail activo, counter y focus states
- **Nuevo:** Setting global `show_lightbox_thumbnails` — carrusel horizontal inferior de miniaturas (thumbnail WP, solo galería actual)
- **Nuevo:** Setting global `lightbox_caption_fields` — campos configurables: `alt`, `title`, `caption`, `description`
- **Nuevo:** Setting global `lightbox_caption_mode` — `hidden` / `minimal` / `full`
- **Nuevo:** `data-thumb`, `data-title`, `data-caption`, `data-description` emitidos por `render_callback`
- **Nuevo:** CSS variables completas en `.bunny-lightbox-overlay` (`--blb-bg`, `--blb-surface`, `--blb-accent`, etc.)
- **Fix:** Hover de botones prev/next eliminaba `transform: translateY(-50%)` causando salto del cursor; reemplazado por hover puramente visual (background + border + box-shadow)

### 0.3.1 — 2025-05
- **Nuevo:** Setting `nsfw_display_style`: `minimal` / `overlay` / `hidden`
- **Nuevo:** Modo `minimal` — blur + badge flotante + modal elegante (sin overlay oscuro)
- **Nuevo:** Modal NSFW con backdrop, botón confirmar/cancelar, cierre por ESC y backdrop click
- **Nuevo:** Modo `hidden` — capa sólida oscura + blur fuerte
- **Nuevo:** Setting `unlock_button_text` — texto del botón configurable globalmente
- **Nuevo:** Botón `🔒 Volver a bloquear contenido NSFW` — visible solo con cookie activa, re-bloquea sin reload
- **Fix:** El blur vive solo en las imágenes; el overlay/modal no destruye visualmente el efecto
- **Mejora:** `data-display-style` y `data-unlock-text` emitidos por el render_callback PHP
- **Mejora:** `deleteCookie()` helper para borrar la cookie de consentimiento

### 0.3.0 — 2025-05
- **Nuevo:** Títulos de galería (SFW / NSFW) con override por bloque
- **Nuevo:** Selector de tamaño de imagen (thumbnail / medium / large / full)
- **Nuevo:** Selector de aspect ratio (square / portrait / landscape / original)
- **Nuevo:** Blur intensity con slider 0–20px y preview visual en settings
- **Nuevo:** Menú propio `Bunny Gallery` en el sidebar de WordPress Admin
- **Fix:** Botones prev/next del lightbox son `position:fixed` — ya no se mueven con la imagen
- **Mejora:** Fade de imagen en lightbox, counter con pill, `body.bunny-lightbox-open`

### 0.2.0 — 2025-05
- **Nuevo:** Sistema de settings globales (Options API)
- **Nuevo:** Lightbox nativo aislado por instancia (`BunnyLightbox`)
- **Mejora:** `render_callback` con `data-gallery-id` único, `wp_localize_script`

### 0.0.1 — inicial
- Bloque Gutenberg dinámico SFW/NSFW
- Sistema de blur y overlay por cookie
- Desbloqueo en cascada entre galerías

---

## Roadmap

- [ ] Roles de usuario (desbloqueo por rol, sin cookie)
- [ ] Login NSFW propio
- [ ] Gap y border-radius configurables en settings
- [ ] Shortcode equivalente al bloque
- [ ] Lazy loading en lightbox para galerías grandes

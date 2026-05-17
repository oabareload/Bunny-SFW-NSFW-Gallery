# Bunny SFW&NSFW Gallery

Plugin de WordPress para Gutenberg que permite crear galerías con control SFW/NSFW basado en cookies, lightbox nativo aislado por instancia y sistema de defaults globales.

---

## Versión actual

**0.3.1** — Sistema NSFW visual mejorado: display styles, modal elegante, reset de cookie

---

## Características

- Bloque Gutenberg dinámico (render en PHP, sin build system)
- Modo SFW / NSFW por bloque
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

---

## Settings globales

Ubicación: **Bunny Gallery** (menú lateral de WordPress Admin)

### Sección: Galería

| Setting              | Descripción                                              | Default   |
|----------------------|----------------------------------------------------------|-----------|
| Columnas             | Número de columnas del grid (1–6)                        | `3`       |
| Tamaño de imagen     | `thumbnail` / `medium` / `large` / `full`                | `large`   |
| Aspect ratio         | `square` / `portrait` / `landscape` / `original`         | `square`  |
| Comportamiento enlace| `none` / `lightbox` / `file` / `attachment`              | `none`    |
| Nueva pestaña        | Abre enlaces en `_blank` (solo file/attachment)          | `false`   |

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

---

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
│   ├── class-plugin.php        # register_block, render_callback
│   ├── class-loader.php        # Registro de hooks
│   ├── class-activator.php     # Hook de activación
│   ├── class-deactivator.php   # Hook de desactivación
│   └── helpers.php             # Funciones auxiliares
└── blocks/
    └── nsfw-gallery/
        ├── block.js            # Editor Gutenberg (sin JSX, sin build)
        ├── frontend.js         # Vanilla JS: NSFW display styles + BunnyLightbox
        └── style.css           # Estilos: grid, minimal badge, modal, overlay, lightbox
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

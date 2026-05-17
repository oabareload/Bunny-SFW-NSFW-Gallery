# Bunny SFW&NSFW Gallery

Plugin de WordPress para Gutenberg que permite crear galerías con control SFW/NSFW basado en cookies, lightbox nativo aislado por instancia y sistema de defaults globales.

---

## Versión actual

**0.3.0** — UX improvements: títulos, image size, aspect ratio, blur intensity, menú propio, lightbox fix

---

## Características

- Bloque Gutenberg dinámico (render en PHP, sin build system)
- Modo SFW / NSFW por bloque
- **Títulos de galería** por modo (SFW / NSFW), override por bloque
- **Tamaño de imagen** configurable: thumbnail, medium, large, full
- **Aspect ratio** configurable: square, portrait, landscape, original
- Blur configurable con **intensidad en px** (0–20, CSS variable `--bunny-blur`)
- Overlay de verificación de edad con mensaje personalizable
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

| Setting              | Descripción                                              | Default                                |
|----------------------|----------------------------------------------------------|----------------------------------------|
| Blur NSFW activado   | Aplica blur hasta confirmación de edad                   | `true`                                 |
| Intensidad del blur  | Slider 0–20px, con preview visual                        | `12`                                   |
| Mensaje overlay NSFW | Texto del overlay de verificación de edad                | `Este contenido es solo para adultos.` |

### Sección: Títulos

| Setting      | Descripción                                   | Default |
|--------------|-----------------------------------------------|---------|
| Título SFW   | Título por defecto para galerías SFW          | vacío   |
| Título NSFW  | Título por defecto para galerías NSFW         | vacío   |

Los bloques con valor propio siempre lo priorizan sobre los settings globales.

---

## Cookie utilizada

```
bunny_nsfw_age = 1
```

Duración: 365 días. Se establece al confirmar edad en cualquier galería NSFW.  
Al estar presente, todas las galerías NSFW de la página se desbloquean en cascada.

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

| Variable        | Valor                                 | Uso                                  |
|-----------------|---------------------------------------|--------------------------------------|
| `--bunny-cols`  | número de columnas                    | grid-template-columns                |
| `--bunny-blur`  | intensidad en px (ej: `12px`)         | filter: blur() en imágenes NSFW      |
| `--bunny-ratio` | aspect-ratio CSS (ej: `1 / 1`)        | aspect-ratio en .bunny-gallery-item  |

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
        ├── frontend.js         # Vanilla JS: NSFW overlay + BunnyLightbox
        └── style.css           # Estilos frontend: grid, overlay, lightbox
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

### 0.3.0 — 2025-05
- **Nuevo:** Títulos de galería (SFW / NSFW) con override por bloque
- **Nuevo:** Selector de tamaño de imagen (thumbnail / medium / large / full)
- **Nuevo:** Selector de aspect ratio (square / portrait / landscape / original) en frontend y editor preview
- **Nuevo:** Blur intensity con slider 0–20px, preview visual en settings, variable CSS `--bunny-blur`
- **Nuevo:** Menú propio `Bunny Gallery` en el sidebar de WordPress Admin (reemplaza Settings sub-página)
- **Fix:** Botones prev/next del lightbox ahora son `position:fixed` al viewport — ya no se mueven con la imagen
- **Mejora:** Fade de imagen en lightbox al navegar (opacity transition)
- **Mejora:** Counter visual con pill de fondo (legibilidad mejorada)
- **Mejora:** `body.bunny-lightbox-open` para bloquear scroll del body
- **Mejora:** `RangeControl` en el editor para columnas y blur intensity
- **Mejora:** Settings organizados en 3 secciones: Galería, Protección NSFW, Títulos

### 0.2.0 — 2025-05
- **Nuevo:** Sistema de settings globales (Options API)
- **Nuevo:** Lightbox nativo aislado por instancia (`BunnyLightbox`)
- **Mejora:** `render_callback` con `data-gallery-id` único
- **Mejora:** `wp_localize_script` pasa defaults al editor JS

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

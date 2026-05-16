# Bunny SFW&NSFW Gallery

Plugin de WordPress para Gutenberg que permite crear galerías con control SFW/NSFW basado en cookies, lightbox nativo aislado por instancia y sistema de defaults globales.

---

## Versión actual

**0.2.0** — Settings globales + Lightbox por galería

---

## Características

- Bloque Gutenberg dinámico (render en PHP, sin build system)
- Modo SFW / NSFW por bloque
- Blur configurable en imágenes NSFW hasta confirmación de edad
- Overlay de verificación de edad con mensaje personalizable
- Desbloqueo sin recarga, con propagación en cascada a galerías de la misma página
- **Lightbox nativo aislado por instancia** — múltiples galerías en el mismo post no se mezclan
- Soporte táctil (swipe izquierda/derecha en lightbox)
- Navegación por teclado en lightbox (←, →, Escape)
- **Settings globales** en Ajustes → Bunny SFW&NSFW Gallery
- Cadena de resolución: bloque → setting global → fallback hardcoded

---

## Settings globales

Ubicación: **Ajustes → Bunny SFW&NSFW Gallery**

| Setting              | Descripción                                              | Default                                |
|----------------------|----------------------------------------------------------|----------------------------------------|
| Columnas             | Número de columnas del grid (1–6)                        | `3`                                    |
| Blur NSFW activado   | Aplica blur hasta confirmación de edad                   | `true`                                 |
| Comportamiento enlace| `none` / `lightbox` / `file` / `attachment`              | `none`                                 |
| Nueva pestaña        | Abre enlaces en `_blank` (solo file/attachment)          | `false`                                |
| Mensaje overlay NSFW | Texto del overlay de verificación de edad                | `Este contenido es solo para adultos.` |

Los bloques con valor propio siempre prioridan su valor sobre los settings globales.

---

## Cookie utilizada

```
bunny_nsfw_age = 1
```

Duración: 365 días. Se establece al confirmar edad en cualquier galería NSFW.  
Al estar presente, todas las galerías NSFW de la página se desbloquean en cascada.

---

## Comportamiento de linkTo

| Valor        | Resultado                                                          |
|--------------|--------------------------------------------------------------------|
| `none`       | La imagen no tiene enlace                                          |
| `lightbox`   | Abre la imagen a pantalla completa con el lightbox nativo          |
| `file`       | Enlace directo al archivo de media (URL original)                  |
| `attachment` | Enlace a la página de adjunto de WordPress                         |

---

## Lightbox

- Aislado por instancia: cada `.bunny-gallery-wrapper` tiene su propio objeto `BunnyLightbox`
- Una galería SFW y una NSFW en el mismo post nunca comparten imágenes
- Swipe táctil (delta > 50px)
- Teclado: ← → navegan, Escape cierra
- Solo se activa cuando `data-link="lightbox"`

---

## Estructura de archivos

```
wp_sfw_nsfw_gallery/
├── wp_sfw_nsfw_gallery.php     # Plugin header, constantes, bootstrap
├── includes/
│   ├── settings.php            # Options API, página admin, bunny_get_setting()
│   ├── class-plugin.php        # register_block, render_callback
│   ├── class-loader.php        # Registro de hooks (infraestructura)
│   ├── class-activator.php     # Hook de activación
│   ├── class-deactivator.php   # Hook de desactivación
│   └── helpers.php             # Funciones auxiliares de cookie (PHP)
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
3. (Opcional) Configurar defaults en **Ajustes → Bunny SFW&NSFW Gallery**
4. Insertar el bloque **Bunny Gallery** en cualquier entrada o página

---

## Uso básico

1. Insertar bloque "Bunny Gallery" en el editor
2. Seleccionar imágenes con el selector de medios
3. Activar modo NSFW si el contenido lo requiere
4. Configurar columnas y comportamiento de enlace en el inspector lateral
5. Publicar — los defaults globales se aplican automáticamente a campos sin valor propio

---

## Changelog

### 0.2.0 — 2025-05
- **Nuevo:** Sistema de settings globales (Options API)
  - Página en Ajustes → Bunny SFW&NSFW Gallery
  - Cadena de resolución: bloque → setting → fallback
  - `bunny_get_setting()` como helper central
- **Nuevo:** Lightbox nativo aislado por instancia
  - Clase `BunnyLightbox` — sin plugins externos
  - Aislamiento total entre galerías en la misma página
  - Soporte swipe táctil y navegación por teclado
- **Mejora:** `render_callback` con `data-gallery-id` único por instancia
- **Mejora:** `wp_localize_script` pasa defaults al editor JS
- **Mejora:** `SelectControl` para `linkTo` en el inspector (reemplaza `<select>` manual)
- **Mejora:** CSS extendido con estilos de lightbox y hover en items
- **Mejora:** `rel="noopener noreferrer"` en enlaces externos

### 0.0.1 — inicial
- Bloque Gutenberg dinámico SFW/NSFW
- Sistema de blur y overlay por cookie
- Desbloqueo en cascada entre galerías

---

## Roadmap

- [ ] Roles de usuario (desbloqueo por rol, sin cookie)
- [ ] Login NSFW propio
- [ ] Soporte para lazy loading de lightbox en galerías grandes
- [ ] Configuración global de estilos (border-radius, gap)
- [ ] Shortcode equivalente al bloque

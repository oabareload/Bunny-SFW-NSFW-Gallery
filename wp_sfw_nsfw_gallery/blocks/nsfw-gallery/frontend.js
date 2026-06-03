/**
 * Bunny SFW&NSFW Gallery — frontend.js
 * @since 0.4.0
 */

( function () {
    'use strict';

    /* ══════════════════════════════════════════════════════════════════════════
     *  COOKIES
     * ══════════════════════════════════════════════════════════════════════════ */

    function getCookie( name ) {
        if ( ! document.cookie ) return null;
        var pairs = document.cookie.split( ';' );
        for ( var i = 0; i < pairs.length; i++ ) {
            var pair  = pairs[ i ].trim();
            var eqIdx = pair.indexOf( '=' );
            if ( eqIdx === -1 ) continue;
            if ( pair.substring( 0, eqIdx ).trim() === name ) {
                return decodeURIComponent( pair.substring( eqIdx + 1 ).trim() );
            }
        }
        return null;
    }

    function setCookie( name, value, days ) {
        var expires = new Date();
        expires.setTime( expires.getTime() + ( days || 365 ) * 864e5 );
        document.cookie = name + '=' + encodeURIComponent( value ) +
            '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
    }

    function deleteCookie( name ) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax';
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  CONSTANTES
     * ══════════════════════════════════════════════════════════════════════════ */

    var COOKIE_NAME   = 'bunny_nsfw_age';
    var COOKIE_VALUE  = '1';
    var CLASS_LOCKED  = 'locked';

    /* ══════════════════════════════════════════════════════════════════════════
     *  LIGHTBOX v0.4.0 — rediseño completo premium
     * ══════════════════════════════════════════════════════════════════════════ */

    // Leer settings globales inyectados por PHP (bunnyGalleryLightbox)
    var LB_CFG = window.bunnyGalleryLightbox || {};
    var LB_SHOW_THUMBS    = LB_CFG.show_lightbox_thumbnails === '1'; // '1' | '0' — wp_localize_script serializa bool como string
    var LB_THEME          = LB_CFG.lightbox_theme            || 'dark'; // dark|light|auto
    var LB_ACCENT         = LB_CFG.lightbox_accent_color     || '#7c6aff';
    var LB_CAPTION_FIELDS = LB_CFG.lightbox_caption_fields   || [];     // ['title','alt','caption','description']
    var LB_CAPTION_MODE   = LB_CFG.lightbox_caption_mode     || 'minimal'; // hidden|minimal|full

    // SVG icons inline
    var SVG_CLOSE = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    var SVG_PREV  = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><polyline points="15 18 9 12 15 6"/></svg>';
    var SVG_NEXT  = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><polyline points="9 18 15 12 9 6"/></svg>';

    function BunnyLightbox( wrapper ) {
        this.wrapper   = wrapper;
        this.galleryId = wrapper.dataset.galleryId || '';
        this.current   = 0;
        this.overlay   = null;
        this.images    = Array.from( wrapper.querySelectorAll( '.bunny-gallery-item' ) ).map( function ( item ) {
            return {
                src:         item.dataset.full        || '',
                thumb:       item.dataset.thumb       || ( item.querySelector( 'img' ) ? item.querySelector( 'img' ).src : '' ),
                alt:         item.dataset.alt         || '',
                title:       item.dataset.title       || '',
                caption:     item.dataset.caption     || '',
                description: item.dataset.description || '',
            };
        } );
        this._bindItems();
    }

    BunnyLightbox.prototype.open = function ( index ) {
        if ( ! this.images.length ) return;
        this.current = index;
        if ( ! this.overlay ) this._buildOverlay();
        this._render();
        this.overlay.removeAttribute( 'hidden' );
        document.body.classList.add( 'bunny-lightbox-open' );
        this.overlay.focus();
    };

    BunnyLightbox.prototype.close = function () {
        if ( this.overlay ) this.overlay.setAttribute( 'hidden', '' );
        document.body.classList.remove( 'bunny-lightbox-open' );
    };

    BunnyLightbox.prototype.prev = function () {
        this.current = ( this.current - 1 + this.images.length ) % this.images.length;
        this._render();
    };

    BunnyLightbox.prototype.next = function () {
        this.current = ( this.current + 1 ) % this.images.length;
        this._render();
    };

    BunnyLightbox.prototype._render = function () {
        if ( ! this.overlay ) return;
        var self  = this;
        var img   = this.images[ this.current ];
        var imgEl = this.overlay.querySelector( '.blb-img' );

        // fade transition
        imgEl.style.opacity = '0';
        imgEl.src = img.src;
        imgEl.alt = img.alt;
        imgEl.onload = function () { imgEl.style.opacity = '1'; };
        if ( imgEl.complete && imgEl.naturalWidth ) imgEl.style.opacity = '1';

        // counter: partes separadas para color accent en current
        var counterEl = this.overlay.querySelector( '.blb-counter' );
        var cur  = this.overlay.querySelector( '.blb-counter-current' );
        var sep  = this.overlay.querySelector( '.blb-counter-sep' );
        var tot  = this.overlay.querySelector( '.blb-counter-total' );
        if ( cur ) cur.textContent = ( this.current + 1 );
        if ( sep ) sep.textContent = '/';
        if ( tot ) tot.textContent = this.images.length;

        // nav buttons visibility
        var showNav = this.images.length > 1;
        var btnPrev = this.overlay.querySelector( '.blb-prev' );
        var btnNext = this.overlay.querySelector( '.blb-next' );
        if ( btnPrev ) btnPrev.style.display = showNav ? 'flex' : 'none';
        if ( btnNext ) btnNext.style.display = showNav ? 'flex' : 'none';

        // caption
        this._renderCaption( img );

        // thumbnails active state
        this._updateThumbActive();
    };

    BunnyLightbox.prototype._renderCaption = function ( img ) {
        var captionEl = this.overlay.querySelector( '.blb-caption' );
        if ( ! captionEl ) return;
        if ( LB_CAPTION_MODE === 'hidden' || LB_CAPTION_FIELDS.length === 0 ) {
            captionEl.setAttribute( 'hidden', '' );
            return;
        }

        var titleText = '';
        var bodyParts = [];

        // En modo minimal: solo title (si en fields) + una sola línea de texto
        // En modo full: todo lo que esté en fields
        var fields = LB_CAPTION_FIELDS;
        fields.forEach( function ( f ) {
            var val = img[ f ] || img.alt || '';
            if ( f === 'alt' )         val = img.alt         || '';
            if ( f === 'title' )       val = img.title       || '';
            if ( f === 'caption' )     val = img.caption     || '';
            if ( f === 'description' ) val = img.description || '';
            if ( ! val ) return;
            if ( f === 'title' && ! titleText ) {
                titleText = val;
            } else {
                bodyParts.push( val );
            }
        } );

        if ( LB_CAPTION_MODE === 'minimal' ) {
            bodyParts = bodyParts.slice( 0, 1 ); // solo la primera línea extra
        }

        var hasContent = titleText || bodyParts.length > 0;
        if ( ! hasContent ) {
            captionEl.setAttribute( 'hidden', '' );
            return;
        }

        captionEl.removeAttribute( 'hidden' );
        captionEl.className = 'blb-caption' + ( LB_CAPTION_MODE === 'full' ? ' blb-caption--full' : '' );

        var titleNode = captionEl.querySelector( '.blb-caption-title' );
        var textNode  = captionEl.querySelector( '.blb-caption-text'  );
        if ( titleNode ) titleNode.textContent = titleText;
        if ( textNode  ) textNode.textContent  = bodyParts.join( ' · ' );
        if ( titleNode ) titleNode.style.display = titleText     ? '' : 'none';
        if ( textNode  ) textNode.style.display  = bodyParts.length ? '' : 'none';

        // actualizar data-caption para que el CSS ajuste el img-wrap
        this.overlay.dataset.caption = hasContent ? '1' : '';
    };

    BunnyLightbox.prototype._updateThumbActive = function () {
        var self   = this;
        var thumbs = this.overlay ? this.overlay.querySelectorAll( '.blb-thumb' ) : [];
        thumbs.forEach( function ( t, i ) {
            if ( i === self.current ) {
                t.classList.add( 'is-active' );
                // scroll into view
                t.scrollIntoView( { behavior: 'smooth', block: 'nearest', inline: 'center' } );
            } else {
                t.classList.remove( 'is-active' );
            }
        } );
    };

    BunnyLightbox.prototype._buildOverlay = function () {
        var self = this;

        var root = document.createElement( 'div' );
        root.className = 'bunny-lightbox-overlay';
        root.setAttribute( 'hidden', '' );
        root.setAttribute( 'role', 'dialog' );
        root.setAttribute( 'aria-modal', 'true' );
        root.setAttribute( 'aria-label', 'Visor de imágenes' );
        root.setAttribute( 'tabindex', '-1' );
        root.dataset.for   = this.galleryId;
        root.dataset.theme = LB_THEME;
        root.dataset.thumbs = ( LB_SHOW_THUMBS && this.images.length > 1 ) ? '1' : '0';
        root.style.setProperty( '--bunny-accent', LB_ACCENT );

        // Backdrop
        var backdrop = document.createElement( 'div' );
        backdrop.className = 'blb-backdrop';
        backdrop.addEventListener( 'click', function () { self.close(); } );

        // Close button
        var btnClose = document.createElement( 'button' );
        btnClose.className = 'blb-btn blb-close';
        btnClose.type = 'button';
        btnClose.setAttribute( 'aria-label', 'Cerrar' );
        btnClose.innerHTML = SVG_CLOSE;
        btnClose.addEventListener( 'click', function () { self.close(); } );

        // Prev button
        var btnPrev = document.createElement( 'button' );
        btnPrev.className = 'blb-btn blb-prev';
        btnPrev.type = 'button';
        btnPrev.setAttribute( 'aria-label', 'Imagen anterior' );
        btnPrev.innerHTML = SVG_PREV;
        btnPrev.addEventListener( 'click', function () { self.prev(); } );

        // Next button
        var btnNext = document.createElement( 'button' );
        btnNext.className = 'blb-btn blb-next';
        btnNext.type = 'button';
        btnNext.setAttribute( 'aria-label', 'Imagen siguiente' );
        btnNext.innerHTML = SVG_NEXT;
        btnNext.addEventListener( 'click', function () { self.next(); } );

        // Image wrap
        var imgWrap = document.createElement( 'div' );
        imgWrap.className = 'blb-img-wrap';
        var imgEl = document.createElement( 'img' );
        imgEl.className = 'blb-img';
        imgEl.src = ''; imgEl.alt = '';
        imgWrap.appendChild( imgEl );

        // Counter (pill)
        var counter = document.createElement( 'div' );
        counter.className = 'blb-counter';
        counter.setAttribute( 'aria-live', 'polite' );
        var curSpan = document.createElement( 'span' ); curSpan.className = 'blb-counter-current';
        var sepSpan = document.createElement( 'span' ); sepSpan.className = 'blb-counter-sep'; sepSpan.textContent = '/';
        var totSpan = document.createElement( 'span' ); totSpan.className = 'blb-counter-total';
        counter.appendChild( curSpan ); counter.appendChild( sepSpan ); counter.appendChild( totSpan );

        // Caption
        var caption = document.createElement( 'div' );
        caption.className = 'blb-caption';
        caption.setAttribute( 'hidden', '' );
        var captTitle = document.createElement( 'p' ); captTitle.className = 'blb-caption-title';
        var captText  = document.createElement( 'p' ); captText.className  = 'blb-caption-text';
        caption.appendChild( captTitle ); caption.appendChild( captText );

        // Thumbnails rail — solo se crea si el setting está activo
        if ( LB_SHOW_THUMBS && this.images.length > 1 ) {
            var thumbsEl = document.createElement( 'div' );
            thumbsEl.className = 'blb-thumbs';
            this.images.forEach( function ( img, i ) {
                var thumb = document.createElement( 'div' );
                thumb.className = 'blb-thumb';
                thumb.setAttribute( 'role', 'button' );
                thumb.setAttribute( 'tabindex', '0' );
                thumb.setAttribute( 'aria-label', 'Imagen ' + ( i + 1 ) );
                var tImg = document.createElement( 'img' );
                tImg.src     = img.thumb || img.src;
                tImg.alt     = '';
                tImg.loading = 'lazy';
                thumb.appendChild( tImg );
                thumb.addEventListener( 'click', function () { self.current = i; self._render(); } );
                thumb.addEventListener( 'keydown', function ( e ) {
                    if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); self.current = i; self._render(); }
                } );
                thumbsEl.appendChild( thumb );
            } );
            root.appendChild( thumbsEl );
        }

        // Assemble — thumbsEl ya fue appendeado condicionalmente arriba
        root.appendChild( backdrop );
        root.appendChild( btnClose );
        root.appendChild( counter );
        root.appendChild( btnPrev );
        root.appendChild( imgWrap );
        root.appendChild( btnNext );
        root.appendChild( caption );

        // Touch swipe
        var touchX = 0;
        root.addEventListener( 'touchstart', function ( e ) { touchX = e.changedTouches[ 0 ].screenX; }, { passive: true } );
        root.addEventListener( 'touchend',   function ( e ) {
            var d = e.changedTouches[ 0 ].screenX - touchX;
            if ( Math.abs( d ) > 50 ) { d < 0 ? self.next() : self.prev(); }
        }, { passive: true } );

        // Keyboard
        document.addEventListener( 'keydown', function ( e ) {
            if ( root.hasAttribute( 'hidden' ) ) return;
            if ( e.key === 'Escape'     ) { e.preventDefault(); self.close(); }
            if ( e.key === 'ArrowLeft'  ) { e.preventDefault(); self.prev();  }
            if ( e.key === 'ArrowRight' ) { e.preventDefault(); self.next();  }
        } );

        document.body.appendChild( root );
        this.overlay = root;
    };

    BunnyLightbox.prototype._bindItems = function () {
        var self  = this;
        var items = this.wrapper.querySelectorAll( '.bunny-gallery-item' );
        items.forEach( function ( item, index ) {
            item.style.cursor = 'pointer';
            item.addEventListener( 'click', function () { self.open( index ); } );
        } );
    };

    /* ══════════════════════════════════════════════════════════════════════════
     *  MODAL NSFW (modo minimal)
     *
     *  Un único modal global reutilizado. No se crea uno por galería.
     *  Se pasa un callback onConfirm al abrirlo.
     * ══════════════════════════════════════════════════════════════════════════ */

    var nsfwModal = null;

    function buildNSFWModal() {
        if ( nsfwModal ) return;

        var root = document.createElement( 'div' );
        root.className = 'bunny-nsfw-modal';
        root.setAttribute( 'hidden', '' );
        root.setAttribute( 'role', 'dialog' );
        root.setAttribute( 'aria-modal', 'true' );
        root.setAttribute( 'aria-label', 'Verificación de edad' );
        root.setAttribute( 'tabindex', '-1' );

        var backdrop = document.createElement( 'div' );
        backdrop.className = 'bnm-backdrop';

        var dialog = document.createElement( 'div' );
        dialog.className = 'bnm-dialog';

        var icon = document.createElement( 'div' );
        icon.className = 'bnm-icon';
        icon.textContent = '🔞';

        var msg = document.createElement( 'p' );
        msg.className = 'bnm-message';

        var btnConfirm = document.createElement( 'button' );
        btnConfirm.className = 'bnm-btn bnm-btn--confirm';
        btnConfirm.type      = 'button';

        var btnCancel = document.createElement( 'button' );
        btnCancel.className = 'bnm-btn bnm-btn--cancel';
        btnCancel.type      = 'button';
        btnCancel.textContent = 'Cancelar';

        dialog.appendChild( icon );
        dialog.appendChild( msg );
        dialog.appendChild( btnConfirm );
        dialog.appendChild( btnCancel );
        root.appendChild( backdrop );
        root.appendChild( dialog );
        document.body.appendChild( root );

        nsfwModal = {
            root:       root,
            msg:        msg,
            btnConfirm: btnConfirm,
            btnCancel:  btnCancel,
            _onConfirm: null,
        };

        function closeModal() {
            root.setAttribute( 'hidden', '' );
            document.body.classList.remove( 'bunny-lightbox-open' );
            nsfwModal._onConfirm = null;
        }

        backdrop.addEventListener( 'click', closeModal );
        btnCancel.addEventListener( 'click', closeModal );
        btnConfirm.addEventListener( 'click', function () {
            if ( nsfwModal._onConfirm ) nsfwModal._onConfirm();
            closeModal();
        } );

        document.addEventListener( 'keydown', function ( e ) {
            if ( root.hasAttribute( 'hidden' ) ) return;
            if ( e.key === 'Escape' ) { e.preventDefault(); closeModal(); }
        } );
    }

    function openNSFWModal( message, unlockText, onConfirm ) {
        buildNSFWModal();
        nsfwModal.msg.textContent        = message   || 'Este contenido es solo para adultos.';
        nsfwModal.btnConfirm.textContent = unlockText || 'Ver contenido (+18)';
        nsfwModal._onConfirm             = onConfirm;
        nsfwModal.root.removeAttribute( 'hidden' );
        document.body.classList.add( 'bunny-lightbox-open' );
        nsfwModal.btnConfirm.focus();
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  SISTEMA NSFW — unlock y propagación
     * ══════════════════════════════════════════════════════════════════════════ */

    function unlockAllNSFW() {
        setCookie( COOKIE_NAME, COOKIE_VALUE, 365 );

        document.querySelectorAll( '.bunny-gallery-wrapper[data-mode="nsfw"]' ).forEach( function ( wrapper ) {
            wrapper.classList.remove( CLASS_LOCKED );

            // Quitar badge minimal
            var badge = wrapper.querySelector( '.bunny-nsfw-badge' );
            if ( badge && badge.parentNode ) badge.parentNode.removeChild( badge );

            // Quitar overlay (overlay/hidden mode)
            var overlay = wrapper.querySelector( '.bunny-nsfw-overlay' );
            if ( overlay && overlay.parentNode ) {
                overlay.style.transition = 'opacity 0.3s ease';
                overlay.style.opacity    = '0';
                setTimeout( function () {
                    if ( overlay.parentNode ) overlay.parentNode.removeChild( overlay );
                }, 300 );
            }
        } );

        // Mostrar botón de re-bloqueo si existe
        updateResetButton();
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  MODO MINIMAL
     *  Blur en imágenes + badge flotante "🔒 Bloqueado (+18)".
     *  Click en badge o en imagen → abre modal.
     * ══════════════════════════════════════════════════════════════════════════ */

    function initMinimal( wrapper ) {
        wrapper.classList.add( CLASS_LOCKED );

        var message     = wrapper.dataset.message    || 'Este contenido es solo para adultos.';
        var unlockText  = wrapper.dataset.unlockText || 'Ver contenido (+18)';

        // Badge flotante
        var badge = document.createElement( 'button' );
        badge.className   = 'bunny-nsfw-badge';
        badge.type        = 'button';
        badge.innerHTML   = '🔒 <span>Contenido +18</span>';
        wrapper.appendChild( badge );

        function triggerModal() {
            openNSFWModal( message, unlockText, unlockAllNSFW );
        }

        badge.addEventListener( 'click', function ( e ) {
            e.stopPropagation();
            triggerModal();
        } );

        // Click en cualquier imagen también abre el modal (no el lightbox)
        wrapper.querySelectorAll( '.bunny-gallery-item' ).forEach( function ( item ) {
            item.addEventListener( 'click', function ( e ) {
                if ( wrapper.classList.contains( CLASS_LOCKED ) ) {
                    e.stopImmediatePropagation();
                    triggerModal();
                }
            }, true ); // capture: true → antes que el listener del lightbox
        } );
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  MODO OVERLAY  (comportamiento original)
     * ══════════════════════════════════════════════════════════════════════════ */

    function initOverlay( wrapper ) {
        wrapper.classList.add( CLASS_LOCKED );

        var message    = wrapper.dataset.message    || 'Este contenido es solo para adultos.';
        var unlockText = wrapper.dataset.unlockText || 'Ver contenido (+18)';

        var overlay = document.createElement( 'div' );
        overlay.className = 'bunny-nsfw-overlay bunny-nsfw-overlay--overlay';
        overlay.setAttribute( 'role', 'dialog' );
        overlay.setAttribute( 'aria-modal', 'true' );
        overlay.setAttribute( 'aria-label', 'Verificación de edad requerida' );

        var box = document.createElement( 'div' );
        box.className = 'bunny-nsfw-box';

        var msg = document.createElement( 'p' );
        msg.textContent = message;

        var btn = document.createElement( 'button' );
        btn.className   = 'bunny-unlock-btn';
        btn.type        = 'button';
        btn.textContent = unlockText;

        box.appendChild( msg );
        box.appendChild( btn );
        overlay.appendChild( box );
        wrapper.appendChild( overlay );

        btn.addEventListener( 'click', unlockAllNSFW );
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  MODO HIDDEN
     * ══════════════════════════════════════════════════════════════════════════ */

    function initHidden( wrapper ) {
        wrapper.classList.add( CLASS_LOCKED );

        var message    = wrapper.dataset.message    || 'Este contenido es solo para adultos.';
        var unlockText = wrapper.dataset.unlockText || 'Ver contenido (+18)';

        var overlay = document.createElement( 'div' );
        overlay.className = 'bunny-nsfw-overlay bunny-nsfw-overlay--hidden';
        overlay.setAttribute( 'role', 'dialog' );
        overlay.setAttribute( 'aria-modal', 'true' );
        overlay.setAttribute( 'aria-label', 'Verificación de edad requerida' );

        var box = document.createElement( 'div' );
        box.className = 'bunny-nsfw-box';

        var msg = document.createElement( 'p' );
        msg.textContent = message;

        var btn = document.createElement( 'button' );
        btn.className   = 'bunny-unlock-btn';
        btn.type        = 'button';
        btn.textContent = unlockText;

        box.appendChild( msg );
        box.appendChild( btn );
        overlay.appendChild( box );
        wrapper.appendChild( overlay );

        btn.addEventListener( 'click', unlockAllNSFW );
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  DISPATCH según display style
     * ══════════════════════════════════════════════════════════════════════════ */

    function initNSFW( wrapper ) {
        var mode = ( wrapper.dataset.mode || 'sfw' ).toLowerCase();
        if ( mode !== 'nsfw' ) return;

        if ( getCookie( COOKIE_NAME ) === COOKIE_VALUE ) {
            wrapper.classList.remove( CLASS_LOCKED );
            return;
        }

        var style = wrapper.dataset.displayStyle || 'minimal';

        if ( style === 'minimal' ) {
            initMinimal( wrapper );
        } else if ( style === 'hidden' ) {
            initHidden( wrapper );
        } else {
            initOverlay( wrapper );
        }
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  BOTÓN DE RESET / RE-BLOQUEO
     *
     *  Se inyecta una vez en el DOM (después de la última galería NSFW).
     *  Solo visible cuando la cookie existe.
     *  Al hacer clic borra la cookie y re-bloquea todo sin recargar.
     * ══════════════════════════════════════════════════════════════════════════ */

    var resetBtn = null;

    function buildResetButton() {
        if ( resetBtn ) return;

        resetBtn = document.createElement( 'button' );
        resetBtn.className   = 'bunny-nsfw-reset';
        resetBtn.type        = 'button';
        resetBtn.innerHTML   = '🔒 Volver a bloquear contenido NSFW';

        resetBtn.addEventListener( 'click', function () {
            deleteCookie( COOKIE_NAME );

            // Re-bloquear todas las galerías NSFW
            document.querySelectorAll( '.bunny-gallery-wrapper[data-mode="nsfw"]' ).forEach( function ( wrapper ) {
                var style = wrapper.dataset.displayStyle || 'minimal';
                if ( style === 'minimal' ) {
                    initMinimal( wrapper );
                } else if ( style === 'hidden' ) {
                    initHidden( wrapper );
                } else {
                    initOverlay( wrapper );
                }
            } );

            updateResetButton();
        } );

        // Insertar después de la última galería NSFW en el DOM
        var galleries = document.querySelectorAll( '.bunny-gallery-wrapper[data-mode="nsfw"]' );
        if ( galleries.length > 0 ) {
            var last = galleries[ galleries.length - 1 ];
            last.parentNode.insertBefore( resetBtn, last.nextSibling );
        } else {
            document.body.appendChild( resetBtn );
        }
    }

    function updateResetButton() {
        var hasConsent = getCookie( COOKIE_NAME ) === COOKIE_VALUE;
        if ( hasConsent ) {
            buildResetButton();
            resetBtn.removeAttribute( 'hidden' );
        } else {
            if ( resetBtn ) resetBtn.setAttribute( 'hidden', '' );
        }
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  INIT
     * ══════════════════════════════════════════════════════════════════════════ */

    function init() {
        var hasNSFW = false;

        document.querySelectorAll( '.bunny-gallery-wrapper' ).forEach( function ( wrapper ) {
            if ( wrapper.dataset.mode === 'nsfw' ) hasNSFW = true;
            initNSFW( wrapper );
            if ( wrapper.dataset.link === 'lightbox' ) {
                new BunnyLightbox( wrapper );
            }
        } );

        if ( hasNSFW ) updateResetButton();
    }

    document.readyState === 'loading'
        ? document.addEventListener( 'DOMContentLoaded', init )
        : init();

    try {// Exponer BunnyLightbox globalmente para que otros bloques puedan reutilizarlo
        window.BunnyLightbox = BunnyLightbox;
    } catch ( e ) {
        console.log( 'No se pudo exponer BunnyLightbox globalmente:', e );
    }
} )();

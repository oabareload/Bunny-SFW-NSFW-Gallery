/**
 * Bunny SFW&NSFW Gallery — frontend.js
 *
 * Dos responsabilidades completamente independientes:
 *
 *  1. NSFW overlay — igual que antes, sin cambios de comportamiento.
 *     La cookie bunny_nsfw_age=1 desbloquea todas las galerías NSFW
 *     de la misma página en cascada.
 *
 *  2. Lightbox aislado por instancia — clase BunnyLightbox.
 *     Cada .bunny-gallery-wrapper con data-link="lightbox" recibe
 *     su propia instancia. Las galerías SFW y NSFW nunca comparten
 *     su array de imágenes ni su overlay de lightbox.
 *
 * Arquitectura:
 *  - Sin variables de estado globales compartidas entre galerías.
 *  - Sin plugins externos.
 *  - Compatible con múltiples galerías por página/post.
 *
 * @since 0.2.0
 */

( function () {
    'use strict';

    /* ══════════════════════════════════════════════════════════════════════════
     *  UTILIDADES DE COOKIE
     * ══════════════════════════════════════════════════════════════════════════ */

    function getCookie( name ) {
        if ( ! document.cookie ) return null;
        var pairs = document.cookie.split( ';' );
        for ( var i = 0; i < pairs.length; i++ ) {
            var pair  = pairs[ i ].trim();
            var eqIdx = pair.indexOf( '=' );
            if ( eqIdx === -1 ) continue;
            var key = pair.substring( 0, eqIdx ).trim();
            if ( key === name ) {
                return decodeURIComponent( pair.substring( eqIdx + 1 ).trim() );
            }
        }
        return null;
    }

    function setCookie( name, value, days ) {
        days = days || 365;
        var expires = new Date();
        expires.setTime( expires.getTime() + days * 24 * 60 * 60 * 1000 );
        document.cookie =
            name + '=' + encodeURIComponent( value ) +
            '; expires=' + expires.toUTCString() +
            '; path=/; SameSite=Lax';
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  CONSTANTES
     * ══════════════════════════════════════════════════════════════════════════ */

    var COOKIE_NAME   = 'bunny_nsfw_age';
    var COOKIE_VALUE  = '1';
    var CLASS_LOCKED  = 'locked';
    var OVERLAY_CLASS = 'bunny-nsfw-overlay';

    /* ══════════════════════════════════════════════════════════════════════════
     *  CLASE BUNNYLIGHTBOX
     *
     *  Una instancia por .bunny-gallery-wrapper con data-link="lightbox".
     *  Cada instancia posee:
     *    - su propio array de imágenes (nunca comparte con otras galerías)
     *    - su propio elemento overlay en el <body>
     *    - sus propios listeners de teclado (activos solo cuando el overlay
     *      de esa instancia está abierto)
     * ══════════════════════════════════════════════════════════════════════════ */

    function BunnyLightbox( wrapper ) {
        this.wrapper   = wrapper;
        this.galleryId = wrapper.dataset.galleryId || '';
        this.current   = 0;
        this.overlay   = null;

        // Recolectar imágenes solo de este wrapper (aislamiento total).
        this.images = Array.from(
            wrapper.querySelectorAll( '.bunny-gallery-item' )
        ).map( function ( item ) {
            return {
                src: item.dataset.full || '',
                alt: item.dataset.alt  || ''
            };
        } );

        this._bindItems();
    }

    // Abre el lightbox en el índice indicado.
    BunnyLightbox.prototype.open = function ( index ) {
        if ( this.images.length === 0 ) return;
        this.current = index;
        if ( ! this.overlay ) this._buildOverlay();
        this._render();
        this.overlay.removeAttribute( 'hidden' );
        document.body.style.overflow = 'hidden';
        this.overlay.focus();
    };

    // Cierra el lightbox.
    BunnyLightbox.prototype.close = function () {
        if ( this.overlay ) this.overlay.setAttribute( 'hidden', '' );
        document.body.style.overflow = '';
    };

    // Navega al ítem anterior (con wrap-around).
    BunnyLightbox.prototype.prev = function () {
        this.current = ( this.current - 1 + this.images.length ) % this.images.length;
        this._render();
    };

    // Navega al ítem siguiente (con wrap-around).
    BunnyLightbox.prototype.next = function () {
        this.current = ( this.current + 1 ) % this.images.length;
        this._render();
    };

    // Actualiza la imagen y el contador en el overlay.
    BunnyLightbox.prototype._render = function () {
        if ( ! this.overlay ) return;
        var img = this.images[ this.current ];
        var imgEl     = this.overlay.querySelector( '.blb-img' );
        var counterEl = this.overlay.querySelector( '.blb-counter' );

        imgEl.src = img.src;
        imgEl.alt = img.alt;
        counterEl.textContent = ( this.current + 1 ) + ' / ' + this.images.length;

        // Ocultar flechas si solo hay 1 imagen.
        var showNav = this.images.length > 1;
        this.overlay.querySelector( '.blb-prev' ).style.display = showNav ? '' : 'none';
        this.overlay.querySelector( '.blb-next' ).style.display = showNav ? '' : 'none';
    };

    // Construye el DOM del overlay y lo añade al <body>.
    BunnyLightbox.prototype._buildOverlay = function () {
        var self = this;

        var el       = document.createElement( 'div' );
        el.className = 'bunny-lightbox-overlay';
        el.setAttribute( 'hidden', '' );
        el.setAttribute( 'role', 'dialog' );
        el.setAttribute( 'aria-modal', 'true' );
        el.setAttribute( 'aria-label', 'Visor de imágenes' );
        el.setAttribute( 'tabindex', '-1' );
        el.dataset.for = this.galleryId; // referencia de debug

        el.innerHTML = [
            '<div class="blb-backdrop"></div>',
            '<div class="blb-dialog">',
            '  <button class="blb-close" type="button" aria-label="Cerrar">&times;</button>',
            '  <button class="blb-prev"  type="button" aria-label="Imagen anterior">&#8592;</button>',
            '  <img    class="blb-img" src="" alt="" />',
            '  <button class="blb-next"  type="button" aria-label="Imagen siguiente">&#8594;</button>',
            '  <span   class="blb-counter"></span>',
            '</div>'
        ].join( '' );

        // Cerrar al hacer clic en el backdrop.
        el.querySelector( '.blb-backdrop' ).addEventListener( 'click', function () {
            self.close();
        } );

        el.querySelector( '.blb-close' ).addEventListener( 'click', function () {
            self.close();
        } );

        el.querySelector( '.blb-prev' ).addEventListener( 'click', function () {
            self.prev();
        } );

        el.querySelector( '.blb-next' ).addEventListener( 'click', function () {
            self.next();
        } );

        // Swipe táctil básico (izquierda/derecha).
        var touchStartX = 0;
        el.addEventListener( 'touchstart', function ( e ) {
            touchStartX = e.changedTouches[ 0 ].screenX;
        }, { passive: true } );
        el.addEventListener( 'touchend', function ( e ) {
            var delta = e.changedTouches[ 0 ].screenX - touchStartX;
            if ( Math.abs( delta ) > 50 ) {
                delta < 0 ? self.next() : self.prev();
            }
        }, { passive: true } );

        // Teclado — solo activo cuando ESTE overlay está abierto.
        document.addEventListener( 'keydown', function ( e ) {
            if ( el.hasAttribute( 'hidden' ) ) return; // este lightbox está cerrado
            if ( e.key === 'Escape'     ) { e.preventDefault(); self.close(); }
            if ( e.key === 'ArrowLeft'  ) { e.preventDefault(); self.prev();  }
            if ( e.key === 'ArrowRight' ) { e.preventDefault(); self.next();  }
        } );

        document.body.appendChild( el );
        this.overlay = el;
    };

    // Enlaza el clic en cada item del grid al método open().
    BunnyLightbox.prototype._bindItems = function () {
        var self  = this;
        var items = this.wrapper.querySelectorAll( '.bunny-gallery-item' );
        items.forEach( function ( item, index ) {
            item.style.cursor = 'pointer';
            item.addEventListener( 'click', function () {
                self.open( index );
            } );
        } );
    };

    /* ══════════════════════════════════════════════════════════════════════════
     *  SISTEMA NSFW (sin cambios respecto a v0.0.1)
     * ══════════════════════════════════════════════════════════════════════════ */

    function createOverlay( message ) {
        var overlay = document.createElement( 'div' );
        overlay.className = OVERLAY_CLASS;
        overlay.setAttribute( 'role', 'dialog' );
        overlay.setAttribute( 'aria-modal', 'true' );
        overlay.setAttribute( 'aria-label', 'Verificación de edad requerida' );

        var box = document.createElement( 'div' );
        box.className = 'bunny-nsfw-box';

        var msg = document.createElement( 'p' );
        msg.textContent = message || 'Este contenido es solo para adultos.';

        var btn = document.createElement( 'button' );
        btn.className   = 'bunny-unlock-btn';
        btn.type        = 'button';
        btn.textContent = 'Ver contenido (+18)';

        box.appendChild( msg );
        box.appendChild( btn );
        overlay.appendChild( box );

        return overlay;
    }

    function lockGallery( wrapper, message, blur ) {
        wrapper.classList.add( CLASS_LOCKED );
        wrapper.dataset.blur = blur ? '1' : '0';

        if ( wrapper.querySelector( '.' + OVERLAY_CLASS ) ) {
            reconnectUnlockBtn( wrapper );
            return;
        }

        var overlay = createOverlay( message );
        wrapper.appendChild( overlay );

        var btn = overlay.querySelector( '.bunny-unlock-btn' );
        if ( btn ) {
            btn.addEventListener( 'click', function () {
                unlockGallery( wrapper, overlay );
            } );
        }
    }

    function reconnectUnlockBtn( wrapper ) {
        var overlay = wrapper.querySelector( '.' + OVERLAY_CLASS );
        if ( ! overlay ) return;
        var btn = overlay.querySelector( '.bunny-unlock-btn' );
        if ( ! btn ) return;
        var freshBtn = btn.cloneNode( true );
        btn.parentNode.replaceChild( freshBtn, btn );
        freshBtn.addEventListener( 'click', function () {
            unlockGallery( wrapper, overlay );
        } );
    }

    function unlockGallery( wrapper, overlay ) {
        setCookie( COOKIE_NAME, COOKIE_VALUE, 365 );
        wrapper.classList.remove( CLASS_LOCKED );

        if ( overlay && overlay.parentNode ) {
            overlay.style.transition = 'opacity 0.3s ease';
            overlay.style.opacity    = '0';
            setTimeout( function () {
                if ( overlay.parentNode ) overlay.parentNode.removeChild( overlay );
            }, 300 );
        }

        // Desbloquear el resto de galerías NSFW de la página.
        unlockAllSiblingGalleries( wrapper );
    }

    function unlockAllSiblingGalleries( currentWrapper ) {
        var all = document.querySelectorAll( '.bunny-gallery-wrapper[data-mode="nsfw"]' );
        for ( var i = 0; i < all.length; i++ ) {
            var sibling = all[ i ];
            if ( sibling === currentWrapper ) continue;
            if ( ! sibling.classList.contains( CLASS_LOCKED ) ) continue;
            sibling.classList.remove( CLASS_LOCKED );
            var siblingOverlay = sibling.querySelector( '.' + OVERLAY_CLASS );
            if ( siblingOverlay && siblingOverlay.parentNode ) {
                siblingOverlay.parentNode.removeChild( siblingOverlay );
            }
        }
    }

    function initNSFW( wrapper ) {
        var mode    = ( wrapper.dataset.mode || 'sfw' ).toLowerCase();
        var message = wrapper.dataset.message || '';
        var blur    = wrapper.dataset.blur !== '0';

        if ( mode !== 'nsfw' ) return;

        var hasConsent = getCookie( COOKIE_NAME ) === COOKIE_VALUE;
        if ( hasConsent ) {
            wrapper.classList.remove( CLASS_LOCKED );
            return;
        }

        lockGallery( wrapper, message, blur );
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  PUNTO DE ENTRADA
     * ══════════════════════════════════════════════════════════════════════════ */

    function init() {
        var wrappers = document.querySelectorAll( '.bunny-gallery-wrapper' );
        if ( ! wrappers || wrappers.length === 0 ) return;

        for ( var i = 0; i < wrappers.length; i++ ) {
            var wrapper = wrappers[ i ];
            if ( ! ( wrapper instanceof HTMLElement ) ) continue;

            // 1. Sistema NSFW (siempre).
            initNSFW( wrapper );

            // 2. Lightbox — solo si el bloque lo indica.
            if ( wrapper.dataset.link === 'lightbox' ) {
                new BunnyLightbox( wrapper );
            }
        }
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

} )();

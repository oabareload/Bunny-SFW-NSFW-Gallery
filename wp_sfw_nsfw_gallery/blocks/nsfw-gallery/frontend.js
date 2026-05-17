/**
 * Bunny SFW&NSFW Gallery — frontend.js
 * @since 0.3.1
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
     *  LIGHTBOX (sin cambios respecto a 0.3.0)
     * ══════════════════════════════════════════════════════════════════════════ */

    function BunnyLightbox( wrapper ) {
        this.wrapper   = wrapper;
        this.galleryId = wrapper.dataset.galleryId || '';
        this.current   = 0;
        this.overlay   = null;
        this.images    = Array.from( wrapper.querySelectorAll( '.bunny-gallery-item' ) ).map( function ( item ) {
            return { src: item.dataset.full || '', alt: item.dataset.alt || '' };
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
        var img   = this.images[ this.current ];
        var imgEl = this.overlay.querySelector( '.blb-img' );
        imgEl.style.opacity = '0';
        imgEl.src = img.src;
        imgEl.alt = img.alt;
        imgEl.onload = function () { imgEl.style.opacity = '1'; };
        if ( imgEl.complete ) imgEl.style.opacity = '1';
        this.overlay.querySelector( '.blb-counter' ).textContent = ( this.current + 1 ) + ' / ' + this.images.length;
        var showNav = this.images.length > 1;
        this.overlay.querySelector( '.blb-prev' ).style.display = showNav ? 'flex' : 'none';
        this.overlay.querySelector( '.blb-next' ).style.display = showNav ? 'flex' : 'none';
    };

    BunnyLightbox.prototype._buildOverlay = function () {
        var self    = this;
        var root    = document.createElement( 'div' );
        root.className = 'bunny-lightbox-overlay';
        root.setAttribute( 'hidden', '' );
        root.setAttribute( 'role', 'dialog' );
        root.setAttribute( 'aria-modal', 'true' );
        root.setAttribute( 'aria-label', 'Visor de imágenes' );
        root.setAttribute( 'tabindex', '-1' );
        root.dataset.for = this.galleryId;

        var backdrop = document.createElement( 'div' );
        backdrop.className = 'blb-backdrop';
        backdrop.addEventListener( 'click', function () { self.close(); } );

        var btnClose = document.createElement( 'button' );
        btnClose.className = 'blb-close'; btnClose.type = 'button';
        btnClose.setAttribute( 'aria-label', 'Cerrar' ); btnClose.innerHTML = '&times;';
        btnClose.addEventListener( 'click', function () { self.close(); } );

        var btnPrev = document.createElement( 'button' );
        btnPrev.className = 'blb-prev'; btnPrev.type = 'button';
        btnPrev.setAttribute( 'aria-label', 'Imagen anterior' ); btnPrev.innerHTML = '&#8592;';
        btnPrev.addEventListener( 'click', function () { self.prev(); } );

        var btnNext = document.createElement( 'button' );
        btnNext.className = 'blb-next'; btnNext.type = 'button';
        btnNext.setAttribute( 'aria-label', 'Imagen siguiente' ); btnNext.innerHTML = '&#8594;';
        btnNext.addEventListener( 'click', function () { self.next(); } );

        var imgWrap = document.createElement( 'div' );
        imgWrap.className = 'blb-img-wrap';
        var imgEl = document.createElement( 'img' );
        imgEl.className = 'blb-img'; imgEl.src = ''; imgEl.alt = '';
        imgEl.style.opacity = '0'; imgEl.style.transition = 'opacity 0.2s ease';
        imgWrap.appendChild( imgEl );

        var counter = document.createElement( 'span' );
        counter.className = 'blb-counter';

        root.appendChild( backdrop ); root.appendChild( btnClose );
        root.appendChild( btnPrev );  root.appendChild( imgWrap );
        root.appendChild( btnNext );  root.appendChild( counter );

        var touchX = 0;
        root.addEventListener( 'touchstart', function ( e ) { touchX = e.changedTouches[ 0 ].screenX; }, { passive: true } );
        root.addEventListener( 'touchend',   function ( e ) {
            var d = e.changedTouches[ 0 ].screenX - touchX;
            if ( Math.abs( d ) > 50 ) { d < 0 ? self.next() : self.prev(); }
        }, { passive: true } );

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

} )();

/**
 * Bunny SFW&NSFW Gallery — frontend.js
 * @since 0.3.0
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
     *  Layout del overlay (todos los elementos son position:fixed, nunca
     *  dentro del .blb-dialog):
     *
     *  .bunny-lightbox-overlay   (fixed, inset:0, z:99999)
     *    .blb-backdrop            (fixed, inset:0)
     *    .blb-close               (fixed, top-right)
     *    .blb-prev                (fixed, left center)
     *    .blb-next                (fixed, right center)
     *    .blb-img-wrap            (fixed, centro)
     *      img.blb-img
     *    .blb-counter             (fixed, bottom center)
     *
     *  Los botones prev/next son fixed al viewport → NO se mueven
     *  aunque la imagen cambie de tamaño.
     * ══════════════════════════════════════════════════════════════════════════ */

    function BunnyLightbox( wrapper ) {
        this.wrapper   = wrapper;
        this.galleryId = wrapper.dataset.galleryId || '';
        this.current   = 0;
        this.overlay   = null;

        this.images = Array.from(
            wrapper.querySelectorAll( '.bunny-gallery-item' )
        ).map( function ( item ) {
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
        var img = this.images[ this.current ];

        // Fade rápido al cambiar imagen
        var imgEl = this.overlay.querySelector( '.blb-img' );
        imgEl.style.opacity = '0';
        imgEl.src = img.src;
        imgEl.alt = img.alt;
        imgEl.onload = function () { imgEl.style.opacity = '1'; };
        // fallback por si ya estaba en caché
        if ( imgEl.complete ) imgEl.style.opacity = '1';

        this.overlay.querySelector( '.blb-counter' ).textContent =
            ( this.current + 1 ) + ' / ' + this.images.length;

        var showNav = this.images.length > 1;
        this.overlay.querySelector( '.blb-prev' ).style.display = showNav ? 'flex' : 'none';
        this.overlay.querySelector( '.blb-next' ).style.display = showNav ? 'flex' : 'none';
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
        root.dataset.for = this.galleryId;

        // ── Backdrop ─────────────────────────────────────────────────────────
        var backdrop = document.createElement( 'div' );
        backdrop.className = 'blb-backdrop';
        backdrop.addEventListener( 'click', function () { self.close(); } );

        // ── Botón cerrar ──────────────────────────────────────────────────────
        var btnClose = document.createElement( 'button' );
        btnClose.className   = 'blb-close';
        btnClose.type        = 'button';
        btnClose.setAttribute( 'aria-label', 'Cerrar' );
        btnClose.innerHTML   = '&times;';
        btnClose.addEventListener( 'click', function () { self.close(); } );

        // ── Botón anterior ────────────────────────────────────────────────────
        var btnPrev = document.createElement( 'button' );
        btnPrev.className  = 'blb-prev';
        btnPrev.type       = 'button';
        btnPrev.setAttribute( 'aria-label', 'Imagen anterior' );
        btnPrev.innerHTML  = '&#8592;';
        btnPrev.addEventListener( 'click', function () { self.prev(); } );

        // ── Botón siguiente ───────────────────────────────────────────────────
        var btnNext = document.createElement( 'button' );
        btnNext.className  = 'blb-next';
        btnNext.type       = 'button';
        btnNext.setAttribute( 'aria-label', 'Imagen siguiente' );
        btnNext.innerHTML  = '&#8594;';
        btnNext.addEventListener( 'click', function () { self.next(); } );

        // ── Imagen ────────────────────────────────────────────────────────────
        var imgWrap = document.createElement( 'div' );
        imgWrap.className = 'blb-img-wrap';

        var imgEl = document.createElement( 'img' );
        imgEl.className = 'blb-img';
        imgEl.src = '';
        imgEl.alt = '';
        imgEl.style.opacity = '0';
        imgEl.style.transition = 'opacity 0.2s ease';
        imgWrap.appendChild( imgEl );

        // ── Counter ───────────────────────────────────────────────────────────
        var counter = document.createElement( 'span' );
        counter.className = 'blb-counter';

        // ── Ensamblar ─────────────────────────────────────────────────────────
        root.appendChild( backdrop );
        root.appendChild( btnClose );
        root.appendChild( btnPrev );
        root.appendChild( imgWrap );
        root.appendChild( btnNext );
        root.appendChild( counter );

        // ── Swipe táctil ──────────────────────────────────────────────────────
        var touchX = 0;
        root.addEventListener( 'touchstart', function ( e ) {
            touchX = e.changedTouches[ 0 ].screenX;
        }, { passive: true } );
        root.addEventListener( 'touchend', function ( e ) {
            var delta = e.changedTouches[ 0 ].screenX - touchX;
            if ( Math.abs( delta ) > 50 ) { delta < 0 ? self.next() : self.prev(); }
        }, { passive: true } );

        // ── Teclado (solo cuando ESTE overlay está visible) ───────────────────
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
     *  SISTEMA NSFW
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

        // Propagar desbloqueo a otras galerías NSFW de la página
        document.querySelectorAll( '.bunny-gallery-wrapper[data-mode="nsfw"]' )
            .forEach( function ( sibling ) {
                if ( sibling === wrapper ) return;
                if ( ! sibling.classList.contains( CLASS_LOCKED ) ) return;
                sibling.classList.remove( CLASS_LOCKED );
                var sOv = sibling.querySelector( '.' + OVERLAY_CLASS );
                if ( sOv && sOv.parentNode ) sOv.parentNode.removeChild( sOv );
            } );
    }

    function initNSFW( wrapper ) {
        var mode = ( wrapper.dataset.mode || 'sfw' ).toLowerCase();
        if ( mode !== 'nsfw' ) return;

        if ( getCookie( COOKIE_NAME ) === COOKIE_VALUE ) {
            wrapper.classList.remove( CLASS_LOCKED );
            return;
        }

        var message = wrapper.dataset.message || '';
        var blur    = wrapper.dataset.blur !== '0';

        wrapper.classList.add( CLASS_LOCKED );
        wrapper.dataset.blur = blur ? '1' : '0';

        var overlay = createOverlay( message );
        wrapper.appendChild( overlay );

        overlay.querySelector( '.bunny-unlock-btn' )
               .addEventListener( 'click', function () { unlockGallery( wrapper, overlay ); } );
    }

    /* ══════════════════════════════════════════════════════════════════════════
     *  INIT
     * ══════════════════════════════════════════════════════════════════════════ */

    function init() {
        document.querySelectorAll( '.bunny-gallery-wrapper' ).forEach( function ( wrapper ) {
            initNSFW( wrapper );
            if ( wrapper.dataset.link === 'lightbox' ) {
                new BunnyLightbox( wrapper );
            }
        } );
    }

    document.readyState === 'loading'
        ? document.addEventListener( 'DOMContentLoaded', init )
        : init();

} )();

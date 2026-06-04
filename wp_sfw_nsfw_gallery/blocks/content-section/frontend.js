/**
 * Bunny Content Section — frontend.js
 * @since 0.5.0
 *
 * Prioriza window.BunnyLightbox si la galería NSFW está en la misma página.
 * Si no, instancia un lightbox mínimo autónomo compatible con el mismo markup
 * y los mismos estilos del lightbox de la galería.
 */

( function () {
    'use strict';

    /* ──────────────────────────────────────────────────────────────────────────
     * Lightbox mínimo autónomo
     * Soporta una sola imagen. Reutiliza las clases CSS del lightbox de galería
     * (bunny-lightbox-overlay, blb-*) para no duplicar estilos.
     * ────────────────────────────────────────────────────────────────────────── */

    var SVG_CLOSE = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

    function BunnyCSLightbox( full, alt ) {
        this.full    = full;
        this.alt     = alt || '';
        this.overlay = null;
    }

    BunnyCSLightbox.prototype.open = function () {
        if ( ! this.overlay ) this._build();
        var imgEl = this.overlay.querySelector( '.blb-img' );
        imgEl.src = this.full;
        imgEl.alt = this.alt;
        this.overlay.removeAttribute( 'hidden' );
        document.body.classList.add( 'bunny-lightbox-open' );
        this.overlay.focus();
    };

    BunnyCSLightbox.prototype.close = function () {
        if ( this.overlay ) this.overlay.setAttribute( 'hidden', '' );
        document.body.classList.remove( 'bunny-lightbox-open' );
    };

    BunnyCSLightbox.prototype._build = function () {
        var self = this;

        var root = document.createElement( 'div' );
        root.className = 'bunny-lightbox-overlay';
        root.setAttribute( 'hidden', '' );
        root.setAttribute( 'role', 'dialog' );
        root.setAttribute( 'aria-modal', 'true' );
        root.setAttribute( 'aria-label', 'Visor de imágenes' );
        root.setAttribute( 'tabindex', '-1' );
        root.dataset.theme  = 'dark';
        root.dataset.thumbs = '0';

        var backdrop = document.createElement( 'div' );
        backdrop.className = 'blb-backdrop';
        backdrop.addEventListener( 'click', function () { self.close(); } );

        var btnClose = document.createElement( 'button' );
        btnClose.className = 'blb-btn blb-close';
        btnClose.type      = 'button';
        btnClose.setAttribute( 'aria-label', 'Cerrar' );
        btnClose.innerHTML = SVG_CLOSE;
        btnClose.addEventListener( 'click', function () { self.close(); } );

        var imgWrap = document.createElement( 'div' );
        imgWrap.className = 'blb-img-wrap';
        var imgEl = document.createElement( 'img' );
        imgEl.className = 'blb-img';
        imgEl.src = ''; imgEl.alt = '';
        imgWrap.appendChild( imgEl );

        root.appendChild( backdrop );
        root.appendChild( btnClose );
        root.appendChild( imgWrap );

        document.addEventListener( 'keydown', function ( e ) {
            if ( root.hasAttribute( 'hidden' ) ) return;
            if ( e.key === 'Escape' ) { e.preventDefault(); self.close(); }
        } );

        document.body.appendChild( root );
        this.overlay = root;
    };

    /* ──────────────────────────────────────────────────────────────────────────
     * Init
     * ────────────────────────────────────────────────────────────────────────── */

    function initContentSectionLightbox() {
        document.querySelectorAll( '.bunny-cs-image-wrap[data-lightbox="1"]' ).forEach( function ( wrap ) {
            var full = wrap.dataset.full;
            if ( ! full ) return;

            var imgEl = wrap.querySelector( 'img' );
            if ( ! imgEl ) return;

            wrap.style.cursor = 'zoom-in';

            if ( typeof window.BunnyLightbox === 'function' ) {
                // Galería NSFW presente en la misma página: reutilizar su lightbox.
                var syntheticWrapper = document.createElement( 'div' );
                syntheticWrapper.dataset.galleryId = 'bunny-cs-' + Math.random().toString( 36 ).slice( 2, 7 );

                var syntheticItem = document.createElement( 'div' );
                syntheticItem.className           = 'bunny-gallery-item';
                syntheticItem.dataset.full        = full;
                syntheticItem.dataset.thumb       = full;
                syntheticItem.dataset.alt         = imgEl.alt || '';
                syntheticItem.dataset.title       = '';
                syntheticItem.dataset.caption     = '';
                syntheticItem.dataset.description = '';
                syntheticWrapper.appendChild( syntheticItem );

                var lb = new window.BunnyLightbox( syntheticWrapper );
                wrap.addEventListener( 'click', function () { lb.open( 0 ); } );
            } else {
                // Sin galería en la página: lightbox autónomo mínimo.
                var csLb = new BunnyCSLightbox( full, imgEl.alt || '' );
                wrap.addEventListener( 'click', function () { csLb.open(); } );
            }
        } );
    }

    document.readyState === 'loading'
        ? document.addEventListener( 'DOMContentLoaded', initContentSectionLightbox )
        : initContentSectionLightbox();

} )();

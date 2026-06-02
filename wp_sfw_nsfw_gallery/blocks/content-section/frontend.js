/**
 * Bunny Content Section — frontend.js
 * @since 0.5.0
 *
 * Reutiliza BunnyLightbox (definido en bunny-nsfw-block-frontend/frontend.js).
 * No crea overlay propio ni listeners adicionales — delega todo al sistema
 * de lightbox existente instanciando BunnyLightbox con un wrapper sintético
 * de una sola imagen.
 */

( function () {
    'use strict';

    function initContentSectionLightbox() {
        document.querySelectorAll( '.bunny-cs-image-wrap[data-lightbox="1"]' ).forEach( function ( wrap ) {
            var full = wrap.dataset.full;
            if ( ! full ) return;

            var imgEl = wrap.querySelector( 'img' );
            if ( ! imgEl ) return;

            // Construir un wrapper sintético compatible con BunnyLightbox:
            // BunnyLightbox lee .bunny-gallery-item[data-full] dentro del wrapper.
            var syntheticWrapper = document.createElement( 'div' );
            syntheticWrapper.dataset.galleryId = 'bunny-cs-' + Math.random().toString( 36 ).slice( 2, 7 );

            var syntheticItem = document.createElement( 'div' );
            syntheticItem.className        = 'bunny-gallery-item';
            syntheticItem.dataset.full     = full;
            syntheticItem.dataset.thumb    = full;
            syntheticItem.dataset.alt      = imgEl.alt || '';
            syntheticItem.dataset.title    = '';
            syntheticItem.dataset.caption  = '';
            syntheticItem.dataset.description = '';
            syntheticWrapper.appendChild( syntheticItem );

            // BunnyLightbox está definido en el scope del IIFE de frontend.js de la galería.
            // Como ambos scripts corren en el mismo window, lo exponemos desde allí.
            // Si no está disponible (página sin galería), fallback al overlay compartido.
            if ( typeof window.BunnyLightbox === 'function' ) {
                var lb = new window.BunnyLightbox( syntheticWrapper );
                wrap.style.cursor = 'zoom-in';
                wrap.addEventListener( 'click', function () { lb.open( 0 ); } );
            } else {
                // Fallback: abrir imagen en nueva pestaña (degradación mínima)
                wrap.style.cursor = 'zoom-in';
                wrap.addEventListener( 'click', function () { window.open( full, '_blank', 'noopener' ); } );
            }
        } );
    }

    document.readyState === 'loading'
        ? document.addEventListener( 'DOMContentLoaded', initContentSectionLightbox )
        : initContentSectionLightbox();

} )();

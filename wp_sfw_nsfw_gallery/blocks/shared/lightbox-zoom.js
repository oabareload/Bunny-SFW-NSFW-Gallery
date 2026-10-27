/**
 * Bunny Lightbox — Zoom & Pan (módulo compartido)
 * @since 1.1.0
 *
 * Reutilizado por BunnyLightbox (nsfw-gallery) y BunnyCSLightbox (content-section).
 * No depende de ninguno de los dos: solo necesita un wrap y un <img>.
 * No introduce ninguna petición adicional: reescala/transforma el <img> ya cargado.
 */

( function () {
    'use strict';

    var MIN_SCALE  = 1;
    var MAX_SCALE  = 4;
    var STEP       = 0.6;
    var DRAG_SLOP  = 4; // px — umbral para distinguir click de arrastre

    function distance( t1, t2 ) {
        var dx = t1.clientX - t2.clientX;
        var dy = t1.clientY - t2.clientY;
        return Math.sqrt( dx * dx + dy * dy );
    }

    /**
     * @param {HTMLElement} wrapEl  Contenedor del área de imagen (.blb-img-wrap)
     * @param {HTMLElement} imgEl   La <img> a hacer zoom/pan
     * @returns {Object} controlador { zoomIn, zoomOut, reset, toggle, getScale, destroy }
     */
    function create( wrapEl, imgEl ) {
        var state = {
            scale: 1,
            x: 0,
            y: 0,
            dragging: false,
            moved: false,
            startX: 0,
            startY: 0,
            origX: 0,
            origY: 0,
            pinchDist: null,
        };

        function apply() {
            imgEl.style.transform  = 'translate(' + state.x + 'px,' + state.y + 'px) scale(' + state.scale + ')';
            imgEl.style.transition = state.dragging ? 'none' : 'transform 0.18s ease';
            wrapEl.classList.toggle( 'is-zoomed', state.scale > 1 );
            imgEl.style.cursor = state.scale > 1 ? ( state.dragging ? 'grabbing' : 'grab' ) : 'zoom-in';
            imgEl.style.touchAction = state.scale > 1 ? 'none' : 'pan-y';
        }

        function getBaseImageSize() {
            // Tamaño del contenedor sin transformar (el wrap nunca tiene transform aplicado,
            // solo la imagen, por lo que su rect es fiable independientemente del zoom).
            var rect = wrapEl.getBoundingClientRect();
            var containerW = rect.width;
            var containerH = rect.height;

            var naturalW = imgEl.naturalWidth;
            var naturalH = imgEl.naturalHeight;

            // Sin dimensiones naturales todavía (imagen no cargada): fallback al tamaño
            // del contenedor para no romper el primer cálculo.
            if ( ! naturalW || ! naturalH || ! containerW || ! containerH ) {
                return { width: containerW, height: containerH };
            }

            // Replicar el resultado de object-fit: contain para obtener el tamaño
            // REAL renderizado de la imagen en escala base (scale = 1), sin depender
            // de getBoundingClientRect() sobre el elemento transformado.
            var containerRatio = containerW / containerH;
            var naturalRatio   = naturalW / naturalH;

            if ( naturalRatio > containerRatio ) {
                // Imagen más panorámica que el contenedor: ajustada por ancho.
                return { width: containerW, height: containerW / naturalRatio };
            }
            // Imagen más vertical (o igual) que el contenedor: ajustada por alto.
            return { width: containerH * naturalRatio, height: containerH };
        }

        function clamp() {
            var base = getBaseImageSize();
            var maxX = Math.max( 0, ( base.width  * ( state.scale - 1 ) ) / 2 );
            var maxY = Math.max( 0, ( base.height * ( state.scale - 1 ) ) / 2 );
            state.x = Math.min( maxX, Math.max( -maxX, state.x ) );
            state.y = Math.min( maxY, Math.max( -maxY, state.y ) );
        }

        function setScale( next ) {
            next = Math.min( MAX_SCALE, Math.max( MIN_SCALE, next ) );
            if ( next === state.scale ) return;
            if ( next === MIN_SCALE ) { state.x = 0; state.y = 0; }
            state.scale = next;
            clamp();
            apply();
        }

        function reset() {
            state.scale = 1;
            state.x = 0;
            state.y = 0;
            state.dragging = false;
            apply();
        }

        function zoomIn()  { setScale( state.scale + STEP ); }
        function zoomOut() { setScale( state.scale - STEP ); }
        function toggle()  { state.scale > 1 ? reset() : setScale( 2 ); }

        /* ── Mouse drag ──────────────────────────────────────────────── */

        function onMouseDown( e ) {
            if ( state.scale <= 1 ) return;
            e.preventDefault();
            state.dragging = true;
            state.moved    = false;
            state.startX   = e.clientX;
            state.startY   = e.clientY;
            state.origX    = state.x;
            state.origY    = state.y;
            apply();
        }

        function onMouseMove( e ) {
            if ( ! state.dragging ) return;
            var dx = e.clientX - state.startX;
            var dy = e.clientY - state.startY;
            if ( Math.abs( dx ) > DRAG_SLOP || Math.abs( dy ) > DRAG_SLOP ) state.moved = true;
            state.x = state.origX + dx;
            state.y = state.origY + dy;
            clamp();
            apply();
        }

        function onMouseUp() {
            if ( ! state.dragging ) return;
            state.dragging = false;
            apply();
            // Si el mouseup ocurre fuera de <img>, el evento 'click' nunca se disparará
            // sobre ella y 'moved' quedaría atascado en true, bloqueando el siguiente
            // clic legítimo. Se limpia en el siguiente tick: si SÍ hay un click síncrono
            // sobre la imagen (mouseup dentro), onClick ya lo habrá consumido antes.
            setTimeout( function () { state.moved = false; }, 0 );
        }

        function onClick( e ) {
            if ( state.moved ) { state.moved = false; e.preventDefault(); return; }
            toggle();
        }

        /* ── Touch (pan de 1 dedo + pinch de 2 dedos) ───────────────── */

        function onTouchStart( e ) {
            if ( e.touches.length === 1 && state.scale > 1 ) {
                state.dragging = true;
                state.moved    = false;
                state.startX   = e.touches[ 0 ].clientX;
                state.startY   = e.touches[ 0 ].clientY;
                state.origX    = state.x;
                state.origY    = state.y;
            } else if ( e.touches.length === 2 ) {
                state.pinchDist = distance( e.touches[ 0 ], e.touches[ 1 ] );
            }
        }

        function onTouchMove( e ) {
            if ( e.touches.length === 1 && state.dragging ) {
                var dx = e.touches[ 0 ].clientX - state.startX;
                var dy = e.touches[ 0 ].clientY - state.startY;
                if ( Math.abs( dx ) > DRAG_SLOP || Math.abs( dy ) > DRAG_SLOP ) state.moved = true;
                state.x = state.origX + dx;
                state.y = state.origY + dy;
                clamp();
                apply();
            } else if ( e.touches.length === 2 && state.pinchDist ) {
                var d     = distance( e.touches[ 0 ], e.touches[ 1 ] );
                var delta = ( d - state.pinchDist ) / 120;
                setScale( state.scale + delta );
                state.pinchDist = d;
            }
        }

        function onTouchEnd( e ) {
            if ( e.touches.length === 1 ) {
                // Transición pinch (2 dedos) → pan (1 dedo): reanclar al dedo restante
                // para evitar un salto visual de la imagen.
                state.pinchDist = null;
                state.dragging  = state.scale > 1;
                state.moved     = false;
                state.startX    = e.touches[ 0 ].clientX;
                state.startY    = e.touches[ 0 ].clientY;
                state.origX     = state.x;
                state.origY     = state.y;
            } else if ( e.touches.length === 0 ) {
                state.dragging  = false;
                state.pinchDist = null;
                apply();
            }
        }

        imgEl.addEventListener( 'mousedown', onMouseDown );
        window.addEventListener( 'mousemove', onMouseMove );
        window.addEventListener( 'mouseup', onMouseUp );
        imgEl.addEventListener( 'click', onClick );

        imgEl.addEventListener( 'touchstart', onTouchStart, { passive: true } );
        imgEl.addEventListener( 'touchmove',  onTouchMove,  { passive: true } );
        imgEl.addEventListener( 'touchend',   onTouchEnd,   { passive: true } );

        apply();

        return {
            zoomIn:   zoomIn,
            zoomOut:  zoomOut,
            reset:    reset,
            toggle:   toggle,
            getScale: function () { return state.scale; },
            destroy:  function () {
                imgEl.removeEventListener( 'mousedown', onMouseDown );
                window.removeEventListener( 'mousemove', onMouseMove );
                window.removeEventListener( 'mouseup', onMouseUp );
                imgEl.removeEventListener( 'click', onClick );
                imgEl.removeEventListener( 'touchstart', onTouchStart );
                imgEl.removeEventListener( 'touchmove', onTouchMove );
                imgEl.removeEventListener( 'touchend', onTouchEnd );
            },
        };
    }

    window.BunnyLightboxZoom = { create: create };
} )();

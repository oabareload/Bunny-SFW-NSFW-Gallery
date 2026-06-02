/* jshint esversion: 6 */
/**
 * Bunny SFW&NSFW Gallery — block.js
 * @since 0.3.0
 */

( function () {
    'use strict';

    var el                = wp.element.createElement;
    var registerBlock     = wp.blocks.registerBlockType;
    var MediaUpload       = wp.blockEditor.MediaUpload;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var BlockControls     = wp.blockEditor.BlockControls;

    var Button        = wp.components.Button;
    var PanelBody     = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var TextControl   = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var RangeControl  = wp.components.RangeControl;
    var ToolbarGroup  = wp.components.ToolbarGroup;
    var ToolbarButton = wp.components.ToolbarButton;
    var Spinner       = wp.components.Spinner;

    var useState       = wp.element.useState;
    var useEffect      = wp.element.useEffect;
    var useBlockProps  = wp.blockEditor.useBlockProps;

    // -------------------------------------------------------------------------
    // DEFAULTS
    // -------------------------------------------------------------------------

    var D = window.bunnyGalleryDefaults || {
        columns:        5,
        blur:           true,
        blur_intensity: 12,
        link_behavior:  'none',
        target_blank:   false,
        nsfw_message:   'Este contenido es solo para adultos.',
        sfw_title:      '',
        nsfw_title:     '',
        image_size:     'large',
        aspect_ratio:   'square',
    };

    // Aspect ratio → CSS aspect-ratio
    var RATIO_MAP = {
        square:    '1 / 1',
        portrait:  '2 / 3',
        landscape: '16 / 9',
        original:  'auto',
    };

    // -------------------------------------------------------------------------
    // REGISTRO
    // -------------------------------------------------------------------------

    registerBlock( 'bunny/nsfw-gallery', {
 
        apiVersion: 3,
        title:    'Bunny Gallery',
        icon:     'format-gallery',
        category: 'media',

        attributes: {
            images:        { type: 'array',   default: [] },
            imageData:     { type: 'array',   default: [] },
            mode:          { type: 'string',  default: 'sfw' },
            message:       { type: 'string',  default: D.nsfw_message },
            columns:       { type: 'number',  default: D.columns },
            linkTo:        { type: 'string',  default: D.link_behavior },
            blur:          { type: 'boolean', default: !! D.blur },
            blurIntensity: { type: 'number',  default: D.blur_intensity },
            targetBlank:   { type: 'boolean', default: !! D.target_blank },
            imageSize:     { type: 'string',  default: D.image_size },
            aspectRatio:   { type: 'string',  default: D.aspect_ratio },
            sfwTitle:      { type: 'string',  default: D.sfw_title  || '' },
            nsfwTitle:     { type: 'string',  default: D.nsfw_title || '' },
            showTitle:     { type: 'boolean', default: true },
        },

        // ----------------------------------------------------------------------
        // EDIT
        // ----------------------------------------------------------------------

        edit: function ( props ) {

            var attrs = props.attributes;
            var set   = props.setAttributes;

            var images        = attrs.images;
            var imageData     = attrs.imageData;
            var mode          = attrs.mode;
            var message       = attrs.message;
            var columns       = attrs.columns;
            var linkTo        = attrs.linkTo;
            var blur          = attrs.blur;
            var blurIntensity = attrs.blurIntensity;
            var imageSize     = attrs.imageSize;
            var aspectRatio   = attrs.aspectRatio;
            var sfwTitle      = attrs.sfwTitle;
            var nsfwTitle     = attrs.nsfwTitle;
            var showTitle     = attrs.showTitle !== false;

            var rState        = useState( imageData || [] );
            var resolvedImages = rState[0];
            var setResolved    = rState[1];

            var lState   = useState( false );
            var loading  = lState[0];
            var setLoading = lState[1];

            // Resolver URLs de imágenes
            useEffect( function () {
                if ( ! images || images.length === 0 ) { setResolved( [] ); return; }

                if (
                    imageData &&
                    imageData.length === images.length &&
                    imageData.every( function ( d, i ) { return d.id === images[ i ]; } )
                ) {
                    setResolved( imageData );
                    return;
                }

                setLoading( true );
                var resolved = [];
                var pending  = images.length;

                images.forEach( function ( id ) {
                    var att = wp.media.attachment( id );

                    function onReady() {
                        var sizes = att.get( 'sizes' ) || {};
                        var url   = att.get( 'url' ) ||
                                    ( sizes.large  && sizes.large.url  ) ||
                                    ( sizes.medium && sizes.medium.url ) ||
                                    ( sizes.full   && sizes.full.url   ) || '';
                        resolved.push( { id: id, url: url, alt: att.get( 'alt' ) || '' } );
                        if ( --pending === 0 ) {
                            resolved.sort( function ( a, b ) {
                                return images.indexOf( a.id ) - images.indexOf( b.id );
                            } );
                            setResolved( resolved );
                            set( { imageData: resolved } );
                            setLoading( false );
                        }
                    }

                    att.get( 'url' ) ? onReady() : att.fetch().then( onReady ).catch( onReady );
                } );
            }, [ images ] );

            // ------------------------------------------------------------------
            // ESTILOS PREVIEW
            // ------------------------------------------------------------------

            var cssRatio  = RATIO_MAP[ aspectRatio ] || '1 / 1';
            var isNsfw    = mode === 'nsfw';
            var activeTitle = isNsfw ? nsfwTitle : sfwTitle;

            var gridStyle = {
                display:             'grid',
                gridTemplateColumns: 'repeat(' + columns + ', 1fr)',
                gap:                 '8px',
            };

            var itemStyle = {
                position:    'relative',
                borderRadius: '8px',
                overflow:    'hidden',
                aspectRatio: cssRatio,
                background:  '#f0f0f0',
            };

            var imgStyle = {
                width:      '100%',
                height:     '100%',
                objectFit:  aspectRatio === 'original' ? 'contain' : 'cover',
                display:    'block',
                filter:     ( isNsfw && blur )
                            ? 'blur(' + blurIntensity + 'px)'
                            : 'none',
                transition: 'filter 0.2s',
            };

            var badgeStyle = {
                display:       'inline-flex',
                alignItems:    'center',
                padding:       '2px 10px',
                borderRadius:  '3px',
                fontSize:      '11px',
                fontWeight:    '600',
                letterSpacing: '0.5px',
                textTransform: 'uppercase',
                background:    isNsfw ? '#cc1818' : '#1d8348',
                color:         '#fff',
            };

            // ------------------------------------------------------------------
            // PLACEHOLDER
            // ------------------------------------------------------------------

            function renderPlaceholder( open ) {
                return el( 'div', {
                    style: {
                        display: 'flex', flexDirection: 'column',
                        alignItems: 'center', justifyContent: 'center',
                        gap: '12px', minHeight: '160px',
                        border: '2px dashed #c3c4c7', borderRadius: '4px',
                        padding: '32px', background: '#f6f7f7',
                        cursor: 'pointer', boxSizing: 'border-box',
                    },
                    onClick: open,
                },
                    el( 'span', {
                        className: 'dashicons dashicons-format-gallery',
                        style: { fontSize: '48px', width: '48px', height: '48px', color: '#a0a5aa' },
                    } ),
                    el( 'p', { style: { margin: 0, color: '#757575', fontSize: '13px' } },
                        'Haz clic para seleccionar imágenes'
                    ),
                    el( Button, { variant: 'primary', onClick: open }, 'Seleccionar imágenes' )
                );
            }

            // ------------------------------------------------------------------
            // GRID DE PREVIEW
            // ------------------------------------------------------------------

            function renderGrid( open ) {
                return el( 'div', {},

                    el( BlockControls, {},
                        el( ToolbarGroup, {},
                            el( ToolbarButton, { icon: 'edit', label: 'Editar galería', onClick: open } )
                        )
                    ),

                    // Cabecera
                    el( 'div', {
                        style: {
                            display: 'flex', alignItems: 'center',
                            justifyContent: 'space-between', marginBottom: '8px',
                        },
                    },
                        el( 'div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } },
                            el( 'span', { style: badgeStyle }, isNsfw ? 'NSFW' : 'SFW' ),
                            showTitle && activeTitle && el( 'span', {
                                style: { fontSize: '14px', fontWeight: '600', color: '#1e1e1e' },
                            }, activeTitle )
                        ),
                        el( 'span', { style: { fontSize: '11px', color: '#9e9e9e' } },
                            resolvedImages.length + ' img · ' + columns + ' col · ' + aspectRatio + ' · ' + imageSize
                        )
                    ),

                    // Título visual (si existe)
                    showTitle && activeTitle && el( 'p', {
                        style: {
                            margin: '0 0 8px 0', fontSize: '16px',
                            fontWeight: '700', color: isNsfw ? '#cc1818' : '#1e1e1e',
                        },
                    }, activeTitle ),

                    // Grid
                    loading
                        ? el( 'div', { style: { textAlign: 'center', padding: '32px' } }, el( Spinner ) )
                        : el( 'div', { style: gridStyle },
                            resolvedImages.map( function ( img ) {
                                return el( 'div', { key: img.id, style: itemStyle },
                                    el( 'img', { src: img.url, alt: img.alt, style: imgStyle } )
                                );
                            } )
                          ),

                    // Pie
                    el( 'div', { style: { marginTop: '8px', textAlign: 'right' } },
                        el( Button, { variant: 'secondary', onClick: open, style: { fontSize: '12px' } },
                            'Editar galería'
                        )
                    )
                );
            }

            // ------------------------------------------------------------------
            // INSPECTOR CONTROLS
            // ------------------------------------------------------------------

            var blockProps = useBlockProps();

            return el( 'div', blockProps,

                el( InspectorControls, {},

                    // ── Panel: NSFW ──────────────────────────────────────────
                    el( PanelBody, { title: 'NSFW Settings', initialOpen: true },

                        el( ToggleControl, {
                            label:    'Modo NSFW',
                            checked:  isNsfw,
                            onChange: function ( v ) { set( { mode: v ? 'nsfw' : 'sfw' } ); },
                        } ),

                        isNsfw && el( TextControl, {
                            label:    'Mensaje overlay',
                            value:    message,
                            onChange: function ( v ) { set( { message: v } ); },
                        } ),

                        isNsfw && el( ToggleControl, {
                            label:    'Blur activo',
                            checked:  blur,
                            onChange: function ( v ) { set( { blur: v } ); },
                        } ),

                        isNsfw && blur && el( RangeControl, {
                            label:    'Intensidad del blur',
                            value:    ( blurIntensity !== undefined && blurIntensity !== null ) ? blurIntensity : D.blur_intensity,
                            min:      0,
                            max:      20,
                            step:     1,
                            onChange: function ( v ) { set( { blurIntensity: v } ); },
                        } )
                    ),

                    // ── Panel: Galería ───────────────────────────────────────
                    el( PanelBody, { title: 'Gallery Settings', initialOpen: false },

                        el( RangeControl, {
                            label:    'Columnas',
                            value:    parseInt( columns, 10 ) || D.columns,
                            min:      1,
                            max:      12,
                            step:     1,
                            onChange: function ( v ) { set( { columns: v } ); },
                        } ),

                        el( SelectControl, {
                            label:    'Tamaño de imagen',
                            value:    imageSize,
                            options:  [
                                { value: 'thumbnail', label: 'Thumbnail (~150px)' },
                                { value: 'medium',    label: 'Medium (~300px)'    },
                                { value: 'large',     label: 'Large (~1024px)'    },
                                { value: 'full',      label: 'Full (original)'    },
                            ],
                            onChange: function ( v ) { set( { imageSize: v } ); },
                        } ),

                        el( SelectControl, {
                            label:    'Aspect ratio',
                            value:    aspectRatio,
                            options:  [
                                { value: 'square',    label: 'Square (1:1)'        },
                                { value: 'portrait',  label: 'Portrait (2:3)'      },
                                { value: 'landscape', label: 'Landscape (16:9)'    },
                                { value: 'original',  label: 'Original (sin recorte)' },
                            ],
                            onChange: function ( v ) { set( { aspectRatio: v } ); },
                        } ),

                        el( SelectControl, {
                            label:    'Comportamiento de enlace',
                            value:    linkTo,
                            options:  [
                                { value: 'none',       label: 'Sin enlace'        },
                                { value: 'lightbox',   label: 'Lightbox'          },
                                { value: 'file',       label: 'Archivo de media'  },
                                { value: 'attachment', label: 'Página de adjunto' },
                            ],
                            onChange: function ( v ) { set( { linkTo: v } ); },
                        } ),

                        ( linkTo === 'file' || linkTo === 'attachment' ) &&
                        el( ToggleControl, {
                            label:    'Abrir en nueva pestaña',
                            checked:  attrs.targetBlank,
                            onChange: function ( v ) { set( { targetBlank: v } ); },
                        } )
                    ),

                    // ── Panel: Títulos ───────────────────────────────────────
                    el( PanelBody, { title: 'Títulos', initialOpen: false },

                        el( TextControl, {
                            label:    'Título SFW',
                            value:    sfwTitle,
                            placeholder: D.sfw_title || 'Ej: Galería de imágenes',
                            onChange: function ( v ) { set( { sfwTitle: v } ); },
                        } ),

                        el( TextControl, {
                            label:    'Título NSFW',
                            value:    nsfwTitle,
                            placeholder: D.nsfw_title || 'Ej: Contenido para adultos',
                            onChange: function ( v ) { set( { nsfwTitle: v } ); },
                        } ),

                        el( ToggleControl, {
                            label:    'Mostrar título',
                            checked:  showTitle,
                            onChange: function ( v ) { set( { showTitle: v } ); },
                        } )
                    )
                ),

                // MediaUpload
                el( MediaUpload, {
                    onSelect: function ( media ) {
                        var ids  = media.map( function ( i ) { return i.id; } );
                        var data = media.map( function ( i ) {
                            return { id: i.id, url: i.url, alt: i.alt || '' };
                        } );
                        set( { images: ids, imageData: data } );
                        setResolved( data );
                    },
                    allowedTypes: [ 'image' ],
                    multiple:     true,
                    gallery:      true,
                    value:        images,
                    render: function ( obj ) {
                        return images.length === 0
                            ? renderPlaceholder( obj.open )
                            : renderGrid( obj.open );
                    },
                } )
            );
        },

        save: function () { return null; },
    } );

} )();

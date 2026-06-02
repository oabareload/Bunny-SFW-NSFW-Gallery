/* jshint esversion: 6 */
/**
 * Bunny Content Section — block.js
 * @since 0.5.0
 */

( function () {
    'use strict';

    var el                = wp.element.createElement;
    var registerBlock     = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var MediaUpload       = wp.blockEditor.MediaUpload;
    var RichText          = wp.blockEditor.RichText;
    var useBlockProps     = wp.blockEditor.useBlockProps;

    var Button        = wp.components.Button;
    var PanelBody     = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;

    var useEffect = wp.element.useEffect;

    // -------------------------------------------------------------------------
    // TAMAÑOS DE IMAGEN — dinámicos desde wp.media
    // -------------------------------------------------------------------------

    function getImageSizeOptions() {
        // wp.media.view.settings.data.sizes no siempre existe antes de abrir el modal.
        // Usamos la lista que WordPress expone en wp_prepare_attachment_for_js via
        // window._wpMediaViewsL10n o directamente de wp.media si ya se inicializó.
        var knownSizes = [];

        // Intentar obtener de un attachment ya cargado en el store de wp.media
        try {
            var attachments = wp.media.model.Attachments.all;
            if ( attachments && attachments.length ) {
                var first = attachments.first();
                if ( first ) {
                    var sizes = first.get( 'sizes' ) || {};
                    Object.keys( sizes ).forEach( function ( key ) {
                        knownSizes.push( key );
                    } );
                }
            }
        } catch ( e ) {}

        // Fallback garantizado con tamaños estándar de WordPress
        var defaults = [ 'thumbnail', 'medium', 'medium_large', 'large', 'full' ];
        defaults.forEach( function ( s ) {
            if ( knownSizes.indexOf( s ) === -1 ) knownSizes.push( s );
        } );

        // Etiquetas legibles
        var labels = {
            thumbnail:    'Thumbnail (~150px)',
            medium:       'Medium (~300px)',
            medium_large: 'Medium Large (~768px)',
            large:        'Large (~1024px)',
            '1536x1536':  '1536×1536',
            '2048x2048':  '2048×2048',
            full:         'Full (original)',
        };

        return knownSizes.map( function ( key ) {
            return { value: key, label: labels[ key ] || key };
        } );
    }

    // Ancho de columna imagen → ratio CSS grid
    var WIDTH_OPTIONS = [
        { value: '25', label: '25% imagen / 75% texto' },
        { value: '33', label: '33% imagen / 67% texto' },
        { value: '40', label: '40% imagen / 60% texto' },
        { value: '50', label: '50% imagen / 50% texto' },
    ];

    function imageWidthToGrid( pct ) {
        var p = parseInt( pct, 10 ) || 33;
        var rest = 100 - p;
        return p + 'fr ' + rest + 'fr';  // aproximación visual en el editor
    }

    // -------------------------------------------------------------------------
    // REGISTRO
    // -------------------------------------------------------------------------

    registerBlock( 'bunny/content-section', {

        apiVersion: 3,
        title:    'Bunny Content Section',
        icon:     'align-pull-left',
        category: 'media',

        attributes: {
            imageId:       { type: 'number',  default: 0     },
            imageUrl:      { type: 'string',  default: ''    },
            imageSize:     { type: 'string',  default: 'large' },
            imageHeight:   { type: 'string',  default: 'medium' },
            imageWidth:    { type: 'string',  default: '33'  },
            imagePosition: { type: 'string',  default: 'left' },
            title:         { type: 'string',  default: ''    },
            content:       { type: 'string',  default: ''    },
            showTitle:     { type: 'boolean', default: true  },
            lightbox:      { type: 'boolean', default: true  },
        },

        // ----------------------------------------------------------------------
        // EDIT
        // ----------------------------------------------------------------------

        edit: function ( props ) {

            var attrs = props.attributes;
            var set   = props.setAttributes;

            var imageId       = attrs.imageId;
            var imageUrl      = attrs.imageUrl;
            var imageSize     = attrs.imageSize;
            var imageHeight   = attrs.imageHeight;
            var imageWidth    = attrs.imageWidth  || '33';
            var imagePosition = attrs.imagePosition;
            var title         = attrs.title;
            var content       = attrs.content;
            var showTitle     = attrs.showTitle !== false;
            var lightbox      = attrs.lightbox !== false;

            var heightMap   = { small: '200px', medium: '320px', large: '480px' };
            var imgHeightPx = heightMap[ imageHeight ] || '320px';
            var isLeft      = imagePosition === 'left';

            // Tamaños dinámicos
            var sizeOptions = getImageSizeOptions();

            // Resolver URL cuando cambia imageId o imageSize
            useEffect( function () {
                if ( ! imageId ) { set( { imageUrl: '' } ); return; }
                var att = wp.media.attachment( imageId );
                function onReady() {
                    var sizes = att.get( 'sizes' ) || {};
                    var url   = ( sizes[ imageSize ] && sizes[ imageSize ].url ) ||
                                att.get( 'url' ) ||
                                ( sizes.large  && sizes.large.url  ) ||
                                ( sizes.medium && sizes.medium.url ) || '';
                    set( { imageUrl: url } );
                }
                att.get( 'url' ) ? onReady() : att.fetch().then( onReady ).catch( onReady );
            }, [ imageId, imageSize ] );

            // ------------------------------------------------------------------
            // ESTILOS PREVIEW
            // ------------------------------------------------------------------

            var gridCols = imageWidthToGrid( imageWidth );
            var wrapStyle = {
                display:             'grid',
                gridTemplateColumns: isLeft ? gridCols : gridCols.split( ' ' ).reverse().join( ' ' ),
                gap:                 '24px',
                alignItems:          'start',
            };

            var imgColStyle = {
                gridColumn: isLeft ? '1' : '2',
                gridRow:    '1',
            };

            var imgWrapStyle = {
                overflow:     'hidden',
                borderRadius: '8px',
                background:   '#f0f0f0',
                height:       imgHeightPx,
                position:     'relative',
            };

            var imgStyle = {
                width:      '100%',
                height:     '100%',
                objectFit:  'cover',
                display:    'block',
                cursor:     lightbox ? 'zoom-in' : 'default',
            };

            var textStyle = {
                gridColumn: isLeft ? '2' : '1',
                gridRow:    '1',
            };

            // ------------------------------------------------------------------
            // INSPECTOR CONTROLS
            // ------------------------------------------------------------------

            var blockProps = useBlockProps();

            return el( 'div', blockProps,

                el( InspectorControls, {},

                    el( PanelBody, { title: 'Imagen', initialOpen: true },

                        el( SelectControl, {
                            label:    'Tamaño de imagen',
                            value:    imageSize,
                            options:  sizeOptions,
                            onChange: function ( v ) { set( { imageSize: v } ); },
                        } ),

                        el( SelectControl, {
                            label:    'Altura visual',
                            value:    imageHeight,
                            options:  [
                                { value: 'small',  label: 'Small (200px)'  },
                                { value: 'medium', label: 'Medium (320px)' },
                                { value: 'large',  label: 'Large (480px)'  },
                            ],
                            onChange: function ( v ) { set( { imageHeight: v } ); },
                        } ),

                        el( SelectControl, {
                            label:    'Ancho de imagen',
                            value:    imageWidth,
                            options:  WIDTH_OPTIONS,
                            onChange: function ( v ) { set( { imageWidth: v } ); },
                        } ),

                        el( SelectControl, {
                            label:    'Posición de imagen',
                            value:    imagePosition,
                            options:  [
                                { value: 'left',  label: 'Izquierda' },
                                { value: 'right', label: 'Derecha'   },
                            ],
                            onChange: function ( v ) { set( { imagePosition: v } ); },
                        } ),

                        el( ToggleControl, {
                            label:    'Abrir en lightbox',
                            checked:  lightbox,
                            onChange: function ( v ) { set( { lightbox: v } ); },
                        } )
                    ),

                    el( PanelBody, { title: 'Texto', initialOpen: false },

                        el( ToggleControl, {
                            label:    'Mostrar título',
                            checked:  showTitle,
                            onChange: function ( v ) { set( { showTitle: v } ); },
                        } )
                    )
                ),

                // --------------------------------------------------------------
                // PREVIEW LAYOUT
                // --------------------------------------------------------------

                el( 'div', { style: wrapStyle },

                    // ── Columna imagen ──────────────────────────────────────
                    el( 'div', { style: imgColStyle },

                        imageUrl
                            // Imagen seleccionada
                            ? el( 'div', {},
                                el( 'div', { style: imgWrapStyle },
                                    el( 'img', { src: imageUrl, alt: '', style: imgStyle } )
                                ),
                                // Botón "Cambiar imagen" debajo de la imagen, fuera del wrap
                                el( MediaUpload, {
                                    onSelect: function ( media ) {
                                        set( { imageId: media.id, imageUrl: media.url } );
                                    },
                                    allowedTypes: [ 'image' ],
                                    multiple:     false,
                                    value:        imageId,
                                    render: function ( obj ) {
                                        return el( Button, {
                                            variant: 'tertiary',
                                            onClick: obj.open,
                                            style:   {
                                                display:    'block',
                                                marginTop:  '6px',
                                                fontSize:   '11px',
                                                width:      '100%',
                                                textAlign:  'center',
                                            },
                                        }, 'Cambiar imagen' );
                                    },
                                } )
                              )
                            // Placeholder — sin imagen seleccionada
                            : el( MediaUpload, {
                                onSelect: function ( media ) {
                                    set( { imageId: media.id, imageUrl: media.url } );
                                },
                                allowedTypes: [ 'image' ],
                                multiple:     false,
                                value:        imageId,
                                render: function ( obj ) {
                                    return el( 'div', {
                                        style: {
                                            display:        'flex',
                                            flexDirection:  'column',
                                            alignItems:     'center',
                                            justifyContent: 'center',
                                            height:         imgHeightPx,
                                            gap:            '8px',
                                            cursor:         'pointer',
                                            background:     '#f0f0f0',
                                            borderRadius:   '8px',
                                        },
                                        onClick: obj.open,
                                    },
                                        el( 'span', {
                                            className: 'dashicons dashicons-format-image',
                                            style: { fontSize: '36px', width: '36px', height: '36px', color: '#a0a5aa' },
                                        } ),
                                        el( Button, { variant: 'primary', onClick: obj.open }, 'Seleccionar imagen' )
                                    );
                                },
                            } )
                    ),

                    // ── Columna texto ───────────────────────────────────────
                    el( 'div', { style: textStyle },

                        showTitle && el( RichText, {
                            tagName:     'h2',
                            className:   'bunny-cs-title',
                            value:       title,
                            onChange:    function ( v ) { set( { title: v } ); },
                            placeholder: 'Título de la sección…',
                        } ),

                        el( RichText, {
                            tagName:        'div',
                            className:      'bunny-cs-content',
                            multiline:      'p',
                            value:          content,
                            onChange:       function ( v ) { set( { content: v } ); },
                            placeholder:    'Escribe el contenido aquí…',
                            allowedFormats: [
                                'core/bold',
                                'core/italic',
                                'core/link',
                                'core/list',
                                'core/list-item',
                            ],
                        } )
                    )
                )
            );
        },

        save: function () { return null; },
    } );

} )();

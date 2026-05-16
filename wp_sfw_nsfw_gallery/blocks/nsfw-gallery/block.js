/* jshint esversion: 6 */
/**
 * Bunny SFW&NSFW Gallery — block.js  (editor Gutenberg, sin JSX)
 *
 * Lee los defaults del plugin desde window.bunnyGalleryDefaults
 * (inyectado por wp_localize_script) para pre-rellenar atributos
 * en bloques nuevos que aún no tienen valor propio guardado.
 *
 * @since 0.2.0
 */

(function () {
    'use strict';

    var el             = wp.element.createElement;
    var registerBlock  = wp.blocks.registerBlockType;
    var MediaUpload    = wp.blockEditor.MediaUpload;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var BlockControls  = wp.blockEditor.BlockControls;

    var Button         = wp.components.Button;
    var PanelBody      = wp.components.PanelBody;
    var ToggleControl  = wp.components.ToggleControl;
    var TextControl    = wp.components.TextControl;
    var SelectControl  = wp.components.SelectControl;
    var ToolbarGroup   = wp.components.ToolbarGroup;
    var ToolbarButton  = wp.components.ToolbarButton;
    var Spinner        = wp.components.Spinner;

    var useState   = wp.element.useState;
    var useEffect  = wp.element.useEffect;

    // -------------------------------------------------------------------------
    // DEFAULTS DEL PLUGIN
    // Inyectados por wp_localize_script como window.bunnyGalleryDefaults.
    // Fallback local si por algún motivo no están disponibles.
    // -------------------------------------------------------------------------

    var pluginDefaults = window.bunnyGalleryDefaults || {
        columns:       3,
        blur:          true,
        link_behavior: 'none',
        target_blank:  false,
        nsfw_message:  'Este contenido es solo para adultos.'
    };

    // -------------------------------------------------------------------------
    // REGISTRO DEL BLOQUE
    // -------------------------------------------------------------------------

    registerBlock( 'bunny/nsfw-gallery', {

        title:    'Bunny Gallery',
        icon:     'format-gallery',
        category: 'media',

        attributes: {
            images:      { type: 'array',   default: [] },
            imageData:   { type: 'array',   default: [] },
            mode:        { type: 'string',  default: 'sfw' },
            message:     { type: 'string',  default: pluginDefaults.nsfw_message  },
            columns:     { type: 'number',  default: pluginDefaults.columns       },
            linkTo:      { type: 'string',  default: pluginDefaults.link_behavior },
            blur:        { type: 'boolean', default: !! pluginDefaults.blur       },
            targetBlank: { type: 'boolean', default: !! pluginDefaults.target_blank }
        },

        // ----------------------------------------------------------------------
        // EDIT
        // ----------------------------------------------------------------------

        edit: function ( props ) {

            var attrs = props.attributes;
            var set   = props.setAttributes;

            var images    = attrs.images;
            var imageData = attrs.imageData;
            var mode      = attrs.mode;
            var message   = attrs.message;
            var columns   = attrs.columns;
            var linkTo    = attrs.linkTo;
            var blur      = attrs.blur;

            var resolvedState = useState( imageData || [] );
            var resolvedImages = resolvedState[0];
            var setResolved    = resolvedState[1];

            var loadingState = useState( false );
            var loading      = loadingState[0];
            var setLoading   = loadingState[1];

            // Resolver URLs cuando cambia la lista de IDs
            useEffect( function () {

                if ( ! images || images.length === 0 ) {
                    setResolved( [] );
                    return;
                }

                // Usar cache si los IDs coinciden exactamente
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
                    var attachment = wp.media.attachment( id );

                    function onReady() {
                        var sizes = attachment.get( 'sizes' ) || {};
                        var url   = attachment.get( 'url' ) ||
                                    ( sizes.large      && sizes.large.url  ) ||
                                    ( sizes.medium     && sizes.medium.url ) ||
                                    ( sizes.full       && sizes.full.url   ) || '';
                        resolved.push( { id: id, url: url, alt: attachment.get( 'alt' ) || '' } );
                        pending--;
                        if ( pending === 0 ) {
                            resolved.sort( function ( a, b ) {
                                return images.indexOf( a.id ) - images.indexOf( b.id );
                            } );
                            setResolved( resolved );
                            set( { imageData: resolved } );
                            setLoading( false );
                        }
                    }

                    if ( attachment.get( 'url' ) ) {
                        onReady();
                    } else {
                        attachment.fetch().then( onReady ).catch( onReady );
                    }
                } );

            }, [ images ] );

            // ------------------------------------------------------------------
            // ESTILOS INLINE
            // ------------------------------------------------------------------

            var gridStyle = {
                display:             'grid',
                gridTemplateColumns: 'repeat(' + columns + ', 1fr)',
                gap:                 '8px'
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
                background:    mode === 'nsfw' ? '#cc1818' : '#1d8348',
                color:         '#fff'
            };

            // ------------------------------------------------------------------
            // PLACEHOLDER (sin imágenes)
            // ------------------------------------------------------------------

            function renderPlaceholder( open ) {
                return el( 'div', {
                    style: {
                        display:        'flex',
                        flexDirection:  'column',
                        alignItems:     'center',
                        justifyContent: 'center',
                        gap:            '12px',
                        minHeight:      '160px',
                        border:         '2px dashed #c3c4c7',
                        borderRadius:   '4px',
                        padding:        '32px',
                        background:     '#f6f7f7',
                        cursor:         'pointer',
                        boxSizing:      'border-box'
                    },
                    onClick: open
                },
                    el( 'span', {
                        className: 'dashicons dashicons-format-gallery',
                        style: { fontSize: '48px', width: '48px', height: '48px', color: '#a0a5aa' }
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
                            el( ToolbarButton, {
                                icon:    'edit',
                                label:   'Editar galería',
                                onClick: open
                            } )
                        )
                    ),

                    // Cabecera
                    el( 'div', {
                        style: {
                            display:        'flex',
                            alignItems:     'center',
                            justifyContent: 'space-between',
                            marginBottom:   '10px'
                        }
                    },
                        el( 'span', { style: badgeStyle }, mode === 'nsfw' ? 'NSFW' : 'SFW' ),
                        el( 'span', { style: { fontSize: '12px', color: '#757575' } },
                            resolvedImages.length + ' imagen' + ( resolvedImages.length !== 1 ? 'es' : '' ) +
                            '  |  ' + columns + ' col  |  ' + linkTo
                        )
                    ),

                    // Grid de imágenes
                    loading
                        ? el( 'div', { style: { textAlign: 'center', padding: '32px' } }, el( Spinner ) )
                        : el( 'div', { style: gridStyle },
                            resolvedImages.map( function ( img ) {
                                return el( 'div', {
                                    key: img.id,
                                    style: {
                                        position:    'relative',
                                        borderRadius: '4px',
                                        overflow:    'hidden',
                                        aspectRatio: '1',
                                        background:  '#f0f0f0'
                                    }
                                },
                                    el( 'img', {
                                        src:   img.url,
                                        alt:   img.alt,
                                        style: {
                                            width:      '100%',
                                            height:     '100%',
                                            objectFit:  'cover',
                                            display:    'block',
                                            filter:     ( mode === 'nsfw' && blur ) ? 'blur(6px)' : 'none',
                                            transition: 'filter 0.2s'
                                        }
                                    } )
                                );
                            } )
                        ),

                    // Botón pie
                    el( 'div', { style: { marginTop: '10px', textAlign: 'right' } },
                        el( Button, { variant: 'secondary', onClick: open, style: { fontSize: '12px' } },
                            'Editar galería'
                        )
                    )
                );
            }

            // ------------------------------------------------------------------
            // INSPECTOR CONTROLS
            // ------------------------------------------------------------------

            return el( 'div', {},

                el( InspectorControls, {},

                    el( PanelBody, { title: 'NSFW Settings', initialOpen: true },

                        el( ToggleControl, {
                            label:    'Modo NSFW',
                            checked:  mode === 'nsfw',
                            onChange: function ( v ) { set( { mode: v ? 'nsfw' : 'sfw' } ); }
                        } ),

                        mode === 'nsfw' && el( TextControl, {
                            label:    'Mensaje overlay',
                            value:    message,
                            onChange: function ( v ) { set( { message: v } ); }
                        } ),

                        mode === 'nsfw' && el( ToggleControl, {
                            label:    'Blur en preview del editor',
                            checked:  blur,
                            onChange: function ( v ) { set( { blur: v } ); }
                        } )
                    ),

                    el( PanelBody, { title: 'Gallery Settings', initialOpen: false },

                        el( TextControl, {
                            label:    'Columnas',
                            type:     'number',
                            min:      1,
                            max:      6,
                            value:    columns,
                            onChange: function ( v ) { set( { columns: Math.max( 1, Math.min( 6, parseInt( v ) || pluginDefaults.columns ) ) } ); }
                        } ),

                        el( SelectControl, {
                            label:    'Comportamiento de enlace',
                            value:    linkTo,
                            options:  [
                                { value: 'none',       label: 'Sin enlace'               },
                                { value: 'lightbox',   label: 'Abrir en lightbox'        },
                                { value: 'file',       label: 'Archivo de media'         },
                                { value: 'attachment', label: 'Página de adjunto'        }
                            ],
                            onChange: function ( v ) { set( { linkTo: v } ); }
                        } ),

                        ( linkTo === 'file' || linkTo === 'attachment' ) &&
                        el( ToggleControl, {
                            label:    'Abrir en nueva pestaña',
                            checked:  attrs.targetBlank,
                            onChange: function ( v ) { set( { targetBlank: v } ); }
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
                    }
                } )
            );
        },

        save: function () {
            return null; // Bloque dinámico — render en PHP
        }
    } );

} )();

( function( wp ) {
  const { registerBlockType } = wp.blocks;
  const blockEditor = wp.blockEditor || wp.editor;
  const { InspectorControls, useBlockProps } = blockEditor;
  const { PanelBody, RangeControl, TextControl } = wp.components;

  registerBlockType('imperatore/tour-map', {
    attributes: {
      height: { type: 'number', default: 360 },
      width: { type: 'string', default: '100%' },
      borderRadius: { type: 'number', default: 18 }
    },

    edit: function(props) {
      const { attributes, setAttributes } = props;
      const height = typeof attributes.height === 'number' ? attributes.height : 360;
      const width = attributes.width || '100%';
      const borderRadius = typeof attributes.borderRadius === 'number' ? attributes.borderRadius : 18;

      return wp.element.createElement(
        wp.element.Fragment,
        null,
        wp.element.createElement(
          InspectorControls,
          null,
          wp.element.createElement(
            PanelBody,
            { title: 'Layout mappa', initialOpen: true },
            wp.element.createElement(TextControl, {
              label: 'Larghezza (CSS)',
              help: 'Esempi: 100%, 720px, 60vw',
              value: width,
              onChange: (v) => setAttributes({ width: v })
            }),
            wp.element.createElement(RangeControl, {
              label: 'Altezza (px)',
              min: 180,
              max: 900,
              value: height,
              onChange: (v) => setAttributes({ height: v })
            }),
            wp.element.createElement(RangeControl, {
              label: 'Raggio angoli (px)',
              min: 0,
              max: 40,
              value: borderRadius,
              onChange: (v) => setAttributes({ borderRadius: v })
            })
          )
        ),
        wp.element.createElement(
          'div',
          useBlockProps({
            className: 'imp-tour-map-block__preview',
            style: { width: width, height: height + 'px', borderRadius: borderRadius + 'px' }
          }),
          wp.element.createElement('div', { className: 'imp-tour-map-block__hint' }, 'Mappa Tour (anteprima). In frontend verrà renderizzata con i dati del tour.')
        )
      );
    },

    save: function(props) {
      const { attributes } = props;
      const height = typeof attributes.height === 'number' ? attributes.height : 360;
      const width = attributes.width || '100%';
      const borderRadius = typeof attributes.borderRadius === 'number' ? attributes.borderRadius : 18;

      const blockProps = (useBlockProps && useBlockProps.save) ? useBlockProps.save({ className: 'imp-tour-map-block' }) : { className: 'imp-tour-map-block' };

      return wp.element.createElement(
        'div',
        Object.assign({}, blockProps, {
          'data-imp-map': '1',
          style: {
            width: width,
            height: height + 'px',
            borderRadius: borderRadius + 'px',
            overflow: 'hidden'
          }
        })
      );
    }
  });
} )( window.wp );
( function( wp ) {
  const { registerBlockType } = wp.blocks;
  const blockEditor = wp.blockEditor || wp.editor;
  const { InspectorControls, useBlockProps } = blockEditor;
  const { PanelBody, TextControl, ToggleControl } = wp.components;

  registerBlockType('imperatore/tour-highlights-text', {
    attributes: {
      title: { type: 'string', default: 'Tour Highlights' },
      showTitle: { type: 'boolean', default: false }
    },
    edit: function(props) {
      const { attributes, setAttributes } = props;
      const blockProps = useBlockProps({ className: 'imp-highlights-text-block' });

      return wp.element.createElement(
        wp.element.Fragment,
        null,
        wp.element.createElement(
          InspectorControls,
          null,
          wp.element.createElement(
            PanelBody,
            { title: 'Impostazioni', initialOpen: true },
            wp.element.createElement(ToggleControl, {
              label: 'Mostra titolo',
              checked: !!attributes.showTitle,
              onChange: (v) => setAttributes({ showTitle: !!v })
            }),
            wp.element.createElement(TextControl, {
              label: 'Titolo',
              value: attributes.title || '',
              onChange: (v) => setAttributes({ title: v })
            })
          )
        ),
        wp.element.createElement(
          'div',
          blockProps,
          wp.element.createElement('div', { className: 'imp-highlights-text-block__hint' },
            'Mostra automaticamente i tag “Highlights” assegnati al tour (frontend).'
          )
        )
      );
    },
    save: function() {
      // Rendered server-side
      return null;
    }
  });
} )( window.wp );

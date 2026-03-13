( function( wp ) {
  const { registerBlockType } = wp.blocks;
  const blockEditor = wp.blockEditor || wp.editor;
  const { InspectorControls, BlockControls, useBlockProps } = blockEditor;
  const { PanelBody, RangeControl, ToolbarGroup, ToolbarButton, Popover } = wp.components;

  const ICON = (window.ImperatoreThemeBlocks && window.ImperatoreThemeBlocks.iconsBase)
    ? window.ImperatoreThemeBlocks.iconsBase + 'pillar-wellness.png'
    : '';

    registerBlockType('imperatore/pillar-wellness', {
    attributes: {
      percent: { type: 'number', default: 60 }
    },
    edit: function(props) {
      const { attributes, setAttributes } = props;
      const percent = typeof attributes.percent === 'number' ? attributes.percent : 0;
      const [isOpen, setOpen] = wp.element.useState(false);
      const blockProps = useBlockProps({ className: 'imp-pillar-block', 'data-key': 'wellness' });

      return wp.element.createElement(wp.element.Fragment, null,
        
        wp.element.createElement(
          BlockControls,
          { key: 'controls' },
          wp.element.createElement(
            ToolbarGroup,
            null,
            wp.element.createElement(ToolbarButton, {
              icon: 'edit',
              label: 'Modifica percentuale',
              onClick: () => setOpen(!isOpen)
            })
          )
        ),
        (isOpen ? wp.element.createElement(
          Popover,
          { position: 'middle center', onClose: () => setOpen(false), focusOnMount: 'firstElement' },
          wp.element.createElement('div', { style: { padding: '12px', width: '240px' } },
            wp.element.createElement(RangeControl, {
              label: 'Percentuale',
              value: percent,
              min: 0,
              max: 100,
              onChange: (v) => setAttributes({ percent: Number(v || 0) })
            })
          )
        ) : null),

        wp.element.createElement(
          InspectorControls,
          { key: 'inspector' },
          wp.element.createElement(
            PanelBody,
            { title: 'Impostazioni', initialOpen: true },
            wp.element.createElement(RangeControl, {
              label: 'Percentuale',
              value: percent,
              min: 0,
              max: 100,
              onChange: (v) => setAttributes({ percent: Number(v || 0) })
            })
          )
        ),

        wp.element.createElement(
          'div',
          Object.assign({ key: 'preview' }, blockProps),
          wp.element.createElement('div', { className: 'imp-pillar-block__icon' },
            ICON ? wp.element.createElement('img', { src: ICON, alt: '' }) : null
          ),
          wp.element.createElement('div', { className: 'imp-pillar-block__label' }, 'WELLNESS'),
          wp.element.createElement('div', { className: 'imp-pillar-block__pct' }, String(percent) + '%')
        )
      );
    },

    save: function(props) {
      const percent = typeof props.attributes.percent === 'number' ? props.attributes.percent : 0;
      return wp.element.createElement(
        'div',
        { className: 'imp-pillar-block', 'data-key': 'wellness', 'data-percent': String(percent) },
        wp.element.createElement('div', { className: 'imp-pillar-block__icon' },
          wp.element.createElement('img', { src: ICON, alt: '' })
        ),
        wp.element.createElement('div', { className: 'imp-pillar-block__label' }, 'WELLNESS'),
        wp.element.createElement('div', { className: 'imp-pillar-block__pct' }, String(percent) + '%')
      );
    }
  });
} )( window.wp );

( function( wp ) {
  const { registerBlockType } = wp.blocks;
  const { Button, TextControl, PanelBody } = wp.components;
  const { InspectorControls, RichText } = wp.blockEditor;

  function normalizeItems(items) {
    return Array.isArray(items) ? items : [];
  }

  registerBlockType('imperatore/itinerary', {
    edit: function(props) {
      const { attributes, setAttributes } = props;
      const items = normalizeItems(attributes.items);

      const updateItem = (index, patch) => {
        const next = items.map((it, i) => i === index ? Object.assign({}, it, patch) : it);
        setAttributes({ items: next });
      };

      const addItem = () => {
        const next = items.concat([{ day: String(items.length + 1), title: '', content: '' }]);
        setAttributes({ items: next });
      };

      const removeItem = (index) => {
        const next = items.filter((_, i) => i !== index);
        setAttributes({ items: next });
      };

      return [
        wp.element.createElement(
          InspectorControls,
          { key: 'inspector' },
          wp.element.createElement(
            PanelBody,
            { title: 'Impostazioni itinerario', initialOpen: true },
            wp.element.createElement(Button, { variant: 'primary', onClick: addItem }, '+ Aggiungi giorno'),
            wp.element.createElement('p', { style: { marginTop: '10px', fontSize: '12px', opacity: 0.8 } },
              'Suggerimento: puoi usare il campo "Giorno" anche come "1/4/8" se vuoi replicare il tuo stile.'
            )
          )
        ),

        wp.element.createElement(
          'div',
          { key: 'content', className: 'imp-itinerary-editor' },
          wp.element.createElement('div', { className: 'imp-itinerary-editor__head' },
            wp.element.createElement('div', { className: 'imp-itinerary-editor__title' }, 'Day-by-Day Itinerary'),
            wp.element.createElement(Button, { variant: 'secondary', onClick: addItem }, '+ Aggiungi giorno')
          ),
          items.length === 0 ?
            wp.element.createElement('p', { className: 'imp-itinerary-editor__empty' }, 'Nessun giorno inserito. Clicca “Aggiungi giorno”.') :
            items.map((it, index) => {
              const day = it.day || String(index + 1);
              return wp.element.createElement(
                'div',
                { key: index, className: 'imp-itinerary-item' },
                wp.element.createElement('div', { className: 'imp-itinerary-item__num' }, day),
                wp.element.createElement('div', { className: 'imp-itinerary-item__main' },
                  wp.element.createElement(TextControl, {
                    label: 'Titolo',
                    value: it.title || '',
                    onChange: (v) => updateItem(index, { title: v }),
                    placeholder: 'Es. Napoli e dintorni'
                  }),
                  wp.element.createElement('div', { className: 'imp-itinerary-item__content' },
                    wp.element.createElement(RichText, {
                      tagName: 'div',
                      value: it.content || '',
                      onChange: (v) => updateItem(index, { content: v }),
                      placeholder: 'Descrizione della giornata…'
                    })
                  ),
                  wp.element.createElement('div', { className: 'imp-itinerary-item__actions' },
                    wp.element.createElement(Button, { isDestructive: true, variant: 'tertiary', onClick: () => removeItem(index) }, 'Rimuovi')
                  )
                )
              );
            })
        )
      ];
    },

    save: function(props) {
      const items = Array.isArray(props.attributes.items) ? props.attributes.items : [];
      return wp.element.createElement(
        'section',
        { className: 'imp-itinerary' },
        wp.element.createElement('h2', { className: 'imp-itinerary__heading' }, 'Day-by-Day Itinerary'),
        wp.element.createElement(
          'div',
          { className: 'imp-itinerary__list' },
          items.map((it, index) => {
            const day = it.day || String(index + 1);
            return wp.element.createElement(
              'article',
              { key: index, className: 'imp-itinerary__card' },
              wp.element.createElement('div', { className: 'imp-itinerary__num' }, day),
              wp.element.createElement(
                'div',
                { className: 'imp-itinerary__body' },
                wp.element.createElement('h3', { className: 'imp-itinerary__title' }, it.title || ''),
                wp.element.createElement(wp.blockEditor.RichText.Content, { tagName: 'div', className: 'imp-itinerary__text', value: it.content || '' })
              )
            );
          })
        )
      );
    }
  });
} )( window.wp );

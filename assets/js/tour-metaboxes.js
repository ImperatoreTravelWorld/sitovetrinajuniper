jQuery(function($){
  var frame;

  function renderPreview(ids){
    var $wrap = $('#impTourGalleryPreview');
    $wrap.empty();
    if(!ids || !ids.length) return;

    ids.forEach(function(id){
      var attachment = wp.media.attachment(id);
      attachment.fetch().then(function(){
        var url = (attachment.get('sizes') && attachment.get('sizes').thumbnail) ? attachment.get('sizes').thumbnail.url : attachment.get('url');
        var $item = $('<span />').css({
          width:'48px',height:'48px',borderRadius:'6px',overflow:'hidden',border:'1px solid rgba(0,0,0,.12)',display:'inline-block'
        });
        $('<img />').attr('src', url).css({width:'100%',height:'100%',objectFit:'cover'}).appendTo($item);
        $wrap.append($item);
      });
    });
  }

  $('#impTourGalleryPick').on('click', function(e){
    e.preventDefault();

    if(frame){
      frame.open();
      return;
    }

    frame = wp.media({
      title: 'Select tour gallery images',
      button: { text: 'Use selected images' },
      multiple: true
    });

    frame.on('select', function(){
      var selection = frame.state().get('selection').toJSON();
      var ids = selection.map(function(att){ return att.id; });
      $('#impTourGalleryIds').val(ids.join(','));
      renderPreview(ids);
    });

    frame.open();
  });

  $('#impTourGalleryClear').on('click', function(e){
    e.preventDefault();
    $('#impTourGalleryIds').val('');
    $('#impTourGalleryPreview').empty();
  });
});

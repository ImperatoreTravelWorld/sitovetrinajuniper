(function(){
  function initGallery(root){
    var track = root.querySelector('.imp-tour-gallery__track');
    var slides = Array.prototype.slice.call(root.querySelectorAll('.imp-tour-gallery__slide'));
    var prev = root.querySelector('.imp-tour-gallery__prev');
    var next = root.querySelector('.imp-tour-gallery__next');
    var dotsWrap = root.querySelector('.imp-tour-gallery__dots');
    if(!track || slides.length===0) return;

    var index = 0;

    function renderDots(){
      if(!dotsWrap) return;
      dotsWrap.innerHTML = '';
      slides.forEach(function(_, i){
        var b = document.createElement('button');
        b.type='button';
        b.className='imp-tour-gallery__dot' + (i===index ? ' is-active':'');
        b.setAttribute('aria-label', 'Go to slide ' + (i+1));
        b.addEventListener('click', function(){ go(i); });
        dotsWrap.appendChild(b);
      });
    }

    function go(i){
      index = (i + slides.length) % slides.length;
      track.style.transform = 'translateX(' + (-index*100) + '%)';
      if(dotsWrap){
        Array.prototype.forEach.call(dotsWrap.querySelectorAll('.imp-tour-gallery__dot'), function(d, di){
          if(di===index) d.classList.add('is-active'); else d.classList.remove('is-active');
        });
      }
    }

    prev && prev.addEventListener('click', function(){ go(index-1); });
    next && next.addEventListener('click', function(){ go(index+1); });

    // simple swipe
    var startX=null;
    track.addEventListener('touchstart', function(e){ startX=e.touches[0].clientX; }, {passive:true});
    track.addEventListener('touchend', function(e){
      if(startX===null) return;
      var dx = e.changedTouches[0].clientX - startX;
      startX=null;
      if(Math.abs(dx) > 40){
        if(dx<0) go(index+1); else go(index-1);
      }
    });

    renderDots();
    go(0);
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.imp-tour-gallery').forEach(initGallery);
  });
})();

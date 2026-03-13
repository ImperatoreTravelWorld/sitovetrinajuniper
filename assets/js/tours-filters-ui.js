(function(){
  function qsa(sel, ctx){ return Array.prototype.slice.call((ctx||document).querySelectorAll(sel)); }

  function setPanelHeight(group){
    var panel = group.querySelector('.imp-filter-group__panel');
    if(!panel) return;
    if(group.classList.contains('is-collapsed')){
      panel.style.maxHeight = '0px';
    }else{
      // set to scrollHeight for smooth transition
      panel.style.maxHeight = panel.scrollHeight + 'px';
    }
  }

  function updateActiveStates(form){
    if(!form) return;

    // option active
    qsa('.imp-check', form).forEach(function(lbl){
      var input = lbl.querySelector('input[type="checkbox"]');
      if(!input) return;
      if(input.checked) lbl.classList.add('is-active');
      else lbl.classList.remove('is-active');
    });

    // group active + count badge
    qsa('.imp-filter-group', form).forEach(function(group){
      var checks = qsa('input[type="checkbox"]', group).filter(function(i){ return i.checked; });
      var countEl = group.querySelector('.imp-filter-group__count');
      if(countEl){
        countEl.textContent = checks.length ? String(checks.length) : '';
        countEl.style.display = checks.length ? '' : 'none';
      }
      if(checks.length) group.classList.add('is-active');
      else group.classList.remove('is-active');
      setPanelHeight(group);
    });
  }

  function initAccordion(form){
    qsa('.imp-filter-group__head', form).forEach(function(head){
      // Only heads that are buttons
      head.addEventListener('click', function(){
        var group = head.closest('.imp-filter-group');
        if(!group) return;
        var isCollapsed = group.classList.toggle('is-collapsed');
        head.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        setPanelHeight(group);
      });
    });

    // initialize panel heights
    qsa('.imp-filter-group', form).forEach(function(group){
      var head = group.querySelector('.imp-filter-group__head');
      if(head && !head.hasAttribute('aria-expanded')){
        head.setAttribute('aria-expanded', group.classList.contains('is-collapsed') ? 'false' : 'true');
      }
      setPanelHeight(group);
    });

    // Recompute on resize
    window.addEventListener('resize', function(){
      qsa('.imp-filter-group', form).forEach(setPanelHeight);
    });
  }

  function initShowMore(){
    qsa('.imp-filter-toggle').forEach(function(btn){
      btn.addEventListener('click', function(){
        var group = btn.closest('.imp-filter-group');
        if(!group) return;
        var opts = qsa('[data-extra="1"]', group);
        var state = btn.getAttribute('data-state') || 'less';
        if(state === 'less'){
          opts.forEach(function(el){ el.style.display = ''; });
          btn.setAttribute('data-state','more');
          btn.textContent = 'Mostra meno';
        }else{
          opts.forEach(function(el){ el.style.display = 'none'; });
          btn.setAttribute('data-state','less');
          btn.textContent = 'Mostra altri';
        }
        setPanelHeight(group);
      });
    });
  }

  function initResetButtons(){
    qsa('.imp-reset-filters').forEach(function(rbtn){
      rbtn.addEventListener('click', function(){
        var form = rbtn.closest('form');
        if(!form) return;
        var target = form.getAttribute('data-reset-url') || form.getAttribute('action');
        window.location.href = target || window.location.pathname;
      });
    });
  }

  function initActiveTracking(){
    qsa('.imp-filter-form').forEach(function(form){
      updateActiveStates(form);
      form.addEventListener('change', function(e){
        if(e && e.target && e.target.matches && e.target.matches('input[type="checkbox"]')){
          updateActiveStates(form);
        }
      });
    });
  }

  function init(){
    initShowMore();
    initResetButtons();
    initActiveTracking();
    qsa('.imp-filter-form').forEach(function(form){
      initAccordion(form);
      updateActiveStates(form);
    });
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

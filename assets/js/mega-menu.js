(function(){
  function bindMega(){
    var header = document.querySelector('.imp-header');
    if(!header) return;

    var triggers = header.querySelectorAll('[data-mega-target]');
    var panels = header.querySelectorAll('.imp-mega');
    var activeId = null;
    var closeTimer = null;

    function showPanel(id){
      if(closeTimer){ clearTimeout(closeTimer); closeTimer = null; }
      activeId = id;
      panels.forEach(function(p){ p.style.display = (p.id === id) ? 'block' : 'none'; });
      // When a panel opens, (re)bind its 3-column behavior.
      var opened = document.getElementById(id);
      if(opened){
        initThreeColumn(opened);
      }
      triggers.forEach(function(t){
        var isActive = t.getAttribute('data-mega-target') === id;
        t.setAttribute('aria-expanded', isActive ? 'true' : 'false');
      });
    }

    function closeAll(){
      activeId = null;
      panels.forEach(function(p){ p.style.display = 'none'; });
      triggers.forEach(function(t){ t.setAttribute('aria-expanded','false'); });
    }

    function scheduleClose(){
      if(closeTimer) clearTimeout(closeTimer);
      closeTimer = setTimeout(closeAll, 180);
    }

    closeAll();

    triggers.forEach(function(t){
      var id = t.getAttribute('data-mega-target');
      var panel = document.getElementById(id);
      if(!panel) return;

      t.setAttribute('aria-haspopup', 'true');
      t.setAttribute('aria-expanded', 'false');

      t.addEventListener('mouseenter', function(){ showPanel(id); });
      t.addEventListener('focus', function(){ showPanel(id); });

      t.addEventListener('blur', function(){
        setTimeout(function(){
          var ae = document.activeElement;
          if(panel.contains(ae)) return;
          if(header.contains(ae)) return;
          scheduleClose();
        }, 0);
      });

      t.addEventListener('click', function(e){
        var href = t.getAttribute('href') || '';
        var isHash = href === '#' || href.trim() === '';
        if(isHash){
          e.preventDefault();
          if(activeId === id){ closeAll(); } else { showPanel(id); }
        }
      });

      panel.addEventListener('mouseleave', function(ev){
        var rt = ev.relatedTarget;
        if(rt && header.contains(rt)) return;
        scheduleClose();
      });

      panel.addEventListener('mouseenter', function(){
        if(closeTimer){ clearTimeout(closeTimer); closeTimer = null; }
      });
    });

    header.addEventListener('mouseleave', function(){ scheduleClose(); });
    header.addEventListener('mouseenter', function(){
      if(closeTimer){ clearTimeout(closeTimer); closeTimer = null; }
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') closeAll();
    });

    document.addEventListener('click', function(e){
      if(!activeId) return;
      if(header.contains(e.target)) return;
      closeAll();
    });
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', bindMega);
  } else {
    bindMega();
  }
})();

// -----------------------------
// 3-column mega behaviour
// -----------------------------
function initThreeColumn(panel){
  if(panel.__impThreeColBound) return;

  var tree = panel.querySelector('.imp-mega__treewrap .imp-mega__tree');
  var colsWrap = panel.querySelector('.imp-mega__cols');
  if(!tree || !colsWrap) return;

  var col1 = panel.querySelector('.imp-mega__col[data-col="1"]');
  var col2 = panel.querySelector('.imp-mega__col[data-col="2"]');
  var col3 = panel.querySelector('.imp-mega__col[data-col="3"]');
  if(!col1 || !col2 || !col3) return;

  function childrenOf(li){
    var ul = li.querySelector(':scope > ul.imp-mega__tree');
    if(!ul) return [];
    return Array.prototype.slice.call(ul.querySelectorAll(':scope > li.imp-mega__node'));
  }

  function linkOf(li){
    return li.querySelector(':scope > a.imp-mega__node-link');
  }

  function renderColumn(colEl, liNodes, activeNodeId){
    colEl.innerHTML = '';
    liNodes.forEach(function(li){
      var a = linkOf(li);
      if(!a) return;
      var item = document.createElement('a');
      item.className = 'imp-mega__item';
      item.href = a.getAttribute('href') || '#';
      item.textContent = a.textContent || '';
      var nodeId = li.getAttribute('data-node-id');
      item.setAttribute('data-node-id', nodeId);
      if(activeNodeId && nodeId === activeNodeId){
        item.classList.add('is-active');
      }
      colEl.appendChild(item);
    });
  }

  var level1 = Array.prototype.slice.call(tree.querySelectorAll(':scope > li.imp-mega__node'));
  if(!level1.length) return;

  // Default selection = first item of each level (matches screenshot behaviour).
  var active1 = level1[0];
  var level2 = childrenOf(active1);
  var active2 = level2[0] || null;
  var level3 = active2 ? childrenOf(active2) : [];

  renderColumn(col1, level1, active1.getAttribute('data-node-id'));
  renderColumn(col2, level2, active2 ? active2.getAttribute('data-node-id') : null);
  renderColumn(col3, level3, null);

  function setActiveInColumn(colEl, nodeId){
    Array.prototype.forEach.call(colEl.querySelectorAll('.imp-mega__item'), function(a){
      a.classList.toggle('is-active', a.getAttribute('data-node-id') === nodeId);
    });
  }

  // Event delegation for hover/focus.
  col1.addEventListener('mouseover', function(e){
    var t = e.target.closest('.imp-mega__item');
    if(!t) return;
    var nodeId = t.getAttribute('data-node-id');
    var li = tree.querySelector('li.imp-mega__node[data-node-id="' + nodeId + '"]');
    if(!li) return;
    active1 = li;
    setActiveInColumn(col1, nodeId);
    level2 = childrenOf(active1);
    active2 = level2[0] || null;
    level3 = active2 ? childrenOf(active2) : [];
    renderColumn(col2, level2, active2 ? active2.getAttribute('data-node-id') : null);
    renderColumn(col3, level3, null);
  });

  col1.addEventListener('focusin', function(e){
    var t = e.target.closest('.imp-mega__item');
    if(!t) return;
    t.dispatchEvent(new MouseEvent('mouseover', {bubbles:true}));
  });

  col2.addEventListener('mouseover', function(e){
    var t = e.target.closest('.imp-mega__item');
    if(!t) return;
    var nodeId = t.getAttribute('data-node-id');
    if(!active1) return;
    var li = active1.querySelector('li.imp-mega__node[data-node-id="' + nodeId + '"]');
    if(!li) return;
    active2 = li;
    setActiveInColumn(col2, nodeId);
    level3 = childrenOf(active2);
    renderColumn(col3, level3, null);
  });

  col2.addEventListener('focusin', function(e){
    var t = e.target.closest('.imp-mega__item');
    if(!t) return;
    t.dispatchEvent(new MouseEvent('mouseover', {bubbles:true}));
  });

  panel.__impThreeColBound = true;
}

// IMP_AUTOHEIGHT_MEGA: keep yellow band tall enough for visible nested submenus
(function () {
  const TOP_SELECTOR = '.kb-navigation > li.menu-item-has-children > ul.sub-menu.kb-nav-sub-menu';
  const HOST_LI_SELECTOR = '.kb-navigation > li.menu-item-has-children';
  const NESTED_SELECTOR = 'ul.sub-menu.kb-nav-sub-menu';

  function px(n) { return `${Math.max(0, Math.ceil(n))}px`; }

  function computeNeededHeight(topUl) {
    // base height (its own content, in case items wrap)
    let needed = topUl.scrollHeight;

    // Add visible nested submenus heights that extend beyond the top band's top edge.
    // We look for nested ULs that are currently displayed (block/flex).
    const nestedUls = topUl.querySelectorAll(`${NESTED_SELECTOR} ${NESTED_SELECTOR}`);
    nestedUls.forEach(ul => {
      const cs = window.getComputedStyle(ul);
      if (cs.display === 'none' || cs.visibility === 'hidden' || cs.opacity === '0') return;

      const rectTop = topUl.getBoundingClientRect();
      const rect = ul.getBoundingClientRect();

      // How much the nested menu extends below the top band top edge
      const extent = (rect.bottom - rectTop.top);
      if (extent > needed) needed = extent;
    });

    // Add a little breathing room for shadows/rounded pills
    needed += 24;
    return needed;
  }

  function setMinHeight(topUl, heightPx) {
    // Keep at least 50vh (CSS baseline). Here we set an explicit px min-height,
    // but only if it's larger than current.
    const current = parseFloat(topUl.dataset.impMinHeight || '0');
    if (heightPx > current) {
      topUl.style.minHeight = px(heightPx);
      topUl.dataset.impMinHeight = String(heightPx);
    }
  }

  function resetMinHeight(topUl) {
    topUl.style.minHeight = ''; // fallback to CSS 50vh
    delete topUl.dataset.impMinHeight;
  }

  function bind() {
    const hostLis = document.querySelectorAll(HOST_LI_SELECTOR);
    if (!hostLis.length) return;

    hostLis.forEach(hostLi => {
      const topUl = hostLi.querySelector(':scope > ul.sub-menu.kb-nav-sub-menu');
      if (!topUl) return;

      // When moving inside the mega band, keep updating height
      topUl.addEventListener('mouseenter', () => {
        // immediate measure
        requestAnimationFrame(() => setMinHeight(topUl, computeNeededHeight(topUl)));
      });

      topUl.addEventListener('mousemove', () => {
        // light update
        requestAnimationFrame(() => setMinHeight(topUl, computeNeededHeight(topUl)));
      });

      // When leaving the whole top item, reset
      hostLi.addEventListener('mouseleave', () => resetMinHeight(topUl));

      // Also when hovering items with children, update
      topUl.addEventListener('mouseover', (e) => {
        const li = e.target && e.target.closest && e.target.closest('li.menu-item.menu-item-has-children');
        if (!li || !topUl.contains(li)) return;
        requestAnimationFrame(() => setMinHeight(topUl, computeNeededHeight(topUl)));
      });

      // Resize recalculation
      window.addEventListener('resize', () => {
        if (window.getComputedStyle(topUl).display !== 'none') {
          resetMinHeight(topUl);
          requestAnimationFrame(() => setMinHeight(topUl, computeNeededHeight(topUl)));
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();

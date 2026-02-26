/* global L */
(function(){
  function initMap(container){
    const dataRaw = container.getAttribute('data-stops') || '[]';
    let stops = [];
    try { stops = JSON.parse(dataRaw) || []; } catch(e) { stops = []; }
    if (!stops.length || !window.L) return;

    const map = L.map(container, { zoomControl: true, scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const pts = stops.map(s => [s.lat, s.lng]);
    const line = L.polyline(pts, { weight: 2 }).addTo(map);
    map.fitBounds(line.getBounds(), { padding: [24, 24] });

    stops.forEach((s, idx) => {
      const num = (s.day && String(s.day).trim()) ? String(s.day).trim() : String(idx+1);
      const icon = L.divIcon({
        className: 'imp-map-marker',
        html: `<div class="imp-map-marker__dot"><span>${num}</span></div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
      });
      const label = s.city ? String(s.city) : '';
      L.marker([s.lat, s.lng], { icon }).addTo(map).bindTooltip(label, { direction: 'top' });
    });

    // Leaflet needs an explicit resize when containers are inside flex/grids or
    // when the page finishes layouting after load.
    setTimeout(() => { try { map.invalidateSize(); } catch(e) {} }, 50);
    window.addEventListener('load', () => { try { map.invalidateSize(); } catch(e) {} }, { once: true });
  }

  function boot(){
    document.querySelectorAll('.imp-tour-map').forEach(initMap);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();

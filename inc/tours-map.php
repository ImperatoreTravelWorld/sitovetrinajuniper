<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Tour Map builder (Leaflet + OpenStreetMap)
 *
 * Admin: repeatable stops (city, day label, lat, lng)
 * Frontend: renders a map with numbered markers and a route polyline.
 *
 * Meta key: _imp_map_stops_json (JSON array)
 */

function imp_tour_map_meta_key() {
  return '_imp_map_stops_json';
}

function imp_get_tour_map_stops($post_id) {
  $raw = get_post_meta($post_id, imp_tour_map_meta_key(), true);
  if (!$raw) return array();
  $arr = json_decode($raw, true);
  return is_array($arr) ? $arr : array();
}

function imp_add_tour_map_metabox() {
  add_meta_box(
    'imp_tour_map',
    __('Mappa itinerario (stops)', 'imperatore'),
    'imp_render_tour_map_metabox',
    'tour',
    'normal',
    'default'
  );
}
add_action('add_meta_boxes', 'imp_add_tour_map_metabox');

function imp_render_tour_map_metabox($post) {
  wp_nonce_field('imp_save_tour_map', 'imp_tour_map_nonce');
  $stops = imp_get_tour_map_stops($post->ID);
  if (!is_array($stops)) $stops = array();
  ?>
  <style>
    .imp-map-stops { width: 100%; border-collapse: collapse; }
    .imp-map-stops th, .imp-map-stops td { padding: 8px; border-bottom: 1px solid #e6e6e6; vertical-align: top; }
    .imp-map-stops input { width: 100%; }
    .imp-map-actions { display:flex; gap: 10px; margin-top: 10px; }
    .imp-map-preview { margin-top: 14px; height: 280px; border: 1px solid #e6e6e6; }
    .imp-small { font-size: 12px; color: #666; }
    .imp-btn-mini { padding: 4px 8px; border: 1px solid #ccc; background: #fff; cursor: pointer; }
  </style>

  <p class="imp-small">
    Inserisci le città (e/o coordinate). Per creare un percorso come nello screenshot: aggiungi gli stop in ordine (1,2,3...).
    Puoi cliccare “Geocodifica” per ottenere lat/lng dal nome città (usa OpenStreetMap Nominatim).
  </p>

  <table class="imp-map-stops" id="imp-map-stops-table">
    <thead>
      <tr>
        <th style="width:90px">Giorno / #</th>
        <th>Città</th>
        <th style="width:140px">Lat</th>
        <th style="width:140px">Lng</th>
        <th style="width:120px">Azioni</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($stops as $i => $s) :
        $day = isset($s['day']) ? $s['day'] : '';
        $city = isset($s['city']) ? $s['city'] : '';
        $lat = isset($s['lat']) ? $s['lat'] : '';
        $lng = isset($s['lng']) ? $s['lng'] : '';
      ?>
      <tr>
        <td><input type="text" name="imp_map_day[]" value="<?php echo esc_attr($day); ?>" placeholder="1" /></td>
        <td><input type="text" name="imp_map_city[]" value="<?php echo esc_attr($city); ?>" placeholder="Napoli" /></td>
        <td><input type="text" name="imp_map_lat[]" value="<?php echo esc_attr($lat); ?>" placeholder="40.8518" /></td>
        <td><input type="text" name="imp_map_lng[]" value="<?php echo esc_attr($lng); ?>" placeholder="14.2681" /></td>
        <td>
          <button type="button" class="imp-btn-mini imp-geocode">Geocodifica</button>
          <button type="button" class="imp-btn-mini imp-remove">Rimuovi</button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="imp-map-actions">
    <button type="button" class="button" id="imp-add-stop">+ Aggiungi stop</button>
    <button type="button" class="button" id="imp-preview-map">Anteprima mappa</button>
  </div>

  <div id="imp-map-preview" class="imp-map-preview"></div>

  <script>
  (function(){
    const table = document.getElementById('imp-map-stops-table').querySelector('tbody');
    const addBtn = document.getElementById('imp-add-stop');
    const previewBtn = document.getElementById('imp-preview-map');

    function rowHtml(){
      return `
      <tr>
        <td><input type="text" name="imp_map_day[]" value="" placeholder="1" /></td>
        <td><input type="text" name="imp_map_city[]" value="" placeholder="Napoli" /></td>
        <td><input type="text" name="imp_map_lat[]" value="" placeholder="40.8518" /></td>
        <td><input type="text" name="imp_map_lng[]" value="" placeholder="14.2681" /></td>
        <td>
          <button type="button" class="imp-btn-mini imp-geocode">Geocodifica</button>
          <button type="button" class="imp-btn-mini imp-remove">Rimuovi</button>
        </td>
      </tr>`;
    }

    addBtn.addEventListener('click', () => {
      table.insertAdjacentHTML('beforeend', rowHtml());
    });

    table.addEventListener('click', async (e) => {
      const btn = e.target;
      if (!(btn instanceof HTMLElement)) return;
      const tr = btn.closest('tr');
      if (!tr) return;

      if (btn.classList.contains('imp-remove')) {
        tr.remove();
      }

      if (btn.classList.contains('imp-geocode')) {
        const cityEl = tr.querySelector('input[name="imp_map_city[]"]');
        const latEl = tr.querySelector('input[name="imp_map_lat[]"]');
        const lngEl = tr.querySelector('input[name="imp_map_lng[]"]');
        const q = (cityEl && cityEl.value) ? cityEl.value.trim() : '';
        if (!q) { alert('Inserisci prima il nome città.'); return; }

        btn.textContent = '...';
        btn.disabled = true;

        try {
          // Nominatim usage: add a user-agent param via "email" is not possible here; keep light usage.
          const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q);
          const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
          const data = await res.json();
          if (data && data[0]) {
            latEl.value = data[0].lat;
            lngEl.value = data[0].lon;
          } else {
            alert('Nessun risultato per: ' + q);
          }
        } catch(err) {
          alert('Errore geocoding: ' + (err && err.message ? err.message : err));
        } finally {
          btn.textContent = 'Geocodifica';
          btn.disabled = false;
        }
      }
    });

    // Simple preview using Leaflet loaded via admin enqueue (we load on demand from CDN if missing)
    async function ensureLeaflet(){
      if (window.L) return;
      await new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        link.onload = resolve;
        link.onerror = reject;
        document.head.appendChild(link);
      });
      await new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        s.onload = resolve;
        s.onerror = reject;
        document.body.appendChild(s);
      });
    }

    function readStops(){
      const rows = table.querySelectorAll('tr');
      const stops = [];
      rows.forEach(r => {
        const day = (r.querySelector('input[name="imp_map_day[]"]')||{}).value || '';
        const city = (r.querySelector('input[name="imp_map_city[]"]')||{}).value || '';
        const lat = parseFloat((r.querySelector('input[name="imp_map_lat[]"]')||{}).value || '');
        const lng = parseFloat((r.querySelector('input[name="imp_map_lng[]"]')||{}).value || '');
        if (!city && !(lat && lng)) return;
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        stops.push({ day, city, lat, lng });
      });
      return stops;
    }

    let map;
    previewBtn.addEventListener('click', async () => {
      const stops = readStops();
      if (!stops.length) { alert('Inserisci almeno uno stop con coordinate.'); return; }

      await ensureLeaflet();
      const el = document.getElementById('imp-map-preview');
      el.innerHTML = '';
      map = L.map(el, { zoomControl: true });
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
      }).addTo(map);

      const pts = stops.map(s => [s.lat, s.lng]);
      const line = L.polyline(pts, { weight: 2 }).addTo(map);
      map.fitBounds(line.getBounds(), { padding: [20, 20] });

      stops.forEach((s, idx) => {
        const num = (s.day && String(s.day).trim()) ? String(s.day).trim() : String(idx+1);
        const icon = L.divIcon({
          className: 'imp-map-marker',
          html: `<div style="width:28px;height:28px;border-radius:999px;background:#1b4c75;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;border:3px solid #f2bf3a;">${num}</div>`,
          iconSize: [28, 28],
          iconAnchor: [14, 14]
        });
        L.marker([s.lat, s.lng], { icon }).addTo(map).bindTooltip(s.city || '', { direction: 'top' });
      });
    });
  })();
  </script>
  <?php
}

function imp_save_tour_map_metabox($post_id) {
  if ( ! isset($_POST['imp_tour_map_nonce']) || ! wp_verify_nonce($_POST['imp_tour_map_nonce'], 'imp_save_tour_map') ) return;
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
  if ( ! current_user_can('edit_post', $post_id) ) return;
  if ( get_post_type($post_id) !== 'tour' ) return;

  $days = isset($_POST['imp_map_day']) ? (array) wp_unslash($_POST['imp_map_day']) : array();
  $cities = isset($_POST['imp_map_city']) ? (array) wp_unslash($_POST['imp_map_city']) : array();
  $lats = isset($_POST['imp_map_lat']) ? (array) wp_unslash($_POST['imp_map_lat']) : array();
  $lngs = isset($_POST['imp_map_lng']) ? (array) wp_unslash($_POST['imp_map_lng']) : array();

  $stops = array();
  $n = max(count($cities), count($lats), count($lngs), count($days));

  for ($i=0; $i<$n; $i++) {
    $day = isset($days[$i]) ? sanitize_text_field($days[$i]) : '';
    $city = isset($cities[$i]) ? sanitize_text_field($cities[$i]) : '';
    $lat = isset($lats[$i]) ? preg_replace('/[^0-9\.\-]/', '', $lats[$i]) : '';
    $lng = isset($lngs[$i]) ? preg_replace('/[^0-9\.\-]/', '', $lngs[$i]) : '';

    if ($city === '' && $lat === '' && $lng === '') continue;
    if ($lat === '' || $lng === '') continue;

    $stops[] = array(
      'day' => $day,
      'city' => $city,
      'lat' => (float) $lat,
      'lng' => (float) $lng,
    );
  }

  update_post_meta($post_id, imp_tour_map_meta_key(), wp_json_encode($stops));
}
add_action('save_post', 'imp_save_tour_map_metabox');

/**
 * Frontend: enqueue Leaflet only when needed (single tour has map)
 */
function imp_enqueue_leaflet_if_needed() {
  if ( is_singular('tour') ) {
    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
    wp_enqueue_script('imp-tour-map', get_template_directory_uri() . '/assets/js/tour-map.js', array('leaflet'), IMP_THEME_VERSION, true);
  }
}
add_action('wp_enqueue_scripts', 'imp_enqueue_leaflet_if_needed');


/**
 * Render Gutenberg block imperatore/tour-map on frontend.
 * The saved markup is a wrapper div with data-imp-map="1".
 */
add_filter('render_block', function($block_content, $block) {
  if ( empty($block['blockName']) || $block['blockName'] !== 'imperatore/tour-map' ) {
    return $block_content;
  }
  if ( ! is_singular('tour') ) {
    return $block_content;
  }
  $post_id = get_the_ID();
  $stops = imp_get_tour_map_stops($post_id);
  if (!is_array($stops) || empty($stops)) {
    return '';
  }
  $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : array();
  $height = isset($attrs['height']) ? intval($attrs['height']) : 360;
  if ($height < 180) $height = 180;
  $width = isset($attrs['width']) ? (string)$attrs['width'] : '100%';
  $radius = isset($attrs['borderRadius']) ? intval($attrs['borderRadius']) : 18;
  if ($radius < 0) $radius = 0;

  ob_start(); ?>
  <div class="imp-tour-map-wrap" style="width:<?php echo esc_attr($width); ?>;height:<?php echo esc_attr($height); ?>px;border-radius:<?php echo esc_attr($radius); ?>px;overflow:hidden;">
    <div class="imp-tour-map" data-stops='<?php echo esc_attr(wp_json_encode($stops)); ?>'></div>
  </div>
  <?php
  return ob_get_clean();
}, 10, 2);

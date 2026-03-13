<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Tour metaboxes (Gallery + Departure Dates).
 * Gallery meta: imp_gallery_ids (array of attachment IDs)
 * CDN folder meta: imp_cdn_folder_url (string)
 * Departures meta: imp_departure_dates (JSON string)
 */

function imp_tour_add_metaboxes() {
  add_meta_box(
    'imp_tour_gallery',
    __('Tour Gallery', 'imperatore'),
    'imp_tour_gallery_metabox_cb',
    'tour',
    'side',
    'default'
  );

  add_meta_box(
    'imp_tour_departures',
    __('Departure Dates', 'imperatore'),
    'imp_tour_departures_metabox_cb',
    'tour',
    'normal',
    'default'
  );
}
add_action('add_meta_boxes', 'imp_tour_add_metaboxes');

function imp_tour_gallery_metabox_cb($post) {
  wp_nonce_field('imp_tour_gallery_save', 'imp_tour_gallery_nonce');

  $ids = get_post_meta($post->ID, 'imp_gallery_ids', true);
  if ( ! is_array($ids) ) $ids = array();

  $cdn = get_post_meta($post->ID, 'imp_cdn_folder_url', true);
  $cdn = is_string($cdn) ? $cdn : '';

  // Pillars values
  $pillar_cultura = get_post_meta($post->ID, 'imp_pillar_cultura', true);
  $pillar_enogastro = get_post_meta($post->ID, 'imp_pillar_enogastro', true);
  $pillar_natura = get_post_meta($post->ID, 'imp_pillar_natura', true);
  $pillar_wellness = get_post_meta($post->ID, 'imp_pillar_wellness', true);

  echo '<p style="margin-top:0;">'.esc_html__('Select multiple images OR provide a CDN folder URL (preferred) to show a gallery on the Tour page.', 'imperatore').'</p>';

  // CDN folder URL
  echo '<p style="margin:10px 0 0;">'
    . '<label for="impCdnFolder" style="display:block;font-weight:600;margin-bottom:4px;">'.esc_html__('CDN Folder URL', 'imperatore').'</label>'
    . '<input type="url" id="impCdnFolder" name="imp_cdn_folder_url" style="width:100%;" placeholder="https://cdn.imperatore.it/gallery/Tour/CAMTURFELIXPW/" value="'.esc_attr($cdn).'" />'
    . '<span class="description" style="display:block;margin-top:4px;">'
    . esc_html__('Incolla la cartella CDN: il sito carica automaticamente tutte le immagini (ordine alfabetico). Se la CDN non consente il listing, aggiungi un manifest.json nella cartella.', 'imperatore')
    . '</span>'
    . '</p>';

  // Pillars / Highlights percentages
  echo '<div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(0,0,0,.08);">'
    . '<div style="font-weight:700;margin-bottom:6px;">'.esc_html__('Highlights (percentuali)', 'imperatore').'</div>'
    . '<div class="description" style="margin-bottom:10px;">'.esc_html__('Inserisci un numero (0-100). Se vuoto o 0, non verrà mostrato nelle schede tour.', 'imperatore').'</div>'
    . '<p style="margin:0 0 8px;">'
      . '<label style="display:block;font-weight:600;margin-bottom:4px;">'.esc_html__('Cultura', 'imperatore').'</label>'
      . '<input type="number" min="0" max="100" step="1" name="imp_pillar_cultura" style="width:100%;" value="'.esc_attr($pillar_cultura).'" placeholder="70" />'
    . '</p>'
    . '<p style="margin:0 0 8px;">'
      . '<label style="display:block;font-weight:600;margin-bottom:4px;">'.esc_html__('Esperienze Enogastronomiche', 'imperatore').'</label>'
      . '<input type="number" min="0" max="100" step="1" name="imp_pillar_enogastro" style="width:100%;" value="'.esc_attr($pillar_enogastro).'" placeholder="60" />'
    . '</p>'
    . '<p style="margin:0 0 8px;">'
      . '<label style="display:block;font-weight:600;margin-bottom:4px;">'.esc_html__('Natura & Outdoors', 'imperatore').'</label>'
      . '<input type="number" min="0" max="100" step="1" name="imp_pillar_natura" style="width:100%;" value="'.esc_attr($pillar_natura).'" placeholder="70" />'
    . '</p>'
    . '<p style="margin:0;">'
      . '<label style="display:block;font-weight:600;margin-bottom:4px;">'.esc_html__('Wellness', 'imperatore').'</label>'
      . '<input type="number" min="0" max="100" step="1" name="imp_pillar_wellness" style="width:100%;" value="'.esc_attr($pillar_wellness).'" placeholder="60" />'
    . '</p>'
  . '</div>';

  // WP media selection fallback
  echo '<div id="impTourGalleryPreview" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">';
  foreach ($ids as $id) {
    $thumb = wp_get_attachment_image_src(intval($id), 'thumbnail');
    if ($thumb) {
      echo '<span style="width:48px;height:48px;border-radius:6px;overflow:hidden;border:1px solid rgba(0,0,0,.12);display:inline-block;">'
        . '<img src="'.esc_url($thumb[0]).'" style="width:100%;height:100%;object-fit:cover;" />'
        . '</span>';
    }
  }
  echo '</div>';

  echo '<input type="hidden" id="impTourGalleryIds" name="imp_gallery_ids" value="'.esc_attr(implode(',', array_map('intval', $ids))).'" />';

  $hide = get_post_meta($post->ID, 'imp_hide_booking_box', true);
  echo '<p style="margin:10px 0 0;">'
    . '<label><input type="checkbox" name="imp_hide_booking_box" value="1" ' . checked($hide, '1', false) . ' /> '
    . esc_html__('Hide price/booking box on the Tour page', 'imperatore') . '</label>'
    . '</p>';

  echo '<p style="margin-bottom:0;margin-top:10px;">'
     . '<button type="button" class="button" id="impTourGalleryPick">'.esc_html__('Select images', 'imperatore').'</button> '
     . '<button type="button" class="button" id="impTourGalleryClear">'.esc_html__('Clear', 'imperatore').'</button>'
     . '</p>';
}

function imp_tour_departures_metabox_cb($post) {
  wp_nonce_field('imp_tour_departures_save', 'imp_tour_departures_nonce');

  $json = (string) get_post_meta($post->ID, 'imp_departure_dates', true);
  $rows = json_decode($json, true);
  if (!is_array($rows)) $rows = array();

  $text = "";
  foreach ($rows as $r) {
    if (!is_array($r)) continue;
    $date = isset($r['date']) ? $r['date'] : '';
    $spots = isset($r['spots']) ? intval($r['spots']) : 0;
    if ($date) $text .= $date . " | " . $spots . "\n";
  }

  echo '<p style="margin-top:0;">'.esc_html__('One per line: YYYY-MM-DD | seats', 'imperatore').'</p>';
  echo '<textarea name="imp_departures_text" rows="8" style="width:100%;font-family:monospace;">'.esc_textarea($text).'</textarea>';
}

function imp_parse_departures_text($text) {
  $lines = preg_split("/\r\n|\n|\r/", (string)$text);
  $out = array();

  foreach ($lines as $line) {
    $line = trim($line);
    if (!$line) continue;

    $parts = preg_split("/\s*[|,]\s*/", $line);
    $date = isset($parts[0]) ? sanitize_text_field($parts[0]) : '';
    $spots = isset($parts[1]) ? max(0, intval($parts[1])) : 0;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      $out[] = array('date' => $date, 'spots' => $spots);
    }
  }

  return $out;
}

function imp_tour_save_metaboxes($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  if (get_post_type($post_id) !== 'tour') return;

  if (isset($_POST['imp_tour_gallery_nonce']) && wp_verify_nonce($_POST['imp_tour_gallery_nonce'], 'imp_tour_gallery_save')) {
    $raw = isset($_POST['imp_gallery_ids']) ? sanitize_text_field(wp_unslash($_POST['imp_gallery_ids'])) : '';
    $ids = array();
    if ($raw) {
      foreach (explode(',', $raw) as $id) {
        $id = intval(trim($id));
        if ($id > 0) $ids[] = $id;
      }
    }
    update_post_meta($post_id, 'imp_gallery_ids', $ids);

    $cdn = isset($_POST['imp_cdn_folder_url']) ? esc_url_raw(wp_unslash($_POST['imp_cdn_folder_url'])) : '';
    update_post_meta($post_id, 'imp_cdn_folder_url', $cdn);

    $hide = isset($_POST['imp_hide_booking_box']) ? '1' : '0';
    update_post_meta($post_id, 'imp_hide_booking_box', $hide);

    // Pillars
    $pillar_keys = array(
      'imp_pillar_cultura',
      'imp_pillar_enogastro',
      'imp_pillar_natura',
      'imp_pillar_wellness',
    );
    foreach ($pillar_keys as $k) {
      $rawv = isset($_POST[$k]) ? wp_unslash($_POST[$k]) : '';
      $rawv = is_string($rawv) ? trim($rawv) : '';
      if ($rawv === '') {
        delete_post_meta($post_id, $k);
        continue;
      }
      $val = intval($rawv);
      $val = min(100, max(0, $val));
      if ($val <= 0) {
        delete_post_meta($post_id, $k);
      } else {
        update_post_meta($post_id, $k, (string)$val);
      }
    }
  }

  if (isset($_POST['imp_tour_departures_nonce']) && wp_verify_nonce($_POST['imp_tour_departures_nonce'], 'imp_tour_departures_save')) {
    $text = isset($_POST['imp_departures_text']) ? wp_unslash($_POST['imp_departures_text']) : '';
    $parsed = imp_parse_departures_text($text);
    update_post_meta($post_id, 'imp_departure_dates', wp_json_encode($parsed));
  }
}
add_action('save_post', 'imp_tour_save_metaboxes');

function imp_tour_metabox_admin_assets($hook) {
  if (!in_array($hook, array('post.php', 'post-new.php'), true)) return;
  $screen = get_current_screen();
  if (!$screen || $screen->post_type !== 'tour') return;

  wp_enqueue_media();
  wp_enqueue_script(
    'imp-tour-metaboxes',
    get_template_directory_uri() . '/assets/js/tour-metaboxes.js',
    array('jquery'),
    defined('IMP_THEME_VERSION') ? IMP_THEME_VERSION : '1.0.0',
    true
  );
}
add_action('admin_enqueue_scripts', 'imp_tour_metabox_admin_assets');

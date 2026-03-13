<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Simple metaboxes for Tours (no external plugins needed)
 * Fields:
 * - price_from (number)
 * - days (number)
 * - itinerary_url (url)
 * - quick_url (url)
 * - badge_text (string) e.g. "VACANZA SLOW"
 * - departure_start (date) (optional, for filtering by date)
 */

function imp_tour_meta_fields() {
  return array(
    'price_from' => array('label' => __('Prezzo da (€)', 'imperatore'), 'type' => 'number'),
    'days' => array('label' => __('Giorni', 'imperatore'), 'type' => 'number'),
    'itinerary_url' => array('label' => __('Link itinerario', 'imperatore'), 'type' => 'url'),
    'quick_url' => array('label' => __('Link "Scopri in modo rapido"', 'imperatore'), 'type' => 'url'),
    'badge_text' => array('label' => __('Badge (testo)', 'imperatore'), 'type' => 'text'),
    'departure_start' => array('label' => __('Data partenza (inizio)', 'imperatore'), 'type' => 'date'),
  );
}

function imp_add_tour_metabox() {
  add_meta_box(
    'imp_tour_details',
    __('Dettagli Tour', 'imperatore'),
    'imp_render_tour_metabox',
    'tour',
    'normal',
    'high'
  );
}
add_action('add_meta_boxes', 'imp_add_tour_metabox');

function imp_render_tour_metabox($post) {
  wp_nonce_field('imp_save_tour_meta', 'imp_tour_meta_nonce');
  $fields = imp_tour_meta_fields();

  echo '<table class="form-table" role="presentation">';
  foreach ($fields as $key => $cfg) {
    $val = get_post_meta($post->ID, '_imp_'.$key, true);
    $type = esc_attr($cfg['type']);
    echo '<tr>';
    echo '<th scope="row"><label for="imp_'.$key.'">'.esc_html($cfg['label']).'</label></th>';
    echo '<td>';
    echo '<input style="width:100%;max-width:420px" type="'.$type.'" id="imp_'.$key.'" name="imp_'.$key.'" value="'.esc_attr($val).'" />';
    if ($key === 'price_from') echo '<p class="description">'.esc_html__('Esempio: 1360 (senza simbolo €).', 'imperatore').'</p>';
    if ($key === 'days') echo '<p class="description">'.esc_html__('Esempio: 6', 'imperatore').'</p>';
    if ($key === 'departure_start') echo '<p class="description">'.esc_html__('Usata per il filtro "Data Partenza".', 'imperatore').'</p>';
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
}

function imp_save_tour_metabox($post_id) {
  if ( ! isset($_POST['imp_tour_meta_nonce']) || ! wp_verify_nonce($_POST['imp_tour_meta_nonce'], 'imp_save_tour_meta') ) return;
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
  if ( ! current_user_can('edit_post', $post_id) ) return;
  if ( get_post_type($post_id) !== 'tour' ) return;

  $fields = imp_tour_meta_fields();
  foreach ($fields as $key => $cfg) {
    $name = 'imp_'.$key;
    if ( ! isset($_POST[$name]) ) continue;
    $raw = wp_unslash($_POST[$name]);
    $val = $raw;

    switch ($cfg['type']) {
      case 'number':
        $val = preg_replace('/[^0-9.]/', '', $raw);
        break;
      case 'url':
        $val = esc_url_raw($raw);
        break;
      case 'date':
        // keep YYYY-MM-DD only
        $val = preg_replace('/[^0-9\-]/', '', $raw);
        break;
      default:
        $val = sanitize_text_field($raw);
    }

    update_post_meta($post_id, '_imp_'.$key, $val);
  }
}
add_action('save_post', 'imp_save_tour_metabox');

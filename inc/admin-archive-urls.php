<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Admin settings to customize archive/taxonomy URLs for Tours.
 *
 * Goal: let editors change the URL structure (slugs) from WordPress,
 * without editing code, and flush rewrite rules automatically.
 */

function imp_get_tour_slugs() : array {
  $defaults = array(
    // Base archive for the CPT
    'tour_base' => 'tour',

    // Taxonomy bases (appended after tour_base)
    'region_base' => 'regione',
    'city_base'   => 'citta',
    'highlights_base' => 'highlights',
    'tipologia_base'  => 'tipologia',
    'durata_base'     => 'durata',
    'caratteristiche_base' => 'caratteristiche',
    'hotel_base'      => 'hotel',
    'trattamento_base'=> 'trattamento',
    'tag_base'        => 'tag',
  );

  $opt = get_option('imp_tour_slugs');
  if ( ! is_array($opt) ) $opt = array();

  $out = $defaults;
  foreach ( $defaults as $k => $v ) {
    if ( isset($opt[$k]) && is_string($opt[$k]) && $opt[$k] !== '' ) {
      $out[$k] = sanitize_title($opt[$k]);
    }
  }
  return $out;
}

function imp_tour_slug( string $key, string $default = '' ) : string {
  $slugs = imp_get_tour_slugs();
  if ( isset($slugs[$key]) && $slugs[$key] !== '' ) return $slugs[$key];
  return $default;
}

// Flush rewrite rules when slugs change.
add_action('update_option_imp_tour_slugs', function($old, $new){
  if ( $old !== $new ) {
    // Ensure CPT/taxonomies are registered before flushing.
    if ( function_exists('imp_register_tours_cpt') ) {
      imp_register_tours_cpt();
    }
    flush_rewrite_rules();
  }
}, 10, 2);

// Admin UI
add_action('admin_menu', function(){
  // Tours menu is the CPT menu.
  add_submenu_page(
    'edit.php?post_type=tour',
    __('URL Archivi Tour', 'imperatore'),
    __('URL Archivi', 'imperatore'),
    'manage_options',
    'imp-tours-urls',
    'imp_render_tours_urls_settings_page'
  );
});

add_action('admin_init', function(){
  register_setting('imp_tours_urls', 'imp_tour_slugs', array(
    'type' => 'array',
    'sanitize_callback' => function($value){
      if ( ! is_array($value) ) return array();
      $clean = array();
      foreach ( $value as $k => $v ) {
        if ( ! is_string($k) ) continue;
        if ( is_string($v) ) {
          $v = trim($v);
          if ( $v !== '' ) $clean[$k] = sanitize_title($v);
        }
      }
      return $clean;
    },
    'default' => array(),
  ));

  register_setting('imp_tours_urls', 'imp_archive_pillars_mode', array(
    'type' => 'string',
    'sanitize_callback' => function($value){
      $v = is_string($value) ? strtolower(trim($value)) : 'bars';
      if (!in_array($v, array('none','icons','bars'), true)) $v = 'bars';
      return $v;
    },
    'default' => 'bars',
  ));
});

function imp_render_tours_urls_settings_page(){
  if ( ! current_user_can('manage_options') ) return;
  $slugs = imp_get_tour_slugs();

  echo '<div class="wrap">';
  echo '<h1>' . esc_html__('URL Archivi Tour', 'imperatore') . '</h1>';
  echo '<p>' . esc_html__('Qui puoi modificare gli slug (URL) dell’archivio Tour e delle tassonomie. Dopo il salvataggio le regole permalink vengono aggiornate automaticamente.', 'imperatore') . '</p>';

  echo '<form method="post" action="options.php">';
  settings_fields('imp_tours_urls');

  echo '<table class="form-table" role="presentation">';

  // Pillars display in archive cards
  $pillars_mode = get_option('imp_archive_pillars_mode', 'bars');
  echo '<tr><th scope="row">' . esc_html__('Pillars in archivio', 'imperatore') . '</th><td>';
  echo '<select name="imp_archive_pillars_mode">';
  $opts = array(
    'bars'  => __('Barre (consigliato)', 'imperatore'),
    'icons' => __('Icone + testo', 'imperatore'),
    'none'  => __('Nascosti', 'imperatore'),
  );
  foreach ($opts as $k => $lab) {
    printf('<option value="%s" %s>%s</option>', esc_attr($k), selected($pillars_mode, $k, false), esc_html($lab));
  }
  echo '</select>';
  echo '<p class="description">' . esc_html__('Controlla come vengono mostrati i 4 indicatori (Cultura, Enogastronomia, Natura, Wellness) nelle card dell’archivio Tour.', 'imperatore') . '</p>';
  echo '</td></tr>';

  echo '<tr><th colspan="2"><hr></th></tr>';

  imp_urls_row('tour_base', __('Base archivio Tour', 'imperatore'), $slugs['tour_base'], '/tour/');
  echo '<tr><th colspan="2"><hr></th></tr>';
  imp_urls_row('region_base', __('Regioni', 'imperatore'), $slugs['region_base'], '/' . $slugs['tour_base'] . '/regione/');
  imp_urls_row('city_base', __('Città', 'imperatore'), $slugs['city_base'], '/' . $slugs['tour_base'] . '/citta/');
  imp_urls_row('tipologia_base', __('Tipologia di Tour', 'imperatore'), $slugs['tipologia_base'], '/' . $slugs['tour_base'] . '/tipologia/');
  imp_urls_row('durata_base', __('Durata', 'imperatore'), $slugs['durata_base'], '/' . $slugs['tour_base'] . '/durata/');
  imp_urls_row('caratteristiche_base', __('Caratteristiche', 'imperatore'), $slugs['caratteristiche_base'], '/' . $slugs['tour_base'] . '/caratteristiche/');
  imp_urls_row('hotel_base', __('Hotel', 'imperatore'), $slugs['hotel_base'], '/' . $slugs['tour_base'] . '/hotel/');
  imp_urls_row('trattamento_base', __('Trattamento', 'imperatore'), $slugs['trattamento_base'], '/' . $slugs['tour_base'] . '/trattamento/');
  imp_urls_row('highlights_base', __('Highlights', 'imperatore'), $slugs['highlights_base'], '/' . $slugs['tour_base'] . '/highlights/');
  imp_urls_row('tag_base', __('Tag Tour', 'imperatore'), $slugs['tag_base'], '/' . $slugs['tour_base'] . '/tag/');

  echo '</table>';

  submit_button(__('Salva URL', 'imperatore'));
  echo '</form>';

  echo '<p><strong>' . esc_html__('Suggerimento:', 'imperatore') . '</strong> ' . esc_html__('Se dopo il salvataggio non vedi gli URL aggiornati, vai in Impostazioni → Permalink e premi “Salva”.', 'imperatore') . '</p>';
  echo '</div>';
}

function imp_urls_row($key, $label, $value, $example){
  echo '<tr>';
  echo '<th scope="row"><label for="imp_tour_slugs_' . esc_attr($key) . '">' . esc_html($label) . '</label></th>';
  echo '<td>';
  echo '<input type="text" class="regular-text" id="imp_tour_slugs_' . esc_attr($key) . '" name="imp_tour_slugs[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
  echo '<p class="description">' . sprintf(esc_html__('Esempio: %s', 'imperatore'), '<code>' . esc_html($example) . '</code>') . '</p>';
  echo '</td>';
  echo '</tr>';
}

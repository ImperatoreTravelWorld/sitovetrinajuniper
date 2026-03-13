<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Tours CPT + taxonomies
 */

function imp_register_tours_cpt() {

  // Slugs are configurable from WP Admin (Tour → URL Archivi).
  $tour_base = function_exists('imp_tour_slug') ? imp_tour_slug('tour_base', 'tour') : 'tour';
  $region_base = function_exists('imp_tour_slug') ? imp_tour_slug('region_base', 'regione') : 'regione';
  $city_base   = function_exists('imp_tour_slug') ? imp_tour_slug('city_base', 'citta') : 'citta';
  $highlights_base = function_exists('imp_tour_slug') ? imp_tour_slug('highlights_base', 'highlights') : 'highlights';
  $tipologia_base  = function_exists('imp_tour_slug') ? imp_tour_slug('tipologia_base', 'tipologia') : 'tipologia';
  $durata_base     = function_exists('imp_tour_slug') ? imp_tour_slug('durata_base', 'durata') : 'durata';
  $caratteristiche_base = function_exists('imp_tour_slug') ? imp_tour_slug('caratteristiche_base', 'caratteristiche') : 'caratteristiche';
  $hotel_base      = function_exists('imp_tour_slug') ? imp_tour_slug('hotel_base', 'hotel') : 'hotel';
  $trattamento_base= function_exists('imp_tour_slug') ? imp_tour_slug('trattamento_base', 'trattamento') : 'trattamento';
  $tag_base        = function_exists('imp_tour_slug') ? imp_tour_slug('tag_base', 'tag') : 'tag';

  $labels = array(
    'name'               => __('Tour', 'imperatore'),
    'singular_name'      => __('Tour', 'imperatore'),
    'menu_name'          => __('Tour', 'imperatore'),
    'add_new'            => __('Aggiungi nuovo', 'imperatore'),
    'add_new_item'       => __('Aggiungi nuovo tour', 'imperatore'),
    'edit_item'          => __('Modifica tour', 'imperatore'),
    'new_item'           => __('Nuovo tour', 'imperatore'),
    'view_item'          => __('Vedi tour', 'imperatore'),
    'search_items'       => __('Cerca tour', 'imperatore'),
    'not_found'          => __('Nessun tour trovato', 'imperatore'),
    'not_found_in_trash' => __('Nessun tour nel cestino', 'imperatore'),
  );

  $args = array(
    'labels'             => $labels,
    'public'             => true,
    // Archive base (default /tour/)
    'has_archive'        => $tour_base,
    'rewrite'            => array('slug' => $tour_base, 'with_front' => false),
    'menu_icon'          => 'dashicons-palmtree',
    'supports'           => array('title','editor','thumbnail','excerpt'),
    'show_in_rest'       => true,
    'template'           => array(
      array('core/paragraph', array('placeholder' => 'Breve descrizione del tour…')),
      array('core/heading', array('level' => 3, 'content' => 'Tour Highlights')),
      array('core/paragraph', array('placeholder' => 'Aggiungi i termini nella tassonomia “Highlights” per mostrarli come pill.')),
      array('core/shortcode', array('text' => '[imp_tour_map]')),
      array('imperatore/itinerary', array()),
      array('core/paragraph', array('placeholder' => 'Contenuti extra…')),
    ),
    'template_lock'      => false,
  );

  register_post_type('tour', $args);

  /**
   * TAXONOMIES
   */

  // Regioni (gerarchica)
  // NOTE: we keep the taxonomy key as "destination" for backward compatibility,
  // but labels/URL are "Regioni".
  register_taxonomy('destination', array('tour'), array(
    'labels' => array(
      'name' => __('Regioni', 'imperatore'),
      'singular_name' => __('Regione', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => true,
    'show_in_rest' => true,
    // Region in URL as requested
    'rewrite' => array('slug' => $tour_base . '/' . $region_base, 'with_front' => false),
  ));

  // Città (geolocalizzazione)
  register_taxonomy('city', array('tour'), array(
    'labels' => array(
      'name' => __('Città', 'imperatore'),
      'singular_name' => __('Città', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => true,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $city_base, 'with_front' => false),
  ));

  // Highlights (tag-like, per pill in scheda tour)
  register_taxonomy('highlight', array('tour'), array(
    'labels' => array(
      'name' => __('Highlights', 'imperatore'),
      'singular_name' => __('Highlight', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $highlights_base, 'with_front' => false),
  ));

  // Tipologia di Tour (ex Modalità)
  register_taxonomy('modality', array('tour'), array(
    'labels' => array(
      'name' => __('Tipologia di Tour', 'imperatore'),
      'singular_name' => __('Tipologia di Tour', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $tipologia_base, 'with_front' => false),
  ));

  // Durata
  register_taxonomy('duration', array('tour'), array(
    'labels' => array(
      'name' => __('Durata', 'imperatore'),
      'singular_name' => __('Durata', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $durata_base, 'with_front' => false),
  ));

  // Caratteristiche
  register_taxonomy('characteristic', array('tour'), array(
    'labels' => array(
      'name' => __('Caratteristiche', 'imperatore'),
      'singular_name' => __('Caratteristica', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $caratteristiche_base, 'with_front' => false),
  ));

  // Hotel
  register_taxonomy('hotel', array('tour'), array(
    'labels' => array(
      'name' => __('Hotel', 'imperatore'),
      'singular_name' => __('Hotel', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $hotel_base, 'with_front' => false),
  ));

  // Trattamento
  register_taxonomy('treatment', array('tour'), array(
    'labels' => array(
      'name' => __('Trattamento', 'imperatore'),
      'singular_name' => __('Trattamento', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $trattamento_base, 'with_front' => false),
  ));

  // Tag liberi (filtri extra)
  register_taxonomy('tour_tag', array('tour'), array(
    'labels' => array(
      'name' => __('Tag Tour', 'imperatore'),
      'singular_name' => __('Tag Tour', 'imperatore'),
    ),
    'public' => true,
    'hierarchical' => false,
    'show_in_rest' => true,
    'rewrite' => array('slug' => $tour_base . '/' . $tag_base, 'with_front' => false),
  ));
}
add_action('init', 'imp_register_tours_cpt');

/**
 * Keep a numeric month meta for fast filtering.
 */
function imp_sync_departure_month_meta($post_id) {
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
  if ( get_post_type($post_id) !== 'tour' ) return;

  $dep = get_post_meta($post_id, '_imp_departure_start', true);
  if ( ! $dep ) {
    delete_post_meta($post_id, '_imp_departure_month');
    return;
  }

  // Expect YYYY-MM-DD
  $m = 0;
  if ( preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dep, $mm) ) {
    $m = intval($mm[2]);
  }

  if ( $m >= 1 && $m <= 12 ) {
    update_post_meta($post_id, '_imp_departure_month', $m);
  } else {
    delete_post_meta($post_id, '_imp_departure_month');
  }
}
add_action('save_post_tour', 'imp_sync_departure_month_meta');

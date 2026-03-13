<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Tours filtering & sorting via query vars / GET.
 *
 * Supported GET params:
 * - dep (YYYY-MM-DD) => departure_start >= dep
 * - month[] (1..12)  => departure month
 * - duration[] (slug)
 * - modality[] (slug)  (shown as "Tipologia di Tour")
 * - destination[] (slug) (shown as "Regioni")
 * - city[] (slug)
 * - characteristic[] (slug)
 * - hotel[] (slug)
 * - treatment[] (slug)
 * - sort => price_asc | price_desc | days_asc | days_desc | date_asc | date_desc
 */

function imp_get_param_array($key) {
  if (!isset($_GET[$key])) return array();
  $v = wp_unslash($_GET[$key]);
  if (is_array($v)) return array_values(array_filter($v, static function($x){ return $x !== ''; }));
  if ($v === '') return array();
  return array($v);
}

/**
 * Build query args from current request (GET) + optional base args.
 */
function imp_build_tours_query_args($base = array()) {
  $args = wp_parse_args($base, array(
    'post_type' => 'tour',
    'post_status' => 'publish',
  ));

  $tax_query = array('relation' => 'AND');

  $map = array(
    'duration' => 'duration',
    'modality' => 'modality',
    'destination' => 'destination',
    'city' => 'city',
    'characteristic' => 'characteristic',
    'hotel' => 'hotel',
    'treatment' => 'treatment',
  );

  foreach ($map as $param => $taxonomy) {
    $vals = imp_get_param_array($param);
    if ($vals) {
      $q = array(
        'taxonomy' => $taxonomy,
        'field' => 'slug',
        'terms' => $vals,
      );
      if ($taxonomy === 'destination' || $taxonomy === 'city') {
        $q['include_children'] = true;
      }
      $tax_query[] = $q;
    }
  }

  if (count($tax_query) > 1) {
    $args['tax_query'] = $tax_query;
  }

  $meta_query = array('relation' => 'AND');

  $dep = isset($_GET['dep']) ? sanitize_text_field(wp_unslash($_GET['dep'])) : '';
  if ($dep) {
    $meta_query[] = array(
      'key' => '_imp_departure_start',
      'value' => $dep,
      'compare' => '>=',
      'type' => 'DATE',
    );
  }

  $months = imp_get_param_array('month');
  $months = array_map('intval', $months);
  $months = array_values(array_filter($months, static function($m){ return $m>=1 && $m<=12; }));
  if ($months) {
    $meta_query[] = array(
      'key' => '_imp_departure_month',
      'value' => $months,
      'compare' => 'IN',
      'type' => 'NUMERIC',
    );
  }

  if (count($meta_query) > 1) {
    $args['meta_query'] = $meta_query;
  }

  // Sorting
  $sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '';
  switch ($sort) {
    case 'price_asc':
      $args['meta_key'] = '_imp_price_from';
      $args['orderby'] = 'meta_value_num';
      $args['order'] = 'ASC';
      break;
    case 'price_desc':
      $args['meta_key'] = '_imp_price_from';
      $args['orderby'] = 'meta_value_num';
      $args['order'] = 'DESC';
      break;
    case 'days_asc':
      $args['meta_key'] = '_imp_days';
      $args['orderby'] = 'meta_value_num';
      $args['order'] = 'ASC';
      break;
    case 'days_desc':
      $args['meta_key'] = '_imp_days';
      $args['orderby'] = 'meta_value_num';
      $args['order'] = 'DESC';
      break;
    case 'date_asc':
      $args['meta_key'] = '_imp_departure_start';
      $args['orderby'] = 'meta_value';
      $args['order'] = 'ASC';
      break;
    case 'date_desc':
      $args['meta_key'] = '_imp_departure_start';
      $args['orderby'] = 'meta_value';
      $args['order'] = 'DESC';
      break;
    default:
      break;
  }

  return $args;
}

function imp_tours_pre_get_posts($q) {
  if ( is_admin() || ! $q->is_main_query() ) return;

  $is_tours = $q->is_post_type_archive('tour') || $q->is_tax(array('destination','city','duration','modality','characteristic','hotel','treatment','highlight','tour_tag'));
  if ( ! $is_tours ) return;

  $args = imp_build_tours_query_args(array());

  foreach (array('tax_query','meta_query','meta_key','orderby','order') as $k) {
    if (isset($args[$k])) $q->set($k, $args[$k]);
  }

  if ( ! $q->get('posts_per_page') ) {
    $q->set('posts_per_page', 12);
  }
}
add_action('pre_get_posts', 'imp_tours_pre_get_posts');

<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Shortcodes:
 * - [imp_tours_archive]
 * - [imp_tour_single]
 */

function imp_tours_clean_html($html) {
  $html = preg_replace('#<br\s*/?>#i', '', $html);
  $html = preg_replace('#<p>\s*</p>#i', '', $html);
  $html = preg_replace('/>\s+</', '><', $html);
  return trim($html);
}

function imp_sc_is_checked($key, $slug) {
  if (!isset($_GET[$key])) return false;
  $v = wp_unslash($_GET[$key]);
  if (is_array($v)) return in_array($slug, $v, true);
  return $v === $slug;
}

function imp_sc_is_checked_int($key, $val) {
  if (!isset($_GET[$key])) return false;
  $v = wp_unslash($_GET[$key]);
  if (is_array($v)) return in_array((string)$val, array_map('strval', $v), true);
  return (string)$v === (string)$val;
}


function imp_sc_selected_count($key) {
  if (!isset($_GET[$key])) return 0;
  $v = wp_unslash($_GET[$key]);
  if (is_array($v)) return count(array_filter($v, function($x){ return $x !== '' && $x !== null; }));
  return ($v !== '' && $v !== null) ? 1 : 0;
}

function imp_sc_filter_group_head($label, $selected_count = 0) {
  $count_style = ($selected_count > 0) ? '' : ' style="display:none"';
  ob_start(); ?>
  <button type="button" class="imp-filter-group__head" aria-expanded="true">
    <span class="imp-filter-group__label"><?php echo esc_html($label); ?></span>
    <span class="imp-filter-group__meta">
      <span class="imp-filter-group__count"<?php echo $count_style; ?>><?php echo intval($selected_count); ?></span>
      <span class="imp-filter-group__chev" aria-hidden="true">
        <svg viewBox="0 0 20 20" width="18" height="18" focusable="false" aria-hidden="true">
          <path d="M5.5 7.5l4.5 5 4.5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
    </span>
  </button>
  <?php
  return ob_get_clean();
}


function imp_sc_build_url($overrides = array()) {
  $params = $_GET;
  foreach ($overrides as $k => $v) {
    if ($v === null) unset($params[$k]);
    else $params[$k] = $v;
  }

  // IMPORTANT: default archive must be /tour/
  $base = home_url('/tour/');
  return esc_url( add_query_arg($params, $base) );
}

function imp_sc_month_label_it($m) {
  $map = array(
    1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
    5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
    9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
  );
  $m = intval($m);
  return isset($map[$m]) ? $map[$m] : (string)$m;
}

function imp_sc_active_terms_for_posts($taxonomy, $post_ids) {
  if (empty($post_ids)) return array();
  $terms = wp_get_object_terms($post_ids, $taxonomy, array(
    'orderby' => 'name',
    'order' => 'ASC',
  ));
  return is_array($terms) ? $terms : array();
}

function imp_sc_active_months_for_posts($post_ids) {
  if (empty($post_ids)) return array();
  global $wpdb;
  $ids = array_map('intval', $post_ids);
  $in = implode(',', $ids);
  // Collect distinct months from meta.
  $rows = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($in) AND meta_key='_imp_departure_month' AND meta_value<>''");
  $months = array();
  foreach ($rows as $r) {
    $m = intval($r);
    if ($m>=1 && $m<=12) $months[] = $m;
  }
  $months = array_values(array_unique($months));
  sort($months);
  return $months;
}

function imp_sc_render_checklist($name, $terms, $limit = 4) {
  if (empty($terms)) return '';
  ob_start();
  $count = 0;
  echo '<div class="imp-filter-options" data-limit="'.intval($limit).'">';
  foreach ($terms as $t) {
    $count++;
    $extra = ($count > $limit) ? ' data-extra="1" style="display:none"' : '';
     $is_on = imp_sc_is_checked($name, $t->slug);
    echo '<label class="imp-check'.($is_on?' is-active':'').'"'.$extra.'>';
    echo '<input type="checkbox" name="'.esc_attr($name).'[]" value="'.esc_attr($t->slug).'" '.checked(true, $is_on, false).' />';
    echo '<span>'.esc_html($t->name).'</span>';
    echo '</label>';
  }
  echo '</div>';
  if ($count > $limit) {
    echo '<button type="button" class="imp-filter-toggle" data-state="less">Mostra altri</button>';
  }
  return ob_get_clean();
}

function imp_sc_render_months($months, $limit = 4) {
  if (empty($months)) return '';
  ob_start();
  $count = 0;
  echo '<div class="imp-filter-options" data-limit="'.intval($limit).'">';
  foreach ($months as $m) {
    $count++;
    $extra = ($count > $limit) ? ' data-extra="1" style="display:none"' : '';
    $is_on = imp_sc_is_checked_int('month', $m);
    echo '<label class="imp-check'.($is_on ? ' is-active' : '').'"'.$extra.'>';
    echo '<input type="checkbox" name="month[]" value="'.intval($m).'" '.checked(true, $is_on, false).' />';
    echo '<span>'.esc_html(imp_sc_month_label_it($m)).'</span>';
    echo '</label>';
  }
  echo '</div>';
  if ($count > $limit) {
    echo '<button type="button" class="imp-filter-toggle" data-state="less">Mostra altri</button>';
  }
  return ob_get_clean();
}


function imp_shortcode_tours_archive() {
  // This shortcode must work anywhere (page, archive, taxonomy, etc.).

  $paged = max(1, intval(get_query_var('paged')));
  $dep  = isset($_GET['dep']) ? sanitize_text_field(wp_unslash($_GET['dep'])) : '';
  $sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '';

  $q_args = imp_build_tours_query_args(array(
    'paged' => $paged,
    'posts_per_page' => 12,
  ));

  // If we are inside a taxonomy archive, automatically apply that filter.
  if (is_tax('destination')) {
    $term = get_queried_object();
    if ($term && !is_wp_error($term)) {
      $q_args['tax_query'][] = array(
        'taxonomy' => 'destination',
        'field' => 'term_id',
        'terms' => array(intval($term->term_id)),
        'include_children' => true,
      );
    }
  }
  if (is_tax('city')) {
    $term = get_queried_object();
    if ($term && !is_wp_error($term)) {
      $q_args['tax_query'][] = array(
        'taxonomy' => 'city',
        'field' => 'term_id',
        'terms' => array(intval($term->term_id)),
        'include_children' => true,
      );
    }
  }

  $tq = new WP_Query($q_args);
  $post_ids = wp_list_pluck($tq->posts, 'ID');

  // Active-only filter values (based on current results)
  $months = imp_sc_active_months_for_posts($post_ids);
  $modalities = imp_sc_active_terms_for_posts('modality', $post_ids);
  $durations = imp_sc_active_terms_for_posts('duration', $post_ids);
  $regions = imp_sc_active_terms_for_posts('destination', $post_ids);
  $cities = imp_sc_active_terms_for_posts('city', $post_ids);
  $characteristics = imp_sc_active_terms_for_posts('characteristic', $post_ids);
  $hotels = imp_sc_active_terms_for_posts('hotel', $post_ids);
  $treatments = imp_sc_active_terms_for_posts('treatment', $post_ids);

  // Enqueue JS for show more/less
  wp_enqueue_script('imp-tours-filters-ui', get_template_directory_uri().'/assets/js/tours-filters-ui.js', array(), defined('IMP_THEME_VERSION') ? IMP_THEME_VERSION : '1.0.0', true);

  ob_start();
  ?>
  <main class="imp-tours imp-tours-page">
    <div class="imp-tours-hero"><div class="imp-tours-hero__inner"><h1 class="imp-tours-title"><?php echo esc_html__('LA NOSTRA SELEZIONE DI VIAGGI', 'imperatore'); ?></h1></div></div>

    <div class="imp-tours-wrap">
      <aside class="imp-tours-filters" aria-label="<?php echo esc_attr__('Filtri tour', 'imperatore'); ?>">
        <div class="imp-filter-box">
          <div class="imp-filter-head">
            <div class="imp-filter-title-wrap"><div class="imp-filter-title"><?php echo esc_html__('FILTRA VIAGGI', 'imperatore'); ?></div><span class="imp-results-badge" aria-label="<?php echo esc_attr__('Numero risultati', 'imperatore'); ?>"><?php echo intval($tq->found_posts); ?></span></div>
            <a class="imp-filter-reset" href="<?php echo esc_url(home_url('/tour/')); ?>"><?php echo esc_html__('Reset', 'imperatore'); ?></a>
          </div>

          <form method="get" action="<?php echo esc_url(home_url('/tour/')); ?>" data-reset-url="<?php echo esc_url(home_url('/tour/')); ?>" class="imp-filter-form">
            <div class="imp-filter-group">
              <label for="imp-dep"><?php echo esc_html__('Data Partenza', 'imperatore'); ?></label>
              <input type="date" id="imp-dep" name="dep" value="<?php echo esc_attr($dep); ?>" />
            </div>

            <?php if (!empty($months)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Mese di partenza', 'imperatore'), imp_sc_selected_count('month')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_months($months, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($modalities)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Tipologia di Tour', 'imperatore'), imp_sc_selected_count('modality')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_checklist('modality', $modalities, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($durations)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Durata', 'imperatore'), imp_sc_selected_count('duration')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_checklist('duration', $durations, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($regions)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Regioni', 'imperatore'), imp_sc_selected_count('destination')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_checklist('destination', $regions, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($cities)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Città', 'imperatore'), imp_sc_selected_count('city')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_checklist('city', $cities, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($characteristics)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Caratteristiche', 'imperatore'), imp_sc_selected_count('characteristic')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_checklist('characteristic', $characteristics, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($hotels)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Hotel', 'imperatore'), imp_sc_selected_count('hotel')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_checklist('hotel', $hotels, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($treatments)) : ?>
              <div class="imp-filter-group">
                <?php echo imp_sc_filter_group_head(__('Trattamento', 'imperatore'), imp_sc_selected_count('treatment')); ?>
                <div class="imp-filter-group__panel">
                <?php echo imp_sc_render_checklist('treatment', $treatments, 4); ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="imp-filter-actions">
              <button type="submit" class="imp-btn imp-btn--primary"><?php echo esc_html__('Applica filtri', 'imperatore'); ?></button>
              <button type="button" class="imp-btn imp-btn--secondary imp-reset-filters"><?php echo esc_html__('Reset filtri', 'imperatore'); ?></button>
            </div>
          </form>
        </div>
      </aside>

      <section class="imp-tours-list" aria-label="<?php echo esc_attr__('Elenco tour', 'imperatore'); ?>">
        <div class="imp-tours-toolbar">
          <div class="imp-tours-count"><?php echo intval($tq->found_posts); ?> <?php echo esc_html__('risultati', 'imperatore'); ?></div>
          <div class="imp-tours-sort">
            <label for="imp-sort"><?php echo esc_html__('Ordina per', 'imperatore'); ?></label>
            <select id="imp-sort" onchange="window.location.href=this.value">
              <?php
                $options = array(
                  '' => __('Predefinito', 'imperatore'),
                  'price_asc' => __('Prezzo (crescente)', 'imperatore'),
                  'price_desc' => __('Prezzo (decrescente)', 'imperatore'),
                  'days_asc' => __('Durata (crescente)', 'imperatore'),
                  'days_desc' => __('Durata (decrescente)', 'imperatore'),
                  'date_asc' => __('Data partenza (crescente)', 'imperatore'),
                  'date_desc' => __('Data partenza (decrescente)', 'imperatore'),
                );
                foreach ($options as $k => $label) {
                  $url = $k === '' ? imp_sc_build_url(array('sort' => null)) : imp_sc_build_url(array('sort' => $k));
                  printf('<option value="%s" %s>%s</option>', esc_url($url), selected($sort, $k, false), esc_html($label));
                }
              ?>
            </select>
          </div>
        </div>

        <?php if ( $tq->have_posts() ) : ?>
          <div class="imp-tour-grid">
            <?php while ( $tq->have_posts() ) : $tq->the_post();
              $id = get_the_ID();
              $price = get_post_meta($id, '_imp_price_from', true);
              $days  = get_post_meta($id, '_imp_days', true);
              $badge = get_post_meta($id, '_imp_badge_text', true);
              $it_url = get_post_meta($id, '_imp_itinerary_url', true);
              $quick_url = get_post_meta($id, '_imp_quick_url', true);
              $thumb = get_the_post_thumbnail_url($id, 'large');
            ?>
              <article class="imp-tour-card">
                <a class="imp-tour-card__media" href="<?php the_permalink(); ?>">
                  <?php if ($badge) : ?><span class="imp-badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
                  <?php if ($thumb) : ?><img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" /><?php endif; ?>
                </a>
                <div class="imp-tour-card__body">
                  <div class="imp-tour-meta">
                    <?php if ($days) : ?><span class="imp-tour-meta__item"><?php echo intval($days); ?> <?php echo esc_html__('giorni', 'imperatore'); ?></span><?php endif; ?>
                    <?php if ($price) : ?><span class="imp-tour-meta__item"><?php echo esc_html__('da', 'imperatore'); ?> <?php echo esc_html(number_format_i18n(floatval($price), 0)); ?>€ <?php echo esc_html__('a persona', 'imperatore'); ?></span><?php endif; ?>
                  </div>

                  <h2 class="imp-tour-title-card"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                  <?php
                    // Highlights pillars (icons + percent)
                    $pillars_html = function_exists('imp_render_tour_pillars_archive') ? imp_render_tour_pillars_archive($id) : ( function_exists('imp_render_tour_pillars') ? imp_render_tour_pillars($id) : '' );
                    if ($pillars_html) {
                      echo $pillars_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                  ?>

                  <div class="imp-tour-actions">
                    <?php if ($it_url) : ?>
                      <a class="imp-btn imp-btn--yellow" href="<?php echo esc_url($it_url); ?>"><?php echo esc_html__('VEDI ITINERARIO', 'imperatore'); ?></a>
                    <?php else : ?>
                      <a class="imp-btn imp-btn--yellow" href="<?php the_permalink(); ?>"><?php echo esc_html__('VEDI ITINERARIO', 'imperatore'); ?></a>
                    <?php endif; ?>

                    <?php if ($quick_url) : ?>
                      <a class="imp-btn imp-btn--outline" href="<?php echo esc_url($quick_url); ?>"><?php echo esc_html__('SCOPRI IN MODO RAPIDO', 'imperatore'); ?></a>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endwhile; wp_reset_postdata(); ?>
          </div>

          <div class="imp-pagination">
            <?php
              echo paginate_links(array(
                'total' => max(1, intval($tq->max_num_pages)),
                'current' => $paged,
                'mid_size' => 1,
                'prev_text' => '←',
                'next_text' => '→',
              ));
            ?>
          </div>
        <?php else : ?>
          <p><?php echo esc_html__('Nessun tour trovato con i filtri selezionati.', 'imperatore'); ?></p>
        <?php endif; ?>
      </section>
    </div>
  </main>
  <?php

  $html = ob_get_clean();
  return imp_tours_clean_html($html);
}
add_shortcode('imp_tours_archive', 'imp_shortcode_tours_archive');
function imp_shortcode_tour_single() {
  if ( ! is_singular('tour') ) return '';

  global $post;
  $id = $post->ID;

  $price = get_post_meta($id, '_imp_price_from', true);
  $days  = get_post_meta($id, '_imp_days', true);
  $badge = get_post_meta($id, '_imp_badge_text', true);
  $it_url = get_post_meta($id, '_imp_itinerary_url', true);
  $booking_url = get_post_meta($id, '_imp_booking_url', true);
  $dep = get_post_meta($id, '_imp_departure_start', true);
  $thumb = get_the_post_thumbnail_url($id, 'full');
  $excerpt = has_excerpt($id) ? get_the_excerpt($id) : '';

  $hide_val = get_post_meta($id, 'imp_hide_booking_box', true);
  $hide_booking = in_array($hide_val, array('1','on','yes','true'), true);

  $dest_terms = get_the_terms($id, 'destination');
  $high_terms = get_the_terms($id, 'highlight');

  $content = apply_filters('the_content', $post->post_content);

  ob_start();
  ?>
  <main class="imp-tour-page imp-tour-page--v4">
    <div class="imp-tour-shell">
      <article class="imp-tour-card">
        <?php
          $gallery = imp_get_tour_gallery_images($id);
          $gallery_type = isset($gallery["type"]) ? $gallery["type"] : "wp";
          $gallery_items = (isset($gallery["images"]) && is_array($gallery["images"])) ? $gallery["images"] : array();
        ?>

        <?php if ( ! empty($gallery_items) ) : ?>
          <div class="imp-tour-card__media imp-tour-gallery" data-count="<?php echo esc_attr(count($gallery_items)); ?>">
            <div class="imp-tour-gallery__track">
              <?php foreach ($gallery_items as $item) :
                $url = "";
                if ($gallery_type === "cdn") {
                  $url = (string) $item;
                } else {
                  $url = wp_get_attachment_image_url(intval($item), "full");
                }
                if (!$url) continue; ?>
                <div class="imp-tour-gallery__slide">
                  <img src="<?php echo esc_url($url); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
                </div>
              <?php endforeach; ?>
            </div>
            <button class="imp-tour-gallery__btn imp-tour-gallery__prev" type="button" aria-label="Previous">‹</button>
            <button class="imp-tour-gallery__btn imp-tour-gallery__next" type="button" aria-label="Next">›</button>
            <div class="imp-tour-gallery__dots"></div>
          </div>
        <?php elseif ($thumb) : ?>
          <div class="imp-tour-card__media">
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
          </div>
        <?php endif; ?>

        <div class="imp-tour-card__body">
          <header class="imp-tour-head">
            <h1 class="imp-tour-title"><?php the_title(); ?></h1>

            <div class="imp-tour-submeta">
              <?php if (!empty($dest_terms) && !is_wp_error($dest_terms)) : ?>
                <span class="imp-tour-location"><?php echo esc_html($dest_terms[0]->name); ?></span>
              <?php endif; ?>

              <?php if ($days) : ?>
                <span class="imp-tour-days"><?php echo intval($days); ?> <?php echo esc_html__('giorni', 'imperatore'); ?></span>
              <?php endif; ?>

              <?php if ($badge) : ?>
                <span class="imp-tour-pill"><?php echo esc_html($badge); ?></span>
              <?php endif; ?>
            </div>

            <?php if ($excerpt) : ?>
              <p class="imp-tour-excerpt"><?php echo esc_html($excerpt); ?></p>
            <?php endif; ?>
          </header>

          <?php if (!empty($high_terms) && !is_wp_error($high_terms)) : ?>
            <section class="imp-tour-highlights">
              <h3 class="imp-tour-section-title"><?php echo esc_html__('Tour Highlights', 'imperatore'); ?></h3>
              <div class="imp-tour-pills">
                <?php foreach ($high_terms as $t) : ?>
                  <span class="imp-pill"><?php echo esc_html($t->name); ?></span>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <section class="imp-tour-main <?php echo $hide_booking ? 'imp-tour-main--noaside' : ''; ?>">
            <div class="imp-tour-main__left">
              <div class="imp-tour-content">
                <?php echo $content; ?>
              </div>
            </div>

            <?php if ( ! $hide_booking ) : ?>
              <aside class="imp-tour-main__right">
                <div class="imp-booking imp-booking--side">
                  <div class="imp-booking__label"><?php echo esc_html__('From', 'imperatore'); ?></div>
                  <div class="imp-booking__price">
                    <?php if ($price) : ?>
                      <span class="imp-booking__amount">€<?php echo esc_html(number_format_i18n(floatval($price), 0)); ?></span>
                      <span class="imp-booking__per">/ <?php echo esc_html__('persona', 'imperatore'); ?></span>
                    <?php else : ?>
                      <span class="imp-booking__amount">—</span>
                    <?php endif; ?>
                  </div>

                  <div class="imp-booking__field">
                    <label><?php echo esc_html__('Seleziona data di partenza', 'imperatore'); ?></label>
                    <input type="date" value="<?php echo esc_attr($dep); ?>" />
                  </div>

                  <a class="imp-booking__cta" href="<?php echo esc_url($booking_url ? $booking_url : ($it_url ? $it_url : get_permalink($id))); ?>">
                    <?php echo esc_html__('Prenota questo Tour', 'imperatore'); ?>
                  </a>

                  <div class="imp-booking__note"><?php echo esc_html__('Cancellazione gratuita fino a 30 giorni prima della partenza.', 'imperatore'); ?></div>
                </div>
              </aside>
            <?php endif; ?>
          </section>
        </div>
      </article>
    </div>
  </main>
  <?php
  $html = ob_get_clean();
  return imp_tours_clean_html($html);
}
add_shortcode('imp_tour_single', 'imp_shortcode_tour_single');


/**
 * Shortcode: [imp_tour_map]
 * Render the tour itinerary map wherever you place it inside the editor content.
 */
function imp_shortcode_tour_map() {
  if ( ! is_singular('tour') ) return '';
  global $post;
  $id = $post->ID;
  if ( ! function_exists('imp_get_tour_map_stops') ) return '';
  $stops = imp_get_tour_map_stops($id);
  if ( empty($stops) ) return '';

  ob_start(); ?>
  <section class="imp-tour-map-inline">
    <div class="imp-tour-map" data-stops='<?php echo esc_attr(wp_json_encode($stops)); ?>'></div>
  </section>
  <?php
  $html = ob_get_clean();
  return imp_tours_clean_html($html);
}
add_shortcode('imp_tour_map', 'imp_shortcode_tour_map');

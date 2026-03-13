<?php
/**
 * Archive: Tour
 */
get_header();

// Helpers
function imp_terms_for_filter($taxonomy) {
  $terms = get_terms(array(
    'taxonomy' => $taxonomy,
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
  ));
  return is_array($terms) ? $terms : array();
}

function imp_is_checked($key, $slug) {
  if (!isset($_GET[$key])) return false;
  $v = wp_unslash($_GET[$key]);
  if (is_array($v)) return in_array($slug, $v, true);
  return $v === $slug;
}


function imp_arc_selected_count($key){
  if (!isset($_GET[$key])) return 0;
  $v = wp_unslash($_GET[$key]);
  if (is_array($v)) return count(array_filter($v, function($x){ return $x !== '' && $x !== null; }));
  return ($v !== '' && $v !== null) ? 1 : 0;
}
function imp_arc_group_head($label,$count=0){
  $count_style = ($count>0)?'':' style="display:none"';
  return '<button type="button" class="imp-filter-group__head" aria-expanded="true">'
    .'<span class="imp-filter-group__label">'.esc_html($label).'</span>'
    .'<span class="imp-filter-group__meta">'
      .'<span class="imp-filter-group__count"'.$count_style.'>'.intval($count).'</span>'
      .'<span class="imp-filter-group__chev" aria-hidden="true">'
        .'<svg viewBox="0 0 20 20" width="18" height="18" focusable="false" aria-hidden="true">'
          .'<path d="M5.5 7.5l4.5 5 4.5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
        .'</svg>'
      .'</span>'
    .'</span>'
  .'</button>';
}


function imp_build_url($overrides = array()) {
  $params = $_GET;
  foreach ($overrides as $k => $v) {
    if ($v === null) {
      unset($params[$k]);
    } else {
      $params[$k] = $v;
    }
  }
  return esc_url( add_query_arg($params, get_post_type_archive_link('tour')) );
}

$durations = imp_terms_for_filter('duration');
$modalities = imp_terms_for_filter('modality');
$destinations = imp_terms_for_filter('destination');

$sort = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : '';
$dep = isset($_GET['dep']) ? sanitize_text_field(wp_unslash($_GET['dep'])) : '';

?>
<main class="imp-tours imp-tours-page">
  <div class="imp-tours-hero">
    <div class="imp-tours-hero__inner">
      <h1 class="imp-tours-title">LA NOSTRA SELEZIONE DI VIAGGI</h1>
    </div>
  </div>

  <div class="imp-tours-wrap">
    <aside class="imp-tours-filters" aria-label="<?php echo esc_attr__('Filtri viaggi', 'imperatore'); ?>">
      <div class="imp-filter-box">
        <div class="imp-filter-head">
          <div class="imp-filter-title-wrap"><div class="imp-filter-title">FILTRA VIAGGI</div><span class="imp-results-badge" aria-label="Numero risultati"><?php echo intval($wp_query->found_posts); ?></span></div>
          <a class="imp-filter-reset" href="<?php echo esc_url(get_post_type_archive_link('tour')); ?>">Reset</a>
        </div>

        <form method="get" action="<?php echo esc_url(get_post_type_archive_link('tour')); ?>" class="imp-filter-form">
          <div class="imp-filter-group">
            <label for="imp-dep">Data Partenza</label>
            <input type="date" id="imp-dep" name="dep" value="<?php echo esc_attr($dep); ?>" />
          </div>

          <?php if ($durations) : ?>
          <div class="imp-filter-group">
            <?php echo imp_arc_group_head('Durata', imp_arc_selected_count('duration')); ?><div class="imp-filter-group__panel">
            <?php foreach ($durations as $t) : ?>
              <label class="imp-check">
                <input type="checkbox" name="duration[]" value="<?php echo esc_attr($t->slug); ?>" <?php checked(true, imp_is_checked('duration', $t->slug)); ?> />
                <span><?php echo esc_html($t->name); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if ($modalities) : ?>
          <div class="imp-filter-group">
            <?php echo imp_arc_group_head('Tipologia di Tour', imp_arc_selected_count('modality')); ?><div class="imp-filter-group__panel">
            <?php foreach ($modalities as $t) : ?>
              <label class="imp-check">
                <input type="checkbox" name="modality[]" value="<?php echo esc_attr($t->slug); ?>" <?php checked(true, imp_is_checked('modality', $t->slug)); ?> />
                <span><?php echo esc_html($t->name); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if ($destinations) : ?>
          <div class="imp-filter-group">
            <div class="imp-filter-group__head">Regioni / Destinazioni</div>
            <?php foreach ($destinations as $t) : ?>
              <label class="imp-check">
                <input type="checkbox" name="destination[]" value="<?php echo esc_attr($t->slug); ?>" <?php checked(true, imp_is_checked('destination', $t->slug)); ?> />
                <span><?php echo esc_html($t->name); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="imp-filter-actions">
            <button type="submit" class="imp-btn imp-btn--primary">Applica filtri</button>
            <button type="button" class="imp-btn imp-btn--secondary imp-reset-filters">Reset filtri</button>
          </div>
        </form>
      </div>
    </aside>

    <section class="imp-tours-list" aria-label="<?php echo esc_attr__('Elenco tour', 'imperatore'); ?>">
      <div class="imp-tours-toolbar">
        <div class="imp-tours-count">
          <?php global $wp_query; echo intval($wp_query->found_posts); ?> risultati
        </div>
        <div class="imp-tours-sort">
          <label for="imp-sort">Ordina per</label>
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
                $url = $k === '' ? imp_build_url(array('sort' => null)) : imp_build_url(array('sort' => $k));
                printf('<option value="%s" %s>%s</option>', esc_url($url), selected($sort, $k, false), esc_html($label));
              }
            ?>
          </select>
        </div>
      </div>

      <?php if ( have_posts() ) : ?>
        <div class="imp-tour-grid">
          <?php while ( have_posts() ) : the_post();
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
                <?php if ($thumb) : ?>
                  <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" />
                <?php endif; ?>
              </a>
              <div class="imp-tour-card__body">
                <div class="imp-tour-meta">
                  <?php if ($days) : ?><span class="imp-tour-meta__item"><?php echo intval($days); ?> giorni</span><?php endif; ?>
                  <?php if ($price) : ?><span class="imp-tour-meta__item">da <?php echo esc_html(number_format_i18n(floatval($price), 0)); ?>€ a persona</span><?php endif; ?>
                </div>

                <h2 class="imp-tour-title-card"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

                <?php if (has_excerpt()) : ?>
                  <div class="imp-tour-excerpt"><?php the_excerpt(); ?></div>
                <?php endif; ?>

                <div class="imp-tour-actions">
                  <?php if ($it_url) : ?>
                    <a class="imp-btn imp-btn--yellow" href="<?php echo esc_url($it_url); ?>">VEDI ITINERARIO</a>
                  <?php else : ?>
                    <a class="imp-btn imp-btn--yellow" href="<?php the_permalink(); ?>">VEDI ITINERARIO</a>
                  <?php endif; ?>

                  <?php if ($quick_url) : ?>
                    <a class="imp-btn imp-btn--outline" href="<?php echo esc_url($quick_url); ?>">SCOPRI IN MODO RAPIDO</a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endwhile; ?>
        </div>

        <div class="imp-pagination">
          <?php the_posts_pagination(array(
            'mid_size' => 1,
            'prev_text' => '←',
            'next_text' => '→',
          )); ?>
        </div>
      <?php else : ?>
        <p>Nessun tour trovato con i filtri selezionati.</p>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php get_footer(); ?>

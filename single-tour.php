<?php
/**
 * Single: Tour
 */
get_header();
the_post();

$id = get_the_ID();
$price = get_post_meta($id, '_imp_price_from', true);
$days  = get_post_meta($id, '_imp_days', true);
$badge = get_post_meta($id, '_imp_badge_text', true);
$it_url = get_post_meta($id, '_imp_itinerary_url', true);
$quick_url = get_post_meta($id, '_imp_quick_url', true);
$dep = get_post_meta($id, '_imp_departure_start', true);
$thumb = get_the_post_thumbnail_url($id, 'full');
?>
<main class="imp-tour-single">
  <div class="imp-tour-single-hero">
    <?php if ($thumb) : ?>
      <img class="imp-tour-single-hero__img" src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" />
    <?php endif; ?>
    <div class="imp-tour-single-hero__overlay">
      <div class="imp-tour-single-hero__inner">
        <?php if ($badge) : ?><span class="imp-badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
        <h1 class="imp-tour-single-title"><?php the_title(); ?></h1>
        <div class="imp-tour-single-meta">
          <?php if ($days) : ?><span><?php echo intval($days); ?> giorni</span><?php endif; ?>
          <?php if ($price) : ?><span>da <?php echo esc_html(number_format_i18n(floatval($price), 0)); ?>€ a persona</span><?php endif; ?>
          <?php if ($dep) : ?><span>Partenza: <?php echo esc_html($dep); ?></span><?php endif; ?>
        </div>
        <div class="imp-tour-single-cta">
          <?php if ($it_url) : ?><a class="imp-btn imp-btn--yellow" href="<?php echo esc_url($it_url); ?>">VEDI ITINERARIO</a><?php endif; ?>
          <?php if ($quick_url) : ?><a class="imp-btn imp-btn--outline" href="<?php echo esc_url($quick_url); ?>">SCOPRI IN MODO RAPIDO</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php
$map_stops = function_exists('imp_get_tour_map_stops') ? imp_get_tour_map_stops($id) : array();
?>
<?php if (!empty($map_stops)) : ?>
  <section class="imp-tour-map-section">
    <div class="imp-container">
      <h2 class="imp-section-title">Itinerario sulla mappa</h2>
      <div class="imp-tour-map" data-stops='<?php echo esc_attr(wp_json_encode($map_stops)); ?>'></div>
    </div>
  </section>
<?php endif; ?>

  <div class="imp-tour-single-content">
    <div class="imp-container">
      <div class="imp-tour-single-body">
        <?php the_content(); ?>
      </div>
    </div>
  </div>
</main>
<?php get_footer(); ?>

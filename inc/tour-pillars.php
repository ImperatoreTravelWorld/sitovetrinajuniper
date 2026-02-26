<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Tour "pillars" (icons + percentage) shown in Tour cards.
 *
 * Meta keys (int 0-100):
 * - imp_pillar_cultura
 * - imp_pillar_enogastro
 * - imp_pillar_natura
 * - imp_pillar_wellness
 */

function imp_tour_pillars_definitions() {
  $base = trailingslashit( get_template_directory_uri() ) . 'assets/images/';
  return array(
    'cultura' => array(
      'label' => __('Cultura', 'imperatore'),
      'meta'  => 'imp_pillar_cultura',
      'icon'  => $base . 'pillar-cultura.png',
    ),
    'enogastro' => array(
      'label' => __('Esperienze Enogastronomiche', 'imperatore'),
      'meta'  => 'imp_pillar_enogastro',
      'icon'  => $base . 'pillar-enogastro.png',
    ),
    'natura' => array(
      'label' => __('Natura & Outdoors', 'imperatore'),
      'meta'  => 'imp_pillar_natura',
      'icon'  => $base . 'pillar-natura.png',
    ),
    'wellness' => array(
      'label' => __('Wellness', 'imperatore'),
      'meta'  => 'imp_pillar_wellness',
      'icon'  => $base . 'pillar-wellness.png',
    ),
  );
}

/**
 * Fallback: extract pillars from Gutenberg blocks in post content (single-tour blocks).
 * Looks for blocks: imperatore/pillar-cultura, imperatore/pillar-enogastro,
 * imperatore/pillar-natura, imperatore/pillar-wellness.
 *
 * @return array<int, array{key:string,label:string,icon:string,value:int}>
 */
function imp_get_tour_pillars_from_blocks($post_id) {
  if (!function_exists('parse_blocks')) return array();
  $content = (string) get_post_field('post_content', $post_id);
  if ($content === '') return array();

  $defs = imp_tour_pillars_definitions();
  $map = array(
    'imperatore/pillar-cultura'   => 'cultura',
    'imperatore/pillar-enogastro' => 'enogastro',
    'imperatore/pillar-natura'    => 'natura',
    'imperatore/pillar-wellness'  => 'wellness',
  );

  $out = array();

  $walk = function($blocks) use (&$walk, &$out, $map, $defs) {
    foreach ((array)$blocks as $b) {
      if (!is_array($b)) continue;

      $name = isset($b['blockName']) ? (string)$b['blockName'] : '';
      if ($name && isset($map[$name])) {
        $key = $map[$name];
        $attrs = isset($b['attrs']) && is_array($b['attrs']) ? $b['attrs'] : array();
        $raw = isset($attrs['percent']) ? $attrs['percent'] : 0;
        $val = intval($raw);
        $val = min(100, max(0, $val));
        if ($val > 0 && isset($defs[$key])) {
          $out[] = array(
            'key' => $key,
            'label' => (string) $defs[$key]['label'],
            'icon' => (string) $defs[$key]['icon'],
            'value' => $val,
          );
        }
      }

      if (!empty($b['innerBlocks'])) {
        $walk($b['innerBlocks']);
      }
    }
  };

  $walk(parse_blocks($content));

  // Dedupe by key (keep first occurrence)
  if (!empty($out)) {
    $seen = array();
    $uniq = array();
    foreach ($out as $p) {
      if (isset($seen[$p['key']])) continue;
      $seen[$p['key']] = true;
      $uniq[] = $p;
    }
    $out = $uniq;
  }

  return $out;
}


/**
 * Returns pillars with values set for a tour.
 * @return array<int, array{key:string,label:string,icon:string,value:int}>
 */
function imp_get_tour_pillars($post_id) {
  $defs = imp_tour_pillars_definitions();
  $out = array();
  foreach ($defs as $key => $d) {
    $raw = get_post_meta($post_id, $d['meta'], true);
    if ($raw === '' || $raw === null) continue;
    $val = intval($raw);
    if ($val <= 0) continue;
    $val = min(100, max(0, $val));
    $out[] = array(
      'key' => $key,
      'label' => (string) $d['label'],
      'icon' => (string) $d['icon'],
      'value' => $val,
    );
  }
  // Fallback: if no meta values are set, try to read from Gutenberg pillar blocks.
  if (empty($out)) {
    $out = imp_get_tour_pillars_from_blocks($post_id);
  }

  return $out;
}

/**
 * Renders the pillars HTML block.
 */
function imp_render_tour_pillars($post_id) {
  $pillars = imp_get_tour_pillars($post_id);
  if (empty($pillars)) return '';

  ob_start();
  ?>
  <div class="imp-tour-pillars" role="list">
    <?php foreach ($pillars as $p) : ?>
      <div class="imp-tour-pillar" role="listitem" data-key="<?php echo esc_attr($p['key']); ?>">
        <div class="imp-tour-pillar__icon">
          <img src="<?php echo esc_url($p['icon']); ?>" alt="" aria-hidden="true" loading="lazy" />
        </div>
        <div class="imp-tour-pillar__label"><?php echo esc_html($p['label']); ?></div>
        <div class="imp-tour-pillar__pct"><?php echo esc_html($p['value']); ?>%</div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php
  return (string) ob_get_clean();
}

/**
 * Archive display mode for pillars:
 * - none: hide completely
 * - icons: icon + label + percent (legacy)
 * - bars: compact progress bars (no text overlap)
 */
function imp_get_archive_pillars_mode() {
  $mode = get_option('imp_archive_pillars_mode', 'bars');
  $mode = is_string($mode) ? $mode : 'bars';
  $mode = strtolower(trim($mode));
  if (!in_array($mode, array('none','icons','bars'), true)) {
    $mode = 'bars';
  }
  return $mode;
}

/**
 * Render pillars for archive cards according to configured mode.
 */
function imp_render_tour_pillars_archive($post_id) {
  $mode = imp_get_archive_pillars_mode();
  if ($mode === 'none') return '';

  $pillars = imp_get_tour_pillars($post_id);
  if (empty($pillars)) return '';

  if ($mode === 'icons') {
    return imp_render_tour_pillars($post_id);
  }

  // bars mode
  ob_start();
  ?>
  <div class="imp-tour-pillars imp-tour-pillars--bars" role="list">
    <?php foreach ($pillars as $p) :
      $val = intval($p['value']);
      $val = min(100, max(0, $val));
      ?>
      <div class="imp-tour-pillar" role="listitem" data-key="<?php echo esc_attr($p['key']); ?>">
        <div class="imp-tour-pillar__top">
          <span class="imp-tour-pillar__icon" aria-hidden="true">
            <img src="<?php echo esc_url($p['icon']); ?>" alt="" loading="lazy" />
          </span>
          <span class="imp-tour-pillar__label"><?php echo esc_html($p['label']); ?></span>
          <span class="imp-tour-pillar__pct"><?php echo esc_html($val); ?>%</span>
        </div>
        <div class="imp-tour-pillar__bar" aria-hidden="true">
          <span class="imp-tour-pillar__bar-fill" style="width: <?php echo esc_attr($val); ?>%"></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php
  return (string) ob_get_clean();
}

<?php

define('IMP_THEME_VERSION','1.5.8.28');
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'imperatore-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800&display=swap', array(), null );
    $theme = wp_get_theme();
    wp_enqueue_style(
        'imperatore-kadence-main',
        get_theme_file_uri( 'assets/css/main.css' ),
        array(),
        $theme->get( 'Version' )
    );
    wp_enqueue_script(
        'imperatore-kadence-mega',
        get_theme_file_uri( 'assets/js/mega-menu.js' ),
        array(),
        $theme->get( 'Version' ),
        true
    );
    // Tours filters UI (accordion, active highlighting, show more/less)
    if ( is_post_type_archive('tour') || is_tax(array('destination','city','modality','duration','characteristic','hotel','treatment','highlight','tour_tag')) ) {
        wp_enqueue_script(
            'imperatore-tours-filters-ui',
            get_theme_file_uri( 'assets/js/tours-filters-ui.js' ),
            array(),
            $theme->get( 'Version' ),
            true
        );
    }

});

/**
 * Mega menu renderer.
 *
 * Expected menu structure (depth 4):
 * - Level 0: header items (e.g. Destinazioni, Esperienze, ...)
 * - Level 1: mega column 1 items
 * - Level 2: mega column 2 items (children of hovered level 1)
 * - Level 3: mega column 3 items (children of hovered level 2)
 */

function imp_build_menu_tree( $items ) {
    $by_id = array();
    foreach ( $items as $it ) {
        $it->imp_children = array();
        $by_id[ (int) $it->ID ] = $it;
    }
    $root = array();
    foreach ( $items as $it ) {
        $pid = (int) $it->menu_item_parent;
        if ( $pid && isset( $by_id[ $pid ] ) ) {
            $by_id[ $pid ]->imp_children[] = $it;
        } else {
            $root[] = $it;
        }
    }
    return $root;
}

function imp_render_tree_ul( $nodes, $depth = 0 ) {
    if ( empty( $nodes ) ) return '';
    $html = '<ul class="imp-mega__tree" data-depth="' . (int) $depth . '">';
    foreach ( $nodes as $n ) {
        $id    = (int) $n->ID;
        $url   = esc_url( $n->url );
        $title = esc_html( $n->title );
        $html .= '<li class="imp-mega__node" data-node-id="' . $id . '">';
        $html .= '<a class="imp-mega__node-link" href="' . $url . '" data-node-id="' . $id . '">' . $title . '</a>';
        if ( ! empty( $n->imp_children ) ) {
            $html .= imp_render_tree_ul( $n->imp_children, $depth + 1 );
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

function imp_primary_nav_shortcode( $atts ) {
    $atts = shortcode_atts(
        array(
            'menu' => 'primary',
        ),
        $atts,
        'imp_primary_nav'
    );

    $items = wp_get_nav_menu_items( $atts['menu'] );
    if ( empty( $items ) ) {
        return '<!-- imp_primary_nav: menu not found / empty -->';
    }

    $tree = imp_build_menu_tree( $items );

    $out  = '<nav class="imp-nav" aria-label="Primary">';
    $out .= '<div class="imp-nav__links">';

    foreach ( $tree as $top ) {
        $top_id = (int) $top->ID;
        $has_mega = ! empty( $top->imp_children );
        $target_id = 'mega-item-' . $top_id;

        $attrs = '';
        if ( $has_mega ) {
            $attrs .= ' data-mega-target="' . esc_attr( $target_id ) . '"';
        }

        $out .= '<a class="imp-nav__link" href="' . esc_url( $top->url ) . '"' . $attrs . '>' . esc_html( $top->title ) . '</a>';
    }

    $out .= '</div>';
    $out .= '</nav>';

    // Panels.
    foreach ( $tree as $top ) {
        if ( empty( $top->imp_children ) ) continue;

        $top_id = (int) $top->ID;
        $panel_id = 'mega-item-' . $top_id;

        $out .= '<div class="imp-mega" id="' . esc_attr( $panel_id ) . '">';
        $out .= '  <div class="imp-container imp-mega__inner">';
        $out .= '    <div class="imp-mega__cols" role="presentation">';
        $out .= '      <div class="imp-mega__col" data-col="1" aria-label="Livello 1"></div>';
        $out .= '      <div class="imp-mega__col" data-col="2" aria-label="Livello 2"></div>';
        $out .= '      <div class="imp-mega__col" data-col="3" aria-label="Livello 3"></div>';
        $out .= '    </div>';

        // Hidden tree used by JS to populate the 3 columns.
        $out .= '    <div class="imp-mega__treewrap" hidden>';
        $out .= imp_render_tree_ul( $top->imp_children, 1 );
        $out .= '    </div>';

        $out .= '  </div>';
        $out .= '</div>';
    }

    return $out;
}

add_shortcode( 'imp_primary_nav', 'imp_primary_nav_shortcode' );

// Admin: slugs/URLs for archives + UX helpers
require_once get_template_directory() . '/inc/admin-archive-urls.php';
require_once get_template_directory() . '/inc/admin-upload-another.php';

// Tours (CPT + admin fields + filters)

require_once get_template_directory() . '/inc/tours-cpt.php';
require_once get_template_directory() . '/inc/tours-metabox.php';
require_once get_template_directory() . '/inc/tours-filters.php';
require_once get_template_directory() . "/inc/cdn-gallery.php";

require_once get_template_directory() . '/inc/tours-map.php';

require_once get_template_directory() . '/inc/tours-shortcodes.php';

// Tour preview "pillars" (icons + percentage)
require_once get_template_directory() . '/inc/tour-pillars.php';

// Theme blocks
require_once get_template_directory() . '/inc/blocks.php';

// Tour metaboxes (gallery + departures)
require_once get_template_directory() . '/inc/tour-metaboxes.php';


// Tour gallery frontend
add_action('wp_enqueue_scripts', function(){
  if (is_singular('tour')) {
    wp_enqueue_script('imp-tour-gallery', get_template_directory_uri() . '/assets/js/tour-gallery.js', array(), defined('IMP_THEME_VERSION') ? IMP_THEME_VERSION : '1.0.0', true);
  }
});


/**
 * Create pre-configured pages on theme activation.
 */
function imp_create_tours_pages() {
  $existing = get_page_by_path('tours');
  if (!$existing) {
    wp_insert_post(array(
      'post_title'   => 'Tours',
      'post_name'    => 'tours',
      'post_status'  => 'publish',
      'post_type'    => 'page',
      'post_content' => "[imp_tours_archive]\n",
    ));
  }
}
add_action('after_switch_theme', 'imp_create_tours_pages');
/**
 * Prevent WP from adding <p> and <br> tags inside our tours shortcodes output.
 */
function imp_clean_tours_shortcode_output($output, $tag, $attr) {
  if ($tag !== 'imp_tours_archive' && $tag !== 'imp_tour_single' && $tag !== 'imp_tour_map') {
    return $output;
  }

  // Remove <p> and <br> that WP sometimes injects around anchors/buttons.
  $output = preg_replace('#<br\s*/?>#i', '', $output);
  $output = preg_replace('#</a>\s*<p>\s*</p>#i', '</a>', $output);
  $output = preg_replace('#<p>\s*(<a[^>]+class="imp-btn[^"]*"[^>]*>.*?</a>)\s*</p>#is', '$1', $output);
  $output = preg_replace('#<p>\s*</p>#i', '', $output);

  return $output;
}
add_filter('do_shortcode_tag', 'imp_clean_tours_shortcode_output', 10, 3);


/**
 * Performance: preconnect to Google Fonts.
 */
add_filter('wp_resource_hints', function($urls, $relation_type) {
  if ($relation_type === 'preconnect') {
    $urls[] = 'https://fonts.googleapis.com';
    $urls[] = array('href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous');
  }
  return $urls;
}, 10, 2);

/**
 * Load fonts in the block editor too.
 */
add_action('enqueue_block_editor_assets', function() {
  wp_enqueue_style( 'imperatore-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800&display=swap', array(), null );
});


// Flush rewrite rules on theme switch (for /tour/ and taxonomies)
add_action("after_switch_theme", function(){
  if (function_exists("imp_register_tours_cpt")) imp_register_tours_cpt();
  flush_rewrite_rules();
});



// Flush rewrite rules on theme switch to avoid 404 on CPT/tax pretty URLs.
function imp_flush_rewrite_on_theme_switch() {
  if ( function_exists('imp_register_tours_cpt') ) {
    imp_register_tours_cpt();
  }
  flush_rewrite_rules();
}
add_action('after_switch_theme', 'imp_flush_rewrite_on_theme_switch', 20);

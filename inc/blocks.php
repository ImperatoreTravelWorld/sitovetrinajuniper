<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Register Gutenberg blocks shipped with the theme.
 */
function imp_register_theme_blocks() {
  $base = get_template_directory() . '/blocks';
  $itinerary = $base . '/itinerary';
  if ( file_exists( $itinerary . '/block.json' ) ) {
    register_block_type( $itinerary );
  }

  // Tour Map block
  $tour_map = $base . '/tour-map';
  if ( file_exists( $tour_map . '/block.json' ) ) {
    register_block_type( $tour_map );
  }

  // Tour Highlights Text block
  $hl = $base . '/tour-highlights-text';
  if ( file_exists( $hl . '/block.json' ) ) {
    register_block_type( $hl );
  }

  // Tour pillar blocks (icon + label + percent)
  $pillars = array('pillar-cultura','pillar-enogastro','pillar-natura','pillar-wellness');
  foreach ($pillars as $slug) {
    $dir = $base . '/' . $slug;
    if ( file_exists( $dir . '/block.json' ) ) {
      register_block_type( $dir );
    }
  }
}
add_action('init', 'imp_register_theme_blocks');

/**
 * Expose theme asset URLs to block editor scripts (no build step).
 */
function imp_blocks_editor_globals() {
  $data = array(
    'iconsBase' => trailingslashit( get_template_directory_uri() ) . 'assets/images/',
  );
  wp_add_inline_script(
    'wp-blocks',
    'window.ImperatoreThemeBlocks = ' . wp_json_encode($data) . ';',
    'before'
  );
}
add_action('enqueue_block_editor_assets', 'imp_blocks_editor_globals');
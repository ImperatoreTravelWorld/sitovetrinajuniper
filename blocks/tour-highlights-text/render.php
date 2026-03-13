<?php
if ( ! defined('ABSPATH') ) { exit; }

$title = isset($attributes['title']) ? (string) $attributes['title'] : 'Tour Highlights';
$show_title = ! empty($attributes['showTitle']);

$post_id = get_the_ID();
if ( ! $post_id ) { return ''; }

$terms = get_the_terms($post_id, 'highlight');
if ( empty($terms) || is_wp_error($terms) ) { return ''; }

usort($terms, function($a,$b){
  return strcasecmp($a->name, $b->name);
});

ob_start(); ?>
<div class="imp-highlights-text" aria-label="<?php echo esc_attr__('Highlights', 'imperatore'); ?>">
  <?php if ( $show_title && $title !== '' ) : ?>
    <span class="imp-highlights-text__title"><?php echo esc_html($title); ?></span>
  <?php endif; ?>
  <?php foreach ( $terms as $t ) : ?>
    <span class="imp-highlights-text__chip"><?php echo esc_html($t->name); ?></span>
  <?php endforeach; ?>
</div>
<?php
return (string) ob_get_clean();

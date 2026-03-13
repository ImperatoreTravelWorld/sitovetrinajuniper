<?php
/**
 * Template Name: Pagina Tour (Presentazione)
 * Template Post Type: page
 *
 * Pagina di presentazione generica dei tour.
 * Contenuto gestibile dal backend (Gutenberg/Kadence Blocks): hero, testo, sezioni, CTA, ecc.
 */
get_header();

if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
  <main class="imp-tours-landing">
    <header class="imp-tours-landing-hero">
      <div class="imp-tours-landing-hero__inner">
        <h1 class="imp-tours-landing-title"><?php the_title(); ?></h1>
      </div>
    </header>

    <section class="imp-tours-landing-content">
      <div class="imp-tours-landing-container">
        <?php the_content(); ?>
      </div>
    </section>
  </main>
<?php endwhile; endif;

get_footer();

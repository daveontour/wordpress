<?php
/**
 * The template for displaying full width pages.
 *
 * Template Name: Full width - No Title
 *
 * @package store
 */

get_header(); ?>

<div id="primary-mono" class="content-area col-md-12 page">
		<main id="main" class="site-main" role="main">

			<?php while ( have_posts() ) : the_post(); ?>

				<?php get_template_part( 'content', 'pagenotitle' ); ?>

				<?php
					// If comments are open or we have at least one comment, load up the comment template
					if ( comments_open() || get_comments_number() ) :
						comments_template();
					endif;
				?>

			<?php endwhile; // end of the loop. ?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_footer(); ?>

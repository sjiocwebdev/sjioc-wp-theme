<?php get_header(); ?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<div class="page-hero"><div class="container"><h1><?php the_title(); ?></h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › <?php the_title(); ?></p></div></div>
<div class="bg-cream"><div class="sec container" style="max-width:860px"><div class="entry-content"><?php the_content(); ?></div></div></div>
<?php endwhile; endif; ?>
<?php sjioc_footer(); get_footer(); ?>

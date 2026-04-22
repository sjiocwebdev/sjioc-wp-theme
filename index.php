<?php get_header(); ?>
<div class="page-hero"><div class="container"><h1><?php echo is_home() ? esc_html(get_option('blogname')) : esc_html(get_the_title()); ?></h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></p></div></div>
<div class="bg-cream"><div class="sec container">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article style="margin-bottom:2rem;padding-bottom:2rem;border-bottom:1px solid var(--border)">
  <h2 style="font-family:'Playfair Display',serif;color:var(--cr);margin-bottom:.5rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
  <p style="font-size:.82rem;color:var(--tl);margin-bottom:.75rem"><?php the_date(); ?></p>
  <div><?php the_excerpt(); ?></div>
</article>
<?php endwhile; else: ?><p><?php esc_html_e('No posts found.','sjioc'); ?></p><?php endif; ?>
</div></div>
<?php sjioc_footer(); get_footer(); ?>

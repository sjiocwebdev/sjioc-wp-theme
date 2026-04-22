<?php
/**
 * Template Name: Photos / Gallery Page
 */
get_header();
?>
<div class="page-hero"><div class="container"><h1>Photos</h1><p class="breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › Photos</p></div></div>
<div class="bg-cream"><div class="sec container">
  <div class="tc" style="margin-bottom:42px">
    <span class="stag">Our Parish Life</span>
    <h2 class="stitle">Photo Gallery</h2>
    <div class="divider"></div>
    <p class="slead">Glimpses of worship, fellowship, and community life at <?php echo esc_html(sjioc_abbr()); ?> Delaware Valley.</p>
  </div>
  <div class="filter-bar" role="group" aria-label="Filter gallery">
    <button class="filter-btn is-active" data-cat="all">All Photos</button>
    <button class="filter-btn" data-cat="worship">Worship</button>
    <button class="filter-btn" data-cat="events">Events</button>
    <button class="filter-btn" data-cat="ministries">Ministries</button>
    <button class="filter-btn" data-cat="community">Community</button>
  </div>
  <div class="gallery-grid" id="gallery-grid">
    <?php
    $gal_q = new WP_Query(['post_type'=>'sjioc_gallery','posts_per_page'=>18]);
    if ($gal_q->have_posts()) :
      while ($gal_q->have_posts()) : $gal_q->the_post();
        $cat  = get_post_meta(get_the_ID(),'photo_category',true);
        $full = get_the_post_thumbnail_url(get_the_ID(),'large');
        $wide = get_post_meta(get_the_ID(),'gallery_wide',true) ? ' g-wide' : '';
        $tall = get_post_meta(get_the_ID(),'gallery_tall',true) ? ' g-tall' : '';
    ?>
    <div class="gallery-item<?php echo esc_attr($wide.$tall); ?>" data-cat="<?php echo esc_attr($cat); ?>"
         onclick="sjiocOpenLightbox('<?php echo esc_url($full); ?>','<?php echo esc_attr(get_the_title()); ?>')"
         role="button" tabindex="0" aria-label="<?php echo esc_attr('View '.get_the_title()); ?>"
         onkeydown="if(event.key==='Enter')sjiocOpenLightbox('<?php echo esc_url($full); ?>','<?php echo esc_attr(get_the_title()); ?>')">
      <?php echo get_the_post_thumbnail(get_the_ID(),'sjioc-card',['loading'=>'lazy']); ?>
      <div class="gallery-overlay"><span><?php the_title(); ?></span></div>
    </div>
    <?php endwhile; wp_reset_postdata();
    else:
      $imgs=[['https://images.unsplash.com/photo-1548625149-720754956904?w=1200&q=85','https://images.unsplash.com/photo-1548625149-720754956904?w=800&q=70','worship','Holy Qurbana','g-wide'],['https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=800&q=85','https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=500&q=70','ministries','Youth Ministry',''],['https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&q=85','https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=500&q=70','ministries','Sunday School',''],['https://images.unsplash.com/photo-1544535830-9df3f56fff6a?w=800&q=85','https://images.unsplash.com/photo-1544535830-9df3f56fff6a?w=500&q=70','events','Parish Fellowship','g-tall'],['https://images.unsplash.com/photo-1573497620053-ea5300f94f21?w=800&q=85','https://images.unsplash.com/photo-1573497620053-ea5300f94f21?w=500&q=70','ministries',"Women's Fellowship",''],['https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=800&q=85','https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=500&q=70','worship','FOCUS Choir',''],['https://images.unsplash.com/photo-1579545670417-69fb3b9085b1?w=1200&q=85','https://images.unsplash.com/photo-1579545670417-69fb3b9085b1?w=700&q=70','events','Feast Day Celebration','g-wide'],['https://images.unsplash.com/photo-1593113598332-cd288d649433?w=800&q=85','https://images.unsplash.com/photo-1593113598332-cd288d649433?w=500&q=70','community','Community Outreach',''],['https://images.unsplash.com/photo-1515162305285-0293e4767cc2?w=800&q=85','https://images.unsplash.com/photo-1515162305285-0293e4767cc2?w=500&q=70','community','Parish Picnic','']];
      foreach ($imgs as $img): ?>
    <div class="gallery-item<?php echo $img[4]?' '.$img[4]:''; ?>" data-cat="<?php echo esc_attr($img[2]); ?>"
         onclick="sjiocOpenLightbox('<?php echo esc_url($img[0]); ?>','<?php echo esc_attr($img[3]); ?>')"
         role="button" tabindex="0" aria-label="<?php echo esc_attr('View '.$img[3]); ?>"
         onkeydown="if(event.key==='Enter')sjiocOpenLightbox('<?php echo esc_url($img[0]); ?>','<?php echo esc_attr($img[3]); ?>')">
      <img src="<?php echo esc_url($img[1]); ?>" alt="<?php echo esc_attr($img[3]); ?>" loading="lazy">
      <div class="gallery-overlay"><span><?php echo esc_html($img[3]); ?></span></div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div></div>

<!-- Lightbox -->
<div class="lightbox" id="sjioc-lightbox" onclick="sjiocCloseLightbox(event)" role="dialog" aria-modal="true" aria-label="Photo viewer">
  <button class="lb-close" id="lb-close" onclick="sjiocCloseLightbox()" aria-label="Close photo viewer">&times;</button>
  <img id="lb-img" src="" alt="">
</div>

<?php sjioc_footer(); get_footer(); ?>

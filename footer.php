<?php
// Load celebrations cache once — used for both the badge and the panel below
$_sjioc_celeb       = get_option('sjioc_celebrations_cache', []);
$_sjioc_bdays       = $_sjioc_celeb['birthdays']    ?? [];
$_sjioc_annivs      = $_sjioc_celeb['anniversaries'] ?? [];
$_sjioc_week_label  = $_sjioc_celeb['week_label']   ?? '';
$_sjioc_celeb_total = count($_sjioc_bdays) + count($_sjioc_annivs);
?>
</main><!-- /#main-content -->

<!-- ═══════════════════════════════════════════════
     BOTTOM WIDGET BAR
═══════════════════════════════════════════════ -->
<div id="widget-bar" role="toolbar" aria-label="<?php esc_attr_e('Quick Access Widgets','sjioc'); ?>">

  <!-- Tab ① Contacts -->
  <div class="wbar-tab" id="tab-contacts" role="button" tabindex="0" aria-controls="panel-contacts" aria-expanded="false" onclick="sjiocTogglePanel('contacts')" onkeydown="if(event.key==='Enter'||event.key===' ')sjiocTogglePanel('contacts')">
    <span class="wbar-icon" aria-hidden="true">👥</span>
    <span class="wbar-label"><?php esc_html_e('Contacts','sjioc'); ?></span>
    <?php $contact_count = wp_count_posts('sjioc_contact')->publish; ?>
    <span class="wbar-badge" id="badge-contacts" aria-label="<?php echo esc_attr($contact_count); ?> contacts"><?php echo esc_html($contact_count ?: ''); ?></span>
  </div>

  <!-- Tab ② Celebrations -->
  <div class="wbar-tab" id="tab-celeb" role="button" tabindex="0" aria-controls="panel-celeb" aria-expanded="false" onclick="sjiocTogglePanel('celeb')" onkeydown="if(event.key==='Enter'||event.key===' ')sjiocTogglePanel('celeb')">
    <span class="wbar-icon" aria-hidden="true">🎂</span>
    <span class="wbar-label"><?php esc_html_e('Celebrations','sjioc'); ?></span>
    <span class="wbar-badge" id="badge-celeb"><?php echo $_sjioc_celeb_total ?: ''; ?></span>
  </div>

  <!-- Tab ③ Chat -->
  <div class="wbar-tab" id="tab-chat" role="button" tabindex="0" aria-controls="panel-chat" aria-expanded="false" onclick="sjiocTogglePanel('chat')" onkeydown="if(event.key==='Enter'||event.key===' ')sjiocTogglePanel('chat')">
    <span class="wbar-icon" aria-hidden="true">💬</span>
    <span class="wbar-label"><?php esc_html_e('Chat','sjioc'); ?></span>
    <span class="wpulse" aria-hidden="true"></span>
  </div>

  <!-- Scrolling ticker -->
  <div class="wbar-ticker" aria-hidden="true">
    <div class="ticker-track">
      <span>📍 <strong><?php echo esc_html(sjioc_address()); ?></strong></span>
      <span>📞 <strong><?php echo esc_html(sjioc_phone()); ?></strong></span>
      <span>🕐 Holy Qurbana <strong><?php echo esc_html(sjioc_qurbana()); ?></strong> · Sunday School <strong><?php echo esc_html(sjioc_school()); ?></strong></span>
      <span>✉ <strong><?php echo esc_html(sjioc_email()); ?></strong></span>
      <span>Serving: Delaware County · Chester County · New Jersey · Delaware · Bucks County · Philadelphia County</span>
      <span>📍 <strong><?php echo esc_html(sjioc_address()); ?></strong></span>
      <span>📞 <strong><?php echo esc_html(sjioc_phone()); ?></strong></span>
      <span>🕐 Holy Qurbana <strong><?php echo esc_html(sjioc_qurbana()); ?></strong> · Sunday School <strong><?php echo esc_html(sjioc_school()); ?></strong></span>
    </div>
  </div>

  <div class="wbar-azure" aria-label="Hosted on Microsoft Azure">☁ <span>Azure</span></div>
</div>

<!-- ═══════════════════════════════════════════════
     PANEL ① — CONTACTS DIRECTORY
═══════════════════════════════════════════════ -->
<div class="widget-panel" id="panel-contacts" role="dialog" aria-label="Parish Directory" aria-modal="true">
  <div class="panel-header">
    <div>
      <h3>👥 <?php esc_html_e('Parish Directory','sjioc'); ?></h3>
      <p><?php echo esc_html(sjioc_abbr()); ?> Delaware Valley</p>
    </div>
    <button class="panel-close" onclick="sjiocClosePanel('contacts')" aria-label="<?php esc_attr_e('Close contacts','sjioc'); ?>">&times;</button>
  </div>
  <div class="panel-body">
    <input class="c-search" type="search" id="c-search" placeholder="🔍  <?php esc_attr_e('Search contacts…','sjioc'); ?>" oninput="sjiocFilterContacts(this.value)" aria-label="Search parish contacts">
    <div id="contacts-list">
      <?php
      $contacts = get_posts([
          'post_type'      => 'sjioc_contact',
          'posts_per_page' => 30,
          'meta_key'       => 'contact_order',
          'orderby'        => 'meta_value_num',
          'order'          => 'ASC',
      ]);
      // Pinned contacts first, then rest
      usort($contacts, function($a, $b) {
          $pa = get_post_meta($a->ID, 'contact_pinned', true);
          $pb = get_post_meta($b->ID, 'contact_pinned', true);
          if ($pa === $pb) return 0;
          return $pa ? -1 : 1;
      });
      if ($contacts) :
          foreach ($contacts as $c) :
              $role   = get_post_meta($c->ID, 'contact_role',   true);
              $type   = get_post_meta($c->ID, 'contact_type',   true);
              $pinned = get_post_meta($c->ID, 'contact_pinned', true);
              $img    = get_the_post_thumbnail_url($c->ID, 'sjioc-square');
              $init   = strtoupper(substr($c->post_title, 0, 1));
              $contact_url = $type ? esc_url(home_url('/contact-us/?to=' . $type)) : '';
      ?>
      <div class="c-item" data-name="<?php echo esc_attr(strtolower($c->post_title . ' ' . $role)); ?>">
        <div class="c-avatar<?php echo $pinned ? ' gold' : ''; ?>">
          <?php if ($img): ?>
            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($c->post_title); ?>">
          <?php else: echo esc_html($init); endif; ?>
        </div>
        <div class="c-info">
          <h4><?php echo esc_html($c->post_title); ?></h4>
          <p><?php echo esc_html($role); ?></p>
        </div>
        <div class="c-actions">
          <?php if ($contact_url): ?>
            <button class="c-btn" title="<?php esc_attr_e('Contact','sjioc'); ?>"
              onclick="window.location='<?php echo $contact_url; ?>'">✉</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach;
      else: ?>
      <p style="padding:16px;color:#888;text-align:center;font-size:13px">
        No contacts added yet.<br>
        <?php if (current_user_can('manage_options')): ?>
          <a href="<?php echo esc_url(admin_url('post-new.php?post_type=sjioc_contact')); ?>" style="color:var(--go)">+ Add first contact</a>
        <?php endif; ?>
      </p>
      <?php endif; ?>
    </div>
  </div>
  <div class="panel-footer">
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="panel-footer-btn" style="text-align:center"><?php esc_html_e('Contact Us','sjioc'); ?></a>
    <?php if (current_user_can('manage_options')): ?>
    <a href="<?php echo esc_url(admin_url('edit.php?post_type=sjioc_contact')); ?>" class="panel-footer-btn gold" style="text-align:center">⚙ <?php esc_html_e('Manage','sjioc'); ?></a>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     PANEL ② — BIRTHDAYS & ANNIVERSARIES
═══════════════════════════════════════════════ -->
<div class="widget-panel" id="panel-celeb" role="dialog" aria-label="Birthdays and Anniversaries" aria-modal="true">
  <div class="panel-header">
    <div>
      <h3>🎂 <?php esc_html_e('Celebrations','sjioc'); ?></h3>
      <p><?php esc_html_e('Birthdays & Wedding Anniversaries','sjioc'); ?></p>
    </div>
    <button class="panel-close" onclick="sjiocClosePanel('celeb')" aria-label="<?php esc_attr_e('Close','sjioc'); ?>">&times;</button>
  </div>
  <div class="panel-body">
    <!-- Type tabs -->
    <div class="cel-tabs" role="tablist">
      <button class="cel-tab is-active" role="tab" onclick="sjiocFilterCeleb('all',this)"><?php esc_html_e('All','sjioc'); ?></button>
      <button class="cel-tab" role="tab" onclick="sjiocFilterCeleb('bday',this)">🎂 <?php esc_html_e('Birthdays','sjioc'); ?></button>
      <button class="cel-tab" role="tab" onclick="sjiocFilterCeleb('anniv',this)">💍 <?php esc_html_e('Anniversaries','sjioc'); ?></button>
    </div>

    <?php if ($_sjioc_week_label): ?>
    <p style="padding:8px 16px 0;font-size:11px;color:var(--tl);text-align:center">
      <?php echo esc_html($_sjioc_week_label); ?>
    </p>
    <?php endif; ?>

    <div id="celeb-list">
      <?php if ($_sjioc_bdays || $_sjioc_annivs): ?>

        <?php foreach ($_sjioc_bdays as $cel): ?>
        <div class="cel-row" data-t="bday">
          <div class="cel-badge bday">
            <span class="cmon"><?php echo esc_html($cel['month_name']); ?></span>
            <span class="cday"><?php echo esc_html($cel['day']); ?></span>
          </div>
          <div class="cel-info">
            <span class="cel-type">Birthday 🎂</span>
            <h4><?php echo esc_html($cel['name']); ?></h4>
          </div>
        </div>
        <?php endforeach; ?>

        <?php foreach ($_sjioc_annivs as $cel): ?>
        <div class="cel-row" data-t="anniv">
          <div class="cel-badge anniv">
            <span class="cmon"><?php echo esc_html($cel['month_name']); ?></span>
            <span class="cday"><?php echo esc_html($cel['day']); ?></span>
          </div>
          <div class="cel-info">
            <span class="cel-type">Anniversary 💍</span>
            <h4><?php echo esc_html($cel['names']); ?></h4>
          </div>
        </div>
        <?php endforeach; ?>

      <?php else: ?>
        <p style="padding:20px 16px;color:#888;text-align:center;font-size:13px">
          No celebrations this week 🙏<br>
          <span style="font-size:11px;color:var(--tl)">Cache refreshes every Sunday</span>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     PANEL ③ — LIVE CHAT
═══════════════════════════════════════════════ -->
<div class="widget-panel" id="panel-chat" role="dialog" aria-label="Parish Chat" aria-modal="true">
  <div class="panel-header">
    <div>
      <h3>💬 <?php esc_html_e('Parish Chat','sjioc'); ?></h3>
      <p><?php esc_html_e('Ask about services, parking, or parish info','sjioc'); ?></p>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block" title="Online" aria-label="Online"></span>
      <button class="panel-close" onclick="sjiocClosePanel('chat')" aria-label="<?php esc_attr_e('Close chat','sjioc'); ?>">&times;</button>
    </div>
  </div>
  <div class="chat-messages" id="chatMessages" aria-live="polite" aria-atomic="false">
    <div class="cmsg bot">
      <div class="bubble"><?php esc_html_e('Glory to God! 🙏 Welcome to St. John\'s Indian Orthodox Church of Delaware Valley. How can we help you today?','sjioc'); ?></div>
      <span class="ctime"><?php esc_html_e('Just now','sjioc'); ?></span>
    </div>
  </div>
  <div class="quick-replies" id="quickReplies">
    <button class="qr-btn" onclick="sjiocQuickSend('Service Times')"><?php esc_html_e('Service Times','sjioc'); ?></button>
    <button class="qr-btn" onclick="sjiocQuickSend('Location')"><?php esc_html_e('Location','sjioc'); ?></button>
    <button class="qr-btn" onclick="sjiocQuickSend('Upcoming Events')"><?php esc_html_e('Events','sjioc'); ?></button>
    <button class="qr-btn" onclick="sjiocQuickSend('Join a Ministry')"><?php esc_html_e('Ministries','sjioc'); ?></button>
    <button class="qr-btn" onclick="sjiocQuickSend('Contact the Vicar')"><?php esc_html_e('Contact Vicar','sjioc'); ?></button>
  </div>
  <div class="chat-input-row">
    <input class="chat-input" id="chatInput" type="text" placeholder="<?php esc_attr_e('Type your message…','sjioc'); ?>" onkeydown="if(event.key==='Enter')sjiocSendChat()" aria-label="<?php esc_attr_e('Chat message','sjioc'); ?>">
    <button class="chat-send" onclick="sjiocSendChat()" aria-label="<?php esc_attr_e('Send message','sjioc'); ?>">➤</button>
  </div>
</div>

<!-- Toast notification -->
<div class="site-toast" id="siteToast" role="alert" aria-live="assertive"></div>

<!-- Back to top -->
<button id="back-to-top" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <polyline points="18 15 12 9 6 15"></polyline>
  </svg>
</button>

<?php wp_footer(); ?>
</body>
</html>

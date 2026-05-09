</main><!-- /#main-content -->

<?php sjioc_footer(); ?>

<!-- ═══════════════════════════════════════════════
     BOTTOM WIDGET BAR
═══════════════════════════════════════════════ -->
<div id="widget-bar" role="toolbar" aria-label="<?php esc_attr_e('Quick Access Widgets','sjioc'); ?>">

  <!-- Tab ① Contacts -->
  <div class="wbar-tab" id="tab-contacts" role="button" tabindex="0" aria-controls="panel-contacts" aria-expanded="false" onclick="sjiocTogglePanel('contacts')" onkeydown="if(event.key==='Enter'||event.key===' ')sjiocTogglePanel('contacts')">
    <span class="wbar-icon" aria-hidden="true">👥</span>
    <span class="wbar-label"><?php esc_html_e('Contacts','sjioc'); ?></span>
    <span class="wbar-badge" id="badge-contacts" aria-label="8 contacts">8</span>
  </div>

  <!-- Tab ② Celebrations -->
  <div class="wbar-tab" id="tab-celeb" role="button" tabindex="0" aria-controls="panel-celeb" aria-expanded="false" onclick="sjiocTogglePanel('celeb')" onkeydown="if(event.key==='Enter'||event.key===' ')sjiocTogglePanel('celeb')">
    <span class="wbar-icon" aria-hidden="true">🎂</span>
    <span class="wbar-label"><?php esc_html_e('Celebrations','sjioc'); ?></span>
    <span class="wbar-badge" id="badge-celeb">3</span>
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
      <span>Serving: Exton · West Chester · Upper Darby · King of Prussia · Springfield · Drexel Hill · Glen Mills</span>
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
      // Try WP query first; if no custom contacts exist, show static fallback
      $contacts = get_posts(['post_type'=>'sjioc_contact','posts_per_page'=>20,'orderby'=>'title','order'=>'ASC']);
      if ($contacts) :
        foreach ($contacts as $c) :
          $phone = get_post_meta($c->ID,'contact_phone',true);
          $email = get_post_meta($c->ID,'contact_email',true);
          $role  = get_post_meta($c->ID,'contact_role', true);
          $img   = get_the_post_thumbnail_url($c->ID,'sjioc-square');
          $init  = strtoupper(substr($c->post_title,0,1));
      ?>
      <div class="c-item" data-name="<?php echo esc_attr(strtolower($c->post_title.' '.$role)); ?>">
        <div class="c-avatar"><?php if($img):?><img src="<?php echo esc_url($img);?>" alt="<?php echo esc_attr($c->post_title);?>"><?php else: echo esc_html($init); endif;?></div>
        <div class="c-info"><h4><?php echo esc_html($c->post_title); ?></h4><p><?php echo esc_html($role); ?></p></div>
        <div class="c-actions">
          <?php if($phone):?><button class="c-btn" title="Call" onclick="window.location='tel:<?php echo esc_attr(preg_replace('/\D/','',$phone)); ?>'">📞</button><?php endif;?>
          <?php if($email):?><button class="c-btn" title="Email" onclick="window.location='mailto:<?php echo esc_attr($email); ?>'">✉</button><?php endif;?>
        </div>
      </div>
      <?php endforeach; else:
      // Static fallback contacts ?>
      <?php
      $static = [
        ['img'=>'https://sjioc.org/images/TojoBaby-1710825551.png','init'=>'TB','name'=>'Rev. Fr. Tojo Baby',     'role'=>'Vicar — SJIOC Delaware Valley','phone'=>'6108220033','email'=>'info@sjioc.org','gold'=>false],
        ['img'=>'https://sjioc.org/images/image_f2df599c.png',     'init'=>'TJ','name'=>'Mr. Tijo Joseph',        'role'=>'Trustee',                         'phone'=>'',           'email'=>'',              'gold'=>true],
        ['img'=>'https://sjioc.org/images/image_edb7e3d.png',      'init'=>'TC','name'=>'Mr. Tom Chacko',         'role'=>'Secretary',                       'phone'=>'',           'email'=>'',              'gold'=>false],
        ['img'=>'',                                                  'init'=>'SS','name'=>'Sunday School Ministry', 'role'=>'Education Ministry',              'phone'=>'',           'email'=>'',              'gold'=>true],
        ['img'=>'',                                                  'init'=>'WF','name'=>'Women\'s Fellowship',   'role'=>'Fellowship Ministry',             'phone'=>'',           'email'=>'',              'gold'=>false],
        ['img'=>'',                                                  'init'=>'FC','name'=>'FOCUS Ministry',         'role'=>'Liturgical Choir',                'phone'=>'',           'email'=>'',              'gold'=>true],
        ['img'=>'',                                                  'init'=>'MG','name'=>'MGOCSM Outreach',        'role'=>'Community Outreach',              'phone'=>'',           'email'=>'',              'gold'=>false],
        ['img'=>'',                                                  'init'=>'🏛','name'=>'Parish Office',           'role'=>sjioc_phone().' · '.sjioc_email(), 'phone'=>'6108220033','email'=>'info@sjioc.org','gold'=>false],
      ];
      foreach ($static as $c):?>
      <div class="c-item" data-name="<?php echo esc_attr(strtolower($c['name'].' '.$c['role'])); ?>">
        <div class="c-avatar<?php echo $c['gold']?' gold':''; ?>">
          <?php if($c['img']): ?>
          <img src="<?php echo esc_url($c['img']); ?>" alt="<?php echo esc_attr($c['name']); ?>" onerror="this.parentNode.innerHTML='<?php echo esc_attr($c['init']); ?>'">
          <?php else: echo esc_html($c['init']); endif; ?>
        </div>
        <div class="c-info"><h4><?php echo esc_html($c['name']); ?></h4><p><?php echo esc_html($c['role']); ?></p></div>
        <div class="c-actions">
          <?php if($c['phone']):?><button class="c-btn" title="<?php esc_attr_e('Call','sjioc'); ?>" onclick="window.location='tel:<?php echo esc_attr($c['phone']); ?>'">📞</button><?php endif; ?>
          <?php if($c['email']):?><button class="c-btn" title="<?php esc_attr_e('Email','sjioc'); ?>" onclick="window.location='mailto:<?php echo esc_attr($c['email']); ?>'">✉</button><?php endif; ?>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="panel-footer">
    <a href="<?php echo esc_url(home_url('/contact-us/')); ?>" class="panel-footer-btn" style="text-align:center"><?php esc_html_e('Contact Page','sjioc'); ?></a>
    <a href="tel:<?php echo preg_replace('/\D/','',sjioc_phone()); ?>" class="panel-footer-btn gold" style="text-align:center">📞 <?php esc_html_e('Call Now','sjioc'); ?></a>
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

    <div id="celeb-list">
      <?php
      // Try WP posts first
      $celebs = get_posts(['post_type'=>'sjioc_celeb','posts_per_page'=>20,'orderby'=>'meta_value','meta_key'=>'celeb_day','order'=>'ASC']);
      if ($celebs) :
        foreach ($celebs as $cel) :
          $type = get_post_meta($cel->ID,'celeb_type',true);
          $day  = get_post_meta($cel->ID,'celeb_day', true);
          $mon  = get_post_meta($cel->ID,'celeb_mon', true);
          $note = get_post_meta($cel->ID,'celeb_note',true);
          $cls  = ($type==='anniv') ? 'anniv' : 'bday';
          $icon = ($type==='anniv') ? '💍' : '🎂';
      ?>
      <div class="cel-row" data-t="<?php echo esc_attr($cls); ?>">
        <div class="cel-badge <?php echo esc_attr($cls); ?>"><span class="cmon"><?php echo esc_html(strtoupper($mon)); ?></span><span class="cday"><?php echo esc_html($day); ?></span></div>
        <div class="cel-info">
          <span class="cel-type"><?php echo ($type==='anniv') ? 'Anniversary '.$icon : 'Birthday '.$icon; ?></span>
          <h4><?php echo esc_html($cel->post_title); ?></h4>
          <?php if($note): ?><p><?php echo esc_html($note); ?></p><?php endif; ?>
        </div>
        <button class="cel-wish" onclick="sjiocWishCeleb('<?php echo esc_attr($cel->post_title); ?>','<?php echo esc_attr($type); ?>')"><?php esc_html_e('Wish ✉','sjioc'); ?></button>
      </div>
      <?php endforeach; else:
      // Static fallback celebrations
      $celebs_static = [
        ['MAY','7', 'bday', 'Latha Philip',            'Sunday School Ministry',   'bday'],
        ['MAY','12','anniv','Thomas & Mary Philip',     '25th Wedding Anniversary', 'anniv'],
        ['MAY','20','bday', 'George Varghese',          'Parish Member',            'bday'],
        ['JUN','3', 'anniv','Mathew & Susan George',    '10th Wedding Anniversary', 'anniv'],
        ['JUN','18','bday', 'Sosamma Thomas',           "Women's Fellowship",       'bday'],
        ['JUL','4', 'bday', 'Philip Abraham',           'Parish Member',            'bday'],
        ['JUL','22','anniv','Jacob & Annamma Cherian',  '15th Wedding Anniversary', 'anniv'],
      ];
      $cur_mon = '';
      foreach ($celebs_static as $cel):
        $mon = $cel[0]; $day = $cel[1]; $type = $cel[2];
        $name = $cel[3]; $note = $cel[4]; $cls = $cel[5];
        if ($mon !== $cur_mon) {
          $cur_mon = $mon;
          echo '<div class="cel-section-head">' . esc_html($mon) . ' 2026</div>';
        }
      ?>
      <div class="cel-row" data-t="<?php echo esc_attr($cls); ?>">
        <div class="cel-badge <?php echo esc_attr($cls); ?>">
          <span class="cmon"><?php echo esc_html($mon); ?></span>
          <span class="cday"><?php echo esc_html($day); ?></span>
        </div>
        <div class="cel-info">
          <span class="cel-type"><?php echo ($cls==='anniv') ? 'Anniversary 💍' : 'Birthday 🎂'; ?></span>
          <h4><?php echo esc_html($name); ?></h4>
          <p><?php echo esc_html($note); ?></p>
        </div>
        <button class="cel-wish" onclick="sjiocWishCeleb('<?php echo esc_attr($name); ?>','<?php echo esc_attr($type); ?>')"><?php esc_html_e('Wish ✉','sjioc'); ?></button>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- Add new entry -->
    <button class="cel-add-btn" id="addToggle" onclick="sjiocToggleAddForm()">+ <?php esc_html_e('Add Birthday / Anniversary','sjioc'); ?></button>
    <div class="cel-form" id="addForm">
      <label><?php esc_html_e('Full Name','sjioc'); ?></label>
      <input type="text" id="af-name" placeholder="<?php esc_attr_e('e.g. John Thomas','sjioc'); ?>">
      <label><?php esc_html_e('Type','sjioc'); ?></label>
      <select id="af-type">
        <option value="bday">🎂 <?php esc_html_e('Birthday','sjioc'); ?></option>
        <option value="anniv">💍 <?php esc_html_e('Wedding Anniversary','sjioc'); ?></option>
      </select>
      <label><?php esc_html_e('Date','sjioc'); ?></label>
      <div class="cel-form-row">
        <input type="number" id="af-day" placeholder="<?php esc_attr_e('Day (1–31)','sjioc'); ?>" min="1" max="31">
        <select id="af-mon">
          <?php
          $months = ['JAN'=>'January','FEB'=>'February','MAR'=>'March','APR'=>'April','MAY'=>'May','JUN'=>'June','JUL'=>'July','AUG'=>'August','SEP'=>'September','OCT'=>'October','NOV'=>'November','DEC'=>'December'];
          foreach ($months as $v => $l) echo "<option value=\"$v\">$l</option>";
          ?>
        </select>
      </div>
      <button class="cel-form-submit" onclick="sjiocAddCeleb()">💾 <?php esc_html_e('Save Entry','sjioc'); ?></button>
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

<?php wp_footer(); ?>
</body>
</html>

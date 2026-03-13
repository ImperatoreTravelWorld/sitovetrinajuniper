<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * UX: After uploading/updating a theme, add a direct "Upload another" button
 * so the admin doesn't need to go back manually.
 */

add_action('admin_footer', function(){
  if ( ! is_admin() ) return;

  global $pagenow;

  // The post-upload result screen is typically update.php?action=upload-theme
  // or update.php?action=upgrade-theme.
  if ( $pagenow !== 'update.php' ) return;

  $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
  if ( $action !== 'upload-theme' && $action !== 'upgrade-theme' && $action !== 'update-selected-themes' ) {
    return;
  }

  $upload_url = admin_url('theme-install.php?tab=upload');

  ?>
  <script>
  (function(){
    try {
      var uploadUrl = <?php echo wp_json_encode($upload_url); ?>;

      // Add button near the default "Return" links.
      var wrap = document.querySelector('.wrap');
      if (!wrap) return;

      var p = document.createElement('p');
      p.style.marginTop = '12px';

      var a = document.createElement('a');
      a.href = uploadUrl;
      a.className = 'button button-primary';
      a.textContent = 'Carica un altro tema';

      var b = document.createElement('a');
      b.href = <?php echo wp_json_encode(admin_url('themes.php')); ?>;
      b.className = 'button';
      b.style.marginLeft = '8px';
      b.textContent = 'Vai ai Temi';

      p.appendChild(a);
      p.appendChild(b);

      // Place it after the first notice/message if possible.
      var firstH1 = wrap.querySelector('h1');
      if (firstH1 && firstH1.parentNode) {
        firstH1.parentNode.insertBefore(p, firstH1.nextSibling);
      } else {
        wrap.appendChild(p);
      }
    } catch(e) {}
  })();
  </script>
  <?php
});

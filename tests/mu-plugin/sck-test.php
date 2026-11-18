<?php
// Test fixtures: held scripts in theme output.
add_action('wp_footer', function () {
  echo '<script type="text/plain" data-seo-cookies="analytics">window.__an=(window.__an||0)+1;</script>';
  echo '<script type="text/plain" data-seo-cookies="functional">window.__fn=(window.__fn||0)+1;</script>';
  echo '<iframe data-seo-cookies="marketing" data-src="about:blank#ok" id="tframe" title="t"></iframe>';
  echo '<p>' . do_shortcode('[seo_cookies]') . '</p>';
}, 1);

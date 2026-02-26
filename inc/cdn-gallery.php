<?php
if ( ! defined('ABSPATH') ) { exit; }

/**
 * CDN folder image extraction.
 *
 * Strategy (in order):
 * 1) manifest.json / images.json / index.json inside folder (recommended if CDN has listing disabled)
 * 2) HTML directory listing (autoindex)
 *
 * Returned URLs are absolute, sorted alphabetically by filename.
 */

function imp_normalize_folder_url($url) {
  $url = trim((string)$url);
  if ($url === '') return '';

  if (strpos($url, '//') === 0) {
    $url = 'https:' . $url;
  }

  if (!preg_match('#^https?://#i', $url)) {
    $url = home_url('/') . ltrim($url, '/');
  }

  if (substr($url, -1) !== '/') {
    $url .= '/';
  }

  return $url;
}

function imp_is_image_filename_simple($name) {
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  return in_array($ext, array('jpg','jpeg','png','webp','gif'), true);
}

function imp_fetch_url_simple($url) {
  $resp = wp_remote_get($url, array(
    'timeout' => 12,
    'redirection' => 5,
    'headers' => array('Accept' => '*/*'),
  ));
  if (is_wp_error($resp)) return array(null, null);
  return array(
    wp_remote_retrieve_response_code($resp),
    wp_remote_retrieve_body($resp),
  );
}

function imp_sort_urls_by_basename($urls) {
  usort($urls, static function($a, $b){
    $pa = parse_url($a, PHP_URL_PATH);
    $pb = parse_url($b, PHP_URL_PATH);
    $ba = $pa ? basename($pa) : $a;
    $bb = $pb ? basename($pb) : $b;
    return strcasecmp($ba, $bb);
  });
  return $urls;
}

function imp_cdn_images_from_manifest_simple($folder_url) {
  $candidates = array('manifest.json','images.json','index.json');

  foreach ($candidates as $file) {
    list($code, $body) = imp_fetch_url_simple($folder_url . $file);
    if ($code !== 200 || !$body) continue;

    $data = json_decode($body, true);
    if (!is_array($data)) continue;

    $files = array();
    foreach ($data as $item) {
      if (is_string($item)) {
        $files[] = $item;
      } elseif (is_array($item) && isset($item['src']) && is_string($item['src'])) {
        $files[] = $item['src'];
      }
    }

    $out = array();
    foreach ($files as $f) {
      $f = ltrim($f, '/');
      if (!imp_is_image_filename_simple($f)) continue;
      $out[] = $folder_url . $f;
    }

    if (!empty($out)) {
      return imp_sort_urls_by_basename(array_values(array_unique($out)));
    }
  }

  return array();
}

function imp_cdn_images_from_html_index_simple($folder_url) {
  list($code, $body) = imp_fetch_url_simple($folder_url);
  if ($code !== 200 || !$body) return array();

  $out = array();

  // Try DOM first
  if (class_exists('DOMDocument')) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if ($dom->loadHTML($body)) {
      $links = $dom->getElementsByTagName('a');
      foreach ($links as $a) {
        $href = $a->getAttribute('href');
        if (!$href) continue;
        if ($href === '../' || $href === '..') continue;

        $path = parse_url($href, PHP_URL_PATH);
        $file = $path ? basename($path) : basename($href);
        if (!imp_is_image_filename_simple($file)) continue;

        if (preg_match('#^https?://#i', $href)) {
          $out[] = $href;
        } else {
          $out[] = $folder_url . ltrim($href, '/');
        }
      }
    }
    libxml_clear_errors();
  }

  // Fallback: very simple href scan
  if (empty($out)) {
    $parts = explode('href="', $body);
    foreach ($parts as $i => $chunk) {
      if ($i === 0) continue;
      $href = strtok($chunk, '"');
      if (!$href) continue;
      $path = parse_url($href, PHP_URL_PATH);
      $file = $path ? basename($path) : basename($href);
      if (!imp_is_image_filename_simple($file)) continue;
      if (preg_match('#^https?://#i', $href)) $out[] = $href;
      else $out[] = $folder_url . ltrim($href, '/');
    }
  }

  $out = array_values(array_unique($out));
  return imp_sort_urls_by_basename($out);
}

function imp_cdn_list_images($folder_url) {
  $folder_url = imp_normalize_folder_url($folder_url);
  if ($folder_url === '') return array();

  $cache_key = 'imp_cdn_imgs_' . md5($folder_url);
  $cached = get_transient($cache_key);
  if (is_array($cached)) return $cached;

  $imgs = imp_cdn_images_from_manifest_simple($folder_url);
  if (empty($imgs)) {
    $imgs = imp_cdn_images_from_html_index_simple($folder_url);
  }

  set_transient($cache_key, $imgs, HOUR_IN_SECONDS);
  return $imgs;
}

function imp_get_tour_gallery_images($post_id) {
  $cdn = get_post_meta($post_id, 'imp_cdn_folder_url', true);
  $cdn = is_string($cdn) ? trim($cdn) : '';

  if ($cdn !== '') {
    $imgs = imp_cdn_list_images($cdn);
    if (!empty($imgs)) {
      return array('type' => 'cdn', 'images' => $imgs);
    }
  }

  $ids = get_post_meta($post_id, 'imp_gallery_ids', true);
  if (!is_array($ids)) $ids = array();
  $ids = array_values(array_filter(array_map('intval', $ids)));
  return array('type' => 'wp', 'images' => $ids);
}

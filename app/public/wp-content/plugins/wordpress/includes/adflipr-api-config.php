<?php
/**
 * Central Adflipr API host and derived endpoint URLs.
 *
 * Override the API host in wp-config.php (before this file loads), for example:
 *   define( 'ADFLIPR_API_BASE_URL', 'https://staging.adflipr.com' );
 *
 * Or use the filter (from a small mu-plugin or theme):
 *   add_filter( 'adflipr_api_base_url', fn () => 'https://staging.adflipr.com' );
 *
 * If the web app lives on a different origin than the API, override:
 *   define( 'ADFLIPR_APP_BASE_URL', 'https://staging.adflipr.com' );
 *   add_filter( 'adflipr_app_base_url', fn () => 'https://app.example.com' );
 *
 * @package AdFlipr
 */

defined('ABSPATH') || exit;

if (! defined('ADFLIPR_API_BASE_URL')) {
  define('ADFLIPR_API_BASE_URL', apply_filters('adflipr_api_base_url', 'https://dev.adflipr.com'));
}

if (! function_exists('adflipr_api_url')) {
  /**
   * Build an absolute URL under ADFLIPR_API_BASE_URL.
   *
   * @param string $path Path after the host (e.g. 'api/v1/wp/users/login').
   */
  function adflipr_api_url($path = '')
  {
    $base = rtrim((string) ADFLIPR_API_BASE_URL, '/');
    if ($path === '' || $path === null) {
      return $base;
    }

    return $base . '/' . ltrim((string) $path, '/');
  }
}

if (! defined('ADFLIPR_APP_BASE_URL')) {
  define('ADFLIPR_APP_BASE_URL', apply_filters('adflipr_app_base_url', ADFLIPR_API_BASE_URL));
}

if (! defined('ADFLIPR_WOOCOMMERCE_WEBHOOK_URL')) {
  define('ADFLIPR_WOOCOMMERCE_WEBHOOK_URL', adflipr_api_url('api/v1/woocommerce/webhook'));
}

if (! defined('ADFLIPR_API_DISCONNECT_URL')) {
  define('ADFLIPR_API_DISCONNECT_URL', adflipr_api_url('api/v1/integration/connection/disconnect'));
}

/**
 * WordPress plugin login: POST /api/v1/wp/users/login (see Adflipr WordPressController), not /api/v1/users/login (web JWT).
 * Request body must include context WORDPRESS; see ADFLIPR_LOGIN_CONTEXT_WORDPRESS in class-adflipr-auth.php.
 */
if (! defined('ADFLIPR_API_WP_USERS_LOGIN_URL')) {
  define('ADFLIPR_API_WP_USERS_LOGIN_URL', adflipr_api_url('api/v1/wp/users/login'));
}

/** @deprecated Use ADFLIPR_API_WP_USERS_LOGIN_URL — kept for wp-config overrides targeting the old path */
if (! defined('ADFLIPR_API_USERS_LOGIN_URL')) {
  define('ADFLIPR_API_USERS_LOGIN_URL', ADFLIPR_API_WP_USERS_LOGIN_URL);
}

if (! defined('ADFLIPR_LOGIN_CONTEXT_WORDPRESS')) {
  define('ADFLIPR_LOGIN_CONTEXT_WORDPRESS', 'WORDPRESS');
}

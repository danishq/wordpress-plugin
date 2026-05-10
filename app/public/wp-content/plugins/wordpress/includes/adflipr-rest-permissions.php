<?php
/**
 * Shared REST permission helpers (WordPress.org cookie auth + wp_rest nonce).
 *
 * @package Adflipr
 */

defined('ABSPATH') || exit;

/**
 * Verify X-WP-Nonce for REST requests authenticated via cookies.
 *
 * @param \WP_REST_Request $request
 * @return true|\WP_Error
 */
function adflipr_rest_verify_wp_nonce_header($request)
{
  if (! $request instanceof WP_REST_Request) {
    return new WP_Error(
      'rest_forbidden',
      __('Invalid REST request.', 'adflipr'),
      array('status' => 403)
    );
  }

  $nonce = $request->get_header('X-WP-Nonce');
  if (! is_string($nonce) || $nonce === '') {
    return new WP_Error(
      'rest_cookie_invalid_nonce',
      __('Missing REST nonce. Reload the admin page and try again.', 'adflipr'),
      array('status' => 403)
    );
  }

  $nonce = sanitize_text_field(wp_unslash($nonce));
  if (! wp_verify_nonce($nonce, 'wp_rest')) {
    return new WP_Error(
      'rest_cookie_invalid_nonce',
      __('Invalid REST nonce. Reload the admin page and try again.', 'adflipr'),
      array('status' => 403)
    );
  }

  return true;
}

/**
 * Administrators only + valid wp_rest nonce (for cookie-authenticated REST).
 *
 * @param \WP_REST_Request $request
 * @return bool|\WP_Error
 */
function adflipr_rest_permission_manage_options_with_nonce($request)
{
  if (! current_user_can('manage_options')) {
    return false;
  }

  return adflipr_rest_verify_wp_nonce_header($request);
}

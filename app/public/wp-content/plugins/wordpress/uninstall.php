<?php

// If uninstall not called from WordPress, die.
if (! defined('WP_UNINSTALL_PLUGIN')) {
  error_log('AdFlipr uninstall: aborted, not called by WordPress.');
  die;
}

require_once dirname(__FILE__) . '/includes/adflipr-api-config.php';

error_log('AdFlipr uninstall: starting.');

// Notify AdFlipr disconnect endpoint before cleanup
$token = get_option('adflipr_auth_token', false);
if (! $token) {
  error_log('AdFlipr uninstall: no token found, skipping disconnect call.');
}

if ($token && function_exists('wp_remote_request')) {
  error_log('AdFlipr uninstall: calling disconnect endpoint...');
  $response = wp_remote_request(ADFLIPR_API_DISCONNECT_URL, array(
    'method'  => 'PUT',
    'headers' => array(
      'Content-Type'  => 'application/json',
      'Authorization' => $token,
    ),
    'timeout' => 30,
  ));

  if (is_wp_error($response)) {
    error_log('AdFlipr uninstall: disconnect request failed: ' . $response->get_error_message());
  } else {
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    error_log('AdFlipr uninstall: disconnect response code ' . $code . '; body: ' . substr((string) $body, 0, 500));
  }
}

// Delete plugin options
$option_keys = array(
  'adflipr_auth_token',
  'adflipr_token_expiry',
  'adflipr_products_last_offset',
  'adflipr_orders_last_offset',
  'adflipr_coupons_last_offset',
  'adflipr_subscriptions_last_offset',
  'adflipr_disabled_wc_emails',
  'adflipr_disabled_wc_emails_pending',
  'adflipr_webhook_retry_queue',
  'adflipr_last_webhook_success_at',
  'adflipr_last_webhook_success_action',
  'adflipr_utm_tracking_enabled',
);

foreach ($option_keys as $key) {
  $deleted = delete_option($key);
  if ($deleted) {
    error_log('AdFlipr uninstall: deleted option ' . $key);
  }
  if (is_multisite()) {
    $deleted_site = delete_site_option($key);
    if ($deleted_site) {
      error_log('AdFlipr uninstall: deleted site option ' . $key);
    }
  }
}

// Clear any scheduled hooks
if (function_exists('wp_clear_scheduled_hook')) {
  wp_clear_scheduled_hook('adflipr_send_data_event');
  error_log('AdFlipr uninstall: cleared scheduled hook adflipr_send_data_event');
  wp_clear_scheduled_hook('adflipr_webhook_retry_process');
  error_log('AdFlipr uninstall: cleared scheduled hook adflipr_webhook_retry_process');
}

error_log('AdFlipr uninstall: completed.');

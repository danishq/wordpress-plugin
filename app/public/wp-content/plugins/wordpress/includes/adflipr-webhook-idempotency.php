<?php
/**
 * Stable idempotency keys for outbound WooCommerce webhook payloads (field: webhookId).
 * The backend deduplicates using Mongo webhookId + provider "WooCommerce".
 *
 * @package AdFlipr
 */

defined('ABSPATH') || exit;

if (! function_exists('adflipr_webhook_payload_with_idempotency')) {
  /**
   * Ensures payload contains webhookId for backend deduplication (does not replace if already set).
   *
   * @param array<string, mixed> $data
   * @return array<string, mixed>
   */
  function adflipr_webhook_payload_with_idempotency(array $data)
  {
    if (! empty($data['webhookId'])) {
      return $data;
    }

    $host   = wp_parse_url(home_url(), PHP_URL_HOST) ?: 'site';
    $action = isset($data['action']) ? strtolower((string) $data['action']) : 'unknown';

    switch ($action) {
      case 'add_product':
        $body = isset($data['product']) && is_array($data['product']) ? wp_json_encode($data['product']) : '';
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|add_product|' . $body);
        break;

      case 'delete_product':
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|delete_product|' . (string) ($data['product_id'] ?? ''));
        break;

      case 'add_coupon':
        $coupon = $data['product'] ?? $data['coupon'] ?? [];
        $body   = is_array($coupon) ? wp_json_encode($coupon) : '';
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|add_coupon|' . $body);
        break;

      case 'delete_coupon':
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|delete_coupon|' . (string) ($data['coupon_id'] ?? ''));
        break;

      case 'send_data_on_connect':
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|send_data_on_connect|' . wp_json_encode($data));
        break;

      case 'cart_abandonment':
        $email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : '';
        $ch    = isset($data['cart_hash']) ? (string) $data['cart_hash'] : '';
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|cart_abandonment|' . $email . '|' . $ch);
        break;

      case 'checkout_purchased':
        $oid = '';
        if (isset($data['order_data']['id'])) {
          $oid = (string) $data['order_data']['id'];
        }
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|checkout_purchased|' . $oid);
        break;

      case 'email_settings':
        $settings          = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : [];
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|email_settings|' . wp_json_encode($settings));
        break;

      default:
        $data['webhookId'] = 'wcwp_' . hash('sha256', $host . '|' . $action . '|' . wp_generate_uuid4());
        break;
    }

    return $data;
  }
}

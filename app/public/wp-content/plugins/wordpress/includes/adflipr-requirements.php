<?php
/**
 * Minimum versions and runtime checks for AdFlipr.
 *
 * Floors target typical production stacks (current PHP/WP/WooCommerce lines), not legacy
 * long-tail installs. Adjust when WordPress.org / WooCommerce supported-version policy shifts.
 */

defined('ABSPATH') || exit;

/** Minimum PHP (broader host coverage; WordPress supports 8.1+). */
const ADFLIPR_MIN_PHP_VERSION = '8.1';

/** Minimum WordPress core (balanced: aligns with Requires Plugins UX on 6.5+). */
const ADFLIPR_MIN_WP_VERSION = '6.4';

/** Minimum WooCommerce (when WC is active); hooks used here are stable from late 7.x / 8.0+. */
const ADFLIPR_MIN_WC_VERSION = '8.0';

/**
 * Whether the current environment satisfies PHP, WordPress, and WooCommerce constraints.
 */
function adflipr_meets_requirements()
{
  if (version_compare(PHP_VERSION, ADFLIPR_MIN_PHP_VERSION, '<')) {
    return false;
  }

  global $wp_version;
  if (version_compare($wp_version, ADFLIPR_MIN_WP_VERSION, '<')) {
    return false;
  }

  if (! class_exists('WooCommerce')) {
    return false;
  }

  if (defined('WC_VERSION') && version_compare(WC_VERSION, ADFLIPR_MIN_WC_VERSION, '<')) {
    return false;
  }

  return true;
}

/**
 * Human-readable requirement failures (empty array if none).
 *
 * @return string[]
 */
function adflipr_requirements_failure_messages()
{
  $messages = array();

  if (version_compare(PHP_VERSION, ADFLIPR_MIN_PHP_VERSION, '<')) {
    $messages[] = sprintf(
      /* translators: 1: required PHP version, 2: current PHP version */
      __('AdFlipr requires PHP %1$s or higher (this server reports %2$s).', 'adflipr'),
      ADFLIPR_MIN_PHP_VERSION,
      PHP_VERSION
    );
  }

  global $wp_version;
  if (version_compare($wp_version, ADFLIPR_MIN_WP_VERSION, '<')) {
    $messages[] = sprintf(
      /* translators: 1: required WordPress version, 2: current WordPress version */
      __('AdFlipr requires WordPress %1$s or higher (this site reports %2$s).', 'adflipr'),
      ADFLIPR_MIN_WP_VERSION,
      $wp_version
    );
  }

  if (! class_exists('WooCommerce')) {
    $messages[] = __('AdFlipr requires WooCommerce to be installed and active.', 'adflipr');
  } elseif (defined('WC_VERSION') && version_compare(WC_VERSION, ADFLIPR_MIN_WC_VERSION, '<')) {
    $messages[] = sprintf(
      /* translators: 1: required WooCommerce version, 2: active WooCommerce version */
      __('AdFlipr requires WooCommerce %1$s or higher (you have %2$s).', 'adflipr'),
      ADFLIPR_MIN_WC_VERSION,
      WC_VERSION
    );
  }

  return $messages;
}

/**
 * HTML body for wp_die() when activation is blocked.
 */
function adflipr_requirements_fail_html()
{
  $messages = adflipr_requirements_failure_messages();
  if (count($messages) === 0) {
    return '';
  }

  return '<p>' . implode('</p><p>', array_map('esc_html', $messages)) . '</p>';
}

/**
 * Warn admins if the plugin is active but requirements are not satisfied (e.g. after a downgrade).
 */
function adflipr_maybe_requirements_notice()
{
  if (! current_user_can('activate_plugins')) {
    return;
  }

  if (! defined('ADFLIPR_PLUGIN_BASENAME')) {
    return;
  }

  if (! function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
  }

  if (! is_plugin_active(ADFLIPR_PLUGIN_BASENAME)) {
    return;
  }

  if (adflipr_meets_requirements()) {
    return;
  }

  $messages = adflipr_requirements_failure_messages();
  if (count($messages) === 0) {
    return;
  }

  echo '<div class="notice notice-error"><p>' . implode(
    '</p><p>',
    array_map('esc_html', $messages)
  ) . '</p></div>';
}

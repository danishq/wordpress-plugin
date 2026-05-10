<?php
/**
 * Optional debug logging (no PII — never log tokens or full payloads).
 */

defined('ABSPATH') || exit;

/**
 * Log a diagnostic line when {@see ADFLIPR_DEBUG} is true.
 *
 * @param array<string, mixed> $context Safe metadata only (action keys, HTTP codes, counts).
 */
function adflipr_debug_log($message, array $context = [])
{
  if (! defined('ADFLIPR_DEBUG') || ! ADFLIPR_DEBUG) {
    return;
  }

  $line = '[AdFlipr] ' . (string) $message;
  if (count($context) > 0) {
    $line .= ' ' . wp_json_encode(adflipr_debug_redact_context($context));
  }

  error_log($line);
}

/**
 * @param array<string, mixed> $context
 * @return array<string, mixed>
 */
function adflipr_debug_redact_context(array $context)
{
  $redact_keys = array('Authorization', 'authorization', 'token', 'password', 'secret');
  foreach ($redact_keys as $key) {
    if (array_key_exists($key, $context)) {
      $context[$key] = '[redacted]';
    }
  }

  return $context;
}

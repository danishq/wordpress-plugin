# Security Scanner Agent

Scans all PHP files in a WordPress plugin for potential security vulnerabilities using pattern matching.

---

## Role

You are the **Security Scanner** - a fast, thorough agent that scans PHP files for security vulnerability patterns. You identify potential issues but do NOT confirm them - that's the Analyzer's job.

---

## Input

```json
{
  "plugin_path": "/path/to/plugin",
  "scan_scope": "all",  // or specific file/directory
  "patterns_reference": "docs/SECURITY_CHECKLIST.md"
}
```

---

## Output

```json
{
  "scan_id": "scan_20251211_143052",
  "scan_date": "2025-12-11T14:30:52Z",
  "files_scanned": 127,
  "scan_duration_ms": 45000,

  "entry_point_index": {
    "ajax_nopriv": [
      {
        "action": "wfacp_update_cart",
        "file": "admin/class-wfacp-admin.php",
        "line": 45,
        "callback": "update_cart_data",
        "auth_level": "UNAUTHENTICATED"
      }
    ],
    "ajax_auth": [
      {
        "action": "wfacp_save_settings",
        "file": "admin/class-wfacp-admin.php",
        "line": 52,
        "callback": "save_settings",
        "auth_level": "SUBSCRIBER+"
      }
    ],
    "rest_routes": [],
    "shortcodes": [
      {
        "tag": "wfacp_checkout",
        "file": "includes/class-wfacp-common.php",
        "line": 25,
        "callback": "render_checkout",
        "auth_level": "CONTRIBUTOR+"
      }
    ],
    "wc_ajax": [],
    "admin_post": []
  },

  "findings": [
    {
      "id": "FINDING-001",
      "file": "includes/ajax-handler.php",
      "line": 45,
      "column": 12,
      "code_snippet": "$wpdb->get_results(\"SELECT * FROM table WHERE id = \" . $_GET['id'])",
      "context_before": "function get_data() {\n    global $wpdb;",
      "context_after": "    return $results;\n}",
      "pattern_id": "SQL_INJECTION_DIRECT_INPUT",
      "pattern_category": "SQL_INJECTION",
      "severity_hint": "HIGH",
      "confidence": "HIGH",
      "function_name": "get_data",
      "class_name": null,
      "potential_entry_points": [
        {
          "type": "AJAX_NOPRIV",
          "action": "wfacp_update_cart",
          "auth_level": "UNAUTHENTICATED",
          "needs_backward_trace": true
        }
      ]
    }
  ],

  "scan_stats": {
    "total_files": 127,
    "php_files": 98,
    "lines_scanned": 15420,
    "patterns_checked": 45,
    "entry_points_found": {
      "unauthenticated": 3,
      "subscriber_plus": 8,
      "contributor_plus": 2,
      "admin_only": 5
    },
    "findings_by_category": {
      "SQL_INJECTION": 3,
      "XSS": 7,
      "CSRF": 2
    }
  }
}
```

---

## Scanning Patterns

### Category 1: SQL Injection

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `SQL_DIRECT_GET` | `\$wpdb->.+\$_GET` | HIGH | Direct $_GET in SQL |
| `SQL_DIRECT_POST` | `\$wpdb->.+\$_POST` | HIGH | Direct $_POST in SQL |
| `SQL_DIRECT_REQUEST` | `\$wpdb->.+\$_REQUEST` | HIGH | Direct $_REQUEST in SQL |
| `SQL_CONCAT_VAR` | `\$wpdb->(query\|get_results\|get_var).*\.\s*\$(?!wpdb)` | HIGH | Variable concatenation |
| `SQL_NO_PREPARE` | `\$wpdb->(query\|get_results).*(?<!prepare\()` | MEDIUM | Missing prepare() |
| `SQL_SHORTCODE_ATTR` | `\$wpdb->.+\$atts\[` | HIGH | Shortcode attr in SQL |

**Scan Command:**
```bash
grep -rn --include="*.php" -E '\$wpdb->(query|get_results|get_var|get_row|get_col)\s*\([^)]*\$_(GET|POST|REQUEST)' .
```

### Category 2: Cross-Site Scripting (XSS)

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `XSS_ECHO_GET` | `echo\s+\$_GET` | HIGH | Echo $_GET directly |
| `XSS_ECHO_POST` | `echo\s+\$_POST` | HIGH | Echo $_POST directly |
| `XSS_ECHO_ATTS` | `echo\s+[^;]*\$atts\s*\[` | MEDIUM | Echo shortcode attr |
| `XSS_PRINT_INPUT` | `print\s+\$_(GET\|POST)` | HIGH | Print user input |
| `XSS_ATTR_UNESC` | `['"]\s*\.\s*\$_(GET\|POST)` | HIGH | Unescaped in attribute |
| `XSS_PRINTF_INPUT` | `printf\s*\([^,]+,\s*\$_(GET\|POST)` | MEDIUM | Printf with user input |

**Scan Command:**
```bash
grep -rn --include="*.php" -E 'echo\s+\$_(GET|POST|REQUEST)' .
grep -rn --include="*.php" -E 'echo\s+.*\$atts\[' .
```

### Category 3: PHP Object Injection

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `OBJ_UNSERIALIZE_INPUT` | `unserialize\s*\(\s*\$_(GET\|POST\|REQUEST\|COOKIE)` | CRITICAL | Unserialize user input |
| `OBJ_MAYBE_UNSER_INPUT` | `maybe_unserialize\s*\(\s*\$_(GET\|POST)` | CRITICAL | Maybe_unserialize user input |
| `OBJ_BASE64_UNSER` | `unserialize\s*\(\s*base64_decode` | HIGH | Base64 then unserialize |

**Scan Command:**
```bash
grep -rn --include="*.php" -E '(unserialize|maybe_unserialize)\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)' .
```

### Category 4: Arbitrary File Upload

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `UPLOAD_MOVE_DIRECT` | `move_uploaded_file\s*\(\s*\$_FILES` | HIGH | Direct file move |
| `UPLOAD_NO_VALIDATION` | `wp_handle_upload.*test_form.*false` | MEDIUM | Upload without form test |
| `UPLOAD_NOPRIV` | `wp_ajax_nopriv.*\$_FILES` | CRITICAL | Unauth file upload |

**Scan Command:**
```bash
grep -rn --include="*.php" -E 'move_uploaded_file\s*\(' .
grep -rn --include="*.php" -E 'wp_handle_upload.*test_form.*false' .
```

### Category 5: Missing Authorization

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `AUTH_NOPRIV_WRITE` | `wp_ajax_nopriv.*update_option` | CRITICAL | Nopriv with update |
| `AUTH_NONCE_NO_CAP` | `wp_verify_nonce.*(?!current_user_can)` | HIGH | Nonce without capability |
| `AUTH_AJAX_NO_CHECK` | `wp_ajax_.*function.*\{[^}]*update_(option\|post_meta)` | MEDIUM | AJAX without auth |

**Scan Command:**
```bash
grep -rn --include="*.php" "wp_ajax_nopriv_" . | grep -v "//.*wp_ajax_nopriv"
```

### Category 6: Local File Inclusion

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `LFI_INCLUDE_GET` | `include\s*\(\s*.*\$_GET` | CRITICAL | Include with $_GET |
| `LFI_REQUIRE_POST` | `require\s*\(\s*.*\$_POST` | CRITICAL | Require with $_POST |
| `LFI_INCLUDE_ATTS` | `include\s*\(\s*.*\$atts` | HIGH | Include with shortcode |

**Scan Command:**
```bash
grep -rn --include="*.php" -E '(include|require|include_once|require_once)\s*\([^)]*\$_(GET|POST|REQUEST)' .
```

### Category 7: Path Traversal

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `PATH_FILE_GET_INPUT` | `file_get_contents\s*\(\s*.*\$_(GET\|POST)` | HIGH | File read with input |
| `PATH_READFILE_INPUT` | `readfile\s*\(\s*.*\$_(GET\|POST)` | HIGH | Readfile with input |
| `PATH_FOPEN_INPUT` | `fopen\s*\(\s*.*\$_(GET\|POST)` | HIGH | Fopen with input |

**Scan Command:**
```bash
grep -rn --include="*.php" -E 'file_get_contents\s*\([^)]*\$_(GET|POST|REQUEST)' .
```

### Category 8: SSRF

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `SSRF_REMOTE_GET` | `wp_remote_get\s*\(\s*\$_(GET\|POST)` | HIGH | Remote get with input |
| `SSRF_REMOTE_POST` | `wp_remote_post\s*\(\s*\$_(GET\|POST)` | HIGH | Remote post with input |

**Scan Command:**
```bash
grep -rn --include="*.php" -E 'wp_remote_(get|post)\s*\(\s*\$_(GET|POST|REQUEST)' .
```

### Category 9: CSRF

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `CSRF_NO_NONCE` | `\$_POST\[.*update_option.*(?!wp_verify_nonce)` | HIGH | POST without nonce |
| `CSRF_FORM_NO_FIELD` | `<form.*method.*post.*(?!wp_nonce_field)` | MEDIUM | Form without nonce |

**Scan Command:**
```bash
grep -rn --include="*.php" "update_option" . | grep -v "wp_verify_nonce"
```

### Category 10: REST API

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `REST_RETURN_TRUE` | `permission_callback.*__return_true` | HIGH | Public permission |
| `REST_NO_CALLBACK` | `register_rest_route.*(?!permission_callback)` | MEDIUM | Missing permission |

**Scan Command:**
```bash
grep -rn --include="*.php" "permission_callback.*__return_true" .
grep -rn --include="*.php" -A5 "register_rest_route" . | grep -v "permission_callback"
```

### Category 11: Authentication Bypass

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `AUTH_BYPASS_COOKIE` | `wp_set_auth_cookie.*\$_(GET\|POST)` | CRITICAL | Auth cookie from input |
| `AUTH_WEAK_TOKEN` | `md5\s*\(.*\$_(GET\|POST)` | HIGH | Weak token hash |

**Scan Command:**
```bash
grep -rn --include="*.php" "wp_set_auth_cookie" .
```

### Category 12: Sensitive Data Exposure

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `EXPOSE_DEBUG` | `var_dump\|print_r\|debug_backtrace` | MEDIUM | Debug output |
| `EXPOSE_WPDB_ERROR` | `\$wpdb->last_error\|\$wpdb->print_error` | MEDIUM | DB error exposure |
| `EXPOSE_PHPINFO` | `phpinfo\s*\(` | HIGH | PHP info exposure |
| `EXPOSE_ERROR_LOG` | `error_log.*\$_(GET\|POST)` | LOW | User input in logs |

**Scan Command:**
```bash
grep -rn --include="*.php" -E '(var_dump|print_r|phpinfo)\s*\(' .
```

### Category 13: Hardcoded Secrets

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `SECRET_API_KEY` | `(api_key\|apikey\|api_secret)\s*=\s*['\"][^'\"]{10,}` | HIGH | Hardcoded API key |
| `SECRET_PASSWORD` | `(password\|passwd\|pwd)\s*=\s*['\"][^'\"]+['\"]` | HIGH | Hardcoded password |
| `SECRET_TOKEN` | `(secret\|token\|auth)\s*=\s*['\"][a-zA-Z0-9]{20,}` | HIGH | Hardcoded token |
| `SECRET_PRIVATE_KEY` | `BEGIN (RSA\|PRIVATE) KEY` | CRITICAL | Private key in code |

**Scan Command:**
```bash
grep -rn --include="*.php" -iE "(api_key|api_secret|password|secret_key)\s*=\s*['\"]" .
```

### Category 14: Raw Input Sources

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `INPUT_PHP_INPUT` | `file_get_contents\s*\(\s*['\"]php://input` | MEDIUM | Raw POST body |
| `INPUT_SERVER_URI` | `\$_SERVER\s*\[\s*['\"]REQUEST_URI` | MEDIUM | Request URI usage |
| `INPUT_SERVER_QUERY` | `\$_SERVER\s*\[\s*['\"]QUERY_STRING` | MEDIUM | Query string usage |
| `INPUT_HTTP_HEADERS` | `\$_SERVER\s*\[\s*['\"]HTTP_` | MEDIUM | HTTP header usage |

**Scan Command:**
```bash
grep -rn --include="*.php" "php://input" .
grep -rn --include="*.php" -E '\$_SERVER\s*\[\s*['"'"'"]HTTP_' .
```

### Category 15: Database-Sourced User Input

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `DB_USER_META` | `get_user_meta.*echo\|print` | MEDIUM | User meta to output |
| `DB_POST_META` | `get_post_meta.*echo\|print` | MEDIUM | Post meta to output |
| `DB_OPTION` | `get_option.*echo\|print` | MEDIUM | Option to output |
| `DB_COMMENT` | `\$comment->comment_` | MEDIUM | Comment data usage |

**Scan Command:**
```bash
grep -rn --include="*.php" -E 'echo.*get_(user|post)_meta' .
```

### Category 16: WooCommerce Specific

| Pattern ID | Regex | Severity | Description |
|------------|-------|----------|-------------|
| `WC_ORDER_META` | `get_meta\s*\(.*echo` | MEDIUM | Order meta to output |
| `WC_CUSTOMER_DATA` | `\$customer->get_` | MEDIUM | Customer data usage |
| `WC_NO_CLEAN` | `\$_POST\[(?!.*wc_clean)` | MEDIUM | POST without wc_clean |
| `WC_CHECKOUT_FIELD` | `\$_POST\[.*checkout` | MEDIUM | Checkout field access |

**Scan Command:**
```bash
grep -rn --include="*.php" -E "wc_get_order.*get_meta" .
```

---

## Scan Procedure

### Step 0: Build Entry Point Index (CRITICAL)

**Before scanning for vulnerabilities, build a complete map of all public entry points.**

This index is essential for the Analyzer to perform backward call chain analysis.

```bash
# 1. Find ALL AJAX actions (both authenticated and unauthenticated)
grep -rn "add_action.*wp_ajax_" --include="*.php" . | grep -v "^Binary"

# 2. Find ALL REST API routes
grep -rn "register_rest_route" --include="*.php" . | grep -v "^Binary"

# 3. Find ALL shortcodes
grep -rn "add_shortcode" --include="*.php" . | grep -v "^Binary"

# 4. Find ALL admin POST handlers
grep -rn "admin_post_\|admin_action_" --include="*.php" . | grep -v "^Binary"

# 5. Find ALL WooCommerce AJAX handlers
grep -rn "wc_ajax_\|woocommerce_ajax_" --include="*.php" . | grep -v "^Binary"

# 6. Find ALL init/template_redirect hooks that access $_GET/$_POST
grep -rn "add_action.*init\|add_action.*template_redirect" --include="*.php" . | grep -v "^Binary"

# 7. Find ALL form handlers
grep -rn "admin_menu\|add_menu_page\|add_submenu_page" --include="*.php" . | grep -v "^Binary"
```

#### Entry Point Index Output Format

```json
{
  "entry_point_index": {
    "ajax_nopriv": [
      {
        "action": "wfacp_update_cart",
        "file": "admin/class-wfacp-admin.php",
        "line": 45,
        "callback": "update_cart_data",
        "auth_level": "UNAUTHENTICATED"
      }
    ],
    "ajax_auth": [
      {
        "action": "wfacp_save_settings",
        "file": "admin/class-wfacp-admin.php",
        "line": 52,
        "callback": "save_settings",
        "auth_level": "SUBSCRIBER+"
      }
    ],
    "rest_routes": [
      {
        "namespace": "wfacp-admin",
        "route": "/checkouts",
        "file": "admin/rest-api/class-wfacp-rest-funnels.php",
        "line": 30,
        "callback": "get_checkouts",
        "permission_callback": "__return_true",
        "auth_level": "UNAUTHENTICATED"
      }
    ],
    "shortcodes": [
      {
        "tag": "wfacp_checkout",
        "file": "includes/class-wfacp-common.php",
        "line": 25,
        "callback": "render_checkout",
        "auth_level": "CONTRIBUTOR+"
      }
    ],
    "wc_ajax": [
      {
        "action": "wfacp_apply_coupon",
        "file": "public/class-wfacp-public.php",
        "line": 78,
        "callback": "apply_coupon",
        "auth_level": "CUSTOMER"
      }
    ],
    "admin_post": [
      {
        "action": "wfacp_export",
        "file": "admin/class-wfacp-admin.php",
        "line": 15,
        "callback": "handle_export",
        "auth_level": "SUBSCRIBER+"
      }
    ]
  }
}
```

#### Classify Entry Point Auth Levels

| Pattern | Auth Level | Risk |
|---------|-----------|------|
| `wp_ajax_nopriv_*` | UNAUTHENTICATED | CRITICAL |
| `admin_post_nopriv_*` | UNAUTHENTICATED | CRITICAL |
| `permission_callback.*__return_true` | UNAUTHENTICATED | CRITICAL |
| `wc_ajax_*` (check context) | CUSTOMER/UNAUTHENTICATED | HIGH |
| `wp_ajax_*` (no nopriv) | SUBSCRIBER+ | HIGH |
| `admin_post_*` (no nopriv) | SUBSCRIBER+ | HIGH |
| `add_shortcode` | CONTRIBUTOR+ | MEDIUM |
| `add_menu_page` | Depends on capability | MEDIUM |

### Step 1: Enumerate Files
```bash
find {plugin_path} -name "*.php" -type f | grep -v vendor | grep -v node_modules
```

### Step 2: Run Pattern Scans
For each pattern category, run grep/ripgrep with the patterns listed above.

### Step 3: Extract Context
For each match, extract:
- 5 lines before
- The matching line
- 5 lines after

### Step 4: Generate Finding
Create a finding object for each match.

### Step 5: Deduplicate
Remove duplicate findings (same file, same line, same pattern).

### Step 6: Map Findings to Entry Points

For each finding, attempt to link it to entry points:

```json
{
  "id": "FINDING-001",
  "file": "includes/class-wfacp-common.php",
  "line": 145,
  "pattern_id": "SQL_DIRECT_GET",
  "potential_entry_points": [
    {
      "entry_point": "wp_ajax_nopriv_wfacp_update_cart",
      "call_distance": 2,
      "path_hint": ["handle_cart_update", "get_cart_data"]
    },
    {
      "entry_point": "[wfacp_checkout] shortcode",
      "call_distance": 1,
      "path_hint": ["render_checkout"]
    }
  ]
}
```

### Step 7: JS Backtracking for AJAX Handlers (CRITICAL)

**For every AJAX handler found, perform JS backtracking to validate security.**

This step is MANDATORY for accurate vulnerability assessment. See `security-js-backtracker.md` for detailed methodology.

#### 7.1: Identify Source JS Files Only

```bash
# Find source JS files (EXCLUDE minified/bundled)
find . -name "*.js" -type f \
  ! -name "*.min.js" \
  ! -name "*.bundle.js" \
  ! -name "*.combined.js" \
  ! -name "*.packed.js" \
  ! -path "*/vendor/*" \
  ! -path "*/node_modules/*" \
  ! -path "*/dist/*" \
  ! -path "*/build/*"
```

#### 7.2: For Each AJAX Action, Find JS Callers

```bash
# Search for the action in source JS files
grep -rn "action.*['\"]ACTION_NAME['\"]" \
  --include="*.js" \
  --exclude="*.min.js" \
  --exclude="*.bundle.js" \
  --exclude="*.combined.js" \
  --exclude-dir=vendor \
  --exclude-dir=node_modules
```

#### 7.3: Analyze What JS Sends vs What PHP Expects

For each AJAX handler:

| PHP Handler | JS Caller |
|-------------|-----------|
| Nonce field expected | Nonce field sent? |
| Nonce action name | Matches localized data? |
| Capability check | JS context (admin/frontend)? |
| Input parameters | Parameters sent? |

#### 7.4: Trace Localized Script Data

```bash
# Find where nonces/tokens are localized to JS
grep -rn "wp_localize_script" --include="*.php" .
```

Verify:
- Token/nonce is included in localized data
- Variable name matches what JS uses
- Token generation matches PHP validation

#### 7.5: Backtrack Results Format

```json
{
  "ajax_handler": "wfacp_save_checkout",
  "js_backtrack": {
    "js_callers_found": [
      {
        "file": "admin/assets/js/wfacp-admin.js",
        "line": 165,
        "sends_nonce": true,
        "nonce_field": "wfacp_nonce",
        "nonce_source": "wfacp_data.nonce"
      }
    ],
    "php_expects": {
      "nonce_field": "wfacp_nonce",
      "nonce_validation": "wp_verify_nonce"
    },
    "localized_data": {
      "file": "admin/class-wfacp-admin.php",
      "line": 85,
      "provides": "nonce via wp_create_nonce('wfacp_admin_secure_key')"
    },
    "security_match": "MATCHED",
    "verdict": "SECURE"
  }
}
```

#### 7.6: Mismatch Detection

Flag as VULNERABLE if:
- `nopriv` handler has no nonce check AND no rate limiting
- PHP expects nonce but JS doesn't send it
- JS sends nonce with wrong field name
- `nopriv` handler performs sensitive operations without auth

Flag as NEEDS_REVIEW if:
- Handler exists but no JS caller found (dead code?)
- JS calls action that doesn't exist in PHP
- Complex token validation that needs manual verification

### Step 8: REST Backtracking for REST Endpoints (CRITICAL)

**For every REST endpoint found, perform REST backtracking to validate security.**

This step is MANDATORY for accurate vulnerability assessment. See `security-rest-backtracker.md` for detailed methodology.

#### 8.1: Index All REST Routes

```bash
# Find direct registrations
grep -rn "register_rest_route" --include="*.php" .

# Find abstract/factory patterns
grep -rn "extends.*REST_Controller" --include="*.php" .
grep -rn "get_routes\|register_routes" --include="*.php" .
```

#### 8.2: Analyze Permission Callbacks

For each endpoint, extract and analyze:
- Permission callback type (`__return_true`, inline function, method reference)
- Capability checks (`current_user_can`)
- Object-level checks (IDOR protection)
- Post type validation

```bash
# Find dangerous __return_true patterns
grep -rn "permission_callback.*__return_true" --include="*.php" .
```

#### 8.3: Find All Callers

**JavaScript Callers:**
```bash
# wp.apiFetch
grep -rn "wp\.apiFetch\|apiFetch" --include="*.js" | grep -v "\.min\.js"

# fetch API
grep -rn "fetch.*wp-json" --include="*.js" | grep -v "\.min\.js"

# jQuery
grep -rn "wpApiSettings" --include="*.js" | grep -v "\.min\.js"
```

**PHP Internal Callers:**
```bash
grep -rn "WP_REST_Request\|rest_do_request" --include="*.php" .
```

**PHP External Callers:**
```bash
grep -rn "wp_remote.*rest_url" --include="*.php" .
```

#### 8.4: Validate Nonce Flow

For authenticated endpoints, verify:
- JS sends nonce via `X-WP-Nonce` header or `_wpnonce` parameter
- Nonce is created with `wp_create_nonce('wp_rest')`
- Nonce is localized to JS correctly

#### 8.5: REST Backtrack Results Format

```json
{
  "rest_endpoint": "/plugin/v1/item/(?P<id>\\d+)",
  "rest_backtrack": {
    "registration": {
      "file": "includes/rest-api.php",
      "line": 45,
      "pattern": "direct"
    },
    "permission_callback": {
      "type": "method_reference",
      "checks_capability": true,
      "has_idor_protection": true
    },
    "callers": {
      "javascript": [
        {
          "file": "assets/js/admin.js",
          "line": 234,
          "sends_nonce": true
        }
      ],
      "php_internal": [],
      "php_external": []
    },
    "security_match": "MATCHED",
    "verdict": "SECURE"
  }
}
```

#### 8.6: REST Vulnerability Detection

Flag as VULNERABLE if:
- `__return_true` on POST/PUT/DELETE endpoints
- Missing IDOR check for ID-based operations
- Public endpoint performs sensitive operations (update_option, delete, etc.)
- JS caller missing nonce for authenticated endpoint

Flag as NEEDS_REVIEW if:
- Abstract permission callback that needs manual tracing
- Admin app endpoint with complex capability patterns
- Endpoint called from PHP scheduled tasks

### Step 9: Return Results
Return the complete findings JSON with entry point index, JS backtrack results, AND REST backtrack results.

---

## Exclusions

Skip scanning these paths:
- `vendor/`
- `node_modules/`
- `*.min.js`
- `tests/`
- `bin/agents/` (this folder)

Skip these patterns in comments:
- Lines starting with `//`
- Lines between `/*` and `*/`

---

## Confidence Levels

| Confidence | Description |
|------------|-------------|
| HIGH | Pattern match is almost certainly a vulnerability |
| MEDIUM | Pattern match likely needs analysis |
| LOW | Pattern match may be false positive |

---

## Common False Positive Patterns

Before flagging as vulnerability, verify these scenarios:

### 1. Dead Code / Unreachable Handlers
**Pattern:** AJAX hook registered but callback method doesn't exist
**Check:**
- Does the callback method/function actually exist?
- Is there any JS that calls this action?
- Is this feature only in Pro version (dead code in Lite)?

**Example False Positive:**
```php
// Hook exists but method wfacp_track_checkout() does NOT exist
add_action('wp_ajax_nopriv_wfacp_track_checkout', array(__CLASS__, 'wfacp_track_checkout'));
```
This is NOT a vulnerability if:
- The JS function that would call it is never invoked
- Pro version uses a different mechanism (cookies instead of AJAX)

### 2. Frontend-Intended nopriv Handlers
**Pattern:** `wp_ajax_nopriv_` hook for unauthenticated access
**Check:**
- Is this frontend functionality that SHOULD work for guests?
- Examples: close UI banner, submit contact form, cache refresh on timer expiry

**Not a vulnerability if:**
- Unauthenticated access is intentional design
- Proper rate limiting/token validation exists
- No sensitive data is modified

### 3. Lite vs Pro Feature Mismatch
**Pattern:** Code references features that don't exist in current version
**Check:**
- Is this a Lite version with Pro-only feature references?
- Does the JS/PHP for this feature actually execute in this version?

**Action:** Mark as DEAD_CODE, not vulnerability. Clean up for code hygiene.

---

## Performance

- Use `ripgrep` (rg) if available for faster scanning
- Process files in parallel if possible
- Limit context extraction to necessary lines
- Cache results by file hash for incremental scans

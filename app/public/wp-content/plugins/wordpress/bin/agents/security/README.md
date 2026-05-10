# Security Agents

AI agents for automated WordPress plugin security scanning, analysis, and remediation.

---

## Overview

This folder contains specialized AI agents that work together to:
1. Scan PHP files for security vulnerabilities
2. Analyze findings to confirm real issues vs false positives
3. Prioritize by risk for efficient remediation
4. Generate and apply security fixes
5. Validate fixes are correct and complete

---

## Agent Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    SECURITY ORCHESTRATOR                        │
│              Coordinates workflow, manages state                │
└─────────────────────┬───────────────────────────────────────────┘
                      │
    ┌────────┬────────┼────────┬─────────────┬───────────┬────────┐
    ▼        ▼        ▼        ▼             ▼           ▼        ▼
┌───────┐ ┌────────┐ ┌────────┐ ┌───────────┐ ┌───────┐ ┌───────┐ ┌─────────┐
│SCANNER│ │  JS    │ │  REST  │ │ ANALYZER  │ │ FIXER │ │PRIORI-│ │VALIDATOR│
│       │ │BACKTRK │ │BACKTRK │ │           │ │       │ │ TIZER │ │         │
└───────┘ └────────┘ └────────┘ └───────────┘ └───────┘ └───────┘ └─────────┘
```

---

## Agents

| Agent | File | Purpose |
|-------|------|---------|
| Orchestrator | `security-orchestrator.md` | Coordinates the entire workflow |
| Scanner | `security-scanner.md` | Pattern-based vulnerability detection |
| **JS Backtracker** | `security-js-backtracker.md` | **Traces AJAX from PHP→JS to validate security** |
| **REST Backtracker** | `security-rest-backtracker.md` | **Traces REST API endpoints from PHP→callers** |
| Analyzer | `security-analyzer.md` | Deep analysis and confirmation |
| Prioritizer | `security-prioritizer.md` | Risk-based ranking |
| Fixer | `security-fixer.md` | Generates security patches |
| Validator | `security-validator.md` | Verifies fixes are correct |

---

## Workflow

### Phase 1: Discovery
```
User Request → Scanner → JS Backtracker → REST Backtracker → Analyzer → Prioritizer → Report
```

The **JS Backtracker** step is CRITICAL for AJAX handlers:
- Finds source JS files (not minified) that call each action
- Compares what PHP expects vs what JS actually sends
- Traces localized script data to verify nonce/token flow
- Flags mismatches as vulnerabilities OR clears false positives

The **REST Backtracker** step is CRITICAL for REST API endpoints:
- Indexes all `register_rest_route` calls (including abstract patterns)
- Analyzes permission callbacks for proper capability/IDOR checks
- Finds all callers: JS (wp.apiFetch, fetch, jQuery), PHP internal, external
- Validates nonce flow via X-WP-Nonce header
- Detects public endpoints performing sensitive operations

### Phase 2: Remediation (with user approval)
```
Approved Fixes → Fixer → Validator → Commit
```

---

## Usage

### Via Slash Command
```
/security-scan         # Full scan
/security-scan quick   # Fast scan, critical only
/security-scan fix     # Scan and fix P0/P1
/security-scan report  # Show last report
```

### Manual Agent Invocation
Reference the agent markdown files when performing security tasks:
- Read the agent's role and instructions
- Follow the defined input/output formats
- Use the patterns and checklists provided

---

## Reports

Scan reports are saved to: `bin/agents/security/reports/`

Format: `scan-{YYYYMMDD-HHMMSS}.json`

---

## Vulnerability Categories Detected

| Category | Patterns | Severity |
|----------|----------|----------|
| SQL Injection | 6 patterns | CRITICAL-HIGH |
| XSS | 6 patterns | HIGH-MEDIUM |
| PHP Object Injection | 3 patterns | CRITICAL |
| File Upload | 3 patterns | CRITICAL |
| Missing Authorization | 3 patterns | HIGH |
| LFI/Path Traversal | 6 patterns | CRITICAL-HIGH |
| SSRF | 2 patterns | HIGH |
| CSRF | 2 patterns | MEDIUM |
| REST API | 2 patterns | HIGH-MEDIUM |
| Auth Bypass | 2 patterns | CRITICAL |

---

## Priority Levels

| Level | Description | Action |
|-------|-------------|--------|
| P0 | Critical | Fix immediately |
| P1 | High | Fix within 24 hours |
| P2 | Medium | Fix within 1 week |
| P3 | Low | Fix in next release |
| FALSE_POSITIVE | Not a vulnerability | Clean up dead code if applicable |

---

## Key Learnings

### JS Backtracking Prevents False Positives
Before flagging an AJAX handler as vulnerable, verify:
- The JS function that calls it **actually gets invoked** somewhere
- Check if Pro version uses a different mechanism (cookies, different action)
- If JS function is defined but never called → Dead code, not vulnerability

### Frontend nopriv Handlers Need Special Treatment
Some `wp_ajax_nopriv_*` hooks are intentionally unauthenticated (e.g., cache refresh on timer expiry). Instead of removing `nopriv`:
- Implement rate-limiting (transient-based)
- Add site-specific token validation
- Maintains functionality while preventing abuse

### Always Update JS When Fixing PHP
When adding nonce/capability checks to PHP AJAX handlers, **must also update JS** to send the nonce:
- Check if JS is inline (in PHP file) or external file
- Add nonce via `wp_create_nonce()` and pass to JS
- Update AJAX call to include the nonce parameter

---

## Reference Documents

- `docs/PATCHSTACK_VULNERABILITY_PATTERNS.md` - Real-world 2025 CVEs
- `docs/SECURITY_CHECKLIST.md` - Security coding standards
- `CLAUDE.md` - Security requirements for code generation

---

## Extending

### Adding New Patterns
1. Add pattern to `security-scanner.md` in appropriate category
2. Add analysis guidance to `security-analyzer.md`
3. Add fix pattern to `security-fixer.md`
4. Add validation checks to `security-validator.md`

### Adding New Vulnerability Categories
1. Create pattern definitions with regex
2. Define severity and priority rules
3. Document fix approaches
4. Add test cases for validation

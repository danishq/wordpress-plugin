# Branch Review Agents

Comprehensive branch/PR review system with security, code quality, performance, and compatibility analysis.

## Quick Start

```bash
# In Claude Code
/branch-review full    # Complete review
/branch-review quick   # Fast critical-only review
/branch-review fix     # Review and auto-fix
/branch-review plan    # Generate fix plan only
/branch-review verify  # Run verification only
```

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         BRANCH REVIEW ORCHESTRATOR                           │
│                    (branch-review-orchestrator.md)                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  PARALLEL ANALYZERS (run simultaneously)                                     │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐     │
│  │ Security  │ │  Code     │ │Performance│ │ Breaking  │ │ Tooling   │     │
│  │   Scan    │ │ Quality   │ │ Analyzer  │ │  Change   │ │  Runner   │     │
│  │(security/)│ │           │ │           │ │ Detector  │ │(PHPCS,etc)│     │
│  └─────┬─────┘ └─────┬─────┘ └─────┬─────┘ └─────┬─────┘ └─────┬─────┘     │
│        │             │             │             │             │            │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐                                 │
│  │ WordPress │ │WooCommerce│ │   i18n    │  ← WordPress-specific           │
│  │ Analyzer  │ │ Analyzer  │ │ Analyzer  │    analyzers                    │
│  └─────┬─────┘ └─────┬─────┘ └─────┬─────┘                                 │
│        │             │             │                                        │
│        └─────────────┴─────────────┴────────────────────────────┘          │
│                                    │                                        │
│                          ┌─────────▼─────────┐                              │
│                          │   Finding         │  ← Eliminates false          │
│                          │   Verifier        │    positives before          │
│                          │                   │    reporting                 │
│                          └─────────┬─────────┘                              │
│                                    │                                        │
│                          ┌─────────▼─────────┐                              │
│                          │    Aggregate      │                              │
│                          │    & Prioritize   │                              │
│                          └─────────┬─────────┘                              │
│                                    │                                        │
│                          ┌─────────▼─────────┐                              │
│                          │   Verification    │  ← Verifies fixes after      │
│                          │   Agent           │    they are applied          │
│                          └───────────────────┘                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Agents

### Core Agents

| Agent | File | Purpose |
|-------|------|---------|
| Orchestrator | `branch-review-orchestrator.md` | Coordinates entire workflow |
| Finding Verifier | `finding-verifier-agent.md` | Eliminates false positives before reporting |
| Verification | `verification-agent.md` | PHP lint, PHPCS, tests (after fixes) |
| Dependency Knowledge | `dependency-knowledge-agent.md` | External API verification |

### Generic Analyzers

| Agent | File | Purpose |
|-------|------|---------|
| Code Quality | `code-quality-analyzer.md` | Empty blocks, formatting, PHPDoc, typos |
| Performance | `performance-analyzer.md` | Queries, caching, loops, memory |

### WordPress-Specific Analyzers

| Agent | File | Purpose |
|-------|------|---------|
| WordPress Analyzer | `wordpress-analyzer.md` | WP deprecated functions, hooks, capabilities, nonces |
| WooCommerce Analyzer | `woocommerce-analyzer.md` | HPOS compatibility, checkout hooks, payment gateways |
| i18n Analyzer | `i18n-analyzer.md` | Text domains, escaping, RTL support, pluralization |

### Automated Tooling

| Agent | File | Purpose |
|-------|------|---------|
| Tooling Runner | `tooling-runner.md` | PHPCS, PHPStan, ESLint, Stylelint automation |

## Integration with Security Agents

This system **reuses** the existing security agents at `bin/agents/security/`:
- Does NOT duplicate security logic
- Invokes security-orchestrator for security scanning
- Aggregates security findings with other categories

## Workflow

### 1. Context Gathering
- Reads CLAUDE.md and docs/
- Understands project architecture
- Identifies external dependencies

### 2. Dependency Knowledge
For each external plugin (WPML, WooCommerce, Elementor):
- Checks if installed locally
- If local: Reads code for API understanding
- If not local: Fetches documentation from web

### 3. Parallel Analysis
Runs simultaneously:
- Security scan (reuses security agents)
- Code quality check
- Performance analysis
- Breaking change detection
- **WordPress Analyzer** - WP-specific issues
- **WooCommerce Analyzer** - HPOS, checkout hooks (CRITICAL for this plugin)
- **i18n Analyzer** - Translation issues
- **Tooling Runner** - PHPCS, PHPStan, ESLint automation

### 3.5. Finding Verification (CRITICAL)
Before reporting, verify each finding is real:
- Checkout the actual branch (not cached diff)
- Read FULL files, not just diff hunks
- Apply De Morgan's law for complex boolean logic
- Check if "issues" were fixed in later commits
- Compare with codebase patterns (phpcs:ignore, etc.)
- Classify as CONFIRMED, LIKELY, or FALSE_POSITIVE
- Only report CONFIRMED and LIKELY findings

### 4. Plan Generation
Creates structured fix plan (NOT interactive plan mode):
```json
{
  "phases": [
    {"phase": 1, "title": "Security Fixes", "priority": "P0"},
    {"phase": 2, "title": "Breaking Changes", "priority": "P0"},
    {"phase": 3, "title": "Performance", "priority": "P1"},
    {"phase": 4, "title": "Code Quality", "priority": "P2"}
  ]
}
```

### 5. Fix Application
Applies fixes with:
- User approval for each phase
- PHP syntax validation after each fix
- Atomic commits per logical change

### 6. Verification
- PHP lint all modified files
- PHPCS standards check
- PHPUnit tests (if available)
- Manual test checklist generation

## Reports

Saved to `reports/`:
```
reports/
├── review-{branch}-{timestamp}.json
├── plan-{branch}-{timestamp}.json
└── verify-{branch}-{timestamp}.json
```

## Key Features

### Dependency Verification
Ensures external API usage is correct:
```php
// Detects issues like:
in_array($lang, array('en', 'es'))  // WRONG: hardcoded

// Suggests:
$sitepress->get_active_languages()  // CORRECT: dynamic
```

### Atomic Commits
Creates proper commits:
```
security: sanitize REQUEST_URI inputs
fix: revert order statuses to original defaults
perf: add translation caching for WPML
refactor: remove empty code blocks
```

### Verification
Ensures fixes don't break anything:
```bash
php -l includes/*.php
composer phpcs
composer test
```

## Usage Examples

### Full Review
```
/branch-review full
```
Runs complete analysis, generates report, suggests fixes.

### Quick Critical Check
```
/branch-review quick
```
Checks P0 issues only, faster turnaround.

### Auto-Fix
```
/branch-review fix
```
Reviews and applies all fixes with verification.

### Generate Plan Only
```
/branch-review plan
```
Creates fix plan without executing.

## Adding Custom Analyzers

To add a new analyzer:

1. Create `bin/agents/branch-review/{name}-analyzer.md`
2. Follow the output format:
```json
{
  "file": "...",
  "issues": [
    {
      "id": "...",
      "type": "...",
      "severity": "critical|high|medium|low",
      "line": 123,
      "code": "...",
      "message": "...",
      "fix": "..."
    }
  ]
}
```
3. Update orchestrator to invoke new analyzer

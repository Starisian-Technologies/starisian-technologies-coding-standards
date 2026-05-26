# PHP + WordPress Implementation Standard

**SPARXSTAR Platform Engineering — PHP/WordPress Reference Implementation**

Starisian Technologies

---

This document is the PHP and WordPress implementation standard for the SPARXSTAR platform. It is a concrete, enforceable rulebook for all PHP and WordPress code written under SPARXSTAR governance.

All rules in the [Standards Handbook](standards-handbook.md) apply in full. This document adds PHP- and WordPress-specific requirements on top of them.

---

## Stack

- PHP 8.2+ (latest stable with active support — not security-only, not end-of-life)
- WordPress 6.8+ (latest stable)
- WordPress VIP standards — override PSR when conflicting
- PSR-1, PSR-4, PSR-12 everywhere else
- MariaDB (latest stable) via `$wpdb` or abstracted query layer
- Redis (object cache)
- OPcache (bytecode cache)

---

## Production Stack Awareness

Code runs behind: Cloudflare → Nginx → Varnish → Apache → PHP-FPM → MariaDB → Redis

Code must not break caching, proxying, or edge behavior at any layer.

---

# 1. Version Policy — Minimum Supported, Not Pinned

This document does not pin to specific version numbers. Version pins create maintenance debt — the document becomes wrong the moment a new release ships. We build at the front of the supported window. We test against the supported minimum. We never write for the unsupported minimum.

| Component | Policy | Rule |
| :---- | :---- | :---- |
| WordPress | Latest stable release | No deprecated WP APIs. Backwards compatibility culture is for adoption, not for development. |
| PHP | Latest stable with active support | Not security-only. Not end-of-life. Strict types required. No dynamic properties. |
| Relational Database | Latest stable — provider-agnostic | No direct SQL interpolation. All queries parameterized. No provider-specific extensions without abstraction layer. |

*A plugin can support WordPress 6.x while being written in modern PHP with strict types. These are separable concerns.*

---

# 2. Strict Typing — Mandatory

```php
// Required at top of every PHP file
declare(strict_types=1);

// All functions must have typed parameters and return types
function process_audio(string $path, int $duration): array { ... }

// Forbidden
function process($path, $duration) { ... }  // no types
```

| **FAIL** | PHP file missing `declare(strict_types=1)` |
| :---- | :---- |
| **FAIL** | function missing typed parameters or return type |

---

# 3. Namespacing and Prefixing

## 3.1 PHP Namespaces

- (M) `Starisian\Sparxstar\{Product}`
- (X) Abbreviations or deviations from this pattern

## 3.2 WordPress Global Scope

All WordPress global identifiers must be prefixed. No exceptions.

This applies to:

- functions
- hooks (actions and filters)
- custom post types
- taxonomies
- meta keys
- options
- database tables

Example prefixes:

```text
spx_
aiwa_
sirus_
```

| **FAIL** | unprefixed global function, hook, CPT, taxonomy, meta key, option, or DB table |
| :---- | :---- |

---

# 4. Input Discipline

All input must be sanitized before use. No raw superglobals. No implicit casting.

```php
// Required
$text  = sanitize_text_field($_POST['text'] ?? '');
$key   = sanitize_key($_GET['key'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

// Forbidden
$text = $_POST['text'];    // raw superglobal
$id   = (int)$_GET['id']; // implicit cast without validation
```

## 4.1 Sanitize → Validate → Escape

The order is non-negotiable:

1. **Sanitize** plain-text input on the way in as appropriate (`sanitize_text_field()`, `sanitize_key()`, `wp_kses_post()`); parse and validate structured payloads with format-appropriate handling instead of `sanitize_text_field()`
2. **Validate** domain logic (type checks, range checks, business rules)
3. **Escape** output on the way out (`esc_html()`, `esc_attr()`, `esc_url()`, `esc_*()`)

| Function / Pattern | Use |
| :---- | :---- |
| `sanitize_text_field()` | Single-line plain text user input (e.g., form text fields) — not for structured data |
| `wp_kses_post()` | Human-authored HTML content |
| `esc_html()` | HTML output context |
| `esc_attr()` | HTML attribute context |
| `esc_url()` | URL output context |
| `esc_js()` | Inline JavaScript context |
| `json_decode()` + schema validation | JSON and other structured payloads — never use `sanitize_text_field()` on structured data |

---

# 5. Database Rules

- All writes require explicit schema mapping and validation before write
- Transactions required wherever atomicity is needed
- No direct SQL string interpolation — prepared statements only via `$wpdb->prepare()`
- No unbounded queries — all queries must have explicit `LIMIT`
- No `SELECT *` — specify required columns explicitly
- Row-level locking or optimistic versioning required for conflicting writes
- Use `$wpdb->prefix` always — never hardcode `wp_`

```php
// Required
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, title FROM {$wpdb->posts} WHERE post_status = %s LIMIT %d",
        'publish',
        20
    )
);

// Forbidden
$results = $wpdb->get_results("SELECT * FROM wp_posts WHERE post_status = 'publish'");
```

## 5.1 Storage Strategy

| Data Type | Storage |
| :---- | :---- |
| Content | Custom Post Types |
| High-volume / structured data | Custom database tables |
| Cache / transient data | Redis (object cache) |

## 5.2 Schema Management

- (P) Use `dbDelta()` for standard schema management
- (S) Versioned migrations allowed when justified
- (M) Activation must create all required tables for all sites in a Multisite network

| **FAIL** | direct SQL string interpolation |
| :---- | :---- |
| **FAIL** | `SELECT *` in any query |
| **FAIL** | unbounded query without `LIMIT` |
| **FAIL** | hardcoded `wp_` prefix instead of `$wpdb->prefix` |

---

# 6. Object Caching — Distributed Cache Layer

- All cache entries must have defined TTL. No infinite TTL.
- Cache keys must be namespaced to prevent collision
- User-specific data must never enter shared cache
- Write operations must invalidate related cache entries immediately
- The distributed cache is a cache only. Never the source of truth.

```php
// Required — namespaced key with TTL
wp_cache_set('spx_user_profile_' . $user_id, $profile_data, 'spx_profiles', 300);

// Required — invalidate on write
wp_cache_delete('spx_user_profile_' . $user_id, 'spx_profiles');

// Forbidden
wp_cache_set('user_profile', $data); // no namespace, no TTL
```

---

# 7. Bytecode Cache — Production Configuration

```ini
; Production only
opcache.validate_timestamps = 0
opcache.memory_consumption  = 128
opcache.max_accelerated_files = 10000
```

Note: This applies to PHP deployments using OPcache. Apply equivalent bytecode cache configuration for other runtimes.

---

# 8. WordPress Plugin Rules

- All plugins must be namespaced — no global namespace pollution
- Scripts and styles loaded conditionally — never globally
- All fields defined in schema and validated before use
- No implicit field access — no dynamic schema mutation at runtime

```php
// Required — conditional enqueueing
if (!is_page('media-record')) {
    return;
}
wp_enqueue_script('spx-record-handle', plugin_dir_url(__FILE__) . 'js/record.js', [], '1.0.0', true);

// Forbidden
wp_enqueue_script('spx-record-handle', ...); // no guard — global enqueue
```

---

# 9. Abilities and Consent API

Every governed action must check ability and verify consent before execution. Bypassing ability checks is forbidden. Assuming consent is forbidden.

```php
// Required
if (!current_user_can('spx_record')) {
    return new WP_Error('forbidden', 'Insufficient ability');
}

if (!has_consent($user_id, 'recording')) {
    return new WP_Error('consent_required', 'Consent not given');
}
```

| **FAIL** | governed action without ability check |
| :---- | :---- |
| **FAIL** | governed action without consent verification |

---

# 10. Static Analysis and Linting

## 10.1 PHPStan

- (M) PHPStan Level 5 minimum
- (P) Level 8+ for core systems
- (M) No suppression without inline reason and linked issue or remediation plan

## 10.2 PHPCS

- (M) PHPCS with WordPress VIP ruleset + PSR-12
- (X) Auto-fix in CI — report mode only
- (M) Lint failures block merge

---

# 11. Multisite Requirements

WordPress Multisite is assumed from line one. It is never retrofitted.

- (M) Network-aware architecture from inception
- (M) Use `$wpdb->prefix` — never hardcode table prefixes
- (M) Distinguish site vs network options: `get_option()` vs `get_site_option()`
- (M) Plugin activation handles all existing sites in the network
- (M) New site creation triggers automatic initialization hook

```php
// Required — handle per-site and network-wide distinctly
$site_setting  = get_option('spx_site_mode');
$network_value = get_site_option('spx_network_license');

// Required — activation covers all sites
register_activation_hook(__FILE__, 'spx_activate');

function spx_activate(bool $network_wide): void {
    if ($network_wide) {
        $sites = get_sites(['fields' => 'ids']);
        foreach ($sites as $site_id) {
            switch_to_blog($site_id);
            spx_activate_for_site();
            restore_current_blog();
        }
    } else {
        spx_activate_for_site();
    }
}
```

| **FAIL** | plugin that does not handle network-wide activation |
| :---- | :---- |
| **FAIL** | code that does not distinguish `get_option()` from `get_site_option()` when the distinction matters |

---

# 12. First-Party Platform Services

These are platform primitives, not optional plugins.

## 12.1 Sirus (Context Engine)

- (M) Used for device, network, and environment context
- (X) Custom device fingerprinting
- (M) Frontend error reporting through Sirus

## 12.2 Helios (Authentication)

- (M) Trust Helios-issued identity
- (X) Custom frontend auth systems
- (X) Direct use of `wp_set_auth_cookie()` for frontend users

## 12.3 Starmus (Audio)

- (M) All recording via Starmus
- (X) Raw `MediaRecorder` implementations

---

# 13. Geo and Privacy

- (M) GeoIP2 for geolocation
- (M) IP anonymization — last octet zeroed before logging or storage
- (X) Trusting user-supplied location blindly

---

# 14. Testing

- (M) PHPUnit for all backend logic
- (M) Tests must cover: sanitization paths, permission checks, DB write/rollback, Sirus integration points
- (M) axe-core for rendered admin UI accessibility

---

# 15. Release Engineering

### Single Source of Truth

- (M) One canonical version source per plugin/theme

### Automated Release Pipeline

On tag (`v*`):

1. Validate version consistency
2. Run PHPCS lint
3. Run PHPStan analysis
4. Run PHPUnit test suite
5. Build/minify assets
6. Generate translations (`.pot` file)
7. Package distribution zip
8. Generate checksums
9. Publish release

- (M) All steps required
- (X) Manual releases

---

# 16. Commercialization

- (M) No hardcoded credentials or environment values
- (M) No undocumented public APIs — DocBlocks required on all public interfaces
- (M) Capability-based access control for all features
- (M) License headers in all PHP files
- (M) Dependency license audit before adding any new package
- (X) Commented-out code in production
- (X) Untracked TODOs in production code

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All PHP and WordPress code governed by SPARXSTAR standards.

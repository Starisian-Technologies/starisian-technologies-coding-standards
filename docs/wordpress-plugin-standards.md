# WordPress Plugin Standards (v1.1)

**Purpose**

Build WordPress plugins optimized for global deployment — with emphasis on West Africa (especially The Gambia), mobile‑first UX, limited data environments, and accessibility. Ensure code is maintainable, testable, and resilient offline.

---

## 1) Scope & Targets

* **WordPress**: 6.4+ (graceful admin‑notice fallback on older).

* **PHP**: 8.2+ (no fatal composer/runtime requirements; bundle vendors if used).

* **Browsers**: Android 8+ baseline; degraded support for older/low‑end (e.g., UC Browser).

* **Networks**: 2G/3G and intermittent connectivity are first‑class constraints.

---

## 2) Architecture & Namespacing

* **Namespace**: `Starisiansrc…` (PSR‑4).

* **Structure**:

* `plugin-slug.php` (bootstrap, guard checks)

* `src/core/PluginCore.php` (lifecycle, services)

* `src/includes/Autoloader.php` (PSR‑4 fallback if composer not present)

* `assets/` (js, css, images)

* `languages/` (MO/PO, load via `load_plugin_textdomain`)

* **Modular OOP**: services for Admin, Frontend, REST, Shortcodes, Queue, Consent, Telemetry (opt‑in only), etc.

---

## 3) Naming Conventions & Prefixing (STAR/AIWA)

* **Corporate prefixes**: Use `STAR` (default for shared libraries) or `AIWA` (product‑specific) consistently. All AIWA programming is contracted to Starisian Technologies; copyright notices should attribute **Starisian Technologies** unless otherwise negotiated.

* **One‑word plugin name**: Each plugin defines a **single PascalCase word** (e.g., `Recorder`) as its canonical name. In the bootstrap file, expose it via constants and derive the namespace:

```php

// Canonical identifiers (set once in the main plugin file)

const STAR_PLUGIN_NAME = 'Recorder'; // One word, PascalCase

const STAR_PLUGIN_SLUG = strtolower(STAR_PLUGIN_NAME); // 'recorder'

const STAR_PLUGIN_NS = 'Starisian' . STAR_PLUGIN_NAME; // 'StarisianRecorder'

```

If not explicitly set, derive `STAR_PLUGIN_NAME` from the directory name: `preg_replace('/[^A-Za-z]/','', basename(__DIR__))`.

* **Namespace root**: `Starisian` mapped PSR‑4 to `src//`.

* **Classes**: `PascalCase` (e.g., `QueueService`, `OfflineStore`). One class per file, filename mirrors class per PSR‑4 (`src/Recorder/QueueService.php`).

* **Methods & properties**: `camelCase` (e.g., `resumeFromByte`, `chunkSize`).

* **Constants**: `SCREAMING_SNAKE_CASE`, prefixed when global (e.g., `STAR_RECORDER_MAX_CHUNK_BYTES`).

* **Global functions (rare)**: prefix `star_recorder_...()` and mark `@internal`.

* **Actions/filters**: `star-/` (e.g., `star-recorder/queue/started`).

* **REST routes**: `/star-/v1/...`.

* **Options/transients/meta keys**: `star__*`.

* **Assets (script/style handles)**: `star--` (e.g., `star-recorder-queue`).

* **Text domain**: `star-`.

---

## 4) Internationalization (i18n)

* All user‑facing text uses `__()`, `esc_html__()` with a defined text domain.

* Keep strings short, grade‑7 readability; avoid jargon.

* Support multi‑script names (e.g., N’Ko) and diacritics; never strip accents in storage.

---

## 5) Accessibility (WCAG 2.1 AA)

* Semantic HTML; ARIA only to supplement.

* Keyboard support (Tab/Shift+Tab), clear **:focus** styles, visible labels.

* Color contrast ≥ 4.5:1; never convey meaning by color alone.

* Announce async states with ARIA live regions (e.g., “Uploading…”, “Saved offline”).

---

## 6) Performance Budgets (mobile‑first)

* **Per‑page payload**: ≤ 60KB gzipped JS, ≤ 25KB gzipped CSS (target).

* **No heavy front‑end frameworks**; prefer vanilla JS.

* Lazy‑load non‑critical assets; defer/`type="module"` where safe; feature‑detect and progressively enhance.

* Images/SVGs optimized; no webfonts by default (system font stack).

---

## 7) Progressive Enhancement & Browser Degrade

* Core features must work without JS (submit forms, read content).

* Provide **basic** HTML fallbacks; hydrate with JS for advanced UX.

* Avoid syntax requiring evergreen browsers (transpile if necessary); no arrow‑function/modern syntax in distributed legacy builds.

---

## 8) UX for Low‑Literacy & Rural Users

* Short labels, large tappable targets (≥ 44×44 px), icon+text buttons.

* Minimal typing; prefer selects, toggles, or guided steps.

* Plain‑language confirmations for record, submit, login, retry.

* Obvious feedback for offline/online state and in‑progress actions.

---

## 9) Data Sensitivity, Consent & Privacy

* Never expose emails, usernames, or system paths in UI/logs.

* Explicit consent UX for recording, uploads, analytics; **telemetry is opt‑in**.

* Support user data deletion/opt‑out; document data flows (Privacy Policy link).

* Assume shared devices: minimize cookies; provide fallback token flows; short‑lived sessions.

---

## 10) Security

* Capabilities & nonces on every privileged action.

* Escape on output (`esc_html`, `esc_attr`, `wp_kses`), sanitize on input; parameterize SQL with `$wpdb->prepare`.

* Validate REST auth; per‑user rate‑limit where appropriate.

* File uploads: validate type/size/MIME; store outside webroot if possible; generate UUID names.

---

## 11) Logging & Monitoring

* Use `error_log()` **only** behind `WP_DEBUG` and plugin debug flag.

* No PII in logs.

* Surface user‑friendly errors in UI; do not block workflows on analytics/telemetry failures.

---

## 12) Offline‑First & Staggered Uploads

**Core Principles**

* All interactions (audio, forms, journals) must work offline.

* Cache pending entries in IndexedDB (preferred), falling back to localStorage/FS API.

* Visual indicators: Offline, Queued, Uploading, Paused, Complete.

**Retention**

* Do not delete local data until: (a) confirmed server receipt, (b) user confirms deletion, or (c) retention threshold hit (≥ 30 days, configurable).

**Chunked / Queued Uploads**

* Upload large files in small retryable chunks (FIFO queue).

* On drop: pause, resume from last confirmed byte.

* Allow partial success notices (e.g., “3/5 uploaded”).

* Background Sync if available; otherwise “Try Again” control.

**Client State Model**

* Each entry = UUID + metadata + status: `pending | uploading | complete | failed`.

* Maintain a visible activity log and optional **Export to File** for manual submission.

---

## 13) Plugin Feature Hooks (Extensibility)

* Role‑based feature toggles (filterable).

* User metadata (dialect, contributor status) via dedicated service & filters.

* Modules: shortcodes, AI form logic, scoring/gamification, queue/consent as independent services.

* All assets enqueued via `wp_enqueue_*` with explicit dependencies and versions.

---

## 14) Dependencies & Builds

* Avoid runtime Composer dependencies; if needed, **bundle vendor/** and guard runtime.

* NPM builds produce dual outputs: `legacy` (transpiled) and `modern` (module).

* Linting/tooling: PHPCS (WordPress + PSR‑12 rulesets), PHPStan (lvl ≥ 6), ESLint, Stylelint, Markdownlint.

* No network‑fetched code at runtime.

---

## 15) Testing

* **Unit**: PHPUnit with WP test suite.

* **Integration**: REST endpoints, capability checks, nonce flows.

* **E2E**: Playwright (or WP‑env) for offline/queue UX and degraded networks.

* Define fixtures for 2G/3G throttling; include UC/old‑Android smoke tests.

---

## 16) Documentation

* `README.md` with purpose, constraints, budgets, and offline model.

* `CONTRIBUTING.md` (coding style, commit conventions, branching).

* Changelog (Keep a Changelog) and SemVer.

* Admin Help tab or onboarding panel for consent/offline behavior.

---

## 17) Release & Maintenance

* SemVer releases; database version stored in option with idempotent migrations.

* Safe deactivation/uninstall routines (cleanup options, custom tables behind user confirmation).

* Backward‑compatible filters/actions; mark deprecations with notices and removal timelines.

---

## 18) Acceptance Checklist (Pre‑Merge / Pre‑Release)

* [ ] Meets payload budget (≤ 60KB JS, ≤ 25KB CSS gz).

* [ ] JS‑off flow works for core actions; progressive enhancement verified.

* [ ] Offline queue & chunked upload pass 2G/3G tests; resume after drop.

* [ ] WCAG 2.1 AA checks: keyboard, focus, labels, contrast.

* [ ] i18n complete; text domain loaded; no hardcoded strings.

* [ ] Security: capabilities, nonces, sanitization, prepared SQL, upload validation.

* [ ] Privacy: consent screens, delete/opt‑out flow, no PII in logs.

* [ ] Admin notices for PHP/WP minimums; graceful disable on failure.

* [ ] Lint/tests green (PHPCS, PHPStan, ESLint, Stylelint, unit/integration/E2E).

* [ ] README/Changelog updated; version bumped; build artifacts committed.

---

## 19) Minimal Bootstrap Guards (Illustrative)
* On load: check PHP/WP versions; if unmet, add admin notice and early return.
* Register autoloader (Composer if present; fallback PSR‑4).
* Instantiate `PluginCore` which wires services (hooks only in runtime, no side effects at include time).

---

## 20) Regional Considerations (West Africa‑First)

* Avoid heavy fonts; rely on system fonts; optional downloadable font pack for extended scripts.
* Time/number formats localized; avoid hardcoded AM/PM.
* Content tone: simple, supportive, non‑technical; pictograms alongside text.
* Keep server endpoints tolerant of brief outages and duplicate submissions (idempotency keys by UUID).

---

## 21) Error Handling

* **Boundary contract**: Public APIs (shortcodes, REST, WP hooks) must return `WP_Error` on failure; internal services may throw exceptions but must be caught at boundaries and translated to user‑safe messages.

* **Retries & timeouts**: Wrap all network/file I/O in try/catch with capped exponential backoff and jitter; default timeouts ≤ 10s on mobile.

* **Idempotency**: Use UUID‑based idempotency keys for uploads and form posts to deduplicate on retry.

* **User messaging**: Short, translatable messages; never expose PII, stack traces, file paths, or emails.

* **JS error envelope**: Normalize to `{ ok:boolean, code:string, message:string, data?:any }`; offline detection triggers queued retry rather than hard failure.

## 22) Commenting Standards

* **File header**: SPDX license + copyright holder (Starisian Technologies, unless specified), brief purpose, and package tags.

* **PHPDoc**: All public classes/methods/properties require docblocks with `@since`, `@param`, `@return`, and `@throws` as applicable. Document hooks with `@hook` and examples.

* **Translator notes**: Use `/* translators: ... */` before localized strings that need context.

* **Inline comments**: Explain **why**, not what; avoid noise. Keep lines ≤ 100 chars.

## 23) File Structure (PSR‑4 + WP)

```

plugin-slug/
├─ plugin-slug.php # Bootstrap, guards, constants
├─ src/
│ └─ /
│ ├─ Core/PluginCore.php # Service wiring, lifecycle
│ ├─ Admin/...
│ ├─ Frontend/...
│ ├─ Rest/...
│ ├─ Services/{Queue,Consent,Storage,...}.php
│ └─ Support/{Http,Validator,Result}.php
├─ assets/
│ ├─ js/{modern,legacy}/...
│ ├─ css/...
│ └─ img/...
├─ templates/ # Escaped view partials (no logic)
├─ languages/
├─ tests/{unit,integration,e2e}
├─ bin/ # Dev scripts (e.g., i18n, build)
├─ config/{phpcs.xml,phpstan.neon}
├─ composer.json (optional; vendor/ if bundled)
└─ README.md, CONTRIBUTING.md, CHANGELOG.md

```

**Rules**: one class per file; no logic on include; side effects only in `PluginCore` hooks. Templates escape output; business logic stays in services.

## 24) Reusable Packages (Composer Add‑ins)

* **Goal**: Migrate generic helpers into versioned Composer packages for reuse across STAR/AIWA plugins.

* **Naming**: `starisian/` (e.g., `starisian/wp-offline-queue`, `starisian/wp-idempotency`). Namespace `StarisianPkgName`.

* **Policy**: No hard runtime Composer requirement—either **bundle vendor/** in the plugin release or feature‑detect and degrade gracefully if the package is absent.

* **Criteria for extraction**: (1) Used by ≥2 plugins, (2) has stable interfaces, (3) test coverage, (4) no project‑specific strings.

* **Release**: SemVer, CHANGELOG, PHPCS/PHPStan gates, minimal deps, security review.

* **Adoption pattern**:

1. Identify helper class candidates (Queue, Storage, Consent, Http, Validator).
2. Extract to package repo with PSR‑4, add tests and docs.
3. Publish to GitHub + Packagist (or private registry).
4. In plugins: require-dev for local builds; for releases, vendor‑bundle or ship a fallback shim.

### Appendix A: Standard Folders (example)

```

plugin-slug/

├─ plugin-slug.php
├─ src/
│ ├─ core/PluginCore.php
│ ├─ includes/Autoloader.php
│ ├─ admin/
│ ├─ frontend/
│ ├─ rest/
│ └─ services/{Consent,Queue,TelemetryOptIn,...}.php
├─ assets/{js,css,img}
├─ languages/
├─ tests/{unit,integration,e2e}
├─ README.md
├─ CONTRIBUTING.md
└─ composer.json (optional; vendor/ bundled if used)

```

### Appendix B: Commit & Branching

* Conventional Commits; `main` protected; feature branches via short prefixes (e.g., `feat/queue-retry`).

### Appendix C: Legal & Licensing

* Include license header (`SPDX-License-Identifier: LicenseRef-Starisian-Technologies-Proprietary`) or GPL as applicable.

* No third‑party code without explicit license file and attribution.

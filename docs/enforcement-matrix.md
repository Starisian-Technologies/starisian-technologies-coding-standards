# Enforcement Matrix

**SPARXSTAR Platform Engineering — CI/CD Enforcement Reference**

Starisian Technologies

---

This document maps every enforceable rule in the SPARXSTAR standards to the tool that enforces it, the CI stage where it runs, and the mode in which it blocks merge.

**Legend:**

| Symbol | Meaning |
| :---- | :---- |
| (B) | Blocks merge |
| (W) | Warns only — does not block |
| draft | Applies in draft mode |
| dev | Applies in development mode |
| prod | Applies in production mode |

---

## Related Standards

| Standard | Document |
| :---- | :---- |
| Language-Agnostic Handbook | [standards-handbook.md](standards-handbook.md) |
| PHP + WordPress | [php-wordpress-standard.md](php-wordpress-standard.md) |
| JavaScript + React | [javascript-react-standard.md](javascript-react-standard.md) |
| Node / Server-side JS | [node-standard.md](node-standard.md) |
| CSS / Build Limits | [css-standard.md](css-standard.md) |
| Media / Audio / TUS | [media-upload-standard.md](media-upload-standard.md) |

---

# 1. PHP Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| PHP file missing `declare(strict_types=1)` | PHPStan + PHPCS | Lint | (W) | (B) | (B) |
| Function missing typed parameters or return type | PHPStan | Static analysis | (W) | (B) | (B) |
| Raw superglobal access without sanitization | PHPCS (custom sniff) | Lint | (W) | (B) | (B) |
| Direct SQL string interpolation | PHPCS VIP sniff | Lint | (W) | (B) | (B) |
| `SELECT *` in any query | PHPCS (custom sniff) | Lint | (W) | (B) | (B) |
| Unbounded query without `LIMIT` | PHPCS (custom sniff) | Lint | (W) | (B) | (B) |
| Hardcoded `wp_` table prefix | PHPCS VIP sniff | Lint | (W) | (B) | (B) |
| Governed action without Sirus call | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |
| Governed action without ability check | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |
| Governed action without consent verification | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |
| Sirus output modified or overridden downstream | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |
| Unprefixed global function, hook, CPT, taxonomy | PHPCS (custom sniff) | Lint | (W) | (B) | (B) |
| PHPStan level < 5 violations | PHPStan | Static analysis | (W) | (B) | (B) |
| Plugin activation not handling network-wide activation | PHPUnit (test) | Test | (W) | (B) | (B) |

---

# 2. JavaScript Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| JS bundle exceeds 150 KB gzipped | Webpack / Vite size plugin | Build | (W) | (B) | (B) |
| Event listener without throttle or debounce | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| Continuous interval without bounded execution | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| API call without timeout | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| Infinite retry loop | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| `localStorage` used for critical data without TTL | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| IndexedDB usage without defined eviction policy | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| Blob in memory exceeds 5 MB | Jest (runtime assertion) | Test | (W) | (B) | (B) |
| Sensor active beyond 5 seconds without auto-disable | Jest (runtime assertion) | Test | (W) | (B) | (B) |
| Direct `MediaRecorder` usage | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| `console.log` in production build | ESLint `no-console` | Lint | (W) | (W) | (B) |
| Accessibility violations | axe-core (Playwright) | E2E test | (W) | (B) | (B) |

---

# 3. TypeScript / Node.js Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| TypeScript `noImplicitAny` violations | `tsc --noEmit` | Build | (W) | (B) | (B) |
| `any` without justification comment | ESLint `@typescript-eslint/no-explicit-any` | Lint | (W) | (B) | (B) |
| `process.env` accessed outside config module | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| Service missing required env var validation at startup | Jest (startup test) | Test | (W) | (B) | (B) |
| Raw SQL string interpolation | ESLint (custom rule) / Knex lint | Lint | (W) | (B) | (B) |
| Unbounded query without `LIMIT` | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| `SELECT *` in any query | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| Async route handler without error boundary | ESLint (custom rule) | Lint | (W) | (B) | (B) |
| `console.log` in production code | ESLint `no-console` | Lint | (W) | (B) | (B) |

---

# 4. CSS Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| CSS bundle exceeds 50 KB gzipped | Webpack / Vite size plugin | Build | (W) | (B) | (B) |
| `filter: blur` in production CSS | Stylelint (custom rule) | Lint | (W) | (B) | (B) |
| `backdrop-filter` in production CSS | Stylelint (custom rule) | Lint | (W) | (B) | (B) |
| `box-shadow` with spread > 4px | Stylelint (custom rule) | Lint | (W) | (B) | (B) |
| Animation on layout properties | Stylelint (custom rule) | Lint | (W) | (B) | (B) |
| `outline: none` without visible focus replacement | Stylelint `a11y` plugin | Lint | (W) | (B) | (B) |
| ID selectors in stylesheets | Stylelint `selector-max-id` | Lint | (W) | (B) | (B) |
| `px` units for font sizes | Stylelint (custom rule) | Lint | (W) | (B) | (B) |
| Hard-coded hex/pixel values (non-token) | Stylelint (custom rule) | Lint | (W) | (W) | (B) |
| Missing `prefers-reduced-motion` for animations | Stylelint `a11y` plugin | Lint | (W) | (B) | (B) |

---

# 5. Media and Upload Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| Audio `sampleRate > 16000` | ESLint (custom rule) / Jest | Lint + Test | (W) | (B) | (B) |
| Audio `channels > 1` | ESLint (custom rule) / Jest | Lint + Test | (W) | (B) | (B) |
| Audio `bitrate > 32000` | ESLint (custom rule) / Jest | Lint + Test | (W) | (B) | (B) |
| Audio format is WAV or uncompressed PCM | Jest (runtime assertion) | Test | (W) | (B) | (B) |
| Video width > 640 or height > 480 | ESLint (custom rule) / Jest | Lint + Test | (W) | (B) | (B) |
| Video fps > 15 | ESLint (custom rule) / Jest | Lint + Test | (W) | (B) | (B) |
| Video bitrate > 800 kbps | Jest (runtime assertion) | Test | (W) | (B) | (B) |
| Video codec is not H.264 Baseline | Jest (runtime assertion) | Test | (W) | (B) | (B) |
| Upload chunk > 512 KB | Jest (server-side test) | Test | (W) | (B) | (B) |
| Upload without chunk checksum | PHPUnit / Jest | Test | (W) | (B) | (B) |
| Upload without UUID | PHPUnit / Jest | Test | (W) | (B) | (B) |
| Full-file upload endpoint present | PHPUnit (route audit) | Test | (W) | (B) | (B) |
| Synchronous media transcoding in request lifecycle | PHPStan + PHPUnit | Static analysis + Test | (W) | (B) | (B) |
| Media processed with user-supplied FFmpeg arguments | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |

---

# 6. Distributed System and Architecture Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| Cache invalidation before DB commit confirmed | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |
| Event emission before DB commit confirmed | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |
| Client-supplied timestamp used for ordering | Code review + PHPStan | Static analysis | (W) | (B) | (B) |
| DB timestamp accepted from client payload | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |
| Async job without retry policy | PHPStan / ESLint | Static analysis | (W) | (B) | (B) |
| Async job without timeout | PHPStan / ESLint | Static analysis | (W) | (B) | (B) |
| Job silently discarded after max retries | PHPUnit / Jest | Test | (W) | (B) | (B) |
| Direct provider-specific API call without abstraction | Code review + PHPStan | Static analysis + Review | (W) | (B) | (B) |
| Breaking schema change without feature flag | Code review (PR checklist) | Review gate | (W) | (B) | (B) |
| DB migration that is not rollback-safe | Code review (PR checklist) | Review gate | (W) | (B) | (B) |

---

# 7. GraphQL Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| Query depth > 5 | graphql-depth-limit | Runtime / Test | (W) | (B) | (B) |
| N+1 query pattern in resolver | PHPStan / DataLoader audit | Static analysis + Review | (W) | (B) | (B) |
| Unbounded list query without limit | graphql-query-complexity | Runtime / Test | (W) | (B) | (B) |
| Governed resolver without Sirus authority check | PHPStan (custom rule) | Static analysis | (W) | (B) | (B) |

---

# 8. Security and Commercialization Enforcement

| Rule | Tool | Stage | draft | dev | prod |
| :---- | :---- | :---- | :---- | :---- | :---- |
| Hardcoded credentials or API keys in source | git-secrets / truffleHog | Pre-commit + CI | (B) | (B) | (B) |
| Undocumented public APIs (missing DocBlocks) | PHPCS / ESLint JSDoc | Lint | (W) | (B) | (B) |
| Commented-out code in production | PHPCS / ESLint | Lint | (W) | (B) | (B) |
| Missing license header in PHP files | PHPCS (custom sniff) | Lint | (W) | (B) | (B) |
| Packages with known high/critical CVEs | npm audit / composer audit | CI | (W) | (B) | (B) |
| Dependency license violation | license-checker | CI | (W) | (B) | (B) |

---

# 9. CI Pipeline Stages and Order

All repositories governed by SPARXSTAR standards run CI in the following stage order. Later stages do not run if earlier stages fail.

| Order | Stage | What Runs |
| :---- | :---- | :---- |
| 1 | Secret scan | git-secrets / truffleHog — immediate block on any match |
| 2 | Lint | PHPCS, ESLint, Stylelint, markdownlint, JSON lint |
| 3 | Static analysis | PHPStan, `tsc --noEmit` |
| 4 | Unit tests | PHPUnit, Jest |
| 5 | Build | Webpack / Vite — bundle size checked here |
| 6 | Integration tests | PHPUnit integration suite, Jest integration suite |
| 7 | E2E tests | Playwright |
| 8 | Accessibility | axe-core (via Playwright) |
| 9 | Security audit | npm audit / composer audit, license-checker |
| 10 | Review gates | PR checklist items (schema changes, migration safety, feature flags) |

**Rules:**

- (M) All stages must pass for merge to be permitted
- (M) Exceptions require documented reason, linked issue, and defined resolution path
- (X) Auto-fix in CI — fix must be committed by the author
- (X) Manual releases — all releases via automated pipeline on tag `v*`

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All repositories governed by SPARXSTAR standards.

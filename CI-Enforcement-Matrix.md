# CI Enforcement Matrix

Companion to `/docs/standards-handbook.md` and implementation standards. This matrix converts standards into machine-checkable enforcement states.

## Scope — Org-Generic Only (Matrix Housing: Split, 2026-06-12)

This matrix holds **organization-wide rules only**. Rules that only make sense with a product name attached do not live here — they live in a matrix supplement inside that product's own standards repo. Per-product supplements:

- Cite this file as their parent matrix.
- Never restate rows that already appear here.
- Use product-scoped rule IDs (e.g. `<PRODUCT>-PHP-001`); org-generic IDs (`PHP-001`, `JS-001`, `AUTH-001`, etc.) are reserved for this matrix.
- Follow the same `ENFORCED` / `WARN` / `SPECIFIED` / `REFERENCE` / `RESERVED` vocabulary and the same honesty rule (no row may claim `ENFORCED` without a backing tool that ships).

## Enforcement Status Vocabulary

| Status | Meaning |
| :--- | :--- |
| `ENFORCED` | Mode-aware CI behavior: warn in `draft`; block violations in `development` and `production` per `docs/enforcement-matrix.md` |
| `WARN` | CI warning only |
| `SPECIFIED` | Required by architecture but not yet fully automated |
| `REFERENCE` | Example implementation only |
| `RESERVED` | Not active yet |

## Matrix

> **Honesty rule:** `ENFORCED` is reserved for rules wired into CI via a reusable workflow (or equivalent CI gate) shipped from this repo. A shipped config (`ruleset.xml`, `tsconfig`, etc.) is necessary but not sufficient — without a workflow that actually runs the tool in `draft` / `development` / `production`, the row reads `SPECIFIED`. Each delivered workflow promotes its rows from `SPECIFIED` to `ENFORCED` in the same PR that lands the workflow. See `docs/standards-catalog.md` Part III–IV.

| Rule ID | Section | Rule | Applies To | Enforcement | Tool |
| :--- | :--- | :--- | :--- | :--- | :--- |
| SYS-001 | standards-handbook §0.1 | System mode declaration required (`draft`, `development`, `production`) | All | SPECIFIED | CI config validation / custom script (no shared script ships yet) |
| SYS-002 | standards-handbook §0.3 | No silent failure (defined error + internal logging) | All | SPECIFIED | Runtime validation + integration tests |
| SYS-003 | standards-handbook §0.4 | Max request size 5 MB | API / Edge | SPECIFIED | API gateway / request limit checks (per-repo ops policy) |
| SYS-004 | standards-handbook §0.4 | Max API response 100 KB | API | SPECIFIED | API tests / response size checks (per-repo) |
| SYS-005 | standards-handbook §0.5 | Idempotency key required for write operations | API / Mutations / Uploads | SPECIFIED | Contract tests / runtime middleware |
| SYS-006 | standards-handbook §0.6 | Reserved section | All | RESERVED | N/A |
| SYS-007 | standards-handbook §0.7 | No direct provider SDK usage without abstraction layer | App code | SPECIFIED | PHPStan custom rule (not yet shipped) + review gate |
| AUTH-001 | standards-handbook §1.2 | Governed actions must call the authority layer before execution | Backend / Frontend governed flows | SPECIFIED | Static analysis custom rules |
| AUTH-002 | standards-handbook §1.3 | Fail closed when authority-layer context/authority is unavailable | All governed repos | SPECIFIED | Integration tests |
| GQL-001 | standards-handbook §2.1 | GraphQL max query depth 5 | GraphQL | SPECIFIED | `graphql-depth-limit` (no shared config ships yet) |
| GQL-002 | standards-handbook §2.2 | Unbounded list query forbidden | GraphQL | SPECIFIED | Query complexity checks |
| EDGE-001 | standards-handbook §3.1 | Empty/absent User-Agent fails request | Edge / API | SPECIFIED | WAF / reverse proxy policy (per-repo ops) |
| EDGE-002 | standards-handbook §3.4 | Never cache authenticated responses | Edge / CDN | SPECIFIED | Cache policy tests |
| CONS-001 | standards-handbook §4.2 | Primary DB is source of truth; cache is disposable | Backend | SPECIFIED | Architecture checks / integration tests |
| CONS-002 | standards-handbook §4.3 | Cache invalidation required after successful writes | Backend / Edge | SPECIFIED | Integration tests |
| ASYNC-001 | standards-handbook §5 | Async jobs require retry policy and bounded timeout | Queue workers | SPECIFIED | Worker tests |
| DATA-001 | standards-handbook §6 | Data lifecycle policy enforcement (retention/deletion) | Data stores | SPECIFIED | Scheduled compliance jobs |
| OBS-001 | standards-handbook §7 | Required metrics must be emitted | Services | SPECIFIED | Metrics conformance checks |
| DIST-001 | standards-handbook §8.1 | DB commit must precede cache invalidation/event emission | Backend | SPECIFIED | Integration tests |
| DIST-002 | standards-handbook §8.2 | Server is authority for ordering timestamps | Backend | SPECIFIED | Static analysis + tests |
| DIST-003 | standards-handbook §8.4 | Deployment changes must be rollback-safe; breaking changes require feature flags | Release engineering | SPECIFIED | Deployment policy checks / migration tests |
| DIST-004 | standards-handbook §8.5 | Schema version compatibility during rollout | Backend / Clients | SPECIFIED | Migration tests |
| DIST-005 | standards-handbook §8.6 | Critical offline data in IndexedDB, not localStorage | Frontend | SPECIFIED | ESLint custom rule (not yet shipped) |
| PHP-001 | php-wordpress-standard §2 | PHP files require `declare(strict_types=1)` | PHP | SPECIFIED | `ruleset.xml` shipped (`PSR12.Files.DeclareStrictTypes`); promotes to `ENFORCED` when the PHP reusable workflow ships (Catalog Step 5) |
| PHP-002 | php-wordpress-standard §5 | No `SELECT *` in queries | PHP / SQL | SPECIFIED | PHPCS custom sniff (not yet shipped) |
| PHP-003 | php-wordpress-standard §5 | All DB queries prepared (`$wpdb->prepare`) | PHP / SQL | SPECIFIED | `ruleset.xml` shipped (`WordPress.DB.PreparedSQL*`); promotes to `ENFORCED` when the PHP reusable workflow ships (Catalog Step 5) |
| PHP-004 | php-wordpress-standard §3.2 | WordPress globals must be prefixed | WordPress | SPECIFIED | `ruleset.xml` shipped (`WordPress.NamingConventions.PrefixAllGlobals`); consumer MUST set `<config name="prefixes" value="myproduct"/>`; promotes to `ENFORCED` when the PHP reusable workflow ships (Catalog Step 5) |
| PHP-005 | php-wordpress-standard §10.1 | PHPStan level 5 minimum | PHP | SPECIFIED | `phpstan.neon` shipped (`level: 5`); promotes to `ENFORCED` when the PHP reusable workflow ships (Catalog Step 5) |
| JS-001 | javascript-react-standard §3 | Fetch/API calls require timeout and bounded retry | JS/TS | SPECIFIED | ESLint custom rule (not yet shipped — Catalog #2) |
| JS-002 | javascript-react-standard §6.2 | IndexedDB for critical offline data | JS/TS | SPECIFIED | ESLint custom rule (not yet shipped) |
| JS-003 | javascript-react-standard §11.2 | The auth SDK required for auth (no custom frontend auth) | JS/TS | SPECIFIED | Architecture checks |
| NODE-001 | node-standard §2 | TypeScript strict mode required | Node.js | SPECIFIED | `@starisian-technologies/tsconfig` shipped (`base.json` sets `strict: true` + `noUncheckedIndexedAccess` etc.); promotes to `ENFORCED` when the JS/TS reusable workflow ships (Catalog Step 5) |
| NODE-002 | node-standard §4.3 | HTTP server timeout configuration required | Node.js | SPECIFIED | Integration tests |
| NODE-003 | node-standard §5 | Parameterized queries only | Node.js / SQL | SPECIFIED | ESLint / query lint rules (not yet shipped) |
| NODE-PKG-001 | node-standard §11 (ADR-017) | `pnpm` is the only permitted JS/Node package manager platform-wide | Node.js / JS / repos with `package.json` | ENFORCED | `.github/workflows/pnpm-enforcement.yml` (reusable; mode-aware) |
| NODE-PKG-002 | node-standard §11 (ADR-017) | `pnpm-lock.yaml` is the only permitted JS lockfile; `package-lock.json` and `yarn.lock` forbidden | Node.js / JS | ENFORCED | `.github/workflows/pnpm-enforcement.yml` |
| NODE-PKG-003 | node-standard §11 (ADR-017) | `package.json` MUST set `packageManager: "pnpm@..."` for Corepack pinning | Node.js / JS | ENFORCED | `.github/workflows/pnpm-enforcement.yml` |
| NODE-PKG-004 | node-standard §11 (ADR-017) | CI installs MUST use `pnpm install --frozen-lockfile` | Node.js / JS / CI | ENFORCED | `.github/workflows/pnpm-enforcement.yml` |
| SQL-001 | standards-handbook §0.8 | SQL queries must be parameterized and explicitly bounded (e.g., `LIMIT` / max rows) | SQL | SPECIFIED | Static analysis + query lint |
| PG-001 | standards-handbook §0.8 | PostgreSQL queries must be parameterized; extension use behind abstraction | PostgreSQL | SPECIFIED | Static analysis + architecture checks |
| NEO4J-001 | standards-handbook §0.8 | Cypher queries must use parameters; no string interpolation | Neo4j | SPECIFIED | Driver lint + integration tests |
| DATA-XML-001 | standards-handbook §0.8 | XML parsing must disable unsafe parser features and validate structure | XML | SPECIFIED | Security tests + parser config checks |
| DATA-JSON-001 | standards-handbook §0.8 | JSON payloads require schema/contract validation before use | JSON | SPECIFIED | Contract tests + runtime validation (per-repo) |
| LARAVEL-001 | standards-handbook §0.8 | Laravel code must enforce explicit authorization and validation boundaries | Laravel | SPECIFIED | PHPUnit + static analysis |
| VITE-001 | standards-handbook §0.8 | Vite production builds must enforce asset budgets and deterministic output | Vite | SPECIFIED | Build checks (no shared Vite config ships yet) |
| CSS-001 | css-standard §1 | CSS bundle must stay within 50 KB gzipped total per page | Frontend CSS | SPECIFIED | Build size check (no shared build config ships yet — Catalog #4, #10) |
| CSS-002 | css-standard §2 | Prohibited high-cost CSS properties blocked | Frontend CSS | SPECIFIED | Stylelint custom rule (no shared `stylelint` config ships yet — Catalog #4) |
| MEDIA-001 | media-upload-standard §1 | Audio sample rate <= 16000 | JS/PHP | SPECIFIED | Runtime validation |
| MEDIA-002 | media-upload-standard §2 | Video constraints (max resolution/FPS/bitrate) enforced | JS/PHP | SPECIFIED | Runtime validation |
| MEDIA-003 | media-upload-standard §4 | The audio capture SDK required (no raw `MediaRecorder`) | JS/TS | SPECIFIED | ESLint custom rule (not yet shipped — Catalog #5) |
| MEDIA-004 | media-upload-standard §5.1 | Upload chunk size <= 512 KB | Upload services | SPECIFIED | Upload API tests (per-repo) |
| MEDIA-005 | media-upload-standard §7.1 | Controlled FFmpeg pipeline only | Backend media processing | SPECIFIED | Processing service policy checks |
| DOC-001 | README.md §Documentation Structure | Repository documentation structure reference | This repository | REFERENCE | Documentation reference |
| REF-001 | docs/enforcement-matrix.md | Existing CI/CD matrix remains reference mapping for stage execution | All | REFERENCE | Documentation cross-reference |

## Notes for CI Implementers

- Keep `Rule ID` stable across repositories.
- Treat `ENFORCED` as mode-sensitive: warn only in `draft`, block violations in `development`, and block violations in `production`, matching `/docs/enforcement-matrix.md`.
- Promote `SPECIFIED` rules to `ENFORCED` as automation lands; do not remove requirement language.

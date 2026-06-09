# SPARXSTAR CI Enforcement Matrix

Companion to `/docs/standards-handbook.md` and implementation standards. This matrix converts standards into machine-checkable enforcement states.

## Enforcement Status Vocabulary

| Status | Meaning |
| :--- | :--- |
| `ENFORCED` | Mode-aware CI behavior: warn in `draft`; block violations in `development` and `production` per `docs/enforcement-matrix.md` |
| `WARN` | CI warning only |
| `SPECIFIED` | Required by architecture but not yet fully automated |
| `REFERENCE` | Example implementation only |
| `RESERVED` | Not active yet |

## Matrix

| Rule ID | Section | Rule | Applies To | Enforcement | Tool |
| :--- | :--- | :--- | :--- | :--- | :--- |
| SYS-001 | standards-handbook §0.1 | System mode declaration required (`draft`, `development`, `production`) | All | ENFORCED | CI config validation / custom script |
| SYS-002 | standards-handbook §0.3 | No silent failure (defined error + internal logging) | All | SPECIFIED | Runtime validation + integration tests |
| SYS-003 | standards-handbook §0.4 | Max request size 5 MB | API / Edge | ENFORCED | API gateway / request limit checks |
| SYS-004 | standards-handbook §0.4 | Max API response 100 KB | API | ENFORCED | API tests / response size checks |
| SYS-005 | standards-handbook §0.5 | Idempotency key required for write operations | API / Mutations / Uploads | SPECIFIED | Contract tests / runtime middleware |
| SYS-006 | standards-handbook §0.6 | Reserved section | All | RESERVED | N/A |
| SYS-007 | standards-handbook §0.7 | No direct provider SDK usage without abstraction layer | App code | ENFORCED | Code review + PHPStan custom rule |
| SIRUS-001 | standards-handbook §1.2 | Governed actions must call Sirus before execution | Backend / Frontend governed flows | SPECIFIED | Static analysis custom rules |
| SIRUS-002 | standards-handbook §1.3 | Fail closed when Sirus context/authority is unavailable | All governed repos | SPECIFIED | Integration tests |
| GQL-001 | standards-handbook §2.1 | GraphQL max query depth 5 | GraphQL | ENFORCED | graphql-depth-limit |
| GQL-002 | standards-handbook §2.2 | Unbounded list query forbidden | GraphQL | SPECIFIED | Query complexity checks |
| EDGE-001 | standards-handbook §3.1 | Empty/absent User-Agent fails request | Edge / API | ENFORCED | WAF / reverse proxy policy |
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
| DIST-005 | standards-handbook §8.6 | Critical offline data in IndexedDB, not localStorage | Frontend | ENFORCED | ESLint custom rule |
| PHP-001 | php-wordpress-standard §2 | PHP files require `declare(strict_types=1)` | PHP | ENFORCED | PHPCS + PHPStan |
| PHP-002 | php-wordpress-standard §5 | No `SELECT *` in queries | PHP / SQL | ENFORCED | PHPCS custom sniff |
| PHP-003 | php-wordpress-standard §5 | All DB queries prepared (`$wpdb->prepare`) | PHP / SQL | ENFORCED | PHPCS VIP sniffs |
| PHP-004 | php-wordpress-standard §3.2 | WordPress globals must be prefixed | WordPress | ENFORCED | PHPCS custom sniff |
| PHP-005 | php-wordpress-standard §10.1 | PHPStan level 5 minimum | PHP | ENFORCED | PHPStan |
| JS-001 | javascript-react-standard §3 | Fetch/API calls require timeout and bounded retry | JS/TS | ENFORCED | ESLint custom rule |
| JS-002 | javascript-react-standard §6.2 | IndexedDB for critical offline data | JS/TS | ENFORCED | ESLint custom rule |
| JS-003 | javascript-react-standard §11.2 | Helios required for auth (no custom frontend auth) | JS/TS | SPECIFIED | Architecture checks |
| NODE-001 | node-standard §2 | TypeScript strict mode required | Node.js | ENFORCED | `tsc --noEmit` + `tsconfig` strict-mode validation |
| NODE-002 | node-standard §4.3 | HTTP server timeout configuration required | Node.js | ENFORCED | Integration tests |
| NODE-003 | node-standard §5 | Parameterized queries only | Node.js / SQL | ENFORCED | ESLint / query lint rules |
| CSS-001 | css-standard §1 | CSS bundle must stay within 50 KB gzipped total per page | Frontend CSS | ENFORCED | Build size check |
| CSS-002 | css-standard §2 | Prohibited high-cost CSS properties blocked | Frontend CSS | ENFORCED | Stylelint custom rule |
| MEDIA-001 | media-upload-standard §1 | Audio sample rate <= 16000 | JS/PHP | SPECIFIED | Runtime validation |
| MEDIA-002 | media-upload-standard §2 | Video constraints (max resolution/FPS/bitrate) enforced | JS/PHP | SPECIFIED | Runtime validation |
| MEDIA-003 | media-upload-standard §4 | Starmus required (no raw `MediaRecorder`) | JS/TS | ENFORCED | ESLint custom rule |
| MEDIA-004 | media-upload-standard §5.1 | Upload chunk size <= 512 KB | Upload services | ENFORCED | Upload API tests |
| MEDIA-005 | media-upload-standard §7.1 | Controlled FFmpeg pipeline only | Backend media processing | SPECIFIED | Processing service policy checks |
| DOC-001 | README.md §Documentation Structure | Repository documentation structure reference | This repository | REFERENCE | Documentation reference |
| REF-001 | docs/enforcement-matrix.md | Existing CI/CD matrix remains reference mapping for stage execution | All | REFERENCE | Documentation cross-reference |

## Notes for CI Implementers

- Keep `Rule ID` stable across repositories.
- Treat `ENFORCED` as mode-sensitive: warn only in `draft`, block violations in `development`, and block violations in `production`, matching `/docs/enforcement-matrix.md`.
- Promote `SPECIFIED` rules to `ENFORCED` as automation lands; do not remove requirement language.

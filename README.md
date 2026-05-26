# SPARXSTAR Platform Engineering Standards

The policies and standards of Starisian Technologies and the SPARXSTAR line of products.

---

## Documentation Structure

The standards are split into a language-agnostic handbook and per-language implementation rulebooks. Each document is independently enforceable.

### `docs/` — Standards

| Document | Purpose |
| :---- | :---- |
| [docs/standards-handbook.md](docs/standards-handbook.md) | Language-agnostic law — system modes, determinism, no silent failure, bounded execution, idempotency, provider abstraction, caching, concurrency, source of truth, deployment safety, data lifecycle |
| [docs/php-wordpress-standard.md](docs/php-wordpress-standard.md) | PHP + WordPress implementation — strict typing, namespacing, input discipline, DB rules, multisite, object caching, plugin architecture |
| [docs/javascript-react-standard.md](docs/javascript-react-standard.md) | JavaScript + React implementation — execution budget, event throttling, API discipline, offline-first PWA, React architecture |
| [docs/node-standard.md](docs/node-standard.md) | Node.js / server-side JS — TypeScript strict mode, HTTP server standards, DB access, async job processing, structured logging |
| [docs/css-standard.md](docs/css-standard.md) | CSS / build limits — bundle size caps, prohibited properties, design tokens, accessibility, build pipeline |
| [docs/media-upload-standard.md](docs/media-upload-standard.md) | Audio, video, and TUS upload — capture constraints, codec limits, chunked upload, atomicity, server-side processing |
| [docs/enforcement-matrix.md](docs/enforcement-matrix.md) | Enforcement matrix — maps every rule to the tool that enforces it and the CI stage where it runs |

### Root — Reference Documents

| Document | Purpose |
| :---- | :---- |
| [sparxstar-coding-standards-v1.md](sparxstar-coding-standards-v1.md) | v1.0 monolithic standards (reference — superseded by `docs/`) |
| [SPARXSTAR-ENGINEERING-STANDARDS.md](SPARXSTAR-ENGINEERING-STANDARDS.md) | Engineering standards v2 summary |
| [THE-STARISIAN-TECHNOLOGIES-CONSTITUTION.md](THE-STARISIAN-TECHNOLOGIES-CONSTITUTION.md) | Organizational constitution |
| [QUESTIONS.md](QUESTIONS.md) | Open clarification questions and review notes |
| [SECURITY.md](SECURITY.md) | Security policy |

---

## Governing Principle

**These are not guidelines. They are law. If it cannot fail a build, it is not a standard.**

Engineering for underserved environments is not about removing features. It is about designing systems that survive reality. Bandwidth is a financial cost. Battery is a finite resource. Connectivity is not guaranteed. Build accordingly.

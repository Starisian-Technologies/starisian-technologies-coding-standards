# SPARXSTAR Platform Engineering Standards

The policies and standards of Starisian Technologies and the SPARXSTAR line of products.

---

## Documentation Structure

The standards are split into a language-agnostic handbook and per-language implementation rulebooks. Each document is independently enforceable.

### `docs/` — Standards

| Document | Purpose |
| :---- | :---- |
| [docs/standards-catalog.md](docs/standards-catalog.md) | **Master catalog** — foundational principles, 16 domain standards (✅ / ⚠️ / ❌), the four delivery layers, and the build sequence. Read this first |
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
| [SPARXSTAR-CI-Enforcement-Matrix.md](SPARXSTAR-CI-Enforcement-Matrix.md) | Companion CI rule-status matrix (`ENFORCED`, `WARN`, `SPECIFIED`, `REFERENCE`, `RESERVED`) |
| [THE-STARISIAN-TECHNOLOGIES-CONSTITUTION.md](THE-STARISIAN-TECHNOLOGIES-CONSTITUTION.md) | Organizational constitution |
| [QUESTIONS.md](QUESTIONS.md) | Open clarification questions and review notes |
| [SECURITY.md](SECURITY.md) | Security policy |
| [AGENTS.md](AGENTS.md) | Cross-agent maintenance guide for keeping standards code-agnostic, enforceable, and complete |
| [.github/copilot-instructions.md](.github/copilot-instructions.md) | Copilot-facing repository law and maintenance guardrails |
| [CODEOWNERS](CODEOWNERS) | Owner approval gate for all standards changes |

---

## Companion Repository — Platform Decisions

This repository is the **HOW**. The **WHY / WHAT** lives in the companion private repo `sparxstar-platform-decisions` (a.k.a. `sparxstar-architecture-decision-record`):

| Surface | Purpose |
| :---- | :---- |
| `decisions/ADR-NNN-*.md` | Append-only architecture decision records; Accepted ADRs are immutable (supersede, never edit) |
| `invariants.md` | Platform-wide falsifiable rules (INV-NNN) |
| `open-questions.md` | Deliberately unsettled questions (OQ-NNN) |
| `specs/` | Cross-repo table schemas |
| `PRODUCT-ROLE-BOUNDARY.md` | Per-product role and boundary statements |

Standards in this repo **cite** ADR / INV / OQ numbers; they do not restate decision text. See [AGENTS.md](AGENTS.md#2a-platform-decisions-cross-reference-companion-repo).

---

## Governing Principle

**These are not guidelines. They are law. If it cannot fail a build, it is not a standard.**

Engineering for underserved environments is not about removing features. It is about designing systems that survive reality. Bandwidth is a financial cost. Battery is a finite resource. Connectivity is not guaranteed. Build accordingly.

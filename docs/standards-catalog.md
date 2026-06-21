# Starisian Technologies — Coding Standards Master Catalog

**Version:** 0.2

**Scope:** Organization-wide. Governs how Starisian Technologies writes code on every product, every client, forever. Product-specific material (platform architecture, seam contracts, governance decisions) lives in that product's own standards and decision repos — never here.

**Admission rule:** a standard earns its place by being enforceable — if no tool can check it, it's guidance inside an existing document, not a new standard.

**Trademark rule:** zero product names, repo names, concept names, or anything trademarkable in this repository. If a rule only makes sense with a product name attached, the rule belongs in that product's repo, not here.

---

## Foundational Principles (org DNA, cross-cutting all domains)

These are not documents; they are constraints every domain standard must satisfy.

1. **Low-resource first.** Built for the lowest bandwidth, weakest device, least infrastructure. Performance budgets are law, not goals.
2. **Mobile-first.** 360px is the first viewport, not a breakpoint. Touch targets ≥ 48px, readable type floors, single-column-first layout.
3. **Offline-first.** Network is an enhancement. Critical data in IndexedDB with eviction policy; resumable uploads only; visible sync state.
4. **Accessible by default.** WCAG 2.1 AA floor, automated a11y testing in E2E, keyboard-completeness, contrast minimums.
5. **No silent failure.** Defined error + internal logging, everywhere.
6. **Secure by default.** Parameterized everything, fail closed, no secrets in code, prefixed globals.

---

## Domain Standards

| # | Standard | Status | Enforcing tool(s) |
|---|---|---|---|
| 1 | PHP / WordPress | doc + Layer 1 config shipped (Composer pkg `starisian-technologies/coding-standards`); Layer 3 workflow pending | `ruleset.xml`, `phpstan.neon` shipped; PHP reusable workflow (Step 5) and custom sniffs (Step 7) outstanding |
| 2 | JavaScript / TypeScript / React | doc + Layer 1 config shipped (npm pkgs `@starisian-technologies/eslint-config`, `@starisian-technologies/tsconfig`); Layer 3 workflow pending | `eslint-config` flat config + `tsconfig` base shipped; JS/TS reusable workflow (Step 5) and custom rules (Step 7) outstanding |
| 3 | Node / server-side JS | doc + Layer 1 config shipped (`@starisian-technologies/eslint-config/node`, `@starisian-technologies/tsconfig/node.json`); Layer 3 workflow pending | Node-specific lint + tsconfig shipped; reusable workflow (Step 5) and custom rules (Step 7) outstanding |
| 4 | CSS / styling (code) | doc exists, config missing | `stylelint` config, build-size checks |
| 5 | Audio / media / upload | doc exists, config missing | ESLint custom rules, runtime validation, upload tests |
| 6 | Markdown / documentation | missing | `markdownlint` config |
| 7 | Accessibility | missing | axe-core / Playwright config, `jsx-a11y` plugin |
| 8 | Code formatting | scattered | Prettier config, `phpcbf` (reads `phpcs.xml.dist`) |
| 9 | Mobile-first / responsive | scattered | stylelint/eslint rules, E2E viewport tests |
| 10 | Low-resource / performance budgets | scattered | build-size plugins, Lighthouse CI, response-size tests |
| 11 | Offline-first / data & sync | matrix rows only | ESLint custom rules |
| 12 | Security | `SECURITY.md` + sniffs | custom sniffs/rules, dependency audit in CI |
| 13 | Testing | missing | coverage thresholds, test-tier definitions |
| 14 | Git / repo & workflow hygiene | missing | Actions on PR metadata |
| 15 | Package management | active (first complete) | pnpm enforcement workflow (`pnpm-enforcement.yml`) |
| 15a | WordPress plugin compliance | active | WordPress Plugin Check workflow (`wp-plugin-check.yml`) |
| 15b | PHP / JS / CSS / formatting / docs CI | active | `php-standards.yml`, `js-standards.yml`, `css-standards.yml`, `formatting.yml`, `markdownlint.yml` |
| 16 | Internationalization basics | missing | No hardcoded strings, UTF-8, BCP-47, ICU format |
| 17 | Python | doc exists (`python-standard.md`), tooling pending | `ruff`, `mypy` strict, `uv` (pending ADR) |

---

## Delivery Layers (build order within each domain)

1. **Configs** — the standard as installable packages repos extend, never copy.
2. **Custom rules** — sniffs/lint rules the matrix promises. Built incrementally; each one honestly promotes a matrix row from `SPECIFIED` to `ENFORCED`.
3. **Reusable workflows** — one per domain, mode-aware, thin wrappers around the configs. Six-line caller per consuming repo.
4. **Repo scaffold** — template repo born conformant.

---

## Build Sequence (by attention saved)

1. ✅ **Hygiene:** dedupe instructions folder; relocate product-specific docs to that product's repo; relabel matrix rows honestly. *(landed in PR #10)*
2. ✅ **Product name scrub:** apply the translation table. *(landed in PR #10)*
3. ✅ **Layer 1 configs** for the two biggest estates: PHP, then JS/TS. *(this PR — `composer.json`, `ruleset.xml`, `phpstan.neon`, `@starisian-technologies/eslint-config`, `@starisian-technologies/tsconfig`)*
4. **Missing docs that protect the system itself:** Markdown standard, Git/workflow hygiene standard.
5. **Workflows** for domains 1–4 + formatting.
6. **Accessibility + testing standards** — docs then configs then workflows.
7. **Custom rules** (hardest last).
8. **Scaffold repo.**

---

## Resolved Items

- **Matrix housing — SPLIT (resolved 2026-06-12).** This repo's [`CI-Enforcement-Matrix.md`](../CI-Enforcement-Matrix.md) and [`enforcement-matrix.md`](enforcement-matrix.md) hold **org-generic rows only**. Product-specific rules (rules that only make sense with a product name attached) live in a matrix supplement inside that product's own standards repo. Each product supplement cites this matrix as its parent and never restates org-generic rows.
- **Audio standard — EXPAND (resolved 2026-06-12).** [`media-upload-standard.md`](media-upload-standard.md) expands to the full audio standard: capture surface, sample rates, formats, chunking, processing pipeline.
- **Repo visibility — PUBLIC (resolved 2026-06-12).** This repo is public so cross-repo reusable workflows (e.g. `pnpm-enforcement.yml`) can be referenced from any consuming repo via `uses: starisian-technologies/starisian-technologies-coding-standards/.github/workflows/<name>.yml@<ref>` without per-org private-reuse configuration.

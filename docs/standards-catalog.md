# Starisian Technologies Coding Standards — Master Catalog

**Version:** 0.1
**Scope:** Organization-wide. Governs how Starisian writes code on every product, every client, forever.
**Out of scope:** Platform-specific material (SPARXSTAR seams, PAM, Sirus rules, design identity) — that lives in `sparxstar-platform-standards` and the `sparxstar-platform-decisions` ADR registry. It never lives here.

**Admission rule:** a standard earns its place by being enforceable — if no tool can check it, it is guidance inside an existing document, not a new standard. This is what keeps the catalog a working system instead of a wiki.

---

## Part I — Foundational Principles

Constraints every domain standard must satisfy. These are not documents; they are cross-cutting laws.

1. **Low-resource first.** Built for the lowest bandwidth, weakest device, least infrastructure. Performance budgets are law, not goals: bundle/request/response size caps, CPU budgets, no unbounded work. *(Today: scattered in `standards-handbook.md` §0.4 and `css-standard.md` — to be consolidated as its own standard, see Part II #10.)*
2. **Mobile-first.** 360px is the first viewport, not a breakpoint. Touch targets ≥ 48px, readable type floors, one-column-first layout. *(Today: lives in Sky §14 as platform material — promotes to org standard.)*
3. **Offline-first.** Network is an enhancement. Critical data in IndexedDB with eviction policy; resumable uploads only; visible sync state. *(Today: matrix rows DIST-005, JS-002.)*
4. **Accessible by default.** WCAG 2.1 AA floor, axe-core in E2E, keyboard-completeness, contrast minimums. For this org accessibility is mission-load-bearing, not compliance.
5. **No silent failure.** Defined error + internal logging, everywhere (`standards-handbook.md` §0.3).
6. **Secure by default.** Parameterized everything, fail closed, no secrets in code, prefixed globals. *(Today: `SECURITY.md` + scattered sniffs.)*

---

## Part II — Domain Standards

| # | Standard | Doc today | Config today | Enforcing tool(s) |
|---|---|---|---|---|
| 1 | PHP / WordPress | ✅ exists | ❌ | `phpcs.xml.dist` (+phpcbf), `phpstan.neon` L5, custom sniffs |
| 2 | JavaScript / TypeScript / React | ✅ exists | ❌ | `eslint-config` pkg, `tsconfig` base (strict), custom rules |
| 3 | Node / server-side JS | ✅ exists | ❌ | shares #2 configs + node-specific rules |
| 4 | CSS / styling (code) | ✅ exists | ❌ | `stylelint` config, build-size checks |
| 5 | Audio / media / upload | ✅ exists (media-upload) | ❌ | ESLint custom (no raw `MediaRecorder`), runtime validation, upload API tests. Expand doc to full audio standard: capture, sample rates, formats, chunking, processing pipeline |
| 6 | Markdown / documentation | ❌ **missing** | ❌ | `markdownlint` config. Docs are infrastructure here — agents parse them as law |
| 7 | Accessibility | ❌ **missing** | ❌ | axe-core/Playwright config, `eslint-plugin-jsx-a11y` |
| 8 | Code formatting | ⚠️ scattered | ❌ | Prettier config, phpcbf (free — reads `phpcs.xml.dist`). Mechanical, auto-fixable, zero-debate |
| 9 | Mobile-first / responsive | ⚠️ platform-scattered | ❌ | stylelint/eslint rules where checkable; E2E viewport tests |
| 10 | Low-resource / performance budgets | ⚠️ scattered | ❌ | build-size plugins, Lighthouse CI budgets, response-size tests |
| 11 | Offline-first / data & sync | ⚠️ matrix rows only | ❌ | ESLint custom rules (IndexedDB / eviction / TTL) |
| 12 | Security | ⚠️ `SECURITY.md` + sniffs | ❌ | the custom sniffs/rules in #1–#3; dependency audit (`pnpm audit`) in CI |
| 13 | Testing | ❌ **missing** | ❌ | coverage thresholds in CI; what must be unit vs integration vs E2E; WP function stubbing pattern |
| 14 | Git / repo & workflow hygiene | ❌ **missing** | partially (pnpm wf) | The work-unit contract becomes law here: every session ends in pushed branch + draft PR; PR size ceiling; ADRs cite never restate; branch naming; conventional commits if desired. Enforced by Actions on PR metadata |
| 15 | Package management | ✅ ADR-017 + workflow | ✅ (first one!) | `pnpm-enforcement.yml` — the template the rest follow |
| 16 | Internationalization basics | ❌ **missing** | ❌ | No hardcoded user-facing strings; UTF-8 everywhere; BCP-47 for language codes; ICU message format. (Language *models* and registries stay platform-side) |

---

## Part III — The Four Delivery Layers

Build order within each domain.

1. **Configs** — the standard as machine-readable files, shipped as installable packages (Composer pkg for PHP ruleset + PHPStan; npm pkgs for `eslint-config` / `tsconfig` / Prettier / Stylelint / markdownlint). Repos extend, never copy — copying is drift.
2. **Custom rules** — the sniffs/rules the matrices already promise (~15 cited, 0 built today). Live inside the Layer-1 packages. Built incrementally; each one flips a matrix row from `SPECIFIED` to `ENFORCED` honestly.
3. **Reusable workflows** — one per domain family, mode-aware (`draft` warn / `dev`+`prod` block), each a thin "install package, run tool" wrapper. Six-line caller per repo.
4. **Repo scaffold** — template repository born conformant: configs extended, workflows called, `ROLE.md` stub, `docs/adr/` ready, `.editorconfig`, `.gitattributes`, PR template.

---

## Part IV — Build Sequence

Ordered by attention saved, not technical ease.

1. **Hygiene & honesty** (one PR): dedupe `.github/instructions/`; relocate platform docs to `sparxstar-platform-standards`; relabel matrix rows `ENFORCED` → `SPECIFIED` where no enforcement exists yet.
2. **Layer 1 for the biggest estates:** `phpcs.xml.dist` + `phpstan.neon` (PHP), then `eslint-config` + `tsconfig` (JS/TS). The moment the standard stops being prose.
3. **The two missing docs that protect the system itself:** Markdown standard (the law must be parseable) and Git/workflow hygiene (the standard that ends the babysitting).
4. **Workflows** for #1–#4 + formatting — cheap once configs exist.
5. **Accessibility + testing standards** — docs then configs then workflows.
6. **Custom rules**, hardest last (Sirus-class static analysis lives platform-side anyway).
7. **Scaffold repo** — once Layers 1–3 stabilize, conformance becomes a birthright.

---

## Open Items (owner call — do not pre-decide)

- **Matrix housing:** split org-generic matrix (here) from platform supplement (`sparxstar-platform-standards`), or one matrix with a `Scope` column. **Owner call, pending.**
- **Audio standard expansion scope** (capture / processing / codecs). Draft for owner review.
- **Whether org repo goes public.** Reusable workflows across the org need either a public repo or the org-level "allow private reuse" setting enabled.

---

## Companion Repositories

| Repo | Role | Citation pattern |
|---|---|---|
| this repo | The **HOW** for the **org** | Cite ADRs and invariants by number |
| `sparxstar-platform-standards` | The **HOW** for the **SPARXSTAR platform** (Sirus rules, PAM, design identity) | This repo defers to it for platform-coupled rules |
| `sparxstar-platform-decisions` (a.k.a. `sparxstar-architecture-decision-record`) | The **WHY / WHAT** (ADRs, invariants, OQs, specs, role-boundary) | Cite ADR-NNN / INV-NNN / OQ-NNN; never restate |

This file is the master catalog. The truth-in-status for each row is enforced by Part IV Step 1 (honest relabel) and by Part III Layer 1–3 deliverables.

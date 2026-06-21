# Platform Standard — Toolchain & Quality Enforcement

> **STATUS: DRAFT — PROPOSAL ONLY**
> Per AGENTS.md: "No ADR = no standard." Normative authority exists only for sections with explicit ADR citations below.
> Uncited sections are implementation proposals pending ADR ratification; they MUST NOT be treated as binding requirements.
>
> | Cited | Section | Authority |
> |-------|---------|-----------|
> | ✅ ADR-017 | §7 Package Manager (pnpm) | Normative |
> | ✅ ADR-042 | §8 Exception Process | Normative |
> | ⏳ pending | §3 Three-Axis Versioning | Proposal |
> | ⏳ pending | §4 Self-Test Fixtures | Proposal |
> | ⏳ pending | §5 Governed-Action Gate | Proposal |
> | ⏳ pending | §9 Profiles | Proposal |
> | ⏳ pending | §10 Bucket Classification | Proposal |

Standard ID: STD-TOOLCHAIN-001
Version: 1.3
Status: Draft
Last Updated: 2026-06-21
Owners: Platform Engineering

---

## 1. Purpose

This standard defines the mandatory toolchain, dependency contracts, and CI enforcement gates for all repositories on this platform. It governs which linters, static analyzers, test frameworks, and package managers are authoritative per repository profile. Consuming repos adopt this standard by referencing the reusable workflows from `this standards repository`.

---

## 2. Scope

Applies to all repositories under the Starisian Technologies GitHub organization that ship production code, including:
- WordPress plugins and modules (`wp-plugin`, `wp-module`)
- Standalone React applications (`standalone-react`)
- Standalone Node.js / server-side TypeScript services (`standalone-node`)
- Python microservices (`python-service`)

---

## 3. Three-Axis Versioning Model

Each consuming repo's `standards.yml` caller has three independent version axes:

| Axis | Where Set | What It Controls |
|------|-----------|-----------------|
| Workflow ref (`@v1`) | `uses:` line | The executable — bugfixes only |
| `profile_version` (e.g. `v1`) | `with:` input | Dependency/config contract |
| `enforcement_mode` | `with:` input | `required` (fail-closed) or `advisory` |

**Pin strategy:**

| Pin | When to use |
|-----|-------------|
| `@v1` | Recommended — tracks latest compatible v1 workflow; receives bugfixes automatically |
| `@v1.2.0` | Maximum reproducibility — exact immutable release; requires manual updates |
| `@main` | Never — breaking changes land here first; not for consumers |

Never pin to `@main` in production. Move axes independently.

**Enforcement governance:** Workflows default to `advisory` (warn-only) when no `enforcement_mode` is passed. Consumers must explicitly set `enforcement_mode: required` to get fail-closed behavior. Only ADR-backed rules (§7, §8) can be mandated org-wide as required; proposal-only sections (§3–§6, §9–§10) must run advisory-only until their backing ADRs are ratified.

---

## 4. Self-Test Fixtures (§4)

Each workflow must have corresponding fixture directories under `fixtures/` in this standards repo:
- `fixtures/{workflow-name}/pass/` — files that must pass all checks
- `fixtures/{workflow-name}/fail/` — files that must trigger at least one check

Presence-validation steps and Bucket-3 grep guards start **WARN-ONLY** until self-test fixtures are authored and the CI self-test suite is wired (tracked in the platform backlog). Each warn-only step carries a comment: `# WARN-ONLY — flips to required after self-test fixtures authored (STD-TOOLCHAIN-001 §4)`.

---

## 5. Governed-Action Gate (§5)

REST route handlers, admin-post handlers, AJAX handlers, WP-CLI mutation commands, and service methods annotated `@governed-mutation` must call `assert_governed_action()` before mutating state.

Enforced by: `GovernedActionGateRule` PHPStan rule (see `standards/phpstan-rules/`). Status: warn-only until backing ADR is ratified.

---

## 6. Conformance Test Suite (§6)

A self-test CI job runs against the fixtures in this repo to validate that pass fixtures pass and fail fixtures fail. This suite must be green before any presence-validation or Bucket-3 check flips from warn-only to required.

---

## 7. Package Manager

All JS/TS repositories use **pnpm** exclusively (ADR-017). `pnpm-enforcement.yml` validates lockfile presence and blocks npm/yarn usage.

---

## 8. Exception Process (§8)

Advisory exceptions to required rules must be declared in `.standards/standards-exceptions.yml` in the consuming repo. Each exception entry requires:

```yaml
exceptions:
  - id: EXC-001
    rule: JS-TEST-001
    reason: "Migrating from Jest to Vitest — 30-day window"
    owner: "@team-handle"
    expires: "2026-07-21"
    approval: "ADR-042"
```

Required fields: `id`, `rule`, `reason`, `owner`, `expires`, `approval`.

Expired exceptions are **fail-closed** — CI blocks immediately. Malformed entries also block. The exceptions parser runs in every workflow before presence validation.

---

## 9. Profiles

### 9.1 wp-plugin / wp-module

**PHP toolchain:**
- `squizlabs/php_codesniffer` ^3.9
- `wp-coding-standards/wpcs` ^3
- `automattic/vipwpcs` ^3
- `phpcompatibility/phpcompatibility-wp` ^2.1
- `phpstan/phpstan` ^2
- `szepeviktor/phpstan-wordpress` ^2
- `php-stubs/wordpress-stubs` ^6
- `brain/monkey` ^2
- `dealerdirect/phpcodesniffer-composer-installer` (allow-plugins)

Required rulesets: `WordPressVIPMinimum`, `WordPress-Extra`, `WordPress-Docs`, `PHPCompatibilityWP`

Required files: `composer.json`, `composer.lock`, `phpcs.xml`, `phpstan.neon`
Recommended files: `phpunit.xml`

**JS toolchain (via @wordpress/scripts):**
- `@wordpress/scripts` ^30.0.0
- `@wordpress/stylelint-config` ^11.0.0
- Do NOT hand-pin `eslint` or `stylelint` — `@wordpress/scripts` owns those versions.
- Prohibited: `typescript-eslint`, hand-pinned `eslint`, hand-pinned `stylelint`

**E2E:** `@playwright/test`, `@wordpress/e2e-test-utils-playwright`, `@axe-core/playwright`

See machine-readable contract: `standards/profiles/wp/v1/manifest.json`

### 9.2 standalone-react

- `eslint` ^9 with flat config (`eslint.config.mjs`)
- `typescript` ^5, `typescript-eslint` ^8
- `eslint-plugin-react` ^7, `eslint-plugin-react-hooks` ^5, `eslint-plugin-jsx-a11y` ^6
- `vitest` ^2 (exception required to use Jest)
- `@testing-library/react` ^16, `@testing-library/jest-dom` ^6
- `stylelint` ^16, `stylelint-config-standard` ^36
- `@playwright/test` ^1, `@axe-core/playwright` ^4
- Prohibited: `@wordpress/scripts`, Jest (without exception)

Required files: `package.json`, `pnpm-lock.yaml`, `eslint.config.mjs`, `tsconfig.json` (strict: true), `.stylelintrc.json`

See machine-readable contract: `standards/profiles/standalone-react/v1/manifest.json`

### 9.3 standalone-node

- `eslint` ^9, `typescript` ^5, `typescript-eslint` ^8
- `eslint-plugin-n` ^17 (NOT `eslint-plugin-node` — unmaintained)
- `vitest` ^2
- Prohibited: `@wordpress/scripts`, `eslint-plugin-node`

Required files: `package.json`, `pnpm-lock.yaml`, `eslint.config.mjs`, `tsconfig.json` (strict: true); `package.json` (`type: "module"` — enforced by NODE-ESM-001)

See machine-readable contract: `standards/profiles/standalone-node/v1/manifest.json`

### 9.4 python-service

- `ruff`, `mypy` (strict: true), `pytest`, `pytest-cov`
- All config in `pyproject.toml`

See machine-readable contract: `standards/profiles/python/v1/manifest.json`

---

## 10. Bucket Classification

| Bucket | Description | Enforcement |
|--------|-------------|-------------|
| 1 | Security-critical (SQL injection, secrets, arbitrary eval) | Required, fail-closed |
| 2 | Standard conformance (lint, type, test, build) | Required, fail-closed |
| 3 | Advisory quality (unbounded queries, CSS perf, media constraints) | Warn-only until §4 |

---

## 11. Changelog

| Version | Date | Summary |
|---------|------|---------|
| 1.3 | 2026-06-21 | Three-axis versioning; profile manifests; exception parser; GovernedActionGateRule scaffold |
| 1.2 | 2026-03-15 | Added python-service profile; Bucket-3 classification |
| 1.1 | 2026-01-10 | Added standalone-node profile; pnpm enforcement |
| 1.0 | 2025-11-01 | Initial standard: wp-plugin, standalone-react |

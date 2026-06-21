# Starisian Technologies — Coding Standards

The organization-wide coding standards of Starisian Technologies. Read [`docs/standards-catalog.md`](docs/standards-catalog.md) first.

---

## Adopting CI Enforcement (Quick Start)

Every product repo enforces these standards by calling the reusable workflows in this repo — no enforcement logic lives in product repos.

**Step 1.** Create `.github/workflows/standards.yml` in your product repo. Choose the template for your repo type:

| Repo type | Template |
|-----------|----------|
| WordPress plugin / PHP | [caller-templates/standards-php-wordpress.yml](caller-templates/standards-php-wordpress.yml) |
| Standalone React app | [caller-templates/standards-react.yml](caller-templates/standards-react.yml) |
| Standalone Node.js / TypeScript | [caller-templates/standards-node.yml](caller-templates/standards-node.yml) |

**Step 2.** Copy the template verbatim into `.github/workflows/standards.yml` in your product repo.

**Step 3.** Set `enforcement_mode: required` to fail-close the checks, or `enforcement_mode: advisory` for warn-only while onboarding.

Full adoption guide: **[REUSABLE-WORKFLOWS.md](REUSABLE-WORKFLOWS.md)**

Pin strategy: `@v1` (recommended) tracks the latest compatible v1 release. `@v1.x.x` pins to an exact immutable release. Never use `@main`.

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
| [docs/STD-TOOLCHAIN-001.md](docs/STD-TOOLCHAIN-001.md) | Toolchain standard — three-axis versioning, profile definitions, exception process, bucket classification |

### Root — Reference Documents

| Document | Purpose |
| :---- | :---- |
| [ENGINEERING-STANDARDS.md](ENGINEERING-STANDARDS.md) | Engineering standards v2 summary |
| [CI-Enforcement-Matrix.md](CI-Enforcement-Matrix.md) | Org-generic CI rule-status matrix (`ENFORCED`, `WARN`, `SPECIFIED`, `REFERENCE`, `RESERVED`). Product-specific rules live in product repos |
| [THE-STARISIAN-TECHNOLOGIES-CONSTITUTION.md](THE-STARISIAN-TECHNOLOGIES-CONSTITUTION.md) | Organizational constitution |
| [QUESTIONS.md](QUESTIONS.md) | Open clarification questions and review notes |
| [SECURITY.md](SECURITY.md) | Security policy |
| [REUSABLE-WORKFLOWS.md](REUSABLE-WORKFLOWS.md) | Full adoption guide — which workflows to call, example `standards.yml` files, pin strategy, mode inputs |
| [AGENTS.md](AGENTS.md) | Cross-agent maintenance guide for keeping standards code-agnostic, enforceable, and complete |
| [.github/copilot-instructions.md](.github/copilot-instructions.md) | Copilot-facing repository law and maintenance guardrails |
| [CODEOWNERS](CODEOWNERS) | Owner approval gate for all standards changes |

---

## Installable Packages

| Package | Ecosystem | Backs |
| :--- | :--- | :--- |
| `starisian-technologies/coding-standards` | Composer (`/composer.json`, `/ruleset.xml`, `/phpstan.neon`) | PHP-001, PHP-003, PHP-004 (with prefix override), PHP-005 |
| `@starisian-technologies/eslint-config` | npm (`packages/eslint-config/`) | baseline JS/TS quality + JSX a11y + flagged `localStorage` (DIST-005 / JS-002) |
| `@starisian-technologies/tsconfig` | npm (`packages/tsconfig/`) | NODE-001 (TypeScript strict mode) |

### Consume PHP standards

Composer 2.2+ requires consumers to allow the `dealerdirect/phpcodesniffer-composer-installer` plugin (the plugin registers the shipped standard with PHPCS so it's reachable by name):

```bash
composer config repositories.starisian-standards vcs https://github.com/starisian-technologies/starisian-technologies-coding-standards
composer config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer require --dev starisian-technologies/coding-standards
```

The PHPCS installer plugin discovers `ruleset.xml` shipped by this package and registers it as the standard name `StarisianCodingStandards`. Consumer's `phpcs.xml.dist`:

```xml
<?xml version="1.0"?>
<ruleset>
    <rule ref="StarisianCodingStandards"/>
    <!-- PHP-004 — your product's WordPress global prefix (REQUIRED). -->
    <config name="prefixes" value="myproduct"/>
    <!-- Scan targets are the consumer's choice. -->
    <file>./src</file>
    <file>./tests</file>
</ruleset>
```

Consumer's `phpstan.neon`:

```yaml
includes:
    - vendor/starisian-technologies/coding-standards/phpstan.neon
parameters:
    paths:
        - src
        - tests
```

### Consume JS/TS standards

```bash
pnpm add -D @starisian-technologies/eslint-config @starisian-technologies/tsconfig
```

See [`packages/eslint-config/README.md`](packages/eslint-config/README.md) and [`packages/tsconfig/README.md`](packages/tsconfig/README.md) for the three entrypoints (base / node / react).

---

## Architecture Decisions Cross-Reference

This repository is the **HOW** for the organization. The **WHY / WHAT** — architecture decisions (ADR-NNN), invariants (INV-NNN), open questions (OQ-NNN), cross-repo specs, role-boundary statements — lives in each product's own decision registry, never here.

Standards in this repo **cite** ADR / INV / OQ numbers; they do not restate decision text. See [AGENTS.md](AGENTS.md#2a-architecture-decisions-cross-reference) for citation discipline.

---

## Governing Principle

**These are not guidelines. They are law. If it cannot fail a build, it is not a standard.**

Engineering for underserved environments is not about removing features. It is about designing systems that survive reality. Bandwidth is a financial cost. Battery is a finite resource. Connectivity is not guaranteed. Build accordingly.

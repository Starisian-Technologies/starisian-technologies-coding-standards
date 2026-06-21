# Adopting Reusable Workflows from Coding Standards

Every product repo calls reusable workflows from the coding-standards repo
instead of writing its own CI checks. This keeps enforcement consistent
and means a standard update fixes every repo at once.

## Source repo

`Starisian-Technologies/starisian-coding-standards`
(public — no auth needed to call reusable workflows)

## How to add a reusable workflow

Create one file in your repo: `.github/workflows/standards.yml`

This single file can call one or more reusable workflows. You don't need
a separate workflow file per check — one file, multiple jobs.

```yaml
# .github/workflows/standards.yml
name: Platform Standards

on:
  pull_request:
  push:
    branches: [main]

jobs:
  pnpm:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/pnpm-enforcement.yml@v1
    with:
      mode: development

  # Additional domain workflows (PHP, JS, CSS, formatting, etc.) will be added
  # to this repository over time. Add their jobs here once they exist under
  # .github/workflows/ in this repo.
```

## Which jobs to include

Not every repo needs every job. Include only what applies:

| Job | Workflow | Include when |
|---|---|---|
| `pnpm` | `pnpm-enforcement.yml` | Repo has a `package.json` |
| `php` | `php-enforcement.yml` | Repo has PHP code |
| `react` | `react-enforcement.yml` | Repo is a standalone React app |
| `node` | `node-enforcement.yml` | Repo is a standalone Node/TS service |
| `css` | `css-enforcement.yml` | Repo has CSS or stylesheets |
| `media` | `media-enforcement.yml` | Repo records, uploads, or processes audio/video |

PHP-only repos (no JS) skip the `react`, `node`, and `css` jobs.
JS/React repos (no PHP) skip the `php` job.

## Example: PHP WordPress plugin

```yaml
name: Platform Standards
on:
  pull_request:
  push:
    branches: [main]
jobs:
  pnpm:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/pnpm-enforcement.yml@v1
    with:
      mode: development
  php:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/php-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      profile_version: v1
      enforcement_mode: required
      phpstan-level: '5'
```

## Example: standalone React app

```yaml
name: Platform Standards
on:
  pull_request:
  push:
    branches: [main]
jobs:
  pnpm:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/pnpm-enforcement.yml@v1
    with:
      mode: development
  react:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/react-enforcement.yml@v1
    with:
      repo_type: standalone-react
      profile_version: v1
      enforcement_mode: required
  css:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/css-enforcement.yml@v1
    with:
      repo_type: standalone-react
      profile_version: v1
      enforcement_mode: required
  media:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/media-enforcement.yml@v1
    with:
      enforcement_mode: required
      profile_version: v1
```

## Example: standalone Node backend

```yaml
name: Platform Standards
on:
  pull_request:
  push:
    branches: [main]
jobs:
  pnpm:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/pnpm-enforcement.yml@v1
    with:
      mode: development
  node:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/node-enforcement.yml@v1
    with:
      repo_type: standalone-node
      profile_version: v1
      enforcement_mode: required
  media:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/media-enforcement.yml@v1
    with:
      enforcement_mode: required
      profile_version: v1
```

## Mode input

The `mode` input on workflows that support it maps to the enforcement
matrix:

| Mode | Behavior |
|---|---|
| `draft` | Violations produce warnings, never block merge |
| `development` | Violations block merge |
| `production` | Violations block merge (strictest checks) |

Default is `development`. Use `draft` for repos in early development
where you want visibility without blocking.

## Composer auth for PHP repos

If the PHP workflow needs private packages, pass the required secrets to
the reusable workflow (e.g., using `secrets: inherit` or explicit secrets)
— or ensure the reusable workflow accepts auth inputs. The org secrets
`COMPOSER_RESOLVER_CLIENT_ID` and `COMPOSER_RESOLVER_PRIVATE_KEY` are
available to all repos.

## Rules

- **One `standards.yml` file per repo.** Don't create separate workflow
  files per check.
- **Always pin to a release tag (e.g., `@v1`).** The coding-standards
  repo uses semantic versioning; pin to the current major tag so you
  receive non-breaking updates automatically. Never pin to `@main` in
  production — breaking changes land on `main` first. See
  STD-TOOLCHAIN-001 §3 for the three-axis versioning model.
- **Don't duplicate checks.** If the reusable workflow checks phpcs,
  don't also run phpcs separately in another workflow. One source of
  enforcement.
- **Don't override reusable workflow behavior.** If a check needs to
  work differently for your repo, file a request in the coding-standards
  repo — don't fork the workflow locally.

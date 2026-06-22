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
      enforcement_mode: required

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

---

## Adopting these workflows in your repo

This section takes a repo from nothing to an enforcing pull request. If you
follow it top to bottom and your repo type is listed, you do not need to read
any other file in this repository.

### Before you start

Confirm two things:

- You know your **repo type**. Pick exactly one:
  - `wp-plugin` — a WordPress plugin or mu-plugin (PHP).
  - `standalone-react` — a React app or component library (TypeScript/JS).
  - `standalone-node` — a Node service or library, no React (TypeScript/JS).
- The **`v1` tag exists** on `starisian-coding-standards`. You pin to it in
  step 2. If `v1` has not been tagged yet, stop — adoption is not yet possible
  and pinning to `@main` is not allowed (see "Why you pin to a tag" below).

### Step 1 — Copy the caller template for your repo type

Each repo type has a ready-made caller in `caller-templates/`. Copy the one
that matches your repo into your repo's `.github/workflows/` directory as
**`standards.yml`**:

| Your repo type | Copy this file | Into your repo at |
|---|---|---|
| `wp-plugin` | `caller-templates/standards-php-wordpress.yml` | `.github/workflows/standards.yml` |
| `standalone-react` | `caller-templates/standards-react.yml` | `.github/workflows/standards.yml` |
| `standalone-node` | `caller-templates/standards-node.yml` | `.github/workflows/standards.yml` |

The template is the only file you add. You do not copy the enforcement
workflows themselves — they live here and your caller references them.

### Step 2 — Pin to `@v1` and set the enforcement mode

Open the file you just copied. Two things must be set correctly:

1. **The `uses:` line is pinned to `@v1`**, not `@main`. The template ships
   pinned to `@v1` already — confirm it, do not change it to `@main`.
2. **`enforcement_mode` is set explicitly.** Choose one:
   - `enforcement_mode: required` — violations **fail the build** and block merge.
   - `enforcement_mode: advisory` — violations are reported as warnings but do
     **not** block merge. Use this for the first week or two while you clean up
     existing violations, then switch to `required`.

Do not leave `enforcement_mode` unset. The empty value falls back to a
deprecated legacy `mode` input that is scheduled for removal on 2027-01-01.

### Step 3 — Add the exceptions file (only if you need an exception)

If a specific rule cannot pass yet and you need a time-boxed waiver, create
`.standards/standards-exceptions.yml` in your repo root. Every exception entry
requires all six fields — a missing field or a past `expires` date **fails the
build closed**, on purpose:

```yaml
exceptions:
  - id: EXC-001                      # unique id for this exception
    rule: CSS-TYPE-001               # the rule id being waived
    reason: "Legacy theme uses px font sizes; migrating in Q3."
    owner: "@your-github-handle"     # who owns getting this fixed
    expires: "2026-09-30"            # ISO date; after this, the build fails
    approval: "ADR-019"              # the decision authorizing the waiver
```

If you have no exceptions, do not create this file. Its absence is fine and
means "no waivers."

### Step 4 — Commit, open a PR, and read the check

Commit the caller file (and the exceptions file if you added one), push, and
open a pull request. The enforcement workflow runs as a check on the PR.

- In `advisory` mode: the check passes, and any violations appear as warning
  annotations in the PR.
- In `required` mode: the check fails the PR if any violation is found, with
  the rule id and the offending file/line in the log.

You are now enforcing. That is the whole adoption.

---

## Worked example: enforcing a wp-plugin repo end to end

This is the complete set of changes for a WordPress plugin repo, going from
no enforcement to a blocking gate.

**1. Copy the template.** Copy `caller-templates/standards-php-wordpress.yml`
to `.github/workflows/standards.yml` in your repo. After copying,
the file looks like this:

```yaml
name: Standards (PHP / WordPress)
on:
  pull_request:
  push:
    branches: [main]
jobs:
  php:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/php-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      enforcement_mode: required
    secrets: inherit
  css:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/css-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      enforcement_mode: required
    secrets: inherit
  media:
    uses: Starisian-Technologies/starisian-coding-standards/.github/workflows/media-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      enforcement_mode: required
    secrets: inherit
```

**2. Confirm the pins and mode.** Every `uses:` line ends in `@v1`. Every
`enforcement_mode` is `required`. `repo_type` is `wp-plugin` everywhere.
Nothing else needs changing.

**3. (Optional) Start in advisory.** If the repo has existing violations you
cannot fix in this PR, set every `enforcement_mode` to `advisory` instead,
merge, clean up over the next week, then flip them to `required`.

**4. Commit and open the PR.** Add `.github/workflows/standards.yml`,
push the branch, open the PR. The three jobs (php, css, media) run. In
`required` mode, a `font-size: 14px` in a stylesheet fails the `css` job with
`CSS-TYPE-001: px font sizes found` and the file and line. Fix it, push, the
check goes green, you merge.

That is a wp-plugin repo fully adopted: one file added, pinned to `@v1`, mode
set, enforcing on every PR.

---

### Why you pin to a tag, not `@main`

`@v1` is an immutable, released version of these workflows. Pinning to it means
your repo's enforcement only changes when you deliberately move to a new tag —
a standards change on `main` can never silently alter or break your CI.
`@main` is never permitted in a caller, for exactly that reason.

---

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
      enforcement_mode: required
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
      enforcement_mode: required
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
      enforcement_mode: required
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

## Pin strategy

| Pin | When to use |
|-----|-------------|
| `@v1` | Recommended stable pin — tracks latest compatible v1; receives bugfixes automatically |
| `@v1.2.0` | Maximum reproducibility — exact immutable release; requires manual bumps |
| `@main` | Never — breaking changes land here first; not for consumers |

## Rules

- **One `standards.yml` file per repo.** Don't create separate workflow
  files per check.
- **Always pin to a release tag (`@v1` or `@v1.x.x`).** Never pin to
  `@main` in production — breaking changes land on `main` first. See
  STD-TOOLCHAIN-001 §3 for the three-axis versioning model.
- **Don't duplicate checks.** If the reusable workflow checks phpcs,
  don't also run phpcs separately in another workflow. One source of
  enforcement.
- **Don't override reusable workflow behavior.** If a check needs to
  work differently for your repo, file a request in the coding-standards
  repo — don't fork the workflow locally.

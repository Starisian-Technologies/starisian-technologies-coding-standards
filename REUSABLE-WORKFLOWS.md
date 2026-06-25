# Adopting Reusable Workflows from Coding Standards

Every product repo calls reusable workflows from the coding-standards repo
instead of writing its own CI checks. This keeps enforcement consistent
and means a standard update fixes every repo at once.

## Source repo

`Starisian-Technologies/sparxstar-code-conformance`
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
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@v1
    with:
      enforcement_mode: gate

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
- The **`v1.0.0` tag exists** on `sparxstar-code-conformance`. You pin to it
  in step 2. If no semver tag exists yet, stop — adoption is not yet possible
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

### Step 2 — Pin to `@v1.0.0` and set the enforcement mode

Open the file you just copied. Two things must be set correctly:

1. **The `uses:` line is pinned to `@v1.0.0`**, not `@main`. Update the
   template pin from `@v1` to `@v1.0.0` — `@v1.0.0` is the org-locked
   recommendation (immutable; never moves). Do not change it to `@main`.
2. **`enforcement_mode` is set to `advisory` for new consumers.** New repos
   start advisory (warn-only) so onboarding is never blocked by a gate the
   repo does not yet pass. Switch to `gate` only when all violations are
   resolved — see "Advisory first, gate when clean" below.

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
- In `gate` mode: the check fails the PR if any violation is found, with
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
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/php-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      enforcement_mode: gate
    secrets: inherit
  css:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/css-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      enforcement_mode: gate
    secrets: inherit
  media:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/media-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      enforcement_mode: gate
    secrets: inherit
```

**2. Update pins and set advisory mode.** Update every `uses:` line to end in
`@v1.0.0`. Set every `enforcement_mode` to `advisory` for a new consumer —
do not flip to `gate` until violations are zero (see "Advisory first,
gate when clean" below). Set `repo_type` to `wp-plugin` everywhere.

**3. Clean up violations in advisory mode.** With `enforcement_mode: advisory`,
merge the caller file, let the gate run, and fix all reported violations.
Once violations are zero, switch each gate to `enforcement_mode: gate`
in a follow-up PR — that is the deliberate, recorded flip to blocking.

**4. Commit and open the PR.** Add `.github/workflows/standards.yml`,
push the branch, open the PR. The three jobs (php, css, media) run. In
`advisory` mode, a `font-size: 14px` in a stylesheet produces a warning
annotation — the job still passes so the PR is not blocked. Resolve all
warnings, then switch to `enforcement_mode: gate` in a follow-up PR.

That is a wp-plugin repo fully adopted: one file added, pinned to `@v1.0.0`,
advisory mode for onboarding, blocking gate once clean.

---

### Why you pin to `@v1.0.0`, not `@main` or `@v1`

`@v1.0.0` is an immutable, released version of these workflows — it resolves
to the same commit forever. Pinning to it means your enforcement only changes
when you deliberately update the pin. `@v1` is the moving major alias; it
advances automatically to future `v1.x.x` releases, which means a standards
update can silently change your CI behaviour. `@main` is never permitted — it
is the integration branch and breaking changes land there first.

Org-locked pin recommendation: `@v1.0.0`.

---

### Advisory first, gate when clean

New consumers set `enforcement_mode: advisory` on every gate. Advisory runs
report violations as warnings but never block merge, so wiring a new gate
cannot stall the team.

Switch a gate to `enforcement_mode: gate` (blocking) only when **all
three** of the following are true:

1. **Zero violations on the most recent advisory run.** Do not flip with
   known unresolved violations — that red-walls every PR immediately.
2. **Conformance is confirmed.** For code-conformance gates: enforcement
   jobs are green. For spec or ADR gates: conformance tests exist and pass.
3. **The switch is a deliberate, recorded decision.** The repo is declaring
   "we now conform and intend to stay conforming."

Once a gate is set to `gate`, a violation blocks merge. That is the
point — it is the commitment that the repo stays clean. Do not switch to
`gate` as a goal in itself; switch when the repo is provably clean and
you want to keep it that way.

**The platform expectation: start advisory, earn gate.** A gate flipped to
blocking on a repo that is not yet clean is a misconfiguration, not
enforcement.

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
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@v1
    with:
      enforcement_mode: gate
  php:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/php-enforcement.yml@v1
    with:
      repo_type: wp-plugin
      profile_version: v1
      enforcement_mode: gate
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
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@v1
    with:
      enforcement_mode: gate
  react:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/react-enforcement.yml@v1
    with:
      repo_type: standalone-react
      profile_version: v1
      enforcement_mode: gate
  css:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/css-enforcement.yml@v1
    with:
      repo_type: standalone-react
      profile_version: v1
      enforcement_mode: gate
  media:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/media-enforcement.yml@v1
    with:
      enforcement_mode: gate
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
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/pnpm-enforcement.yml@v1
    with:
      enforcement_mode: gate
  node:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/node-enforcement.yml@v1
    with:
      repo_type: standalone-node
      profile_version: v1
      enforcement_mode: gate
  media:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/media-enforcement.yml@v1
    with:
      enforcement_mode: gate
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
| `@v1.0.0` | **Recommended (org-locked).** Immutable — resolves to the same commit forever. Update the pin deliberately to adopt a new release. |
| `@v1` | Moving alias — advances automatically to each new `v1.x.x` release. Available but not the documented recommendation. |
| `@main` | Never — breaking changes land here first; not for consumers. |

## Rules

- **One `standards.yml` file per repo.** Don't create separate workflow
  files per check.
- **Always pin to an immutable semver tag (`@v1.0.0` or later).** `@v1.0.0`
  is the org-locked recommendation. Never pin to `@main` — breaking changes
  land there first. See STD-TOOLCHAIN-001 §3 for the three-axis versioning
  model.
- **Don't duplicate checks.** If the reusable workflow checks phpcs,
  don't also run phpcs separately in another workflow. One source of
  enforcement.
- **Don't override reusable workflow behavior.** If a check needs to
  work differently for your repo, file a request in the coding-standards
  repo — don't fork the workflow locally.

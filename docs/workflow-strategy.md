# Workflow Strategy — Centralized Enforcement Architecture

**Version:** 0.1  
**Scope:** Organization-wide — governs how Starisian Technologies enforces coding standards across repositories.

---

## Goal

A single source of truth for enforcement logic. Consuming repos are as thin as possible. When a check changes, update one file in this repo — not one file in every repo.

---

## What GitHub Supports (and What It Does Not)

GitHub Actions provides two mechanisms relevant to centralization:

| Mechanism | How it works | Limitation |
|---|---|---|
| **Reusable workflows** (`workflow_call`) | A repo calls a workflow defined in another repo. The caller is ≤6 lines. All logic lives in the called workflow. | Only works for triggers the caller controls (push, PR, schedule, etc.). Cannot be used for event triggers that fire from repo-local events (issues, repository_dispatch). |
| **Workflow templates** | Canonical `.yml` files stored in a templates directory. Repos copy them once. | The copy drifts unless a sync mechanism is maintained. This is unavoidable for repo-local triggers. |

---

## Workflow Inventory

### Layer 1 — Reusable Workflows (live in this repo, called from consumers)

These are the DRY enforcement layer. All logic lives here. Consuming repos call them in ≤6–12 lines.

| File | Trigger | Purpose | Status |
|---|---|---|---|
| `.github/workflows/pnpm-enforcement.yml` | `workflow_call` | Enforce pnpm as the only package manager (ADR-017, NODE-PKG-001..004) | Active |
| `.github/workflows/adr-conformance.yml` | `workflow_call` | Check ADR/INV/OQ citation patterns in consuming repos when the standards registry changes | Active (scan only; full diff pending) |

**Caller pattern:**

```yaml
# In consuming-repo/.github/workflows/standards.yml
jobs:
  pnpm:
    uses: Starisian-Technologies/starisian-technologies-coding-standards/.github/workflows/pnpm-enforcement.yml@main
    with:
      mode: development
```

---

### Layer 2 — Event Dispatch Workflows (live in this repo, fire to consumers)

These workflows fire on changes in this repo and push notifications or trigger actions in consuming repos.

| File | Trigger | Purpose | Status |
|---|---|---|---|
| `.github/workflows/adr-dispatch.yml` | `push` to `main` on standards files | Dispatch `adr-registry-changed` to all registered consuming repos | Active (requires `STANDARDS_DISPATCH_TOKEN` and `ADR_CONSUMER_REPOS` secrets) |

**Required secrets:**

| Secret | Scope | Value |
|---|---|---|
| `STANDARDS_DISPATCH_TOKEN` | Repo or org | PAT or GitHub App token with `repo` scope on every consuming repo. See QUESTIONS.md WF-2. |
| `ADR_CONSUMER_REPOS` | Repo or org | Newline- or comma-separated `owner/repo` slugs. Example: `Starisian-Technologies/sparxstar-ouroboros-integrity`. See QUESTIONS.md WF-1. |

---

### Layer 3 — Repo-Local Workflows (templates only; copied to each repo)

These workflows respond to repo-local events and cannot be centralized. The canonical source is `workflow-templates/`. Each repo copies once; updates are manual.

| Template | Trigger | Purpose |
|---|---|---|
| `workflow-templates/first-interaction.yml` | `issues`, `pull_request_target` | Welcome first-time contributors with repo-appropriate messaging |
| `workflow-templates/adr-conformance-receiver.yml` | `repository_dispatch: [adr-registry-changed]` | Thin wrapper; calls the reusable `adr-conformance.yml` above |

**The receiver template is maximally thin** — it is the thin end of the DRY wedge. The receiver fires the event trigger and immediately delegates to the reusable workflow in this repo. When conformance logic changes, only `adr-conformance.yml` in this repo needs updating; the receiver files in consuming repos do not change.

---

## ADR Conformance Flow (end-to-end)

```
Standards repo                         Consuming repo
─────────────────────────────────────  ──────────────────────────────────────
1. ADR/standards file changed on main
2. adr-dispatch.yml fires
3. Reads ADR_CONSUMER_REPOS secret
4. POST /repos/{owner}/{repo}/dispatches  ──► repository_dispatch fires
   (for each consuming repo)              adr-conformance-receiver.yml (≤12 lines)
                                          └─► calls adr-conformance.yml (this repo)
                                              - logs the change type
                                              - scans for ADR/INV/OQ citations
                                              - posts ::notice to the run
                                              - (future) diffs citations against
                                                registry for superseded entries
```

---

## Adding a New Consuming Repo

1. Copy `workflow-templates/adr-conformance-receiver.yml` to `.github/workflows/adr-conformance-receiver.yml` in the consuming repo.
2. Add the consuming repo's slug (`owner/repo`) to the `ADR_CONSUMER_REPOS` secret.
3. No other changes required in the consuming repo.

For pnpm enforcement, add a caller workflow:

```yaml
# .github/workflows/standards.yml in the consuming repo
name: Standards
on: [pull_request, push]
jobs:
  pnpm:
    uses: Starisian-Technologies/starisian-technologies-coding-standards/.github/workflows/pnpm-enforcement.yml@main
    with:
      mode: development
```

---

## Open Questions

See `QUESTIONS.md` sections WF-1 through WF-8 for unresolved decisions affecting this architecture.

---

## Future Additions

Per the build sequence in `docs/standards-catalog.md`, upcoming reusable workflows include:

| Planned workflow | Domain | Status |
|---|---|---|
| `php-lint.yml` | PHP / WordPress | Planned (awaits `phpcs.xml.dist` config package) |
| `js-lint.yml` | JavaScript / TypeScript | Planned (awaits ESLint config package) |
| `css-lint.yml` | CSS | Planned (awaits Stylelint config package) |
| `a11y.yml` | Accessibility | Planned (axe-core / Playwright) |
| `bundle-size.yml` | Performance budgets | Planned |

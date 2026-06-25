# Setup & Install — `version-drift-enforcement`

This document is for a consuming repo wiring the `version-drift-enforcement`
gate into its CI. Every concrete value (workflow path, tag, secret name,
variable name, input name) is read from the live workflow files in this repo.

---

## Section 0 — Prerequisites

This gate cannot run without org-level infrastructure. Check every item here
before wiring. If any prerequisite is missing the gate fails with a generic
auth error — not a clear "prerequisite missing" message.

### GitHub Apps — two Apps required

| App | Org variable that holds its client-id | Permission required | What it does |
|---|---|---|---|
| `composer-resolver` | `vars.COMPOSER_RESOLVER_CLIENT_ID` | Contents: Read | Checks out `sparxstar-code-conformance` to read `config/version-policy.yml` and git tags |
| `contract-sync` | `vars.CONTRACT_SYNC_CLIENT_ID` | Contents: Read/Write | Commits the drift record to `drift/<slug>.json` in `sparxstar-code-conformance` |

Both Apps must be installed in the **Starisian-Technologies** org and their
installation must include `sparxstar-code-conformance` in their repository
scope. **An App existing in the org is not the same as it being scoped to
the repo.** Verify under org Settings → GitHub Apps → (App name) →
Repository access. A missing scope produces a "repository not found" error
with no indication the App caused it.

`composer-resolver` is required on every run, even when `write_drift_record:
false`. `contract-sync` is only exercised when `write_drift_record: true`
(the default), but its secret must still be declared in the caller's
`secrets:` block.

### Org secrets

Must exist at org or repo level under Settings → Secrets and variables →
Actions → Secrets before a consumer passes them. These are secrets — do not
store them as Variables.

| Secret name | What it holds |
|---|---|
| `COMPOSER_RESOLVER_PRIVATE_KEY` | PEM private key for the composer-resolver App |
| `CONTRACT_SYNC_PRIVATE_KEY` | PEM private key for the contract-sync App |

### Org variables

Must exist as Variables (not Secrets) under Settings → Secrets and variables →
Actions → Variables. A client-id stored in the wrong slot (or stored as a
Secret instead of a Variable) causes the token-mint step to fail with an
opaque 401.

| Variable name | What it holds |
|---|---|
| `COMPOSER_RESOLVER_CLIENT_ID` | Client-id string for the composer-resolver App |
| `CONTRACT_SYNC_CLIENT_ID` | Client-id string for the contract-sync App |

### Who provisions these

App installation and org secret/variable creation require org-admin rights.
A consuming repo's developer typically cannot do this. Request provisioning
from your org administrator and confirm all four items above exist before
wiring the gate.

---

## Section 1 — What this gate does

The `version-drift-enforcement` reusable workflow checks whether a consuming
repo's pinned `contract_ref` meets the minimum version floor defined in
`config/version-policy.yml` and reports how far it drifts from the current
recommended version. It applies a three-tier result: an exact match with the
recommended version passes silently; a ref that is behind the recommended but
at or above the floor passes with a `::warning::` annotation visible in the
Actions UI; a ref below the floor hard-fails the job unconditionally
regardless of any `enforcement_mode` setting. On every passing or warning run
the gate also commits a JSON drift record to `drift/` in this repo via the
contract-sync App, giving the platform team a single place to see every
consumer's version state without querying each repo individually.

---

## Section 2 — The `uses:` line

```yaml
uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/version-drift-enforcement.yml@v1.0.0
```

### Tags that currently exist

| Tag | Commit | Type | Meaning |
|---|---|---|---|
| `v1.0.0` | `64b0ccf` | Immutable semver release | Exact point-in-time snapshot. Never moves. |
| `v1` | `64b0ccf` | Moving major alias | Advances to each new `v1.x.x` release. |

### Which to pin

**Pin `@v1.0.0`.** This is the org-wide locked recommendation.

The `v1.0.0` tag is immutable — it always resolves to the same commit. When
a new release is cut (e.g. `v1.1.0`), `v1.0.0` does not move. This means your
pinned gate is stable until you deliberately upgrade the pin, which makes
audit, rollback, and incident diagnosis straightforward.

`@v1` is the moving major alias — it will advance to future `v1.x.x` releases
automatically. It is available and will continue to work, but it is not the
documented recommendation for consumers. Do not use `@main`.

---

## Section 3 — Inputs

Read from `on.workflow_call.inputs` in
`.github/workflows/version-drift-enforcement.yml`:

| Input | Required | Type | Default | Purpose and valid values |
|---|---|---|---|---|
| `contract_ref` | **yes** | `string` | — | The ref the calling repo pins its enforcement workflow calls to. Must be an existing tag matching `vMAJOR`, `vMAJOR.MINOR`, or `vMAJOR.MINOR.PATCH` (e.g. `v1.0.0`). Hard-fails if the tag does not exist on `sparxstar-code-conformance` or falls below the floor in `config/version-policy.yml`. |
| `calling_repo` | no | `string` | `''` | Full `owner/repo` of the caller. Defaults to `github.repository` when empty. Override only when the caller needs to report a different identity — this is rare. |
| `write_drift_record` | no | `boolean` | `true` | Whether to commit a JSON drift record to `drift/` in this repo via contract-sync. Set `false` for read-only runs. Omit on normal wiring. |

---

## Section 4 — Secrets the consumer must pass

Read from `on.workflow_call.secrets` in
`.github/workflows/version-drift-enforcement.yml`.

```yaml
secrets:
  COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
  CONTRACT_SYNC_PRIVATE_KEY:     ${{ secrets.CONTRACT_SYNC_PRIVATE_KEY }}
```

| Secret name | Required | Purpose |
|---|---|---|
| `COMPOSER_RESOLVER_PRIVATE_KEY` | **always** | Private key for the composer-resolver App. Mints a read token to check out `sparxstar-code-conformance`. |
| `CONTRACT_SYNC_PRIVATE_KEY` | required when `write_drift_record: true` (the default) | Private key for the contract-sync App. Mints a write token to commit the drift record. Must be declared even when `write_drift_record: false` — omitting it causes a workflow validation error. |

**`secrets: inherit` is prohibited.** Pass each secret by name as shown. Only
these two cross the boundary.

**Org variables are not passed by the consumer.** `COMPOSER_RESOLVER_CLIENT_ID`
and `CONTRACT_SYNC_CLIENT_ID` are org Variables (referenced as `vars.*` inside
the workflow). They propagate automatically and must not appear in the caller's
`secrets:` block.

---

## Section 5 — Required caller setup

**Trigger:** use `pull_request` (not `pull_request_target`). The workflow reads
config from the checked-out repo. `pull_request_target` runs with base-branch
context and elevated write access that is both unnecessary and unsafe for this
gate.

**Minimum permissions for the calling job:** none beyond defaults. The workflow
mints its own scoped tokens internally. No `permissions:` block is needed in
the caller job.

**Required files in your repo:** none. The floor config (`config/version-policy.yml`)
lives in `sparxstar-code-conformance` and is checked out by the gate. No
config files need to be added to the consuming repo.

---

## Section 6 — Enforcement mode: advisory first, gate when clean

### New consumers: start with `enforcement_mode: advisory`

When wiring any gate for the first time, set `enforcement_mode: advisory`.
In advisory mode, violations are reported as warnings in the Actions UI but
**never block merge**. This prevents a consuming repo from being held hostage
by a gate it does not yet pass.

### Switching to `enforcement_mode: gate`

Switch a gate from advisory to required (blocking) only when **all three** of
the following are true:

1. **Zero violations on the most recent advisory run.** Do not flip to
   blocking with known unresolved violations — that red-walls every PR
   immediately.
2. **Conformance is confirmed.** For code-conformance gates: enforcement jobs
   are green. For spec or ADR gates: conformance tests exist and pass.
3. **The switch is a deliberate, recorded decision.** The repo is declaring
   "we now conform and intend to stay conforming." Switching to `required` is
   a commitment, not a default to leave set.

Once set to `required`, a violation blocks merge. That is the point — it is
the commitment that the repo stays clean. Do not switch to `required` as a
goal in itself; switch when the repo is provably clean and you intend to keep
it that way.

**The platform expectation: start advisory, earn gate.** A gate flipped to
blocking on a repo that is not yet clean is a misconfiguration, not enforcement.

### `version-drift-enforcement` and enforcement_mode

The `version-drift-enforcement` workflow does not accept an `enforcement_mode`
input. The three-tier check is always enforced as written: warn for refs above
floor but below recommended, hard-fail for refs below floor. There is no
advisory-only mode for the floor check. Wire this gate when the prerequisites
in Section 0 are satisfied — the gate itself does not need an onboarding period
because it only checks the version tag you pin, which you control.

---

## Section 7 — Minimal copy-paste caller block

```yaml
jobs:
  version-check:
    uses: Starisian-Technologies/sparxstar-code-conformance/.github/workflows/version-drift-enforcement.yml@v1.0.0
    with:
      contract_ref: v1.0.0        # ← the ref you pin your other enforcement jobs to
    secrets:
      COMPOSER_RESOLVER_PRIVATE_KEY: ${{ secrets.COMPOSER_RESOLVER_PRIVATE_KEY }}
      CONTRACT_SYNC_PRIVATE_KEY:     ${{ secrets.CONTRACT_SYNC_PRIVATE_KEY }}
```

**Values to substitute:** `contract_ref` — set this to the same ref you
pass to `@` when calling other enforcement workflows in this same caller
file. If your other jobs are pinned `@v1.0.0`, pass `v1.0.0` here.

Everything else is literal. Do not add `secrets: inherit`.

---

## Section 8 — Sequencing rule for cross-repo callers

A secret must be declared in this reusable workflow's `on.workflow_call.secrets`
block before a consumer can pass it. If you pass a secret the workflow has not
yet declared, GitHub silently drops it rather than forwarding it. Conversely, if
this workflow adds a new secret declaration after a consumer has pinned an older
tag, the consumer's pinned tag still contains the old declaration and will not
forward the new secret.

Always re-tag (move `v1` to the new commit and cut a new immutable semver tag)
after any change to secrets, inputs, or job logic. Consumers pinned to an
immutable tag must update their pin to receive the new declaration. Notify
consuming repos when a secrets block changes — they cannot pick it up
automatically on an immutable pin.

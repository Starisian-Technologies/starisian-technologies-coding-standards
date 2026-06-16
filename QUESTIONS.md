# Questions for Clarification — Coding Standards Review

This file tracks current clarification questions, review notes, and organization implications. Originally written against the v1 monolithic standards; those have been split into the `docs/` rulebooks and the legacy file deleted (2026-06-12).

## What It Gets Right

1. **The authority layer as the only named dependency**
   Naming the authority layer specifically while keeping other dependencies provider-agnostic is architecturally sound. The authority layer is structural as the control plane, while other dependencies are operational.

2. **Fail closed as the default posture**
   Every unavailability condition (authority layer down, context null, authority null) resolves to fail closed. This is the correct governance stance.

3. **Idempotency as a first-class requirement**
   Making idempotency mandatory for governed mutations is correct for unreliable networks where retries are expected.

4. **Bounded limits throughout the system**
   Explicit limits for query depth, list size, chunk size, CPU time, concurrent ops, bundle size, event rate, and blob size reflect disciplined engineering for constrained environments.

5. **No partial success states**
   The transaction pattern in §8.5 (commit or roll back, including upload cleanup on DB failure) correctly avoids orphaned state.

6. **Write-order guarantee**
   The order DB commit → cache invalidation → edge purge → event emission addresses common distributed-systems failure modes.

## Where It Has Gaps or Tensions

1. **Audio limits vs. preservation of non-digitized, oral languages**
   §4.1 caps production audio at 120 seconds. This is efficient for everyday capture, but can truncate culturally important long-form recordings. Clarification is needed on whether this cap applies only to intake/capture while longer recordings use a governed async flow.

2. **Concurrency model may be aggressive for low-end Android on 2G**
   §0.4 appears to impose a one-operation-per-user rule, while §8.1 separately caps active mutations and active uploads and rejects concurrent requests with 429. The standard should explicitly connect this with client queueing/retry behavior under high latency when responses may be delayed or dropped.

3. **Offline-first architecture lacks explicit state-machine standards**
   IndexedDB queueing is specified (§3.5), but there is no governed offline state model defining what is available offline, what queues, what blocks until online, and what requires fresh authority-layer context.

4. **Governance token flow is not reflected in this standards document**
   The standards cover authority-layer resolution and write ordering, but do not explicitly include governance token lifecycle requirements for high-stakes vault writes. The document should reference this requirement directly or point to the authoritative governance spec.

5. **CSS blur/shadow rule in §13.6 needs rationale**
   The rule is appropriate for constrained devices, but adding one line of rationale would improve enforceability and reduce workaround behavior.

6. **Dead-letter queue rule (§12.3) has no retention policy**
   Alerting and manual retry are defined, but retention and deletion policy for dead-letter entries is not. Governance-sensitive failures need explicit retention and audit handling.

7. **No code standard for access-tier propagation**
   Access tiers are referenced at edge level (§7.5), but there is no coding standard for how access tier is represented and propagated through the request lifecycle.

## What This Implies for the Platform

- This document should be published to a central standards repository so rules can be consumed and automated consistently.
- CI enforcement in §13 is the key part of the developer loop. Mechanical violations should be blocked in CI so review focuses on architecture and correctness.
- The audio-duration policy should be resolved before publication to avoid hard-coding the wrong default behavior across downstream repositories.
- If version 1.0 is intended as normative law, a clear versioning and propagation policy should be defined for dependent repositories.

## One Question Before This Is Considered Final

The document scopes to WordPress + PHP as the reference implementation. The JavaScript section applies broadly, but there is no Python-specific section for MCP services.

**Question:** Is Python in scope for this document, or should it be governed by a separate standard?

If Python services process audio and governed jobs, async job rules, bounded execution, and no-silent-failure expectations likely apply; however, there are no Python-specific enforcement rules. This should be explicitly scoped in or scoped out.

---

## Session Questions — 2026-06-16

These questions arose during the workflow infrastructure session.

### WF-1: ADR consumer registry — how does the sender know which repos to notify?

The `adr-dispatch.yml` sender fires a `repository_dispatch` event to consuming repos when ADR-related files change. To dispatch cross-repo, the workflow needs a list of target repo slugs. Three options exist:

- **Org secret** (`ADR_CONSUMER_REPOS`): comma-separated `owner/repo` list, stored once at the org level. Repos added or removed without touching this workflow.
- **Hardcoded list in the workflow**: simple, version-controlled, but requires a PR here whenever a new repo joins the platform.
- **GitHub App** with org-wide `contents:write` and `actions:write` scope: most scalable; dispatches to all repos in the org matching a topic tag.

**Question:** Which mechanism do you want for the consumer registry? If org secret, I will document the secret name and format. If GitHub App, I will document the App permissions required.

---

### WF-2: Cross-repo dispatch token — PAT or GitHub App?

Dispatching `repository_dispatch` to another repo requires a token with `repo` scope (or a GitHub App installation token). `GITHUB_TOKEN` is scoped to the current repo and cannot dispatch cross-repo.

**Question:** Should this use a long-lived org-level PAT (`STANDARDS_DISPATCH_TOKEN`) or a GitHub App? A PAT is simpler now; a GitHub App does not expire and is better for automation at scale.

---

### WF-3: `first-interaction.yml` — standards-repo messages vs. product-repo messages

The provided `first-interaction.yml` messages reference "auto-synced interfaces and contracts" — language appropriate for an interface/contract repo, not for this standards repo. The template in `workflow-templates/` carries placeholder `TODO:` markers for per-repo customization.

**Question:** Do you want the first-interaction messages for THIS standards repo to address contributors proposing changes to the standards themselves? Or should this repo's issues be scoped to internal team only (i.e., no public first-interaction welcome needed)?

---

### WF-4: WordPress 7.0 and the Abilities API

The problem statement references "We need WordPress 7.0 because we use the Abilities API." As of 2026-06, WordPress has not shipped a `7.0` release; the current series is `6.x`. The "Abilities API" (a modernization of the capabilities system) has been discussed in WordPress core proposals but is not in a stable release.

**Question:** Is WordPress 7.0 the internal target version you are building toward (and acceptable to gate features behind), or is there a currently-available alternative API or plugin that provides equivalent functionality? This affects the version floor in `docs/php-wordpress-standard.md` and CI matrix configuration.

---

### WF-5: Secure Custom Fields — which plugin?

The problem statement specifies "Secure Custom Fields not ACF." This appears to refer to the fork of Advanced Custom Fields maintained after the WP Engine / Automattic dispute, now published as "Secure Custom Fields" in the WordPress plugin directory.

**Question:** Is this correct? If so, should `docs/php-wordpress-standard.md` name "Secure Custom Fields" explicitly as the required meta-fields plugin, and should ACF be listed as a forbidden dependency?

---

### WF-6: Ubuntu 22 → 24 and PHP 8.2 → 8.3 migration timelines

The problem statement flags both migrations as imminent ("will come quick"). The CI matrix in workflows currently targets a single runtime.

**Question:** Should CI workflows immediately run dual-matrix (Ubuntu 22 + 24, PHP 8.2 + 8.3) now, or should the upgrade happen in a single cutover? Dual-matrix is safer and recommended but doubles CI minutes until the old version is dropped.

---

### WF-7: ADR and tech-specs repos — access for this agent

Both `sparxstar-architecture-decision-record` and `sparxstar-product-technical--specifications` returned 404 — either private or not yet migrated to this org. ADR numbers cannot be cited in standards text until the registry is accessible.

**Question:** When will these repos be accessible (read-only) from this agent's context? Until then, ADR citations in standards documents will use placeholder `ADR-TBD` markers that must be resolved in a follow-up session.

---

### WF-8: Reusable workflow for `first-interaction` — is org-level `.github` repo an option?

GitHub supports a `.github` repository at the organization level (`Starisian-Technologies/.github`) where workflow templates are published for the entire org. Contributors see these in the Actions "new workflow" UI, and they can be enforced via org rulesets.

**Question:** Should a `Starisian-Technologies/.github` repo be created (or does one already exist) to host org-level workflow templates? If yes, the `first-interaction.yml` template could live there and be auto-suggested to every new repo in the org — a stronger DRY posture than copying from this repo manually.

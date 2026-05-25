# Questions for Clarification — Coding Standards Review

This file tracks current clarification questions against `sparxstar-coding-standards-v1.md`.

## What It Gets Right

1. **Sirus as the only named dependency**
   Naming Sirus specifically while keeping other dependencies provider-agnostic is architecturally sound. Sirus is structural as the control plane, while other dependencies are operational.

2. **Fail closed as the default posture**
   Every unavailability condition (Sirus down, context null, authority null) resolves to fail closed. This is the correct governance stance.

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
   Section 4.1 caps production audio at 120 seconds. This is efficient for everyday capture, but can truncate culturally important long-form recordings. Clarification is needed on whether this cap applies only to intake/capture while longer recordings use a governed async flow.

2. **Concurrency model may be aggressive for low-end Android on 2G**
   §0.4 appears to impose a one-operation-per-user rule, while §8.1 separately caps active mutations and active uploads and rejects concurrent requests with 429. The standard should explicitly connect this with client queueing/retry behavior under high latency when responses may be delayed or dropped.

3. **Offline-first architecture lacks explicit state-machine standards**
   IndexedDB queueing is specified (§3.5), but there is no governed offline state model defining what is available offline, what queues, what blocks until online, and what requires fresh Sirus context.

4. **Governance token flow is not reflected in this standards document**
   The standards cover Sirus authority resolution and write ordering, but do not explicitly include governance token lifecycle requirements for high-stakes vault writes. The document should reference this requirement directly or point to the authoritative governance spec.

5. **CSS blur/shadow rule in §13.6 needs rationale**
   The rule is appropriate for constrained devices, but adding one line of rationale would improve enforceability and reduce workaround behavior.

6. **Section 0.6 reserved gap needs explicit wording**
   Although reserved for numbering stability, a clear “intentionally vacant—do not assign content” note prevents onboarding confusion.

7. **Dead-letter queue rule (§12.3) has no retention policy**
   Alerting and manual retry are defined, but retention and deletion policy for dead-letter entries is not. Governance-sensitive failures need explicit retention and audit handling.

8. **No code standard for DVE trust-tier propagation**
   Trust tiers are referenced at edge level (§7.5), but there is no coding standard for how trust tier is represented and propagated through request lifecycle.

## What This Implies for the Platform

- This document should be published to `sparxstar-platform-standards` so rules can be consumed and automated consistently.
- CI enforcement in §13 is the key part of the developer loop. Mechanical violations should be blocked in CI so review focuses on architecture and correctness.
- The audio-duration policy should be resolved before publication to avoid hard-coding the wrong default behavior across downstream repositories.
- If version 1.0 is intended as normative law, a clear versioning and propagation policy should be defined for dependent repositories.

## One Question Before This Is Considered Final

The document scopes to WordPress + PHP as the reference implementation. The JavaScript section applies broadly, but there is no Python-specific section for MCP services.

**Question:** Is Python in scope for this document, or should it be governed by a separate standard?

If Python services process audio and governed jobs, async job rules, bounded execution, and no-silent-failure expectations likely apply; however, there are no Python-specific enforcement rules. This should be explicitly scoped in or scoped out.

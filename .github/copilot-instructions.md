Starisian Technologies --- Copilot Instructions
=============================================

This repository defines coding standards. Treat this repo as the source of policy, not product implementation.

Reference repositories (read via MCP)
-------------------------------------

Before reviewing any PR in this repo, read these two internal repos:

-   **ADR Registry:** `Starisian-Technologies/sparxstar-architecture-decision-record` --- decisions, invariants, open questions. Every standard must trace to a decision here. If a standard has no ADR source, flag it.
-   **Product Specs:** `Starisian-Technologies/sparxstar-product-technical--specifications` --- what each product does. Standards must not contradict how products are specced. If a standard conflicts with a spec, flag both.
-   **This repo:** `Starisian-Technologies/starisian-technologies-coding-standards` --- you are here. Standards, configs, workflows, matrices.

Mission
-------

-   Keep standards enforceable, testable, and technology-agnostic by default.
-   Encode stack-specific rules only where implementation constraints demand it.
-   Preserve consistency between `docs/`, root reference files, and agent guidance.

Authoritative sources (within this repo)
----------------------------------------

1.  `docs/standards-catalog.md` --- master catalog, read this first
2.  `docs/standards-handbook.md` --- global principles
3.  `docs/*-standard.md` --- per-language standards
4.  `docs/enforcement-matrix.md` and `CI-Enforcement-Matrix.md` --- enforcement mapping

Legacy material at the root (`ENGINEERING-STANDARDS.md`) is reference only; not final authority unless merged into the canonical docs.

Review checklist
----------------

On every PR, check:

1.  **ADR traceability.** Does every standard cite its source ADR/INV? A standard with no source has no authority --- flag it.
2.  **ADR compliance.** Read the ADR registry via MCP. Does the standard contradict any decision or invariant? Flag with the number.
3.  **OQ discipline.** Does the standard assume an OPEN OQ is resolved? Flag with the OQ number.
4.  **Spec consistency.** Read the product specs via MCP. Does the standard conflict with how a product is specced? Flag both.
5.  **Matrix honesty.** Is a row marked ENFORCED without a workflow? Flag --- status should be SPECIFIED.
6.  **Trademark discipline.** Any product name, repo name, service name, or trademark? Flag --- this is the org-wide repo, zero product names.
7.  **Governance snapshots.** Is the PR editing a file under `.github/instructions/governance/`? Flag --- auto-synced, read-only.

Trademark discipline
--------------------

Zero product names. Refer to capabilities by generic role: "the authority layer", "the auth SDK", "the audio capture SDK", "the runtime layer". If a rule only makes sense with a product name, the rule belongs in that product's repo.

Non-negotiable engineering rules
--------------------------------

-   If a rule cannot be enforced or verified, it is incomplete.
-   Sanitize → Validate → Escape (in that order).
-   No silent failure.
-   Bounded execution and explicit limits are mandatory.
-   Fail-closed for authority, trust, and safety decisions.
-   Platform abstractions required; no hardwired provider-specific behavior.

Stack coverage
--------------

All maintenance must keep standards current for: PHP, WordPress, JavaScript, React, Node, CSS, SQL, PostgreSQL, Neo4j, XML, JSON, Laravel, Vite.

Security guardrails
-------------------

-   Never commit credentials, tokens, or secrets.
-   Require parameterized database access.
-   Keep CI stages explicit and ordered.

What you must NOT do
--------------------

-   You are a reviewer, not the authority. Flag and explain. The owner decides.
-   Do not suggest edits to governance snapshot files.
-   Do not add product names in suggested changes.

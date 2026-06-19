Agent Guide --- Starisian Technologies Coding Standards
=====================================================

This repository is policy infrastructure. Its output is enforceable standards, tool configs, and reusable workflows that all product repositories implement.

**The one rule above all others:** zero product names, repo names, concept names, or anything trademarkable in this repo. If a rule only makes sense with a product name attached, it belongs in that product's repo, not here. See `docs/standards-catalog.md`.

Repository role
---------------

This is the **HOW** for the organization: language-agnostic principles, per-language implementation rules, enforcement configs, reusable workflows, and the enforcement matrix.

The **WHY / WHAT** (architecture decisions, product specs) lives elsewhere --- in the ADR registry and the product-specs repo. This repo cites them by number, never restates.

Canonical documents
-------------------

-   `docs/standards-catalog.md` --- master catalog, read this first
-   `docs/standards-handbook.md` --- global principles
-   `docs/*-standard.md` --- per-language standards
-   `docs/enforcement-matrix.md` --- detailed enforcement mapping
-   `CI-Enforcement-Matrix.md` --- summary matrix
-   `.github/workflows/` --- reusable enforcement workflows

What you may do
---------------

-   Draft new standards citing ADR sources
-   Draft reusable workflows enforcing existing standards
-   Update tool configs to match updated standards
-   Update the enforcement matrix for new workflows
-   Update the catalog when standards are added
-   Fix inconsistencies between prose and configs

What you must NOT do
--------------------

-   **Invent standards without an ADR source.** No ADR = no standard. File an open question instead.
-   **Add product names anywhere.** Not in filenames, not in prose, not in code comments, not in examples.
-   **Edit governance snapshot files** under `.github/instructions/governance/`. Auto-synced, read-only.
-   **Mark a matrix row ENFORCED without a workflow.** Honesty rule: if no workflow checks it, the status is SPECIFIED.
-   **Create decision records.** This repo has no ADRs. Decisions live in the ADR registry.

ADR cross-reference discipline
------------------------------

Cite ADR-NNN, INV-NNN, OQ-NNN by number in standards text and commit messages. Never paraphrase.

Before editing standards text:

1.  Contradicts an INV? → Block, cite number.
2.  Assumes an OPEN OQ is resolved? → Block, cite OQ.
3.  Duplicates an ADR/INV statement? → Replace with citation.
4.  Names a product? → Replace with generic concept.

If the ADR registry is inaccessible, do not fabricate numbers. Ask.

Enforcement-first authoring
---------------------------

For each new or changed rule, maintain:

1.  Rule statement
2.  Enforcement status (ENFORCED / SPECIFIED / WARN)
3.  Tooling path (lint / static analysis / test / build gate)
4.  CI stage placement

If enforcement is not yet automated, mark SPECIFIED. Do not weaken the requirement language.

Content quality rules
---------------------

-   Standards are measurable and enforceable
-   Global principles are language-agnostic
-   Stack-specific constraints only where justified
-   Normative language: MUST, MUST NOT, REQUIRED, FORBIDDEN
-   Separate rules from examples
-   Security and privacy constraints are explicit

Coverage domains
----------------

Maintain explicit standards for: PHP, WordPress, JavaScript, React, Node, CSS, SQL, PostgreSQL, Neo4j, XML, JSON, Laravel, Vite.

When a stack lacks a dedicated standard, update the handbook and matrices to capture rules and enforcement status.

Governance reference
--------------------

Read `.github/instructions/governance/` for current decisions, invariants, and open questions. These are the rules your standards enforce. Do not assume rules not in the governance reference.

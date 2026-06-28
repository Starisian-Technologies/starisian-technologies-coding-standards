# Contributing

Thank you for improving this standards repository.

## Scope

This repository contains organization-wide coding standards, reusable enforcement workflows, and governance documentation.

- Do not add product names, product-specific examples, or repository-specific architecture from product codebases.
- Do not edit governance snapshot files under `.github/instructions/governance/`.
- Every new or changed standard must cite an authoritative ADR/INV/OQ identifier.

## Prerequisites

- Node.js 20+
- pnpm 9+
- PHP 8.2+
- Composer 2+

## Local validation

Run these before opening a pull request:

```bash
pnpm lint
pnpm test
composer lint
composer analyze
```

If PHP tooling is unavailable in your environment, note that in the PR and run it in CI.

## Pull request requirements

- Use the pull request template.
- Include risk assessment and rollback notes.
- Keep changes narrowly scoped.
- Update docs and enforcement matrices when standards or workflows change.
- Do not mark a matrix row `ENFORCED` without a real workflow/tool gate.

## Security and secrets

- Never commit secrets, keys, or credentials.
- Use GitHub secret scanning and dependency review findings as blocking input.
- For vulnerabilities, follow `SECURITY.md`.

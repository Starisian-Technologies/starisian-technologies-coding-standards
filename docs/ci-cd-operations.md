# CI/CD Operations

## Workflow categories

- **Reusable enforcement workflows** for consuming repositories (`.github/workflows/*-enforcement.yml`).
- **Repository self-check workflows** for this standards repository (`formatting.yml`, `markdownlint.yml`, standards checks).
- **Security governance workflows** (`dependency-review.yml`, `secret-scanning.yml`).

## CI expectations

- Workflows must be deterministic and fail-closed for required gates.
- Every `ENFORCED` matrix row must have a real workflow/tool implementation.
- Secrets must be referenced via GitHub Secrets only.
- Workflow changes require CODEOWNERS review.

## Operational checks

Before release tagging:

1. Verify lint/test pipelines pass.
2. Verify security workflows report no blocking findings.
3. Confirm matrix status aligns with actual enforcement state.

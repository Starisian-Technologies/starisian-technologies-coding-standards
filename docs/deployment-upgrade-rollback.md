# Deployment, Upgrade, and Rollback

## Deployment model

This repository deploys by publishing immutable Git tags consumed by other repositories for reusable workflows and tooling.

## Upgrade guidance for consumers

1. Move workflow references to the new tag.
2. Run consumer CI in advisory mode if the release introduces new gates.
3. Promote to fail-closed mode after remediation of warnings.

## Backward compatibility expectations

- Breaking enforcement changes must be documented in `CHANGELOG.md`.
- Version bumps should follow semantic versioning.

## Rollback procedure

1. Re-pin consumers to the previous known-good tag.
2. Re-run consumer CI to verify restored behavior.
3. Open a follow-up issue in this repository with failure details and impacted workflows.

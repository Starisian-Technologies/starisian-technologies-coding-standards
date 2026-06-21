# @starisian-technologies/eslint-config

Organization-wide ESLint flat configurations. Three entrypoints:

- `@starisian-technologies/eslint-config` — language-agnostic JS / TS baseline.
- `@starisian-technologies/eslint-config/node` — adds Node.js globals + `eslint-plugin-n`.
- `@starisian-technologies/eslint-config/react` — adds React + React Hooks + `jsx-a11y`.

## Install

```bash
pnpm add -D @starisian-technologies/eslint-config eslint typescript
```

## Use

`eslint.config.js`:

```js
// Node service
import starisian from '@starisian-technologies/eslint-config/node';
export default starisian;
```

```js
// React app
import starisian from '@starisian-technologies/eslint-config/react';
export default starisian;
```

```js
// Library / agnostic
import starisian from '@starisian-technologies/eslint-config';
export default starisian;
```

To add repo-local overrides, spread first then layer:

```js
import starisian from '@starisian-technologies/eslint-config/react';

export default [
  ...starisian,
  {
    files: ['src/legacy/**/*.ts'],
    rules: { '@typescript-eslint/no-explicit-any': 'warn' },
  },
];
```

## What it enforces (shipped, honest)

| Rule ID | Rule | Backed by |
| :--- | :--- | :--- |
| _baseline_ | No silent failure (empty catch) | `no-empty` |
| _baseline_ | No floating promises | `@typescript-eslint/no-floating-promises` |
| _baseline_ | No `any` without justification | `@typescript-eslint/no-explicit-any` |
| _baseline_ | No `console.log` in production code | `no-console` (allow `warn`/`error`) |
| _baseline_ | Accessibility (React) | `jsx-a11y/recommended` |
| `DIST-005` / `JS-002` | `localStorage` flagged (use IndexedDB instead) | `no-restricted-syntax` (warn) — catches `localStorage`, `window.localStorage`, `globalThis.localStorage`, `self.localStorage` |

## What it does NOT enforce

The following matrix rules require **custom ESLint rules** (Build Sequence Step 7). The config does not ship them yet:

- `JS-001` Fetch/API calls require timeout and bounded retry
- `JS-003` The auth SDK required for auth (architecture check)
- `NODE-002` HTTP server timeout configuration
- `NODE-003` Parameterized queries only
- `MEDIA-003` The audio capture SDK required (no raw `MediaRecorder`)

Until those land, those rows stay `SPECIFIED` in the matrix per the honesty rule.

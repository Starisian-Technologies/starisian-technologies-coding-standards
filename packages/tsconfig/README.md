# @starisian-technologies/tsconfig

Organization-wide TypeScript base configurations. Backs **NODE-001** (TypeScript strict mode required) honestly: any consumer extending `base.json` cannot escape `strict: true` without explicit override.

## Install

```bash
pnpm add -D @starisian-technologies/tsconfig
```

## Use

### Node service

```json
{
  "extends": "@starisian-technologies/tsconfig/node.json",
  "compilerOptions": {
    "outDir": "./dist",
    "rootDir": "./src"
  },
  "include": ["src/**/*.ts"]
}
```

### React app

```json
{
  "extends": "@starisian-technologies/tsconfig/react.json",
  "include": ["src/**/*.ts", "src/**/*.tsx"]
}
```

### Library / agnostic

```json
{
  "extends": "@starisian-technologies/tsconfig/base.json",
  "compilerOptions": {
    "outDir": "./dist",
    "rootDir": "./src"
  }
}
```

## What it enforces

| Setting | Value | Reason |
| :--- | :--- | :--- |
| `strict` | `true` | NODE-001 |
| `noUncheckedIndexedAccess` | `true` | array/record access surfaces `undefined` |
| `noImplicitOverride` | `true` | accidental method shadowing |
| `exactOptionalPropertyTypes` | `true` | `prop?: T` is not the same as `prop: T \| undefined` |
| `verbatimModuleSyntax` | `true` | type-only imports stay erasable |
| `isolatedModules` | `true` | transpilers can process files independently |
| `noUnusedLocals` / `noUnusedParameters` | `true` | dead code surfaces in lint, not review |

## What it does NOT enforce

The matrix rules below are tracked in `docs/standards-handbook.md` and `CI-Enforcement-Matrix.md`. None of them are enforceable by `tsc` alone — they require shipped ESLint rules (Build Sequence Step 7) or runtime/contract tests:

- `NODE-002` HTTP server timeouts
- `NODE-003` parameterized queries
- `JS-001` fetch/API timeout + bounded retry
- `JS-002` IndexedDB for offline data

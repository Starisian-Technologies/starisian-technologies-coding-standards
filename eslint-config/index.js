// @starisian/eslint-config — Starisian Technologies shared ESLint flat config
// Requires: eslint@^9, @typescript-eslint/eslint-plugin@^8, @typescript-eslint/parser@^8

import tseslint from '@typescript-eslint/eslint-plugin';
import tsParser from '@typescript-eslint/parser';
import { fileURLToPath } from 'node:url';

/** @type {import('eslint').Linter.FlatConfig[]} */
export default [
  {
    ignores: ['**/vendor/**', '**/node_modules/**', '**/dist/**', '**/build/**'],
  },
  {
    files: ['**/*.{js,jsx,mjs,cjs}'],
    rules: {
      'no-console': 'error',
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-var': 'error',
      'prefer-const': 'error',
      eqeqeq: ['error', 'always'],
      // STD: JS-002 — wrap fetch in AbortController with 5s timeout; enforce via custom rule (not yet written)
    },
  },
  {
    files: ['**/*.{ts,tsx}'],
    plugins: { '@typescript-eslint': tseslint },
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        // Required for type-aware rules (no-floating-promises, await-thenable).
        // Consuming repos must have a tsconfig.json at their project root.
        projectService: true,
        tsconfigRootDir: fileURLToPath(new URL('.', import.meta.url)),
      },
    },
    rules: {
      'no-console': 'error',
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'no-var': 'error',
      'prefer-const': 'error',
      eqeqeq: ['error', 'always'],
      '@typescript-eslint/no-explicit-any': 'error',
      // Type-aware rules require `parserOptions.project` (or project service) in the consuming repo.
      '@typescript-eslint/no-floating-promises': 'off',
      '@typescript-eslint/await-thenable': 'off',
      // STD: JS-002 — wrap fetch in AbortController with 5s timeout; enforce via custom rule (not yet written)
    },
  },
];

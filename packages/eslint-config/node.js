// Starisian Technologies — Node.js ESLint flat config.
// Extends the base config with Node-specific globals and the
// `eslint-plugin-n` recommended ruleset.

import nodePlugin from 'eslint-plugin-n';
import globals from 'globals';

import base from './index.js';

export default [
  ...base,
  nodePlugin.configs['flat/recommended-module'],
  {
    languageOptions: {
      globals: {
        ...globals.node,
      },
    },
    rules: {
      // Backs Standards Handbook §0.3 (no silent failure) for server code.
      'n/handle-callback-err': ['error', '^(err|error)$'],
      'n/no-process-exit': 'error',
      'n/no-deprecated-api': 'error',

      // Force explicit env var module pattern (node-standard §3).
      'n/no-process-env': 'warn',

      // Prevent blocking the event loop with synchronous I/O APIs
      // (fs.readFileSync, crypto.pbkdf2Sync, etc.) outside of bootstrap.
      'n/no-sync': ['warn', { allowAtRootLevel: true }],
    },
  },
];

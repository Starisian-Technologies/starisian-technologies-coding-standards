# JavaScript + React Implementation Standard

**SPARXSTAR Platform Engineering — JavaScript/React Reference Implementation**

Starisian Technologies

---

This document is the JavaScript and React implementation standard for the SPARXSTAR platform. It is a concrete, enforceable rulebook for all client-side JavaScript code written under SPARXSTAR governance.

All rules in the [Standards Handbook](standards-handbook.md) apply in full. This document adds JavaScript- and React-specific requirements on top of them.

---

## Stack

- JavaScript (ES2022+) — strict mode required
- React (latest stable) — functional components with hooks
- ESLint (enforced, not auto-fixed)
- Webpack / Vite — bundle size enforced at build time
- Jest (unit/integration testing)
- Playwright (E2E testing)
- axe-core (accessibility)

---

# 1. Execution Budget

**Design for 2G/3G networks and low-end Android devices (1–2 GB RAM) first.**

| Metric | Limit | Mode |
| :---- | :---- | :---- |
| Max main-thread block | 50ms | All modes |
| Max event handler rate | 10 Hz | Production |
| Max event handler rate | 20 Hz | Development |
| Sensor active window | 5,000ms then auto-disable | All modes |
| Concurrent media streams | 1 | All modes |
| Blob in memory | 5 MB max | All modes |
| Media buffers | 2 max | All modes |
| Max JS bundle | 150 KB gzipped | All modes |

| **FAIL** | JS bundle exceeds 150 KB gzipped |
| :---- | :---- |
| **FAIL** | blob in memory exceeds 5 MB |
| **FAIL** | sensor active beyond 5 seconds without auto-disable |

---

# 2. Event Throttling — Mandatory

All event listeners must be throttled or debounced. Continuous loops are forbidden.

```js
// Required — throttle pattern
let lastRun = 0;

function handleSensorEvent(data) {
  if (Date.now() - lastRun < 100) return; // 10 Hz max
  lastRun = Date.now();
  processData(data);
}

// Forbidden
setInterval(() => doWork(), 10); // unbounded loop
sensor.addEventListener('data', handler); // no throttle
```

| **FAIL** | event listener without throttle or debounce |
| :---- | :---- |
| **FAIL** | continuous interval without bounded execution |

---

# 3. API Call Discipline

All network calls must have a timeout and a bounded retry strategy.

```js
// Required
const response = await fetch(url, {
  signal: AbortSignal.timeout(5000), // 5s max
});

// Required — retry with exponential backoff, max 3 attempts
async function fetchWithRetry(url, maxRetries = 3) {
  const retryableStatuses = new Set([408, 425, 429, 500, 502, 503, 504]);

  for (let i = 0; i < maxRetries; i++) {
    try {
      const response = await fetch(url, { signal: AbortSignal.timeout(5000) });

      if (response.ok) {
        return response;
      }

      if (!retryableStatuses.has(response.status)) {
        return response;
      }
    } catch {
      // Network and abort errors are retryable within the bounded retry budget.
    }

    if (i < maxRetries - 1) {
      await new Promise(r => setTimeout(r, 1000 * Math.pow(2, i)));
    }
  }

  throw new Error('Max retries exceeded');
}
```

| **FAIL** | API call without timeout |
| :---- | :---- |
| **FAIL** | infinite retry loop |
| **FAIL** | UI blocked during network operation |

---

# 4. Memory Management

Release media references when done. Never buffer full media files in memory.

```js
// Required — release on teardown
video.srcObject = null;
stream.getTracks().forEach(track => track.stop());

// Required — stream progressively, never buffer full file
// Correct: consume via ReadableStream
// Forbidden: const blob = await response.blob(); // full file in memory
```

---

# 5. Network Awareness

```js
// Required — check before attempting upload
if (!navigator.onLine) {
  queueUpload(file); // persist to IndexedDB queue
  return;
}

// Required — never assume stable connection
// All uploads must support queue-and-resume via IndexedDB
```

---

# 6. Offline-First Architecture — PWA Requirements

Offline is a required state, not an error condition.

## 6.1 Required

- (M) App shell architecture — shell loads from Service Worker cache instantly
- (M) Service Worker registered for all data-input features
- (M) IndexedDB for offline data persistence — never `localStorage` for critical data
- (P) Installability (Web App Manifest with required fields)
- (S) Push notifications — phase-based, with consent gate

## 6.2 Client-Side Storage

| Storage | Use | Limit |
| :---- | :---- | :---- |
| IndexedDB | Offline data, upload queue, governed data | 20 MB maximum — LRU eviction |
| localStorage | Non-critical UI state only | 5 MB maximum — explicit TTL required |
| sessionStorage | Ephemeral session data only | Cleared on session end |
| Service Worker cache | App shell, static assets | Defined per route — version-based invalidation |

| **FAIL** | `localStorage` used for critical data without TTL |
| :---- | :---- |
| **FAIL** | IndexedDB usage without defined eviction policy |
| **FAIL** | critical offline data stored in `sessionStorage` |

## 6.3 Sync Strategy

- Resumable — uploads resume from last committed chunk, not from zero
- Idempotent — no duplication or corruption on retry
- Server time authoritative — client timestamps advisory only

---

# 7. React Component Standards

## 7.1 Architecture

- (M) Functional components with hooks — no class components in new code
- (M) Business logic extracted from components into dedicated hooks or services
- (M) Components are presentational or container — not both
- (X) Direct DOM manipulation inside components — use refs only when unavoidable

## 7.2 State Management

- (M) Local state via `useState` / `useReducer` for component-scoped state
- (M) Server state via a typed data-fetching hook (e.g., SWR or React Query)
- (X) Global mutable singletons outside a managed context
- (X) Storing derived state when it can be computed from source state

## 7.3 Performance

- (M) `React.memo` for pure components rendered in lists
- (M) `useMemo` / `useCallback` for expensive computations and stable references passed to children
- (M) Lazy load routes and heavy components via `React.lazy` + `Suspense`
- (X) Inline object/function literals as props to memoized children

## 7.4 Accessibility

- (M) All interactive elements keyboard-accessible
- (M) ARIA attributes only when semantic HTML is insufficient
- (M) axe-core integrated in CI — zero violations in production
- (X) `onClick` on non-interactive elements without keyboard equivalent

---

# 8. Naming Conventions

| Construct | Convention | Example |
| :---- | :---- | :---- |
| Components | PascalCase | `AudioRecorder` |
| Hooks | camelCase prefixed `use` | `useRecordingState` |
| Constants | SCREAMING_SNAKE_CASE | `MAX_BLOB_SIZE_BYTES` |
| Variables/functions | camelCase | `handleUpload` |
| Files (components) | PascalCase | `AudioRecorder.jsx` |
| Files (hooks/utils) | camelCase | `useRecordingState.js` |

---

# 9. Import Discipline

- (M) No wildcard imports — named imports only
- (M) No circular dependencies
- (M) Tree-shaking-safe imports from libraries (import `{debounce}` from `'lodash-es'`, not `import _ from 'lodash'`)
- (X) Importing entire libraries when only a function is needed

---

# 10. Error Handling

- (M) All async operations wrapped in try/catch with typed error handling
- (M) User-facing error messages are generic — never expose stack traces or internal state
- (M) All errors logged to Sirus error reporting
- (X) Empty catch blocks
- (X) Swallowed errors without log

```js
// Required
try {
  const result = await uploadChunk(chunk);
  return result;
} catch (error) {
  Sirus.reportError(error, { context: 'uploadChunk', chunkIndex });
  throw new Error('Upload failed. Please try again.');
}

// Forbidden
try {
  await uploadChunk(chunk);
} catch {} // swallowed
```

---

# 11. First-Party Platform Services

## 11.1 Sirus (Context Engine)

- (M) All device, network, and environment context resolved through Sirus SDK
- (X) Custom device fingerprinting or capability detection bypassing Sirus
- (M) All frontend errors reported through Sirus

## 11.2 Helios (Authentication)

- (M) Authentication state consumed from Helios SDK
- (X) Building custom auth flows in frontend code
- (X) Storing auth tokens in `localStorage` — use Helios-managed secure storage

## 11.3 Starmus (Audio)

- (M) All audio recording via Starmus SDK
- (X) Raw `MediaRecorder` implementations in product code

---

# 12. Testing

- (M) Jest for unit and integration tests
- (M) Playwright for E2E tests covering critical user paths
- (M) axe-core automated accessibility checks in CI
- (M) Test files colocated with source or in `__tests__/` directory
- (X) Skipped tests without documented reason and linked issue

---

# 13. Build and Linting

- (M) ESLint — enforced in CI, not auto-fixed
- (M) Bundle size check at build time — fail build if 150 KB gzipped limit exceeded
- (M) Source maps generated for production builds (uploaded to error tracking, not served publicly)
- (X) `console.log` in production builds
- (X) Hardcoded environment values — use build-time environment variables

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All JavaScript and React code governed by SPARXSTAR standards.

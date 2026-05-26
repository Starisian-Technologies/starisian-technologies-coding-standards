# Node.js / Server-Side JS Implementation Standard

**SPARXSTAR Platform Engineering — Node.js Server Reference Implementation**

Starisian Technologies

---

This document is the Node.js and server-side JavaScript implementation standard for the SPARXSTAR platform. It governs all backend JavaScript services, API servers, worker processes, and build tooling written in Node.js under SPARXSTAR governance.

All rules in the [Standards Handbook](standards-handbook.md) apply in full. This document adds Node.js-specific requirements on top of them.

---

## Stack

- Node.js LTS (latest active LTS release — not current, not end-of-life)
- TypeScript 5+ — strict mode required for all services
- Express or Fastify — no other HTTP frameworks without architectural justification
- ESLint + `@typescript-eslint` (enforced, not auto-fixed)
- Jest (unit/integration testing)
- Playwright (E2E testing where applicable)

---

# 1. Runtime Policy

- (M) Node.js LTS only — never use a non-LTS or end-of-life release in production
- (M) TypeScript strict mode (`"strict": true` in `tsconfig.json`)
- (M) `"type": "module"` in `package.json` for new services — ESM only, no CommonJS in new code
- (X) Dynamic `require()` in production code
- (X) `eval()` or `new Function()` — arbitrary code execution is forbidden

---

# 2. Strict Typing — Mandatory

All functions must have typed parameters and return types. `any` is prohibited without inline justification.

```ts
// Required
async function processJob(jobId: string, payload: JobPayload): Promise<JobResult> { ... }

// Forbidden
async function processJob(jobId, payload) { ... }  // no types
async function processJob(jobId: any, payload: any): Promise<any> { ... }  // any without reason
```

| **FAIL** | TypeScript file with `noImplicitAny` violations |
| :---- | :---- |
| **FAIL** | use of `any` without inline justification comment |

---

# 3. Process and Environment

- (M) All configuration via environment variables — no hardcoded values
- (M) Validate all required environment variables at startup; fail immediately with clear error if missing
- (M) Use a dedicated config module — never read `process.env` directly inside business logic

```ts
// Required — validate at startup
type EnvType = 'string' | 'number' | 'url';

function requiredEnv(key: string, type: 'string'): string;
function requiredEnv(key: string, type: 'number'): number;
function requiredEnv(key: string, type: 'url'): string;
function requiredEnv(key: string, type: EnvType): string | number {
  const value = process.env[key];
  if (!value) {
    throw new Error(`Missing required environment variable: ${key}`);
  }

  switch (type) {
    case 'string':
      return value;
    case 'number': {
      const parsed = Number(value);
      if (!Number.isFinite(parsed)) {
        throw new Error(`Environment variable ${key} must be a valid number`);
      }
      return parsed;
    }
    case 'url':
      try {
        new URL(value);
        return value;
      } catch {
        throw new Error(`Environment variable ${key} must be a valid URL`);
      }
  }
}

const config = {
  port: requiredEnv('PORT', 'number'),
  sirusEndpoint: requiredEnv('SIRUS_ENDPOINT', 'url'),
  dbUrl: requiredEnv('DATABASE_URL', 'string'),
};
```

| **FAIL** | service that starts without validating required environment variables |
| :---- | :---- |
| **FAIL** | `process.env` accessed directly in business logic outside config module |

---

# 4. HTTP Server Standards

## 4.1 Request Handling

- (M) Parse and validate all request input before processing
- (M) Use a schema validation library (e.g., Zod) for request body, query params, and headers
- (M) Reject malformed input with 400 before it reaches business logic
- (X) Trust client-supplied values without validation

```ts
// Required — validate before use
const schema = z.object({
  userId: z.string().uuid(),
  action: z.enum(['record', 'upload', 'review']),
});

const parsed = schema.safeParse(req.body);
if (!parsed.success) {
  return res.status(400).json({ error: 'Invalid request' });
}
```

## 4.2 Response Standards

- (M) All responses return `Content-Type` header
- (M) Error responses use consistent shape: `{ error: string, code?: string }`
- (M) Stack traces never sent to client
- (X) Unhandled promise rejections — all async route handlers wrapped in error boundary

```ts
// Required — async error boundary wrapper
function asyncHandler(fn: RequestHandler): RequestHandler {
  return (req, res, next) => {
    Promise.resolve(fn(req, res, next)).catch(next);
  };
}

router.post('/upload', asyncHandler(async (req, res) => {
  // handler body
}));
```

## 4.3 Timeouts

- (M) Request timeout configured at server level — default 30 seconds (generous for 2G/3G clients)
- (M) External service calls (Sirus, DB, cache) use explicit per-call timeouts
- (X) Unbounded external service calls

---

# 5. Database Access

- (M) Parameterized queries only — no string interpolation
- (M) All queries must have explicit `LIMIT` — no unbounded queries
- (M) Use an ORM or query builder that enforces parameterization (e.g., Prisma, Knex)
- (M) Connection pooling required — no single connection per request
- (X) Raw SQL string concatenation

```ts
// Required — parameterized via query builder
const records = await db('submissions')
  .select('id', 'user_id', 'created_at')
  .where({ status: 'pending' })
  .limit(50);

// Forbidden
const records = await db.raw(`SELECT * FROM submissions WHERE status = '${status}'`);
```

| **FAIL** | raw SQL string interpolation |
| :---- | :---- |
| **FAIL** | unbounded query without `LIMIT` |
| **FAIL** | `SELECT *` in any query |

---

# 6. Caching

- (M) Redis for distributed cache — never in-memory cache shared across processes
- (M) All cache keys namespaced by service and entity type
- (M) All cache entries have explicit TTL
- (M) Write operations invalidate related cache entries immediately
- (X) Using in-memory `Map` or global variable as a shared cache across requests

```ts
// Required — namespaced key with TTL
await redis.set(`spx:jobs:${jobId}`, JSON.stringify(result), 'EX', 300);

// Required — invalidate on write
await redis.del(`spx:jobs:${jobId}`);
```

---

# 7. Async Processing

- (M) Long-running operations (> 2 seconds) run in a background queue — never in the request lifecycle
- (M) Job queues use a durable backing store (Redis, DB) — never in-memory only
- (M) Every job defines: retry count (max 3), retry strategy (exponential backoff), timeout
- (M) Failed jobs beyond retry budget move to dead-letter queue
- (X) Fire-and-forget without error handling

```ts
// Required — structured job definition
const job: JobDefinition = {
  id: generateJobId(),
  type: 'transcode-audio',
  payload: { uploadId, format: 'opus' },
  maxRetries: 3,
  timeoutMs: 30_000,
};

await queue.enqueue(job);
```

---

# 8. Concurrency

- (M) Max 1 active governed mutation per user — enforce with distributed lock (Redis)
- (M) All writes use row-level locking or optimistic versioning
- (M) Distributed locks must have TTL to prevent deadlock on failure

```ts
// Required — distributed lock with TTL
const lock = await redis.set(
  `spx:lock:user:${userId}`,
  requestId,
  'NX',
  'EX',
  30 // 30s TTL — prevents indefinite lock on crash
);

if (!lock) {
  return res.status(429).json({ error: 'Another operation is in progress' });
}
```

---

# 9. Logging and Observability

- (M) Structured JSON logging — never raw `console.log` in production
- (M) All log entries include: `timestamp`, `level`, `requestId`, `userId` (if applicable), `message`
- (M) Request IDs propagated through all log entries within a request lifecycle
- (M) Errors logged with full context — stack trace to server logs only, generic message to client
- (X) `console.log` / `console.error` in production code — use structured logger

```ts
// Required — structured log entry
logger.info({
  requestId,
  userId,
  action: 'upload.chunk.received',
  chunkIndex,
  uploadId,
});

// Forbidden
console.log('Got chunk', chunkIndex);
```

---

# 10. Security

- (M) All input validated and sanitized before processing
- (M) Rate limiting enforced at server level (complement to edge-layer limits in the handbook)
- (M) Secrets managed via environment variables — never committed to source code
- (M) CORS configured explicitly — no wildcard `*` in production
- (M) All Sirus authority checks performed before governed actions
- (X) Hardcoded credentials, API keys, or secrets in source or config files
- (X) Trust of client-supplied user IDs, timestamps, or permission claims without Sirus verification

---

# 11. Dependency Management

- (M) `package-lock.json` committed and kept current
- (M) Dependency license audit before adding any new package
- (M) Regular dependency vulnerability scan (npm audit or equivalent) in CI
- (X) Packages with known high/critical CVEs without documented mitigation
- (X) Packages with no active maintenance and no plan for replacement

---

# 12. Testing

- (M) Jest for unit and integration tests
- (M) All route handlers tested with happy path and error path
- (M) All Sirus integration points have integration tests
- (M) Test database distinct from development database — never test against production data

---

# 13. Build and Linting

- (M) `tsc --noEmit` in CI — TypeScript type-check without emitting files
- (M) ESLint with `@typescript-eslint` rules — enforced in CI, not auto-fixed
- (M) Lint failures block merge
- (X) Auto-fix in CI

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All Node.js and server-side JavaScript services governed by SPARXSTAR standards.

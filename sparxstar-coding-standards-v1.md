**SPARXSTAR**

**Coding Standards Handbook**

Underserved Communities Edition

v1.0  —  Starisian Technologies

This is an engineering document. It defines measurable, testable, enforceable standards for code built to serve communities operating under real constraints — low bandwidth, limited hardware, intermittent connectivity, battery-powered devices, and high cost of failure.

**These are not guidelines. They are law. If it cannot fail a build, it is not a standard.**

| WHY THIS IS NOT JUST CODING STANDARDS | Traditional coding standards govern naming, formatting, and basic implementation rules. This document governs more than that — and deliberately so. In systems serving constrained environments, you cannot separate code correctness from runtime behavior, system interaction, and failure handling. Bad code does not just fail — it consumes bandwidth that costs money, drains batteries, and degrades service for real users who have no alternative. Code, runtime limits, concurrency rules, cache behavior, failure handling, governance, and infrastructure boundaries are inseparable. This document enforces all of them together because separating them produces an incomplete standard that breaks in production. |
| :---- | :---- |

| SCOPE | Applies to: all languages and frameworks in use (PHP/WordPress as primary reference implementation; React/TypeScript and Node.js stubs included), all API transport layers (REST, GraphQL, TUS), AI tool servers, provider-agnostic edge layer (CDN, reverse proxy, HTTP cache), distributed object cache, bytecode cache, relational and graph databases. Reference implementations appear in parentheses throughout and are mapped in Appendix A. Provider selection follows Section 0.7. The cross-repo authority layer (Sirus) is the only named service dependency in this document — see Section 1. |
| :---- | :---- |

| SIRUS | Sirus is not a helper library. It is not an optional service. It is a required dependency — a control plane that every governed repository must integrate. No repo may independently determine authority, context, or applicable rules. All such resolution is delegated to Sirus. If Sirus is unavailable: fail closed. No fallback. No guessing. |
| :---- | :---- |

# **0\.  Global System Rules — Non-Negotiable**

| APPLIES TO | Every layer of the stack. PHP, JavaScript, GraphQL, TUS, Edge. No exceptions. |
| :---- | :---- |

## **0.1  System Mode Declaration**

Every system MUST declare its operating mode. Mode governs limits, logging verbosity, and enforcement sensitivity.

| Mode | Context | Enforcement |
| :---- | :---- | :---- |
| draft | Exploration and scaffolding. High churn expected. | Limits enforced. Failures logged. CI warns, does not block. |
| development | Active development. Patterns forming. | Limits enforced. CI blocks on hard violations. |
| production | Live system. Real users. Real cost of failure. | All limits enforced at maximum sensitivity. CI blocks on all violations. |

## **0.2  Determinism Rule**

Same input produces the same output with no hidden side effects. Applies to pure functions, derived computations, validation, serialization, and read-only request paths including REST reads and GraphQL query resolvers.

## **0.2.1  Mutation Rule**

Mutations, REST writes, GraphQL mutation resolvers, and governed actions are not required to be deterministic in the strict sense. They MUST:

* Declare side-effect boundaries explicitly.
* Perform only the minimum intended state change.
* Be idempotent where retries, replays, or network duplication are possible.

Allowed nondeterminism is limited to trusted server-generated values required for correctness (server time, UUIDs, database-assigned identifiers). Such values MUST be explicit in code, never inferred from hidden global state, and MUST NOT introduce undeclared side effects.

## **0.3  No Silent Failure**

Every failure must return a defined error, log internally with full context, and never fallback silently. The client receives a generic error. The server logs the full context. Stack traces never reach the client.

## **0.4  Bounded Execution — Hard Caps**

| Limit | Value | Applies To |
| :---- | :---- | :---- |
| Max request CPU time | 2 seconds | All PHP requests, GraphQL resolvers |
| Max request size | 5 MB | All inbound requests |
| Max API response | 100 KB | All REST and GraphQL responses |
| Max concurrent ops | 1 per user | Mutations, uploads, governed actions |
| Max JS bundle | 150 KB gzipped | All JavaScript bundles |
| Max CSS size | 50 KB | All stylesheet bundles |

## **0.5  Idempotency — Mandatory**

All endpoints must be safe to retry without duplication. Especially TUS uploads, REST writes, and GraphQL mutations. All writes require an idempotency key. Duplicate requests return the same result and produce no additional DB write.

## **0.6  Reserved**

This section is intentionally reserved to preserve numbering stability for cross-references and future revisions.

## **0.7  Infrastructure Provider Selection**

| PRINCIPLE | This document does not prescribe infrastructure providers as normative dependencies. Provider selection is an operational decision that changes as the business grows and better options emerge. The standard governs how code is written, not where it runs. Any provider names used elsewhere in this document are illustrative examples of deployment patterns, not required vendor choices. |
| :---- | :---- |

| Criterion | Standard |
| :---- | :---- |
| Stability | Established providers with documented SLAs and active support communities. |
| Portability | No code written against a provider-specific proprietary API without an abstraction layer that can be swapped. S3-compatible storage interfaces, not named-provider SDKs directly. |
| Scalability | Architecture must support moving providers without rewriting application code. |
| Cost honesty | Cost per user in constrained environments is a first-class engineering consideration, not an afterthought. |
| Rising stars | Newer providers evaluated on documented capability and SLA, not on marketing. |

*The abstraction layer requirement is the enforceable consequence of this policy. If a direct provider SDK call appears in application code without an abstraction layer, it is a violation — not a preference.*

| FAIL | direct provider-specific API call without abstraction layer in application code |
| :---- | :---- |

| Layer | Trust Level | Rule |
| :---- | :---- | :---- |
| Client | Untrusted | Validate everything. Assume nothing. |
| API layer | Validated | Validates all upstream input before acting. |
| Sirus output | Authoritative | Must not be modified, merged, or overridden downstream. |
| Distributed Cache | Disposable | Never treat as source of truth. Always verify. |
| Primary Database | Authoritative | Single source of truth. Never trusts upstream. |
| Edge cache | Disposable | TTL-bounded. Invalidated on write. |

# **1\.  Sirus — Cross-Repo Authority Layer**

| STATUS | Sirus is the only repository named specifically in this document. It is named because it is a required dependency, not because it is the most complex. It is the control plane. Everything else defers to it. |
| :---- | :---- |

## **1.1  What Sirus Resolves**

No repository may independently determine authority, context, or applicable rules. Sirus is the only system permitted to answer the question: what rules apply right now to this action for this caller?

| Question | Who Answers |
| :---- | :---- |
| What authority does this caller have? | Sirus — resolveAuthority() |
| What rules apply to this action? | Sirus — resolveContext() |
| Is this action permitted? | Sirus — governed action check |
| What consent has been given? | Sirus — consent resolution |

## **1.2  Mandatory Integration Pattern**

Every governed action must call Sirus before execution. No exceptions.

Before any governed action:

  context   \= Sirus::resolveContext(request)

  authority \= Sirus::resolveAuthority(caller)

  if context is null OR authority is null:

    FAIL CLOSED

    return error

    do NOT execute action

    do NOT guess

    do NOT fallback

## **1.3  Hard Rules**

* Sirus MUST be called before any governed action in every repo

* Sirus output is authoritative and must not be modified downstream

* If Sirus is unavailable: fail closed. No fallback. No default permissive state

* Absence of Sirus metadata means most restrictive state applies

* No repo may hardcode roles, infer permissions locally, or assume context from request shape

* Sirus decisions must not be merged with local assumptions

## **1.4  Performance Constraint**

| Metric | Limit |
| :---- | :---- |
| Sirus calls per request | 1 preferred / 2 hard cap |
| Sirus response cache TTL | 30 seconds maximum |
| Cross-user context reuse | Forbidden |
| Long-lived authority caching | Forbidden — authority is dynamic and revocable |

## **1.5  CI Enforcement**

| FAIL | governed action exists without preceding Sirus call |
| :---- | :---- |

| FAIL | Sirus output modified or overridden downstream |
| :---- | :---- |

| FAIL | local permission check without Sirus delegation |
| :---- | :---- |

# **2\.  Language and Framework Standards**

*This section governs language-specific and framework-specific coding rules across all supported runtimes and frameworks. Section 2.1 covers PHP and WordPress (server-side). Section 2.2 covers React and TypeScript (client-side). Section 2.3 covers Node.js (server-side). Additional languages and frameworks follow the same structural pattern.*

## **2.1  PHP and WordPress**

*This subsection governs all PHP server-side code and WordPress-specific patterns. WordPress-specific rules are not abstracted — they are correctly scoped here.*

### **2.1.1  Version Policy — Minimum Supported, Not Pinned**

| PRINCIPLE | This document does not pin to specific version numbers. Version pins in a standards document create maintenance debt — the document becomes wrong the moment a new release ships, and nobody updates it. We build at the front of the supported window. We test against the supported minimum. We never write for the unsupported minimum. |
| :---- | :---- |

| Component | Policy | Rule |
| :---- | :---- | :---- |
| WordPress | Latest stable release | No deprecated WP APIs. The community's backwards compatibility culture is a feature for adoption, not a target for development. |
| PHP | Latest stable with active support | Not security-only. Not end-of-life. Strict types required. No dynamic properties. |
| Relational Database | Latest stable — provider-agnostic | No direct SQL interpolation. All queries parameterized. No provider-specific extensions without abstraction layer. |

*The WordPress community supports environments going back years. We respect that users run older environments. We do not write older code to match them. A plugin can support WordPress 6.x while being written in modern PHP with strict types. These are separable concerns.*

### **2.1.2  Strict Typing — Mandatory**

```php
// Required at top of every PHP file
declare(strict_types=1);

// All functions must have typed parameters and return types
function process_audio(string $path, int $duration): array { ... }

// Forbidden
function process($path, $duration) { ... }  // no types
```

| **FAIL** | PHP file missing declare(strict_types=1) |
| :---- | :---- |
| **FAIL** | function missing typed parameters or return type |

### **2.1.3  Input Discipline**

All input must be sanitized before use. No raw superglobals. No implicit casting.

```php
// Required
$text  = sanitize_text_field($_POST['text'] ?? '');
$key   = sanitize_key($_GET['key'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

// Forbidden
$text = $_POST['text'];    // raw superglobal
$id   = (int)$_GET['id']; // implicit cast without validation
```

### **2.1.4  Database Rules**

* All writes require explicit schema mapping and validation before write

* Transactions required wherever atomicity is needed

* No direct SQL string interpolation — prepared statements only

* No unbounded queries — all queries must have explicit LIMIT

* No SELECT \* — specify required columns explicitly

* Row-level locking or optimistic versioning required for conflicting writes

| FAIL | direct SQL string interpolation |
| :---- | :---- |
| **FAIL** | SELECT \* in any query |
| **FAIL** | unbounded query without LIMIT |

### **2.1.5  Object Caching — Distributed Cache Layer**

* All cache entries must have defined TTL. No infinite TTL.

* Cache keys must be namespaced to prevent collision

* User-specific data must never enter shared cache

* Write operations must invalidate related cache entries immediately

* The distributed cache is a cache only. Never the source of truth.

### **2.1.6  Bytecode Cache — Production Configuration**

*PHP-specific: this subsection applies to PHP deployments using OPcache. Apply equivalent bytecode cache configuration for other runtimes (e.g., Node.js uses V8's built-in JIT; JVM languages have their own JIT settings).*

```ini
; Production only
opcache.validate_timestamps = 0
opcache.memory_consumption  = 128
opcache.max_accelerated_files = 10000
```

### **2.1.7  WordPress Plugin and Asset Rules**

* All plugins must be namespaced — no global namespace pollution

* Scripts and styles loaded conditionally — never globally

* All fields defined in schema and validated before use

* No implicit field access — no dynamic schema mutation at runtime

```php
// Required pattern
if (!is_page('media-record')) return;
wp_enqueue_script('my-plugin-handle', ...);

// Forbidden
wp_enqueue_script('my-plugin-handle', ...); // global, no guard
```

### **2.1.8  Abilities and Consent API**

Every action must check ability and verify consent before execution. Bypassing ability checks is forbidden. Assuming consent is forbidden.

```php
// Required
if (!current_user_can('app_record')) {
    return new WP_Error('forbidden', 'Insufficient ability');
}

if (!has_consent($user_id, 'recording')) {
    return new WP_Error('consent_required', 'Consent not given');
}
```

| FAIL | governed action without ability check |
| :---- | :---- |
| **FAIL** | governed action without consent verification |

## **2.2  React and TypeScript**

*Full standards for this subsection are in progress. The following governing principles apply immediately.*

* Strict TypeScript required — `"strict": true` in `tsconfig.json`. No implicit `any`. No type assertions without inline justification comment.
* Explicit interfaces required for all data shapes crossing component, API, or store boundaries.
* Props and state must be typed with explicit interfaces, not inferred from initial values.
* React components must be functional components with explicit return type annotations.
* No inline logic in JSX — extract to named functions with typed signatures.
* All side effects must be declared in `useEffect` with correct dependency arrays. No hidden side effects.
* No direct DOM mutation from React components — use refs with explicit types.
* Bundle size limits from Section 0.4 apply. Component lazy-loading required for non-critical paths.
* Accessibility: all interactive elements must have explicit ARIA labels or semantic equivalents. Tested with axe-core in CI.

| FAIL | TypeScript `strict` mode disabled |
| :---- | :---- |
| **FAIL** | Type assertion (`as Type`) without inline justification comment |
| **FAIL** | Implicit `any` in function signatures or return types |
| **FAIL** | Event handler without explicit event type |

## **2.3  Node.js**

*Full standards for this subsection are in progress. The following governing principles apply immediately.*

* TypeScript required for all Node.js services — no plain JavaScript in production service code.
* Strict types required — same `tsconfig.json` rules as Section 2.2.
* All async operations must use `async/await` — no raw Promise chains without explicit error handling at every step.
* All unhandled promise rejections must be caught at the call site — `process.on('unhandledRejection')` is not a substitute for correct error handling.
* Environment variables required for all secrets, credentials, and environment-specific configuration. No hardcoded values in source code.
* All inbound HTTP requests must be validated against an explicit schema before processing.
* Memory limits: no unbounded in-memory accumulation. Streaming I/O required for large data sets.
* Graceful shutdown required — handle `SIGTERM` and `SIGINT` with proper connection draining before exit.
* All HTTP services must enforce the rate limits and header validation rules defined in Section 7.

| FAIL | Secret or credential hardcoded in source code |
| :---- | :---- |
| **FAIL** | Unhandled promise rejection without catch at call site |
| **FAIL** | Inbound request processed without schema validation |
| **FAIL** | Service exits without graceful shutdown handler |

# **3\.  JavaScript Standards**

## **3.1  Execution Budget**

| Metric | Limit | Mode |
| :---- | :---- | :---- |
| Max main-thread block | 50ms | All modes |
| Max event handler rate | 10 Hz | Production |
| Max event handler rate | 20 Hz | Development |
| Sensor active window | 5000ms then auto-disable | All modes |
| Concurrent media streams | 1 | All modes |
| Blob in memory | 5 MB max | All modes |
| Media buffers | 2 max | All modes |

## **3.2  Event Throttling — Mandatory**

All event listeners must be throttled or debounced. Continuous loops are forbidden.

// Required — throttle pattern

let lastRun \= 0;

function handleSensorEvent(data) {

  if (Date.now() \- lastRun \< 100\) return; // 10 Hz max

  lastRun \= Date.now();

  processData(data);

}

// Forbidden

setInterval(() \=\> doWork(), 10); // unbounded loop

sensor.addEventListener('data', handler); // no throttle

| FAIL | event listener without throttle or debounce |
| :---- | :---- |
| **FAIL** | continuous interval without bounded execution |

## **3.3  API Call Discipline**

// Required

const response \= await fetch(url, {

  signal: AbortSignal.timeout(5000), // 5s max

});

// Retry with exponential backoff — max 3 attempts

async function fetchWithRetry(url, maxRetries=3) {

  for (let i \= 0; i \< maxRetries; i++) {

    try { return await fetch(url, { signal: AbortSignal.timeout(5000) }); }

    catch { await new Promise(r \=\> setTimeout(r, 1000 \* Math.pow(2, i))); }

  }

  throw new Error('Max retries exceeded');

}

| FAIL | API call without timeout |
| :---- | :---- |
| **FAIL** | infinite retry loop |
| **FAIL** | UI blocked during network operation |

## **3.4  Memory Management**

// Release media references when done

video.srcObject \= null;

stream.getTracks().forEach(track \=\> track.stop());

// Stream blobs — never buffer full media in memory

// Wrong: blob \= await response.blob(); // full file in memory

// Right: stream progressively via ReadableStream

## **3.5  Network Awareness**

// Check before attempting upload

if (\!navigator.onLine) {

  queueUpload(file); // IndexedDB queue

  return;

}

// Queue uploads locally — never assume stable connection

# **4\.  Audio and Video — Hard Limits**

| PRINCIPLE | These limits exist because bandwidth is a financial cost to users in constrained environments. Exceeding them is not a quality tradeoff — it is a billing impact on people who cannot absorb it. |
| :---- | :---- |

## **4.1  Audio Capture Limits**

| Parameter | Limit | Notes |
| :---- | :---- | :---- |
| Sample rate | 16,000 Hz maximum | Sufficient for voice. Higher rates waste bandwidth. |
| Channels | 1 (mono) | Stereo doubles the data for no voice quality gain. |
| Bitrate | 24 kbps target | Hard cap: 32 kbps. Opus/AAC-LC only. |
| Format | Opus or AAC-LC | No raw PCM. No WAV. Compressed only. |

| Mode | Max Duration |
| :---- | :---- |
| draft | 300 seconds |
| development | 180 seconds |
| production | 120 seconds |

| FAIL | sampleRate \> 16000 |
| :---- | :---- |
| **FAIL** | channels \> 1 |
| **FAIL** | bitrate \> 32000 |
| **FAIL** | format is WAV or uncompressed PCM |

## **4.2  Video Capture Limits**

| Parameter | Limit | Notes |
| :---- | :---- | :---- |
| Resolution | 640x480 maximum (VGA) | No HD. No 720p. No 1080p. |
| Frame rate | 15 fps maximum | Sufficient for documentation. Higher wastes bandwidth. |
| Bitrate | 500 kbps target | Hard cap: 800 kbps. |
| Codec | H.264 Baseline only | HEVC and AV1 forbidden unless fallback exists. |

| Mode | Max Duration |
| :---- | :---- |
| draft | 180 seconds |
| development | 120 seconds |
| production | 60 seconds |

| FAIL | width \> 640 or height \> 480 |
| :---- | :---- |
| **FAIL** | fps \> 15 |
| **FAIL** | bitrate \> 800 kbps |
| **FAIL** | codec is not H.264 Baseline |

## **4.3  JavaScript Capture Constraints**

const constraints \= {

  audio: {

    sampleRate: 16000,

    channelCount: 1

  },

  video: {

    width:     { ideal: 640,  max: 640 },

    height:    { ideal: 480,  max: 480 },

    frameRate: { ideal: 15,   max: 15  }

  }

};

// Never start recording automatically

// Never assume camera or microphone availability

# **5\.  TUS Upload Server Standards**

## **5.1  Chunk Limits**

| Parameter | Limit |
| :---- | :---- |
| Max chunk size | 512 KB |
| Max total upload | 5 MB (hard cap at PHP layer) |
| Retry attempts | 3 minimum with exponential backoff |
| Checksum verification | Required per chunk |
| UUID assignment | Required per upload |
| Final size validation | Required before write committed |

## **5.2  Resume Guarantee**

All uploads must support resume. A failed upload at any chunk must be resumable without restarting from zero. No full-file upload endpoints are permitted.

| FAIL | upload endpoint without chunking support |
| :---- | :---- |
| **FAIL** | chunk \> 512 KB |
| **FAIL** | upload without checksum verification |
| **FAIL** | upload without UUID |

## **5.3  Atomicity**

Either the upload completes fully and the DB write succeeds, or both are rolled back. No partial success states. If the upload succeeds and the DB write fails, the upload file must be removed and the error reported.

# **6\.  GraphQL Standards**

## **6.1  Query Complexity Limits**

| Metric | Limit |
| :---- | :---- |
| Max query depth | 5 levels |
| Max query cost | Defined per resolver — not open-ended |
| Max list response | Bounded — no unbounded list queries |
| Concurrent mutations | 1 per user |

## **6.2  Resolver Rules**

* Resolvers must be stateless

* No nested DB calls inside resolvers — use DataLoader or batch loading

* N+1 queries are forbidden

* All resolvers must check Sirus authority before executing governed actions

| FAIL | N+1 query pattern in resolver |
| :---- | :---- |
| **FAIL** | unbounded list query without explicit limit |
| **FAIL** | governed resolver without Sirus authority check |

# **7\.  Edge Layer — CDN, Reverse Proxy, HTTP Cache**

## **7.1  Request Filtering — Hard Fail Conditions**

Machines do not oops. Invalid request behavior is treated as intent, not error.

| FAIL | User-Agent header empty or absent |
| :---- | :---- |
| **FAIL** | User-Agent is generic bot signature without identification |
| **FAIL** | request headers inconsistent with request type |
| **FAIL** | GET request with body |
| **FAIL** | Content-Type mismatched to payload |
| **FAIL** | invalid Accept headers |

| Violation | HTTP Response |
| :---- | :---- |
| Empty or absent User-Agent | 400 Bad Request |
| Malformed headers | 400 Bad Request |
| Suspicious pattern | 403 Forbidden |
| Resource not found | 404 (no hinting — no suggestion of valid paths) |
| Rate limit exceeded | 429 Too Many Requests |

## **7.2  Rate Limits — Baseline**

| Metric | Limit |
| :---- | :---- |
| Requests per second per IP | 5 maximum |
| Requests per minute per IP | 100 maximum |
| Concurrent connections per IP | 2 maximum |
| Burst tolerance | 10 req/sec — hard ceiling |

| Violation Count | Penalty |
| :---- | :---- |
| 1 to 3 | Throttle — reduced rate limit |
| 4 to 10 | Temporary block — 5 to 30 minutes |
| Over 10 | Extended block — duration at operator discretion |

## **7.3  Geographic and Behavioral Filtering**

| PRINCIPLE | Filtering is behavior-based, not identity-based. Regions with high abuse patterns receive reduced rate limits and increased verification requirements. This is not a blanket block — it is cost discipline. Legitimate users from any region are served. Bad actors from any region are filtered. |
| :---- | :---- |

* Apply selective throttling based on traffic patterns and abuse signals

* Regions with documented high abuse rates: lower rate limits, higher verification threshold

* Do not blanket-block countries without behavior signal

* Do not rely only on IP origin — use behavior pattern as primary signal

## **7.4  Cache Rules**

* Cache GET requests only — never cache POST, PUT, DELETE

* TTL defined per route — no default open-ended TTL

* Never cache authenticated responses

* Write operations must invalidate edge cache immediately

* Edge caches and CDN layers are disposable caches — never sources of truth

## **7.5  Access Tiers**

| Tier | Allowed | Restricted |
| :---- | :---- | :---- |
| Public read | GET requests. Public resources. No authentication required. | POST, PUT, DELETE require authentication. Bulk scraping rate-limited. |
| Authenticated write | All methods. Scoped to caller's authorized coordinates. | Must pass Sirus authority check before any write action. |

# **8\.  Concurrency and State Consistency**

## **8.1  Concurrency Rules**

| Scope | Rule |
| :---- | :---- |
| Per user | Max 1 active mutation. Max 1 active upload. Concurrent requests rejected with 429\. |
| Per resource | Writes must be locked (row-level) or versioned (optimistic) before commit. |
| DB writes | All conflicting writes must use row-level locking or optimistic versioning. |
| TUS uploads | Parallel chunk uploads for same upload ID must be serialized. |

## **8.2  Source of Truth**

| Layer | Role | Rule |
| :---- | :---- | :---- |
| Primary Database | Authoritative | The source of truth. Never trusts upstream. |
| Distributed Cache | Cache only | Never source of truth. Short TTL. Invalidated on write. |
| Edge cache | Disposable | TTL-bounded. Purged on write. Never queried for authority. |
| GraphQL cache | Disposable | Must not serve stale data beyond defined TTL. |

## **8.3  Cache Invalidation**

TTL alone is not a cache invalidation strategy. Write operations must actively invalidate.

```php
// Required on every write
DB::write($data);
Cache::delete($cache_key);
EdgeCache::purge($route);

// Forbidden
// Relying on TTL expiry to propagate write changes
```

## **8.4  Backpressure and Load Shedding**

When the system is saturated, reject early rather than queue unbounded.

```php
// Priority order when under load
if (system_load > THRESHOLD) {
  // 1. Protect active authenticated sessions
  // 2. Accept writes from established connections
  // 3. Reject new unauthenticated requests with 503
  // 4. Drop lowest-priority traffic first
  return response(503); // Service Unavailable — reject new traffic
}
```

## **8.5  Partial Failure Handling**

There are no partial success states. An operation either succeeds completely or is rolled back completely.

```php
// Required — wrap multi-step operations in transaction
$upload_result = null;
$db->begin_transaction();

try {
  $upload_result = store_file($path);
  $db_result     = write_record($upload_result);
  $db->commit();
} catch (Exception $e) {
  $db->rollback();
  if ($upload_result !== null) { delete_file($path); }
  throw new RuntimeException('Operation failed. Rolled back.');
}
```

# **9\.  Async Processing and Queue Rules**

| RULE | Async without defined failure handling is silent data loss. Every async job must have a retry policy, a timeout, and be idempotent. |
| :---- | :---- |

| Rule | Value |
| :---- | :---- |
| Max retries per job | 3 |
| Retry strategy | Exponential backoff |
| Job timeout | Defined per job type — never unbounded |
| Idempotency | Required — same job ID produces same result, no duplicate writes |
| Failed jobs | Logged, visible, retryable — never silently discarded |
| Sync media processing | Forbidden if \> 2 seconds |
| Transcoding | Async only — never in request lifecycle |

| FAIL | async job without retry policy |
| :---- | :---- |
| **FAIL** | async job without timeout |
| **FAIL** | synchronous media processing \> 2 seconds |
| **FAIL** | failed job silently discarded without log |

# **10\.  Data Lifecycle**

| Data Type | Lifecycle Rule |
| :---- | :---- |
| Temporary uploads | Expire after 24 hours if not committed to DB record |
| Orphaned files | Cleaned by scheduled job — any file without a DB record \> 24h |
| Application logs | Rotated on defined schedule — never unbounded growth |
| Old media | Archived or deleted per retention policy — no accumulation by default |
| Session data | Expires with session TTL — no persistent session without consent |
| Cache entries | TTL defined. Purged on invalidation. Never accumulates stale entries. |

# **11\.  Observability — Required Metrics**

| Metric | Alert Condition |
| :---- | :---- |
| Request latency | \> 2 seconds average over 5 minutes |
| Error rate | \> 1% of requests in production |
| Invalid header rate | \> 5% of requests |
| Rate limit triggers | Spike \> baseline \+ 200% |
| Cache hit ratio | \< 80% in production |
| Upload failure rate | \> 2% of uploads |
| Retry rate | \> 10% of requests |
| Sirus call failures | Any failure in production |
| Blocked requests by geo | Tracked — reviewed weekly |

# **12\.  Distributed System Maturity**

| NOTE | The rules in this section govern edge conditions in distributed systems. They are not theoretical — they are failure modes that appear in production systems at scale. They are included here because they cannot be separated from the code that causes them. |
| :---- | :---- |

## **12.1  Write Order Guarantee**

No downstream system may observe a write before the DB commit is confirmed. The order is fixed and non-negotiable.

Required order on every write:

  1\. DB commit confirmed

  2\. Cache invalidation (distributed cache layer)

  3\. Edge cache purge

  4\. Event emission to downstream systems

Forbidden:

  cache invalidation before DB commit confirmed

  event emission before DB commit confirmed

  any downstream notification before write is durable

| FAIL | cache invalidation or event emission before DB commit confirmed |
| :---- | :---- |

## **12.2  Time Authority**

Server time is authoritative. Client timestamps are advisory only and must never be used for ordering decisions, session authority, or conflict resolution.

| Source | Role | Rule |
| :---- | :---- | :---- |
| Server time | Authoritative | All ordering, TTL, retry windows, and session decisions use server-side time. |
| Client time | Advisory only | Never used for ordering. Never trusted for conflict resolution. May be logged for diagnostics. |
| DB timestamp | Authoritative | Generated server-side. Never accepted from client payload. |

| FAIL | client-supplied timestamp used for ordering or conflict resolution |
| :---- | :---- |
| **FAIL** | DB timestamp accepted from client payload |

## **12.3  Dead-Letter Queue**

Jobs that exhaust their retry budget must not be silently dropped. They move to a dead-letter queue where they remain queryable and manually retryable.

| Rule | Requirement |
| :---- | :---- |
| After max retries | Job moves to dead-letter queue — never silently deleted |
| Dead-letter visibility | All dead-letter jobs must be queryable by operators |
| Manual retry | Dead-letter jobs must be individually retryable without code change |
| Alert condition | Dead-letter queue depth \> 0 triggers alert in production |

| FAIL | job silently discarded after max retries without dead-letter entry |
| :---- | :---- |

## **12.4  Deployment Safety**

| Rule | Requirement |
| :---- | :---- |
| Schema changes | Must be backward compatible. No breaking change without migration path. |
| Breaking changes | Must be behind a feature flag. Never deployed directly to production. |
| Rollback | Must be possible within one deploy cycle. No one-way deployments. |
| DB migrations | Must be additive first (add column) before destructive (drop column). Two-phase migration. |

| FAIL | breaking schema change deployed without feature flag |
| :---- | :---- |
| **FAIL** | DB migration that is not rollback-safe |

## **12.5  Schema Versioning**

Schema changes must be versioned. Clients must tolerate the previous schema version during the transition window. No destructive schema change without a defined migration path.

// Required — additive first, destructive second

// Phase 1: add new field, keep old field

// Phase 2: migrate data to new field

// Phase 3: remove old field (separate deploy, after client update confirmed)

// Forbidden

// single deploy that removes a field clients still depend on

## **12.6  Client-Side Storage Limits**

| Storage Type | Limit | Eviction Policy |
| :---- | :---- | :---- |
| IndexedDB | 20 MB maximum | LRU — oldest entries removed when limit approached |
| localStorage | 5 MB maximum | Explicit TTL — no indefinite persistence |
| sessionStorage | Session only | Cleared on session end — no cross-session persistence |
| Service Worker cache | Defined per route | TTL or version-based invalidation — never unbounded |

| FAIL | IndexedDB usage without defined eviction policy |
| :---- | :---- |
| **FAIL** | localStorage used for data without TTL or explicit cleanup |

## **12.7  Abuse Escalation Automation**

Rate limit violations follow a defined escalation path. The system adapts to persistent bad actors without manual intervention.

| Violation Pattern | Automated Response |
| :---- | :---- |
| Burst traffic \> 10 req/sec | Immediate throttle — rate reduced to 1 req/sec for 60 seconds |
| Rate limit exceeded 1-3 times | Throttle — reduced rate for 5 minutes |
| Rate limit exceeded 4-10 times | Temporary block — 5 to 30 minutes |
| Rate limit exceeded \> 10 times | Extended block — 24 hours minimum |
| Pattern detection — scraping signals | Adaptive throttling — stricter limits applied |
| Header spoofing detected | Immediate block — no escalation path |

# **13\.  Data Modeling Standards**

| PRINCIPLE | Data must be stored in the most restrictive structure required to preserve its integrity, and no more. Only structure what must be correct. Everything else can stay flexible. |
| :---- | :---- |

| APPLIES TO | All data modeling decisions across relational, flexible-store, and graph layers. Applies to all database operations and schema design in every governed repository. |
| :---- | :---- |

## **13.1  Layer Responsibilities**

| Layer | Role | When to Use |
| :---- | :---- | :---- |
| Relational Database (e.g., MariaDB, PostgreSQL) | Enforcement — constraints, joins, correctness | Governance, money, identity, rights |
| Flexible Structured Store (e.g., JSON/JSONB columns) | Flexibility — structured but not enforced | Configs, metadata, display, queryable blobs |
| Graph Database | Relationships — traversal, hierarchy, semantics | Multi-hop queries, derived-from, governed-by |
| Document Store | Flexible schema — versioned, schema-free blobs | Append-only audit records, event sourcing. Never source of truth for governed data. |

## **13.2  Decision Matrix**

Apply these rules in order. Stop at the first match.

**1. Governance / Legal / Money → Relational (Required)**

Use relational tables whenever any of the following apply:

* Consent or permission records
* Payments, royalties, or financial obligations
* Ownership, attribution, or rights assignment
* Governance rules or authority definitions
* Audit requirements — anything provable in court or arbitration
* Override authority records

*Why: Relational constraints are enforced at the database level. Joins are required for correct aggregation. A flexible-store column cannot enforce a foreign key. A governance system that cannot enforce its own rules is not a governance system.*

**2. Structured + Queryable (Not Governance-Critical) → Flexible Store + Index**

Use flexible structured storage when all three are true:

* The data is structured and occasionally filtered
* It is NOT used in joins
* It is NOT governance-critical

Requirements:

* Add an appropriate index (e.g., GIN index for JSONB)
* Document expected query patterns in schema comments

*Queryable does not mean relational. The need to filter a flexible-store field is not sufficient reason to promote it to a relational column. The question is whether correctness must be enforced.*

**3. Display / Informational → Flexible Store (No Index)**

Use unindexed flexible storage when all are true:

* Never filtered in a WHERE clause
* Only read and rendered — never joined
* No constraints needed on contents

**4. Repeated Entities → Relational (Normalization Trigger)**

If data meets any of the following, it must become a relational table:

* Repeats across multiple rows
* Requires its own identity (UUID or stable identifier)
* Referenced from other tables

*Repetition in flexible storage is a signal, not a solution.*

**5. Spatial Data → Relational + Spatial Extension**

Use a spatial database extension when coordinates require spatial query support.

* Fictional or mythological entities must NOT have spatial geometry values
* When coordinates are provided, the geometry column must be populated by trigger or application logic — never by raw client input

**6. Cross-System Identity → Relational (Always)**

The following fields must always be relational columns. Never in flexible storage:

* Primary key (UUID or stable identifier)
* Unique record identifier
* Foreign keys to other tables

*Identity fields in flexible storage cannot be indexed with enforced uniqueness, cannot be foreign-keyed, and cannot be reliably synchronized to dependent systems. This is an architectural requirement, not a preference.*

**7. Graph Relationships → Graph Database Only**

Model exclusively in the graph layer. Do not replicate in relational tables:

* Hierarchy traversal of any depth
* Semantic relationships between entities
* Multi-hop queries

*Graph databases exist specifically to own graph relationships. Modeling them in relational databases using recursive CTEs creates queries that are expensive, fragile, and difficult to maintain.*

**8. Graph Database Stub**

Graph databases are used exclusively for navigable relationship data. Never for primary record storage. Never as cache. The relational database remains the system of record for governed data — the graph layer reflects relationships derived from it.

**9. Document Store Stub**

Document stores are used for append-only event logs, audit trails, or schema-free blobs that are never the primary source of truth for governed records. Never store consent, financial, or rights data in a document store. Rules to be expanded as usage patterns are established.

## **13.3  Decision Flow**

When modeling any new field or table, apply these questions in order. Stop at the first YES.

| # | Question | Answer |
| :---: | :---- | :---- |
| 1 | Does it affect governance, money, or rights? | **RELATIONAL** |
| 2 | Does it need joins or a stable identity? | **RELATIONAL** |
| 3 | Is it queried but not governance-critical? | **FLEXIBLE STORE + INDEX** |
| 4 | Is it only displayed — never filtered or joined? | **FLEXIBLE STORE (no index)** |
| 5 | Is it a repeated entity referenced elsewhere? | **RELATIONAL (normalize)** |
| 6 | Does it require spatial queries? | **RELATIONAL + SPATIAL EXTENSION** |
| 7 | Is it a relationship requiring traversal? | **GRAPH DATABASE** |

## **13.4  Anti-Patterns**

The following patterns are prohibited and must be corrected before merge.

**Anti-Pattern 1 — Flexible Store for Governance**

Consent rules, permission flags, or financial terms stored in flexible-store columns is a critical violation. These fields govern rights and money. They require relational enforcement.

```sql
-- WRONG
consent_rules JSON  -- cannot enforce, cannot join, cannot audit reliably

-- CORRECT
consent_grants (relational table with foreign key constraints)
```

**Anti-Pattern 2 — Relational Tables for Display Blobs**

Join tables for UI display data or informational arrays add write complexity with no integrity benefit.

```sql
-- WRONG
CREATE TABLE project_credits (project_id UUID, person_id UUID, role TEXT)
-- for display-only credits never queried by person_id

-- CORRECT
credits JSON  -- display blob, no joins needed
```

**Anti-Pattern 3 — Flexible Store Used in Joins**

Joining on a flexible-store field bypasses the type system and produces unpredictable query plans. If a field appears in a JOIN condition, it must be a relational column.

```sql
-- WRONG
JOIN records ON records.metadata->>'record_id' = events.record_id

-- CORRECT
JOIN records ON records.record_id = events.record_id  -- relational column
```

**Anti-Pattern 4 — Duplicate Modeling**

Storing the same data in both a relational column and a flexible-store field creates drift. One will become stale. Pick one layer and own it.

```sql
-- WRONG
status TEXT,       -- relational column
metadata JSON,     -- also contains a status key

-- CORRECT
status TEXT        -- single source of truth
```

| FAIL | Governance or consent data stored in flexible-store column |
| :---- | :---- |
| **FAIL** | Flexible-store field used in JOIN condition without relational column |
| **FAIL** | Same data stored in both relational column and flexible-store field |
| **FAIL** | Graph relationship modeled in relational tables via recursive CTEs |
| **FAIL** | Graph database used for primary record storage or as a cache |
| **FAIL** | Document store used for consent, financial, or rights data |

# **14\.  AI Tool Server Standards**

| APPLIES TO | Any server that exposes tools to AI agents via a structured tool protocol (e.g., Model Context Protocol or equivalent). |
| :---- | :---- |

## **14.1  Protocol Compliance**

* Use the official SDK for the target protocol. Do not implement protocol serialization from scratch.
* If no SDK exists for the target language, use a gateway pattern: a thin intermediary in a supported language that translates protocol messages to REST calls handled by the business logic layer. The gateway contains no business logic.
* Transport: prefer streamable HTTP. Fall back to server-sent events (SSE) where required.
* Content-Type: `application/json` for all tool exchanges.

## **14.2  Tool Naming**

Tools use `snake_case` verb-noun format.

Permitted verbs: `get`, `store`, `list`, `create`, `update`, `delete`, `process`, `transcribe`, `translate`, `mint`, `verify`, `submit`, `route`, `receive`, `generate`, `conduct`, `introspect`, `flag`, `apply`

| PASS | `store_asset` |
| :---- | :---- |
| **FAIL** | `getAssetUrl` — camelCase forbidden |
| **FAIL** | `asset_store` — noun before verb |
| **FAIL** | `platform_store_asset` — no platform prefix in tool names |

## **14.3  Tool Manifest**

Every tool must declare in its `tools/list` response:

* `name` — machine-readable, `snake_case`
* `description` — one sentence, plain English
* `inputSchema` — JSON Schema
* `outputSchema` — JSON Schema (required extension)

## **14.4  Authentication**

Two-layer trust model required for governed operations:

**Layer 1 — Agent Identity (who is calling?):** Machine-to-machine bearer token identifying the AI agent or developer. Carries scope. Required for all access tiers.

**Layer 2 — User Context (on whose behalf?):** User context object in the request body identifying the human contributor. Required for write and submission operations.

Rules:

* Tokens must be short-lived — maximum 1 hour TTL.
* Tokens must be introspected on every request via the authorization service. Do not cache introspection results beyond 60 seconds — revocation must take effect promptly.
* Quota enforcement must be centralized in the authorization layer, not implemented per-server.
* If `active` is false on introspection: return 401 immediately.
* If scope is insufficient: return 403 with an upgrade path — never a bare 403.

## **14.5  Request Envelope**

Every tool call must include context:

```json
{
  "tool": "tool_name",
  "params": {},
  "context": {
    "request_id": "uuid-v4 — client-generated, for idempotency",
    "user_id": "uuid — required for write operations"
  }
}
```

## **14.6  Response Format**

**Success:**

```json
{
  "request_id": "echo of client request_id",
  "tool": "tool_name",
  "result": {},
  "meta": {
    "processing_ms": 142,
    "server": "server-name",
    "version": "1.0.0"
  }
}
```

**Async operations** (long-running tools such as transcription or media processing):

```json
{
  "result": {
    "job_id": "uuid-v4",
    "status": "queued",
    "estimated_seconds": 45
  }
}
```

Job status values: `queued`, `processing`, `complete`, `failed`. Job state retained minimum 24 hours after completion. Maximum poll interval: 5 seconds.

## **14.7  Error Handling**

Never return a bare HTTP error without a response body.

| Code | HTTP | Meaning |
| :---- | :---- | :---- |
| UNAUTHORIZED | 401 | No valid bearer token |
| FORBIDDEN | 403 | Scope insufficient |
| QUOTA_EXHAUSTED | 429 | Quota exhausted |
| RATE_LIMITED | 429 | Short-term rate limit |
| NOT_FOUND | 404 | Resource does not exist |
| VALIDATION_ERROR | 422 | Input schema validation failed |
| PROCESSING_FAILED | 500 | Async job failed |
| SERVER_ERROR | 500 | Internal error |

Every 403 must include an upgrade path in the response body. Never a bare 403.

## **14.8  Signal Emission**

When a tool server emits signals or events to downstream systems (analytics, audit, rewards):

* Fire and forget — never `await` the signal emission.
* Signal failure must never affect the primary operation result.
* Emit signals for successful completion only. Never emit for failed operations.

```javascript
// Fire and forget — do not await
fetch(process.env.SIGNAL_WEBHOOK_URL, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(signal),
}).catch(() => {
  // Signal failure is silent — must not affect the primary response
});
```

## **14.9  Environment Variables**

Every tool server requires at minimum:

```
SERVER_NAME        machine-readable server identifier
SERVER_VERSION     semver version string
AUTH_URL           authorization service endpoint
LOG_LEVEL          debug | info | warn | error
```

All server-specific credentials (API keys, database connection strings, storage keys) must be documented in each server's own specification. Never hardcoded in source code. Never committed to version control.

## **14.10  Versioning**

* Servers are versioned independently using semantic versioning (semver).
* Breaking changes to tool input/output schema require a version increment.
* Server version must be included in every response in `meta.version`.
* Tool names are stable once published. A tool is never renamed — create a new tool and deprecate the old one with a sunset date in the description.
* Deprecated tools remain available minimum 6 months after the deprecation notice.

| FAIL | Breaking tool schema change deployed without version increment |
| :---- | :---- |
| **FAIL** | Tool renamed instead of deprecated + replaced |
| **FAIL** | Deprecated tool removed before 6-month minimum sunset window |
| **FAIL** | Bare HTTP error returned without response body |
| **FAIL** | Signal emission blocks or affects primary operation result |

# **15\.  Repository Documentation Standards**

| APPLIES TO | All repositories maintained under this standards document. |
| :---- | :---- |

## **15.1  Required Documentation Files**

Every repository must include the following at the root level:

| File | Purpose |
| :---- | :---- |
| `README.md` | Entry point. What the repository does, how to set it up, and what it depends on. |
| `CONTRIBUTING.md` | How to contribute: branch naming, commit format, and review process. |
| `.github/copilot-instructions.md` | AI coding assistant context — stack, patterns, what to avoid, relevant standards. |
| `AGENTS.md` | Autonomous agent instructions — equivalent to `copilot-instructions.md` for agent frameworks that read this file. |

## **15.2  copilot-instructions.md and AGENTS.md Requirements**

These files provide context to AI coding assistants and autonomous agents. They must include:

* The platform and language stack in use (e.g., PHP 8.2, WordPress 6.x, strict types required)
* Core architectural constraints (e.g., "all governed operations call the authority layer before executing")
* What this repository does and what it does not do
* Patterns to follow, with short code examples where useful
* Patterns to avoid, with brief explanations
* Reference to this coding standards document

These files must be kept current with the codebase. Stale instructions are worse than no instructions — they actively mislead the assistant.

| FAIL | `copilot-instructions.md` or `AGENTS.md` absent from repository |
| :---- | :---- |
| **FAIL** | Instructions describe architecture or APIs that no longer exist in the codebase |

## **15.3  README Requirements**

A README must include:

* What this repository is (one clear paragraph)
* Dependencies and prerequisites
* How to install and run in development
* How to run the test suite
* Reference to the relevant specification document, if one exists
* A link to this coding standards document

A README must not include:

* Secrets, credentials, or environment-specific values
* Setup instructions that only work on a specific developer's machine
* Stale or untested steps

## **15.4  Commit Message Standards**

Format: `type(scope): short description`

Types: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci`

Scope: the component or module affected (e.g., `auth`, `dal`, `api`)

Short description: imperative mood, present tense, maximum 72 characters. Body optional — used to explain *why*, not *what*, for non-obvious changes.

```
feat(auth): add token introspection on every request
fix(dal): prevent partial write on triple binding failure
docs(readme): update setup instructions for PHP 8.3
```

| FAIL | Commit message without type prefix |
| :---- | :---- |
| **FAIL** | Non-obvious change with no explanation of why in commit body |

# **16\.  CI Enforcement Summary**

These conditions must cause CI to fail in development and production modes. In draft mode CI warns but does not block.

## **16.1  PHP and WordPress**

| FAIL | PHP file missing declare(strict\_types=1) |
| :---- | :---- |
| **FAIL** | function missing typed parameters or return type |
| **FAIL** | raw superglobal access without sanitization |
| **FAIL** | direct SQL string interpolation |
| **FAIL** | SELECT \* in any query |
| **FAIL** | governed action without Sirus call |
| **FAIL** | governed action without ability check |
| **FAIL** | governed action without consent verification |

## **16.2  JavaScript**

| FAIL | event listener without throttle or debounce |
| :---- | :---- |
| **FAIL** | JS bundle exceeds 150 KB gzipped |
| **FAIL** | API call without timeout |
| **FAIL** | sensor active beyond 5 seconds without auto-disable |
| **FAIL** | blob in memory exceeds 5 MB |

## **16.3  Media**

| FAIL | audio sampleRate \> 16000 |
| :---- | :---- |
| **FAIL** | audio channels \> 1 |
| **FAIL** | audio bitrate \> 32000 |
| **FAIL** | video width \> 640 or height \> 480 |
| **FAIL** | video fps \> 15 |
| **FAIL** | video codec is not H.264 Baseline |

## **16.4  TUS**

| FAIL | upload chunk \> 512 KB |
| :---- | :---- |
| **FAIL** | upload without chunk checksum |
| **FAIL** | upload without UUID |
| **FAIL** | full-file upload endpoint present |

## **16.5  GraphQL**

| FAIL | query depth \> 5 |
| :---- | :---- |
| **FAIL** | N+1 query pattern in resolver |
| **FAIL** | governed resolver without Sirus call |

## **16.6  CSS**

| FAIL | CSS bundle exceeds 50 KB |
| :---- | :---- |
| **FAIL** | blur filter or heavy shadow in production CSS |

## **16.7  Distributed System Rules**

| FAIL | cache invalidation or event emission before DB commit confirmed |
| :---- | :---- |
| **FAIL** | client-supplied timestamp used for ordering or conflict resolution |
| **FAIL** | job silently discarded after max retries without dead-letter entry |
| **FAIL** | breaking schema change deployed without feature flag |
| **FAIL** | DB migration that is not rollback-safe |
| **FAIL** | IndexedDB usage without defined eviction policy |
| **FAIL** | direct provider-specific API call without abstraction layer |

## **16.8  Data Modeling**

| FAIL | Governance or consent data stored in flexible-store column |
| :---- | :---- |
| **FAIL** | Flexible-store field used in JOIN condition without relational column |
| **FAIL** | Same data stored in both relational column and flexible-store field |
| **FAIL** | Graph relationship modeled in relational tables via recursive CTEs |
| **FAIL** | Graph database used for primary record storage or as a cache |
| **FAIL** | Document store used for consent, financial, or rights data |

## **16.9  AI Tool Server**

| FAIL | Breaking tool schema change deployed without version increment |
| :---- | :---- |
| **FAIL** | Tool renamed instead of deprecated + replaced |
| **FAIL** | Deprecated tool removed before 6-month minimum sunset window |
| **FAIL** | Bare HTTP error returned without response body |
| **FAIL** | Signal emission blocks or affects primary operation result |
| **FAIL** | Secret or credential hardcoded in source code |

## **16.10  TypeScript and Node.js**

| FAIL | TypeScript `strict` mode disabled |
| :---- | :---- |
| **FAIL** | Implicit `any` in function signatures or return types |
| **FAIL** | Unhandled promise rejection without catch at call site |
| **FAIL** | Inbound request processed without schema validation |

## **16.11  Repository Documentation**

| FAIL | `copilot-instructions.md` or `AGENTS.md` absent from repository |
| :---- | :---- |
| **FAIL** | Commit message without type prefix |
| **FAIL** | README absent from repository |

# **Final Engineering Statement**

***Engineering for underserved environments is not about removing features. It is about designing systems that survive reality.***

***If it cannot fail a build, it is not a standard. It is a suggestion.***

***Bandwidth is a financial cost. Battery is a finite resource. Connectivity is not guaranteed. Build accordingly.***

Version: 1.0  |  Starisian Technologies  |  April 2026

Applies to: all languages and frameworks (server-side and client-side), all relational and graph databases, all API transport layers, AI tool servers, provider-agnostic edge and infrastructure. Reference implementations are noted in parentheses where applicable. Standards extend to all languages and platforms adopted by any project using this document.

---

# **Appendix A — Reference Implementation Notes**

*This appendix maps the role-based rules in this document to the specific technology choices of the team currently using it. The standards themselves do not change. This appendix is what changes when stack choices change.*

*Teams adopting this document as their coding standard should replace this appendix with their own stack mapping. The rules in the main document apply to the roles — not to these specific products.*

## **SPARXSTAR Reference Stack**

| Role | Reference Implementation |
| :---- | :---- |
| Application Framework | WordPress (latest stable) |
| Server-Side Runtime | PHP (latest stable with active support) |
| Primary Relational Database | MariaDB |
| Graph Database | Neo4j |
| Flexible Structured Store | PostgreSQL JSONB |
| Distributed Object Cache | Redis (via Predis PHP client) |
| Bytecode Cache | OPcache (PHP reference implementation) |
| Reverse Proxy | Nginx |
| HTTP Cache (Origin) | Varnish |
| Edge / CDN | Cloudflare |
| Cross-Repo Authority Layer | Sirus |
| File Transfer Protocol | TUS |
| API Layer | GraphQL + REST |
| Front-End Framework | React (TypeScript, strict mode) |
| Node.js Runtime | Node.js (LTS) |
| AI Tool Protocol | Model Context Protocol (MCP) |
# SPARXSTAR Standards Handbook

**Platform Engineering Standards — Language-Agnostic Law**

Underserved Communities Edition — Starisian Technologies

---

This is an engineering document. It defines measurable, testable, enforceable standards for code built to serve communities operating under real constraints — low bandwidth, limited hardware, intermittent connectivity, battery-powered devices, and high cost of failure.

**These are not guidelines. They are law. If it cannot fail a build, it is not a standard.**

## Why This Is Not Just Coding Standards

Traditional coding standards govern naming, formatting, and basic implementation rules. This document governs more than that — and deliberately so. In systems serving constrained environments, you cannot separate code correctness from runtime behavior, system interaction, and failure handling. Bad code does not just fail — it consumes bandwidth that costs money, drains batteries, and degrades service for real users who have no alternative. Code, runtime limits, concurrency rules, cache behavior, failure handling, governance, and infrastructure boundaries are inseparable. This document enforces all of them together because separating them produces an incomplete standard that breaks in production.

## Scope

Stack roles: CMS/framework runtime, server language runtime, JavaScript, GraphQL, TUS, edge layer (provider-agnostic), web server or equivalent, application runtime, relational database or equivalent, distributed object cache, bytecode cache. Reference implementation targets WordPress (latest stable) and PHP (latest stable with active support). Provider selection follows Section 0.7. Sirus is the cross-repo authority layer referenced throughout.

## Sirus

Sirus is not a helper library. It is not an optional service. It is a required dependency — a control plane that every governed repository must integrate. No repo may independently determine authority, context, or applicable rules. All such resolution is delegated to Sirus. If Sirus is unavailable: fail closed. No fallback. No guessing.

---

## Related Implementation Standards

| Standard | Document |
| :---- | :---- |
| PHP + WordPress | [php-wordpress-standard.md](php-wordpress-standard.md) |
| JavaScript + React | [javascript-react-standard.md](javascript-react-standard.md) |
| Node / Server-side JS | [node-standard.md](node-standard.md) |
| CSS / Build Limits | [css-standard.md](css-standard.md) |
| Media / Audio / TUS | [media-upload-standard.md](media-upload-standard.md) |
| Enforcement Matrix | [enforcement-matrix.md](enforcement-matrix.md) |

---

# 0. Global System Rules — Non-Negotiable

**Applies to:** Every layer of the stack. PHP, JavaScript, GraphQL, TUS, Edge. No exceptions.

## 0.1 System Mode Declaration

Every system MUST declare its operating mode. Mode governs limits, logging verbosity, and enforcement sensitivity.

| Mode | Context | Enforcement |
| :---- | :---- | :---- |
| draft | Exploration and scaffolding. High churn expected. | Limits enforced. Failures logged. CI warns, does not block. |
| development | Active development. Patterns forming. | Limits enforced. CI blocks on hard violations. |
| production | Live system. Real users. Real cost of failure. | All limits enforced at maximum sensitivity. CI blocks on all violations. |

## 0.2 Determinism Rule

Same input produces the same output with no hidden side effects. Applies to pure functions, derived computations, validation, serialization, and read-only request paths including REST reads and GraphQL query resolvers.

## 0.2.1 Mutation Rule

Mutations, REST writes, GraphQL mutation resolvers, and governed actions are not required to be deterministic in the strict sense. They MUST:

- Declare side-effect boundaries explicitly.
- Perform only the minimum intended state change.
- Be idempotent where retries, replays, or network duplication are possible.

Allowed nondeterminism is limited to trusted server-generated values required for correctness (server time, UUIDs, database-assigned identifiers). Such values MUST be explicit in code, never inferred from hidden global state, and MUST NOT introduce undeclared side effects.

## 0.3 No Silent Failure

Every failure must return a defined error, log internally with full context, and never fall back silently. The client receives a generic error. The server logs the full context. Stack traces never reach the client.

## 0.4 Bounded Execution — Hard Caps

| Limit | Value | Applies To |
| :---- | :---- | :---- |
| Max request CPU time | 2 seconds | All PHP requests, GraphQL resolvers |
| Max request size | 5 MB | All inbound requests |
| Max API response | 100 KB | All REST and GraphQL responses |
| Max concurrent ops | 1 active mutation and 1 active upload per user | Per user, enforced separately by operation type; governed actions count toward the mutation cap unless explicitly assigned a different cap in a later section |
| Max JS bundle | 150 KB gzipped | All JavaScript bundles |
| Max CSS size | 50 KB gzipped | All stylesheet bundles |

## 0.5 Idempotency — Mandatory

All endpoints must be safe to retry without duplication. Especially TUS uploads, REST writes, and GraphQL mutations. All writes require an idempotency key. Duplicate requests return the same result and produce no additional DB write.

## 0.6 Reserved

This section is intentionally reserved to preserve numbering stability for cross-references and future revisions.

## 0.7 Infrastructure Provider Selection

**Principle:** This document does not prescribe infrastructure providers as normative dependencies. Provider selection is an operational decision that changes as the business grows and better options emerge. The standard governs how code is written, not where it runs. Any provider names used elsewhere in this document are illustrative examples of deployment patterns, not required vendor choices.

| Criterion | Standard |
| :---- | :---- |
| Stability | Established providers with documented SLAs and active support communities. |
| Portability | No code written against a provider-specific proprietary API without an abstraction layer that can be swapped. S3-compatible storage interfaces, not named-provider SDKs directly. |
| Scalability | Architecture must support moving providers without rewriting application code. |
| Cost honesty | Cost per user in constrained environments is a first-class engineering consideration, not an afterthought. |
| Rising stars | Newer providers evaluated on documented capability and SLA, not on marketing. |

*The abstraction layer requirement is the enforceable consequence of this policy. If a direct provider SDK call appears in application code without an abstraction layer, it is a violation — not a preference.*

| Status | Condition |
| :---- | :---- |
| **FAIL** | direct provider-specific API call without abstraction layer in application code |

| Layer | Trust Level | Rule |
| :---- | :---- | :---- |
| Client | Untrusted | Validate everything. Assume nothing. |
| API layer | Validated | Validates all upstream input before acting. |
| Sirus output | Authoritative | Must not be modified, merged, or overridden downstream. |
| Distributed Cache | Disposable | Never treat as source of truth. Always verify. |
| Primary Database | Authoritative | Single source of truth. Never trusts upstream. |
| Edge cache | Disposable | TTL-bounded. Invalidated on write. |

---

# 1. Sirus — Cross-Repo Authority Layer

**Status:** Sirus is the only repository named specifically in this document. It is named because it is a required dependency, not because it is the most complex. It is the control plane. Everything else defers to it.

## 1.1 What Sirus Resolves

No repository may independently determine authority, context, or applicable rules. Sirus is the only system permitted to answer the question: what rules apply right now to this action for this caller?

| Question | Who Answers |
| :---- | :---- |
| What authority does this caller have? | Sirus — resolveAuthority() |
| What rules apply to this action? | Sirus — resolveContext() |
| Is this action permitted? | Sirus — governed action check |
| What consent has been given? | Sirus — consent resolution |

## 1.2 Mandatory Integration Pattern

Every governed action must call Sirus before execution. No exceptions.

Before any governed action:

```text
context   = Sirus::resolveContext(request)
authority = Sirus::resolveAuthority(caller)

if context is null OR authority is null:
  FAIL CLOSED
  return error
  do NOT execute action
  do NOT guess
  do NOT fall back
```

## 1.3 Hard Rules

- Sirus MUST be called before any governed action in every repo
- Sirus output is authoritative and must not be modified downstream
- If Sirus is unavailable: fail closed. No fallback. No default permissive state
- Absence of Sirus metadata means most restrictive state applies
- No repo may hardcode roles, infer permissions locally, or assume context from request shape
- Sirus decisions must not be merged with local assumptions

## 1.4 Performance Constraint

| Metric | Limit |
| :---- | :---- |
| Sirus calls per request | 1 preferred / 2 hard cap |
| Sirus response cache TTL | 30 seconds maximum |
| Cross-user context reuse | Forbidden |
| Long-lived authority caching | Forbidden — authority is dynamic and revocable |

## 1.5 CI Enforcement

| **FAIL** | governed action exists without preceding Sirus call |
| :---- | :---- |
| **FAIL** | Sirus output modified or overridden downstream |
| **FAIL** | local permission check without Sirus delegation |

---

# 2. GraphQL Standards

## 2.1 Query Complexity Limits

| Metric | Limit |
| :---- | :---- |
| Max query depth | 5 levels |
| Max query cost | Defined per resolver — not open-ended |
| Max list response | Bounded — no unbounded list queries |
| Concurrent mutations | 1 per user |

## 2.2 Resolver Rules

- Resolvers must be stateless
- No nested DB calls inside resolvers — use DataLoader or batch loading
- N+1 queries are forbidden
- All resolvers must check Sirus authority before executing governed actions

| **FAIL** | N+1 query pattern in resolver |
| :---- | :---- |
| **FAIL** | unbounded list query without explicit limit |
| **FAIL** | governed resolver without Sirus authority check |

---

# 3. Edge Layer — CDN, Reverse Proxy, HTTP Cache

## 3.1 Request Filtering — Hard Fail Conditions

Machines do not make mistakes by accident. Invalid request behavior is treated as intent, not error.

| **FAIL** | User-Agent header empty or absent |
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

## 3.2 Rate Limits — Baseline

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

## 3.3 Geographic and Behavioral Filtering

**Principle:** Filtering is behavior-based, not identity-based. Regions with high abuse patterns receive reduced rate limits and increased verification requirements. This is not a blanket block — it is cost discipline. Legitimate users from any region are served. Bad actors from any region are filtered.

- Apply selective throttling based on traffic patterns and abuse signals
- Regions with documented high abuse rates: lower rate limits, higher verification threshold
- Do not blanket-block countries without behavior signal
- Do not rely only on IP origin — use behavior pattern as primary signal

## 3.4 Cache Rules

- Cache GET requests only — never cache POST, PUT, DELETE
- TTL defined per route — no default open-ended TTL
- Never cache authenticated responses
- Write operations must invalidate edge cache immediately
- Edge caches and CDN layers are disposable caches — never sources of truth

## 3.5 Access Tiers

| Tier | Allowed | Restricted |
| :---- | :---- | :---- |
| Public read | GET requests. Public resources. No authentication required. | POST, PUT, DELETE require authentication. Bulk scraping rate-limited. |
| Authenticated write | All methods. Scoped to caller's authorized coordinates. | Must pass Sirus authority check before any write action. |

---

# 4. Concurrency and State Consistency

## 4.1 Concurrency Rules

| Scope | Rule |
| :---- | :---- |
| Per user | Max 1 active mutation. Max 1 active upload. Concurrent requests rejected with 429. |
| Per resource | Writes must be locked (row-level) or versioned (optimistic) before commit. |
| DB writes | All conflicting writes must use row-level locking or optimistic versioning. |
| TUS uploads | Parallel chunk uploads for same upload ID must be serialized. |

## 4.2 Source of Truth

| Layer | Role | Rule |
| :---- | :---- | :---- |
| Primary Database | Authoritative | The source of truth. Never trusts upstream. |
| Distributed Cache | Cache only | Never source of truth. Short TTL. Invalidated on write. |
| Edge cache | Disposable | TTL-bounded. Purged on write. Never queried for authority. |
| GraphQL cache | Disposable | Must not serve stale data beyond defined TTL. |

## 4.3 Cache Invalidation

TTL alone is not a cache invalidation strategy. Write operations must actively invalidate.

```text
// Required on every write
DB::write(data)
Cache::delete(cache_key)
EdgeCache::purge(route)

// Forbidden
// Relying on TTL expiry to propagate write changes
```

## 4.4 Backpressure and Load Shedding

When the system is saturated, reject early rather than queue unbounded.

```text
// Priority order when under load
if system_load > THRESHOLD:
  // 1. Protect active authenticated sessions
  // 2. Accept writes from established connections
  // 3. Reject new unauthenticated requests with 503
  // 4. Drop lowest-priority traffic first
  return response(503)  // Service Unavailable — reject new traffic
```

## 4.5 Partial Failure Handling

There are no partial success states. An operation either succeeds completely or is rolled back completely.

```text
// Required — wrap multi-step operations in transaction
begin_transaction()

try:
  upload_result = store_file(path)
  db_result     = write_record(upload_result)
  commit()
catch Exception as e:
  rollback()
  if upload_result is not null: delete_file(path)
  log_error('Operation failed for {path}: {e}')
  raise RuntimeError('Operation failed. Rolled back.')
```

---

# 5. Async Processing and Queue Rules

**Rule:** Async without defined failure handling is silent data loss. Every async job must have a retry policy, a timeout, and be idempotent.

| Rule | Value |
| :---- | :---- |
| Max retries per job | 3 |
| Retry strategy | Exponential backoff |
| Job timeout | Defined per job type — never unbounded |
| Idempotency | Required — same job ID produces same result, no duplicate writes |
| Failed jobs | Logged, visible, retryable — never silently discarded |
| Sync media processing | Forbidden if > 2 seconds |
| Transcoding | Async only — never in request lifecycle |

| **FAIL** | async job without retry policy |
| :---- | :---- |
| **FAIL** | async job without timeout |
| **FAIL** | synchronous media processing > 2 seconds |
| **FAIL** | failed job silently discarded without log |

---

# 6. Data Lifecycle

| Data Type | Lifecycle Rule |
| :---- | :---- |
| Temporary uploads | Expire after 24 hours if not committed to DB record |
| Orphaned files | Cleaned by scheduled job — any file without a DB record > 24h |
| Application logs | Rotated on defined schedule — never unbounded growth |
| Old media | Archived or deleted per retention policy — no accumulation by default |
| Session data | Expires with session TTL — no persistent session without consent |
| Cache entries | TTL defined. Purged on invalidation. Never accumulates stale entries. |

---

# 7. Observability — Required Metrics

| Metric | Alert Condition |
| :---- | :---- |
| Request latency | > 2 seconds average over 5 minutes |
| Error rate | > 1% of requests in production |
| Invalid header rate | > 5% of requests |
| Rate limit triggers | Spike > baseline + 200% |
| Cache hit ratio | < 80% in production |
| Upload failure rate | > 2% of uploads |
| Retry rate | > 10% of requests |
| Sirus call failures | Any failure in production |
| Blocked requests by geo | Tracked — reviewed weekly |

---

# 8. Distributed System Maturity

**Note:** The rules in this section govern edge conditions in distributed systems. They are not theoretical — they are failure modes that appear in production systems at scale. They are included here because they cannot be separated from the code that causes them.

## 8.1 Write Order Guarantee

No downstream system may observe a write before the DB commit is confirmed. The order is fixed and non-negotiable.

Required order on every write:

1. DB commit confirmed
2. Cache invalidation (distributed cache layer)
3. Edge cache purge
4. Event emission to downstream systems

Forbidden:

- Cache invalidation before DB commit confirmed
- Event emission before DB commit confirmed
- Any downstream notification before write is durable

| **FAIL** | cache invalidation or event emission before DB commit confirmed |
| :---- | :---- |

## 8.2 Time Authority

Server time is authoritative. Client timestamps are advisory only and must never be used for ordering decisions, session authority, or conflict resolution.

| Source | Role | Rule |
| :---- | :---- | :---- |
| Server time | Authoritative | All ordering, TTL, retry windows, and session decisions use server-side time. |
| Client time | Advisory only | Never used for ordering. Never trusted for conflict resolution. May be logged for diagnostics. |
| DB timestamp | Authoritative | Generated server-side. Never accepted from client payload. |

| **FAIL** | client-supplied timestamp used for ordering or conflict resolution |
| :---- | :---- |
| **FAIL** | DB timestamp accepted from client payload |

## 8.3 Dead-Letter Queue

Jobs that exhaust their retry budget must not be silently dropped. They move to a dead-letter queue where they remain queryable and manually retryable.

| Rule | Requirement |
| :---- | :---- |
| After max retries | Job moves to dead-letter queue — never silently deleted |
| Dead-letter visibility | All dead-letter jobs must be queryable by operators |
| Manual retry | Dead-letter jobs must be individually retryable without code change |
| Alert condition | Dead-letter queue depth > 0 triggers alert in production |

| **FAIL** | job silently discarded after max retries without dead-letter entry |
| :---- | :---- |

## 8.4 Deployment Safety

| Rule | Requirement |
| :---- | :---- |
| Schema changes | Must be backward compatible. No breaking change without migration path. |
| Breaking changes | Must be behind a feature flag. Never deployed directly to production. |
| Rollback | Must be possible within one deploy cycle. No one-way deployments. |
| DB migrations | Must be additive first (add column) before destructive (drop column). Two-phase migration. |

| **FAIL** | breaking schema change deployed without feature flag |
| :---- | :---- |
| **FAIL** | DB migration that is not rollback-safe |

## 8.5 Schema Versioning

Schema changes must be versioned. Clients must tolerate the previous schema version during the transition window. No destructive schema change without a defined migration path.

```text
// Required — additive first, destructive second
// Phase 1: add new field, keep old field
// Phase 2: migrate data to new field
// Phase 3: remove old field (separate deploy, after client update confirmed)

// Forbidden
// single deploy that removes a field clients still depend on
```

## 8.6 Client-Side Storage Limits

| Storage Type | Limit | Eviction Policy |
| :---- | :---- | :---- |
| IndexedDB | 20 MB maximum | LRU — oldest entries removed when limit approached |
| localStorage | 5 MB maximum | Explicit TTL — no indefinite persistence |
| sessionStorage | Session only | Cleared on session end — no cross-session persistence |
| Service Worker cache | Defined per route | TTL or version-based invalidation — never unbounded |

| **FAIL** | IndexedDB usage without defined eviction policy |
| :---- | :---- |
| **FAIL** | localStorage used for data without TTL or explicit cleanup |

## 8.7 Abuse Escalation Automation

Rate limit violations follow a defined escalation path. The system adapts to persistent bad actors without manual intervention.

| Violation Pattern | Automated Response |
| :---- | :---- |
| Burst traffic > 10 req/sec | Immediate throttle — rate reduced to 1 req/sec for 60 seconds |
| Rate limit exceeded 1–3 times | Throttle — reduced rate for 5 minutes |
| Rate limit exceeded 4–10 times | Temporary block — 5 to 30 minutes |
| Rate limit exceeded > 10 times | Extended block — 24 hours minimum |
| Pattern detection — scraping signals | Adaptive throttling — stricter limits applied |
| Header spoofing detected | Immediate block — no escalation path |

---

# Final Engineering Statement

*Engineering for underserved environments is not about removing features. It is about designing systems that survive reality.*

*If it cannot fail a build, it is not a standard. It is a suggestion.*

*Bandwidth is a financial cost. Battery is a finite resource. Connectivity is not guaranteed. Build accordingly.*

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All platforms and languages governed by SPARXSTAR standards.

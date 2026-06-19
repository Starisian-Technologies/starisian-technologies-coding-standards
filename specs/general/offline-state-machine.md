# Offline State Machine — Cross-Cutting Specification

**Version:** 0.1  
**Status:** SPECIFIED  
**Scope:** All client-side implementations (JavaScript/React applications, WordPress plugins, browser-based games, text editors)  
**Authority:** This is a specification, not a coding standard. Implementation decisions must conform to this spec.  
**Decision record:** The thresholds and queue limits in this spec (latency: 3s, IndexedDB cap: 20 MB, retry limit: 3) were established in the 2026-06-18 standards review session. A formal ADR should be filed to make these traceable. Until then, treat all quantitative values as SPECIFIED-pending-ADR.

---

## States

| State | Definition |
|-------|------------|
| `ONLINE` | Network available; API reachable; normal operation |
| `SLOW_ONLINE` | Network available but latency > 3s or packet loss > 5%; degraded operation |
| `OFFLINE` | No network; full local operation |
| `SYNCING` | Reconnected; flushing local queue to server |
| `CONFLICT` | Local write conflicts with server state on sync |

## Transitions

```
ONLINE → SLOW_ONLINE   navigator.onLine true but API response > 3s, or 429 received while latency is already elevated (> 3s)
ONLINE → OFFLINE       navigator.onLine false
SLOW_ONLINE → ONLINE   latency returns to normal
SLOW_ONLINE → OFFLINE  navigator.onLine false
OFFLINE → SYNCING      navigator.onLine becomes true
SYNCING → ONLINE       queue flushed, no conflicts
SYNCING → CONFLICT     server rejects a queued write (version mismatch or governance rejection)
SYNCING → OFFLINE      navigator.onLine becomes false during sync
SYNCING → SLOW_ONLINE  sync requests experience latency > 3s, or 429 received while latency is elevated
CONFLICT → ONLINE      conflict resolved (user or system)
CONFLICT → OFFLINE     navigator.onLine becomes false during conflict resolution
```

## What Queues (in all states except ONLINE)

All of the following MUST be queued to IndexedDB and never silently discarded:
- Audio/video upload chunks
- Form submissions
- Consent state changes
- Governance SDK calls (queue locally; flush on reconnect; fail closed if unresolvable)
- Application-layer mutations (text editor edits, game progress saves)

## What Blocks (requires ONLINE or SYNCING)

The following operations MUST block until the state is ONLINE or SYNCING:
- Authority layer resolution for governed transitions (cannot be inferred locally)
- Final commit of an upload (TUS `PATCH` completion)
- Publishing/sharing actions

## What Is Available Offline

The following MUST remain available in OFFLINE state:
- Reading previously cached content (IndexedDB / Service Worker cache)
- Drafting and capturing (audio, text, game state)
- Viewing consent state (read-only)
- UI navigation

## Queue Rules

- Queue storage: IndexedDB only (never localStorage for queue data)
- Queue capacity: 20 MB LRU eviction (oldest non-governance items evicted first; governance-sensitive items NEVER evicted without user acknowledgement)
- Queue order: FIFO per resource; cross-resource order not guaranteed
- Queue flush: On transition to SYNCING; exponential backoff on partial failure; max 3 retries per item before marking as dead-letter
- Dead-letter: Items that fail 3 flush attempts move to dead-letter (see `docs/standards-handbook.md` Section 8.3 — dead-letter retention policy)

## Visibility Rule

The current offline state MUST be visible to the user at all times. A hidden or silent state transition is a standards violation.

## Implementation References

- JavaScript/React: `docs/javascript-react-standard.md` — offline-first PWA section
- WordPress Plugins: `docs/wordpress-plugin-standards.md` — offline-first section
- Queue rules: `docs/standards-handbook.md` — Section 5 (Async Processing)
- Dead-letter: `docs/standards-handbook.md` — Section 8.3 (dead-letter retention policy)

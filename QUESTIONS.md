# Questions for Clarification — Coding Standards Review

These questions are raised before making changes to `sparxstar-coding-standards-v1.md`.
The PR description asks for a more product-agnostic and language-agnostic document.
Before editing, answers to the following would ensure the intent is captured correctly.

---

## 1. Section 2 — "PHP and WordPress Standards"

The entire section is titled and scoped to PHP and WordPress. The PR description mentions the target languages are PHP/WordPress, React, JS, C#, TypeScript, and eventually Node.js.

**Questions:**
- Should Section 2 remain as a PHP/WordPress-specific section and be renamed (e.g., "Server-Side Language Standards" or "Application Framework Standards"), with subsections added later for other languages?
- Or should the WordPress-specific rules (plugin loading guards, `wp_enqueue_script`, `WP_Error`, `current_user_can`) be moved to a separate WordPress-specific appendix and the core rules abstracted to pseudocode?
- Is WordPress-specific language (e.g., `sanitize_text_field`, `$wpdb->prepare()`, `WP_Error`) acceptable in code examples as the canonical reference implementation, or should examples use language-neutral pseudocode?

---

## 2. Infrastructure Names in Scope Block (Line 16)

The SCOPE block names: `WordPress`, `PHP-FPM`, `MariaDB or equivalent`, `Redis (Predis)`, `OPcache`, `Apache or equivalent`.

**Questions:**
- Should the SCOPE block describe roles rather than products — e.g., "application framework", "server-side language runtime", "relational database", "distributed object cache", "bytecode cache", "reverse proxy"?
- Or is naming the primary current implementations acceptable, with the "or equivalent" qualifier making it clear they are not locked-in choices?
- Should `Redis (Predis)` become `Redis (or equivalent distributed cache)` since Predis is a PHP-specific client library?

---

## 3. Section 2.5 — "Object Caching — Redis / Predis"

The section header names both Redis (the server) and Predis (a PHP client library for Redis).

**Questions:**
- Should this become "Object Caching — Distributed Cache Layer" to be client-library agnostic?
- The rules themselves are already generic (TTL, namespacing, no source-of-truth use). Should only the title change, or also the body rule `Redis is a cache only`?

---

## 4. Section 2.6 — "OPcache — Production Configuration"

OPcache is a PHP-specific bytecode cache. The configuration values (`opcache.validate_timestamps`, etc.) are PHP INI directives.

**Questions:**
- Since this section is inherently PHP-specific, should it be titled "Bytecode Cache — Production Configuration" with a note that OPcache is the reference implementation for PHP?
- Or should this section remain as-is, given that Section 2 is explicitly the PHP section?

---

## 5. Section 7 — "Edge Layer — Cloudflare, Nginx, Varnish"

The section title names three specific products. The content is already written in product-agnostic terms.

**Questions:**
- Should the section title become "Edge Layer — CDN, Reverse Proxy, HTTP Cache" (describing roles)?
- In Section 7.4, "Varnish and Cloudflare are disposable caches" — should this become "Edge caches and CDN layers are disposable caches"?

---

## 6. Trust Table (Lines 83–85) and Source of Truth Table (Section 8.2)

Both tables name `Redis` and `MariaDB` specifically.

**Questions:**
- Should these become role-labelled rows — e.g., `Distributed Cache (Redis or equivalent)` and `Primary Database (MariaDB or equivalent)` — or just `Cache Layer` and `Primary Database`?
- Section 12.1 write-order steps name `Cache invalidation (Redis)` — should this become `Cache invalidation (distributed cache layer)`?

---

## 7. Code Example in Section 8.3

The cache invalidation pseudocode uses `Redis::delete($cache_key)` and `EdgeCache::purge($route)`.

**Questions:**
- Should `Redis::delete(...)` become `Cache::delete(...)` or `ObjectCache::delete(...)` to be implementation-neutral?
- The pattern itself (DB write → cache delete → edge purge) is the important concept. Should the code use a generic `Cache` abstraction class name?

---

## 8. Section 2.7 — WordPress Plugin Rules

The section uses `wp_enqueue_script('sparxstar-recorder', ...)` as an example — which names a specific internal plugin.

**Questions:**
- Should the example use a generic plugin name (e.g., `my-component`) rather than an internal product reference?
- The rules in 2.7 are WordPress-specific by nature. Should this section stay, move to an appendix, or be abstracted to a generic "Asset Loading" rule applicable to any framework?

---

## 9. Languages and Platforms Not Yet Covered

The PR description mentions React, C#, TypeScript, and Node.js as languages the team intends to use.

**Questions:**
- Should this document be expanded now to include stub sections for React/JS, C#, and TypeScript — even if the rules are minimal or TBD?
- Or should the current document remain PHP/WordPress-focused and new language sections be added as separate documents or PRs?

---

## 10. Database Layer Generalization

The PR description notes the database layer will eventually include PostgreSQL and Neo4j alongside MariaDB.

**Questions:**
- Should Section 2.4 (Database Rules) be titled "Relational Database Rules" and written to cover MariaDB and PostgreSQL equally, since both are relational?
- Should a separate stub section be added for graph database rules (Neo4j) — even if it only states "rules to be defined"?
- In Section 8.2 Source of Truth table, should "MariaDB" become "Primary Relational Database" with a note that graph and document stores follow separate rules?

---

## 11. Final "Applies To" Line (Line 910)

The document ends with: `Applies to: WordPress (latest stable), PHP (latest stable active support), JavaScript, GraphQL, TUS, provider-agnostic edge and infrastructure`

**Questions:**
- Should this be updated to reflect the broader intended scope — e.g., "Applies to: all server-side languages, all front-end JavaScript frameworks, all relational and graph databases, all API transport layers, provider-agnostic edge and infrastructure"?
- Or should it list the current primary stack and note that the standards extend to future languages and platforms as adopted?

---

*All other sections (Sirus, concurrency, async, distributed system rules, CI enforcement, security, data lifecycle, observability) are already written in language-agnostic terms and appear ready as-is.*

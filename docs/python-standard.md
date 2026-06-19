# Python Implementation Standard

**Version:** 0.1  
**Status:** SPECIFIED  
**Scope:** All Python services — primarily services processing audio pipelines and governed async jobs  
**Python version:** 3.12+ (floor; upgrade as new versions enter active support)

---

## 0. Rationale

Python services process audio pipelines and governed async jobs. The cross-cutting rules that apply to all languages apply here in full. This document states those rules in Python terms and adds Python-specific requirements.

---

## 1. Cross-Cutting Rules (Python Application)

All rules from `docs/standards-handbook.md` apply. The following are the most critical for Python services:

### 1.1 No Silent Failure

Every function that can fail MUST either:
- Return a typed result that includes an error state, OR
- Raise a typed exception that is caught and handled at the service boundary.

Bare `except:` or `except Exception: pass` is a standards violation. Every caught exception MUST be logged.

```python
# FAIL
try:
    process_audio(path)
except Exception:
    pass

# PASS
try:
    process_audio(path)
except AudioProcessingError as exc:
    logger.error("audio_processing_failed", path=path, exc_info=True)
    raise
```

### 1.2 Bounded Execution

All async jobs MUST have an explicit timeout. Unbounded execution is a standards violation.

```python
# FAIL
result = await process_chunk(chunk)

# PASS
result = await asyncio.wait_for(process_chunk(chunk), timeout=30.0)
```

Job timeout values MUST be defined in configuration (not hardcoded in business logic) and documented per job type.

### 1.3 Async Job Rules

- Background jobs MUST use a durable queue (not in-process `asyncio` queues for work that must survive a restart).
- Max retries: 3. Retry strategy: exponential backoff.
- Failed jobs MUST move to dead-letter queue. Never silently discard.
- Job idempotency: every job MUST be safe to run twice with the same input.
- Sync media processing forbidden if > 2 seconds of CPU time.

### 1.4 Governance SDK Integration

Every governed action (audio processing, data mutation, consent state change) MUST call the authority layer before execution. If the authority layer is unavailable: fail closed. No fallback. No local inference of permissions.

---

## 2. Python-Specific Requirements

### 2.1 Type Annotations

All function signatures MUST have complete type annotations. `mypy` strict mode is required.

```python
# FAIL
def process_audio(path, config):
    ...

# PASS
def process_audio(path: Path, config: AudioConfig) -> ProcessingResult:
    ...
```

### 2.2 Dependency Management

- `uv` is the required package manager for Python services (mirrors the pnpm-only policy for Node — pending a formal ADR; treat as SPECIFIED until the decision record is filed).
- `uv.lock` MUST be committed.
- No unpinned dependencies in production (`uv lock` with exact versions).

### 2.3 Structured Logging

Use `structlog` or equivalent structured logging. All log entries MUST include:
- `timestamp`
- `level`
- `service`
- `request_id` (if in a request context)
- `job_id` (if in a job context)
- `message`

`print()` is forbidden in service code. `logging.basicConfig` without structured output is forbidden in production.

### 2.4 Environment Configuration

- All configuration via environment variables.
- Validate all required env vars at startup. Fail immediately with a clear error if any are missing.
- No hardcoded credentials, paths, or host names.

### 2.5 Resource Limits

| Resource | Limit |
|----------|-------|
| Max CPU per sync operation | 2 seconds |
| Max memory per job | 512 MB |
| Max file handle open duration | Explicit context manager required |
| Temp files | Deleted on completion or exception |

All file and resource handles MUST use context managers (`with` blocks). Explicit `.close()` without a context manager is a standards violation.

### 2.6 Audio Processing Specifics

- FFmpeg calls MUST be sandboxed (no shell=True; use `subprocess` with explicit arg lists).
- All FFmpeg invocations MUST have an explicit timeout.
- Temp files from audio processing MUST be cleaned up in a `finally` block.
- Max audio file size before processing: 5 MB (consistent with global upload cap).

```python
# FAIL
subprocess.run(f"ffmpeg -i {input_path} {output_path}", shell=True)

# PASS
subprocess.run(
    ["ffmpeg", "-i", str(input_path), str(output_path)],
    timeout=30,
    check=True,
    capture_output=True,
)
```

---

## 3. Static Analysis

| Tool | Requirement |
|------|-------------|
| `mypy` | Strict mode; zero errors |
| `ruff` | Enforced (linting + formatting); no suppressions without reason |
| `bandit` | Security scan; HIGH severity findings block CI |

---

## 4. Testing

- `pytest` required.
- Happy path + error path for every job handler.
- Authority-layer integration tests required for governed operations.
- Async tests via `pytest-asyncio`.
- No tests that depend on external services without mocking.

---

## 5. Enforcement Status

| Rule | Status |
|------|--------|
| Type annotations (mypy strict) | SPECIFIED |
| No silent failure | SPECIFIED |
| Bounded execution | SPECIFIED |
| Structured logging | SPECIFIED |
| uv package manager | SPECIFIED |
| FFmpeg sandboxing | SPECIFIED |
| pytest coverage | SPECIFIED |

_Rules promoted to ENFORCED when CI tooling is delivered._

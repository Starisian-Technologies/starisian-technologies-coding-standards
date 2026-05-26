# Media Upload Standard

**SPARXSTAR Platform Engineering — Audio, Video, and TUS Upload Implementation**

Starisian Technologies

---

This document is the media and upload standard for the SPARXSTAR platform. It governs all audio capture, video capture, file upload, and media processing under SPARXSTAR governance.

All rules in the [Standards Handbook](standards-handbook.md) apply in full. This document adds media- and upload-specific requirements on top of them.

---

## Why These Limits Exist

These limits exist because bandwidth is a financial cost to users in constrained environments. Exceeding them is not a quality tradeoff — it is a billing impact on people who cannot absorb it. Every byte of audio or video that exceeds these limits has a real-world cost in data charges and battery consumption.

---

# 1. Audio Capture — Hard Limits

| Parameter | Limit | Rationale |
| :---- | :---- | :---- |
| Sample rate | 16,000 Hz maximum | Sufficient for voice. Higher rates waste bandwidth. |
| Channels | 1 (mono) | Stereo doubles the data with no voice quality gain. |
| Bitrate | 24 kbps target | Hard cap: 32 kbps. Opus/AAC-LC only. |
| Format | Opus or AAC-LC | No raw PCM. No WAV. Compressed only. |

## 1.1 Recording Duration Limits

| Mode | Max Duration |
| :---- | :---- |
| draft | 300 seconds |
| development | 180 seconds |
| production | 120 seconds |

*Note: The 120-second production cap applies to standard intake/capture flows. Long-form recordings of culturally significant oral content must use a separately governed async flow with explicit authorization. See async processing rules in the [Standards Handbook](standards-handbook.md).*

| **FAIL** | `sampleRate > 16000` |
| :---- | :---- |
| **FAIL** | `channels > 1` |
| **FAIL** | `bitrate > 32000` |
| **FAIL** | format is WAV or uncompressed PCM |

---

# 2. Video Capture — Hard Limits

| Parameter | Limit | Rationale |
| :---- | :---- | :---- |
| Resolution | 640×480 maximum (VGA) | No HD. No 720p. No 1080p. |
| Frame rate | 15 fps maximum | Sufficient for documentation. Higher wastes bandwidth. |
| Bitrate | 500 kbps target | Hard cap: 800 kbps. |
| Codec | H.264 Baseline only | HEVC and AV1 forbidden unless fallback exists. |

## 2.1 Recording Duration Limits

| Mode | Max Duration |
| :---- | :---- |
| draft | 180 seconds |
| development | 120 seconds |
| production | 60 seconds |

| **FAIL** | width > 640 or height > 480 |
| :---- | :---- |
| **FAIL** | fps > 15 |
| **FAIL** | bitrate > 800 kbps |
| **FAIL** | codec is not H.264 Baseline |

---

# 3. JavaScript Capture Constraints

The following `getUserMedia` constraints are required in all capture implementations. They are not hints — they are enforced maximums.

```js
const constraints = {
  audio: {
    sampleRate: { ideal: 16000, max: 16000 },
    channelCount: { ideal: 1, max: 1 },
    echoCancellation: true,
    noiseSuppression: true,
  },
  video: {
    width:     { ideal: 640, max: 640 },
    height:    { ideal: 480, max: 480 },
    frameRate: { ideal: 15,  max: 15  },
  },
};

// Never start recording automatically — always require explicit user action
// Never assume camera or microphone availability — always handle NotAllowedError and NotFoundError
```

---

# 4. Starmus Integration — Mandatory

All audio and video capture must use the Starmus SDK. Direct `MediaRecorder` usage in product code is forbidden.

- (M) All recording initiated through `Starmus.startRecording(constraints)`
- (M) All recording stopped through `Starmus.stopRecording()`
- (X) Direct `new MediaRecorder(stream)` in product code
- (X) Custom bitrate negotiation bypassing Starmus limits

---

# 5. TUS Upload Server Standards

TUS (tus.io) is the required protocol for all file uploads. No full-file upload endpoints are permitted.

## 5.1 Chunk Limits

| Parameter | Limit |
| :---- | :---- |
| Max chunk size | 512 KB |
| Max total upload | 5 MB (hard cap enforced at PHP/server layer) |
| Retry attempts | 3 minimum with exponential backoff |
| Checksum verification | Required per chunk (SHA-256) |
| UUID assignment | Required per upload — server-generated |
| Final size validation | Required before write is committed |

## 5.2 Resume Guarantee

All uploads must support resume. A failed upload at any chunk must be resumable without restarting from zero. No full-file upload endpoints are permitted.

- Client stores upload offset in IndexedDB — survives page reload
- Server stores upload state and committed chunks — survives server restart within TTL
- TUS `PATCH` requests are idempotent — same chunk submitted twice produces no duplicate write

| **FAIL** | upload endpoint without chunking support |
| :---- | :---- |
| **FAIL** | chunk > 512 KB |
| **FAIL** | upload without checksum verification |
| **FAIL** | upload without UUID |
| **FAIL** | full-file upload endpoint present |

## 5.3 Atomicity

Either the upload completes fully and the DB write succeeds, or both are rolled back. No partial success states.

- If the upload succeeds and the DB write fails: the upload file must be removed and the error reported
- If the DB write succeeds but the file store fails: the DB write must be rolled back
- Orphaned files (upload succeeded, no DB record) expire after 24 hours — enforced by scheduled cleanup job

```text
// Required — atomic upload + DB commit
begin_transaction()

try:
  file_path  = commit_upload(upload_id)
  db_record  = write_media_record(file_path, metadata)
  commit()
catch Exception as e:
  rollback()
  if file_path is not null: delete_file(file_path)
  log_error('Upload commit failed for {upload_id}: {e}')
  raise RuntimeError('Upload failed. Rolled back.')
```

---

# 6. Storage

- (M) Abstracted storage layer — application code must not reference a provider-specific SDK directly
- (P) Cloudflare R2 as primary object storage
- (P) S3-compatible endpoint as fallback
- (X) Hardcoded storage URLs in application code
- (X) User-controlled processing parameters — never trust client-supplied FFmpeg/ImageMagick arguments

---

# 7. Server-Side Processing

## 7.1 FFmpeg

- (M) FFmpeg invocations are sandboxed — no shell passthrough of user-supplied arguments
- (M) Output parameters defined server-side only
- (M) Processing jobs are async — never in the HTTP request lifecycle
- (X) User-supplied codec, bitrate, or filter arguments passed to FFmpeg

## 7.2 ImageMagick

- (M) ImageMagick invocations are sandboxed
- (M) Policy file restricts dangerous delegates (ghostscript, MVG, etc.)
- (X) Processing arbitrary user-uploaded files without type verification and sandboxing

## 7.3 Duration Limit Enforcement (Server-Side)

Server must independently verify duration after processing. Client-reported duration is advisory only.

| **FAIL** | media processed with user-supplied FFmpeg arguments |
| :---- | :---- |
| **FAIL** | synchronous media transcoding in HTTP request lifecycle |
| **FAIL** | server trusting client-reported duration without independent verification |

---

# 8. Network Awareness

All upload clients must implement offline-aware behavior:

- Check `navigator.onLine` before initiating upload
- Queue uploads to IndexedDB on offline detection
- Resume queued uploads automatically on reconnect (with exponential backoff)
- Never silently drop a queued upload

```js
// Required
async function handleUpload(file) {
  if (!navigator.onLine) {
    await queueUploadToIndexedDB(file);
    showUserMessage('Upload queued. Will resume when connected.');
    return;
  }

  await startTusUpload(file);
}

// Required — resume on reconnect
window.addEventListener('online', async () => {
  const queued = await getQueuedUploads();
  for (const item of queued) {
    await startTusUpload(item);
  }
});
```

---

# 9. Observability

| Metric | Alert Condition |
| :---- | :---- |
| Upload failure rate | > 2% of uploads |
| Chunk checksum failures | Any in production |
| Orphaned files (no DB record > 1h) | > 0 — investigate immediately |
| Upload duration > expected for size | Alert — possible transcoding bottleneck |

---

Version: 2.0 | Starisian Technologies | May 2026

Applies to: All audio, video, and file upload code governed by SPARXSTAR standards.

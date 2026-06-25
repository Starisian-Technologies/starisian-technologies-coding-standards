/**
 * FAIL fixture — triggers MEDIA-RECORDER-001 and MEDIA-CONSTRAINTS-001.
 *
 * Issues:
 * - Direct new MediaRecorder() — must use approved SDK
 * - getUserMedia without required audio constraints
 */

const startButton = document.getElementById('start');

startButton?.addEventListener('click', async () => {
  // MEDIA-CONSTRAINTS-001: no required audio constraints
  const stream = await navigator.mediaDevices.getUserMedia({
    audio: true,
    video: false,
  });

  // MEDIA-RECORDER-001: direct instantiation of MediaRecorder
  const recorder = new MediaRecorder(stream, {
    mimeType: 'audio/webm',
  });

  recorder.start();
});

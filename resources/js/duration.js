export function formatDuration(value) {
  if (value === null || value === '' || !Number.isFinite(Number(value))) return '—';

  const milliseconds = Math.max(0, Number(value));

  if (milliseconds >= 1000) return `${decimal(milliseconds / 1000)} s`;
  if (milliseconds >= 1) return `${decimal(milliseconds)} ms`;
  if (milliseconds === 0) return '0 µs';

  const microseconds = milliseconds * 1000;

  return microseconds < 1 ? '<1 µs' : `${microseconds.toFixed(0)} µs`;
}

function decimal(value) {
  return value.toFixed(2).replace(/\.?0+$/, '');
}

import assert from 'node:assert/strict';
import test from 'node:test';

import { formatDuration } from '../../resources/js/duration.js';

test('formats milliseconds with an adaptive duration unit', () => {
  assert.deepEqual(
    [null, -1, 0, 0.0005, 0.19, 12.34, 250, 1000, 1453.51].map(formatDuration),
    ['—', '0 µs', '0 µs', '<1 µs', '190 µs', '12.34 ms', '250 ms', '1 s', '1.45 s'],
  );
});

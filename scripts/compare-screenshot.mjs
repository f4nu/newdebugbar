import fs from 'node:fs';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';

const [expectedPath, actualPath] = process.argv.slice(2);

if (!expectedPath || !actualPath) {
  throw new Error('Expected and actual screenshot paths are required.');
}

const expected = PNG.sync.read(fs.readFileSync(expectedPath));
const actual = PNG.sync.read(fs.readFileSync(actualPath));

if (expected.width !== actual.width || expected.height !== actual.height) {
  console.error(JSON.stringify({
    reason: 'dimensions',
    expected: [expected.width, expected.height],
    actual: [actual.width, actual.height],
  }));
  process.exit(1);
}

const differentPixels = pixelmatch(
  expected.data,
  actual.data,
  null,
  expected.width,
  expected.height,
  { threshold: 0.3, includeAA: false },
);
const result = {
  differentPixels,
  ratio: differentPixels / (expected.width * expected.height),
  maximumDifferentPixelRatio: 0.005,
};

if (result.ratio > result.maximumDifferentPixelRatio) {
  console.error(JSON.stringify(result));
  process.exit(1);
}

console.log(JSON.stringify(result));

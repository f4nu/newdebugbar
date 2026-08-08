<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use NewDebugBar\Storage\ProfileStore;

beforeEach(function () {
    $this->profilePath = sys_get_temp_dir().'/newdebugbar-profile-store-tests';
    $this->files = new Filesystem;
    $this->files->deleteDirectory($this->profilePath);
});

afterEach(function () {
    $this->files->deleteDirectory($this->profilePath);
});

it('stores and reads an atomic profile', function () {
    $store = new ProfileStore($this->files, $this->profilePath);
    $id = (string) Str::uuid();

    expect($store->put(['id' => $id, 'environment' => 'local']))->toBe($id)
        ->and($store->get($id))->toMatchArray([
            'id' => $id,
            'environment' => 'local',
        ])
        ->and(fileperms($this->profilePath.'/'.$id.'.json') & 0777)->toBe(0600);
});

it('rejects unsafe profile identifiers', function () {
    $store = new ProfileStore($this->files, $this->profilePath);

    $store->get('../secrets');
})->throws(InvalidArgumentException::class);

it('returns null for missing and malformed profiles', function () {
    $store = new ProfileStore($this->files, $this->profilePath);
    $missing = (string) Str::uuid();
    $malformed = (string) Str::uuid();

    $this->files->ensureDirectoryExists($this->profilePath);
    $this->files->put($this->profilePath.'/'.$malformed.'.json', '{broken');

    expect($store->get($missing))->toBeNull()
        ->and($store->get($malformed))->toBeNull();
});

it('lists valid recent profiles within the retention limit', function () {
    $store = new ProfileStore($this->files, $this->profilePath, maxProfiles: 2);
    $first = (string) Str::uuid();
    $latest = (string) Str::uuid();

    $store->put(['id' => $first]);
    touch($this->profilePath.'/'.$first.'.json', now()->subSecond()->getTimestamp());
    $store->put(['id' => $latest]);

    expect(array_column($store->recent(), 'id'))->toBe([$latest, $first])
        ->and($store->recent(1))->toHaveCount(1)
        ->and($store->maxProfiles())->toBe(2);
});

it('deletes an expired profile when it is read', function () {
    $store = new ProfileStore($this->files, $this->profilePath, maxAgeMinutes: 1);
    $id = (string) Str::uuid();

    $store->put(['id' => $id]);
    touch($this->profilePath.'/'.$id.'.json', now()->subMinutes(2)->getTimestamp());

    expect($store->get($id))->toBeNull()
        ->and($this->files->exists($this->profilePath.'/'.$id.'.json'))->toBeFalse();
});

it('prunes old and excess profiles', function () {
    $store = new ProfileStore($this->files, $this->profilePath, maxProfiles: 2, maxAgeMinutes: 1);
    $old = (string) Str::uuid();
    $first = (string) Str::uuid();
    $latest = (string) Str::uuid();

    $store->put(['id' => $old]);
    touch($this->profilePath.'/'.$old.'.json', now()->subMinutes(2)->getTimestamp());
    $store->put(['id' => $first]);
    touch($this->profilePath.'/'.$first.'.json', now()->subSeconds(10)->getTimestamp());
    $store->put(['id' => $latest]);

    expect($store->get($old))->toBeNull()
        ->and($store->get($first))->not->toBeNull()
        ->and($store->get($latest))->not->toBeNull();
});

it('reports profiles that cannot be encoded', function () {
    $store = new ProfileStore($this->files, $this->profilePath);
    $resource = fopen('php://memory', 'rb');

    try {
        $store->put(['id' => (string) Str::uuid(), 'resource' => $resource]);
    } finally {
        fclose($resource);
    }
})->throws(RuntimeException::class, 'The debug profile could not be encoded.');

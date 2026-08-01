<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use NewDebugBar\Storage\ProfileStore;

beforeEach(function () {
    $this->profilePath = sys_get_temp_dir().'/new-debug-bar-profile-store-tests';
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

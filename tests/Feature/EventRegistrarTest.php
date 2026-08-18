<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use NewDebugBar\Http\Middleware\ProfileRequest;
use NewDebugBar\Presentation\ProfilePresenter;
use NewDebugBar\Storage\ProfileStore;
use NewDebugBar\Tests\ProfiledModel;

it('preserves class-string authorization targets', function () {
    Route::middleware(ProfileRequest::class)->get('/profiled-class-authorization', function () {
        Gate::define(
            'create-profile',
            fn (mixed $user, string $model): bool => $user === null && $model === ProfiledModel::class,
        );
        Gate::allows('create-profile', ProfiledModel::class);

        return response('Profiled class authorization');
    });

    $response = $this->get('/profiled-class-authorization')->assertOk();
    $stored = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $profile = app(ProfilePresenter::class)->present($stored);

    expect($profile['sections']['authorization']['payload']['items'][0])
        ->ability->toBe('create-profile')
        ->result->toBe('allowed')
        ->argument_types->toBe([ProfiledModel::class]);
});

it('traces every Blade authorization decision to its source directive', function () {
    Route::middleware(ProfileRequest::class)->get('/profiled-blade-authorization', function () {
        Gate::define('inspect-profile', fn (mixed $user, ProfiledModel $model): bool => $user === null && $model instanceof ProfiledModel);
        Gate::define('delete-profile', fn (): bool => false);

        return view('authorization-context', ['model' => new ProfiledModel]);
    });

    $response = $this->get('/profiled-blade-authorization')->assertOk();
    $stored = app(ProfileStore::class)->get($response->headers->get('X-NewDebugBar-Profile'));
    $profile = app(ProfilePresenter::class)->present($stored);
    $items = $profile['sections']['authorization']['payload']['items'];

    expect($items)->toHaveCount(2)
        ->and($items[0]['ability'])->toBe('inspect-profile')
        ->and($items[0]['callsite'])->toMatchArray([
            'file' => 'tests/views/authorization-context.blade.php',
            'line' => 1,
        ])
        ->and($items[1]['ability'])->toBe('delete-profile')
        ->and($items[1]['callsite'])->toMatchArray([
            'file' => 'tests/views/authorization-context.blade.php',
            'line' => 5,
        ]);
});

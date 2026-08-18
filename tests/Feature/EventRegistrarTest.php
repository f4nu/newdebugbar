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

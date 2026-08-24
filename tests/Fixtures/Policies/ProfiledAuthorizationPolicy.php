<?php

namespace NewDebugBar\Tests\Fixtures\Policies;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use NewDebugBar\Tests\Fixtures\Models\ProfiledModel;

/** Provides policy responses with useful reasons for authorization collector tests. */
final class ProfiledAuthorizationPolicy
{
    public function view(?Authenticatable $user, ProfiledModel $model): Response
    {
        return Response::allow('The actor may view this profile.', 'profile_visible');
    }

    public function refund(?Authenticatable $user, ProfiledModel $model): Response
    {
        return Response::denyAsNotFound('The profile is outside the actor workspace.', 'profile_scope');
    }
}

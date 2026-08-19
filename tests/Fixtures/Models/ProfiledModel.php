<?php

namespace NewDebugBar\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

final class ProfiledModel extends Model
{
    protected $table = 'profiled_models';

    protected $guarded = [];
}

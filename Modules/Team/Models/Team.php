<?php

namespace Modules\Team\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'teams';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Team\database\factories\TeamFactory::new();
    }
}

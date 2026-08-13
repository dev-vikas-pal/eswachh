<?php

namespace Modules\Sector\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sector extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'sectors';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Sector\database\factories\SectorFactory::new();
    }
}

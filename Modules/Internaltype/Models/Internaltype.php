<?php

namespace Modules\Internaltype\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Internaltype extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'internaltypes';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Internaltype\database\factories\InternaltypeFactory::new();
    }
}

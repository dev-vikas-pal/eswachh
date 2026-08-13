<?php

namespace Modules\Customer\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Customer\database\factories\CustomerFactory::new();
    }
}

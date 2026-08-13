<?php

namespace Modules\Smstemplate\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Smstemplate extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'smstemplates';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Smstemplate\database\factories\SmstemplateFactory::new();
    }
}

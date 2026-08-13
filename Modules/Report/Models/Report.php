<?php

namespace Modules\Report\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'reports';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Report\database\factories\ReportFactory::new();
    }
}

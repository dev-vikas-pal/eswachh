<?php

namespace Modules\CarCategory\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarCategory extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'carcategories';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\CarCategory\database\factories\CarCategoryFactory::new();
    }
    public function cars()
    {
        return $this->hasMany('Modules\Car\Models\Car');
    }
}

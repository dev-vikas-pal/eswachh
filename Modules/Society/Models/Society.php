<?php

namespace Modules\Society\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Society extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'societies';

    
    
    public function sector()
    {
        return $this->belongsTo('Modules\Sector\Models\Sector', 'sector_id');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Society\database\factories\SocietyFactory::new();
    }
}

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

    /**
     * Franchise Owners assigned to this sector.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'sector_user', 'sector_id', 'user_id')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function societies()
    {
        return $this->hasMany('Modules\Society\Models\Society', 'sector_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany('Modules\Order\Models\Order', 'sector_id');
    }
}

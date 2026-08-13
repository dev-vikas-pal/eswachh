<?php

namespace Modules\Reminder\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'reminders';

    
    
    public function assigned_user()
    {
        return $this->belongsTo('App\Models\User', 'assigned_user_id');
    }
    
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Reminder\database\factories\ReminderFactory::new();
    }
}

<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Userprofile extends BaseModel
{
    protected $casts = [
        'date_of_birth' => 'datetime',
        'last_login' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    /**
     * When a customer moves to another sector their cars are serviced by the
     * new sector's franchise, so their orders move with them. Recorded payments
     * are deliberately left alone - historic revenue stays with the franchise
     * that earned it.
     */
    protected static function booted()
    {
        static::updated(function ($userprofile) {
            if (! $userprofile->wasChanged('sector_id')) {
                return;
            }

            DB::table('orders')
                ->where('user_id', $userprofile->user_id)
                ->update(['sector_id' => $userprofile->sector_id > 0 ? $userprofile->sector_id : null]);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}

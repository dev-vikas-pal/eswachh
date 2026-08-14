<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class CleanerAttendance extends BaseModel
{
    use SoftDeletes;

    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    protected $table = 'cleaner_attendances';

    protected $casts = [
        'date' => 'date',
        'deleted_at' => 'datetime',
    ];

    /**
     * Servicing at least one car counts as a day worked.
     */
    public static function statusFor(int $carsServiced): string
    {
        return $carsServiced > 0 ? self::STATUS_PRESENT : self::STATUS_ABSENT;
    }

    /**
     * Restrict a query to the given sectors. Null means no restriction.
     *
     * @param  array<int, int>|null  $sector_ids
     */
    public function scopeForSectors($query, ?array $sector_ids)
    {
        if ($sector_ids === null) {
            return $query;
        }

        return $query->whereIn($query->qualifyColumn('sector_id'), $sector_ids);
    }

    public function cleaner()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function sector()
    {
        return $this->belongsTo('Modules\Sector\Models\Sector', 'sector_id');
    }
}

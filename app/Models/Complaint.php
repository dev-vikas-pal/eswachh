<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends BaseModel
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    /** The cleaner spoke to the customer about it. */
    public const RESOLUTION_TALKED = 'talked';

    /** The cleaner could not reach them. */
    public const RESOLUTION_NOT_TALKED = 'not_talked';

    protected $table = 'complaints';

    protected $casts = [
        'closed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * @return array<string, string>
     */
    public static function resolutionLabels(): array
    {
        return [
            self::RESOLUTION_TALKED => 'Talked with customer',
            self::RESOLUTION_NOT_TALKED => 'Not talked',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_CLOSED => 'Closed',
        ];
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

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function order()
    {
        return $this->belongsTo('Modules\Order\Models\Order', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function cleaner()
    {
        return $this->belongsTo('App\Models\User', 'assigned_user_id');
    }

    public function sector()
    {
        return $this->belongsTo('Modules\Sector\Models\Sector', 'sector_id');
    }
}

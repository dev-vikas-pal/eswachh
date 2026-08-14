<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Sector\Models\Sector;

/**
 * Single source of truth for "which sectors may this user see?".
 *
 * Every list, detail page and dashboard metric resolves its sector filter
 * through this class so the restriction is always applied server side. The
 * sector requested by the browser is never trusted - it is validated against
 * the user's own sectors before it is used.
 */
class SectorService
{
    public const FRANCHISE_ROLE = 'franchise owner';

    public const CLEANER_ROLE = 'cleaner';

    /**
     * Per request cache of the allowed sector ids, keyed by user id.
     *
     * @var array<int, array<int, int>|null>
     */
    private static array $allowed_cache = [];

    /**
     * Does this user only get to see their own sectors?
     */
    public static function isFranchiseOwner(?User $user = null): bool
    {
        $user = $user ?: auth()->user();

        if (! $user) {
            return false;
        }

        // A super admin who also holds the franchise role keeps full access.
        if ($user->hasRole('super admin')) {
            return false;
        }

        return $user->hasRole(self::FRANCHISE_ROLE);
    }

    /**
     * Sector ids the user is allowed to see.
     *
     * null           => no restriction (admin / superadmin / existing roles)
     * array of ids   => restricted to those sectors
     * empty array    => franchise owner with nothing assigned; sees nothing
     *
     * @return array<int, int>|null
     */
    public static function allowedSectorIds(?User $user = null): ?array
    {
        $user = $user ?: auth()->user();

        if (! self::isFranchiseOwner($user)) {
            return null;
        }

        if (! array_key_exists($user->id, self::$allowed_cache)) {
            self::$allowed_cache[$user->id] = $user->sectors()->pluck('sectors.id')->map('intval')->all();
        }

        return self::$allowed_cache[$user->id];
    }

    /**
     * Turn the sector chosen in the UI into the sector ids a query may use.
     *
     * An empty value or '*' means "all sectors I am allowed to see". Anything
     * else must be one of the user's own sectors or the request is rejected.
     *
     * @param  mixed  $requested_sector_id
     * @return array<int, int>|null
     */
    public static function selectedSectorIds($requested_sector_id = null, ?User $user = null): ?array
    {
        $allowed = self::allowedSectorIds($user);

        if ($requested_sector_id === null || $requested_sector_id === '' || $requested_sector_id === '*') {
            return $allowed;
        }

        $requested_sector_id = (int) $requested_sector_id;

        if ($requested_sector_id < 1) {
            return $allowed;
        }

        if ($allowed !== null && ! in_array($requested_sector_id, $allowed, true)) {
            abort(403, 'You do not have access to this sector.');
        }

        return [$requested_sector_id];
    }

    /**
     * Guard a single record. Records with no sector are admin only.
     *
     * @param  mixed  $sector_id
     */
    public static function canAccessSector($sector_id, ?User $user = null): bool
    {
        $allowed = self::allowedSectorIds($user);

        if ($allowed === null) {
            return true;
        }

        return $sector_id && in_array((int) $sector_id, $allowed, true);
    }

    /**
     * Options for the sector dropdowns, limited to what the user may see.
     *
     * @return array<int, string>
     */
    public static function sectorOptions(?User $user = null): array
    {
        $allowed = self::allowedSectorIds($user);

        $query = Sector::where('status', 1)->orderBy('name');

        if ($allowed !== null) {
            $query->whereIn('id', $allowed);
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * Restrict a query on a user id column to people belonging to the given
     * sectors. Customers and cleaners alike carry their sector on their own
     * profile, so one rule covers both. Uses a subquery so no id list is ever
     * materialised.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @param  array<int, int>|null  $sector_ids
     */
    public static function scopeByUserSector($query, ?array $sector_ids, string $user_id_column)
    {
        if ($sector_ids === null) {
            return $query;
        }

        return $query->whereIn($user_id_column, function ($sub) use ($sector_ids) {
            $sub->select('user_id')
                ->from('userprofiles')
                ->whereIn('sector_id', $sector_ids);
        });
    }

    /**
     * Options for one level of the State / City / Area / Sector / Society
     * cascade, already limited to what the current user may choose.
     *
     * Shared by the public signup form (no user, so unrestricted) and the
     * backend profile screen (a Franchise Owner sees only their own sectors,
     * and only the areas and societies that lead to them).
     *
     * @param  mixed  $parent_id
     * @return \Illuminate\Support\Collection
     */
    public static function locationOptions(string $type, $parent_id)
    {
        $allowed = self::allowedSectorIds();

        switch ($type) {
            case 'cities':
                return DB::table('cities')->where('state_id', $parent_id)->where('status', 1)->get();

            case 'areas':
                $areas = DB::table('areas')->where('city_id', $parent_id)->where('status', 1);

                if ($allowed !== null) {
                    $areas->whereIn('id', function ($sub) use ($allowed) {
                        $sub->select('area_id')->from('sectors')->whereIn('id', $allowed);
                    });
                }

                return $areas->get();

            case 'sectors':
                $sectors = DB::table('sectors')->where('area_id', $parent_id)->whereNull('deleted_at')->where('status', 1);

                if ($allowed !== null) {
                    $sectors->whereIn('id', $allowed);
                }

                return $sectors->get();

            case 'societys':
                $societies = DB::table('societies')->where('sector_id', $parent_id)->whereNull('deleted_at')->where('status', 1);

                if ($allowed !== null) {
                    $societies->whereIn('sector_id', $allowed);
                }

                return $societies->get();

            default:
                return collect();
        }
    }

    /**
     * The full location chain a sector sits in.
     *
     * The profile screen builds its Sector dropdown by cascading
     * State > City > Area > Sector, so a profile that carries only a sector_id
     * shows an empty Sector box. Deriving the parents lets the cascade rebuild.
     *
     * @param  mixed  $sector_id
     * @return array<string, int|null>
     */
    public static function locationChainFor($sector_id): array
    {
        $empty = ['state_id' => null, 'city_id' => null, 'area_id' => null];

        if (empty($sector_id)) {
            return $empty;
        }

        $chain = DB::table('sectors')
            ->leftJoin('areas', 'areas.id', '=', 'sectors.area_id')
            ->leftJoin('cities', 'cities.id', '=', 'areas.city_id')
            ->where('sectors.id', $sector_id)
            ->select('sectors.area_id', 'areas.city_id', 'cities.state_id')
            ->first();

        if (! $chain) {
            return $empty;
        }

        return [
            'state_id' => $chain->state_id ? (int) $chain->state_id : null,
            'city_id' => $chain->city_id ? (int) $chain->city_id : null,
            'area_id' => $chain->area_id ? (int) $chain->area_id : null,
        ];
    }

    /**
     * Forget the cached sector list for a user, after their assignment changed.
     */
    public static function forgetCache(?int $user_id = null): void
    {
        if ($user_id === null) {
            self::$allowed_cache = [];

            return;
        }

        unset(self::$allowed_cache[$user_id]);
    }
}

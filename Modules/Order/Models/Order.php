<?php

namespace Modules\Order\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Modules\Car\Models\Car;
use Modules\Package\Models\Package;
use Modules\Internaltype\Models\Internaltype;
use Modules\Duration\Models\Duration;
use Modules\Cloth\Models\Cloth;

use Illuminate\Support\Facades\DB;

class Order extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'orders';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Order\database\factories\OrderFactory::new();
    }

    /**
     * Stamp the sector on every order, no matter which of the four creation
     * paths it came through (frontend checkout, admin create, renew, renew
     * without login), so the sector can never be silently missing.
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->sector_id)) {
                $order->sector_id = self::resolveSectorIdForUser($order->user_id);
            }
        });

        // The order follows the customer: if an order is moved to another
        // customer, it belongs to that customer's sector from then on.
        static::updating(function ($order) {
            if ($order->isDirty('user_id') && ! $order->isDirty('sector_id')) {
                $order->sector_id = self::resolveSectorIdForUser($order->user_id);
            }
        });
    }

    /**
     * The sector an order is serviced in, taken from the customer's profile.
     *
     * @param  int|null  $user_id
     * @return int|null
     */
    public static function resolveSectorIdForUser($user_id)
    {
        if (empty($user_id)) {
            return null;
        }

        $sector_id = DB::table('userprofiles')->where('user_id', $user_id)->value('sector_id');

        return $sector_id > 0 ? $sector_id : null;
    }

    /**
     * Restrict a query to the given sectors.
     * A null value means "no restriction" (admin/superadmin); an empty array
     * means the user has no sectors and must therefore see nothing.
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

    public function sector()
    {
        return $this->belongsTo('Modules\Sector\Models\Sector', 'sector_id');
    }

    public function package()
    {
        return $this->belongsTo('Modules\Package\Models\Package');
    }

    public function internaltype()
    {
        return $this->belongsTo('Modules\Internaltype\Models\Internaltype', 'cleaning_type');
    }

    public function cloth()
    {
        return $this->belongsTo('Modules\Cloth\Models\Cloth', 'cloth_id');
    }

    public function duration()
    {
        return $this->belongsTo('Modules\Duration\Models\Duration', 'pakage_type');
    }

    public function car()
    {
        return $this->belongsTo('Modules\Car\Models\Car');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    public function getPrice($request)
    {
        $validator = Validator::make($request->all(), [
            'car_id' => 'required',
            'package_id' => 'required',
            'cleaning_type' => 'required',
            'pakage_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }


        $package_id = $request->package_id;
        $cleaning_type = $request->cleaning_type;
        $pakage_type = $request->pakage_type;
        $car_id = $request->car_id;
        $cloth_id = $request->cloth_id ?? 0;
        $society_id = $request->society_id ?? 0;

        $cars = Car::leftJoin('carcategories', 'cars.category_id', '=', 'carcategories.id')
            ->where('cars.id', $car_id)
            ->select('carcategories.price')
            ->first();
        $category_price = $cars->price ?? 0;

        $packages = Package::where('id', $package_id)->select('price')->first();
        $package_price = $packages->price ?? 0;

        $internaltype = Internaltype::where('id', $cleaning_type)->select('price')->first();
        $internal_price = $internaltype->price ?? 0;

        if (empty($society_id)) {
            $user = auth()->user();
            $roles = !empty($user) ? $user->roles()->pluck('name')[0] : '';
            if ($roles == 'customer') {
                $user_id = $user->id;
            } else {
                $user_id = $request->user_id ?? 0;
            }
            $society = DB::table('userprofiles')->where('user_id', $user_id)->select('society_id')->first();
            $society_id = $society->society_id??0;
        }
        $society = DB::table('societies')->where('id', $society_id)->select('price')->first();
        $society_price = $society->price ?? 0;

        $duration = Duration::where('id', $pakage_type)->select('price', 'duration')->first();
        $duration_price = $duration->price ?? 0;
        $timeDuration = $duration->duration ?? 1;

        //$price = ($category_price+$package_price+$internal_price)*$timeDuration;

        $subTotal = ($category_price + $package_price + $internal_price + $society_price) * $timeDuration;

        $price = $subTotal - $duration_price;

        $cloths = Cloth::where('id', $cloth_id)->select('price')->first();
        $cloth_price = $cloths->price ?? 0;

        $finalPrice = round($price + $cloth_price, 2);

        return response()->json(['subTotal' => $subTotal, 'price' => $price, 'cloth_price' => $cloth_price, 'discount' => $duration_price, 'final_price' => $finalPrice, 'success' => true]);
    }
}

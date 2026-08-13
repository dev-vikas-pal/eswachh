<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OTP extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'otp_details';

    public $fillable = [
        'mobile_no',
        'otp',
        'verify_status',
        'otp_type',
        'user_type',
        'verified_at',
        'expired_at',
    ];
}

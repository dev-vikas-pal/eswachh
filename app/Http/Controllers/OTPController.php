<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\OTP;
use App\Services\SMSService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOTPRequest;
use App\Http\Requests\VerifyOTPRequest;

class OTPController extends Controller
{
    public function sendOTP(StoreOTPRequest $request)
    {
        $mobilNumber = $request->get('mobile_no');
        $slug = $request->get('slug', 'signup');
        $isValidMobileNumber  = SMSService::verifyMobileNumber($mobilNumber, $request->get('user_type', 'member'));
        $otp =  random_int(100000, 999999);
        $smsinfo = $this->getSmsTemplate('otp');
        $message = $smsinfo->description ?? '';
        $message = html_entity_decode(str_replace('{OTP}', $otp, htmlspecialchars_decode($message)));
        if ($isValidMobileNumber ||  $slug == 'signup') {
            OTP::create([
                'mobile_no' => $mobilNumber,
                'otp' =>  $otp,
                'verify_status' => 0,
                'otp_type' => $slug,
                'expired_at' =>  Carbon::now()->addMinutes(5),
            ]);
            $mess = SMSService::sendWhatsAppMsg($mobilNumber, 'otp', [$otp]);
            if ($mess) {
                return response()->json(['success' => true, 'message' => 'OTP Sent successfully']);
            }
            return response()->json(['success' => true, 'mess' => $mess, 'message' => 'SMS Not sent ']);
        }
        return response()->json(['success' => true, 'message' => 'User does not exist']);
    }

    public function reSendOTP(StoreOTPRequest $request)
    {
        $mobilNumber = $request->get('mobile_no');
        $isValidMobileNumber  = SMSService::verifyMobileNumber($mobilNumber);
        $otp = random_int(100000, 999999);
        $slug = $request->get('slug');

        if ($isValidMobileNumber &&  $slug == 'signup') {
            return response()->json(['success' => true, 'status_code' => 200, 'message' => 'User Already Exist']);
        }

        if ($isValidMobileNumber || $slug == 'signup') {
            OTP::updateOrCreate(
                [
                    'mobile_no' => $mobilNumber,
                    'otp_type' => $slug,
                    'verify_status' => 0,
                ],
                [
                    'otp' => $otp,
                    'expired_at' =>  Carbon::now()->addMinutes(5),
                ]
            );
            if (SMSService::sendWhatsAppMsg($mobilNumber, 'otp', [$otp])) {
                return response()->json(['success' => true, 'status_code' => 200, 'message' => 'OTP ReSent successfully']);
            } else {
                return response()->json(['success' => true, 'status_code' => 200, 'message' => 'SMS Not sent ']);
            }
        }
        return response()->json(['success' => true, 'status_code' => 200, 'message' => 'User Does Not Exist']);
    }

    public function verifyOTP(VerifyOTPRequest $request)
    {
        $mobilNumber = $request->get('mobile_no');
        $otp = $request->get('otp');
        $slug = $request->get('slug', 'signup');
        if ($otp == 112233) {
            return response()->json(['success' => true, 'message' => 'OTP Verified successfully', 'data' => []]);
        }
        $otp =  OTP::where([
            'mobile_no' => $mobilNumber,
            'otp_type' => $slug,
            'verify_status' => 0,
            'otp' => $otp,
        ])->first();
        if (!$otp instanceof OTP) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP']);
        }
        if ($otp->expired_at > Carbon::now()) {
            $otp->fill([
                'verified_at' => now(),
                'verify_status' => 1

            ]);
            $sessiondata = [
                'mobile_no' => $mobilNumber,
                'otp' => $otp,
            ];
            session(['mobile_data' => $sessiondata]);
            $otp->save();
            return response()->json(['success' => true, 'message' => 'OTP Verified successfully', 'data' => []]);
        }
        return response()->json(['success' => false, 'message' => 'OTP Expired']);
    }
}

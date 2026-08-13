<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SMSService
{
    public static function verifyMobileNumber($mobilNumber)
    {
        $mobileNumberCount = User::where('mobile', $mobilNumber)->count('id');

        return $mobileNumberCount > 0 ? true : false;
    }

    public static function sendWhatsAppMsg($mobileNo, $template_id, $variables = [])
    {
        $parameters = [];
        foreach ($variables as $variable) {
            $parameters[] = ['type' => 'text', 'text' => $variable];
        }
        $authkey = env('MSG91_AUTH_TOKEN');
        $integrated_number = env('MSG91_WHATSAPP_NUMBER');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'authkey' => $authkey,
        ])->post('https://api.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/', [
            "integrated_number" => $integrated_number,
            "content_type" => "template",
            "payload" => [
                "to" => '91'.$mobileNo,
                "type" => "template",
                "template" => [
                    "name" => $template_id,
                    "language" => [
                        "code" => "en",
                        "policy" => "deterministic"
                    ],
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => $parameters
                        ]
                    ]
                ],
                "messaging_product" => "whatsapp"
            ]
        ]);

        $responseBody = $response->getBody()->getContents();
        $responsearr = json_decode($responseBody);
        Log::info("Receiving response from msg91 for => ".$template_id);
        Log::info("Response => " . print_r($responsearr,true));
        if ($responsearr->status == 'success') {
            return true;
        }
        return false;
    }

    public static function verifyOTP($mobileNo, $otp)
    {
        $apiKey = env('2FA_SMS_API_KEY');
        $smsURL = 'https://2factor.in/API/V1/' . $apiKey . '/SMS/VERIFY3/' . $mobileNo . '/' . $otp;
        $response = Http::post($smsURL);
        if ($response->successful()) {
            return true;
        }

        return false;
    }

    public static function sendSMS($mobileNo, $sms)
    {
        $apiKey = env('2FA_SMS_API_KEY');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://2factor.in/API/R1/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'module=TRANS_SMS&apikey=' . $apiKey . '&to=' . $mobileNo . '&from=HEADER&msg=' . $sms . '&ctid=',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
    }
}

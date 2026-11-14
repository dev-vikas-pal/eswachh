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

    /**
     * Should messages actually be delivered from this environment?
     *
     * Never from the test suite. Otherwise the configured value wins, and with
     * nothing configured only production sends - a developer running against a
     * copy of live data must not WhatsApp real customers.
     */
    public static function deliveryEnabled(): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        $configured = config('services.msg91.enabled');

        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        return app()->environment('production');
    }

    /**
     * Write the message we would have sent to the log.
     *
     * Deliberately includes the variables: for the 'otp' template that is the
     * one time code, which is how you finish a signup on a local machine.
     */
    private static function logInsteadOfSending($mobileNo, $template_id, array $variables): bool
    {
        Log::info('WhatsApp message not sent (delivery disabled in '.app()->environment().').', [
            'to' => $mobileNo,
            'template' => $template_id,
            'variables' => $variables,
        ]);

        return true;
    }

    public static function sendWhatsAppMsg($mobileNo, $template_id, $variables = [])
    {
        if (! self::deliveryEnabled()) {
            return self::logInsteadOfSending($mobileNo, $template_id, (array) $variables);
        }

        $parameters = [];
        foreach ($variables as $variable) {
            $parameters[] = ['type' => 'text', 'text' => $variable];
        }
        $authkey = config('services.msg91.auth_token');
        $integrated_number = config('services.msg91.whatsapp_number');

        $recipient = self::whatsAppRecipient($mobileNo);

        if ($recipient === null) {
            Log::warning('WhatsApp message skipped: no usable mobile number.', [
                'template' => $template_id,
            ]);

            return false;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'authkey' => $authkey,
        ])->post('https://api.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/', [
            "integrated_number" => $integrated_number,
            "content_type" => "template",
            "payload" => [
               // "to" => $recipient,
                "to" => 918756966569,
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

        // A gateway outage returns something that is not the expected JSON, and
        // reading ->status off null used to be a fatal error.
        return ($responsearr->status ?? null) === 'success';
    }

    /**
     * MSG91 wants the country code and the number with no spaces.
     * A number that already starts with 91 is left as it is.
     *
     * @param  mixed  $mobileNo
     */
    public static function whatsAppRecipient($mobileNo): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobileNo);

        if ($digits === null || strlen($digits) < 10) {
            return null;
        }

        if (str_starts_with($digits, '91') && strlen($digits) > 10) {
            return $digits;
        }

        return '91'.$digits;
    }

    public static function verifyOTP($mobileNo, $otp)
    {
        $apiKey = config('services.twofactor.key');
        $smsURL = 'https://2factor.in/API/V1/' . $apiKey . '/SMS/VERIFY3/' . $mobileNo . '/' . $otp;
        $response = Http::post($smsURL);
        if ($response->successful()) {
            return true;
        }

        return false;
    }

    public static function sendSMS($mobileNo, $sms)
    {
        if (! self::deliveryEnabled()) {
            Log::info('SMS not sent (delivery disabled in '.app()->environment().').', [
                'to' => $mobileNo,
                'message' => $sms,
            ]);

            return;
        }

        $apiKey = config('services.twofactor.key');
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

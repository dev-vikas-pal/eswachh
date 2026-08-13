<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use App\Mail\KuMail;
use App\Services\SMSService;

class SendRenewalNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renewal:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = User::join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.renew_date', '>=', now()->subDays(7))
            ->where('orders.renew_date', '<=', now()->addDays(7))
            ->get(['users.mobile', 'users.email', 'orders.renew_date']);

        // Send notifications to users
        foreach ($orders as $order) {
            if (!empty($order->email)) {
                $carbonRenewDate = \Carbon\Carbon::parse($order->renew_date);
                // Format the renewal date
                $formattedRenewDate = $carbonRenewDate->format('d/m/Y');
                $message = "Dear Customer, 
        
                Your car subscription expired on $formattedRenewDate
                
                As per the system in place, there will be a 1-week grace period, and after that, cleaning will be stopped. Please renew.
                 
                Go to the renew section at
                https://www.eswachh.in
                
                You can renew for 3/6 months to save 75/300.
                
                Thanks
                Team eSwachh";

                $content = [
                    'subject' => 'Renewal Reminder',
                    'body' => $message
                ];

                // Mail::to($order->email)->send(new KuMail($content));
                SMSService::sendWhatsAppMsg($order->mobile, 'subscription_expire', [$formattedRenewDate]);
            }
        }

        $orders = User::join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.cloth_service', 1)
            ->where('orders.cloth_count', '<=', 10)
            ->get(['users.mobile']);
        foreach ($orders as $order) {
            SMSService::sendWhatsAppMsg($order->mobile, 'lower_cloth_count_cutomer');
        }

        $this->info('Renewal notifications sent successfully.');
    }
}

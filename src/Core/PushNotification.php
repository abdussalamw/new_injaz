<?php
declare(strict_types=1);

namespace App\Core;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotification
{
    private WebPush $webPush;

    public function __construct()
    {
        if (!extension_loaded('gmp') && !extension_loaded('bcmath')) {
            error_log('Push Notification Error: GMP or BCMath extension is required.');
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => 'mailto:info@enjaz-media.com',
                'publicKey' => 'BOO6hReDhGKEWlAlGekjIsorm_s8NMuhCvlFuIxc-LpSZ7RBlf4bjD-2mt0kZXSw07dDoOoUb_eneVr2JllwOc4',
                'privateKey' => 'GoBoBojBv6-0xbHk2szzxp0woEnbSaIa0KGQZCb0eKE',
            ],
        ];

        $this->webPush = new WebPush($auth);
    }

    public function send(array $subscription_data, array $payload): void
    {
        $subscription = Subscription::create([
            'endpoint' => $subscription_data['endpoint'],
            'publicKey' => $subscription_data['p256dh'],
            'authToken' => $subscription_data['auth'],
        ]);

        $this->webPush->queueNotification($subscription, json_encode($payload));
        
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                error_log("Push Notification Error: {$report->getReason()}");
            }
        }
    }
}

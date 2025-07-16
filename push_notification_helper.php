<?php

require __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Sends a single Web Push Notification.
 * @param array $subscription_data The subscription data from the database.
 * @param array $payload The notification content (title, body, url).
 */
function send_push_notification($subscription_data, $payload) {
    // **هام جداً:** استبدل هذه المفاتيح بالمفاتيح الخاصة بك
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:info@enjaz-media.com', // بريدك الإلكتروني أو رابط موقعك
            'publicKey' => 'BOO6hReDhGKEWlAlGekjIsorm_s8NMuhCvlFuIxc-LpSZ7RBlf4bjD-2mt0kZXSw07dDoOoUb_eneVr2JllwOc4',      // المفتاح العام
            'privateKey' => 'GoBoBojBv6-0xbHk2szzxp0woEnbSaIa0KGQZCb0eKE',     // المفتاح الخاص
        ],
    ];

    $webPush = new WebPush($auth);
    $subscription = Subscription::create([
        'endpoint' => $subscription_data['endpoint'],
        'publicKey' => $subscription_data['p256dh'],
        'authToken' => $subscription_data['auth'],
    ]);

    $webPush->queueNotification($subscription, json_encode($payload));
    
    // إرسال جميع الإشعارات في قائمة الانتظار
    foreach ($webPush->flush() as $report) {
        if (!$report->isSuccess()) {
            error_log("Push Notification Error: {$report->getReason()}");
        }
    }
}
?>
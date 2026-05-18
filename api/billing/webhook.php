<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify Stripe signature
function stripe_verify(string $payload, string $sigHeader, string $secret): bool {
    $parts = [];
    foreach (explode(',', $sigHeader) as $part) {
        [$k, $v] = explode('=', $part, 2);
        $parts[$k][] = $v;
    }
    $timestamp = $parts['t'][0] ?? '';
    $signed    = hash_hmac('sha256', "$timestamp.$payload", $secret);
    foreach (($parts['v1'] ?? []) as $sig) {
        if (hash_equals($signed, $sig)) return true;
    }
    return false;
}

if (!stripe_verify($payload, $sigHeader, STRIPE_WEBHOOK_SECRET)) {
    http_response_code(400);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
$obj   = $event['data']['object'] ?? [];

// Map Stripe price IDs to plan names
$priceMap = [
    STRIPE_PRICE_STARTER    => 'starter',
    STRIPE_PRICE_PRO        => 'pro',
    STRIPE_PRICE_BUSINESS   => 'business',
    STRIPE_PRICE_ENTERPRISE => 'enterprise',
];

function upsert_subscription(array $sub, array $priceMap): void {
    $user = DB::find('SELECT id FROM users WHERE stripe_customer_id = ?', [$sub['customer']]);
    if (!$user) return;

    $priceId = $sub['items']['data'][0]['price']['id'] ?? '';
    $plan    = $priceMap[$priceId] ?? 'starter';

    $data = [
        'user_id'                => $user['id'],
        'stripe_subscription_id' => $sub['id'],
        'stripe_price_id'        => $priceId,
        'plan'                   => $plan,
        'status'                 => $sub['status'],
        'trial_ends_at'          => $sub['trial_end']   ? date('Y-m-d H:i:s', $sub['trial_end'])   : null,
        'current_period_start'   => $sub['current_period_start'] ? date('Y-m-d H:i:s', $sub['current_period_start']) : null,
        'current_period_end'     => $sub['current_period_end']   ? date('Y-m-d H:i:s', $sub['current_period_end'])   : null,
        'canceled_at'            => $sub['canceled_at'] ? date('Y-m-d H:i:s', $sub['canceled_at']) : null,
    ];

    $existing = DB::find('SELECT id FROM subscriptions WHERE stripe_subscription_id = ?', [$sub['id']]);
    if ($existing) {
        DB::update('subscriptions', $data, 'stripe_subscription_id = ?', [$sub['id']]);
    } else {
        DB::insert('subscriptions', $data);
    }
}

match ($event['type'] ?? '') {
    'customer.subscription.created',
    'customer.subscription.updated',
    'customer.subscription.deleted' => upsert_subscription($obj, $priceMap),
    default                          => null,
};

http_response_code(200);
echo 'ok';

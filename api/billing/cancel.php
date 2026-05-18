<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = require_auth();

$sub = DB::find(
    "SELECT stripe_subscription_id FROM subscriptions WHERE user_id = ? AND status IN ('active','trialing','past_due') ORDER BY id DESC LIMIT 1",
    [$user['id']]
);

if (!$sub) json_error('No active subscription found', 404);

$subId = $sub['stripe_subscription_id'];

// Cancel at period end so the user keeps access until they've paid for
$ch = curl_init("https://api.stripe.com/v1/subscriptions/$subId");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => http_build_query(['cancel_at_period_end' => 'true']),
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
]);
$result = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($result['id'])) json_error('Could not cancel subscription', 500);

DB::query(
    "UPDATE subscriptions SET canceled_at = NOW(), updated_at = NOW() WHERE stripe_subscription_id = ?",
    [$subId]
);

json_ok(['message' => 'Subscription will be canceled at the end of the current billing period.']);

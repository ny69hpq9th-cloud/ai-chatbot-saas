<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = require_auth();
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$priceId = $body['price_id'] ?? '';
if (!$priceId) json_error('price_id is required');

// Ensure Stripe customer exists
$dbUser = DB::find('SELECT * FROM users WHERE id = ?', [$user['id']]);
$customerId = $dbUser['stripe_customer_id'];

if (!$customerId) {
    $ch = curl_init('https://api.stripe.com/v1/customers');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['email' => $dbUser['email'], 'name' => $dbUser['full_name']]),
        CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $customerId = $res['id'];
    DB::update('users', ['stripe_customer_id' => $customerId], 'id = ?', [$user['id']]);
}

// Create Stripe Checkout Session
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'mode'                                 => 'subscription',
        'customer'                             => $customerId,
        'line_items[0][price]'                 => $priceId,
        'line_items[0][quantity]'              => 1,
        'success_url'                          => APP_URL . '/admin/billing.php?success=1',
        'cancel_url'                           => APP_URL . '/admin/billing.php?canceled=1',
        'subscription_data[trial_period_days]' => 14,
    ]),
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
]);
$session = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($session['url'])) json_error('Could not create checkout session', 500);
json_ok(['url' => $session['url']]);

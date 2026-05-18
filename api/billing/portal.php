<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user   = require_auth();
$dbUser = DB::find('SELECT stripe_customer_id FROM users WHERE id = ?', [$user['id']]);
if (empty($dbUser['stripe_customer_id'])) json_error('No billing account found', 404);

$ch = curl_init('https://api.stripe.com/v1/billing_portal/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'customer'   => $dbUser['stripe_customer_id'],
        'return_url' => APP_URL . '/admin/billing.php',
    ]),
    CURLOPT_USERPWD => STRIPE_SECRET_KEY . ':',
]);
$session = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($session['url'])) json_error('Could not open billing portal', 500);
json_ok(['url' => $session['url']]);

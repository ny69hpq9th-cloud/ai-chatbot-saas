<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';

json_ok([
    [
        'id'       => 'starter',
        'name'     => 'Starter',
        'price'    => 9,
        'currency' => 'usd',
        'limits'   => PLAN_LIMITS['starter'],
        'stripe_price_id' => STRIPE_PRICE_STARTER,
        'features' => ['1 chatbot','500 messages/mo','Basic analytics','Email support'],
    ],
    [
        'id'       => 'pro',
        'name'     => 'Pro',
        'price'    => 29,
        'currency' => 'usd',
        'limits'   => PLAN_LIMITS['pro'],
        'stripe_price_id' => STRIPE_PRICE_PRO,
        'features' => ['5 chatbots','5,000 messages/mo','Advanced analytics','Priority support','Custom branding'],
    ],
    [
        'id'       => 'business',
        'name'     => 'Business',
        'price'    => 79,
        'currency' => 'usd',
        'limits'   => PLAN_LIMITS['business'],
        'stripe_price_id' => STRIPE_PRICE_BUSINESS,
        'features' => ['20 chatbots','25,000 messages/mo','Full analytics','Dedicated support','API access','Custom domain'],
    ],
    [
        'id'       => 'agency',
        'name'     => 'Agency',
        'price'    => 149,
        'currency' => 'usd',
        'limits'   => PLAN_LIMITS['agency'],
        'stripe_price_id' => STRIPE_PRICE_AGENCY,
        'features' => ['Unlimited chatbots','9,999,999 messages/mo','Full analytics','Dedicated support','API access','White-label','Client management'],
    ],
    [
        'id'       => 'enterprise',
        'name'     => 'Enterprise',
        'price'    => null,
        'currency' => 'usd',
        'limits'   => PLAN_LIMITS['enterprise'],
        'stripe_price_id' => STRIPE_PRICE_ENTERPRISE,
        'features' => ['Unlimited chatbots','Unlimited messages','SLA 99.9%','Onboarding','SSO','Custom contracts'],
    ],
]);

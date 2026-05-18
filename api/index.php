<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = str_replace('/api', '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

// Route map: [method, regex] => handler file
$routes = [
    ['POST',   '#^/auth/register$#',                'auth/register.php'],
    ['POST',   '#^/auth/login$#',                   'auth/login.php'],
    ['POST',   '#^/auth/logout$#',                  'auth/logout.php'],

    ['GET',    '#^/chatbots$#',                     'chatbots/index.php'],
    ['POST',   '#^/chatbots$#',                     'chatbots/create.php'],
    ['GET',    '#^/chatbots/([a-z0-9-]+)$#',        'chatbots/show.php'],
    ['PUT',    '#^/chatbots/([a-z0-9-]+)$#',        'chatbots/update.php'],
    ['DELETE', '#^/chatbots/([a-z0-9-]+)$#',        'chatbots/delete.php'],

    ['POST',   '#^/chat/([a-z0-9-]+)/message$#',    'chat/message.php'],
    ['GET',    '#^/chat/([a-z0-9-]+)/history$#',    'chat/history.php'],

    ['GET',    '#^/conversations$#',                'conversations/index.php'],
    ['GET',    '#^/conversations/([a-z0-9-]+)$#',   'conversations/show.php'],
    ['DELETE', '#^/conversations/([a-z0-9-]+)$#',   'conversations/delete.php'],

    ['GET',    '#^/billing/plans$#',                'billing/plans.php'],
    ['POST',   '#^/billing/checkout$#',             'billing/checkout.php'],
    ['POST',   '#^/billing/portal$#',               'billing/portal.php'],
    ['POST',   '#^/billing/cancel$#',               'billing/cancel.php'],
    ['POST',   '#^/billing/webhook$#',              'billing/webhook.php'],

    ['GET',    '#^/stats$#',                        'stats/index.php'],

    ['GET',    '#^/widget/([a-z0-9-]+)/config$#',              'widget/config.php'],

    ['GET',    '#^/chatbots/([a-z0-9-]+)/knowledge$#',        'knowledge/index.php'],
    ['POST',   '#^/chatbots/([a-z0-9-]+)/knowledge$#',        'knowledge/create.php'],
    ['POST',   '#^/chatbots/([a-z0-9-]+)/knowledge/scrape$#', 'knowledge/scrape.php'],
    ['DELETE', '#^/chatbots/([a-z0-9-]+)/knowledge/([0-9]+)$#','knowledge/delete.php'],
];

$params = [];
foreach ($routes as [$routeMethod, $pattern, $file]) {
    if ($routeMethod !== $method) continue;
    if (!preg_match($pattern, $uri, $matches)) continue;
    $params = array_slice($matches, 1);
    $handler = __DIR__ . '/' . $file;
    if (!file_exists($handler)) json_error('Handler not implemented', 501);
    require $handler;
    exit;
}

json_error('Not found', 404);

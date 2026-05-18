<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = require_auth();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$name = trim($body['name'] ?? '');
if (!$name) json_error('name is required');

// Enforce plan chatbot limit
$sub   = DB::find('SELECT plan FROM subscriptions WHERE user_id = ? AND status IN ("active","trialing") ORDER BY id DESC LIMIT 1', [$user['id']]);
$plan  = $sub['plan'] ?? 'starter';
$limit = PLAN_LIMITS[$plan]['chatbots'];
$count = DB::count('SELECT COUNT(*) FROM chatbots WHERE user_id = ?', [$user['id']]);
if ($count >= $limit) json_error("Your $plan plan allows up to $limit chatbot(s). Upgrade to add more.", 403);

$uuid = generate_uuid();
$slug = slugify($name) . '-' . substr($uuid, 0, 6);
DB::insert('chatbots', [
    'uuid'            => $uuid,
    'user_id'         => $user['id'],
    'name'            => sanitize($name),
    'slug'            => $slug,
    'description'     => sanitize($body['description'] ?? ''),
    'system_prompt'   => $body['system_prompt'] ?? '',
    'model'           => $body['model'] ?? 'claude-sonnet-4-6',
    'welcome_message' => $body['welcome_message'] ?? 'Hi! How can I help you today?',
    'widget_color'    => $body['widget_color'] ?? '#6366f1',
]);

$bot = DB::find('SELECT * FROM chatbots WHERE uuid = ?', [$uuid]);
json_ok($bot, 201);

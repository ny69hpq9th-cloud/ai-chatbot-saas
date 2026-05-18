<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user    = require_auth();
$botUuid = $params[0] ?? '';

$bot = DB::find('SELECT * FROM chatbots WHERE uuid = ? AND user_id = ?', [$botUuid, $user['id']]);
if (!$bot) json_error('Chatbot not found', 404);

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$title   = trim($body['title'] ?? '');
$content = trim($body['content'] ?? '');

if (!$content) json_error('content is required');
if (mb_strlen($content) > 200_000) json_error('Content too large (max 200 000 characters)');

$id = DB::insert('knowledge_base', [
    'chatbot_id' => $bot['id'],
    'type'       => 'text',
    'title'      => mb_substr($title, 0, 255),
    'content'    => $content,
    'char_count' => mb_strlen($content),
]);

$item = DB::find('SELECT id, type, title, source_url, char_count, created_at FROM knowledge_base WHERE id = ?', [$id]);
json_ok($item, 201);

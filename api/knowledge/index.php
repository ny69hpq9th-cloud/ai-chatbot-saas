<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user    = require_auth();
$botUuid = $params[0] ?? '';

$bot = DB::find('SELECT * FROM chatbots WHERE uuid = ? AND user_id = ?', [$botUuid, $user['id']]);
if (!$bot) json_error('Chatbot not found', 404);

$items = DB::findAll(
    'SELECT id, type, title, source_url, char_count, created_at FROM knowledge_base WHERE chatbot_id = ? ORDER BY id DESC',
    [$bot['id']]
);

json_ok(['chatbot_id' => $botUuid, 'items' => $items, 'count' => count($items)]);

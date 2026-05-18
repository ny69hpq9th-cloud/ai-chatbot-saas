<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user    = require_auth();
$botUuid = $params[0] ?? '';
$itemId  = (int) ($params[1] ?? 0);

$bot = DB::find('SELECT * FROM chatbots WHERE uuid = ? AND user_id = ?', [$botUuid, $user['id']]);
if (!$bot) json_error('Chatbot not found', 404);

$item = DB::find('SELECT * FROM knowledge_base WHERE id = ? AND chatbot_id = ?', [$itemId, $bot['id']]);
if (!$item) json_error('Knowledge base item not found', 404);

DB::delete('knowledge_base', 'id = ?', [$itemId]);
json_ok(['deleted' => true, 'id' => $itemId]);

<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$botUuid   = $params[0] ?? '';
$sessionId = $_GET['session_id'] ?? '';
if (!$sessionId) json_error('session_id is required');

$bot  = DB::find('SELECT id FROM chatbots WHERE uuid = ? AND is_active = 1', [$botUuid]);
if (!$bot) json_error('Chatbot not found', 404);

$conv = DB::find('SELECT id FROM conversations WHERE chatbot_id = ? AND session_id = ?', [$bot['id'], $sessionId]);
if (!$conv) json_ok([]);

$messages = DB::findAll(
    'SELECT role, content, created_at FROM messages WHERE conversation_id = ? ORDER BY id ASC',
    [$conv['id']]
);
json_ok($messages);

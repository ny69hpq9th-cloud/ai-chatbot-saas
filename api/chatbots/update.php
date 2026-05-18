<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = require_auth();
$uuid = $params[0] ?? '';
$bot  = DB::find('SELECT * FROM chatbots WHERE uuid = ? AND user_id = ?', [$uuid, $user['id']]);
if (!$bot) json_error('Chatbot not found', 404);

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$allowed = ['name','description','system_prompt','model','temperature','max_tokens','welcome_message','widget_color','widget_position','allowed_domains','is_active'];
$update  = [];
foreach ($allowed as $field) {
    if (array_key_exists($field, $body)) $update[$field] = $body[$field];
}
if (empty($update)) json_error('Nothing to update');

DB::update('chatbots', $update, 'uuid = ?', [$uuid]);
json_ok(DB::find('SELECT * FROM chatbots WHERE uuid = ?', [$uuid]));

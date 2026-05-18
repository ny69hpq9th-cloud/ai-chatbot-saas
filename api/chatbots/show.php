<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = require_auth();
$uuid = $params[0] ?? '';
$bot  = DB::find('SELECT * FROM chatbots WHERE uuid = ? AND user_id = ?', [$uuid, $user['id']]);
if (!$bot) json_error('Chatbot not found', 404);
json_ok($bot);

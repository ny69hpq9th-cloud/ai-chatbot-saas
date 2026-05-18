<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Public endpoint — CORS open so any website can embed the widget
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$uuid = $params[0] ?? '';
if (!$uuid) json_error('uuid required', 400);

$bot = DB::find(
    'SELECT name, welcome_message, widget_color, widget_position, is_active FROM chatbots WHERE uuid = ?',
    [$uuid]
);

if (!$bot || !$bot['is_active']) {
    json_error('Chatbot not found or inactive', 404);
}

json_ok([
    'name'            => $bot['name'],
    'welcome_message' => $bot['welcome_message'] ?: 'Hi! How can I help you today?',
    'widget_color'    => $bot['widget_color']    ?: '#00D4FF',
    'widget_position' => $bot['widget_position'] ?: 'bottom-right',
]);

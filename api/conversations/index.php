<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user    = require_auth();
$botUuid = $_GET['chatbot'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where  = 'c.user_id = ?';
$params = [$user['id']];
if ($botUuid) {
    $where   .= ' AND b.uuid = ?';
    $params[] = $botUuid;
}

$total = DB::count(
    "SELECT COUNT(*) FROM conversations conv JOIN chatbots b ON b.id=conv.chatbot_id WHERE $where",
    $params
);
$pager = paginate($total, $perPage, $page);

$rows = DB::findAll(
    "SELECT conv.uuid, conv.session_id, conv.message_count, conv.started_at, conv.last_message_at, conv.is_resolved, b.name chatbot_name
     FROM conversations conv
     JOIN chatbots b ON b.id = conv.chatbot_id
     JOIN users c ON c.id = b.user_id
     WHERE $where
     ORDER BY conv.last_message_at DESC
     LIMIT $perPage OFFSET {$pager['offset']}",
    $params
);

json_ok(['conversations' => $rows, 'pagination' => $pager]);

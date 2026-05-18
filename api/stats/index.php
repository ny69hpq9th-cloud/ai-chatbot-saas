<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = require_auth();
$days = min(90, max(7, (int)($_GET['days'] ?? 30)));

$stats = DB::findAll(
    'SELECT us.stat_date, SUM(us.conversations) conversations, SUM(us.messages_sent) messages_sent, SUM(us.tokens_consumed) tokens_consumed
     FROM usage_stats us
     JOIN chatbots c ON c.id = us.chatbot_id
     WHERE c.user_id = ? AND us.stat_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY us.stat_date ORDER BY us.stat_date ASC',
    [$user['id'], $days]
);

$totals = DB::find(
    'SELECT COUNT(DISTINCT conv.id) total_conversations,
            COUNT(msg.id) total_messages,
            COALESCE(SUM(msg.tokens_used),0) total_tokens
     FROM chatbots c
     LEFT JOIN conversations conv ON conv.chatbot_id = c.id
     LEFT JOIN messages msg ON msg.conversation_id = conv.id
     WHERE c.user_id = ?',
    [$user['id']]
);

json_ok(['daily' => $stats, 'totals' => $totals, 'days' => $days]);

<?php
/**
 * Daily stats rollup cron — run once per day
 * Crontab: 0 2 * * * php /path/to/cron/daily_stats.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Backfill any missing stat_date rows for yesterday
$yesterday = date('Y-m-d', strtotime('-1 day'));

$rows = DB::findAll(
    'SELECT c.id chatbot_id,
            COUNT(DISTINCT conv.id) conversations,
            COUNT(msg.id) messages_sent,
            COALESCE(SUM(msg.tokens_used), 0) tokens_consumed
     FROM chatbots c
     LEFT JOIN conversations conv ON conv.chatbot_id = c.id AND DATE(conv.started_at) = ?
     LEFT JOIN messages msg ON msg.conversation_id = conv.id AND DATE(msg.created_at) = ?
     GROUP BY c.id',
    [$yesterday, $yesterday]
);

foreach ($rows as $row) {
    DB::query(
        'INSERT INTO usage_stats (chatbot_id, stat_date, conversations, messages_sent, tokens_consumed)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           conversations    = VALUES(conversations),
           messages_sent    = VALUES(messages_sent),
           tokens_consumed  = VALUES(tokens_consumed)',
        [$row['chatbot_id'], $yesterday, $row['conversations'], $row['messages_sent'], $row['tokens_consumed']]
    );
}

echo "[" . date('Y-m-d H:i:s') . "] Daily stats backfill done for $yesterday\n";

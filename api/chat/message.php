<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Public endpoint — identified by chatbot UUID + optional API key
$botUuid = $params[0] ?? '';
$bot = DB::find('SELECT * FROM chatbots WHERE uuid = ? AND is_active = 1', [$botUuid]);
if (!$bot) json_error('Chatbot not found', 404);

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$message   = trim($body['message'] ?? '');
$sessionId = trim($body['session_id'] ?? '');
if (!$message || !$sessionId) json_error('message and session_id are required');

// Rate-limit: max tokens per month per chatbot owner
$ownerSub  = DB::find('SELECT plan FROM subscriptions WHERE user_id = ? AND status IN ("active","trialing") ORDER BY id DESC LIMIT 1', [$bot['user_id']]);
$plan      = $ownerSub['plan'] ?? 'starter';
$monthLimit = PLAN_LIMITS[$plan]['tokens_per_month'];
$monthStart = date('Y-m-01');
$usedTokens = (int) DB::count(
    'SELECT COALESCE(SUM(tokens_consumed),0) FROM usage_stats us JOIN chatbots c ON c.id=us.chatbot_id WHERE c.user_id=? AND us.stat_date>=?',
    [$bot['user_id'], $monthStart]
);
if ($usedTokens >= $monthLimit) json_error('Monthly token limit reached. Please upgrade your plan.', 429);

// Upsert conversation
$conv = DB::find('SELECT * FROM conversations WHERE chatbot_id = ? AND session_id = ?', [$bot['id'], $sessionId]);
if (!$conv) {
    $convUuid = generate_uuid();
    DB::insert('conversations', [
        'uuid'       => $convUuid,
        'chatbot_id' => $bot['id'],
        'session_id' => $sessionId,
        'visitor_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'visitor_ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        'referrer'   => substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
        'page_url'   => substr($body['page_url'] ?? '', 0, 500),
    ]);
    $conv = DB::find('SELECT * FROM conversations WHERE uuid = ?', [$convUuid]);
}

// Build message history for context (last 20)
$history = DB::findAll(
    'SELECT role, content FROM messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 20',
    [$conv['id']]
);
$history = array_reverse($history);

// Save user message
DB::insert('messages', [
    'conversation_id' => $conv['id'],
    'role'            => 'user',
    'content'         => $message,
]);

// Build Claude API request
$messages = array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $history);
$messages[] = ['role' => 'user', 'content' => $message];

$payload = [
    'model'      => $bot['model'],
    'max_tokens' => $bot['max_tokens'],
    'messages'   => $messages,
];
if (!empty($bot['system_prompt'])) {
    $payload['system'] = $bot['system_prompt'];
}

$start  = microtime(true);
$ch     = curl_init(ANTHROPIC_API_URL . '/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: ' . ANTHROPIC_API_VERSION,
    ],
    CURLOPT_TIMEOUT        => 60,
]);
$raw        = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$latencyMs  = (int) ((microtime(true) - $start) * 1000);
curl_close($ch);

if ($httpCode !== 200) {
    json_error('AI service error', 502);
}

$response    = json_decode($raw, true);
$assistantMsg = $response['content'][0]['text'] ?? '';
$tokensUsed   = $response['usage']['output_tokens'] ?? 0;

// Save assistant message
DB::insert('messages', [
    'conversation_id' => $conv['id'],
    'role'            => 'assistant',
    'content'         => $assistantMsg,
    'tokens_used'     => $tokensUsed,
    'latency_ms'      => $latencyMs,
]);

// Update conversation meta
DB::update('conversations', [
    'last_message_at' => now(),
    'message_count'   => $conv['message_count'] + 2,
], 'id = ?', [$conv['id']]);

// Upsert daily usage stats
DB::query(
    'INSERT INTO usage_stats (chatbot_id, stat_date, conversations, messages_sent, tokens_consumed)
     VALUES (?, CURDATE(), 0, 1, ?)
     ON DUPLICATE KEY UPDATE messages_sent = messages_sent + 1, tokens_consumed = tokens_consumed + ?',
    [$bot['id'], $tokensUsed, $tokensUsed]
);

json_ok([
    'message'     => $assistantMsg,
    'session_id'  => $sessionId,
    'tokens_used' => $tokensUsed,
]);

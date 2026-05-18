<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user    = require_auth();
$botUuid = $params[0] ?? '';

$bot = DB::find('SELECT * FROM chatbots WHERE uuid = ? AND user_id = ?', [$botUuid, $user['id']]);
if (!$bot) json_error('Chatbot not found', 404);

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$url  = trim($body['url'] ?? '');

if (!$url) json_error('url is required');
if (!filter_var($url, FILTER_VALIDATE_URL)) json_error('Invalid URL');

$parsed = parse_url($url);
if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
    json_error('Only http/https URLs are allowed');
}

// Block private/internal addresses
$host = $parsed['host'] ?? '';
if (preg_match('/^(localhost|127\.|10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/i', $host)) {
    json_error('Private/internal URLs are not allowed', 403);
}

// Fetch the URL
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; KnowledgeBot/1.0)',
    CURLOPT_HTTPHEADER     => ['Accept: text/html'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_ENCODING       => '',
]);
$html     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error || !$html) json_error('Could not fetch URL: ' . ($error ?: 'empty response'), 502);
if ($httpCode >= 400) json_error("URL returned HTTP $httpCode", 502);

// Extract meaningful text from HTML
$text = extractText($html);
if (mb_strlen($text) < 50) json_error('Could not extract meaningful text from that page', 422);

// Truncate to 200k chars
if (mb_strlen($text) > 200_000) {
    $text = mb_substr($text, 0, 200_000) . "\n[Truncated]";
}

// Derive a title from <title> tag if available
preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $titleMatch);
$pageTitle = isset($titleMatch[1]) ? trim(html_entity_decode(strip_tags($titleMatch[1]))) : $host;
$pageTitle = mb_substr($pageTitle, 0, 255);

$id = DB::insert('knowledge_base', [
    'chatbot_id' => $bot['id'],
    'type'       => 'url',
    'title'      => $pageTitle,
    'content'    => $text,
    'source_url' => mb_substr($url, 0, 500),
    'char_count' => mb_strlen($text),
]);

$item = DB::find('SELECT id, type, title, source_url, char_count, created_at FROM knowledge_base WHERE id = ?', [$id]);
json_ok($item, 201);

/* ── Text extraction helper ─────────────────────────────────── */
function extractText(string $html): string
{
    // Remove scripts, styles, nav, footer
    $html = preg_replace('#<(script|style|nav|footer|header|aside|noscript)[^>]*>.*?</\1>#is', ' ', $html);
    // Remove all tags, decode entities, collapse whitespace
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[ \t]{2,}/', ' ', $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

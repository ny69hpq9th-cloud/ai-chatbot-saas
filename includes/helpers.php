<?php
declare(strict_types=1);

// ── UUID v4 ───────────────────────────────────────────────────────────────────
function generate_uuid(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ── JSON responses ────────────────────────────────────────────────────────────
function json_ok(mixed $data = null, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400, array $errors = []): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $body = ['success' => false, 'message' => $message];
    if ($errors) $body['errors'] = $errors;
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Auth helpers ──────────────────────────────────────────────────────────────
function hash_password(string $plain): string {
    return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_password(string $plain, string $hash): bool {
    return password_verify($plain, $hash);
}

function generate_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

// ── Sanitisation ──────────────────────────────────────────────────────────────
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function sanitize_email(string $email): string|false {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

function validate_email(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ── Session ───────────────────────────────────────────────────────────────────
function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function auth_user(): ?array {
    session_start_safe();
    return $_SESSION['user'] ?? null;
}

function require_auth(): array {
    $user = auth_user();
    if (!$user) {
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            json_error('Unauthenticated', 401);
        }
        header('Location: /login.php');
        exit;
    }
    return $user;
}

function login_user(array $user): void {
    session_start_safe();
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => $user['id'],
        'uuid'     => $user['uuid'],
        'email'    => $user['email'],
        'name'     => $user['full_name'],
        'role'     => $user['role'],
    ];
}

function logout_user(): void {
    session_start_safe();
    $_SESSION = [];
    session_destroy();
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
function csrf_token(): string {
    session_start_safe();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generate_token();
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(string $token): bool {
    return hash_equals(csrf_token(), $token);
}

// ── Redirect ──────────────────────────────────────────────────────────────────
function redirect(string $url, int $code = 302): never {
    header("Location: $url", true, $code);
    exit;
}

// ── Date / time ───────────────────────────────────────────────────────────────
function now(): string {
    return date('Y-m-d H:i:s');
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    return match(true) {
        $diff < 60     => 'just now',
        $diff < 3600   => (int)($diff / 60) . 'm ago',
        $diff < 86400  => (int)($diff / 3600) . 'h ago',
        default        => (int)($diff / 86400) . 'd ago',
    };
}

// ── Slugify ───────────────────────────────────────────────────────────────────
function slugify(string $text): string {
    $text = preg_replace('/[^\w\s-]/', '', mb_strtolower($text));
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return substr($text, 0, 80);
}

// ── Pagination ────────────────────────────────────────────────────────────────
function paginate(int $total, int $perPage, int $page): array {
    $pages = (int) ceil($total / max(1, $perPage));
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $page,
        'pages'       => $pages,
        'offset'      => ($page - 1) * $perPage,
        'has_prev'    => $page > 1,
        'has_next'    => $page < $pages,
    ];
}

<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';
$name     = trim($body['name'] ?? '');

if (!$email || !$password || !$name) json_error('name, email and password are required');

try {
    $user = Auth::register($email, $password, $name);
    login_user($user);
    json_ok(['user' => ['id' => $user['uuid'], 'email' => $user['email'], 'name' => $user['full_name']]], 201);
} catch (RuntimeException $e) {
    json_error($e->getMessage(), 422);
}

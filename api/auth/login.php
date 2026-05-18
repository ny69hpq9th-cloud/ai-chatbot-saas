<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$email    = trim($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) json_error('email and password are required');

$user = Auth::attempt($email, $password);
if (!$user) json_error('Invalid credentials', 401);

login_user($user);
json_ok(['user' => ['id' => $user['uuid'], 'email' => $user['email'], 'name' => $user['full_name']]]);

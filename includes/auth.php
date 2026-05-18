<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

class Auth {
    public static function register(string $email, string $password, string $name): array {
        $email = sanitize_email($email);
        if (!validate_email($email)) {
            throw new RuntimeException('Invalid email address');
        }
        if (strlen($password) < 8) {
            throw new RuntimeException('Password must be at least 8 characters');
        }
        if (DB::find('SELECT id FROM users WHERE email = ?', [$email])) {
            throw new RuntimeException('Email is already registered');
        }

        $uuid = generate_uuid();
        DB::insert('users', [
            'uuid'          => $uuid,
            'email'         => $email,
            'password_hash' => hash_password($password),
            'full_name'     => sanitize($name),
        ]);

        return DB::find('SELECT * FROM users WHERE uuid = ?', [$uuid]);
    }

    public static function attempt(string $email, string $password): ?array {
        $user = DB::find('SELECT * FROM users WHERE email = ?', [sanitize_email($email)]);
        if (!$user || !verify_password($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    public static function sendPasswordReset(string $email): bool {
        $user = DB::find('SELECT id FROM users WHERE email = ?', [$email]);
        if (!$user) return false;

        $token = generate_token();
        DB::query(
            'REPLACE INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)',
            [$email, hash('sha256', $token), date('Y-m-d H:i:s', strtotime('+1 hour'))]
        );
        // TODO: send email with APP_URL . '/reset-password.php?token=' . $token
        return true;
    }

    public static function resetPassword(string $token, string $newPassword): bool {
        $hash = hash('sha256', $token);
        $row  = DB::find(
            'SELECT email FROM password_resets WHERE token_hash = ? AND expires_at > NOW()',
            [$hash]
        );
        if (!$row) return false;

        DB::update('users', ['password_hash' => hash_password($newPassword)], 'email = ?', [$row['email']]);
        DB::delete('password_resets', 'email = ?', [$row['email']]);
        return true;
    }
}

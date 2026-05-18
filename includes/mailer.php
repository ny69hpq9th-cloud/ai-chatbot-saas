<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Mailer {
    public static function send(string $to, string $subject, string $htmlBody): bool {
        $fromName  = MAIL_FROM_NAME;
        $fromEmail = MAIL_FROM;
        $text      = strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody));

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";

        // In production replace with PHPMailer + SMTP
        // For dev: logs to file instead of sending
        if (APP_ENV !== 'production') {
            $logDir = ROOT_PATH . '/logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            file_put_contents(
                $logDir . '/mail.log',
                "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject\n$text\n---\n",
                FILE_APPEND
            );
            return true;
        }

        return mail($to, $subject, $htmlBody, $headers);
    }

    public static function sendWelcome(string $to, string $name): bool {
        $appName = APP_NAME;
        $url     = APP_URL;
        $subject = "Welcome to {$appName} — let's build your first chatbot";

        $html = <<<HTML
<!DOCTYPE html><html><body style="font-family:'Segoe UI',sans-serif;background:#000;color:#fff;margin:0;padding:40px 20px;">
<div style="max-width:560px;margin:0 auto;">
  <div style="margin-bottom:32px;">
    <span style="font-size:1.3rem;font-weight:800;background:linear-gradient(90deg,#00D4FF,#8B5CF6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">{$appName}</span>
  </div>
  <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:12px;color:#fff;">Welcome, {$name}! 👋</h1>
  <p style="color:#a0a0a0;line-height:1.7;margin-bottom:24px;">Your account is ready. You're on a <strong style="color:#00D4FF;">14-day free trial</strong> — no credit card needed.</p>
  <p style="color:#a0a0a0;line-height:1.7;margin-bottom:32px;">Here's what to do next:</p>
  <ol style="color:#a0a0a0;line-height:2;margin-bottom:32px;padding-left:20px;">
    <li>Create your first chatbot</li>
    <li>Customize it with your brand</li>
    <li>Copy the embed snippet to your website</li>
  </ol>
  <a href="{$url}/admin/" style="display:inline-block;background:linear-gradient(90deg,#00D4FF,#8B5CF6);color:#000;font-weight:700;font-size:.9rem;padding:14px 32px;border-radius:8px;text-decoration:none;letter-spacing:.05em;">Go to dashboard →</a>
  <p style="color:#505050;font-size:.8rem;margin-top:40px;border-top:1px solid #1a1a1a;padding-top:20px;">{$appName} · You received this because you signed up at {$url}</p>
</div>
</body></html>
HTML;
        return self::send($to, $subject, $html);
    }

    public static function sendPasswordReset(string $to, string $name, string $resetUrl): bool {
        $appName = APP_NAME;
        $subject = "Reset your {$appName} password";

        $html = <<<HTML
<!DOCTYPE html><html><body style="font-family:'Segoe UI',sans-serif;background:#000;color:#fff;margin:0;padding:40px 20px;">
<div style="max-width:560px;margin:0 auto;">
  <div style="margin-bottom:32px;">
    <span style="font-size:1.3rem;font-weight:800;background:linear-gradient(90deg,#00D4FF,#8B5CF6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">{$appName}</span>
  </div>
  <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:12px;color:#fff;">Reset your password</h1>
  <p style="color:#a0a0a0;line-height:1.7;margin-bottom:32px;">Hi {$name}, click the button below to set a new password. This link expires in <strong style="color:#00D4FF;">1 hour</strong>.</p>
  <a href="{$resetUrl}" style="display:inline-block;background:linear-gradient(90deg,#00D4FF,#8B5CF6);color:#000;font-weight:700;font-size:.9rem;padding:14px 32px;border-radius:8px;text-decoration:none;letter-spacing:.05em;">Reset password →</a>
  <p style="color:#a0a0a0;line-height:1.7;margin-top:32px;font-size:.875rem;">If you didn't request this, you can safely ignore this email.</p>
  <p style="color:#505050;font-size:.8rem;margin-top:40px;border-top:1px solid #1a1a1a;padding-top:20px;">{$appName}</p>
</div>
</body></html>
HTML;
        return self::send($to, $subject, $html);
    }
}

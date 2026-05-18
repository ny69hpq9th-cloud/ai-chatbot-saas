<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

$user = require_auth();
$bots = DB::findAll('SELECT * FROM chatbots WHERE user_id = ? ORDER BY created_at DESC', [$user['id']]);
json_ok($bots);

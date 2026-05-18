<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/helpers.php';
logout_user();
json_ok(['message' => 'Logged out']);

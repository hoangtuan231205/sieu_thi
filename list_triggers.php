<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

$db = Database::getInstance();
$triggers = $db->query("SHOW TRIGGERS")->fetchAll();

header('Content-Type: application/json');
echo json_encode($triggers, JSON_PRETTY_PRINT);

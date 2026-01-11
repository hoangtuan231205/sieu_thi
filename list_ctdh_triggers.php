<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

$db = Database::getInstance();
$triggers = $db->query("SHOW TRIGGERS WHERE `Table` = 'chi_tiet_don_hang'")->fetchAll();

foreach ($triggers as $t) {
    echo "TRIGGER: " . $t['Trigger'] . " (" . $t['Timing'] . " " . $t['Event'] . ")\n";
}

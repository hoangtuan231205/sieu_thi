<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "SUCCESS: PhpOffice\PhpSpreadsheet\Spreadsheet class found!";
} catch (\Throwable $e) {
    echo "FAILURE: " . $e->getMessage();
}

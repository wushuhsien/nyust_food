<?php
// db_mongo.php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
} catch (Exception $e) {
    echo "MongoDB 連線失敗: " . $e->getMessage();
    exit;
}
?>
<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    // 檢查類別是否存在
    if (!class_exists('MongoDB\Driver\Manager')) {
        die("錯誤：找不到 MongoDB 驅動！請檢查 php.ini 是否有開啟 extension=mongodb");
    }

    // 嘗試連線
    $manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
    $command = new MongoDB\Driver\Command(['ping' => 1]);
    $manager->executeCommand('db', $command);

    echo "恭喜！MongoDB連線成功！環境設定正確。";
} catch (Exception $e) {
    echo "連線失敗：" . $e->getMessage();
    echo "<br>請確認：<br>1. MongoDB Server 有在執行嗎？(工作管理員)<br>2. Port 是 27017 嗎？";
}
?>
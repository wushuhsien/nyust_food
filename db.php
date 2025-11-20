<?php
// 建立資料庫連線
$hostname = '172.31.2.38';
$db_username = 'nyust_food';
$db_password = '0306131649';
$databasename = 'iron_man';

$link = new mysqli($hostname, $db_username, $db_password, $databasename);

// 檢查連線
if ($link->connect_error) {
    die("資料庫連線失敗: " . $link->connect_error);
}

// 設定 UTF-8 編碼
$link->set_charset("utf8");

// 啟動 session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

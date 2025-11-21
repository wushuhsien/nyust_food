<?php
// 建立資料庫連線
$hostname = '127.0.0.1';
$db_username = 'root';
$db_password = '';
$databasename = 'nyust_food';

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

// 如果需要抓 storetype name 欄位
$storeTypes = [];
$sql = "SELECT name FROM storetype";
$result = $link->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $storeTypes[] = $row['name'];
    }
}
?>

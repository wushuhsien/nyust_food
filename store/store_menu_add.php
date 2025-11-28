<?php
header("Content-Type: text/plain; charset=utf-8");
session_start();
include "../db.php";

$loginAccount = $_SESSION['user'] ?? '';
if ($loginAccount == "") {
    echo "未登入使用者";
    exit;
}

// 讀取 JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data["action"])) {
    echo "無效請求";
    exit;
}

$action = $data["action"];

if ($action === "add_menu_series") {

    $type = $data["type"] ?? '';
    $items = $data["items"] ?? [];

    if ($type === "" || empty($items)) {
        echo "資料不足（系列名稱 + 至少一個品項）";
        exit;
    }

    // 準備 SQL
    // 注意：欄位名稱要與您的資料庫完全一致
    $stmt2 = $link->prepare("
        INSERT INTO menu (`type`, `name`, `description`, `price`, `stock`, `note`, `cook_time`, `account`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt2) {
        echo "SQL 準備失敗: " . $link->error;
        exit;
    }

    foreach ($items as $item) {

        $name = $item["name"];
        $desc = $item["description"];
        $price = intval($item["price"]); // 確保是數字
        $stock = ($item["stock"] === "") ? 0 : intval($item["stock"]); // 確保是數字
        $note  = $item["note"];
        
        // 處理時間格式：前端傳來的是分鐘數 (例如 "15")，但資料庫是 time 格式
        // 建議轉換成 HH:MM:SS 格式，否則 MySQL 可能會存成 00:00:15 (15秒)
        $cookInput = intval($item["cook_time"]);
        // 簡單轉換：假設輸入是分鐘，轉成 00:XX:00
        $cook = sprintf("00:%02d:00", $cookInput); 

        // 修正重點：加上第一個參數 "sssiisss"
        // s = string (字串), i = integer (整數)
        // 對應順序：type(s), name(s), description(s), price(i), stock(i), note(s), cook_time(s), account(s)
        $stmt2->bind_param(
            "sssiisss", 
            $type,
            $name,
            $desc,
            $price,
            $stock,
            $note,
            $cook,
            $loginAccount
        );

        if (!$stmt2->execute()) {
            echo "品項新增失敗：" . $stmt2->error;
            $stmt2->close();
            exit;
        }
    }

    $stmt2->close();

    echo "系列與品項新增成功！";
    exit;
}

echo "未知動作";
exit;
?>
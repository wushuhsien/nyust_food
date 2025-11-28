<?php
session_start();
include "../db.php";

// 1. 權限檢查
$loginAccount = $_SESSION['user'] ?? '';
if (!$loginAccount) {
    echo "請先登入";
    exit;
}

// 2. 檢查是否有收到資料
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu'])) {
    
    $items = $_POST['menu']; // 這會是一個陣列 [menu_id => [name=>..., price=>...]]
    $count = 0;
    $errorCount = 0;

    // 準備 SQL (為了安全，限定只能更新該帳號 account 的資料)
    $sql = "UPDATE menu SET 
            name = ?, 
            description = ?, 
            price = ?, 
            stock = ?, 
            cook_time = ? 
            WHERE menu_id = ? AND account = ?";
            
    $stmt = $link->prepare($sql);

    foreach ($items as $id => $data) {
        $name = $data['name'];
        $desc = $data['description'];
        $price = intval($data['price']);
        $stock = intval($data['stock']);
        
        // 處理時間格式 (假設前端傳來的是分鐘數 15，轉為 00:15:00)
        // 若您的資料庫是存 INT 分鐘數，則不用轉格式
        $cookMinutes = intval($data['cook_time']);
        $cookTimeStr = "00:" . str_pad($cookMinutes, 2, "0", STR_PAD_LEFT) . ":00";

        // 綁定參數 (s=string, i=integer)
        // 對應: name(s), description(s), price(i), stock(i), cook_time(s), menu_id(i), account(s)
        $stmt->bind_param("ssiisds", 
            $name, 
            $desc, 
            $price, 
            $stock, 
            $cookTimeStr,
            $id,
            $loginAccount
        );

        if ($stmt->execute()) {
            $count++;
        } else {
            $errorCount++;
        }
    }
    $stmt->close();

    echo "更新完成！";
} else {
    echo "無效的請求";
}
?>
<?php
session_start();
include "../db.php";
include "../db_mongo.php"; // ★ 1. 記得引入 MongoDB 連線

// 權限檢查
$loginAccount = $_SESSION['user'] ?? '';
if (!$loginAccount) {
    echo "請先登入";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu'])) {
    
    $items = $_POST['menu']; 
    $count = 0;
    $errorCount = 0;

    // 準備 SQL (更新文字資料)
    $sql = "UPDATE menu SET 
            name = ?, 
            description = ?, 
            price = ?, 
            stock = ?, 
            cook_time = ? 
            WHERE menu_id = ? AND account = ?";
            
    $stmt = $link->prepare($sql);

    // 準備 SQL (更新圖片 ID) - 獨立出來比較單純
    $sqlImg = "UPDATE menu SET img_id = ? WHERE menu_id = ? AND account = ?";
    $stmtImg = $link->prepare($sqlImg);

    foreach ($items as $id => $data) {
        $name = $data['name'];
        $desc = $data['description'];
        $price = intval($data['price']);
        $stock = intval($data['stock']);
        
        $cookMinutes = intval($data['cook_time']);
        $cookTimeStr = "00:" . str_pad($cookMinutes, 2, "0", STR_PAD_LEFT) . ":00";

        // 1. 先更新文字資料
        $stmt->bind_param("ssiisds", 
            $name, $desc, $price, $stock, $cookTimeStr, $id, $loginAccount
        );

        if ($stmt->execute()) {
            $count++;
        } else {
            $errorCount++;
        }

        // ★★★ 2. 處理圖片 (如果有上傳新圖) ★★★
        if (isset($data['image_base64']) && !empty($data['image_base64'])) {
            try {
                // A. 存入 MongoDB
                $bulk = new MongoDB\Driver\BulkWrite;
                $mongoId = new MongoDB\BSON\ObjectId();
                
                $doc = [
                    '_id' => $mongoId,
                    'account' => $loginAccount,
                    'menu_name' => $name,
                    'image_base64' => $data['image_base64'], // 取得隱藏欄位的 Base64
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $bulk->insert($doc);
                $manager->executeBulkWrite('store_db.menu_images', $bulk);
                
                // B. 取得新的 ID 字串
                $newImgId = (string)$mongoId;
                
                // C. 更新 MySQL 的 img_id
                $stmtImg->bind_param("sis", $newImgId, $id, $loginAccount);
                $stmtImg->execute();

            } catch (Exception $e) {
                // 圖片錯誤不中斷文字更新，但可以記錄
                // error_log("Image update failed: " . $e->getMessage());
            }
        }
    }
    
    $stmt->close();
    $stmtImg->close();

    echo "更新完成！";
} else {
    echo "無效的請求";
}
?>
<?php
session_start();
include "../db.php";
include "../db_mongo.php"; // 確保 MongoDB 連線正常

// 權限檢查
$loginAccount = $_SESSION['user'] ?? '';
if (!$loginAccount) {
    echo "請先登入";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu'])) {
    
    $items = $_POST['menu']; 
    $count = 0;
    
    // 1. 準備 SQL (更新文字資料)
    $sql = "UPDATE menu SET 
            name = ?, 
            description = ?, 
            price = ?, 
            stock = ?, 
            cook_time = ? 
            WHERE menu_id = ? AND account = ?";
            
    $stmt = $link->prepare($sql);

    // 2. 準備 SQL (更新圖片 ID：換新圖片用)
    $sqlImg = "UPDATE menu SET img_id = ? WHERE menu_id = ? AND account = ?";
    $stmtImg = $link->prepare($sqlImg);

    // ★ 3. 準備 SQL (刪除圖片 ID：將 img_id 設為 NULL)
    $sqlDelImg = "UPDATE menu SET img_id = NULL WHERE menu_id = ? AND account = ?";
    $stmtDelImg = $link->prepare($sqlDelImg);

    foreach ($items as $id => $data) {
        $name = $data['name'];
        $desc = $data['description'];
        $price = intval($data['price']);
        $stock = intval($data['stock']);
        
        $cookMinutes = intval($data['cook_time']);
        $cookTimeStr = "00:" . str_pad($cookMinutes, 2, "0", STR_PAD_LEFT) . ":00";

        // --- Step 1: 先更新文字資料 ---
        // 注意 bind_param 參數類型：s=string, i=integer, d=double
        // 這裡 id 若是 int 應用 i，若是 string 應用 s，假設 menu_id 是 int
        $stmt->bind_param("ssiisis", 
            $name, $desc, $price, $stock, $cookTimeStr, $id, $loginAccount
        );
        $stmt->execute();
        $count++;

        // --- Step 2: 圖片處理邏輯 ---

        // A. 判斷是否要刪除圖片
        if (isset($data['delete_image']) && $data['delete_image'] == '1') {
            
            // 執行刪除：把 MySQL 的 img_id 清空
            $stmtDelImg->bind_param("is", $id, $loginAccount);
            $stmtDelImg->execute();

            // (進階選項：如果你希望同時刪除 MongoDB 裡的舊檔案以節省空間，
            //  需要先 SELECT 舊 img_id，然後呼叫 MongoDB delete。
            //  目前這樣寫只會斷開連結，舊圖會留在 NoSQL 裡，不影響功能)
        }
        // B. 如果沒刪除，再判斷是否有上傳新圖片
        elseif (isset($data['image_base64']) && !empty($data['image_base64'])) {
            try {
                // 存入 MongoDB
                $bulk = new MongoDB\Driver\BulkWrite;
                $mongoId = new MongoDB\BSON\ObjectId();
                
                $doc = [
                    '_id' => $mongoId,
                    'account' => $loginAccount,
                    'menu_name' => $name,
                    'image_base64' => $data['image_base64'],
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $bulk->insert($doc);
                $manager->executeBulkWrite('store_db.menu_images', $bulk);
                
                // 取得新的 ID 字串
                $newImgId = (string)$mongoId;
                
                // 更新 MySQL 的 img_id
                $stmtImg->bind_param("sis", $newImgId, $id, $loginAccount);
                $stmtImg->execute();

            } catch (Exception $e) {
                // 記錄錯誤但不中斷
                // error_log("Image update failed: " . $e->getMessage());
            }
        }
    }
    
    // 關閉所有 Statement
    $stmt->close();
    $stmtImg->close();
    $stmtDelImg->close();

    echo "更新完成！";
} else {
    echo "無效的請求";
}
?>
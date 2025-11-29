<?php
header("Content-Type: text/plain; charset=utf-8");
session_start();
include "../db.php";
include "../db_mongo.php"; // 確保這裡面建立了 MongoDB 連線，例如 $manager

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
        echo "資料不足";
        exit;
    }

    // 準備 MySQL SQL (多了一個 img_id 欄位)
    $stmt2 = $link->prepare("
        INSERT INTO menu (`type`, `name`, `description`, `price`, `stock`, `note`, `cook_time`, `account`, `img_id`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt2) {
        echo "SQL 準備失敗: " . $link->error;
        exit;
    }

    foreach ($items as $item) {
        $name = $item["name"];
        $desc = $item["description"];
        $price = intval($item["price"]);
        $stock = ($item["stock"] === "") ? 0 : intval($item["stock"]);
        $note  = $item["note"];
        $cookInput = intval($item["cook_time"]);
        $cook = sprintf("00:%02d:00", $cookInput);
        
        // --- NoSQL 圖片處理開始 ---
        $img_id_str = null; // 預設為 null

        // 檢查是否有圖片資料 (Base64字串)
        if (!empty($item['image_data'])) {
            try {
                // 1. 建立 MongoDB BulkWrite 物件
                $bulk = new MongoDB\Driver\BulkWrite;
                
                // 2. 產生一個新的 ObjectId
                $mongoId = new MongoDB\BSON\ObjectId();
                
                // 3. 準備要寫入 NoSQL 的文件
                $doc = [
                    '_id' => $mongoId,
                    'account' => $loginAccount,
                    'menu_name' => $name,
                    'image_base64' => $item['image_data'], // 存入前端傳來的 Base64
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                // 4. 加入插入佇列
                $bulk->insert($doc);
                
                // 5. 執行寫入 (假設資料庫叫 'store_db'，集合叫 'menu_images')
                // 請根據你的 db_mongo.php 設定修改 db 名稱
                $manager->executeBulkWrite('store_db.menu_images', $bulk);
                
                // 6. 取得 ID 字串，準備存入 MySQL
                $img_id_str = (string)$mongoId;

            } catch (Exception $e) {
                // 圖片上傳失敗不應阻擋文字存檔，但可以記錄 log
                // error_log("MongoDB Error: " . $e->getMessage());
            }
        }
        // --- NoSQL 圖片處理結束 ---

        // 綁定參數 (注意多了最後一個 s 對應 img_id)
        $stmt2->bind_param(
            "sssiissss", // 9個參數
            $type,
            $name,
            $desc,
            $price,
            $stock,
            $note,
            $cook,
            $loginAccount,
            $img_id_str // 存入 MongoDB 的 ID
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
?>
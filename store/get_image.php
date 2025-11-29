<?php
// get_image.php
// 1. 確保沒有快取
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 2. 引入資料庫 (注意路徑是否正確)
require_once "../db_mongo.php"; 

if (isset($_GET['id']) && !empty($_GET['id'])) {
    // 3. 清除緩衝區 (這行最關鍵，可以刪除前面意外輸出的空白)
    if (ob_get_length()) ob_clean();
    
    try {
        $id = $_GET['id'];
        
        // 簡單驗證 ID 格式
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            die("Error: Invalid ID format");
        }

        $filter = ['_id' => new MongoDB\BSON\ObjectId($id)];
        $query = new MongoDB\Driver\Query($filter);
        
        // ★★★ 請確認這邊的 db 名稱跟 store_menu_add.php 裡的一樣 ★★★
        $cursor = $manager->executeQuery('store_db.menu_images', $query);
        
        $iterator = new IteratorIterator($cursor);
        $iterator->rewind();
        
        if ($document = $iterator->current()) {
            $b64 = $document->image_base64;
            
            // 處理 Base64 前綴 (data:image/png;base64,...)
            if (strpos($b64, ',') !== false) {
                $parts = explode(',', $b64);
                $b64Data = end($parts);
            } else {
                $b64Data = $b64;
            }

            // 解碼
            $image_data = base64_decode($b64Data);
            
            if ($image_data === false) {
                die("Error: Base64 decode failed");
            }

            // 判斷並輸出 Header
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($image_data);
            
            header("Content-Type: " . $mime);
            echo $image_data;
            exit;
        } else {
            http_response_code(404);
            die("Error: Image not found in MongoDB");
        }
    } catch (Exception $e) {
        http_response_code(500);
        die("Error: " . $e->getMessage());
    }
} else {
    die("Error: No ID provided");
}
?>
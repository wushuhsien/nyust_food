<?php
session_start();
include "../db.php";
include "../db_mongo.php"; // ★ 1. 務必引入 MongoDB 連線

$loginAccount = $_SESSION['user'] ?? '';

if (!$loginAccount) {
    echo "請先登入";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ==========================================
    // 1. 刪除單一品項 (順便刪除對應圖片)
    // ==========================================
    if ($action === 'delete_item') {
        $menu_id = intval($_POST['menu_id']);
        
        // A. 先查詢該品項有沒有 img_id
        $imgIdToDelete = null;
        $sqlGet = "SELECT img_id FROM menu WHERE menu_id = ? AND account = ?";
        if ($stmtGet = $link->prepare($sqlGet)) {
            $stmtGet->bind_param("is", $menu_id, $loginAccount);
            $stmtGet->execute();
            $stmtGet->bind_result($fetchedImgId);
            if ($stmtGet->fetch()) {
                $imgIdToDelete = $fetchedImgId;
            }
            $stmtGet->close();
        }

        // B. 執行 MySQL 刪除
        $sql = "DELETE FROM menu WHERE menu_id = ? AND account = ?";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("is", $menu_id, $loginAccount);
        
        try {
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    
                    // ★ C. MySQL 刪除成功後，若有圖片 ID，則刪除 MongoDB 資料
                    if (!empty($imgIdToDelete)) {
                        try {
                            $bulk = new MongoDB\Driver\BulkWrite;
                            $bulk->delete(['_id' => new MongoDB\BSON\ObjectId($imgIdToDelete)]);
                            $manager->executeBulkWrite('store_db.menu_images', $bulk);
                        } catch (Exception $e) {
                            // 圖片刪除失敗不影響流程，可記錄 log
                        }
                    }

                    echo "品項已刪除";
                } else {
                    echo "刪除失敗或無此權限";
                }
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1451) {
                echo "無法刪除！\n\n原因：此品項已有銷售紀錄 (存在於歷史訂單中)。\n為了保留帳務完整，資料庫禁止刪除。\n\n建議：請將「庫存」設為 0 即可停止販售。";
            } else {
                echo "發生錯誤：" . $e->getMessage();
            }
        }
        $stmt->close();

    // ==========================================
    // 2. 刪除整系列 (並刪除該系列所有圖片)
    // ==========================================
    } elseif ($action === 'delete_type') {
        $type_name = $_POST['type_name'] ?? '';
        
        if ($type_name) {
            
            // ★ A. 先把這個系列底下「所有的 img_id」都撈出來存著
            $imgIdsToDelete = [];
            $sqlGetTypeImgs = "SELECT img_id FROM menu WHERE type = ? AND account = ? AND img_id IS NOT NULL";
            if ($stmtGet = $link->prepare($sqlGetTypeImgs)) {
                $stmtGet->bind_param("ss", $type_name, $loginAccount);
                $stmtGet->execute();
                $res = $stmtGet->get_result();
                while ($row = $res->fetch_assoc()) {
                    if (!empty($row['img_id'])) {
                        $imgIdsToDelete[] = $row['img_id'];
                    }
                }
                $stmtGet->close();
            }

            // B. 執行 MySQL 刪除
            $sql = "DELETE FROM menu WHERE type = ? AND account = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("ss", $type_name, $loginAccount);
            
            try {
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        
                        // ★ C. MySQL 刪除成功後，批次刪除 MongoDB 裡的圖片
                        if (!empty($imgIdsToDelete)) {
                            try {
                                $bulk = new MongoDB\Driver\BulkWrite;
                                foreach ($imgIdsToDelete as $idStr) {
                                    // 檢查 ID 格式是否正確再放入刪除佇列
                                    if (preg_match('/^[a-f\d]{24}$/i', $idStr)) {
                                        $bulk->delete(['_id' => new MongoDB\BSON\ObjectId($idStr)]);
                                    }
                                }
                                // 一次執行所有刪除動作
                                $manager->executeBulkWrite('store_db.menu_images', $bulk);
                            } catch (Exception $e) {
                                // 忽略圖片刪除錯誤
                            }
                        }

                        echo "系列「" . htmlspecialchars($type_name) . "」及其所有品項(含圖片)已刪除";
                    } else {
                        echo "找不到該系列或已被刪除";
                    }
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1451) {
                    echo "無法刪除此系列！\n\n原因：系列中包含已有銷售紀錄的商品。\n請改為個別調整商品庫存。";
                } else {
                    echo "發生錯誤：" . $e->getMessage();
                }
            }
            $stmt->close();
        } else {
            echo "無效的系列名稱";
        }
    }
}
?>
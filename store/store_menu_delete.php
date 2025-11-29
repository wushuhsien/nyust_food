<?php
session_start();
include "../db.php"; 

$loginAccount = $_SESSION['user'] ?? '';

if (!$loginAccount) {
    echo "請先登入";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. 刪除單一品項
    if ($action === 'delete_item') {
        $menu_id = intval($_POST['menu_id']);
        
        $sql = "DELETE FROM menu WHERE menu_id = ? AND account = ?";
        $stmt = $link->prepare($sql);
        $stmt->bind_param("is", $menu_id, $loginAccount);
        
        // ★ 修改：加入 try-catch 來捕捉外鍵錯誤
        try {
            if ($stmt->execute()) {
                // 檢查是否有刪除到資料 (affected_rows)
                if ($stmt->affected_rows > 0) {
                    echo "品項已刪除";
                } else {
                    echo "刪除失敗或無此權限";
                }
            }
        } catch (mysqli_sql_exception $e) {
            // 錯誤代碼 1451 代表 Foreign Key 限制
            if ($e->getCode() == 1451) {
                echo "無法刪除！\n\n原因：此品項已有銷售紀錄 (存在於歷史訂單中)。\n為了保留帳務完整，資料庫禁止刪除。\n\n建議：請將「庫存」設為 0 即可停止販售。";
            } else {
                echo "發生錯誤：" . $e->getMessage();
            }
        }
        $stmt->close();

    // 2. 刪除整系列
    } elseif ($action === 'delete_type') {
        $type_name = $_POST['type_name'] ?? '';
        
        if ($type_name) {
            $sql = "DELETE FROM menu WHERE type = ? AND account = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("ss", $type_name, $loginAccount);
            
            // ★ 修改：加入 try-catch
            try {
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo "系列「" . htmlspecialchars($type_name) . "」及其所有品項已刪除";
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
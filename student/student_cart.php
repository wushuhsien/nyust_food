<?php
session_start();
include "../db.php";

// 設定時區為台灣時間
date_default_timezone_set("Asia/Taipei");

$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
if (!$account) {
    echo "<script>alert('請先登入'); window.location='../login.html';</script>";
    exit;
}

// 初始化購物車
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. 處理購物車內容修改 (增加/減少/刪除)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    if (isset($_SESSION['cart'][$id])) {
        if ($action === 'add') {
            $_SESSION['cart'][$id]++;
        } elseif ($action === 'minus') {
            $_SESSION['cart'][$id]--;
            if ($_SESSION['cart'][$id] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$id]);
        }
    }
    // 重新導向回購物車
    header("Location: student_cart.php");
    exit;
}

// 2. 處理單一店家結帳送出
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_store'])) {
    
    // 接收該店家的資訊
    $target_store_account = $_POST['store_account']; // 店家帳號
    $user_pick_time = $_POST['pick_time'];           // 使用者選的時間
    $user_note = $_POST['note'];                     // 備註
    
    // 再次確認購物車有東西
    if (empty($_SESSION['cart'])) {
        echo "<script>alert('購物車是空的');</script>";
    } else {
        $link->begin_transaction();
        try {
            // 2-1. 找出購物車內屬於「這家店」的所有商品 ID
            $cart_ids = implode(',', array_keys($_SESSION['cart']));
            
            // 撈取屬於該店家的品項
            $sql = "SELECT m.* FROM menu m WHERE m.menu_id IN ($cart_ids) AND m.account = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("s", $target_store_account);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items_to_order = [];
            $max_cook_seconds = 0;

            // 整理要下單的商品並計算時間
            while ($row = $result->fetch_assoc()) {
                $m_id = $row['menu_id'];
                $qty = $_SESSION['cart'][$m_id];
                
                // 存入陣列待會寫入 orderitem
                $row['buy_qty'] = $qty; 
                $items_to_order[] = $row;

                // 計算最大烹煮時間
                $time_parts = explode(':', $row['cook_time']);
                $seconds = ($time_parts[0] * 3600) + ($time_parts[1] * 60) + $time_parts[2];
                if ($seconds > $max_cook_seconds) {
                    $max_cook_seconds = $seconds;
                }
            }
            $stmt->close();

            if (empty($items_to_order)) {
                throw new Exception("找不到該店家的商品，可能已被移除");
            }

            // ★ 修改重點：撈取該店家的「今日營業時間」以計算正確的基準時間
            $current_weekday = date('w');
            $sql_hours = "SELECT open_time FROM storehours WHERE account = ? AND weekday = ?";
            $stmt_hours = $link->prepare($sql_hours);
            $stmt_hours->bind_param("si", $target_store_account, $current_weekday);
            $stmt_hours->execute();
            $res_hours = $stmt_hours->get_result();
            
            $today_open_time_str = "00:00:00"; // 預設值
            if ($row_h = $res_hours->fetch_assoc()) {
                $today_open_time_str = $row_h['open_time'];
            }
            $stmt_hours->close();

            // 計算時間戳記
            $today_open_timestamp = strtotime(date('Y-m-d') . ' ' . $today_open_time_str);
            $current_timestamp = time();

            // ★ 核心邏輯：基準時間 = MAX(現在時間, 今日開店時間)
            // 如果現在 8:45，開店 9:00 -> 基準時間為 9:00
            // 如果現在 9:10，開店 9:00 -> 基準時間為 9:10
            $base_timestamp = max($current_timestamp, $today_open_timestamp);

            // 預估系統最快完成時間 = 基準時間 + 製作時間
            $estimate_timestamp = $base_timestamp + $max_cook_seconds;
            $estimate_time = date('Y-m-d H:i:s', $estimate_timestamp);
            
            $user_pick_timestamp = strtotime($user_pick_time);

            // 驗證：使用者選的時間不能早於系統計算出的最快完成時間
            if ($user_pick_timestamp < $estimate_timestamp) {
                // 為了友善顯示，轉成 HH:mm 格式提示
                $min_time_str = date('H:i', $estimate_timestamp);
                echo "<script>
                    alert('您選擇的取餐時間太早了！\\n依據店家營業時間與製作時間，此訂單最快需於 {$min_time_str} 後才能取餐。\\n請重新選擇時間。');
                    history.back(); 
                </script>";
                exit; // 停止程式，不進行資料庫寫入
            }

            // 2-3. 寫入 order 主表
            // 修改：使用 $user_pick_time (使用者選擇的時間) 作為預估時間存入 estimate_time 欄位
            // 或者你可以維持存入系統計算的 $estimate_time，看你的需求。
            // 這裡依照上一輪的修正，我們將 estimate_time 欄位存入「使用者選擇的時間」
            $order_sql = "INSERT INTO `order` (estimate_time, status, note, payment, account) 
                          VALUES (?, ?, ?, ?, ?)";
            $status = "等待店家接單";
            $payment = "現金"; 
            
            $stmt = $link->prepare($order_sql);
            $stmt->bind_param("sssss", $user_pick_time, $status, $user_note, $payment, $account);
            
            if (!$stmt->execute()) {
                throw new Exception("訂單建立失敗");
            }
            $new_order_id = $link->insert_id;
            $stmt->close();

            // 2-4. 寫入 orderitem 並 ★ 扣除庫存
            $item_sql = "INSERT INTO orderitem (quantity, note, menu_id, order_id) VALUES (?, ?, ?, ?)";
            $stmt_item = $link->prepare($item_sql);

            // ★ 準備扣庫存的 SQL (庫存 - 數量, 銷量 + 數量)
            // 加上 AND stock >= ? 確保不會扣到變成負數
            $stock_sql = "UPDATE menu SET stock = stock - ?, sale_amount = sale_amount + ? WHERE menu_id = ? AND stock >= ?";
            $stmt_stock = $link->prepare($stock_sql);
            
            foreach ($items_to_order as $item) {
                $buy_qty = $item['buy_qty'];
                $menu_id = $item['menu_id'];
                $item_note = ""; 

                // A. 寫入訂單明細
                $stmt_item->bind_param("isii", $buy_qty, $item_note, $menu_id, $new_order_id);
                $stmt_item->execute();
                
                // B. ★ 扣除庫存 & 增加銷量
                $stmt_stock->bind_param("iiii", $buy_qty, $buy_qty, $menu_id, $buy_qty);
                $stmt_stock->execute();

                // 檢查是否扣除成功 (如果 affected_rows 是 0，代表庫存不足)
                if ($stmt_stock->affected_rows === 0) {
                    throw new Exception("商品「" . $item['name'] . "」庫存不足，無法結帳。");
                }

                // C. 從 Session 移除該商品
                unset($_SESSION['cart'][$menu_id]);
            }
            $stmt_item->close();
            $stmt_stock->close();

            $link->commit();
            echo "<script>alert('訂單送出成功！'); window.location='student_cart.php';</script>";

        } catch (Exception $e) {
            $link->rollback();
            echo "<script>alert('訂單送出失敗：" . $e->getMessage() . "'); history.back();</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>我的購物車</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; background: #f2f6fc; margin: 20px; }
        
        .cart-wrapper {
            max-width: 900px; margin: 0 auto; 
        }
        
        .cart-header {
            background: #4a90e2; color: white; padding: 20px; border-radius: 10px 10px 0 0;
            font-size: 24px; font-weight: bold; display: flex; align-items: center; gap: 10px;
        }

        .store-card {
            background: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            margin-bottom: 30px; 
            overflow: hidden;
            border: 1px solid #dcebfc;
        }

        .store-title-bar {
            background-color: #f0f7ff;
            padding: 15px 20px;
            border-bottom: 2px solid #4a90e2;
            font-size: 18px; font-weight: bold; color: #333;
            display: flex; align-items: center; gap: 10px;
        }

        .cart-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 20px; border-bottom: 1px solid #eee;
        }

        .item-info { flex: 1; }
        .item-name { font-size: 16px; font-weight: bold; color: #2c3e50; }
        .item-price { color: #888; font-size: 14px; margin-top: 4px;}
        
        .item-action { display: flex; align-items: center; gap: 10px; }
        .qty-control {
            display: flex; align-items: center; border: 1px solid #ddd; border-radius: 20px; padding: 2px 8px;
        }
        .qty-btn {
            text-decoration: none; color: #4a90e2; font-weight: bold; font-size: 18px; padding: 0 8px;
        }
        .qty-val { font-weight: bold; color: #333; margin: 0 5px; }
        .btn-trash { color: #e74c3c; cursor: pointer; text-decoration: none; font-size: 18px;}

        .store-footer {
            padding: 20px; background: #fffcf5; border-top: 1px solid #eee;
        }

        .total-row {
            text-align: right; font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #555;
        }
        .total-price { color: #e74c3c; font-size: 22px; }

        .form-row {
            display: flex; gap: 20px; margin-bottom: 15px;
        }
        .form-group { flex: 1; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; font-size: 14px;}
        .form-group input, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;
            box-sizing: border-box; font-size: 15px;
        }

        .checkout-btn {
            width: 100%; padding: 12px; background: #27ae60; color: white;
            border: none; border-radius: 5px; font-size: 16px; cursor: pointer;
            transition: 0.3s; display: flex; justify-content: center; align-items: center; gap: 8px;
        }
        .checkout-btn:hover { background: #219150; }
        
        .empty-cart { 
            background: white; border-radius: 10px; padding: 50px; text-align: center; color: #999; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <?php include "student_menu.php"; ?>
    <br>

    <div class="cart-wrapper">
        <div class="cart-header">
            <i class="bi bi-cart-check"></i> 我的購物車
        </div>

        <?php
        // 讀取購物車 Session
        $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

        if (empty($cart)): ?>
            <div class="empty-cart">
                <i class="bi bi-basket" style="font-size: 50px;"></i>
                <p>購物車目前沒有商品</p>
                <a href="student_menumanage.php" style="color: #4a90e2; text-decoration: none; font-weight: bold;">前往點餐</a>
            </div>
        <?php else: 
            // 撈取資料庫並依店家分組
            $ids = implode(',', array_keys($cart));
            // 為了要分開送出，我們需要選取 store.account (作為識別店家的 Key)
            $sql = "SELECT m.*, s.name as store_name, s.account as store_account 
                    FROM menu m 
                    JOIN store s ON m.account = s.account 
                    WHERE m.menu_id IN ($ids) 
                    ORDER BY s.store_id";
            $result = $link->query($sql);

            $grouped_items = [];
            
            while ($row = $result->fetch_assoc()) {
                $m_id = $row['menu_id'];
                // 確保 session 還有這個 key (防呆)
                if(isset($cart[$m_id])) {
                    $row['qty'] = $cart[$m_id];
                    $row['subtotal'] = $row['price'] * $cart[$m_id];
                    // 使用 store_account 作為分組 key
                    $grouped_items[$row['store_account']][] = $row; 
                }
            }
        ?>

        <?php foreach ($grouped_items as $store_account => $items): 
            $store_name = $items[0]['store_name'];
            $store_total = 0;
            $max_cook_seconds = 0;
            
            // 計算該店總金額與最大烹煮時間
            foreach($items as $item) {
                $store_total += $item['subtotal'];
                $time_parts = explode(':', $item['cook_time']);
                $seconds = ($time_parts[0] * 3600) + ($time_parts[1] * 60) + $time_parts[2];
                if ($seconds > $max_cook_seconds) $max_cook_seconds = $seconds;
            }

            // ★ 修改重點：撈取該店家的「今日營業時間」
            // 目的是為了讓前端 input 預設值顯示「MAX(現在, 開店) + 製作時間」
            $current_weekday = date('w');
            $sql_h = "SELECT open_time FROM storehours WHERE account = ? AND weekday = ?";
            $stmt_h = $link->prepare($sql_h);
            $stmt_h->bind_param("si", $store_account, $current_weekday);
            $stmt_h->execute();
            $res_h = $stmt_h->get_result();
            
            $today_open_ts = time(); // 預設值為現在
            if($row_h = $res_h->fetch_assoc()){
                // 將店家今天的 open_time 轉為 Timestamp
                $today_open_ts = strtotime(date('Y-m-d').' '.$row_h['open_time']);
            }
            $stmt_h->close();

            // 計算基準時間：如果現在還沒開門，就從開門時間算起
            $base_ts = max(time(), $today_open_ts);
            
            // 預計完成時間 = 基準時間 + 製作時間
            $final_predict_ts = $base_ts + $max_cook_seconds;

            $suggested_time_str = date('Y-m-d\TH:i', $final_predict_ts);
            $display_time_hint = date('H:i', $final_predict_ts);
        ?>
            
            <form method="POST" action="student_cart.php">
                <input type="hidden" name="store_account" value="<?= htmlspecialchars($store_account) ?>">
                
                <div class="store-card">
                    <div class="store-title-bar">
                        <i class="bi bi-shop-window"></i> <?= htmlspecialchars($store_name) ?>
                    </div>

                    <div>
                        <?php foreach ($items as $item): ?>
                            <div class="cart-item">
                                <div class="item-info">
                                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="item-price">$<?= intval($item['price']) ?></div>
                                </div>
                                <div class="item-action">
                                    <div class="qty-control">
                                        <a href="student_cart.php?action=minus&id=<?= $item['menu_id'] ?>" class="qty-btn">−</a>
                                        <span class="qty-val"><?= $item['qty'] ?></span>
                                        <a href="student_cart.php?action=add&id=<?= $item['menu_id'] ?>" class="qty-btn">+</a>
                                    </div>
                                    <div style="font-weight:bold; width: 60px; text-align:right;">
                                        $<?= $item['subtotal'] ?>
                                    </div>
                                    <a href="student_cart.php?action=remove&id=<?= $item['menu_id'] ?>" class="btn-trash" onclick="return confirm('確定要移除嗎？')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="store-footer">
                        <div class="total-row">
                            小計：<span class="total-price">$<?= $store_total ?></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="pick_time_<?= $store_account ?>">
                                    <i class="bi bi-clock"></i> 取餐時間 (需 <?= ceil($max_cook_seconds/60) ?> 分鐘)
                                </label>
                                <input type="datetime-local" 
                                       id="pick_time_<?= $store_account ?>" 
                                       name="pick_time" 
                                       value="<?= $suggested_time_str ?>" 
                                       min="<?= date('Y-m-d\TH:i') ?>"
                                       required>
                                <small style="color: #e74c3c;">* 系統建議 <?= $display_time_hint ?> 後取餐</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="note_<?= $store_account ?>"><i class="bi bi-pencil"></i> 備註</label>
                                <input type="text" 
                                       id="note_<?= $store_account ?>" 
                                       name="note" 
                                       placeholder="例：不要香菜、去冰">
                            </div>
                        </div>

                        <button type="submit" name="checkout_store" class="checkout-btn">
                            送出 <?= htmlspecialchars($store_name) ?> 的訂單 <i class="bi bi-arrow-right-circle"></i>
                        </button>
                    </div>
                </div>
            </form>

        <?php endforeach; ?>

        <?php endif; ?>
    </div>

</body>
</html>
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
    
    $target_store_account = $_POST['store_account'];
    $user_pick_time = $_POST['pick_time'];
    $user_note = $_POST['note'];
    
    if (empty($_SESSION['cart'])) {
        echo "<script>alert('購物車是空的');</script>";
    } else {
        $link->begin_transaction();
        try {
            // 2-1. 找出購物車內商品
            $cart_ids = implode(',', array_keys($_SESSION['cart']));
            $sql = "SELECT m.* FROM menu m WHERE m.menu_id IN ($cart_ids) AND m.account = ?";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("s", $target_store_account);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items_to_order = [];
            $max_cook_seconds = 0;

            while ($row = $result->fetch_assoc()) {
                $m_id = $row['menu_id'];
                $qty = $_SESSION['cart'][$m_id];
                $row['buy_qty'] = $qty; 
                $items_to_order[] = $row;

                // 計算製作時間 (時:分:秒 轉 秒)
                $time_parts = explode(':', $row['cook_time']);
                $seconds = ($time_parts[0] * 3600) + ($time_parts[1] * 60) + $time_parts[2];
                // 取所有商品中製作時間最長的
                if ($seconds > $max_cook_seconds) {
                    $max_cook_seconds = $seconds;
                }
            }
            $stmt->close();

            if (empty($items_to_order)) {
                throw new Exception("找不到該店家的商品，可能已被移除");
            }

            // 2-2. 驗證時間 (後端雙重檢查)
            $current_weekday = date('w');
            if ($current_weekday == 0) $current_weekday = 7; // 修正星期日為 7

            $sql_hours = "SELECT open_time, close_time FROM storehours WHERE account = ? AND weekday = ?";
            $stmt_hours = $link->prepare($sql_hours);
            $stmt_hours->bind_param("si", $target_store_account, $current_weekday);
            $stmt_hours->execute();
            $res_hours = $stmt_hours->get_result();
            
            $today_open_time_str = "00:00:00"; 
            $today_close_time_str = "23:59:59"; 
            $has_hours = false;
            
            if ($row_h = $res_hours->fetch_assoc()) {
                $today_open_time_str = $row_h['open_time'];
                $today_close_time_str = $row_h['close_time'];
                $has_hours = true;
            }
            $stmt_hours->close();

            if (!$has_hours) {
                throw new Exception("該店家今日未營業，無法送出訂單。");
            }

            // 計算時間戳記
            $today_open_timestamp = strtotime(date('Y-m-d') . ' ' . $today_open_time_str);
            $today_close_timestamp = strtotime(date('Y-m-d') . ' ' . $today_close_time_str);
            $current_timestamp = time();

            // ★ 核心邏輯：基準時間 = MAX(現在時間, 今日開店時間)
            // 如果現在還沒開店，就從開店時間開始算；如果已經開店，就從現在開始算
            $base_timestamp = max($current_timestamp, $today_open_timestamp);

            // 系統最早可取餐時間 = 基準時間 + 製作時間
            $min_allowed_timestamp = $base_timestamp + $max_cook_seconds;
            $user_pick_timestamp = strtotime($user_pick_time);

            // 驗證 A：取餐時間不能早於 (開店時間 + 製作時間) 或 (現在 + 製作時間)
            if ($user_pick_timestamp < $min_allowed_timestamp) {
                $min_time_str = date('H:i', $min_allowed_timestamp);
                echo "<script>
                    alert('您選擇的取餐時間太早了！\\n依據店家營業時間與製作時間，此訂單最快需於 {$min_time_str} 後才能取餐。');
                    history.back(); 
                </script>";
                exit; 
            }

            // 驗證 B：取餐時間不能晚於打烊時間
            if ($user_pick_timestamp > $today_close_timestamp) {
                $close_time_str = date('H:i', $today_close_timestamp);
                echo "<script>
                    alert('您選擇的取餐時間已超過店家打烊時間 ({$close_time_str})！\\n請選擇較早的時間或改天訂購。');
                    history.back(); 
                </script>";
                exit;
            }

            // 2-3. 寫入 order 主表 (已移除 payment)
            $order_sql = "INSERT INTO `order` (estimate_time, status, note, account) 
                          VALUES (?, ?, ?, ?)";
            $status = "等待店家接單";
            
            $stmt = $link->prepare($order_sql);
            $stmt->bind_param("ssss", $user_pick_time, $status, $user_note, $account);
            
            if (!$stmt->execute()) {
                throw new Exception("訂單建立失敗");
            }
            $new_order_id = $link->insert_id;
            $stmt->close();

            // 2-4. 寫入 orderitem 並扣庫存
            $item_sql = "INSERT INTO orderitem (quantity, note, menu_id, order_id) VALUES (?, ?, ?, ?)";
            $stmt_item = $link->prepare($item_sql);

            $stock_sql = "UPDATE menu SET stock = stock - ?, sale_amount = sale_amount + ? WHERE menu_id = ? AND stock >= ?";
            $stmt_stock = $link->prepare($stock_sql);
            
            foreach ($items_to_order as $item) {
                $buy_qty = $item['buy_qty'];
                $menu_id = $item['menu_id'];
                $item_note = ""; 

                $stmt_item->bind_param("isii", $buy_qty, $item_note, $menu_id, $new_order_id);
                $stmt_item->execute();
                
                $stmt_stock->bind_param("iiii", $buy_qty, $buy_qty, $menu_id, $buy_qty);
                $stmt_stock->execute();

                if ($stmt_stock->affected_rows === 0) {
                    throw new Exception("商品「" . $item['name'] . "」庫存不足。");
                }

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
        
        .error-msg {
            color: #721c24; background-color: #f8d7da; border-color: #f5c6cb;
            padding: 10px; border-radius: 5px; margin-bottom: 10px; font-size: 14px;
            display: flex; align-items: center; gap: 8px;
        }

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
            $sql = "SELECT m.*, s.name as store_name, s.account as store_account 
                    FROM menu m 
                    JOIN store s ON m.account = s.account 
                    WHERE m.menu_id IN ($ids) 
                    ORDER BY s.store_id";
            $result = $link->query($sql);

            $grouped_items = [];
            
            while ($row = $result->fetch_assoc()) {
                $m_id = $row['menu_id'];
                if(isset($cart[$m_id])) {
                    $row['qty'] = $cart[$m_id];
                    $row['subtotal'] = $row['price'] * $cart[$m_id];
                    $grouped_items[$row['store_account']][] = $row; 
                }
            }
        ?>

        <?php foreach ($grouped_items as $store_account => $items): 
            $store_name = $items[0]['store_name'];
            $store_total = 0;
            $max_cook_seconds = 0;
            
            foreach($items as $item) {
                $store_total += $item['subtotal'];
                // 計算這道菜的製作時間
                $time_parts = explode(':', $item['cook_time']);
                $seconds = ($time_parts[0] * 3600) + ($time_parts[1] * 60) + $time_parts[2];
                // 找出該筆訂單中製作時間最久的菜 (因為可以同時做)
                if ($seconds > $max_cook_seconds) $max_cook_seconds = $seconds;
            }

            // 前端顯示計算：撈取該店家的開店與打烊時間
            $current_weekday = date('w');
            if ($current_weekday == 0) $current_weekday = 7; // 修正星期日為 7

            $sql_h = "SELECT open_time, close_time FROM storehours WHERE account = ? AND weekday = ?";
            $stmt_h = $link->prepare($sql_h);
            $stmt_h->bind_param("si", $store_account, $current_weekday);
            $stmt_h->execute();
            $res_h = $stmt_h->get_result();
            
            $today_open_ts = time(); 
            $today_close_ts = strtotime(date('Y-m-d') . ' 23:59:59'); // 預設打烊時間
            $is_store_open = false; // 預設沒營業
            
            if($row_h = $res_h->fetch_assoc()){
                $today_open_ts = strtotime(date('Y-m-d').' '.$row_h['open_time']);
                $today_close_ts = strtotime(date('Y-m-d').' '.$row_h['close_time']);
                $is_store_open = true;
            }
            $stmt_h->close();

            // ★ 核心邏輯：基準時間 = MAX(現在時間, 今日開店時間)
            $base_ts = max(time(), $today_open_ts);
            
            // 最早可取餐時間 = 基準時間 + 製作時間
            $final_predict_ts = $base_ts + $max_cook_seconds;

            // 格式化顯示
            $suggested_time_str = date('Y-m-d\TH:i', $final_predict_ts); // 給 input value 用
            $display_time_hint = date('H:i', $final_predict_ts);         // 給提示文字用
            $close_time_hint = date('H:i', $today_close_ts);

            // 檢查：1. 是否超過打烊時間 2. 今日是否有營業
            $is_over_close_time = ($final_predict_ts > $today_close_ts);
            
            // 如果今日沒營業，也視為無法下單
            if (!$is_store_open) {
                $is_over_close_time = true; 
            }
        ?>
            
            <form method="POST" action="student_cart.php">
                <input type="hidden" name="store_account" value="<?= htmlspecialchars($store_account) ?>">
                
                <div class="store-card">
                    <div class="store-title-bar">
                        <i class="bi bi-shop-window"></i> <?= htmlspecialchars($store_name) ?>
                        <?php if(!$is_store_open): ?>
                            <span style="color:red; font-size:14px; margin-left:10px;">(今日未營業)</span>
                        <?php elseif($is_over_close_time): ?>
                            <span style="color:red; font-size:14px; margin-left:10px;">(已過接單時間)</span>
                        <?php endif; ?>
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
                                    
                                    min="<?= $suggested_time_str ?>"
                                    
                                    max="<?= date('Y-m-d\TH:i', $today_close_ts) ?>"
                                    required
                                    <?= ($is_over_close_time || !$is_store_open) ? 'disabled' : '' ?> >
                                
                                <small style="color: #e74c3c;">* 系統建議 <?= $display_time_hint ?> 後取餐</small><br>
                                <small style="color: #555;">(打烊時間：<?= $close_time_hint ?>)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="note_<?= $store_account ?>"><i class="bi bi-pencil"></i> 備註</label>
                                <input type="text" 
                                     id="note_<?= $store_account ?>" 
                                     name="note" 
                                     placeholder="例：不要香菜、去冰"
                                     <?= ($is_over_close_time || !$is_store_open) ? 'disabled' : '' ?> >
                            </div>
                        </div>

                        <?php if (!$is_store_open): ?>
                            <div class="error-msg">
                                <i class="bi bi-exclamation-octagon"></i> 該店家今日未營業，無法送出訂單。
                            </div>
                            <button type="button" class="checkout-btn" style="background:#ccc; cursor:not-allowed;" disabled>
                                無法送出
                            </button>
                        <?php elseif ($is_over_close_time): ?>
                            <div class="error-msg">
                                <i class="bi bi-exclamation-triangle"></i> 預計完成時間已超過打烊時間，無法送出訂單。
                            </div>
                            <button type="button" class="checkout-btn" style="background:#ccc; cursor:not-allowed;" disabled>
                                無法送出
                            </button>
                        <?php else: ?>
                            <button type="submit" name="checkout_store" class="checkout-btn">
                                送出 <?= htmlspecialchars($store_name) ?> 的訂單 <i class="bi bi-arrow-right-circle"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

        <?php endforeach; ?>

        <?php endif; ?>
    </div>

</body>
</html>
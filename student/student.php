<?php
session_start();
include "../db.php";

$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
if (!$account) {
    echo "<script>alert('請先登入'); window.location='../login.html';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>學生/教職員首頁</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            font-family: "Microsoft JhengHei", Arial, sans-serif;
            background: #f2f6fc;
            margin: 20px;
        }

        #b {
            background-color: transparent;
            margin: 20px auto;
            max-width: 1200px;
        }

        #b h1 {
            font-size: 22px;
            margin-top: 0;
            color: #4a90e2;
        }

        /* ====== 公告與區塊通用樣式 ====== */
        .dashboard-card {
            width: 450px;
            border: 2px solid #4a90e2;
            border-radius: 10px;
            padding: 15px;
            background-color: #f9fbff;
            max-height: 500px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .dashboard-title {
            text-align: center;
            color: #005AB5;
            margin-top: 0;
            border-bottom: 2px solid #dcebfc;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .announcement {
            background: #e8f3ff;
            border: 1px solid #4a90e2;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
            box-shadow: 1px 1px 6px rgba(0, 0, 0, 0.05);
        }

        .announcement p {
            margin: 6px 0;
            line-height: 1.5;
        }

        /* ====== 未取餐訂單樣式 (Accordion) ====== */
        .order-card {
            margin-bottom: 10px;
            border: 1px solid #4a90e2;
            border-radius: 8px;
            background: white;
            overflow: hidden;
        }

        details {
            width: 100%;
        }

        summary {
            padding: 15px;
            background-color: #e8f3ff;
            cursor: pointer;
            font-weight: bold;
            color: #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
            list-style: none;
        }

        summary::-webkit-details-marker {
            display: none;
        }

        summary::after {
            content: '\F282';
            font-family: 'bootstrap-icons';
            transition: 0.3s;
        }

        details[open] summary::after {
            transform: rotate(180deg);
        }

        .order-content {
            padding: 15px;
            border-top: 1px solid #eee;
            background-color: #fff;
        }

        .order-item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            font-size: 14px;
        }

        .order-item-row:last-child {
            border-bottom: none;
        }

        .status-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .btn-order-more {
            display: block;
            width: 95%;
            text-align: center;
            background: #4a90e2;
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 10px;
            font-weight: bold;
        }

        .btn-order-more:hover {
            background: #357abd;
        }

        /* 訂單備註樣式 */
        .order-note-box {
            font-size: 13px;
            color: #856404;
            background-color: #fffcf0;
            border: 1px solid #ffeeba;
            padding: 8px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include "student_menu.php"; ?>

    <div id="b" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">

        <div class="dashboard-card">
            <h2 class="dashboard-title"><i class="bi bi-receipt"></i> 進行中的訂單</h2>

            <?php
            // 1. 先撈出該學生最近 48 小時內的所有未完成/進行中訂單
            // (時間範圍放寬到 48 小時，以免漏掉跨日訂單，精確過濾由 PHP 處理)
            $sql_orders = "
                SELECT 
                    o.order_id, 
                    o.status, 
                    o.estimate_time, 
                    o.note AS order_note, 
                    s.name AS store_name, 
                    s.account AS store_account, /* 多撈 store_account 用來查營業時間 */
                    m.name AS menu_name, 
                    m.price,
                    oi.quantity, 
                    oi.note AS item_note
                FROM `order` o
                JOIN `orderitem` oi ON o.order_id = oi.order_id
                JOIN `menu` m ON oi.menu_id = m.menu_id
                JOIN `store` s ON m.account = s.account
                WHERE o.account = ? 
                AND o.status NOT IN ('已完成', '已取消', '商家拒單', '已取餐')
                AND o.estimate_time >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                ORDER BY o.order_id DESC
            ";

            $stmt = $link->prepare($sql_orders);
            $stmt->bind_param("s", $account);
            $stmt->execute();
            $result = $stmt->get_result();

            $active_orders = [];
            
            // 用來快取店家營業時間，避免同一家店重複查詢資料庫多次
            $store_hours_cache = []; 
            date_default_timezone_set("Asia/Taipei");

            while ($row = $result->fetch_assoc()) {
                $store_acc = $row['store_account'];
                $estimate_time = $row['estimate_time'];

                // --- 智慧過濾邏輯 START ---
                // 1. 如果快取沒資料，去撈該店家的營業時間
                if (!isset($store_hours_cache[$store_acc])) {
                    $w = date('N'); // 今天星期幾
                    $q_hours = "SELECT open_time, close_time FROM storehours WHERE account = '$store_acc' AND weekday = $w";
                    $res_h = $link->query($q_hours);
                    $store_hours_cache[$store_acc] = $res_h->fetch_assoc();
                }

                $hours = $store_hours_cache[$store_acc];
                $show_order = true; // 預設顯示

                if ($hours) {
                    $open = $hours['open_time'];
                    $close = $hours['close_time'];
                    $shift_start = '';
                    $current_time_str = date('H:i:s');

                    // 計算該店當前營業班次的「起始時間」
                    if ($close < $open) { // 跨日 (例如 18:00 - 02:00)
                        if ($current_time_str < $close) {
                            // 現在是凌晨 (例如 01:00)，班次起始時間是昨天 18:00
                            $shift_start = date('Y-m-d H:i:s', strtotime("yesterday $open"));
                        } else {
                            // 現在是晚上 (例如 23:00)，班次起始時間是今天 18:00
                            $shift_start = date('Y-m-d H:i:s', strtotime("today $open"));
                        }
                    } else { // 一般 (例如 10:00 - 22:00)
                        $shift_start = date('Y-m-d H:i:s', strtotime("today $open -1 hour"));
                    }

                    // 比較：如果這筆訂單的預計時間，早於目前的班次起始時間，代表這是「上一班」的舊單，不顯示
                    if ($estimate_time < $shift_start) {
                        $show_order = false;
                    }
                }
                
                // 如果判斷不需要顯示，就跳過這筆資料
                if (!$show_order) continue; 
                // --- 智慧過濾邏輯 END ---

                // 組裝資料 (與原本邏輯相同)
                $oid = $row['order_id'];
                if (!isset($active_orders[$oid])) {
                    $active_orders[$oid] = [
                        'store_name' => $row['store_name'],
                        'status' => $row['status'],
                        'estimate_time' => $row['estimate_time'],
                        'order_note' => $row['order_note'],
                        'items' => [],
                        'total_price' => 0
                    ];
                }
                $active_orders[$oid]['items'][] = [
                    'name' => $row['menu_name'],
                    'qty' => $row['quantity'],
                    'note' => $row['item_note']
                ];
                $active_orders[$oid]['total_price'] += ($row['price'] * $row['quantity']);
            }
            $stmt->close();

            // --- 顯示 HTML (與原本邏輯相同) ---
            if (!empty($active_orders)) {
                foreach ($active_orders as $order_id => $order) {
                    $time_display = date('Y/m/d H:i', strtotime($order['estimate_time']));
                    ?>
                    <div class="order-card">
                        <details>
                            <summary>
                                <div>
                                    <span style="font-size:18px; color:#4a90e2; margin-right:5px;">
                                        <?= htmlspecialchars($order['store_name']) ?>
                                    </span>
                                    <span class="status-badge"><?= htmlspecialchars($order['status']) ?></span>
                                </div>
                                <div style="font-size:14px; color:#666;">
                                    <i class="bi bi-clock"></i> 預計 <?= $time_display ?>
                                </div>
                            </summary>

                            <div class="order-content">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item-row">
                                        <span>
                                            <?= htmlspecialchars($item['name']) ?>
                                            <span style="color:#888;">x<?= $item['qty'] ?></span>
                                            <?php if ($item['note']): ?>
                                                <span style="font-size:12px; color:#e74c3c;">(<?= htmlspecialchars($item['note']) ?>)</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (!empty($order['order_note'])): ?>
                                    <div class="order-note-box">
                                        <i class="bi bi-pencil-fill"></i> 備註：<?= htmlspecialchars($order['order_note']) ?>
                                    </div>
                                <?php endif; ?>

                                <div style="text-align:right; margin-top:10px; font-weight:bold; color:#333;">
                                    總計：$<?= $order['total_price'] ?>
                                </div>
                            </div>
                        </details>
                    </div>
                    <?php
                }
            } else {
                echo '<div style="text-align:center; padding:30px; color:#888;">
                        <i class="bi bi-cup-hot" style="font-size:40px;"></i>
                        <p>目前沒有進行中的訂單</p>
                      </div>';
            }
            ?>

            <a href="student_menumanage.php" class="btn-order-more">
                <i class="bi bi-plus-circle"></i> 前往點餐
            </a>
        </div>

        <!-- <div class="dashboard-card">
            <h2 class="dashboard-title"><i class="bi bi-shop-window"></i> 店家公告</h2>
            <?php
            $sql_store = "SELECT a.topic, a.description, a.start_time, a.end_time, s.name AS store_name
              FROM announcement a
              JOIN store s ON a.account = s.account
              WHERE a.type = '店休'
                AND a.start_time <= NOW()
                AND a.end_time >= NOW()
              ORDER BY a.start_time DESC";

            $result_store = $link->query($sql_store);

            if ($result_store->num_rows > 0) {
                while ($row = $result_store->fetch_assoc()) {
                    echo '<div class="announcement">';
                    echo '<p style="color:#d35400; font-weight:bold;">' . htmlspecialchars($row['store_name']) . '</p>';
                    echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                    echo '<p style="font-size:14px; color:#555;">' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    echo '<p style="font-size:12px; color:#888; margin-top:5px;">' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p style='text-align:center; color:#888;'>目前沒有店家店休公告。</p>";
            }
            ?>
        </div> -->

        <div class="dashboard-card">
            <h2 class="dashboard-title"><i class="bi bi-megaphone"></i> 系統公告</h2>
            <?php
            $sql_admin = "SELECT topic, description, start_time, end_time
                      FROM announcement
                      WHERE type='公告'
                        AND start_time <= NOW()
                        AND end_time >= NOW()
                      ORDER BY start_time DESC";

            $result_admin = $link->query($sql_admin);

            if ($result_admin->num_rows > 0) {
                while ($row = $result_admin->fetch_assoc()) {
                    echo '<div class="announcement" style="background:#fffcf5; border-color:#f0ad4e;">';
                    echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                    echo '<p style="font-size:14px;">' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    echo '<p style="font-size:12px; color:#888; margin-top:5px;">' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p style='text-align:center; color:#888;'>目前沒有系統公告。</p>";
            }
            ?>
        </div>

    </div>
</body>

</html>
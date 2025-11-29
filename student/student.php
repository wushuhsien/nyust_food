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
            width: 100%;
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

    <div id="b" style="display: grid; grid-template-columns: 1fr 1fr 0.8fr; gap: 20px;">

        <div class="dashboard-card">
            <h2 class="dashboard-title"><i class="bi bi-receipt"></i> 進行中的訂單</h2>

            <?php
            // ★ 修改 1: 在 SQL 中加入 o.note AS order_note
            // ★ 修改 1: 在 SQL 中加入 o.note AS order_note，並排除 '已取餐'
            $sql_orders = "
                SELECT 
                    o.order_id, 
                    o.status, 
                    o.estimate_time, 
                    o.note AS order_note, 
                    s.name AS store_name,
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
                ORDER BY o.order_id DESC
            ";

            $stmt = $link->prepare($sql_orders);
            $stmt->bind_param("s", $account);
            $stmt->execute();
            $result = $stmt->get_result();

            $active_orders = [];

            while ($row = $result->fetch_assoc()) {
                $oid = $row['order_id'];

                if (!isset($active_orders[$oid])) {
                    $active_orders[$oid] = [
                        'store_name' => $row['store_name'],
                        'status' => $row['status'],
                        'estimate_time' => $row['estimate_time'],
                        'order_note' => $row['order_note'], // ★ 修改 2: 存入陣列
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

            if (!empty($active_orders)) {
                foreach ($active_orders as $order_id => $order) {
                    // 修改格式為 'Y/m/d H:i' 以顯示日期
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
                                                <span
                                                    style="font-size:12px; color:#e74c3c;">(<?= htmlspecialchars($item['note']) ?>)</span>
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

        <div class="dashboard-card">
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
        </div>

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
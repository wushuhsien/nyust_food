<?php
session_start();
include "../db.php";

$store_account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
if (!$store_account) {
    echo "<script>alert('請先登入'); window.location='../login.html';</script>";
    exit;
}

// ==========================================
// 0. 初始化訂單陣列 (修復 Undefined variable 錯誤)
// ==========================================
$orders = [
    '等待店家接單' => [],
    '餐點製作中' => [],
    '等待取餐' => [],
    '已取餐' => []
];

// ==========================================
// 1. 處理訂單狀態更新 (POST 請求)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];
    $new_status = '';

    date_default_timezone_set("Asia/Taipei");

    switch ($action) {
        case 'accept':      $new_status = '餐點製作中'; break;
        case 'finish_cook': $new_status = '等待取餐'; break;
        case 'complete':    $new_status = '已取餐'; break;
    }

    if ($new_status) {
        if ($new_status === '已取餐') {
            $current_time = date('Y-m-d H:i:s');
            $sql_update = "UPDATE `order` SET status = ?, pick_time = ? WHERE order_id = ?";
            $stmt = $link->prepare($sql_update);
            $stmt->bind_param("ssi", $new_status, $current_time, $order_id);
        } else {
            $sql_update = "UPDATE `order` SET status = ? WHERE order_id = ?";
            $stmt = $link->prepare($sql_update);
            $stmt->bind_param("si", $new_status, $order_id);
        }
        $stmt->execute();
        $stmt->close();
        
        // 重新導向避免表單重複提交
        header("Location: store.php");
        exit;
    }
}

// ==========================================
// 2. 判斷營業時段並計算「顯示起始時間」
// ==========================================
date_default_timezone_set("Asia/Taipei");
$current_weekday = date('N'); // 1(週一) ~ 7(週日)
$current_time_str = date('H:i:s');

// 撈取店家今日營業時間
$sql_hours = "SELECT open_time, close_time FROM storehours WHERE account = ? AND weekday = ?";
$stmt_h = $link->prepare($sql_hours);
$stmt_h->bind_param("si", $store_account, $current_weekday);
$stmt_h->execute();
$res_h = $stmt_h->get_result();
$hours = $res_h->fetch_assoc();
$stmt_h->close();

// 預設顯示過去 24 小時 (若沒設定營業時間)
$filter_start_time = date('Y-m-d H:i:s', strtotime("-24 hours"));

if ($hours) {
    $open = $hours['open_time'];
    $close = $hours['close_time'];

    // 判斷是否跨日營業 (例如: Open 18:00, Close 02:00)
    if ($close < $open) {
        // 如果現在時間還沒過午夜 (例如 01:00)，代表這是「昨天」開始的班次
        // 我們要看的是 昨天 18:00 之後的訂單
        if ($current_time_str < $close) {
            $filter_start_time = date('Y-m-d H:i:s', strtotime("yesterday $open"));
        } else {
            // 如果現在是 23:00，代表是 今天 18:00 開始的班次
            $filter_start_time = date('Y-m-d H:i:s', strtotime("today $open"));
        }
    } else {
        // 一般營業 (例如: 10:00 - 22:00)
        // 為了避免顧客提早一點點下單導致看不到，我們抓開店前 1 小時
        $filter_start_time = date('Y-m-d H:i:s', strtotime("today $open -1 hour"));
    }
}

// ==========================================
// 3. 撈取訂單資料 (加入時間過濾)
// ==========================================
// ★ 修改 1：多撈取 st.name AS student_name
$sql_orders = "
    SELECT 
        o.order_id, 
        o.status, 
        o.estimate_time, 
        o.pick_time, 
        o.note AS order_note,
        o.account AS student_account,
        st.phone AS student_phone,
        st.name AS student_name,  /* 新增這一行：撈取學生本名 */
        SUM(m.price * oi.quantity) AS total_price
    FROM `order` o
    JOIN `orderitem` oi ON o.order_id = oi.order_id
    JOIN `menu` m ON oi.menu_id = m.menu_id
    JOIN `student` st ON o.account = st.account
    WHERE m.account = ? 
    AND o.estimate_time >= ?  /* ★ 關鍵：只顯示本班次開始之後的訂單 */
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
";

$stmt = $link->prepare($sql_orders);
$stmt->bind_param("ss", $store_account, $filter_start_time);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $oid = $row['order_id'];
    $status = $row['status'];

    // 撈取細項
    $sql_items = "SELECT m.name, oi.quantity, oi.note 
                  FROM orderitem oi 
                  JOIN menu m ON oi.menu_id = m.menu_id 
                  WHERE oi.order_id = $oid";
    $res_items = $link->query($sql_items);
    $items_detail = [];
    while ($item = $res_items->fetch_assoc()) {
        $items_detail[] = $item;
    }
    $row['items'] = $items_detail;

    // 分類放入陣列
    if (isset($orders[$status])) {
        $orders[$status][] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家首頁</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: "Microsoft JhengHei", Arial, sans-serif;
            background-color: #fff7f0;
            margin: 20px;
        }

        #b {
            background-color: transparent;
            margin: 20px auto;
            max-width: 1200px;
        }

        /* === 訂單看板樣式 === */
        .board-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
            max-width: 1400px;
            margin: 0 auto 30px auto;
        }

        @media (max-width: 1024px) {
            .board-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .board-container {
                grid-template-columns: 1fr;
            }
        }

        .board-column {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
            display: flex;
            flex-direction: column;
            height: 600px;
        }

        .column-header {
            padding: 15px;
            color: white;
            font-weight: bold;
            text-align: center;
            border-radius: 8px 8px 0 0;
            font-size: 18px;
        }

        .header-waiting {
            background-color: #dc3545;
        }

        .header-cooking {
            background-color: #fd7e14;
        }

        .header-ready {
            background-color: #28a745;
        }

        .header-done {
            background-color: #6c757d;
        }

        .column-content {
            padding: 10px;
            overflow-y: auto;
            flex: 1;
            background-color: #fcfcfc;
        }

        .order-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .order-id {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .order-time {
            font-size: 12px;
            color: #888;
        }

        .order-user {
            font-size: 14px;
            color: #0056b3;
            margin-bottom: 8px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            /* 讓帳號靠左，電話靠右 */
            align-items: center;
        }

        .order-items {
            font-size: 14px;
            color: #444;
            border-top: 1px dashed #eee;
            border-bottom: 1px dashed #eee;
            padding: 5px 0;
            margin: 5px 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .order-note {
            font-size: 13px;
            color: #d63384;
            background: #fff0f6;
            padding: 4px;
            border-radius: 4px;
            margin-top: 5px;
        }

        .order-total {
            text-align: right;
            font-weight: bold;
            color: #b35c00;
            margin-top: 5px;
        }

        .btn-action {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            margin-top: 8px;
            transition: 0.3s;
        }

        .btn-accept {
            background-color: #fd7e14;
        }

        .btn-accept:hover {
            background-color: #e36d0d;
        }

        .btn-finish {
            background-color: #28a745;
        }

        .btn-finish:hover {
            background-color: #218838;
        }

        .btn-pickup {
            background-color: #6c757d;
        }

        .btn-pickup:hover {
            background-color: #5a6268;
        }

        .announcement {
            background-color: #fff3e6;
            border: 1px solid #f2c79e;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 15px;
            box-shadow: 1px 2px 6px rgba(0, 0, 0, 0.05);
        }

        .announcement p {
            margin: 6px 0;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <?php include "store_menu.php"; ?>

    <h2 style="text-align:center; color:#b35c00; margin: 20px 0;">訂單管理看板</h2>

    <div class="board-container">
        
        <div class="board-column">
            <div class="column-header header-waiting">
                等待接單 (<?= count($orders['等待店家接單']) ?>)
            </div>
            <div class="column-content">
                <?php foreach ($orders['等待店家接單'] as $o): ?>
                    <div class="order-card">
                        <div class="order-id">
                            #<?= $o['order_id'] ?>
                            <span class="order-time"><?= date('H:i', strtotime($o['estimate_time'])) ?> 預計</span>
                        </div>
                        
                        <div class="order-user">
                            <span><i class="bi bi-person"></i> <?= $o['student_account'] ?> (<?= htmlspecialchars($o['student_name']) ?>)</span>
                            <span style="color:#555; font-weight:normal; font-size:13px;">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($o['student_phone']) ?>
                            </span>
                        </div>
                        
                        <div class="order-items">
                            <?php foreach ($o['items'] as $item): ?>
                                <div class="item-row">
                                    <span><?= $item['name'] ?></span>
                                    <span>x<?= $item['quantity'] ?></span>
                                </div>
                                <?php if($item['note']) echo "<div style='font-size:12px; color:#999;'>({$item['note']})</div>"; ?>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($o['order_note']): ?>
                            <div class="order-note">備註：<?= htmlspecialchars($o['order_note']) ?></div>
                        <?php endif; ?>

                        <div class="order-total">總計 $<?= $o['total_price'] ?></div>

                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <button type="submit" name="action" value="accept" class="btn-action btn-accept">
                                <i class="bi bi-check-circle"></i> 確認接單
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="board-column">
            <div class="column-header header-cooking">
                製作中 (<?= count($orders['餐點製作中']) ?>)
            </div>
            <div class="column-content">
                <?php foreach ($orders['餐點製作中'] as $o): ?>
                    <div class="order-card">
                        <div class="order-id">
                            #<?= $o['order_id'] ?>
                            <span class="order-time"><?= date('H:i', strtotime($o['estimate_time'])) ?> 截止</span>
                        </div>
                        
                        <div class="order-user">
                            <span><i class="bi bi-person"></i> <?= $o['student_account'] ?> (<?= htmlspecialchars($o['student_name']) ?>)</span>
                            <span style="color:#555; font-weight:normal; font-size:13px;">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($o['student_phone']) ?>
                            </span>
                        </div>

                        <div class="order-items">
                            <?php foreach ($o['items'] as $item): ?>
                                <div class="item-row">
                                    <span><?= $item['name'] ?></span>
                                    <span>x<?= $item['quantity'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($o['order_note']): ?>
                            <div class="order-note">備註：<?= htmlspecialchars($o['order_note']) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <button type="submit" name="action" value="finish_cook" class="btn-action btn-finish">
                                <i class="bi bi-fire"></i> 製作完成
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="board-column">
            <div class="column-header header-ready">
                等待取餐 (<?= count($orders['等待取餐']) ?>)
            </div>
            <div class="column-content">
                <?php foreach ($orders['等待取餐'] as $o): ?>
                    <div class="order-card">
                        <div class="order-id">#<?= $o['order_id'] ?></div>
                        
                        <div class="order-user">
                            <span><i class="bi bi-person"></i> <?= $o['student_account'] ?> (<?= htmlspecialchars($o['student_name']) ?>)</span>
                            <span style="color:#555; font-weight:normal; font-size:13px;">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($o['student_phone']) ?>
                            </span>
                        </div>

                        <div class="order-items">
                            <?php foreach ($o['items'] as $item): ?>
                                <div class="item-row">
                                    <span><?= $item['name'] ?></span>
                                    <span>x<?= $item['quantity'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-total" style="color:#28a745;">請向顧客收款 $<?= $o['total_price'] ?></div>
                        
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <button type="submit" name="action" value="complete" class="btn-action btn-pickup">
                                <i class="bi bi-bag-check"></i> 已取餐
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="board-column">
            <div class="column-header header-done">
                已取餐 (最近20筆)
            </div>
            <div class="column-content">
                <?php 
                $count = 0;
                foreach ($orders['已取餐'] as $o): 
                    if($count >= 20) break;
                    $count++;
                ?>
                    <div class="order-card" style="opacity: 0.7;">
                        <div class="order-id">
                            #<?= $o['order_id'] ?> 
                            <span class="order-time">
                                <?= !empty($o['pick_time']) ? date('H:i', strtotime($o['pick_time'])) . ' 取餐' : '已完成' ?>
                            </span>
                        </div>
                        
                        <div class="order-user">
                            <span><i class="bi bi-person"></i> <?= $o['student_account'] ?> (<?= htmlspecialchars($o['student_name']) ?>)</span>
                            <span style="color:#555; font-weight:normal; font-size:13px;">
                                <i class="bi bi-telephone"></i> <?= htmlspecialchars($o['student_phone']) ?>
                            </span>
                        </div>

                        <div class="order-items">
                            <?php foreach ($o['items'] as $item): ?>
                                <div class="item-row">
                                    <span><?= $item['name'] ?></span>
                                    <span>x<?= $item['quantity'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-total">已收款 $<?= $o['total_price'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <div id="b" style="display:flex; gap:20px; justify-content:space-between;">
        <div style="flex:1; border:2px solid #f28c28; border-radius:10px; padding:15px; background-color:#fff3e6; max-height:500px; overflow-y:auto;">
            <h2 style="text-align:center; color:#b35c00;">店家公告</h2>
            <?php
            $sql_store_ann = "SELECT announcement_id, topic, description, start_time, end_time
                      FROM announcement
                      WHERE type='店休'
                        AND account = ?
                        AND start_time <= NOW()
                        AND end_time >= NOW()
                      ORDER BY start_time DESC";

            $stmt_store = $link->prepare($sql_store_ann);
            $stmt_store->bind_param("s", $store_account);
            $stmt_store->execute();
            $result_store = $stmt_store->get_result();

            if ($result_store->num_rows > 0) {
                while ($row = $result_store->fetch_assoc()) {
                    echo '<div class="announcement">';
                    echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                    echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p>目前沒有店家公告。</p>";
            }
            $stmt_store->close();
            ?>
        </div>

        <div style="flex:1; border:2px solid #f28c28; border-radius:10px; padding:15px; background-color:#fff3e6; max-height:500px; overflow-y:auto;">
            <h2 style="text-align:center; color:#b35c00;">系統公告</h2>
            <?php
            $sql_admin = "SELECT announcement_id, topic, description, start_time, end_time
                      FROM announcement
                      WHERE type='公告'
                        AND start_time <= NOW()
                        AND end_time >= NOW()
                      ORDER BY start_time DESC";

            $result_admin = $link->query($sql_admin);

            if ($result_admin->num_rows > 0) {
                while ($row = $result_admin->fetch_assoc()) {
                    echo '<div class="announcement">';
                    echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                    echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p>目前沒有系統公告。</p>";
            }
            ?>
        </div>
    </div>

</body>
</html>
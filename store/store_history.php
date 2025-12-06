<?php
session_start();
include "../db.php";

$store_account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
if (!$store_account) {
    echo "<script>alert('請先登入'); window.location='../login.html';</script>";
    exit;
}

// 設定時區
date_default_timezone_set("Asia/Taipei");

// ==========================================
// ★ 處理店家回覆 (已修正為使用 mealreview_id)
// ==========================================
if (isset($_POST['submit_reply'])) {
    // 1. 接收 mealreview_id
    $p_mealreview_id = $_POST['mealreview_id']; 
    $p_reply = trim($_POST['reply_content']);
    $p_time = date("Y-m-d H:i:s");

    if (!empty($p_reply) && !empty($p_mealreview_id)) {
        // 2. 檢查是否已經回覆過
        $check_sql = "SELECT mealreviewreply_id FROM mealreviewreply WHERE mealreview_id = ?";
        $check_stmt = $link->prepare($check_sql);
        $check_stmt->bind_param("i", $p_mealreview_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            echo "<script>alert('您已經回覆過此評論了！'); history.back();</script>";
        } else {
            // 3. 插入新回覆
            $ins_sql = "INSERT INTO mealreviewreply (description, time, account, mealreview_id) VALUES (?, ?, ?, ?)";
            $ins_stmt = $link->prepare($ins_sql);
            $ins_stmt->bind_param("sssi", $p_reply, $p_time, $store_account, $p_mealreview_id);
            
            if ($ins_stmt->execute()) {
                echo "<script>alert('回覆已送出！'); window.location.href='store_history.php';</script>";
            } else {
                echo "<script>alert('回覆失敗，請稍後再試。');</script>";
            }
            $ins_stmt->close();
        }
        $check_stmt->close();
    }
}
// ==========================================

// 1. 處理篩選參數
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : ''; 

// 2. 準備 SQL 查詢
// ★★★ 重點修正區塊 ★★★
$sql = "
    SELECT 
        o.order_id, 
        o.status, 
        o.estimate_time, 
        o.pick_time, 
        o.note AS order_note,
        o.account AS student_account,
        st.phone AS student_phone,
        m.name AS menu_name,
        m.price,
        oi.quantity,
        oi.note AS item_note,
        
        /* ★ 新增：必須選取 mealreview_id */
        mr.mealreview_id,
        mr.rate AS review_rate,
        mr.description AS review_desc,
        mr.time AS review_time,
        
        mrr.description AS reply_desc,
        mrr.time AS reply_time

    FROM `order` o
    JOIN `orderitem` oi ON o.order_id = oi.order_id
    JOIN `menu` m ON oi.menu_id = m.menu_id
    JOIN `student` st ON o.account = st.account
    LEFT JOIN `mealreview` mr ON o.order_id = mr.order_id           
    /* ★ 修正：回覆是關聯到評論(mr)，而不是訂單(o) */
    LEFT JOIN `mealreviewreply` mrr ON mr.mealreview_id = mrr.mealreview_id    
    
    WHERE m.account = ? 
    AND o.status IN ('已取餐', '已取消', '商家拒單')
";

$params = [$store_account];
$types = "s";

if (!empty($start_date)) {
    $sql .= " AND DATE(o.estimate_time) >= ?";
    $params[] = $start_date;
    $types .= "s";
}

if (!empty($end_date)) {
    $sql .= " AND DATE(o.estimate_time) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (o.order_id LIKE ? OR o.account LIKE ? OR st.phone LIKE ?)";
    $searchTerm = "%" . $search_query . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if ($rating_filter !== '') {
    if ($rating_filter === 'unrated') {
        $sql .= " AND mr.rate IS NULL";
    } else {
        $sql .= " AND mr.rate = ?";
        $params[] = $rating_filter;
        $types .= "i";
    }
}

$sql .= " ORDER BY o.estimate_time DESC";

$stmt = $link->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 3. 資料整理
$history_orders = [];
$total_revenue = 0;
$total_count = 0;

while ($row = $result->fetch_assoc()) {
    $oid = $row['order_id'];
    
    if (!isset($history_orders[$oid])) {
        $history_orders[$oid] = [
            'status' => $row['status'],
            'student_account' => $row['student_account'],
            'student_phone' => $row['student_phone'],
            'estimate_time' => $row['estimate_time'],
            'pick_time' => $row['pick_time'],
            'order_note' => $row['order_note'],
            'items' => [],
            'total_price' => 0,
            
            // ★ 將 mealreview_id 存入陣列
            'mealreview_id' => $row['mealreview_id'],
            'rate' => $row['review_rate'],
            'review_desc' => $row['review_desc'],
            'review_time' => $row['review_time'],
            
            'reply_desc' => $row['reply_desc'],
            'reply_time' => $row['reply_time']
        ];
    }
    
    $history_orders[$oid]['items'][] = [
        'name' => $row['menu_name'],
        'qty' => $row['quantity'],
        'note' => $row['item_note'],
        'price' => $row['price']
    ];
    
    $subtotal = $row['price'] * $row['quantity'];
    $history_orders[$oid]['total_price'] += $subtotal;
}
$stmt->close();

foreach ($history_orders as $order) {
    if ($order['status'] == '已取餐') {
        $total_revenue += $order['total_price'];
        $total_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家歷史訂單</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; background: #f2f6fc; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }

        .filter-section {
            background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
            margin-bottom: 20px; border-left: 5px solid #6c757d;
        }
        
        .filter-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-form input, .filter-form select { padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
        .search-input { width: 180px; }

        .filter-btn { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .filter-btn:hover { background: #5a6268; }
        
        .reset-link { color: #888; text-decoration: none; font-size: 14px; margin-left: 5px; }
        .reset-link:hover { text-decoration: underline; color: #dc3545; }

        .stats-box { display: flex; gap: 20px; }
        .stat-item { background: #f8f9fa; padding: 10px 20px; border-radius: 8px; border: 1px solid #eee; }
        .stat-label { font-size: 13px; color: #666; }
        .stat-value { font-size: 20px; font-weight: bold; color: #333; }
        .text-money { color: #28a745; }

        .order-card { background: white; border-bottom: 1px solid #eee; margin-bottom: 0; }
        .order-card:first-child { border-top-left-radius: 10px; border-top-right-radius: 10px; }
        .order-card:last-child { border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; border-bottom: none; }
        
        details { width: 100%; }
        summary {
            padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
            background-color: #fff; transition: 0.2s; list-style: none;
        }
        summary:hover { background-color: #f8f9fa; }
        summary::-webkit-details-marker { display: none; }
        
        .status-badge { font-size: 13px; padding: 5px 10px; border-radius: 15px; font-weight: bold; }
        .status-done { background: #d1e7dd; color: #0f5132; }
        .status-cancel { background: #f8d7da; color: #842029; }
        .status-reject { background: #e2e3e5; color: #41464b; }

        .order-content { padding: 20px; background: #fafafa; border-top: 1px solid #eee; }
        .item-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 15px; border-bottom: 1px dashed #eee; }
        .total-row { text-align: right; font-weight: bold; font-size: 18px; margin-top: 10px; }
        .no-data { text-align: center; padding: 50px; color: #888; background: white; border-radius: 10px; margin-top: 20px;}
        
        /* 評論區塊樣式 */
        .review-section {
            background: white; border: 1px solid #ffeeba; border-radius: 8px; padding: 15px; margin-top: 15px;
        }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .star-display { color: #ffc107; font-size: 16px; }
        .review-text { color: #555; line-height: 1.4; }
        .review-time { font-size: 12px; color: #999; }
        
        /* 回覆區塊樣式 */
        .reply-box {
            margin-top: 12px; padding-top: 12px; border-top: 1px solid #eee;
        }
        .reply-display {
            background: #f1f3f5; padding: 10px; border-radius: 6px; font-size: 14px; color: #495057;
            border-left: 3px solid #6c757d;
        }
        .reply-input {
            width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; resize: none; font-family: inherit;
        }
        .btn-reply {
            background: #0d6efd; color: white; border: none; padding: 6px 15px; border-radius: 5px; 
            margin-top: 5px; cursor: pointer; font-size: 13px;
        }
        .btn-reply:hover { background: #0b5ed7; }
    </style>
</head>

<body>
    <?php include "store_menu.php"; ?>
    <br>

    <div class="container">
        <h2 style="color: #666; margin-bottom: 20px;"><i class="bi bi-clock-history"></i> 歷史訂單查詢</h2>

        <div class="filter-section">
            <form method="GET" class="filter-form">
                <input type="text" name="q" class="search-input" 
                       placeholder="單號/帳號/電話" 
                       value="<?= htmlspecialchars($search_query) ?>">

                <select name="rating">
                    <option value="">所有評價</option>
                    <option value="unrated" <?= $rating_filter === 'unrated' ? 'selected' : '' ?>>尚未評價</option>
                    <option value="5" <?= $rating_filter === '5' ? 'selected' : '' ?>>5 顆星</option>
                    <option value="4" <?= $rating_filter === '4' ? 'selected' : '' ?>>4 顆星</option>
                    <option value="3" <?= $rating_filter === '3' ? 'selected' : '' ?>>3 顆星</option>
                    <option value="2" <?= $rating_filter === '2' ? 'selected' : '' ?>>2 顆星</option>
                    <option value="1" <?= $rating_filter === '1' ? 'selected' : '' ?>>1 顆星</option>
                </select>

                <label>區間：</label>
                <input type="date" name="start_date" value="<?= $start_date ?>">
                <span>~</span>
                <input type="date" name="end_date" value="<?= $end_date ?>">
                
                <button type="submit" class="filter-btn"><i class="bi bi-search"></i> 查詢</button>
                
                <?php if (!empty($search_query) || !empty($start_date) || !empty($end_date) || $rating_filter): ?>
                    <a href="store_history.php" class="reset-link">清除條件</a>
                <?php endif; ?>
            </form>

            <div class="stats-box">
                <div class="stat-item">
                    <div class="stat-label">完成單數</div>
                    <div class="stat-value"><?= $total_count ?> 單</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">區間營收</div>
                    <div class="stat-value text-money">$<?= number_format($total_revenue) ?></div>
                </div>
            </div>
        </div>

        <?php if (empty($history_orders)): ?>
            <div class="no-data">
                <i class="bi bi-calendar-x" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                查無符合條件的歷史訂單
            </div>
        <?php else: ?>
            <div style="box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 10px; overflow: hidden;">
                <?php foreach ($history_orders as $oid => $order): 
                    $badgeClass = 'status-reject';
                    if ($order['status'] == '已取餐') $badgeClass = 'status-done';
                    if ($order['status'] == '已取消') $badgeClass = 'status-cancel';
                    
                    $show_time = !empty($order['pick_time']) ? $order['pick_time'] : $order['estimate_time'];
                ?>
                    <details class="order-card">
                        <summary>
                            <div style="display: flex; align-items: center;">
                                <span style="font-weight: bold; margin-right: 10px;">#<?= $oid ?></span>
                                <span style="font-size: 14px; color: #555;">
                                    <i class="bi bi-calendar3"></i> <?= date('m/d H:i', strtotime($show_time)) ?>
                                </span>
                                <span style="margin-left: 10px; font-size: 14px; color: #0056b3;">
                                    <i class="bi bi-person"></i> <?= $order['student_account'] ?> 
                                    (<?= $order['student_phone'] ?>)
                                </span>
                                
                                <?php if (!empty($order['rate'])): ?>
                                    <span style="margin-left: 10px; color: #ffc107; font-size: 14px;">
                                        <i class="bi bi-star-fill"></i> <?= $order['rate'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="status-badge <?= $badgeClass ?>"><?= $order['status'] ?></span>
                                <span style="margin-left: 10px; font-weight: bold; width: 60px; display: inline-block; text-align: right;">
                                    $<?= $order['total_price'] ?>
                                </span>
                            </div>
                        </summary>
                        <div class="order-content">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item-row">
                                    <span>
                                        <?= $item['name'] ?> 
                                        <span style="color:#888; font-size:13px;">x<?= $item['qty'] ?></span>
                                        <?php if($item['note']): ?>
                                            <span style="color:#dc3545; font-size:12px; margin-left:5px;">(<?= $item['note'] ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                    <span>$<?= $item['price'] * $item['qty'] ?></span>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($order['order_note']): ?>
                                <div style="margin-top: 10px; font-size: 13px; color: #856404; background: #fff3cd; padding: 8px; border-radius: 5px;">
                                    備註：<?= htmlspecialchars($order['order_note']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="total-row">總計：$<?= $order['total_price'] ?></div>

                            <?php if (!empty($order['rate'])): ?>
                                <div class="review-section">
                                    <div class="review-header">
                                        <strong style="color:#333;">評價：</strong>
                                        <div>
                                            <span class="star-display">
                                                <?php 
                                                for($i=1; $i<=5; $i++) {
                                                    if($i <= $order['rate']) echo '<i class="bi bi-star-fill"></i>';
                                                    else echo '<i class="bi bi-star" style="color:#ccc"></i>';
                                                }
                                                ?>
                                            </span>
                                            <span class="review-time" style="margin-left:5px;">
                                                (<?= date('Y/m/d H:i', strtotime($order['review_time'])) ?>)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="review-text">
                                        <?= !empty($order['review_desc']) ? htmlspecialchars($order['review_desc']) : '<span style="color:#888; font-style:italic;">(無文字評論)</span>' ?>
                                    </div>

                                    <div class="reply-box">
                                        <?php if (!empty($order['reply_desc'])): ?>
                                            <div style="font-size:13px; color:#666; margin-bottom:5px;">
                                                您的回覆 (<?= date('Y/m/d H:i', strtotime($order['reply_time'])) ?>)：
                                            </div>
                                            <div class="reply-display">
                                                <?= htmlspecialchars($order['reply_desc']) ?>
                                            </div>
                                        <?php else: ?>
                                            <form method="POST" onsubmit="return confirm('送出後無法修改或刪除，確定要回覆嗎？');">
                                                <input type="hidden" name="mealreview_id" value="<?= $order['mealreview_id'] ?>">
                                                
                                                <textarea name="reply_content" class="reply-input" rows="2" placeholder="輸入回覆內容..." required></textarea>
                                                <div style="text-align: right;">
                                                    <button type="submit" name="submit_reply" class="btn-reply">
                                                        <i class="bi bi-reply-fill"></i> 送出回覆
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
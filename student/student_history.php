<?php
session_start();
include "../db.php";

$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
if (!$account) {
    echo "<script>alert('請先登入'); window.location='../login.html';</script>";
    exit;
}

// 設定時區
date_default_timezone_set("Asia/Taipei");

// ==========================================
// 處理評論提交
// ==========================================
if (isset($_POST['submit_review'])) {
    $review_oid = $_POST['order_id'];
    $review_rate = $_POST['rate'];
    $review_desc = trim($_POST['description']);
    $review_time = date("Y-m-d H:i:s");

    $check_stmt = $link->prepare("SELECT mealreview_id FROM mealreview WHERE order_id = ?");
    $check_stmt->bind_param("i", $review_oid);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        echo "<script>alert('此訂單已經評論過囉！'); history.back();</script>";
    } else {
        $ins_stmt = $link->prepare("INSERT INTO mealreview (description, time, rate, order_id) VALUES (?, ?, ?, ?)");
        $ins_stmt->bind_param("ssii", $review_desc, $review_time, $review_rate, $review_oid);
        
        if ($ins_stmt->execute()) {
            echo "<script>alert('評論已送出！'); window.location.href='student_history.php';</script>";
        } else {
            echo "<script>alert('評論失敗，請稍後再試');</script>";
        }
        $ins_stmt->close();
    }
    $check_stmt->close();
}
// ==========================================

// 1. 處理篩選參數
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
// ★ 新增：接收評價篩選參數
$rating_filter = isset($_GET['rating']) ? $_GET['rating'] : ''; 

// 2. 準備 SQL 查詢

// 步驟 A: 先找出符合搜尋條件的 order_id 列表
// ★ 修改：在這裡就要 LEFT JOIN mealreview，才能針對評價進行篩選
$id_sql = "
    SELECT DISTINCT o.order_id
    FROM `order` o
    JOIN `orderitem` oi ON o.order_id = oi.order_id
    JOIN `menu` m ON oi.menu_id = m.menu_id
    JOIN `store` s ON m.account = s.account
    LEFT JOIN `mealreview` mr ON o.order_id = mr.order_id  /* ★ 加入關聯以便篩選 */
    WHERE o.account = ? 
    AND o.status IN ('已取餐', '已取消', '商家拒單')
";

$id_params = [$account];
$id_types = "s";

// 關鍵字搜尋
if (!empty($search_query)) {
    $id_sql .= " AND (s.name LIKE ? OR m.name LIKE ?)";
    $term = "%" . $search_query . "%";
    $id_params[] = $term;
    $id_params[] = $term;
    $id_types .= "ss";
}

// 日期篩選
if (!empty($start_date)) {
    $id_sql .= " AND DATE(o.estimate_time) >= ?";
    $id_params[] = $start_date;
    $id_types .= "s";
}
if (!empty($end_date)) {
    $id_sql .= " AND DATE(o.estimate_time) <= ?";
    $id_params[] = $end_date;
    $id_types .= "s";
}

// ★ 新增：評價篩選邏輯
if ($rating_filter !== '') {
    if ($rating_filter === 'unrated') {
        // 篩選尚未評價 (rate 為 NULL)
        $id_sql .= " AND mr.rate IS NULL";
    } else {
        // 篩選特定星數 (1~5)
        $id_sql .= " AND mr.rate = ?";
        $id_params[] = $rating_filter;
        $id_types .= "i";
    }
}

$id_stmt = $link->prepare($id_sql);
$id_stmt->bind_param($id_types, ...$id_params);
$id_stmt->execute();
$id_result = $id_stmt->get_result();

$target_order_ids = [];
while ($row = $id_result->fetch_assoc()) {
    $target_order_ids[] = $row['order_id'];
}
$id_stmt->close();

// 步驟 B: 撈出完整細項
$history_orders = [];

if (!empty($target_order_ids)) {
    $placeholders = implode(',', array_fill(0, count($target_order_ids), '?'));
    
    $sql = "
        SELECT 
            o.order_id, 
            o.status, 
            o.estimate_time, 
            o.pick_time, 
            o.note AS order_note, 
            s.name AS store_name,
            m.name AS menu_name,
            m.price,
            oi.quantity,
            oi.note AS item_note,
            mr.rate AS review_rate,        
            mr.description AS review_desc   
        FROM `order` o
        JOIN `orderitem` oi ON o.order_id = oi.order_id
        JOIN `menu` m ON oi.menu_id = m.menu_id
        JOIN `store` s ON m.account = s.account
        LEFT JOIN `mealreview` mr ON o.order_id = mr.order_id
        WHERE o.order_id IN ($placeholders)
        ORDER BY o.order_id DESC
    ";

    $stmt = $link->prepare($sql);
    $types = str_repeat('i', count($target_order_ids));
    $stmt->bind_param($types, ...$target_order_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $oid = $row['order_id'];
        
        if (!isset($history_orders[$oid])) {
            $display_time = !empty($row['pick_time']) ? $row['pick_time'] : $row['estimate_time'];
            
            $history_orders[$oid] = [
                'store_name' => $row['store_name'],
                'status' => $row['status'],
                'time' => $display_time, 
                'order_note' => $row['order_note'],
                'items' => [],
                'total_price' => 0,
                'review_rate' => $row['review_rate'], 
                'review_desc' => $row['review_desc']
            ];
        }
        
        $history_orders[$oid]['items'][] = [
            'name' => $row['menu_name'],
            'qty' => $row['quantity'],
            'note' => $row['item_note'],
            'price' => $row['price']
        ];
        
        $history_orders[$oid]['total_price'] += ($row['price'] * $row['quantity']);
    }
    $stmt->close();
}

$total_completed_count = 0;
$total_spend = 0;

foreach ($history_orders as $order) {
    if ($order['status'] == '已取餐') {
        $total_completed_count++;
        $total_spend += $order['total_price'];
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>我的歷史訂單</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: "Microsoft JhengHei", Arial, sans-serif; background: #f2f6fc; margin: 20px; }
        .history-wrapper { max-width: 900px; margin: 0 auto; }
        .history-header { background: #6c757d; color: white; padding: 20px; border-radius: 10px 10px 0 0; font-size: 24px; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        
        .search-section { background: white; padding: 15px; border-bottom: 1px solid #eee; }
        .search-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .search-bar input, .search-bar select { padding: 8px; border: 1px solid #ccc; border-radius: 5px; } /* 新增 select 樣式 */
        .search-btn { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
        .search-btn:hover { background: #5a6268; }
        .reset-link { color: #dc3545; text-decoration: none; font-size: 14px; margin-left: 5px; }

        .stats-container { display: flex; gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; }
        .stat-box { flex: 1; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 10px 15px; text-align: center; }
        .stat-title { font-size: 13px; color: #666; margin-bottom: 5px; }
        .stat-value { font-size: 20px; font-weight: bold; color: #333; }
        .text-money { color: #d63384; }

        .order-card { background: white; border-bottom: 1px solid #eee; margin-bottom: 0; }
        .order-card:last-child { border-radius: 0 0 10px 10px; }

        details { width: 100%; border-bottom: 1px solid #eee; }
        summary { padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none; background-color: #fff; transition: 0.2s; }
        summary:hover { background-color: #f8f9fa; }
        summary::-webkit-details-marker { display: none; }
        summary::after { content: '\F282'; font-family: 'bootstrap-icons'; transition: 0.3s; color: #999; }
        details[open] summary::after { transform: rotate(180deg); }

        .order-info { display: flex; flex-direction: column; gap: 5px; }
        .store-name { font-size: 18px; font-weight: bold; color: #333; }
        .order-meta { font-size: 13px; color: #888; }

        .status-badge { font-size: 13px; padding: 5px 10px; border-radius: 15px; font-weight: bold; }
        .status-completed { background-color: #d1e7dd; color: #0f5132; }
        .status-cancelled { background-color: #f8d7da; color: #842029; }
        .status-rejected  { background-color: #e2e3e5; color: #41464b; }

        .order-content { padding: 20px; background-color: #fafafa; border-top: 1px solid #eee; }
        .item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ddd; font-size: 15px; color: #555; }
        .item-row:last-child { border-bottom: none; }
        .total-row { text-align: right; margin-top: 15px; font-weight: bold; font-size: 18px; color: #333; }
        .empty-history { text-align: center; padding: 50px; color: #999; background: white; border-radius: 0 0 10px 10px; }

        .review-section { margin-top: 20px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; }
        .review-title { font-weight: bold; margin-bottom: 10px; color: #495057; border-bottom: 2px solid #ffc107; display: inline-block; padding-bottom: 3px; }
        
        .star-rating { font-size: 24px; color: #ddd; cursor: pointer; display: inline-block; }
        .star-rating .bi-star-fill { color: #ffc107; } 
        .star-rating .bi-star { color: #ccc; }      
        
        .review-textarea { width: 100%; height: 60px; padding: 8px; margin-top: 10px; border-radius: 5px; border: 1px solid #ccc; resize: none; font-family: inherit; }
        .btn-submit-review { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 5px; margin-top: 10px; cursor: pointer; font-weight: bold; }
        .btn-submit-review:hover { background: #218838; }

        .reviewed-box { background: #fff8e1; border: 1px solid #ffeeba; padding: 10px; border-radius: 8px; margin-top: 15px; }
        .reviewed-stars { color: #ffc107; font-size: 18px; }
        .reviewed-text { color: #555; margin-top: 5px; font-size: 14px; }
    </style>
</head>
<body>

    <?php include "student_menu.php"; ?>
    <br>

    <div class="history-wrapper">
        <div class="history-header">
            <i class="bi bi-clock-history"></i> 歷史訂單紀錄
        </div>

        <div style="background: white; border-radius: 0 0 10px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden;">
            
            <div class="search-section">
                <form method="GET" class="search-bar">
                    <input type="text" name="q" placeholder="店家名稱 / 餐點名稱" value="<?= htmlspecialchars($search_query) ?>">
                    
                    <span style="color:#666; font-size:14px;">日期：</span>
                    <input type="date" name="start_date" value="<?= $start_date ?>">
                    <span>~</span>
                    <input type="date" name="end_date" value="<?= $end_date ?>">
                    
                    <select name="rating">
                        <option value="">所有評價</option>
                        <option value="unrated" <?= $rating_filter === 'unrated' ? 'selected' : '' ?>>尚未評價</option>
                        <option value="5" <?= $rating_filter === '5' ? 'selected' : '' ?>>5 顆星</option>
                        <option value="4" <?= $rating_filter === '4' ? 'selected' : '' ?>>4 顆星</option>
                        <option value="3" <?= $rating_filter === '3' ? 'selected' : '' ?>>3 顆星</option>
                        <option value="2" <?= $rating_filter === '2' ? 'selected' : '' ?>>2 顆星</option>
                        <option value="1" <?= $rating_filter === '1' ? 'selected' : '' ?>>1 顆星</option>
                    </select>

                    <button type="submit" class="search-btn"><i class="bi bi-search"></i> 查詢</button>
                    <?php if ($search_query || $start_date || $end_date || $rating_filter): ?>
                        <a href="?" class="reset-link">清除條件</a>
                    <?php endif; ?>
                </form>

                <div class="stats-container">
                    <div class="stat-box">
                        <div class="stat-title">完成單數</div>
                        <div class="stat-value"><?= $total_completed_count ?> 單</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-title">區間總花費</div>
                        <div class="stat-value text-money">$<?= number_format($total_spend) ?></div>
                    </div>
                </div>
            </div>

            <?php if (empty($history_orders)): ?>
                <div class="empty-history">
                    <i class="bi bi-inbox" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                    <?php if ($search_query || $start_date || $end_date || $rating_filter): ?>
                        查無符合條件的訂單
                    <?php else: ?>
                        尚無歷史訂單紀錄
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($history_orders as $order_id => $order): 
                    $badge_class = 'status-rejected';
                    if ($order['status'] == '已取餐') $badge_class = 'status-completed';
                    if ($order['status'] == '已取消') $badge_class = 'status-cancelled';
                    $date_str = date('Y/m/d H:i', strtotime($order['time']));
                ?>
                    <details class="order-card">
                        <summary>
                            <div class="order-info">
                                <div class="store-name">
                                    <?= htmlspecialchars($order['store_name']) ?>
                                    <?php if (!empty($order['review_rate'])): ?>
                                        <span style="font-size:12px; color:#ffc107; margin-left:5px;">
                                            <i class="bi bi-star-fill"></i> <?= $order['review_rate'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="order-meta">
                                    <span><i class="bi bi-calendar3"></i> <?= $date_str ?></span>
                                    <span style="margin-left: 10px;">#<?= $order_id ?></span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="status-badge <?= $badge_class ?>">
                                    <?= htmlspecialchars($order['status']) ?>
                                </span>
                                <div style="margin-top: 5px; font-weight: bold; color: #555;">
                                    $<?= $order['total_price'] ?>
                                </div>
                            </div>
                        </summary>
                        
                        <div class="order-content">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item-row">
                                    <div>
                                        <?= htmlspecialchars($item['name']) ?> 
                                        <?php if($item['note']): ?>
                                            <span style="font-size:12px; color:#e74c3c;">(<?= htmlspecialchars($item['note']) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>$<?= $item['price'] ?> x <?= $item['qty'] ?></div>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($order['order_note']): ?>
                                <div style="margin-top: 10px; font-size: 13px; color: #856404; background: #fff3cd; padding: 8px; border-radius: 5px;">
                                    <i class="bi bi-pencil-fill"></i> 備註：<?= htmlspecialchars($order['order_note']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="total-row">總計：$<?= $order['total_price'] ?></div>

                            <?php if ($order['status'] == '已取餐'): ?>
                                
                                <?php if (!empty($order['review_rate'])): ?>
                                    <div class="reviewed-box">
                                        <div class="reviewed-stars">
                                            <?php 
                                            for($i=1; $i<=5; $i++) {
                                                if($i <= $order['review_rate']) echo '<i class="bi bi-star-fill"></i>';
                                                else echo '<i class="bi bi-star" style="color:#ccc"></i>';
                                            }
                                            ?>
                                            <span style="color:#888; font-size:14px; margin-left:5px;">(已評分)</span>
                                        </div>
                                        <div class="reviewed-text">
                                            <strong>評論：</strong><?= htmlspecialchars($order['review_desc']) ?>
                                        </div>
                                    </div>

                                <?php else: ?>
                                    <div class="review-section">
                                        <div class="review-title">訂單評價</div>
                                        <form method="POST" onsubmit="return validateReview(this);">
                                            <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                            
                                            <div class="star-rating" id="star-container-<?= $order_id ?>">
                                                <i class="bi bi-star star-icon" data-val="1" onclick="setRate(<?= $order_id ?>, 1)"></i>
                                                <i class="bi bi-star star-icon" data-val="2" onclick="setRate(<?= $order_id ?>, 2)"></i>
                                                <i class="bi bi-star star-icon" data-val="3" onclick="setRate(<?= $order_id ?>, 3)"></i>
                                                <i class="bi bi-star star-icon" data-val="4" onclick="setRate(<?= $order_id ?>, 4)"></i>
                                                <i class="bi bi-star star-icon" data-val="5" onclick="setRate(<?= $order_id ?>, 5)"></i>
                                            </div>
                                            <input type="hidden" name="rate" id="rate-input-<?= $order_id ?>" value="0">
                                            
                                            <textarea name="description" class="review-textarea" placeholder="寫下您對這餐的評價..." required></textarea>
                                            <button type="submit" name="submit_review" class="btn-submit-review">送出評論</button>
                                        </form>
                                    </div>
                                <?php endif; ?>

                            <?php endif; ?>

                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function setRate(orderId, val) {
            document.getElementById('rate-input-' + orderId).value = val;
            const container = document.getElementById('star-container-' + orderId);
            const stars = container.getElementsByClassName('star-icon');
            
            for (let i = 0; i < stars.length; i++) {
                let starVal = parseInt(stars[i].getAttribute('data-val'));
                if (starVal <= val) {
                    stars[i].classList.remove('bi-star');
                    stars[i].classList.add('bi-star-fill');
                    stars[i].style.color = '#ffc107';
                } else {
                    stars[i].classList.remove('bi-star-fill');
                    stars[i].classList.add('bi-star');
                    stars[i].style.color = '#ccc';
                }
            }
        }

        function validateReview(form) {
            const rateInput = form.querySelector('input[name="rate"]');
            if (rateInput.value == "0") {
                alert("請點選星星進行評分！");
                return false;
            }
            return confirm("送出後將無法修改或刪除，確定要送出嗎？");
        }
    </script>
</body>
</html>
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

// 1. 處理篩選參數
$default_start = date('Y-m-d');
$default_end = date('Y-m-d');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end;
$search_query = isset($_GET['q']) ? trim($_GET['q']) : ''; // ★ 取得搜尋關鍵字

// 2. 準備 SQL 查詢
// 基本 SQL
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
        oi.note AS item_note
    FROM `order` o
    JOIN `orderitem` oi ON o.order_id = oi.order_id
    JOIN `menu` m ON oi.menu_id = m.menu_id
    JOIN `student` st ON o.account = st.account
    WHERE m.account = ? 
    AND o.status IN ('已取餐', '已取消', '商家拒單')
    AND DATE(o.estimate_time) BETWEEN ? AND ?
";

// 參數陣列初始化
$params = [$store_account, $start_date, $end_date];
$types = "sss";

// ★ 如果有搜尋關鍵字，動態加入 SQL 條件
if (!empty($search_query)) {
    $sql .= " AND (o.order_id LIKE ? OR o.account LIKE ? OR st.phone LIKE ?)";
    $searchTerm = "%" . $search_query . "%";
    // 塞入三次，對應三個 ?
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

$sql .= " ORDER BY o.estimate_time DESC";

$stmt = $link->prepare($sql);
// 使用解包運算子 (...) 動態綁定參數
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 3. 資料整理 & 統計
$history_orders = [];
$total_revenue = 0; // 總營收 (只算已取餐)
$total_count = 0;   // 總單量 (只算已取餐)

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
            'total_price' => 0
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

// 計算總營收 (針對篩選後的結果計算)
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

        /* 篩選區塊 */
        .filter-section {
            background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
            margin-bottom: 20px; border-left: 5px solid #6c757d;
        }
        
        .filter-form { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .filter-form input { padding: 8px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; }
        
        /* 搜尋框樣式 */
        .search-input { width: 200px; }

        .filter-btn {
            background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 5px;
        }
        .filter-btn:hover { background: #5a6268; }
        
        .reset-link { color: #888; text-decoration: none; font-size: 14px; margin-left: 5px; }
        .reset-link:hover { text-decoration: underline; color: #dc3545; }

        /* 統計區塊 */
        .stats-box {
            display: flex; gap: 20px;
        }
        .stat-item {
            background: #f8f9fa; padding: 10px 20px; border-radius: 8px; border: 1px solid #eee;
        }
        .stat-label { font-size: 13px; color: #666; }
        .stat-value { font-size: 20px; font-weight: bold; color: #333; }
        .text-money { color: #28a745; }

        /* 列表樣式 */
        .order-card {
            background: white; border-bottom: 1px solid #eee; margin-bottom: 0;
        }
        .order-card:first-child { border-top-left-radius: 10px; border-top-right-radius: 10px; }
        .order-card:last-child { border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; border-bottom: none; }
        
        details { width: 100%; }
        summary {
            padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
            background-color: #fff; transition: 0.2s; list-style: none;
        }
        summary:hover { background-color: #f8f9fa; }
        summary::-webkit-details-marker { display: none; }
        
        /* 狀態標籤 */
        .status-badge { font-size: 13px; padding: 5px 10px; border-radius: 15px; font-weight: bold; }
        .status-done { background: #d1e7dd; color: #0f5132; }
        .status-cancel { background: #f8d7da; color: #842029; }
        .status-reject { background: #e2e3e5; color: #41464b; }

        .order-content { padding: 20px; background: #fafafa; border-top: 1px solid #eee; }
        .item-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 15px; border-bottom: 1px dashed #eee; }
        .total-row { text-align: right; font-weight: bold; font-size: 18px; margin-top: 10px; }
        
        .no-data { text-align: center; padding: 50px; color: #888; background: white; border-radius: 10px; margin-top: 20px;}
        
        /* 高亮搜尋字詞 (選用) */
        .highlight { background-color: #fff3cd; }
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
                       placeholder="搜尋單號 / 帳號 / 電話" 
                       value="<?= htmlspecialchars($search_query) ?>">

                <label style="margin-left: 10px;">區間：</label>
                <input type="date" name="start_date" value="<?= $start_date ?>" required>
                <span>~</span>
                <input type="date" name="end_date" value="<?= $end_date ?>" required>
                
                <button type="submit" class="filter-btn"><i class="bi bi-search"></i> 查詢</button>
                
                <?php if (!empty($search_query) || $start_date != date('Y-m-d') || $end_date != date('Y-m-d')): ?>
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
                    // 決定狀態顏色
                    $badgeClass = 'status-reject';
                    if ($order['status'] == '已取餐') $badgeClass = 'status-done';
                    if ($order['status'] == '已取消') $badgeClass = 'status-cancel';
                    
                    // 顯示時間
                    $show_time = !empty($order['pick_time']) ? $order['pick_time'] : $order['estimate_time'];
                ?>
                    <details class="order-card">
                        <summary>
                            <div>
                                <span style="font-weight: bold; margin-right: 10px;">#<?= $oid ?></span>
                                <span style="font-size: 14px; color: #555;">
                                    <i class="bi bi-calendar3"></i> <?= date('m/d H:i', strtotime($show_time)) ?>
                                </span>
                                <span style="margin-left: 10px; font-size: 14px; color: #0056b3;">
                                    <i class="bi bi-person"></i> <?= $order['student_account'] ?> 
                                    (<?= $order['student_phone'] ?>)
                                </span>
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
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
<?php
session_start();
include "../db.php"; // 引入資料庫連線

$target_account = $_GET['account'] ?? '';

if (empty($target_account)) {
    echo "<script>alert('未指定帳號'); history.back();</script>";
    exit;
}

// 1. 取得店家資訊
$store_name = $target_account;
$store_id = 0;

$stmt = $link->prepare("SELECT store_id, name FROM store WHERE account = ?");
$stmt->bind_param("s", $target_account);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $store_name = $row['name'];
    $store_id = $row['store_id'];
}
$stmt->close();

// --- 2. 接收搜尋參數 ---
$search_query = $_GET['search_query'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>歷史訂單 - <?= htmlspecialchars($store_name) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --green: #3d9462;
            --main-brown: #C19A6B;
            --brown-dark: #5c3d2e;
            --bg-light: #faf7f2;
            --border: #e0dcd6;
            --orange: #d97a2b;
        }

        body {
            font-family: sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            color: var(--brown-dark);
            margin-bottom: 20px;
        }

        /* 頂部資訊與按鈕 */
        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .store-title {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--brown-dark);
            border-left: 5px solid var(--main-brown);
            padding-left: 10px;
        }

        /* 搜尋區塊樣式 */
        .search-box {
            background: var(--bg-light);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }

        .search-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-box input[type="text"],
        .search-box input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-search {
            padding: 8px 16px;
            background: var(--main-brown);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-search:hover {
            background: var(--brown-dark);
        }

        .btn-reset {
            padding: 8px 16px;
            background: #999;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .btn-reset:hover { background: #777; }

        .btn-back {
            padding: 8px 16px;
            background: #6e7073;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-back:hover {
            opacity: 0.9;
        }

        /* 表格樣式 */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        thead {
            background: var(--main-brown);
            color: white;
        }

        thead th {
            padding: 12px;
            text-align: center;
            white-space: nowrap;
        }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: center;
            color: #333;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #fcf8f4;
        }

        .status-tag {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            color: white;
            background: #999;
            display: inline-block;
        }

        .status-wait { background: #d97a2b; }
        .status-done { background: #3d9462; }
        .status-cancel { background: #d9534f; }

        .note-text {
            color: #666;
            font-style: italic;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin: 0 auto;
        }

        /* 明細樣式 */
        .detail-row {
            background-color: #f9f9f9;
            display: none;
        }

        .detail-box {
            text-align: left;
            padding: 15px 40px;
            background: #fffcf5;
            border-left: 4px solid var(--main-brown);
        }

        .toggle-btn {
            cursor: pointer;
            color: var(--main-brown);
            text-decoration: underline;
            font-weight: 600;
        }

        /* 評論與回覆樣式 */
        .reviewed-box { background: #fff8e1; border: 1px solid #ffeeba; padding: 10px; border-radius: 8px; margin-top: 15px; }
        .reviewed-stars { color: #ffc107; font-size: 18px; }
        .reviewed-text { color: #555; margin-top: 5px; font-size: 14px; }
        
        .reply-box {
            margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e6dbb9;
        }
        .reply-title { font-size: 13px; color: #997404; font-weight: bold; margin-bottom: 3px; }
        .reply-content { font-size: 14px; color: #666; background: rgba(255,255,255,0.6); padding: 5px; border-radius: 4px; }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>
    <br>
    <div class="container">
        <div class="info-bar">
            <div class="store-title">店家：<?= htmlspecialchars($store_name) ?> (<?= htmlspecialchars($target_account) ?>)</div>
        </div>

        <h2>歷史訂單列表</h2>

        <form method="GET" class="search-box">
            <input type="hidden" name="account" value="<?= htmlspecialchars($target_account) ?>">
            
            <div class="search-group">
                <label>關鍵字：</label>
                <input type="text" name="search_query" value="<?= htmlspecialchars($search_query) ?>" placeholder="訂單編號 或 訂購人姓名">
            </div>

            <div class="search-group">
                <label>時間區間：</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                <span>~</span>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>

            <button type="submit" class="btn-search">搜尋</button>
            <a href="store_material_history.php?account=<?= htmlspecialchars($target_account) ?>" class="btn-reset">清除條件</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>訂單編號</th>
                    <th>訂購人</th>
                    <th>預計取餐時間</th>
                    <th>實際取餐時間</th>
                    <th>備註</th> 
                    <th>總金額</th>
                    <th>狀態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // --- SQL 查詢與動態條件組合 ---
                
                $sql_base = "";
                $params = [];
                $types = "";

                if ($store_id > 0) {
                    // 店家模式
                    $sql_base = "
                        SELECT 
                            o.order_id, 
                            o.account AS student_account,
                            s_acc.name AS student_name,
                            o.estimate_time, 
                            o.pick_time, 
                            /* 移除 o.payment */
                            o.status,
                            o.note, 
                            SUM(oi.quantity * m.price) as total_price,
                            
                            /* ★ 新增：選取評論與回覆 */
                            mr.rate AS review_rate,
                            mr.description AS review_desc,
                            mr.time AS review_time,        /* 學生評論時間 */
                            mrr.description AS reply_desc,
                            mrr.time AS reply_time         /* 店家回覆時間 */

                        FROM `order` o
                        JOIN `orderitem` oi ON o.order_id = oi.order_id
                        JOIN `menu` m ON oi.menu_id = m.menu_id
                        LEFT JOIN `student` s_acc ON o.account = s_acc.account
                        
                        /* ★ 關聯評論表與回覆表 */
                        LEFT JOIN `mealreview` mr ON o.order_id = mr.order_id
                        LEFT JOIN `mealreviewreply` mrr ON o.order_id = mrr.order_id
                        
                        WHERE m.account = ? 
                    ";
                    $types .= "s";
                    $params[] = $target_account;
                } else {
                    // 備用模式 (直接查 user)
                    $sql_base = "
                        SELECT o.*, o.note, '未知總額' as total_price, s.name as student_name,
                        mr.rate AS review_rate, mr.description AS review_desc, 
                        mr.time AS review_time, /* ★ 新增 */
                        mrr.description AS reply_desc, mrr.time AS reply_time
                        
                        FROM `order` o 
                        LEFT JOIN student s ON o.account = s.account
                        LEFT JOIN `mealreview` mr ON o.order_id = mr.order_id
                        LEFT JOIN `mealreviewreply` mrr ON o.order_id = mrr.order_id
                        
                        WHERE o.account = ? 
                    ";
                    $types .= "s";
                    $params[] = $target_account;
                }

                // --- 動態加入篩選條件 ---
                
                // 1. 搜尋 (訂單編號 OR 訂購人姓名)
                if (!empty($search_query)) {
                    $alias = ($store_id > 0) ? "s_acc" : "s";
                    $sql_base .= " AND (o.order_id LIKE ? OR $alias.name LIKE ?) ";
                    $types .= "ss";
                    $likeTerm = "%" . $search_query . "%";
                    $params[] = $likeTerm;
                    $params[] = $likeTerm;
                }

                // 2. 日期區間 (使用 estimate_time 作為篩選基準)
                if (!empty($start_date)) {
                    $sql_base .= " AND o.estimate_time >= ? ";
                    $types .= "s";
                    $params[] = $start_date . " 00:00:00";
                }

                if (!empty($end_date)) {
                    $sql_base .= " AND o.estimate_time <= ? ";
                    $types .= "s";
                    $params[] = $end_date . " 23:59:59";
                }

                // --- 結尾 GROUP BY 與 ORDER BY ---
                if ($store_id > 0) {
                    $sql_base .= " GROUP BY o.order_id ";
                }
                $sql_base .= " ORDER BY o.estimate_time DESC";

                // --- 執行查詢 ---
                $stmt = $link->prepare($sql_base);
                
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }

                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // 狀態顏色
                        $statusClass = 'status-tag';
                        if (strpos($row['status'], '等待') !== false) $statusClass .= ' status-wait';
                        elseif (strpos($row['status'], '已') !== false) $statusClass .= ' status-done';
                        else $statusClass .= ' status-cancel';

                        $stuName = $row['student_name'] ?? $row['student_account'];
                        $orderNote = !empty($row['note']) ? htmlspecialchars($row['note']) : '<span style="color:#ccc">--</span>';
                        ?>
                        <tr>
                            <td>#<?= $row['order_id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($stuName) ?></strong><br>
                                <small style="color:#888"><?= $row['student_account'] ?></small>
                            </td>
                            <td><?= $row['estimate_time'] ?></td>
                            <td><?= $row['pick_time'] ?? '--' ?></td>
                            
                            <td title="<?= htmlspecialchars($row['note']) ?>">
                                <div class="note-text"><?= $orderNote ?></div>
                            </td>

                            <td style="color: #d9534f; font-weight:bold;">$<?= $row['total_price'] ?></td>
                            <td><span class="<?= $statusClass ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <span class="toggle-btn" onclick="toggleDetail(<?= $row['order_id'] ?>)">查看明細</span>
                            </td>
                        </tr>

                        <tr id="detail-<?= $row['order_id'] ?>" class="detail-row">
                            <td colspan="8"> 
                                <div class="detail-box">
                                    <strong>訂單內容：</strong><br>
                                    <?php
                                    $oid = $row['order_id'];
                                    // 查詢詳細餐點
                                    $sql_items = "
                                        SELECT m.name, oi.quantity, oi.note, m.price 
                                        FROM orderitem oi 
                                        JOIN menu m ON oi.menu_id = m.menu_id 
                                        WHERE oi.order_id = $oid
                                    ";
                                    $res_items = $link->query($sql_items);
                                    if ($res_items) {
                                        while ($item = $res_items->fetch_assoc()) {
                                            echo "• " . htmlspecialchars($item['name']) . " x " . $item['quantity'];
                                            if (!empty($item['note'])) {
                                                echo " <span style='color:#888; font-size:12px;'>(" . htmlspecialchars($item['note']) . ")</span>";
                                            }
                                            echo " - $" . ($item['price'] * $item['quantity']) . "<br>";
                                        }
                                    }
                                    ?>

                                    <?php if (!empty($row['review_rate'])): ?>
                                        <div class="reviewed-box">
                                            <div class="reviewed-stars">
                                                <?php 
                                                for($i=1; $i<=5; $i++) {
                                                    if($i <= $row['review_rate']) echo '<i class="bi bi-star-fill"></i>';
                                                    else echo '<i class="bi bi-star" style="color:#ccc"></i>';
                                                }
                                                ?>
                                                <span style="color:#888; font-size:14px; margin-left:5px;">
                                                    <i class="bi bi-person-fill"></i> (訂購人評價)
                                                    <span style="font-size:12px; color:#999; margin-left:5px;">
                                                        <?= date('Y/m/d', strtotime($row['review_time'])) ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <div class="reviewed-text">
                                                <strong>評論：</strong><?= htmlspecialchars($row['review_desc']) ?>
                                            </div>

                                            <?php if (!empty($row['reply_desc'])): ?>
                                                <div class="reply-box">
                                                    <div class="reply-title">
                                                        <i class="bi bi-shop"></i> 店家回覆 
                                                        <span style="font-weight:normal; color:#999; font-size:12px;">(<?= date('Y/m/d', strtotime($row['reply_time'])) ?>)</span>
                                                    </div>
                                                    <div class="reply-content">
                                                        <?= htmlspecialchars($row['reply_desc']) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <?php
                    }
                } else {
                    echo "<tr><td colspan='8' style='padding:30px; color:#888;'>查無符合條件的訂單</td></tr>";
                }
                $stmt->close();
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function toggleDetail(id) {
            var row = document.getElementById('detail-' + id);
            if (row.style.display === 'table-row') {
                row.style.display = 'none';
            } else {
                row.style.display = 'table-row';
            }
        }
    </script>
</body>

</html>
<?php
session_start();
include "../db.php";

// 設定時區為台灣時間 (重要：否則篩選營業時間會不準)
date_default_timezone_set("Asia/Taipei");

// ==========================================
// ★ 1. AJAX 處理區塊 (維持不變)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cart') {

    // 初始化購物車
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

    if ($menu_id > 0) {
        if ($quantity > 0) {
            $_SESSION['cart'][$menu_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$menu_id]);
        }
    }

    $total_items = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $total_items += $qty;
    }

    echo $total_items;
    exit;
}
// ==========================================

$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';

// 取得所有店家類別
$types_sql = "SELECT * FROM storetype";
$types_result = $link->query($types_sql);

// 處理搜尋與篩選
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$type_filter = isset($_GET['tid']) ? intval($_GET['tid']) : 0;

// 準備撈取店家的 SQL
$sql = "SELECT * FROM store s WHERE 1=1";
$params = [];
$types = "";

// 搜尋關鍵字邏輯
if (!empty($search_query)) {
    $sql .= " AND (
                s.name LIKE ? 
                OR EXISTS (
                    SELECT 1 FROM menu m 
                    WHERE m.account = s.account 
                    AND m.name LIKE ?
                )
              )";
    $term = "%" . $search_query . "%";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}

// 店家類別篩選
if ($type_filter > 0) {
    $sql .= " AND s.storetype_id = ?";
    $params[] = $type_filter;
    $types .= "i";
}

// ★ 新增：營業時間篩選邏輯
// 只有在當前時間有營業的店家才會被撈出來
$current_weekday = date('w'); // 取得星期幾 (0=週日, 1=週一, ... 6=週六)

if ($current_weekday == 0) {
    $current_weekday = 7;
}

$current_time = date('H:i:s'); // 取得目前時間 (HH:mm:ss)

// 注意：如果你的資料庫 weekday 存的是 1-7 (1=週一, 7=週日)，這邊可能需要轉換
// 這裡預設你的資料庫 weekday 也是跟 PHP 一樣 (0=週日 ~ 6=週六)
$sql .= " AND EXISTS (
    SELECT 1 FROM storehours h
    WHERE h.account = s.account
    AND h.weekday = ?
    AND ? >= SUBTIME(h.open_time, '00:30:00')
    AND h.close_time >= ?
  )";

$params[] = $current_weekday;
$params[] = $current_time;
$params[] = $current_time;
$types .= "iss"; // integer, string, string

$stmt = $link->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$store_result = $stmt->get_result();

// 取得目前購物車資料
$cart_data = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>學生/教職員訂餐菜單</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: "Microsoft JhengHei", Arial, sans-serif;
            margin: 20px;
            background: #f2f6fc;
        }



        /* --- 新增：搜尋與篩選區樣式 --- */
        .search-section {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-bar {
            flex: 1;
            display: flex;
            gap: 5px;
        }

        .search-bar input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .search-bar button {
            padding: 10px 20px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .category-nav {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }

        .category-btn {
            padding: 8px 16px;
            background-color: white;
            border: 1px solid #4a90e2;
            color: #4a90e2;
            border-radius: 20px;
            text-decoration: none;
            white-space: nowrap;
            transition: 0.3s;
        }

        .category-btn:hover,
        .category-btn.active {
            background-color: #4a90e2;
            color: white;
        }

        /* --- 新增：店家與菜單列表樣式 --- */
        .store-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #4a90e2;
        }

        .store-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .menu-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            background-color: #fcfcfc;
            position: relative;
        }

        .menu-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: #b3d7ff;
        }

        .menu-name {
            font-weight: bold;
            font-size: 1.1em;
            color: #2c3e50;
        }

        .menu-price {
            color: #e74c3c;
            font-weight: bold;
            margin-top: 5px;
        }

        .menu-desc {
            font-size: 0.9em;
            color: #666;
            margin: 5px 0;
        }

        .menu-stock {
            font-size: 0.8em;
            color: #27ae60;
            background: #eafaf1;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
        }

        .category-title {
            font-size: 18px;
            font-weight: bold;
            color: #4a90e2;
            /* 配合學生頁面的藍色系 */
            border-left: 4px solid #4a90e2;
            padding-left: 10px;
            margin-top: 25px;
            margin-bottom: 15px;
            background-color: #f0f7ff;
            padding-top: 5px;
            padding-bottom: 5px;
            border-radius: 0 5px 5px 0;
        }

        /* --- 店家內部的水平分類導覽列 --- */
        .store-category-nav {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            background: #fff;
            padding: 10px 0;
            margin-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            position: sticky;
            /* 讓導覽列黏在頂部 (視窗捲動時) */
            top: 0;
            z-index: 10;
        }

        .store-category-nav a {
            display: inline-block;
            padding: 8px 16px;
            margin-right: 10px;
            background: #f8f9fa;
            color: #666;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            border: 1px solid #eee;
            transition: 0.2s;
        }

        .store-category-nav a:hover,
        .store-category-nav a.active {
            background: #eef6fc;
            /* 淺藍底 */
            color: #4a90e2;
            /* 深藍字 */
            border-color: #4a90e2;
            font-weight: bold;
        }

        /* 隱藏滾動條但保留功能 */
        .store-category-nav::-webkit-scrollbar {
            height: 4px;
        }

        .store-category-nav::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }

        /* --- 列表式菜單項目 (模仿圖片佈局) --- */
        .menu-category-header {
            font-size: 18px;
            font-weight: bold;
            color: #d35400;
            /* 圖片中的橘紅色系標題 */
            margin-top: 25px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .menu-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            /* 分隔線 */
        }

        .menu-list-item:last-child {
            border-bottom: none;
        }

        /* 左側資訊區 */
        .menu-info {
            flex: 1;
            padding-right: 15px;
        }

        .menu-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .menu-desc {
            font-size: 13px;
            color: #888;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .menu-price {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
        }

        /* 右側圖片與操作區 */
        .menu-action-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* 圖片 Placeholder (圖片中的方框) */
        .menu-img-placeholder {
            width: 80px;
            height: 80px;
            background-color: #fffbf7;
            /* 淡米色背景 */
            border: 1px solid #eee;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #ccc;
            font-size: 24px;
        }

        /* 購物車按鈕樣式 */
        .btn-add-cart {
            background: white;
            border: 1px solid #ddd;
            border-radius: 20px;
            /* 膠囊狀 */
            padding: 5px 12px;
            color: #333;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }

        .btn-add-cart:hover {
            border-color: #4a90e2;
            color: #4a90e2;
            background: #f0f8ff;
        }

        .sold-out-tag {
            font-size: 13px;
            color: #e74c3c;
            background: #fdeaea;
            padding: 5px 10px;
            border-radius: 5px;
        }

        /* --- 兩欄網格排版 --- */
        .menu-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .menu-grid-2 {
                grid-template-columns: 1fr;
            }

            /* 手機版變回單欄 */
        }

        /* --- 卡片內容樣式 --- */
        .menu-card-item {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* 左側資訊 */
        .menu-info-left {
            flex: 1;
            padding-right: 10px;
        }

        .menu-name-text {
            font-size: 18px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        .menu-price-text {
            font-size: 18px;
            font-weight: bold;
            color: #2f7a75;
            margin-top: 5px;
            margin-bottom: 8px;
        }

        /* 標籤 (庫存/時間) */
        .menu-badges {
            font-size: 12px;
            color: #666;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        .badge-time {
            color: #888;
        }

        .badge-stock {
            color: #27ae60;
            background: #eafaf1;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* 右側操作區 */
        .menu-action-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* --- [- 0 +] 膠囊計數器 --- */
        .qty-control-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100px;
            height: 36px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 50px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .qty-btn {
            width: 30px;
            height: 100%;
            border: none;
            background: transparent;
            font-size: 20px;
            color: #2f7a75;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-bottom: 3px;
        }

        .qty-btn:hover {
            background-color: #f7fdfc;
        }

        .qty-display {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            width: 30px;
            text-align: center;
            border: none;
            outline: none;
            background: transparent;
        }

        /* 圖片框 */
        .menu-img-box {
            width: 70px;
            height: 70px;
            border: 2px solid #888;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #2f7a75;
            font-size: 30px;
            background: white;
        }

        /* 售完標籤 */
        .sold-out-text {
            color: #e74c3c;
            font-weight: bold;
            font-size: 14px;
            background: #fdeaea;
            padding: 6px 12px;
            border-radius: 20px;
        }

        /* --- 新增：備註樣式 --- */
        .menu-note {
            font-size: 13px;
            color: #856404;
            /* 深黃色字 */
            background-color: #fff3cd;
            /* 淺黃色底 */
            border: 1px solid #ffeeba;
            /* 邊框 */
            padding: 5px 10px;
            border-radius: 6px;
            margin-top: 8px;
            display: inline-block;
            line-height: 1.4;
        }

        /* ====== 公告與區塊通用樣式 ====== */
        #b {
            background-color: transparent;
            margin: 20px auto;
            max-width: 1200px;
        }

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
    </style>
</head>

<body>
    <?php include "student_menu.php"; ?>

    <?php
    $total_in_cart = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $q) $total_in_cart += $q;
    }
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var badge = document.getElementById('global-cart-count');
            if (badge) badge.innerText = "<?= $total_in_cart ?>";
        });
    </script>

    <br>
    <br>
    <div class="search-section">
        <form action="" method="GET" class="search-bar">
            <?php if ($type_filter): ?>
                <input type="hidden" name="tid" value="<?= $type_filter ?>">
            <?php endif; ?>

            <input type="text" name="q" placeholder="輸入店家名稱搜尋..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit"><i class="bi bi-search"></i> 搜尋</button>

            <?php if ($search_query || $type_filter): ?>
                <a href="student_menumanage.php" style="padding: 10px; color: #666;">清除篩選</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="category-nav">
        <a href="student_menumanage.php" class="category-btn <?= ($type_filter == 0) ? 'active' : '' ?>">
            全部店家
        </a>

        <?php
        if ($types_result->num_rows > 0) {
            while ($row = $types_result->fetch_assoc()) {
                $isActive = ($type_filter == $row['storetype_id']) ? 'active' : '';
                echo '<a href="student_menumanage.php?tid=' . $row['storetype_id'] . '" class="category-btn ' . $isActive . '">';
                echo htmlspecialchars($row['name']);
                echo '</a>';
            }
        }
        ?>
    </div>
    <div id="b" style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
        <div class="dashboard-card">
            <h2 class="dashboard-title"><i class="bi bi-megaphone"></i> 店家公告</h2>
            <div style="max-height: 150px; overflow-y: auto;">
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
        </div>
    </div>

    <div id="store-list">
        <?php
        $has_any_store_printed = false;
        if ($store_result->num_rows > 0):
            while ($store = $store_result->fetch_assoc()):
                $store_account = $store['account'];
                $menu_sql = "SELECT * FROM menu WHERE account = ? ORDER BY type, price";
                $menu_stmt = $link->prepare($menu_sql);
                $menu_stmt->bind_param("s", $store_account);
                $menu_stmt->execute();
                $menu_result = $menu_stmt->get_result();

                if ($menu_result->num_rows === 0) {
                    $menu_stmt->close();
                    continue;
                }
                $has_any_store_printed = true;

                $grouped_menu = [];
                while ($row = $menu_result->fetch_assoc()) {
                    $type = $row['type'];
                    if (empty($type)) $type = "其他";
                    $grouped_menu[$type][] = $row;
                }
                $menu_stmt->close();
                $store_uid = "store_" . $store['store_id'];
        ?>

                <div class="store-container">
                    <div class="store-title">
                        <span><i class="bi bi-shop"></i> <?= htmlspecialchars($store['name']) ?></span>

                        <small style="color:#888; font-size:14px; font-weight:normal;">
                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($store['phone']) ?>
                            <span style="margin: 0 8px; color:#ccc;">|</span>
                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($store['address']) ?>
                        </small>
                    </div>

                    <div class="store-category-nav">
                        <?php foreach (array_keys($grouped_menu) as $type_name): ?>
                            <a href="#cat-<?= $store_uid ?>-<?= htmlspecialchars($type_name) ?>">
                                <?= htmlspecialchars($type_name) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="menu-content-list">
                        <?php foreach ($grouped_menu as $type => $items): ?>
                            <div id="cat-<?= $store_uid ?>-<?= htmlspecialchars($type) ?>" class="menu-category-header">
                                <?= htmlspecialchars($type) ?>
                            </div>
                            <div class="menu-grid-2">
                                <?php foreach ($items as $menu):
                                    $menu_id = $menu['menu_id'];
                                    $max_stock = $menu['stock'];
                                    $current_qty_in_cart = isset($cart_data[$menu_id]) ? $cart_data[$menu_id] : 0;
                                ?>
                                    <div class="menu-card-item">
                                        <div class="menu-info-left">
                                            <div class="menu-name-text"><?= htmlspecialchars($menu['name']) ?></div>
                                            <?php if (!empty($menu['description'])): ?>
                                                <div style="font-size:13px; color:#999; margin-top:5px;"><?= htmlspecialchars($menu['description']) ?></div>
                                            <?php endif; ?>
                                            <div class="menu-price-text">$<?= number_format($menu['price']) ?></div>
                                            <div class="menu-badges">
                                                <span class="badge-stock">庫存: <?= $max_stock ?></span>
                                                <?php if (!empty($menu['cook_time']) && $menu['cook_time'] != '00:00:00'): ?>
                                                    <span class="badge-time"><i class="bi bi-clock"></i> <?= substr($menu['cook_time'], 3, 2) ?>分</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($menu['note'])): ?>
                                                <div class="menu-note"><i class="bi bi-info-circle-fill"></i> 備註：<?= htmlspecialchars($menu['note']) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="menu-action-right">
                                            <?php if ($max_stock > 0): ?>
                                                <div class="qty-control-pill">
                                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $menu_id ?>, -1, <?= $max_stock ?>)">−</button>
                                                    <input type="text" id="qty-<?= $menu_id ?>" class="qty-display" value="<?= $current_qty_in_cart ?>" readonly>
                                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $menu_id ?>, 1, <?= $max_stock ?>)">+</button>
                                                </div>
                                            <?php else: ?>
                                                <span class="sold-out-text">已售完</span>
                                            <?php endif; ?>
                                            <div class="menu-img-box" style="overflow:hidden; position:relative; padding:0;">
                                                <?php if (!empty($menu['img_id'])): ?>
                                                    <img src="../store/get_image.php?id=<?= $menu['img_id'] ?>"
                                                        alt="<?= htmlspecialchars($menu['name']) ?>"
                                                        style="width:100%; height:100%; object-fit:cover; border-radius:8px;">
                                                <?php else: ?>
                                                    <i class="bi bi-cup-hot"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center; padding:50px; color:#666;">
                <i class="bi bi-search" style="font-size:48px; display:block; margin-bottom:10px;"></i>
                <p>找不到符合條件或目前營業中的店家</p>
                <small>(可能是目前非營業時段)</small>
            </div>
        <?php endif; ?>
    </div>

</body>
<script>
    // --- 下拉選單邏輯 (維持不變) ---
    function toggleSubMenu() {
        var sub = document.getElementById("subMenu");
        sub.style.display = (sub.style.display === "block") ? "none" : "block";
    }

    function toggleDropdown() {
        var dropdown = document.getElementById("myDropdown");
        dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
    }

    window.onclick = function(event) {
        if (!event.target.closest('.dropdown')) {
            document.getElementById("myDropdown").style.display = "none";
        }
    }

    // --- ★ 重點修改：數量控制與購物車連動 ---

    function updateQty(menuId, change, maxStock) {
        // 1. 抓取該商品的 input 欄位
        var qtyInput = document.getElementById('qty-' + menuId);

        // 防呆：如果找不到欄位就結束
        if (!qtyInput) return;

        // 2. 取得「變動前」的數量
        var currentQty = parseInt(qtyInput.value) || 0;

        // 3. 計算「預期的新數量」
        var newQty = currentQty + change;

        // 4. 檢查限制 (不能小於 0，不能大於庫存)
        if (newQty < 0) {
            newQty = 0;
        }
        if (newQty > maxStock) {
            alert("已達庫存上限！目前剩餘 " + maxStock + " 份");
            newQty = maxStock;
        }

        // 5. ★ 關鍵：計算「實際變動量」
        // 例如：原本是 0，按 -1，變成 0。實際變動量 = 0 - 0 = 0 (購物車不變)
        // 例如：原本是 0，按 +1，變成 1。實際變動量 = 1 - 0 = +1 (購物車 +1)
        var actualChange = newQty - currentQty;

        // 6. 更新該商品的輸入框顯示
        qtyInput.value = newQty;

        // 7. 如果實際數量有變，就更新右上角購物車
        if (actualChange !== 0) {
            updateGlobalCartCount(actualChange);
        }

        // (進階：這裡可以加 AJAX 去通知後端 session 更新購物車)
        // updateSessionCart(menuId, newQty);
    }

    // ★ 輔助函式：更新右上角購物車總數
    function updateGlobalCartCount(diff) {
        var badge = document.getElementById('global-cart-count');

        // 確保 header 有載入且抓得到這個 ID
        if (badge) {
            var currentTotal = parseInt(badge.innerText) || 0;
            var newTotal = currentTotal + diff;

            // 確保總數不會變負的
            if (newTotal < 0) newTotal = 0;

            badge.innerText = newTotal;

            // 加一個簡單的縮放動畫，讓使用者注意到數字變了
            badge.style.transition = "transform 0.2s";
            badge.style.transform = "scale(1.5)";
            setTimeout(function() {
                badge.style.transform = "scale(1)";
            }, 200);
        }
    }

    // ★ 修改後的 AJAX 邏輯 (Fetch 同一個檔案)
    function updateQty(menuId, change, maxStock) {
        var qtyInput = document.getElementById('qty-' + menuId);
        if (!qtyInput) return;

        var currentQty = parseInt(qtyInput.value) || 0;
        var newQty = currentQty + change;

        if (newQty < 0) newQty = 0;
        if (newQty > maxStock) {
            alert("已達庫存上限！");
            newQty = maxStock;
        }

        // 1. 前端先更新數字，給使用者即時回饋
        qtyInput.value = newQty;

        // 2. 準備 AJAX 資料
        var formData = new FormData();
        formData.append('action', 'update_cart'); // ★ 告訴 PHP 我要執行更新購物車
        formData.append('menu_id', menuId);
        formData.append('quantity', newQty);

        // 3. 發送到當前頁面 (student_menumanage.php)
        fetch('student_menumanage.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(totalItems => {
                // 4. 更新右上角購物車
                var badge = document.getElementById('global-cart-count');
                if (badge) {
                    badge.innerText = totalItems;
                    badge.style.transform = "scale(1.5)";
                    setTimeout(() => badge.style.transform = "scale(1)", 200);
                }
            })
            .catch(error => console.error('Error:', error));
    }
</script>

</html>
<?php
$stmt->close();
$link->close();
?>
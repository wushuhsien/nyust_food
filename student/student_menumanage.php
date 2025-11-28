<?php
session_start();
include "../db.php"; // 確保連結資料庫

$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';

// 1. 取得所有店家類別 (用於上方分類按鈕)
$types_sql = "SELECT * FROM storetype";
$types_result = $link->query($types_sql);

// 2. 處理搜尋與篩選邏輯
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$type_filter = isset($_GET['tid']) ? intval($_GET['tid']) : 0;

// 準備撈取店家的 SQL (預設撈全部)
$sql = "SELECT * FROM store WHERE 1=1";
$params = [];
$types = "";

// 如果有搜尋名稱
if (!empty($search_query)) {
    $sql .= " AND name LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= "s";
}

// 如果有選類別
if ($type_filter > 0) {
    $sql .= " AND storetype_id = ?";
    $params[] = $type_filter;
    $types .= "i";
}

$stmt = $link->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$store_result = $stmt->get_result();
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
    </style>
</head>

<body>
    <?php include "student_menu.php"; ?>
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

    <div id="store-list">
        <?php
        $has_any_store_printed = false;

        if ($store_result->num_rows > 0):
            ?>
            <?php while ($store = $store_result->fetch_assoc()): ?>

                <?php
                // 1. 撈取該店家的菜單
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

                // 2. 資料分組
                $grouped_menu = [];
                while ($row = $menu_result->fetch_assoc()) {
                    $type = $row['type'];
                    if (empty($type))
                        $type = "其他";
                    $grouped_menu[$type][] = $row;
                }
                $menu_stmt->close();

                // 產生隨機 ID 避免錨點衝突 (用店家 ID)
                $store_uid = "store_" . $store['store_id'];
                ?>

                <div class="store-container">

                    <div class="store-title">
                        <span><i class="bi bi-shop"></i> <?= htmlspecialchars($store['name']) ?></span>
                        <small style="color:#888; font-size:14px; font-weight:normal;">
                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($store['phone']) ?>
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
                                    ?>
                                    <div class="menu-card-item">

                                        <div class="menu-info-left">
                                            <div class="menu-name-text"><?= htmlspecialchars($menu['name']) ?></div>

                                            <?php if (!empty($menu['description'])): ?>
                                                <div style="font-size:13px; color:#999; margin-top:5px;">
                                                    <?= htmlspecialchars($menu['description']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="menu-price-text">$<?= number_format($menu['price']) ?></div>

                                            <div class="menu-badges">
                                                <span class="badge-stock">庫存: <?= $max_stock ?></span>
                                                <?php if (!empty($menu['cook_time']) && $menu['cook_time'] != '00:00:00'): ?>
                                                    <span class="badge-time">
                                                        <i class="bi bi-clock"></i> <?= substr($menu['cook_time'], 3, 2) ?>分
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($menu['note'])): ?>
                                                <div class="menu-note">
                                                    <i class="bi bi-info-circle-fill"></i> 備註：<?= htmlspecialchars($menu['note']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="menu-action-right">

                                            <?php if ($max_stock > 0): ?>
                                                <div class="qty-control-pill">
                                                    <button type="button" class="qty-btn"
                                                        onclick="updateQty(<?= $menu_id ?>, -1, <?= $max_stock ?>)">−</button>
                                                    <input type="text" id="qty-<?= $menu_id ?>" class="qty-display" value="0" readonly>
                                                    <button type="button" class="qty-btn"
                                                        onclick="updateQty(<?= $menu_id ?>, 1, <?= $max_stock ?>)">+</button>
                                                </div>
                                            <?php else: ?>
                                                <span class="sold-out-text">已售完</span>
                                            <?php endif; ?>

                                            <div class="menu-img-box">
                                                <i class="bi bi-cup-hot"></i>
                                            </div>

                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endwhile; ?>

            <?php if (!$has_any_store_printed): ?>
                <div style="text-align:center; padding:50px; color:#666;">
                    <i class="bi bi-clipboard-x" style="font-size:48px; display:block; margin-bottom:10px;"></i>
                    符合條件的店家目前皆尚未上架菜單。
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align:center; padding:50px; color:#666;">
                <i class="bi bi-search" style="font-size:48px; display:block; margin-bottom:10px;"></i>
                找不到符合條件的店家
            </div>
        <?php endif; ?>
    </div>

</body>
<script>
    // 原本的 Dropdown JS 邏輯
    function toggleSubMenu() {
        var sub = document.getElementById("subMenu");
        sub.style.display = (sub.style.display === "block") ? "none" : "block";
    }

    function toggleDropdown() {
        var dropdown = document.getElementById("myDropdown");
        dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
    }

    window.onclick = function (event) {
        if (!event.target.closest('.dropdown')) {
            document.getElementById("myDropdown").style.display = "none";
        }
    }

    // ★ 新增：控制數量的 JavaScript 函式
    function updateQty(menuId, change, maxStock) {
        // 抓取該商品的 input 欄位
        var qtyInput = document.getElementById('qty-' + menuId);

        // 確保欄位存在
        if (!qtyInput) return;

        // 計算新數值
        var currentQty = parseInt(qtyInput.value) || 0;
        var newQty = currentQty + change;

        // 檢查最小與最大限制
        if (newQty < 0) {
            newQty = 0;
        }
        if (newQty > maxStock) {
            alert("已達庫存上限！目前剩餘 " + maxStock + " 份");
            newQty = maxStock;
        }

        // 更新顯示
        qtyInput.value = newQty;

        // (可以在這裡加 AJAX 程式碼，將數量同步到後端購物車 Session)
        // console.log("餐點 ID: " + menuId + ", 數量更新為: " + newQty);
    }
</script>

</html>
<?php
$stmt->close();
$link->close();
?>
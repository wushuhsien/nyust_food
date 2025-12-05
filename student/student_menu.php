<?php
include "../db.php";  // 引入資料庫連線
// 判斷 session 是否已啟動，若無則啟動 (防止重複啟動錯誤)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';

// ★ 新增：計算目前 Session 購物車內的總數量
$total_cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $total_cart_count += intval($qty);
    }
}

// 設定時區
date_default_timezone_set('Asia/Taipei');

// 如果帳號存在，寫入 OUT 動作
if ($account !== '') {
    $currentTime = date("Y-m-d H:i:s");
    $insertSql = "INSERT INTO accountaction (time, action, account) VALUES (?, 'OUT', ?)";
    $insertStmt = $link->prepare($insertSql);
    $insertStmt->bind_param("ss", $currentTime, $account);
    $insertStmt->execute();
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>學生/教職員menu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20;
            background: #f2f6fc;
        }

        /* 上方橙色條 */
        .top-menu {
            background-color: #4a90e2;
            display: flex;
            align-items: center;
            padding: 0 30px;
            border-radius: 10px;
            height: 70px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .top-menu h1 {
            color: #ffffff;
            font-size: 22px;
            margin: 0;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .top-menu h1:hover {
            color: #e8f3ff;
            /* hover 顏色變淡橙 */
        }

        /* 帳號與齒輪設定 */
        #top-right-box {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: auto;
            /* 推到最右 */
            position: relative;
        }

        .user-account {
            color: white;
            font-size: 16px;
            font-weight: bold;
        }

        .dropdown {
            position: relative;
        }

        .dropbtn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .dropbtn i {
            font-size: 26px;
            color: white;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9faff;
            min-width: 150px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.15);
            border-radius: 6px;
            z-index: 100; /* 這裡改大一點，避免被遮住 */
            border: 1px solid #e8f3ff;
        }

        .dropdown-content input[type="button"] {
            width: 100%;
            padding: 10px 12px;
            border: none;
            background-color: #f9faff;
            text-align: left;
            cursor: pointer;
            border-bottom: 1px solid #d6e9ff;
            font-size: 14px;
        }

        .dropdown-content input[type="button"]:hover {
            background-color: #72a7e4ff;
            color: white;
        }

        .dropdown-content input[type="button"]:last-child {
            border-bottom: none;
        }

        .sub-dropdown {
            display: none;
            background-color: #f9faff;
            border-left: 3px solid #4a90e2;
        }

        .sub-dropdown input[type="button"] {
            padding-left: 20px;
        }

        /* ★ 新增：購物車樣式 */
        .cart-container {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            margin-right: 15px; /* 與帳號名稱拉開距離 */
        }
        .cart-icon {
            font-size: 24px;
            color: white;
        }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background-color: #e74c3c; /* 紅色圓圈 */
            color: white;
            font-size: 12px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 2px solid #4a90e2;
        }
    </style>
</head>

<body>
    <div class="top-menu">
        <h1 style="cursor:pointer;" onclick="window.location.href='student.php'">學生/教職員首頁</h1>

        <div id="top-right-box">
            <div class="cart-container" onclick="window.location.href='student_cart.php'">
                <i class="bi bi-cart-fill cart-icon"></i>
                <span id="global-cart-count" class="cart-badge"><?= $total_cart_count ?></span>
            </div>

            <div class="user-account"><?php echo htmlspecialchars($account); ?></div>
            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <i class="bi bi-gear"></i>
                </button>
                <div id="myDropdown" class="dropdown-content">
                    <input type="button" value="個人設定 ▼" onclick="toggleSubMenu()">
                    <div id="subMenu" class="sub-dropdown">
                        <input type="button" value="基本資料" onclick="window.location='student_information.php'">
                        <input type="button" value="歷史訂單" onclick="window.location='student_history.php'">
                        <input type="button" value="評價紀錄" onclick="alert('評價紀錄')">
                    </div>
                    <input type="button" value="問題" onclick="window.location='student_report.php'">
                    <input type="button" value="登出" onclick="window.location='../login.html'">
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    // 下拉選單邏輯
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
    // 控制數量 (UpdateQty) 的 JS 不需要放在這裡，因為這是 Header，
    // UpdateQty 邏輯應該在 student_menumanage.php 裡面。
</script>

</html>
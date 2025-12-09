<?php
include "../db.php";  // 引入資料庫連線
$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';

// // 設定時區
// date_default_timezone_set('Asia/Taipei');

// // 如果帳號存在，寫入 OUT 動作
// if ($account !== '') {
//     $currentTime = date("Y-m-d H:i:s");
//     $insertSql = "INSERT INTO accountaction (time, action, account) VALUES (?, 'OUT', ?)";
//     $insertStmt = $link->prepare($insertSql);
//     $insertStmt->bind_param("ss", $currentTime, $account);
//     $insertStmt->execute();
// }
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>管理員menu</title>
    <link rel="stylesheet" href="../css/admin_menu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #F3ECE6;
            /* 原本偏冷的藍改成溫暖淡米背景 */
        }

        .user-settings {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: auto;
            position: relative;
            /* ✅ 讓下拉定位以這個按鈕為基準 */
        }

        .dropbtn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2em;
            color: white;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            /* ✅ 讓它出現在按鈕正下方 */
            left: 0;
            /* ✅ 左邊與按鈕對齊 */
            background-color: #f9faff;
            min-width: 150px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.15);
            border-radius: 6px;
            z-index: 1;
            border: 1px solid #D7C1B2;
        }

        .dropdown-content input[type="button"] {
            width: 100%;
            padding: 10px 12px;
            border: none;
            background-color: #f9faff;
            text-align: left;
            cursor: pointer;
            border-bottom: 1px solid #D7C1B2;
            font-size: 14px;
        }

        .dropdown-content input[type="button"]:hover {
            background-color: #C19A6B;
            color: white;
        }

        .dropdown-content input[type="button"]:last-child {
            border-bottom: none;
        }

        .sub-dropdown {
            display: none;
            background-color: #f9faff;
            border-left: 3px solid #8B5E3C;
        }

        .sub-dropdown input[type="button"] {
            padding-left: 20px;
        }

        .account {
            padding: 0 5px;
            /* 左右各 5px 空白 */
        }
    </style>
</head>

<body>
    <div class="top-menu">
        <h1 style="cursor:pointer;" onclick="window.location.href='admin.php'">管理員首頁</h1>

        <div class="menu-items">

            <div class="menu-item">使用者資料管理
                <div class="dropdown">
                    <a href="student_material.php">學生/教職員</a>
                    <a href="store_material.php">店家</a>
                    <a href="admin_store_material.php">待審核店家</a>
                </div>
            </div>
            <div class="menu-item">公告管理
                <div class="dropdown">
                    <a href="store-announcement.php">店家</a>
                    <a href="admin-announcement.php">管理員</a>
                </div>
            </div>
            <div class="menu-item">問題管理
                <div class="dropdown">
                    <a href="store_report.php">店家</a>
                    <a href="admin_report.php">管理員</a>
                </div>
            </div>
            <div class="menu-item">
                <div class="user-settings">
                    <button class="dropbtn" onclick="toggleDropdown()">
                        <span class="account"><?php echo htmlspecialchars($account); ?></span>
                        <i class="bi bi-gear"></i>
                    </button>
                    <div id="userDropdown" class="dropdown-content">
                        <input type="button" value="管理員設定 ▼" onclick="toggleSubMenu1()">
                        <div id="subMenu1" class="sub-dropdown">
                            <input type="button" value="管理員資料" onclick="window.location='admin_information.php'">
                        </div>
                        <input type="button" value="登出" onclick="window.location='../login.html'">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    function toggleSubMenu1() {
        var sub = document.getElementById("subMenu1");
        sub.style.display = (sub.style.display === "block") ? "none" : "block";
    }

    function toggleDropdown() {
        var dropdown = document.getElementById("userDropdown"); // 改這裡
        dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
    }

    window.onclick = function(event) {
        if (!event.target.closest('.user-settings')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                dropdowns[i].style.display = "none";
            }
        }
    }
</script>

</html>
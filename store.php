<?php
session_start(); 
include "db.php"; 
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>店家首頁</title>
<!-- 引入 Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #fdf6f0; /* 淡咖啡色背景 */
    }

    #a { /* 頂部橙色欄 */
        background-color: #f28c28;
        height: 60px;
        text-align: center;
        line-height: 60px; /* 垂直置中 */
        color: white;
        position: relative;
        font-size: 22px;
        font-weight: bold;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        border-radius: 8px;
    }

    #b {
        background-color: #fff7f0; /* 淡橙色 */
        margin: 20px auto;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        max-width: 700px;
        overflow-y: auto; 
        text-align: left;
        border: 1px solid #f0d4b2;
    }

    #b h1 {
        font-size: 22px;
        margin-top: 0;
        color: #b35c00;
    }

    .announcement {
        background-color: #fff3e6;
        border: 1px solid #f2c79e;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 15px;
        box-shadow: 1px 2px 6px rgba(0,0,0,0.05);
    }
    .announcement p {
        margin: 6px 0;
        line-height: 1.5;
    }

    /*帳號*/
    #top-right-box {
        position: absolute;
        top: 0;
        right: 15px;
        height: 60px; 
        display: flex;
        align-items: center;
        gap: 12px;
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
        background-color: #fff8f0;
        min-width: 150px;
        box-shadow: 0px 4px 10px rgba(0,0,0,0.15);
        border-radius: 6px;
        z-index: 1;
        border: 1px solid #f2c79e;
    }

    .dropdown-content input[type="button"] {
        width: 100%;
        padding: 10px 12px;
        border: none;
        background-color: #fff8f0;
        text-align: left;
        cursor: pointer;
        border-bottom: 1px solid #f2c79e;
        font-size: 14px;
    }

    .dropdown-content input[type="button"]:hover {
        background-color: #f2c79e;
        color: white;
    }

    .dropdown-content input[type="button"]:last-child {
        border-bottom: none;
    }

    .sub-dropdown {
        display: none;
        background-color: #fff0e0;
        border-left: 3px solid #f28c28;
    }

    .sub-dropdown input[type="button"] {
        padding-left: 20px;
    }
</style>
</head>
<body>
    <div id="a">
        <?php $account = $_SESSION['user'] ?? "未登入"; ?>
        <h1>店家首頁</h1>
        <div id="top-right-box">
            <div class="user-account"><?php echo htmlspecialchars($account); ?></div>
            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <i class="bi bi-gear"></i>
                </button>
                <div id="myDropdown" class="dropdown-content">
                    <input type="button" value="店家設定 ▼" onclick="toggleSubMenu1()">
                    <div id="subMenu1" class="sub-dropdown">
                        <input type="button" value="基本資料" onclick="window.location='store_information.php'">
                    </div>
                    <input type="button" value="店家管理 ▼" onclick="toggleSubMenu2()">
                    <div id="subMenu2" class="sub-dropdown">
                        <input type="button" value="店家資料" onclick="alert('店家資料')">
                        <input type="button" value="菜單管理" onclick="alert('菜單管理')">
                        <input type="button" value="公告管理" onclick="alert('公告管理')">
                        <input type="button" value="歷史訂單" onclick="alert('歷史訂單')">
                        <input type="button" value="評價紀錄" onclick="alert('評價紀錄')">
                    </div>
                    <input type="button" value="問題" onclick="alert('問題按鈕')">
                    <input type="button" value="登出" onclick="window.location='login.html'">
                </div>
            </div>
        </div>
    </div>

    <div id="b">
        <h1>公告</h1>
        <?php
            $sql = "SELECT `announcement_id`, `topic`, `description`, `start_time`, `end_time`, `type`, `account`
                    FROM `announcement`
                    WHERE `type`='店休' OR `type`='公告'
					AND `start_time` <= NOW()
                    AND `end_time` >= NOW()
                    ORDER BY `start_time` DESC";

            $result = $link->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="announcement">';
                    echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                    echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p>目前沒有公告。</p>";
            }
        ?>
    </div>

<script>
    function toggleSubMenu1() {
        var sub = document.getElementById("subMenu1");
        sub.style.display = (sub.style.display === "block") ? "none" : "block";
    }

    function toggleSubMenu2() {
        var sub = document.getElementById("subMenu2");
        sub.style.display = (sub.style.display === "block") ? "none" : "block";
    }

    function toggleDropdown() {
        var dropdown = document.getElementById("myDropdown");
        dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
    }

    window.onclick = function(event) {
        if (!event.target.closest('.dropdown')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                dropdowns[i].style.display = "none";
            }
        }
    }
</script>
</body>
</html>

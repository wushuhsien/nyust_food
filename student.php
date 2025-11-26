<?php
session_start();
include "db.php";
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>學生/教職員首頁</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20;
            background: #f2f6fc;
        }

        /* ====== 頂部藍色標題 ====== */
        #a {
            background-color: #4a90e2;
            height: 60px;
            text-align: center;
            line-height: 60px;
            /* 垂直置中 */
            color: white;
            position: relative;
            font-size: 22px;
            font-weight: bold;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        #b {
            background-color: #f9fbff;
            /* 淡橙色 */
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 700px;
            overflow-y: auto;
            text-align: left;
            border: 1px solid #4a90e2;
        }

        #b h1 {
            font-size: 22px;
            margin-top: 0;
            color: #4a90e2;
        }

        /* ====== 右上角帳號與齒輪 ====== */
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
            font-size: 24px;
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
            z-index: 1;
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
            background: #b5cee8ff;
            color: white;
        }

        .dropdown-content input[type="button"]:last-child {
            border-bottom: none;
        }

        .sub-dropdown {
            display: none;
            background: #f9faff;
            border-left: 3px solid #4a90e2;
        }

        .sub-dropdown input[type="button"] {
            padding-left: 20px;
        }

        /* ====== 公告 ====== */

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

        /* ====== 店家類型 ====== */
        #c {
            background-color: #f9fbff;
            /* 淡橙色 */
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 700px;
            overflow-y: auto;
            text-align: left;
            border: 1px solid #4a90e2;

        }

        #c h3 {
            margin: 0 0 10px;
            color: #333;
        }

        .storetype-box {
            display: inline-block;
            padding: 10px 15px;
            margin-right: 10px;
            background-color: #e8f3ff;
            border: 1px solid #4a90e2;
            border-radius: 10px;
            font-size: 16px;
            text-decoration: none;
            color: #305a96;
            transition: 0.2s;
        }

        .storetype-box:hover {
            background-color: #d6e9ff;
        }
    </style>
</head>

<body>
    <div id="a">
        <h1>學生/教職員首頁</h1>
        <div id="top-right-box">
            <div class="user-account"><?php echo htmlspecialchars($_SESSION['user'] ?? "未登入"); ?></div>
            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <i class="bi bi-gear"></i>
                </button>
                <div id="myDropdown" class="dropdown-content">
                    <input type="button" value="個人設定 ▼" onclick="toggleSubMenu()">
                    <div id="subMenu" class="sub-dropdown">
                        <input type="button" value="基本資料" onclick="window.location='student_information.php'">
                        <input type="button" value="歷史訂單" onclick="alert('歷史訂單')">
                        <input type="button" value="評價紀錄" onclick="alert('評價紀錄')">
                    </div>
                    <input type="button" value="問題" onclick="alert('問題按鈕')">
                    <input type="button" value="登出" onclick="window.location='login.html'">
                </div>
            </div>
        </div>
    </div>

    <!-- ====== 公告區 ====== -->
    <div id="b">
        <h1>公告</h1>
        <?php
        $sql = "SELECT `topic`, `description`, `start_time`, `end_time`
                    FROM `announcement`
                    WHERE (`type`='店休' OR `type`='公告')
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

    <!-- ====== 店家類型區 ====== -->
    <div id="c">
        <h3>店家類型</h3>
        <?php
        $sql2 = "SELECT `storetype_id`, `name` FROM `storetype`";
        $result2 = $link->query($sql2);
        if ($result2->num_rows > 0) {
            while ($row2 = $result2->fetch_assoc()) {
                $id = $row2['storetype_id'];
                $name = htmlspecialchars($row2['name']);
                echo '<a class="storetype-box" href="store_list.php?type=' . $id . '">' . $name . '</a>';
            }
        } else {
            echo "<p>沒有店家類型資料。</p>";
        }
        ?>
    </div>

    <script>
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
    </script>
</body>

</html>
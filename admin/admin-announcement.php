<?php
session_start();
include "../db.php";  // 引入資料庫連線
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>管理員公告</title>

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            margin: 20px;
            background-color: #E8EEFF;
            /* 淡藍背景 */
        }

        /* 包公告卡片的容器 */
        #b {
            background-color: #FFFFFF;
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 750px;
            text-align: left;
            border: 1px solid #CBD5E1;
        }

        #b h1 {
            font-size: 22px;
            margin-top: 0;
            color: #1E3A8A;
        }

        /* 公告卡片 */
        .announcement {
            background-color: #F8FAFF;
            border: 1px solid #C7D2FE;
            border-radius: 8px;
            padding: 15px 18px;
            margin-bottom: 15px;
            position: relative;
        }

        .announcement p {
            margin: 6px 0;
            line-height: 1.5;
            color: #1E293B;
        }

        /* 卡片右上角按鈕區 */
        .announcement .btn-area {
            position: absolute;
            right: 12px;
            top: 12px;
            display: flex;
            gap: 8px;
        }

        .edit-btn,
        .delete-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            color: white;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #2563EB;
            /* 雲科藍 */
        }

        .edit-btn:hover {
            background-color: #1D4ED8;
        }

        .delete-btn {
            background-color: #DC2626;
            /* 紅色 */
        }

        .delete-btn:hover {
            background-color: #B91C1C;
        }

        /* 帳號右上角 */
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
        }

        .dropbtn i {
            font-size: 26px;
            color: white;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            border-radius: 6px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 100;
            border: 1px solid #C7D2FE;
        }

        .dropdown-content input[type="button"] {
            width: 100%;
            padding: 10px 12px;
            border: none;
            background-color: white;
            text-align: left;
            cursor: pointer;
            border-bottom: 1px solid #E2E8F0;
        }

        .dropdown-content input[type="button"]:hover {
            background-color: #EFF6FF;
            color: #1E3A8A;
        }

        .dropdown-content input:last-child {
            border-bottom: none;
        }

        .sub-dropdown {
            display: none;
            background-color: #F1F5FF;
        }

        .sub-dropdown input {
            padding-left: 22px;
        }
    </style>

</head>

<body>
    <?php include "admin_menu.php"; ?>

    <div id="b">
        <h1>管理員公告</h1>

        <!-- 工具列在同一行 -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">

            <!-- 日期篩選 -->
            <form method="GET" style="display:flex; gap:10px; align-items:center; margin:0;">
                <label>開始日期：</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">

                <label>結束日期：</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">

                <button type="submit" style="
                padding:6px 12px;
                background:#2563EB;
                color:white;
                border:none;
                border-radius:6px;
                cursor:pointer;
            ">查詢</button>
            </form>

            <!-- 新增公告按鈕 -->
            <button onclick="location.href='admin-insert-announcement.php'"
                style="
                padding: 8px 14px;
                background: #1E3A8A;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                cursor: pointer;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            ">
                新增公告
            </button>
        </div>

        <?php
        // 日期篩選檢查
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';

        if (!empty($start_date) && !empty($end_date)) {
            if ($start_date > $end_date) {
                echo "<script>alert('開始日期不能大於結束日期'); history.back();</script>";
                exit();
            }
        }

        // 取得今天日期與時間
        $now = date("Y-m-d H:i:s");

        // SQL：只查今天有效的公告
        $sql = "SELECT * FROM announcement
        WHERE type='公告'
        AND start_time <= '$now'
        AND end_time >= '$now'
        ORDER BY start_time DESC";

        $result = $link->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="announcement">';
                echo '<div class="btn-area">';
                echo '<button class="edit-btn" onclick="location.href=\'admin-update-announcement.php?id=' . $row['announcement_id'] . '\'">修改</button>';
                echo '<button class="delete-btn" onclick="deleteAnnouncement(' . $row['announcement_id'] . ')">刪除</button>';
                echo '</div>';

                echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) .
                    ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                echo '</div>';
            }
        } else {
            echo "<p>目前沒有公告。</p>";
        }
        ?>

    </div>

    <script>
        function toggleDropdown() {
            let menu = document.getElementById("myDropdown");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        function deleteAnnouncement(id) {
            if (confirm("確定要刪除這則公告嗎？")) {
                window.location = "admin_delete_announcement.php?id=" + id;
            }
        }

        window.onclick = function(event) {
            if (!event.target.closest('.dropdown')) {
                document.getElementById("myDropdown").style.display = "none";
            }
        }
        document.getElementById("start_date").addEventListener("change", function() {
            let start = this.value;
            let endInput = document.getElementById("end_date");

            // 結束日最小值 = 開始日
            endInput.min = start;

            // 如果已選結束日 < 開始日 → 自動清空
            if (endInput.value < start) {
                endInput.value = "";
            }
        });
    </script>

</body>

</html>
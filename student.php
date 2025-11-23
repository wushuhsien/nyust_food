<?php
session_start(); 
include "db.php"; 
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>學生/教職員首頁</title>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    body {
        font-family: "Microsoft JhengHei", Arial, sans-serif;
        margin: 0;
        background: #f2f6fc;
    }

    /* ======== 頂部標題區 ======== */
    #a {
        background-color: #4a90e2;
        height: 60px;
        color: white;
        text-align: center;
        font-size: 20px;
        position: relative;
        line-height: 60px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    #a h1 {
        margin: 0;
    }

    /* 帳號與齒輪 */
    #top-right-box {
        position: absolute;
        top: 0;
        right: 10px;
        height: 60px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-account {
        color: white;
        font-weight: bold;
        font-size: 16px;
    }

    /* ===== 下拉設定選單 ===== */
    .dropdown { position: relative; }

    .dropbtn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }

    .dropbtn i {
        font-size: 28px;
        color: white;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background: white;
        min-width: 140px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        z-index: 100;
    }

    .dropdown-content input[type="button"] {
        width: 100%;
        padding: 10px 12px;
        border: none;
        background: white;
        font-size: 14px;
        text-align: left;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .dropdown-content input[type="button"]:hover {
        background-color: #f4f4f4;
    }

    .dropdown-content input:last-child {
        border-bottom: none;
    }

    /* 子選單 */
    .sub-dropdown {
        display: none;
        background: #f9f9f9;
        border-left: 3px solid #4a90e2;
    }
    .sub-dropdown input[type="button"] {
        padding-left: 25px;
    }

    /* ======= 公告區 ======= */
    #b {
        width: 90%;
        max-width: 900px;
        margin: 20px auto;
        background: #ffffff;
        padding: 20px 25px;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }

    #b h1 {
        margin: 0 0 15px 0;
        font-size: 22px;
        color: #333;
    }

    /* 公告內容可縱向捲動 */
    .announcement-wrapper {
        max-height: 350px; /* 高度固定，可捲動 */
        overflow-y: auto;
        padding-right: 5px;
    }

    .announcement {
        background-color: #fdfdfd;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 15px;
        box-shadow: 1px 1px 4px rgba(0,0,0,0.08);
    }
    .announcement strong {
        color: #4a90e2;
    }

    /* ===== 店家類型 ===== */
    .storetype-container {
        width: 90%;
        max-width: 900px;
        margin: 20px auto;
        background: white;
        padding: 15px 20px;
        border-radius: 12px;
        border: 1px solid #ddd;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        overflow-x: auto;
        white-space: nowrap;
    }

    .storetype-container h3 {
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

<!-- ====================== TOP BAR ====================== -->
<div id="a">
    <?php $account = $_SESSION['user'] ?? "未登入"; ?>
    <h1>學生/教職員首頁</h1>

    <div id="top-right-box">
        <div class="user-account"><?php echo htmlspecialchars($account); ?></div>

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

<!-- ====================== 公告區 ====================== -->
<div id="b">
    <h1>公告</h1>

    <div class="announcement-wrapper">
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
</div>

<!-- ====================== 店家類型區 ====================== -->
<div class="storetype-container">
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

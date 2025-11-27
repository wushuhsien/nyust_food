<?php
$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
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
            background-color: #d6e9ff;
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
    </style>
</head>

<body>
    <div class="top-menu">
        <h1 style="cursor:pointer;" onclick="window.location.href='student.php'">學生/教職員首頁</h1>

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
</body>
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

</html>
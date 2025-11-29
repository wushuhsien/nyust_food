<?php
$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家menu</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #fdf6f0;
            /* 淡咖啡色背景 */
        }

        /* 上方橙色條 */
        .top-menu {
            background-color: #f28c28;
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
            color: #ffd699;
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
            background-color: #fff8f0;
            min-width: 150px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.15);
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
            background-color: #d17a22;
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
    <div class="top-menu">
        <h1 style="cursor:pointer;" onclick="window.location.href='store.php'">店家首頁</h1>

        <div id="top-right-box">
            <div class="user-account"><?php echo htmlspecialchars($account); ?></div>
            <div class="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <i class="bi bi-gear"></i>
                </button>
                <div id="myDropdown" class="dropdown-content">
                    <input type="button" value="店家設定 ▼" onclick="toggleSubMenu1()">
                    <div id="subMenu1" class="sub-dropdown">
                        <input type="button" value="店家資料" onclick="window.location='store_information.php'">
                    </div>
                    <input type="button" value="店家管理 ▼" onclick="toggleSubMenu2()">
                    <div id="subMenu2" class="sub-dropdown">
                        <input type="button" value="菜單管理" onclick="window.location='store_menumanage.php'">
                        <input type="button" value="公告管理" onclick="window.location='store-announcement.php'">
                        <input type="button" value="歷史訂單" onclick="window.location='store_history.php'">
                    </div>
                    <input type="button" value="問題 ▼" onclick="toggleSubMenu3()">
                    <div id="subMenu3" class="sub-dropdown">
                        <input type="button" value="歷史紀錄" onclick="window.location='store_report1.php'">
                        <input type="button" value="被投訴歷史紀錄" onclick="window.location='store_report2.php'">
                    </div>
                    <input type="button" value="登出" onclick="window.location='../login.html'">
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

    function toggleSubMenu2() {
        var sub = document.getElementById("subMenu2");
        sub.style.display = (sub.style.display === "block") ? "none" : "block";
    }

    function toggleSubMenu3() {
        var sub = document.getElementById("subMenu3");
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

</html>
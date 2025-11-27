<?php
$account = isset($_SESSION['user']) ? $_SESSION['user'] : '';
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>管理員menu</title>
    <link rel="stylesheet" href="../css/admin_menu.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .user-settings {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-left: auto;
            /* 推到最右 */
            position: relative;
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
            right: 0;
            background-color: white;
            border: 1px solid #ccc;
            min-width: 150px;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .dropdown-content input {
            width: 100%;
            padding: 8px 12px;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-content input:hover {
            background-color: #f0f0f0;
        }

        .sub-dropdown {
            display: none;
            padding-left: 10px;
        }

        .account {
            padding: 0 5px;
            /* 左右各 5px 空白 */
        }
    </style>
</head>

<body>
    <div class="top-menu">
        <h1 style="cursor:pointer;" onclick="window.location.href='admin.php'">管理員後台</h1>

        <div class="menu-items">

            <div class="menu-item">使用者資料管理
                <div class="dropdown">
                    <a href="student_material.php">學生/教職員</a>
                    <a href="store_material.php">店家</a>
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
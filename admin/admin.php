<?php
session_start();
include "../db.php";  // 引入資料庫連線
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>管理員後台</title>
    <style>
        /* 新增店家帳號待審核容器 */
        .announcement-box {
            width: 90%;
            margin: 20px auto;
            padding: 15px 20px;
            background: #fff8e1;
            /* 淡黃色背景 */
            border-left: 6px solid #f7b500;
            border-radius: 8px;
            font-family: "Segoe UI", sans-serif;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .announcement-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #b36b00;
        }

        .announcement-content {
            font-size: 16px;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>
    <!-- 新增店家帳號待審核區塊 -->
    <div class="announcement-box">
        <div class="announcement-title">
            <a href="view_announcement.php" style="text-decoration:none; color:#b36b00;">📢 待審核店家帳號</a>
        </div>
        <!-- 列出店家待審核帳號、店名 -->
        <div class="announcement-content">
            <?php
            $sql = "SELECT a.`account`, b.`name` 
                FROM `account` AS a 
                INNER JOIN `store` AS b ON a.`account` = b.`account` 
                WHERE a.`role` = 3";
            $result = $link->query($sql);

            if ($result && $result->num_rows > 0) {
                $i = 1; // 流水號起始值
                while ($row = $result->fetch_assoc()) {
                    $account = $row['account'];
                    $storeName = $row['name'];

                    echo "<div style='margin-bottom: 6px; font-size:16px; color:#333;'>
                        $i. 帳號：$account 、 店名：$storeName
                      </div>";
                    $i++; // 流水號遞增
                }
            } else {
                echo "<div style='font-size:16px; color:#666;'>目前沒有待審核店家。</div>";
            }
            ?>
        </div>
    </div>

    <!-- 系統問題區塊 -->
    <div class="announcement-box">
        <div class="announcement-title">
            <a href="view_issues.php" style="text-decoration:none; color:#b36b00;">⚠️ 待處理系統問題</a>
        </div>
        <div class="announcement-content">
            <?php
            $sql = "SELECT `description`   
                FROM `report`
                WHERE `type`='系統問題' AND `status`='待處理'";
            $result = $link->query($sql);

            if ($result && $result->num_rows > 0) {
                $i = 1; // 流水號起始值
                while ($row = $result->fetch_assoc()) {
                    $description = $row['description'];

                    echo "<div style='margin-bottom: 6px; font-size:16px; color:#333;'>
                        $i. $description
                      </div>";
                    $i++;
                }
            } else {
                echo "<div style='font-size:16px; color:#666;'>目前沒有系統問題。</div>";
            }
            ?>
        </div>
    </div>
</body>

</html>
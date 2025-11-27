<?php
session_start();
include "../db.php";
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>學生/教職員首頁</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        #b {
            background-color: #f9fbff;
            /* 淡橙色 */
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 1200px;
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
    <?php include "student_menu.php"; ?>

    <!-- ====== 公告區 ====== -->
    <div id="b" style="display: grid; grid-template-columns: 1fr 1fr 0.6fr; gap: 20px;">
        <!-- 左邊：店家公告 -->
        <div style="flex:1; border:2px solid #4a90e2; border-radius:10px; padding:15px; background-color:#e8f3ff; max-height:500px; overflow-y:auto;">
            <h2 style="text-align:center; color:#005AB5;">店家公告</h2>
            <?php
            $sql_store = "SELECT a.announcement_id, a.topic, a.description, a.start_time, a.end_time, s.name AS store_name
              FROM announcement a
              JOIN store s ON a.account = s.account
              WHERE a.type = '店休'
                AND a.start_time <= NOW()
                AND a.end_time >= NOW()
              ORDER BY a.start_time DESC";

            $result_store = $link->query($sql_store);

            if ($result_store->num_rows > 0) {
                while ($row = $result_store->fetch_assoc()) {
                    echo '<div class="announcement">';
                    echo '<p><strong>店家名稱：</strong>' . htmlspecialchars($row['store_name']) . '</p>';
                    echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                    echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p>目前沒有店家公告。</p>";
            }
            ?>
        </div>

        <!-- 中間：系統公告 -->
        <div style="flex:1; border:2px solid #4a90e2; border-radius:10px; padding:15px; background-color:#e8f3ff; max-height:500px; overflow-y:auto;">
            <h2 style="text-align:center; color:#005AB5;">系統公告</h2>
            <?php
            $sql_admin = "SELECT announcement_id, topic, description, start_time, end_time
                      FROM announcement
                      WHERE type='公告'
                        AND start_time <= NOW()
                        AND end_time >= NOW()
                      ORDER BY start_time DESC";

            $result_admin = $link->query($sql_admin);

            if ($result_admin->num_rows > 0) {
                while ($row = $result_admin->fetch_assoc()) {
                    echo '<div class="announcement">';
                    echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                    echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                    echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo "<p>目前沒有系統公告。</p>";
            }
            ?>
        </div>
        <!-- 右邊：店家類型 -->
        <div style="flex:1; border:2px solid #4a90e2; border-radius:10px; padding:15px; background-color:#e8f3ff; max-height:500px; overflow-y:auto;">
            <h2 style="text-align:center; color:#005AB5;">店家類型</h2>
            <div class="storetype-container">
                <?php
                $sql2 = "SELECT `storetype_id`, `name` FROM `storetype`";
                $result2 = $link->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {
                        $id = $row2['storetype_id'];
                        $name = htmlspecialchars($row2['name']);
                        echo '<a class="storetype-box" style="display:block; width:50%; margin:0 auto 10px; text-align:center;" href="store_list.php?type=' . $id . '">' . $name . '</a>';
                    }
                } else {
                    echo "<p>沒有店家類型資料。</p>";
                }
                ?>
            </div>
        </div>
    </div>
</body>

</html>
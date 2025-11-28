<?php
session_start();
include "../db.php";
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家首頁</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        #b {
            background-color: #fff7f0;
            /* 淡橙色 */
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 900px;
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
            box-shadow: 1px 2px 6px rgba(0, 0, 0, 0.05);
        }

        .announcement p {
            margin: 6px 0;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <?php include "store_menu.php"; ?>

    <div id="b" style="display:flex; gap:20px; justify-content:space-between;">
        <!-- 左邊：店家公告 -->
        <div style="flex:1; border:2px solid #f28c28; border-radius:10px; padding:15px; background-color:#fff3e6; max-height:500px; overflow-y:auto;">
            <h2 style="text-align:center; color:#b35c00;">店家公告</h2>
            <?php
            $loginAccount = $_SESSION['user'] ?? '';
            $sql_store = "SELECT announcement_id, topic, description, start_time, end_time
                      FROM announcement
                      WHERE type='店休'
                        AND account = ?
                        AND start_time <= NOW()
                        AND end_time >= NOW()
                      ORDER BY start_time DESC";

            $stmt_store = $link->prepare($sql_store);
            $stmt_store->bind_param("s", $loginAccount);
            $stmt_store->execute();
            $result_store = $stmt_store->get_result();

            if ($result_store->num_rows > 0) {
                while ($row = $result_store->fetch_assoc()) {
                    echo '<div class="announcement">';
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

        <!-- 右邊：系統公告 -->
        <div style="flex:1; border:2px solid #f28c28; border-radius:10px; padding:15px; background-color:#fff3e6; max-height:500px; overflow-y:auto;">
            <h2 style="text-align:center; color:#b35c00;">系統公告</h2>
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
    </div>

</body>

</html>
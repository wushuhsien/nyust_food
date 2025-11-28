<?php
session_start();
include "../db.php";

$loginAccount = $_SESSION['user'] ?? ''; // 目前登入帳號

// AJAX 刪除處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    if ($id > 0) {
        $stmt = $link->prepare("DELETE FROM announcement WHERE announcement_id = ? AND account = ?");
        $stmt->bind_param("is", $id, $loginAccount); // 確保只能刪除自己帳號的公告
        echo $stmt->execute() ? "success" : "刪除失敗";
        $stmt->close();
    } else {
        echo "無效ID";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家公告</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        #b {
            background-color: #fff7f0;
            /* 淺橘色卡片背景 */
            margin: 20px auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 780px;
            text-align: left;
            border: 1px solid #f0d4b2;
            /* 卡片邊框橘色系 */
        }

        #b h1 {
            font-size: 24px;
            margin-top: 0;
            color: #b35c00;
            /* 主標題橘色 */
        }

        input {
            padding: 8px 10px;
            border: 1px solid #f2c79e;
            /* 橘色邊框 */
            border-radius: 8px;
        }

        button {
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .announcement {
            background-color: #fff3e6;
            /* 淡橘色卡片 */
            border: 1px solid #f2c79e;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 16px;
            position: relative;
        }

        .announcement p {
            margin: 6px 0;
            line-height: 1.5;
            color: #4b2500;
            /* 深橘色文字 */
        }

        .announcement .btn-area {
            position: absolute;
            right: 14px;
            top: 14px;
            display: flex;
            gap: 8px;
        }

        .edit-btn {
            padding: 5px 10px;
            font-size: 12px;
            background-color: #f28c28;
            /* 橘色按鈕 */
            color: white;
        }

        .edit-btn:hover {
            background-color: #d97706;
            /* 深橘色 hover */
        }

        .delete-btn {
            padding: 5px 10px;
            font-size: 12px;
            background-color: #dc2626;
            /* 保留紅色刪除按鈕 */
            color: white;
        }

        .delete-btn:hover {
            background-color: #b91c1c;
        }

        /* 查詢表單 */
        .search-form {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form button {
            background-color: #f28c28;
            /* 橘色搜尋按鈕 */
            color: white;
            padding: 6px 12px;
        }

        .search-form button:hover {
            background-color: #d97706;
            /* 深橘色 hover */
        }
    </style>
</head>

<body>
    <?php include "store_menu.php"; ?>

    <div id="b">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>我的公告</h1>
            <button onclick="location.href='store-add-announcement.php'" style="background:#f28c28; color:white; padding:8px 14px; font-size:14px;">
                新增公告
            </button>
        </div>

        <form method="POST" class="search-form">
            <label>開始日期：</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $_POST['start_date'] ?? ''; ?>">
            <label>結束日期：</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $_POST['end_date'] ?? ''; ?>">
            <input type="text" id="query_name" name="query_name" placeholder="查詢主題" value="<?php echo $_POST['query_name'] ?? ''; ?>">
            <button type="submit">查詢</button>
        </form>

        <?php
        // 處理篩選
        $start_date = $_POST['start_date'] ?? '';
        $end_date   = $_POST['end_date'] ?? '';
        $query      = $_POST['query_name'] ?? '';

        $sql = "SELECT * FROM announcement WHERE account = ?";
        $conditions = [];
        $params = [$loginAccount];
        $types = "s";

        if (!empty($query)) {
            $conditions[] = "topic LIKE ?";
            $params[] = "%" . $query . "%";
            $types .= "s";
        }

        if (!empty($start_date)) {
            $conditions[] = "start_time <= ? AND end_time >= ?";
            $params[] = "$start_date 23:59:59";
            $params[] = "$start_date 00:00:00";
            $types .= "ss";
        }

        if (!empty($end_date)) {
            $conditions[] = "start_time <= ? AND end_time >= ?";
            $params[] = "$end_date 23:59:59";
            $params[] = "$end_date 00:00:00";
            $types .= "ss";
        }

        if (count($conditions) > 0) {
            $sql .= " AND " . implode(" AND ", $conditions);
        } else {
            $now = date("Y-m-d H:i:s");
            $sql .= " AND start_time <= '$now' AND end_time >= '$now'";
        }

        $sql .= " ORDER BY start_time DESC";

        $stmt = $link->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="announcement">';
                echo '<div class="btn-area">';
                echo '<button class="edit-btn" onclick="location.href=\'store-update-announcement.php?id=' . $row['announcement_id'] . '\'">修改</button>';
                echo '<button class="delete-btn" onclick="deleteAnnouncement(' . $row['announcement_id'] . ')">刪除</button>';
                echo '</div>';
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
        function deleteAnnouncement(id) {
            if (!confirm("確定要刪除這則公告嗎？")) return;

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    let res = xhr.responseText.trim();
                    if (res === "success") {
                        alert("刪除公告成功！");
                        location.reload();
                    } else {
                        alert("刪除失敗: " + res);
                    }
                } else {
                    alert("伺服器錯誤，請稍後再試。");
                }
            };
            xhr.send("delete_id=" + id);
        }

        const startInput = document.getElementById("start_date");
        const endInput = document.getElementById("end_date");

        startInput.addEventListener("change", function() {
            endInput.min = this.value || "";
        });
        endInput.addEventListener("change", function() {
            startInput.max = this.value || "";
        });
    </script>
</body>

</html>
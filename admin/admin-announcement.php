<?php
session_start();
include "../db.php";  // 引入資料庫連線

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    if ($id > 0) {
        $stmt = $link->prepare("DELETE FROM announcement WHERE announcement_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "success"; // AJAX 接收到 success 後再 alert
        } else {
            echo "刪除失敗";
        }
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
    <title>管理員公告</title>

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            margin: 20px;
            background-color: #E8EEFF;
        }

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

        input {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

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
        }

        .edit-btn:hover {
            background-color: #1D4ED8;
        }

        .delete-btn {
            background-color: #DC2626;
        }

        .delete-btn:hover {
            background-color: #B91C1C;
        }

        #top-right-box {
            position: absolute;
            top: 0;
            right: 15px;
            height: 60px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
    </style>

</head>

<body>
    <?php include "admin_menu.php"; ?>

    <div id="b">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>管理員公告</h1>
            <button onclick="location.href='admin-insert-announcement.php'"
                style="padding: 8px 14px; background: #1E3A8A; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
                新增公告
            </button>
        </div>


        <!-- 查詢表單 & 新增公告按鈕 -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <form method="POST" style="display:flex; gap:10px; align-items:center; margin:0;">
                <label>開始日期：</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $_POST['start_date'] ?? ''; ?>">
                <label>結束日期：</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $_POST['end_date'] ?? ''; ?>">
                <input type="text" id="query_name" name="query_name" placeholder="查詢主題">
                <button type="submit" id="query_btn" style="padding:6px 12px; background:#2563EB; color:white; border:none; border-radius:6px; cursor:pointer;">查詢</button>
            </form>
        </div>

        <?php
        // 處理日期篩選
        $start_date = $_POST['start_date'] ?? '';
        $end_date   = $_POST['end_date'] ?? '';
        $query      = $_POST['query_name'] ?? '';

        $sql = "SELECT * FROM announcement WHERE type='公告'";
        $conditions = [];
        $params = [];
        $types = "";

        // 轉查詢日期為時間範圍邊界
        if (!empty($start_date)) {
            $startStart = "$start_date 00:00:00";
            $startEnd   = "$start_date 23:59:59";
        }

        if (!empty($end_date)) {
            $endStart = "$end_date 00:00:00";
            $endEnd   = "$end_date 23:59:59";
        }

        // ✅ 主題模糊查詢
        if (!empty($query)) {
            $conditions[] = "topic LIKE ?";
            $params[] = "%" . $query . "%";
            $types .= "s";
        }

        // ✅ 只有開始日期（只要該日落在公告區間內就命中）
        if (!empty($start_date) && empty($end_date)) {
            $conditions[] = "start_time <= ? AND end_time >= ?";
            $params[] = $startEnd;
            $params[] = $startStart;
            $types .= "ss";
        }

        // ✅ 只有結束日期
        if (empty($start_date) && !empty($end_date)) {
            $conditions[] = "start_time <= ? AND end_time >= ?";
            $params[] = $endEnd;
            $params[] = $endStart;
            $types .= "ss";
        }

        // ✅ 開始 + 結束都有（交集篩選）
        if (!empty($start_date) && !empty($end_date)) {
            if ($start_date > $end_date) {
                echo "<script>alert('開始日期不能大於結束日期'); history.back();</script>";
                exit;
            }
            $conditions[] = "start_time <= ? AND end_time >= ?";
            $params[] = $endEnd;
            $params[] = $startStart;
            $types .= "ss";
        }

        // ✅ 把條件真正加回 SQL
        if (count($conditions) > 0) {
            $sql .= " AND " . implode(" AND ", $conditions);
        } else {
            // 🔹 若完全沒選日期也沒主題，顯示目前有效公告
            $now = date("Y-m-d H:i:s");
            $sql .= " AND start_time <= '$now' AND end_time >= '$now'";
        }

        $sql .= " ORDER BY start_time ASC";

        // ✅ Prepared statement
        $stmt = $link->prepare($sql);
        if (count($params) > 0) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        // 顯示公告 UI
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
            xhr.open("POST", "", true); // 同一個檔案處理
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    let res = xhr.responseText.trim();
                    if (res === "success") {
                        alert("刪除管理員公告成功！");
                        // 重新整理頁面，保持更新
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
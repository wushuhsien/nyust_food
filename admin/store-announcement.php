<?php
session_start();
include "../db.php";  // 引入資料庫連線

// AJAX 刪除處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    if ($id > 0) {
        $stmt = $link->prepare("DELETE FROM announcement WHERE announcement_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "success";
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
    <title>店家公告</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        #b {
            background-color: #ffffff;
            margin: 20px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 750px;
            text-align: left;
            border: 1px solid #D7C1B2;
            /* 改成柔和暖棕邊框 */
        }

        #b h1 {
            font-size: 22px;
            margin-top: 0;
            color: #5A3E2B;
            /* 更沉穩的棕黑標題 */
        }

        input {
            padding: 8px 10px;
            border: 1px solid #C19A6B;
            /* 配合主色 */
            border-radius: 8px;
            background-color: #FAF6F3;
        }

        input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 94, 60, 0.25);
            border-color: #8B5E3C;
        }

        .announcement {
            background-color: #FFF8E1;
            /* 你要保留的淡黃色 */
            border-left: 5px solid #8B5E3C;
            /* 改成你選的焦糖棕 */
            border-radius: 8px;
            padding: 15px 18px;
            margin-bottom: 15px;
            position: relative;
            box-shadow: 0 2px 8px rgba(139, 94, 60, 0.18);
        }

        .announcement p {
            margin: 6px 0;
            line-height: 1.5;
            color: #3B2F2F;
            /* 深灰棕文字更耐看 */
        }

        .announcement .btn-area {
            position: absolute;
            right: 12px;
            top: 12px;
            display: flex;
            gap: 8px;
        }

        /* 編輯 / 刪除按鈕 */
        .edit-btn,
        .delete-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            color: white;
            cursor: pointer;
        }

        /* edit 用柔和但有對比的「咖啡黑」 */
        .edit-btn {
            background-color: #6F4E37;
        }

        .edit-btn:hover {
            background-color: #5A3B2A;
            transform: translateY(-1px);
        }

        /* delete 用不刺眼但專業的「灰酒紅」 */
        .delete-btn {
            background-color: #A63D40;
        }

        .delete-btn:hover {
            background-color: #8A2F32;
            transform: translateY(-1px);
        }
    </style>

</head>

<body>

    <?php include "admin_menu.php"; ?>

    <div id="b">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>店家公告</h1>
            <button onclick="location.href='store-insert-announcement.php'"
                style="padding: 8px 14px; background: #8B5E3C; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer;">
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
                <button type="submit" id="query_btn" style="padding:6px 12px; background:#8B5E3C; color:white; border:none; border-radius:6px; cursor:pointer;">查詢</button>
            </form>
        </div>

        <?php
        // 處理日期篩選
        $start_date = $_POST['start_date'] ?? '';
        $end_date   = $_POST['end_date'] ?? '';
        $query      = $_POST['query_name'] ?? '';

        $sql = "SELECT a.announcement_id, a.topic, a.description, a.start_time, a.end_time, s.name AS store_name
        FROM announcement a
        LEFT JOIN store s ON a.account = s.account
        WHERE a.type = '店休'";

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
            echo '<div style="max-height:500px; overflow-y:auto;">';
            while ($row = $result->fetch_assoc()) {
                echo '<div class="announcement">';
                echo '<div class="btn-area">';
                echo '<button class="edit-btn" onclick="location.href=\'store-update-announcement.php?id=' . $row['announcement_id'] . '\'">修改</button>';
                echo '<button class="delete-btn" onclick="deleteAnnouncement(' . $row['announcement_id'] . ')">刪除</button>';
                echo '</div>';
                echo '<p><strong>店家名稱：</strong>' . htmlspecialchars($row['store_name']) . '</p>';
                echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
                echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
                echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
                echo '</div>';
            }
            echo '</div>'; 
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

        // 當開始日期改變 → 限制結束日期最小值
        startInput.addEventListener("change", function() {
            if (this.value) {
                endInput.min = this.value;
            } else {
                endInput.min = "";
            }
        });

        // 當結束日期改變 → 限制開始日期最大值
        endInput.addEventListener("change", function() {
            if (this.value) {
                startInput.max = this.value;
            } else {
                startInput.max = "";
            }
        });
    </script>

</body>

</html>
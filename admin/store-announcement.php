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
        body { font-family: 'Inter','Segoe UI',sans-serif; margin: 20px; background-color: #E8EEFF; }
        #b { background-color: #fff; margin: 20px auto; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 750px; text-align: left; border: 1px solid #CBD5E1; }
        #b h1 { font-size: 22px; margin-top: 0; color: #1E3A8A; }
        .announcement { background-color: #F8FAFF; border: 1px solid #C7D2FE; border-radius: 8px; padding: 15px 18px; margin-bottom: 15px; position: relative; }
        .announcement p { margin: 6px 0; line-height: 1.5; color: #1E293B; }
        .announcement .btn-area { position: absolute; right: 12px; top: 12px; display: flex; gap: 8px; }
        .edit-btn, .delete-btn { padding: 4px 8px; border: none; border-radius: 5px; font-size: 12px; color: white; cursor: pointer; }
        .edit-btn { background-color: #2563EB; }
        .edit-btn:hover { background-color: #1D4ED8; }
        .delete-btn { background-color: #DC2626; }
        .delete-btn:hover { background-color: #B91C1C; }
    </style>
</head>
<body>

<?php include "admin_menu.php"; ?>

<div id="b">
    <h1>店家公告</h1>

    <!-- 查詢表單 & 新增公告按鈕 -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <form method="GET" style="display:flex; gap:10px; align-items:center; margin:0;">
            <label>開始日期：</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">
            <label>結束日期：</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">
            <button type="submit" style="padding:6px 12px; background:#2563EB; color:white; border:none; border-radius:6px; cursor:pointer;">查詢</button>
        </form>

        <button onclick="location.href='store-insert-announcement.php'"
            style="padding: 8px 14px; background: #1E3A8A; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            新增公告
        </button>
    </div>

    <?php
    // 處理日期篩選
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    if (!empty($start_date) && !empty($end_date) && $start_date > $end_date) {
        echo "<script>alert('開始日期不能大於結束日期'); history.back();</script>";
        exit();
    }

    // SQL：依日期篩選公告
    $sql = "SELECT * FROM announcement WHERE type='店休'";
    if (!empty($start_date) && !empty($end_date)) {
        $sql .= " AND start_time <= '$end_date 23:59:59' AND end_time >= '$start_date 00:00:00'";
    } else {
        $now = date("Y-m-d H:i:s");
        $sql .= " AND start_time <= '$now' AND end_time >= '$now'";
    }
    $sql .= " ORDER BY start_time ASC";

    $result = $link->query($sql);

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

// 自動限制結束日期不能早於開始日期
document.getElementById("start_date").addEventListener("change", function() {
    let start = this.value;
    let endInput = document.getElementById("end_date");
    endInput.min = start;
    if (endInput.value < start) endInput.value = "";
});
</script>

</body>
</html>

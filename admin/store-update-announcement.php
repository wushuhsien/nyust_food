<?php
session_start();
include "../db.php";  // 引入資料庫連線

// 1. 檢查是否有傳入 ID
if (!isset($_GET['id'])) {
    echo "<script>alert('無公告 ID'); location.href='admin-announcement.php';</script>";
    exit;
}

$id = intval($_GET['id']);

// 2. 抓取原本的資料
$stmt = $link->prepare("SELECT * FROM announcement WHERE announcement_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// ★ 修正重點：將變數名稱從 $row 改為 $announcement，避免被 admin_menu.php 的 $row 覆蓋
$announcement = $result->fetch_assoc();

// 如果抓不到資料 (代表找不到該 ID 的資料)，直接擋下來
if (!$announcement) {
    echo "<script>alert('找不到該公告，可能已被刪除'); location.href='admin-announcement.php';</script>";
    exit; // 務必停止執行
}


// 3. 處理表單送出 → 更新資料
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $topic = $_POST['topic'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // 時間邏輯檢查
    if ($start_time > $end_time) {
        echo "<script>alert('開始時間不能大於結束時間'); history.back();</script>";
        exit();
    }

    $update = $link->prepare("
        UPDATE announcement
        SET topic=?, description=?, start_time=?, end_time=?
        WHERE announcement_id=?
    ");
    $update->bind_param("ssssi", $topic, $description, $start_time, $end_time, $id);

    if ($update->execute()) {
        // 更新成功後跳轉回列表頁
        echo "<script>alert('修改成功'); location.href='admin-announcement.php';</script>";
    } else {
        echo "<script>alert('修改失敗：" . addslashes($link->error) . "');</script>";
    }
    $update->close();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家公告修改</title>

    <style>
        /* 主要區塊 */
        #container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 650px;
            margin: 20px auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid #D7C1B2;
        }

        h2 {
            color: #5A3E2B;
            margin-top: 0;
        }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .form-row label {
            width: 80px;
            font-weight: bold;
            color: #5A3E2B;
        }

        .form-row input[type="text"] {
            width: 300px;
            padding: 8px;
            border: 1px solid #C19A6B;
            border-radius: 6px;
        }

        textarea {
            width: 500px; /* 稍微縮小一點避免破版 */
            height: 150px;
            padding: 10px;
            border: 1px solid #C19A6B;
            border-radius: 6px;
            resize: vertical;
        }

        .time-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .time-row label {
            width: 80px;
            font-weight: bold;
        }

        .time-row input[type="datetime-local"] {
            width: 250px;
            padding: 8px;
            border: 1px solid #C19A6B;
            border-radius: 6px;
        }

        /* 按鈕 */
        .btn-area {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .btn-save {
            background-color: #C19A6B;
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-save:hover {
            background-color: #8B5E3C;
        }

        .btn-cancel {
            background-color: #6F4E37;
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-cancel:hover {
            background-color: #5A3B2A;
        }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>

    <div id="container">
        <h2>修改公告</h2>

        <form method="POST">
            <div class="form-row">
                <label>主題：</label>
                <input type="text" name="topic" value="<?php echo htmlspecialchars($announcement['topic']); ?>" required>
            </div>

            <div class="form-row">
                <label>內容：</label>
                <textarea name="description" required><?php echo htmlspecialchars($announcement['description']); ?></textarea>
            </div>
            <div class="time-row">
                <label>開始時間：</label>
                <input type="datetime-local" name="start_time"
                    value="<?php echo date('Y-m-d\TH:i', strtotime($announcement['start_time'])); ?>" required>
            </div>

            <div class="time-row">
                <label>結束時間：</label>
                <input type="datetime-local" name="end_time"
                    value="<?php echo date('Y-m-d\TH:i', strtotime($announcement['end_time'])); ?>" required>
            </div>
            <div class="btn-area">
                <button class="btn-save" type="submit">修改</button>
                <button class="btn-cancel" type="button" onclick="location.href='admin-announcement.php'">取消</button>
            </div>

        </form>
    </div>

</body>
</html>
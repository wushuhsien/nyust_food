<?php
session_start();
include "../db.php";  // 引入資料庫連線

if (!isset($_GET['id'])) {
    echo "<script>alert('無公告 ID'); location.href='admin-announcement.php';</script>";
    exit;
}

// 日期篩選檢查
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

if (!empty($start_date) && !empty($end_date)) {
    if ($start_date > $end_date) {
        echo "<script>alert('開始日期不能大於結束日期'); history.back();</script>";
        exit();
    }
}

$id = intval($_GET['id']);

// 抓資料
$stmt = $link->prepare("SELECT topic, description, start_time, end_time FROM announcement WHERE announcement_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('找不到公告'); location.href='admin-announcement.php';</script>";
    exit;
}

$row = $result->fetch_assoc();

// 若表單送出 → 更新資料
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $topic = $_POST['topic'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

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
        echo "<script>alert('修改成功'); location.href='store-announcement.php';</script>";
    } else {
        echo "<script>alert('修改失敗');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家公告修改</title>

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #E8EEFF;
            /* 淡藍背景 */
            padding: 20px;
        }

        /* 主要區塊 */
        #container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 650px;
            margin: 20px auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid #CBD5E1;
        }

        h2 {
            color: #1E3A8A;
            margin-top: 0;
        }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .form-row label {
            width: 80px;
            /* 控制標籤寬度，不會被撐開 */
            font-weight: bold;
            color: #1E293B;
        }

        .form-row input[type="text"] {
            width: 300px;
            /* 改成比較舒服的寬度 */
            padding: 8px;
            border: 1px solid #C7D2FE;
            border-radius: 6px;
        }

        textarea {
            width: 600px;
            /* 調整內容框不要太長 */
            height: 150px;
            padding: 10px;
            border: 1px solid #C7D2FE;
            border-radius: 6px;
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
            border: 1px solid #C7D2FE;
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
            background-color: #2563EB;
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-save:hover {
            background-color: #1D4ED8;
        }

        .btn-cancel {
            background-color: #6B7280;
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
        }

        .btn-cancel:hover {
            background-color: #4B5563;
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
                <input type="text" name="topic" value="<?php echo htmlspecialchars($row['topic']); ?>" required>
            </div>

            <div class="form-row">
                <label>內容：</label>
                <textarea name="description" required><?php echo htmlspecialchars($row['description']); ?></textarea>
            </div>
            <div class="time-row">
                <label>開始時間：</label>
                <input type="datetime-local" name="start_time"
                    value="<?php echo date('Y-m-d\TH:i', strtotime($row['start_time'])); ?>" required>
            </div>

            <div class="time-row">
                <label>結束時間：</label>
                <input type="datetime-local" name="end_time"
                    value="<?php echo date('Y-m-d\TH:i', strtotime($row['end_time'])); ?>" required>
            </div>
            <div class="btn-area">
                <button class="btn-save" type="submit">修改</button>
                <button class="btn-cancel" type="button" onclick="location.href='store-announcement.php'">取消</button>
            </div>

        </form>
    </div>

</body>

</html>
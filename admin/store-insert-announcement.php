<?php
session_start();
include "../db.php";  // 引入資料庫連線

// 若表單送出 → 新增資料
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $topic = $_POST['topic'];
    $description = $_POST['description'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($start_time > $end_time) {
        echo "<script>alert('開始時間不能大於結束時間'); history.back();</script>";
        exit();
    }

    $type = "店休";

    // 從 session 拿管理員帳號
    $account = $_SESSION['user'] ?? null;

    if (!$account) {
        echo "<script>alert('請重新登入'); location.href='login.php';</script>";
        exit;
    }

    // INSERT 包含 account
    $insert = $link->prepare("
        INSERT INTO announcement (topic, description, start_time, end_time, type, account)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // 6 個欄位 → 6 個 s
    $insert->bind_param("ssssss", $topic, $description, $start_time, $end_time, $type, $account);

    if ($insert->execute()) {
        echo "<script>alert('新增成功'); location.href='store-announcement.php';</script>";
    } else {
        echo "<script>alert('新增失敗');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家新增公告</title>

    <style>
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #E8EEFF;
            padding: 20px;
        }

        #header {
            background-color: #1E3A8A;
            height: 60px;
            text-align: center;
            line-height: 60px;
            color: white;
            position: relative;
            font-size: 22px;
            font-weight: bold;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

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
        <h2>新增公告</h2>

        <form method="POST">

            <div class="form-row">
                <label>主題：</label>
                <input type="text" name="topic" placeholder="請輸入公告主題" required>
            </div>

            <div class="form-row">
                <label>內容：</label>
                <textarea name="description" placeholder="請輸入公告內容" required></textarea>
            </div>
            <div class="time-row">
                <label>開始時間：</label>
                <input type="datetime-local" name="start_time" required>
            </div>

            <div class="time-row">
                <label>結束時間：</label>
                <input type="datetime-local" name="end_time" required>
            </div>


            <div class="btn-area">
                <button class="btn-save" type="submit">新增</button>
                <button class="btn-cancel" type="button" onclick="location.href='store-announcement.php'">取消</button>
            </div>

        </form>
    </div>

</body>

</html>
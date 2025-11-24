<?php
session_start();
include "db.php"; // 你的資料庫連線

$account = $_SESSION['user'] ?? "";

if (!$account) {
    echo "<script>alert('未登入'); window.location='login.html';</script>";
    exit;
}

// 處理修改表單提交
if (isset($_POST['update'])) {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $nickname = $_POST['nickname'];
    $phone = $_POST['phone'];
    if (!preg_match('/^(09\d{8}|0\d{1,3}-?\d{5,8})$/', $phone)) {
        echo "<script>alert('電話格式不正確，請輸入手機或市話'); history.back();</script>";
        exit;
    }
    $email = $_POST['email'];

    $sql = "UPDATE `student` SET 
                `name`=?, 
                `nickname`=?, 
                `phone`=?, 
                `email`=?
            WHERE `student_id`=?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ssssssi", $name, $nickname, $phone, $email, $student_id);
    $stmt->execute();

    echo "<script>alert('基本資料修改成功！'); window.location='student.php';</script>";
    exit;
}

// 讀取資料
$sql = "SELECT `student_id`, `name`, `nickname`, `phone`, `email`, `account` 
        FROM `student` WHERE `account`=?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $account);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>基本資料</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        #header {
            background: #3b82f6;
            height: 60px;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .container {
            background: white;
            width: 420px;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 15px;
            color: #333;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.2s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.4);
        }

        .btn-area {
            text-align: center;
            margin-top: 25px;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 8px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-save {
            background: #3b82f6;
            color: white;
        }

        .btn-save:hover {
            background: #2563eb;
        }

        .btn-cancel {
            background: #e5e7eb;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }
    </style>
</head>

<body>

    <div id="header">基本資料</div>

    <div class="container">

        <form method="POST">
            <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">

            <div class="form-group">
                <label>姓名</label>
                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>">
            </div>

            <div class="form-group">
                <label>暱稱</label>
                <input type="text" name="nickname" value="<?= htmlspecialchars($row['nickname']) ?>">
            </div>

            <div class="form-group">
                <label>電話</label>
                <input type="text" name="phone" required
                    pattern="(09\d{8}|0\d{1,3}?\d{5,8})"
                    title="請輸入手機（0912345678）或市話（例如0212345678）"
                    value="<?= htmlspecialchars($row['phone']) ?>">
            </div>

            <div class="form-group">
                <label>電子郵件</label>
                <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>">
            </div>

            <div class="btn-area">
                <input type="submit" name="update" value="修改" class="btn btn-save">
                <button type="button" onclick="window.location='student.php'" class="btn btn-cancel">取消</button>
            </div>

        </form>

    </div>

</body>

</html>
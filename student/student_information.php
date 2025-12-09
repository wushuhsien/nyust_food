<?php
session_start();
include "../db.php"; // 你的資料庫連線

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
    $password = trim($_POST['password']);
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
    $stmt->bind_param("ssssi", $name, $nickname, $phone, $email, $student_id);
    $stmt->execute();

    // 密碼有輸入才更新，並且加密存入 account
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $sql2 = "UPDATE `account` SET `password`=? WHERE `account`=?";
        $stmt2 = $link->prepare($sql2);
        $stmt2->bind_param("ss", $hashed, $account);
        $stmt2->execute();
    }

    echo "<script>alert('基本資料修改成功！'); window.location='student.php';</script>";
    exit;
}

// 讀取資料
$sql = "SELECT a.`student_id`, a.`name`, a.`nickname`, a.`phone`, a.`email` , b.`password` 
        FROM `student` a
        INNER JOIN `account` b ON a.`account` = b.`account`
        WHERE a.`account`=?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $account);
$stmt->execute();

// 如果 get_result() 不可用，使用 bind_result
$stmt->bind_result($student_id, $name, $nickname, $phone, $email, $password);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>基本資料</title>

    <style>
        .container {
            background: white;
            width: 420px;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        h1 {
            font-size: 24px;
            margin-top: 12px;
            color: #2563eb;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            color: #000;
            margin-bottom: 6px;
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
    <?php include "student_menu.php"; ?>
    <h1>基本資料</h1>
    <div class="container">

        <form method="POST">
            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">

            <div class="form-group">
                <label>密碼</label>
                <input type="text" name="password" placeholder="若需修改再輸入">
            </div>

            <div class="form-group">
                <label>姓名</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>">
            </div>

            <div class="form-group">
                <label>暱稱</label>
                <input type="text" name="nickname" value="<?= htmlspecialchars($nickname ?? '') ?>">
            </div>

            <div class="form-group">
                <label>電話</label>
                <input type="text" name="phone" required
                    pattern="(09\d{8}|0\d{1,3}?\d{5,8})"
                    title="請輸入手機（0912345678）或市話（例如0212345678）"
                    value="<?= htmlspecialchars($phone ?? '') ?>">
            </div>

            <div class="form-group">
                <label>電子郵件</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>

            <div class="btn-area">
                <input type="submit" name="update" value="修改" class="btn btn-save">
                <button type="button" onclick="window.location='student.php'" class="btn btn-cancel">取消</button>
            </div>

        </form>

    </div>

</body>

</html>
<?php
session_start();
include "../db.php";  // 引入資料庫連線

// 假設你已經知道要編輯哪個帳號
$account = $_SESSION['user']; // 或其他來源

// 取得帳號資料
$stmt = $link->prepare("SELECT * FROM account WHERE account = ?");
$stmt->bind_param("s", $account);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// 處理表單提交
if (isset($_POST['update'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "<script>alert('密碼與確認密碼不一致！');</script>";
    } else {
        // 更新密碼
        $stmt = $link->prepare("UPDATE account SET password = ? WHERE account = ?");
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT); // 建議使用 password_hash 亂碼
        $stmt->bind_param("ss", $password, $account);
        if ($stmt->execute()) {
            echo "<script>alert('密碼更新成功！');window.location='admin.php';</script>";
        } else {
            echo "<script>alert('更新失敗！');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>管理員基本資料</title>
    <style>
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
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
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.2s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
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
    <?php include "admin_menu.php"; ?>
    <div class="container">
        <h2>管理員變更密碼</h2>
        <form method="POST">
            <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">


            <div class="form-group">
                <label>帳號</label>
                <input type="text" name="username" value="<?= htmlspecialchars($row['account']) ?>" readonly style="background-color:#e5e7eb; color:#6b7280; cursor:not-allowed;">
            </div>


            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>確認密碼</label>
                <input type="password" name="confirm_password" required>
            </div>

            <div class="btn-area">
                <input type="submit" name="update" value="修改" class="btn btn-save">
                <button type="button" onclick="window.location='admin.php'" class="btn btn-cancel">取消</button>
            </div>

        </form>

    </div>
</body>

</html>
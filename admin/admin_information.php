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
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $link->prepare("UPDATE account SET password = ? WHERE account = ?");
        $stmt->bind_param("ss", $hashed_password, $account);
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
            color: #2B2B2B;
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
            color: #4A403A;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #C19A6B;
            background: #FCF8F3;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.2s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #E6B566;
            outline: none;
            box-shadow: 0 0 6px rgba(230, 181, 102, 0.42);
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
    <div class="container">
        <h2>管理員變更密碼</h2>
        <form method="POST">
            <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">


            <div class="form-group">
                <label>帳號</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($account); ?>" readonly style="background-color:#e5e7eb; color:#6b7280; cursor:not-allowed;">
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
<?php
session_start();
include "db.php";  // 已經連接資料庫

// 按下下一頁（只傳值，不寫入資料庫）
if (isset($_POST['action']) && $_POST['action'] == 'next') {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $role  = $_POST['role'];

    if ($password !== $confirm) {
        echo "<script>alert('密碼與確認密碼不一致！');</script>";
    } else {

        // 帳號是否已存在
        $checkSql = "SELECT * FROM account WHERE account = ?";
        $stmt = $link->prepare($checkSql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs->num_rows > 0) {
            echo "<script>alert('帳號已存在！');</script>";
        } else {
            // ▸ 只存到 SESSION，不寫 DB
            $_SESSION['reg_username']   = $username;
            $_SESSION['reg_password']   = $password;
            $_SESSION['reg_role'] = $role;

            header("Location: register-1.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>註冊頁面</title>
    <style>
        body {
            font-family: "Microsoft JhengHei", sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            background: white;
            padding: 40px 50px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 380px;
        }

        .system-title {
            font-size: 26px;
            font-weight: bold;
            color: #2d6cdf;
            margin-bottom: 20px;
        }

        h1 {
            margin-bottom: 25px;
            color: #333;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 90%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        button,
        input[type="button"] {
            margin-top: 20px;
            padding: 10px;
            width: 95%;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            color: white;
        }

        button {
            background-color: #2d6cdf;
        }

        button:hover {
            background-color: #1f53b6;
        }

        input[type="button"] {
            background-color: #4caf50;
        }

        input[type="button"]:hover {
            background-color: #3d8e41;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="system-title">雲科大周遭點餐系統</div>
        <h1>註冊帳號</h1>

        <form method="POST" action="">
            <input type="text" name="username" placeholder="帳號" required><br>
            <input type="password" name="password" placeholder="密碼" required><br>
            <input type="password" name="confirm_password" placeholder="確認密碼" required><br>

            <select name="role">
                <option value="0">學生/教職員</option>
                <option value="3">店家</option>
            </select><br>

            <button type="submit" name="action" value="next">下一頁</button>
            <input type="button" value="登入" onclick="window.location.href='login.html'">
        </form>
    </div>

</body>
</html>


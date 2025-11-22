<?php
session_start();
include "db.php";  // 已經連接資料庫

// 按下下一頁（只傳值，不寫入資料庫）
if (isset($_POST['action']) && $_POST['action'] == 'next') {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $permission  = $_POST['permission'];

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
            $_SESSION['reg_permission'] = $permission;

            header("Location: register-1.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>註冊頁面</title>
</head>
<body>
    <h1>註冊帳號</h1>
    <form method="POST" action="">
        帳號：<input type="text" name="username" required><br><br>
        密碼：<input type="password" name="password" required><br><br>
        確認密碼：<input type="password" name="confirm_password" required><br><br>
        身份：
        <select name="permission">
            <option value="0">學生/教職員</option>
            <option value="1">店家</option>
        </select><br><br>
        <button type="submit" name="action" value="next">下一頁</button>
        <input type="button" value="登入" onclick="window.location.href='login.html'">
    </form>
</body>
</html>

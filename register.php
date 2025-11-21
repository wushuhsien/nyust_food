<?php
include "db.php";  // 已經連接資料庫

// 當按下建立
if (isset($_POST['action']) && $_POST['action'] == 'create') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $permission = $_POST['permission'] ?? 0; // 0=學生/教職員, 1=店家

    if ($password !== $confirm_password) {
        echo "<script>alert('密碼與確認密碼不一致！');</script>";
    } else {
        // 檢查帳號是否已存在
        $checkSql = "SELECT * FROM `account` WHERE `account` = ?";
        $stmtCheck = $link->prepare($checkSql);
        $stmtCheck->bind_param("s", $username);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('建立失敗：帳號已存在！');</script>";
        } else {
            // 建立帳號
            $sql = "INSERT INTO `account`(`account`, `password`, `created_time`, `permission`, `stop_reason`) 
                    VALUES (?, ?, CURRENT_TIMESTAMP(), ?, NULL)";
            $stmt = $link->prepare($sql);
            $stmt->bind_param("ssi", $username, $password, $permission);

            if ($stmt->execute()) {
                // 成功建立後直接跳轉，不 echo 任何訊息
                $stmt->close();
                $stmtCheck->close();
                echo "<script>alert('帳號建立成功！'); window.location='login.html';</script>";
                exit();
            } else {
                echo "<script>alert('建立失敗：請稍後再試！');</script>";
            }
            $stmt->close();
        }
        $stmtCheck->close();
    }
}

// // 如果按下登入，直接跳轉到 login.php
// if (isset($_POST['action']) && $_POST['action'] == 'login') {
//     header("Location: login.php");
//     exit();
// }
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
        <button type="submit" name="action" value="create">建立</button>
        <input type="button" value="登入" onclick="window.location.href='login.html'">
    </form>
</body>
</html>

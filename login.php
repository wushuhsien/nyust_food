<?php
header("Content-Type: text/html; charset=utf-8");
session_start();
include "db.php";  // 引入資料庫連線

//新增登入紀錄accountaction
function addLoginAction($link, $username) {
    date_default_timezone_set('Asia/Taipei'); // 確保時區正確
    $currentTime = date("Y-m-d H:i:s");
    $insertSql = "INSERT INTO accountaction (time, action, account) VALUES (?, 'IN', ?)";
    $insertStmt = $link->prepare($insertSql);
    $insertStmt->bind_param("ss", $currentTime, $username);
    $insertStmt->execute();
}

// 取得表單資料
$username = $_POST['username'];
$password = $_POST['password'];

// 查詢帳號是否存在
$sql = "SELECT * FROM account WHERE account = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// 判斷帳號是否存在
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // 直接比對明碼 (你可改成 password_hash)
    if ($password === $row['password']) {

        $_SESSION['user'] = $row['account']; // 記錄登入狀態
        $role = $row['role'];    // 取得權限欄位

        // 根據 role 導向不同頁面
        if ($role == 0) {
            addLoginAction($link, $username);
            echo "<script>alert('登入成功！'); window.location='student.php';</script>";
        } else if ($role == 1) {
            addLoginAction($link, $username);
            echo "<script>alert('登入成功！'); window.location='store.php';</script>";
        } else if ($role == 2) {
            addLoginAction($link, $username);
            echo "<script>alert('登入成功！'); window.location='admin/admin.php';</script>";
        } else if ($role == 3) {
            echo "<script>alert('店家帳號還在審核中，請稍後再試'); history.back();</script>";
        } else {
            // 避免未知權限造成問題
            echo "<script>alert('未知權限，請聯絡管理員'); history.back();</script>";
        }
        exit;
    } else {
        echo "<script>alert('密碼錯誤！'); history.back();</script>";
        exit;
    }
} else {
    echo "<script>alert('查無此帳號！'); history.back();</script>";
    exit;
}
?>

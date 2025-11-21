<?php
header("Content-Type: text/html; charset=utf-8");
session_start();
include "db.php";  // 引入資料庫連線

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
        $permission = $row['permission'];    // 取得權限欄位

        // 根據 permission 導向不同頁面
        if ($permission == 0) {
            echo "<script>alert('登入成功！'); window.location='student.php';</script>";
        } else if ($permission == 1) {
            echo "<script>alert('登入成功！'); window.location='store.php';</script>";
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

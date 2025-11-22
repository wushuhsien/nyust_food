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
    $email = $_POST['email'];
    $payment = $_POST['payment'];
    $notice = $_POST['notice'];

    $sql = "UPDATE `student` SET 
                `name`=?, 
                `nickname`=?, 
                `phone`=?, 
                `email`=?, 
                `payment`=?, 
                `notice`=? 
            WHERE `student_id`=?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ssssssi", $name, $nickname, $phone, $email, $payment, $notice, $student_id);
    $stmt->execute();

    echo "<script>alert('基本資料修改成功！'); window.location='student.php';</script>";
    exit;
}

// 讀取資料
$sql = "SELECT `student_id`, `name`, `nickname`, `phone`, `email`, `payment`, `notice`, `account` 
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
<title>店家首頁</title>
<style>
    #a{
        background-color:#66B3FF;
        height:50px;
        text-align:center;
        line-height:50px;
        color:#fff;
    }
    .form-group {
        margin: 15px 0;
    }
    label {
        display:inline-block;
        width:100px;
    }
    input[type="text"], input[type="email"], input[type="hidden"] {
        width:200px;
        padding:5px;
    }
    .btn {
        padding: 8px 15px;
        margin-right: 10px;
        cursor:pointer;
    }
</style>
</head>
<body>	
<div id="a"><h1>基本資料</h1></div>

<form method="POST">
    <input type="hidden" name="student_id" value="<?= $row['student_id'] ?>">
    
    <div class="form-group">
        <label>姓名：</label>
        <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>">
    </div>
    
    <div class="form-group">
        <label>暱稱：</label>
        <input type="text" name="nickname" value="<?= htmlspecialchars($row['nickname']) ?>">
    </div>
    
    <div class="form-group">
        <label>電話：</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
    </div>
    
    <div class="form-group">
        <label>電子郵件：</label>
        <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>">
    </div>
    
    <input type="hidden" name="payment" value="<?= htmlspecialchars($row['payment'] ?: '現金') ?>">
    <input type="hidden" name="notice" value="<?= htmlspecialchars($row['notice']) ?>">

    <div class="form-group">
        <input type="submit" name="update" value="修改" class="btn">
        <input type="button" value="取消" class="btn" onclick="window.location='student.php'">
    </div>
</form>
</body>
</html>

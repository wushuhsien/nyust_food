<?php
session_start();
include "db.php"; // 你的資料庫連線

$account = $_SESSION['user'] ?? "";

if (!$account) {
    echo "<script>alert('未登入'); window.location='login.html';</script>";
    exit;
}

// 讀取店家資料
$sql = "SELECT `store_id`, `name`, `description`, `address`, `phone`, `email`, `storetype_id` 
        FROM `store` WHERE `account`=?";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $account);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// 取得所有店家類型
$storeTypes = [];
$sql2 = "SELECT `storetype_id`, `name` FROM `storetype`";
$result2 = $link->query($sql2);
if ($result2->num_rows > 0) {
    while($typeRow = $result2->fetch_assoc()) {
        $storeTypes[] = $typeRow; // array of ['storetype_id'=>..., 'name'=>...]
    }
}

// 處理修改表單提交
if (isset($_POST['update'])) {
    $store_id = (int)$_POST['store_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $store_type = $_POST['store_type']; 

    if (empty($store_type)) {
        echo "<script>alert('請選擇店家類型'); history.back();</script>";
        exit;
    }

    $sql = "UPDATE `store` SET 
                `name`=?, 
                `description`=?, 
                `address`=?, 
                `phone`=?, 
                `email`=?, 
                `storetype_id`=? 
            WHERE `store_id`=?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ssssssi", $name, $description, $address, $phone, $email, $store_type, $store_id);
    $stmt->execute();

    echo "<script>alert('基本資料修改成功！'); window.location='store.php';</script>";
    exit;
}
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
    <input type="hidden" name="store_id" value="<?= $row['store_id'] ?>">
    
    <div class="form-group">
        <label>店名：</label>
        <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>">
    </div>
    
    <div class="form-group">
        <label>描述：</label>
        <input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>">
    </div>

    <div class="form-group">
        <label>地址：</label>
        <input type="text" name="address" value="<?= htmlspecialchars($row['address']) ?>">
    </div>
    
    <div class="form-group">
        <label>電話：</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>">
    </div>
    
    <div class="form-group">
        <label>電子郵件：</label>
        <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>">
    </div>
    
    <div class="form-group">
        <label>店家類型：</label><br>
        <select name="store_type" required>
            <option value="">請選擇</option>
            <?php foreach($storeTypes as $type): ?>
                <option value="<?= $type['storetype_id'] ?>" <?= $type['storetype_id'] == $row['storetype_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div><br>

    <div class="form-group">
        <input type="submit" name="update" value="修改" class="btn">
        <input type="button" value="取消" class="btn" onclick="window.location='store.php'">
    </div>
</form>
</body>
</html>

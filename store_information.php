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
$storeData  = $result->fetch_assoc();

// 取得店家營業時間
$sql = "SELECT `weekday`, `open_time`, `close_time` FROM `storehours` WHERE `account`=? ORDER BY `weekday`, `open_time`";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $account);
$stmt->execute();
$result = $stmt->get_result();

$hoursData = [];
while ($row = $result->fetch_assoc()) {
    $hoursData[$row['weekday']][] = $row['open_time'] . "~" . $row['close_time'];
}

// 取得所有店家類型
$storeTypes = [];
$sql2 = "SELECT `storetype_id`, `name` FROM `storetype`";
$result2 = $link->query($sql2);
if ($result2->num_rows > 0) {
    while ($typeRow = $result2->fetch_assoc()) {
        $storeTypes[] = $typeRow;
    }
}

// 處理修改表單提交
if (isset($_POST['update'])) {
    $store_id = (int)$_POST['store_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    if (!preg_match('/^(09\d{8}|0\d{1,3}-?\d{5,8})$/', $phone)) {
        echo "<script>alert('電話格式不正確，請輸入手機或市話'); history.back();</script>";
        exit;
    }
    $email = $_POST['email'];
    $store_type = $_POST['store_type'];

    if (empty($store_type)) {
        echo "<script>alert('請選擇店家類型'); history.back();</script>";
        exit;
    }

    $sql = "UPDATE `store` SET `name`=?, `description`=?, `address`=?, `phone`=?, `email`=?, `storetype_id`=? WHERE `store_id`=?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ssssssi", $name, $description, $address, $phone, $email, $store_type, $store_id);
    $stmt->execute();

    // 刪除原有營業時間
    $stmt = $link->prepare("DELETE FROM `storehours` WHERE `account`=?");
    $stmt->bind_param("s", $account);
    $stmt->execute();

    // 插入新的營業時間
    if (!empty($_POST['open_time'])) {
        foreach ($_POST['open_time'] as $weekday => $opens) {
            $closes = $_POST['close_time'][$weekday];
            for ($i = 0; $i < count($opens); $i++) {
                $open = $opens[$i];
                $close = $closes[$i];
                if ($open && $close) {
                    $stmt = $link->prepare("INSERT INTO `storehours`(`weekday`,`open_time`,`close_time`,`account`) VALUES (?,?,?,?)");
                    $stmt->bind_param("isss", $weekday, $open, $close, $account);
                    $stmt->execute();
                }
            }
        }
    }

    echo "<script>alert('基本資料修改成功！'); window.location='store.php';</script>";
    exit;
}
$days = ["1" => "星期一", "2" => "星期二", "3" => "星期三", "4" => "星期四", "5" => "星期五", "6" => "星期六", "7" => "星期日"];
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>店家基本資料</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fdf6f0;
            margin: 0;
            padding: 0;
        }

        #a {
            background-color: #f28c28;
            height: 60px;
            line-height: 60px;
            text-align: center;
            color: white;
            font-size: 22px;
            font-weight: bold;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0 0 10px 10px;
            margin-bottom: 20px;
        }

        .container {
            background-color: #fff7f0;
            max-width: 500px;
            margin: 30px auto;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #f2c79e;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            color: #b35c00;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0b387;
            border-radius: 6px;
            background-color: #fff8f0;
            font-size: 14px;
            transition: 0.2s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            border-color: #f28c28;
            outline: none;
            box-shadow: 0 0 5px rgba(242, 140, 40, 0.4);
        }

        .add-btn {
            margin-top: 5px;
            padding: 4px 8px;
            font-size: 12px;
            cursor: pointer;
            background-color: #66B3FF;
            color: white;
            border: none;
            border-radius: 4px;
        }

        .del-btn {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 2px 5px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 8px;
        }

        .del-btn:hover {
            background-color: #f90000ff;
        }

        .btn {
            padding: 10px 20px;
            margin-right: 10px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-save {
            background-color: #f28c28;
            color: white;
        }

        .btn-save:hover {
            background-color: #d1731f;
        }

        .btn-cancel {
            background-color: #e5e7eb;
        }

        .btn-cancel:hover {
            background-color: #d1d5db;
        }
    </style>
</head>

<body>
    <div id="a">店家基本資料</div>

    <div class="container">
        <form method="POST">
            <input type="hidden" name="store_id" value="<?= $storeData['store_id'] ?>">

            <div class="form-group">
                <label>店名</label>
                <input type="text" name="name" value="<?= htmlspecialchars($storeData['name']) ?>">
            </div>

            <div class="form-group">
                <label>描述</label>
                <input type="text" name="description" value="<?= htmlspecialchars($storeData['description']) ?>">
            </div>

            <div class="form-group">
                <label>地址</label>
                <input type="text" name="address" value="<?= htmlspecialchars($storeData['address']) ?>">
            </div>

            <div class="form-group">
                <label>電話</label>
                <input type="text" name="phone" required
                    pattern="(09\d{8}|0\d{1,3}?\d{5,8})"
                    title="請輸入手機（0912345678）或市話（例如0212345678）"
                    value="<?= htmlspecialchars($storeData['phone']) ?>">
            </div>

            <div class="form-group">
                <label>電子郵件</label>
                <input type="email" name="email" value="<?= htmlspecialchars($storeData['email']) ?>">
            </div>

            <div class="form-group">
                <label>店家類型</label>
                <select name="store_type" required>
                    <option value="">請選擇</option>
                    <?php foreach ($storeTypes as $type): ?>
                        <option value="<?= $type['storetype_id'] ?>" <?= $type['storetype_id'] == $storeData['storetype_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>營業時間</label>
                <?php foreach ($days as $w => $dayName): ?>
                    <div class="hours-block">
                        <strong><?= $dayName ?>:</strong>
                        <div id="ranges-<?= $w ?>"></div>
                        <button type="button" class="add-btn" onclick="addRange(<?= $w ?>)">+新增時段</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group" style="text-align:center;">
                <input type="submit" name="update" value="修改" class="btn btn-save">
                <input type="button" value="取消" class="btn btn-cancel" onclick="window.location='store.php'">
            </div>
        </form>
    </div>

    <script>
        const hoursData = <?= json_encode($hoursData) ?>;
        const days = <?= json_encode($days) ?>;

        for (const w in hoursData) {
            hoursData[w].forEach(t => {
                const [open, close] = t.split("~");
                addRange(w, open, close);
            });
        }

        function addRange(weekday, openVal = '', closeVal = '') {
            const container = document.getElementById('ranges-' + weekday);
            const div = document.createElement('div');
            div.className = 'time-range';
            div.innerHTML = `
        <input type="time" name="open_time[${weekday}][]" value="${openVal}">
        <span> - </span>
        <input type="time" name="close_time[${weekday}][]" value="${closeVal}">
        <button type="button" class="del-btn" onclick="this.parentElement.remove()">-刪除</button>
    `;
            container.appendChild(div);
        }
    </script>
</body>

</html>
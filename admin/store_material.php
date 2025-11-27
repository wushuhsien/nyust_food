<?php
session_start();
include "../db.php";  // 引入資料庫連線

//修改帳號狀態
if (isset($_POST['update'])) {
    $account = $_POST['account'];
    $role = $_POST['role'];
    $permission = $_POST['permission'];

    $stmt = $link->prepare("UPDATE `account` SET `role`=? , `permission`=? WHERE `account`=?");
    $stmt->bind_param("iis", $role, $permission, $account);
    if ($stmt->execute()) {
        echo "<script>alert('帳號 $account 狀態修改成功'); window.location='store_material.php';</script>";
        exit;
    } else {
        echo "<script>alert('更新失敗: " . $link->error . "'); history.back();</script>";
        exit;
    }
}

if (isset($_POST['add_store'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $storetype_id = $_POST['storetype'];

    if ($password !== $confirm_password) {
        echo "<script>alert('密碼與確認密碼不一致'); history.back();</script>";
        exit;
    }

    if (!preg_match('/^(09\d{8}|0\d{1,3}-?\d{5,8})$/', $phone)) {
        echo "<script>alert('電話格式不正確'); history.back();</script>";
        exit;
    }

    $link->begin_transaction();

    try {
        // 取得下一個 store_id
        $result = $link->query("SELECT MAX(store_id) AS maxid FROM store");
        $row = $result->fetch_assoc();
        $nextId = ($row['maxid'] === NULL ? 1 : $row['maxid'] + 1);

        $stmt1 = $link->prepare("INSERT INTO `account`(`account`, `password`, `created_time`, `role`, `permission`, `stop_reason`) VALUES (?, ?, CURRENT_TIMESTAMP(), 3, 0, NULL)");
        $stmt1->bind_param("ss", $username, $password);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $link->prepare("INSERT INTO `store`(`store_id`, `name`, `description`, `address`, `phone`, `email`, `storetype_id`, `account`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("isssssis", $nextId, $name, $description, $address, $phone, $email, $storetype_id, $username);
        $stmt2->execute();
        $stmt2->close();

        if (isset($_POST['open_time']) && isset($_POST['close_time'])) {
            $stmt3 = $link->prepare("INSERT INTO storehours(weekday, open_time, close_time, account) VALUES (?, ?, ?, ?)");
            foreach ($_POST['open_time'] as $weekday => $opens) {
                $closes = $_POST['close_time'][$weekday];
                for ($i = 0; $i < count($opens); $i++) {
                    $open = $opens[$i];
                    $close = $closes[$i];
                    if ($open && $close) {
                        $stmt3->bind_param("isss", $weekday, $open, $close, $username);
                        $stmt3->execute();
                    }
                }
            }
            $stmt3->close();
        }

        $link->commit();
        echo "<script>alert('新增店家資料成功！'); window.location='store_material.php';</script>";
        exit;
    } catch (Exception $e) {
        $link->rollback();
        echo "<script>alert('新增失敗: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家資料</title>

    <style>
        :root {
            --main-green: #4caf50;
            --dark-green: #388e3c;
            --red: #e53935;
            --blue: #1e88e5;
            --purple: #8e24aa;
            --orange: #fb8c00;
            --gray: #6c757d;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 10px;
        }


        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
            margin: 20px auto -20px auto;
        }

        /* 新增店家表單欄位 */
        .add-box {
            display: flex;
            flex-direction: column;
            /* 分上下兩行 */
            gap: 12px;
            /* 兩行間距 */
            padding: 12px;
            background: #fafafa;
            border-radius: 12px;
            margin: 0 auto 18px auto;
            /* 整個表單置中 */
            width: fit-content;
            /* 寬度自動包裹內容 */
        }

        .add-box .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 20px;
            justify-content: flex-start;
            /* 靠左排列 */
        }

        .add-box .form-row {
            display: flex;
            /* 使用彈性排列 */
            flex-wrap: wrap;
            /* 超出自動換行 */
            gap: 20px;
            /* 每個欄位間距 5px */
        }

        .add-box input,
        .add-box select {
            width: 180px;
            height: 40px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 12px;
            background-color: #fff;
            outline: none;
            cursor: pointer;
            box-sizing: border-box;
        }

        .add-box select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .add-box input:focus,
        .add-box select:focus {
            border-color: #007bff;
            box-shadow: 0 0 4px rgba(0, 123, 255, 0.4);
        }

        /* 讓整個 form-row 使用 flex 排列 */
        .add-box .form-row {
            display: flex;
            align-items: flex-start;
            /* 頂部對齊 */
            justify-content: space-between;
            /* 左右分開：左邊營業時間、右邊按鈕 */
            gap: 20px;
            /* 區塊間距 */
        }

        .add-box button {
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            background: var(--main-green);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        /* 新增店家按鈕 */
        .add-box button[name="add_store"] {
            width: 100px;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            background: var(--main-green);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .add-box button:hover {
            background: var(--dark-green);
            transform: scale(1.02);
        }

        /* 營業時間容器 */
        .hours-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            /* 兩行間距 */
        }

        .hours-row {
            display: flex;
            gap: 80px;
            /* 每個區塊間距 */
        }

        .hours-block {
            display: flex;
            flex-direction: column;
            width: 300px;
            box-sizing: border-box;
        }

        .hours-block input[type="time"] {
            width: 125px;
        }

        .hours-block button {
            width: 80px;
        }

        .hours-block .add-btn,
        .hours-block .del-btn {
            font-size: 12px;
            padding: 3px 6px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            background: #66B3FF;
            color: white;
        }

        .hours-block .del-btn {
            background: #ff6b6b;
        }

        .hours-block .time-range {
            display: flex;
            align-items: center;
            /* 垂直置中 */
            gap: 5px;
            /* 元素間距 */
            margin-top: 5px;
            /* 每個時段間距 */
        }

        .hours-block .time-range button.del-btn {
            flex-shrink: 0;
            /* 按鈕不縮小 */
        }


        /* 表格 */
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #eee;
        }

        thead {
            background: var(--main-green);
            color: white;
        }

        thead th {
            padding: 12px;
            font-weight: 500;
            text-align: center;
        }

        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f2f2f2;
        }

        tbody tr:hover {
            background: #e8f5e9;
        }

        .status-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .select-style {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #fff;
            font-size: 15px;
            outline: none;
            cursor: pointer;
        }

        .select-style:focus {
            border-color: #007bff;
            box-shadow: 0 0 4px rgba(0, 123, 255, 0.4);
        }

        /* 按鈕群組 */
        .btn-group {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            justify-items: center;
        }

        .btn-group button {
            padding: 6px 10px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-edit {
            background: var(--blue);
        }

        .btn-del {
            background: var(--red);
        }

        .btn-order {
            background: var(--orange);
        }

        .btn-rate {
            background: var(--purple);
        }

        .btn-chart {
            background: var(--main-green);
        }

        .btn-log {
            background: var(--gray);
        }

        .btn-group button:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }

        .password {
            font-family: monospace;
            letter-spacing: 2px;
            color: #999;
        }

        .title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .search-box {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .search-box input {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .search-box button {
            padding: 8px 14px;
            background: var(--blue);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .search-box button:hover {
            opacity: 0.85;
        }

        /* 日誌按鈕 */
        .btn-log {
            background: var(--gray);
            color: white;
            text-decoration: none;
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 13px;
            transition: 0.2s;
        }

        .btn-log:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>

    <div class="container">
        <h2>店家資料管理</h2>
        <!--查詢-->
        <form method="POST" class="search-box">
            <input type="text" name="query_name" placeholder="查詢店名">
            <button type="submit" name="search_btn">查詢</button>
        </form>

        <!-- 新增學生 -->
        <form method="POST" class="add-box" onsubmit="return validateHours();">
            <!-- 第一行 -->
            <div class="form-row">
                <input type="text" name="username" placeholder="帳號" required>
                <input type="password" name="password" placeholder="密碼" required>
                <input type="password" name="confirm_password" placeholder="確認密碼" required>
                <input type="text" name="phone" placeholder="電話" required pattern="(09\d{8}|0\d{1,3}-?\d{5,8})">
                <input type="text" name="email" placeholder="電子郵件">
                <?php
                $result = $link->query("SELECT `storetype_id`, `name` FROM `storetype`");
                if ($result->num_rows > 0) {
                    echo '<select name="storetype" class="select-style" required>';
                    echo '<option value="">店家類型</option>'; // 預設提示
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row['storetype_id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                    }
                    echo '</select>';
                }
                ?>
                <input type="text" name="name" placeholder="店名">
                <input type="text" name="description" placeholder="描述">
                <input type="text" name="address" placeholder="地址">
            </div>

            <!-- 第二行 -->
            <div class="form-row">
                <!-- 營業時間 -->
                <div class="hours-container">
                    <?php
                    $days = ["1" => "星期一", "2" => "星期二", "3" => "星期三", "4" => "星期四", "5" => "星期五", "6" => "星期六", "7" => "星期日"];
                    // 奇數天一行
                    echo '<div class="hours-row">';
                    foreach ([1, 3, 5, 7] as $w) {
                        echo '<div class="hours-block">';
                        echo '<strong>' . $days[$w] . ':</strong>';
                        echo '<div id="ranges-' . $w . '"></div>';
                        echo '<button type="button" class="add-btn" onclick="addRange(' . $w . ')">+新增時段</button>';
                        echo '</div>';
                    }
                    echo '</div>';

                    // 偶數天一行
                    echo '<div class="hours-row">';
                    foreach ([2, 4, 6] as $w) {
                        echo '<div class="hours-block">';
                        echo '<strong>' . $days[$w] . ':</strong>';
                        echo '<div id="ranges-' . $w . '"></div>';
                        echo '<button type="button" class="add-btn" onclick="addRange(' . $w . ')">+新增時段</button>';
                        echo '</div>';
                    }
                    echo '</div>';
                    ?>
                </div>
                <button type="submit" name="add_store">＋ 新增店家</button>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>帳號</th>
                    <!-- <th>密碼</th> -->
                    <th>店名</th>
                    <th>描述</th>
                    <th>地址</th>
                    <th>電話</th>
                    <th>電子郵件</th>
                    <th>店家類型</th>
                    <th>營業時間</th>
                    <!-- <th>建立時間</th> -->
                    <th>權限</th>
                    <th>狀態</th>
                    <th>停機原因</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                /* 先組合 SQL */
                if (isset($_POST['search_btn']) && !empty($_POST['query_name'])) {
                    $query_name = $link->real_escape_string($_POST['query_name']);
                    $sql = "SELECT a.account, b.name AS store_name, b.description, b.address, b.phone, b.email,
                                    c.name AS type_name, d.weekday, d.open_time, d.close_time,
                                    a.created_time, a.role, a.permission, a.stop_reason
                                FROM account AS a
                                INNER JOIN store AS b ON a.account = b.account
                                INNER JOIN storetype AS c ON b.storetype_id = c.storetype_id
                                LEFT JOIN storehours AS d ON a.account = d.account
                                WHERE (a.role=1 OR a.role=3) AND b.name = '$query_name'
                                ORDER BY a.account, d.weekday";
                } else {
                    $sql = "SELECT a.account, b.name AS store_name, b.description, b.address, b.phone, b.email,
                                    c.name AS type_name, d.weekday, d.open_time, d.close_time,
                                    a.created_time, a.role, a.permission, a.stop_reason
                                FROM account AS a
                                INNER JOIN store AS b ON a.account = b.account
                                INNER JOIN storetype AS c ON b.storetype_id = c.storetype_id
                                LEFT JOIN storehours AS d ON a.account = d.account
                                WHERE a.role=1 OR a.role=3
                                ORDER BY a.account, d.weekday";
                }

                /* 取資料 */
                $result = $link->query($sql);

                /* 依帳號整理成只一列 */
                $stores = [];

                while ($row = $result->fetch_assoc()) {
                    $acc = $row['account'];

                    if (!isset($stores[$acc])) {
                        $stores[$acc] = $row;
                        $stores[$acc]['hours'] = [];
                    }

                    if (!empty($row['weekday'])) {
                        $stores[$acc]['hours'][$row['weekday']] =
                            $row['open_time'] . " ~ " . $row['close_time'];
                    }
                }

                /* 星期對照 */
                $weekMap = ['一', '二', '三', '四', '五', '六', '日'];

                /* 輸出表格 */
                if (empty($stores)) {
                    echo "<tr><td colspan='14' style='text-align:center;color:#888'>無店家資料</td></tr>";
                } else {
                    $i = 1;

                    foreach ($stores as $row) {
                ?>
                        <tr>
                            <td style="text-align:center"><?= $i++ ?></td>
                            <td><?= $row['account'] ?></td>
                            <td><?= $row['store_name'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td><?= $row['address'] ?></td>
                            <td><?= $row['phone'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td><?= $row['type_name'] ?></td>

                            <!-- 營業時間 -->
                            <td style="text-align:center; vertical-align:middle;">
                                <?php
                                for ($w = 1; $w <= 7; $w++) {
                                    if (isset($row['hours'][$w])) {
                                        echo "星期" . $weekMap[$w - 1] . "<br> " . $row['hours'][$w] . "<br>";
                                    } else {
                                        // 未設定就留空
                                        echo "<br>";
                                    }
                                }
                                ?>
                            </td>

                            <!-- <td><?= $row['created_time'] ?></td> -->

                            <td>
                                <form method="POST" class="status-form">
                                    <select name="role" class="select-style" style="width: 150px;">
                                        <option value="1" <?= ($row['role'] == 1 ? 'selected' : '') ?>>店家</option>
                                        <option value="3" <?= ($row['role'] == 3 ? 'selected' : '') ?>>店家註冊審核中</option>
                                    </select>
                            </td>

                            <td>
                                <select name="permission" class="select-style" style="width:80px;">
                                    <option value="0" <?= ($row['permission'] == 0 ? 'selected' : '') ?>>啟用</option>
                                    <option value="1" <?= ($row['permission'] == 1 ? 'selected' : '') ?>>停用</option>
                                </select>
                            </td>

                            <td><?= $row['stop_reason'] ?></td>

                            <td>
                                <div class="action-box">
                                    <div class="btn-group">
                                        <form method="POST">
                                            <input type="hidden" name="account" value="<?= $row['account'] ?>">
                                            <button type="submit" name="update" class="btn-edit">修改</button>
                                            <button type="button" class="btn-del">刪除</button>
                                        </form>
                                    </div>

                                    <hr class="divider"> <!-- 分隔線 -->

                                    <div class="btn-group">
                                        <button class="btn-order">歷史訂單</button>
                                        <button class="btn-rate">評價</button>
                                        <button class="btn-chart">圖表</button>
                                        <a href="accountaction.php?account=<?= $row['account'] ?>" class="btn-log">日誌</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
<script>
    function validateHours() {
        const days = [1, 2, 3, 4, 5, 6, 7];
        let hasTime = false;

        for (let w of days) {
            const ranges = document.querySelectorAll(`#ranges-${w} .time-range`);
            for (let range of ranges) {
                const open = range.querySelector(`input[name="open_time[${w}][]"]`).value;
                const close = range.querySelector(`input[name="close_time[${w}][]"]`).value;
                if (open && close) {
                    hasTime = true;
                    break;
                }
            }
            if (hasTime) break;
        }

        if (!hasTime) {
            alert("請至少填寫一個營業時間！");
            return false; // 阻止表單送出
        }

        return true; // 通過檢查
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


</html>
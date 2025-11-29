<?php
session_start();
include "../db.php";  // 引入資料庫連線

//修改帳號狀態
if (isset($_POST['update'])) {
    $account = $_POST['account'];
    $role = $_POST['role'];
    $permission = $_POST['permission'];
    $stop_reason = trim($_POST['stop_reason'] ?? '');

    if ($permission == 1 && $stop_reason !== "") {
        $stmt = $link->prepare("UPDATE `account` SET `role`=?, `permission`=?, `stop_reason`=? WHERE `account`=?");
        $stmt->bind_param("iiss", $role, $permission, $stop_reason, $account);
    } else {
        $stmt = $link->prepare("UPDATE `account` SET `role`=?, `permission`=?, `stop_reason`=NULL WHERE `account`=?");
        $stmt->bind_param("iis", $role, $permission, $account);
    }

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
            --green: #3d9462;
            --green-dark: #2b6b47;
            --brown: #c19a6b;
            --brown-dark: #5c3d2e;
            --bg-light: #faf7f2;
            --border: #e0dcd6;
            --text-dark: #3d3d3d;
            --main-green: #3d9462;
            --dark-green: #2b6b47;
            --main-brown: #C19A6B;
            --dark-brown: #5C3D2E;
            --yellow: #c18f2c;
            --blue: #2f7dd2;
            --purple: #9b6fb5;
            --orange: #d97a2b;
            --gray: #6e7073;
        }

        .container {
            width: 95%;
            margin: 20px auto 0 auto;
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
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

        /* 表格 */
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
            border-radius: 14px;
        }

        thead {
            background: var(--main-brown);
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        thead th {
            padding: 12px;
            font-weight: 500;
            text-align: center;
        }

        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f2f2f2;
            text-align: center;
            font-size: 14px;
            color: #333;
        }

        tbody tr:hover {
            background: #f5f0eb;
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

        .btn-order {
            background: var(--orange);
        }

        .btn-rate {
            background: var(--purple);
        }

        .btn-chart {
            background: var(--main-green);
        }

        .btn-see {
            background: var(--yellow);
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

        .storemodal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 100;
            overflow: hidden;
            /* 背景不捲動，只讓 storemodal-content 捲 */
        }

        /* storemodal 內容框（加入垂直滾輪 & 視窗位置優化） */
        .storemodal-content {
            background-color: #fff7ef;
            margin: 6vh auto;
            /* 讓 storemodal 置中但更靠上，增加可滾動區域 */
            padding: 20px 30px;
            width: 80%;
            max-width: 1000px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            max-height: 80vh;
            /* 視窗最大高度 80% */
            overflow-y: auto !important;
            /* ✅ 強制啟用垂直滾輪 */
        }

        /* ✅ 讓滾輪符合你的棕色主題、但不花 */
        .storemodal-content::-webkit-scrollbar {
            width: 8px;
        }

        .storemodal-content::-webkit-scrollbar-track {
            background: var(--light-brown);
            border-radius: 10px;
        }

        .storemodal-content::-webkit-scrollbar-thumb {
            background: var(--mid-brown);
            border-radius: 10px;
        }

        .storemodal-content::-webkit-scrollbar-thumb:hover {
            background: var(--dark-brown);
        }

        /* storeModal 標題樣式 */
        .storemodal-content h2 {
            font-size: 22px;
            font-weight: 600;
            color: var(--brown-dark);
            margin-bottom: 18px;
            border-left: 5px solid var(--brown);
            padding-left: 10px;
        }

        .checkmodal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 100;
            overflow: hidden;
            /* 背景不捲動，只讓 checkmodal-content 捲 */
        }

        .checkmodal-content {
            background-color: #fefaf4;
            /* 溫暖乳白棕（比純白有質感、好閱讀） */
            margin: 20vh auto;
            /* 再稍微下移一點，更自然置中 */
            padding: 24px 30px;
            width: 90%;
            max-width: 1000px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(92, 61, 46, 0.22);
            /* 微棕陰影，更符合主題 */
            max-height: 76vh;
            overflow-y: auto;
            color: var(--text-dark);
        }


        /* ✅ 讓滾輪符合你的棕色主題、但不花 */
        .checkmodal-content::-webkit-scrollbar {
            width: 8px;
        }

        .checkmodal-content::-webkit-scrollbar-track {
            background: var(--light-brown);
            border-radius: 10px;
        }

        .checkmodal-content::-webkit-scrollbar-thumb {
            background: var(--mid-brown);
            border-radius: 10px;
        }

        .checkmodal-content::-webkit-scrollbar-thumb:hover {
            background: var(--dark-brown);
        }

        /* storeModal 標題樣式 */
        .checkmodal-content h2 {
            font-size: 22px;
            font-weight: 600;
            color: var(--brown-dark);
            margin-bottom: 18px;
            border-left: 5px solid var(--brown);
            padding-left: 10px;
        }

        /* 輸入區塊網格 */
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px 14px;
            margin-bottom: 20px;
        }

        /* Input & Select 統一外觀 */
        .form-row input,
        .form-row select {
            width: 80%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--main-brown);
            font-size: 14px;
            background: #fff;
            color: var(--deep-brown);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .form-row input:focus,
        .form-row select:focus {
            outline: 2px solid var(--dark-brown);
            border-color: var(--dark-brown);
        }

        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #ca5b2d;
        }

        /* FORM ---------------------------------- */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px 18px;
            margin-bottom: 20px;
        }


        .form-grid input,
        .form-grid select {
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            color: var(--text-dark);
            transition: 0.15s;
        }


        .form-grid input:focus,
        .form-grid select:focus {
            border-color: var(--brown);
            box-shadow: 0 0 4px rgba(193, 154, 107, 0.35);
        }

        /* HOURS BLOCK ----------------------------- */
        .hours-section {
            background: var(--bg-light);
            padding: 18px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 16px;
        }


        .hours-title {
            font-weight: 600;
            margin-bottom: 12px;
        }

        .hours-row {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
        }

        .hours-block {
            flex: 1;
            /* 🟢 每個區塊等寬 */
            min-width: 220px;
            /* 避免太窄 */
            background: white;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .hours-block strong {
            color: var(--brown-dark);
        }


        .time-range {
            display: flex;
            gap: 6px;
            margin-top: 6px;
        }


        .time-range input[type="time"] {
            padding: 1px;
            border-radius: 6px;
            border: 1px solid var(--border);
        }


        .add-btn {
            margin-top: 6px;
            padding: 6px 10px;
            border-radius: 8px;
            background: var(--brown);
            color: white;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }


        .add-btn:hover {
            background: var(--brown-dark);
        }


        .del-btn {
            background: #ff6b6b;
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }


        .del-btn:hover {
            background: #e60000;
        }

        /* FOOTER ---------------------------------- */
        .storemodal-footer {
            text-align: right;
            margin-top: 20px;
        }

        .checkmodal-footer {
            text-align: right;
            margin-top: 20px;
        }


        .btn-save {
            background: var(--green);
            padding: 10px 16px;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
        }


        .btn-save:hover {
            background: var(--green-dark);
        }

        .search-row {
            display: flex;
            justify-content: flex-end;
            /* ✅ 讓內容全部靠右 */
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .search-row input {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid var(--main-brown);
            font-size: 14px;
            width: 180px;
        }

        .search-row button {
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            background: var(--main-brown);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .search-row button:hover {
            opacity: 0.85;
            transform: scale(1.05);
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
        <div class="search-row">
            <form method="POST" class="search-box">
                <input type="text" name="query_name" placeholder="查詢店名">
                <button type="submit" name="search_btn">查詢</button>
            </form>
            <button type="button" onclick="openModal()">＋ 新增店家</button>
        </div>

        <!-- 店家新增 Modal -->
        <div id="storeModal" class="storemodal">
            <div class="storemodal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>新增店家</h2>

                <form method="POST" onsubmit="return validateHours();">
                    <div class="form-row">
                        <input type="text" name="username" placeholder="帳號" required>
                        <input type="password" name="password" placeholder="密碼" required>
                        <input type="password" name="confirm_password" placeholder="確認密碼" required>
                        <input type="text" name="phone" placeholder="電話" required pattern="(09\d{8}|0\d{1,3}-?\d{5,8})">
                        <input type="text" name="email" placeholder="電子郵件">

                        <!-- 店家類型 select -->
                        <?php
                        $result = $link->query("SELECT storetype_id, name FROM storetype");
                        if ($result->num_rows > 0) {
                            echo '<select name="storetype" class="select-style" required>';
                            echo '<option value="">店家類型</option>';
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

                    <div class="hours-container">
                        <?php
                        $days = ["1" => "星期一", "2" => "星期二", "3" => "星期三", "4" => "星期四", "5" => "星期五", "6" => "星期六", "7" => "星期日"];
                        echo '<div class="hours-row">';
                        foreach ([1, 2, 3, 4] as $w) {
                            echo '<div class="hours-block"><strong>' . $days[$w] . ':</strong>
                   <div id="ranges-' . $w . '"></div>
                   <button type="button" class="add-btn" onclick="addRange(' . $w . ')">+新增時段</button>
                  </div>';
                        }
                        echo '</div><div class="hours-row">';
                        foreach ([5, 6, 7] as $w) {
                            echo '<div class="hours-block"><strong>' . $days[$w] . ':</strong>
                   <div id="ranges-' . $w . '"></div>
                   <button type="button" class="add-btn" onclick="addRange(' . $w . ')">+新增時段</button>
                  </div>';
                        }
                        echo '</div>';
                        ?>
                    </div>

                    <div class="storemodal-footer">
                        <button type="submit" name="add_store" class="btn-save">儲存</button>
                    </div>
                </form>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>帳號</th>
                    <!-- <th>密碼</th> -->
                    <th>店名</th>
                    <!-- <th>描述</th>
                    <th>地址</th>
                    <th>電話</th>
                    <th>電子郵件</th> -->
                    <th>店家類型</th>
                    <!-- <th>營業時間</th> -->
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
                                WHERE (a.role=1 OR a.role=3) AND b.name LIKE '%$query_name%'
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
                        $stores[$acc]['logUrl'] = "accountaction.php?account=" . urlencode($acc); //存入 URL
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
                            <!-- <td><?= $row['description'] ?></td>
                            <td><?= $row['address'] ?></td>
                            <td><?= $row['phone'] ?></td>
                            <td><?= $row['email'] ?></td> -->
                            <td><?= $row['type_name'] ?></td>

                            <!-- 營業時間 -->
                            <!-- <td style="text-align:center; vertical-align:middle;">
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
                            </td> -->

                            <!-- <td><?= $row['created_time'] ?></td> -->

                            <td>
                                <select name="role" class="select-style" style="width: 150px;">
                                    <option value="1" <?= ($row['role'] == 1 ? 'selected' : '') ?>>店家</option>
                                    <option value="3" <?= ($row['role'] == 3 ? 'selected' : '') ?>>店家註冊審核中</option>
                                </select>
                            </td>

                            <td>
                                <select name="permission" class="select-style perm-select" style="width: 150px;"
                                    data-account="<?= $row['account'] ?>" id="perm_<?= $row['account'] ?>">
                                    <option value="0" <?= ($row['permission'] == 0 ? 'selected' : '') ?>>啟用</option>
                                    <option value="1" <?= ($row['permission'] == 1 ? 'selected' : '') ?>>停用</option>
                                </select>

                                <!-- <input type="hidden" name="stop_reason" id="stop_input_<?= $row['account'] ?>"
                                    value="<?= htmlspecialchars($row['stop_reason']) ?>"
                                    data-current="<?= $row['permission'] ?>"> -->
                            </td>

                            <td><?= $row['stop_reason'] ?></td>

                            <td>
                                <div class="action-box">
                                    <div class="btn-group">
                                        <form method="POST" onsubmit="return submitPermissionForm('<?= $row['account'] ?>')">
                                            <input type="hidden" name="account" value="<?= $row['account'] ?>">
                                            <input type="hidden" name="role" id="role_input_<?= $row['account'] ?>"
                                                value="<?= $row['role'] ?>">
                                            <input type="hidden" name="permission" id="perm_input_<?= $row['account'] ?>"
                                                value="<?= $row['permission'] ?>">
                                            <input type="hidden" name="stop_reason" id="stop_input_<?= $row['account'] ?>"
                                                value="<?= htmlspecialchars($row['stop_reason']) ?>"
                                                data-current="<?= $row['permission'] ?>">
                                            <button type="submit" name="update" class="btn-edit">修改</button>
                                        </form>

                                        <form method="POST">
                                            <button type="button" class="btn-see" onclick='opencheckModal({
                                                                        no: "<?= $i - 1 ?>",
                                                                        account: "<?= $row["account"] ?>",
                                                                        store: "<?= addslashes($row["store_name"]) ?>",
                                                                        description: "<?= addslashes($row["description"]) ?>",
                                                                        address: "<?= addslashes($row["address"]) ?>",
                                                                        phone: "<?= $row["phone"] ?>",
                                                                        email: "<?= $row["email"] ?>",
                                                                        type: "<?= addslashes($row["type_name"]) ?>",
                                                                        created: "<?= $row["created_time"] ?>",
                                                                        permission: <?= $row["permission"] ?>,
                                                                        role: <?= $row["role"] ?>,
                                                                        stopReason: "<?= addslashes($row["stop_reason"]) ?>",
                                                                        hours: <?= json_encode($row["hours"], JSON_UNESCAPED_UNICODE) ?>
                                                                    })'>
                                                查看詳細
                                            </button>

                                        </form>
                                    </div>
                                </div>

                                <hr class="divider"> <!-- 分隔線 -->

                                <div class="btn-group">
                                    <button type="button" class="btn-order"
                                        onclick="location.href='store_material_history.php?account=<?= $row['account'] ?>'">歷史訂單</button>
                                    <button class="btn-rate">評價</button>
                                    <button class="btn-chart">圖表</button>
                                    <button type="button" class="btn-log"
                                        onclick="window.location.href='<?= $row['logUrl'] ?>'">日誌</button>
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

    <!-- ===== 查看詳細 Modal ===== -->
    <div id="checkModal" class="checkmodal" style="display:none">
        <div class="checkmodal-content">
            <span class="close" onclick="closecheckModal()">&times;</span>

            <!-- 你要的那一行標題 -->
            <h3 id="checkTitle" style="margin-bottom:14px;color:var(--dark-brown);font-weight:600"></h3>

            <!-- 下方顯示你指定欄位 -->
            <table style="width:100%;border:1px solid var(--border);border-radius:10px;border-collapse:collapse;">
                <thead>
                    <tr style="background:var(--main-brown);color:#fff;">
                        <th>描述</th>
                        <th>地址</th>
                        <th>電話</th>
                        <th>電子郵件</th>
                        <th>店家類型</th>
                        <th>營業時間</th>
                        <th>建立時間</th>
                        <th>權限</th>
                        <th>狀態</th>
                        <th>停機原因</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td id="cDesc"></td>
                        <td id="cAddr"></td>
                        <td id="cPhone"></td>
                        <td id="cEmail"></td>
                        <td id="cType"></td>
                        <td id="cHours"></td>
                        <td id="cCreated"></td>
                        <td id="cPermission"></td>
                        <td id="cRole"></td>
                        <td id="cReason"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</body>
<script>
    function submitPermissionForm(account) {
        const permSelect = document.getElementById("perm_" + account);
        const stopInput = document.getElementById("stop_input_" + account);
        const roleSelect = document.querySelector(`select[name="role"][data-account="${account}"]`);
        const roleInput = document.getElementById("role_input_" + account);
        const permInput = document.getElementById("perm_input_" + account);

        // 同步值到 hidden input
        roleInput.value = roleSelect.value;
        permInput.value = permSelect.value;

        // 只有從非停用變成停用才要求輸入原因
        const currentPermission = stopInput.dataset.current || "0";

        if (permSelect.value === "1" && currentPermission !== "1") {
            let reason = prompt("請輸入停用原因：");
            if (!reason || reason.trim() === "") {
                alert("必須填寫停用原因！");
                return false; // 阻止送出
            }
            stopInput.value = reason.trim(); // ✅ 這裡一定要改 hidden input 的 value
        } else if (permSelect.value !== "1") {
            stopInput.value = ""; // 啟用就清空
        }

        return true; // 允許送出
    }


    // 將 role select 加上 data-account 屬性，方便 JS 讀取
    document.querySelectorAll('select[name="role"]').forEach(sel => {
        const tr = sel.closest('tr');
        sel.dataset.account = tr.querySelector('td:nth-child(2)').innerText;
    });

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

    function openModal() {
        document.getElementById("storeModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("storeModal").style.display = "none";
    }

    // 點擊外部關閉
    window.onclick = function (event) {
        const modal = document.getElementById("storeModal");
        if (event.target === modal) {
            closeModal();
        }
    }

    function opencheckModal(data) {
        document.getElementById("checkModal").style.display = "block";

        // 第一行標題
        document.getElementById("checkTitle").innerHTML =
            `#${data.no}　帳號: ${data.account}　店家名稱: ${data.store}`;

        // 文字欄位
        document.getElementById("cDesc").innerText = data.description;
        document.getElementById("cAddr").innerText = data.address;
        document.getElementById("cPhone").innerText = data.phone;
        document.getElementById("cEmail").innerText = data.email;
        document.getElementById("cType").innerText = data.type;
        document.getElementById("cCreated").innerText = data.created;

        // 0/1 轉中文
        document.getElementById("cPermission").innerText = (data.permission == 0 ? "啟用" : "停用");
        document.getElementById("cRole").innerText = (data.role == 1 ? "店家" : "店家註冊審核中");

        // 停機原因
        document.getElementById("cReason").innerText = data.stopReason || "無";

        // 權限
        document.getElementById("cPermission").innerText = (data.permission == 0 ? "啟用" : "停用");

        // 營業時間整理
        let hoursText = "";
        const weekName = ['星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期日'];
        for (let w = 1; w <= 7; w++) {
            if (data.hours[w]) {
                hoursText += `${weekName[w - 1]}<br>${data.hours[w]}<br><br>`;
            }
        }

        document.getElementById("cHours").innerHTML = hoursText || "未設定";
    }

    function closecheckModal() {
        document.getElementById("checkModal").style.display = "none";
    }

    // 背景點擊關閉
    window.addEventListener("click", e => {
        if (e.target.id === "checkModal") closecheckModal();
    });


    function closecheckModal() {
        document.getElementById("checkModal").style.display = "none";
    }
</script>


</html>
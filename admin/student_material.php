<?php
session_start();
include "../db.php";  // 引入資料庫連線

//修改帳號狀態
if (isset($_POST['update_permission'])) {
    $account = $_POST['account'];
    $permission = $_POST['permission'];
    $stop_reason = trim($_POST['stop_reason']);

    if ($permission == 1 && $stop_reason !== "") {
        $stmt = $link->prepare("UPDATE `account` SET `permission`=?, `stop_reason`=? WHERE `account`=?");
        $stmt->bind_param("iss", $permission, $stop_reason, $account);
    } else {
        $stmt = $link->prepare("UPDATE `account` SET `permission`=?, `stop_reason`=NULL WHERE `account`=?");
        $stmt->bind_param("is", $permission, $account);
    }

    if ($stmt->execute()) {
        echo "<script>alert('帳號 $account 狀態修改成功'); window.location='student_material.php';</script>";
        exit;
    } else {
        echo "<script>alert('更新失敗: " . $link->error . "'); history.back();</script>";
        exit;
    }
}


if (isset($_POST['add_student'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $name = $_POST['name'];
    $nickname = $_POST['nickname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    if ($password !== $confirm_password) {
        echo "<script>alert('密碼與確認密碼不一致'); history.back();</script>";
        exit;
    }

    if (!preg_match('/^(09\d{8}|0\d{1,3}-?\d{5,8})$/', $phone)) {
        echo "<script>alert('電話格式不正確'); history.back();</script>";
        exit;
    }

    $link->begin_transaction();

    $result = $link->query("SELECT MAX(student_id) AS maxid FROM student");
    $row    = $result->fetch_assoc();
    $nextId = ($row['maxid'] === NULL ? 1 : $row['maxid'] + 1);

    try {
        $stmt1 = $link->prepare("INSERT INTO `account`(`account`, `password`, `created_time`, `role`, `permission`, `stop_reason`) VALUES (?, ?, CURRENT_TIMESTAMP(), 0, 0, NULL)");
        $stmt1->bind_param("ss", $username, $password);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $link->prepare("INSERT INTO `student`(`student_id`, `name`, `nickname`, `phone`, `email`, `account`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("isssss", $nextId, $name, $nickname, $phone, $email, $username);
        $stmt2->execute();
        $stmt2->close();

        $link->commit();
        echo "<script>alert('新增學生資料成功！'); window.location='student_material.php';</script>";
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
    <title>學生/教職員資料</title>

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

        .add-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            padding: 12px;
            background: #fafafa;
            border-radius: 12px;
            margin-bottom: 18px;
        }

        .add-box input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
        }

        .add-box button {
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

        .add-box button:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }

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

        .btn-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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

        .search-box button:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }
    </style>

</head>

<body>
    <?php include "admin_menu.php"; ?>

    <div class="container">
        <h2>學生/教職員資料管理</h2>
        <!--查詢-->
        <form method="POST" class="search-box">
            <input type="text" name="query_name" placeholder="查詢姓名">
            <button type="submit" name="search_btn">查詢</button>
        </form>

        <!-- 新增學生 -->
        <form method="POST" class="add-box">
            <input type="text" name="username" placeholder="帳號" required>
            <input type="password" name="password" placeholder="密碼" required>
            <input type="password" name="confirm_password" placeholder="確認密碼" required>
            <input type="text" name="name" placeholder="姓名">
            <input type="text" name="nickname" placeholder="暱稱">
            <input type="text" name="phone" placeholder="電話" required pattern="(09\d{8}|0\d{1,3}-?\d{5,8})">
            <input type="text" name="email" placeholder="電子郵件">
            <button type="submit" name="add_student">＋ 新增學生/教職員</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>帳號</th>
                    <!-- <th>密碼</th> -->
                    <th>姓名</th>
                    <th>暱稱</th>
                    <th>電話</th>
                    <th>電子郵件</th>
                    <th>建立時間</th>
                    <th>狀態</th>
                    <th>停機原因</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // 判斷是否按下查詢按鈕
                if (isset($_POST['search_btn']) && !empty($_POST['query_name'])) {
                    $query_name = $link->real_escape_string($_POST['query_name']);

                    $sql = "SELECT a.`account`, a.`password`, b.`name`, b.`nickname`, b.`phone`, b.`email`,
            a.`created_time`, a.`permission`, a.`stop_reason`
            FROM `account` As a
            INNER JOIN `student` AS b ON a.`account` = b.`account`
            WHERE a.role=0 AND b.`name` LIKE '%$query_name%'";
                } else {
                    // 沒按查詢 → 顯示全部
                    $sql = "SELECT a.`account`, a.`password`, b.`name`, b.`nickname`, b.`phone`, b.`email`,
            a.`created_time`, a.`permission`, a.`stop_reason`
            FROM `account` As a
            INNER JOIN `student` AS b ON a.`account` = b.`account`
            WHERE a.role=0";
                }

                $result = $link->query($sql);
                $i = 1;

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $logUrl = "accountaction.php?account=" . urlencode($row['account']); // 確保帳號安全
                ?>
                        <tr>
                            <td style="text-align:center"><?= $i ?></td>
                            <td><?= htmlspecialchars($row['account']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['nickname']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= $row['created_time'] ?></td>

                            <td>
                                <form method="POST" class="status-form">
                                    <select id="perm_<?= $row['account'] ?>" class="select-style" style="width:80px;">
                                        <option value="0" <?= ($row['permission'] == 0 ? 'selected' : '') ?>>啟用</option>
                                        <option value="1" <?= ($row['permission'] == 1 ? 'selected' : '') ?>>停用</option>
                                    </select>
                                </form>
                            </td>

                            <td><?= htmlspecialchars($row['stop_reason']) ?></td>

                            <td>
                                <div class="action-box">
                                    <div class="btn-group">
                                        <form method="POST" onsubmit="return submitPermissionForm('<?= $row['account'] ?>')">
                                            <input type="hidden" name="account" value="<?= $row['account'] ?>">
                                            <input type="hidden" name="permission" id="perm_input_<?= $row['account'] ?>">
                                            <input type="hidden" name="stop_reason" id="stop_input_<?= $row['account'] ?>">
                                            <button type="submit" name="update_permission" class="btn-edit">修改</button>
                                        </form>
                                    </div>

                                    <hr class="divider">

                                    <div class="btn-group">
                                        <button type="button" class="btn-order" onclick="location.href='student_material_history.php?account=<?= htmlspecialchars($row['account']) ?>'">歷史訂單</button>
                                        <!-- <button class="btn-rate">評價</button> -->
                                        <!-- <button class="btn-chart">圖表</button> -->
                                        <button class="btn-log" onclick="window.location.href='<?php echo $logUrl; ?>'">日誌</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                <?php
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='10' style='text-align:center;color:#888'>無學生/教職員資料</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

<script>
    function submitPermissionForm(account) {
        const selectVal = document.getElementById("perm_" + account).value;

        // 把 select 的值塞入 form hidden
        document.getElementById("perm_input_" + account).value = selectVal;

        if (selectVal == "1") { // 停用
            let reason = prompt("請輸入停用原因：");
            if (reason === null || reason.trim() === "") {
                alert("必須填寫停用原因！");
                return false; // 取消 submit
            }
            document.getElementById("stop_input_" + account).value = reason.trim();
        } else {
            document.getElementById("stop_input_" + account).value = "";
        }
        return true; // 允許送出
    }
</script>

</html>
<?php
session_start();
include "../db.php";  // 引入資料庫連線

//修改帳號狀態
if (isset($_POST['update_permission'])) {
    $account = $_POST['account'];
    $permission = $_POST['permission'];

    $stmt = $link->prepare("UPDATE `account` SET `permission`=? WHERE `account`=?");
    $stmt->bind_param("is", $permission, $account);
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
            padding: 20px;
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
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>

    <div class="container">
        <h2>學生/教職員資料管理</h2>

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
                    <th>Email</th>
                    <th>建立時間</th>
                    <th>狀態</th>
                    <th>停機原因</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>

                <?php
                $sql = "SELECT a.`account`, a.`password`, b.`name`, b.`nickname`, b.`phone`, b.`email`, a.`created_time`, a.`permission`, a.`stop_reason`
FROM `account` As a INNER JOIN `student` AS b ON a.`account` = b.`account`
WHERE a.role=0";

                $result = $link->query($sql);
                $i = 1;

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                ?>
                        <tr>
                            <td style="text-align:center"><?= $i ?></td>
                            <td><?= $row['account'] ?></td>
                            <!-- <td class="password">••••••••</td> -->
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['nickname'] ?></td>
                            <td><?= $row['phone'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td><?= $row['created_time'] ?></td>

                            <td>
                                <form method='POST' class="status-form">
                                    <input type='hidden' name='account' value='<?= $row['account'] ?>'>
                                    <select name='permission'>
                                        <option value='0' <?= ($row['permission'] == 0 ? 'selected' : '') ?>>啟用</option>
                                        <option value='1' <?= ($row['permission'] == 1 ? 'selected' : '') ?>>停用</option>
                                    </select>
                            </td>

                            <td><?= $row['stop_reason'] ?></td>

                            <td>
                                <div class="action-box">
                                    <div class="btn-group">
                                        <form method="POST">
                                            <input type="hidden" name="account" value="<?= $row['account'] ?>">
                                            <button type="submit" name="update_permission" class="btn-edit">修改狀態</button>
                                            <button type="button" class="btn-del">刪除</button>
                                        </form>
                                    </div>

                                    <hr class="divider"> <!-- 分隔線 -->

                                    <div class="btn-group">
                                        <button class="btn-order">歷史訂單</button>
                                        <button class="btn-rate">評價</button>
                                        <button class="btn-chart">圖表</button>
                                        <button class="btn-log">日誌</button>
                                    </div>
                                </div>
                            </td>
                            <td>

                        </tr>
                <?php
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='11' style='text-align:center;color:#888'>尚無學生資料</td></tr>";
                }
                ?>

            </tbody>
        </table>
    </div>

</body>

</html>
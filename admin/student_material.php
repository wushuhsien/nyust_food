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

//新增資料
if (isset($_POST['add_student'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $name = $_POST['name'];
    $nickname = $_POST['nickname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // 驗證密碼
    if ($password !== $confirm_password) {
        echo "<script>alert('密碼與確認密碼不一致'); history.back();</script>";
        exit;
    }

    // 驗證電話格式
    if (!preg_match('/^(09\d{8}|0\d{1,3}-?\d{5,8})$/', $phone)) {
        echo "<script>alert('電話格式不正確'); history.back();</script>";
        exit;
    }

    // 使用 transaction 保證兩個 SQL 一起成功
    $link->begin_transaction();

    // 取得 student 最大流水號
    $result = $link->query("SELECT MAX(student_id) AS maxid FROM student");
    $row    = $result->fetch_assoc();
    $nextId = ($row['maxid'] === NULL ? 1 : $row['maxid'] + 1);

    try {
        // 1️⃣ 插入 account 表
        $stmt1 = $link->prepare("INSERT INTO `account`(`account`, `password`, `created_time`, `role`, `permission`, `stop_reason`) VALUES (?, ?, CURRENT_TIMESTAMP(), 0, 0, NULL)");
        $stmt1->bind_param("ss", $username, $password);
        $stmt1->execute();
        $stmt1->close();

        // 2️⃣ 插入 student 表
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-top: 30px;
            font-weight: 600;
        }

        .table-container {
            width: 90%;
            margin: 0 auto;
        }

        .add-row {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            margin-bottom: 15px;
        }

        .add-row input {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .add-row button {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            background-color: #4caf50;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .add-row button:hover {
            background-color: #43a047;
            transform: scale(1.05);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            background-color: #ffffff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }

        thead {
            background: linear-gradient(90deg, #4caf50, #43a047);
            color: #fff;
            font-size: 16px;
        }

        thead th {
            text-align: center;
        }

        th,
        td {
            padding: 14px 18px;
            text-align: left;
        }

        th:first-child,
        td:first-child {
            text-align: center;
            width: 70px;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr:hover {
            background-color: #e0f2f1;
            transform: scale(1.01);
        }

        td button {
            margin: 2px 4px;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            background-color: #4caf50;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        td button:hover {
            background-color: #43a047;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {

            table,
            th,
            td {
                font-size: 14px;
            }

            td button,
            .add-row button {
                padding: 4px 8px;
                font-size: 12px;
            }

            .add-row input {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>

    <h2>學生/教職員資料</h2>

    <div class="table-container">
        <!-- 新增資料輸入列 -->
        <form method="POST" class="add-row">
            <input type="text" name="username" placeholder="帳號" required><br>
            <input type="password" name="password" placeholder="密碼" required><br>
            <input type="password" name="confirm_password" placeholder="確認密碼" required><br>
            <input type="text" name="name" placeholder="姓名">
            <input type="text" name="nickname" placeholder="暱稱">
            <input type="text" name="phone" required
                pattern="(09\d{8}|0\d{1,3}?\d{5,8})"
                placeholder="電話">
            <input type="text" name="email" placeholder="電子郵件">
            <button type="submit" name="add_student">新增</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>流水號</th>
                    <th>帳號</th>
                    <th>密碼</th>
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
                $sql = "SELECT a.`account`, a.`password`, b.`name`, b.`nickname`, b.`phone`, b.`email`, a.`created_time`, a.`permission`, a.`stop_reason`
                FROM `account` As a INNER JOIN `student` AS b ON a.`account` = b.`account`
                WHERE a.role=0";
                $result = $link->query($sql);
                $i = 1; // 流水號
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $i . "</td>";
                        echo "<td>" . $row['account'] . "</td>";
                        echo "<td>" . $row['password'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['nickname'] . "</td>";
                        echo "<td>" . $row['phone'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['created_time'] . "</td>";
                        echo "<td>
                                <form method='POST'>
                                    <input type='hidden' name='account' value='" . $row['account'] . "'>
                                    <select name='permission'>
                                        <option value='0' " . ($row['permission'] == 0 ? 'selected' : '') . ">啟用</option>
                                        <option value='1' " . ($row['permission'] == 1 ? 'selected' : '') . ">停用</option>
                                    </select>
                            </td>";
                        echo "<td>" . $row['stop_reason'] . "</td>";
                        echo "<td>
                            <button type='submit' name='update_permission'>修改</button>
                                </form>
                            <button>刪除</button>
                            <button>歷史訂單</button>
                            <button>評價</button>
                            <button>圖表</button>
                            <button>日誌</button>
                        </td>";
                        echo "</tr>";
                        $i++;
                    }
                } else {
                    echo "<tr><td colspan='11' style='text-align:center;'>沒有學生/教職員資料</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>
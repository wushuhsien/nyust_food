<?php
session_start();
include "../db.php";  // 引入資料庫連線

$account = $_SESSION['user'] ?? "";

if (!$account) {
    echo "<script>alert('未登入'); window.location='login.html';</script>";
    exit;
}

// 引入資料庫MongoDB
require_once "../db_mongo.php";

// 處理修改或刪除
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $report_id = intval($_POST['report_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($report_id > 0) {
        if ($action === "update") {
            $status = $_POST['status'] ?? '';

            //先查出該報告在資料庫的資料
            $stmt2 = $link->prepare("SELECT account_student, description, time FROM report WHERE report_id=?");
            $stmt2->bind_param("i", $report_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $target = $result2->fetch_assoc();
            $stmt2->close();

            //更新資料庫
            $stmt = $link->prepare("UPDATE `report` SET `status`=? WHERE `report_id`=?");
            $stmt->bind_param("si", $status, $report_id);
            if ($stmt->execute()) {
                // 更新 MongoDB (同步 status)
                $filter = [
                    "user_account" => $target["account_student"],
                    "description"  => $target["description"],
                    "time"         => $target["time"]
                ];

                $bulk = new MongoDB\Driver\BulkWrite();
                $bulk->update(
                    $filter,
                    ['$set' => ["status" => $status]],
                    ['multi' => true]
                );

                $manager->executeBulkWrite("store_db.store_report", $bulk);
                echo "<script>alert('修改成功！'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
            } else {
                echo "<script>alert('修改失敗！');</script>";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家被投訴歷史紀錄</title>
    <style>
        .container {
            width: 90%;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            font-size: 26px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 15px;
        }

        th {
            background: #d17a22;
            color: #fff8f0;
            font-weight: 600;
        }

        tr:hover {
            background: #FFEEDD;
        }

        .status {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .s1 {
            /* 未處理 */
            background: #e74c3c;
        }


        .s2 {
            /* 處理中 */
            background: #f39c12;
        }


        .s3 {
            /* 已完成 */
            background: #27ae60;
        }

        /* 彈跳視窗背景 */
        .modal-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        /* 彈跳視窗內容 */
        .modal-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            width: 420px;
            text-align: center;
            position: relative;
        }

        .modal-box img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-bottom: 15px;
            /* 圖片下方增加空間 */
        }

        .close-btn {
            position: absolute;
            top: 8px;
            right: 10px;
            font-size: 18px;
            cursor: pointer;
        }

        .btn-nav {
            cursor: pointer;
            padding: 10px 12px;
            background: #d17a22;
            color: #fff8f0;
            border-radius: 8px;
            margin: 10px;
            font-size: 14px;
        }
         .edit-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            color: white;
            cursor: pointer;
            background-color: #f28c28;
        }

        .edit-btn:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <?php include "store_menu.php"; ?>
    <div class="container">
        <h2>店家被投訴歷史紀錄</h2>

        <table>
            <tr>
                <th>流水號</th>
                <th>投訴者</th>
                <th>訴求</th>
                <th>圖片</th>
                <th>時間</th>
                <th>狀態</th>
                <th>操作</th>
            </tr>

            <?php
            $sql = "SELECT `report_id`, `account_student`, `description`, `time`, `status`
                FROM `report`
                WHERE `type`='投訴店家' And `account_store`='$account'
                ORDER BY `time` ASC";

            $result = $link->query($sql);
            $count = 1;
            if ($result->num_rows == 0) {
                echo "<tr>
                        <td colspan='7' style='padding:20px; font-size:18px; color:#888;'>
                            目前沒有任何投訴資料
                        </td>
                    </tr>";
            } else {
                while ($row = $result->fetch_assoc()) {
                    $images = [];

                    // 比對 JSON 找圖片
                    $filter1 = [
                        "description"   => $row["description"],
                        "time"          => $row["time"],
                        "store_account" => $account
                    ];

                    $query1 = new MongoDB\Driver\Query($filter1);
                    $cursor1 = $manager->executeQuery("store_db.store_report", $query1);

                    foreach ($cursor1 as $doc) {
                        if (isset($doc->images)) {
                            foreach ($doc->images as $img) {
                                // MongoDB 裡的圖片是路徑字串
                                $images[] = $img;
                            }
                        }
                    }

                    $imgBtn = !empty($images) ? "<button onclick='showImages(" . htmlspecialchars(json_encode($images), ENT_QUOTES) . ")'>查看</button>" : "";

                    // 狀態下拉選單
                    $statusOptions = ["未處理", "處理中", "已完成"];
                    $statusSelect = "<select name='status' class='status'>";
                    foreach ($statusOptions as $status) {
                        $selected = ($row['status'] === $status) ? "selected" : "";
                        $statusSelect .= "<option value='{$status}' $selected>{$status}</option>";
                    }
                    $statusSelect .= "</select>";

                    // 狀態顯示樣式
                    $statusClass = "s1";
                    if ($row['status'] === "處理中") $statusClass = "s2";
                    if ($row['status'] === "已完成") $statusClass = "s3";

                    echo "<tr>
                            <td>{$count}</td>
                            <td>{$row['account_student']}</td>
                            <td>{$row['description']}</td>
                            <td>$imgBtn</td>
                            <td>{$row['time']}</td>
                            <td>                
                            <form method='POST' style='display:inline-block;'>
                                $statusSelect
                            </td>
                            <td>                    
                                <input type='hidden' name='report_id' value='{$row['report_id']}'>
                                    <input type='hidden' name='action' value='update'>
                                    <button type='submit' class='edit-btn'>修改</button>
                                </form>
                            </td>
                        </tr>";
                    $count++;
                }
            }
            ?>

        </table>
    </div>

    <!-- 圖片彈跳視窗 -->
    <div class="modal-bg" id="modal">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">✖</span>
            <img id="modalImg" src="">
            <div>
                <span class="btn-nav" onclick="prevImg()">上一張</span>
                <span class="btn-nav" onclick="nextImg()">下一張</span>
            </div>
        </div>
    </div>

    <script>
        let images = [];
        let index = 0;

        function showImages(arr) {
            images = arr;
            index = 0;
            document.getElementById("modalImg").src = images[index];
            document.getElementById("modal").style.display = "flex";

            // 判斷是否顯示上一張/下一張按鈕
            const navBtns = document.querySelectorAll(".btn-nav");
            if (images.length <= 1) {
                navBtns.forEach(btn => btn.style.display = "none");
            } else {
                navBtns.forEach(btn => btn.style.display = "inline-block");
            }
        }

        function closeModal() {
            document.getElementById("modal").style.display = "none";
        }

        function prevImg() {
            if (index > 0) {
                index--;
                document.getElementById("modalImg").src = images[index];
            }
        }

        function nextImg() {
            if (index < images.length - 1) {
                index++;
                document.getElementById("modalImg").src = images[index];
            }
        }
    </script>
</body>

</html>
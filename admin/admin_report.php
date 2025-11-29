<?php
session_start();
include "../db.php";  // 引入資料庫連線

// 讀取 store_report.json
$jsonPath = "../JSON/admin_report.json";
$jsonData = json_decode(file_get_contents($jsonPath), true);

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
                //同步JSON
                foreach ($jsonData as &$item) {
                    if (
                        $item["user_account"] == $target["account_student"] &&
                        $item["description"] == $target["description"] &&
                        $item["time"] == $target["time"]
                    ) {
                        $item["status"] = $status;
                        break;
                    }
                }
                file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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
    <title>系統問題</title>
    <style>
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

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
            border-radius: 14px;
            margin-top: 30px;
        }

        th,
        td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            font-size: 15px;
        }

        th {
            background: #C19A6B;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .status {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
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
            background: #C19A6B;
            border-radius: 8px;
            color: white;
            margin: 10px;
            font-size: 14px;
        }

        .search {
            padding: 4px 8px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            color: white;
            cursor: pointer;
            background-color: #C19A6B;
        }

        .search:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }

        .edit-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            color: white;
            cursor: pointer;
            background-color: #6F4E37;
        }

        .edit-btn:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>
    <div class="container">
        <h2>系統問題</h2>

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
                WHERE `type`='系統問題'
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
                    foreach ($jsonData as $item) {
                        if (
                            $item["user_account"] == $row["account_student"] &&
                            $item["description"] == $row["description"] &&
                            $item["time"] == $row["time"]
                        ) {
                            $images = $item["images"];
                            break;
                        }
                    }
                    $imgBtn = !empty($images) ? "<button class='search' onclick='showImages(" . htmlspecialchars(json_encode($images), ENT_QUOTES) . ")'>查看</button>" : "";

                    // 狀態下拉選單
                    $statusOptions = ["未處理", "處理中", "已完成"];
                    $statusSelect = "<select name='status' class='status'>";
                    foreach ($statusOptions as $status) {
                        $selected = ($row['status'] === $status) ? "selected" : "";
                        $statusSelect .= "<option value='{$status}' $selected>{$status}</option>";
                    }
                    $statusSelect .= "</select>";

                    // 表格裡直接放 form 讓每列自己送出
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
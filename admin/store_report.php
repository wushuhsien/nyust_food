<?php
session_start();
include "../db.php";  // 引入資料庫連線

// 讀取 store_report.json
$jsonPath = "../JSON/store_report.json";
$jsonData = json_decode(file_get_contents($jsonPath), true);
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家問題</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Microsoft JhengHei", sans-serif;
            background: #f5f6fa;
        }

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
            background: #2ecc71;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .status {
            padding: 6px 10px;
            border-radius: 6px;
            color: white;
            font-size: 14px;
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
            background: #2ecc71;
            border-radius: 8px;
            color: white;
            margin: 10px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>
    <div class="container">
        <h2>店家問題</h2>

        <table>
            <tr>
                <th>流水號</th>
                <th>投訴者</th>
                <th>訴求</th>
                <th>圖片</th>
                <th>時間</th>
                <th>被投訴店家</th>
                <th>狀態</th>
            </tr>

            <?php
            $sql = "SELECT `account_student`, `description`, `time`, `account_store`, `status`
                FROM `report`
                WHERE `type`='投訴店家'
                ORDER BY `time` ASC";

            $result = $link->query($sql);
            $count = 1;
            if ($result->num_rows == 0) {
                echo "
                        <tr>
                            <td colspan='7' style='padding:20px; font-size:18px; color:#888;'>
                                目前沒有任何投訴資料
                            </td>
                        </tr>
                    ";
            } else {
                while ($row = $result->fetch_assoc()) {
                    $images = [];

                    // 比對 JSON 找圖片
                    foreach ($jsonData as $item) {
                        if (
                            $item["user_account"] == $row["account_student"] &&
                            $item["description"] == $row["description"] &&
                            $item["time"] == $row["time"] &&
                            $item["store_account"] == $row["account_store"]
                        ) {
                            $images = $item["images"];
                            break;
                        }
                    }

                    if (!empty($images)) {
                        $imgJson = htmlspecialchars(json_encode($images), ENT_QUOTES);
                        $btn = "<button onclick='showImages($imgJson)'>查看</button>";
                    } else {
                        $btn = "";
                    }

                    // 狀態顯示樣式
                    $statusClass = "s1";
                    if ($row['status'] === "處理中") $statusClass = "s2";
                    if ($row['status'] === "已完成") $statusClass = "s3";

                    echo "<tr>
                            <td>{$count}</td>
                            <td>{$row['account_student']}</td>
                            <td>{$row['description']}</td>
                            <td>$btn</td>
                            <td>{$row['time']}</td>
                            <td>{$row['account_store']}</td>
                            <td><span class='status {$statusClass}'>{$row['status']}</span></td>
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
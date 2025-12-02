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

//上傳圖片+新增系統問題
if (isset($_POST['add_report'])) {
    $description = trim($_POST['description']);

    // 設定時區為台北
    date_default_timezone_set('Asia/Taipei');
    $time = date("Y-m-d H:i:s"); // PHP 時間

    // 處理圖片 → base64
    $savedFiles = [];

    if (!empty($_FILES['images']['name'][0])) {
        $count = 1;
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if (!empty($tmpName)) { // 確保檔案存在
                $fileData = file_get_contents($tmpName);
                $base64 = base64_encode($fileData);

                $ext = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);

                $savedFiles[] = "data:image/$ext;base64," . $base64;
            }
        }
    }

    // 2. 新增資料到資料庫
    $link->begin_transaction();
    $stmt = $link->prepare("INSERT INTO `report`(`description`, `time`, `type`, `status`, `account_student`, `account_store`) VALUES (?, CURRENT_TIMESTAMP(), '系統問題', '未處理', ?, NULL)");
    $stmt->bind_param("ss", $description, $account);

    if ($stmt->execute()) {
        $link->commit();
    } else {
        $link->rollback();
        echo "<script>alert('新增失敗: " . $stmt->error . "'); history.back();</script>";
        exit;
    }

    // 3. MongoDB新增（取代JSON）
    $bulk = new MongoDB\Driver\BulkWrite;

    $bulk->insert([
        'user_account' => $account,
        'description'  => $description,
        'images'       => $savedFiles,
        'time'         => $time,
        'status'       => '未處理'
    ]);

    // 寫入 store_db.admin_report
    $manager->executeBulkWrite('store_db.admin_report', $bulk);

    echo "<script>alert('新增系統問題成功！');window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>問題歷史紀錄</title>
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

        .add-box {
            display: flex;
            gap: 20px;
            padding: 12px;
            background: #fafafa;
            border-radius: 12px;
            margin-bottom: 18px;

        }

        .add-box input {
            width: 200px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
        }

        .add-box button {
            width: 100px;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            background: #0066CC;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .add-box button:hover {
            opacity: 0.85;
            transform: scale(1.02);
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
            background: #0066CC;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #D2E9FF;
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

        .search {
            padding: 4px 8px;
            border: none;
            border-radius: 5px;
            font-size: 12px;
            color: white;
            cursor: pointer;
            background-color: #0080FF;
        }

        .search:hover {
            opacity: 0.85;
            transform: scale(1.05);
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
            background: #0066CC;
            border-radius: 8px;
            color: white;
            margin: 10px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php include "student_menu.php"; ?>
    <div class="container">
        <h2>新增系統問題</h2>
        <form method="POST" class="add-box" enctype="multipart/form-data">
            <input type="text" name="description" placeholder="訴求" required>
            <input type="file" name="images[]" id="fileInput" accept="image/*" multiple style="display:none">
            <button type="button" id="fileBtn">選擇圖片</button>
            <button type="submit" name="add_report">新增</button>
        </form>

        <h2>問題歷史紀錄</h2>
        <table>
            <tr>
                <th>流水號</th>
                <th>訴求</th>
                <th>圖片</th>
                <th>時間</th>
                <th>被投訴店家</th>
                <th>狀態</th>
            </tr>

            <?php
            $sql = "SELECT `description`, `time`, `account_store`, `type`, `status`
                FROM `report`
                WHERE `account_student`='$account'
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

                    // 比對 投訴店家JSON 找圖片
                    $filter1 = [
                        "description"   => $row["description"],
                        "time"          => $row["time"],
                        "store_account" => $row["account_store"]
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

                    // 比對 系統問題JSON 找圖片
                    $filter2 = [
                        "user_account" => $account,
                        "description"  => $row["description"],
                        "time"         => $row["time"]
                    ];

                    $query2 = new MongoDB\Driver\Query($filter2);
                    $cursor2 = $manager->executeQuery("store_db.admin_report", $query2);

                    foreach ($cursor2 as $doc) {
                        if (isset($doc->images)) {
                            foreach ($doc->images as $img) {
                                $images[] = $img;
                            }
                        }
                    }

                    if (!empty($images)) {
                        $imgJson = htmlspecialchars(json_encode($images), ENT_QUOTES);
                        $btn = "<button class='search' onclick='showImages($imgJson)'>查看</button>";
                    } else {
                        $btn = "";
                    }

                    // 狀態顯示樣式
                    $statusClass = "s1";
                    if ($row['status'] === "處理中") $statusClass = "s2";
                    if ($row['status'] === "已完成") $statusClass = "s3";

                    echo "<tr>
                            <td>{$count}</td>
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

        function showImages(imgJson) {
            images = imgJson; // ★★★ 接收 PHP 傳來的圖 ★★★
            index = 0;

            if (images.length === 0) {
                alert("無圖片可顯示");
                return;
            }

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

        //新增上傳圖片
        const fileInput = document.getElementById('fileInput');
        const fileBtn = document.getElementById('fileBtn');
        fileBtn.addEventListener('click', () => fileInput.click());
    </script>
</body>

</html>
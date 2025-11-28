<?php
session_start();
include "../db.php";  // 引入資料庫連線

$account = $_SESSION['user'] ?? "";

if (!$account) {
    echo "<script>alert('未登入'); window.location='login.html';</script>";
    exit;
}

// 讀取 投訴店家 store_report.json
$jsonPath = "../JSON/store_report.json";
$jsonData = json_decode(file_get_contents($jsonPath), true);

// 讀取 系統問題 admin_report.json
$jsonPath1 = "../JSON/admin_report.json";
$jsonData1 = json_decode(file_get_contents($jsonPath1), true);

//上傳圖片+新增系統問題
if (isset($_POST['add_report'])) {
    $description = trim($_POST['description']);

    // 設定時區為台北
    date_default_timezone_set('Asia/Taipei');
    $time = date("Y-m-d H:i:s"); // PHP 時間

    $date = date('Ymd'); // 當天日期格式

    // 1. 上傳圖片
    $uploadDir = "../picture/report/admin/"; // 存檔資料夾
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $savedFiles = [];
    if (!empty($_FILES['images']['name'][0])) {
        $count = 1;
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            $ext = pathinfo($_FILES['images']['name'][$index], PATHINFO_EXTENSION);
            $newName = sprintf("%s_%s_%02d.%s", $date, $account, $count, $ext);
            $destination = $uploadDir . $newName;

            if (move_uploaded_file($tmpName, $destination)) {
                $savedFiles[] = "../picture/report/admin/" . $newName; // 使用正常斜線
            } else {
                error_log("上傳檔案失敗: " . $_FILES['images']['name'][$index]);
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

    // 3. 新增資料到 JSON
    $jsonPath = "../JSON/admin_report.json";
    $jsonData = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];

    $nextId = !empty($jsonData) ? max(array_column($jsonData, 'report_id')) + 1 : 1;

    $jsonData[] = [
        'report_id' => $nextId,
        'user_account' => $account,
        'description' => $description,
        'images' => $savedFiles,   // 確認 $savedFiles 有值
        'time' => $time,
        'status' => '未處理'
    ];

    file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));


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
        :root {
            --main-green: #4caf50;
            --dark-green: #388e3c;
            --main-brown: #C19A6B;
            --dark-brown: #5C3D2E;
            --blue: #1e88e5;
            --purple: #8e24aa;
            --orange: #fb8c00;
            --gray: #6c757d;
        }

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
            background: var(--main-brown);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .add-box button:hover {
            background: var(--dark-brown);
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

            while ($row = $result->fetch_assoc()) {
                $images = [];

                // 比對 投訴店家JSON 找圖片
                foreach ($jsonData as $item) {
                    if (
                        $item["description"] == $row["description"] &&
                        $item["time"] == $row["time"] &&
                        $item["store_account"] == $row["account_store"]
                    ) {
                        $images = $item["images"];
                        break;
                    }
                }

                // 比對 系統問題JSON 找圖片
                foreach ($jsonData1 as $item) {
                    if (
                        $item["user_account"] == $account &&
                        $item["description"] == $row["description"] &&
                        $item["time"] == $row["time"]
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

                echo "
            <tr>
                <td>{$count}</td>
                <td>{$row['description']}</td>
                <td>$btn</td>
                <td>{$row['time']}</td>
                <td>{$row['account_store']}</td>
                <td><span class='status {$statusClass}'>{$row['status']}</span></td>
            </tr>";
                $count++;
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

        //新增上傳圖片
        const fileInput = document.getElementById('fileInput');
        const fileBtn = document.getElementById('fileBtn');
        fileBtn.addEventListener('click', () => fileInput.click());
    </script>
</body>

</html>
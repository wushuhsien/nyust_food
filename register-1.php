<?php
session_start();
include "db.php";  // 這裡已經會產生 $storeTypes 陣列

// 取得上一頁帳密與角色
$username   = $_SESSION['reg_username'] ?? '';
$password   = $_SESSION['reg_password'] ?? '';
$permission = $_SESSION['reg_permission'] ?? '';

if (!$username || !$password || $permission === '') {
    echo "<script>alert('請先完成第一步註冊！'); location.href='register.php';</script>";
    exit;
}

// 建立資料庫
if (isset($_POST['action']) && $_POST['action'] == 'create') {

    //新增account帳密資料表
    $sql = "INSERT INTO account(account, password, created_time, permission, stop_reason)
            VALUES (?, ?, CURRENT_TIMESTAMP(), ?, NULL)";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("ssi", $username, $password, $permission);

    if (!$stmt->execute()) {
        echo "<script>alert('建立 account 失敗');</script>";
        exit;
    }


    //如果是學生/教職員（permission=0），新增student學生/教職員資料表
    if ($permission == 0) {

        // 取得 student 最大流水號
        $result = $link->query("SELECT MAX(student_id) AS maxid FROM student");
        $row    = $result->fetch_assoc();
        $nextId = ($row['maxid'] === NULL ? 1 : $row['maxid'] + 1);

        $stu_name  = $_POST['stu_name'];
        $stu_nick  = $_POST['stu_nick'];
        $stu_phone = $_POST['stu_phone'];
        $stu_email = $_POST['stu_email'];;
        $payment   = NULL;
        $notice    = NULL;
        $acc       = $username;

        $sql2 = "INSERT INTO student(student_id, name, nickname, phone, email, payment, notice, account)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt2 = $link->prepare($sql2);
        $stmt2->bind_param(
            "isssssss",
            $nextId,
            $stu_name,
            $stu_nick,
            $stu_phone,
            $stu_email,
            $payment,
            $notice,
            $acc
        );

        if ($stmt2->execute()) {
            session_destroy();
            echo "<script>alert('學生/教職員註冊成功！'); location.href='login.html';</script>";
            exit;
        } else {
            echo "學生資料寫入失敗";
            exit;
        }
    }

    //如果是店家（permission=1），新增store店家資料表
    if ($permission == 1) {

        //取得store最大流水號
        $result = $link->query("SELECT MAX(store_id) AS maxid FROM store");
        $row    = $result->fetch_assoc();
        $nextId = ($row['maxid'] === NULL ? 1 : $row['maxid'] + 1);

        $name    = $_POST['store_name'];
        $desc    = $_POST['store_desc'];
        $address = $_POST['store_address'];
        $phone   = $_POST['store_phone'];
        $email   = $_POST['store_email'];
        $storetypeName = $_POST['store_type'];

        // 用name找storetype_id
        $stmtType = $link->prepare("SELECT storetype_id FROM storetype WHERE name=?");
        $stmtType->bind_param("s", $storetypeName);
        $stmtType->execute();
        $typeResult = $stmtType->get_result();

        if ($typeResult->num_rows == 0) {
            echo "<script>alert('找不到店家類型！');</script>";
            exit;
        }
        $storetype_id = $typeResult->fetch_assoc()['storetype_id'];

        $sql2 = "INSERT INTO store(store_id, name, description, address, phone, email, storetype_id, account)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt2 = $link->prepare($sql2);
        $stmt2->bind_param(
            "isssssis",
            $nextId,
            $name,
            $desc,
            $address,
            $phone,
            $email,
            $storetype_id,
            $username
        );

        if ($stmt2->execute()) {
            // 插入 storehours
            if (!empty($_POST['open_time']) && !empty($_POST['close_time'])) {
                foreach ($_POST['open_time'] as $weekday => $opens) {
                    $closes = $_POST['close_time'][$weekday];
                    for ($i = 0; $i < count($opens); $i++) {
                        $open  = $opens[$i];
                        $close = $closes[$i];

                        $stmtHour = $link->prepare("INSERT INTO storehours(weekday, open_time, close_time, account) VALUES (?, ?, ?, ?)");
                        $stmtHour->bind_param("isss", $weekday, $open, $close, $username);
                        $stmtHour->execute();
                    }
                }
            }

            session_destroy();
            echo "<script>alert('店家註冊成功！'); location.href='login.html';</script>";
            exit;
        } else {
            echo "店家資料寫入失敗";
            exit;
        }
    }

    // 註冊成功
    if ($success) {
        session_destroy();
        echo "<script>alert('註冊成功！'); window.location='login.html';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>註冊資料</title>

    <style>
        body {
            font-family: "Microsoft JhengHei", sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            height: 100vh;
        }

        .container {
            background: white;
            width: 720px;
            padding: 35px 45px;
            /* padding-top: 60px; */
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .system-title {
            font-size: 26px;
            font-weight: bold;
            color: #2d6cdf;
            margin-bottom: 20px;
        }

        h2 {
            margin-top: 0;
            margin-bottom: 25px;
            color: #333;
        }

        h3 {
            color: #2d6cdf;
            margin-bottom: 15px;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 90%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        button {
            margin-top: 20px;
            padding: 10px;
            width: 95%;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            color: white;
            background-color: #2d6cdf;
        }

        button:hover {
            background-color: #1f53b6;
        }

        .form-box {
            padding: 20px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fafafa;
            text-align: left;
            display: none;
        }

        .form-box label {
            font-weight: bold;
        }

        span.required {
            color: red;
        }

        .two-col {
            display: flex;
            gap: 40px;
        }

        .left-col,
        .right-col {
            flex: 1;
        }

        label {
            white-space: nowrap;
        }

        .day-block {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            width: 300px;
            background: #f9f9f9;
        }

        .time-range {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 5px;
        }

        .add-btn {
            margin-top: 5px;
            padding: 4px 8px;
            font-size: 12px;
            cursor: pointer;
            background-color: #66B3FF;
            color: white;
            border: none;
            border-radius: 4px;
        }

        .del-btn {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .del-btn:hover {
            background-color: #f90000ff;
        }

        #apply-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            width: 200px;
        }

        #apply-btn:hover {
            background-color: #45a049;
        }
    </style>

    <script>
        function showForm() {
            var permission = "<?php echo $permission; ?>";
            document.getElementById("storeForm").style.display = (permission == "1") ? "block" : "none";
            document.getElementById("studentForm").style.display = (permission == "0") ? "block" : "none";
        }
    </script>
</head>

<body onload="showForm()">

    <div class="container">
        <h2>填寫資料</h2>

        <!-- 店家表單 -->
        <form method="POST" action="" id="storeForm" class="form-box" onsubmit="return validateBusinessHours()">
            <h3>店家註冊</h3>
            <div class="two-col">
                <!-- 左欄 -->
                <div class="left-col">
                    <label>店家類型：<span class="required">*</span></label><br>
                    <select name="store_type" required>
                        <option value="">請選擇</option>
                        <?php foreach ($storeTypes as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label><span class="required">*</span>店家名稱：</label><br>
                    <input type="text" name="store_name" required><br><br>

                    <label>描述：</label><br>
                    <input type="text" name="store_desc"><br><br>

                    <label><span class="required">*</span>地址：</label><br>
                    <input type="text" name="store_address" required><br><br>

                    <label><span class="required">*</span>電話：</label><br>
                    <input type="text" name="store_phone"
                        required
                        pattern="(09\d{8}|0\d{1,3}\d{5,8})"
                        title="請輸入手機（0912345678）或市話（例如0212345678）">
                    <br><br>

                    <label><span class="required">*</span>電子郵件：</label><br>
                    <input type="email" name="store_email" required><br><br>
                </div>
                <!-- 右欄 -->
                <div class="right-col">
                    <label><span class="required">*</span>營業時間：<button type="button" id="apply-btn">套用到其他天</button></label><br>
                    <div id="business-hours"></div>
                </div>
                <script>
                    // 生成星期選項與時間區塊
                    const days = ["星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"];
                    const container = document.getElementById("business-hours");

                    days.forEach((day, index) => {
                        const weekday = index + 1;
                        const block = document.createElement("div");
                        block.className = "day-block";
                        block.innerHTML = `
        <label>
            <input type="checkbox" onchange="toggleDay(${weekday})">
            <strong>${day}</strong>
        </label>
        <div id="time-box-${weekday}" style="display:none; margin-top:10px;">
            <div id="ranges-${weekday}"></div>
            <button type="button" class="add-btn" onclick="addRange(${weekday})">+新增時段</button>
        </div>`;
                        container.appendChild(block);
                    });

                    // 顯示/隱藏時間區塊
                    function toggleDay(weekday) {
                        const box = document.getElementById("time-box-" + weekday);
                        const checkbox = box.parentNode.querySelector("input[type='checkbox']");
                        box.style.display = checkbox.checked ? "block" : "none";
                    }

                    // 新增營業時段
                    function addRange(weekday, openValue = '', closeValue = '') {
                        if (openValue && closeValue && closeValue < openValue) {
                            let [h, m] = closeValue.split(":").map(Number);
                            h = (h + 12) % 24; // 自動往後推 12 小時
                            closeValue = h.toString().padStart(2, "0") + ":" + m.toString().padStart(2,"0");
                        }
                        
                        const rangeBox = document.getElementById("ranges-" + weekday);
                        const div = document.createElement("div");
                        div.className = "time-range";

                        // 如果已經有預設值，做自動判斷
                        if (openValue && closeValue && closeValue < openValue) {
                            let [h, m] = closeValue.split(":").map(Number);

                            // 判斷是否為 12 小時制跨日錯誤
                            if (h < 12) {
                                h += 12; // 自動轉下午
                            }

                            closeValue = h.toString().padStart(2, "0") + ":" + m.toString().padStart(2, "0");
                        }

                        div.innerHTML = `
                            <input type="time" name="open_time[${weekday}][]" value="${openValue}">
                            <span> - </span>
                            <input type="time" name="close_time[${weekday}][]" value="${closeValue}">
                            <button type="button" class="del-btn" onclick="this.parentElement.remove()">-刪除</button>
                        `;
                        rangeBox.appendChild(div);
                    }

                    // 套用到其他勾選天
                    document.getElementById("apply-btn").addEventListener("click", () => {
                        let sourceWeekday = null;
                        for (let i = 1; i <= 7; i++) {
                            const checkbox = document.querySelector(`#time-box-${i}`).parentNode.querySelector('input[type="checkbox"]');
                            if (checkbox.checked) {
                                sourceWeekday = i;
                                break;
                            }
                        }
                        if (!sourceWeekday) {
                            alert("請先勾選一個星期！");
                            return;
                        }

                        const sourceRanges = Array.from(document.querySelectorAll(`#ranges-${sourceWeekday} .time-range`)).map(div => {
                            const inputs = div.querySelectorAll("input");
                            return {
                                open: inputs[0].value,
                                close: inputs[1].value
                            };
                        });

                        for (let i = 1; i <= 7; i++) {
                            if (i === sourceWeekday) continue;
                            const checkbox = document.querySelector(`#time-box-${i}`).parentNode.querySelector('input[type="checkbox"]');
                            if (checkbox.checked) {
                                const rangeBox = document.getElementById(`ranges-${i}`);
                                rangeBox.innerHTML = '';
                                sourceRanges.forEach(r => addRange(i, r.open, r.close));
                            }
                        }
                    });
                </script>
            </div>
            <button type="submit" name="action" value="create">建立</button>
        </form>

        <!-- 學生表單 -->
        <form method="POST" action="" id="studentForm" class="form-box">
            <h3>學生 / 教職員 註冊</h3>
            <label>姓名：</label><br>
            <input type="text" name="stu_name" required><br><br>

            <label>暱稱：</label><br>
            <input type="text" name="stu_nick" required><br><br>
            <label><span class="required">*</span>電話：</label><br>
            <input type="text" name="stu_phone"
                required
                pattern="(09\d{8}|0\d{1,3}\d{5,8})"
                title="請輸入手機（0912345678）或市話（例如0212345678）">
            <br><br>

            <label>電子郵件：</label><br>
            <input type="email" name="stu_email" required><br><br>

            <button type="submit" name="action" value="create">建立</button>
        </form>
    </div>

    <script>
        function showForm() {
            const permission = "<?php echo $permission; ?>";
            if (permission == "1") {
                document.getElementById("storeForm").style.display = "block";
                document.getElementById("studentForm").style.display = "none";
            } else {
                document.getElementById("storeForm").style.display = "none";
                document.getElementById("studentForm").style.display = "block";
            }
        }

        // 店家營業時間驗證
        function validateBusinessHours() {
            let valid = false;
            let hasChecked = false;

            for (let i = 1; i <= 7; i++) {
                const checkbox = document.querySelector(`#time-box-${i}`).parentNode.querySelector('input[type="checkbox"]');
                if (checkbox.checked) {
                    hasChecked = true;
                    const ranges = document.querySelectorAll(`#ranges-${i} .time-range`);
                    if (ranges.length === 0) continue;

                    let allFilled = true;
                    ranges.forEach(div => {
                        const inputs = div.querySelectorAll("input[type='time']");
                        const open = inputs[0].value;
                        const close = inputs[1].value;
                        if (!open || !close) allFilled = false;
                    });

                    if (allFilled) valid = true;
                }
            }

            if (!hasChecked) {
                alert("請至少勾選一個星期！");
                return false;
            }

            if (!valid) {
                alert("請至少填寫完整營業時間！");
                return false;
            }

            return true;
        }
    </script>

</body>


</html>
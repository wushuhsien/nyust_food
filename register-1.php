<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
        $stmt2->bind_param("isssssss",
            $nextId, $stu_name, $stu_nick, $stu_phone,
            $stu_email, $payment, $notice, $acc
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
        $stmt2->bind_param("isssssis",
            $nextId, $name, $desc, $address, $phone, $email, $storetype_id, $username
        );

        if ($stmt2->execute()) {
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
<html>
<head>
    <meta charset="UTF-8">
    <title>第二步註冊</title>
    <script>
        function showForm() {
            var permission = "<?php echo $permission; ?>";
            document.getElementById("storeForm").style.display = (permission == "1") ? "block" : "none";
            document.getElementById("studentForm").style.display = (permission == "0") ? "block" : "none";
        }
    </script>
</head>
<body onload="showForm()">

    <h2>第二步 - 填寫資料</h2>
    <form method="POST" action="">

        <!-- 店家表單 -->
        <div id="storeForm" style="display:none; border:1px solid #999; padding:10px; width:300px;">
            <h3>店家註冊</h3>
            店家類型：
            <select name="store_type" >
                <option value="">請選擇</option>
                <?php foreach($storeTypes as $type): ?>
                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select><br><br>
            <span style="color:red">*</span>店家名稱：<input type="text" name="store_name" ><br><br>
            描述：<input type="text" name="store_desc"><br><br>
            <span style="color:red">*</span>地址：<input type="text" name="store_address" ><br><br>
            <span style="color:red">*</span>電話：<input type="text" name="store_phone" ><br><br>
            <span style="color:red">*</span>電子郵件：<input type="email" name="store_email" ><br><br>
        </div>

        <!-- 學生 / 教職員表單 -->
        <div id="studentForm" style="display:none; border:1px solid #999; padding:10px; width:300px;">
            <h3>學生 / 教職員 註冊</h3>
            姓名：<input type="text" name="stu_name"><br><br>
            暱稱：<input type="text" name="stu_nick"><br><br>
            電話：<input type="text" name="stu_phone"><br><br>
            電子郵件：<input type="email" name="stu_email"><br><br>
        </div>

        <br>
        <button type="submit" name="action" value="create">建立</button>
    </form>

</body>
</html>

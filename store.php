<?php
session_start(); 
include "db.php"; 
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>學生首頁</title>
<!-- 引入 Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    #a { /* 頂部藍色欄 */
        background-color: #66B3FF;
        height: 50px;
        text-align: center;
        line-height: 50px; /* 垂直置中 */
        color: white;
		position: relative;
    }

    #b {
        background-color: #F0F0F0;
        height: 300px; /* 可增加高度顯示更多文字 */
        margin: 10px 20px;
        padding: 10px;
        overflow-y: auto; 
        text-align: left;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    #b h1 {
        font-size: 20px;
        margin-top: 0;
    }

    input[type="text"] {
        width: 90%;
        padding: 5px;
        font-size: 16px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .logout-btn {
        margin-top: 10px;
        padding: 8px 16px;
        font-size: 16px;
        border: none;
        background-color: #FF4C4C;
        color: white;
        border-radius: 4px;
        cursor: pointer;
    }

    .logout-btn:hover {
        background-color: #CC0000;
    }
	.announcement {
		background-color: #ffffff;
		border: 1px solid #ccc;
		border-radius: 6px;
		padding: 10px 15px;
		margin-bottom: 15px; /* 每個公告之間的距離 */
		box-shadow: 1px 1px 5px rgba(0,0,0,0.1);
	}
	.announcement p {
		margin: 5px 0; /* 每行文字間距 */
		line-height: 1.5;
	}

	/*帳號*/
	#top-right-box {
		position: absolute;
		top: 0;
		right: 10px;
		height: 50px; /* 和 #a 一樣高 */
		display: flex;
		align-items: center; /* 垂直置中 */
		gap: 10px;          /* 帳號與齒輪的間距 */
	}

	.user-account {
		color: white;
		font-size: 16px;
		font-weight: bold;
	}

  /* 右上角齒輪按鈕容器 */
    .dropdown {
        position: relative;
    }

    .dropbtn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }

    .dropbtn i {
        font-size: 24px;
        color: white;
    }

    /* 下拉選單 */
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #f9f9f9;
        min-width: 120px;
        box-shadow: 0px 4px 8px rgba(0,0,0,0.2);
        border-radius: 5px;
        z-index: 1;
    }

    .dropdown-content input[type="button"] {
        width: 100%;
        padding: 8px 10px;
        border: none;
        background-color: #fff;
        text-align: left;
        cursor: pointer;
        border-bottom: 1px solid #ddd;
        font-size: 14px;
    }

    .dropdown-content input[type="button"]:hover {
        background-color: #f1f1f1;
    }

    /* 最後一個按鈕去掉底線 */
    .dropdown-content input[type="button"]:last-child {
        border-bottom: none;
    }

	.sub-dropdown {
		display: none;
		background-color: #ffffff;
		border-left: 3px solid #66B3FF;
	}

	.sub-dropdown input[type="button"] {
		padding-left: 20px; /* 子選單縮排 */
	}
</style>
</head>
<body>
    <div id="a">
		<?php
			// 顯示帳號
			$account = $_SESSION['user'] ?? "未登入";
		?>
		<h1>店家首頁</h1>
		<div id="top-right-box">
			<div class="user-account">  <!--帳號-->
				<?php echo htmlspecialchars($account); ?>
			</div>
			<div class="dropdown">
				<button class="dropbtn" onclick="toggleDropdown()">
					<i class="bi bi-gear"></i>
				</button>
				<div id="myDropdown" class="dropdown-content">
					<input type="button" value="店家設定 ▼" onclick="toggleSubMenu1()">
					<div id="subMenu1" class="sub-dropdown">
						<input type="button" value="基本資料" onclick="window.location='store_information.php'">
					</div>
					<input type="button" value="店家管理 ▼" onclick="toggleSubMenu2()">
					<div id="subMenu2" class="sub-dropdown">
						<input type="button" value="店家資料" onclick="alert('店家資料')">
						<input type="button" value="菜單管理" onclick="alert('菜單管理')">
						<input type="button" value="歷史訂單" onclick="alert('歷史訂單')">
						<input type="button" value="評價紀錄" onclick="alert('評價紀錄')">
					</div>
					<input type="button" value="問題" onclick="alert('問題按鈕')">
					<input type="button" value="登出" onclick="window.location='login.html'">
				</div>
			</div>
		</div>
	</div>

    <div id="b">
        <h1>公告</h1>
			<?php
			// 查詢店休或公告
			$sql = "SELECT `announcement_id`, `topic`, `description`, `start_time`, `end_time`, `type`, `account`
					FROM `announcement`
					WHERE `type`='店休' OR `type`='公告'
					ORDER BY `start_time` DESC";

			$result = $link->query($sql);

			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					echo '<div class="announcement">';
					echo '<p><strong>主題：</strong>' . htmlspecialchars($row['topic']) . '</p>';
					echo '<p><strong>內容：</strong>' . nl2br(htmlspecialchars($row['description'])) . '</p>';
					echo '<p><strong>時間：</strong>' . htmlspecialchars($row['start_time']) . ' ~ ' . htmlspecialchars($row['end_time']) . '</p>';
					echo '</div>';
				}
			} else {
				echo "<p>目前沒有公告。</p>";
			}
		?>
	</div>

	<script>
		function toggleSubMenu1() {
			var sub = document.getElementById("subMenu1");
			sub.style.display = (sub.style.display === "block") ? "none" : "block";
		}

		function toggleSubMenu2() {
			var sub = document.getElementById("subMenu2");
			sub.style.display = (sub.style.display === "block") ? "none" : "block";
		}

		// 切換下拉選單
		function toggleDropdown() {
			var dropdown = document.getElementById("myDropdown");
			dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
		}

		// 點擊頁面其他地方，關閉下拉選單
		window.onclick = function(event) {
			if (!event.target.closest('.dropdown')) {
				var dropdowns = document.getElementsByClassName("dropdown-content");
				for (var i = 0; i < dropdowns.length; i++) {
					dropdowns[i].style.display = "none";
				}
			}
		}
	</script>
</body>
</html>

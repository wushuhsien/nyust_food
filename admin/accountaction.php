<?php
session_start();
include "../db.php";  // 引入資料庫連線
$log_account = isset($_GET['account']) ? $_GET['account'] : '';
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10; // 預設每頁顯示 10 筆
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>學生/教職員操作日誌</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --main-brown: #C19A6B;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
        }

        .container {
            width: 40%;
            margin: 20px auto 0 auto;
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #eee;
        }

        thead {
            background: var(--main-brown);
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
            text-align: center;
        }

        tbody tr:hover {
            background: #fae6c0;
        }

        .action-bar {
            width: 95%;
            margin: 0 auto 12px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* text-align: right; */
        }

        .search-btn {
            padding: 6px 12px;
            background: #1e88e5;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }

        .search-btn:hover {
            opacity: 0.85;
            transform: scale(1.05);
        }

        .per-page-select {
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include "admin_menu.php"; ?>
    <div class="container">
        <h2><?= htmlspecialchars($log_account) ?>操作日誌</h2>
        <!-- 查詢按鈕，放在 container 外面 -->
        <div class="action-bar">
            <div class="btn-group">
                <button type="button" class="search-btn" onclick="history.back()">返回</button>
                <button type="button" class="search-btn" style="margin-left:10px">圖表</button>
            </div>
            <!-- 每頁筆數選單 -->
            <form method="get" style="margin:0">
                <input type="hidden" name="account" value="<?= htmlspecialchars($log_account) ?>">
                <label for="per_page">每頁顯示：</label>
                <select name="per_page" id="per_page" class="per-page-select" onchange="this.form.submit()">
                    <?php
                    $options = [5, 10, 20, 50, 100];
                    foreach ($options as $opt) {
                        $selected = ($opt == $perPage) ? 'selected' : '';
                        echo "<option value='$opt' $selected>$opt 筆</option>";
                    }
                    ?>
                </select>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>時間</th>
                    <th>動作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($log_account)) {
                    $perPage = intval($perPage); // 確保是整數，避免 SQL 注入
                    $sql = "SELECT `time`, `action` FROM `accountaction` WHERE `account`=? ORDER BY `time` ASC LIMIT $perPage";
                    $stmt = $link->prepare($sql);
                    $stmt->bind_param("s", $log_account);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $i = 1;
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $actionText = $row['action'] === 'IN' ? '登入' : ($row['action'] === 'OUT' ? '登出' : $row['action']);

                            echo "<tr>
                                    <td>{$i}</td>
                                    <td>{$row['time']}</td>
                                    <td>{$actionText}</td>
                                  </tr>";
                            $i++;
                        }
                    } else {
                        echo "<tr><td colspan='3' style='color:#888'>" . htmlspecialchars($log_account) . "無操作紀錄</td></tr>";
                    }
                    $stmt->close();
                } else {
                    echo "<tr><td colspan='3' style='color:#888'>請選擇帳號</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- 圖表彈窗 -->
    <div id="chartModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000;">

        <div style="background:white; padding:20px; border-radius:12px; width:720px; position:relative;">
            <span id="closeChart" style="position:absolute; top:10px; right:15px; cursor:pointer; font-weight:bold;">✖</span>

            <div style="display:flex;">

                <!-- 左側年份選單 -->
                <div style="width:180px; padding-right:20px; border-right:1px solid #ddd;">
                    <h3>選擇年份</h3>
                    <select id="yearSelect" style="width:150px; padding:6px; border-radius:6px;"></select>
                </div>

                <!-- 右側圖表 -->
                <div style="flex:1; padding-left:20px;">
                    <canvas id="logChart" width="500" height="350"></canvas>
                </div>

            </div>
        </div>
    </div>

    <script>
        let modal = document.getElementById("chartModal");
        let closeBtn = document.getElementById("closeChart");
        let yearSelect = document.getElementById("yearSelect");

        // 點擊「圖表」按鈕
        document.querySelector(".search-btn:nth-child(2)").addEventListener("click", () => {
            modal.style.display = "flex";
            loadYearsAndBuildChart();
        });

        // 關閉彈窗
        closeBtn.onclick = () => modal.style.display = "none";

        // 從表格資料抓年月與登入/登出
        function parseTableData() {
            let logs = [];
            document.querySelectorAll("tbody tr").forEach(tr => {
                let tds = tr.querySelectorAll("td");
                if (tds.length < 3) return;

                let time = tds[1].textContent.trim();
                let action = tds[2].textContent.trim();

                if (time.includes("無操作紀錄") || time.includes("請選擇帳號")) return;

                let year = time.substring(0, 4);
                let month = parseInt(time.substring(5, 7)); // 01 → 1

                logs.push({
                    year,
                    month,
                    action
                });
            });
            return logs;
        }

        // 填入年份下拉 + 建立圖表
        function loadYearsAndBuildChart() {
            let logs = parseTableData();

            let years = [...new Set(logs.map(x => x.year))]; // 去重
            years.sort().reverse();

            yearSelect.innerHTML = years.map(y => `<option value="${y}">${y}</option>`).join("");

            buildChart(yearSelect.value);

            yearSelect.onchange = () => buildChart(yearSelect.value);
        }

        let chartInstance = null;

        // 生成圖表
        function buildChart(selectedYear) {
            let logs = parseTableData();

            let loginCount = new Array(12).fill(0);
            let logoutCount = new Array(12).fill(0);

            logs.forEach(l => {
                if (l.year === selectedYear) {
                    if (l.action === "登入") loginCount[l.month - 1]++;
                    if (l.action === "登出") logoutCount[l.month - 1]++;
                }
            });

            let ctx = document.getElementById("logChart").getContext("2d");
            if (chartInstance) chartInstance.destroy();

            chartInstance = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
                    datasets: [{
                            label: "登入次數",
                            data: loginCount,
                            backgroundColor: "#4caf50"
                        },
                        {
                            label: "登出次數",
                            data: logoutCount,
                            backgroundColor: "#f44336"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
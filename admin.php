<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>管理員後台</title>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background-color: #F2F4F6;
            /* 科技灰 */
        }

        /* 頂部選單 */
        .top-menu {
            background-color: #004B97;
            /* 雲科藍 */
            display: flex;
            align-items: center;
            padding: 0 30px;
            height: 70px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .top-menu h1 {
            color: #ffffff;
            font-size: 22px;
            margin: 0;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* 按鈕置右 */
        .menu-items {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        /* 主按鈕 */
        .menu-item {
            position: relative;
            padding: 14px 18px;
            color: #ffffff;
            cursor: pointer;
            border-radius: 8px;
            margin-left: 15px;
            transition: all 0.25s ease;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(3px);
        }

        .menu-item:hover {
            background: #1E90FF;
            /* 點綴亮藍 */
            box-shadow: 0 4px 10px rgba(30, 144, 255, 0.4);
        }

        /* 下拉選單 */
        .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 220px;
            background-color: #003A75;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .dropdown a {
            display: block;
            padding: 12px 20px;
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;

            /* << 新增：分隔線 >> */
            border-bottom: 1px solid rgba(255, 255, 255, 1);

            transition: background 0.2s;
        }

        .dropdown a:last-child {
            border-bottom: none;
        }

        .dropdown a:hover {
            background-color: #1E90FF;
        }


        .menu-item:hover .dropdown {
            display: block;
        }
    </style>
</head>

<body>

    <div class="top-menu">
        <h1>管理員後台</h1>

        <div class="menu-items">

            <div class="menu-item">使用者資料管理
                <div class="dropdown">
                    <a href="#">所有學生/教職員資料</a>
                    <a href="#">所有店家資料</a>
                    <a href="#">管理帳號</a>
                </div>
            </div>

            <div class="menu-item">店家菜單管理</div>
            <div class="menu-item">評價管理</div>
            <div class="menu-item">公告管理</div>
            <div class="menu-item">問題管理</div>

            <div class="menu-item">統計圖、報表管理
                <div class="dropdown">
                    <a href="#">店家歷史訂單</a>
                    <a href="#">使用者歷史訂單</a>
                    <a href="#">店家財務分析報表</a>
                </div>
            </div>

            <div class="menu-item">日誌管理</div>
            <div class="menu-item" onclick="window.location='login.html'">登出</div>
        </div>
    </div>

</body>

</html>
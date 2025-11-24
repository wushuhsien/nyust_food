<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>管理員首頁</title>
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f4f4;
    }

    /* 頂部選單列 */
    .top-menu {
        background-color: #f28989; /* 主色：珊瑚粉 */
        display: flex;
        align-items: center;
        padding: 0 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        height: 70px;
    }

    /* 標題 */
    .top-menu h1 {
        color: #ffffff;
        font-size: 24px;
        margin: 0;
        margin-right: 50px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }

    /* 標題長方形 */
    .menu-item {
        position: relative;
        padding: 15px 20px;
        color: #ffffff;
        cursor: pointer;
        text-decoration: none;
        border-radius: 5px;
        margin-right: 15px;
        transition: all 0.3s ease;
        background: linear-gradient(145deg, #f28989, #f2b8b8); /* 漸層 */
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .menu-item:hover {
        background: linear-gradient(145deg, #f25e5e, #f28989);
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    }

    /* 下拉選單 */
    .dropdown {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #f25e5e;
        min-width: 200px;
        border-radius: 0 0 5px 5px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 1000;
    }

    .dropdown a {
        display: block;
        padding: 12px 20px;
        color: #ffffff;
        text-decoration: none;
        transition: background-color 0.2s;
    }

    .dropdown a:hover {
        background-color: #f2b8b8;
        color: #333;
    }

    /* 滑鼠碰到顯示下拉選單 */
    .menu-item:hover .dropdown {
        display: block;
    }

    /* 調整頂部選單排列 */
    .menu-item:first-child {
        margin-left: auto; /* 讓第一個項目靠右，標題靠左 */
    }

    /* 響應式調整 (小螢幕) */
    @media screen and (max-width: 900px) {
        .top-menu {
            flex-wrap: wrap;
            height: auto;
            padding: 10px 20px;
        }
        .menu-item {
            margin: 5px 10px;
        }
    }
</style>
</head>
<body>

<div class="top-menu">
    <h1>管理員首頁</h1>

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
</div>

</body>
</html>

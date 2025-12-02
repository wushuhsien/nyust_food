<?php
session_start();
include "../db.php";

$loginAccount = $_SESSION['user'] ?? ''; // 目前登入帳號

// 如果未登入，視情況處理 (例如跳轉回登入頁)
if (!$loginAccount) {
    echo "<script>alert('請先登入！'); window.location.href='../login.php';</script>";
    exit;
}

// --- 核心邏輯：利用 account 判斷並撈取該店家的菜單 ---
$menuData = []; // 用來存分組後的資料

// 準備 SQL：只撈取該帳號 (account) 的資料，並依系列 (type) 排序
$sql = "SELECT * FROM menu WHERE account = ? ORDER BY type DESC";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $loginAccount); // 綁定帳號參數
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // 將資料以 type (系列名稱) 為 key 存入陣列，實現自動分組
    $menuData[$row['type']][] = $row;
}
$stmt->close();
// ----------------------------------------------------


?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>店家菜單管理</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        #b {
            background-color: #fff7f0;
            /* 淺橘色卡片背景 */
            margin: 20px auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 780px;
            text-align: left;
            border: 1px solid #f0d4b2;
            /* 卡片邊框橘色系 */
        }

        #b h1 {
            font-size: 24px;
            margin-top: 0;
            color: #b35c00;
            /* 主標題橘色 */
        }

        input {
            padding: 8px 10px;
            border: 1px solid #f2c79e;
            /* 橘色邊框 */
            border-radius: 8px;
        }

        button {
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .announcement {
            background-color: #fff3e6;
            /* 淡橘色卡片 */
            border: 1px solid #f2c79e;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 16px;
            position: relative;
        }

        .announcement p {
            margin: 6px 0;
            line-height: 1.5;
            color: #4b2500;
            /* 深橘色文字 */
        }

        .announcement .btn-area {
            position: absolute;
            right: 14px;
            top: 14px;
            display: flex;
            gap: 8px;
        }

        .edit-btn {
            padding: 5px 10px;
            font-size: 12px;
            background-color: #f28c28;
            /* 橘色按鈕 */
            color: white;
        }

        .edit-btn:hover {
            background-color: #d97706;
            /* 深橘色 hover */
        }

        .delete-btn {
            padding: 5px 10px;
            font-size: 12px;
            background-color: #dc2626;
            /* 保留紅色刪除按鈕 */
            color: white;
        }

        .delete-btn:hover {
            background-color: #b91c1c;
        }

        .menu-nav {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding-bottom: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f2c79e;
        }

        .menu-nav a {
            text-decoration: none;
            color: #777;
            padding: 8px 15px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 4px 4px 0 0;
            white-space: nowrap;
            font-size: 14px;
            transition: 0.3s;
        }

        .menu-nav a:hover {
            background: #f28c28;
            color: white;
            border-color: #f28c28;
        }

        /* 系列標題 */
        .category-title {
            color: #d35400;
            border-bottom: 1px solid #eecfa1;
            padding-bottom: 8px;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: bold;
        }

        /* 網格佈局 (兩欄式) */
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }

        /* 單個菜單卡片 */
        .menu-card {
            background: white;
            border: 1px solid #fcefe3;
            border-left: 5px solid #f28c28;
            /* 左側橘色線條裝飾 */
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .menu-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        .menu-info {
            flex: 1;
            padding-right: 15px;
        }

        .menu-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .menu-desc {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .menu-tags span {
            font-size: 12px;
            color: #c0392b;
            padding: 2px 6px;
            border-radius: 4px;
            margin-right: 5px;
            display: inline-block;
            margin-bottom: 4px;
        }

        .menu-price {
            font-size: 18px;
            color: #2c3e50;
            font-weight: bold;
            margin-top: 8px;
        }

        /* 圖片框 Placeholder */
        .menu-img-box {
            width: 80px;
            height: 80px;
            background: #fffbf7;
            border: 1px solid #f2e0c9;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #e6b083;
            font-size: 30px;
            flex-shrink: 0;
        }

        /* 查詢表單 */
        .search-form {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form button {
            background-color: #f28c28;
            /* 橘色搜尋按鈕 */
            color: white;
            padding: 6px 12px;
        }

        .search-form button:hover {
            background-color: #d97706;
            /* 深橘色 hover */
        }

        /* 刪除按鈕樣式 (預設隱藏，編輯模式顯示) */
        .delete-item-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            font-size: 12px;
            display: none;
            /* 預設隱藏 */
            justify-content: center;
            align-items: center;
        }

        .delete-type-btn {
            background: #c0392b;
            color: white;
            padding: 4px 10px;
            font-size: 14px;
            display: none;
            /* 預設隱藏 */
        }

        /* 新增在上方的 style 標籤內 */
        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            /* 半透明黑色 */
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }

        .upload-overlay:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        /* 圖片刪除按鈕 (紅色小叉叉) */
        .img-delete-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 14px;
            cursor: pointer;
            z-index: 10;
            /* 確保在最上層 */
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .img-delete-btn:hover {
            background-color: #c0392b;
        }
    </style>

</head>

<body>
    <?php include "store_menu.php"; ?>

    <div id="b">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>新增菜單</h1>
            <button style="background:#f28c28; color:white; padding:8px 14px; font-size:14px;">
                新增菜單
            </button>
        </div>
        <!-- 新增菜單區塊（預設隱藏） -->
        <div id="addMenuBlock" style="display:none;">

            <!-- Step 1：新增系列 -->
            <label>系列名稱</label><br>
            <input type="text" id="menuType" placeholder="例如：漢堡、吐司、飲料">

            <button id="saveTypeBtn" style="background:#f28c28; color:white; padding:6px 12px;">下一步</button>

            <hr>

            <!-- Step 2：新增品項（預設隱藏） -->
            <div id="itemBlock" style="display:none;">
                <h3 style="color:#b35c00;">新增品項</h3>

                品項名稱：<br>
                <input type="text" id="itemName"><br><br>

                描述：<br>
                <input type="text" id="itemDesc"><br><br>

                價格：<br>
                <input type="number" id="itemPrice"><br><br>

                庫存：<br>
                <input type="number" id="itemStock"><br><br>

                備註：<br>
                <input type="text" id="itemNote"><br><br>

                烹飪時間（分鐘）：<br>
                <input type="number" id="itemCook" placeholder="例如：15"><br><br>

                餐點圖片：<br>
                <input type="file" id="itemImgFile" accept="image/*"><br><br>

                <button id="saveItemBtn" style="background:#f28c28; color:white; padding:6px 12px;">
                    儲存品項
                </button>
            </div>
        </div>
    </div>
    <div id="b">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1>我的菜單</h1>
            <button onclick="toggleAddMenu()" id="toggleBtn"
                style="background:#f28c28; color:white; padding:8px 14px; font-size:14px;">
                編輯菜單
            </button>
        </div>

        <div id="displayMenuBlock">
            <?php if (empty($menuData)): ?>
                <p style="text-align:center; color:#888; padding:20px;">目前還沒有菜單，請點擊「新增菜單」開始建立。</p>
            <?php else: ?>

                <form id="editMenuForm">
                    <div class="menu-nav">
                        <?php foreach ($menuData as $type => $items): ?>
                            <a href="#cat-<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></a>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($menuData as $type => $items): ?>
                        <div class="category-header">
                            <div id="cat-<?= htmlspecialchars($type) ?>" class="category-title">
                                <?= htmlspecialchars($type) ?>
                            </div>
                            <button type="button" class="delete-type-btn edit-mode"
                                onclick="deleteType('<?= htmlspecialchars($type) ?>')">
                                <i class="bi bi-trash"></i> 刪除此系列
                            </button>
                        </div>

                        <div class="menu-grid">
                            <?php foreach ($items as $item):
                                $id = $item['menu_id']; ?>
                                <div class="menu-card">
                                    <button type="button" class="delete-item-btn edit-mode" onclick="deleteItem(<?= $id ?>)">
                                        <i class="bi bi-x"></i>
                                    </button>

                                    <div class="menu-info" style="width: 100%;">
                                        <div class="menu-name">
                                            <span class="view-mode"><?= htmlspecialchars($item['name']) ?></span>
                                            <input type="text" class="edit-mode form-control" name="menu[<?= $id ?>][name]"
                                                value="<?= htmlspecialchars($item['name']) ?>"
                                                style="display:none; width:100%; font-weight:bold; margin-bottom:5px;">
                                        </div>

                                        <div class="menu-desc">
                                            <?php $desc = $item['description'] ?? ''; ?>
                                            <span class="view-mode"><?= htmlspecialchars($desc) ?></span>
                                            <input type="text" class="edit-mode" name="menu[<?= $id ?>][description]"
                                                value="<?= htmlspecialchars($desc) ?>" placeholder="描述"
                                                style="display:none; width:100%; margin-bottom:5px;">
                                        </div>

                                        <div class="menu-tags">
                                            <span class="view-mode">
                                                <?php if (!empty($item['cook_time']) && $item['cook_time'] != '00:00:00'): ?>
                                                    <span
                                                        style="font-size:12px; color:#888; background:#eee; padding:2px 6px; border-radius:4px;"><i
                                                            class="bi bi-clock"></i> <?= substr($item['cook_time'], 3, 2) ?>分</span>
                                                <?php endif; ?>
                                            </span>
                                            <label class="edit-mode" style="display:none; font-size:12px;">時間(分):</label>
                                            <input type="number" class="edit-mode" name="menu[<?= $id ?>][cook_time]"
                                                value="<?= isset($item['cook_time']) ? substr($item['cook_time'], 3, 2) : '' ?>"
                                                style="display:none; width:50px; padding:2px;">

                                            <span class="view-mode">
                                                <?php if (isset($item['stock'])): ?>
                                                    <span
                                                        style="color:#27ae60; background:#eafaf1; font-size:12px; padding:2px 6px; border-radius:4px;">庫存:<?= $item['stock'] ?></span>
                                                <?php endif; ?>
                                            </span>
                                            <label class="edit-mode"
                                                style="display:none; font-size:12px; margin-left:5px;">庫存:</label>
                                            <input type="number" class="edit-mode" name="menu[<?= $id ?>][stock]"
                                                value="<?= $item['stock'] ?>" style="display:none; width:50px; padding:2px;">
                                        </div>

                                        <div class="menu-price">
                                            <span class="view-mode">$<?= number_format($item['price']) ?></span>
                                            <span class="edit-mode" style="display:none;">$</span>
                                            <input type="number" class="edit-mode" name="menu[<?= $id ?>][price]"
                                                value="<?= $item['price'] ?>"
                                                style="display:none; width:80px; font-weight:bold; color:#2c3e50;">
                                        </div>
                                    </div>

                                    <div class="menu-img-box" style="overflow:visible; position:relative;"> <img
                                            id="preview-<?= $id ?>"
                                            src="<?= !empty($item['img_id']) ? 'get_image.php?id=' . $item['img_id'] : '' ?>"
                                            style="width:100%; height:100%; object-fit:cover; border-radius:8px; display: <?= !empty($item['img_id']) ? 'block' : 'none' ?>;">

                                        <i id="icon-<?= $id ?>" class="bi bi-cup-hot"
                                            style="display: <?= !empty($item['img_id']) ? 'none' : 'block' ?>;"></i>

                                        <span id="btn-del-img-<?= $id ?>" class="edit-mode img-delete-btn" style="display:none;"
                                            onclick="deleteImage(<?= $id ?>)">
                                            &times;
                                        </span>

                                        <label class="edit-mode upload-overlay" style="display:none; border-radius:8px;">
                                            <i class="bi bi-camera-fill"></i> 更換
                                            <input type="file" style="display:none;" accept="image/*"
                                                onchange="handleEditImage(this, <?= $id ?>)">
                                        </label>

                                        <input type="hidden" name="menu[<?= $id ?>][image_base64]" id="base64-<?= $id ?>">

                                        <input type="hidden" name="menu[<?= $id ?>][delete_image]" id="delete-flag-<?= $id ?>"
                                            value="0">

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </form>
            <?php endif; ?>
        </div>

        <div id="addMenuBlock" style="display:none;">
            <h2 style="color:#b35c00; border-bottom:1px solid #ccc; padding-bottom:10px;">新增菜單模式</h2>
            <label>系列名稱</label><br>
            <input type="text" id="menuType" placeholder="例如：漢堡、吐司、飲料">
            <button id="saveTypeBtn" style="background:#f28c28; color:white; padding:6px 12px;">下一步</button>
            <hr>
            <div id="itemBlock" style="display:none;">
                <h3 style="color:#b35c00;">新增品項</h3>
                品項名稱：<br><input type="text" id="itemName"><br><br>
                描述：<br><input type="text" id="itemDesc"><br><br>
                價格：<br><input type="number" id="itemPrice"><br><br>
                庫存：<br><input type="number" id="itemStock"><br><br>
                備註：<br><input type="text" id="itemNote"><br><br>
                烹飪時間（分鐘）：<br><input type="number" id="itemCook" placeholder="例如：15"><br><br>
                <button id="saveItemBtn" style="background:#f28c28; color:white; padding:6px 12px;">儲存品項</button>
            </div>
        </div>
    </div>
    <script>
        let currentItems = [];  // 暫存目前系列的所有品項

        // 顯示新增菜單區塊
        document.querySelector("#b button").addEventListener("click", function () {
            document.getElementById("addMenuBlock").style.display = "block";
        });

        // Step1：下一步 → 顯示品項欄位
        document.getElementById("saveTypeBtn").onclick = function () {
            let type = document.getElementById("menuType").value.trim();
            if (type === "") {
                alert("請輸入系列名稱");
                return;
            }
            document.getElementById("itemBlock").style.display = "block";
        };

        // ⭐「加入品項」按鈕 → 可重複新增
        document.getElementById("saveItemBtn").onclick = function () {
            let item = {
                name: document.getElementById("itemName").value,
                description: document.getElementById("itemDesc").value,
                price: document.getElementById("itemPrice").value,
                stock: document.getElementById("itemStock").value,
                note: document.getElementById("itemNote").value,
                cook_time: document.getElementById("itemCook").value
            };

            // 基本檢查
            if (!item.name || !item.price) {
                alert("品項名稱與價格必填！");
                return;
            }

            // 加進暫存陣列
            currentItems.push(item);
            alert("品項已加入本系列！");

            // 清空欄位給下一個品項使用
            document.getElementById("itemName").value = "";
            document.getElementById("itemDesc").value = "";
            document.getElementById("itemPrice").value = "";
            document.getElementById("itemStock").value = "";
            document.getElementById("itemNote").value = "";
            document.getElementById("itemCook").value = "";
        };

        // ⭐ 新增「完成系列」按鈕
        let finishBtn = document.createElement("button");
        finishBtn.textContent = "完成此系列並儲存";
        finishBtn.style = "background:#b35c00; color:white; padding:6px 12px; margin-top:10px;";
        document.getElementById("itemBlock").appendChild(finishBtn);

        finishBtn.onclick = function () {
            let typeName = document.getElementById("menuType").value;

            if (currentItems.length === 0) {
                alert("至少需要一個品項！");
                return;
            }

            // 要送給後端的資料
            let data = {
                action: "add_menu_series",
                type: typeName,
                items: currentItems
            };

            fetch("../store/store_menu_add.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(data)
            })
                .then(res => res.text())
                .then(res => {
                    alert(res); // 顯示後端回傳的 "新增成功" 訊息

                    // ★ 修改重點：新增成功後，重新整理頁面，讓 PHP 重新撈取資料庫的最新資料
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("發生錯誤，請稍後再試");
                });
        };

        // 定義編輯狀態變數
        let isEditMode = false;

        // 綁定編輯按鈕功能
        function toggleAddMenu() {
            const btn = document.getElementById("toggleBtn");
            const viewElements = document.querySelectorAll('.view-mode');
            const editElements = document.querySelectorAll('.edit-mode');

            if (!isEditMode) {
                // --- 進入編輯模式 ---
                viewElements.forEach(el => el.style.display = 'none');

                // 顯示所有 edit-mode 元素
                editElements.forEach(el => el.style.display = 'inline-block');

                // ★ 特別處理：檢查每一張圖，如果現在是「預設圖示」狀態，就不要顯示刪除按鈕
                document.querySelectorAll('.menu-img-box').forEach(box => {
                    // 找出這個 box 對應的 ID (從小叉叉的 ID 裡抓數字)
                    let delBtn = box.querySelector('.img-delete-btn');
                    if (delBtn) {
                        let id = delBtn.id.split('-').pop(); // 取得 ID
                        let img = document.getElementById('preview-' + id);

                        // 如果圖片是隱藏的 (代表現在沒圖)，就隱藏刪除按鈕
                        if (img.style.display === 'none') {
                            delBtn.style.display = 'none';
                        } else {
                            delBtn.style.display = 'block';
                        }
                    }
                });

                // 隱藏上傳遮罩的 inline-block 改為 flex (因為 CSS 定義它是 flex)
                document.querySelectorAll('.upload-overlay').forEach(el => el.style.display = 'flex');

                btn.innerHTML = "儲存所有變更";
                btn.style.backgroundColor = "#27ae60";
                isEditMode = true;
            } else {
                // ... (儲存邏輯不變) ...
                if (!confirm("確定要更新所有菜單嗎？")) return;
                const form = document.getElementById("editMenuForm");
                const formData = new FormData(form);
                fetch('../store/store_menu_edit.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.text())
                    .then(result => {
                        alert(result);
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("發生錯誤");
                    });
            }
        }

        // ★ 新增：刪除圖片函式
        function deleteImage(id) {
            // 1. 隱藏圖片預覽
            document.getElementById('preview-' + id).style.display = 'none';
            document.getElementById('preview-' + id).src = '';

            // 2. 顯示預設杯子 Icon
            document.getElementById('icon-' + id).style.display = 'block';

            // 3. 隱藏刪除按鈕自己
            document.getElementById('btn-del-img-' + id).style.display = 'none';

            // 4. 設定資料標記
            document.getElementById('base64-' + id).value = ''; // 清空可能剛上傳的新圖
            document.getElementById('delete-flag-' + id).value = '1'; // ★ 設定刪除標記為 1
        }

        // 修改：處理編輯模式下的圖片選擇
        async function handleEditImage(input, id) {
            if (input.files && input.files[0]) {
                let file = input.files[0];
                try {
                    let base64 = await toBase64(file);

                    // 更新 Base64 欄位
                    document.getElementById('base64-' + id).value = base64;

                    // ★ 重置刪除標記 (因為使用者剛選了新圖，代表不刪了)
                    document.getElementById('delete-flag-' + id).value = '0';

                    // 即時預覽
                    document.getElementById('icon-' + id).style.display = 'none';
                    let img = document.getElementById('preview-' + id);
                    img.style.display = 'block';
                    img.src = base64;

                    // ★ 選了新圖後，顯示刪除按鈕 (讓使用者可以反悔)
                    document.getElementById('btn-del-img-' + id).style.display = 'block';

                } catch (e) {
                    alert("圖片處理錯誤");
                }
            }
        }

        // ★ 刪除單一品項
        function deleteItem(id) {
            if (!confirm("確定要刪除這個品項嗎？")) return;

            let formData = new FormData();
            formData.append('action', 'delete_item');
            formData.append('menu_id', id);

            fetch('store_menu_delete.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.text())
                .then(res => {
                    alert(res);
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert("發生錯誤，請檢查 Console");
                });
        }

        // ★ 刪除整系列
        function deleteType(typeName) {
            if (!confirm("警告！這將會刪除「" + typeName + "」系列底下的【所有品項】！\n確定要繼續嗎？")) return;

            let formData = new FormData();
            formData.append('action', 'delete_type');
            formData.append('type_name', typeName);

            // 修改路徑：直接用當前目錄的檔案
            fetch('store_menu_delete.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.text())
                .then(res => {
                    alert(res);
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert("發生錯誤，請檢查 Console");
                });
        }

        // 1. 新增轉檔工具函式 (放在 script 最前面或 onclick 外面)
        function toBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file); // 讀取檔案轉為 Data URL
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        }

        // 2. 修改儲存品項的按鈕事件
        document.getElementById("saveItemBtn").onclick = async function () {
            // ^ 注意：這裡加上了 async 關鍵字，因為轉檔需要等待

            let fileInput = document.getElementById("itemImgFile");
            let base64String = ""; // 用來存圖片字串

            // 如果使用者有選圖片，就進行轉檔
            if (fileInput.files.length > 0) {
                try {
                    base64String = await toBase64(fileInput.files[0]);
                } catch (e) {
                    alert("圖片處理失敗");
                    return;
                }
            }

            // 建立要存入陣列的物件
            let item = {
                name: document.getElementById("itemName").value,
                description: document.getElementById("itemDesc").value,
                price: document.getElementById("itemPrice").value,
                stock: document.getElementById("itemStock").value,
                note: document.getElementById("itemNote").value,
                cook_time: document.getElementById("itemCook").value,
                image_data: base64String // ★ 把轉換好的超長字串存進去
            };

            // (原本的驗證邏輯)
            if (!item.name || !item.price) {
                alert("品項名稱與價格必填！");
                return;
            }

            // 加入暫存陣列
            currentItems.push(item);
            alert("品項已加入 (含圖片)，請繼續新增或點擊「完成此系列」");

            // 清空欄位
            document.getElementById("itemName").value = "";
            document.getElementById("itemDesc").value = "";
            document.getElementById("itemPrice").value = "";
            document.getElementById("itemStock").value = "";
            document.getElementById("itemNote").value = "";
            document.getElementById("itemCook").value = "";
            document.getElementById("itemImgFile").value = ""; // ★ 記得清空檔案欄位
        };

        // ★★★ 處理編輯模式下的圖片選擇 ★★★
        async function handleEditImage(input, id) {
            if (input.files && input.files[0]) {
                let file = input.files[0];

                try {
                    // 1. 轉成 Base64
                    let base64 = await toBase64(file);

                    // 2. 存入隱藏欄位 (準備給後端)
                    document.getElementById('base64-' + id).value = base64;

                    // 3. 即時預覽：隱藏 icon，顯示 img，並更換 src
                    document.getElementById('icon-' + id).style.display = 'none';
                    let img = document.getElementById('preview-' + id);
                    img.style.display = 'block';
                    img.src = base64; // 直接用 Base64 當作圖片來源預覽

                } catch (e) {
                    alert("圖片處理錯誤");
                    console.error(e);
                }
            }
        }
    </script>
</body>

</html>
<?php
session_start();
include "../db.php";

$loginAccount = $_SESSION['user'] ?? '';
$loginRole = $_SESSION['role'] ?? '';

// 未登入 → 強制回登入
if (!$loginAccount) {
    echo "<script>alert('請先登入！'); window.location.href='../login.php';</script>";
    exit;
}

/*------------------------------------------------------
    1) 決定瀏覽哪個店家的菜單
------------------------------------------------------*/
$targetAccount = $_GET['account'] ?? $loginAccount;

/*
    管理員 (admin) 可以查看任意店家
    店家 (store) 只能看自己
*/
if ($loginRole === 'store' && $targetAccount !== $loginAccount) {
    echo "<script>alert('您無權限查看其他店家的菜單'); history.back();</script>";
    exit;
}

/*------------------------------------------------------
    2) 撈取該店家的菜單
------------------------------------------------------*/
$menuData = [];

// 調整為依 type DESC, price ASC 排序
$sql = "SELECT * FROM menu WHERE account = ? ORDER BY type DESC, price ASC";
$stmt = $link->prepare($sql);
$stmt->bind_param("s", $targetAccount);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $menuData[$row['type']][] = $row;
}
$stmt->close();

// 注意：圖片路徑 'get_image.php?id=' . $item['img_id'] 必須確保 /get_image.php 存在且能正確從 MongoDB 讀取圖片數據並輸出為圖片類型。

?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>檢視店家菜單</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* (您的 CSS 樣式) */
        #b {
            background-color: #fff7f0;
            margin: 20px auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 780px;
            text-align: left;
            border: 1px solid #f0d4b2;
        }

        #b h1 {
            font-size: 24px;
            margin-top: 0;
            color: #b35c00;
        }

        input {
            padding: 8px 10px;
            border: 1px solid #f2c79e;
            border-radius: 8px;
        }

        button {
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .announcement {
            background-color: #fff3e6;
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
            color: white;
        }

        .edit-btn:hover {
            background-color: #d97706;
        }

        .delete-btn {
            padding: 5px 10px;
            font-size: 12px;
            background-color: #dc2626;
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

        .category-title {
            color: #d35400;
            border-bottom: 1px solid #eecfa1;
            padding-bottom: 8px;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: bold;
        }

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

        .menu-card {
            background: white;
            border: 1px solid #fcefe3;
            border-left: 5px solid #f28c28;
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

        .search-form {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form button {
            background-color: #f28c28;
            color: white;
            padding: 6px 12px;
        }

        .search-form button:hover {
            background-color: #d97706;
        }

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
            justify-content: center;
            align-items: center;
        }

        .delete-type-btn {
            background: #c0392b;
            color: white;
            padding: 4px 10px;
            font-size: 14px;
            display: none;
        }

        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
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
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .img-delete-btn:hover {
            background-color: #c0392b;
        }

        .menu-badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 8px;
            /* 與價格隔開 */
        }

        /* 銷售量 */
        .badge-sale {
            font-size: 12px;
            color: #d35400;
            /* 深橘色字 */
            background: #fdf2e9;
            /* 淺橘色底 */
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #fae5d3;
        }

        /* 庫存 */
        .badge-stock {
            font-size: 12px;
            color: #27ae60;
            /* 綠色字 */
            background: #eafaf1;
            /* 淺綠底 */
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #d4efdf;
        }

        /* 低庫存警示 */
        .badge-stock.low {
            color: #c0392b;
            /* 紅色字 */
            background: #fdedec;
            /* 淺紅底 */
            border: 1px solid #fadbd8;
            font-weight: bold;
        }

        /* 烹飪時間 */
        .badge-time {
            font-size: 12px;
            color: #888;
            background: #eee;
            padding: 2px 8px;
            border-radius: 4px;
        }
    </style>

</head>

<body>
    <?php include "admin_menu.php"; ?>
    <div id="b">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1><?= htmlspecialchars($targetAccount) ?> 的菜單</h1>
            </div>

        <div id="displayMenuBlock">
            <?php if (empty($menuData)): ?>
                <p style="text-align:center; color:#888; padding:20px;">目前還沒有菜單。</p>
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

                                        <div class="menu-badges">
                                            <span class="view-mode badge-sale">
                                                <i class="bi bi-fire"></i> 已售:
                                                <?= isset($item['sale_amount']) ? $item['sale_amount'] : 0 ?>
                                            </span>

                                            <span class="view-mode">
                                                <?php
                                                $stock = isset($item['stock']) ? $item['stock'] : 0;
                                                $stock_class = "badge-stock";
                                                if ($stock < 5) {
                                                    $stock_class .= " low"; // 低庫存變紅
                                                }
                                                ?>
                                                <span class="<?= $stock_class ?>">
                                                    <i class="bi bi-box-seam"></i> 庫存: <?= $stock ?>
                                                </span>
                                            </span>
                                            <label class="edit-mode" style="display:none; font-size:12px; color:#666;">庫存:</label>
                                            <input type="number" class="edit-mode" name="menu[<?= $id ?>][stock]"
                                                value="<?= $stock ?>"
                                                style="display:none; width:60px; padding:2px; margin-right:5px;">

                                            <span class="view-mode">
                                                <?php if (!empty($item['cook_time']) && $item['cook_time'] != '00:00:00'): ?>
                                                    <span class="badge-time">
                                                        <i class="bi bi-clock"></i> <?= substr($item['cook_time'], 3, 2) ?>分
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                            <label class="edit-mode"
                                                style="display:none; font-size:12px; color:#666;">時間(分):</label>
                                            <input type="number" class="edit-mode" name="menu[<?= $id ?>][cook_time]"
                                                value="<?= isset($item['cook_time']) ? substr($item['cook_time'], 3, 2) : '' ?>"
                                                style="display:none; width:50px; padding:2px;">
                                        </div>

                                        <div class="menu-price">
                                            <span class="view-mode">$<?= number_format($item['price']) ?></span>
                                            <span class="edit-mode" style="display:none;">$</span>
                                            <input type="number" class="edit-mode" name="menu[<?= $id ?>][price]"
                                                value="<?= $item['price'] ?>"
                                                style="display:none; width:80px; font-weight:bold; color:#2c3e50;">
                                        </div>
                                    </div>

                                    <div class="menu-img-box" style="overflow:visible; position:relative;"> 
                                        <img id="preview-<?= $id ?>"
                                            src="<?= !empty($item['img_id']) ? '../store/get_image.php?id=' . $item['img_id'] : '' ?>"
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
            </div>
    </div>
    
    <script>
        let currentItems = [];

        // 1. 轉檔工具函式 (被 handleEditImage 引用)
        function toBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        }

        // 2. 處理編輯模式下的圖片選擇 (被 onchange="handleEditImage(this, id)" 引用)
        async function handleEditImage(input, id) {
            if (input.files && input.files[0]) {
                let file = input.files[0];
                try {
                    let base64 = await toBase64(file);
                    document.getElementById('base64-' + id).value = base64;
                    document.getElementById('delete-flag-' + id).value = '0';

                    document.getElementById('icon-' + id).style.display = 'none';
                    let img = document.getElementById('preview-' + id);
                    img.style.display = 'block';
                    img.src = base64;

                    document.getElementById('btn-del-img-' + id).style.display = 'block';
                } catch (e) {
                    alert("圖片處理錯誤");
                    console.error(e);
                }
            }
        }

        // 3. 刪除圖片函式 (被 onclick="deleteImage(id)" 引用)
        function deleteImage(id) {
            if (!confirm("確定要移除這張圖片嗎？")) return;
            document.getElementById('preview-' + id).style.display = 'none';
            document.getElementById('preview-' + id).src = '';
            document.getElementById('icon-' + id).style.display = 'block';
            document.getElementById('btn-del-img-' + id).style.display = 'none';
            document.getElementById('base64-' + id).value = ''; 
            document.getElementById('delete-flag-' + id).value = '1'; 
        }

        // 4. 刪除單一品項函式 (被 onclick="deleteItem(id)" 引用)
        function deleteItem(id) {
            if (!confirm("確定要刪除這個品項嗎？")) return;
            // 由於這是管理員檢視頁面，這裡不應執行實際刪除。您可以根據您的權限設計來決定是否保留此處的刪除邏輯。
            // 假設您希望管理員可以刪除:
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

        // 5. 刪除整系列函式 (被 onclick="deleteType(typeName)" 引用)
        function deleteType(typeName) {
            if (!confirm("警告！這將會刪除「" + typeName + "」系列底下的【所有品項】！\n確定要繼續嗎？")) return;
            // 執行刪除的 fetch 邏輯...
            let formData = new FormData();
            formData.append('action', 'delete_type');
            formData.append('type_name', typeName);

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
        
        // 以下為新增邏輯，保留結構但不會被執行，可刪除或保留
        document.querySelector("#b button")?.addEventListener("click", function() {
             document.getElementById("addMenuBlock")?.style.display = "block";
        });
        document.getElementById("saveTypeBtn")?.onclick = function () { /* ... */ };
        document.getElementById("saveItemBtn")?.onclick = async function () { /* ... */ };
        
        let finishBtn = document.createElement("button");
        // ... (省略新增按鈕的邏輯)
        
    </script>
</body>

</html>
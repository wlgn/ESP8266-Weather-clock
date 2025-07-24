<?php
$host = "localhost";
$user = "root";
$password = "Zax6424900";
$dbname = "otdb";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) die("连接失败: " . $conn->connect_error);

// 图片上传处理
if (isset($_FILES['upload_logo'])) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileName = uniqid() . '_' . basename($_FILES['upload_logo']['name']);
    $targetFile = $uploadDir . $fileName;
    
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($imageFileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['upload_logo']['tmp_name'], $targetFile)) {
            echo json_encode(['success' => true, 'filepath' => $targetFile]);
        } else {
            echo json_encode(['success' => false, 'error' => '文件上传失败']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => '仅支持 JPG, JPEG, PNG, GIF 格式']);
    }
    exit;
}

// 添加工具
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO onlinetools (toolname, category, toollogo, toolurl, description, onedesc,`order`) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $_POST['toolname'], $_POST['category'], $_POST['toollogo'], $_POST['toolurl'], $_POST['description'], $_POST['onedesc'],$_POST['order']);
    $stmt->execute();
}

// 更新工具
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE onlinetools SET toolname=?, category=?, toollogo=?, toolurl=?, description=?, onedesc=?, `order`=? WHERE id=?");
    $stmt->bind_param("ssssssii", $_POST['toolname'], $_POST['category'], $_POST['toollogo'], $_POST['toolurl'], $_POST['description'], $_POST['onedesc'], $_POST['order'], $_POST['id']);
    $stmt->execute();
}

// 删除工具
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM onlinetools WHERE id=" . intval($_GET['delete']));
}

// 搜索处理
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $whereClause = " WHERE toolname LIKE ? OR category LIKE ? OR description LIKE ?";
    $searchTerm = "%{$searchTerm}%";
    $types = 'sss';
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

// 分页处理
$perPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// 获取总数
$countSql = "SELECT COUNT(*) AS total FROM onlinetools" . $whereClause;
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result()->fetch_assoc();
$totalItems = $totalResult['total'];
$totalPages = ceil($totalItems / $perPage);

// 获取工具列表
$sql = "SELECT * FROM onlinetools" . $whereClause . " ORDER BY `order` ASC LIMIT ?, ?";
$types .= 'ii';
$params[] = $offset;
$params[] = $perPage;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tools = $stmt->get_result();

// 获取所有分类
$categories = $conn->query("SELECT DISTINCT category FROM onlinetools");
?>
<!DOCTYPE html>
<html>
<head>
    <title>工具管理后台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); padding: 20px; }
        h1 { color: #2c3e50; margin-bottom: 20px; text-align: center; }
        
        /* 搜索区域 */
        .search-area { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-box { flex: 1; position: relative; }
        .search-input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #7f8c8d; }
        
        /* 表单样式 */
        .form-container { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #495057; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; font-size: 16px; }
        textarea.form-control { min-height: 80px; resize: vertical; }
        .upload-group { display: flex; gap: 10px; }
        .upload-preview { width: 60px; height: 60px; border: 1px dashed #ddd; display: flex; align-items: center; justify-content: center; border-radius: 4px; overflow: hidden; }
        .upload-preview img { max-width: 100%; max-height: 100%; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: all 0.3s; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-group { display: flex; gap: 10px; }
        
        /* 表格样式 */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; border: 1px solid #dee2e6; }
        thead { background: #2c3e50; color: white; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        tbody tr:hover { background: #e9f7fe; }
        .action-cell { white-space: nowrap; }
        .thumbnail { width: 50px; height: 50px; object-fit: contain; }
        
        /* 分页样式 */
        .pagination { display: flex; justify-content: center; margin-top: 20px; }
        .page-item { margin: 0 5px; }
        .page-link { display: block; padding: 8px 15px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
        .page-link:hover { background: #2980b9; }
        .current-page { background: #1a5276; }
        .disabled { opacity: 0.5; pointer-events: none; }
        
        /* 消息提示 */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* 隐藏元素 */
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>在线工具管理后台</h1>
        
        <!-- 搜索区域 -->
        <div class="search-area">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="搜索工具名称、分类或描述..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <button id="searchBtn" class="btn btn-primary">搜索</button>
            <button id="resetBtn" class="btn btn-secondary">重置</button>
        </div>
        
        <!-- 添加/编辑表单 -->
        <div class="form-container">
            <h2 id="formTitle">添加新工具</h2>
            <form id="toolForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="toolname">工具名称 *</label>
                        <input type="text" name="toolname" id="toolname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="category">分类 *</label>
                        <input type="text" name="category" id="category" class="form-control" required list="categories">
                        <datalist id="categories">
                            <?php while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label for="toollogo">图标链接 *</label>
                        <div class="upload-group">
                            <input type="text" name="toollogo" id="toollogo" class="form-control" required>
                            <input type="file" id="logoUpload" accept="image/*" style="display: none">
                            <button type="button" id="uploadBtn" class="btn btn-secondary">上传图片</button>
                        </div>
                        <div class="upload-preview" id="logoPreview">
                            <span>预览</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="toolurl">工具链接 *</label>
                        <input type="url" name="toolurl" id="toolurl" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">简要说明 *</label>
                        <textarea name="description" id="description" class="form-control" required></textarea>
                    </div>
					<div class="form-group">
                        <label for="onedesc">一句话介绍 *</label>
                        <textarea name="onedesc" id="onedesc" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="order">排序号</label>
                        <input type="number" name="order" id="order" class="form-control" value="0">
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" name="add" id="addBtn" class="btn btn-primary">添加工具</button>
                    <button type="submit" name="update" id="updateBtn" class="btn btn-success hidden">更新工具</button>
                    <button type="button" id="cancelBtn" class="btn btn-secondary hidden">取消编辑</button>
                </div>
            </form>
        </div>
        
        <!-- 工具列表 -->
        <div class="table-container">
            <h2>工具列表 (共 <?= $totalItems ?> 个)</h2>
            <table>
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>工具名称</th>
                        <th width="120">分类</th>
                        <th width="80">图标</th>
                        <th width="80">排序</th>
                        <th width="150">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $tools->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['toolname']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><img src="<?= htmlspecialchars($row['toollogo']) ?>" class="thumbnail" onerror="this.src='uploads/icon_nopic.png'"></td>
                        <td><?= $row['order'] ?></td>
                        <td class="action-cell">
                            <button onclick="editTool(<?= $row['id'] ?>, '<?= addslashes($row['toolname']) ?>', '<?= addslashes($row['category']) ?>', '<?= addslashes($row['toollogo']) ?>', '<?= addslashes($row['toolurl']) ?>', `<?= addslashes($row['description']) ?>`, `<?= addslashes($row['onedesc']) ?>`, <?= $row['order'] ?>)" 
                                class="btn btn-primary btn-sm">编辑</button>
                            <a href="?delete=<?= $row['id'] ?>&page=<?= $page ?>&search=<?= urlencode($searchTerm) ?>" 
                                onclick="return confirm('确定删除此工具?')" class="btn btn-danger btn-sm">删除</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <!-- 分页导航 -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <div class="page-item">
                    <a href="?page=1&search=<?= urlencode($searchTerm) ?>" class="page-link">首页</a>
                </div>
                <div class="page-item">
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($searchTerm) ?>" class="page-link">上一页</a>
                </div>
                <?php endif; ?>
                
                <?php 
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) echo '<div class="page-item disabled"><span class="page-link">...</span></div>';
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                ?>
                <div class="page-item">
                    <a href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>" 
                       class="page-link <?= $i == $page ? 'current-page' : '' ?>">
                        <?= $i ?>
                    </a>
                </div>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                <div class="page-item disabled"><span class="page-link">...</span></div>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                <div class="page-item">
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($searchTerm) ?>" class="page-link">下一页</a>
                </div>
                <div class="page-item">
                    <a href="?page=<?= $totalPages ?>&search=<?= urlencode($searchTerm) ?>" class="page-link">尾页</a>
                </div>
                <?php endif; ?>
            </div>
            <div style="text-align: center; margin-top: 10px; color: #6c757d;">
                第 <?= $page ?> 页 / 共 <?= $totalPages ?> 页
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // 编辑工具
        function editTool(id, name, category, logo, url, desc, onedesc, order) {
            document.getElementById('edit_id').value = id;
            document.getElementById('toolname').value = name;
            document.getElementById('category').value = category;
            document.getElementById('toollogo').value = logo;
            document.getElementById('toolurl').value = url;
            document.getElementById('description').value = desc;
			document.getElementById('onedesc').value = onedesc;
            document.getElementById('order').value = order;
            updateLogoPreview(logo);
            
            document.getElementById('formTitle').textContent = '编辑工具';
            document.getElementById('addBtn').classList.add('hidden');
            document.getElementById('updateBtn').classList.remove('hidden');
            document.getElementById('cancelBtn').classList.remove('hidden');
            
            // 滚动到表单
            document.querySelector('.form-container').scrollIntoView({ behavior: 'smooth' });
        }
        
        // 取消编辑
        document.getElementById('cancelBtn').addEventListener('click', function() {
            document.getElementById('edit_id').value = '';
            document.getElementById('toolname').value = '';
            document.getElementById('category').value = '';
            document.getElementById('toollogo').value = '';
            document.getElementById('toolurl').value = '';
            document.getElementById('description').value = '';
			document.getElementById('onedesc').value = '';
            document.getElementById('order').value = '0';
            updateLogoPreview('');
            
            document.getElementById('formTitle').textContent = '添加新工具';
            document.getElementById('addBtn').classList.remove('hidden');
            document.getElementById('updateBtn').classList.add('hidden');
            this.classList.add('hidden');
        });
        
        // 图片上传
        document.getElementById('uploadBtn').addEventListener('click', function() {
            document.getElementById('logoUpload').click();
        });
        
        document.getElementById('logoUpload').addEventListener('change', function() {
            if (!this.files.length) return;
            
            const formData = new FormData();
            formData.append('upload_logo', this.files[0]);
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('toollogo').value = data.filepath;
                    updateLogoPreview(data.filepath);
                } else {
                    alert('上传失败: ' + (data.error || '未知错误'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('上传失败，请重试');
            });
        });
        
        // 更新图标预览
        function updateLogoPreview(url) {
            const preview = document.getElementById('logoPreview');
            if (url) {
                preview.innerHTML = `<img src="${url}" alt="Logo预览">`;
            } else {
                preview.innerHTML = '<span>预览</span>';
            }
        }
        
        // 搜索功能
        document.getElementById('searchBtn').addEventListener('click', function() {
            const term = document.getElementById('searchInput').value.trim();
            window.location.href = `admin.php?search=${encodeURIComponent(term)}`;
        });
        
        document.getElementById('resetBtn').addEventListener('click', function() {
            window.location.href = 'admin.php';
        });
        
        // 按Enter键搜索
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchBtn').click();
            }
        });
        
        // 初始化图标预览
        document.addEventListener('DOMContentLoaded', function() {
            const logoUrl = document.getElementById('toollogo').value;
            if (logoUrl) updateLogoPreview(logoUrl);
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
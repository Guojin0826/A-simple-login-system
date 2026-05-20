<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'check.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require 'users.php';
$pdo = getDbConnection();
$s = '';

// 处理头像上传 —— 只存SESSION，不操作数据库（兼容你的表结构）
if (isset($_FILES['avatar']) && $_FILES['avatar']['name'] != "") {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $fileTmpPath = $_FILES["avatar"]["tmp_name"];
    $fileName = $_FILES["avatar"]["name"];
    $fileSize = $_FILES["avatar"]["size"];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $maxSize = 20 * 1024 * 1024;
    if ($fileSize > $maxSize) {
        $s = "抱歉，文件太大，最大为20MB。";
        goto END;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        $s = "抱歉，只允许上传图片文件（jpg, jpeg, png, gif）。";
        goto END;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileMimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($fileMimeType, $allowedMimeTypes)) {
        $s = "你上传的文件类型不正确, 只能上传 jpg, jpeg, png, gif 类型的图片。";
        goto END;
    }

    $imageInfo = getimagesize($fileTmpPath);
    if ($imageInfo === false) {
        $s = "你上传的文件不是有效的图片。";
        goto END;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    if ($width > 4000 || $height > 4000) {
        $s = "上传图片的分辨率过大，宽或高不得超过4000px。";
        goto END;
    }

    $newFileName = bin2hex(random_bytes(8)) . "." . $fileExtension;
    $targetFilePath = $targetDir . $newFileName;

    if (!move_uploaded_file($fileTmpPath, $targetFilePath)) {
        $s = "头像上传失败，请重试。";
        goto END;
    } else {
        // 只保存到SESSION，不写入数据库（解决报错）
        $_SESSION['avatar'] = $newFileName;
        $s = "头像上传成功";
    }
}

END:

// 获取管理员信息
$admin_id = $_SESSION['user_id'];
$stmtAdmin = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmtAdmin->execute(['id' => $admin_id]);
$admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

// 获取所有用户信息
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

// 处理添加用户操作
if (isset($_POST['add_user'])) {
    $username = trim($_POST['add_username']);
    $email = trim($_POST['add_email']);
    $role = trim($_POST['add_role']);
    $password = trim($_POST['add_password']);

    if ($username && $email && $role && $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmtCheck->execute(['username' => $username]);
        $userExists = $stmtCheck->fetchColumn();

        if ($userExists) {
            $s = "用户名已存在，请选择其他用户名";
        } else {
            $insertSql = "INSERT INTO users (username, email, role, password) VALUES (:username, :email, :role, :password)";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'role' => $role,
                    'password' => $hashed_password
            ]);
            header("Location: admin.php");
            exit();
        }
    } else {
        $s = "请填写完整的用户信息";
    }
}

// 处理编辑用户操作
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);
    $role = trim($_POST['edit_role']);
    $password = trim($_POST['edit_password']);

    if (empty($password)) {
        $updateSql = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
                'id' => $user_id,
                'username' => $username,
                'email' => $email,
                'role' => $role
        ]);
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateSql = "UPDATE users SET username = :username, email = :email, role = :role, password = :password WHERE id = :id";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
                'id' => $user_id,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'password' => $hashed_password
        ]);
    }

    header("Location: admin.php");
    exit();
}

// 处理删除用户操作
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    $deleteSql = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($deleteSql);
    $stmt->execute(['id' => $user_id]);

    header("Location: admin.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员后台</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
<div class="admin-container">
    <!-- 顶部导航栏 -->
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="uploads/<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default.png'); ?>"
                 alt="头像" class="admin-avatar"
                 onerror="this.src='https://picsum.photos/80/80'">
            <div class="admin-info">
                <span class="admin-welcome">欢迎回来</span>
                <span class="admin-name"><?php echo htmlspecialchars($admin['username']); ?></span>
                <span class="admin-badge">管理员</span>
            </div>
        </div>
        <div class="admin-header-right">
            <form method="POST" enctype="multipart/form-data" class="avatar-form">
                <label class="upload-label">
                    <input type="file" name="avatar" accept="image/*" hidden>
                    <span class="upload-avatar-btn">更换头像</span>
                </label>
                <button type="submit" class="btn-upload">确认上传</button>
            </form>
        </div>
    </div>

    <?php if ($s): ?>
        <div class="message-toast <?php echo strpos($s, '成功') !== false ? 'success' : 'error'; ?>">
            <?php echo $s; ?>
        </div>
    <?php endif; ?>

    <!-- 统计卡片 -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon users-icon">👥</div>
            <div class="stat-info">
                <span class="stat-number"><?php echo count($users); ?></span>
                <span class="stat-label">用户总数</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon admin-icon">👑</div>
            <div class="stat-info">
                <span class="stat-number"><?php 
                    $adminCount = 0;
                    foreach ($users as $u) {
                        if ($u['role'] === 'admin') $adminCount++;
                    }
                    echo $adminCount;
                ?></span>
                <span class="stat-label">管理员</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon normal-icon">👤</div>
            <div class="stat-info">
                <span class="stat-number"><?php 
                    $userCount = 0;
                    foreach ($users as $u) {
                        if ($u['role'] === 'user') $userCount++;
                    }
                    echo $userCount;
                ?></span>
                <span class="stat-label">普通用户</span>
            </div>
        </div>
    </div>

    <!-- 添加用户卡片 -->
    <div class="admin-card">
        <div class="card-header">
            <h3>添加新用户</h3>
        </div>
        <div class="card-body">
            <form method="POST" class="add-user-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>用户名</label>
                        <input type="text" name="add_username" placeholder="请输入用户名" required>
                    </div>
                    <div class="form-group">
                        <label>邮箱</label>
                        <input type="email" name="add_email" placeholder="请输入邮箱" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>角色</label>
                        <select name="add_role" required>
                            <option value="user">普通用户</option>
                            <option value="admin">管理员</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>密码</label>
                        <input type="password" name="add_password" placeholder="请输入密码" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_user" class="btn-primary">添加用户</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 用户列表卡片 -->
    <div class="admin-card">
        <div class="card-header">
            <h3>用户列表</h3>
            <span class="user-count">共 <?php echo count($users); ?> 个用户</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>用户名</th>
                            <th>邮箱</th>
                            <th>角色</th>
                            <th>密码</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                        <?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?>
                                    </span>
                                </td>
                                <td><code class="password-code"><?php echo substr(htmlspecialchars($user['password']),0,15).'...'; ?></code></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="openEditModal(
                                            <?php echo $user['id']; ?>,
                                            '<?php echo addslashes($user['username']); ?>',
                                            '<?php echo addslashes($user['email']); ?>',
                                            '<?php echo $user['role']; ?>'
                                        )">编辑</button>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn-delete" onclick="return confirm('确定要删除该用户吗？')">删除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 底部操作栏 -->
    <div class="admin-footer">
        <button onclick="location.href='index.php'" class="btn-secondary">返回留言板</button>
        <button onclick="location.href='logout.php'" class="btn-outline">退出登录</button>
    </div>

    <!-- 遮罩层 -->
    <div id="modalOverlay" class="modal-overlay" onclick="closeEditModal()"></div>
    
    <!-- 编辑弹窗 -->
    <div id="editModal">
        <div class="modal-header">
            <h4>编辑用户信息</h4>
            <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" class="edit-form">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="edit_username" id="editUsername" required>
            </div>
            <div class="form-group">
                <label>邮箱</label>
                <input type="email" name="edit_email" id="editEmail" required>
            </div>
            <div class="form-group">
                <label>角色</label>
                <select name="edit_role" id="editRole" required>
                    <option value="user">普通用户</option>
                    <option value="admin">管理员</option>
                </select>
            </div>
            <div class="form-group">
                <label>密码 <span class="hint">（留空则不修改）</span></label>
                <input type="password" name="edit_password" placeholder="不修改请留空">
            </div>
            <div class="modal-actions">
                <button type="submit" name="edit_user" class="btn-primary">保存修改</button>
                <button type="button" onclick="closeEditModal()" class="btn-secondary">取消</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(id, name, email, role) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editUsername').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('modalOverlay').style.display = 'block';
        document.body.style.overflow = 'hidden'; // 禁止背景滚动
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
        document.body.style.overflow = 'auto'; // 恢复背景滚动
    }
    
    // 按 ESC 键关闭弹窗
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
</script>
</body>
</html>
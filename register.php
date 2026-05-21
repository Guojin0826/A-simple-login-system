<?php
/**
 * 用户注册页面
 */

require_once 'functions.php';
require_once 'users.php';

startSession();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        // 获取并清理输入
        $name = sanitizeInput($_POST['name'] ?? '');
        $passwd = $_POST['passwd'] ?? '';
        $email = sanitizeInput($_POST['email'] ?? '');
        $confirm_passwd = $_POST['confirm_passwd'] ?? '';

        // 验证输入
        if (empty($name) || empty($passwd) || empty($email)) {
            $error = '请填写所有必填项';
        } elseif (!isValidEmail($email)) {
            $error = '请输入有效的邮箱地址';
        } elseif (strlen($name) < 3 || strlen($name) > 20) {
            $error = '用户名长度应在3-20个字符之间';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            $error = '用户名只能包含字母、数字和下划线';
        } elseif ($passwd !== $confirm_passwd) {
            $error = '两次输入的密码不一致';
        } else {
            $passwordValidation = validatePasswordStrength($passwd);
            if (!$passwordValidation['valid']) {
                $error = $passwordValidation['message'];
            } else {
                try {
                    $pdo = getDbConnection();
                    
                    // 检查用户名或邮箱是否已存在
                    $checksql = "SELECT id FROM users WHERE email = :email OR username = :username";
                    $stmtcheck = $pdo->prepare($checksql);
                    $stmtcheck->execute(['username' => $name, 'email' => $email]);
                    
                    if ($stmtcheck->fetch()) {
                        $error = '该用户名或邮箱已被注册';
                    } else {
                        // 插入新用户
                        $insertsql = "INSERT INTO users (username, email, password, created_at) VALUES (:username, :email, :passwd, NOW())";
                        $stmtinsert = $pdo->prepare($insertsql);
                        $result = $stmtinsert->execute([
                            'username' => $name,
                            'email' => $email,
                            'passwd' => password_hash($passwd, PASSWORD_DEFAULT)
                        ]);
                        
                        if ($result) {
                            $_SESSION['success_message'] = '注册成功！请登录';
                            redirect('login.php');
                        } else {
                            $error = '注册失败，请稍后再试';
                        }
                    }
                } catch (PDOException $e) {
                    if (DEBUG_MODE) {
                        $error = '数据库错误: ' . $e->getMessage();
                    } else {
                        $error = '系统错误，请稍后再试';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="用户注册">
    <title>注册 - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="register-container">
    <h2>用户注册</h2>

    <?php if ($error): ?>
        <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="register.php" method="post" id="registerForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <label class="label" for="name">用户名：</label>
        <input type="text" id="name" name="name" class="input-field" required
               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
               placeholder="3-20个字符，字母、数字、下划线" autofocus>
        <small class="hint">3-20个字符，只能包含字母、数字和下划线</small>

        <label class="label" for="email">邮箱：</label>
        <input type="email" id="email" name="email" class="input-field" required
               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
               placeholder="请输入有效的邮箱地址">

        <label class="label" for="passwd">密码：</label>
        <input type="password" name="passwd" id="passwd" class="input-field" required
               placeholder="至少<?php echo PASSWORD_MIN_LENGTH; ?>个字符">
        <small class="hint">建议包含大小写字母、数字和特殊字符</small>
        <div id="passwordStrength" class="password-strength"></div>

        <label class="label" for="confirm_passwd">确认密码：</label>
        <input type="password" name="confirm_passwd" id="confirm_passwd" class="input-field" required
               placeholder="请再次输入密码">

        <button type="submit" class="submit-btn">注册</button>
    </form>

    <button class="link-btn" onclick="window.location.href='login.php'">已有账户？去登录</button>
</div>

<script>
// 密码强度检测
document.getElementById('passwd').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthDiv = document.getElementById('passwordStrength');
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const messages = ['', '弱', '较弱', '中等', '强', '非常强'];
    const colors = ['', '#ff4444', '#ff8844', '#ffaa44', '#44aa44', '#44aa44'];
    
    strengthDiv.textContent = messages[strength] || '';
    strengthDiv.style.color = colors[strength] || '';
});

// 密码确认验证
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const passwd = document.getElementById('passwd').value;
    const confirm = document.getElementById('confirm_passwd').value;
    
    if (passwd !== confirm) {
        e.preventDefault();
        alert('两次输入的密码不一致！');
    }
});
</script>
</body>
</html>
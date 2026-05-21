<?php
/**
 * 用户登录页面
 */

require_once 'functions.php';
require_once 'users.php';

startSecureSession();

$error = '';
$success = '';

// 显示session中的消息
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// 检查请求方式是否为 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = '安全验证失败，请刷新页面重试';
    } else {
        // 获取并清理输入数据
        $name = sanitizeInput($_POST['name'] ?? '');
        $passwd = $_POST['passwd'] ?? '';  // 密码不需要htmlspecialchars
        $remember = isset($_POST['remember']);

        // 验证输入
        if (empty($name) || empty($passwd)) {
            $error = '请输入用户名和密码';
        } else {
            try {
                $pdo = getDbConnection();
                $client_ip = $_SERVER['REMOTE_ADDR'];
                
                // 检查是否被锁定
                if (isLockedOut($pdo, $client_ip)) {
                    $error = '登录尝试次数过多，请稍后再试';
                } else {
                    // 查询用户
                    $sql = "SELECT * FROM users WHERE username = :username OR email = :email";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['username' => $name, 'email' => $name]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($passwd, $user['password'])) {
                        // 登录成功，清除尝试记录
                        clearLoginAttempts($pdo, $client_ip);
                        
                        // 更新会话信息
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['last_activity'] = time();

                        // 记住用户名
                        if ($remember) {
                            setcookie('username', $name, time() + (86400 * 30), '/', '', isset($_SERVER['HTTPS']), true);
                        } else {
                            setcookie('username', '', time() - 3600, '/');
                        }

                        redirect('index.php');
                    } else {
                        // 记录失败尝试
                        logLoginAttempt($pdo, $name, $client_ip);
                        $error = '用户名或密码错误';
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
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="用户登录">
    <title>登录 - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="login-container">
    <h2>登录</h2>
    
    <?php if ($error): ?>
        <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <p class="success-message"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form action="login.php" method="post" class="login-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <label for="name" class="label">用户名/邮箱：</label>
        <input type="text" id="name" name="name" class="input-field" required
               value="<?php echo isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : ''; ?>"
               placeholder="请输入用户名或邮箱" autofocus>
        <br>

        <label for="passwd" class="label">密码：</label>
        <input type="password" name="passwd" id="passwd" class="input-field" required
               placeholder="请输入密码">
        <br>

        <div class="remember-me">
            <input type="checkbox" name="remember" id="remember" class="checkbox"
                <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>
            <label for="remember" class="label">记住用户名</label>
        </div>

        <input type="submit" value="登录" class="submit-btn">
    </form>

    <div class="links">
        <button onclick="window.location.href='reset_password.php'" class="link-btn">忘记密码？</button>
        <button onclick="window.location.href='register.php'" class="link-btn">注册新账户</button>
    </div>
</div>
</body>
</html>

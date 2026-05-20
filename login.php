<?php
session_start();  // 启动一个新的会话或继续现有的会话，允许在多个页面间存储数据

require 'users.php';  // 引入用户相关的数据库连接等操作函数文件

$s = "";  // 用于存储错误或提示信息，初始化为空字符串
$lockout_time = 15 * 60;  // 锁定时间：15分钟
$max_attempts = 5;  // 最大尝试次数：5次

// 获取客户端IP地址
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// 检查是否被锁定
function isLockedOut($pdo, $ip, $lockout_time, $max_attempts) {
    $sql = "SELECT COUNT(*) as attempt_count, MAX(attempt_time) as last_attempt 
            FROM login_attempts 
            WHERE ip_address = :ip 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL :lockout_time SECOND)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ip' => $ip, 'lockout_time' => $lockout_time]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['attempt_count'] >= $max_attempts) {
        $last_attempt = strtotime($result['last_attempt']);
        $remaining_time = $lockout_time - (time() - $last_attempt);
        return ['locked' => true, 'remaining' => $remaining_time];
    }
    return ['locked' => false, 'remaining' => 0];
}

// 记录登录尝试
function recordLoginAttempt($pdo, $ip, $username) {
    $sql = "INSERT INTO login_attempts (ip_address, username) VALUES (:ip, :username)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ip' => $ip, 'username' => $username]);
}

// 清除登录尝试记录（登录成功后）
function clearLoginAttempts($pdo, $ip) {
    $sql = "DELETE FROM login_attempts WHERE ip_address = :ip";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['ip' => $ip]);
}

// 检查请求方式是否为 POST，表单提交时触发此条件
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // 获取表单提交的用户名和密码，使用 htmlspecialchars 和 trim 处理输入数据
    $name = htmlspecialchars(trim($_POST["name"]));  // 去掉首尾空格并转义 HTML 特殊字符，防止 XSS 攻击
    $passwd = htmlspecialchars(trim($_POST["passwd"]));  // 同上，处理密码输入
    // 检查用户是否勾选了“记住用户名”复选框
    $remember = isset($_POST["remember"]) ? true : false;  // 如果复选框存在，设置为 true，否则为 false

    // 检查用户名和密码是否为空，如果为空，设置错误信息
    if (empty($name) or empty($passwd)) {
        $s = "请输入正确的用户名和密码";  // 设置错误信息，提示用户输入用户名和密码
    } else {
        // 获取数据库连接
        $pdo = getDbConnection();
        
        // 获取客户端IP
        $client_ip = getClientIP();
        
        // 检查是否被锁定
        $lockout_status = isLockedOut($pdo, $client_ip, $lockout_time, $max_attempts);
        
        if ($lockout_status['locked']) {
            $minutes = ceil($lockout_status['remaining'] / 60);
            $s = "登录尝试次数过多，请 {$minutes} 分钟后再试";
        } else {
            // 准备 SQL 查询，查找用户名对应的用户记录
            $sql = "SELECT * FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);  // 准备 SQL 语句
            $stmt->execute(["username" => $name]);  // 执行查询并绑定参数，防止 SQL 注入
            $user = $stmt->fetch(PDO::FETCH_ASSOC);  // 获取查询结果并以关联数组形式返回

            // 如果找到了对应的用户，并且密码验证通过
            if ($user && password_verify($passwd, $user['password'])) {
                // 清除登录尝试记录
                clearLoginAttempts($pdo, $client_ip);
                
                // 用户验证成功，开始设置会话信息
                $_SESSION["user_id"] = $user["id"];  // 将用户 ID 存入 session，用于后续验证
                $_SESSION["username"] = $user["username"];  // 将用户名存入 session
                $_SESSION["role"] = $user["role"];  // 将用户角色存入 session（例如普通用户或管理员）

                // 如果勾选了记住用户名，将用户名保存在 cookie 中，保存 30 天
                if ($remember) {
                    setcookie("username", $name, time() + (86400 * 30), "/", "", isset($_SERVER["HTTPS"]), true);
                    // setcookie() 函数用于设置 cookie，时间单位为秒（86400 * 30 是 30 天）
                    // isset($_SERVER["HTTPS"])：检查是否通过 HTTPS 协议请求。若是 HTTPS，则返回 true，表示 cookie 只会在 HTTPS 连接下发送。
                    //true：设置 HttpOnly 属性。如果设置为 true，则这个 cookie 只能通过 HTTP 协议访问，而无法通过 JavaScript 等客户端脚本访问，从而提升安全性。
                } else {
                    // 如果未勾选记住用户名，则删除 cookie
                    setcookie("username", "", time() - 3600, "/");  // 设置一个过去的时间来删除 cookie
                }

                // 用户验证成功后，跳转到主页
                header("Location: index.php");  // 重定向到主页
                exit();  // 确保后续代码不再执行
            } else {
                // 记录失败的登录尝试
                recordLoginAttempt($pdo, $client_ip, $name);
                
                // 获取剩余尝试次数
                $lockout_status = isLockedOut($pdo, $client_ip, $lockout_time, $max_attempts);
                $remaining_attempts = $max_attempts - $lockout_status['remaining'];
                
                // 如果用户验证失败，设置错误信息
                $s = "用户不存在或密码错误";  // 提示用户用户名或密码错误
                if ($remaining_attempts > 0 && $remaining_attempts <= 3) {
                    $s .= "（剩余尝试次数：{$remaining_attempts}）";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">  <!-- 设置网页字符编码为 UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  <!-- 适配移动设备，设置视口 -->
    <title>Login</title>  <!-- 设置网页标题 -->
    <link rel="stylesheet" type="text/css" href="style.css">  <!-- 引入外部样式表 -->
</head>
<body>
<!-- 登录容器 -->
<div class="login-container">
    <h2>登录</h2>  <!-- 页面标题：登录 -->
    <p class="error-message"><?php echo htmlspecialchars($s); ?></p>  <!-- 显示错误信息，如果有的话 -->

    <!-- 登录表单，表单提交时使用 POST 方法 -->
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="login-form">
        <!-- 用户名输入框 -->
        <label for="name" class="label">用户名：</label>
        <input type="text" id="name" name="name"
               class="input-field" required
               value="<?php echo isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : ''; ?>"> <br> <!-- 如果 cookie 中有用户名，填充表单 -->

        <!-- 密码输入框 -->
        <label for="passwd" class="label">密码：</label>
        <input type="password" name="passwd" id="passwd" class="input-field" required><br>

        <!-- 记住用户名的复选框 -->
        <div class="remember-me">
            <input type="checkbox" name="remember" id="remember" class="checkbox"
                <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>  <!-- 如果 cookie 中有用户名，复选框默认勾选 -->
            <label for="remember" class="label">记住用户名</label><br>
        </div>

        <!-- 提交按钮 -->
        <input type="submit" value="登录" class="submit-btn">
    </form>

    <!-- 链接到忘记密码和注册页面 -->
    <div class="links">
        <button onclick="window.location.href='reset_password.php'" class="link-btn">忘记密码？重置密码</button><br>
        <button onclick="window.location.href='register.php'" class="link-btn">没有账户？请注册</button>
    </div>
</div>
</body>
</html>

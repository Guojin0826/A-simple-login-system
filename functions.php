<?php
/**
 * 公共函数库
 */

/**
 * 安全地启动会话
 */
function startSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // 防止会话固定攻击
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // 验证会话安全性
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] || 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            header('Location: login.php?error=session_hijack');
            exit();
        }
    }
}

/**
 * 生成CSRF令牌
 * @return string CSRF令牌
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证CSRF令牌
 * @param string $token 待验证的令牌
 * @return bool 是否有效
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * 清理输入数据
 * @param string $data 待清理的数据
 * @return string 清理后的数据
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * 验证邮箱格式
 * @param string $email 邮箱地址
 * @return bool 是否有效
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证密码强度
 * @param string $password 密码
 * @return array 包含验证结果和提示信息
 */
function validatePasswordStrength($password)
{
    $result = ['valid' => true, 'message' => ''];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return ['valid' => false, 'message' => '密码至少需要' . PASSWORD_MIN_LENGTH . '个字符'];
    }
    
    $strength = 0;
    if (strlen($password) >= 8) $strength++;
    if (preg_match('/[a-z]/', $password)) $strength++;
    if (preg_match('/[A-Z]/', $password)) $strength++;
    if (preg_match('/[0-9]/', $password)) $strength++;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength++;
    
    $messages = [
        1 => '密码强度：弱',
        2 => '密码强度：较弱',
        3 => '密码强度：中等',
        4 => '密码强度：强',
        5 => '密码强度：非常强'
    ];
    
    $result['message'] = $messages[$strength] ?? '';
    
    return $result;
}

/**
 * 记录登录尝试
 * @param PDO $pdo 数据库连接
 * @param string $username 用户名
 * @param string $ip IP地址
 */
function logLoginAttempt($pdo, $username, $ip)
{
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (:ip, :username)");
    $stmt->execute(['ip' => $ip, 'username' => $username]);
}

/**
 * 检查是否被锁定
 * @param PDO $pdo 数据库连接
 * @param string $ip IP地址
 * @return bool 是否被锁定
 */
function isLockedOut($pdo, $ip)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND attempt_time > DATE_SUB(NOW(), INTERVAL :lockout SECOND)");
    $stmt->execute(['ip' => $ip, 'lockout' => LOCKOUT_TIME]);
    return $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
}

/**
 * 清除登录尝试记录
 * @param PDO $pdo 数据库连接
 * @param string $ip IP地址
 */
function clearLoginAttempts($pdo, $ip)
{
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
    $stmt->execute(['ip' => $ip]);
}

/**
 * 格式化时间
 * @param string $datetime 时间字符串
 * @return string 格式化后的时间
 */
function formatTime($datetime)
{
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . '天前';
    } else {
        return date('Y-m-d', $timestamp);
    }
}

/**
 * 重定向并退出
 * @param string $url 目标URL
 */
function redirect($url)
{
    header("Location: $url");
    exit();
}

/**
 * 输出JSON响应
 * @param array $data 数据
 * @param int $status HTTP状态码
 */
function jsonResponse($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

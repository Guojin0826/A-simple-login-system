<?php
/**
 * 应用配置文件
 * 包含数据库配置、安全配置、应用设置等
 */

// 数据库配置
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_NAME', 'webuser');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// 安全配置
define('MAX_LOGIN_ATTEMPTS', 5);           // 最大登录尝试次数
define('LOCKOUT_TIME', 15 * 60);           // 锁定时间(秒)
define('SESSION_LIFETIME', 7200);          // 会话生命周期(秒)
define('PASSWORD_MIN_LENGTH', 6);          // 密码最小长度
define('UPLOAD_MAX_SIZE', 20 * 1024 * 1024); // 上传文件最大大小(字节)

// 应用配置
define('APP_NAME', '用户管理系统');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', false);               // 调试模式（生产环境请设为false）

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告设置
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 会话安全配置
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

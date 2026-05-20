<?php
// 避免重复启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    echo "你没有登录";
    header("Refresh:3;url=login.php");
    exit();
}

// 检查用户角色是否为管理员
if ($_SESSION['role'] != 'admin') {
    echo "你不是管理员";
    header("Refresh:3;url=login.php");
    exit();
}
?>
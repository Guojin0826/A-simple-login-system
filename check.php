<?php
/**
 * 管理员权限检查
 */

require_once 'functions.php';

startSession();

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = '请先登录';
    redirect('login.php');
}

// 检查用户角色是否为管理员
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = '权限不足，需要管理员权限';
    redirect('index.php');
}
?>
<?php
require_once 'config.php';
require_once 'functions.php';

startSession();

// 清空所有session变量
$_SESSION = array();

// 删除session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000,
        $params["path"], $params["domain"], 
        $params["secure"], $params["httponly"]
    );
}

// 销毁session
session_destroy();

// 重定向到登录页
header("Location: login.php");
exit();
?>
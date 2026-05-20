<?php
session_start();
$_SESSION = array();     //清空所有session变量
if (ini_get("session.use_cookies")){     //session存在cookie里
    $params = session_get_cookie_params();     //获取sesion和cookie参数
    setcookie(session_name(),"", time() -42000,
        $params["path"],$params["domain"],$params["secure"],$params["httponly"]);
}
session_destroy();
header("Location:login.php");
?>
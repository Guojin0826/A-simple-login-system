<?php
require 'users.php';
$s = '';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = htmlspecialchars(trim(($_POST['name'])));
    $passwd = htmlspecialchars(trim($_POST['passwd']));
    $email = htmlspecialchars(trim($_POST['email']));

    if (empty($name) || empty($passwd) || empty($email)) {
        $s = '请输入完整内容';
    } elseif (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
        $s = '请输入有效的邮箱';
    } elseif (strlen($passwd) < 6) {
        $s = '密码至少需要6个字符';
    } elseif (strlen($email) < 5 ) {
        $s = '邮箱至少需要5个字符';
    } else {
        $pdo = getDbConnection();
        $checksql = "SELECT id FROM users WHERE email = :email OR username = :username";
        $stmtcheck = $pdo->prepare($checksql);
        $stmtcheck->execute(['username' => $name, 'email' => $email]);
        $row = $stmtcheck->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $s = "该用户已存在";
        } else {
            $insertsql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :passwd)";
            $stmtinsert = $pdo->prepare($insertsql);
            $result = $stmtinsert->execute([
                'username' => $name,
                'email' => $email,
                'passwd' => password_hash($passwd, PASSWORD_DEFAULT)
            ]);
            if ($result) {
                $s = "注册成功";
                // header("Location: login.php");
                // exit();
            } else {
                $s = "注册失败";
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
    <title>注册</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
<!-- 注册容器（自动应用你的样式） -->
<div class="register-container">
    <h2>用户注册</h2>

    <!-- 错误信息 -->
    <?php if(!empty($s)): ?>
        <p class="error-message"><?php echo $s; ?></p>
    <?php endif; ?>

    <!-- 表单 -->
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <label class="label" for="name">用户名：</label>
        <input type="text" id="name" name="name"
               value="<?php echo isset($name) ? $name : ""; ?>"
               class="input-field" required>

        <label class="label" for="passwd">密码：</label>
        <input type="password" name="passwd" id="passwd"
               value="<?php echo isset($passwd) ? $passwd : ""; ?>"
               class="input-field" required>

        <label class="label" for="email">邮箱：</label>
        <input type="email" id="email" name="email"
               value="<?php echo isset($email) ? $email : ""; ?>"
               class="input-field" required>

        <button type="submit" class="submit-btn">注册</button>
    </form>

    <!-- 去登录按钮 -->
    <button class="link-btn" onclick="window.location.href='login.php'">去登录</button>
</div>
</body>
</html>
<?php
require 'users.php';
$s = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = htmlspecialchars(trim($_POST['email'])); // 补充trim()去空格，更严谨
    $passwd = htmlspecialchars(trim($_POST['passwd']));

    if (empty($email) || empty($passwd)) {
        $s = "请输入正确内容";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // 已trim，无需重复
        $s = '请输入有效的邮箱';
    } elseif (strlen($passwd) < 6) {
        $s = '密码至少需要6个字符';
    } else {
        $pdo = getDbConnection();
        $checksql = "SELECT id , password FROM users WHERE email = :email";
        $stmtcheck = $pdo->prepare($checksql);
        $stmtcheck->execute(['email' => $email]);
        $row = $stmtcheck->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // 核心校验：新密码不能和旧密码相同
            if (password_verify($passwd, $row['password'])) {
                $s = "新密码不能与旧密码相同";
            } else {
                $upstmtdate = "UPDATE users SET password = :passwd WHERE email = :email";
                $stmtupadte = $pdo->prepare($upstmtdate);
                $result = $stmtupadte->execute([
                        'passwd' => password_hash($passwd, PASSWORD_DEFAULT),
                        'email' => $email
                ]);

                if ($result) {
                    $s = "密码重置成功";
                } else {
                    $s = "密码重置失败";
                }
            }
        } else {
            $s = "用户不存在";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>重置密码</title>
</head>

<body>
<!-- 套上重置密码容器，和注册/登录页样式统一 -->
<div class="reset-password-container">
    <h2>重置密码</h2>

    <!-- 提示信息，用统一的错误样式 -->
    <?php if(!empty($s)): ?>
        <p class="error-message"><?php echo $s; ?></p>
    <?php endif; ?>

    <!-- 表单，套上样式类 -->
    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <label class="label" for="email">邮箱：</label>
        <input type="email" name="email" id="email"
               value="<?php echo isset($email) ? $email : ""; ?>"
               class="input-field" required>

        <label class="label" for="passwd">新密码：</label>
        <input type="password" name="passwd" id="passwd"
               class="input-field" required>

        <button type="submit" class="submit-btn">提交</button>
    </form>

    <!-- 返回登录按钮，用统一的链接按钮样式 -->
    <button class="link-btn" onclick="window.location.href='login.php'">返回登录</button>
</div>
</body>
</html>
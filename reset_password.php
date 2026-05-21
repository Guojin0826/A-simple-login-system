<?php
require_once 'config.php';
require_once 'functions.php';

startSession();

$message = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    // CSRF 验证
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = "安全验证失败，请重试";
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $passwd = $_POST['passwd'] ?? '';
        $confirm_passwd = $_POST['confirm_passwd'] ?? '';

        if (empty($email) || empty($passwd) || empty($confirm_passwd)) {
            $message = "请填写所有字段";
        } elseif (!isValidEmail($email)) {
            $message = '请输入有效的邮箱地址';
        } elseif (strlen($passwd) < 6) {
            $message = '密码至少需要6个字符';
        } elseif ($passwd !== $confirm_passwd) {
            $message = '两次输入的密码不一致';
        } else {
            try {
                $pdo = getDbConnection();
                $checksql = "SELECT id, password FROM users WHERE email = :email";
                $stmtcheck = $pdo->prepare($checksql);
                $stmtcheck->execute(['email' => $email]);
                $row = $stmtcheck->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    if (password_verify($passwd, $row['password'])) {
                        $message = "新密码不能与旧密码相同";
                    } else {
                        $updateSql = "UPDATE users SET password = :passwd WHERE email = :email";
                        $stmtupdate = $pdo->prepare($updateSql);
                        $result = $stmtupdate->execute([
                            'passwd' => password_hash($passwd, PASSWORD_DEFAULT),
                            'email' => $email
                        ]);

                        if ($result) {
                            $message = "密码重置成功，请登录";
                            // 重定向到登录页
                            header('Refresh: 2; url=login.php');
                        } else {
                            $message = "密码重置失败，请重试";
                        }
                    }
                } else {
                    $message = "该邮箱未注册";
                }
            } catch (PDOException $e) {
                error_log("Password reset error: " . $e->getMessage());
                $message = "系统错误，请稍后重试";
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
    <meta name="description" content="重置密码">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>重置密码 - <?php echo APP_NAME; ?></title>
</head>

<body>
<div class="reset-password-container">
    <h2>重置密码</h2>

    <?php if(!empty($message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <label class="label" for="email">邮箱：</label>
        <input type="email" name="email" id="email"
               value="<?php echo isset($email) ? htmlspecialchars($email) : ""; ?>"
               class="input-field" required autofocus>

        <label class="label" for="passwd">新密码：</label>
        <input type="password" name="passwd" id="passwd"
               class="input-field" required minlength="6" placeholder="至少6个字符">

        <label class="label" for="confirm_passwd">确认密码：</label>
        <input type="password" name="confirm_passwd" id="confirm_passwd"
               class="input-field" required minlength="6" placeholder="再次输入新密码">

        <button type="submit" class="submit-btn">重置密码</button>
    </form>

    <button class="link-btn" onclick="window.location.href='login.php'">返回登录</button>
</div>
</body>
</html>
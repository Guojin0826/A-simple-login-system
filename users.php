<?php
/**
 * 数据库操作类
 */

require_once 'config.php';

/**
 * 获取数据库连接
 * @return PDO 数据库连接对象
 * @throws PDOException 连接失败时抛出异常
 */
function getDbConnection()
{
    try {
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=%s",
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("数据库连接失败: " . $e->getMessage());
        } else {
            die("系统错误，请联系管理员");
        }
    }
}


// 插入管理员数据
function insertAdminUser()
{
    // 获取数据库连接
    $pdo = getDbConnection();

    // 管理员的用户名、邮箱和密码
    $username = 'admin';  // 管理员用户名
    $email = 'admin@qq.com';  // 管理员邮箱
    $password = 'admin123';  // 设置管理员的原始密码

    // 使用 bcrypt 算法加密密码
    // bcrypt 是一种单向加密算法，常用于密码存储。password_hash 会生成一个加密后的密码，
    // 之后存储在数据库中，无法反向解密。这样即使数据库泄露，密码也不会被直接暴露。
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 检查管理员用户是否已存在
    $checkSql = "SELECT * FROM users WHERE username = :username";  // 查询用户名是否存在
    $stmt = $pdo->prepare($checkSql);  // 准备 SQL 语句
    $stmt->execute(['username' => $username]);  // 执行查询并传递参数（用户名）
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);  // 获取查询结果（以关联数组形式返回）

    // 如果管理员用户不存在，则插入
    if (!$existingAdmin) {
        // 插入管理员用户的 SQL 语句
        $insertSql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
        $stmt = $pdo->prepare($insertSql);  // 准备插入的 SQL 语句
        $stmt->execute([  // 执行插入操作
            'username' => $username,  // 传递用户名
            'email' => $email,  // 传递邮箱
            'password' => $hashed_password,  // 传递加密后的密码
            'role' => 'admin'  // 设置角色为管理员
        ]);
        echo "管理员用户已成功插入。\n";  // 输出成功信息
    } else {
        // 如果管理员用户已存在
        echo "管理员用户已存在。\n";
    }
}

// 调用插入管理员用户的函数（第一次调用即可）
// insertAdminUser();  // 可取消注释，实际调用时使用
?>
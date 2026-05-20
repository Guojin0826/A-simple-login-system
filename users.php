<?php
// 配置数据库连接
$host = '127.0.0.1';  // 数据库主机地址，通常是localhost，表示本地数据库
$username = 'root';   // 数据库用户名，通常是root
$password = 'root'; // 数据库密码，密码需要根据你的实际设置进行修改
$dbname = 'webuser';   // 要连接的数据库名称
$port = 3306;          // 数据库端口号，默认为 3306，若你的 MySQL 使用不同的端口，可以在这里设置

// 获取数据库连接的函数
function getDbConnection()
{
    // 引入全局变量，数据库连接的配置信息
    global $host, $username, $password, $dbname, $port;

    try {
        // 构建 PDO DSN 字符串，包含数据库主机、端口、数据库名和字符集
        // DSN（Data Source Name）是用于连接数据库的字符串，通常包含数据库类型、主机、端口、数据库名称和字符集
        // 在这里，使用的是 MySQL 数据库，字符集设置为 utf8mb4，这样可以支持更多的字符（例如表情符号）
        $des = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

        // 创建 PDO 实例，尝试连接数据库
        $pdo = new PDO($des, $username, $password);

        // 设置 PDO 的属性，这里设置的是“错误处理模式”(ERRMODE)
        // PDO::ATTR_ERRMODE：表示要设置的属性是“错误处理模式”
        // PDO::ERRMODE_EXCEPTION：表示发生数据库错误时，PDO 会抛出异常（Exception）
        //
        // 为什么需要设置为 ERRMODE_EXCEPTION？
        // 1. 如果不设置，PDO 默认只会返回 false，不会告诉你发生了什么错，你根本不知道问题在哪。
        // 2. 设置为 ERRMODE_EXCEPTION 之后，只要数据库连接、SQL 查询或执行出现问题，PHP 就会抛出异常。
        // 3. 抛出异常可以被 try...catch 捕获，这样你就能更清楚地知道错误信息（例如 SQL 语法错、表不存在、字段名写错等）。
        // 常见的三种错误模式对比：
        // 1. PDO::ERRMODE_SILENT（默认）：错误时不会报错，只会让返回值是 false
        // 2. PDO::ERRMODE_WARNING：PHP 会发出警告（warning），但不会中断代码
        // 3. PDO::ERRMODE_EXCEPTION（最推荐）：一旦有错误，PDO 会抛出异常并告诉你具体错误原因
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 如果连接成功，返回 PDO 实例
        return $pdo;
    } catch (PDOException $e) {
        // 如果连接失败，捕获异常并输出错误信息
        die("Connection failed: " . $e->getMessage());
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
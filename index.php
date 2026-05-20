<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'users.php';
$pdo = getDbConnection();
$s = '';

// 删除留言
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
    // 查询留言信息
    $sql = "SELECT * FROM messages WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($message) {
        // 管理员可以删除所有留言，普通用户只能删除自己的留言
        if ($_SESSION['role'] === 'admin' || $message['name'] === $_SESSION['username']) {
            $deleteSql = "DELETE FROM messages WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute(['id' => $message_id]);
            $s = "留言删除成功";
        } else {
            $s = "您没有权限删除此留言";
        }
    } else {
        $s = "留言不存在";
    }
}

// 提交留言
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['message']) && !isset($_POST['reply_message_id'])) {
    $message = htmlspecialchars(trim($_POST["message"]));
    if (strlen($message) === 0) {
        $s = "留言内容不能为空";
    } else {
        $sql = "INSERT INTO messages (user_id, name, message) VALUES (:user_id, :name, :message)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['username'],
            'message' => $message
        ]);
        $s = "留言成功";
    }
}

// 提交回复
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['reply_message_id'])) {
    $message_id = (int)$_POST['reply_message_id'];
    $reply_content = htmlspecialchars(trim($_POST['reply_content']));
    
    if (strlen($reply_content) === 0) {
        $s = "回复内容不能为空";
    } else {
        $sql = "INSERT INTO replies (message_id, user_id, name, reply) VALUES (:message_id, :user_id, :name, :reply)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'message_id' => $message_id,
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['username'],
            'reply' => $reply_content
        ]);
        $s = "回复成功";
    }
}

// 删除回复
if (isset($_GET['action']) && $_GET['action'] === 'delete_reply' && isset($_GET['reply_id'])) {
    $reply_id = (int)$_GET['reply_id'];
    
    // 查询回复信息
    $sql = "SELECT * FROM replies WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $reply_id]);
    $reply = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reply) {
        // 管理员可以删除所有回复，普通用户只能删除自己的回复
        if ($_SESSION['role'] === 'admin' || $reply['user_id'] == $_SESSION['user_id']) {
            $deleteSql = "DELETE FROM replies WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute(['id' => $reply_id]);
            $s = "回复删除成功";
        } else {
            $s = "您没有权限删除此回复";
        }
    } else {
        $s = "回复不存在";
    }
}

// 每页显示留言条数
$messages_per_page = 5;

// 计算总留言数
$sql = "SELECT COUNT(*) FROM messages";
$stmt = $pdo->query($sql);
$total_messages = $stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_messages / $messages_per_page);

// 获取当前页数
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
if ($page > $total_pages)
    $page = $total_pages;

// 计算偏移量 ✅ 这里只改了这一行！
$offset = max(($page - 1) * $messages_per_page, 0);

// 获取当前页的留言
$sql = "SELECT * FROM messages ORDER BY created_at DESC LIMIT :offset, :limit";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':limit', $messages_per_page, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>留言板</title>
</head>

<body>
<div class="container">
    <h2>欢迎，<?php echo htmlspecialchars($_SESSION["username"] ?? 'Guest'); ?></h2>
    <p style="color: green;"><?php echo htmlspecialchars($s); ?></p>

    <form method="post">
        <textarea name="message" placeholder="请输入留言" required class="input-field"></textarea><br>
        <input type="submit" value="提交留言" class="submit-btn">
    </form>

    <h3>留言列表</h3>
    <ul class="message-list">
        <?php foreach ($messages as $message): ?>
            <li class="message-item">
                <div class="message-header">
                    <p><strong><?php echo htmlspecialchars($message['name']); ?></strong>
                        - <?php echo htmlspecialchars($message['created_at']); ?>
                    </p>
                    <?php if ($_SESSION['role'] === 'admin' || $message['name'] === $_SESSION['username']): ?>
                        <a href="?action=delete&id=<?php echo $message['id']; ?>" 
                           class="delete-btn" 
                           onclick="return confirm('确定要删除这条留言吗？');">
                            删除
                        </a>
                    <?php endif; ?>
                </div>
                <p><?php echo htmlspecialchars($message['message']); ?></p>
                
                <!-- 回复按钮 -->
                <button class="reply-btn" onclick="toggleReplyForm(<?php echo $message['id']; ?>)">
                    回复
                </button>
                
                <!-- 回复表单 -->
                <div id="reply-form-<?php echo $message['id']; ?>" class="reply-form" style="display: none;">
                    <form method="post">
                        <input type="hidden" name="reply_message_id" value="<?php echo $message['id']; ?>">
                        <textarea name="reply_content" placeholder="请输入回复内容" required></textarea>
                        <button type="submit" class="submit-reply-btn">提交回复</button>
                        <button type="button" class="cancel-reply-btn" onclick="toggleReplyForm(<?php echo $message['id']; ?>)">取消</button>
                    </form>
                </div>
                
                <!-- 回复列表 -->
                <?php
                // 获取该留言的所有回复
                $replySql = "SELECT * FROM replies WHERE message_id = :message_id ORDER BY created_at ASC";
                $replyStmt = $pdo->prepare($replySql);
                $replyStmt->execute(['message_id' => $message['id']]);
                $replies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($replies) > 0):
                ?>
                    <div class="replies-container">
                        <?php foreach ($replies as $reply): ?>
                            <div class="reply-item">
                                <div class="reply-header">
                                    <strong><?php echo htmlspecialchars($reply['name']); ?></strong>
                                    <span class="reply-time"><?php echo htmlspecialchars($reply['created_at']); ?></span>
                                    <?php if ($_SESSION['role'] === 'admin' || $reply['user_id'] == $_SESSION['user_id']): ?>
                                        <a href="?action=delete_reply&reply_id=<?php echo $reply['id']; ?>" 
                                           class="delete-reply-btn" 
                                           onclick="return confirm('确定要删除这条回复吗？');">
                                            删除
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <p><?php echo htmlspecialchars($reply['reply']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1">&laquo; 首页</a>
            <a href="?page=<?php echo $page - 1; ?>">上一页</a>
        <?php endif; ?>

        <span>第 <?php echo $page; ?> 页 / 共 <?php echo $total_pages; ?> 页</span>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">下一页</a>
            <a href="?page=<?php echo $total_pages; ?>">末页 &raquo;</a>
        <?php endif; ?>
    </div>

    <?php if ($_SESSION["role"] == "admin"): ?>
        <a href="admin.php"><button class="link-btn">进入管理员后台</button></a>
    <?php endif; ?>

    <p>
        <a href="logout.php"><button class="link-btn">退出登录</button></a>
    </p>
</div>
<script>
function toggleReplyForm(messageId) {
    var form = document.getElementById('reply-form-' + messageId);
    if (form.style.display === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
</script>
</body>

</html>
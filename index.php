<?php
/**
 * 留言板主页
 */

require_once 'functions.php';
require_once 'users.php';

startSecureSession();

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = '请先登录';
    redirect('login.php');
}

$pdo = getDbConnection();
$message = '';

// 删除留言
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
    try {
        $sql = "SELECT * FROM messages WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $message_id]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($msg) {
            if ($_SESSION['role'] === 'admin' || $msg['user_id'] == $_SESSION['user_id']) {
                $deleteSql = "DELETE FROM messages WHERE id = :id";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute(['id' => $message_id]);
                $message = "留言删除成功";
            } else {
                $message = "您没有权限删除此留言";
            }
        } else {
            $message = "留言不存在";
        }
    } catch (PDOException $e) {
        $message = "删除失败";
    }
}

// 提交留言
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !isset($_POST['reply_message_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = '安全验证失败';
    } else {
        $msg_content = sanitizeInput($_POST['message']);
        if (strlen($msg_content) === 0) {
            $message = "留言内容不能为空";
        } elseif (strlen($msg_content) > 1000) {
            $message = "留言内容不能超过1000个字符";
        } else {
            try {
                $sql = "INSERT INTO messages (user_id, name, message, created_at) VALUES (:user_id, :name, :message, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'user_id' => $_SESSION['user_id'],
                    'name' => $_SESSION['username'],
                    'message' => $msg_content
                ]);
                $message = "留言成功";
            } catch (PDOException $e) {
                $message = "留言失败";
            }
        }
    }
}

// 提交回复
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = '安全验证失败';
    } else {
        $message_id = (int)$_POST['reply_message_id'];
        $reply_content = sanitizeInput($_POST['reply_content']);
        
        if (strlen($reply_content) === 0) {
            $message = "回复内容不能为空";
        } elseif (strlen($reply_content) > 500) {
            $message = "回复内容不能超过500个字符";
        } else {
            try {
                $sql = "INSERT INTO replies (message_id, user_id, name, reply, created_at) VALUES (:message_id, :user_id, :name, :reply, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'message_id' => $message_id,
                    'user_id' => $_SESSION['user_id'],
                    'name' => $_SESSION['username'],
                    'reply' => $reply_content
                ]);
                $message = "回复成功";
            } catch (PDOException $e) {
                $message = "回复失败";
            }
        }
    }
}

// 删除回复
if (isset($_GET['action']) && $_GET['action'] === 'delete_reply' && isset($_GET['reply_id'])) {
    $reply_id = (int)$_GET['reply_id'];
    
    try {
        $sql = "SELECT * FROM replies WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $reply_id]);
        $reply = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reply) {
            if ($_SESSION['role'] === 'admin' || $reply['user_id'] == $_SESSION['user_id']) {
                $deleteSql = "DELETE FROM replies WHERE id = :id";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute(['id' => $reply_id]);
                $message = "回复删除成功";
            } else {
                $message = "您没有权限删除此回复";
            }
        } else {
            $message = "回复不存在";
        }
    } catch (PDOException $e) {
        $message = "删除失败";
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
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="留言板">
    <title>留言板 - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="container">
    <h2>欢迎，<?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></h2>
    
    <?php if ($message): ?>
        <p class="success-message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="post" id="messageForm">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <textarea name="message" placeholder="请输入留言（最多1000字）" required class="input-field" maxlength="1000"></textarea><br>
        <small class="char-count"><span id="charCount">0</span>/1000</small>
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
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="reply_message_id" value="<?php echo $message['id']; ?>">
                        <textarea name="reply_content" placeholder="请输入回复内容（最多500字）" required maxlength="500"></textarea>
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
// 切换回复表单显示
function toggleReplyForm(messageId) {
    var form = document.getElementById('reply-form-' + messageId);
    form.style.display = (form.style.display === 'none') ? 'block' : 'none';
}

// 字符计数
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelector('textarea[name="message"]');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
});
</script>
</body>

</html>
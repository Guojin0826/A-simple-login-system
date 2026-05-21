# LJW 留言板系统

一个简单、安全的PHP留言板系统，支持用户注册、登录、留言、回复等功能。

## 功能特性

- ✅ 用户注册与登录
- ✅ 密码强度检测
- ✅ 留言发布与管理
- ✅ 留言回复功能
- ✅ 管理员后台
- ✅ 用户头像上传
- ✅ CSRF防护
- ✅ XSS防护
- ✅ SQL注入防护
- ✅ 响应式设计

## 目录结构

```
LJW/
├── config.php           # 数据库配置文件
├── functions.php        # 公共函数库
├── users.php           # 数据库操作类
├── index.php           # 首页（留言列表）
├── login.php           # 登录页面
├── register.php        # 注册页面
├── logout.php          # 退出登录
├── reset_password.php  # 重置密码
├── admin.php           # 管理员后台
├── check.php           # 权限检查
├── style.css           # 样式文件
├── password_strength.js # 密码强度检测脚本
├── .htaccess           # Apache安全配置
├── uploads/            # 上传文件目录
└── sql.txt             # 数据库结构
```

## 安装说明

### 1. 环境要求

- PHP >= 5.6
- MySQL >= 5.5
- Apache/Nginx 服务器

### 2. 安装步骤

1. **创建数据库**
   ```sql
   CREATE DATABASE ljw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **导入数据表**
   - 执行 `sql.txt` 中的SQL语句创建数据表

3. **配置数据库连接**
   - 修改 `config.php` 中的数据库连接信息：
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'ljw');
   ```

4. **设置目录权限**
   ```bash
   chmod 755 uploads/
   chmod 644 config.php
   ```

5. **访问系统**
   - 首页: `http://your-domain/index.php`
   - 登录: `http://your-domain/login.php`
   - 注册: `http://your-domain/register.php`

### 3. 默认管理员账号

首次使用需要手动在数据库中创建管理员账号，密码需使用 `password_hash()` 加密。

## 安全建议

1. **修改默认配置**
   - 修改 `config.php` 中的数据库连接信息
   - 修改 `SESSION_NAME` 为随机字符串

2. **文件权限**
   - `config.php` 设置为 644
   - `uploads/` 目录设置为 755
   - 禁止直接访问 `config.php`、`functions.php`、`users.php`

3. **定期备份**
   - 定期备份数据库
   - 定期备份 `uploads/` 目录

4. **HTTPS**
   - 建议启用 HTTPS 加密传输

## 功能说明

### 用户功能

- **注册**: 新用户注册，支持密码强度检测
- **登录**: 用户登录，支持"记住我"功能
- **留言**: 发布留言，支持HTML转义防XSS
- **回复**: 回复留言，支持删除自己的回复
- **个人中心**: 修改个人信息、头像、密码

### 管理员功能

- **用户管理**: 查看、编辑、删除用户
- **留言管理**: 查看、删除所有留言
- **回复管理**: 删除所有回复
- **权限管理**: 设置用户权限

## 技术栈

- **后端**: PHP 5.6+
- **数据库**: MySQL 5.5+
- **前端**: HTML5, CSS3, JavaScript
- **安全**: PDO预处理、CSRF Token、XSS过滤

## 更新日志

### v2.0 (2026-05-21)

- ✨ 新增配置文件，分离数据库配置
- ✨ 新增公共函数库，统一常用功能
- ✨ 新增CSRF防护机制
- ✨ 新增密码强度检测功能
- ✨ 新增密码确认验证
- ✨ 新增输入过滤和验证
- 🔒 增强SQL注入防护（使用PDO预处理）
- 🔒 增强XSS防护（统一输出转义）
- 🔒 新增.htaccess安全配置
- 🎨 优化CSS样式，新增密码强度指示器
- 📝 完善代码注释和文档
- 🐛 修复多处安全隐患

### v1.0

- 初始版本发布

## 作者

- Author: guojin
- Date: 2026-05-21

## 许可证

本项目仅供学习交流使用。

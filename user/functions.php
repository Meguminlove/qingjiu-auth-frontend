<?php
// user/functions.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务-保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 引入全局配置，它会提供 $conn 数据库连接对象
require_once __DIR__ . '/../bootstrap.php';

/**
 * 检查用户是否已登录
 * @return bool
 */
function is_user_logged_in() {
    return isset($_SESSION['user_info']);
}

/**
 * 要求用户必须登录，否则跳转到登录页
 * 并且会从数据库刷新用户信息存入SESSION
 */
function require_user_login() {
    global $conn;
    if (!is_user_logged_in() || !isset($_SESSION['user_info']['id'])) {
        header('Location: login.php');
        exit;
    }

    // [MODIFIED] 每次都从数据库刷新用户信息，确保数据最新
    $user_id = $_SESSION['user_info']['id'];
    $stmt = $conn->prepare("SELECT id, email, nickname, role, api_key, last_login_time, last_login_ip, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['user_info'] = $result->fetch_assoc();
    } else {
        // 如果在数据库找不到该用户，则强制登出
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $stmt->close();
}


/**
 * [REMOVED] 移除 api_request 函数
 * 因为我们正在将系统完全本地化，不再需要它
 */

/**
 * 渲染用户中心页面的头部
 * @param string $title 页面标题
 */
function user_center_header($title) {
    global $settings;
    require_once __DIR__ . '/header.php';
}

/**
 * 渲染用户中心页面的尾部
 */
function user_center_footer() {
    require_once __DIR__ . '/footer.php';
}
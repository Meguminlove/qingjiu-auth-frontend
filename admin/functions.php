<?php
// admin/functions.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 数据库连接 ---
function get_db_connection() {
    if (!file_exists(__DIR__ . '/../config.php')) {
        // Redirect to installation if config file is missing
        header('Location: ../install.php');
        exit;
    }
    require_once __DIR__ . '/../config.php';
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // Use a user-friendly error page instead of die()
        error_log("Database connection failed: " . $conn->connect_error);
        die("数据库连接失败，请检查配置文件或联系管理员。");
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// --- 安全与会话 ---
function is_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// --- 设置管理 ---
function get_all_settings() {
    $conn = get_db_connection();
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    $conn->close();
    return $settings;
}

function update_settings($settings_array) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    if (!$stmt) {
        $conn->close();
        return false;
    }
    foreach ($settings_array as $key => $value) {
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    $stmt->close();
    $conn->close();
    return true;
}


// --- 页面渲染 ---
function render_header($title) {
    $admin_username = escape_html($_SESSION['admin_username'] ?? 'Admin');
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>{$title} - 授权后台管理系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .sidebar-link.active {
            background-color: #3b82f6; /* bg-blue-600 */
            color: white;
        }
        .sidebar-link.active i {
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-blue-600">授权后台</h1>
            </div>
            <nav class="mt-6">
                <div class="px-6 py-2">
HTML;
    render_nav_link('index.php', 'layout-dashboard', '工作台');
    render_nav_link('settings_site.php', 'settings', '网站设置');
    render_nav_link('settings_api.php', 'key-round', 'API设置');
    render_nav_link('settings_version.php', 'git-branch-plus', '版本设置');
    render_nav_link('settings_smtp.php', 'mail', '邮箱设置');
    echo <<<HTML
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm z-10">
               <div class="flex items-center justify-between px-6 h-16">
                    <div>
                         <h2 class="text-xl font-semibold text-gray-700">{$title}</h2>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">欢迎, {$admin_username}</span>
                        <div class="w-px h-6 bg-gray-200"></div>
                        <a href="logout.php" title="退出登录" class="text-gray-500 hover:text-red-600">
                            <i data-lucide="log-out" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>
            </header>
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="max-w-7xl mx-auto">
HTML;
}

function render_footer() {
    echo <<<HTML
                </div>
            </main>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
HTML;
}

function render_nav_link($href, $icon, $text) {
    $current_page = basename($_SERVER['PHP_SELF']);
    $active_class = ($current_page == $href) ? 'active' : '';
    echo <<<HTML
    <a href="{$href}" class="sidebar-link flex items-center px-4 py-2.5 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors {$active_class}">
        <i data-lucide="{$icon}" class="w-5 h-5 mr-3 text-gray-500"></i>
        <span>{$text}</span>
    </a>
HTML;
}


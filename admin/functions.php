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
        header('Location: ../install.php');
        exit;
    }
    require_once __DIR__ . '/../config.php';
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// --- 安全函数 ---
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

function update_settings($settings_data) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    
    foreach ($settings_data as $key => $value) {
        $stmt->bind_param('ss', $value, $key);
        $stmt->execute();
    }
    
    $stmt->close();
    $conn->close();
    return true;
}

// --- [美化更新] 页面模板 ---
function render_header($title) {
    $admin_username = escape_html($_SESSION['admin_username'] ?? 'Admin');
    echo <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - 授权后台管理系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .sidebar-link.active {
            background-color: #f3f4f6;
            color: #1f2937;
            font-weight: 600;
        }
        .sidebar-link:hover {
             background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-60 bg-white border-r border-gray-200 flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200">
                <a href="index.php" class="flex items-center space-x-2">
                    <i data-lucide="shield-check" class="text-blue-600 h-7 w-7"></i>
                    <h1 class="text-lg font-bold text-gray-800">授权管理系统</h1>
                </a>
            </div>
            <nav class="flex-1 mt-4 px-4">
HTML;
    render_nav_link('index.php', 'layout-dashboard', '工作台');
    render_nav_link('settings_site.php', 'settings', '网站设置');
    render_nav_link('settings_api.php', 'key-round', 'API设置');
    render_nav_link('settings_version.php', 'git-branch-plus', '程序版本设置');
    echo <<<HTML
            </nav>
        </div>
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Topbar -->
            <header class="bg-white border-b border-gray-200">
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
    <a href="{$href}" class="sidebar-link flex items-center px-4 py-2.5 text-sm text-gray-600 rounded-lg transition-colors duration-200 {$active_class}">
        <i data-lucide="{$icon}" class="w-5 h-5"></i>
        <span class="ml-3">{$text}</span>
    </a>
HTML;
}
?>


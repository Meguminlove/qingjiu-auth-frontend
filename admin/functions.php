<?php
// admin/functions.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务-保留原作者的版权信息。
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
    
    // 移除错误抑制符，以便更好地处理错误
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
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
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - 授权后台管理系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <style>
        .sidebar-link.active {
            background-color: #3b82f6; /* bg-blue-600 */
            color: white;
        }
        .sidebar-link.active i {
            color: white;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    <div x-data="{ sidebarOpen: false }" @keydown.window.escape="sidebarOpen = false" class="flex h-screen bg-gray-100 font-sans">
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 flex md:hidden" x-cloak>
            <div @click="sidebarOpen = false" class="fixed inset-0 bg-gray-600 opacity-75" aria-hidden="true"></div>
            
            <!-- Sidebar -->
            <aside class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button type="button" @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Close sidebar</span>
                        <i data-lucide="x" class="h-6 w-6 text-white"></i>
                    </button>
                </div>
                <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                    <div class="flex-shrink-0 flex items-center px-4">
                        <h1 class="text-2xl font-bold text-blue-600">授权后台</h1>
                    </div>
                    <nav class="mt-5 px-2 space-y-1">
HTML;
    render_nav_link('index.php', 'layout-dashboard', '工作台');
    render_nav_link('settings_site.php', 'settings', '网站设置');
    render_nav_link('settings_api.php', 'key-round', 'API设置');
    render_nav_link('settings_version.php', 'git-branch-plus', '版本设置');
    render_nav_link('settings_smtp.php', 'mail', '邮箱设置');
    echo <<<HTML
                    </nav>
                </div>
                 <div class="flex-shrink-0 p-4 border-t">
                    <div class="flex items-center">
                        <div>
                            <img class="inline-block h-10 w-10 rounded-full" src="https://blog.mofuc.cn/wp-content/uploads/2024/04/2024042805404112-scaled.jpeg" alt="作者头像">
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">小奏</p>
                            <p class="text-xs text-gray-500">作者</p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-around">
                        <a href="https://blog.mofuc.cn/" target="_blank" class="text-gray-500 hover:text-blue-600 flex flex-col items-center">
                            <i data-lucide="book-open" class="w-5 h-5"></i>
                            <span class="text-xs mt-1">博客</span>
                        </a>
                        <a href="https://space.bilibili.com/63216596" target="_blank" class="text-gray-500 hover:text-blue-600 flex flex-col items-center">
                            <i data-lucide="tv" class="w-5 h-5"></i>
                            <span class="text-xs mt-1">B站</span>
                        </a>
                        <a href="https://github.com/Meguminlove" target="_blank" class="text-gray-500 hover:text-blue-600 flex flex-col items-center">
                            <i data-lucide="github" class="w-5 h-5"></i>
                             <span class="text-xs mt-1">GitHub</span>
                        </a>
                    </div>
                </div>
            </aside>
            <div class="flex-shrink-0 w-14" aria-hidden="true"></div>
        </div>

        <!-- Static sidebar for desktop -->
        <aside class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64">
                <div class="flex flex-col h-0 flex-1 bg-white shadow-md">
                     <div class="flex items-center h-16 flex-shrink-0 px-4">
                        <h1 class="text-2xl font-bold text-blue-600">授权后台</h1>
                     </div>
                     <div class="flex-1 flex flex-col overflow-y-auto">
                        <nav class="flex-1 px-2 py-4 space-y-1">
HTML;
    render_nav_link('index.php', 'layout-dashboard', '工作台');
    render_nav_link('settings_site.php', 'settings', '网站设置');
    render_nav_link('settings_api.php', 'key-round', 'API设置');
    render_nav_link('settings_version.php', 'git-branch-plus', '版本设置');
    render_nav_link('settings_smtp.php', 'mail', '邮箱设置');
    echo <<<HTML
                        </nav>
                         <div class="flex-shrink-0 p-4 border-t">
                            <div class="flex items-center">
                                <div>
                                    <img class="inline-block h-10 w-10 rounded-full" src="http://q2.qlogo.cn/headimg_dl?dst_uin=1421733942&spec=100" alt="作者头像">
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-700">小奏</p>
                                    <p class="text-xs text-gray-500">作者</p>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-around">
                                <a href="https://blog.mofuc.cn/" target="_blank" class="text-gray-500 hover:text-blue-600 flex flex-col items-center p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                                    <i data-lucide="book-open" class="w-5 h-5"></i>
                                    <span class="text-xs mt-1">博客</span>
                                </a>
                                <a href="https://space.bilibili.com/63216596" target="_blank" class="text-gray-500 hover:text-blue-600 flex flex-col items-center p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                                    <i data-lucide="tv" class="w-5 h-5"></i>
                                    <span class="text-xs mt-1">B站</span>
                                </a>
                                <a href="https://github.com/Meguminlove" target="_blank" class="text-gray-500 hover:text-blue-600 flex flex-col items-center p-2 rounded-lg transition-colors duration-200 hover:bg-gray-100">
                                    <i data-lucide="github" class="w-5 h-5"></i>
                                     <span class="text-xs mt-1">GitHub</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <header class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
                <button type="button" @click.stop="sidebarOpen = true" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 md:hidden">
                    <span class="sr-only">Open sidebar</span>
                    <i data-lucide="menu" class="h-6 w-6"></i>
                </button>
                <div class="flex-1 px-4 sm:px-6 md:px-8 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-700">{$title}</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600 hidden sm:block">欢迎, {$admin_username}</span>
                        <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>
                        <a href="logout.php" title="退出登录" class="text-gray-500 hover:text-red-600">
                            <i data-lucide="log-out" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>
            </header>
            <main class="flex-1 relative overflow-y-auto focus:outline-none p-4 sm:p-6">
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
?>


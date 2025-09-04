<?php
// header.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

// 函数：渲染顶部导航链接 (PC端)
function render_nav_link($href, $icon, $text, $current_page) {
    $active_class = ($current_page === $href) ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100';
    echo <<<HTML
    <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 {$active_class}" href="./{$href}">
        <i data-lucide="{$icon}" class="inline-block w-4 h-4 mr-1"></i>{$text}
    </a>
HTML;
}

// 函数：渲染底部导航链接 (移动端)
function render_bottom_nav_link($href, $icon, $text, $current_page) {
    $active_class = ($current_page === $href) ? 'text-blue-600' : 'text-gray-500';
    echo <<<HTML
    <a href="./{$href}" class="flex flex-col items-center justify-center flex-1 pt-2 pb-1 text-center transition-colors duration-200 {$active_class} hover:text-blue-500">
        <i data-lucide="{$icon}" class="w-6 h-6 mb-1"></i>
        <span class="text-xs tracking-tight">{$text}</span>
    </a>
HTML;
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? '授权系统'); ?> - <?php echo htmlspecialchars($settings['site_name'] ?? '小奏授权'); ?></title>
    <meta name="keywords" content="<?php echo htmlspecialchars($settings['site_keywords'] ?? ''); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_description'] ?? ''); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #f7fafc; }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="container mx-auto max-w-4xl p-4">
        <!-- 顶部导航栏在手机端隐藏 -->
        <header class="bg-white rounded-lg shadow-md p-2 mb-6 hidden md:block">
            <nav class="flex flex-wrap items-center justify-center gap-2">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                
                // [新增] 首页选项
                render_nav_link('index.php', 'home', '首页', $current_page);
                render_nav_link('query.php', 'search', '授权查询', $current_page);
                render_nav_link('domain_manager.php', 'replace', '更换授权', $current_page);
                render_nav_link('activate.php', 'user-check', '自助授权', $current_page);
                render_nav_link('key_query.php', 'key-round', '密钥查询', $current_page);
                render_nav_link('auth.php', 'message-circle', '联系客服', $current_page);
                render_nav_link('download.php', 'download', '程序下载', $current_page);
                ?>
            </nav>
        </header>

        <!-- 为主内容区域在手机端增加底部内边距，防止被导航栏遮挡 -->
        <main class="pb-20 md:pb-0">


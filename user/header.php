<?php
// user/header.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
$current_page = basename($_SERVER['PHP_SELF']);
$user_nickname = $_SESSION['user_info']['nickname'] ?? '用户';

function render_user_nav_link($href, $icon, $text, $current_page) {
    $active_class = ($current_page === $href) ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
    echo <<<HTML
    <a href="{$href}" class="group flex items-center px-3 py-2 text-sm font-medium rounded-md {$active_class}">
        <i data-lucide="{$icon}" class="mr-3 h-5 w-5"></i>
        {$text}
    </a>
HTML;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? '用户中心'); ?> - <?php echo htmlspecialchars($settings['site_name'] ?? '授权系统'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="../index.php" class="flex-shrink-0 flex items-center">
                             <i data-lucide="shield-check" class="h-8 w-auto text-blue-600"></i>
                             <span class="ml-2 text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($settings['site_name'] ?? '授权系统'); ?></span>
                        </a>
                    </div>
                    <div class="flex items-center">
                         <span class="text-sm text-gray-600 mr-4">欢迎, <?php echo htmlspecialchars($user_nickname); ?></span>
                        <a href="logout.php" class="text-sm font-medium text-gray-500 hover:text-gray-700">登出</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                <div class="lg:col-span-3">
                    <nav class="space-y-1 bg-white p-4 rounded-lg shadow-sm">
                        <?php
                        render_user_nav_link('index.php', 'layout-dashboard', '仪表盘', $current_page);
                        // 未来可以在这里添加个人资料等链接
                        ?>
                    </nav>
                </div>
                <main class="lg:col-span-9 mt-6 lg:mt-0">

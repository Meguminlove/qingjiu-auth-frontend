<?php
// admin/index.php
require_once 'functions.php';
require_login();

$settings = get_all_settings();

// 获取服务器信息
$php_version = PHP_VERSION;
$db_connection = get_db_connection();
$mysql_version = $db_connection->server_info;
$db_connection->close();
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? '未知';

render_header('工作台');
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Card 1: API URL -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4 overflow-hidden">
        <div class="bg-blue-100 p-3 rounded-full shrink-0">
            <i data-lucide="api" class="w-6 h-6 text-blue-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-gray-500">当前API接口</p>
            <p class="text-lg font-semibold text-gray-800 truncate" title="<?php echo escape_html($settings['api_url'] ?? '未设置'); ?>"><?php echo escape_html($settings['api_url'] ?? '未设置'); ?></p>
        </div>
    </div>
    <!-- Card 2: API KEY -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4 overflow-hidden">
        <div class="bg-green-100 p-3 rounded-full shrink-0">
            <i data-lucide="key-round" class="w-6 h-6 text-green-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-gray-500">API KEY</p>
            <p class="text-lg font-semibold text-gray-800"><?php echo !empty($settings['api_key']) ? '已配置' : '未配置'; ?></p>
        </div>
    </div>
    <!-- Card 3: 程序版本 -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4 overflow-hidden">
        <div class="bg-yellow-100 p-3 rounded-full shrink-0">
            <i data-lucide="git-branch-plus" class="w-6 h-6 text-yellow-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-gray-500">程序版本</p>
            <p class="text-lg font-semibold text-gray-800 truncate" title="<?php echo escape_html($settings['update_version'] ?? '未设置'); ?>"><?php echo escape_html($settings['update_version'] ?? '未设置'); ?></p>
        </div>
    </div>
    <!-- Card 4: 下载地址 -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4 overflow-hidden">
        <div class="bg-purple-100 p-3 rounded-full shrink-0">
            <i data-lucide="download-cloud" class="w-6 h-6 text-purple-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-sm text-gray-500">下载地址</p>
            <p class="text-lg font-semibold text-gray-800"><?php echo !empty($settings['download_url']) ? '已设置' : '未设置'; ?></p>
        </div>
    </div>
</div>

<div class="mt-8 bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">系统信息</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
        <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">网站名称:</span>
            <span class="font-medium text-gray-800 text-right truncate" title="<?php echo escape_html($settings['site_name'] ?? '未设置'); ?>"><?php echo escape_html($settings['site_name'] ?? '未设置'); ?></span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">服务器软件:</span>
            <span class="font-medium text-gray-800 text-right truncate" title="<?php echo escape_html($server_software); ?>"><?php echo escape_html($server_software); ?></span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">PHP 版本:</span>
            <span class="font-medium text-gray-800 text-right truncate"><?php echo escape_html($php_version); ?></span>
        </div>
        <div class="flex justify-between py-2 border-b">
            <span class="text-gray-600">MySQL 版本:</span>
            <span class="font-medium text-gray-800 text-right truncate"><?php echo escape_html($mysql_version); ?></span>
        </div>
    </div>
</div>

<div class="mt-8 bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">常用操作</h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 text-center">
        <a href="settings_site.php" class="block bg-gray-50 hover:bg-gray-100 p-4 rounded-lg transition-colors">
            <i data-lucide="settings" class="w-8 h-8 mx-auto text-gray-600"></i>
            <p class="mt-2 text-sm font-medium text-gray-700">网站设置</p>
        </a>
        <a href="settings_api.php" class="block bg-gray-50 hover:bg-gray-100 p-4 rounded-lg transition-colors">
            <i data-lucide="key-round" class="w-8 h-8 mx-auto text-gray-600"></i>
            <p class="mt-2 text-sm font-medium text-gray-700">API设置</p>
        </a>
        <a href="settings_version.php" class="block bg-gray-50 hover:bg-gray-100 p-4 rounded-lg transition-colors">
            <i data-lucide="git-branch-plus" class="w-8 h-8 mx-auto text-gray-600"></i>
            <p class="mt-2 text-sm font-medium text-gray-700">版本设置</p>
        </a>
    </div>
</div>


<?php
render_footer();
?>


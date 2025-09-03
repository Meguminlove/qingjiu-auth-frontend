<?php
// admin/settings_site.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
require_once 'functions.php';
require_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 更新了要保存的字段
    $settings_to_update = [
        'site_name' => $_POST['site_name'] ?? '',
        'site_keywords' => $_POST['site_keywords'] ?? '',
        'site_description' => $_POST['site_description'] ?? '',
        'query_product_id' => $_POST['query_product_id'] ?? '1', // 新增: 查询产品ID
        'system_announcement' => $_POST['system_announcement'] ?? '', // 新增: 网站公告
        'site_icp' => $_POST['site_icp'] ?? '',
        'site_footer' => $_POST['site_footer'] ?? ''
    ];

    if (update_settings($settings_to_update)) {
        $message = '网站设置已成功更新！';
    } else {
        $message = '更新失败，请重试。';
    }
}

$settings = get_all_settings();

render_header('网站设置');
?>

<?php if ($message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
    <span class="block sm:inline"><?php echo escape_html($message); ?></span>
</div>
<?php endif; ?>

<div class="bg-white p-6 rounded-lg shadow-md">
    <form action="settings_site.php" method="POST">
        <div class="space-y-6">
            <div>
                <label for="site_name" class="block text-sm font-medium text-gray-700">网站名称</label>
                <input type="text" id="site_name" name="site_name" value="<?php echo escape_html($settings['site_name'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="site_keywords" class="block text-sm font-medium text-gray-700">关键字</label>
                <input type="text" id="site_keywords" name="site_keywords" value="<?php echo escape_html($settings['site_keywords'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div>
                <label for="site_description" class="block text-sm font-medium text-gray-700">网站介绍</label>
                <textarea id="site_description" name="site_description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo escape_html($settings['site_description'] ?? ''); ?></textarea>
            </div>

            <!-- [MODIFIED] 字段已修改 -->
            <div>
                <label for="query_product_id" class="block text-sm font-medium text-gray-700">查询产品ID</label>
                <input type="text" id="query_product_id" name="query_product_id" value="<?php echo escape_html($settings['query_product_id'] ?? '1'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <p class="mt-2 text-xs text-gray-500">前端查询页面默认使用的产品ID。</p>
            </div>

            <div>
                <label for="system_announcement" class="block text-sm font-medium text-gray-700">网站公告</label>
                <textarea id="system_announcement" name="system_announcement" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo escape_html($settings['system_announcement'] ?? ''); ?></textarea>
                <p class="mt-2 text-xs text-gray-500">将显示在前端所有页面的公告区域。</p>
            </div>
            
            <div>
                <label for="site_icp" class="block text-sm font-medium text-gray-700">网站备案号</label>
                <input type="text" id="site_icp" name="site_icp" value="<?php echo escape_html($settings['site_icp'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            
            <div>
                <label for="site_footer" class="block text-sm font-medium text-gray-700">底部版权设置</label>
                <input type="text" id="site_footer" name="site_footer" value="<?php echo escape_html($settings['site_footer'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            
        </div>

        <div class="mt-8 border-t pt-5">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline">
                保存设置
            </button>
        </div>
    </form>
</div>

<?php
render_footer();
?>

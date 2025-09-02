<?php
// admin/settings_version.php
require_once 'functions.php';
require_login();

$message = '';
$message_type = 'green';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_to_update = [
        'update_version' => $_POST['update_version'] ?? '',
        'download_url' => $_POST['download_url'] ?? '',
        'update_log' => $_POST['update_log'] ?? ''
    ];

    if (update_settings($settings_to_update)) {
        $message = '程序版本设置已成功更新！';
    } else {
        $message = '更新失败，请重试。';
        $message_type = 'red';
    }
}

$settings = get_all_settings();

render_header('程序版本设置');
?>

<?php if ($message): ?>
<div class="bg-<?php echo $message_type; ?>-100 border border-<?php echo $message_type; ?>-400 text-<?php echo $message_type; ?>-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
    <span class="block sm:inline"><?php echo escape_html($message); ?></span>
</div>
<?php endif; ?>

<div class="bg-white p-8 rounded-lg shadow-md">
    <form action="settings_version.php" method="POST">
        <div class="space-y-8">
            <div>
                <label for="update_version" class="block text-sm font-medium text-gray-700">最新版本号</label>
                <input type="text" id="update_version" name="update_version" value="<?php echo escape_html($settings['update_version'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="例如：v1.4.0">
                <p class="mt-2 text-xs text-gray-500">将显示在下载页面的版本信息。</p>
            </div>

            <div>
                <label for="download_url" class="block text-sm font-medium text-gray-700">下载地址</label>
                <input type="text" id="download_url" name="download_url" value="<?php echo escape_html($settings['download_url'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="例如：https://gitee.com/user/repo/releases/download/v1.0/file.zip">
                <p class="mt-2 text-xs text-gray-500">用户在验证卡密后，将从此地址下载程序。</p>
            </div>
            
            <div>
                <label for="update_log" class="block text-sm font-medium text-gray-700">更新日志</label>
                <textarea id="update_log" name="update_log" rows="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono" placeholder="请填写本次更新的内容..."><?php echo escape_html($settings['update_log'] ?? ''); ?></textarea>
                <p class="mt-2 text-xs text-gray-500">将显示在下载页面的“查看更新内容”区域。</p>
            </div>
        </div>

        <div class="mt-8 border-t pt-6 flex justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors">
                保存设置
            </button>
        </div>
    </form>
</div>

<?php
render_footer();
?>


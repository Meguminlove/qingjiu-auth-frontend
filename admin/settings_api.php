<?php
// admin/settings_api.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
require_once 'functions.php';
require_login();

$message = '';
$message_type = 'green'; // 'green' for success, 'red' for error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_to_update = [
        'api_url' => $_POST['api_url'] ?? '',
        'api_key' => $_POST['api_key'] ?? '',
        'customer_service_qq' => $_POST['customer_service_qq'] ?? ''
    ];

    // 处理文件上传
    if (isset($_FILES['wechat_qrcode']) && $_FILES['wechat_qrcode']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['wechat_qrcode']['type'], $allowed_types)) {
            $file_extension = pathinfo($_FILES['wechat_qrcode']['name'], PATHINFO_EXTENSION);
            $new_filename = 'wechat_qrcode.' . $file_extension;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['wechat_qrcode']['tmp_name'], $destination)) {
                // 将相对路径存入数据库
                $settings_to_update['wechat_qrcode_url'] = 'uploads/' . $new_filename;
            } else {
                $message = '文件移动失败，请检查uploads目录权限。';
                $message_type = 'red';
            }
        } else {
            $message = '无效的文件类型。只允许上传 JPG, PNG, GIF 格式的图片。';
            $message_type = 'red';
        }
    }

    if (empty($message)) {
        if (update_settings($settings_to_update)) {
            $message = 'API设置已成功更新！';
            $message_type = 'green';
        } else {
            $message = '更新失败，请重试。';
            $message_type = 'red';
        }
    }
}

$settings = get_all_settings();

render_header('API设置');
?>

<?php if ($message): ?>
<div class="bg-<?php echo $message_type; ?>-100 border border-<?php echo $message_type; ?>-400 text-<?php echo $message_type; ?>-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
    <span class="block sm:inline"><?php echo escape_html($message); ?></span>
</div>
<?php endif; ?>

<div class="bg-white p-8 rounded-lg shadow-md">
    <form action="settings_api.php" method="POST" enctype="multipart/form-data">
        <div class="space-y-8">
            <div>
                <label for="api_url" class="block text-sm font-medium text-gray-700">API接口选项</label>
                <input type="text" id="api_url" name="api_url" value="<?php echo escape_html($settings['api_url'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <p class="mt-2 text-xs text-gray-500">您的授权系统后端API地址。</p>
            </div>

            <div>
                <label for="api_key" class="block text-sm font-medium text-gray-700">API KEY</label>
                <input type="text" id="api_key" name="api_key" value="<?php echo escape_html($settings['api_key'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                 <p class="mt-2 text-xs text-gray-500">用于和API服务器通信的密钥。</p>
            </div>
            
            <div class="border-t pt-8">
                 <label for="customer_service_qq" class="block text-sm font-medium text-gray-700">客服QQ</label>
                <input type="text" id="customer_service_qq" name="customer_service_qq" value="<?php echo escape_html($settings['customer_service_qq'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <p class="mt-2 text-xs text-gray-500">将显示在“联系客服”页面的客服QQ号。</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">微信二维码</label>
                <div class="mt-2 flex items-center space-x-6">
                    <div class="shrink-0">
                        <?php if (!empty($settings['wechat_qrcode_url']) && file_exists(__DIR__ . '/../' . $settings['wechat_qrcode_url'])): ?>
                            <img id="qrcode_preview" class="h-20 w-20 object-cover rounded-md" src="../<?php echo escape_html($settings['wechat_qrcode_url']); ?>?t=<?php echo time(); ?>" alt="当前二维码">
                        <?php else: ?>
                             <img id="qrcode_preview" class="h-20 w-20 object-cover rounded-md bg-gray-100" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" alt="暂无图片">
                        <?php endif; ?>
                    </div>
                    <label class="block">
                        <span class="sr-only">选择文件</span>
                        <input type="file" id="wechat_qrcode" name="wechat_qrcode" onchange="document.getElementById('qrcode_preview').src = window.URL.createObjectURL(this.files[0])" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                    </label>
                </div>
                <p class="mt-2 text-xs text-gray-500">上传新的二维码图片将会覆盖旧的。将显示在“联系客服”页面。</p>
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


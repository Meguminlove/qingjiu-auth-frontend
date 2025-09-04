<?php
// admin/settings_smtp.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

require_once 'functions.php';
require_login();

$message = '';
$message_type = 'green';
$settings = get_all_settings();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save settings
    if (isset($_POST['save_settings'])) {
        $settings_to_update = [
            'smtp_auth_method' => $_POST['smtp_auth_method'] ?? 'smtp',
            'smtp_secure' => $_POST['smtp_secure'] ?? 'tls',
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '',
            'pop3_host' => $_POST['pop3_host'] ?? '',
            'pop3_port' => $_POST['pop3_port'] ?? '110',
            'smtp_user' => $_POST['smtp_user'] ?? '',
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
            'smtp_from_name' => $_POST['smtp_from_name'] ?? ''
        ];

        if (update_settings($settings_to_update)) {
            $message = 'SMTP设置已成功保存！';
            $message_type = 'green';
            $settings = get_all_settings(); // Reload settings
        } else {
            $message = '保存失败，请重试。';
            $message_type = 'red';
        }
    }
    // Send test email
    elseif (isset($_POST['send_test_email'])) {
        // Include the mailer functions only when this action is triggered
        require_once __DIR__ . '/../api/mailer_functions.php';
        
        $test_email_to = $_POST['test_email_to'] ?? '';
        if (filter_var($test_email_to, FILTER_VALIDATE_EMAIL)) {
            $subject = '这是一封来自授权系统的测试邮件';
            $body = '您好！<br><br>如果您收到了这封邮件，说明您的邮件配置已生效。<br><br>发送时间: ' . date('Y-m-d H:i:s');
            
            // Call the unified email sending function
            $send_result = send_email($settings, $test_email_to, $subject, $body);

            if ($send_result === true) {
                $message = "测试邮件已成功发送至 " . htmlspecialchars($test_email_to);
                $message_type = 'green';
            } else {
                $message = "测试邮件发送失败！<br><strong>错误详情:</strong> " . $send_result; // Error message is returned directly
                $message_type = 'red';
            }
        } else {
            $message = '请输入一个有效的测试收件箱地址。';
            $message_type = 'red';
        }
    }
}

render_header('邮箱设置');
?>

<?php if ($message): ?>
<div class="bg-<?php echo $message_type; ?>-100 border border-<?php echo $message_type; ?>-400 text-<?php echo $message_type; ?>-700 px-4 py-3 rounded-lg relative mb-6" role="alert">
    <span><?php echo $message; // Allow HTML for error details ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- SMTP Settings Form -->
    <div class="lg:col-span-2 bg-white p-8 rounded-lg shadow-md">
        <form action="settings_smtp.php" method="POST">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-6">邮件服务器配置</h3>
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="smtp_auth_method" class="block text-sm font-medium text-gray-700">发送方式</label>
                        <select id="smtp_auth_method" name="smtp_auth_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="smtp" <?php echo ($settings['smtp_auth_method'] ?? 'smtp') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                            <option value="pop-before-smtp" <?php echo ($settings['smtp_auth_method'] ?? '') === 'pop-before-smtp' ? 'selected' : ''; ?>>POP3</option>
                        </select>
                    </div>
                     <div>
                        <label for="smtp_secure" class="block text-sm font-medium text-gray-700">加密方式</label>
                        <select id="smtp_secure" name="smtp_secure" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="tls" <?php echo ($settings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo ($settings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="smtp_host" class="block text-sm font-medium text-gray-700">SMTP 主机</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo escape_html($settings['smtp_host'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="例如：smtp.qq.com">
                    </div>
                    <div>
                        <label for="smtp_port" class="block text-sm font-medium text-gray-700">SMTP 端口</label>
                        <input type="text" id="smtp_port" name="smtp_port" value="<?php echo escape_html($settings['smtp_port'] ?? '465'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="例如：465 或 587">
                    </div>
                </div>
                <div id="pop3_settings" class="space-y-6" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pop3_host" class="block text-sm font-medium text-gray-700">POP3 主机</label>
                            <input type="text" id="pop3_host" name="pop3_host" value="<?php echo escape_html($settings['pop3_host'] ?? ($settings['smtp_host'] ?? '')); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="通常与SMTP主机相同">
                        </div>
                        <div>
                            <label for="pop3_port" class="block text-sm font-medium text-gray-700">POP3 端口</label>
                            <input type="text" id="pop3_port" name="pop3_port" value="<?php echo escape_html($settings['pop3_port'] ?? '110'); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="默认: 110">
                        </div>
                    </div>
                </div>
                 <div>
                    <label for="smtp_user" class="block text-sm font-medium text-gray-700">发信账号</label>
                    <input type="text" id="smtp_user" name="smtp_user" value="<?php echo escape_html($settings['smtp_user'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="您的邮箱地址">
                </div>
                <div>
                    <label for="smtp_pass" class="block text-sm font-medium text-gray-700">授权码/密码</label>
                    <input type="password" id="smtp_pass" name="smtp_pass" value="<?php echo escape_html($settings['smtp_pass'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="QQ邮箱请填写授权码">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="smtp_from_email" class="block text-sm font-medium text-gray-700">发件人邮箱</label>
                        <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo escape_html($settings['smtp_from_email'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="显示在邮件中的发件人地址">
                    </div>
                    <div>
                        <label for="smtp_from_name" class="block text-sm font-medium text-gray-700">发件人名称</label>
                        <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo escape_html($settings['smtp_from_name'] ?? ''); ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="例如：授权系统">
                    </div>
                </div>
            </div>
            <div class="mt-8 border-t pt-6 flex justify-end">
                <button type="submit" name="save_settings" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
                    保存设置
                </button>
            </div>
        </form>
    </div>

    <!-- Test Email Form -->
    <div class="bg-white p-8 rounded-lg shadow-md h-fit">
         <form action="settings_smtp.php" method="POST">
            <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-6">发送测试邮件</h3>
            <div class="space-y-4">
                <div>
                    <label for="test_email_to" class="block text-sm font-medium text-gray-700">收件邮箱</label>
                    <input type="email" id="test_email_to" name="test_email_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="请输入收件地址" required>
                </div>
                <div class="pt-2">
                    <button type="submit" name="send_test_email" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg">
                        发送测试
                    </button>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500">请先保存设置，再发送测试邮件。此功能用于验证您的配置是否正确。</p>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const authMethodSelect = document.getElementById('smtp_auth_method');
        const pop3SettingsDiv = document.getElementById('pop3_settings');

        function togglePop3Settings() {
            if (authMethodSelect.value === 'pop-before-smtp') {
                pop3SettingsDiv.style.display = 'block';
            } else {
                pop3SettingsDiv.style.display = 'none';
            }
        }

        // Initial check on page load
        togglePop3Settings();

        // Listen for future changes
        authMethodSelect.addEventListener('change', togglePop3Settings);
    });
</script>

<?php
render_footer();
?>


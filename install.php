<?php
// install.php (Fixed Version)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
// --- 步骤管理 ---
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';

// --- [FIXED] 安全检查：仅当访问安装步骤(1-3)且系统已安装时才阻止 ---
if (file_exists('config.php') && $step !== 4) {
    header('HTTP/1.1 403 Forbidden');
    die('<!DOCTYPE html><html><head><title>错误</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 flex items-center justify-center h-screen"><div class="text-center"><h1 class="text-2xl font-bold text-red-600">系统已经安装！</h1><p class="text-gray-600 mt-2">如果您需要重新安装，请先手动删除网站根目录下的 <strong>config.php</strong> 文件。</p></div></body></html>');
}

// --- 核心安装逻辑 (仅在第3步提交时触发) ---
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';

    if (empty($db_host) || empty($db_user) || empty($db_name) || empty($admin_user) || empty($admin_pass)) {
        $error = '所有字段均为必填项，请填写完整。';
    } else {
        $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            $error = '数据库连接失败，请检查您的数据库信息是否正确。错误: ' . htmlspecialchars($conn->connect_error);
        } else {
            $config_content = "<?php\n";
            $config_content .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
            $config_content .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
            $config_content .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n";
            $config_content .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";

            if (!@file_put_contents('config.php', $config_content)) {
                 $error = '无法创建配置文件 config.php，请检查目录是否具有写入权限。';
            } else {
                $conn->set_charset('utf8mb4');

                // [FIXED] Added UNIQUE KEY to username to prevent duplicates
                $sql_admins = "CREATE TABLE IF NOT EXISTS `admins` (`id` int(11) NOT NULL AUTO_INCREMENT, `username` varchar(50) NOT NULL, `password` varchar(255) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `uniq_username` (`username`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $sql_settings = "CREATE TABLE IF NOT EXISTS `settings` (`setting_key` varchar(50) NOT NULL, `setting_value` text, PRIMARY KEY (`setting_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $sql_local_auths = "CREATE TABLE IF NOT EXISTS `local_authorizations` (`id` int(11) NOT NULL AUTO_INCREMENT, `product_id` int(11) NOT NULL, `auth_domain` varchar(255) NOT NULL, `auth_email` varchar(255) NOT NULL, `license_key` varchar(255) NOT NULL, `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `uniq_auth` (`product_id`,`auth_domain`,`auth_email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

                if ($conn->query($sql_admins) && $conn->query($sql_settings) && $conn->query($sql_local_auths)) {
                    $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
                    // [FIXED] Changed from INSERT to REPLACE to handle re-installation gracefully
                    $stmt_admin = $conn->prepare("REPLACE INTO `admins` (username, password) VALUES (?, ?)");
                    $stmt_admin->bind_param("ss", $admin_user, $hashed_password);
                    $stmt_admin->execute();
                    $stmt_admin->close();
                    
                    $default_settings = [
                        'site_name' => '小奏授权查询系统', 
                        'site_keywords' => '授权查询, 源码授权', 
                        'site_description' => '一个轻量、高效的授权查询系统',
                        'site_icp' => '', 
                        'site_footer' => '© ' . date('Y') . ' 小奏授权查询系统. All Rights Reserved.',
                        'api_url' => 'https://www.79tian.com/api/v1', 
                        'api_key' => '', 
                        'wechat_qrcode_url' => '', 
                        'customer_service_qq' => '',
                        'download_url' => '', 
                        'update_version' => 'v1.0.0', 
                        'update_log' => '初始版本发布。', 
                        'system_announcement' => '欢迎使用本系统！',
                        'query_product_id' => '1',
                        'smtp_host' => '',
                        'smtp_port' => '587',
                        'smtp_secure' => 'tls',
                        'smtp_user' => '',
                        'smtp_pass' => '',
                        'smtp_from_email' => '',
                        'smtp_from_name' => '授权系统',
                    ];
                    
                    $stmt_settings = $conn->prepare("REPLACE INTO `settings` (setting_key, setting_value) VALUES (?, ?)");
                    foreach ($default_settings as $key => $value) {
                         $stmt_settings->bind_param("ss", $key, $value);
                         $stmt_settings->execute();
                    }
                    $stmt_settings->close();
                    
                    // 安装成功，跳转到成功页面
                    header('Location: install.php?step=4');
                    exit;
                } else {
                    $error = '创建数据表失败: ' . htmlspecialchars($conn->error);
                }
            }
            $conn->close();
        }
    }
}

// --- 成功页面逻辑 ---
if ($step === 4) {
    // 自动删除安装文件
    @unlink(__FILE__); 
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>小奏授权查询系统 - 安装向导</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-8">
            <div class="flex items-center space-x-3 mb-6">
                <i data-lucide="shield-check" class="w-8 h-8 text-blue-600"></i>
                <h1 class="text-2xl font-bold text-gray-800">小奏授权查询系统 - 安装向导</h1>
            </div>
            
            <!-- 步骤条 -->
            <div class="mb-8">
                <div class="flex items-center">
                    <div class="flex items-center <?php echo $step >= 1 ? 'text-blue-600' : 'text-gray-500'; ?>">
                        <div class="rounded-full h-8 w-8 flex items-center justify-center <?php echo $step >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">1</div>
                        <span class="ml-2 font-semibold">欢迎使用</span>
                    </div>
                    <div class="flex-auto border-t-2 transition duration-500 ease-in-out <?php echo $step >= 2 ? 'border-blue-600' : 'border-gray-200'; ?>"></div>
                    <div class="flex items-center <?php echo $step >= 2 ? 'text-blue-600' : 'text-gray-500'; ?>">
                        <div class="rounded-full h-8 w-8 flex items-center justify-center <?php echo $step >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">2</div>
                        <span class="ml-2 font-semibold">阅读协议</span>
                    </div>
                    <div class="flex-auto border-t-2 transition duration-500 ease-in-out <?php echo $step >= 3 ? 'border-blue-600' : 'border-gray-200'; ?>"></div>
                    <div class="flex items-center <?php echo $step >= 3 ? 'text-blue-600' : 'text-gray-500'; ?>">
                        <div class="rounded-full h-8 w-8 flex items-center justify-center <?php echo $step >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">3</div>
                        <span class="ml-2 font-semibold">环境配置</span>
                    </div>
                </div>
            </div>

            <?php if ($step === 1): ?>
                <div class="text-center">
                    <h2 class="text-xl font-semibold mb-4">欢迎使用 小奏授权查询系统</h2>
                    <p class="text-gray-600 leading-relaxed">
                        本系统是一套轻量级、高性能的PHP授权查询解决方案。它提供了一个简洁的前端查询界面，以及一个功能强大的后台管理面板，让您可以轻松管理授权、配置API和程序版本等信息。
                        感谢您的选择，请点击“下一步”开始安装之旅。
                    </p>
                </div>
                <div class="mt-10 flex justify-end">
                    <a href="install.php?step=2" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors">
                        下一步 &rarr;
                    </a>
                </div>

            <?php elseif ($step === 2): ?>
                <div>
                    <h2 class="text-xl font-semibold mb-4">版权与免责声明</h2>
                    <div class="prose prose-sm max-w-none h-64 overflow-y-auto border p-4 rounded-lg bg-gray-50 text-gray-700">
                        <h4>版权声明</h4>
                        <p>本软件由 小奏（作者名） 原创开发，拥有所有版权。未经书面许可，任何单位及个人不得以任何方式或理由对上述软件的任何部分进行使用、复制、修改、抄录、传播或与其它产品捆绑使用、销售。</p>
                        <h4>免责声明</h4>
                        <p>您充分了解并同意，您必须为自己使用本软件的行为负责，包括您所发表的任何内容以及由此产生的任何后果。您应对本软件中的内容自行加以判断，并承担因使用内容而引起的所有风险，包括因对内容的正确性、完整性或实用性的依赖而产生的风险。我们无法且不会对因用户行为而导致的任何损失或损害承担责任。</p>
                        <p>任何情况下，对于因使用或无法使用本软件而导致的任何直接、间接、偶然、特殊、惩罚性或后果性的损害，包括但不限于利润损失、商誉损失、数据损失或其他无形损失，我们均不承担任何责任。</p>
                        <p>请在遵守国家相关法律法规的前提下使用本软件！</p>
                    </div>
                    <form action="install.php?step=3" method="GET" class="mt-6">
                         <input type="hidden" name="step" value="3">
                         <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input type="checkbox" id="agree" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">我已阅读并同意上述协议</span>
                            </label>
                            <button type="submit" id="next-btn" disabled class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed">
                                下一步 &rarr;
                            </button>
                         </div>
                    </form>
                    <script>
                        const agreeCheckbox = document.getElementById('agree');
                        const nextBtn = document.getElementById('next-btn');
                        agreeCheckbox.addEventListener('change', function() {
                            nextBtn.disabled = !this.checked;
                        });
                    </script>
                </div>

            <?php elseif ($step === 3): ?>
                <div>
                    <p class="text-gray-600 mb-6">请在下方填写您的数据库及管理员信息以完成安装。</p>
                    <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                        <strong class="font-bold">发生错误!</strong>
                        <span class="block sm:inline"><?php echo $error; ?></span>
                    </div>
                    <?php endif; ?>
                    <form action="install.php?step=3" method="POST">
                        <fieldset class="mb-6">
                            <legend class="text-lg font-semibold text-gray-700 border-b w-full pb-2 mb-4">数据库设置</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><label for="db_host" class="block text-sm font-medium text-gray-700">数据库地址</label><input type="text" id="db_host" name="db_host" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" value="localhost" required></div>
                                <div><label for="db_name" class="block text-sm font-medium text-gray-700">数据库名称</label><input type="text" id="db_name" name="db_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required></div>
                                <div><label for="db_user" class="block text-sm font-medium text-gray-700">数据库用户</label><input type="text" id="db_user" name="db_user" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required></div>
                                <div><label for="db_pass" class="block text-sm font-medium text-gray-700">数据库密码</label><input type="password" id="db_pass" name="db_pass" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend class="text-lg font-semibold text-gray-700 border-b w-full pb-2 mb-4">管理员设置</legend>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><label for="admin_user" class="block text-sm font-medium text-gray-700">管理员账号</label><input type="text" id="admin_user" name="admin_user" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required></div>
                                <div><label for="admin_pass" class="block text-sm font-medium text-gray-700">管理员密码</label><input type="password" id="admin_pass" name="admin_pass" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required></div>
                            </div>
                        </fieldset>
                        <div class="mt-10 flex justify-between items-center">
                            <a href="install.php?step=2" class="text-sm text-gray-600 hover:text-blue-600">&larr; 返回上一步</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors">
                                立即安装
                            </button>
                        </div>
                    </form>
                </div>

            <?php elseif ($step === 4): ?>
                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <div class="bg-green-100 p-4 rounded-full">
                            <i data-lucide="party-popper" class="w-12 h-12 text-green-600"></i>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">安装成功！</h2>
                    <p class="text-gray-600">恭喜您，小奏授权查询系统已成功安装。</p>
                    <p class="text-sm text-red-600 mt-4">为安全起见，安装文件 <strong>install.php</strong> 已被自动删除。</p>
                    <div class="mt-8">
                        <a href="admin/login.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg text-lg focus:outline-none focus:shadow-outline transition-colors">
                            进入后台
                        </a>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>


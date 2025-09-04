<?php
// domain_manager.php (一体化PHP源码 - 修复版)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
require_once 'bootstrap.php';

// --- 初始化变量 ---
$step = 1; // 1 for verify, 2 for change form
$auth_data = null;
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');

// --- 表单提交处理 ---
if ($is_post_request) {
    if (!$is_installed) {
        $error_message = '系统尚未安装或配置文件丢失。';
    } else {
        $api_base_url = $settings['api_url'] ?? '';
        $api_key = $settings['api_key'] ?? '';
        
        if (empty($api_base_url) || empty($api_key)) {
            $error_message = '管理员尚未在后台配置API信息。';
        } else {
            // --- 步骤1：验证密钥 ---
            if (isset($_POST['verify-license'])) {
                $license_key = trim($_POST['license-key'] ?? '');
                if (empty($license_key)) {
                    $error_message = '请输入您的授权密钥。';
                } else {
                    $target_url = rtrim($api_base_url, '/') . '/authorizations/license/' . urlencode($license_key) . '/verify';
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $target_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key]);
                    $response_body = curl_exec($ch);
                    curl_close($ch);
                    
                    $data = json_decode($response_body, true);
                    if (($data['code'] ?? 500) === 200) {
                        $step = 2;
                        $auth_data = $data['data'];
                        $_SESSION['auth_data_for_change'] = $auth_data;
                    } else {
                        $error_message = $data['message'] ?? '验证失败，请检查密钥是否正确。';
                    }
                }
            }
            
            // --- 步骤2：更换域名 ---
            elseif (isset($_POST['change-domain'])) {
                if (!isset($_SESSION['auth_data_for_change'])) {
                    $error_message = '会话已过期，请重新验证授权密钥。';
                    $step = 1;
                } else {
                    $auth_data = $_SESSION['auth_data_for_change'];
                    $new_domain = trim($_POST['new-domain'] ?? '');
                    $auth_id = $auth_data['auth_id'] ?? null;

                    if (empty($new_domain)) {
                        $error_message = '请输入新的授权域名。';
                        $step = 2; // Keep them on step 2
                    } elseif (!$auth_id) {
                        $error_message = '无法获取授权ID，请重试。';
                        $step = 1;
                    } else {
                        $target_url = rtrim($api_base_url, '/') . '/users/authorizations/' . $auth_id;
                        $post_data = json_encode(['auth_domain' => $new_domain]);
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $target_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key, 'Content-Type: application/json']);
                        $response_body = curl_exec($ch);
                        curl_close($ch);
                        
                        $data = json_decode($response_body, true);
                        if (($data['code'] ?? 500) === 200) {
                            $success_message = '授权域名已成功更换为: ' . htmlspecialchars($data['data']['auth_domain'] ?? $new_domain);
                            $step = 3; // Finished step

                            // [修复] 同步更新本地备份数据库
                            if (isset($conn) && !$conn->connect_error) {
                                $stmt = $conn->prepare("UPDATE local_authorizations SET auth_domain = ? WHERE license_key = ?");
                                $stmt->bind_param("ss", $new_domain, $auth_data['license_key']);
                                $stmt->execute();
                                $stmt->close();
                            }

                            unset($_SESSION['auth_data_for_change']);
                        } else {
                            $error_message = $data['message'] ?? '更换失败，此操作可能需要用户登录才能完成。';
                            $step = 2;
                        }
                    }
                }
            }
        }
    }
} else {
    if(session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['auth_data_for_change'])) {
        unset($_SESSION['auth_data_for_change']);
    }
}
if(isset($conn)) $conn->close();

$page_title = '更换授权';
$current_page = 'domain_manager.php';
require_once 'header.php';
?>
            <!-- Results Area -->
            <div id="results-container" class="mb-6" aria-live="polite">
                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert"><p><?php echo htmlspecialchars($error_message); ?></p></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert"><p><?php echo $success_message; ?></p></div>
                <?php endif; ?>
            </div>
            
            <?php if ($step === 1): ?>
            <!-- Step 1: Verify License -->
            <div id="verify-section" class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">第一步：验证您的授权</h2>
                <form action="domain_manager.php" method="POST" class="space-y-4">
                    <div>
                        <label for="license-key" class="block text-sm font-medium text-gray-700">您的授权密钥 (License Key):</label>
                        <input type="text" id="license-key" name="license-key" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="请输入您的授权密钥" required>
                    </div>
                    <div class="pt-2">
                        <button type="submit" name="verify-license" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                           <i data-lucide="shield-check" class="w-5 h-5 mr-2"></i>验证并继续
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($step === 2 && $auth_data): ?>
            <!-- Step 2: Change Domain -->
            <div id="change-domain-section" class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">第二步：更换授权域名</h2>
                <div class="mb-4 bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">当前授权域名: <strong class="font-mono"><?php echo htmlspecialchars($auth_data['auth_domain'] ?? 'N/A'); ?></strong></p>
                    <p class="text-sm text-gray-600">产品名称: <strong><?php echo htmlspecialchars($auth_data['product_name'] ?? 'N/A'); ?></strong></p>
                </div>
                <form action="domain_manager.php" method="POST" class="space-y-4">
                     <div>
                        <label for="new-domain" class="block text-sm font-medium text-gray-700">新的授权域名:</label>
                        <input type="text" id="new-domain" name="new-domain" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="请输入新的域名" required>
                    </div>
                    <div class="pt-2">
                        <button type="submit" name="change-domain" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>确认更换
                        </button>
                    </div>
                </form>
            </div>
             <?php endif; ?>

<?php require_once 'footer.php'; ?>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

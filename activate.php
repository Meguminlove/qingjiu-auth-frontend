<?php
// activate.php (自助授权页面 - 新流程)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
// --- 初始化变量 ---
$settings = [];
$error_message = '';
$success_message = '';
$result_data = null;
$step = 1; // 1: 卡密 -> 2: 邮箱 -> 3: 密钥 -> 4: 域名 -> 5: 成功
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');

session_start();

// --- 动态加载配置 ---
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        $conn->close();
    }
}

// --- 表单提交处理 ---
if ($is_post_request) {
    if (!file_exists(__DIR__ . '/config.php')) {
        $error_message = '系统尚未安装或配置文件丢失。';
    } else {
        $api_base_url = $settings['api_url'] ?? '';
        $api_key = $settings['api_key'] ?? '';
        $product_id = trim($settings['query_product_id'] ?? '1');

        if (empty($api_base_url) || empty($api_key)) {
            $error_message = '管理员尚未在后台配置API信息。';
        }

        // --- 步骤 1: 验证卡密 ---
        elseif (isset($_POST['submit_step1'])) {
            $card_key = trim($_POST['card_key'] ?? '');
            if (empty($card_key)) {
                $error_message = '请输入您的授权卡密。';
            } else {
                $target_url = rtrim($api_base_url, '/') . '/cards/check-status?card_key=' . urlencode($card_key);
                $ch = curl_init($target_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key]);
                $response = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (isset($response['code']) && $response['code'] === 200) {
                    $_SESSION['activate_card_key'] = $card_key;
                    $step = 2;
                } else {
                    $error_message = $response['message'] ?? '卡密无效或已被使用。';
                }
            }
        }
        
        // --- 步骤 2: 提交邮箱以激活 ---
        elseif (isset($_POST['submit_step2'])) {
            $card_key = $_SESSION['activate_card_key'] ?? '';
            $email = trim($_POST['email'] ?? '');
            if (empty($card_key) || empty($email)) {
                $error_message = '会话丢失或邮箱为空，请重试。';
                $step = 1;
            } else {
                $target_url = rtrim($api_base_url, '/') . '/authorizations/activate';
                $post_data = json_encode(['card_key' => $card_key, 'product_id' => (int)$product_id, 'auth_email' => $email]);
                
                $ch = curl_init($target_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-API-Key: ' . $api_key]);
                $response = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (isset($response['code']) && $response['code'] === 200) {
                    $_SESSION['activate_license_key'] = $response['data']['license_key'] ?? '';
                    $_SESSION['activate_auth_id'] = $response['data']['auth_id'] ?? 0;
                    $success_message = '授权已成功生成！您的授权密钥为: ' . htmlspecialchars($_SESSION['activate_license_key']);
                    $step = 3;
                } else {
                    $error_message = $response['message'] ?? '激活失败，请检查邮箱或联系客服。';
                    $step = 2;
                }
            }
        }

        // --- 步骤 3: 验证授权密钥 (按照 domain_manager.php 逻辑重写) ---
        elseif (isset($_POST['submit_step3'])) {
             $license_key_input = trim($_POST['license_key'] ?? '');
             if (empty($license_key_input)) {
                 $error_message = '请输入您的授权密钥。';
                 $step = 3;
                 // 保留成功信息，以便用户知道密钥是什么
                 if(isset($_SESSION['activate_license_key'])) {
                    $success_message = '授权已成功生成！您的授权密钥为: ' . htmlspecialchars($_SESSION['activate_license_key']);
                 }
             } else {
                 // 核心修改：调用API进行验证，而不是仅在会话中检查
                 $target_url = rtrim($api_base_url, '/') . '/authorizations/license/' . urlencode($license_key_input) . '/verify';
                 
                 $ch = curl_init($target_url);
                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key]);
                 $response = json_decode(curl_exec($ch), true);
                 curl_close($ch);

                 if (isset($response['code']) && $response['code'] === 200) {
                     // API验证成功，刷新会话中的auth_id，确保第四步可用
                     $_SESSION['activate_auth_id'] = $response['data']['auth_id'] ?? 0;
                     $_SESSION['activate_license_key'] = $license_key_input; // 存储我们刚刚验证过的密钥
                     $step = 4;
                 } else {
                     // API验证失败
                     $error_message = $response['message'] ?? '授权密钥验证失败，请确认密钥是否正确。';
                     $step = 3;
                     if(isset($_SESSION['activate_license_key'])) {
                        $success_message = '授权已成功生成！您的授权密钥为: ' . htmlspecialchars($_SESSION['activate_license_key']);
                     }
                 }
             }
        }

        // --- 步骤 4: 绑定域名 ---
        elseif (isset($_POST['submit_step4'])) {
            $auth_id = $_SESSION['activate_auth_id'] ?? 0;
            $domain = trim($_POST['domain'] ?? '');
            if (empty($auth_id) || empty($domain)) {
                $error_message = '会话丢失或域名为空，请重新开始。';
                $step = 1;
                session_destroy();
            } else {
                // 使用更新接口绑定域名，逻辑与 domain_manager.php 一致
                $target_url = rtrim($api_base_url, '/') . '/users/authorizations/' . $auth_id;
                $post_data = json_encode(['auth_domain' => $domain]);

                $ch = curl_init($target_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-API-Key: ' . $api_key]);
                $response = json_decode(curl_exec($ch), true);
                curl_close($ch);

                if (isset($response['code']) && $response['code'] === 200) {
                    // 为了显示完整信息，我们再用密钥查一次最终结果
                    $final_key = $_SESSION['activate_license_key'];
                    $verify_url = rtrim($api_base_url, '/') . '/authorizations/license/' . urlencode($final_key) . '/verify';
                    $ch_verify = curl_init($verify_url);
                    curl_setopt($ch_verify, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_verify, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key]);
                    $final_response = json_decode(curl_exec($ch_verify), true);
                    curl_close($ch_verify);
                    
                    $result_data = $final_response['data'] ?? null;
                    $success_message = "授权成功！您的授权信息如下。";
                    $step = 5;

                    // [NEW] Backup the successful authorization to local database
                    if ($result_data && file_exists(__DIR__ . '/config.php')) {
                        $conn_backup = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                        if (!$conn_backup->connect_error) {
                            $conn_backup->set_charset('utf8mb4');
                            $stmt = $conn_backup->prepare(
                                "REPLACE INTO local_authorizations (product_id, auth_domain, auth_email, license_key) VALUES (?, ?, ?, ?)"
                            );
                            // Bind parameters
                            $p_id = $result_data['product_id'];
                            $p_domain = $result_data['auth_domain'];
                            $p_email = $result_data['auth_email'];
                            $p_key = $result_data['license_key'];
                            $stmt->bind_param("isss", $p_id, $p_domain, $p_email, $p_key);
                            $stmt->execute();
                            $stmt->close();
                            $conn_backup->close();
                        }
                    }

                    session_destroy();
                } else {
                    $error_message = $response['message'] ?? '域名绑定失败，请联系客服。';
                    $step = 4;
                }
            }
        }
    }
}
// 仅当用户通过GET请求访问第一页时才销毁会话，避免在流程中意外重置
elseif (!$is_post_request && (!isset($_GET['step']) || $_GET['step'] == 1)) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

$page_title = '自助授权';
$current_page = 'activate.php';
require_once 'header.php';
?>
        <div class="bg-white rounded-lg shadow-md p-6 sm:p-8">
            <div class="mb-6">
                <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert"><p><?php echo htmlspecialchars($error_message); ?></p></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert"><p><?php echo $success_message; ?></p></div>
                <?php endif; ?>
            </div>

            <!-- Step 1: Card Key -->
            <?php if ($step === 1): ?>
            <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-6">自助授权 - 第1步 / 共4步</h2>
            <form action="activate.php" method="POST" class="space-y-4 max-w-md mx-auto">
                <div>
                    <label for="card_key" class="block text-sm font-medium text-gray-700">授权卡密</label>
                    <input type="text" name="card_key" id="card_key" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <button type="submit" name="submit_step1" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">下一步</button>
            </form>
            <?php endif; ?>

            <!-- Step 2: Email -->
            <?php if ($step === 2): ?>
            <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-6">自助授权 - 第2步 / 共4步</h2>
            <form action="activate.php" method="POST" class="space-y-4 max-w-md mx-auto">
                 <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">联系邮箱</label>
                    <input type="email" name="email" id="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="用于接收授权凭证" required>
                </div>
                <button type="submit" name="submit_step2" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">生成授权密钥</button>
            </form>
            <?php endif; ?>

            <!-- Step 3: License Key -->
            <?php if ($step === 3): ?>
             <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-6">自助授权 - 第3步 / 共4步</h2>
            <form action="activate.php" method="POST" class="space-y-4 max-w-md mx-auto">
                <div>
                    <label for="license_key" class="block text-sm font-medium text-gray-700">授权密钥</label>
                    <input type="text" name="license_key" id="license_key" value="<?php echo htmlspecialchars($_SESSION['activate_license_key'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                     <p class="mt-2 text-xs text-gray-500">请务必复制并妥善保管您的授权密钥，它是您授权的唯一凭证。</p>
                </div>
                 <button type="submit" name="submit_step3" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">下一步</button>
            </form>
            <?php endif; ?>

            <!-- Step 4: Domain -->
             <?php if ($step === 4): ?>
             <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-6">自助授权 - 第4步 / 共4步</h2>
            <form action="activate.php" method="POST" class="space-y-4 max-w-md mx-auto">
                 <div>
                    <label for="domain" class="block text-sm font-medium text-gray-700">授权域名</label>
                    <input type="text" name="domain" id="domain" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="例如：example.com" required>
                </div>
                <button type="submit" name="submit_step4" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">完成授权</button>
            </form>
            <?php endif; ?>

            <!-- Step 5: Success -->
            <?php if ($step === 5 && $result_data): ?>
            <div class="text-center">
                <h2 class="text-2xl font-bold text-green-600 mb-4">授权全部完成！</h2>
            </div>
            <div id="auth-info" class="space-y-3 text-sm border p-4 rounded-md bg-gray-50">
                <p><strong>产品名称:</strong> <span id="info-product-name"><?php echo htmlspecialchars($result_data['product_name'] ?? 'N/A'); ?></span></p>
                <p><strong>授权域名:</strong> <span id="info-auth-domain"><?php echo htmlspecialchars($result_data['auth_domain'] ?? 'N/A'); ?></span></p>
                <p><strong>授权邮箱:</strong> <span id="info-auth-email"><?php echo htmlspecialchars($result_data['auth_email'] ?? 'N/A'); ?></span></p>
                <p><strong>授权状态:</strong> <span id="info-status-text"><?php echo htmlspecialchars($result_data['status_text'] ?? 'N/A'); ?></span></p>
                <p><strong>到期时间:</strong> <span id="info-expire-time"><?php echo ($result_data['is_lifetime'] ?? false) ? '永久' : date('Y-m-d H:i:s', strtotime($result_data['expire_time'] ?? '')); ?></span></p>
                <p class="font-mono break-all"><strong>授权密钥:</strong> <span id="info-license-key"><?php echo htmlspecialchars($result_data['license_key'] ?? 'N/A'); ?></span></p>
            </div>
            <div class="mt-6 text-center">
                <button id="download-auth-info" class="bg-indigo-600 text-white py-2 px-6 rounded-md hover:bg-indigo-700">
                    <i data-lucide="download" class="inline-block w-4 h-4 mr-1"></i> 下载授权信息 (.txt)
                </button>
            </div>
            <?php endif; ?>
        </div>

<?php require_once 'footer.php'; ?>
        </main>
    </div>
    
    <script>
        lucide.createIcons();
        
        <?php if ($step === 5 && $result_data): ?>
        // This block executes only on the final success step to enable the download functionality.
        const downloadBtn = document.getElementById('download-auth-info');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                // Explicitly construct the text content to ensure all fields, including the license key, are included.
                let textContent = "======= 授权信息详情 =======\n\n";
                textContent += `产品名称: ${document.getElementById('info-product-name').textContent}\n`;
                textContent += `授权域名: ${document.getElementById('info-auth-domain').textContent}\n`;
                textContent += `授权邮箱: ${document.getElementById('info-auth-email').textContent}\n`;
                textContent += `授权状态: ${document.getElementById('info-status-text').textContent}\n`;
                textContent += `到期时间: ${document.getElementById('info-expire-time').textContent}\n`;
                textContent += `授权密钥: ${document.getElementById('info-license-key').textContent}\n`; // 确保授权密钥被包含
                textContent += "\n=============================\n";
                textContent += "请妥善保管您的授权信息，尤其是授权密钥。\n";
                
                const blob = new Blob([textContent], { type: 'text/plain;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                
                // Generate a more descriptive filename
                const productName = document.getElementById('info-product-name').textContent || '未知产品';
                const fileName = `授权信息-${productName}.txt`;
                a.download = fileName.replace(/ /g, '_'); // Replace spaces for better compatibility
                
                a.href = url;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>


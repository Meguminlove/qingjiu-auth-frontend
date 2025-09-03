<?php
// download.php (一体化PHP源码)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
// --- 初始化变量 ---
$settings = [];
$error_message = '';
$result_data = null;
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');
$TARGET_PRODUCT_ID = 1; // 默认产品ID

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
$TARGET_PRODUCT_ID = $settings['query_product_id'] ?? 1;


// --- 表单提交处理 ---
if ($is_post_request) {
    if (!file_exists(__DIR__ . '/config.php')) {
        $error_message = '系统尚未安装或配置文件丢失。';
    } else {
        $card_key = trim($_POST['card-key'] ?? '');
        $api_base_url = $settings['api_url'] ?? '';
        $api_key = $settings['api_key'] ?? '';

        if (empty($card_key)) {
            $error_message = '请输入您的卡密。';
        } elseif (empty($api_base_url) || empty($api_key)) {
            $error_message = '管理员尚未在后台配置API信息。';
        } else {
            // --- cURL 请求 ---
            $target_url = rtrim($api_base_url, '/') . '/cards/check-status?card_key=' . urlencode($card_key);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $target_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key]);
            
            $response_body = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($response_body === false) {
                $error_message = '验证请求失败: ' . $curl_error;
            } else {
                $data = json_decode($response_body, true);
                if ($http_status == 404) {
                     $error_message = '卡密不存在或无效，请检查后重试。';
                } elseif (isset($data['code'])) {
                    if ($data['code'] === 200 || $data['code'] === 409) { // 200: 可用, 409: 已激活
                        if (($data['data']['product_id'] ?? 0) != $TARGET_PRODUCT_ID) {
                            $error_message = '此卡密不适用于本程序。';
                        } elseif (isset($data['data']['status']) && in_array($data['data']['status'], [1, 2])) { // 1: 已售未激活, 2: 已激活
                            $result_data = [
                                'download_url' => $settings['download_url'] ?? '',
                                'update_version' => $settings['update_version'] ?? 'N/A',
                                'update_log' => $settings['update_log'] ?? '暂无更新说明。'
                            ];
                        } else {
                             $error_message = '卡密状态无效: ' . ($data['data']['status_text'] ?? '未知状态');
                        }
                    } else {
                        $error_message = $data['message'] ?? '卡密验证失败，未知错误。';
                    }
                } else {
                     $error_message = 'API响应格式不正确。';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>程序下载 - <?php echo htmlspecialchars($settings['site_name'] ?? '授权查询系统'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #f7fafc; }
        .nav-link.active { background-color: #ef4444; color: white; }
        .nav-link { transition: background-color 0.2s ease-in-out; }
        .update-log-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.6;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
    </style>
</head>
<body class="font-sans antialiased">

    <div class="container mx-auto max-w-2xl p-4">
        <!-- Header Navigation Card -->
        <header class="bg-white rounded-lg shadow-md p-2 mb-6">
            <nav class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-2">
                <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100" href="./query.php">
                    <i data-lucide="search" class="inline-block w-4 h-4 mr-1"></i>授权查询
                </a>
                <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100" href="./domain_manager.php">
                   <i data-lucide="replace" class="inline-block w-4 h-4 mr-1"></i>更换授权
                </a>
                <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100" href="./activate.php">
                    <i data-lucide="user-check" class="inline-block w-4 h-4 mr-1"></i>自助授权
                </a>
                <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100" href="./auth.php">
                    <i data-lucide="message-circle" class="inline-block w-4 h-4 mr-1"></i>联系客服
                </a>
                <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium nav-link active" href="./download.php">
                    <i data-lucide="download" class="inline-block w-4 h-4 mr-1"></i>下载程序
                </a>
            </nav>
        </header>

        <!-- Download Section -->
        <main class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="text-center">
                <i data-lucide="cloud-download" class="w-16 h-16 text-blue-500 mx-auto mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800">程序下载</h2>
                <p class="text-gray-600 mt-2">请输入您购买的卡密以获取下载链接。</p>
            </div>
            
            <form id="download-form" action="download.php" method="POST" class="mt-8 space-y-4 max-w-md mx-auto">
                <div>
                    <label for="card-key" class="block text-sm font-medium text-gray-700">授权卡密:</label>
                    <input type="text" id="card-key" name="card-key" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="请输入您的卡密" required>
                </div>
                <div class="pt-2">
                    <button type="submit" id="submit-button" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i data-lucide="key-round" class="w-5 h-5 mr-2"></i>验证并获取下载链接
                    </button>
                </div>
            </form>

            <div id="results-container" class="mt-6 max-w-md mx-auto" aria-live="polite">
                <?php if ($is_post_request): ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md flex items-center" role="alert"><p><?php echo htmlspecialchars($error_message); ?></p></div>
                    <?php elseif (isset($result_data)): ?>
                        <?php if (empty($result_data['download_url']) || empty($result_data['update_version'])): ?>
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert"><p>管理员尚未在后台设置程序版本信息，暂时无法下载。</p></div>
                        <?php else: 
                            $versionText = $result_data['update_version'];
                            $versionText = strpos($versionText, 'v') === 0 ? $versionText : 'v' . $versionText;
                        ?>
                            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-4" role="alert">
                                <p class="font-bold">验证成功!</p>
                                <p>您现在可以下载最新版本 (<?php echo htmlspecialchars($versionText); ?>)。</p>
                            </div>
                            <a href="<?php echo htmlspecialchars($result_data['download_url']); ?>" target="_blank" class="w-full flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <i data-lucide="download" class="w-5 h-5 mr-2"></i>
                                立即下载 <?php echo htmlspecialchars($versionText); ?>
                            </a>
                            <details class="bg-gray-50 p-4 rounded-lg mt-4 border">
                                <summary class="font-medium cursor-pointer text-gray-800">查看 <?php echo htmlspecialchars($versionText); ?> 更新内容</summary>
                                <div class="mt-3 pt-3 border-t text-sm text-gray-700 update-log-content">
                                   <?php echo nl2br(htmlspecialchars($result_data['update_log'])); ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
        
        <?php require_once 'footer.php'; ?>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

<?php
// query.php (一体化PHP源码)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
// --- 初始化变量 ---
$settings = [];
$error_message = '';
$result_data = null;
$result_message = '';
$result_color = 'yellow';
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');

// --- 动态加载配置 ---
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // 在页面加载时，如果数据库连接失败，只影响公告加载，不阻止表单显示
    } else {
        $conn->set_charset('utf8mb4');
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        $conn->close();
    }
} else {
    // 如果未安装，表单提交时会提示错误
}

// --- 表单提交处理 ---
if ($is_post_request) {
    if (!file_exists(__DIR__ . '/config.php')) {
        $error_message = '系统尚未安装或配置文件丢失。';
    } else {
        $query_type = $_POST['query_type'] ?? 'domain';
        $query_info = trim($_POST['query_info'] ?? '');
        $product_id = trim($settings['query_product_id'] ?? '1'); 
        $api_base_url = $settings['api_url'] ?? '';
        $api_key = $settings['api_key'] ?? '';

        if (empty($query_info)) {
            $error_message = '请输入查询信息。';
        } elseif (empty($api_base_url) || empty($api_key)) {
            $error_message = '管理员尚未在后台配置API信息。';
        } else {
            // --- 构建目标URL ---
            if ($query_type === 'license_key') {
                $target_url = rtrim($api_base_url, '/') . '/authorizations/license/' . urlencode($query_info) . '/verify';
            } else {
                $target_url = rtrim($api_base_url, '/') . '/authorizations/verify?domain=' . urlencode($query_info) . '&product_id=' . urlencode($product_id);
            }
            
            // --- cURL 请求 ---
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $target_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'QingJiu-PHP-Querier/1.0');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key, 'Accept: application/json']);
            
            $response_body = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($response_body === false) {
                $error_message = '查询请求失败: ' . $curl_error;
            } else {
                $data = json_decode($response_body, true);
                if (isset($data['code'])) {
                    $result_data = $data['data'] ?? null;
                    
                    if ($data['code'] === 200) {
                        $result_message = '授权有效 正版软件';
                        $result_color = 'green';
                    } elseif ($data['code'] === 403) {
                        $result_message = $data['message'] ?? '查询完成。';
                        $result_color = 'red';
                    } elseif ($data['code'] === 404) {
                        $result_message = $data['message'] ?? '查询完成。';
                        $result_color = 'yellow';
                        $error_message = $result_message; // For 404, we show it as a main error.
                        $result_data = null;
                    } else {
                        $result_message = $data['message'] ?? '查询完成。';
                        $result_color = 'red';
                        $error_message = $result_message;
                        $result_data = null;
                    }
                } else {
                    $error_message = 'API响应格式不正确。';
                }
            }
        }
    }
}

$announcement = !empty($settings['system_announcement']) ? htmlspecialchars($settings['system_announcement']) : '暂无最新公告。';
$page_title = '授权查询';
$current_page = 'query.php';

require_once 'header.php';
?>
            <!-- Authorization Query Form Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">授权查询</h2>
                <form id="query-form" action="query.php" method="POST" class="space-y-4">
                    <div>
                        <label for="query-type" class="block text-sm font-medium text-gray-700">查询类型:</label>
                        <select id="query-type" name="query_type" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="domain" selected>授权域名</option>
                            <option value="license_key">授权密钥</option>
                        </select>
                    </div>
                    <div>
                        <label for="query-info" class="block text-sm font-medium text-gray-700">查询信息:</label>
                        <input type="text" id="query-info" name="query_info" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="请输入查询信息" required>
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" id="submit-button" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>立即查询
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="results-container" class="mb-6" aria-live="polite">
                <?php if ($is_post_request): ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                        </div>
                    <?php elseif (isset($result_data)):
                        $isPermanent = $result_data['is_lifetime'] ?? false;
                        $expireTime = $isPermanent ? '永久有效' : (isset($result_data['expire_time']) ? date('Y-m-d H:i:s', strtotime($result_data['expire_time'])) : 'N/A');
                        $statusText = $result_data['status_text'] ?? '未知';
                        $statusBadgeColor = $result_color === 'green' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                    ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="bg-<?php echo $result_color; ?>-500 text-white p-4"><h3 class="text-lg font-semibold">查询结果: <?php echo htmlspecialchars($result_message); ?></h3></div>
                            <div class="p-6">
                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                                    <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500">产品名称</dt><dd class="mt-1 text-md text-gray-900"><?php echo htmlspecialchars($result_data['product_name'] ?? 'N/A'); ?></dd></div>
                                    <div><dt class="text-sm font-medium text-gray-500">授权域名</dt><dd class="mt-1 text-md text-gray-900"><?php echo htmlspecialchars($result_data['auth_domain'] ?? 'N/A'); ?></dd></div>
                                    <div><dt class="text-sm font-medium text-gray-500">授权邮箱</dt><dd class="mt-1 text-md text-gray-900"><?php echo htmlspecialchars($result_data['auth_email'] ?? 'N/A'); ?></dd></div>
                                    <div><dt class="text-sm font-medium text-gray-500">授权状态</dt><dd class="mt-1 text-md"><span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusBadgeColor; ?>"><?php echo htmlspecialchars($statusText); ?></span></dd></div>
                                    <div><dt class="text-sm font-medium text-gray-500">到期时间</dt><dd class="mt-1 text-md text-gray-900"><?php echo $expireTime; ?></dd></div>
                                </dl>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                 <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">系统公告</h2>
                 <p id="announcement-content" class="text-gray-600"><?php echo nl2br($announcement); ?></p>
            </div>
        
<?php require_once 'footer.php'; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        document.getElementById('query-type').addEventListener('change', function() {
            const input = document.getElementById('query-info');
            if (this.value === 'domain') {
                input.placeholder = '请输入要查询的授权域名';
            } else {
                input.placeholder = '请输入您的完整授权密钥 (License Key)';
            }
        });
    </script>
</body>
</html>


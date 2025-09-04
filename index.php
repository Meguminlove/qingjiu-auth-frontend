<?php
// index.php (全新主页)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

require_once 'bootstrap.php';

// --- 初始化变量 ---
$result_data = null;
$result_message = '';
$result_color = 'yellow';
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');
$error_message = '';

// --- 表单提交处理 ---
if ($is_post_request) {
    if (!$is_installed) {
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
            if ($query_type === 'license_key') {
                $target_url = rtrim($api_base_url, '/') . '/authorizations/license/' . urlencode($query_info) . '/verify';
            } else {
                $target_url = rtrim($api_base_url, '/') . '/authorizations/verify?domain=' . urlencode($query_info) . '&product_id=' . urlencode($product_id);
            }
            
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
                $error_message = '查询请求失败: ' . htmlspecialchars($curl_error);
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
                        $error_message = $result_message;
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
if(isset($conn)) $conn->close();

$announcement = !empty($settings['system_announcement']) ? htmlspecialchars($settings['system_announcement']) : '暂无最新公告。';
$page_title = '首页';
$current_page = 'index.php'; // 为了导航栏高亮
require_once 'header.php';
?>
            <!-- 公告区域 -->
            <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-lg mb-6" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="megaphone" class="h-5 w-5"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm"><?php echo nl2br($announcement); ?></p>
                    </div>
                </div>
            </div>

            <!-- 核心查询区域 -->
            <div class="bg-white rounded-lg shadow-md p-6 sm:p-8 mb-6">
                <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-6">授权查询</h2>
                <form id="query-form" action="index.php" method="POST" class="space-y-4 max-w-lg mx-auto">
                    <div>
                        <label for="query-type" class="block text-sm font-medium text-gray-700">查询类型</label>
                        <select id="query-type" name="query_type" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="domain" selected>授权域名</option>
                            <option value="license_key">授权密钥</option>
                        </select>
                    </div>
                    <div>
                        <label for="query-info" class="block text-sm font-medium text-gray-700">查询信息</label>
                        <input type="text" id="query-info" name="query_info" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="请输入要查询的授权域名" required>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i data-lucide="search" class="w-5 h-5 mr-2"></i>立即查询
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- 查询结果显示区域 -->
            <div id="results-container" class="mb-6 max-w-lg mx-auto" aria-live="polite">
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

            <!-- 功能导航卡片 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                    // 定义卡片数据
                    $cards = [
                        ['href' => 'activate.php', 'icon' => 'user-check', 'title' => '自助授权', 'desc' => '使用卡密激活您的产品授权。', 'color' => 'blue'],
                        ['href' => 'domain_manager.php', 'icon' => 'replace', 'title' => '更换授权', 'desc' => '更换您已授权产品的域名。', 'color' => 'indigo'],
                        ['href' => 'key_query.php', 'icon' => 'key-round', 'title' => '密钥找回', 'desc' => '通过邮箱找回您的授权密钥。', 'color' => 'purple'],
                        ['href' => 'download.php', 'icon' => 'download', 'title' => '程序下载', 'desc' => '验证卡密后下载最新版程序。', 'color' => 'green'],
                        ['href' => 'auth.php', 'icon' => 'message-circle', 'title' => '联系客服', 'desc' => '获取帮助或进行人工业务。', 'color' => 'sky'],
                        ['href' => '#', 'icon' => 'log-in', 'title' => '用户登录', 'desc' => '登录以管理您的授权与服务。', 'color' => 'slate'],
                    ];

                    foreach ($cards as $card) {
                        $attributes = $card['title'] === '用户登录' ? 'onclick="alert(\'此功能正在开发中，敬请期待！\'); return false;"' : '';
                        echo <<<HTML
                        <a href="{$card['href']}" {$attributes} class="group block bg-white rounded-lg shadow-md p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-{$card['color']}-100 text-{$card['color']}-600">
                                    <i data-lucide="{$card['icon']}" class="w-6 h-6"></i>
                                </div>
                                <h3 class="ml-4 text-lg font-semibold text-gray-800">{$card['title']}</h3>
                            </div>
                            <p class="mt-3 text-sm text-gray-600">{$card['desc']}</p>
                        </a>
HTML;
                    }
                ?>
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


<?php
// key_query.php (New Local Backup Flow)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

$settings = [];
$error_message = '';
$success_message = '';
$result_data = null;
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');

// --- Load Config ---
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

// --- Form Submission Logic ---
if ($is_post_request) {
    $domain = trim($_POST['auth_domain'] ?? '');
    $email = trim($_POST['auth_email'] ?? '');
    $product_id = (int)($settings['query_product_id'] ?? 1);

    if (empty($domain) || empty($email)) {
        $error_message = '请输入授权域名和邮箱以进行查询。';
    } else {
        if (!file_exists(__DIR__ . '/config.php')) {
            $error_message = '系统尚未安装或配置文件丢失。';
        } else {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                $error_message = '数据库连接失败，请联系管理员。';
            } else {
                $conn->set_charset('utf8mb4');
                $stmt = $conn->prepare(
                    "SELECT license_key, auth_domain, auth_email FROM local_authorizations WHERE auth_domain = ? AND auth_email = ? AND product_id = ? LIMIT 1"
                );
                $stmt->bind_param("ssi", $domain, $email, $product_id);
                $stmt->execute();
                $query_result = $stmt->get_result();

                if ($query_result->num_rows > 0) {
                    $result_data = $query_result->fetch_assoc();
                    $success_message = '成功从本地备份中找回您的授权信息！';
                } else {
                    $error_message = '未在本地备份中找到匹配的授权记录。请确认信息是否正确，或通过“自助授权”重新激活。';
                }
                $stmt->close();
                $conn->close();
            }
        }
    }
}

$page_title = '授权密钥查询';
$current_page = 'key_query.php';
require_once 'header.php';
?>
<div class="bg-white rounded-lg shadow-md p-6 sm:p-8">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">本地密钥查询</h2>
        <p class="text-gray-600 mt-2">通过您授权时使用的域名和邮箱，从本地备份找回您的授权密钥。</p>
    </div>

    <!-- Query Form -->
    <form action="key_query.php" method="POST" class="space-y-4 max-w-md mx-auto mb-8">
        <div>
            <label for="auth-domain" class="block text-sm font-medium text-gray-700">授权域名</label>
            <input type="text" name="auth_domain" id="auth-domain" value="<?php echo htmlspecialchars($_POST['auth_domain'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="例如：example.com" required>
        </div>
        <div>
            <label for="auth-email" class="block text-sm font-medium text-gray-700">授权邮箱</label>
            <input type="email" name="auth_email" id="auth-email" value="<?php echo htmlspecialchars($_POST['auth_email'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="请输入您的授权邮箱" required>
        </div>
        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <i data-lucide="search" class="w-5 h-5 mr-2"></i>立即查询
        </button>
    </form>
    
    <!-- Results Area -->
    <div id="results-container" class="max-w-md mx-auto">
        <?php if ($is_post_request): ?>
            <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert"><p><?php echo htmlspecialchars($error_message); ?></p></div>
            <?php elseif ($success_message && $result_data): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md mb-4" role="alert"><p><?php echo htmlspecialchars($success_message); ?></p></div>
                <div class="space-y-3 text-sm border p-4 rounded-md bg-gray-50">
                    <p><strong>授权域名:</strong> <span><?php echo htmlspecialchars($result_data['auth_domain']); ?></span></p>
                    <p><strong>授权邮箱:</strong> <span><?php echo htmlspecialchars($result_data['auth_email']); ?></span></p>
                    <p class="font-mono break-all"><strong>授权密钥:</strong> <span><?php echo htmlspecialchars($result_data['license_key']); ?></span></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
</main>
</div>
<script>
    lucide.createIcons();
</script>
</body>
</html>


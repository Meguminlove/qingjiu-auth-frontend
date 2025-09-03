<?php
// auth.php (一体化PHP源码)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
// --- 初始化变量 ---
$settings = [];
$qq_number = '尚未设置';
$wechat_qr_url = '';

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

// 从数据库设置中获取值
$qq_number = htmlspecialchars($settings['customer_service_qq'] ?? '尚未设置');
$wechat_qr_url = htmlspecialchars($settings['wechat_qrcode_url'] ?? '');

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>联系客服 - <?php echo htmlspecialchars($settings['site_name'] ?? '授权查询系统'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: #f7fafc; }
        .nav-link.active { background-color: #ef4444; color: white; }
        .nav-link { transition: background-color 0.2s ease-in-out; }
    </style>
</head>
<body class="font-sans antialiased">

    <div class="container mx-auto max-w-4xl p-4">
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
                <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium nav-link active" href="./auth.php">
                    <i data-lucide="message-circle" class="inline-block w-4 h-4 mr-1"></i>联系客服
                </a>
                <a class="w-full sm:w-auto text-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100" href="./download.php">
                    <i data-lucide="download" class="inline-block w-4 h-4 mr-1"></i>下载程序
                </a>
            </nav>
        </header>

        <main>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">联系客服购买授权</h2>
                
                <div class="flex flex-col md:flex-row gap-8 items-start justify-center text-center py-8">
                    
                    <!-- QQ Contact -->
                    <div class="flex flex-col items-center flex-1">
                        <i data-lucide="message-circle" class="w-16 h-16 text-blue-500 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">联系客服QQ</h3>
                        <p class="text-gray-600 my-2">请添加下方QQ号进行授权咨询</p>
                        <div class="bg-gray-100 font-mono text-lg px-4 py-2 rounded-md">
                           <?php echo $qq_number; ?>
                        </div>
                        <button id="copy-qq" class="mt-4 flex items-center justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <i data-lucide="clipboard" class="w-4 h-4 mr-2"></i>
                            <span id="copy-qq-text">点击复制</span>
                        </button>
                    </div>

                    <!-- WeChat Contact -->
                    <div class="flex flex-col items-center flex-1">
                         <i data-lucide="scan-line" class="w-16 h-16 text-green-500 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-800">扫码添加客服微信</h3>
                        <p class="text-gray-600 my-2">请使用微信扫描下方二维码</p>
                        <?php if (!empty($wechat_qr_url) && file_exists(__DIR__ . '/' . $wechat_qr_url)): ?>
                            <img src="<?php echo $wechat_qr_url; ?>" alt="微信二维码" class="w-48 h-48 rounded-lg shadow-sm">
                        <?php else: ?>
                            <div class="w-48 h-48 rounded-lg shadow-sm bg-gray-200 flex items-center justify-center">
                                <p class="text-gray-500">管理员未上传二维码</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </main>
        
        <?php require_once 'footer.php'; ?>
    </div>

    <script>
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', function() {
            const copyQqBtn = document.getElementById('copy-qq');
            const copyQqText = document.getElementById('copy-qq-text');
            const qqNumber = '<?php echo $qq_number; ?>';

            copyQqBtn.addEventListener('click', function() {
                const textArea = document.createElement('textarea');
                textArea.value = qqNumber;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    copyQqText.textContent = '复制成功!';
                } catch (err) {
                    console.error('复制失败:', err);
                    copyQqText.textContent = '复制失败';
                }
                document.body.removeChild(textArea);

                setTimeout(() => {
                    copyQqText.textContent = '点击复制';
                }, 2000);
            });
        });
    </script>
</body>
</html>

<?php
// user/index.php (本地化改造版)
// 版权所有：小奏 (https://blog.mofuc.cn/)
require_once 'functions.php';
require_user_login();

$user_info = $_SESSION['user_info'];
$page_title = '仪表盘';
user_center_header($page_title);
?>
<div class="bg-white p-6 rounded-lg shadow-sm">
    <h1 class="text-2xl font-semibold text-gray-800 border-b pb-4 mb-6">仪表盘</h1>
    
    <div class="mb-8">
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md">
            <p class="font-bold">欢迎回来, <?php echo htmlspecialchars($user_info['nickname'] ?? ''); ?>!</p>
            <p class="text-sm">这是一个基础框架，更多功能敬请期待。</p>
        </div>
    </div>
    
    <h2 class="text-xl font-semibold text-gray-800 border-b pb-3 mb-4">用户信息概览</h2>
    <div class="text-gray-700 space-y-3">
        <p><strong>昵称:</strong> <?php echo htmlspecialchars($user_info['nickname'] ?? 'N/A'); ?></p>
        <p><strong>邮箱:</strong> <?php echo htmlspecialchars($user_info['email'] ?? 'N/A'); ?></p>
        <p><strong>上次登录时间:</strong> <?php echo isset($user_info['last_login_time']) ? date('Y-m-d H:i:s', strtotime($user_info['last_login_time'])) : '首次登录'; ?></p>
        <p><strong>上次登录IP:</strong> <?php echo htmlspecialchars($user_info['last_login_ip'] ?? 'N/A'); ?></p>
        <p><strong>注册时间:</strong> <?php echo isset($user_info['created_at']) ? date('Y-m-d H:i:s', strtotime($user_info['created_at'])) : 'N/A'; ?></p>
    </div>
</div>

<?php user_center_footer(); ?>

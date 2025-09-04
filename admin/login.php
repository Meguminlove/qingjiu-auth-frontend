<?php
// admin/login.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
require_once 'functions.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$max_login_attempts = 5;
$lockout_time = 15 * 60; // 15 分钟

// [安全增强] 检查是否已因多次失败而被锁定
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_login_attempts && isset($_SESSION['lockout_until']) && time() < $_SESSION['lockout_until']) {
    $remaining_time = ceil(($_SESSION['lockout_until'] - time()) / 60);
    $error = "登录尝试次数过多，请在 {$remaining_time} 分钟后重试。";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码。';
    } else {
        $conn = get_db_connection();
        if ($stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?")) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                if (password_verify($password, $admin['password'])) {
                    // 登录成功
                    unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);
                    session_regenerate_id(true); // [安全增强] 防止会话固定攻击
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    header('Location: index.php');
                    exit;
                }
            }
            
            // 登录失败处理
            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 0;
            }
            $_SESSION['login_attempts']++;

            if ($_SESSION['login_attempts'] >= $max_login_attempts) {
                $_SESSION['lockout_until'] = time() + $lockout_time;
                $error = '登录尝试次数过多，您的账户已被锁定15分钟。';
            } else {
                 $error = '用户名或密码不正确。';
            }

            $stmt->close();
        } else {
            $error = '登录时发生数据库错误，请联系管理员。';
            error_log("Login statement prepare failed: " . $conn->error);
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 授权后台管理系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-sm">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="flex flex-col items-center mb-6">
                <div class="bg-blue-100 p-3 rounded-full mb-3">
                    <i data-lucide="shield-check" class="w-8 h-8 text-blue-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">授权系统后台</h1>
                <p class="text-gray-500 text-sm mt-1">请登录您的管理员账户</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 text-sm" role="alert">
                <span><?php echo escape_html($error); ?></span>
            </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">用户名</label>
                    <div class="mt-1 relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                        </span>
                        <input type="text" id="username" name="username" class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" placeholder="请输入用户名" required>
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">密码</label>
                     <div class="mt-1 relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                        </span>
                        <input type="password" id="password" name="password" class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" placeholder="请输入密码" required>
                    </div>
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" <?php if (isset($_SESSION['lockout_until']) && time() < $_SESSION['lockout_until']) echo 'disabled'; ?>>
                        安全登录
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>


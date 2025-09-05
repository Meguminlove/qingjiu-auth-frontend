<?php
// user/login.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务-保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend
require_once 'functions.php';
global $conn;

if (is_user_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = '邮箱和密码不能为空。';
    } else {
        $stmt = $conn->prepare("SELECT id, email, nickname, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_info'] = $user;
                
                $update_stmt = $conn->prepare("UPDATE users SET last_login_time = NOW(), last_login_ip = ? WHERE id = ?");
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $update_stmt->bind_param("si", $ip_address, $user['id']);
                $update_stmt->execute();
                $update_stmt->close();

                header('Location: index.php');
                exit;
            } else {
                $error = '邮箱或密码不正确。';
            }
        } else {
            $error = '邮箱或密码不正确。';
        }
        $stmt->close();
    }
}

$page_title = '用户登录';
require_once __DIR__ .'/../header.php'; 
?>
<div class="bg-white rounded-lg shadow-md p-6 sm:p-8 max-w-md mx-auto">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">用户登录</h2>
    <?php if ($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    <form action="login.php" method="POST" class="space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">邮箱</label>
            <input type="email" name="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">密码</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
        </div>
        <div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 text-white bg-blue-600 hover:bg-blue-700 rounded-lg">登录</button>
        </div>
    </form>
    
    <!-- [FINAL FIX] 统一链接颜色 -->
    <div class="mt-6 flex justify-between items-center text-sm">
        <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500">
            忘记密码
        </a>
        <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
            立即注册
        </a>
    </div>
</div>
<?php require_once __DIR__ .'/../footer.php'; ?>
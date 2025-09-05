<?php
// update.php (模块化更新 - 安全加固版)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

require_once 'bootstrap.php';
// [安全修复] 必须引入后台函数库以使用登录验证
require_once 'admin/functions.php';
// [安全修复] 必须要求管理员登录后才能访问此页面
require_login();

global $settings, $conn, $db_connection_error;

$update_error = '';
$update_success = '';
$current_db_version = (int)($settings['db_version'] ?? 0);
$updates_dir = __DIR__ . '/updates/';
$update_files = [];
$latest_version = $current_db_version;
$pending_updates = false;

if (is_dir($updates_dir)) {
    $update_files = glob($updates_dir . '*.sql');
    if ($update_files) {
        sort($update_files, SORT_NATURAL);
        $last_file = end($update_files);
        $latest_version = (int)basename($last_file, '.sql');
    }
}

$pending_updates = $latest_version > $current_db_version;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_update'])) {
    if (!$pending_updates) {
        $update_error = '没有检测到需要执行的更新。';
    } elseif (!$conn || !empty($db_connection_error)) {
        $update_error = '数据库连接失败，无法继续更新。请检查 config.php 配置。';
    } else {
        // [安全修复] 移除了密码验证逻辑，因为已通过 require_login() 验证了管理员身份

        // 开始执行更新
        $conn->begin_transaction();
        $all_updates_successful = true;

        for ($i = $current_db_version + 1; $i <= $latest_version; $i++) {
            $update_file_path = $updates_dir . $i . '.sql';
            if (file_exists($update_file_path)) {
                $sql_script = file_get_contents($update_file_path);
                if ($sql_script && $conn->multi_query($sql_script)) {
                    while ($conn->more_results() && $conn->next_result()) {;}
                    if (!@unlink($update_file_path)) {
                        $update_error .= "警告：无法自动删除更新脚本 '{$i}.sql'，请手动删除。<br>";
                    }
                } else {
                    $update_error = "执行更新脚本 '{$i}.sql' 失败: " . htmlspecialchars($conn->error);
                    $all_updates_successful = false;
                    break; 
                }
            }
        }

        if ($all_updates_successful) {
            $stmt_version = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'db_version'");
            $stmt_version->bind_param('s', $latest_version);
            if ($stmt_version->execute()) {
                $conn->commit();
                $update_success = '数据库已成功更新到最新版本！页面将在3秒后自动刷新。';
                header("Refresh:3; url=index.php"); // 更新后跳转到首页
            } else {
                $conn->rollback();
                $update_error = '更新数据库版本号失败: ' . htmlspecialchars($conn->error);
            }
            $stmt_version->close();
        } else {
            $conn->rollback();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>数据库更新 - <?php echo htmlspecialchars($settings['site_name'] ?? '授权系统'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-8">
        <div class="flex items-center space-x-3 mb-6">
            <i data-lucide="database-zap" class="w-8 h-8 text-blue-600"></i>
            <h1 class="text-2xl font-bold text-gray-800">数据库更新程序</h1>
        </div>

        <?php if ($update_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                <strong class="font-bold">发生错误!</strong>
                <span class="block sm:inline"><?php echo $update_error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($update_success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
                <strong class="font-bold">更新成功!</strong>
                <span class="block sm:inline"><?php echo $update_success; ?></span>
            </div>
        <?php else: ?>
            <?php if ($pending_updates): ?>
                <form action="update.php" method="POST">
                    <div class="text-gray-700 space-y-4">
                        <p>系统检测到您的数据库版本 (v<?php echo $current_db_version; ?>) 低于当前程序要求的最新版本 (v<?php echo $latest_version; ?>)。</p>
                        <p class="font-semibold">为了确保所有功能正常工作，需要对数据库进行升级。</p>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>重要提示:</strong> 更新过程不会删除您现有的数据。但在开始之前，强烈建议您手动备份一次数据库以防万一。
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <button type="submit" name="start_update" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg text-lg focus:outline-none focus:shadow-outline transition-colors">
                            立即更新数据库
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center text-green-700">
                    <i data-lucide="check-check" class="w-12 h-12 mx-auto mb-4"></i>
                    <p class="text-lg font-semibold">您的数据库已经是最新版本，无需更新！</p>
                    <a href="index.php" class="inline-block mt-4 text-sm text-blue-600 hover:underline">返回首页 &rarr;</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>



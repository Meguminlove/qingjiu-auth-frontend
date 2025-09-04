<?php
// bootstrap.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

// --- 全局初始化脚本 ---

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 定义全局变量
$is_installed = false;
$settings = [];
$conn = null;
$bootstrap_error = '';

$config_path = __DIR__ . '/config.php';

// --- 步骤 1: 检查安装状态 ---
if (!file_exists($config_path)) {
    // 配置文件不存在，系统未安装。
    // 让各个页面自行处理此状态，通常是显示“未安装”信息。
    $is_installed = false;
} else {
    // --- 步骤 2: 加载配置并连接数据库 ---
    require_once $config_path;
    
    // 错误报告：在连接时临时打开，以便捕获错误
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        $is_installed = true;
    } catch (mysqli_sql_exception $e) {
        // 数据库连接失败是一个致命错误，必须立即停止并给出明确指示。
        // 这是导致之前“系统未安装”误报的根本原因。
        header('Content-Type: text/html; charset=utf-8');
        die('<!DOCTYPE html><html lang="zh-CN"><head><title>数据库连接错误</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 flex items-center justify-center h-screen"><div class="text-center p-4 bg-white shadow-md rounded-lg max-w-lg"><h1 class="text-2xl font-bold text-red-600">数据库连接失败！</h1><p class="text-gray-700 mt-4">系统无法连接到数据库。这通常意味着 <strong>config.php</strong> 文件中的数据库信息不正确。</p><p class="text-gray-600 mt-2">请检查以下几点：</p><ul class="text-left list-disc list-inside bg-gray-50 p-4 rounded-md mt-2 text-sm"><li>数据库地址 (DB_HOST) 是否正确？</li><li>数据库用户名 (DB_USER) 是否正确？</li><li>数据库密码 (DB_PASS) 是否正确？</li><li>数据库名称 (DB_NAME) 是否存在？</li><li>数据库服务是否正在运行？</li></ul><p class="mt-4 text-xs text-gray-500">错误详情: ' . htmlspecialchars($e->getMessage()) . '</p></div></body></html>');
    }
    // 恢复默认错误报告
    mysqli_report(MYSQLI_REPORT_OFF);

    // --- 步骤 3: 从数据库加载设置 ---
    if ($is_installed && $conn) {
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            // 检查数据库是否为空 (settings表没有内容)
            if (empty($settings) && basename($_SERVER['PHP_SELF']) !== 'install.php') {
                 header('Content-Type: text/html; charset=utf-8');
                 die('<!DOCTYPE html><html lang="zh-CN"><head><title>数据库错误</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-100 flex items-center justify-center h-screen"><div class="text-center p-4 bg-white shadow-md rounded-lg max-w-lg"><h1 class="text-2xl font-bold text-red-600">数据库为空！</h1><p class="text-gray-700 mt-4">系统已连接到数据库，但未找到任何配置数据。</p><p class="text-gray-600 mt-2">这可能是因为数据库被清空。请删除根目录下的 <strong>config.php</strong> 和 <strong>updates</strong> 文件夹，然后重新上传并运行 <strong>install.php</strong> 来重置系统。</p></div></body></html>');
            }
        } else {
             // settings 表不存在
             $is_installed = false; 
             $bootstrap_error = '无法从数据库读取配置，可能数据表不完整。';
        }
    }
    
    // --- 步骤 4: 检查数据库版本更新 ---
    if ($is_installed && $conn && basename($_SERVER['PHP_SELF']) !== 'update.php') {
        $update_script_path = __DIR__ . '/updates/';
        if (is_dir($update_script_path)) {
            $current_db_version = (int)($settings['db_version'] ?? 1);
            $update_files = glob($update_script_path . '*.sql');
            
            if (!empty($update_files)) {
                $latest_version_in_files = 0;
                foreach ($update_files as $file) {
                    $version = (int)basename($file, '.sql');
                    if ($version > $latest_version_in_files) {
                        $latest_version_in_files = $version;
                    }
                }
                
                if ($latest_version_in_files > $current_db_version) {
                    header('Location: update.php');
                    exit;
                }
            }
        }
    }
}
?>


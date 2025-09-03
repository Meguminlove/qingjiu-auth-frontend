<?php
// api/send_recovery_code.php (REVISED FOR NEW FLOW)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

session_start();
header('Content-Type: application/json; charset=utf-8');

// --- Load Dependencies ---
if (!file_exists(__DIR__ . '/../config.php')) {
    echo json_encode(['success' => false, 'message' => '系统错误：配置文件丢失。']);
    exit;
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/mailer_functions.php';

// --- Helper function to get settings from DB ---
function get_all_db_settings() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) return [];
    $conn->set_charset('utf8mb4');
    $settings = [];
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    $conn->close();
    return $settings;
}

$settings = get_all_db_settings();
$api_base_url = $settings['api_url'] ?? '';
$api_key = $settings['api_key'] ?? '';
$product_id = $settings['query_product_id'] ?? '1';

if (empty($api_base_url) || empty($api_key)) {
     echo json_encode(['success' => false, 'message' => '系统API未配置，无法提供服务。']);
    exit;
}

$action = $_POST['action'] ?? '';

// --- ACTION: verify_identity ---
if ($action === 'verify_identity') {
    $domain = trim($_POST['auth_domain'] ?? '');
    $card_key = trim($_POST['card_key'] ?? '');

    if (empty($domain) || empty($card_key)) {
        echo json_encode(['success' => false, 'message' => '域名和卡密均不能为空。']);
        exit;
    }

    $target_url = rtrim($api_base_url, '/') . '/authorizations/verify?domain=' . urlencode($domain) . '&product_id=' . urlencode($product_id);
    
    $ch = curl_init($target_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key, 'Accept: application/json']);
    $response_body = curl_exec($ch);
    curl_close($ch);
    
    $response = json_decode($response_body, true);

    if (isset($response['code']) && $response['code'] === 200) {
        if (isset($response['data']['card_key']) && $response['data']['card_key'] === $card_key) {
            $_SESSION['recovery_license_key'] = $response['data']['license_key'] ?? '';
            $_SESSION['recovery_auth_email'] = $response['data']['auth_email'] ?? '';
            $_SESSION['recovery_product_name'] = $response['data']['product_name'] ?? '未知产品';
            $_SESSION['recovery_step_2_allowed'] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '卡密与该域名授权不匹配。']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => $response['message'] ?? '域名未授权或查询失败。']);
    }
    exit;
}

// --- ACTION: find_key ---
if ($action === 'find_key') {
    if (!isset($_SESSION['recovery_step_2_allowed']) || $_SESSION['recovery_step_2_allowed'] !== true) {
        echo json_encode(['success' => false, 'message' => '请先完成第一步身份验证。']);
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => '请输入有效的邮箱地址。']);
        exit;
    }
    
    $auth_email = $_SESSION['recovery_auth_email'] ?? '';
    if (strtolower($email) !== strtolower($auth_email)) {
        echo json_encode(['success' => false, 'message' => '您输入的邮箱与授权记录中的邮箱不符。']);
        exit;
    }

    $license_key = $_SESSION['recovery_license_key'] ?? '';
    $product_name = $_SESSION['recovery_product_name'] ?? 'N/A';
    
    $_SESSION['recovery_step_3_allowed'] = true; // Allow sending email

    echo json_encode([
        'success' => true, 
        'data' => [
            'license_key' => $license_key,
            'product_name' => $product_name
        ]
    ]);
    exit;
}


// --- ACTION: send_email_copy ---
if ($action === 'send_email_copy') {
    if (!isset($_SESSION['recovery_step_3_allowed']) || $_SESSION['recovery_step_3_allowed'] !== true) {
        echo json_encode(['success' => false, 'message' => '无效操作，请刷新页面后重试。']);
        exit;
    }

    $email = $_SESSION['recovery_auth_email'] ?? '';
    $license_key = $_SESSION['recovery_license_key'] ?? '';

    if (empty($license_key) || empty($email)) {
        echo json_encode(['success' => false, 'message' => '会话数据已丢失，无法发送邮件，请刷新重试。']);
        exit;
    }

    $site_name = $settings['site_name'] ?? '授权系统';
    $subject = "【{$site_name}】您的授权密钥备份";
    $body = "您好，<br><br>这是您请求的授权密钥备份。请妥善保管。<br><br>您的授权密钥是：<b>{$license_key}</b><br><br>-- {$site_name} 团队";

    $send_result = send_smtp_email($settings, $email, $subject, $body);

    if ($send_result === true) {
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => '授权密钥已发送至您的邮箱。']);
    } else {
        // Don't destroy session on failure, so user can try again.
        echo json_encode(['success' => false, 'message' => '邮件发送失败: ' . $send_result]);
    }
    exit;
}

// Fallback for invalid action
echo json_encode(['success' => false, 'message' => '无效的操作。']);


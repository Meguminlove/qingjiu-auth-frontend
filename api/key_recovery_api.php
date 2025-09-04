<?php
// api/key_recovery_api.php (全新安全流程API - 修复版)
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

// [CRITICAL FIX]: Ensure bootstrap is loaded FIRST to get all configurations and DB connection.
// This was the root cause of the "network error" because this script was crashing.
require_once __DIR__ . '/../bootstrap.php';

// Now that bootstrap.php has run, we can safely use the global variables it provides.
global $settings, $conn, $db_connection_error;

// Load mailer functions, which depend on the settings loaded by bootstrap.
require_once __DIR__ . '/mailer_functions.php';

header('Content-Type: application/json; charset=utf-8');

// Check if the system is ready (DB connection successful)
if (!empty($db_connection_error)) {
    echo json_encode(['success' => false, 'message' => '系统错误: ' . $db_connection_error]);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => '无效的操作。'];

switch ($action) {
    // Step 1: Verify user info and send verification code
    case 'send_code':
        $domain = trim($_POST['auth_domain'] ?? '');
        $email = trim($_POST['auth_email'] ?? '');
        $product_id = (int)($settings['query_product_id'] ?? 1);

        if (empty($domain) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = '请输入有效的授权域名和邮箱。';
            break;
        }
        
        $stmt = $conn->prepare("SELECT license_key, auth_domain, auth_email FROM local_authorizations WHERE auth_domain = ? AND auth_email = ? AND product_id = ? LIMIT 1");
        $stmt->bind_param("ssi", $domain, $email, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $auth_data = $result->fetch_assoc();
            $code = random_int(100000, 999999);
            
            $_SESSION['recovery_code'] = $code;
            $_SESSION['recovery_code_expires'] = time() + 300; // 5 minutes validity
            $_SESSION['recovery_auth_data'] = $auth_data;

            // Send email with the code
            $site_name = $settings['site_name'] ?? '授权系统';
            $subject = "【{$site_name}】您的密钥找回验证码";
            $body = "您好，<br><br>您正在找回授权密钥，您的验证码是：<b>{$code}</b><br><br>该验证码将在5分钟后失效，请勿泄露给他人。<br><br>-- {$site_name} 团队";

            // This function now uses the correctly loaded settings
            $send_result = send_email($settings, $email, $subject, $body);

            if ($send_result === true) {
                $response = ['success' => true];
            } else {
                $response['message'] = '验证码邮件发送失败：' . htmlspecialchars($send_result) . '。请检查后台邮箱配置或联系管理员。';
            }
        } else {
            $response['message'] = '未找到匹配的授权记录，请确认信息是否正确。';
        }
        $stmt->close();
        break;

    // Step 2: Verify the code provided by the user
    case 'verify_code':
        $user_code = trim($_POST['verification_code'] ?? '');
        
        if (empty($user_code) || !isset($_SESSION['recovery_code']) || !isset($_SESSION['recovery_code_expires'])) {
            $response['message'] = '会话无效，请刷新页面后重试。';
            break;
        }
        if (time() > $_SESSION['recovery_code_expires']) {
            $response['message'] = '验证码已过期，请刷新页面重试。';
            unset($_SESSION['recovery_code'], $_SESSION['recovery_code_expires'], $_SESSION['recovery_auth_data']);
            break;
        }
        if ($user_code != $_SESSION['recovery_code']) {
            $response['message'] = '验证码不正确。';
            break;
        }

        // Verification successful
        $_SESSION['recovery_verified'] = true;
        $response = [
            'success' => true,
            'data' => $_SESSION['recovery_auth_data']
        ];
        break;
        
    // Step 3: Send a copy of the key to the user's email
    case 'send_copy':
        if (!isset($_SESSION['recovery_verified']) || $_SESSION['recovery_verified'] !== true) {
            $response['message'] = '请先完成验证码校验。';
            break;
        }
        if (!isset($_SESSION['recovery_auth_data'])) {
             $response['message'] = '会话数据丢失，无法发送邮件。';
             break;
        }
        
        $auth_data = $_SESSION['recovery_auth_data'];
        
        // Use the beautiful HTML email template
        $template_path = __DIR__ . '/mail/email_template.html';
        if (!file_exists($template_path)) {
            $response['message'] = '系统错误：邮件模板文件丢失。';
            break;
        }
        $html_body = file_get_contents($template_path);

        $site_name = htmlspecialchars($settings['site_name'] ?? '授权系统');
        // Product name is not stored locally, so we fetch it from the main API for a better email.
        $product_name = '授权产品'; // Default value
        $api_base_url = $settings['api_url'] ?? '';
        $api_key = $settings['api_key'] ?? '';
        if ($api_base_url && $api_key) {
            $product_id = (int)($settings['query_product_id'] ?? 1);
            $url = rtrim($api_base_url, '/') . '/public/products/' . $product_id;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $api_key]);
            $product_res = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if(isset($product_res['data']['name'])) {
                $product_name = $product_res['data']['name'];
            }
        }

        $license_key = htmlspecialchars($auth_data['license_key']);
        $current_date = date("Y-m-d H:i:s");

        // Replace placeholders
        $placeholders = [
            '{{site_name}}' => $site_name,
            '{{license_key}}' => $license_key,
            '{{product_name}}' => htmlspecialchars($product_name),
            '{{activation_time}}' => $current_date, 
            '{{expiry_time}}' => 'N/A (请以实际查询为准)',
            '{{card_type}}' => 'N/A'
        ];

        $final_body = str_replace(array_keys($placeholders), array_values($placeholders), $html_body);
        
        $subject = "【{$site_name}】您的授权密钥信息副本";
        $send_result = send_email($settings, $auth_data['auth_email'], $subject, $final_body);

        if ($send_result === true) {
            $response = ['success' => true, 'message' => '邮件副本已成功发送！'];
            // Clean up session after successful operation
            session_destroy();
        } else {
            $response['message'] = '邮件发送失败: ' . htmlspecialchars($send_result);
        }
        break;
}

echo json_encode($response);
?>


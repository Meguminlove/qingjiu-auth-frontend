<?php
// api/password_recovery_api.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/mailer_functions.php';

global $conn, $settings;
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => '无效的操作。'];

switch ($action) {
    // 步骤1: 发送验证码
    case 'send_code':
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = '请输入有效的邮箱地址。';
            break;
        }
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $code = random_int(100000, 999999);
            $_SESSION['recovery_code'] = $code;
            $_SESSION['recovery_email'] = $email;
            $_SESSION['recovery_code_expires'] = time() + 300; // 5分钟有效

            $site_name = $settings['site_name'] ?? '授权系统';
            $subject = "【{$site_name}】密码找回验证码";
            $body = "您的密码找回验证码是：<b>{$code}</b>，5分钟内有效。";
            $send_result = send_email($settings, $email, $subject, $body);

            if ($send_result === true) {
                $response = ['success' => true];
            } else {
                $response['message'] = '验证码邮件发送失败：' . htmlspecialchars($send_result);
            }
        } else {
            $response['message'] = '该邮箱地址未注册。';
        }
        $stmt->close();
        break;

    // 步骤2: 验证验证码
    case 'verify_code':
        $user_code = trim($_POST['verification_code'] ?? '');
        if (empty($user_code) || empty($_SESSION['recovery_code']) || time() > $_SESSION['recovery_code_expires']) {
            $response['message'] = '验证码错误或已过期。';
        } elseif ($user_code == $_SESSION['recovery_code']) {
            $_SESSION['recovery_verified'] = true; // 标记为已验证
            $response = ['success' => true];
        } else {
            $response['message'] = '验证码不正确。';
        }
        break;

    // 步骤3: 重置密码
    case 'reset_password':
        if (!isset($_SESSION['recovery_verified']) || $_SESSION['recovery_verified'] !== true || empty($_SESSION['recovery_email'])) {
            $response['message'] = '请先完成邮箱验证。';
            break;
        }
        $new_password = $_POST['new_password'] ?? '';
        if (strlen($new_password) < 6) {
            $response['message'] = '新密码长度至少需要6位。';
            break;
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $email = $_SESSION['recovery_email'];
        
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute()) {
            $response = ['success' => true];
            // 清理会话，销毁所有恢复过程中的session数据
            unset($_SESSION['recovery_code'], $_SESSION['recovery_email'], $_SESSION['recovery_code_expires'], $_SESSION['recovery_verified']);
        } else {
            $response['message'] = '密码重置失败，请联系管理员。';
        }
        $stmt->close();
        break;
}

echo json_encode($response);
?>

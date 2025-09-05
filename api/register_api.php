<?php
// api/register_api.php
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
            $response['message'] = '该邮箱已被注册，请直接登录或找回密码。';
        } else {
            $code = random_int(100000, 999999);
            $_SESSION['register_code'] = $code;
            $_SESSION['register_email'] = $email;
            $_SESSION['register_code_expires'] = time() + 300; // 5分钟有效

            $site_name = $settings['site_name'] ?? '授权系统';
            $subject = "【{$site_name}】您的注册验证码";
            $body = "您的注册验证码是：<b>{$code}</b>，5分钟内有效。";
            $send_result = send_email($settings, $email, $subject, $body);

            if ($send_result === true) {
                $response = ['success' => true];
            } else {
                $response['message'] = '验证码邮件发送失败：' . htmlspecialchars($send_result);
            }
        }
        $stmt->close();
        break;

    // 步骤2: 验证验证码
    case 'verify_code':
        $user_code = trim($_POST['verification_code'] ?? '');
        if (empty($user_code) || empty($_SESSION['register_code']) || time() > $_SESSION['register_code_expires']) {
            $response['message'] = '验证码错误或已过期。';
        } elseif ($user_code == $_SESSION['register_code']) {
            $_SESSION['register_verified'] = true; // 标记为已验证
            $response = ['success' => true];
        } else {
            $response['message'] = '验证码不正确。';
        }
        break;

    // 步骤3: 创建账户
    case 'create_account':
        if (!isset($_SESSION['register_verified']) || $_SESSION['register_verified'] !== true || empty($_SESSION['register_email'])) {
            $response['message'] = '请先完成邮箱验证。';
            break;
        }
        $nickname = trim($_POST['nickname'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($nickname) || strlen($password) < 6) {
            $response['message'] = '昵称不能为空，且密码至少需要6位。';
            break;
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $email = $_SESSION['register_email'];

        $stmt = $conn->prepare("INSERT INTO users (nickname, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nickname, $email, $hashed_password);
        if ($stmt->execute()) {
            $response = ['success' => true];
            // 注册成功后清理会话
            unset($_SESSION['register_code'], $_SESSION['register_email'], $_SESSION['register_code_expires'], $_SESSION['register_verified']);
        } else {
            $response['message'] = '创建账户失败，请联系管理员。';
        }
        $stmt->close();
        break;
}

echo json_encode($response);
?>

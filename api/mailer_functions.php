<?php
// api/mailer_functions.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

require_once __DIR__ . '/phpmailer_library.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
// [FIXED] 移除了此处的 'use PHPMailer\PHPMailer\POP3;' 声明，因为它会导致在文件不存在时发生致命错误。

/**
 * 通用邮件发送函数，支持常规SMTP和POP-before-SMTP.
 *
 * @param array $settings 系统设置数组，包含邮件配置.
 * @param string $to 收件人邮箱.
 * @param string $subject 邮件主题.
 * @param string $body 邮件内容 (HTML).
 * @return bool|string 发送成功返回 true，失败返回错误信息字符串.
 */
function send_email($settings, $to, $subject, $body) {
    // POP-before-SMTP 认证逻辑
    if (($settings['smtp_auth_method'] ?? 'smtp') === 'pop-before-smtp') {
        // [FIXED] 使用完全限定类名进行运行时检查，避免编译时错误。
        if (!class_exists(\PHPMailer\PHPMailer\POP3::class)) {
             return "错误: POP3 功能所需文件 (POP3.php) 未找到。";
        }
        
        $pop = new \PHPMailer\PHPMailer\POP3();
        // 设置为0以禁止调试信息直接输出
        $pop->do_debug = 0; 

        $pop3_host = $settings['pop3_host'] ?? $settings['smtp_host'] ?? '';
        $pop3_port = $settings['pop3_port'] ?? 110;

        if (empty($pop3_host)) {
            return "POP3 主机未配置。";
        }

        if (!$pop->authorise($pop3_host, (int)$pop3_port, 30, $settings['smtp_user'] ?? '', $settings['smtp_pass'] ?? '')) {
            $pop_errors = $pop->getErrors();
            $last_error = end($pop_errors);
            return "POP-before-SMTP 认证失败: " . ($last_error ? htmlspecialchars($last_error) : '未知POP3错误');
        }
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        // 若需调试，请取消下一行注释
        // $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER; 
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_user'] ?? '';
        $mail->Password   = $settings['smtp_pass'] ?? '';
        $mail->SMTPSecure = $settings['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($settings['smtp_port'] ?? 587);
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;

        // Recipients
        $from_email = $settings['smtp_from_email'] ?? ($settings['smtp_user'] ?? '');
        $from_name = $settings['smtp_from_name'] ?? '授权系统';
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($from_email, $from_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        // 返回详细的错误信息
        return "邮件发送失败。错误详情: {$mail->ErrorInfo}";
    }
}

